<?php


// Iniciar buffer de salida para capturar cualquier mensaje de error
ob_start();

// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();

// Evitar caché para que botón atrás no muestre esta página sin validar sesión
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

$usuario_id = $_SESSION['usuario_id'] ?? null;
$rol = $_SESSION['rol'] ?? 'vendedor';  // Asumo que el rol viene así

if (!$usuario_id) {
    enviarRespuesta('error', 'Usuario no autenticado o no definido');
    exit;
}

// Desactivar la notificación de errores para producción
error_reporting(0);
ini_set('display_errors', 0);

// Obtener el ID del usuario


// Incluir la conexión a la base de datos
require_once __DIR__ . '/../../../privado/conexion.php';

// Configurar headers para JSON y CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Función para enviar respuesta
function enviarRespuesta($status, $message, $data = null) {
    // Limpiar cualquier output previo
    if (ob_get_length()) ob_end_clean();
    
    $response = [
        'status' => $status,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Función para formatear el tiempo transcurrido en un formato legible
 * @param int $timestamp Timestamp de la fecha a formatear
 * @return string Tiempo transcurrido en formato legible
 */
function formatearTiempoTranscurrido($timestamp) {
    $diferencia = time() - $timestamp;
    
    if ($diferencia < 60) {
        return "Hace un momento";
    } elseif ($diferencia < 3600) {
        $minutos = round($diferencia / 60);
        return "Hace " . $minutos . " minuto" . ($minutos > 1 ? "s" : "");
    } elseif ($diferencia < 86400) {
        $horas = round($diferencia / 3600);
        return "Hace " . $horas . " hora" . ($horas > 1 ? "s" : "");
    } elseif ($diferencia < 604800) {
        $dias = round($diferencia / 86400);
        return "Hace " . $dias . " día" . ($dias > 1 ? "s" : "");
    } elseif ($diferencia < 2592000) {
        $semanas = round($diferencia / 604800);
        return "Hace " . $semanas . " semana" . ($semanas > 1 ? "s" : "");
    } else {
        return date('d/m/Y', $timestamp);
    }
}

try {
    // Preparar estructura básica de datos del dashboard
    $dashboardData = [
        'totalClientes' => 0,
        'totalProductos' => 0,
        'ventasMes' => 'Q 0',
        'stockBajo' => 0,
        'actividadReciente' => []
    ];

    // Intentar obtener datos reales de la base de datos con manejo de errores
   try {
    if ($rol === 'administrador' || $rol === 'bodeguero') {
        $sqlClientes = "SELECT COUNT(*) as total FROM clientes WHERE estado = 1";
        $stmt = $conexion->prepare($sqlClientes);
        $stmt->execute();
    } else {
        $sqlClientes = "SELECT COUNT(*) as total FROM clientes WHERE estado = 1 AND id_vendedor = ?";
        $stmt = $conexion->prepare($sqlClientes);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
    }

    $resultClientes = $stmt->get_result();
    if ($resultClientes && $resultClientes->num_rows > 0) {
        $row = $resultClientes->fetch_assoc();
        $dashboardData['totalClientes'] = intval($row['total']);
    }
} catch (Exception $e) {
    error_log("Error al obtener total de clientes: " . $e->getMessage());
    // Se mantiene el valor predeterminado
}

    try {
        // 2. Total de productos
        $sqlProductos = "SELECT COUNT(*) as total FROM productos WHERE estado = 1";
        $resultProductos = $conexion->query($sqlProductos);
        if ($resultProductos && $resultProductos->num_rows > 0) {
            $row = $resultProductos->fetch_assoc();
            $dashboardData['totalProductos'] = intval($row['total']);
        }
    } catch (Exception $e) {
        error_log("Error al obtener total de productos: " . $e->getMessage());
        // Mantener el valor predeterminado en caso de error
    }

    try {
    // Obtener mes y año actual
    $mesActual = date('m');
    $añoActual = date('Y');

    if ($rol === 'administrador') {
        // Ventas totales del mes (solo para administrador)
        $sqlVentas = "SELECT SUM(total) as total FROM ventas 
                      WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND estado = 1";
        $stmt = $conexion->prepare($sqlVentas);
        $stmt->bind_param("ss", $mesActual, $añoActual);
        
        // Ejecutar y obtener resultado
        $stmt->execute();
        $resultVentas = $stmt->get_result();

        if ($resultVentas && $resultVentas->num_rows > 0) {
            $row = $resultVentas->fetch_assoc();
            $totalVentas = $row['total'] ? floatval($row['total']) : 0;
            $dashboardData['ventasMes'] = 'Q ' . number_format($totalVentas, 2);
        }
    }
    
    /* BODEGUERO Y VENDEDOR NO PUEDEN VER SUS VENTAS 28/11/2025
    else {
        // Ventas del mes correspondientes al usuario actual (vendedor o bodeguero)
        $sqlVentas = "SELECT SUM(total) as total FROM ventas 
                      WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND usuario_id = ?";
                      
        $stmt = $conexion->prepare($sqlVentas);
        $stmt->bind_param("ssi", $mesActual, $añoActual, $usuario_id);
        
        $stmt->execute();
        $resultVentas = $stmt->get_result();

        if ($resultVentas && $resultVentas->num_rows > 0) {
            $row = $resultVentas->fetch_assoc();
            $totalVentas = $row['total'] ? floatval($row['total']) : 0;
            $dashboardData['ventasMes'] = 'Q ' . number_format($totalVentas, 2);
        }
    }
    */
    
} catch (Exception $e) {
    error_log("Error al obtener total de ventas: " . $e->getMessage());
}


    try {
        // 4. Productos en stock bajo
        
                         $sqlStockBajo = "SELECT COUNT(*) as total FROM productos 
                 WHERE stock < 10 AND estado = 1";
        $resultStockBajo = $conexion->query($sqlStockBajo);
        if ($resultStockBajo && $resultStockBajo->num_rows > 0) {
            $row = $resultStockBajo->fetch_assoc();
            $dashboardData['stockBajo'] = intval($row['total']);
        }
    } catch (Exception $e) {
        error_log("Error al obtener stock bajo: " . $e->getMessage());
        // Mantener el valor predeterminado en caso de error
    }


try {
    // 5. Pedidos pendientes
    $sqlPedidosPendientes = "SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'";
    $resultPedidosPendientes = $conexion->query($sqlPedidosPendientes);
    if ($resultPedidosPendientes && $resultPedidosPendientes->num_rows > 0) {
        $row = $resultPedidosPendientes->fetch_assoc();
        $dashboardData['pedidosPendientes'] = intval($row['total']);
    }
} catch (Exception $e) {
    error_log("Error al obtener pedidos pendientes: " . $e->getMessage());
    // Mantener valor por defecto o manejar el error
}
    // Array para almacenar las actividades
    $actividades = [];

    // Intentar obtener cada tipo de actividad con manejo de errores individual
    try {
        // 5.1 Últimas ventas
        $sqlVentasRecientes = "SELECT 'venta' as tipo, 'Nueva venta registrada' as descripcion, 
                              fecha, 'shopping-cart' as icono, 'text-primary' as colorClase 
                              FROM ventas ORDER BY fecha DESC LIMIT 2";
        $resultVentasRecientes = $conexion->query($sqlVentasRecientes);
        if ($resultVentasRecientes && $resultVentasRecientes->num_rows > 0) {
            while ($row = $resultVentasRecientes->fetch_assoc()) {
                $fechaVenta = strtotime($row['fecha']);
                $tiempoTranscurrido = formatearTiempoTranscurrido($fechaVenta);
                $row['tiempo'] = $tiempoTranscurrido;
                unset($row['fecha']); // Eliminar la fecha original
                $actividades[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error al obtener ventas recientes: " . $e->getMessage());
    }

    try {
        // 5.2 Últimos clientes añadidos
        $sqlClientesRecientes = "SELECT 'cliente' as tipo, 'Nuevo cliente agregado' as descripcion, 
                                fecha_registro as fecha, 'user-plus' as icono, 'text-success' as colorClase 
                                FROM clientes ORDER BY fecha_registro DESC LIMIT 1";
        $resultClientesRecientes = $conexion->query($sqlClientesRecientes);
        if ($resultClientesRecientes && $resultClientesRecientes->num_rows > 0) {
            while ($row = $resultClientesRecientes->fetch_assoc()) {
                $fechaCliente = strtotime($row['fecha']);
                $tiempoTranscurrido = formatearTiempoTranscurrido($fechaCliente);
                $row['tiempo'] = $tiempoTranscurrido;
                unset($row['fecha']); // Eliminar la fecha original
                $actividades[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error al obtener clientes recientes: " . $e->getMessage());
    }

    // Verificar si no se obtuvo ninguna actividad
    if (empty($actividades)) {
        // Si no hay actividades, usar datos de respaldo
        $actividades = [
            [
                'tipo' => 'venta', 
                'descripcion' => 'Nueva venta registrada', 
                'tiempo' => 'Hace 10 minutos',
                'icono' => 'shopping-cart',
                'colorClase' => 'text-primary'
            ],
            [
                'tipo' => 'cliente', 
                'descripcion' => 'Nuevo cliente agregado', 
                'tiempo' => 'Hace 2 horas',
                'icono' => 'user-plus',
                'colorClase' => 'text-success'
            ],
            [
                'tipo' => 'producto', 
                'descripcion' => 'Productos recibidos', 
                'tiempo' => 'Hace 5 horas',
                'icono' => 'truck',
                'colorClase' => 'text-warning'
            ],
            [
                'tipo' => 'pago', 
                'descripcion' => 'Pago recibido', 
                'tiempo' => 'Hace 1 día',
                'icono' => 'money-bill-wave',
                'colorClase' => 'text-info'
            ],
            [
                'tipo' => 'stock', 
                'descripcion' => 'Stock bajo de producto', 
                'tiempo' => 'Hace 2 días',
                'icono' => 'box',
                'colorClase' => 'text-danger'
            ]
        ];
    }

    // Limitar a las 5 actividades más recientes
    $dashboardData['actividadReciente'] = array_slice($actividades, 0, 5);

    // Enviar respuesta con los datos del dashboard
    enviarRespuesta('success', 'Datos del dashboard obtenidos correctamente', $dashboardData);
    
} catch (Exception $e) {
    error_log("Error al obtener datos del dashboard: " . $e->getMessage());
    
    // Manejo de error general: devolver datos de respaldo para evitar romper la interfaz
    $dashboardData = [
        'totalClientes' => 125,
        'totalProductos' => 284,
        'ventasMes' => 'Q 45,230',
        'stockBajo' => 8,
        'actividadReciente' => [
            [
                'tipo' => 'venta', 
                'descripcion' => 'Nueva venta registrada', 
                'tiempo' => 'Hace 10 minutos',
                'icono' => 'shopping-cart',
                'colorClase' => 'text-primary'
            ],
            [
                'tipo' => 'cliente', 
                'descripcion' => 'Nuevo cliente agregado', 
                'tiempo' => 'Hace 2 horas',
                'icono' => 'user-plus',
                'colorClase' => 'text-success'
            ],
            [
                'tipo' => 'producto', 
                'descripcion' => 'Productos recibidos', 
                'tiempo' => 'Hace 5 horas',
                'icono' => 'truck',
                'colorClase' => 'text-warning'
            ],
            [
                'tipo' => 'pago', 
                'descripcion' => 'Pago recibido', 
                'tiempo' => 'Hace 1 día',
                'icono' => 'money-bill-wave',
                'colorClase' => 'text-info'
            ],
            [
                'tipo' => 'stock', 
                'descripcion' => 'Stock bajo de producto', 
                'tiempo' => 'Hace 2 días',
                'icono' => 'box',
                'colorClase' => 'text-danger'
            ]
        ]
    ];
    
    enviarRespuesta('success', 'Datos de respaldo cargados debido a un error', $dashboardData);
} finally {
    if (isset($conexion)) $conexion->close();
    // Asegurarse de que nada más se envíe después
    if (ob_get_length()) ob_end_clean();
}


?>