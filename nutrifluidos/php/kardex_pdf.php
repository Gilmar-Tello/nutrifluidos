<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
require_once '../fpdf/fpdf.php'; // Ajusta la ruta segun donde tengas FPDF

class ReporteKardex extends FPDF
{
    private $modo;
    private $producto;

    public function setContexto($modo, $producto) {
        $this->modo = $modo;
        $this->producto = $producto;
    }

    function Header()
    {
        if ($this->PageNo() == 1) {
            if (file_exists('../imagenes/nutrifluidos.jpg')) {
                $this->Image('../imagenes/nutrifluidos.jpg', 10, 8, 50);
            }

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
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 3, 'JACOBO AMILCAR MONTERROSO CHAVEZ', 0, 1, 'C');

            $this->SetXY(65, 31);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 3, 'NIT: 95675876', 0, 1, 'C');

            $this->Ln(4);
            $this->Line(10, 38, 290, 38);

            $this->Ln(2);
            $this->SetFont('Arial', 'B', 11);
            if ($this->modo === 'producto' && $this->producto) {
                $this->Cell(0, 5, 'KARDEX DE PRODUCTO: ' . mb_strtoupper($this->producto['nombre'], 'UTF-8'), 0, 1, 'C');
                $this->SetFont('Arial', '', 8);
                $this->Cell(0, 4, 'Codigo: ' . $this->producto['codigo'] . '   |   Stock actual: ' . $this->producto['stock'] . '   |   Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
            } else {
                $this->Cell(0, 5, 'KARDEX DE INVENTARIO - TODOS LOS PRODUCTOS', 0, 1, 'C');
                $this->SetFont('Arial', '', 8);
                $this->Cell(0, 4, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
            }
            $this->Ln(2);

            $this->pintarEncabezadoTabla();
        } else {
            $this->Ln(5);
            $this->pintarEncabezadoTabla();
        }
    }

    private function pintarEncabezadoTabla()
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(230, 230, 230);

        $this->Cell(28, 8, 'Fecha', 1, 0, 'C', true);
        if ($this->modo !== 'producto') {
            $this->Cell(65, 8, 'Producto', 1, 0, 'C', true);
        }
        $this->Cell(22, 8, 'Operacion', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Cantidad', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Precio Unit.', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Costo Total', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Stock Result.', 1, 0, 'C', true);
        $anchoUsuario = $this->modo !== 'producto' ? 60 : 90;
        $this->Cell($anchoUsuario, 8, 'Usuario', 1, 1, 'C', true);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . ' de {nb}', 0, 0, 'C');
        $this->Cell(0, 10, 'Sistema NUTRI-FLUIDOS - ' . date('d/m/Y'), 0, 0, 'R');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = leerJsonAutenticado();

    if (!isset($input['accion']) || $input['accion'] !== 'exportarKardexPDF') {
        http_response_code(400);
        echo json_encode(['error' => 'Accion no valida']);
        exit;
    }

    try {
        $modo = $input['modo'] === 'producto' ? 'producto' : 'global';
        $producto = $input['producto'] ?? null;
        $stats = $input['stats'] ?? [];
        $movimientos = $input['movimientos'] ?? [];

        $pdf = new ReporteKardex('L'); // Horizontal
        $pdf->setContexto($modo, $producto);
        $pdf->AliasNbPages();
        $pdf->AddPage();

        $pdf->SetFont('Arial', '', 8);
        $contadorFilas = 0;
        $totalEntradas = 0;
        $totalSalidas = 0;
        $totalCostoComprado = 0;

        foreach ($movimientos as $mov) {
            $contadorFilas++;

            $fecha = !empty($mov['fecha']) ? date('d/m/Y H:i', strtotime($mov['fecha'])) : '-';
            $operacion = $mov['operacion'] === 'agregar' ? 'Entrada' : 'Salida';
            $cantidad = (int)($mov['cantidad'] ?? 0);
            $precioUnit = isset($mov['precio_compra_unitario']) && $mov['precio_compra_unitario'] !== null
                ? 'Q' . number_format((float)$mov['precio_compra_unitario'], 2) : '-';
            $costoTotal = isset($mov['costo_total']) && $mov['costo_total'] !== null
                ? 'Q' . number_format((float)$mov['costo_total'], 2) : '-';
            $stockResultante = (int)($mov['stock_nuevo'] ?? 0);
            $usuario = $mov['usuario_responsable'] ?? '-';
            $nombreProducto = $mov['producto_nombre'] ?? '-';
            if (strlen($nombreProducto) > 45) {
                $nombreProducto = substr($nombreProducto, 0, 42) . '...';
            }

            if ($mov['operacion'] === 'agregar') {
                $totalEntradas += $cantidad;
                $totalCostoComprado += (float)($mov['costo_total'] ?? 0);
            } else {
                $totalSalidas += $cantidad;
            }

            $fill = ($contadorFilas % 2 == 0);
            if ($fill) {
                $pdf->SetFillColor(248, 248, 248);
            }

            $pdf->Cell(28, 6, $fecha, 1, 0, 'C', $fill);
            if ($modo !== 'producto') {
                $pdf->Cell(65, 6, $nombreProducto, 1, 0, 'L', $fill);
            }
            $pdf->Cell(22, 6, $operacion, 1, 0, 'C', $fill);
            $pdf->Cell(20, 6, $cantidad, 1, 0, 'C', $fill);
            $pdf->Cell(30, 6, $precioUnit, 1, 0, 'R', $fill);
            $pdf->Cell(30, 6, $costoTotal, 1, 0, 'R', $fill);
            $pdf->Cell(25, 6, $stockResultante, 1, 0, 'C', $fill);
            $anchoUsuario = $modo !== 'producto' ? 60 : 90;
            $pdf->Cell($anchoUsuario, 6, $usuario, 1, 1, 'L', $fill);
        }

        // Resumen final
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 5, 'RESUMEN:', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(0, 5, '- Total de movimientos: ' . count($movimientos), 0, 1, 'L');
        $pdf->Cell(0, 5, '- Unidades de entrada: ' . $totalEntradas, 0, 1, 'L');
        $pdf->Cell(0, 5, '- Unidades de salida: ' . $totalSalidas, 0, 1, 'L');
        $pdf->Cell(0, 5, '- Total comprado (entradas con precio): Q' . number_format($totalCostoComprado, 2), 0, 1, 'L');

        if ($modo === 'producto' && !empty($stats)) {
            $pdf->Ln(2);
            $pdf->Cell(0, 5, '- Precio actual: Q' . number_format((float)($stats['precio_actual'] ?? 0), 2), 0, 1, 'L');
            $pdf->Cell(0, 5, '- Precio promedio historico: Q' . number_format((float)($stats['precio_promedio'] ?? 0), 2), 0, 1, 'L');
            $pdf->Cell(0, 5, '- Precio minimo registrado: Q' . number_format((float)($stats['precio_minimo'] ?? 0), 2), 0, 1, 'L');
            $pdf->Cell(0, 5, '- Precio maximo registrado: Q' . number_format((float)($stats['precio_maximo'] ?? 0), 2), 0, 1, 'L');
        }

        $fechaActual = date('Y-m-d_H-i-s');
        if ($modo === 'producto' && $producto) {
            $nombreArchivo = 'kardex_' . preg_replace('/[^A-Za-z0-9_]+/', '_', $producto['nombre']) . '_' . $fechaActual . '.pdf';
        } else {
            $nombreArchivo = 'kardex_global_' . $fechaActual . '.pdf';
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $pdf->Output('D', $nombreArchivo);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al generar PDF: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo no permitido']);
}
