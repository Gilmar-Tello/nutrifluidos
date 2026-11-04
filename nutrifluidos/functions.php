<?php
// =====================================================================
// SEGURIDAD (16-09-2026): las páginas del sistema exigen sesión del servidor.
// Este archivo lo incluyen todas las páginas de /nutrifluidos/ y también el
// login (/index.php), por eso solo se protege lo que está dentro de /nutrifluidos/.
// =====================================================================
require_once __DIR__ . '/php/auth.php';

(function () {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $pagina = basename($script);
    if (strpos($script, '/nutrifluidos/') === false || strpos($script, '/nutrifluidos/php/') !== false) {
        return; // login u otro script: no aplica
    }

    // Páginas solo para administrador (igual que los permisos del menú)
    $soloAdmin = ['usuarios.php', 'proveedores.php', 'productos.php', 'abonos.php',
                  'calculadora.php', 'informes-ventas.php', 'kardex.php'];

    $u = usuarioSesion();
    if (!$u) {
        header('Location: /index.php');
        exit;
    }
    if (in_array($pagina, $soloAdmin, true) && $u['rol'] !== 'administrador') {
        header('Location: /nutrifluidos/menu.php');
        exit;
    }
    header('Cache-Control: no-cache, no-store, must-revalidate, private');
})();
// Función para versioning de archivos CSS y JS
function getFileVersion($file) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/nutrifluidos/' . $file;
    if (file_exists($filePath)) {
        return '?v=' . filemtime($filePath);
    }
    return '?v=' . time();
}

// Función para versioning del index (fuera de nutrifluidos)
function getIndexFileVersion($file) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $file;
    if (file_exists($filePath)) {
        return '?v=' . filemtime($filePath);
    }
    return '?v=' . time();
}
?>