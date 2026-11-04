<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();
date_default_timezone_set('America/Guatemala');
require_once __DIR__ . '/../../../privado/conexion.php';
header('Content-Type: application/json');

// Configuración de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

try {
    $action = $_GET['action'] ?? 'listar';
    
    switch ($action) {
        case 'listar':
            listarPedidos($conexion);
            break;
            
        case 'obtener_detalle':
            obtenerDetallePedido($conexion, $_GET['pedido_id']);
            break;
            
        case 'obtener_detalle_completo':
            obtenerDetalleCompletoPedido($conexion, $_GET['pedido_id']);
            break;
            
        case 'modificar':
            $input = leerJsonAutenticado();
            modificarPedido($conexion, $input);
            break;
            
        case 'obtener_productos':
            obtenerProductosDisponibles($conexion);
            break;

        case 'empacar':
            $input = leerJsonAutenticado();
            empacarPedido($conexion, $input);
            break;

        case 'enviar':
            $input = leerJsonAutenticado();
            enviarPedido($conexion, $input);
            break;
        
        case 'entregar':
    entregarPedido($conexion, array_merge($_POST, ['usuario_id' => usuarioActual()['id']]));
    break;
            
        default:
            throw new Exception('Acción no válida');
    }

} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// REEMPLAZAR la función listarPedidos en pedidos.php

function listarPedidos($conexion) {
    // Usar la misma lógica que clientes.php
    // SEGURIDAD: identidad desde la sesión, no desde la URL
    $userData = usuarioActual();
    
    if (!$userData) {
        echo json_encode(['error' => 'Usuario no autenticado']);
        return;
    }
    
    $usuario_id = $userData['id'];
    $rol_usuario = $userData['rol'];
    
    // Administradores y bodegueros pueden ver todo, vendedores solo sus pedidos
    $puede_ver_todo = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    // Construir consulta base - SIMPLIFICADA para mejor ordenamiento
    $sql_base = "SELECT 
                p.id,
                p.numero_pedido,
                p.fecha_pedido,
                p.subtotal,
                p.descuento,
                p.total,
                p.tipo_venta,
                p.estado,
                p.direccion_entrega,
                p.notas,
                p.fecha_registro,
                p.usuario_id,
                c.empresa as cliente_nombre,
                u.nombre_completo as vendedor_nombre
            FROM pedidos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN usuarios u ON p.usuario_id = u.id";

    // Aplicar filtro según el rol - ORDEN SIMPLIFICADO DESDE BASE DE DATOS
    if (!$puede_ver_todo && $usuario_id) {
        // Vendedores: solo sus pedidos, ordenados por fecha descendente
        $sql = $sql_base . " WHERE p.usuario_id = ? ORDER BY p.fecha_pedido DESC, p.id DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Administradores y bodegueros: todos los pedidos, ordenados por fecha descendente
        $sql = $sql_base . " ORDER BY p.fecha_pedido DESC, p.id DESC";
        $result = $conexion->query($sql);
    }
    
    // Formatear los datos para DataTables
    $data = [];
    if ($result && $result->num_rows > 0) {
        while($pedido = $result->fetch_assoc()) {
            // CORRECCIÓN: Formatear fecha correctamente para ordenamiento
            $fecha_obj = new DateTime($pedido['fecha_pedido']);
$fecha_display = $fecha_obj->format('d/m/Y'); // Para mostrar (DD/MM/YYYY)
$fecha_sort = $pedido['fecha_pedido']; // Para ordenar (YYYY-MM-DD)
            
            // Formatear total SIN lógica compleja de ordenamiento
            $total = 'Q' . number_format($pedido['total'], 2);
            
            // Estado con badge de colores
            $estadoBadge = '';
            switch($pedido['estado']) {
                case 'pendiente':
                    $estadoBadge = '<span class="badge bg-warning text-dark">Pendiente</span>';
                    break;
                case 'empacado':
                    $estadoBadge = '<span class="badge bg-info">Empacado</span>';
                    break;
                case 'enviado':
                    $estadoBadge = '<span class="badge bg-primary">Enviado</span>';
                    break;
                case 'entregado':
                    $estadoBadge = '<span class="badge bg-success">Entregado</span>';
                    break;
                case 'cancelado':
                    $estadoBadge = '<span class="badge bg-danger">Cancelado</span>';
                    break;
                default:
                    $estadoBadge = '<span class="badge bg-secondary">Sin Estado</span>';
            }
            
            // Crear barra de progreso con colores
            $porcentaje = 0;
            $colorProgreso = 'bg-success';
            switch($pedido['estado']) {
                case 'pendiente': 
                    $porcentaje = 25; 
                    $colorProgreso = 'bg-warning';
                    break;
                case 'empacado': 
                    $porcentaje = 50; 
                    $colorProgreso = 'bg-info';
                    break;
                case 'enviado': 
                    $porcentaje = 75; 
                    $colorProgreso = 'bg-primary';
                    break;
                case 'entregado': 
                    $porcentaje = 100; 
                    $colorProgreso = 'bg-success';
                    break;
                case 'cancelado': 
                    $porcentaje = 100; 
                    $colorProgreso = 'bg-danger';
                    break;
            }
            
            $progreso = '<div class="progress" style="height: 10px;">
                            <div class="progress-bar ' . $colorProgreso . '" role="progressbar" 
                                 style="width: ' . $porcentaje . '%;" 
                                 aria-valuenow="' . $porcentaje . '" aria-valuemin="0" aria-valuemax="100"></div>
                         </div>';
            
            // Botones de acciones dinámicos según el rol del usuario
            $acciones = '<div class="btn-group btn-group-sm" role="group">';
            
            // Botón VER - siempre visible
            $acciones .= '<button type="button" class="btn btn-outline-primary" onclick="verDetallePedido(' . $pedido['id'] . ')" title="Ver Detalle">
                            <i class="fas fa-eye"></i>
                          </button>';
            
            // Solo administradores y bodegueros pueden realizar acciones de gestión
            if ($puede_ver_todo) {
                // Botones según el estado del pedido
                switch($pedido['estado']) {
                    case 'pendiente':
                        // EDITAR y EMPACAR
                        $acciones .= '<button type="button" class="btn btn-outline-info" onclick="editarPedido(' . $pedido['id'] . ')" title="Modificar">
                                        <i class="fas fa-edit"></i>
                                      </button>';
                        $acciones .= '<button type="button" class="btn btn-outline-success" onclick="empacarPedido(' . $pedido['id'] . ')" title="Marcar como Empacado">
                                        <i class="fas fa-box"></i>
                                      </button>';
                        break;
                        
                    case 'empacado':
                        // VER y ENVIAR
                        $acciones .= '<button type="button" class="btn btn-outline-warning" onclick="enviarPedido(' . $pedido['id'] . ')" title="Marcar como Enviado">
                                        <i class="fas fa-truck"></i>
                                      </button>';
                        break;
                        
                    case 'enviado':
                        // VER y ENTREGAR
                        $acciones .= '<button type="button" class="btn btn-outline-success" onclick="entregarPedido(' . $pedido['id'] . ')" title="Marcar como Entregado">
                                        <i class="fas fa-check-circle"></i>
                                      </button>';
                        break;
                        
                    case 'entregado':
                        // Solo VER para pedidos entregados
                        break;
                        
                    case 'cancelado':
                        // Solo VER para pedidos cancelados
                        break;
                }
            }
            
            $acciones .= '</div>';
            
            // DATOS SIMPLIFICADOS - solo lo esencial para ordenamiento
            $data[] = [
    $pedido['numero_pedido'],                    // 0: Número (no ordenable)
    $pedido['cliente_nombre'] ?: 'Sin cliente', // 1: Cliente (ordenable)
    $fecha_display,                              // 2: Fecha (ordenable) - MOSTRAR
    $total,                                      // 3: Total (no ordenable)
    $estadoBadge,                               // 4: Estado (no ordenable)
    $progreso,                                  // 5: Progreso (no ordenable)
    $acciones,                                  // 6: Acciones (no ordenable)
    $fecha_sort                                 // 7: Fecha ISO (OCULTA para ordenar)
];
        }
    }
    
    // Respuesta para DataTables
    echo json_encode([
        'data' => $data
    ]);
}

function obtenerDetalleCompletoPedido($conexion, $pedido_id) {
    // Misma lógica
    // SEGURIDAD: identidad desde la sesión, no desde la URL
    $userData = usuarioActual();
    
    if (!$userData) {
        echo json_encode(['error' => 'Usuario no autenticado']);
        return;
    }
    
    $usuario_id = $userData['id'];
    $rol_usuario = $userData['rol'];
    $puede_ver_todo = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    if (!$pedido_id) {
        throw new Exception('ID de pedido requerido');
    }

    // Obtener información completa del pedido
    $sql = "SELECT 
            p.*,
            c.empresa as cliente_nombre,
            c.nit as cliente_nit,
            c.contacto1 as cliente_contacto,
            c.telefono1 as cliente_telefono,
            c.email as cliente_email,
            c.direccion as cliente_direccion,
            u.nombre_completo as vendedor_nombre,
            v.codigo as venta_codigo,
            v.dte as dte_venta
        FROM pedidos p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN ventas v ON p.venta_id = v.id
        WHERE p.id = ?";
    
    // Si es vendedor, verificar que sea su pedido
    if (!$puede_ver_todo && $usuario_id) {
        $sql .= " AND p.usuario_id = " . intval($usuario_id);
    }
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    
    if (!$pedido) {
        if (!$puede_ver_todo) {
            throw new Exception('Pedido no encontrado o no tiene permisos para verlo');
        } else {
            throw new Exception('Pedido no encontrado');
        }
    }
    
    $pedido['dte'] = $pedido['dte_venta'];
    
    // Obtener detalles de productos del pedido
$sql = "SELECT 
            pd.*,
            pd.es_complemento,
            pr.codigo as producto_codigo,
            pr.nombre as producto_nombre
        FROM pedidos_detalle pd
        LEFT JOIN productos pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
        ORDER BY pd.id";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $pedido_id);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Obtener historial del pedido
    $sql = "SELECT 
                ph.*,
                u.nombre_completo as usuario_nombre
            FROM pedidos_historial ph
            LEFT JOIN usuarios u ON ph.usuario_id = u.id
            WHERE ph.pedido_id = ?
            ORDER BY ph.fecha_cambio DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $historial = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Obtener información de empaque si existe
    $sql = "SELECT 
                pe.*,
                u.nombre_completo as empacador_nombre
            FROM pedidos_empaque pe
            LEFT JOIN usuarios u ON pe.empacado_por = u.id
            WHERE pe.pedido_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $empaque = $stmt->get_result()->fetch_assoc();
    
    // Obtener información de envío si existe
    $sql = "SELECT 
                pv.*,
                u.nombre_completo as registro_usuario
            FROM pedidos_envio pv
            LEFT JOIN usuarios u ON pv.usuario_registro = u.id
            WHERE pv.pedido_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $envio = $stmt->get_result()->fetch_assoc();
    
    // Obtener información de entrega si existe
    $sql = "SELECT 
                pe.*,
                u.nombre_completo as registro_usuario
            FROM pedidos_entrega pe
            LEFT JOIN usuarios u ON pe.usuario_registro = u.id
            WHERE pe.pedido_id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $entrega = $stmt->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'pedido' => $pedido,
        'detalles' => $detalles,
        'historial' => $historial,
        'empaque' => $empaque,
        'envio' => $envio,
        'entrega' => $entrega,
        'permisos' => [
            'puede_gestionar' => $puede_ver_todo,
            'rol_usuario' => $rol_usuario
        ]
    ]);
}

function obtenerDetallePedido($conexion, $pedido_id) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $rol_usuario = $_SESSION['rol'] ?? '';
    $puede_gestionar = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    if (!$puede_gestionar) {
        throw new Exception('No tiene permisos para modificar pedidos');
    }
    
    if (!$pedido_id) {
        throw new Exception('ID de pedido requerido');
    }

    // Verificar que el pedido esté en estado pendiente
    $sql = "SELECT p.*, c.empresa as cliente_nombre, c.nit, c.direccion, c.contacto1, c.telefono1,
               v.dte as dte_venta,
               v.descuento as descuento_venta
        FROM pedidos p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        LEFT JOIN ventas v ON p.venta_id = v.id
        WHERE p.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $pedido_id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    
    if (!$pedido) {
        throw new Exception('Pedido no encontrado');
    }
    
    // Validar que el pedido se pueda modificar (solo pendiente, empacado y enviado)
$estados_editables = ['pendiente', 'empacado', 'enviado'];

if (!in_array($pedido['estado'], $estados_editables)) {
    throw new Exception('No se pueden modificar pedidos en estado ' . $pedido['estado'] . '. Solo se permiten modificaciones en pedidos pendientes, empacados o enviados.');
}
    
    $pedido['dte'] = $pedido['dte_venta'];
    
// Obtener detalles del pedido
$sql = "SELECT pd.*, 
               pd.es_complemento,
               pr.codigo, 
               pr.nombre, 
               pr.precio_venta_sugerido, 
               pr.precio_venta_real, 
               pr.stock
        FROM pedidos_detalle pd
        LEFT JOIN productos pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
        ORDER BY pd.id";

$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $pedido_id);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pedido' => $pedido,
        'detalles' => $detalles
    ]);
}

function obtenerProductosDisponibles($conexion) {
    $sql = "SELECT id, codigo, nombre, precio_venta_sugerido, precio_venta_real, stock
            FROM productos 
            WHERE estado = 1 AND stock > 0
            ORDER BY nombre";
    
    $result = $conexion->query($sql);
    $productos = [];
    
    if ($result && $result->num_rows > 0) {
        while($producto = $result->fetch_assoc()) {
            $productos[] = $producto;
        }
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productos
    ]);
}

function modificarPedido($conexion, $input) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $rol_usuario = $_SESSION['rol'] ?? '';
    $puede_gestionar = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    if (!$puede_gestionar) {
        throw new Exception('No tiene permisos para modificar pedidos');
    }
    
    $pedido_id = $input['pedido_id'] ?? null;
    $productos = $input['productos'] ?? [];
    $usuario_id = $input['usuario_id'] ?? 1;
    $dte = $input['dte'] ?? null;
    // ✅ DESCUENTO: puede venir del modal; si no viene, se conserva el actual
    $descuento_recibido = (isset($input['descuento']) && $input['descuento'] !== '')
        ? round((float)$input['descuento'], 2)
        : null;
    
    if (!$pedido_id) {
        throw new Exception('ID de pedido requerido');
    }
    
    if (empty($productos)) {
        throw new Exception('Debe incluir al menos un producto');
    }
    
    $conexion->autocommit(false);
    
    try {
        // Verificar estado del pedido
        $sql = "SELECT p.estado, p.venta_id, v.descuento AS descuento_actual, v.estado AS venta_estado
                FROM pedidos p
                JOIN ventas v ON v.id = p.venta_id
                WHERE p.id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        
        // N5 (16-09-2026): un pedido entregado o cancelado ya no se puede editar,
        // para no descuadrar inventario ni créditos ya cobrados.
        $estados_editables = ['pendiente', 'empacado', 'enviado'];
        
        if (!$pedido) {
            throw new Exception('Pedido no encontrado');
        }
        if ((int)$pedido['venta_estado'] === 0) {
            throw new Exception('No se puede modificar el pedido de una venta anulada.');
        }
        if (!in_array($pedido['estado'], $estados_editables)) {
            throw new Exception('No se pueden modificar pedidos en estado ' . $pedido['estado'] . '. Solo se permiten modificaciones en pedidos pendientes, empacados o enviados.');
        }
        
        $venta_id = $pedido['venta_id'];
        
        // Obtener productos actuales del pedido para restaurar stock
        $sql = "SELECT producto_id, cantidad FROM pedidos_detalle WHERE pedido_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $productos_actuales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Restaurar stock de productos actuales
        foreach ($productos_actuales as $producto_actual) {
            $sql = "UPDATE productos SET stock = stock + ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('ii', $producto_actual['cantidad'], $producto_actual['producto_id']);
            $stmt->execute();
            
            registrarHistorialStock($conexion, $producto_actual['producto_id'], 'agregar', 
                                  $producto_actual['cantidad'], $usuario_id, 'Restauración por modificación de pedido');
        }
        
        // Eliminar detalles actuales del pedido y venta
        $sql = "DELETE FROM pedidos_detalle WHERE pedido_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        
        $sql = "DELETE FROM venta_detalle WHERE venta_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $venta_id);
        $stmt->execute();
        
$nuevo_subtotal = 0;

foreach ($productos as $producto) {
    $producto_id = $producto['producto_id'];
    $cantidad = $producto['cantidad'];
    $precio_unitario = $producto['precio_unitario'];
    
    // ✅ NUEVO: Obtener es_complemento
    $es_complemento = isset($producto['es_complemento']) ? intval($producto['es_complemento']) : 0;
    
    // Redondeo explícito a 2 decimales
    $subtotal_producto = round($cantidad * $precio_unitario, 2);
    
    // Verificar stock disponible
    $sql = "SELECT stock, nombre FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $producto_info = $stmt->get_result()->fetch_assoc();
    
    if (!$producto_info) {
        throw new Exception("Producto con ID $producto_id no encontrado");
    }
    
    if ($producto_info['stock'] < $cantidad) {
        throw new Exception("Stock insuficiente para {$producto_info['nombre']}. Disponible: {$producto_info['stock']}, Solicitado: $cantidad");
    }
    
    // ✅ MODIFICADO: Insertar en pedidos_detalle con es_complemento
    $sql = "INSERT INTO pedidos_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal, es_complemento) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('iiiddi', $pedido_id, $producto_id, $cantidad, $precio_unitario, $subtotal_producto, $es_complemento);
    $stmt->execute();
    
    // ✅ MODIFICADO: Insertar en venta_detalle con es_complemento
    $sql = "INSERT INTO venta_detalle (venta_id, producto_id, cantidad, precio_unitario, subtotal, es_complemento) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('iiiddi', $venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal_producto, $es_complemento);
    $stmt->execute();
    
    // Actualizar stock
    $sql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $cantidad, $producto_id);
    $stmt->execute();
    
    registrarHistorialStock($conexion, $producto_id, 'reducir', $cantidad, $usuario_id, 'Modificación de pedido');
    
    // ✅ CRÍTICO: Solo sumar al subtotal si NO es complemento
    if ($es_complemento === 0) {
        $nuevo_subtotal += $subtotal_producto;
    }
}

// Asegurar redondeo final
$nuevo_subtotal = round($nuevo_subtotal, 2);

        // ✅ DESCUENTO: validar y calcular el total real
        $descuento_anterior = round((float)$pedido['descuento_actual'], 2);
        $nuevo_descuento = $descuento_recibido ?? $descuento_anterior;

        if ($nuevo_descuento < 0) {
            throw new Exception('El descuento no puede ser negativo');
        }
        if ($nuevo_descuento > $nuevo_subtotal) {
            throw new Exception('El descuento (Q' . number_format($nuevo_descuento, 2) .
                ') no puede ser mayor al subtotal (Q' . number_format($nuevo_subtotal, 2) . ')');
        }
        $nuevo_total = round($nuevo_subtotal - $nuevo_descuento, 2);

        // N5 (16-09-2026): si la venta es a crédito, el nuevo total no puede quedar
        // por debajo de lo ya cobrado (anticipo + abonos + descuentos aplicados),
        // porque eso dejaría el saldo pendiente en negativo (pago de más).
        $sql_pagado = "SELECT v.tipo_venta,
                              IFNULL(vc.anticipo, 0) AS anticipo,
                              IFNULL((SELECT SUM(hp.monto + IFNULL(hp.descuento, 0))
                                      FROM historial_pagos hp
                                      WHERE hp.factura = v.codigo
                                        AND hp.estado IN ('Aprobado', '')), 0) AS pagos
                       FROM ventas v
                       LEFT JOIN venta_credito vc ON vc.venta_id = v.id
                       WHERE v.id = ?";
        $stmt_pg = $conexion->prepare($sql_pagado);
        $stmt_pg->bind_param('i', $venta_id);
        $stmt_pg->execute();
        $info_pago = $stmt_pg->get_result()->fetch_assoc();
        $stmt_pg->close();

        if ($info_pago && $info_pago['tipo_venta'] === 'credito') {
            $ya_cobrado = round((float)$info_pago['anticipo'] + (float)$info_pago['pagos'], 2);
            if ($nuevo_total < $ya_cobrado - 0.01) {
                throw new Exception('El nuevo total (Q' . number_format($nuevo_total, 2) .
                    ') no puede ser menor a lo ya cobrado en este crédito (Q' .
                    number_format($ya_cobrado, 2) . '). Ajuste el descuento o los productos, ' .
                    'o registre una nota de crédito.');
            }
        }
        
        // Actualizar totales en ventas (ahora con descuento)
        if ($dte !== null) {
            $dte_valor = trim($dte) === '' ? NULL : trim($dte);
            $sql = "UPDATE ventas SET subtotal = ?, descuento = ?, total = ?, dte = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('dddsi', $nuevo_subtotal, $nuevo_descuento, $nuevo_total, $dte_valor, $venta_id);
        } else {
            $sql = "UPDATE ventas SET subtotal = ?, descuento = ?, total = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param('dddi', $nuevo_subtotal, $nuevo_descuento, $nuevo_total, $venta_id);
        }
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar la venta: ' . $stmt->error);
        }

        // Actualizar totales del pedido. Se hace explícito (no solo por trigger)
        // porque los triggers de pedidos_detalle suman también los complementos
        // y leen el descuento viejo.
        $sql = "UPDATE pedidos SET subtotal = ?, descuento = ?, total = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('dddi', $nuevo_subtotal, $nuevo_descuento, $nuevo_total, $pedido_id);
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar el pedido: ' . $stmt->error);
        }

        // ===========================================================
        // ✅ FIX (descuadre de créditos al editar pedidos):
        // Este UPDATE cambia ventas.total pero NO inserta/edita nada en
        // historial_pagos, así que el trigger hp_ai/au_recalcular_credito
        // NUNCA se dispara aquí. Si la venta es a crédito, venta_credito.
        // saldo_pendiente se queda calculado contra el total viejo y todo
        // se descuadra (el mismo bug que ya se había corregido en
        // abonos.php). Se llama al mismo procedimiento que usan los
        // triggers para recalcular el saldo de forma consistente:
        //   saldo_pendiente = total - anticipo - SUM(monto) - SUM(descuento)
        // ===========================================================
        $sql_venta_credito = "SELECT codigo, tipo_venta FROM ventas WHERE id = ?";
        $stmt_vc = $conexion->prepare($sql_venta_credito);
        $stmt_vc->bind_param('i', $venta_id);
        $stmt_vc->execute();
        $venta_actualizada = $stmt_vc->get_result()->fetch_assoc();
        $stmt_vc->close();

        if ($venta_actualizada && $venta_actualizada['tipo_venta'] === 'credito') {
            $codigo_venta_credito = $venta_actualizada['codigo'];
            $stmt_recalc = $conexion->prepare("CALL recalcular_credito_por_codigo(?)");
            $stmt_recalc->bind_param('s', $codigo_venta_credito);
            $stmt_recalc->execute();
            $stmt_recalc->close();
            // Drenar resultsets pendientes que deja un CALL en mysqli,
            // para no romper las siguientes consultas preparadas.
            while ($conexion->more_results() && $conexion->next_result()) { ; }

            // Actualizar la cuota informativa según el nuevo saldo y los plazos,
            // para que no quede con el valor viejo tras modificar el pedido.
            $stmt_cuota = $conexion->prepare(
                "UPDATE venta_credito SET monto_cuota = ROUND(saldo_pendiente / GREATEST(plazos, 1), 2) WHERE venta_id = ?");
            $stmt_cuota->bind_param('i', $venta_id);
            $stmt_cuota->execute();
            $stmt_cuota->close();
        }

        // Registrar en historial de pedidos
        $estado_actual = $pedido['estado'];
        $nota_historial = 'Pedido modificado';
        if (abs($nuevo_descuento - $descuento_anterior) >= 0.01) {
            $nota_historial .= ' | Descuento: Q' . number_format($descuento_anterior, 2) .
                               ' -> Q' . number_format($nuevo_descuento, 2);
        }
        $sql = "INSERT INTO pedidos_historial (pedido_id, estado_anterior, estado_nuevo, usuario_id, notas) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('issis', $pedido_id, $estado_actual, $estado_actual, $usuario_id, $nota_historial);
        $stmt->execute();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido modificado exitosamente',
            'nuevo_subtotal' => number_format($nuevo_subtotal, 2),
            'nuevo_descuento' => number_format($nuevo_descuento, 2),
            'nuevo_total' => number_format($nuevo_total, 2)
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        throw $e;
    }
}

function registrarHistorialStock($conexion, $producto_id, $operacion, $cantidad, $usuario_id, $motivo) {
    // Obtener información del producto y stock actual
    $sql = "SELECT nombre, stock FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $producto_id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    
    if (!$producto) return;
    
    $stock_anterior = $operacion === 'agregar' ? $producto['stock'] - $cantidad : $producto['stock'] + $cantidad;
    $stock_nuevo = $producto['stock'];
    
    // Obtener nombre del usuario
    $sql = "SELECT nombre_completo FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $usuario_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $nombre_usuario = $usuario ? $usuario['nombre_completo'] : 'Sistema';
    
    // Insertar en historial
    $sql = "INSERT INTO historial_stock (producto_id, producto_nombre, operacion, cantidad, stock_anterior, stock_nuevo, usuario_responsable) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('issiiis', $producto_id, $producto['nombre'], $operacion, $cantidad, $stock_anterior, $stock_nuevo, $nombre_usuario);
    $stmt->execute();
}

function empacarPedido($conexion, $input) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $rol_usuario = $_SESSION['rol'] ?? '';
    $puede_gestionar = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    if (!$puede_gestionar) {
        throw new Exception('No tiene permisos para empacar pedidos');
    }
    
    // Configurar zona horaria
    date_default_timezone_set('America/Guatemala');
    
    $pedido_id = $input['pedido_id'] ?? null;
    $fecha_empaque = $input['fecha_empaque'] ?? null;
    $notas_empaque = $input['notas_empaque'] ?? '';
    $usuario_id = $input['usuario_id'] ?? 1; // Obtener del sistema de sesiones actual
    
    if (!$pedido_id) {
        throw new Exception('ID de pedido requerido');
    }
    
    if (!$fecha_empaque) {
        throw new Exception('Fecha de empaque requerida');
    }
    
    if (!$usuario_id) {
        throw new Exception('Usuario no identificado');
    }
    
    // Validar fecha en servidor con zona horaria correcta
    try {
        $fecha_seleccionada = new DateTime($fecha_empaque);
        $ahora = new DateTime();
        
        if ($fecha_seleccionada > $ahora) {
            throw new Exception('La fecha de empaque no puede ser futura');
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'futura') !== false) {
            throw $e;
        }
        throw new Exception('Formato de fecha inválido');
    }
    
    $conexion->autocommit(false);
    
    try {
        // Verificar que el pedido esté en estado pendiente
        $sql = "SELECT estado FROM pedidos WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        
        if (!$pedido) {
            throw new Exception('Pedido no encontrado');
        }
        
        if ($pedido['estado'] !== 'pendiente') {
            throw new Exception('Solo se pueden empacar pedidos en estado pendiente');
        }
        
        // Verificar que no esté ya empacado
        $sql = "SELECT id FROM pedidos_empaque WHERE pedido_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $empaque_existente = $stmt->get_result()->fetch_assoc();
        
        if ($empaque_existente) {
            throw new Exception('Este pedido ya ha sido empacado');
        }
        
        // Registrar el empaque con el usuario actual de la sesión
        $sql = "INSERT INTO pedidos_empaque (pedido_id, empacado_por, fecha_empaque, notas_empaque, verificado) 
                VALUES (?, ?, ?, ?, 1)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('iiss', $pedido_id, $usuario_id, $fecha_empaque, $notas_empaque);
        $stmt->execute();
        
        // Cambiar estado del pedido a 'empacado'
        $sql = "UPDATE pedidos SET estado = 'empacado' WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        
        // Registrar en historial con el usuario actual
        $sql = "INSERT INTO pedidos_historial (pedido_id, estado_anterior, estado_nuevo, usuario_id, notas) 
                VALUES (?, 'pendiente', 'empacado', ?, ?)";
        $notas_historial = 'Pedido marcado como empacado' . ($notas_empaque ? '. Notas: ' . $notas_empaque : '');
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('iis', $pedido_id, $usuario_id, $notas_historial);
        $stmt->execute();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido empacado exitosamente'
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        throw $e;
    }
}

function enviarPedido($conexion, $input) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $rol_usuario = $_SESSION['rol'] ?? '';
    $puede_gestionar = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    if (!$puede_gestionar) {
        throw new Exception('No tiene permisos para enviar pedidos');
    }
    
    // Configurar zona horaria
    date_default_timezone_set('America/Guatemala');
    
    $pedido_id = $input['pedido_id'] ?? null;
    $fecha_envio = $input['fecha_envio'] ?? null;
    $transportista = $input['transportista'] ?? '';
    $numero_guia = $input['numero_guia'] ?? '';
    $costo_envio = $input['costo_envio'] ?? 0.00;
    $fecha_entrega_estimada = $input['fecha_entrega_estimada'] ?? null;
    $notas_envio = $input['notas_envio'] ?? '';
    $usuario_id = $input['usuario_id'] ?? 1; // Obtener del sistema de sesiones actual
    
    if (!$pedido_id) {
        throw new Exception('ID de pedido requerido');
    }
    
    if (!$fecha_envio) {
        throw new Exception('Fecha de envío requerida');
    }
    
    if (!$usuario_id) {
        throw new Exception('Usuario no identificado');
    }
    
    // CORRECCIÓN: Validar fecha en servidor con mayor flexibilidad para mismo día
    try {
        $fecha_seleccionada = new DateTime($fecha_envio);
        $ahora = new DateTime();
        
        // Permitir hasta 30 minutos en el futuro para compensar diferencias de sincronización
        $fecha_limite = clone $ahora;
        $fecha_limite->add(new DateInterval('PT30M')); // +30 minutos
        
        // Fecha mínima: 30 días atrás
        $fecha_minima = clone $ahora;
        $fecha_minima->sub(new DateInterval('P30D')); // -30 días
        
        if ($fecha_seleccionada > $fecha_limite) {
            throw new Exception('La fecha de envío no puede ser futura (máximo 30 minutos de tolerancia)');
        }
        
        if ($fecha_seleccionada < $fecha_minima) {
            throw new Exception('La fecha de envío no puede ser anterior a 30 días');
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'futura') !== false || strpos($e->getMessage(), 'anterior') !== false) {
            throw $e;
        }
        throw new Exception('Formato de fecha inválido: ' . $e->getMessage());
    }
    
    $conexion->autocommit(false);
    
    try {
        // Verificar que el pedido esté en estado empacado
        $sql = "SELECT estado FROM pedidos WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        
        if (!$pedido) {
            throw new Exception('Pedido no encontrado');
        }
        
        if ($pedido['estado'] !== 'empacado') {
            throw new Exception('Solo se pueden enviar pedidos en estado empacado');
        }
        
        // Verificar que no esté ya enviado
        $sql = "SELECT id FROM pedidos_envio WHERE pedido_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $envio_existente = $stmt->get_result()->fetch_assoc();
        
        if ($envio_existente) {
            throw new Exception('Este pedido ya ha sido enviado');
        }
        
        // Registrar el envío
        $sql = "INSERT INTO pedidos_envio (pedido_id, transportista, numero_guia, costo_envio, fecha_envio, fecha_entrega_estimada, notas_envio, usuario_registro) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('issdsssi', $pedido_id, $transportista, $numero_guia, $costo_envio, $fecha_envio, $fecha_entrega_estimada, $notas_envio, $usuario_id);
        $stmt->execute();
        
        // Cambiar estado del pedido a 'enviado'
        $sql = "UPDATE pedidos SET estado = 'enviado' WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        
        // Registrar en historial
        $sql = "INSERT INTO pedidos_historial (pedido_id, estado_anterior, estado_nuevo, usuario_id, notas) 
                VALUES (?, 'empacado', 'enviado', ?, ?)";
        $notas_historial = 'Pedido marcado como enviado';
        if ($transportista) $notas_historial .= '. Transportista: ' . $transportista;
        if ($numero_guia) $notas_historial .= '. Guía: ' . $numero_guia;
        if ($notas_envio) $notas_historial .= '. Notas: ' . $notas_envio;
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('iis', $pedido_id, $usuario_id, $notas_historial);
        $stmt->execute();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido enviado exitosamente'
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        throw $e;
    }
}

function entregarPedido($conexion, $input) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $rol_usuario = $_SESSION['rol'] ?? '';
    $puede_gestionar = in_array($rol_usuario, ['administrador', 'bodeguero']);
    
    if (!$puede_gestionar) {
        throw new Exception('No tiene permisos para entregar pedidos');
    }
    
    // Configurar zona horaria
    date_default_timezone_set('America/Guatemala');
    
    $pedido_id = $input['pedido_id'] ?? null;
    $fecha_entrega = $input['fecha_entrega'] ?? null;
    $recibido_por = trim($input['recibido_por'] ?? '');
    $notas_entrega = trim($input['notas_entrega'] ?? '');
    $usuario_id = $input['usuario_id'] ?? 1; // Obtener del sistema de sesiones actual
    
    if (!$pedido_id) {
        throw new Exception('ID de pedido requerido');
    }
    
    if (!$fecha_entrega) {
        throw new Exception('Fecha de entrega requerida');
    }
    
    if (!$recibido_por) {
        throw new Exception('El nombre de quien recibió el pedido es requerido');
    }
    
    if (strlen($recibido_por) < 3) {
        throw new Exception('El nombre debe tener al menos 3 caracteres');
    }
    
    if (!$usuario_id) {
        throw new Exception('Usuario no identificado');
    }
    
    // CORRECCIÓN: Validar fecha en servidor con mayor flexibilidad para mismo día
    try {
        $fecha_seleccionada = new DateTime($fecha_entrega);
        $ahora = new DateTime();
        
        // Permitir hasta 30 minutos en el futuro para compensar diferencias de sincronización
        $fecha_limite = clone $ahora;
        $fecha_limite->add(new DateInterval('PT30M')); // +30 minutos
        
        // Fecha mínima: 30 días atrás
        $fecha_minima = clone $ahora;
        $fecha_minima->sub(new DateInterval('P30D')); // -30 días
        
        if ($fecha_seleccionada > $fecha_limite) {
            throw new Exception('La fecha de entrega no puede ser futura (máximo 30 minutos de tolerancia)');
        }
        
        if ($fecha_seleccionada < $fecha_minima) {
            throw new Exception('La fecha de entrega no puede ser anterior a 30 días');
        }
        
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'futura') !== false || strpos($e->getMessage(), 'anterior') !== false) {
            throw $e;
        }
        throw new Exception('Formato de fecha inválido: ' . $e->getMessage());
    }
    
    // Manejar archivo de comprobante si se subió
    $nombre_archivo = null;
    if (isset($_FILES['comprobante_entrega']) && $_FILES['comprobante_entrega']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['comprobante_entrega'];
        
        // Validar tipo de archivo
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipo_archivo = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($tipo_archivo, $tipos_permitidos)) {
            throw new Exception('Solo se permiten archivos de imagen (JPG, PNG) o PDF');
        }
        
        // Validar tamaño (máximo 5MB)
        if ($archivo['size'] > 5 * 1024 * 1024) {
            throw new Exception('El archivo no puede ser mayor a 5MB');
        }
        
        // Crear directorio si no existe
        $directorio_destino = 'uploads/comprobantes/';
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0755, true);
        }
        
        // SEGURIDAD (16-09-2026): la extensión la decide el servidor según el tipo real
        require_once __DIR__ . '/subidas.php';
        $nombre_archivo = guardarArchivoSubido($archivo, $directorio_destino, ['imagen', 'pdf'], 'comprobante_' . (int)$pedido_id . '_' . time());
    }
    
    $conexion->autocommit(false);
    
    try {
        // Verificar que el pedido esté en estado enviado
        $sql = "SELECT estado FROM pedidos WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $pedido = $stmt->get_result()->fetch_assoc();
        
        if (!$pedido) {
            throw new Exception('Pedido no encontrado');
        }
        
        if ($pedido['estado'] !== 'enviado') {
            throw new Exception('Solo se pueden entregar pedidos en estado enviado');
        }
        
        // Verificar que no esté ya entregado
        $sql = "SELECT id FROM pedidos_entrega WHERE pedido_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        $entrega_existente = $stmt->get_result()->fetch_assoc();
        
        if ($entrega_existente) {
            throw new Exception('Este pedido ya ha sido entregado');
        }
        
        // Registrar la entrega
        $sql = "INSERT INTO pedidos_entrega (pedido_id, fecha_entrega, recibido_por, comprobante_entrega, notas_entrega, usuario_registro) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('issssi', $pedido_id, $fecha_entrega, $recibido_por, $nombre_archivo, $notas_entrega, $usuario_id);
        $stmt->execute();
        
        // Cambiar estado del pedido a 'entregado'
        $sql = "UPDATE pedidos SET estado = 'entregado' WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
        $stmt->execute();
        
        // Registrar en historial
        $sql = "INSERT INTO pedidos_historial (pedido_id, estado_anterior, estado_nuevo, usuario_id, notas) 
                VALUES (?, 'enviado', 'entregado', ?, ?)";
        $notas_historial = 'Pedido marcado como entregado. Recibido por: ' . $recibido_por;
        if ($notas_entrega) $notas_historial .= '. Notas: ' . $notas_entrega;
        if ($nombre_archivo) $notas_historial .= '. Comprobante adjunto: ' . $nombre_archivo;
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('iis', $pedido_id, $usuario_id, $notas_historial);
        $stmt->execute();
        
        $conexion->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido entregado exitosamente',
            'comprobante' => $nombre_archivo ? $directorio_destino . $nombre_archivo : null
        ]);
        
    } catch (Exception $e) {
        $conexion->rollback();
        
        // Eliminar archivo si se subió pero hubo error
        if ($nombre_archivo && file_exists($directorio_destino . $nombre_archivo)) {
            unlink($directorio_destino . $nombre_archivo);
        }
        
        throw $e;
    }
}

$conexion->close();
?>