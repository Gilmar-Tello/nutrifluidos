<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala');

header('Content-Type: application/json; charset=utf-8');

function sanitizar($dato) {
    global $conexion;
    // SEGURIDAD (16-09-2026): quita < y > para que no se pueda guardar <script> ni otras
    // etiquetas (mitiga XSS almacenado en nombres de cliente, producto, etc.).
    $dato = str_replace(array('<', '>'), '', trim((string)$dato));
    return $conexion->real_escape_string($dato);
}

$accion = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = isset($_GET['accion']) ? sanitizar($_GET['accion']) : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = isset($_POST['accion']) ? sanitizar($_POST['accion']) : '';
}

switch ($accion) {
    case 'listar':
        listarProductos();
        break;
    case 'crear':
        crearProducto();
        break;
    case 'obtener':
        obtenerProducto();
        break;
    case 'actualizarPrecios':
        actualizarPreciosProducto();
        break;
    case 'actualizarProducto':
        actualizarProducto();
        break;
    case 'actualizar_stock':
        actualizarStock();
        break;
    case 'eliminar';
        eliminarProducto();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

// 🟩 Crear Producto
function crearProducto() {
    global $conexion;
    
    $nombre = stripslashes(sanitizar($_POST['nombre'] ?? ''));     // AGREGADO stripslashes
    $codigo = stripslashes(sanitizar($_POST['codigo'] ?? ''));     // AGREGADO stripslashes
    $precio_compra = isset($_POST['precio_compra']) && $_POST['precio_compra'] !== '' 
    ? floatval($_POST['precio_compra']) 
    : 0;

$precio_venta_sugerido = isset($_POST['precio_venta_sugerido']) && $_POST['precio_venta_sugerido'] !== '' 
    ? floatval($_POST['precio_venta_sugerido']) 
    : 0;

$precio_venta_real = isset($_POST['precio_venta_real']) && $_POST['precio_venta_real'] !== '' 
    ? floatval($_POST['precio_venta_real']) 
    : 0;
    $stock = intval($_POST['stock'] ?? 0);
    $stock_minimo = intval($_POST['stockminimo'] ?? 0);
    $proveedor_id = intval($_POST['proveedor_id'] ?? 0);
    $estado = 1;
    
    // 📂 Procesar imagen usando el CÓDIGO del producto
$nombreImagen = '';
if (isset($_FILES['fotoProducto']) && $_FILES['fotoProducto']['error'] === UPLOAD_ERR_OK) {
    // SEGURIDAD (16-09-2026): solo imágenes reales; la extensión la decide el servidor
    require_once __DIR__ . '/subidas.php';
    $codigoLimpio = preg_replace('/[^A-Za-z0-9]/', '_', $codigo);
    try {
        $nombreImagen = guardarArchivoSubido($_FILES['fotoProducto'], '../imagenes/productos/', ['imagen'], strtoupper($codigoLimpio));
    } catch (Exception $e) {
        echo json_encode(['error' => 'Imagen no válida: ' . $e->getMessage()]);
        return;
    }
}
    
    // 📝 Insertar producto en la base de datos
    $sql = "INSERT INTO productos 
    (codigo, imagen, nombre, precio_compra, precio_venta_sugerido, precio_venta_real, stock, stock_minimo, proveedor_id, estado, fecha_registro) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    // 🟢 Asignar nombre de imagen a la variable usada en bind_param
    $imagen = $nombreImagen;
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssddddiii", 
    $codigo, 
    $imagen, 
    $nombre, 
    $precio_compra, 
    $precio_venta_sugerido, 
    $precio_venta_real, 
    $stock, 
    $stock_minimo,
    $proveedor_id, 
    $estado
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Producto agregado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al crear producto: ' . $stmt->error]);
    }
    $stmt->close();
}

function listarProductos() {
    global $conexion;

    $sql = "SELECT 
            productos.id, productos.codigo, productos.imagen, productos.nombre, 
            productos.precio_compra, productos.precio_venta_sugerido, productos.precio_venta_real, 
            productos.stock,productos.stock_minimo, proveedores.empresa, productos.estado, productos.fecha_registro  
        FROM productos 
        LEFT JOIN proveedores ON productos.proveedor_id = proveedores.id

        WHERE productos.estado = 1 
        ORDER BY productos.nombre ASC";
    
    $resultado = $conexion->query($sql);

    if (!$resultado) {
        echo json_encode(['error' => 'Error al obtener productos: ' . $conexion->error]);
        return;
    }

    $productos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = [
            'id' => $fila['id'],
            'codigo' => strtoupper($fila['codigo']),
            'imagen' => $fila['imagen'],
            'nombre' => strtoupper($fila['nombre']),
            'precio_compra' => $fila['precio_compra'],
            'precio_venta_sugerido' => $fila['precio_venta_sugerido'],
            'precio_venta_real' => $fila['precio_venta_real'],
            'stock' => $fila['stock'],
            'stock_minimo' => $fila['stock_minimo'],
            'proveedor' => strtoupper($fila['empresa']),
            'estado' => strtoupper($fila['estado']),
            'fecha_registro' => $fila['fecha_registro']
        ];
    }

    echo json_encode($productos);
}

function obtenerProducto() {
    global $conexion;

    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'ID de producto no especificado']);
        return;
    }

    $id = (int)$_GET['id'];

    $sql = "SELECT 
    productos.id, 
    productos.codigo, 
    productos.imagen, 
    productos.nombre, 
    productos.precio_compra, 
    productos.precio_venta_sugerido, 
    productos.precio_venta_real, 
    productos.stock,
    productos.stock_minimo, 
    productos.proveedor_id, 
    proveedores.empresa AS proveedor_nombre
FROM productos
LEFT JOIN proveedores ON productos.proveedor_id = proveedores.id
WHERE productos.id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(['error' => 'Producto no encontrado']);
        return;
    }

    $producto = $resultado->fetch_assoc();

    echo json_encode($producto);
}

function actualizarPreciosProducto() {
    global $conexion;

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    // Los precios pueden ser 0 ahora
$precio_compra = isset($_POST['precio_compra']) && $_POST['precio_compra'] !== '' 
    ? floatval($_POST['precio_compra']) 
    : 0;

$precio_venta_sugerido = isset($_POST['precio_venta_sugerido']) && $_POST['precio_venta_sugerido'] !== '' 
    ? floatval($_POST['precio_venta_sugerido']) 
    : 0;

$precio_venta_real = isset($_POST['precio_venta_real']) && $_POST['precio_venta_real'] !== '' 
    ? floatval($_POST['precio_venta_real']) 
    : 0;
    $stock_minimo = isset($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : -1;

    // Validacion de campos en 0
    // Solo validar ID y stock mínimo
if ($id === 0 || $stock_minimo < 0) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }

    $sql = "UPDATE productos 
            SET precio_compra = ?, precio_venta_sugerido = ?, precio_venta_real = ?, stock_minimo = ?
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("dddii", $precio_compra, $precio_venta_sugerido, $precio_venta_real, $stock_minimo, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Precios actualizados correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar precios: ' . $stmt->error]);
    }

    $stmt->close();
}


// Función para eliminar imagen anterior
function eliminarImagenAnterior($nombreImagen) {
    if (!empty($nombreImagen)) {
        $rutaImagen = '../imagenes/productos/' . basename($nombreImagen);
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        }
    }
}

function actualizarProducto() {
    global $conexion;

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = isset($_POST['nombre']) ? stripslashes(trim($_POST['nombre'])) : '';
    $codigo = isset($_POST['codigo']) ? stripslashes(trim($_POST['codigo'])) : '';
    // Los precios pueden ser 0 si están vacíos
$precio_compra = isset($_POST['precio_compra']) && $_POST['precio_compra'] !== '' 
    ? floatval($_POST['precio_compra']) 
    : 0;

$precio_venta_sugerido = isset($_POST['precio_venta_sugerido']) && $_POST['precio_venta_sugerido'] !== '' 
    ? floatval($_POST['precio_venta_sugerido']) 
    : 0;

$precio_venta_real = isset($_POST['precio_venta_real']) && $_POST['precio_venta_real'] !== '' 
    ? floatval($_POST['precio_venta_real']) 
    : 0;
    $stock_minimo = isset($_POST['stock_minimo']) ? (int)$_POST['stock_minimo'] : -1;

    // Validación
    // Solo validar campos realmente requeridos
if ($id === 0 || empty($nombre) || empty($codigo) || $stock_minimo < 0) {
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }

    // Convertir a mayúsculas
    $nombre = strtoupper($nombre);
    $codigo = strtoupper($codigo);

    // Obtener datos actuales del producto
    $sqlActual = "SELECT codigo, imagen FROM productos WHERE id = ?";
    $stmtActual = $conexion->prepare($sqlActual);
    $stmtActual->bind_param('i', $id);
    $stmtActual->execute();
    $resultadoActual = $stmtActual->get_result();
    
    if ($resultadoActual->num_rows === 0) {
        echo json_encode(['error' => 'Producto no encontrado']);
        return;
    }
    
    $datosActuales = $resultadoActual->fetch_assoc();
    $codigoAnterior = $datosActuales['codigo'];
    $imagenAnterior = $datosActuales['imagen'];
    
    $nuevaImagen = $imagenAnterior; // Por defecto mantener la imagen actual
    $hayNuevaImagen = isset($_FILES['editFotoProducto']) && $_FILES['editFotoProducto']['error'] === UPLOAD_ERR_OK;
    $codigoCambio = strtoupper($codigoAnterior) !== strtoupper($codigo);

    // ESCENARIO 1: Se sube nueva imagen (con o sin cambio de código)
    if ($hayNuevaImagen) {
        // SEGURIDAD (16-09-2026): solo imágenes reales; la extensión la decide el servidor
        require_once __DIR__ . '/subidas.php';
        $codigoLimpio = preg_replace('/[^A-Za-z0-9]/', '_', $codigo);
        try {
            $nuevaImagen = guardarArchivoSubido($_FILES['editFotoProducto'], '../imagenes/productos/', ['imagen'], strtoupper($codigoLimpio));
        } catch (Exception $e) {
            echo json_encode(['error' => 'Imagen no válida: ' . $e->getMessage()]);
            return;
        }
        
        // Eliminar la imagen anterior solo si tenía otro nombre (si era igual, ya se reemplazó)
        if (!empty($imagenAnterior) && $imagenAnterior !== $nuevaImagen) {
            eliminarImagenAnterior($imagenAnterior);
        }
    }
    // ESCENARIO 2: Solo cambió el código (sin nueva imagen)
    elseif ($codigoCambio && !empty($imagenAnterior)) {
        // Renombrar imagen existente con el nuevo código
        $extActual = pathinfo($imagenAnterior, PATHINFO_EXTENSION);
        $codigoLimpioNuevo = preg_replace('/[^A-Za-z0-9]/', '_', $codigo);
        $nuevoNombreImagen = strtoupper($codigoLimpioNuevo) . '.' . $extActual;
        
        $rutaAnterior = '../imagenes/productos/' . $imagenAnterior;
        $rutaNueva = '../imagenes/productos/' . $nuevoNombreImagen;
        
        if (file_exists($rutaAnterior)) {
            if (rename($rutaAnterior, $rutaNueva)) {
                $nuevaImagen = $nuevoNombreImagen;
            } else {
                echo json_encode(['error' => 'Error al renombrar la imagen existente.']);
                return;
            }
        }
    }

    // Actualizar producto incluyendo imagen
    $sql = "UPDATE productos 
            SET nombre = ?, codigo = ?, precio_compra = ?, precio_venta_sugerido = ?, 
                precio_venta_real = ?, stock_minimo = ?, imagen = ?
            WHERE id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssdddisi", $nombre, $codigo, $precio_compra, 
                      $precio_venta_sugerido, $precio_venta_real, $stock_minimo, $nuevaImagen, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Producto actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar producto: ' . $stmt->error]);
    }

    $stmt->close();
}



    function actualizarStock() {
    global $conexion;

    $id = (int)$_POST['id'];
    $operacion = $_POST['operacion'];
    $cantidad = (int)$_POST['cantidad'];
    $nombreUsuario = $_POST['nombre_usuario'] ?? 'Desconocido'; // Recibimos el nombre del usuario

    if ($cantidad <= 0) {
        echo json_encode(['error' => 'Cantidad inválida']);
        return;
    }

    // El precio de compra solo aplica cuando se agrega stock (es una compra real).
    // En 'reducir' no se registra precio, tal como se definió.
    $precioCompraUnitario = null;
    $costoTotal = null;
    if ($operacion === 'agregar') {
        if (!isset($_POST['precio_compra_unitario']) || !is_numeric($_POST['precio_compra_unitario']) || (float)$_POST['precio_compra_unitario'] <= 0) {
            echo json_encode(['error' => 'Precio de compra unitario inválido']);
            return;
        }
        $precioCompraUnitario = round((float)$_POST['precio_compra_unitario'], 2);
        $costoTotal = round($precioCompraUnitario * $cantidad, 2);
    }

    // Obtener stock actual
    $sql = "SELECT stock, nombre FROM productos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(['error' => 'Producto no encontrado']);
        return;
    }

    $producto = $resultado->fetch_assoc();
    $stockActual = (int)$producto['stock'];
    $nombreProducto = $producto['nombre'];

    // Calcular nuevo stock
    if ($operacion === 'agregar') {
        $nuevoStock = $stockActual + $cantidad;
    } elseif ($operacion === 'reducir') {
        if ($cantidad > $stockActual) {
            echo json_encode(['error' => 'No puedes reducir más de lo disponible']);
            return;
        }
        $nuevoStock = $stockActual - $cantidad;
    } else {
        echo json_encode(['error' => 'Operación inválida']);
        return;
    }

    // Actualizar stock en tabla productos.
    // Si es una compra (agregar), también se actualiza precio_compra al precio
    // más reciente ingresado, sin importar si subió, bajó o quedó igual.
    if ($operacion === 'agregar') {
        $updateSql = "UPDATE productos SET stock = ?, precio_compra = ? WHERE id = ?";
        $updateStmt = $conexion->prepare($updateSql);
        $updateStmt->bind_param('idi', $nuevoStock, $precioCompraUnitario, $id);
    } else {
        $updateSql = "UPDATE productos SET stock = ? WHERE id = ?";
        $updateStmt = $conexion->prepare($updateSql);
        $updateStmt->bind_param('ii', $nuevoStock, $id);
    }
    $updateStmt->execute();

    // Registrar en historial (opcional)
    $historialSql = "INSERT INTO historial_stock (producto_id, producto_nombre, operacion, cantidad, precio_compra_unitario, costo_total, stock_anterior, stock_nuevo, usuario_responsable, fecha)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $historialStmt = $conexion->prepare($historialSql);
    $historialStmt->bind_param('issiddiis', $id, $nombreProducto, $operacion, $cantidad, $precioCompraUnitario, $costoTotal, $stockActual, $nuevoStock, $nombreUsuario);
    $historialStmt->execute();

    echo json_encode(['mensaje' => 'Stock actualizado correctamente']);
}


function eliminarProducto() {
    global $conexion;

    if (!isset($_POST['id'])) {
        echo json_encode(['error' => 'ID de producto no especificado']);
        return;
    }

    $id = (int)$_POST['id'];

    $sql = "UPDATE productos SET estado = 0 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Producto eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar producto: ' . $stmt->error]);
    }

    $stmt->close();
}



