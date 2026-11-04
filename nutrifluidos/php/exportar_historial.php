<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();
require_once __DIR__ . '/../../../privado/conexion.php';
require_once '../fpdf/fpdf.php'; // Ajusta la ruta segun donde tengas FPDF

// Clase personalizada para el reporte
class ReporteVentas extends FPDF
{
    private $filtros;
    
    public function setFiltros($filtros) {
        $this->filtros = $filtros;
    }
    
    // Cabecera de pagina
    // Cabecera de pagina
function Header()
{
    // Solo mostrar encabezado completo en la primera página
    if ($this->PageNo() == 1) {
        // Logo más grande a la izquierda
        if (file_exists('../imagenes/nutrifluidos.jpg')) {
            $this->Image('../imagenes/nutrifluidos.jpg', 10, 8, 50); // x=10, y=8, ancho=50
        }
        
        // Datos de la empresa centrados a la derecha del logo
        $this->SetXY(65, 10); // Posicionarse después del logo
        
        // Título de la empresa
        $this->SetFont('Arial', 'B', 12);
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
        $this->Line(10, 38, 290, 38); // Línea horizontal ajustada para orientación horizontal (290 en lugar de 200)
        
        // Título del reporte
        $this->Ln(2);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 5, 'HISTORIAL DE VENTAS', 0, 1, 'C');
        
        // Información de filtros (más compacta)
        $this->SetFont('Arial', '', 8);
        $filtrosTexto = "Filtros: ";
        
        if (!empty($this->filtros['tipo_venta'])) {
            $filtrosTexto .= "Tipo: " . ucfirst($this->filtros['tipo_venta']) . " | ";
        }
        if (!empty($this->filtros['cliente'])) {
            $filtrosTexto .= "Cliente: " . $this->filtros['cliente'] . " | ";
        }
        if (!empty($this->filtros['fecha_inicio']) && !empty($this->filtros['fecha_fin'])) {
            $filtrosTexto .= "Periodo: " . $this->filtros['fecha_inicio'] . " - " . $this->filtros['fecha_fin'] . " | ";
        } elseif (!empty($this->filtros['fecha_inicio'])) {
            $filtrosTexto .= "Desde: " . $this->filtros['fecha_inicio'] . " | ";
        } elseif (!empty($this->filtros['fecha_fin'])) {
            $filtrosTexto .= "Hasta: " . $this->filtros['fecha_fin'] . " | ";
        }
        
        $filtrosTexto .= "Generado: " . date('d/m/Y H:i:s');
        
        $this->Cell(0, 5, $filtrosTexto, 0, 1, 'L');
        $this->Ln(2);
        
        // Encabezados de tabla (ajustados para orientación horizontal)
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        
        $this->Cell(35, 8, 'No. Factura', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $this->Cell(80, 8, 'Cliente', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Vendedor', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Tipo', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Total', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Estado', 1, 1, 'C', true);
    } else {
        // En páginas adicionales, solo encabezados de tabla
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);
        
        $this->Cell(35, 8, 'No. Factura', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $this->Cell(80, 8, 'Cliente', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Vendedor', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Tipo', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Total', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Estado', 1, 1, 'C', true);
    }
}
    
    // Pie de pagina
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().' de {nb}',0,0,'C');
        $this->Cell(0,10,'Sistema NUTRI-FLUIDOS - '.date('d/m/Y'),0,0,'R');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = leerJsonAutenticado();
    
    if (!isset($input['accion']) || $input['accion'] !== 'exportarHistorialPDF') {
        http_response_code(400);
        echo json_encode(['error' => 'Accion no valida']);
        exit;
    }
    
    try {
        // Obtener datos del filtro y usuario
        $filtros = $input['filtros'];
        $ventas = $input['ventas'];
        
        // Crear instancia del PDF en orientacion horizontal
        $pdf = new ReporteVentas('L'); // 'L' = Landscape (horizontal)
        $pdf->setFiltros($filtros);
        $pdf->AliasNbPages();
        $pdf->AddPage();
        
        // Datos de ventas
        $pdf->SetFont('Arial','',8);
        $totalGeneral = 0;
        $contadorVentas = 0;
        $contadorFilas = 0;
        
        foreach ($ventas as $venta) {
            $contadorFilas++;
            
            // Preparar datos (con mas espacio para nombres)
            $fecha = date('d/m/Y', strtotime($venta['fecha']));
            $cliente = strlen($venta['cliente']) > 65 ? substr($venta['cliente'], 0, 62) . '...' : $venta['cliente'];
            $vendedor = strlen($venta['vendedor']) > 40 ? substr($venta['vendedor'], 0, 37) . '...' : $venta['vendedor'];
            $tipo = $venta['tipo_venta'] === 'credito' ? 'Credito' : 'Contado';
            $total = 'Q' . number_format($venta['total'], 2);
            $estado = $venta['estado'] == 1 ? 'Activa' : 'Anulada';
            
            // Solo sumar si esta activa
            if ($venta['estado'] == 1) {
                $totalGeneral += floatval($venta['total']);
                $contadorVentas++;
            }
            
            // Establecer color de fondo alternado
            $fill = ($contadorFilas % 2 == 0);
            if ($fill) {
                $pdf->SetFillColor(248, 248, 248);
            }
            
            // Color diferente para ventas anuladas
            if ($venta['estado'] != 1) {
                $pdf->SetFillColor(255, 200, 200);
                $fill = true;
            }
            
            $pdf->Cell(35,6,$venta['codigo'],1,0,'C',$fill);
            $pdf->Cell(25,6,$fecha,1,0,'C',$fill);
            $pdf->Cell(80,6,$cliente,1,0,'L',$fill);
            $pdf->Cell(50,6,$vendedor,1,0,'L',$fill);
            $pdf->Cell(25,6,$tipo,1,0,'C',$fill);
            $pdf->Cell(30,6,$total,1,0,'R',$fill);
            $pdf->Cell(20,6,$estado,1,1,'C',$fill);
        }
        
        // Totales finales
        $pdf->Ln(5);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(200, 200, 200);
        
        $pdf->Cell(215,8,'TOTALES:',1,0,'R',true);
        $pdf->Cell(30,8,'Q' . number_format($totalGeneral, 2),1,0,'R',true);
        $pdf->Cell(20,8,$contadorVentas,1,1,'C',true);
        
        // Informacion adicional
        $pdf->Ln(5);
        $pdf->SetFont('Arial','',8);
        $pdf->SetFillColor(255, 255, 255);
        
        $pdf->Cell(0,5,'RESUMEN:',0,1,'L');
        $pdf->Cell(0,5,'- Total de ventas activas: ' . $contadorVentas,0,1,'L');
        $pdf->Cell(0,5,'- Total de ventas anuladas: ' . (count($ventas) - $contadorVentas),0,1,'L');
        $pdf->Cell(0,5,'- Monto total de ventas activas: Q' . number_format($totalGeneral, 2),0,1,'L');
        
        if ($contadorVentas > 0) {
            $pdf->Cell(0,5,'- Promedio por venta: Q' . number_format($totalGeneral / $contadorVentas, 2),0,1,'L');
        }
        
        // Estadisticas por tipo de venta
        $ventasContado = 0;
        $ventasCredito = 0;
        $montoContado = 0;
        $montoCredito = 0;
        
        foreach ($ventas as $venta) {
            if ($venta['estado'] == 1) {
                if ($venta['tipo_venta'] === 'contado') {
                    $ventasContado++;
                    $montoContado += floatval($venta['total']);
                } else {
                    $ventasCredito++;
                    $montoCredito += floatval($venta['total']);
                }
            }
        }
        
        $pdf->Ln(3);
        $pdf->Cell(0,5,'ESTADISTICAS POR TIPO DE VENTA:',0,1,'L');
        $pdf->Cell(0,5,'- Ventas al contado: ' . $ventasContado . ' (Q' . number_format($montoContado, 2) . ')',0,1,'L');
        $pdf->Cell(0,5,'- Ventas a credito: ' . $ventasCredito . ' (Q' . number_format($montoCredito, 2) . ')',0,1,'L');
        
        // Generar nombre del archivo
$fechaActual = date('Y-m-d_H-i-s');
$tipoTexto = !empty($filtros['tipo_venta']) ? '_' . $filtros['tipo_venta'] : '';
$fechaTexto = '';

if (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
    $fechaTexto = '_' . $filtros['fecha_inicio'] . '_a_' . $filtros['fecha_fin'];
} elseif (!empty($filtros['fecha_inicio'])) {
    $fechaTexto = '_desde_' . $filtros['fecha_inicio'];
} elseif (!empty($filtros['fecha_fin'])) {
    $fechaTexto = '_hasta_' . $filtros['fecha_fin'];
}

$nombreArchivo = 'historial_ventas' . $tipoTexto . $fechaTexto . '_' . $fechaActual . '.pdf';
        
        // Configurar headers para descarga
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Salida del PDF
        $pdf->Output('D', $nombreArchivo);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al generar PDF: ' . $e->getMessage()]);
    }
    
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
}
?>