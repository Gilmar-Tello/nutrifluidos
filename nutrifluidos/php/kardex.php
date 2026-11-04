<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala');

header('Content-Type: application/json; charset=utf-8');

$accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';

switch ($accion) {
    case 'buscarProductos':
        buscarProductos();
        break;
    case 'resumenGlobal':
        resumenGlobal();
        break;
    case 'kardexProducto':
        kardexProducto();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

/**
 * Autocompletado del buscador de productos (por nombre o código).
 */
function buscarProductos() {
    global $conexion;

    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($q === '') {
        echo json_encode(['productos' => []]);
        return;
    }

    $like = '%' . $q . '%';
    $sql = "SELECT id, codigo, nombre, stock, precio_compra
            FROM productos
            WHERE estado = 1 AND (nombre LIKE ? OR codigo LIKE ?)
            ORDER BY nombre ASC
            LIMIT 15";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(['productos' => $productos]);
}

/**
 * Vista global: estadísticas y movimientos agregados de TODOS los productos.
 */
function resumenGlobal() {
    global $conexion;

    $stats = [];

    $res = $conexion->query("SELECT COUNT(*) AS total FROM historial_stock");
    $stats['total_movimientos'] = (int)$res->fetch_assoc()['total'];

    $res = $conexion->query("SELECT COALESCE(SUM(costo_total),0) AS total FROM historial_stock WHERE operacion = 'agregar'");
    $stats['total_comprado'] = (float)$res->fetch_assoc()['total'];

    $res = $conexion->query("SELECT COUNT(DISTINCT producto_id) AS total FROM historial_stock WHERE operacion = 'agregar' AND precio_compra_unitario IS NOT NULL");
    $stats['productos_con_compras'] = (int)$res->fetch_assoc()['total'];

    $res = $conexion->query("SELECT COALESCE(AVG(precio_compra_unitario),0) AS promedio FROM historial_stock WHERE operacion = 'agregar' AND precio_compra_unitario IS NOT NULL");
    $stats['precio_promedio_general'] = (float)$res->fetch_assoc()['promedio'];

    // Costo total comprado por mes (solo entradas con precio registrado)
    $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(costo_total) AS total
            FROM historial_stock
            WHERE operacion = 'agregar' AND costo_total IS NOT NULL
            GROUP BY mes
            ORDER BY mes ASC";
    $serie_costos = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

    // Entradas vs salidas por mes (todas las operaciones, con o sin precio)
    $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, operacion, SUM(cantidad) AS total_cantidad
            FROM historial_stock
            GROUP BY mes, operacion
            ORDER BY mes ASC";
    $serie_movimientos = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

    // Movimientos más recientes (limitados para no sobrecargar la tabla)
    $sql = "SELECT id, producto_id, producto_nombre, operacion, cantidad,
                   precio_compra_unitario, costo_total, stock_anterior, stock_nuevo,
                   usuario_responsable, fecha
            FROM historial_stock
            ORDER BY fecha DESC, id DESC
            LIMIT 300";
    $movimientos = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'stats' => $stats,
        'serie_costos' => $serie_costos,
        'serie_movimientos' => $serie_movimientos,
        'movimientos' => $movimientos
    ]);
}

/**
 * Kardex detallado de un producto específico.
 */
function kardexProducto() {
    global $conexion;

    $producto_id = isset($_GET['producto_id']) ? (int)$_GET['producto_id'] : 0;
    if ($producto_id <= 0) {
        echo json_encode(['error' => 'ID de producto inválido']);
        return;
    }

    $sql = "SELECT id, codigo, nombre, stock, precio_compra FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();

    if (!$producto) {
        echo json_encode(['error' => 'Producto no encontrado']);
        return;
    }

    $stats = [];
    $stats['precio_actual'] = (float)$producto['precio_compra'];

    $sql = "SELECT COALESCE(AVG(precio_compra_unitario),0) AS promedio,
                   MIN(precio_compra_unitario) AS minimo,
                   MAX(precio_compra_unitario) AS maximo
            FROM historial_stock
            WHERE producto_id = ? AND operacion = 'agregar' AND precio_compra_unitario IS NOT NULL";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stats['precio_promedio'] = (float)$res['promedio'];
    $stats['precio_minimo'] = $res['minimo'] !== null ? (float)$res['minimo'] : 0;
    $stats['precio_maximo'] = $res['maximo'] !== null ? (float)$res['maximo'] : 0;

    // Serie de precio de compra a través del tiempo (solo entradas con precio)
    $sql = "SELECT fecha, precio_compra_unitario
            FROM historial_stock
            WHERE producto_id = ? AND operacion = 'agregar' AND precio_compra_unitario IS NOT NULL
            ORDER BY fecha ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $serie_precios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Serie de stock resultante a través del tiempo (todos los movimientos)
    $sql = "SELECT fecha, stock_nuevo
            FROM historial_stock
            WHERE producto_id = ?
            ORDER BY fecha ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $serie_stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Detalle de movimientos de este producto (más recientes primero)
    $sql = "SELECT id, producto_id, producto_nombre, operacion, cantidad,
                   precio_compra_unitario, costo_total, stock_anterior, stock_nuevo,
                   usuario_responsable, fecha
            FROM historial_stock
            WHERE producto_id = ?
            ORDER BY fecha DESC, id DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'producto' => $producto,
        'stats' => $stats,
        'serie_precios' => $serie_precios,
        'serie_stock' => $serie_stock,
        'movimientos' => $movimientos
    ]);
}
