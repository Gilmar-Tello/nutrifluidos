<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();
// comprobante.php - Genera comprobante de venta
require_once __DIR__ . '/../../../privado/conexion.php';

// Configuración de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '../php_errors.log');

// Validar ID de venta
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de venta no especificado']);
    exit;
}

$venta_id = intval($_GET['id']);
$guardarPDF = isset($_GET['guardarPDF']) && $_GET['guardarPDF'] == 1;

// Obtener datos de la venta
try {
    // Consultar información de la venta - AGREGANDO direccion2
    $sql = "SELECT v.*, c.empresa, c.nit, c.direccion, c.direccion2, c.contacto1 as contacto, 
           u.nombre_completo as vendedor 
        FROM ventas v
        JOIN clientes c ON v.cliente_id = c.id
        JOIN usuarios u ON v.usuario_id = u.id
        WHERE v.id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $venta_id);
    $stmt->execute();
    $venta = $stmt->get_result()->fetch_assoc();
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }
    
    // FORMATEAR NIT COMO STRING
    $venta['nit'] = formatearNITComprobante($venta['nit']);
    
    // CONCATENAR DIRECCIONES
    $direccion_completa = construirDireccionCompleta($venta['direccion'], $venta['direccion2']);
    
    // Obtener detalles de la venta
    $sql = "SELECT vd.*, p.codigo, p.nombre 
            FROM venta_detalle vd
            JOIN productos p ON vd.producto_id = p.id
            WHERE vd.venta_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $venta_id);
    $stmt->execute();
    $detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Verificar si es venta a crédito
    $sql = "SELECT * FROM venta_credito WHERE venta_id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $venta_id);
    $stmt->execute();
    $credito = $stmt->get_result()->fetch_assoc();
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos: ' . $e->getMessage()]);
    exit;
}

// Formatear datos
$fecha_formateada = date('d/m/Y', strtotime($venta['fecha']));
$subtotal_formateado = number_format($venta['subtotal'], 2);
$descuento_formateado = number_format($venta['descuento'], 2);
$total_formateado = number_format($venta['total'], 2);
// ✅ DESCUENTO: mostrar subtotal y descuento solo si hay descuento
$tiene_descuento = (float)$venta['descuento'] > 0;

// NUEVAS FUNCIONES AUXILIARES - Agregar después de numeroALetras()

/**
 * Formatea un NIT para el comprobante manteniendo el tipo string
 * @param string|number $nit - El NIT a formatear
 * @returns string NIT formateado
 */
function formatearNITComprobante($nit) {
    if (!$nit) return 'N/A';
    
    // IMPORTANTE: Convertir a string y mantener como string
    $nitStr = (string)$nit;
    
    // Manejar casos especiales
    if ($nitStr === '0' || strtolower($nitStr) === 'n/a') {
        return $nitStr;
    }
    
    // Si ya tiene guiones, devolverlo como está
    if (strpos($nitStr, '-') !== false) {
        return $nitStr;
    }
    
    // Si tiene menos de 8 dígitos, devolverlo como está
    if (strlen($nitStr) < 8) {
        return $nitStr;
    }
    
    // Formatear NIT típico guatemalteco: #######-#
    $base = substr($nitStr, 0, -1);
    $verificador = substr($nitStr, -1);
    
    return $base . '-' . $verificador;
}

/**
 * Construye la dirección completa concatenando direccion y direccion2
 * @param string $direccion - Dirección principal
 * @param string $direccion2 - Dirección secundaria
 * @returns string Dirección completa
 */
function construirDireccionCompleta($direccion, $direccion2) {
    $direccion_principal = trim($direccion ?: '');
    $direccion_secundaria = trim($direccion2 ?: '');
    
    // Si solo hay dirección principal
    if (!empty($direccion_principal) && empty($direccion_secundaria)) {
        return $direccion_principal;
    }
    
    // Si solo hay dirección secundaria
    if (empty($direccion_principal) && !empty($direccion_secundaria)) {
        return $direccion_secundaria;
    }
    
    // Si hay ambas, concatenarlas
    if (!empty($direccion_principal) && !empty($direccion_secundaria)) {
        return $direccion_principal . ' - ' . $direccion_secundaria;
    }
    
    // Si no hay ninguna
    return 'DIRECCIÓN NO DISPONIBLE';
}


// Función mejorada para convertir números a letras incluyendo centavos
function numeroALetras($numero) {
    $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    $especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    
    // Separar la parte entera de los centavos
    $partes = explode('.', number_format($numero, 2, '.', ''));
    $entero = intval($partes[0]);
    $centavos = intval($partes[1]);
    
    // Función auxiliar para convertir números menores a 1000
    function convertirMenorMil($num, $unidades, $especiales, $decenas, $centenas) {
        if ($num == 0) return '';
        
        $texto = '';
        
        // Centenas
        if ($num >= 100) {
            if ($num == 100) {
                $texto .= 'CIEN ';
            } else {
                $texto .= $centenas[floor($num / 100)] . ' ';
            }
            $num %= 100;
        }
        
        // Decenas y unidades
        if ($num >= 20) {
            $texto .= $decenas[floor($num / 10)];
            if ($num % 10 > 0) {
                $texto .= ' Y ' . $unidades[$num % 10];
            }
        } else if ($num >= 10) {
            $texto .= $especiales[$num - 10];
        } else if ($num > 0) {
            $texto .= $unidades[$num];
        }
        
        return trim($texto);
    }
    
    // Función auxiliar para convertir números mayores a 999
    function convertirCompleto($num, $unidades, $especiales, $decenas, $centenas) {
        if ($num == 0) return 'CERO';
        
        $texto = '';
        
        // Millones
        if ($num >= 1000000) {
            $millones = floor($num / 1000000);
            if ($millones == 1) {
                $texto .= 'UN MILLON ';
            } else {
                $texto .= convertirMenorMil($millones, $unidades, $especiales, $decenas, $centenas) . ' MILLONES ';
            }
            $num %= 1000000;
        }
        
        // Miles
        if ($num >= 1000) {
            $miles = floor($num / 1000);
            if ($miles == 1) {
                $texto .= 'MIL ';
            } else {
                $texto .= convertirMenorMil($miles, $unidades, $especiales, $decenas, $centenas) . ' MIL ';
            }
            $num %= 1000;
        }
        
        // Unidades, decenas y centenas
        if ($num > 0) {
            $texto .= convertirMenorMil($num, $unidades, $especiales, $decenas, $centenas);
        }
        
        return trim($texto);
    }
    
    // Convertir parte entera
    $textoEntero = convertirCompleto($entero, $unidades, $especiales, $decenas, $centenas);
    
    // Construir el resultado
    $resultado = $textoEntero . ' QUETZALES';
    
    // Agregar centavos si existen
    if ($centavos > 0) {
        $textoCentavos = convertirCompleto($centavos, $unidades, $especiales, $decenas, $centenas);
        $resultado .= ' CON ' . $textoCentavos . ' CENTAVOS';
    }
    
    $resultado .= ' EXACTOS';
    
    return $resultado;
}

$total_letras = numeroALetras($venta['total']);

// Generar PDF si se solicita
if ($guardarPDF) {
    try {
        // Validar y normalizar código de venta
        $codigoVenta = $venta['codigo'];
        if (!preg_match('/^VEN-\d{4}-\d{2}-\d{4}$/', $codigoVenta)) {
            if (preg_match('/^VEN-\d{4}-\d{2}-\d{3}$/', $codigoVenta)) {
                $codigoVenta .= '0'; // Agregar dígito faltante
            } else {
                // Generar código alternativo si el formato es incorrecto
                $codigoVenta = 'VEN-' . date('Y-m') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        }

        // Crear directorio si no existe
        if (!file_exists('facturas')) {
            mkdir('facturas', 0755, true);
        }

        $archivo = 'facturas/' . $codigoVenta . '.pdf';
        
        // Eliminar archivo existente si hay uno
        if (file_exists($archivo)) {
            unlink($archivo);
        }

        // Generar PDF
        if (!file_exists('../fpdf/fpdf.php')) {
            throw new Exception("Biblioteca FPDF no encontrada");
        }
        require_once('../fpdf/fpdf.php');
        
        // Definir clase personalizada para el PDF
        class PDF extends FPDF {
            function Header() {
                // No se necesita un encabezado predefinido
            }
            
            function Footer() {
                // No se necesita un pie de página predefinido
            }
        }
        
        // Crear documento PDF con tamaño personalizado
        $pdf = new PDF('P', 'mm', 'A4');
        $pdf->AddPage();
        
        // Logo en la esquina superior izquierda (más grande)
        // Logo en la esquina superior izquierda (más compacto)
if (file_exists('../imagenes/nutrifluidos.jpg')) {
    $pdf->Image('../imagenes/nutrifluidos.jpg', 10, 8, 50, 25); // Logo más compacto: x=10, y=8, ancho=50, alto=25
}

// Información de la empresa en la parte superior central (más compacta)
$pdf->SetFont('Arial', 'B', 10); // Reducido de 14 a 12
$pdf->SetTextColor(0, 0, 0); // Azul
$pdf->SetXY(65, 10); // Posicionarse después del logo
$pdf->Cell(0, 4, 'NUTRI-FLUIDOS', 0, 1, 'C'); // Reducido de 7 a 4

$pdf->SetFont('Arial', '', 8); // Reducido de 9 a 8
$pdf->SetXY(65, 15); // Ajustado de 18 a 15
$pdf->Cell(0, 3, 'CALLEJON VILLA FLORIDALMA', 0, 1, 'C'); // Reducido de 4 a 3
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(65, 19); // Ajustado de 22 a 19
$pdf->Cell(0, 3, 'TIQUISATE, ESCUINTLA, GUATEMALA', 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(65, 23); // Ajustado de 26 a 23
$pdf->Cell(0, 3, 'NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(65, 27); // Ajustado de 30 a 27
$pdf->Cell(0, 3, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->SetXY(65, 31); // Ajustado de 34 a 31
$pdf->Cell(0, 3, 'NIT: 95675876', 0, 1, 'C');

// Departamentos debajo del logo (más compacto)
$pdf->SetFont('Arial', '', 8); // Reducido de 9 a 8
$pdf->SetTextColor(0, 0, 0); // Negro
$pdf->SetXY(10, 32); // Ajustado de 47 a 35
$pdf->Cell(50, 3, 'NUTRICION - SISTEMAS DE RIEGO', 0, 1, 'C'); // Reducido altura de 4 a 3

// Línea separadora después del encabezado (más arriba)
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.2);
$pdf->Line(10, 38, 200, 38);

// Fecha y número de envío (REDUCIR ALTURA)
$pdf->SetTextColor(0, 0, 0);

// FECHA con menos altura
$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(10, 30); // Posición más cerca de la línea superior
$pdf->Cell(35, 3, 'FECHA: ', 0, 0); // REDUCIDO: altura de 5 a 3
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY(45, 40);
$pdf->Cell(65, 3, $fecha_formateada, 0, 0); // REDUCIDO: altura de 5 a 3

// ENVIO con menos altura
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY(142, 40);
$pdf->Cell(30, 3, 'ENVIO No.: ', 0, 0, 'R'); // REDUCIDO: altura de 5 a 3
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetXY(172, 40);
$pdf->Cell(28, 3, $codigoVenta, 0, 0, 'R'); // REDUCIDO: altura de 5 a 3

// Línea separadora más cerca (REDUCIR ESPACIO)
$pdf->SetLineWidth(0.2);
$pdf->Line(10, 44, 200, 44); // REDUCIDO: de 47 a 44

// Información del cliente más cerca
$pdf->SetXY(10, 46); // REDUCIDO: de 49 a 46 // Movido de 59 a 47
        
        // Información del cliente (posición ajustada)
$pdf->SetXY(10, 49); // Ajustado de 61 a 49
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(20, 5, 'NOMBRE:', 0, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(80, 5, strtoupper($venta['empresa']), 0, 0);

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(30, 5, 'ORDEN DE COMPRA:', 0, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(70, 5, !empty($venta['ordenCompra']) ? $venta['ordenCompra'] : 'N/A', 0, 1);

/// Segunda línea: NIT y DTE
$pdf->SetXY(10, 54);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(20, 5, 'NIT:', 0, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(80, 5, $venta['nit'], 0, 0); // Ya está formateado por la función

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(30, 5, 'DTE:', 0, 0);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(70, 5, !empty($venta['dte']) ? $venta['dte'] : 'N/A', 0, 1);

// Tercera línea: DIRECCION COMPLETA (línea completa)
$pdf->SetXY(10, 59);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(22, 5, 'DIRECCION:', 0, 0);
$pdf->SetFont('Arial', 'B', 9);
// USAR LA DIRECCIÓN COMPLETA CONCATENADA
$pdf->Cell(168, 5, strtoupper($direccion_completa), 0, 1);

// Tabla de productos - Posición fija
$tablaY = 67;
$pdf->SetXY(10, $tablaY);
        $pdf->SetDrawColor(0, 0, 0); // Negro para los bordes de la tabla
        $pdf->SetFillColor(240, 240, 240); // Gris muy claro para el encabezado
        
        // Encabezados de la tabla
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(22, 7, 'CANT. ORDEN', 1, 0, 'C', true);
        $pdf->Cell(78, 7, 'DESCRIPCION', 1, 0, 'C', true);
        $pdf->Cell(22, 7, 'CANT. ENT.', 1, 0, 'C', true);
        $pdf->Cell(22, 7, 'CANT. PEND.', 1, 0, 'C', true);
        $pdf->Cell(28, 7, 'PRECIO UNIT.', 1, 0, 'C', true);
        $pdf->Cell(28, 7, 'VALOR', 1, 1, 'C', true);
        
        // Contenido de la tabla
        $pdf->SetFont('Arial', '', 9);
        
        // Agregar suficientes filas vacías para completar la tabla
        $filasOcupadas = count($detalles);
        $filasTotal = 3; // Queremos tener 3 filas en total
        
        foreach ($detalles as $item) {
            $cantidadPendiente = 0; // Si no hay información de pendientes, asumir 0
            
            $pdf->Cell(22, 7, $item['cantidad'], 1, 0, 'C');
            $pdf->Cell(78, 7, strtoupper($item['nombre']), 1, 0, 'L');
            $pdf->Cell(22, 7, $item['cantidad'], 1, 0, 'C');
            $pdf->Cell(22, 7, $cantidadPendiente, 1, 0, 'C');
            $pdf->Cell(28, 7, 'Q' . number_format($item['precio_unitario'], 2), 1, 0, 'R');
            $pdf->Cell(28, 7, 'Q' . number_format($item['subtotal'], 2), 1, 1, 'R');
        }
        
        // Agregar filas vacías para completar la tabla
        for ($i = 0; $i < ($filasTotal - $filasOcupadas); $i++) {
            $pdf->Cell(22, 7, '', 1, 0, 'C');
            $pdf->Cell(78, 7, '', 1, 0, 'L');
            $pdf->Cell(22, 7, '', 1, 0, 'C');
            $pdf->Cell(22, 7, '', 1, 0, 'C');
            $pdf->Cell(28, 7, '', 1, 0, 'R');
            $pdf->Cell(28, 7, '', 1, 1, 'R');
        }
        
        // ✅ Filas de subtotal y descuento (solo si hay descuento)
        if ($tiene_descuento) {
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(172, 7, 'SUBTOTAL', 1, 0, 'R');
            $pdf->Cell(28, 7, 'Q' . $subtotal_formateado, 1, 1, 'R');
            $pdf->Cell(172, 7, 'DESCUENTO', 1, 0, 'R');
            $pdf->Cell(28, 7, '-Q' . $descuento_formateado, 1, 1, 'R');
        }

        // Fila para el total
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(22, 7, 'TOTAL', 1, 0, 'C');
        $pdf->Cell(150, 7, $total_letras, 1, 0, 'C');
        $pdf->Cell(28, 7, 'Q' . $total_formateado, 1, 1, 'R');
        
        // Sección de firmas
        $pdf->Ln(2);
        
        // Crear celdas para las firmas
        $pdf->Cell(67, 12, '', 1, 0, 'C'); // Espacio para la firma
        $pdf->Cell(67, 12, '', 1, 0, 'C'); // Espacio para la firma
        $pdf->Cell(66, 12, '', 1, 1, 'C'); // Espacio para la firma
        
        // Agregar textos de las firmas (en la parte inferior de cada celda)
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(67, 5, 'FIRMA Y NOMBRE', 1, 0, 'C');
        $pdf->Cell(67, 5, 'FIRMA Y NOMBRE', 1, 0, 'C');
        $pdf->Cell(66, 5, 'FIRMA Y NOMBRE', 1, 1, 'C');
        
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(67, 5, 'TRANSPORTISTA', 1, 0, 'C');
        $pdf->Cell(67, 5, 'RECIBIDO POR', 1, 0, 'C');
        $pdf->Cell(66, 5, 'Vo.Bo. GERENCIA', 1, 1, 'C');
        
        // Sección de observaciones
        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(200, 5, 'OBSERVACIONES: ___________________________________________________________________________', 0, 1);
        
        // Línea para atendido por
        $pdf->Ln(2);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(200, 5, 'ATENDIDO POR: ' . strtoupper($venta['vendedor']), 0, 1);
        
        // Guardar PDF
        $pdf->Output('F', $archivo);

        // Respuesta JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'ruta' => $archivo,
            'codigo' => $codigoVenta
        ]);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Error al generar PDF: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Mostrar comprobante HTML si no se solicita PDF
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMPROBANTE DE VENTA #<?php echo htmlspecialchars($venta['codigo']); ?></title>
    <style>
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 10px;
    font-size: 12px;
}
.comprobante {
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    padding: 10px;
    box-sizing: border-box;
}
.cabecera {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 10px;
    border-bottom: 1px solid #000;
    margin-bottom: 10px;
}
.logo-container {
    width: 200px;
    height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 5px;
}
.logo {
    max-width: 190px;
    max-height: 80px;
    object-fit: contain;
    margin-bottom: 5px;
}
.departamentos-logo {
    font-weight: normal;
    font-size: 11px;
    text-align: center;
    color: #000;
    margin-top: 1px; /* REDUCIDO: de 5px a 1px */
    line-height: 1.1; /* AGREGADO: para mejor espaciado */
}
.info-empresa {
    text-align: center;
    color: #000000;       /* Mantener azul como estaba originalmente */
    flex-grow: 1;
    padding: 0 10px;
}
.info-empresa h2 {
    margin: 0 0 5px 0;
    font-size: 14px;      /* Tamaño 10 del PDF = 14px HTML */
    font-weight: bold;
}
.info-empresa p {
    margin: 2px 0;
    font-size: 11px;      /* Tamaño 8 del PDF = 11px HTML */
    font-weight: normal;  /* Sin negrita */
}
.departamentos-header {
    font-weight: bold;
    font-size: 10px;
    margin-top: 5px;
    display: none;
}
.info-envio {
    display: flex;
    justify-content: space-between;
    padding: 4px 0; /* REDUCIDO: de 8px a 4px */
    border-bottom: 1px solid #000;
    margin-bottom: 10px;
    align-items: center;
    line-height: 1; /* REDUCIDO: para texto más compacto */
} 
.fecha, .envio {
    font-size: 12px;
    font-weight: normal;
    line-height: 1; /* REDUCIDO: para menos espacio entre líneas */
} 
.info-cliente {
    padding: 5px 0;
    margin: 10px 0;
}
.info-cliente p {
    margin: 5px 0;
    font-size: 12px;      /* Equivalente al tamaño 9 del PDF */
}
.info-cliente span {
    font-weight: bold;
}
/* Grid mejorado para alineación exacta como en PDF */
.grid-cliente-nueva {
    display: grid;
    grid-template-columns: 60px 280px 140px 1fr; /* Aumenté la tercera columna de 120px a 140px */
    gap: 5px;
    align-items: center;
    margin-bottom: 8px;
}
.grid-cliente-nueva div:nth-child(3) {
    text-align: left; /* "ORDEN DE COMPRA:" alineado a la izquierda */
    white-space: nowrap; /* Evitar que se parta en múltiples líneas */
}
.grid-cliente-nueva div:nth-child(7) {
    text-align: left; /* "DTE:" alineado a la izquierda */
}
table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
}
th, td {
    border: 1px solid #000;
    padding: 6px;
    text-align: left;
    font-size: 11px;      /* Equivalente al tamaño 9 del PDF */
}
th {
    background-color: #f2f2f2;
    text-align: center;
    font-weight: bold;
}
.precio, .valor {
    text-align: right;
}
.centrado {
    text-align: center;
}
.total {
    font-weight: bold;
    font-size: 10px;      /* Equivalente al tamaño 8 del PDF */
}

.observaciones {
    margin-top: 10px;
    font-size: 12px;      /* Equivalente al tamaño 9 del PDF */
    font-weight: bold;    /* Con negrita como en PDF */
}
.atendido-por {
    margin-top: 10px;
    font-size: 10px;      /* Equivalente al tamaño 8 del PDF */
    font-weight: normal;  /* Sin negrita como en PDF */
}
@media print {
    .no-print {
        display: none;
    }
    body {
        padding: 0;
        margin: 0;
    }
    .comprobante {
        max-width: 18.5cm;
        height: 10.5cm;
    }
}
</style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()">Imprimir Comprobante</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <div class="comprobante">
        <div class="cabecera">
            <div class="logo-container">
                <img src="../imagenes/nutrifluidos.jpg" alt="Logo Nutri-Fluidos" class="logo">
                <div class="departamentos-logo">NUTRICION - SISTEMAS DE RIEGO</div>
            </div>
            <div class="info-empresa">
                <h2>NUTRI-FLUIDOS</h2>
                <p>CALLEJON VILLA FLORIDALMA</p>
                <p>TIQUISATE, ESCUINTLA, GUATEMALA</p>
                <p>NUTRIFLUIDOS@GMAIL.COM</p>
                <p>JACOBO AMILCAR MONTERROSO CHAVEZ</p>
                <p>NIT: 95675876</p>
            </div>
        </div>

        <div class="info-envio">
    <div class="fecha">
        FECHA: <strong><?php echo htmlspecialchars($fecha_formateada); ?></strong>
    </div>
    <div class="envio">
        <strong>ENVIO No.: <?php echo htmlspecialchars($venta['codigo']); ?></strong>
    </div>
</div>

        <div class="info-cliente">
    <div class="grid-cliente-nueva">
        <div>NOMBRE:</div>
        <div><span><?php echo strtoupper(mb_convert_encoding($venta['empresa'], 'UTF-8', 'auto')); ?></span></div>
        <div>ORDEN DE COMPRA:</div>
        <div><span><?php echo !empty($venta['ordenCompra']) ? htmlspecialchars($venta['ordenCompra']) : 'N/A'; ?></span></div>
    </div>
    <div class="grid-cliente-nueva">
        <div>NIT:</div>
        <div><span><?php echo htmlspecialchars($venta['nit']); ?></span></div> <!-- Ya está formateado -->
        <div>DTE:</div>
        <div><span><?php echo !empty($venta['dte']) ? htmlspecialchars($venta['dte']) : 'N/A'; ?></span></div>
    </div>
    <!-- USAR LA DIRECCIÓN COMPLETA CONCATENADA -->
    <p>DIRECCION: <span><?php echo strtoupper(mb_convert_encoding($direccion_completa, 'UTF-8', 'auto')); ?></span></p>
</div>

        <table>
            <thead>
                <tr>
                    <th>CANT. ORDEN</th>
                    <th>DESCRIPCION</th>
                    <th>CANT. ENT.</th>
                    <th>CANT. PEND.</th>
                    <th>PRECIO UNIT.</th>
                    <th>VALOR</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $item): ?>
                <tr>
                    <td class="centrado"><?php echo htmlspecialchars($item['cantidad']); ?></td>
                    <td><?php echo strtoupper(mb_convert_encoding($item['nombre'], 'UTF-8', 'auto')); ?></td>
                    <td class="centrado"><?php echo htmlspecialchars($item['cantidad']); ?></td>
                    <td class="centrado">0</td>
                    <td class="precio">Q<?php echo number_format($item['precio_unitario'], 2); ?></td>
                    <td class="valor">Q<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php 
                // Agregar filas vacías para completar la tabla
                $filasOcupadas = count($detalles);
                $filasTotal = 3; // Queremos tener 3 filas en total
                
                for ($i = 0; $i < ($filasTotal - $filasOcupadas); $i++): 
                ?>
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <?php if ($tiene_descuento): ?>
                <tr>
                    <td colspan="5" class="total valor">SUBTOTAL</td>
                    <td class="total valor">Q<?php echo $subtotal_formateado; ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="total valor">DESCUENTO</td>
                    <td class="total valor">-Q<?php echo $descuento_formateado; ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="total centrado">TOTAL</td>
                    <td colspan="4" class="total centrado"><?php echo $total_letras; ?></td>
                    <td class="total valor">Q<?php echo $total_formateado; ?></td>
                </tr>
            </tfoot>
        </table>

       <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
    <tr>
        <td style="border: 1px solid #000; height: 50px;"></td>
        <td style="border: 1px solid #000; height: 50px;"></td>
        <td style="border: 1px solid #000; height: 50px;"></td>
    </tr>
    <tr>
        <td style="border: 1px solid #000; text-align: center; font-weight: normal; padding: 4px; font-size: 10px;">FIRMA Y NOMBRE</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: normal; padding: 4px; font-size: 10px;">FIRMA Y NOMBRE</td>
        <td style="border: 1px solid #000; text-align: center; font-weight: normal; padding: 4px; font-size: 10px;">FIRMA Y NOMBRE</td>
    </tr>
    <tr>
        <td style="border: 1px solid #000; text-align: center; padding: 4px; font-weight: bold; font-size: 10px;">TRANSPORTISTA</td>
        <td style="border: 1px solid #000; text-align: center; padding: 4px; font-weight: bold; font-size: 10px;">RECIBIDO POR</td>
        <td style="border: 1px solid #000; text-align: center; padding: 4px; font-weight: bold; font-size: 10px;">Vo.Bo. GERENCIA</td>
    </tr>
</table>

        <div class="observaciones">
            <p>OBSERVACIONES: <span style="border-bottom: 1px solid #000; display: inline-block; width: 80%;"></span></p>
        </div>

        <div class="atendido-por">
            <p>ATENDIDO POR: <?php echo strtoupper($venta['vendedor']); ?></p>
        </div>
    </div>

    <script>
        // Imprimir automáticamente al cargar (opcional)
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>