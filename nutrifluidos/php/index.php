<?php
// ==========================================
// IMPORTANTE: NO debe haber NADA antes de esta línea
// Ni espacios, ni comentarios, ni saltos de línea
// ==========================================

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// DESACTIVAR errores en producción (CRÍTICO)
ini_set('display_errors', 0);
error_reporting(0);

// Configurar headers PRIMERO antes de cualquier otra cosa
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
// SEGURIDAD: se eliminó 'Access-Control-Allow-Origin: *' (el login solo se usa desde este mismo sitio)

// Función para enviar respuesta JSON
function enviarRespuesta($status, $message, $data = null) {
    // Limpiar COMPLETAMENTE el buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit(0);
}

// Iniciar sesión DESPUÉS de los headers (cookies seguras)
require_once __DIR__ . '/auth.php';
nf_iniciarSesion();

// Incluir conexión
require_once __DIR__ . '/../../../privado/conexion.php';

// Verificar conexión a la base de datos
if (!isset($conexion) || $conexion->connect_error) {
    enviarRespuesta('error', 'Error de conexión a la base de datos');
}

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarRespuesta('error', 'Método no permitido');
    }

    // Servicio suspendido por falta de pago (28-10-2026): no se permite iniciar sesión
    if (nf_estadoServicio()['suspendido']) {
        enviarRespuesta('error', nf_mensajeSuspension(), ['servicio_suspendido' => true]);
    }

    // Obtener datos JSON
    $jsonData = file_get_contents('php://input');
    
    if (empty($jsonData)) {
        enviarRespuesta('error', 'No se recibieron datos');
    }

    $data = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        enviarRespuesta('error', 'Formato de datos inválido');
    }

    // Validar campos
    if (!isset($data['username']) || !isset($data['password'])) {
        enviarRespuesta('error', 'Faltan campos obligatorios');
    }

    $username = trim($data['username']);
    $password = trim($data['password']);

    // Validación básica
    if (empty($username) || empty($password)) {
        enviarRespuesta('error', 'Usuario y contraseña son requeridos');
    }

    // SEGURIDAD (16-09-2026): bloqueo por intentos fallidos
    $bloqueo = nf_login_bloqueo_restante($conexion, $username);
    if ($bloqueo > 0) {
        enviarRespuesta('error', 'Demasiados intentos fallidos. Intente de nuevo en ' .
            ceil($bloqueo / 60) . ' minuto(s).');
    }

    // Sanitizar
    $username = $conexion->real_escape_string($username);
    
    // Consulta SQL
    $sql = "SELECT id, nombre_completo, usuario, contrasena, rol, estado, correo_electronico, telefono 
            FROM usuarios 
            WHERE usuario = ? AND estado = 1 
            LIMIT 1";
    
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        enviarRespuesta('error', 'Error en la preparación de la consulta');
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        nf_login_fallo($conexion, $username);
        enviarRespuesta('error', 'Credenciales incorrectas');
    }

    $usuario = $resultado->fetch_assoc();

    // Verificar contraseña (SEGURIDAD: cifrada con password_hash)
    $guardada = (string)$usuario['contrasena'];
    $info = password_get_info($guardada);
    $esHash = !empty($info['algo']);

    if ($esHash) {
        $valida = password_verify($password, $guardada);
    } else {
        // Contraseña antigua en texto plano: se compara una sola vez y se migra
        $valida = hash_equals($guardada, $password);
    }

    if (!$valida) {
        nf_login_fallo($conexion, $username);
        usleep(random_int(200000, 500000)); // frena ataques de fuerza bruta
        enviarRespuesta('error', 'Credenciales incorrectas');
    }

    // Login válido: limpiar contador de intentos
    nf_login_exito($conexion, $username);

    // Migrar a hash (primer login) o actualizar el algoritmo si cambió
    if (!$esHash || password_needs_rehash($guardada, PASSWORD_DEFAULT)) {
        $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
        $stmtHash = $conexion->prepare("UPDATE usuarios SET contrasena = ? WHERE id = ?");
        if ($stmtHash) {
            $stmtHash->bind_param("si", $nuevoHash, $usuario['id']);
            $stmtHash->execute();
            $stmtHash->close();
        }
    }

    // Nueva sesión (evita fijación de sesión) y datos oficiales del usuario
    session_regenerate_id(true);
    $_SESSION = [];
    $_SESSION['usuario_id'] = (int)$usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
    $_SESSION['rol'] = strtolower($usuario['rol']);
    $_SESSION['nombre'] = $usuario['nombre_completo'];
    $_SESSION['usuario'] = $usuario['usuario'];
    $_SESSION['loggedin'] = true;
    $_SESSION['ultima_actividad'] = time();

    // Determinar permisos
    $rolLower = strtolower($usuario['rol']);
    $esAdmin = $rolLower === 'administrador';
    $esVendedor = $rolLower === 'vendedor';
    $esBodeguero = $rolLower === 'bodeguero';

    $response = [
        'usuario' => [
            'id' => $usuario['id'],
            'nombre' => $usuario['nombre_completo'],
            'usuario' => $usuario['usuario'],
            'correo_electronico' => $usuario['correo_electronico'],
            'telefono' => $usuario['telefono'],
            'rol' => $usuario['rol'],
            'permisos' => [
                'usuarios' => $esAdmin,
                'ventas' => $esAdmin || $esVendedor || $esBodeguero,
                'productos' => $esAdmin,
                'clientes' => $esAdmin || $esVendedor || $esBodeguero,
                'proveedores' => $esAdmin,
                'informes' => $esAdmin,
                'pedidos' => $esAdmin || $esVendedor || $esBodeguero,
                'abonos' => $esAdmin 
            ]
        ]
    ];

    $stmt->close();
    $conexion->close();
    
    enviarRespuesta('success', 'Inicio de sesión exitoso', $response);

} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    enviarRespuesta('error', 'Error en el servidor');
}
?>