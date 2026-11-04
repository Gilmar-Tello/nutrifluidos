<?php
/**
 * archivo.php — Entrega archivos privados SOLO a usuarios con sesión.
 * (SEGURIDAD, 16-09-2026). Antes estos PDF/imágenes se podían bajar por URL directa.
 *
 *   php/archivo.php?tipo=<clave>&f=<nombre.ext>
 *
 * 'f' se reduce a basename y se valida (sin ../). Cada 'tipo' apunta a una
 * carpeta fija. Las carpetas se protegen además con .htaccess (Require all denied),
 * así que la única vía de lectura es este script autenticado.
 */
require_once __DIR__ . '/auth.php';
requerirSesion(); // cualquier usuario con sesión; los reportes de admin ya se filtran en su acción

$TIPOS = [
    'comprobante_pago' => __DIR__ . '/comprobantes_pdf',   // recibos de abono
    'reporte'          => __DIR__ . '/reportes_pdf',        // reportes de abonos
    'factura_venta'    => __DIR__ . '/facturas',            // comprobantes de venta
    'informe'          => __DIR__ . '/../informes',         // informes por cliente
    'entrega'          => __DIR__ . '/uploads/comprobantes', // fotos de entrega
    'comprobante_subido' => __DIR__ . '/comprobantes',     // comprobantes de pago subidos
    'factura_prov'     => __DIR__ . '/../imagenes/facturas_pagar',
    'abono_prov'       => __DIR__ . '/../imagenes/abonos_facturas',
];

$tipo = $_GET['tipo'] ?? '';
$f    = basename((string)($_GET['f'] ?? ''));

if (!isset($TIPOS[$tipo]) || $f === '' || !preg_match('/^[A-Za-z0-9._()-]+$/', $f)) {
    http_response_code(400);
    exit('Solicitud no válida');
}

$base = realpath($TIPOS[$tipo]);
$ruta = $base ? realpath($base . '/' . $f) : false;

// El archivo debe existir y estar dentro de la carpeta permitida
if (!$ruta || strpos($ruta, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($ruta)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

$mimes = [
    'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'avif' => 'image/avif',
];
$ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
if (!isset($mimes[$ext])) {
    http_response_code(415);
    exit('Tipo de archivo no permitido');
}

header('Content-Type: ' . $mimes[$ext]);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="' . $f . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
readfile($ruta);
exit;
