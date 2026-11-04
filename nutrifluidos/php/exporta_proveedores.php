<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require('../fpdf/fpdf.php');
require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala');
$fecha = date('d/m/Y');

// Clase personalizada que extiende FPDF
class PDF extends FPDF
{
    function Header()
    {
        // Solo en la primera página
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
            $this->Cell(0, 5, 'LISTADO DE PROVEEDORES', 0, 1, 'C');
            $this->SetFont('Arial', '', 9);
            $this->Cell(0, 4, 'Fecha de reporte: ' . date('d/m/Y'), 0, 1, 'C');
            $this->Ln(3);
        } else {
            $this->Ln(10);
        }
        
        // Encabezado de tabla (en todas las páginas)
        $this->SetFillColor(180, 200, 255);
        $this->SetFont('Arial', 'B', 8);
        $this->Cell(35, 8, 'EMPRESA', 1, 0, 'C', true);
        $this->Cell(28, 8, 'CONTACTO 1', 1, 0, 'C', true);
        $this->Cell(20, 8, 'TELEFONO 1', 1, 0, 'C', true);
        $this->Cell(28, 8, 'CONTACTO 2', 1, 0, 'C', true);
        $this->Cell(20, 8, 'TELEFONO 2', 1, 0, 'C', true);
        $this->Cell(40, 8, 'EMAIL', 1, 0, 'C', true);
        $this->Cell(70, 8, 'DIRECCION', 1, 0, 'C', true);
        $this->Cell(25, 8, 'PAIS', 1, 1, 'C', true);
    }
    
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'PAGINA ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Instanciar PDF y configuración
$pdf = new PDF();
$pdf->AddPage('L'); // 'L' para formato horizontal (Landscape)
$pdf->SetFont('Arial', '', 7);

// Consulta ordenada alfabéticamente por empresa
$query = "SELECT empresa, contacto, telefono, contacto2, telefono2, email, direccion, pais_origen
          FROM proveedores
          WHERE estado = 1
          ORDER BY empresa ASC";

$resultado = mysqli_query($conexion, $query);

if ($resultado && mysqli_num_rows($resultado) > 0) {
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Calcular altura necesaria basada en dirección y email
        $direccion = utf8_decode($fila['direccion'] ?: '-');
        $email = utf8_decode($fila['email'] ?: '-');
        
        // Determinar altura de fila necesaria
        $altura_fila = 9; // altura mínima aumentada
        if ($pdf->GetStringWidth($direccion) > 70) $altura_fila = 14;
        if ($pdf->GetStringWidth($email) > 40) $altura_fila = max($altura_fila, 14);
        
        // Guardar posición Y inicial
        $y_inicial = $pdf->GetY();
        
        // Dibujar las celdas con la altura calculada
        $pdf->Cell(35, $altura_fila, utf8_decode($fila['empresa']), 1, 0, 'L');
        $pdf->Cell(28, $altura_fila, utf8_decode($fila['contacto'] ?: '-'), 1, 0, 'L');
        $pdf->Cell(20, $altura_fila, utf8_decode($fila['telefono'] ?: '-'), 1, 0, 'C');
        $pdf->Cell(28, $altura_fila, utf8_decode($fila['contacto2'] ?: '-'), 1, 0, 'L');
        $pdf->Cell(20, $altura_fila, utf8_decode($fila['telefono2'] ?: '-'), 1, 0, 'C');
        
        // Posición para email
        $x_email = $pdf->GetX();
        $pdf->Rect($x_email, $y_inicial, 40, $altura_fila);
        $pdf->SetXY($x_email + 1, $y_inicial + 1);
        $pdf->MultiCell(43, 3, $email, 0, 'L');
        
        // Posición para dirección
        $x_direccion = $x_email + 40;
        $pdf->Rect($x_direccion, $y_inicial, 70, $altura_fila);
        $pdf->SetXY($x_direccion + 1, $y_inicial + 1);
        $pdf->MultiCell(83, 3, $direccion, 0, 'L');
        
        // Posicionar para la última columna (País)
        $pdf->SetXY($x_direccion + 70, $y_inicial);
        $pdf->Cell(25, $altura_fila, utf8_decode($fila['pais_origen'] ?: '-'), 1, 1, 'L');
    }
} else {
    $pdf->Cell(0, 10, 'No hay proveedores registrados.', 1, 1, 'C');
}

$pdf->Output('I', 'listado_proveedores.pdf');
?>