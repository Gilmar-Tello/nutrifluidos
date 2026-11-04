<?php
/**
 * subidas.php — Validación segura de archivos subidos (SEGURIDAD, 16-09-2026)
 *
 * guardarArchivoSubido($_FILES['campo'], $directorio, $tipos, $nombreBase)
 *  - Verifica el tipo REAL del archivo (contenido), no solo la extensión.
 *  - Solo permite los tipos indicados (imagen y/o pdf).
 *  - La extensión final la decide el servidor según el tipo real.
 *  - Límite de tamaño y nombre seguro.
 * Devuelve el nombre del archivo guardado (sin directorio) o lanza Exception.
 */

if (!defined('NF_SUBIDAS_CARGADO')) {
    define('NF_SUBIDAS_CARGADO', true);

    define('NF_MAX_SUBIDA_BYTES', 5 * 1024 * 1024); // 5 MB

    function nf_tiposPermitidos(array $grupos)
    {
        $mapa = [
            'imagen' => [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                'image/avif' => 'avif',
            ],
            'pdf' => [
                'application/pdf' => 'pdf',
            ],
        ];
        $permitidos = [];
        foreach ($grupos as $g) {
            $permitidos += $mapa[$g] ?? [];
        }
        return $permitidos;
    }

    function nf_mimeReal($rutaTmp)
    {
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($f, $rutaTmp);
            finfo_close($f);
            if ($mime) {
                return $mime;
            }
        }
        if (function_exists('mime_content_type')) {
            return mime_content_type($rutaTmp) ?: '';
        }
        $info = @getimagesize($rutaTmp);
        return $info['mime'] ?? '';
    }

    function guardarArchivoSubido($archivo, $directorio, array $grupos, $nombreBase = null)
    {
        if (!is_array($archivo) || !isset($archivo['error']) || is_array($archivo['error'])) {
            throw new Exception('Archivo no válido');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo (código ' . (int)$archivo['error'] . ')');
        }
        if (!is_uploaded_file($archivo['tmp_name'])) {
            throw new Exception('Archivo no válido');
        }
        if ($archivo['size'] <= 0 || $archivo['size'] > NF_MAX_SUBIDA_BYTES) {
            throw new Exception('El archivo debe pesar menos de 5 MB');
        }

        $permitidos = nf_tiposPermitidos($grupos);
        $mime = nf_mimeReal($archivo['tmp_name']);
        if (!isset($permitidos[$mime])) {
            $lista = strtoupper(implode(', ', array_unique(array_values($permitidos))));
            throw new Exception('Tipo de archivo no permitido. Solo se aceptan: ' . $lista);
        }

        // Las imágenes deben poder abrirse como imagen
        if (strpos($mime, 'image/') === 0 && $mime !== 'image/avif' && @getimagesize($archivo['tmp_name']) === false) {
            throw new Exception('La imagen está dañada o no es válida');
        }

        $extension = $permitidos[$mime];

        if ($nombreBase === null || $nombreBase === '') {
            $nombreBase = date('YmdHis') . '_' . bin2hex(random_bytes(6));
        }
        $nombreBase = preg_replace('/[^A-Za-z0-9_()-]/', '_', (string)$nombreBase);
        $nombreFinal = $nombreBase . '.' . $extension;

        $directorio = rtrim($directorio, '/\\') . '/';
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de destino');
        }
        protegerDirectorioSubidas($directorio);

        $destino = $directorio . $nombreFinal;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new Exception('No se pudo guardar el archivo');
        }
        @chmod($destino, 0644);

        return $nombreFinal;
    }

    /** Crea un .htaccess que impide ejecutar scripts dentro del directorio. */
    function protegerDirectorioSubidas($directorio)
    {
        $htaccess = rtrim($directorio, '/\\') . '/.htaccess';
        if (file_exists($htaccess)) {
            return;
        }
        @file_put_contents($htaccess, NF_HTACCESS_SUBIDAS);
    }

    define('NF_HTACCESS_SUBIDAS', <<<'HT'
# Carpeta de archivos subidos: prohibido ejecutar scripts
Options -Indexes
<FilesMatch "(?i)\.(php\d?|phtml|phar|pht|cgi|pl|py|sh|htaccess|htpasswd)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

HT
    );
}
