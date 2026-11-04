<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
// informe-cliente.php - Genera informe PDF detallado del cliente
require_once __DIR__ . '/../../../privado/conexion.php';
require_once('../fpdf/fpdf.php');

// Configuración de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Validar ID de cliente
if (!isset($_GET['cliente_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID de cliente no especificado']);
    exit;
}

$cliente_id = intval($_GET['cliente_id']);
date_default_timezone_set('America/Guatemala');

try {
    // Obtener información del cliente
    $query = "
        SELECT 
            c.*,
            u.nombre_completo as vendedor_asignado
        FROM clientes c
        LEFT JOIN usuarios u ON c.id_vendedor = u.id
        WHERE c.id = ?
    ";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    
    if (!$cliente) {
        throw new Exception('Cliente no encontrado');
    }
    
    // Obtener estadísticas
    $query_stats = "
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
    
    $stmt = $conexion->prepare($query_stats);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    
    // Obtener historial completo
    $query_historial = "
        SELECT 
            v.codigo,
            v.fecha,
            v.total,
            v.tipo_venta,
            v.estado
        FROM ventas v
        WHERE v.cliente_id = ?
        ORDER BY v.fecha DESC
        LIMIT 20
    ";
    
    $stmt = $conexion->prepare($query_historial);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $historial = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Obtener productos favoritos
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
        GROUP BY p.id
        ORDER BY cantidad_total DESC
        LIMIT 10
    ";
    
    $stmt = $conexion->prepare($query_productos);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Obtener créditos pendientes
    $query_creditos = "
        SELECT 
            vc.*,
            v.codigo as venta_codigo,
            v.fecha as fecha_venta,
            v.total as venta_total
        FROM venta_credito vc
        INNER JOIN ventas v ON vc.venta_id = v.id
        WHERE v.cliente_id = ? AND vc.estado = 1 AND vc.saldo_pendiente > 0
        ORDER BY vc.fecha_primer_pago ASC
    ";
    
    $stmt = $conexion->prepare($query_creditos);
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $creditos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos: ' . $e->getMessage()]);
    exit;
}

// Crear PDF
class PDF extends FPDF {
    function Header() {
    // Solo mostrar encabezado en la primera página
    if ($this->PageNo() == 1) {
        // Logo más grande a la izquierda
        if (file_exists('../imagenes/nutrifluidos.jpg')) {
            $this->Image('../imagenes/nutrifluidos.jpg', 10, 8, 50); // x=10, y=8, ancho=50
        }
        
        // Datos de la empresa centrados a la derecha del logo
        $this->SetXY(65, 10); // Posicionarse después del logo
        
        // Título de la empresa
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0); // Negro para consistencia
        $this->Cell(0, 4, 'NUTRI-FLUIDOS', 0, 1, 'C');
        
        // Dirección
        $this->SetXY(65, 15);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, 'CALLEJON VILLA FLORIDALMA', 0, 1, 'C');
        
        $this->SetXY(65, 19);
        $this->Cell(0, 3, 'TIQUISATE, ESCUINTLA, GUATEMALA', 0, 1, 'C');
        
        // Email
        $this->SetXY(65, 23);
        $this->Cell(0, 3, 'NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'C');
        
        // Nombre del responsable
        $this->SetXY(65, 27);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C');
        
        // NIT
        $this->SetXY(65, 31);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, 'NIT: 95675876', 0, 1, 'C');
        
        // Línea separadora
        $this->Ln(4);
        $this->Line(10, 38, 200, 38); // Línea horizontal
        
        // Título del reporte
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 5, 'INFORME DETALLADO DE CLIENTE', 0, 1, 'C');
        
        // Fecha del reporte
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, 'Fecha de reporte: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $this->Ln(3);
    } else {
        // En páginas adicionales, solo un pequeño espacio en la parte superior
        $this->Ln(10);
    }
}
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Página ' . $this->PageNo() . ' - Nutri-Fluidos', 0, 0, 'C');
    }
    
    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 255);
        $this->Cell(0, 8, $this->removerTildes($title), 0, 1, 'L');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }
    
    function removerTildes($texto) {
        $tildes = array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U'
        );
        return strtr($texto, $tildes);
    }
    
    function InfoRow($label, $value) {
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(40, 6, $this->removerTildes($label) . ':', 0, 0, 'L');
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 6, $this->removerTildes($value), 0, 1, 'L');
    }
}

$pdf = new PDF();
$pdf->AddPage();

// INFORMACIÓN DEL CLIENTE
$pdf->SectionTitle('INFORMACION DEL CLIENTE');

$pdf->InfoRow('Empresa', $cliente['empresa']);
$pdf->InfoRow('NIT', $cliente['nit'] ?: 'No especificado');
$pdf->InfoRow('Estado', $cliente['estado'] == 1 ? 'Activo' : 'Inactivo');
$pdf->InfoRow('Contacto Principal', $cliente['contacto1'] ?: 'No especificado');
$pdf->InfoRow('Telefono', $cliente['telefono1'] ?: 'No especificado');

if ($cliente['contacto2']) {
    $pdf->InfoRow('Contacto Secundario', $cliente['contacto2']);
}
if ($cliente['telefono2']) {
    $pdf->InfoRow('Telefono 2', $cliente['telefono2']);
}

$pdf->InfoRow('Email', $cliente['email'] ?: 'No especificado');
$pdf->InfoRow('Direccion', $cliente['direccion'] ?: 'No especificado');
$pdf->InfoRow('Vendedor Asignado', $cliente['vendedor_asignado'] ?: 'No asignado');
$pdf->InfoRow('Cliente desde', date('d/m/Y', strtotime($cliente['fecha_registro'])));

$pdf->Ln(5);

// ESTADÍSTICAS DE COMPRAS
$pdf->SectionTitle('ESTADISTICAS DE COMPRAS');

$pdf->InfoRow('Total de Compras', $stats['total_compras'] ?: '0');
$pdf->InfoRow('Valor Total Comprado', 'Q' . number_format($stats['valor_total'] ?: 0, 2));
$pdf->InfoRow('Ticket Promedio', 'Q' . number_format($stats['ticket_promedio'] ?: 0, 2));
$pdf->InfoRow('Primera Compra', $stats['primera_compra'] ? date('d/m/Y', strtotime($stats['primera_compra'])) : 'N/A');
$pdf->InfoRow('Ultima Compra', $stats['ultima_compra'] ? date('d/m/Y', strtotime($stats['ultima_compra'])) : 'N/A');
$pdf->InfoRow('Compras al Contado', $stats['compras_contado'] ?: '0');
$pdf->InfoRow('Compras al Credito', $stats['compras_credito'] ?: '0');

$pdf->Ln(5);

// CRÉDITOS PENDIENTES
if (!empty($creditos)) {
    $pdf->SectionTitle('CREDITOS PENDIENTES');
    
    // Encabezados de tabla con altura fija
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(30, 8, 'Venta', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Total', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Saldo Pend.', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Cuota', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Prox. Pago', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 8);
    foreach ($creditos as $credito) {
        $pdf->Cell(30, 7, $pdf->removerTildes($credito['venta_codigo']), 1, 0, 'C');
        $pdf->Cell(25, 7, date('d/m/Y', strtotime($credito['fecha_venta'])), 1, 0, 'C');
        $pdf->Cell(30, 7, 'Q' . number_format($credito['venta_total'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, 'Q' . number_format($credito['saldo_pendiente'], 2), 1, 0, 'R');
        $pdf->Cell(25, 7, 'Q' . number_format($credito['monto_cuota'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, date('d/m/Y', strtotime($credito['fecha_primer_pago'])), 1, 1, 'C');
    }
    $pdf->Ln(5);
}

// PRODUCTOS FAVORITOS
if (!empty($productos)) {
    $pdf->SectionTitle('PRODUCTOS MAS COMPRADOS');
    
    // Encabezados con altura fija y mejor distribución
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 8, 'Codigo', 1, 0, 'C');
    $pdf->Cell(85, 8, 'Producto', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Cantidad', 1, 0, 'C');
    $pdf->Cell(20, 8, 'Veces', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Precio Prom.', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 8);
    foreach (array_slice($productos, 0, 10) as $producto) {
        // Calcular altura necesaria para el nombre del producto
        $nombreProducto = $pdf->removerTildes($producto['nombre']);
        if (strlen($nombreProducto) > 45) {
            $nombreProducto = substr($nombreProducto, 0, 42) . '...';
        }
        
        $pdf->Cell(25, 7, $pdf->removerTildes($producto['codigo']), 1, 0, 'C');
        $pdf->Cell(85, 7, $nombreProducto, 1, 0, 'L');
        $pdf->Cell(25, 7, $producto['cantidad_total'], 1, 0, 'C');
        $pdf->Cell(20, 7, $producto['veces_comprado'], 1, 0, 'C');
        $pdf->Cell(25, 7, 'Q' . number_format($producto['precio_promedio'], 2), 1, 1, 'R');
    }
}

// Nueva página para historial si es necesario
if ($pdf->GetY() > 220) {
    $pdf->AddPage();
}

$pdf->Ln(5);

// HISTORIAL DE COMPRAS
if (!empty($historial)) {
    $pdf->SectionTitle('HISTORIAL DE COMPRAS RECIENTES');
    
    // Encabezados con altura fija y mejor distribución
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C');
    $pdf->Cell(45, 8, 'Factura', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Tipo', 1, 0, 'C');
    $pdf->Cell(30, 8, 'Total', 1, 0, 'C');
    $pdf->Cell(25, 8, 'Estado', 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 8);
    foreach (array_slice($historial, 0, 15) as $venta) {
        $pdf->Cell(25, 7, date('d/m/Y', strtotime($venta['fecha'])), 1, 0, 'C');
        $pdf->Cell(45, 7, $pdf->removerTildes($venta['codigo']), 1, 0, 'C');
        $pdf->Cell(25, 7, $venta['tipo_venta'] == 'contado' ? 'Contado' : 'Credito', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Q' . number_format($venta['total'], 2), 1, 0, 'R');
        $pdf->Cell(25, 7, $venta['estado'] == 1 ? 'Activa' : 'Anulada', 1, 1, 'C');
    }
}

// Generar nombre del archivo sin tildes
$nombreEmpresa = $pdf->removerTildes($cliente['empresa']);
$nombreArchivo = 'informe_cliente_' . $nombreEmpresa . '_' . date('Y-m-d_H-i-s') . '.pdf';
$nombreArchivo = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreArchivo);

// Crear directorio si no existe
if (!file_exists('informes')) {
    mkdir('informes', 0755, true);
}

$rutaArchivo = 'informes/' . $nombreArchivo;

// Guardar archivo
$pdf->Output('F', $rutaArchivo);

// Respuesta
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'archivo' => 'php/archivo.php?tipo=informe&f=' . rawurlencode(basename($rutaArchivo)),
    'nombre' => $nombreArchivo,
    'cliente' => $cliente['empresa']
]);

?>