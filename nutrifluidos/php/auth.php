<?php
/**
 * auth.php — Control de sesión y permisos (SEGURIDAD, 16-09-2026)
 *
 * Uso al inicio de cada endpoint:
 *     require_once __DIR__ . '/auth.php';
 *     requerirSesion();                     // cualquier usuario con sesión
 *     requerirSesion(['administrador']);    // solo esos roles
 *
 * La identidad (id, rol, nombre) SOLO sale de la sesión que crea el login
 * (php/index.php). Cualquier 'usuario_id', 'rol', 'idVendedor',
 * 'nombre_usuario' o 'usuario_abono' que mande el navegador se reemplaza por
 * los valores de la sesión, así el código existente que los lee sigue
 * funcionando pero ya no se puede falsificar.
 */

if (!defined('NF_AUTH_CARGADO')) {
    define('NF_AUTH_CARGADO', true);

    // Duración máxima de inactividad (segundos)
    define('NF_SESION_INACTIVIDAD', 8 * 60 * 60);

    /** Inicia la sesión con cookies seguras (una sola vez). */
    function nf_iniciarSesion()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $https,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $https, true);
        }
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    /** Devuelve el usuario de la sesión o null si no hay sesión válida. */
    function usuarioSesion()
    {
        nf_iniciarSesion();

        if (empty($_SESSION['usuario_id']) || empty($_SESSION['loggedin']) || empty($_SESSION['rol'])) {
            return null;
        }

        // Expirar por inactividad
        $ahora = time();
        if (isset($_SESSION['ultima_actividad']) && ($ahora - $_SESSION['ultima_actividad']) > NF_SESION_INACTIVIDAD) {
            cerrarSesionServidor();
            return null;
        }
        $_SESSION['ultima_actividad'] = $ahora;

        return [
            'id'      => (int)$_SESSION['usuario_id'],
            'rol'     => (string)$_SESSION['rol'],
            'nombre'  => (string)($_SESSION['nombre'] ?? ''),
            'usuario' => (string)($_SESSION['usuario'] ?? ''),
        ];
    }

    /** Destruye la sesión actual. */
    function cerrarSesionServidor()
    {
        nf_iniciarSesion();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ==== Servicio mensual: suspensión por falta de pago (28-10-2026) ====
    // El estado vive en la tabla servicio_estado (UNA fila) y lo cambia SOLO el
    // panel maestro de SOLTEC. Aquí únicamente se lee.
    define('NF_AVISO_DIAS_ANTES', 3);                         // desde cuántos días antes se avisa
    define('NF_CONTACTO_SOPORTE', 'SOLTEC: 5492-5395 / 4256-1960');

    /**
     * Lee el estado del servicio. Devuelve:
     *   suspendido (bool), dias (int|null: días que faltan para el pago; negativo = vencido), motivo.
     * Falla en abierto: si la tabla no existe o la BD no responde NO se bloquea a nadie
     * (un error técnico no debe cortarle el negocio al cliente); se deja constancia en el log.
     * Los días los calcula MySQL con DATEDIFF sobre fechas (sin horas) y con la zona
     * horaria de Guatemala que fija conexion.php: la fecha de pago vale hasta las 23:59.
     */
    function nf_estadoServicio()
    {
        static $estado = null;
        if ($estado !== null) {
            return $estado;
        }
        $estado = ['suspendido' => false, 'dias' => null, 'motivo' => ''];
        try {
            // Mismas variables globales que crea conexion.php, para que el endpoint
            // que se ejecuta después encuentre $conexion ya lista.
            global $conexion, $host, $usuario, $contrasena, $base_de_datos;
            if (!($conexion instanceof mysqli)) {
                require_once __DIR__ . '/../../../privado/conexion.php';
            }
            $r = $conexion->query(
                "SELECT estado, motivo, DATEDIFF(fecha_pago, CURDATE()) AS dias
                   FROM servicio_estado WHERE id = 1 LIMIT 1"
            );
            $f = $r ? $r->fetch_assoc() : null;
            if ($f) {
                $estado['suspendido'] = ((int)$f['estado'] === 0);
                $estado['dias']       = ($f['dias'] === null) ? null : (int)$f['dias'];
                $estado['motivo']     = (string)($f['motivo'] ?? '');
            }
        } catch (Throwable $e) {
            error_log('[NutriFluidos] No se pudo leer servicio_estado (se deja trabajar): ' . $e->getMessage());
        }
        return $estado;
    }

    function nf_plural($n, $uno, $varios)
    {
        return $n . ' ' . ($n === 1 ? $uno : $varios);
    }

    function nf_mensajeSuspension()
    {
        return 'El servicio se encuentra suspendido por pago pendiente. Comuníquese con '
            . NF_CONTACTO_SOPORTE . ' para reactivarlo.';
    }

    /** Aviso de pago para mostrar en pantalla, o null si no corresponde. */
    function nf_avisoPago(array $svc)
    {
        $d = $svc['dias'];
        if ($d === null || $svc['suspendido'] || $d > NF_AVISO_DIAS_ANTES) {
            return null;
        }
        if ($d > 0) {
            return ['nivel' => 'aviso', 'dias' => $d,
                    'mensaje' => 'Faltan ' . nf_plural($d, 'día', 'días') . ' para su fecha de pago.'];
        }
        if ($d === 0) {
            return ['nivel' => 'aviso', 'dias' => 0, 'mensaje' => 'Su pago vence hoy.'];
        }
        $v = abs($d);
        return ['nivel' => 'vencido', 'dias' => $d,
                'mensaje' => 'Su pago está pendiente: venció hace ' . nf_plural($v, 'día', 'días')
                    . '. Comuníquese con ' . NF_CONTACTO_SOPORTE . ' para evitar la suspensión del servicio.'];
    }

    /** Responde un error JSON y termina. */
    function nf_denegar($codigoHttp, $mensaje, array $extra = [])
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($codigoHttp);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(array_merge([
            'success'         => false,
            'error'           => $mensaje,
            'message'         => $mensaje,
            'sesion_expirada' => $codigoHttp === 401,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Exige sesión activa (y opcionalmente uno de los roles indicados).
     * Además reemplaza los datos de identidad enviados por el navegador.
     */
    function requerirSesion(array $rolesPermitidos = [])
    {
        $u = usuarioSesion();
        if (!$u) {
            nf_denegar(401, 'Sesión no válida o expirada. Inicie sesión nuevamente.');
        }
        // Servicio suspendido por falta de pago: se cierra la puerta a todos, en cada petición
        if (nf_estadoServicio()['suspendido']) {
            nf_denegar(401, nf_mensajeSuspension(), ['servicio_suspendido' => true]);
        }
        if ($rolesPermitidos && !in_array($u['rol'], $rolesPermitidos, true)) {
            nf_denegar(403, 'No tiene permisos para realizar esta acción.');
        }

        // Forzar identidad desde la sesión en GET/POST/REQUEST
        foreach (['_GET', '_POST', '_REQUEST'] as $origen) {
            foreach (['usuario_id' => $u['id'], 'idVendedor' => $u['id'], 'rol' => $u['rol']] as $campo => $valor) {
                if (isset($GLOBALS[$origen][$campo])) {
                    $GLOBALS[$origen][$campo] = $valor;
                }
            }
            if (isset($GLOBALS[$origen]['nombre_usuario'])) {
                $GLOBALS[$origen]['nombre_usuario'] = $u['nombre'];
            }
            if (isset($GLOBALS[$origen]['usuario_abono'])) {
                $GLOBALS[$origen]['usuario_abono'] = $u['nombre'];
            }
        }
        $GLOBALS['NF_USUARIO'] = $u;
        return $u;
    }

    /** Usuario autenticado actual (llamar después de requerirSesion). */
    function usuarioActual()
    {
        return $GLOBALS['NF_USUARIO'] ?? usuarioSesion();
    }

    // ==== Límite de intentos de login (anti fuerza bruta) ====
    define('NF_LOGIN_MAX_INTENTOS', 5);       // intentos permitidos
    define('NF_LOGIN_BLOQUEO_SEG', 15 * 60);  // bloqueo de 15 minutos

    function nf_login_ip()
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
    }

    /**
     * Devuelve los segundos que faltan de bloqueo, o 0 si puede intentar.
     * Falla en abierto: si la tabla no existe, no bloquea (no rompe el login).
     */
    function nf_login_bloqueo_restante($conexion, $usuario)
    {
        try {
            $ip = nf_login_ip();
            $stmt = $conexion->prepare("SELECT intentos, bloqueado_hasta FROM intentos_login WHERE usuario = ? AND ip = ?");
            if (!$stmt) { return 0; }
            $stmt->bind_param('ss', $usuario, $ip);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($r && $r['bloqueado_hasta']) {
                $falta = strtotime($r['bloqueado_hasta']) - time();
                return $falta > 0 ? $falta : 0;
            }
        } catch (Throwable $e) { /* tabla ausente: no bloquear */ }
        return 0;
    }

    /** Registra un intento fallido y bloquea al llegar al máximo. */
    function nf_login_fallo($conexion, $usuario)
    {
        try {
            $ip = nf_login_ip();
            $stmt = $conexion->prepare("SELECT id, intentos FROM intentos_login WHERE usuario = ? AND ip = ?");
            if (!$stmt) { return; }
            $stmt->bind_param('ss', $usuario, $ip);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $intentos = ($r ? (int)$r['intentos'] : 0) + 1;
            $bloqueo = $intentos >= NF_LOGIN_MAX_INTENTOS
                ? date('Y-m-d H:i:s', time() + NF_LOGIN_BLOQUEO_SEG) : null;
            if ($r) {
                $stmt = $conexion->prepare("UPDATE intentos_login SET intentos = ?, bloqueado_hasta = ?, ultimo = NOW() WHERE id = ?");
                $stmt->bind_param('isi', $intentos, $bloqueo, $r['id']);
            } else {
                $stmt = $conexion->prepare("INSERT INTO intentos_login (usuario, ip, intentos, bloqueado_hasta, ultimo) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param('ssis', $usuario, $ip, $intentos, $bloqueo);
            }
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) { /* tabla ausente: ignorar */ }
    }

    /** Limpia los intentos tras un login exitoso. */
    function nf_login_exito($conexion, $usuario)
    {
        try {
            $ip = nf_login_ip();
            $stmt = $conexion->prepare("DELETE FROM intentos_login WHERE usuario = ? AND ip = ?");
            if (!$stmt) { return; }
            $stmt->bind_param('ss', $usuario, $ip);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $e) { /* ignorar */ }
    }

    /**
     * Lee el cuerpo JSON y reemplaza los campos de identidad por los de la sesión.
     * Usar en lugar de json_decode(file_get_contents('php://input'), true).
     */
    function leerJsonAutenticado()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            return $data;
        }
        $u = usuarioActual();
        if ($u) {
            // Siempre se fija la identidad desde la sesión
            $data['usuario_id'] = $u['id'];
            $data['idVendedor'] = $u['id'];
            $data['rol']        = $u['rol'];
            if (isset($data['credito']) && is_array($data['credito']) && array_key_exists('usuario_id', $data['credito'])) {
                $data['credito']['usuario_id'] = $u['id'];
            }
        }
        return $data;
    }
}
