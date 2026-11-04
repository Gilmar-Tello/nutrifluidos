<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala');
require_once '../fpdf/fpdf.php';

header('Content-Type: application/json; charset=utf-8');

// Función de sanitización
function sanitizar($dato) {
    global $conexion;
    // SEGURIDAD (16-09-2026): quita < y > para que no se pueda guardar <script> ni otras
    // etiquetas (mitiga XSS almacenado en nombres de cliente, producto, etc.).
    $dato = str_replace(array('<', '>'), '', trim((string)$dato));
    return $conexion->real_escape_string($dato);
}

// Determinar acción
$accion = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = isset($_GET['accion']) ? sanitizar($_GET['accion']) : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? sanitizar($_POST['accion']) : '';
}

// Enrutamiento de acciones
switch ($accion) {
    case 'creditos_pendientes':
        listarCreditosPendientes();
        break;
    case 'historial_pagos':
        listarHistorialPagos();
        break;
    case 'obtenerFacturasPendientes':
        obtenerFacturasPendientes();
        break;
    case 'guardarPago':
        guardarPago();
        break;
    case 'creditos_pagados':
        listarCreditosPagados();
        break;
    case 'descargar_comprobante':
        descargarComprobantePDF();
        break;
    case 'obtener_datos_factura':
        obtenerDatosFactura();
        break;
    case 'generar_reporte':
        generarReporteAbonos();
        break;
    case 'descargar_reporte':
        descargarReportePDF();
        break;
    case 'obtener_datos_reporte':
        obtenerDatosReporte();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

// Funciones específicas

/**
 * Función para generar número de recibo
 */
function generarNumeroRecibo($id_pago) {
    $dia = date('d');
    $mes = date('m');
    $anio = date('y');
    return 'NFG' . $id_pago . $dia . $mes . $anio;
}

/**
 * Función CORREGIDA 1: listarCreditosPendientes
 */
function listarCreditosPendientes() {
    global $conexion;
    
    try {
        $sql = "SELECT 
                    vc.id as id_credito,
                    v.codigo as factura,
                    c.empresa as cliente,
                    v.fecha as fecha_venta,
                    v.total as monto_total,
                    COALESCE(vc.anticipo, 0) as anticipo,
                    vc.saldo_pendiente as saldo,
                    -- Calcular el monto pagado considerando anticipo + abonos + descuentos
                    (COALESCE(vc.anticipo, 0) + COALESCE((
                        SELECT SUM(hp.monto + COALESCE(hp.descuento, 0))
                        FROM historial_pagos hp
                        WHERE hp.factura = v.codigo
                    ), 0)) as monto_pagado_total
                FROM venta_credito vc
                LEFT JOIN ventas v ON vc.venta_id = v.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE vc.saldo_pendiente > 0 AND v.estado = 1
                ORDER BY v.fecha DESC";
        
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            throw new Exception('Error en consulta: ' . $conexion->error);
        }
        
        $data = [];
        
        if ($resultado->num_rows > 0) {
            while ($row = $resultado->fetch_assoc()) {
                $data[] = [
                    'id_credito' => $row['id_credito'],
                    'factura' => $row['factura'],
                    'cliente' => $row['cliente'],
                    'fecha_venta' => $row['fecha_venta'],
                    'monto_total' => $row['monto_total'],
                    'monto_pagado' => floatval($row['monto_pagado_total']),
                    'saldo' => $row['saldo']
                ];
            }
        }
        
        echo json_encode($data);
        
    } catch (Exception $e) {
        echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
    }
    
    exit;
}

function listarHistorialPagos() {
    global $conexion;

    try {
        $sql = "SELECT id_pago, numero_recibo, factura, cliente, fecha_pago, monto, metodo_pago, comprobante, estado 
                FROM historial_pagos 
                ORDER BY fecha_pago DESC";

        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            throw new Exception('Error en consulta: ' . $conexion->error);
        }
        
        $data = [];

        while ($row = $resultado->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data);
        
    } catch (Exception $e) {
        echo json_encode(['error' => true, 'mensaje' => $e->getMessage()]);
    }

    exit;
}

/**
 * Función CORREGIDA 3: obtenerFacturasPendientes
 */
function obtenerFacturasPendientes() {
    global $conexion;
    
    try {
        $sql = "SELECT 
                    vc.id as id_credito,
                    v.codigo as factura,
                    c.empresa as cliente,
                    v.fecha as fecha_venta,
                    v.total as monto_total,
                    COALESCE(vc.anticipo, 0) as anticipo,
                    vc.saldo_pendiente as saldo
                FROM venta_credito vc
                LEFT JOIN ventas v ON vc.venta_id = v.id
                LEFT JOIN clientes c ON v.cliente_id = c.id
                WHERE vc.saldo_pendiente > 0 AND v.estado = 1
                ORDER BY v.fecha DESC";
        
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            throw new Exception('Error en consulta: ' . $conexion->error);
        }
        
        $facturas = [];
        
        if ($resultado->num_rows > 0) {
            while($fila = $resultado->fetch_assoc()) {
                $facturas[] = [
                    'id_credito' => $fila['id_credito'],
                    'factura' => $fila['factura'] ?? 'N/A',
                    'cliente' => $fila['cliente'] ?? 'Sin cliente',
                    'fecha_venta' => $fila['fecha_venta'],
                    'monto_total' => $fila['monto_total'],
                    'anticipo' => floatval($fila['anticipo']),
                    'saldo' => $fila['saldo']
                ];
            }
        }
        
        echo json_encode($facturas);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

/**
 * Función para guardar un nuevo pago con descuentos aplicados correctamente
 */
function guardarPago() {
    global $conexion;
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // Obtener datos del formulario
        $id_credito = $_POST['id_credito'];
        $factura = $_POST['factura'];
        $cliente = $_POST['cliente'];
        $fecha_pago = $_POST['fecha_pago'];
        $monto = floatval($_POST['monto']);
        $metodo_pago = $_POST['metodo_pago'];
        $numero_referencia = isset($_POST['numero_referencia']) ? $_POST['numero_referencia'] : '';
        $observaciones = isset($_POST['observaciones']) ? $_POST['observaciones'] : '';
        $usuario_abono = isset($_POST['usuario_abono']) ? $_POST['usuario_abono'] : 'Usuario';
        $ObPago = isset($_POST['ObPago']) ? trim($_POST['ObPago']) : '';
        $descuento = isset($_POST['descuento']) ? floatval($_POST['descuento']) : 0;
        $ob_descuento = isset($_POST['ob_descuento']) ? trim($_POST['ob_descuento']) : '';

        
        // Manejar el archivo de comprobante si se ha enviado
        $ruta_comprobante = '';
        if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == 0) {
            // SEGURIDAD (16-09-2026): solo imágenes o PDF, validados por contenido
            require_once __DIR__ . '/subidas.php';
            $directorio_destino = 'comprobantes/';
            $nombre_archivo = guardarArchivoSubido($_FILES['comprobante'], $directorio_destino, ['imagen', 'pdf'], uniqid('', true));
            $ruta_comprobante = $directorio_destino . $nombre_archivo;
        }
        
        // Insertar el pago en la tabla historial_pagos
        $sql_insertar = "INSERT INTO historial_pagos (
                            factura, 
                            cliente, 
                            fecha_pago, 
                            monto, 
                            metodo_pago, 
                            numero_referencia, 
                            comprobante, 
                            observaciones, 
                            estado, 
                            ObPago, 
                            descuento, 
                            ob_descuento
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado', ?, ?, ?)";

        
        $stmt = $conexion->prepare($sql_insertar);
        if (!$stmt) {
            throw new Exception("Error en prepare() del INSERT: " . $conexion->error);
        }
        
        // Bind de parámetros - IMPORTANTE: Asegurarse que ob_descuento se pase como string
        $stmt->bind_param("sssdsssssds", 
            $factura, 
            $cliente, 
            $fecha_pago, 
            $monto, 
            $metodo_pago, 
            $numero_referencia, 
            $ruta_comprobante, 
            $observaciones, 
            $ObPago, 
            $descuento, 
            $ob_descuento  // Este debe ser string
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Error al insertar el pago: ' . $stmt->error);
        }
        
        // Obtener el ID del pago recién insertado
        $id_pago_nuevo = $conexion->insert_id;

        // Generar número de recibo
        $numero_recibo = generarNumeroRecibo($id_pago_nuevo);

        // Actualizar el registro con el número de recibo
        $sql_update_recibo = "UPDATE historial_pagos SET numero_recibo = ? WHERE id_pago = ?";
        $stmt_update = $conexion->prepare($sql_update_recibo);
        if ($stmt_update) {
            $stmt_update->bind_param("si", $numero_recibo, $id_pago_nuevo);
            $stmt_update->execute();
            $stmt_update->close();
        }
        
        // ===========================================================
        // NOTA IMPORTANTE: NO se actualiza manualmente saldo_pendiente.
        // El trigger `hp_ai_recalcular_credito` (AFTER INSERT en
        // historial_pagos) llama al procedimiento
        // `recalcular_credito_por_codigo`, que ya recalcula el saldo
        // desde cero usando:
        //   saldo = total - anticipo - SUM(monto) - SUM(descuento)
        // Si aquí volviéramos a restar (monto + descuento) tendríamos
        // doble descuento y el saldo quedaría negativo (regresión 2026).
        // ===========================================================
        $total_aplicado = $monto + $descuento; // sólo se usa en la respuesta JSON

        // Verificar que el saldo (ya recalculado por el trigger) no quede negativo
        $sql_verificar = "SELECT saldo_pendiente FROM venta_credito WHERE id = ?";
        $stmt_verificar = $conexion->prepare($sql_verificar);
        $stmt_verificar->bind_param("i", $id_credito);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->get_result();
        $fila = $resultado->fetch_assoc();
        
        if ($fila['saldo_pendiente'] < 0) {
            throw new Exception('El monto del pago más el descuento exceden el saldo pendiente');
        }
        
        // Generar comprobante PDF con el saldo actualizado
        $ruta_pdf = generarComprobantePDF($factura, $cliente, $fecha_pago, $monto, $fila['saldo_pendiente'], $usuario_abono);

        // Si el saldo llega a 0, marcar el crédito como pagado
        if ($fila['saldo_pendiente'] <= 0) {
            // estado es tinyint: 0 = crédito cancelado/pagado, 1 = activo
            $sql_marcar_pagado = "UPDATE venta_credito SET estado = 0 WHERE id = ?";
            $stmt_pagado = $conexion->prepare($sql_marcar_pagado);
            $stmt_pagado->bind_param("i", $id_credito);
            $stmt_pagado->execute();
        }
        
        // LOG para verificar los datos guardados
        error_log("Pago guardado - Descuento: $descuento, Obs Descuento: $ob_descuento, Total aplicado: $total_aplicado");
        
        // Confirmar la transacción
        $conexion->commit();
        
        // Devolver respuesta exitosa con la ruta del PDF
        echo json_encode([
            'success' => true, 
            'message' => 'Pago registrado correctamente',
            'pdf_url' => 'php/archivo.php?tipo=comprobante_pago&f=' . rawurlencode(basename($ruta_pdf)),
            'descuento_aplicado' => $descuento,
            'total_aplicado' => $total_aplicado,
            'nuevo_saldo' => $fila['saldo_pendiente']
        ]);
        
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

/**
 * Función para obtener los créditos completamente pagados (saldo_pendiente = 0)
 */
function listarCreditosPagados() {
    global $conexion;
    
    try {
        $sql = "SELECT vc.id as id_credito, 
                       v.codigo as numero_factura, 
                       c.empresa as cliente, 
                       v.total as monto_original, 
                       v.fecha as fecha_credito, 
                       vc.saldo_pendiente 
                FROM venta_credito vc 
                LEFT JOIN ventas v ON vc.venta_id = v.id 
                LEFT JOIN clientes c ON v.cliente_id = c.id 
                WHERE vc.saldo_pendiente <= 0 AND v.estado = 1 
                ORDER BY v.fecha DESC";
        
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            throw new Exception('Error en consulta: ' . $conexion->error);
        }
        
        $creditos = [];
        if ($resultado->num_rows > 0) {
            while($fila = $resultado->fetch_assoc()) {
                $creditos[] = [
                    'id_credito' => $fila['id_credito'],
                    'numero_factura' => $fila['numero_factura'] ?? 'N/A',
                    'cliente' => $fila['cliente'] ?? 'Sin cliente',
                    'monto_original' => $fila['monto_original'],
                    'fecha_credito' => $fila['fecha_credito']
                ];
            }
        }
        
        echo json_encode($creditos);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

/**
 * Función para descargar el comprobante PDF
 */
// FUNCIÓN CORREGIDA: descargarComprobantePDF()
function descargarComprobantePDF() {
    global $conexion;
    
    if (!isset($_GET['factura'])) {
        echo json_encode(['error' => 'Factura no especificada']);
        return;
    }
    
    $factura = sanitizar($_GET['factura']);
    
    // LÓGICA CORREGIDA: Verificar si existe DTE en la columna 'dte'
    $codigo_archivo = $factura; // Por defecto usar código de venta
    
    $sql_dte = "SELECT dte FROM ventas WHERE codigo = ? AND dte IS NOT NULL AND dte != ''";
    $stmt_dte = $conexion->prepare($sql_dte);
    if ($stmt_dte) {
        $stmt_dte->bind_param("s", $factura);
        $stmt_dte->execute();
        $resultado_dte = $stmt_dte->get_result();
        
        if ($resultado_dte->num_rows > 0) {
            $row_dte = $resultado_dte->fetch_assoc();
            $codigo_archivo = $row_dte['dte']; // Usar DTE si existe
        }
        $stmt_dte->close();
    }
    
    // SEGURIDAD: el nombre del archivo solo puede tener letras, números, - y _
    $codigo_archivo = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$codigo_archivo);
    $archivo = 'comprobantes_pdf/abono_' . $codigo_archivo . '.pdf';
    
    if (file_exists($archivo)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="comprobante_abono_' . $codigo_archivo . '.pdf"');
        header('Content-Length: ' . filesize($archivo));
        readfile($archivo);
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado';
        exit;
    }
}

/**
 * Función COMPLETA Y CORREGIDA para generar comprobante PDF con diseño mejorado
 */
function generarComprobantePDF($factura, $cliente, $fecha_pago, $monto, $saldo_restante, $usuario_abono = 'Usuario') {
    global $conexion;
    
    try {
        // Obtener datos adicionales del cliente y crédito CON ANTICIPO
        $sql_datos_adicionales = "SELECT 
                                    c.direccion,
                                    c.nit,
                                    vc.anticipo,
                                    v.total as monto_total_factura
                                  FROM ventas v
                                  LEFT JOIN clientes c ON v.cliente_id = c.id
                                  LEFT JOIN venta_credito vc ON v.id = vc.venta_id
                                  WHERE v.codigo = ?
                                  LIMIT 1";
        
        $direccion_cliente = '';
        $nit_cliente = '';
        $anticipo = 0;
        $monto_total_factura = 0;
        
        $stmt_datos = $conexion->prepare($sql_datos_adicionales);
        if ($stmt_datos) {
            $stmt_datos->bind_param("s", $factura);
            $stmt_datos->execute();
            $resultado_datos = $stmt_datos->get_result();
            
            if ($resultado_datos->num_rows > 0) {
                $row_datos = $resultado_datos->fetch_assoc();
                $direccion_cliente = $row_datos['direccion'] ?? '';
                $nit_cliente = $row_datos['nit'] ?? '';
                $anticipo = floatval($row_datos['anticipo'] ?? 0);
                $monto_total_factura = floatval($row_datos['monto_total_factura'] ?? 0);
                
                // LOG para verificar valores
                error_log("PDF - Factura: $factura, Anticipo: $anticipo, Total: $monto_total_factura");
            }
            $stmt_datos->close();
        }
        
        // Verificar si existe DTE en la columna 'dte'
        $codigo_comprobante = $factura;
        
        $sql_dte = "SELECT dte FROM ventas WHERE codigo = ? AND dte IS NOT NULL AND dte != ''";
        $stmt_dte = $conexion->prepare($sql_dte);
        if ($stmt_dte) {
            $stmt_dte->bind_param("s", $factura);
            $stmt_dte->execute();
            $resultado_dte = $stmt_dte->get_result();
            
            if ($resultado_dte->num_rows > 0) {
                $row_dte = $resultado_dte->fetch_assoc();
                $codigo_comprobante = $row_dte['dte'];
            }
            $stmt_dte->close();
        }
        
        // Crear directorio si no existe
        $directorio_pdf = 'comprobantes_pdf/';
        if (!is_dir($directorio_pdf)) {
            mkdir($directorio_pdf, 0755, true);
        }
        
        // SEGURIDAD: nombre de archivo seguro (el DTE lo escribe un usuario)
        $codigo_comprobante = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$codigo_comprobante);
        $nombre_archivo = 'abono_' . $codigo_comprobante . '.pdf';
        $ruta_completa = $directorio_pdf . $nombre_archivo;
        
        // Obtener historial de pagos COMPLETO de esta factura
        $sql_historial = "SELECT fecha_pago, monto, metodo_pago, observaciones, comprobante, 
                                 ObPago, numero_referencia, descuento, ob_descuento
                          FROM historial_pagos 
                          WHERE factura = ? 
                          ORDER BY fecha_pago ASC";
        
        $stmt = $conexion->prepare($sql_historial);
        $stmt->bind_param("s", $factura);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $historial = [];
        $total_abonos = 0;
        $total_descuentos = 0;
        
        while ($row = $resultado->fetch_assoc()) {
            $historial[] = [
                'fecha_pago' => $row['fecha_pago'],
                'monto' => $row['monto'],
                'metodo_pago' => $row['metodo_pago'],
                'observaciones' => $row['observaciones'],
                'comprobante' => $row['comprobante'],
                'ObPago' => $row['ObPago'],
                'numero_referencia' => $row['numero_referencia'],
                'descuento' => $row['descuento'],
                'ob_descuento' => $row['ob_descuento']
            ];
            $total_abonos += floatval($row['monto']);
            $total_descuentos += floatval($row['descuento']);
        }
        
        // Obtener el último pago (el más reciente)
        $ultimo_pago = end($historial);
        
        // Crear PDF
        $pdf = new FPDF();
        $pdf->AddPage();
        
        // ===== ENCABEZADO COMPACTO UNIFICADO =====
        $ruta_logo = '../imagenes/nutrifluidos.jpg';
        if (file_exists($ruta_logo)) {
            $pdf->Image($ruta_logo, 10, 8, 50, 25);
        }
        
        // Datos de la empresa
        $pdf->SetXY(65, 10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 4, 'NUTRI-FLUIDOS', 0, 1, 'C');
        
        $pdf->SetXY(65, 15);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 3, 'CALLEJON VILLA FLORIDALMA', 0, 1, 'C');
        
        $pdf->SetXY(65, 19);
        $pdf->Cell(0, 3, 'TIQUISATE, ESCUINTLA, GUATEMALA', 0, 1, 'C');
        
        $pdf->SetXY(65, 23);
        $pdf->Cell(0, 3, 'NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'C');
        
        $pdf->SetXY(65, 27);
        $pdf->Cell(0, 3, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C');
        
        $pdf->SetXY(65, 31);
        $pdf->Cell(0, 3, 'NIT: 95675876', 0, 1, 'C');
        
        // Línea separadora
        $pdf->Ln(4);
        $pdf->Line(10, 38, 200, 38);
        
        // ===== TÍTULO DEL DOCUMENTO =====
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, 'RECIBO DE CAJA ELECTRONICO', 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 4, 'Fecha de generacion: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(3);
        
        // ===== INFORMACIÓN BÁSICA EN COLUMNAS =====
        // Obtener el número de recibo
        $numero_recibo = '';
        $sql_ultimo_recibo = "SELECT numero_recibo FROM historial_pagos 
                              WHERE factura = ? AND numero_recibo IS NOT NULL
                              ORDER BY fecha_pago DESC, id_pago DESC 
                              LIMIT 1";
        $stmt_recibo = $conexion->prepare($sql_ultimo_recibo);
        if ($stmt_recibo) {
            $stmt_recibo->bind_param("s", $factura);
            $stmt_recibo->execute();
            $resultado_recibo = $stmt_recibo->get_result();
            
            if ($resultado_recibo->num_rows > 0) {
                $row_recibo = $resultado_recibo->fetch_assoc();
                $numero_recibo = $row_recibo['numero_recibo'];
            }
            $stmt_recibo->close();
        }
        
        $mostrar_numero = !empty($numero_recibo) ? $numero_recibo : $codigo_comprobante;
        
        // Primera fila
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(25, 5, 'No:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(50, 5, $mostrar_numero, 0, 0, 'L');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(120);
        $pdf->Cell(25, 5, 'Fecha:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(50, 5, date('d/m/Y'), 0, 1, 'L');
        
        // Segunda fila
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(20);
        $pdf->Cell(25, 5, 'Cliente:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(75, 5, $cliente, 0, 0, 'L');
        
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(120);
        $pdf->Cell(25, 5, 'Direccion:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(50, 5, $direccion_cliente, 0, 1, 'L');
        
        // Tercera fila
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(120);
        $pdf->Cell(25, 5, 'Nit:', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(50, 5, $nit_cliente, 0, 1, 'L');
        
        $pdf->Ln(3);
        
        // ===== TABLA PRINCIPAL MEJORADA =====
        
        // Encabezado "Detalle"
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(20);
        $pdf->Cell(65, 6, 'Detalle', 0, 0, 'L');
        
        // Línea divisoria vertical entre las dos secciones
        $y_inicial = $pdf->GetY();
        
        // Encabezado "Ajustes"
        $pdf->SetX(95);
        $pdf->Cell(95, 6, 'Ajustes', 0, 1, 'L');
        
        // Subencabezados de la tabla
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        
        // Lado izquierdo - Detalle
        $pdf->SetX(20);
        $pdf->Cell(40, 6, 'Documento', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Monto', 1, 0, 'C', true);
        
        // Espacio entre tablas
        $pdf->SetX(95);
        
        // Lado derecho - Ajustes
        $pdf->Cell(35, 6, 'Descuentos', 1, 0, 'C', true);
        $pdf->Cell(60, 6, 'Comentarios', 1, 1, 'C', true);
        
        // Fila de datos
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        // Lado izquierdo - Detalle
        $pdf->SetX(20);
        $pdf->Cell(40, 8, $codigo_comprobante, 1, 0, 'C');
        $pdf->Cell(25, 8, 'Q ' . number_format($monto_total_factura, 2), 1, 0, 'R');
        
        // Espacio entre tablas
        $pdf->SetX(95);
        
        // Lado derecho - Ajustes con datos reales
        $descuento_texto = ($ultimo_pago['descuento'] > 0) ? 
            'Q ' . number_format($ultimo_pago['descuento'], 2) : 'Q 0.00';
        $pdf->Cell(35, 8, $descuento_texto, 1, 0, 'C');
        
        // Observación del descuento
        $obs_descuento = $ultimo_pago['ob_descuento'] ?? '';
        if (strlen($obs_descuento) > 40) {
            $obs_descuento = substr($obs_descuento, 0, 38) . '...';
        }
        $pdf->Cell(60, 8, $obs_descuento, 1, 1, 'L');
        
        // Línea vertical separadora entre las dos secciones
        $y_final = $pdf->GetY();
        $pdf->Line(90, $y_inicial + 6, 90, $y_final);
        
        $pdf->Ln(5);
        
        // ===== SECCIÓN DE PAGO MEJORADA =====
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(20);
        $pdf->Cell(170, 6, 'Pago', 0, 1, 'L');
        
        // Tabla de pago
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetX(20);
        $pdf->Cell(30, 6, 'Referencia', 1, 0, 'C', true);
        $pdf->Cell(25, 6, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(85, 6, 'Forma de Pago', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'Monto', 1, 1, 'C', true);
        
        // Datos del último pago con información completa
        $pdf->SetFont('Arial', '', 8);
        
        // Concatenar método de pago con observación
        $forma_pago_completa = $ultimo_pago['metodo_pago'];
        if (!empty($ultimo_pago['ObPago'])) {
            $forma_pago_completa .= ' - ' . $ultimo_pago['ObPago'];
        }
        
        // Truncar si es muy larga
        if (strlen($forma_pago_completa) > 60) {
            $forma_pago_completa = substr($forma_pago_completa, 0, 58) . '...';
        }
        
        // Número de referencia
        $referencia = !empty($ultimo_pago['numero_referencia']) ? 
            $ultimo_pago['numero_referencia'] : 'Abono No. ' . count($historial);
        
        $pdf->SetX(20);
        $pdf->Cell(30, 8, $referencia, 1, 0, 'C');
        $pdf->Cell(25, 8, date('d/m/Y', strtotime($ultimo_pago['fecha_pago'])), 1, 0, 'C');
        $pdf->Cell(85, 8, $forma_pago_completa, 1, 0, 'L');
        $pdf->Cell(30, 8, 'Q ' . number_format($ultimo_pago['monto'], 2), 1, 1, 'R');
        
        $pdf->Ln(5);
        
        // ===== RESUMEN CORREGIDO CON ANTICIPO =====
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetX(20);
        $pdf->Cell(100, 6, 'Resumen', 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 9);
        
        // Total de la factura original
        $pdf->SetX(20);
        $pdf->Cell(60, 5, 'Total Factura', 0, 0, 'L');
        $pdf->Cell(40, 5, 'Q. ' . number_format($monto_total_factura, 2), 0, 1, 'R');
        
        /*
        // Mostrar anticipo si existe
        if ($anticipo > 0) {
            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(60, 5, 'Pago Anticipado', 0, 0, 'L');
            $pdf->Cell(40, 5, 'Q. ' . number_format($anticipo, 2), 0, 1, 'R');
            
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->SetX(20);
            $pdf->Cell(60, 5, 'Monto a Financiar', 0, 0, 'L');
            $pdf->Cell(40, 5, 'Q. ' . number_format($monto_total_factura - $anticipo, 2), 0, 1, 'R');
            
            $pdf->SetFont('Arial', '', 9);
        }*/
        
        // Total de descuentos aplicados
        if ($total_descuentos > 0) {
            $pdf->SetX(20);
            $pdf->Cell(60, 5, 'Descuentos aplicados', 0, 0, 'L');
            $pdf->Cell(40, 5, 'Q. ' . number_format($total_descuentos, 2), 0, 1, 'R');
            
        
            // Total a pagar después de descuentos (solo del monto financiado)
           // $monto_financiado = $monto_total_factura - $anticipo;
            //$total_con_descuento = $monto_financiado - $total_descuentos;
            //$pdf->SetX(20);
            //$pdf->SetFont('Arial', 'I', 9);
            //$pdf->Cell(60, 5, 'Monto financiado (con descuento)', 0, 0, 'L');
            //$pdf->Cell(40, 5, 'Q. ' . number_format($total_con_descuento, 2), 0, 1, 'R');
            //$pdf->SetFont('Arial', '', 9);
        }
        /*
        // Total pagado en abonos (sin incluir anticipo)
        
        
        $pdf->SetX(20);
        $pdf->Cell(60, 5, 'Total en Abonos', 0, 0, 'L');
        $pdf->Cell(40, 5, 'Q. ' . number_format($total_abonos, 2), 0, 1, 'R');
        */
        
        // TOTAL PAGADO (anticipo + abonos)
        $total_pagado_completo = $anticipo + $total_abonos;
        $pdf->SetX(20);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(60, 5, 'Total Pagado ', 0, 0, 'L');
        $pdf->Cell(40, 5, 'Q. ' . number_format($total_pagado_completo, 2), 0, 1, 'R');
        
        // CORRECCIÓN: Saldo correcto considerando anticipo y descuentos
        $monto_financiado = $monto_total_factura - $anticipo;
        $total_aplicado_credito = $total_abonos + $total_descuentos;
        $saldo_real = $monto_financiado - $total_aplicado_credito;
        
        // Saldo por pagar
        $pdf->SetX(20);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(255, 255, 0); // Fondo amarillo para destacar
        $pdf->Cell(60, 6, 'SALDO POR PAGAR', 1, 0, 'L', true);
        $pdf->Cell(40, 6, 'Q. ' . number_format(max(0, $saldo_real), 2), 1, 1, 'R', true);
        
        $pdf->Ln(5);
        
        // ===== OBSERVACIONES =====
        $ultima_observacion = $ultimo_pago['observaciones'] ?? '';
        if (!empty($ultima_observacion)) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetX(20);
            $pdf->Cell(30, 5, 'Observaciones:', 0, 0, 'L');
            
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetX(50);
            $pdf->MultiCell(140, 4, $ultima_observacion, 0, 'L');
            $pdf->Ln(2);
        }
        
        /*
        // ===== COMPROBANTE ADJUNTO =====
        $nombre_comprobante = basename($ultimo_pago['comprobante'] ?? '');
        if (!empty($nombre_comprobante)) {
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetX(20);
            $pdf->Cell(0, 5, 'Comprobante adjunto: ' . $nombre_comprobante, 0, 1, 'L');
        }*/
        
        // ===== REALIZADO POR =====
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetX(20);
        $pdf->Cell(100, 5, 'Realizado por: ' . $usuario_abono, 0, 1, 'L');
        
        $pdf->Ln(10);
        
        /*
        // ===== HISTORIAL DE PAGOS (OPCIONAL - SI HAY MÁS DE UN PAGO) =====
        if (count($historial) > 1 || $anticipo > 0) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetX(20);
            $pdf->Cell(170, 5, 'Historial Completo de Pagos', 0, 1, 'L');
            
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetX(20);
            $pdf->Cell(15, 5, 'No.', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Fecha', 1, 0, 'C', true);
            $pdf->Cell(25, 5, 'Monto', 1, 0, 'C', true);
            $pdf->Cell(25, 5, 'Descuento', 1, 0, 'C', true);
            $pdf->Cell(25, 5, 'Metodo', 1, 0, 'C', true);
            $pdf->Cell(30, 5, 'Referencia', 1, 0, 'C', true);
            $pdf->Cell(30, 5, 'Saldo Post-Pago', 1, 1, 'C', true);
            
            $pdf->SetFont('Arial', '', 7);
            $numero = 1;
            
            // MOSTRAR ANTICIPO COMO PRIMER PAGO SI EXISTE
            if ($anticipo > 0) {
                $saldo_temporal = $monto_total_factura - $anticipo;
                
                $pdf->SetX(20);
                $pdf->Cell(15, 5, $numero, 1, 0, 'C');
                $pdf->Cell(20, 5, 'ANTICIPO', 1, 0, 'C');
                $pdf->Cell(25, 5, 'Q ' . number_format($anticipo, 2), 1, 0, 'R');
                $pdf->Cell(25, 5, 'Q 0.00', 1, 0, 'R');
                $pdf->Cell(25, 5, 'INICIAL', 1, 0, 'C');
                $pdf->Cell(30, 5, 'VENTA', 1, 0, 'C');
                $pdf->Cell(30, 5, 'Q ' . number_format($saldo_temporal, 2), 1, 1, 'R');
                
                $numero++;
            } else {
                $saldo_temporal = $monto_total_factura;
            }
            
            // HISTORIAL DE ABONOS
            foreach ($historial as $pago) {
                $descuento_actual = floatval($pago['descuento']);
                $monto_pago = floatval($pago['monto']);
                
                // Aplicar descuento y pago
                $saldo_temporal -= ($descuento_actual + $monto_pago);
                
                $pdf->SetX(20);
                $pdf->Cell(15, 5, $numero, 1, 0, 'C');
                $pdf->Cell(20, 5, date('d/m/Y', strtotime($pago['fecha_pago'])), 1, 0, 'C');
                $pdf->Cell(25, 5, 'Q ' . number_format($monto_pago, 2), 1, 0, 'R');
                $pdf->Cell(25, 5, 'Q ' . number_format($descuento_actual, 2), 1, 0, 'R');
                $pdf->Cell(25, 5, $pago['metodo_pago'], 1, 0, 'C');
                $pdf->Cell(30, 5, $pago['numero_referencia'] ?? '', 1, 0, 'C');
                $pdf->Cell(30, 5, 'Q ' . number_format(max(0, $saldo_temporal), 2), 1, 1, 'R');
                
                $numero++;
            }
            $pdf->Ln(5);
        }*/
        
        // ===== NOTAS AL PIE =====
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetX(20);
        $pdf->MultiCell(170, 3, 'SI EL CHEQUE ES DEVUELTO, LA OPERACION QUE AMPARA ESTE COMPROBANTE NO TENDRA VALIDEZ. POR CADA CHEQUE RECHAZADO SE COBRARA Q50.00 POR CONCEPTOS ADMINISTRATIVOS.', 0, 'J');
        
        $pdf->Ln(2);
        $pdf->SetX(20);
        $pdf->Cell(170, 3, 'ESTE RECIBO ES EL UNICO COMPROBANTE DE PAGO QUE RECONOCEMOS.', 0, 1, 'J');
        
        // Guardar PDF
        $pdf->Output('F', $ruta_completa);
        
        return $ruta_completa;
        
    } catch (Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        return false;
    }
}

/**
 * Función CORREGIDA 2: obtenerDatosFactura
 */
function obtenerDatosFactura() {
    global $conexion;
    
    try {
        if (!isset($_GET['factura'])) {
            throw new Exception('Factura no especificada');
        }
        
        $factura = sanitizar($_GET['factura']);
        
        // Obtener información básica CON ANTICIPO
        $sql_factura = "SELECT 
                            vc.id as id_credito,
                            v.codigo as factura,
                            c.empresa as cliente,
                            v.fecha as fecha_venta,
                            v.total as monto_total,
                            vc.saldo_pendiente,
                            COALESCE(vc.anticipo, 0) as anticipo
                        FROM venta_credito vc
                        LEFT JOIN ventas v ON vc.venta_id = v.id
                        LEFT JOIN clientes c ON v.cliente_id = c.id
                        WHERE v.codigo = ?";
        
        $stmt = $conexion->prepare($sql_factura);
        if (!$stmt) {
            throw new Exception('Error en preparación de consulta: ' . $conexion->error);
        }
        
        $stmt->bind_param("s", $factura);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            throw new Exception('Factura no encontrada');
        }
        
        $datosFactura = $resultado->fetch_assoc();
        
        // Obtener historial de pagos CON DESCUENTOS
        $sql_historial = "SELECT 
                            fecha_pago,
                            monto,
                            metodo_pago,
                            numero_referencia,
                            observaciones,
                            COALESCE(descuento, 0) as descuento,
                            COALESCE(ob_descuento, '') as ob_descuento,
                            COALESCE(ObPago, '') as ObPago
                         FROM historial_pagos 
                         WHERE factura = ? 
                         ORDER BY fecha_pago ASC";
        
        $stmt_historial = $conexion->prepare($sql_historial);
        if (!$stmt_historial) {
            throw new Exception('Error en preparación de consulta de historial: ' . $conexion->error);
        }
        
        $stmt_historial->bind_param("s", $factura);
        $stmt_historial->execute();
        $resultado_historial = $stmt_historial->get_result();
        
        $historial_pagos = [];
        $total_abonos = 0;
        $total_descuentos = 0;
        
        while ($pago = $resultado_historial->fetch_assoc()) {
            $historial_pagos[] = $pago;
            $total_abonos += floatval($pago['monto']);
            $total_descuentos += floatval($pago['descuento']);
        }
        
        // CALCULAR CORRECTAMENTE
        $monto_total = floatval($datosFactura['monto_total']);
        $anticipo = floatval($datosFactura['anticipo']);
        $monto_pendiente_inicial = $monto_total - $anticipo;
        
        // Preparar respuesta
        $respuesta = [
            'factura' => $datosFactura['factura'],
            'cliente' => $datosFactura['cliente'] ?? 'Cliente no especificado',
            'fecha_venta' => $datosFactura['fecha_venta'],
            'monto_total' => $monto_total,
            'anticipo' => $anticipo,
            'monto_pendiente_inicial' => $monto_pendiente_inicial,
            'saldo_pendiente' => floatval($datosFactura['saldo_pendiente']),
            'total_abonos' => $total_abonos,
            'total_descuentos' => $total_descuentos,
            'historial_pagos' => $historial_pagos
        ];
        
        echo json_encode($respuesta);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => true,
            'mensaje' => $e->getMessage()
        ]);
    }
    
    exit;
}


/**
 * Función para generar reporte de abonos con filtros
 */
function generarReporteAbonos() {
    global $conexion;

    try {
        $fecha_inicio = isset($_GET['fecha_inicio']) ? sanitizar($_GET['fecha_inicio']) : '';
        $fecha_fin = isset($_GET['fecha_fin']) ? sanitizar($_GET['fecha_fin']) : '';
        $cliente = isset($_GET['cliente']) ? sanitizar($_GET['cliente']) : '';

        $sql = "SELECT 
                    hp.factura,
                    hp.cliente,
                    hp.fecha_pago,
                    hp.monto,
                    hp.metodo_pago,
                    hp.ObPago,
                    hp.numero_referencia,
                    hp.descuento,
                    hp.ob_descuento,
                    hp.observaciones,
                    v.total as total_factura,
                    vc.saldo_pendiente
                FROM historial_pagos hp
                LEFT JOIN ventas v ON hp.factura = v.codigo
                LEFT JOIN venta_credito vc ON v.id = vc.venta_id
                WHERE 1=1";

        $params = [];
        $types = '';

        if (!empty($fecha_inicio)) {
            $sql .= " AND hp.fecha_pago >= ?";
            $params[] = $fecha_inicio;
            $types .= 's';
        }

        if (!empty($fecha_fin)) {
            $sql .= " AND hp.fecha_pago <= ?";
            $params[] = $fecha_fin;
            $types .= 's';
        }

        if (!empty($cliente)) {
            $sql .= " AND hp.cliente LIKE ?";
            $params[] = '%' . $cliente . '%';
            $types .= 's';
        }

        $sql .= " ORDER BY hp.cliente, vc.saldo_pendiente = 0 DESC, hp.fecha_pago ASC";

        $stmt = $conexion->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $resultado = $stmt->get_result();

        $datos = [];
        $total_general = 0;

        while ($row = $resultado->fetch_assoc()) {
            $datos[] = $row;
            $total_general += floatval($row['monto']);
        }

        $ruta_pdf = generarReportePDF($datos, $total_general, $fecha_inicio, $fecha_fin, $cliente);

        echo json_encode([
            'success' => true,
            'pdf_url' => $ruta_pdf,
            'total_registros' => count($datos),
            'total_monto' => $total_general
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error: ' . $e->getMessage()
        ]);
    }

    exit;
}


/**
 * Función mejorada para generar el PDF del reporte con mejor diseño y legibilidad
 */
function generarReportePDF($datos, $total_general, $fecha_inicio, $fecha_fin, $cliente_filtro) {
    require_once '../fpdf/fpdf.php';

    // Crear directorio si no existe
    $directorio_pdf = 'reportes_pdf/';
    if (!is_dir($directorio_pdf)) {
        mkdir($directorio_pdf, 0755, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $nombre_archivo = 'reporte_abonos_' . $timestamp . '.pdf';
    $ruta_completa = $directorio_pdf . $nombre_archivo;

    // Crear PDF en orientación horizontal para más espacio
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();

    // ===== ENCABEZADO MEJORADO =====
    $logo = '../imagenes/nutrifluidos.jpg';
    if (file_exists($logo)) {
        $pdf->Image($logo, 10, 8, 40, 20);
    }

    // Información de la empresa
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(55, 10);
    $pdf->Cell(0, 6, 'NUTRI-FLUIDOS', 0, 1, 'L');
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetX(55);
    $pdf->Cell(0, 5, 'CALLEJON VILLA FLORIDALMA, TIQUISATE, ESCUINTLA', 0, 1, 'L');
    $pdf->SetX(55);
    $pdf->Cell(0, 5, 'NIT: 95675876 | Email: NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'L');

    // Línea separadora
    $pdf->Ln(3);
    $pdf->Line(10, 35, 287, 35);

    // Título del reporte
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'REPORTE DE ABONOS', 0, 1, 'C');

    // Información del periodo y filtros
    $pdf->SetFont('Arial', '', 10);
    if (!empty($fecha_inicio) || !empty($fecha_fin)) {
        $periodo = 'Periodo: ';
        if ($fecha_inicio) $periodo .= 'Desde ' . date('d/m/Y', strtotime($fecha_inicio));
        if ($fecha_inicio && $fecha_fin) $periodo .= ' - ';
        if ($fecha_fin) $periodo .= 'Hasta ' . date('d/m/Y', strtotime($fecha_fin));
        $pdf->Cell(0, 5, $periodo, 0, 1, 'C');
    }
    
    if (!empty($cliente_filtro)) {
        $pdf->Cell(0, 5, 'Cliente: ' . $cliente_filtro, 0, 1, 'C');
    }
    
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(0, 5, 'Generado el: ' . date('d/m/Y H:i:s'), 0, 1, 'C');

    $pdf->Ln(5);

    // ===== TABLA DE DATOS MEJORADA =====
    
    // Encabezados de la tabla
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(50, 50, 50);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(128, 128, 128);
    $pdf->SetLineWidth(0.3);
    
    // Definir anchos de columna optimizados para caber en A4 horizontal (277mm útiles)
    $w = array(22, 28, 35, 20, 20, 20, 28, 28, 25, 20, 20); // Total: 266mm
    
    // Primera fila de encabezados
    $pdf->Cell($w[0], 8, 'Fecha Pago', 1, 0, 'C', true);
    $pdf->Cell($w[1], 8, 'No. Factura', 1, 0, 'C', true);
    $pdf->Cell($w[2], 8, 'Cliente', 1, 0, 'C', true);
    $pdf->Cell($w[3], 8, 'Monto', 1, 0, 'C', true);
    $pdf->Cell($w[4], 8, 'Descuento', 1, 0, 'C', true);
    $pdf->Cell($w[5], 8, 'Metodo', 1, 0, 'C', true);
    $pdf->Cell($w[6], 8, 'Forma Pago', 1, 0, 'C', true);
    $pdf->Cell($w[7], 8, 'No. Referencia', 1, 0, 'C', true);
    $pdf->Cell($w[8], 8, 'Obs. Desc.', 1, 0, 'C', true);
    $pdf->Cell($w[9], 8, 'Obs. Pago', 1, 0, 'C', true);
    $pdf->Cell($w[10], 8, 'Estado', 1, 1, 'C', true);

    // Restaurar colores para el contenido
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetFillColor(245, 245, 245);

    // Variables para control de totales por cliente
    $cliente_actual = '';
    $total_cliente = 0;
    $descuento_cliente = 0;
    $registros_cliente = 0;
    $fill = false;

    // Agrupar datos por cliente para mejor organización
    $datos_agrupados = array();
    foreach ($datos as $row) {
        $cliente = $row['cliente'];
        if (!isset($datos_agrupados[$cliente])) {
            $datos_agrupados[$cliente] = array();
        }
        $datos_agrupados[$cliente][] = $row;
    }

    // Procesar datos agrupados
    foreach ($datos_agrupados as $cliente => $pagos_cliente) {
        // Encabezado del cliente
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Cell(array_sum($w), 6, 'CLIENTE: ' . strtoupper($cliente), 1, 1, 'L', true);
        
        // Resetear variables del cliente
        $total_cliente = 0;
        $descuento_cliente = 0;
        $registros_cliente = 0;
        
        // Datos del cliente
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetFillColor(245, 245, 245);
        
        foreach ($pagos_cliente as $row) {
            // Determinar estado de pago
            $estado = ($row['saldo_pendiente'] == 0) ? 'PAGADO' : 'PENDIENTE';
            $pdf->SetTextColor(($estado == 'PAGADO') ? 0 : 200, ($estado == 'PAGADO') ? 150 : 0, 0);
            
            // Fecha
            $pdf->Cell($w[0], 6, date('d/m/Y', strtotime($row['fecha_pago'])), 1, 0, 'C', $fill);
            
            // Resetear color para resto de campos
            $pdf->SetTextColor(0, 0, 0);
            
            // No. Factura
            $pdf->Cell($w[1], 6, $row['factura'], 1, 0, 'C', $fill);
            
            // Cliente (truncar si es muy largo)
            $cliente_texto = (strlen($row['cliente']) > 20) ? 
                substr($row['cliente'], 0, 18) . '..' : $row['cliente'];
            $pdf->Cell($w[2], 6, $cliente_texto, 1, 0, 'L', $fill);
            
            // Monto
            $pdf->Cell($w[3], 6, 'Q ' . number_format($row['monto'], 2), 1, 0, 'R', $fill);
            
            // Descuento
            $descuento = isset($row['descuento']) ? floatval($row['descuento']) : 0;
            $pdf->Cell($w[4], 6, 'Q ' . number_format($descuento, 2), 1, 0, 'R', $fill);
            
            // Método de pago
            $metodo = isset($row['metodo_pago']) ? $row['metodo_pago'] : '';
            $pdf->Cell($w[5], 6, $metodo, 1, 0, 'C', $fill);
            
            // Observación forma de pago (truncar)
            $obs_pago = isset($row['ObPago']) ? $row['ObPago'] : '';
            $obs_pago = (strlen($obs_pago) > 15) ? substr($obs_pago, 0, 13) . '..' : $obs_pago;
            $pdf->Cell($w[6], 6, $obs_pago, 1, 0, 'L', $fill);
            
            // Número de referencia
            $referencia = isset($row['numero_referencia']) ? $row['numero_referencia'] : '';
            $pdf->Cell($w[7], 6, $referencia, 1, 0, 'C', $fill);
            
            // Observación descuento (truncar)
            $obs_desc = isset($row['ob_descuento']) ? $row['ob_descuento'] : '';
            $obs_desc = (strlen($obs_desc) > 14) ? substr($obs_desc, 0, 12) . '..' : $obs_desc;
            $pdf->Cell($w[8], 6, $obs_desc, 1, 0, 'L', $fill);
            
            // Observación de pago adicional
            $obs_pago2 = isset($row['observaciones']) ? $row['observaciones'] : '';
            $obs_pago2 = (strlen($obs_pago2) > 14) ? substr($obs_pago2, 0, 12) . '..' : $obs_pago2;
            $pdf->Cell($w[9], 6, $obs_pago2, 1, 0, 'L', $fill);
            
            // Estado con color
            $pdf->SetTextColor(($estado == 'PAGADO') ? 0 : 200, ($estado == 'PAGADO') ? 150 : 0, 0);
            $pdf->Cell($w[10], 6, $estado, 1, 1, 'C', $fill);
            $pdf->SetTextColor(0, 0, 0);
            
            // Actualizar totales
            $total_cliente += floatval($row['monto']);
            $descuento_cliente += $descuento;
            $registros_cliente++;
            
            $fill = !$fill;
        }
        
        // Subtotal del cliente
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell($w[0] + $w[1] + $w[2], 6, 'SUBTOTAL ' . $registros_cliente . ' pagos', 1, 0, 'R', true);
        $pdf->Cell($w[3], 6, 'Q ' . number_format($total_cliente, 2), 1, 0, 'R', true);
        $pdf->Cell($w[4], 6, 'Q ' . number_format($descuento_cliente, 2), 1, 0, 'R', true);
        $pdf->Cell($w[5] + $w[6] + $w[7] + $w[8] + $w[9] + $w[10], 6, 'Neto: Q ' . number_format($total_cliente - $descuento_cliente, 2), 1, 1, 'C', true);
        
        $pdf->Ln(2);
    }

    // ===== RESUMEN GENERAL =====
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(50, 50, 50);
    $pdf->SetTextColor(255, 255, 255);
    
    // Calcular totales generales
    $total_descuentos = 0;
    $total_registros = count($datos);
    foreach ($datos as $row) {
        $total_descuentos += isset($row['descuento']) ? floatval($row['descuento']) : 0;
    }
    
    // Tabla de resumen
    $pdf->Cell(60, 7, 'RESUMEN GENERAL', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'CANTIDAD', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'MONTO', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetFillColor(245, 245, 245);
    
    $pdf->Cell(60, 6, 'Total de Pagos Registrados', 1, 0, 'L', false);
    $pdf->Cell(40, 6, $total_registros, 1, 0, 'C', false);
    $pdf->Cell(50, 6, '-', 1, 1, 'C', false);
    
    $pdf->Cell(60, 6, 'Total Abonado', 1, 0, 'L', true);
    $pdf->Cell(40, 6, '-', 1, 0, 'C', true);
    $pdf->Cell(50, 6, 'Q ' . number_format($total_general, 2), 1, 1, 'R', true);
    
    $pdf->Cell(60, 6, 'Total Descuentos', 1, 0, 'L', false);
    $pdf->Cell(40, 6, '-', 1, 0, 'C', false);
    $pdf->Cell(50, 6, 'Q ' . number_format($total_descuentos, 2), 1, 1, 'R', false);
    
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(220, 220, 220);
    $pdf->Cell(60, 7, 'TOTAL NETO RECIBIDO', 1, 0, 'L', true);
    $pdf->Cell(40, 7, $total_registros . ' pagos', 1, 0, 'C', true);
    $pdf->Cell(50, 7, 'Q ' . number_format($total_general - $total_descuentos, 2), 1, 1, 'R', true);

    // ===== PIE DE PÁGINA =====
    $pdf->Ln(10);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 5, 'Este reporte fue generado automaticamente por el sistema de gestion.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Para cualquier consulta, contacte al departamento de contabilidad.', 0, 1, 'C');

    // Guardar el PDF
    $pdf->Output('F', $ruta_completa);
    
    return $ruta_completa;
}

/**
 * Función para descargar el reporte PDF
 */
function descargarReportePDF() {
    if (!isset($_GET['archivo'])) {
        echo json_encode(['error' => 'Archivo no especificado']);
        return;
    }
    
    // SEGURIDAD (16-09-2026): solo nombres de reporte PDF, sin rutas (bloquea ../conexion.php)
    $archivo = basename((string)$_GET['archivo']);
    if (!preg_match('/^[A-Za-z0-9_-]+\.pdf$/', $archivo)) {
        header('HTTP/1.0 400 Bad Request');
        echo 'Nombre de archivo no válido';
        exit;
    }
    $directorio_reportes = realpath(__DIR__ . '/reportes_pdf');
    $ruta_completa = $directorio_reportes ? realpath($directorio_reportes . '/' . $archivo) : false;
    
    if ($ruta_completa && strpos($ruta_completa, $directorio_reportes . DIRECTORY_SEPARATOR) === 0 && is_file($ruta_completa)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta_completa));
        readfile($ruta_completa);
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Archivo no encontrado';
        exit;
    }
}
?>
