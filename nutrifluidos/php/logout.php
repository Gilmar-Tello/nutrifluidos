<?php
// NO debe haber nada antes de esta línea
if (ob_get_level()) {
    ob_end_clean();
}

// SEGURIDAD (16-09-2026): cierre de sesión con las mismas cookies seguras del login
require_once __DIR__ . '/auth.php';
cerrarSesionServidor();

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirigir
header('Location: /index.php');
exit();
