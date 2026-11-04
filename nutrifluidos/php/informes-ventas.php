<?php
date_default_timezone_set('America/Guatemala');

// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';

header('Content-Type: application/json; charset=utf-8');

// (Se eliminó la validación débil y el 'debug_session' que exponía la sesión)

$accion = $_REQUEST['accion'] ?? '';

try {
    switch ($accion) {
        case 'obtener_clientes':
            obtenerClientes();
            break;
        case 'obtener_vendedores':
            obtenerVendedores();
            break;
        case 'obtener_estadisticas':
            obtenerEstadisticas();
            break;
        case 'obtener_ventas_resumen':
            obtenerVentasResumen();
            break;
        case 'obtener_productos_vendidos':
            obtenerProductosVendidos();
            break;
        case 'obtener_clientes_ventas':
            obtenerClientesVentas();
            break;
        case 'obtener_datos_graficos':
            obtenerDatosGraficos();
            break;
        case 'obtener_detalle_venta':
            obtenerDetalleVenta();
            break;
        case 'obtener_detalle_cliente':
            obtenerDetalleCliente();
            break;
        case 'anular_venta':
            anularVenta();
            break;
        case 'exportar_excel':
            exportarExcel();
            break;
        case 'exportar_pdf':
            exportarPDF();
            break;
        case 'obtener_estadisticas_anuladas':
            obtenerEstadisticasAnuladas();
            break;
        case 'obtener_datos_graficos_anuladas':
            obtenerDatosGraficosAnuladas();
            break;
        case 'obtener_detalle_anuladas':
            obtenerDetalleAnuladas();
            break;
    break;
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// RESTO DEL CÓDIGO IGUAL - Solo cambio la verificación de sesión al inicio
function obtenerClientes() {
    global $conexion;
    
    $query = "SELECT id, empresa FROM clientes WHERE estado = 1 ORDER BY empresa";
    $result = $conexion->query($query);
    $clientes = [];
    
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $clientes]);
}

function obtenerVendedores() {
    global $conexion;
    
    $query = "SELECT id, nombre_completo FROM usuarios WHERE estado = 'activo' ORDER BY nombre_completo";
    $result = $conexion->query($query);
    $vendedores = [];
    
    while ($row = $result->fetch_assoc()) {
        $vendedores[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $vendedores]);
}

function obtenerEstadisticas() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClause($filtros);
    
    $query = "
        SELECT 
            COALESCE(SUM(total), 0) as total_ventas,
            COUNT(*) as total_ventas_count,
            COUNT(DISTINCT cliente_id) as total_clientes
        FROM ventas v 
        INNER JOIN clientes c ON v.cliente_id = c.id 
        INNER JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.estado = 1 $whereClause
    ";
    $result = $conexion->query($query);
    $stats = $result->fetch_assoc();
    
    $query = "
        SELECT COALESCE(SUM(vd.cantidad), 0) as total_productos
        FROM venta_detalle vd
        INNER JOIN ventas v ON vd.venta_id = v.id
        INNER JOIN clientes c ON v.cliente_id = c.id 
        INNER JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.estado = 1 $whereClause
    ";
    $result = $conexion->query($query);
    $productos_stats = $result->fetch_assoc();
    
    $fecha_inicio_anterior = date('Y-m-d', strtotime($filtros['fecha_inicio'] . ' -1 month'));
    $fecha_fin_anterior = date('Y-m-d', strtotime($filtros['fecha_fin'] . ' -1 month'));
    
    $stmt = $conexion->prepare("
        SELECT COALESCE(SUM(total), 0) as total_anterior
        FROM ventas v 
        WHERE v.estado = 1 AND v.fecha BETWEEN ? AND ?
    ");
    $stmt->bind_param('ss', $fecha_inicio_anterior, $fecha_fin_anterior);
    $stmt->execute();
    $result = $stmt->get_result();
    $anterior = $result->fetch_assoc();
    
    $crecimiento = 0;
    if ($anterior['total_anterior'] > 0) {
        $crecimiento = round((($stats['total_ventas'] - $anterior['total_anterior']) / $anterior['total_anterior']) * 100, 1);
    }
    
    $resultado = [
        'total_ventas' => $stats['total_ventas'],
        'total_productos' => $productos_stats['total_productos'],
        'total_clientes' => $stats['total_clientes'],
        'crecimiento' => $crecimiento
    ];
    
    echo json_encode(['success' => true, 'data' => $resultado]);
}

function obtenerVentasResumen() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClause($filtros);
    
    $query = "
        SELECT 
            v.id,
            v.codigo,
            v.fecha,
            v.subtotal,
            v.descuento,
            v.total,
            v.estado,
            v.tipo_venta,
            c.empresa as cliente_nombre,
            u.nombre_completo as vendedor_nombre
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        ORDER BY v.fecha DESC, v.id DESC
    ";
    
    $result = $conexion->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Error en consulta: ' . $conexion->error]);
        return;
    }
    
    $ventas = [];
    
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $ventas]);
}

function obtenerProductosVendidos() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClause($filtros);
    
    $queryTotal = "
        SELECT SUM(total) as total_general 
        FROM ventas v 
        INNER JOIN clientes c ON v.cliente_id = c.id 
        INNER JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.estado = 1 $whereClause
    ";
    $resultTotal = $conexion->query($queryTotal);
    $totalGeneral = $resultTotal->fetch_assoc()['total_general'] ?? 1;
    
    $query = "
        SELECT 
            p.codigo,
            p.nombre,
            SUM(vd.cantidad) as cantidad_total,
            AVG(vd.precio_unitario) as precio_promedio,
            SUM(vd.subtotal) as ingreso_total,
            ROUND((SUM(vd.subtotal) / $totalGeneral) * 100, 2) as porcentaje_total
        FROM venta_detalle vd
        INNER JOIN productos p ON vd.producto_id = p.id
        INNER JOIN ventas v ON vd.venta_id = v.id
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        GROUP BY p.id, p.codigo, p.nombre
        ORDER BY ingreso_total DESC
    ";
    
    $result = $conexion->query($query);
    $productos = [];
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $productos]);
}

function obtenerClientesVentas() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClause($filtros);
    
    $query = "
        SELECT 
            c.id,
            c.empresa,
            COUNT(v.id) as total_compras,
            SUM(v.total) as valor_total,
            AVG(v.total) as ticket_promedio,
            MAX(v.fecha) as ultima_compra,
            (SELECT p.nombre 
             FROM venta_detalle vd2
             INNER JOIN productos p ON vd2.producto_id = p.id
             INNER JOIN ventas v2 ON vd2.venta_id = v2.id
             WHERE v2.cliente_id = c.id AND v2.estado = 1
             GROUP BY p.id
             ORDER BY SUM(vd2.cantidad) DESC
             LIMIT 1) as producto_favorito
        FROM clientes c
        INNER JOIN ventas v ON c.id = v.cliente_id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        GROUP BY c.id, c.empresa
        ORDER BY valor_total DESC
    ";
    
    $result = $conexion->query($query);
    $clientes = [];
    
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $clientes]);
}

function obtenerDatosGraficos() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClause($filtros);
    
    $datos = [];
    
    $query = "
        SELECT 
            DATE_FORMAT(v.fecha, '%Y-%m') as mes,
            SUM(v.total) as total
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(v.fecha, '%Y-%m')
        ORDER BY mes
    ";
    $result = $conexion->query($query);
    $ventas_mensuales = [];
    while ($row = $result->fetch_assoc()) {
        $ventas_mensuales[] = $row;
    }
    $datos['ventas_mensuales'] = $ventas_mensuales;
    
    $query = "
        SELECT 
            v.tipo_venta,
            SUM(v.total) as total
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        GROUP BY v.tipo_venta
    ";
    $result = $conexion->query($query);
    $distribucion_ventas = [];
    while ($row = $result->fetch_assoc()) {
        $distribucion_ventas[] = $row;
    }
    $datos['distribucion_ventas'] = $distribucion_ventas;
    
    $query = "
        SELECT 
            p.nombre,
            SUM(vd.cantidad) as cantidad
        FROM venta_detalle vd
        INNER JOIN productos p ON vd.producto_id = p.id
        INNER JOIN ventas v ON vd.venta_id = v.id
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        GROUP BY p.id, p.nombre
        ORDER BY cantidad DESC
        LIMIT 5
    ";
    $result = $conexion->query($query);
    $productos_mas_vendidos = [];
    while ($row = $result->fetch_assoc()) {
        $productos_mas_vendidos[] = $row;
    }
    $datos['productos_mas_vendidos'] = $productos_mas_vendidos;
    
    $query = "
        SELECT 
            p.nombre,
            SUM(vd.subtotal) as total
        FROM venta_detalle vd
        INNER JOIN productos p ON vd.producto_id = p.id
        INNER JOIN ventas v ON vd.venta_id = v.id
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        GROUP BY p.id, p.nombre
        ORDER BY total DESC
        LIMIT 5
    ";
    $result = $conexion->query($query);
    $ingresos_por_producto = [];
    while ($row = $result->fetch_assoc()) {
        $ingresos_por_producto[] = $row;
    }
    $datos['ingresos_por_producto'] = $ingresos_por_producto;
    
    $query = "
        SELECT 
            c.empresa,
            SUM(v.total) as total
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.estado = 1 $whereClause
        GROUP BY c.id, c.empresa
        ORDER BY total DESC
        LIMIT 5
    ";
    $result = $conexion->query($query);
    $clientes_ventas = [];
    while ($row = $result->fetch_assoc()) {
        $clientes_ventas[] = $row;
    }
    $datos['clientes_ventas'] = $clientes_ventas;
    
    $datos['frecuencia_compra'] = [
        ['frecuencia' => 'Semanal', 'cantidad' => 5],
        ['frecuencia' => 'Quincenal', 'cantidad' => 8],
        ['frecuencia' => 'Mensual', 'cantidad' => 12],
        ['frecuencia' => 'Trimestral', 'cantidad' => 6],
        ['frecuencia' => 'Ocasional', 'cantidad' => 3]
    ];
    
    echo json_encode(['success' => true, 'data' => $datos]);
}

// Funciones auxiliares
function obtenerFiltros() {
    $fechaInicio = $_REQUEST['fecha_inicio'] ?? date('Y-m-01');
    $fechaFin = $_REQUEST['fecha_fin'] ?? date('Y-m-t');
    
    if (!strtotime($fechaInicio)) {
        $fechaInicio = date('Y-m-01');
    }
    if (!strtotime($fechaFin)) {
        $fechaFin = date('Y-m-t');
    }
    
    return [
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'cliente_id' => $_REQUEST['cliente_id'] ?? '',
        'vendedor_id' => $_REQUEST['vendedor_id'] ?? ''
    ];
}

function construirWhereClause($filtros) {
    global $conexion;
    
    $where = " AND v.fecha BETWEEN '" . $conexion->real_escape_string($filtros['fecha_inicio']) . "' 
               AND '" . $conexion->real_escape_string($filtros['fecha_fin']) . "'";
    
    if (!empty($filtros['cliente_id']) && is_numeric($filtros['cliente_id'])) {
        $where .= " AND v.cliente_id = " . (int)$filtros['cliente_id'];
    }
    
    if (!empty($filtros['vendedor_id']) && is_numeric($filtros['vendedor_id'])) {
        $where .= " AND v.usuario_id = " . (int)$filtros['vendedor_id'];
    }
    
    return $where;
}

// Funciones restantes vacías para evitar errores
function obtenerDetalleVenta() {
    global $conexion;
    
    $venta_id = $_REQUEST['venta_id'] ?? 0;
    
    if (!is_numeric($venta_id) || $venta_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de venta inválido']);
        return;
    }
    
    // Obtener información de la venta - Adaptado a tu BD real
    $query = "
        SELECT 
            v.id,
            v.codigo,
            v.fecha,
            v.subtotal,
            v.descuento,
            v.total,
            v.estado,
            v.tipo_venta,
            v.fecha_registro,
            c.empresa as cliente_empresa,
            c.nit as cliente_nit,
            c.contacto1 as cliente_contacto,
            c.telefono1 as cliente_telefono,
            c.email as cliente_email,
            c.direccion as cliente_direccion,
            u.nombre_completo as vendedor_nombre
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        INNER JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?
    ";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $venta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Venta no encontrada']);
        return;
    }
    
    $venta = $result->fetch_assoc();
    
    // Obtener detalles de productos - Adaptado a tu estructura real
    $query_detalle = "
        SELECT 
            vd.id,
            vd.cantidad,
            vd.precio_unitario,
            vd.subtotal,
            p.codigo,
            p.nombre,
            p.precio_compra,
            p.precio_venta_real,
            p.stock,
            p.stock_minimo
        FROM venta_detalle vd
        INNER JOIN productos p ON vd.producto_id = p.id
        WHERE vd.venta_id = ?
        ORDER BY vd.id
    ";
    
    $stmt_detalle = $conexion->prepare($query_detalle);
    $stmt_detalle->bind_param('i', $venta_id);
    $stmt_detalle->execute();
    $result_detalle = $stmt_detalle->get_result();
    
    $detalles = [];
    while ($row = $result_detalle->fetch_assoc()) {
        $detalles[] = $row;
    }
    
    $venta['detalles'] = $detalles;
    
    // Si la venta está anulada, obtener información de la anulación
    if ($venta['estado'] == 0) {
        $query_anulacion = "
            SELECT 
                motivo,
                detalle_adicional,
                usuario_anulo_nombre,
                fecha_anulacion
            FROM ventas_anulaciones 
            WHERE venta_id = ?
            ORDER BY fecha_anulacion DESC 
            LIMIT 1
        ";
        
        $stmt_anulacion = $conexion->prepare($query_anulacion);
        $stmt_anulacion->bind_param('i', $venta_id);
        $stmt_anulacion->execute();
        $result_anulacion = $stmt_anulacion->get_result();
        
        if ($result_anulacion->num_rows > 0) {
            $venta['anulacion'] = $result_anulacion->fetch_assoc();
        }
    }
    
    echo json_encode(['success' => true, 'data' => $venta]);
}

function obtenerDetalleCliente() {
    global $conexion;
    
    $cliente_id = $_REQUEST['cliente_id'] ?? 0;
    
    if (!is_numeric($cliente_id) || $cliente_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de cliente inválido']);
        return;
    }
    
    // Obtener información del cliente - Adaptado a tu BD real
    $query_cliente = "
        SELECT 
            c.id,
            c.empresa,
            c.nit,
            c.contacto1,
            c.telefono1,
            c.contacto2,
            c.telefono2,
            c.email,
            c.direccion,
            c.estado,
            c.fecha_registro,
            u.nombre_completo as vendedor_asignado
        FROM clientes c
        LEFT JOIN usuarios u ON c.id_vendedor = u.id
        WHERE c.id = ?
    ";
    
    $stmt = $conexion->prepare($query_cliente);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        return;
    }
    
    $cliente = $result->fetch_assoc();
    
    // Obtener estadísticas del cliente
    $query_estadisticas = "
        SELECT 
            COUNT(v.id) as total_compras,
            COALESCE(SUM(v.total), 0) as valor_total,
            COALESCE(AVG(v.total), 0) as ticket_promedio,
            MAX(v.fecha) as ultima_compra,
            MIN(v.fecha) as primera_compra,
            COUNT(CASE WHEN v.tipo_venta = 'contado' THEN 1 END) as compras_contado,
            COUNT(CASE WHEN v.tipo_venta = 'credito' THEN 1 END) as compras_credito
        FROM ventas v
        WHERE v.cliente_id = ? AND v.estado = 1
    ";
    
    $stmt_stats = $conexion->prepare($query_estadisticas);
    $stmt_stats->bind_param('i', $cliente_id);
    $stmt_stats->execute();
    $estadisticas = $stmt_stats->get_result()->fetch_assoc();
    
    // Obtener historial de compras (últimas 10)
    $query_historial = "
        SELECT 
            v.id,
            v.codigo,
            v.fecha,
            v.total,
            v.estado,
            v.tipo_venta
        FROM ventas v
        WHERE v.cliente_id = ?
        ORDER BY v.fecha DESC, v.id DESC
        LIMIT 10
    ";
    
    $stmt_historial = $conexion->prepare($query_historial);
    $stmt_historial->bind_param('i', $cliente_id);
    $stmt_historial->execute();
    $result_historial = $stmt_historial->get_result();
    
    $historial = [];
    while ($row = $result_historial->fetch_assoc()) {
        $historial[] = $row;
    }
    
    // Obtener productos favoritos (más comprados)
    $query_productos = "
        SELECT 
            p.codigo,
            p.nombre,
            SUM(vd.cantidad) as cantidad_total,
            COUNT(DISTINCT v.id) as veces_comprado,
            AVG(vd.precio_unitario) as precio_promedio
        FROM venta_detalle vd
        INNER JOIN productos p ON vd.producto_id = p.id
        INNER JOIN ventas v ON vd.venta_id = v.id
        WHERE v.cliente_id = ? AND v.estado = 1
        GROUP BY p.id, p.codigo, p.nombre
        ORDER BY cantidad_total DESC
        LIMIT 5
    ";
    
    $stmt_productos = $conexion->prepare($query_productos);
    $stmt_productos->bind_param('i', $cliente_id);
    $stmt_productos->execute();
    $result_productos = $stmt_productos->get_result();
    
    $productos_favoritos = [];
    while ($row = $result_productos->fetch_assoc()) {
        $productos_favoritos[] = $row;
    }
    
    // Obtener información de créditos pendientes si aplica
    $query_creditos = "
        SELECT 
            vc.id,
            vc.anticipo,
            vc.plazos,
            vc.monto_cuota,
            vc.saldo_pendiente,
            vc.fecha_primer_pago,
            v.codigo as venta_codigo,
            v.total as venta_total,
            v.fecha as fecha_venta
        FROM venta_credito vc
        INNER JOIN ventas v ON vc.venta_id = v.id
        WHERE v.cliente_id = ? AND vc.estado = 1 AND vc.saldo_pendiente > 0
        ORDER BY vc.fecha_primer_pago ASC
    ";
    
    $stmt_creditos = $conexion->prepare($query_creditos);
    $stmt_creditos->bind_param('i', $cliente_id);
    $stmt_creditos->execute();
    $result_creditos = $stmt_creditos->get_result();
    
    $creditos_pendientes = [];
    while ($row = $result_creditos->fetch_assoc()) {
        $creditos_pendientes[] = $row;
    }
    
    // Construir respuesta completa
    $response = [
        'id' => $cliente['id'],
        'empresa' => $cliente['empresa'],
        'nit' => $cliente['nit'],
        'contacto1' => $cliente['contacto1'],
        'telefono1' => $cliente['telefono1'],
        'contacto2' => $cliente['contacto2'],
        'telefono2' => $cliente['telefono2'],
        'email' => $cliente['email'],
        'direccion' => $cliente['direccion'],
        'estado' => $cliente['estado'],
        'fecha_registro' => $cliente['fecha_registro'],
        'vendedor_asignado' => $cliente['vendedor_asignado'],
        'estadisticas' => $estadisticas,
        'historial' => $historial,
        'productos_favoritos' => $productos_favoritos,
        'creditos_pendientes' => $creditos_pendientes
    ];
    
    echo json_encode(['success' => true, 'data' => $response]);
}

function anularVenta() {
    global $conexion;
    
    $venta_id = $_REQUEST['venta_id'] ?? 0;
    $motivo = trim($_REQUEST['motivo'] ?? '');
    $detalle_adicional = trim($_REQUEST['detalle_adicional'] ?? '');
    
    // Configurar zona horaria de Guatemala
    date_default_timezone_set('America/Guatemala');
    
    if (!is_numeric($venta_id) || $venta_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de venta invalido']);
        return;
    }
    
    if (empty($motivo)) {
        echo json_encode(['success' => false, 'error' => 'Debe proporcionar un motivo para la anulacion']);
        return;
    }
    
    // Verificar que la venta existe y está activa
    $query_verificar = "
        SELECT 
            v.id, 
            v.codigo, 
            v.fecha, 
            v.total, 
            v.cliente_id,
            c.empresa as cliente_nombre,
            v.usuario_id
        FROM ventas v
        INNER JOIN clientes c ON v.cliente_id = c.id
        WHERE v.id = ? AND v.estado = 1
    ";
    
    $stmt = $conexion->prepare($query_verificar);
    $stmt->bind_param('i', $venta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Venta no encontrada o ya esta anulada']);
        return;
    }
    
    $venta = $result->fetch_assoc();
    
    // Verificar si la venta se puede anular (máximo 360 horas = 15 dias)
    $fecha_venta = new DateTime($venta['fecha']);
    $fecha_actual = new DateTime();
    $diferencia = $fecha_actual->diff($fecha_venta);
    $horas_transcurridas = ($diferencia->days * 24) + $diferencia->h;
    
    if ($horas_transcurridas > 360) {
        echo json_encode([
            'success' => false, 
            'error' => 'No se puede anular una venta con mas de 15 dias (360 horas) de antiguedad. Han transcurrido ' . $horas_transcurridas . ' horas.'
        ]);
        return;
    }
    
    // Obtener información del usuario que anula
    $usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['user_id'] ?? 1;
    $query_usuario = "SELECT nombre_completo FROM usuarios WHERE id = ?";
    $stmt_usuario = $conexion->prepare($query_usuario);
    $stmt_usuario->bind_param('i', $usuario_id);
    $stmt_usuario->execute();
    $usuario_result = $stmt_usuario->get_result();
    $usuario_nombre = $usuario_result->num_rows > 0 ? $usuario_result->fetch_assoc()['nombre_completo'] : 'Usuario Desconocido';
    
    try {
        $conexion->begin_transaction();
        
        // 1. Registrar la anulación en la tabla ventas_anulaciones
        $query_anulacion = "
            INSERT INTO ventas_anulaciones (
                venta_id, 
                numero_factura, 
                motivo, 
                detalle_adicional, 
                usuario_anulo_id, 
                usuario_anulo_nombre, 
                fecha_anulacion, 
                total_venta, 
                cliente_id, 
                cliente_nombre
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ";
        
        $stmt_anulacion = $conexion->prepare($query_anulacion);
        $stmt_anulacion->bind_param(
            'isssisdis', 
            $venta['id'],
            $venta['codigo'],
            $motivo,
            $detalle_adicional,
            $usuario_id,
            $usuario_nombre,
            $venta['total'],
            $venta['cliente_id'],
            $venta['cliente_nombre']
        );
        
        if (!$stmt_anulacion->execute()) {
            throw new Exception('Error al registrar la anulacion en el historial');
        }
        
        // 2. Anular la venta (cambiar estado a 0)
        $query_anular_venta = "UPDATE ventas SET estado = 0 WHERE id = ?";
        $stmt_anular = $conexion->prepare($query_anular_venta);
        $stmt_anular->bind_param('i', $venta_id);
        
        if (!$stmt_anular->execute()) {
            throw new Exception('Error al anular la venta');
        }
        
        // 3. Cancelar pedido asociado si existe
        $query_pedido = "UPDATE pedidos SET estado = 'cancelado' WHERE venta_id = ?";
        $stmt_pedido = $conexion->prepare($query_pedido);
        $stmt_pedido->bind_param('i', $venta_id);
        $stmt_pedido->execute(); // No es crítico si falla
        
        // 4. Registrar en historial de pedidos si existe
        $query_historial_pedido = "
            INSERT INTO pedidos_historial (pedido_id, estado_anterior, estado_nuevo, usuario_id, notas) 
            SELECT p.id, p.estado, 'cancelado', ?, ? 
            FROM pedidos p 
            WHERE p.venta_id = ?
        ";
        $stmt_historial = $conexion->prepare($query_historial_pedido);
        $nota_anulacion = "Venta anulada - Motivo: " . $motivo;
        $stmt_historial->bind_param('isi', $usuario_id, $nota_anulacion, $venta_id);
        $stmt_historial->execute(); // No es crítico si falla
        
        // 5. Restaurar stock de productos vendidos
        $query_productos_venta = "
            SELECT vd.producto_id, vd.cantidad, p.nombre 
            FROM venta_detalle vd 
            INNER JOIN productos p ON vd.producto_id = p.id 
            WHERE vd.venta_id = ?
        ";
        $stmt_productos = $conexion->prepare($query_productos_venta);
        $stmt_productos->bind_param('i', $venta_id);
        $stmt_productos->execute();
        $productos_result = $stmt_productos->get_result();
        
        $productos_restaurados = [];
        while ($producto = $productos_result->fetch_assoc()) {
            // Obtener stock actual antes de restaurar
            $query_stock_actual = "SELECT stock FROM productos WHERE id = ?";
            $stmt_stock_actual = $conexion->prepare($query_stock_actual);
            $stmt_stock_actual->bind_param('i', $producto['producto_id']);
            $stmt_stock_actual->execute();
            $stock_anterior = $stmt_stock_actual->get_result()->fetch_assoc()['stock'];
            
            // Restaurar stock
            $query_restaurar_stock = "UPDATE productos SET stock = stock + ? WHERE id = ?";
            $stmt_restaurar = $conexion->prepare($query_restaurar_stock);
            $stmt_restaurar->bind_param('ii', $producto['cantidad'], $producto['producto_id']);
            
            if ($stmt_restaurar->execute()) {
                $stock_nuevo = $stock_anterior + $producto['cantidad'];
                
                // Registrar en historial de stock
                $query_historial_stock = "
                    INSERT INTO historial_stock (
                        producto_id, 
                        producto_nombre, 
                        operacion, 
                        cantidad, 
                        stock_anterior, 
                        stock_nuevo, 
                        usuario_responsable, 
                        fecha
                    ) VALUES (?, ?, 'agregar', ?, ?, ?, ?, NOW())
                ";
                $stmt_hist_stock = $conexion->prepare($query_historial_stock);
                // N11 (16-09-2026): usuario_responsable debe ser el usuario, no un texto.
                $resp_kardex = $usuario_nombre . ' (anulacion venta ' . $venta['codigo'] . ')';
                $stmt_hist_stock->bind_param('isiiss', 
                    $producto['producto_id'], 
                    $producto['nombre'], 
                    $producto['cantidad'], 
                    $stock_anterior, 
                    $stock_nuevo, 
                    $resp_kardex
                );
                $stmt_hist_stock->execute();
                
                $productos_restaurados[] = [
                    'nombre' => $producto['nombre'],
                    'cantidad' => $producto['cantidad'],
                    'stock_anterior' => $stock_anterior,
                    'stock_nuevo' => $stock_nuevo
                ];
            }
        }
        
        // 6. Si es venta a crédito, marcar crédito como cancelado
        $query_credito = "UPDATE venta_credito SET estado = 0, saldo_pendiente = 0 WHERE venta_id = ?";
        $stmt_credito = $conexion->prepare($query_credito);
        $stmt_credito->bind_param('i', $venta_id);
        $stmt_credito->execute(); // No es crítico si falla
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Venta anulada correctamente',
            'data' => [
                'venta_id' => $venta_id,
                'codigo' => $venta['codigo'],
                'total' => $venta['total'],
                'cliente' => $venta['cliente_nombre'],
                'usuario_anulo' => $usuario_nombre,
                'fecha_anulacion' => date('d/m/Y H:i:s'),
                'productos_restaurados' => $productos_restaurados,
                'horas_transcurridas' => $horas_transcurridas
            ]
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode([
            'success' => false, 
            'error' => 'Error al anular la venta: ' . $e->getMessage()
        ]);
    }
}

function obtenerEstadisticasAnuladas() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClauseAnuladas($filtros);
    
    // Estadísticas de ventas anuladas
    $query = "
        SELECT 
            COUNT(*) as total_anuladas,
            COALESCE(SUM(total_venta), 0) as monto_total_anulado,
            COALESCE(AVG(TIMESTAMPDIFF(HOUR, fecha_registro, fecha_anulacion)), 0) as tiempo_promedio_horas
        FROM ventas_anulaciones va
        WHERE 1=1 $whereClause
    ";
    
    $result = $conexion->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Error en consulta estadísticas: ' . $conexion->error]);
        return;
    }
    
    $estadisticas = $result->fetch_assoc();
    
    // Obtener total de ventas del período para calcular porcentaje
    $whereVentas = construirWhereClause($filtros);
    $queryTotal = "
        SELECT COUNT(*) as total_ventas_periodo 
        FROM ventas v 
        INNER JOIN clientes c ON v.cliente_id = c.id 
        INNER JOIN usuarios u ON v.usuario_id = u.id 
        WHERE 1=1 $whereVentas
    ";
    $resultTotal = $conexion->query($queryTotal);
    $totalVentas = $resultTotal ? $resultTotal->fetch_assoc()['total_ventas_periodo'] : 0;
    
    $porcentaje = $totalVentas > 0 ? 
        round(($estadisticas['total_anuladas'] / $totalVentas) * 100, 2) : 0;
    
    $resultado = [
        'total_anuladas' => $estadisticas['total_anuladas'] ?? 0,
        'monto_total_anulado' => $estadisticas['monto_total_anulado'] ?? 0,
        'porcentaje_anuladas' => $porcentaje,
        'tiempo_promedio_horas' => round($estadisticas['tiempo_promedio_horas'] ?? 0, 1)
    ];
    
    echo json_encode(['success' => true, 'data' => $resultado]);
}

function obtenerDatosGraficosAnuladas() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClauseAnuladas($filtros);
    
    $datos = [];
    
    // 1. Motivos de anulación
    $query = "
        SELECT 
            COALESCE(NULLIF(TRIM(motivo), ''), 'Sin motivo especificado') as motivo,
            COUNT(*) as cantidad,
            SUM(total_venta) as monto_total
        FROM ventas_anulaciones va
        WHERE 1=1 $whereClause
        GROUP BY motivo
        ORDER BY cantidad DESC
        LIMIT 10
    ";
    $result = $conexion->query($query);
    $motivos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $motivos[] = $row;
        }
    }
    $datos['motivos'] = $motivos;
    
    // 2. Tendencia de anulaciones por mes
    $query = "
        SELECT 
            DATE_FORMAT(fecha_anulacion, '%Y-%m') as mes,
            COUNT(*) as cantidad,
            SUM(total_venta) as monto
        FROM ventas_anulaciones va
        WHERE fecha_anulacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_anulacion, '%Y-%m')
        ORDER BY mes
    ";
    $result = $conexion->query($query);
    $tendencia = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $tendencia[] = $row;
        }
    }
    $datos['tendencia'] = $tendencia;
    
    // 3. Anulaciones por usuario
    $query = "
        SELECT 
            COALESCE(NULLIF(TRIM(usuario_anulo_nombre), ''), 'Usuario desconocido') as usuario,
            COUNT(*) as cantidad,
            SUM(total_venta) as monto_total
        FROM ventas_anulaciones va
        WHERE 1=1 $whereClause
        GROUP BY usuario_anulo_id, usuario_anulo_nombre
        ORDER BY cantidad DESC
        LIMIT 10
    ";
    $result = $conexion->query($query);
    $usuarios = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $usuarios[] = $row;
        }
    }
    $datos['usuarios'] = $usuarios;
    
    // 4. Productos más anulados
    $query = "
        SELECT 
            COALESCE(p.nombre, 'Producto no disponible') as producto,
            COUNT(DISTINCT va.venta_id) as cantidad_anulaciones,
            COALESCE(SUM(vd.cantidad), 0) as cantidad_productos,
            COALESCE(SUM(vd.subtotal), 0) as monto_perdido
        FROM ventas_anulaciones va
        LEFT JOIN venta_detalle vd ON va.venta_id = vd.venta_id
        LEFT JOIN productos p ON vd.producto_id = p.id
        WHERE 1=1 $whereClause
        GROUP BY p.id, p.nombre
        HAVING cantidad_anulaciones > 0
        ORDER BY cantidad_anulaciones DESC
        LIMIT 10
    ";
    $result = $conexion->query($query);
    $productos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
    }
    $datos['productos'] = $productos;
    
    echo json_encode(['success' => true, 'data' => $datos]);
}

function obtenerDetalleAnuladas() {
    global $conexion;
    
    $filtros = obtenerFiltros();
    $whereClause = construirWhereClauseAnuladas($filtros);
    
    $query = "
        SELECT 
            va.id,
            va.venta_id,
            va.numero_factura,
            va.fecha_registro as fecha_venta,
            va.fecha_anulacion,
            va.cliente_nombre,
            va.motivo,
            va.detalle_adicional,
            va.usuario_anulo_nombre,
            va.total_venta,
            TIMESTAMPDIFF(HOUR, va.fecha_registro, va.fecha_anulacion) as horas_transcurridas
        FROM ventas_anulaciones va
        WHERE 1=1 $whereClause
        ORDER BY va.fecha_anulacion DESC
        LIMIT 500
    ";
    
    $result = $conexion->query($query);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Error en consulta detalle: ' . $conexion->error]);
        return;
    }
    
    $anuladas = [];
    while ($row = $result->fetch_assoc()) {
        $anuladas[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $anuladas]);
}

// Función auxiliar para construir WHERE de anuladas
function construirWhereClauseAnuladas($filtros) {
    global $conexion;
    
    $where = " AND va.fecha_anulacion BETWEEN '" . 
             $conexion->real_escape_string($filtros['fecha_inicio']) . " 00:00:00' AND '" . 
             $conexion->real_escape_string($filtros['fecha_fin']) . " 23:59:59'";
    
    if (!empty($filtros['cliente_id']) && is_numeric($filtros['cliente_id'])) {
        $where .= " AND va.cliente_id = " . (int)$filtros['cliente_id'];
    }
    
    return $where;
}

?>