<?php
date_default_timezone_set('America/Guatemala');

// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
require_once '../fpdf/fpdf.php';


class InformeVentasPDF extends FPDF
{
    private $titulo;
    private $periodo;
    
    function __construct($titulo = 'Informe de Ventas', $periodo = '') {
        parent::__construct();
        $this->titulo = $titulo;
        $this->periodo = $periodo;
    }
    
    // Cabecera de página
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
        $this->Cell(0, 5, utf8_decode($this->titulo), 0, 1, 'C');
        
        // Período (si existe)
        if ($this->periodo) {
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 4, utf8_decode('Periodo: ' . $this->periodo), 0, 1, 'C');
        }
        
        // Fecha del reporte
        $this->SetFont('Arial', '', 9);
        $this->Cell(0, 4, 'Fecha de reporte: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $this->Ln(3);
    } else {
        // En páginas adicionales, solo un pequeño espacio en la parte superior
        $this->Ln(10);
    }
}
    
    // Pie de página
    function Footer()
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial','I',8);
        // Número de página
        $this->Cell(0,10, utf8_decode('Página ').$this->PageNo().'/{nb}', 0, 0, 'C');
        
        // Fecha de generación
        $this->SetY(-25);
        $this->Cell(0,10, utf8_decode('Generado el: ' . date('d/m/Y H:i')), 0, 0, 'R');
    }
    
    // Tabla de estadísticas
    function TablaEstadisticas($stats)
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10, utf8_decode('Resumen Ejecutivo'), 0, 1, 'L');
        $this->Ln(5);
        
        // Colores de fondo
        $this->SetFillColor(230,230,230);
        
        // Anchos de columna
        $w = array(70, 40);
        
        $data = array(
            array('Ventas Totales', 'Q' . number_format($stats['total_ventas'], 2)),
            array('Productos Vendidos', number_format($stats['total_productos'])),
            array('Clientes Atendidos', number_format($stats['total_clientes'])),
            array('Crecimiento vs Mes Anterior', $stats['crecimiento'] . '%'),
            array('Ventas Anuladas', $stats['total_anuladas'] . ' (Q' . number_format($stats['monto_anulado'], 2) . ')')
        );
        
        foreach($data as $row)
        {
            $this->SetFont('Arial','B',10);
            $this->Cell($w[0], 8, utf8_decode($row[0]), 1, 0, 'L', true);
            $this->SetFont('Arial','',10);
            $this->Cell($w[1], 8, utf8_decode($row[1]), 1, 1, 'R');
        }
        
        $this->Ln(10);
    }
    
    // Tabla de ventas por producto
    function TablaProductos($productos)
{
    $this->SetFont('Arial','B',14);
    $this->Cell(0,10, utf8_decode('Top 10 Productos Mas Vendidos'), 0, 1, 'L');
    $this->Ln(3);
    
    // Línea decorativa
    $this->SetDrawColor(70, 130, 180);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    $this->Ln(3);
    
    // Cabecera de tabla con gradiente
    $this->SetFillColor(70, 130, 180);
    $this->SetTextColor(255, 255, 255);
    $this->SetFont('Arial','B',9);
    
    $w = array(25, 70, 25, 30, 35);
    $headers = array('Codigo', 'Producto', 'Cant.', 'Precio Prom.', 'Ingresos');
    
    for($i = 0; $i < count($headers); $i++) {
        $this->Cell($w[$i], 10, utf8_decode($headers[$i]), 1, 0, 'C', true);
    }
    $this->Ln();
    
    // Restaurar color de texto
    $this->SetTextColor(0, 0, 0);
    
    // Datos con alternancia de colores mejorada
    $this->SetFont('Arial','',8);
    $contador = 0;
    
    foreach($productos as $producto) {
        if ($contador >= 10) break;
        
        // Alternar colores más suaves
        if ($contador % 2 == 0) {
            $this->SetFillColor(248, 248, 248);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        // Ranking number
        $ranking = $contador + 1;
        if ($ranking <= 3) {
            $this->SetFont('Arial','B',8);
            $this->SetTextColor(200, 0, 0); // Rojo para top 3
        } else {
            $this->SetFont('Arial','',8);
            $this->SetTextColor(0, 0, 0);
        }
        
        $this->Cell($w[0], 8, utf8_decode($producto['codigo']), 1, 0, 'C', true);
        $this->Cell($w[1], 8, utf8_decode(substr($producto['nombre'], 0, 35)), 1, 0, 'L', true);
        $this->Cell($w[2], 8, number_format($producto['cantidad_total']), 1, 0, 'R', true);
        $this->Cell($w[3], 8, 'Q' . number_format($producto['precio_promedio'], 2), 1, 0, 'R', true);
        $this->Cell($w[4], 8, 'Q' . number_format($producto['ingreso_total'], 2), 1, 1, 'R', true);
        
        $contador++;
    }
    
    // Restaurar colores
    $this->SetTextColor(0, 0, 0);
    $this->Ln(8);
}

    
    // Tabla de clientes
    function TablaClientes($clientes)
{
    $this->SetFont('Arial','B',14);
    $this->Cell(0,10, utf8_decode('Top 10 Clientes Mas Importantes'), 0, 1, 'L');
    $this->Ln(3);
    
    // Línea decorativa
    $this->SetDrawColor(34, 139, 34);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    $this->Ln(3);
    
    // Cabecera de tabla
    $this->SetFillColor(34, 139, 34);
    $this->SetTextColor(255, 255, 255);
    $this->SetFont('Arial','B',9);
    
    $w = array(55, 25, 35, 35, 30);
    $headers = array('Cliente', 'Compras', 'Valor Total', 'Ticket Prom.', 'Ult. Compra');
    
    for($i = 0; $i < count($headers); $i++) {
        $this->Cell($w[$i], 10, utf8_decode($headers[$i]), 1, 0, 'C', true);
    }
    $this->Ln();
    
    // Restaurar color de texto
    $this->SetTextColor(0, 0, 0);
    
    // Datos
    $this->SetFont('Arial','',8);
    $contador = 0;
    
    foreach($clientes as $cliente) {
        if ($contador >= 10) break;
        
        // Alternar colores
        if ($contador % 2 == 0) {
            $this->SetFillColor(248, 255, 248);
        } else {
            $this->SetFillColor(255, 255, 255);
        }
        
        // Destacar top 3
        if ($contador < 3) {
            $this->SetFont('Arial','B',8);
            $this->SetTextColor(0, 100, 0);
        } else {
            $this->SetFont('Arial','',8);
            $this->SetTextColor(0, 0, 0);
        }
        
        $this->Cell($w[0], 8, utf8_decode(substr($cliente['empresa'], 0, 28)), 1, 0, 'L', true);
        $this->Cell($w[1], 8, number_format($cliente['total_compras']), 1, 0, 'C', true);
        $this->Cell($w[2], 8, 'Q' . number_format($cliente['valor_total'], 2), 1, 0, 'R', true);
        $this->Cell($w[3], 8, 'Q' . number_format($cliente['ticket_promedio'], 2), 1, 0, 'R', true);
        $this->Cell($w[4], 8, date('d/m/Y', strtotime($cliente['ultima_compra'])), 1, 1, 'C', true);
        
        $contador++;
    }
    
    // Restaurar colores
    $this->SetTextColor(0, 0, 0);
    $this->Ln(8);
}
    
    // Tabla de ventas detalladas
    function TablaVentasDetalle($ventas)
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10, utf8_decode('Detalle de Ventas (Ultimas 20)'), 0, 1, 'L');
        $this->Ln(5);
        
        // Cabecera de tabla
        $this->SetFillColor(255,200,200);
        $this->SetFont('Arial','B',8);
        
        $w = array(20, 35, 50, 40, 30, 20);
        $headers = array('Fecha', 'Factura', 'Cliente', 'Vendedor', 'Total', 'Tipo');
        
        for($i = 0; $i < count($headers); $i++) {
            $this->Cell($w[$i], 8, utf8_decode($headers[$i]), 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Datos
        $this->SetFont('Arial','',7);
        $this->SetFillColor(255,245,245);
        $fill = false;
        
        $contador = 0;
        foreach($ventas as $venta) {
            if ($contador >= 20) break; // Solo últimas 20
            
            $this->Cell($w[0], 5, date('d/m/Y', strtotime($venta['fecha'])), 1, 0, 'C', $fill);
            $this->Cell($w[1], 5, utf8_decode($venta['codigo']), 1, 0, 'L', $fill);
            $this->Cell($w[2], 5, utf8_decode(substr($venta['cliente_nombre'], 0, 25)), 1, 0, 'L', $fill);
            $this->Cell($w[3], 5, utf8_decode(substr($venta['vendedor_nombre'], 0, 20)), 1, 0, 'L', $fill);
            $this->Cell($w[4], 5, 'Q' . number_format($venta['total'], 2), 1, 0, 'R', $fill);
            $this->Cell($w[5], 5, utf8_decode(ucfirst($venta['tipo_venta'])), 1, 1, 'C', $fill);
            
            $fill = !$fill;
            $contador++;
        }
        
        $this->Ln(10);
    }
    
    // Gráfico con rectángulos dibujados
function GraficoBarrasVisual($ventas_mensuales)
{
    // Título
    $this->SetFont('Arial','B',14);
    $this->Cell(0,10, utf8_decode('Tendencia de Ventas (Ultimos 6 meses)'), 0, 1, 'L');
    
    // Línea decorativa debajo del título
    $this->SetDrawColor(70, 130, 180);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    $this->Ln(8);
    
    if (empty($ventas_mensuales)) {
        $this->SetFont('Arial','I',10);
        $this->Cell(0,10, utf8_decode('No hay datos suficientes para mostrar tendencia'), 0, 1, 'C');
        $this->Ln(10);
        return;
    }
    
    // Encontrar el máximo para normalizar
    $max_venta = 0;
    foreach($ventas_mensuales as $venta) {
        if ($venta['total'] > $max_venta) {
            $max_venta = $venta['total'];
        }
    }
    
    // Configuración del gráfico
    $x_start = 30;
    $y_start = $this->GetY() + 35; // Posición base del gráfico (más espacio)
    $bar_width = 25;
    $max_height = 30;
    $spacing = 15;
    $total_width = count($ventas_mensuales) * ($bar_width + $spacing);
    
    // Ajustar posición para centrar
    $x_start = (210 - $total_width) / 2;
    
    // Dibujar ejes con mejor posicionamiento
    $this->SetDrawColor(100, 100, 100);
    $this->Line($x_start - 5, $y_start, $x_start - 5, $y_start - $max_height - 5); // Eje Y
    $this->Line($x_start - 5, $y_start, $x_start + $total_width - $spacing, $y_start); // Eje X
    
    // Líneas de referencia horizontales PRIMERO
    $this->SetDrawColor(230, 230, 230);
    for($j = 1; $j <= 4; $j++) {
        $y_ref = $y_start - ($max_height * $j / 4);
        $this->Line($x_start - 5, $y_ref, $x_start + $total_width - $spacing, $y_ref);
        
        // Etiquetas del eje Y
        $valor_ref = $max_venta * $j / 4;
        $this->SetXY($x_start - 25, $y_ref - 2);
        $this->SetFont('Arial','',7);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(15, 4, 'Q' . number_format($valor_ref, 0), 0, 0, 'R');
    }
    
    // Ahora dibujar las barras
    $this->SetFont('Arial','',8);
    $this->SetTextColor(0, 0, 0);
    
    $i = 0;
    foreach($ventas_mensuales as $venta) {
        $x = $x_start + ($i * ($bar_width + $spacing));
        $height = ($venta['total'] / $max_venta) * $max_height;
        
        // Gradiente de colores
        $color_r = 70;
        $color_g = 130;
        $color_b = 180 + ($i * 15);
        if ($color_b > 255) $color_b = 255;
        
        // Dibujar sombra
        $this->SetFillColor(200, 200, 200);
        $this->Rect($x + 2, $y_start - $height + 2, $bar_width, $height, 'F');
        
        // Dibujar barra principal
        $this->SetFillColor($color_r, $color_g, $color_b);
        $this->Rect($x, $y_start - $height, $bar_width, $height, 'F');
        
        // Borde de la barra
        $this->SetDrawColor(50, 80, 120);
        $this->Rect($x, $y_start - $height, $bar_width, $height);
        
        // Valor encima de la barra
        $valor = 'Q' . number_format($venta['total'], 0);
        $this->SetXY($x + ($bar_width/2) - 12, $y_start - $height - 10);
        $this->SetFont('Arial','B',8);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(24, 4, $valor, 0, 0, 'C');
        
        // Etiqueta del mes debajo
        $mes = date('M/y', strtotime($venta['mes'] . '-01'));
        $this->SetXY($x + ($bar_width/2) - 12, $y_start + 3);
        $this->SetFont('Arial','',9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(24, 4, $mes, 0, 0, 'C');
        
        $i++;
    }
    
    // Título del eje Y
    $this->SetXY(8, $y_start - ($max_height/2) - 5);
    $this->SetFont('Arial','B',9);
    $this->SetTextColor(100, 100, 100);
    $this->Cell(15, 8, 'Ventas', 0, 0, 'C');
    
    // Título del eje X
    $this->SetXY($x_start + ($total_width/2) - 15, $y_start + 15);
    $this->SetFont('Arial','B',9);
    $this->Cell(30, 4, 'Periodo', 0, 0, 'C');
    
    // Restaurar color de texto
    $this->SetTextColor(0, 0, 0);
    
    // Mover el cursor DESPUÉS del gráfico
    $this->SetY($y_start + 25);
    $this->Ln(5);
}

    // NUEVAS FUNCIONES PARA VENTAS ANULADAS
    
    // Método para tabla de estadísticas de anuladas
    function TablaEstadisticasAnuladas($stats_anuladas)
    {
        $this->SetFont('Arial','B',14);
        $this->SetTextColor(220, 53, 69); // Color rojo
        $this->Cell(0,10, utf8_decode('📊 Análisis de Ventas Anuladas'), 0, 1, 'L');
        $this->SetTextColor(0, 0, 0); // Restaurar color negro
        $this->Ln(3);
        
        // Línea decorativa roja
        $this->SetDrawColor(220, 53, 69);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
        
        if ($stats_anuladas['total_anuladas'] == 0) {
            // Mensaje positivo si no hay anulaciones
            $this->SetFillColor(212, 237, 218); // Verde claro
            $this->SetTextColor(21, 87, 36); // Verde oscuro
            $this->SetFont('Arial','B',12);
            $this->Cell(0, 15, utf8_decode('✅ Excelente: No hay ventas anuladas en el período'), 1, 1, 'C', true);
            $this->SetTextColor(0, 0, 0);
            $this->Ln(10);
            return;
        }
        
        // Tabla de estadísticas con colores
        $this->SetFillColor(248, 215, 218); // Fondo rojo claro
        
        $w = array(70, 40);
        
        $data = array(
            array('Total Ventas Anuladas', number_format($stats_anuladas['total_anuladas'])),
            array('Monto Total Perdido', 'Q' . number_format($stats_anuladas['monto_total_anulado'], 2)),
            array('Tiempo Promedio Anulación', number_format($stats_anuladas['tiempo_promedio_horas'], 1) . 'h')
        );
        
        foreach($data as $row)
        {
            $this->SetFont('Arial','B',10);
            $this->Cell($w[0], 8, utf8_decode($row[0]), 1, 0, 'L', true);
            $this->SetFont('Arial','',10);
            $this->SetTextColor(220, 53, 69); // Números en rojo
            $this->Cell($w[1], 8, utf8_decode($row[1]), 1, 1, 'R', true);
            $this->SetTextColor(0, 0, 0); // Restaurar color
        }
        
        $this->Ln(8);
    }

    // Método para tabla de motivos de anulación
    function TablaMotivosAnulacion($motivos)
    {
        if (empty($motivos)) {
            return;
        }
        
        $this->SetFont('Arial','B',12);
        $this->Cell(0,8, utf8_decode('Principales Motivos de Anulación'), 0, 1, 'L');
        $this->Ln(3);
        
        // Cabecera de tabla
        $this->SetFillColor(220, 53, 69); // Rojo
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial','B',9);
        
        $w = array(80, 30, 25);
        $headers = array('Motivo', 'Cantidad', '%');
        
        for($i = 0; $i < count($headers); $i++) {
            $this->Cell($w[$i], 8, utf8_decode($headers[$i]), 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Restaurar color de texto
        $this->SetTextColor(0, 0, 0);
        
        // Calcular total para porcentajes
        $total_motivos = array_sum(array_column($motivos, 'cantidad'));
        
        // Datos
        $this->SetFont('Arial','',8);
        $contador = 0;
        
        foreach($motivos as $motivo) {
            if ($contador >= 5) break;
            
            // Alternar colores
            if ($contador % 2 == 0) {
                $this->SetFillColor(253, 236, 237);
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            
            $porcentaje = $total_motivos > 0 ? round(($motivo['cantidad'] / $total_motivos) * 100, 1) : 0;
            
            $this->Cell($w[0], 6, utf8_decode(substr($motivo['motivo'], 0, 40)), 1, 0, 'L', true);
            $this->Cell($w[1], 6, number_format($motivo['cantidad']), 1, 0, 'C', true);
            $this->Cell($w[2], 6, $porcentaje . '%', 1, 1, 'C', true);
            
            $contador++;
        }
        
        $this->Ln(8);
    }

    // Método para tabla detallada de anulaciones
    function TablaDetalleAnuladas($anulaciones)
    {
        if (empty($anulaciones)) {
            return;
        }
        
        $this->SetFont('Arial','B',12);
        $this->Cell(0,8, utf8_decode('Detalle de Ventas Anuladas (Últimas 15)'), 0, 1, 'L');
        $this->Ln(3);
        
        // Cabecera de tabla
        $this->SetFillColor(108, 117, 125); // Gris
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial','B',7);
        
        $w = array(22, 28, 35, 35, 35, 25, 15);
        $headers = array('Fecha', 'Factura', 'Cliente', 'Motivo', 'Anulada por', 'Total', 'Tiempo');
        
        for($i = 0; $i < count($headers); $i++) {
            $this->Cell($w[$i], 8, utf8_decode($headers[$i]), 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Restaurar color de texto
        $this->SetTextColor(0, 0, 0);
        
        // Datos
        $this->SetFont('Arial','',6);
        $contador = 0;
        
        foreach($anulaciones as $anulacion) {
            if ($contador >= 15) break;
            
            // Alternar colores
            if ($contador % 2 == 0) {
                $this->SetFillColor(248, 249, 250);
            } else {
                $this->SetFillColor(255, 255, 255);
            }
            
            $fecha_anulacion = date('d/m/Y H:i', strtotime($anulacion['fecha_anulacion']));
            $tiempo = $anulacion['horas_transcurridas'] < 24 ? 
                $anulacion['horas_transcurridas'] . 'h' : 
                floor($anulacion['horas_transcurridas'] / 24) . 'd';
            
            $motivo_corto = strlen($anulacion['motivo']) > 20 ? 
                substr($anulacion['motivo'], 0, 20) . '...' : 
                $anulacion['motivo'];
            
            $this->Cell($w[0], 5, $fecha_anulacion, 1, 0, 'C', true);
            $this->SetTextColor(220, 53, 69); // Rojo para factura
            $this->Cell($w[1], 5, utf8_decode($anulacion['numero_factura']), 1, 0, 'C', true);
            $this->SetTextColor(0, 0, 0);
            $this->Cell($w[2], 5, utf8_decode(substr($anulacion['cliente_nombre'], 0, 18)), 1, 0, 'L', true);
            $this->Cell($w[3], 5, utf8_decode($motivo_corto), 1, 0, 'L', true);
            $this->Cell($w[4], 5, utf8_decode(substr($anulacion['usuario_anulo_nombre'], 0, 18)), 1, 0, 'L', true);
            $this->SetTextColor(220, 53, 69); // Rojo para total
            $this->Cell($w[5], 5, 'Q' . number_format($anulacion['total_venta'], 2), 1, 0, 'R', true);
            $this->SetTextColor(0, 0, 0);
            $this->Cell($w[6], 5, $tiempo, 1, 1, 'C', true);
            
            $contador++;
        }
        
        $this->Ln(5);
    }
}

// Obtener filtros
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

// Obtener datos para el PDF
$filtros = obtenerFiltros();
$whereClause = construirWhereClause($filtros);

// Estadísticas
$query = "
    SELECT 
        COALESCE(SUM(total), 0) as total_ventas,
        COUNT(*) as total_ventas_count,
        COUNT(DISTINCT cliente_id) as total_clientes
    FROM ventas v 
    WHERE v.estado = 1 $whereClause
";
$result = $conexion->query($query);
$stats = $result->fetch_assoc();

$query = "
    SELECT COALESCE(SUM(vd.cantidad), 0) as total_productos
    FROM venta_detalle vd
    INNER JOIN ventas v ON vd.venta_id = v.id
    WHERE v.estado = 1 $whereClause
";
$result = $conexion->query($query);
$productos_stats = $result->fetch_assoc();

$stats['total_productos'] = $productos_stats['total_productos'];
$stats['crecimiento'] = 0; // Simplificado

// Productos
$query = "
    SELECT 
        p.codigo,
        p.nombre,
        SUM(vd.cantidad) as cantidad_total,
        AVG(vd.precio_unitario) as precio_promedio,
        SUM(vd.subtotal) as ingreso_total
    FROM venta_detalle vd
    INNER JOIN productos p ON vd.producto_id = p.id
    INNER JOIN ventas v ON vd.venta_id = v.id
    WHERE v.estado = 1 $whereClause
    GROUP BY p.id, p.codigo, p.nombre
    ORDER BY ingreso_total DESC
    LIMIT 10
";
$result = $conexion->query($query);
$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

// Clientes
$query = "
    SELECT 
        c.empresa,
        COUNT(v.id) as total_compras,
        SUM(v.total) as valor_total,
        AVG(v.total) as ticket_promedio,
        MAX(v.fecha) as ultima_compra
    FROM clientes c
    INNER JOIN ventas v ON c.id = v.cliente_id
    WHERE v.estado = 1 $whereClause
    GROUP BY c.id, c.empresa
    ORDER BY valor_total DESC
    LIMIT 10
";
$result = $conexion->query($query);
$clientes = [];
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

// Ventas detalle
$query = "
    SELECT 
        v.fecha,
        v.codigo,
        v.total,
        v.tipo_venta,
        c.empresa as cliente_nombre,
        u.nombre_completo as vendedor_nombre
    FROM ventas v
    INNER JOIN clientes c ON v.cliente_id = c.id
    INNER JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.estado = 1 $whereClause
    ORDER BY v.fecha DESC
    LIMIT 20
";
$result = $conexion->query($query);
$ventas = [];
while ($row = $result->fetch_assoc()) {
    $ventas[] = $row;
}

// Ventas mensuales
$query = "
    SELECT 
        DATE_FORMAT(v.fecha, '%Y-%m') as mes,
        SUM(v.total) as total
    FROM ventas v
    WHERE v.estado = 1 AND v.fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(v.fecha, '%Y-%m')
    ORDER BY mes
";
$result = $conexion->query($query);
$ventas_mensuales = [];
while ($row = $result->fetch_assoc()) {
    $ventas_mensuales[] = $row;
}

// NUEVAS CONSULTAS PARA DATOS DE ANULACIONES
$whereClauseAnuladas = construirWhereClauseAnuladas($filtros);

// Estadísticas de anulaciones
$query = "
    SELECT 
        COUNT(*) as total_anuladas,
        COALESCE(SUM(total_venta), 0) as monto_total_anulado,
        COALESCE(AVG(TIMESTAMPDIFF(HOUR, fecha_registro, fecha_anulacion)), 0) as tiempo_promedio_horas
    FROM ventas_anulaciones va
    WHERE 1=1 $whereClauseAnuladas
";
$result = $conexion->query($query);
$stats_anuladas = $result->fetch_assoc();

// Agregar al array de stats principales
$stats['total_anuladas'] = $stats_anuladas['total_anuladas'];
$stats['monto_anulado'] = $stats_anuladas['monto_total_anulado'];

// Motivos de anulación
$motivos_anulacion = [];
if ($stats_anuladas['total_anuladas'] > 0) {
    $query = "
        SELECT 
            COALESCE(NULLIF(TRIM(motivo), ''), 'Sin motivo especificado') as motivo,
            COUNT(*) as cantidad
        FROM ventas_anulaciones va
        WHERE 1=1 $whereClauseAnuladas
        GROUP BY motivo
        ORDER BY cantidad DESC
        LIMIT 5
    ";
    $result = $conexion->query($query);
    while ($row = $result->fetch_assoc()) {
        $motivos_anulacion[] = $row;
    }
}

// Detalle de anulaciones
$detalle_anulaciones = [];
if ($stats_anuladas['total_anuladas'] > 0) {
    $query = "
        SELECT 
            va.numero_factura,
            va.fecha_anulacion,
            va.cliente_nombre,
            va.motivo,
            va.usuario_anulo_nombre,
            va.total_venta,
            TIMESTAMPDIFF(HOUR, va.fecha_registro, va.fecha_anulacion) as horas_transcurridas
        FROM ventas_anulaciones va
        WHERE 1=1 $whereClauseAnuladas
        ORDER BY va.fecha_anulacion DESC
        LIMIT 15
    ";
    $result = $conexion->query($query);
    while ($row = $result->fetch_assoc()) {
        $detalle_anulaciones[] = $row;
    }
}

// Crear PDF
$periodo = date('d/m/Y', strtotime($filtros['fecha_inicio'])) . ' al ' . date('d/m/Y', strtotime($filtros['fecha_fin']));
$pdf = new InformeVentasPDF('Informe de Ventas', $periodo);

$pdf->AliasNbPages();
$pdf->AddPage();

// PRIMERA PÁGINA: Resumen ejecutivo y productos
$pdf->TablaEstadisticas($stats);
$pdf->TablaProductos($productos);

// SEGUNDA PÁGINA: Clientes y gráfico de tendencias
$pdf->AddPage();
$pdf->TablaClientes($clientes);

// Asegurar que hay espacio suficiente para el gráfico
$pdf->Ln(5);
$pdf->GraficoBarrasVisual($ventas_mensuales);

// TERCERA PÁGINA: Análisis de ventas anuladas
$pdf->AddPage();
$pdf->TablaEstadisticasAnuladas($stats_anuladas);
$pdf->TablaMotivosAnulacion($motivos_anulacion);
$pdf->TablaDetalleAnuladas($detalle_anulaciones);

// CUARTA PÁGINA: Detalle de ventas normales
$pdf->AddPage();
$pdf->TablaVentasDetalle($ventas);

// Salida del PDF
$pdf->Output('D', 'Informe_Ventas_' . date('Y-m-d') . '.pdf');
?>