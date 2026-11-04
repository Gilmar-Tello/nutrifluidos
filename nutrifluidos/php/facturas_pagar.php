<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);

require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala');



header('Content-Type: application/json; charset=utf-8');

function sanitizar($dato) {
    global $conexion;
    // SEGURIDAD (16-09-2026): quita < y > para que no se pueda guardar <script> ni otras
    // etiquetas (mitiga XSS almacenado en nombres de cliente, producto, etc.).
    $dato = str_replace(array('<', '>'), '', trim((string)$dato));
    return $conexion->real_escape_string($dato);
}

$accion = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = isset($_GET['accion']) ? sanitizar($_GET['accion']) : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? sanitizar($_POST['accion']) : '';
}

switch ($accion) {
    case 'crear':
        crearFactura();
        break;
    case 'listar':
        listarFacturas();
        break;
    case 'abonar':
        abonarFactura();
        break;
    case 'ver_comprobantes':
        ver_comprobantes();
        break;
    case 'editarFactura':
        editarFactura();
        break;

        default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}


function crearFactura() {
    global $conexion;

    // Sanitizar y preparar datos
    $proveedor_id   = sanitizar($_POST['proveedor_id']);
    $numeroFactura  = strtoupper(sanitizar($_POST['numeroFactura']));
    $montoTotal     = floatval(sanitizar($_POST['montoTotal']));
    $pagado         = floatval(sanitizar($_POST['pagado']));
    $fechaEmision   = sanitizar($_POST['fechaEmision']);
    $archivoNombre  = '';

    // Validación: pagado no debe ser mayor al monto total
    if ($pagado > $montoTotal) {
        echo json_encode(['success' => false, 'message' => 'El monto pagado no puede ser mayor al monto total.']);
        return;
    }

    // Calcular saldo pendiente
    $saldoPendiente = $montoTotal - $pagado;

    // Procesar archivo si fue cargado
    if (isset($_FILES['factura']) && $_FILES['factura']['error'] === UPLOAD_ERR_OK) {
        // SEGURIDAD (16-09-2026): solo imágenes o PDF, validados por contenido
        require_once __DIR__ . '/subidas.php';
        $numeroLimpio = preg_replace('/[^A-Za-z0-9]/', '_', $numeroFactura);
        try {
            $archivoNombre = guardarArchivoSubido($_FILES['factura'], 'imagenes/facturas_pagar/', ['imagen', 'pdf'], strtoupper(substr($numeroLimpio, 0, 20)));
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Archivo de factura no válido: ' . $e->getMessage()]);
            return;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Debe adjuntar una factura.']);
        return;
    }

// Insertar en la base de datos
    $query = "INSERT INTO facturas_por_pagar 
        (proveedor_id, numero_factura, monto_total, pagado, saldo_pendiente, fecha_emision, archivo, fecha_registro, estado) 
        VALUES 
        ('$proveedor_id', '$numeroFactura', '$montoTotal', '$pagado', '$saldoPendiente', '$fechaEmision', '$archivoNombre', NOW(), 1)";

    if ($conexion->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos: ' . $conexion->error]);
    }
}


function listarFacturas() {
    global $conexion;

    $sql = "SELECT 
                f.id,
                f.numero_factura,
                f.monto_total,
                f.pagado,
                f.saldo_pendiente,
                f.fecha_emision,
                f.archivo,
                f.estado,
                f.fecha_registro,
                p.empresa AS proveedor
            FROM facturas_por_pagar f
            LEFT JOIN proveedores p ON f.proveedor_id = p.id
            WHERE f.estado = 1 AND f.saldo_pendiente > 0
            ORDER BY f.id DESC";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        echo json_encode(['error' => 'Error al obtener facturas: ' . $conexion->error]);
        return;
    }

    $facturas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $facturas[] = [
            'id' => $fila['id'],
            'numero_factura' => strtoupper($fila['numero_factura']),
            'monto_total' => $fila['monto_total'],
            'pagado' => $fila['pagado'],
            'saldo_pendiente' => $fila['saldo_pendiente'],
            'fecha_emision' => $fila['fecha_emision'],
            'archivo' => $fila['archivo'],
            'estado' => strtoupper($fila['estado']),
            'fecha_registro' => $fila['fecha_registro'],
            'proveedor' => strtoupper($fila['proveedor'])
        ];
    }

    echo json_encode($facturas);
}

function abonarFactura() {
    global $conexion;

    // Asegurar que la respuesta será JSON
    header('Content-Type: application/json');

    $factura_id = intval($_POST['factura_id']);
    $monto_abono = floatval($_POST['monto_abono']);
    $fecha_subida = date('Y-m-d');

    // Obtener datos de la factura
    $consulta = $conexion->query("SELECT numero_factura, pagado, monto_total FROM facturas_por_pagar WHERE id = $factura_id");
    $factura = $consulta->fetch_assoc();
    if (!$factura) {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        return;
    }

    $numero_factura = strtoupper($factura['numero_factura']);
    $nuevo_pagado = $factura['pagado'] + $monto_abono;
    $saldo_pendiente = $factura['monto_total'] - $nuevo_pagado;

    // Generar correlativo
    $res = $conexion->query("SELECT COUNT(*) AS total FROM comprobantes_factura WHERE factura_id = $factura_id");
    $count = $res->fetch_assoc()['total'];
    $correlativo = $count + 1;
    $archivoNombre = $numero_factura . "($correlativo)";

    // Procesar archivo
    if (!isset($_FILES['archivo_abono']) || $_FILES['archivo_abono']['error'] !== 0) {
        echo json_encode(['success' => false, 'message' => 'Archivo no válido']);
        return;
    }

    // SEGURIDAD (16-09-2026): solo imágenes o PDF, validados por contenido
    require_once __DIR__ . '/subidas.php';
    try {
        $nombreFinal = guardarArchivoSubido($_FILES['archivo_abono'], 'imagenes/abonos_facturas/', ['imagen', 'pdf'], $archivoNombre);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Archivo no válido: ' . $e->getMessage()]);
        return;
    }
    $nombreFinal = $conexion->real_escape_string($nombreFinal);

    // Insertar comprobante
    $conexion->query("INSERT INTO comprobantes_factura (factura_id, comprobante, fecha_subida) VALUES ($factura_id, '$nombreFinal', '$fecha_subida')");

    // Actualizar factura
    $conexion->query("UPDATE facturas_por_pagar SET pagado = $nuevo_pagado, saldo_pendiente = $saldo_pendiente WHERE id = $factura_id");

    echo json_encode(['success' => true]);
}


function ver_comprobantes() {
    global $conexion;

    if (!isset($_GET['factura_id'])) {
        echo json_encode([]);
        return;
    }

    $factura_id = intval($_GET['factura_id']);

    $res = $conexion->query("SELECT comprobante, fecha_subida FROM comprobantes_factura WHERE factura_id = $factura_id ORDER BY fecha_subida ASC");

    $comprobantes = [];
    while ($row = $res->fetch_assoc()) {
        $comprobantes[] = $row;
    }

    echo json_encode($comprobantes);
    return;
}


function editarFactura() {
    global $conexion;

    // Obtener ID de factura
    $factura_id = intval($_POST['factura_id']);

    // Obtener factura actual
    $consulta = $conexion->query("SELECT * FROM facturas_por_pagar WHERE id = $factura_id");
    if (!$consulta || $consulta->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada.']);
        return;
    }
    $factura = $consulta->fetch_assoc();

    // Obtener abonos existentes
    $resAbonos = $conexion->query("SELECT COUNT(*) AS total FROM comprobantes_factura WHERE factura_id = $factura_id");
    $totalAbonos = $resAbonos->fetch_assoc()['total'];

    // Sanitizar datos nuevos
    $proveedor_id   = sanitizar($_POST['proveedor_id']);
    $numeroFactura  = strtoupper(sanitizar($_POST['numeroFactura']));
    $montoTotal     = floatval(sanitizar($_POST['montoTotal']));
    $pagado         = floatval(sanitizar($_POST['pagado']));
    $fechaEmision   = sanitizar($_POST['fechaEmision']);
    $archivoNombre  = $factura['archivo']; // Por defecto mantenemos el mismo archivo

    // Validación: pagado no debe ser mayor al monto
    if ($pagado > $montoTotal) {
        echo json_encode(['success' => false, 'message' => 'El monto pagado no puede ser mayor al monto total.']);
        return;
    }

    // Validación: si ya hay abonos, no se puede modificar el monto total
    if ($totalAbonos > 0 && $montoTotal != floatval($factura['monto_total'])) {
        echo json_encode(['success' => false, 'message' => 'No se puede modificar el monto total porque ya hay abonos registrados.']);
        return;
    }

    $saldoPendiente = $montoTotal - $pagado;

    // Verificar si hay un nuevo archivo
    $archivoEditado = false;
    if (isset($_FILES['factura']) && $_FILES['factura']['error'] === UPLOAD_ERR_OK) {
        // SEGURIDAD (16-09-2026): validación por contenido (ver guardarArchivoSubido)
        require_once __DIR__ . '/subidas.php';

        // Contar ediciones existentes
        $ediciones = 1;
        preg_match('/\(E(\d+)\)/', $archivoNombre, $match);
        if (isset($match[1])) {
            $ediciones = intval($match[1]) + 1;
        }

        $numeroLimpio = preg_replace('/[^A-Za-z0-9]/', '_', $numeroFactura);
        $baseNombre = strtoupper(substr($numeroLimpio, 0, 20));
        try {
            $archivoNombre = guardarArchivoSubido($_FILES['factura'], 'imagenes/facturas_pagar/', ['imagen', 'pdf'], $baseNombre . "(E$ediciones)");
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Archivo de factura no válido: ' . $e->getMessage()]);
            return;
        }

        $archivoEditado = true;
    }

    // Actualizar datos
    $query = "UPDATE facturas_por_pagar SET 
                proveedor_id = '$proveedor_id',
                numero_factura = '$numeroFactura',
                monto_total = '$montoTotal',
                pagado = '$pagado',
                saldo_pendiente = '$saldoPendiente',
                fecha_emision = '$fechaEmision'" .
                ($archivoEditado ? ", archivo = '$archivoNombre'" : '') .
            " WHERE id = $factura_id";

    if ($conexion->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar en la base de datos: ' . $conexion->error]);
    }
}




