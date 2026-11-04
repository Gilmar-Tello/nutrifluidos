<?php
date_default_timezone_set('America/Guatemala');
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
require_once '../fpdf/fpdf.php';


class ReporteStockBajoPDF extends FPDF {
    function Header() {
    // Solo mostrar encabezado en la primera página
    if ($this->PageNo() == 1) {
        // Logo más grande a la izquierda
        $this->Image('../imagenes/nutrifluidos.jpg', 10, 8, 50); // x=10, y=8, ancho=50
        
        // Datos de la empresa centrados a la derecha del logo
        $this->SetXY(65, 10); // Posicionarse después del logo
        
        // Título de la empresa - REDUCIDO de 14 a 12
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 4, 'NUTRI-FLUIDOS', 0, 1, 'C'); // Reducido de 5 a 4
        
        // Dirección - REDUCIDO de 9 a 8
        $this->SetXY(65, 15); // Reducido de 17 a 15
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, 'CALLEJON VILLA FLORIDALMA', 0, 1, 'C'); // Reducido de 4 a 3
        
        $this->SetXY(65, 19); // Reducido de 22 a 19
        $this->Cell(0, 3, 'TIQUISATE, ESCUINTLA, GUATEMALA', 0, 1, 'C'); // Reducido de 4 a 3
        
        // Email
        $this->SetXY(65, 23); // Reducido de 27 a 23
        $this->Cell(0, 3, 'NUTRIFLUIDOS@GMAIL.COM', 0, 1, 'C'); // Reducido de 4 a 3
        
        // Nombre del responsable
        $this->SetXY(65, 27); // Reducido de 32 a 27
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C'); // Reducido de 4 a 3
        
        // NIT
        $this->SetXY(65, 31); // Reducido de 37 a 31
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 3, 'NIT: 95675876', 0, 1, 'C'); // Reducido de 4 a 3
        
        // Línea separadora - REDUCIDO el espacio
        $this->Ln(4); // Reducido de 8 a 4
        $this->Line(10, 38, 200, 38); // Línea horizontal más arriba (de 50 a 38)
        
        // Título del reporte
        $this->Ln(2); // Reducido de 3 a 2
        $this->SetFont('Arial', 'B', 11); // Reducido de 12 a 11
        $this->Cell(0, 5, 'REPORTE DE PRODUCTOS CON STOCK BAJO', 0, 1, 'C'); // Reducido de 6 a 5
        
        // Fecha del reporte
        $this->SetFont('Arial', '', 9); // Reducido de 10 a 9
        $this->Cell(0, 4, 'Fecha de reporte: ' . date('d/m/Y'), 0, 1, 'C'); // Reducido de 5 a 4
        $this->Ln(3); // Reducido de 5 a 3
    } else {
        // En páginas adicionales, solo un pequeño espacio en la parte superior
        $this->Ln(10);
        }
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }

    function proveedorHeader($nombre, $direccion = 'Sin direccion') {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(30, 7, 'Proveedor:', 0, 0);
        $this->SetFont('Arial', '', 11);
        $this->Cell(80, 7, $this->quitarTildes($nombre), 0, 1);
        
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(30, 7, 'Direccion:', 0, 0);
        $this->SetFont('Arial', '', 11);
        $this->Cell(80, 7, $this->quitarTildes($direccion), 0, 1);
        $this->Ln(3);
    }

    function tablaProductos($productos) {
        $this->SetFont('Arial', 'B', 10);
        // Cabecera de la tabla con colores
        $this->SetFillColor(200, 220, 255); // Azul claro
        $this->Cell(35, 10, 'Codigo', 1, 0, 'C', true);
        $this->Cell(75, 10, 'Producto', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Stock', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Stock Min.', 1, 0, 'C', true);
        $this->Cell(25, 10, 'Faltante', 1, 1, 'C', true);
        
        $this->SetFont('Arial', '', 9);
        $this->SetFillColor(255, 255, 255); // Blanco para las filas
        
        foreach ($productos as $prod) {
            $faltante = $prod['stock_minimo'] - $prod['stock'];
            
            // Alternar colores de las filas
            $fill = ($this->GetY() / 10) % 2 == 0;
            if ($fill) {
                $this->SetFillColor(248, 248, 248); // Gris muy claro
            } else {
                $this->SetFillColor(255, 255, 255); // Blanco
            }
            
            $this->Cell(35, 8, $this->quitarTildes($prod['codigo']), 1, 0, 'L', true);
            $this->Cell(75, 8, $this->quitarTildes($prod['nombre']), 1, 0, 'L', true);
            $this->Cell(25, 8, $prod['stock'], 1, 0, 'C', true);
            $this->Cell(25, 8, $prod['stock_minimo'], 1, 0, 'C', true);
            $this->Cell(25, 8, $faltante, 1, 1, 'C', true);
        }
        $this->Ln(8);
    }
    
    // Función para quitar tildes y caracteres especiales
    function quitarTildes($texto) {
        $tildes = array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N',
            'ü' => 'u', 'Ü' => 'U',
            '°' => '', '´' => '', '`' => '', '^' => '', '~' => ''
        );
        
        return strtr($texto, $tildes);
    }
}

// Crear PDF
$pdf = new ReporteStockBajoPDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Consulta optimizada (similar al enfoque del informe funcional)
$sql_proveedores = "
    SELECT pr.id, pr.empresa, IFNULL(pr.direccion, 'Sin direccion') AS direccion
    FROM productos p
    INNER JOIN proveedores pr ON pr.id = p.proveedor_id AND pr.estado = 1
    WHERE p.estado = 1 AND p.stock < p.stock_minimo
    GROUP BY pr.id
";
$res_prov = $conexion->query($sql_proveedores);

if (!$res_prov) {
    die("Error en la consulta: " . $conexion->error);
}

if ($res_prov->num_rows === 0) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'No hay productos con stock bajo actualmente.', 0, 1, 'C');
} else {
    while ($prov = $res_prov->fetch_assoc()) {
        $idProv = $prov['id'];
        $sql_productos = "
            SELECT codigo, nombre, stock, stock_minimo
            FROM productos
            WHERE proveedor_id = $idProv AND estado = 1 AND stock < stock_minimo
            ORDER BY nombre
        ";
        $res_prods = $conexion->query($sql_productos);
        
        if (!$res_prods) {
            die("Error en la consulta: " . $conexion->error);
        }
        
        $productos = [];
        while ($row = $res_prods->fetch_assoc()) {
            $productos[] = $row;
        }
        
        if (!empty($productos)) {
            $pdf->proveedorHeader($prov['empresa'], $prov['direccion']);
            $pdf->tablaProductos($productos);
        }
    }
}

$pdf->Output('I', 'reporte_stock_bajo_' . date('Y-m-d') . '.pdf');
?>