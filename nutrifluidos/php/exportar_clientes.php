<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();
require_once __DIR__ . '/../../../privado/conexion.php';
require_once '../fpdf/fpdf.php'; // Ajusta la ruta según donde tengas FPDF

// Clase personalizada para el reporte de clientes con regiones
class ReporteClientesConRegiones extends FPDF
{
    // Cabecera de página
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
            $this->SetFont('Arial', 'B', 14);
            $this->Cell(0, 6, 'NUTRI-FLUIDOS', 0, 1, 'C');
            
            // Dirección
            $this->SetXY(65, 17);
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 4, 'CALLEJON VILLA FLORIDALMA', 0, 1, 'C');
            
            $this->SetXY(65, 22);
            $this->Cell(0, 4, 'TIQUISATE, ESCUINTLA, GUATEMALA', 0, 1, 'C');
            
            // Email
            $this->SetXY(65, 27);
            $this->Cell(0, 4, 'NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'C');
            
            // Nombre del responsable
            $this->SetXY(65, 32);
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 4, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C');
            
            // NIT
            $this->SetXY(65, 37);
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 4, 'NIT: 95675876', 0, 1, 'C');
            
            // Línea separadora
            $this->Ln(6);
            $this->Line(10, 45, 290, 45); // Línea horizontal ajustada para orientación horizontal
            
            // Título del reporte
            $this->Ln(4);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 6, 'REPORTE DE CLIENTES POR REGION', 0, 1, 'C');
            
            // Información del reporte (más compacta)
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 5, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'L');
            $this->Ln(3);
        } else {
            // En páginas posteriores, solo mostrar título del reporte
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 8, 'REPORTE DE CLIENTES POR REGION - Pagina ' . $this->PageNo(), 0, 1, 'C');
            $this->Ln(3);
        }
    }
    
    // Función para dividir texto en múltiples líneas
    function DividirTexto($texto, $anchoMaximo, $fuente = 'Arial', $estilo = '', $tamaño = 7)
    {
        if (empty($texto) || $texto == '-') return ['-'];
        
        $this->SetFont($fuente, $estilo, $tamaño);
        $anchoTexto = $this->GetStringWidth($texto);
        
        if ($anchoTexto <= $anchoMaximo) {
            return [$texto];
        }
        
        // Dividir el texto en palabras
        $palabras = explode(' ', $texto);
        $lineas = [];
        $lineaActual = '';
        
        foreach ($palabras as $palabra) {
            $lineaTest = empty($lineaActual) ? $palabra : $lineaActual . ' ' . $palabra;
            
            if ($this->GetStringWidth($lineaTest) <= $anchoMaximo) {
                $lineaActual = $lineaTest;
            } else {
                if (!empty($lineaActual)) {
                    $lineas[] = $lineaActual;
                    $lineaActual = $palabra;
                } else {
                    // Si una sola palabra es muy larga, truncarla
                    $lineas[] = substr($palabra, 0, 15) . '...';
                    $lineaActual = '';
                }
            }
        }
        
        if (!empty($lineaActual)) {
            $lineas[] = $lineaActual;
        }
        
        return $lineas;
    }
    
    // Función para crear celda con múltiples líneas
    function CeldaMultiLinea($ancho, $alto, $texto, $borde = 1, $alineacion = 'L', $fill = false)
    {
        $lineas = $this->DividirTexto($texto, $ancho - 2); // -2 para margen interno
        $alturaLinea = $alto / max(1, count($lineas));
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Establecer color de fondo si es necesario
        if ($fill) {
            $this->SetFillColor(248, 248, 248);
        }
        
        // Dibujar el borde y fondo de la celda completa
        if ($borde) {
            $this->Rect($x, $y, $ancho, $alto, $fill ? 'FD' : 'D');
        }
        
        // Escribir cada línea
        foreach ($lineas as $i => $linea) {
            $this->SetXY($x + 1, $y + ($i * $alturaLinea) + 1); // +1 para margen interno
            $this->Cell($ancho - 2, $alturaLinea, $linea, 0, 0, $alineacion, false);
        }
        
        // Mover a la siguiente posición
        $this->SetXY($x + $ancho, $y);
    }
    
    // Función para dibujar encabezados de tabla
    function DibujarEncabezadosTabla()
    {
        $this->SetFont('Arial', 'B', 8);
        $this->SetFillColor(230, 230, 230);
        
        $this->Cell(35, 8, 'Empresa', 1, 0, 'C', true);
        $this->Cell(20, 8, 'NIT', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Contacto 1', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Telefono 1', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Contacto 2', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Telefono 2', 1, 0, 'C', true);
        $this->Cell(55, 8, 'Email', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Region', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Vendedor', 1, 1, 'C', true);
    }
    
    // Función para dibujar encabezado de región
    function DibujarEncabezadoRegion($region, $cantidadClientes)
    {
        // Verificar si necesitamos una nueva página
        if ($this->GetY() > 160) { // Si estamos muy abajo en la página
            $this->AddPage();
        }
        
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(52, 152, 219); // Azul
        $this->SetTextColor(255, 255, 255); // Texto blanco
        
        $textoRegion = strtoupper($region ?: 'SIN REGION ASIGNADA') . " ({$cantidadClientes} clientes)";
        $this->Cell(0, 8, $textoRegion, 1, 1, 'C', true);
        
        $this->SetTextColor(0, 0, 0); // Restaurar texto negro
        
        // Dibujar encabezados de columnas
        $this->DibujarEncabezadosTabla();
    }
    
    // Función para verificar espacio disponible antes de agregar una fila
    function VerificarEspacio($alturaFila = 12)
    {
        if ($this->GetY() + $alturaFila > 180) { // Dejar margen para pie de página
            $this->AddPage();
            // Redibujar encabezados en nueva página
            $this->DibujarEncabezadosTabla();
        }
    }
    
    // Pie de página
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().' de {nb}',0,0,'C');
        $this->Cell(0,10,'Sistema Nutri-Fluidos - '.date('d/m/Y'),0,0,'R');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = leerJsonAutenticado();
    
    if (!isset($input['accion']) || $input['accion'] !== 'generar_reporte') {
        http_response_code(400);
        echo json_encode(['error' => 'Accion no valida']);
        exit;
    }
    
    // Verificar permisos de administrador
    if (!isset($input['usuario_id']) || !isset($input['rol'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario no autenticado']);
        exit;
    }
    
    if ($input['rol'] !== 'administrador') {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para generar reportes']);
        exit;
    }
    
    try {
        // Obtener todos los clientes activos AGRUPADOS POR REGIÓN
        $sql = "SELECT c.*, u.nombre_completo as nombre_vendedor 
                FROM clientes c
                LEFT JOIN usuarios u ON c.id_vendedor = u.id
                WHERE c.estado = 1
                ORDER BY 
                    CASE 
                        WHEN c.region IS NULL THEN 'ZZZZ' 
                        ELSE c.region 
                    END ASC, 
                    c.empresa ASC";
        
        $resultado = $conexion->query($sql);
        
        if (!$resultado) {
            throw new Exception('Error al consultar clientes: ' . $conexion->error);
        }
        
        // Agrupar clientes por región
        $clientesPorRegion = [];
        $totalClientes = 0;
        $vendedoresStats = [];
        $regionesStats = [];
        
        while ($cliente = $resultado->fetch_assoc()) {
            $region = $cliente['region'] ?: 'Sin región asignada';
            
            if (!isset($clientesPorRegion[$region])) {
                $clientesPorRegion[$region] = [];
            }
            
            $clientesPorRegion[$region][] = $cliente;
            $totalClientes++;
            
            // Estadísticas por vendedor
            $vendedor = $cliente['nombre_vendedor'] ?? 'Sin asignar';
            if (!isset($vendedoresStats[$vendedor])) {
                $vendedoresStats[$vendedor] = 0;
            }
            $vendedoresStats[$vendedor]++;
            
            // Estadísticas por región
            if (!isset($regionesStats[$region])) {
                $regionesStats[$region] = 0;
            }
            $regionesStats[$region]++;
        }
        
        // Crear instancia del PDF en orientación horizontal
        $pdf = new ReporteClientesConRegiones('L'); // 'L' = Landscape (horizontal)
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetMargins(5, 10, 5); // Márgenes reducidos: izquierdo, superior, derecho
        $pdf->SetAutoPageBreak(true, 15); // Salto automático con margen inferior
        
        // Mostrar clientes agrupados por región
        $pdf->SetFont('Arial','',7);
        
        foreach ($clientesPorRegion as $region => $clientes) {
            // Encabezado de región
            $pdf->DibujarEncabezadoRegion($region, count($clientes));
            
            $contadorFilas = 0;
            
            foreach ($clientes as $cliente) {
                $contadorFilas++;
                
                // Verificar espacio antes de agregar fila (ahora necesitamos más espacio para filas múltiples)
                $pdf->VerificarEspacio(12);
                
                // Preparar datos sin truncar
                $empresa = $cliente['empresa'] ?? '';
                $nit = $cliente['nit'] ?? '-';
                $contacto1 = $cliente['contacto1'] ?? '';
                $telefono1 = $cliente['telefono1'] ?? '-';
                $contacto2 = $cliente['contacto2'] ?? '-';
                $telefono2 = $cliente['telefono2'] ?? '-';
                $email = $cliente['email'] ?? '-';
                $regionTexto = $cliente['region'] ?: 'N/A';
                $vendedorNombre = $cliente['nombre_vendedor'] ?? 'Sin asignar';
                
                // Establecer color de fondo alternado
                $fill = ($contadorFilas % 2 == 0);
                
                // Calcular altura de fila basada en el contenido
                $alturaFila = 12; // Altura fija para mantener consistencia
                
                // Usar celdas multilínea
                $y_inicial = $pdf->GetY();
                
                $pdf->SetFont('Arial','',7);
                $pdf->CeldaMultiLinea(35, $alturaFila, $empresa, 1, 'L', $fill);
                $pdf->CeldaMultiLinea(20, $alturaFila, $nit, 1, 'C', $fill);
                $pdf->CeldaMultiLinea(30, $alturaFila, $contacto1, 1, 'L', $fill);
                $pdf->CeldaMultiLinea(20, $alturaFila, $telefono1, 1, 'C', $fill);
                $pdf->CeldaMultiLinea(30, $alturaFila, $contacto2, 1, 'L', $fill);
                $pdf->CeldaMultiLinea(20, $alturaFila, $telefono2, 1, 'C', $fill);
                $pdf->CeldaMultiLinea(55, $alturaFila, $email, 1, 'L', $fill);
                $pdf->CeldaMultiLinea(25, $alturaFila, $regionTexto, 1, 'C', $fill);
                $pdf->CeldaMultiLinea(35, $alturaFila, $vendedorNombre, 1, 'L', $fill);
                
                // Mover a la siguiente fila
                $pdf->SetXY(5, $y_inicial + $alturaFila);
            }
            
            // Subtotal por región
            $pdf->Ln(3);
            $pdf->SetFont('Arial','B',8);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->Cell(235,7,'SUBTOTAL REGION ' . strtoupper($region) . ':',1,0,'R',true);
            $pdf->Cell(35,7,count($clientes),1,1,'C',true);
            $pdf->SetFont('Arial','',7);
            
            $pdf->Ln(4);
        }
        
        // RESUMEN GENERAL - Nueva página para estadísticas
        $pdf->AddPage();
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10,'RESUMEN GENERAL',0,1,'C');
        $pdf->Ln(5);
        
        // Total de clientes
        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(200,10,'TOTAL DE CLIENTES:',1,0,'R',true);
        $pdf->Cell(50,10,$totalClientes,1,1,'C',true);
        
        // Estadísticas por región
        $pdf->Ln(8);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,'ESTADISTICAS POR REGION:',0,1,'L');
        $pdf->Ln(3);
        
        $pdf->SetFont('Arial','B',10);
        $pdf->SetFillColor(230, 230, 230);
        
        $pdf->Cell(120,8,'Region',1,0,'C',true);
        $pdf->Cell(40,8,'Clientes',1,0,'C',true);
        $pdf->Cell(40,8,'Porcentaje',1,1,'C',true);
        
        $pdf->SetFont('Arial','',9);
        arsort($regionesStats); // Ordenar por cantidad descendente
        
        $contadorStats = 0;
        foreach ($regionesStats as $region => $cantidad) {
            $contadorStats++;
            $porcentaje = ($cantidad / $totalClientes) * 100;
            
            $fill = ($contadorStats % 2 == 0);
            if ($fill) {
                $pdf->SetFillColor(248, 248, 248);
            }
            
            $pdf->Cell(120,7,$region,1,0,'L',$fill);
            $pdf->Cell(40,7,$cantidad,1,0,'C',$fill);
            $pdf->Cell(40,7,number_format($porcentaje, 1) . '%',1,1,'C',$fill);
        }
        
        // Estadísticas por vendedor
        $pdf->Ln(8);
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,8,'ESTADISTICAS POR VENDEDOR:',0,1,'L');
        $pdf->Ln(3);
        
        $pdf->SetFont('Arial','B',10);
        $pdf->SetFillColor(230, 230, 230);
        
        $pdf->Cell(120,8,'Vendedor',1,0,'C',true);
        $pdf->Cell(40,8,'Clientes',1,0,'C',true);
        $pdf->Cell(40,8,'Porcentaje',1,1,'C',true);
        
        $pdf->SetFont('Arial','',9);
        arsort($vendedoresStats); // Ordenar por cantidad descendente
        
        $contadorStats = 0;
        foreach ($vendedoresStats as $vendedor => $cantidad) {
            $contadorStats++;
            $porcentaje = ($cantidad / $totalClientes) * 100;
            
            $fill = ($contadorStats % 2 == 0);
            if ($fill) {
                $pdf->SetFillColor(248, 248, 248);
            }
            
            $pdf->Cell(120,7,$vendedor,1,0,'L',$fill);
            $pdf->Cell(40,7,$cantidad,1,0,'C',$fill);
            $pdf->Cell(40,7,number_format($porcentaje, 1) . '%',1,1,'C',$fill);
        }
        
        // Información adicional
        $pdf->Ln(10);
        $pdf->SetFont('Arial','B',11);
        $pdf->SetFillColor(255, 255, 255);
        
        $pdf->Cell(0,7,'INFORMACION ADICIONAL:',0,1,'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(0,6,' Total de clientes activos: ' . $totalClientes,0,1,'L');
        $pdf->Cell(0,6,' Numero de regiones con clientes: ' . count($regionesStats),0,1,'L');
        $pdf->Cell(0,6,' Numero de vendedores con clientes: ' . count($vendedoresStats),0,1,'L');
        
        if ($totalClientes > 0) {
            $promedioClientesPorVendedor = $totalClientes / count($vendedoresStats);
            $promedioClientesPorRegion = $totalClientes / count($regionesStats);
            $pdf->Cell(0,6,' Promedio de clientes por vendedor: ' . number_format($promedioClientesPorVendedor, 1),0,1,'L');
            $pdf->Cell(0,6,' Promedio de clientes por region: ' . number_format($promedioClientesPorRegion, 1),0,1,'L');
        }
        
        // Generar nombre del archivo
        $fechaActual = date('Y-m-d_H-i-s');
        $nombreArchivo = 'reporte_clientes_por_region_' . $fechaActual . '.pdf';
        
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