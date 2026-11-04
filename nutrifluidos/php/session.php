<?php
/**
 * session.php — Consulta y cierre de la sesión del servidor.
 *
 * SEGURIDAD (16-09-2026): se eliminó la posibilidad de que el navegador
 * escriba datos en la sesión. La acción 'sincronizar' ya NO guarda nada:
 * solo informa si la sesión (creada por el login) sigue activa.
 */
require_once __DIR__ . '/auth.php';

header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);
$accion = is_array($data) ? ($data['accion'] ?? '') : '';

switch ($accion) {
    case 'sincronizar': // compatibilidad con auth.js: ahora solo verifica
    case 'verificar':
    case 'obtener':
        responderEstadoSesion();
        break;

    case 'cerrar':
        cerrarSesionServidor();
        echo json_encode(['success' => true, 'mensaje' => 'Sesión cerrada correctamente']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}

function responderEstadoSesion()
{
    $u = usuarioSesion();
    if (!$u) {
        http_response_code(401);
        echo json_encode([
            'success'         => false,
            'sesion_activa'   => false,
            'sesion_expirada' => true,
            'error'           => 'No hay sesión activa',
            'mensaje'         => 'No hay sesión activa',
        ]);
        return;
    }

    // Servicio suspendido por falta de pago (28-10-2026)
    $svc = nf_estadoServicio();
    if ($svc['suspendido']) {
        http_response_code(401);
        echo json_encode([
            'success'             => false,
            'sesion_activa'       => false,
            'sesion_expirada'     => true,
            'servicio_suspendido' => true,
            'error'               => nf_mensajeSuspension(),
            'mensaje'             => nf_mensajeSuspension(),
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    echo json_encode([
        'success'       => true,
        'sesion_activa' => true,
        'mensaje'       => 'Sesión activa',
        'aviso_pago'    => nf_avisoPago($svc),
        'datos'         => [
            'usuario_id' => $u['id'],
            'rol'        => $u['rol'],
            'nombre'     => $u['nombre'],
            'usuario'    => $u['usuario'],
        ],
    ], JSON_UNESCAPED_UNICODE);
}
