<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require('../fpdf/fpdf.php');
require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala');

// Configuración extendida para manejar muchos productos
ini_set('memory_limit', '512M');           // 512MB de memoria
ini_set('max_execution_time', 600);        // 10 minutos (600 segundos)
ini_set('max_input_time', 600);            // 10 minutos de tiempo de entrada
set_time_limit(600);                       // Forzar 10 minutos

// Clase personalizada que extiende FPDF
class PDF extends FPDF
{
    function Header()
    {
        if ($this->PageNo() == 1) {
            $this->Image('../imagenes/nutrifluidos.jpg', 10, 8, 50);
            $this->SetXY(65, 10);
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 4, 'NUTRI-FLUIDOS', 0, 1, 'C');
            $this->SetXY(65, 15);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 3, 'CALLEJON VILLA FLORIDALMA', 0, 1, 'C');
            $this->SetXY(65, 19);
            $this->Cell(0, 3, 'TIQUISATE, ESCUINTLA, GUATEMALA', 0, 1, 'C');
            $this->SetXY(65, 23);
            $this->Cell(0, 3, 'NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'C');
            $this->SetXY(65, 27);
            $this->Cell(0, 3, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C');
            $this->SetXY(65, 31);
            $this->Cell(0, 3, 'NIT: 95675876', 0, 1, 'C');
            $this->Ln(4);
            $this->Line(10, 38, 287, 38);
            $this->Ln(2);
            $this->SetFont('Arial', 'B', 11);
            $this->Cell(0, 5, 'REPORTE DE PRODUCTOS EN EXISTENCIA', 0, 1, 'C');
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 4, 'Fecha de reporte: ' . date('d/m/Y'), 0, 1, 'C');
            $this->Ln(3);
        } else {
            $this->Ln(10);
        }
        
        $this->SetFillColor(180, 200, 255);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(35, 8, 'Codigo', 1, 0, 'C', true);
        // IMAGEN DESHABILITADA - Columna de imagen removida del encabezado
        // $this->Cell(20, 8, 'Imagen', 1, 0, 'C', true);
        $this->Cell(100, 8, 'Producto', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Stock', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Precio Compra', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Subtotal', 1, 0, 'C', true);
        $this->Cell(57, 8, 'Proveedor', 1, 1, 'C', true);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
    
    // Función que evita la fragmentación
    function agregarFilaProducto($codigo, $imagen_path, $producto, $stock, $precio, $subtotal, $proveedor)
    {
        // IMAGEN DESHABILITADA - Altura reducida ya que no se muestra imagen
        // $altura_fila = 20;
        $altura_fila = 8;
        
        // VERIFICACIÓN ESTRICTA: Si no hay espacio suficiente, nueva página
        if (($this->GetY() + $altura_fila) > ($this->PageBreakTrigger)) {
            $this->AddPage();
        }
        
        // Guardar posición Y inicial de la fila
        $y_inicial = $this->GetY();
        
        // 1. CÓDIGO
        $this->Cell(35, $altura_fila, $codigo, 1, 0, 'C');
        
        // 2. IMAGEN DESHABILITADA - Columna de imagen comentada
        // Si en el futuro se desea habilitar, descomentar este bloque completo
        /*
        $x_imagen = $this->GetX();
        $this->Cell(20, $altura_fila, '', 1, 0, 'C');
        
        // INSERTAR IMAGEN DENTRO DE LA CELDA CON LÍMITES ESTRICTOS
        if (!empty($imagen_path) && file_exists($imagen_path)) {
            try {
                // Imagen PEQUEÑA que definitivamente cabe: 12x12mm con margen de 4mm
                $this->Image($imagen_path, $x_imagen + 4, $y_inicial + 4, 12, 12);
            } catch (Exception $e) {
                // Si hay error, solo texto centrado
                $this->SetXY($x_imagen + 2, $y_inicial + 8);
                $this->SetFont('Arial', '', 7);
                $this->Cell(16, 4, 'Error', 0, 0, 'C');
                $this->SetFont('Arial', '', 8);
            }
        } else {
            // Sin imagen - texto centrado
            $this->SetXY($x_imagen + 2, $y_inicial + 8);
            $this->SetFont('Arial', '', 7);
            $this->Cell(16, 4, 'Sin img', 0, 0, 'C');
            $this->SetFont('Arial', '', 8);
        }
        
        // REPOSICIONAR para continuar la fila (MUY IMPORTANTE)
        $this->SetXY($x_imagen + 20, $y_inicial);
        */
        
        // 3. PRODUCTO - Ancho aumentado de 80 a 100 para aprovechar el espacio de la imagen
        $producto_corto = strlen($producto) > 60 ? substr($producto, 0, 57) . '...' : $producto;
        $this->Cell(100, $altura_fila, $producto_corto, 1, 0, 'L');
        
        // 4. STOCK
        $this->Cell(25, $altura_fila, number_format($stock, 0), 1, 0, 'C');
        
        // 5. PRECIO
        $this->Cell(30, $altura_fila, 'Q. ' . number_format($precio, 2), 1, 0, 'R');
        
        // 6. SUBTOTAL
        $this->Cell(30, $altura_fila, 'Q. ' . number_format($subtotal, 2), 1, 0, 'R');
        
        // 7. PROVEEDOR
        $proveedor_corto = strlen($proveedor) > 25 ? substr($proveedor, 0, 22) . '...' : $proveedor;
        $this->Cell(57, $altura_fila, $proveedor_corto, 1, 1, 'L');
        
        // ASEGURAR que la siguiente fila empiece en la posición correcta
        $this->SetY($y_inicial + $altura_fila);
    }
}

// Instanciar PDF
$pdf = new PDF('L', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 25); // Margen inferior más amplio
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

// Consulta SIN LÍMITE - TODOS LOS PRODUCTOS
$query = "SELECT p.codigo, p.nombre AS producto, p.stock, p.precio_compra, p.imagen, pr.empresa AS proveedor
          FROM productos p
          LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
          WHERE p.estado = 1
          ORDER BY p.nombre ASC";

$resultado = mysqli_query($conexion, $query);
$total_general = 0;
$total_stock = 0;
$contador_productos = 0;

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $stock = $fila['stock'];
        $precio_compra = $fila['precio_compra'];
        $subtotal = $stock * $precio_compra;
        
        $total_general += $subtotal;
        $total_stock += $stock;
        $contador_productos++;
        
        // IMAGEN DESHABILITADA - Ruta de imagen comentada
        // $imagen_path = !empty($fila['imagen']) ? '../imagenes/productos/' . $fila['imagen'] : '';
        $imagen_path = ''; // Sin imagen por ahora
        
        // Agregar fila SIN fragmentación
        $pdf->agregarFilaProducto(
            $fila['codigo'],
            $imagen_path,
            $fila['producto'],
            $stock,
            $precio_compra,
            $subtotal,
            $fila['proveedor'] ?? ''
        );
    }
    
    // TOTALES
    $pdf->Ln(3);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(220, 220, 220);
    
    $pdf->Cell(35, 10, 'TOTALES:', 1, 0, 'C', true);
    // IMAGEN DESHABILITADA - Celda de imagen removida de totales
    // $pdf->Cell(20, 10, '', 1, 0, 'C', true);
    $pdf->Cell(100, 10, '', 1, 0, 'C', true);
    $pdf->Cell(25, 10, number_format($total_stock, 0), 1, 0, 'C', true);
    $pdf->Cell(30, 10, '', 1, 0, 'C', true);
    $pdf->Cell(30, 10, 'Q. ' . number_format($total_general, 2), 1, 0, 'R', true);
    $pdf->Cell(57, 10, '', 1, 1, 'C', true);
    
} else {
    $pdf->Cell(0, 10, 'No hay productos registrados.', 1, 1, 'C');
    $contador_productos = 0;
}

$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, 'Total de productos en inventario: ' . $contador_productos, 0, 1, 'L');
$pdf->Cell(0, 5, 'Valor total del inventario: Q. ' . number_format($total_general, 2), 0, 1, 'L');

$pdf->Output('I', 'reporte_productos_existencia_completo.pdf');
?>