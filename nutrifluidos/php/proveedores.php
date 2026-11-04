<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';
date_default_timezone_set('America/Guatemala'); // <-- ESTA LÍNEA ES CLAVE


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
        listarProveedores();
        break;
    case 'obtener':
        obtenerProveedor();
        break;
    case 'crear':
        crearProveedor();
        break;
    case 'actualizar':
        actualizarProveedor();
        break;
    case 'eliminar':
        eliminarProveedor();
        break;
    case 'actualizarFavorito':
        actualizarFavorito();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}


function listarProveedores() { 
    global $conexion;

    $sql = "SELECT 
    p.id, 
    p.empresa, 
    p.contacto, 
    p.telefono,
    p.contacto2, 
    p.telefono2,
    p.email, 
    p.direccion, 
    p.pais_origen, 
    p.estado, 
    p.favorito,
    p.fecha_registro,
    COUNT(pr.id) AS total_productos
FROM proveedores p
LEFT JOIN productos pr ON pr.proveedor_id = p.id AND pr.estado = 1
WHERE p.estado = 1
GROUP BY p.id
ORDER BY p.favorito DESC, p.empresa ASC";

    $resultado = $conexion->query($sql);

    if (!$resultado) {
        echo json_encode(['error' => 'Error al obtener proveedores: ' . $conexion->error]);
        return;
    }

    $proveedores = [];
    while ($fila = $resultado->fetch_assoc()) {
        $proveedores[] = [
            'id' => $fila['id'],
            'empresa' => strtoupper($fila['empresa']),
            'contacto' => strtoupper($fila['contacto']),
            'telefono' => $fila['telefono'],
            'contacto2' => strtoupper($fila['contacto2']),
            'telefono2' => $fila['telefono2'],
            'email' => $fila['email'],
            'direccion' => strtoupper($fila['direccion']),
            'pais_origen' => strtoupper($fila['pais_origen']),
            'estado' => strtoupper($fila['estado']),
            'fecha_registro' => $fila['fecha_registro'],
            'total_productos' => (int)$fila['total_productos'],
            'favorito' => (bool)$fila['favorito'] // ✅ Aquí lo agregas

        ];
    }

    echo json_encode($proveedores);
}


function obtenerProveedor() {
    global $conexion;

    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'ID de proveedor no especificado']);
        return;
    }

    $id = (int)$_GET['id'];

    $sql = "SELECT id, empresa, contacto, telefono, contacto2, telefono2, email, direccion, pais_origen, estado, fecha_registro 
            FROM proveedores 
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(['error' => 'Proveedor no encontrado']);
        return;
    }

    $proveedor = $resultado->fetch_assoc();

    // Puedes convertir en mayúscula algunos campos si quieres mostrar bonitos en el formulario
    $proveedor['empresa'] = strtoupper($proveedor['empresa']);
    $proveedor['contacto'] = strtoupper($proveedor['contacto']);
    $proveedor['contacto2'] = strtoupper($proveedor['contacto2']);
    $proveedor['pais_origen'] = strtoupper($proveedor['pais_origen']);
    $proveedor['estado'] = (int)$proveedor['estado']; // Asegura que venga como 0 o 1


    echo json_encode($proveedor);
}

function actualizarProveedor() {
    global $conexion;

    // Validar que todos los campos estén presentes
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $empresa = sanitizar($_POST['empresa'] ?? '');
    $contacto = sanitizar($_POST['contacto'] ?? '');
    $telefono = sanitizar($_POST['telefono'] ?? '');
    $contacto2 = sanitizar($_POST['contacto2'] ?? '');
    $telefono2 = sanitizar($_POST['telefono2'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $direccion = sanitizar($_POST['direccion'] ?? '');
    $pais_origen = sanitizar($_POST['pais_origen'] ?? '');
   // $estado = sanitizar($_POST['estado'] ?? '');
    
    // Validación básica
    if ($id === 0 || empty($empresa) || empty($contacto)) {
        echo json_encode(['error' => 'Faltan datos requeridos']);
        return;
    }

    // Convertir estado a formato de base de datos
   // $estadoDB = strtoupper($estado) === 'ACTIVO' ? 1 : 0;

   $sql = "UPDATE proveedores 
   SET empresa=?, contacto=?, telefono=?, contacto2=?, telefono2=?, email=?, direccion=?, pais_origen=?
   WHERE id=?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssssssssi", $empresa, $contacto, $telefono, $contacto2, $telefono2, $email, $direccion, $pais_origen, $id);


    if ($stmt->execute()) {
        echo json_encode(['success' => 'Proveedor actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar proveedor: ' . $stmt->error]);
    }

    $stmt->close();
}

function crearProveedor() {
    global $conexion;

    $empresa = sanitizar($_POST['empresa'] ?? '');
    $contacto = sanitizar($_POST['contacto'] ?? '');
    $telefono = sanitizar($_POST['telefono'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $direccion = sanitizar($_POST['direccion'] ?? '');
    $pais_origen = sanitizar($_POST['pais_origen'] ?? '');

    $contacto2 = sanitizar($_POST['contacto2'] ?? null);
    $telefono2 = sanitizar($_POST['telefono2'] ?? null);

    if (empty($empresa) || empty($contacto)) {
        echo json_encode(['error' => 'Faltan datos requeridos']);
        return;
    }

    $estadoDB = 1;

    $sql = "INSERT INTO proveedores (
                empresa, contacto, telefono, email, direccion, pais_origen, 
                contacto2, telefono2, estado, fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
    "ssssssssi", 
    $empresa, $contacto, $telefono, $email, $direccion, $pais_origen,
    $contacto2, $telefono2, $estadoDB
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Proveedor agregado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al crear proveedor: ' . $stmt->error]);
    }

    $stmt->close();
}


function eliminarProveedor() {
    global $conexion;

    if (!isset($_POST['id'])) {
        echo json_encode(['error' => 'ID de proveedor no especificado']);
        return;
    }

    $id = (int)$_POST['id'];

    $sql = "UPDATE proveedores SET estado = 0 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Proveedor eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar proveedor: ' . $stmt->error]);
    }

    $stmt->close();
}

function actualizarFavorito() {
    global $conexion;

    $id = intval($_POST['id'] ?? 0);
    $favorito = intval($_POST['favorito'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        return;
    }

    $stmt = $conexion->prepare("UPDATE proveedores SET favorito = ? WHERE id = ?");
    $stmt->bind_param("ii", $favorito, $id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => $favorito 
                ? 'Proveedor marcado como favorito' 
                : 'Proveedor eliminado de favoritos'
        ]);
    } else {
        echo json_encode(['error' => 'Error al actualizar favorito: ' . $stmt->error]);
    }

    $stmt->close();
}

