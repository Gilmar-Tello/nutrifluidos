<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();
date_default_timezone_set('America/Guatemala');
// Incluir archivo de conexión a la base de datos
require_once __DIR__ . '/../../../privado/conexion.php';

// Verificar que se recibió una petición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos enviados como JSON
    $input = leerJsonAutenticado();
    
    // Verificar que se haya especificado una acción
    if (!isset($input['accion'])) {
        echo json_encode(['error' => 'No se especificó ninguna acción']);
        exit;
    }
    
    // Procesar según la acción solicitada
    switch ($input['accion']) {
        case 'buscarCliente':
            buscarCliente($input);
            break;
        case 'buscarProducto':
            buscarProducto($input);
            break;
        case 'registrarVenta':
             $resultado = registrarVenta($input);
             echo json_encode($resultado);
            break;
        case 'obtenerHistorialVentas':
             obtenerHistorialVentas($input);
             break;
        case 'obtenerDetalleVenta':
             obtenerDetalleVenta($input);
            break;
        default:
            echo json_encode(['error' => 'Acción no reconocida']);
            break;
    }
} else {
    // Si no es una petición POST, mostrar error
    echo json_encode(['error' => 'Método no permitido']);
}

/**
 * Busca clientes en la base de datos según el término proporcionado
 * y los filtra según el rol del usuario
 * @param array $data - Datos de la petición
 */
function buscarCliente($data) {
    global $conexion;
    
    // Validar parámetros necesarios
    if (!isset($data['termino']) || empty(trim($data['termino']))) {
        echo json_encode(['error' => 'Se requiere un término de búsqueda']);
        return;
    }
    
    if (!isset($data['idVendedor']) || !isset($data['rol'])) {
        echo json_encode(['error' => 'Datos de usuario incompletos']);
        return;
    }
    
    try {
        // Sanitizar término de búsqueda y asegurar que se mantenga como string
        $termino_original = trim($data['termino']);
        
        // Si es numérico, asegurar que se mantenga como string para preservar formato
        if (is_numeric($termino_original)) {
            $termino_original = (string)$termino_original;
        }
        
        $idVendedor = (int)$data['idVendedor'];
        $rol = strtolower(trim($data['rol']));
        
        // Construir consulta según el tipo de búsqueda
        if (isset($data['busquedaExacta']) && $data['busquedaExacta']) {
            // Para búsqueda exacta, incluir NIT
            $sql = "SELECT * FROM clientes WHERE (empresa = ? OR contacto1 = ? OR nit = ?)";
            $termino1 = $termino_original;
            $termino2 = $termino_original;
            $termino3 = $termino_original;
        } else {
            // Para sugerencias autocompletado, usar LIKE e incluir NIT
            $sql = "SELECT * FROM clientes WHERE (empresa LIKE ? OR contacto1 LIKE ? OR nit LIKE ?)";
            $termino1 = '%' . $termino_original . '%';
            $termino2 = '%' . $termino_original . '%';
            $termino3 = '%' . $termino_original . '%';
        }
        
        // Aplicar filtros de permisos para vendedores
        if ($rol === 'vendedor') {
            $vendedoresConAcceso = obtenerVendedoresConAccesoVentas($idVendedor);
            $placeholders = str_repeat('?,', count($vendedoresConAcceso) - 1) . '?';
            $sql .= " AND id_vendedor IN ($placeholders)";
        }
        
        // Agregar filtro para mostrar solo clientes activos
        $sql .= " AND estado = 1";
        
        // Preparar y ejecutar consulta
        $stmt = $conexion->prepare($sql);
        
        if ($rol === 'vendedor') {
            // Construir array de parámetros: 3 términos de búsqueda + IDs de vendedores
            $params = [$termino1, $termino2, $termino3];
            $types = 'sss'; // 3 strings para búsqueda
            
            foreach ($vendedoresConAcceso as $vendedorId) {
                $params[] = $vendedorId;
                $types .= 'i'; // integer para cada vendedor
            }
            
            $stmt->bind_param($types, ...$params);
        } else {
            // Solo 3 términos de búsqueda para administradores
            $stmt->bind_param("sss", $termino1, $termino2, $termino3);
        }
        
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        // Verificar si hay resultados
        if ($resultado->num_rows > 0) {
            $clientes = [];
            while ($cliente = $resultado->fetch_assoc()) {
                // Agregar información de permiso temporal para vendedores
                if ($rol === 'vendedor') {
                    $cliente['es_propio'] = ($cliente['id_vendedor'] == $idVendedor);
                    $cliente['permiso_temporal'] = !$cliente['es_propio'] && 
                                                 tienePermisoTemporalVentas($idVendedor, $cliente['id_vendedor']);
                }
                
                $clientes[] = $cliente;
            }
            echo json_encode(['clientes' => $clientes]);
        } else {
            echo json_encode(['clientes' => []]);
        }
        
    } catch (Exception $e) {
        // En caso de error, devolver mensaje
        error_log("Error en buscarCliente: " . $e->getMessage());
        echo json_encode(['error' => 'Error al buscar cliente: ' . $e->getMessage()]);
    }
}

/**
 * Busca productos en la base de datos según el término proporcionado
 * @param array $data - Datos de la petición
 */
function buscarProducto($data) {
    global $conexion;
    
    // Validar parámetros necesarios
    if (!isset($data['termino']) || empty(trim($data['termino']))) {
        echo json_encode(['error' => 'Se requiere un término de búsqueda']);
        return;
    }
    
    try {
        // Sanitizar término de búsqueda
        $termino = trim($data['termino']);
        $busquedaExacta = isset($data['busquedaExacta']) && $data['busquedaExacta'];
        
        // Construir consulta según tipo de búsqueda
        if ($busquedaExacta) {
            // Para búsqueda exacta, usamos igualdad
            $sql = "SELECT * FROM productos WHERE (codigo = ? OR nombre = ?) AND estado = 1";
        } else {
            // Para sugerencias autocompletado, usamos LIKE
            $terminoLike = '%' . $termino . '%';
            $sql = "SELECT * FROM productos WHERE (codigo LIKE ? OR nombre LIKE ?) AND estado = 1";
        }
        
        // Preparar y ejecutar consulta
        $stmt = $conexion->prepare($sql);
        
        if ($busquedaExacta) {
            $stmt->bind_param("ss", $termino, $termino);
        } else {
            $terminoLike = '%' . $termino . '%';
            $stmt->bind_param("ss", $terminoLike, $terminoLike);
        }
        
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        // Verificar si hay resultados
        if ($resultado->num_rows > 0) {
            $productos = [];
            while ($producto = $resultado->fetch_assoc()) {
                $productos[] = $producto;
            }
            echo json_encode(['productos' => $productos]);
        } else {
            echo json_encode(['productos' => []]);
        }
        
    } catch (Exception $e) {
        // En caso de error, devolver mensaje
        error_log("Error en buscarProducto: " . $e->getMessage());
        echo json_encode(['error' => 'Error al buscar producto: ' . $e->getMessage()]);
    }
}

/**
 * Registra una nueva venta en la base de datos
 * MODIFICADO: Soporte completo para productos complemento
 * @param array $data - Datos de la venta
 * @return array - Resultado de la operación
 */
function registrarVenta($data) {
    global $conexion;
    
    // Iniciar transacción para asegurar la integridad de los datos
    $conexion->begin_transaction();
    
    try {
        // Validar datos mínimos requeridos
        if (!isset($data['cliente_id']) || !isset($data['productos']) || empty($data['productos'])) {
            throw new Exception('Datos incompletos para registrar la venta');
        }
        
        // Obtener información del usuario desde la sesión
        $usuario_id = $data['usuario_id'];
        
        // Generar código de venta
        $codigo = generarCodigoVenta();
        
        // ✅ MODIFICADO: Calcular subtotal EXCLUYENDO productos complemento
        $subtotal = 0;
        foreach ($data['productos'] as $producto) {
            // Solo sumar al subtotal si NO es complemento
            $es_complemento = isset($producto['es_complemento']) ? intval($producto['es_complemento']) : 0;
            
            if ($es_complemento === 0) {
                $subtotal += round($producto['cantidad'] * $producto['precio'], 2);
            }
        }
        // Asegurar redondeo final del subtotal
        $subtotal = round($subtotal, 2);
        
        $descuento = isset($data['descuento']) ? floatval($data['descuento']) : 0;
        $total = round($subtotal - $descuento, 2);
        
        // Verificar si es venta a crédito
        $tipo_venta = isset($data['tipo_venta']) ? $data['tipo_venta'] : 'contado';
        
        // Formatear fecha
        $fecha = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d');
        
        // Obtener DTE (opcional)
        $dte = isset($data['dte']) && !empty(trim($data['dte'])) ? trim($data['dte']) : null;
        
        // Obtener Orden de Compra (opcional)
        $ordenCompra = isset($data['ordenCompra']) && !empty(trim($data['ordenCompra'])) ? trim($data['ordenCompra']) : null;
        
        // LOG: Inicio de registro de venta
        error_log("=== REGISTRANDO VENTA ===");
        error_log("Código: $codigo, Cliente: {$data['cliente_id']}, Usuario: $usuario_id");
        
        // ✅ CORRECTO: Insertar en tabla VENTAS (tabla principal)
        $stmt = $conexion->prepare("
            INSERT INTO ventas (codigo, dte, ordenCompra, fecha, cliente_id, usuario_id, tipo_venta, subtotal, descuento, total, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->bind_param(
            'sssssissdd', 
            $codigo,
            $dte,           // Campo DTE
            $ordenCompra,   // Campo Orden de Compra
            $fecha, 
            $data['cliente_id'], 
            $usuario_id, 
            $tipo_venta, 
            $subtotal, 
            $descuento, 
            $total
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar la venta: ' . $stmt->error);
        }
        
        // Obtener el ID de la venta insertada
        $venta_id = $conexion->insert_id;
        error_log("Venta registrada con ID: $venta_id");
        
        // ✅ MODIFICADO: Insertar los detalles de la venta CON es_complemento
        $stmt = $conexion->prepare("
            INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio_unitario, subtotal, es_complemento) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($data['productos'] as $producto) {
            $producto_id = $producto['id'];
            $cantidad = $producto['cantidad'];
            $precio = $producto['precio'];
            
            // ✅ NUEVO: Obtener es_complemento del producto
            $es_complemento = isset($producto['es_complemento']) ? intval($producto['es_complemento']) : 0;
            
            // Redondeo explícito del subtotal del producto
            $subtotal_producto = round($cantidad * $precio, 2);
            
            // ✅ MODIFICADO: bind_param con 6 parámetros (agregado es_complemento)
            $stmt->bind_param('iiiddi', $venta_id, $producto_id, $cantidad, $precio, $subtotal_producto, $es_complemento);
            
            if (!$stmt->execute()) {
                throw new Exception('Error al registrar detalle de venta: ' . $stmt->error);
            }
            
            // Actualizar stock del producto (siempre, sea complemento o no)
            // N8 (16-09-2026): valida stock disponible y registra el movimiento en el kardex
            actualizarStockProducto($producto_id, $cantidad, $usuario_id, 'Venta ' . $codigo);
        }
        
        // Si es venta a crédito, registrar datos adicionales
        if ($tipo_venta === 'credito') {
            registrarVentaCredito($venta_id, $data['credito'], $total);
        }

        // *** REGISTRO DE PEDIDO AUTOMÁTICO ***
        error_log("Iniciando registro de pedido automático...");
        
        try {
            $datos_pedido = registrarPedido($venta_id, $codigo, $data, $total);
            error_log("Pedido registrado exitosamente: " . json_encode($datos_pedido));
            
            // Confirmar la transacción
            $conexion->commit();
            
            // Devolver información completa
            return [
                'success' => true,
                'venta_id' => $venta_id,
                'codigo' => $codigo,
                'pedido_id' => $datos_pedido['pedido_id'],
                'numero_pedido' => $datos_pedido['numero_pedido'],
                'mensaje' => 'Venta y pedido registrados con éxito'
            ];
            
        } catch (Exception $e) {
            // N9 (16-09-2026): si falla el pedido se revierte TODA la venta.
            // Antes se confirmaba la venta sin pedido, dejando stock descontado
            // y una venta imposible de despachar. Mejor que el usuario reintente.
            error_log("ERROR al registrar pedido: " . $e->getMessage() . " -> revirtiendo venta");
            $conexion->rollback();

            return [
                'success' => false,
                'error' => 'No se pudo registrar el pedido, la venta fue revertida: ' . $e->getMessage()
            ];
        }
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error en la venta
        $conexion->rollback();
        error_log("ERROR EN registrarVenta: " . $e->getMessage());
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function generarCodigoVenta() {
    global $conexion;
    
    // Obtener el último ID de venta + 1 para el correlativo
    $query = "SELECT MAX(id) + 1 as siguiente_id FROM ventas";
    $result = $conexion->query($query);
    $row = $result->fetch_assoc();
    $correlativo = $row['siguiente_id'] ?? 1;
    
    // Obtener fecha actual en formato AAAMMDD (año 2 dígitos + mes + día)
    $anio = date('y');  // Año en 2 dígitos (25 para 2025)
    $mes = date('m');   // Mes con 2 dígitos (07 para julio)
    $dia = date('d');   // Día con 2 dígitos (07 para día 7)
    
    // Concatenar: correlativo + año + mes + día
    $codigo = $correlativo . $anio . $mes . $dia;
    
    error_log("Código generado: " . $codigo);
    return $codigo;
}

/**
 * Actualiza el stock de un producto después de una venta
 * @param int $producto_id - ID del producto
 * @param int $cantidad - Cantidad vendida
 */
function actualizarStockProducto($producto_id, $cantidad, $usuario_id = null, $motivo = 'Venta') {
    global $conexion;

    // N8: leer producto y validar stock disponible antes de descontar
    $stmt = $conexion->prepare("SELECT nombre, stock FROM productos WHERE id = ?");
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    if (!$prod) {
        throw new Exception('Producto con ID ' . (int)$producto_id . ' no encontrado');
    }
    $stock_anterior = (int)$prod['stock'];
    if ($stock_anterior < $cantidad) {
        throw new Exception('Stock insuficiente para ' . $prod['nombre'] .
            '. Disponible: ' . $stock_anterior . ', solicitado: ' . (int)$cantidad);
    }

    $stmt = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
    $stmt->bind_param('ii', $cantidad, $producto_id);
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el stock del producto: ' . $stmt->error);
    }

    // N8: registrar el movimiento en el kardex (antes las ventas no lo hacían)
    $stock_nuevo = $stock_anterior - (int)$cantidad;
    $nombre_resp = 'Sistema';
    if ($usuario_id) {
        $stmtU = $conexion->prepare("SELECT nombre_completo FROM usuarios WHERE id = ?");
        $stmtU->bind_param('i', $usuario_id);
        $stmtU->execute();
        $u = $stmtU->get_result()->fetch_assoc();
        if ($u) { $nombre_resp = $u['nombre_completo']; }
    }
    $op = 'reducir';
    $stmtK = $conexion->prepare("INSERT INTO historial_stock
        (producto_id, producto_nombre, operacion, cantidad, stock_anterior, stock_nuevo, usuario_responsable)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtK->bind_param('issiiis', $producto_id, $prod['nombre'], $op, $cantidad, $stock_anterior, $stock_nuevo, $nombre_resp);
    $stmtK->execute();
}

/**
 * Registra los datos de crédito para una venta
 * @param int $venta_id - ID de la venta
 * @param array $datos_credito - Datos del crédito
 * @param float $total - Total de la venta
 */
function registrarVentaCredito($venta_id, $datos_credito, $total) {
    global $conexion;
    
    // Validar datos del crédito
    if (!isset($datos_credito['plazos']) || $datos_credito['plazos'] <= 0) {
        throw new Exception('Número de plazos inválido para venta a crédito');
    }
    
    // Anticipo (opcional)
    $anticipo = isset($datos_credito['anticipo']) ? floatval($datos_credito['anticipo']) : 0;
    
    // Validar que el anticipo no sea mayor al total
    if ($anticipo > $total) {
        throw new Exception('El anticipo no puede ser mayor al total de la venta');
    }
    
    // Calcular saldo pendiente y monto de cuota
    $saldo_pendiente = $total - $anticipo;
    $plazos = intval($datos_credito['plazos']);
    $monto_cuota = $saldo_pendiente / $plazos;
    
    // Calcular fecha del primer pago (1 mes después de la fecha de venta)
    $fecha_primer_pago = date('Y-m-d', strtotime('+1 month'));
    
    // Insertar registro de crédito
    $stmt = $conexion->prepare("
        INSERT INTO venta_credito (venta_id, anticipo, plazos, monto_cuota, fecha_primer_pago, saldo_pendiente, estado) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    
    $stmt->bind_param(
        'ididsd', 
        $venta_id, 
        $anticipo, 
        $plazos, 
        $monto_cuota, 
        $fecha_primer_pago, 
        $saldo_pendiente
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Error al registrar crédito: ' . $stmt->error);
    }
    
    // Si hay anticipo, registrar como primer pago
    if ($anticipo > 0) {
        $venta_credito_id = $conexion->insert_id;
        
        // PROBLEMA: Aquí se espera un usuario_id, pero no se está enviando
        // debemos asegurarnos de tener el usuario_id
        
        // Verificar si tenemos el usuario_id en los datos de crédito
        if (!isset($datos_credito['usuario_id']) || !$datos_credito['usuario_id']) {
            // Si no está en los datos, lo obtenemos de la venta
            $stmtUsuario = $conexion->prepare("SELECT usuario_id FROM ventas WHERE id = ?");
            $stmtUsuario->bind_param('i', $venta_id);
            $stmtUsuario->execute();
            $resultUsuario = $stmtUsuario->get_result();
            
            if ($resultUsuario->num_rows > 0) {
                $usuario_id = $resultUsuario->fetch_assoc()['usuario_id'];
            } else {
                throw new Exception('No se pudo obtener el usuario para registrar el anticipo');
            }
        } else {
            $usuario_id = $datos_credito['usuario_id'];
        }
        
        $stmt = $conexion->prepare("
            INSERT INTO venta_credito_pagos (venta_credito_id, numero_cuota, fecha, monto, saldo_restante, usuario_id) 
            VALUES (?, 0, CURDATE(), ?, ?, ?)
        ");
        
        $stmt->bind_param(
            'iddi', 
            $venta_credito_id, 
            $anticipo, 
            $saldo_pendiente, 
            $usuario_id // Ahora tenemos un usuario_id válido
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar anticipo: ' . $stmt->error);
        }
    }
}

/**
 * Obtiene el historial de ventas aplicando filtros y respetando el rol del usuario
 * @param array $data - Datos de la petición
 */
function obtenerHistorialVentas($data) {
    global $conexion;
    
    try {
        // Obtener datos obligatorios
        if (!isset($data['usuario_id']) || !isset($data['rol'])) {
            echo json_encode(['error' => 'Datos de usuario incompletos']);
            return;
        }
        
        $usuario_id = (int)$data['usuario_id'];
        $rol = strtolower(trim($data['rol']));
        
        // Preparar filtros opcionales
        // Preparar filtros opcionales
$tipo_venta = isset($data['tipo_venta']) && !empty(trim($data['tipo_venta'])) ? trim($data['tipo_venta']) : '';
$cliente_termino = isset($data['cliente']) && !empty(trim($data['cliente'])) ? trim($data['cliente']) : '';
$fecha_inicio = isset($data['fecha_inicio']) && !empty(trim($data['fecha_inicio'])) ? trim($data['fecha_inicio']) : '';
$fecha_fin = isset($data['fecha_fin']) && !empty(trim($data['fecha_fin'])) ? trim($data['fecha_fin']) : '';
        
        // Construir consulta base
  $sql = "
    SELECT v.id, v.codigo, v.dte, v.ordenCompra, v.fecha, v.tipo_venta, v.subtotal, v.descuento, v.total, v.estado,
           c.empresa AS cliente_nombre, c.contacto1 AS cliente_contacto, c.nit AS cliente_nit,
           u.nombre_completo AS vendedor_nombre, u.usuario AS vendedor_usuario
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.estado = 1
";
        
        // Array para parámetros de la consulta
        $params = [];
        $types = '';
        
        // Aplicar filtro por rol de usuario
        if ($rol === 'vendedor') {
            $sql .= " AND v.usuario_id = ?";
            $params[] = $usuario_id;
            $types .= 'i';
        }
        
        // Aplicar filtro por tipo de venta
        if (!empty($tipo_venta)) {
            $sql .= " AND v.tipo_venta = ?";
            $params[] = $tipo_venta;
            $types .= 's';
        }
        
        // Aplicar filtro por cliente
        if (!empty($cliente_termino)) {
            $sql .= " AND (c.empresa LIKE ? OR c.contacto1 LIKE ? OR c.nit LIKE ?)";
            $cliente_busqueda = "%{$cliente_termino}%";
            $params[] = $cliente_busqueda;
            $params[] = $cliente_busqueda;
            $params[] = $cliente_busqueda;
            $types .= 'sss';
        }
        
        // Aplicar filtro por rango de fechas
if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql .= " AND DATE(v.fecha) BETWEEN ? AND ?";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
    $types .= 'ss';
} elseif (!empty($fecha_inicio)) {
    $sql .= " AND DATE(v.fecha) >= ?";
    $params[] = $fecha_inicio;
    $types .= 's';
} elseif (!empty($fecha_fin)) {
    $sql .= " AND DATE(v.fecha) <= ?";
    $params[] = $fecha_fin;
    $types .= 's';
}
        
        // Ordenar resultados por fecha descendente
        $sql .= " ORDER BY v.fecha DESC";
        
        // Preparar y ejecutar consulta
        $stmt = $conexion->prepare($sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Procesar resultados
        $ventas = [];
        while ($row = $result->fetch_assoc()) {
            // Preparar nombre del cliente
            $cliente = $row['cliente_nombre'] ? $row['cliente_nombre'] : $row['cliente_contacto'];
            
            // Si hay NIT, agregarlo al nombre del cliente
            if ($row['cliente_nit']) {
                $cliente .= " (" . formatearNIT($row['cliente_nit']) . ")";
            }
            
            // Preparar nombre del vendedor
            $vendedor = $row['vendedor_nombre'] ? $row['vendedor_nombre'] : $row['vendedor_usuario'];
            
            // Agregar a la lista
            // Agregar a la lista
$ventas[] = [
    'id' => $row['id'],
    'codigo' => $row['codigo'],
    'dte' => $row['dte'],
    'ordenCompra' => $row['ordenCompra'],  // NUEVO: Campo Orden de Compra
    'fecha' => $row['fecha'],
    'cliente' => $cliente,
    'vendedor' => $vendedor,
    'tipo_venta' => $row['tipo_venta'],
    'subtotal' => $row['subtotal'],
    'descuento' => $row['descuento'],
    'total' => $row['total'],
    'estado' => $row['estado']
];
        }
        
        // Enviar respuesta
        echo json_encode(['ventas' => $ventas]);
        
    } catch (Exception $e) {
        error_log("Error en obtenerHistorialVentas: " . $e->getMessage());
        echo json_encode(['error' => 'Error al obtener historial: ' . $e->getMessage()]);
    }
}

/**
 * Registra un pedido automáticamente al crear una venta
 * MODIFICADO: Soporte completo para productos complemento
 * @param int $venta_id - ID de la venta
 * @param string $codigo_venta - Código de la venta
 * @param array $data - Datos de la venta
 * @param float $total - Total de la venta
 * @return array - ID del pedido y número de pedido
*/
function registrarPedido($venta_id, $codigo_venta, $data, $total) {
    global $conexion;
    
    try {
        $numero_pedido = $codigo_venta;
        $cliente_id = (int)$data['cliente_id'];
        
        error_log("=== REGISTRAR PEDIDO ===");
        error_log("Venta ID: $venta_id, Código: $codigo_venta, Cliente: $cliente_id");
        
        // Verificar si ya existe un pedido para esta venta
        $stmt_check = $conexion->prepare("SELECT id FROM pedidos WHERE venta_id = ?");
        $stmt_check->bind_param('i', $venta_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        
        if ($check_result->num_rows > 0) {
            $pedido_existente = $check_result->fetch_assoc();
            error_log("Pedido ya existe para venta_id $venta_id con ID: " . $pedido_existente['id']);
            return [
                'pedido_id' => $pedido_existente['id'],
                'numero_pedido' => $numero_pedido
            ];
        }
        
        // Obtener dirección del cliente
        $direccion_entrega = 'Dirección no disponible';
        
        $stmt_cliente = $conexion->prepare("SELECT direccion FROM clientes WHERE id = ?");
        $stmt_cliente->bind_param('i', $cliente_id);
        $stmt_cliente->execute();
        $result_cliente = $stmt_cliente->get_result();
        
        if ($result_cliente->num_rows > 0) {
            $cliente_data = $result_cliente->fetch_assoc();
            if (!empty($cliente_data['direccion']) && trim($cliente_data['direccion']) !== '') {
                $direccion_entrega = trim($cliente_data['direccion']);
            }
        }
        
        // ✅ MODIFICADO: Calcular subtotal EXCLUYENDO productos complemento
        $subtotal = 0;
        foreach ($data['productos'] as $producto) {
            // Solo sumar al subtotal si NO es complemento
            $es_complemento = isset($producto['es_complemento']) ? intval($producto['es_complemento']) : 0;
            
            if ($es_complemento === 0) {
                $subtotal += round($producto['cantidad'] * $producto['precio'], 2);
            }
        }
        // Asegurar redondeo final del subtotal
        $subtotal = round($subtotal, 2);
        
        $descuento = isset($data['descuento']) ? floatval($data['descuento']) : 0;
        $tipo_venta = isset($data['tipo_venta']) ? $data['tipo_venta'] : 'contado';
        $fecha_pedido = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d');
        
        // Insertar pedido
        $stmt_pedido = $conexion->prepare("
            INSERT INTO pedidos (
                venta_id, numero_pedido, cliente_id, usuario_id, fecha_pedido, 
                subtotal, descuento, total, tipo_venta, estado, direccion_entrega, 
                fecha_registro, fecha_actualizacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, NOW(), NOW())
        ");
        
        $stmt_pedido->bind_param(
            'ississddss',
            $venta_id,
            $numero_pedido,
            $cliente_id,
            $data['usuario_id'],
            $fecha_pedido,
            $subtotal,
            $descuento,
            $total,
            $tipo_venta,
            $direccion_entrega
        );
        
        if (!$stmt_pedido->execute()) {
            throw new Exception('Error al insertar pedido: ' . $stmt_pedido->error);
        }
        
        $pedido_id = $conexion->insert_id;
        error_log("Pedido insertado con ID: $pedido_id");
        
        // ✅ MODIFICADO: Insertar detalles del pedido CON es_complemento
        $stmt_detalle = $conexion->prepare("
            INSERT INTO pedidos_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal, es_complemento) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($data['productos'] as $producto) {
            $producto_id = (int)$producto['id'];
            $cantidad = (int)$producto['cantidad'];
            $precio = (float)$producto['precio'];
            
            // ✅ NUEVO: Obtener es_complemento del producto
            $es_complemento = isset($producto['es_complemento']) ? intval($producto['es_complemento']) : 0;
            
            // Redondeo explícito del subtotal del producto
            $subtotal_prod = round($cantidad * $precio, 2);
            
            // ✅ MODIFICADO: bind_param con 6 parámetros (agregado es_complemento)
            $stmt_detalle->bind_param('iiiddi', $pedido_id, $producto_id, $cantidad, $precio, $subtotal_prod, $es_complemento);
            
            if (!$stmt_detalle->execute()) {
                throw new Exception('Error al insertar detalle del pedido: ' . $stmt_detalle->error);
            }
        }
        
        error_log("=== PEDIDO COMPLETADO EXITOSAMENTE ===");
        
        return [
            'pedido_id' => $pedido_id,
            'numero_pedido' => $numero_pedido
        ];
        
    } catch (Exception $e) {
        error_log("ERROR en registrarPedido: " . $e->getMessage());
        throw $e;
    }
}


/**
 * Obtiene el detalle completo de una venta específica
 * @param array $data - Datos de la petición
 */
function obtenerDetalleVenta($data) {
    global $conexion;
    
    try {
        // Validar parámetros necesarios
        if (!isset($data['venta_id']) || empty($data['venta_id'])) {
            echo json_encode(['error' => 'ID de venta requerido']);
            return;
        }
        
        $venta_id = (int)$data['venta_id'];
        
        // Obtener información principal de la venta
        $sql_venta = "
            SELECT v.*, 
                   c.empresa AS cliente_nombre, c.contacto1 AS cliente_contacto, c.nit AS cliente_nit,
                   u.nombre_completo AS vendedor_nombre, u.usuario AS vendedor_usuario
            FROM ventas v
            LEFT JOIN clientes c ON v.cliente_id = c.id
            LEFT JOIN usuarios u ON v.usuario_id = u.id
            WHERE v.id = ?
        ";
        
        $stmt = $conexion->prepare($sql_venta);
        $stmt->bind_param('i', $venta_id);
        $stmt->execute();
        $result_venta = $stmt->get_result();
        
        if ($result_venta->num_rows === 0) {
            echo json_encode(['error' => 'Venta no encontrada']);
            return;
        }
        
        $venta = $result_venta->fetch_assoc();
        
        // Preparar información del cliente
        $cliente_nombre = $venta['cliente_nombre'] ? $venta['cliente_nombre'] : $venta['cliente_contacto'];
        if ($venta['cliente_nit']) {
            $cliente_nombre .= " (" . formatearNIT($venta['cliente_nit']) . ")";
        }
        
        // Preparar información del vendedor
        $vendedor_nombre = $venta['vendedor_nombre'] ? $venta['vendedor_nombre'] : $venta['vendedor_usuario'];
        
        // Obtener detalles de los productos
        $sql_detalle = "
            SELECT vd.*, p.codigo, p.nombre
            FROM venta_detalle vd
            LEFT JOIN productos p ON vd.producto_id = p.id
            WHERE vd.venta_id = ?
            ORDER BY vd.id
        ";
        
        $stmt = $conexion->prepare($sql_detalle);
        $stmt->bind_param('i', $venta_id);
        $stmt->execute();
        $result_detalle = $stmt->get_result();
        
        $detalles = [];
        while ($row = $result_detalle->fetch_assoc()) {
            $detalles[] = $row;
        }
        
        // Obtener información de crédito si aplica
        $credito = null;
        if ($venta['tipo_venta'] === 'credito') {
            $sql_credito = "SELECT * FROM venta_credito WHERE venta_id = ?";
            $stmt = $conexion->prepare($sql_credito);
            $stmt->bind_param('i', $venta_id);
            $stmt->execute();
            $result_credito = $stmt->get_result();
            
            if ($result_credito->num_rows > 0) {
                $credito = $result_credito->fetch_assoc();
            }
        }
        
        // Preparar respuesta
        $respuesta = [
            'venta' => [
                'id' => $venta['id'],
                'codigo' => $venta['codigo'],
                'fecha' => $venta['fecha'],
                'cliente' => $cliente_nombre,
                'vendedor' => $vendedor_nombre,
                'tipo_venta' => $venta['tipo_venta'],
                'subtotal' => $venta['subtotal'],
                'descuento' => $venta['descuento'],
                'total' => $venta['total'],
                'estado' => $venta['estado']
            ],
            'detalles' => $detalles,
            'credito' => $credito
        ];
        
        echo json_encode($respuesta);
        
    } catch (Exception $e) {
        error_log("Error en obtenerDetalleVenta: " . $e->getMessage());
        echo json_encode(['error' => 'Error al obtener detalle de la venta: ' . $e->getMessage()]);
    }
}

/**
 * Formatea un NIT para mejor legibilidad
 */
function formatearNIT($nit) {
    if (!$nit) return 'N/A';
    
    $nitStr = (string)$nit;
    
    if (strpos($nitStr, '-') !== false || strlen($nitStr) < 8) {
        return $nitStr;
    }
    
    $base = substr($nitStr, 0, -1);
    $verificador = substr($nitStr, -1);
    
    return $base . '-' . $verificador;
}

// NUEVAS FUNCIONES PARA PERMISOS TEMPORALES EN VENTAS
function obtenerVendedoresConAccesoVentas($idVendedorActual) {
    global $conexion;
    
    // Siempre incluir al vendedor actual
    $vendedoresAcceso = [$idVendedorActual];
    
    // Obtener vendedores cuyos clientes puede gestionar temporalmente
    $sql = "SELECT id_vendedor_propietario 
            FROM permisos_vendedores 
            WHERE id_vendedor_autorizado = ? AND estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idVendedorActual);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    while ($permiso = $resultado->fetch_assoc()) {
        $vendedoresAcceso[] = $permiso['id_vendedor_propietario'];
    }
    
    return array_unique($vendedoresAcceso);
}

function tienePermisoTemporalVentas($idVendedorAutorizado, $idVendedorPropietario) {
    global $conexion;
    
    $sql = "SELECT id FROM permisos_vendedores 
            WHERE id_vendedor_autorizado = ? 
            AND id_vendedor_propietario = ? 
            AND estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $idVendedorAutorizado, $idVendedorPropietario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->num_rows > 0;
}

?>