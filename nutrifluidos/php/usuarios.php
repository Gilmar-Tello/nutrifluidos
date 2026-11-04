<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);


date_default_timezone_set('America/Guatemala');
require_once __DIR__ . '/../../../privado/conexion.php';

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
        listarUsuarios();
        break;
    case 'obtener':
        obtenerUsuario();
        break;
    case 'crear':
        crearUsuario();
        break;
    case 'actualizar':
        actualizarUsuario();
        break;
    case 'eliminar':
        eliminarUsuario();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}


function listarUsuarios() {
    global $conexion;

    // SEGURIDAD: nunca se devuelve la contraseña
    $sql = "SELECT id, nombre_completo, telefono, correo_electronico, direccion, usuario, rol, estado 
            FROM usuarios 
            WHERE estado = 1 
            ORDER BY id ASC";
    
    $resultado = $conexion->query($sql);

    if (!$resultado) {
        echo json_encode(['error' => 'Error al obtener usuarios: ' . $conexion->error]);
        return;
    }

    $usuarios = [];
    while ($fila = $resultado->fetch_assoc()) {
        $usuarios[] = [
            'id' => $fila['id'],
            'nombre_completo' => strtoupper($fila['nombre_completo']),
            'telefono' => $fila['telefono'],
            'correo_electronico' => $fila['correo_electronico'],
            'direccion' => strtoupper($fila['direccion']),
            'usuario' => $fila['usuario'],
            'rol' => strtoupper($fila['rol']),
            'estado' => $fila['estado']
        ];
    }

    echo json_encode($usuarios);
}

function obtenerUsuario() {
    global $conexion;

    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'ID de usuario no especificado']);
        return;
    }

    $id = (int)$_GET['id'];

    $sql = "SELECT id, nombre_completo, telefono, correo_electronico, direccion, usuario, rol, estado 
            FROM usuarios 
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(['error' => 'Usuario no encontrado']);
        return;
    }

    $usuario = $resultado->fetch_assoc();

    $usuario['nombre_completo'] = strtoupper($usuario['nombre_completo']);
    $usuario['direccion'] = strtoupper($usuario['direccion']);
    $usuario['rol'] = strtoupper($usuario['rol']);
    $usuario['estado'] = (int)$usuario['estado'];

    echo json_encode($usuario);
}



function crearUsuario() {
    global $conexion;

    $nombre_completo = sanitizar($_POST['nombre_completo'] ?? '');
    $telefono = sanitizar($_POST['telefono'] ?? '');
    $correo_electronico = sanitizar($_POST['correo_electronico'] ?? '');
    $direccion = sanitizar($_POST['direccion'] ?? '');
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? ''); // sin escapar: se guarda cifrada
    $rol = sanitizar($_POST['rol'] ?? '');

    // Validamos que el rol esté entre los valores permitidos
    $roles_validos = ['administrador', 'bodeguero', 'vendedor'];
    if (!in_array($rol, $roles_validos)) {
        echo json_encode(['error' => 'Rol no válido']);
        return;
    }

    if (empty($nombre_completo) || empty($usuario) || empty($contrasena)) {
        echo json_encode(['error' => 'Faltan datos requeridos']);
        return;
    }

    if (strlen($contrasena) < 6) {
        echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
        return;
    }
    // SEGURIDAD: contraseña cifrada
    $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);

    $estadoDB = 1; // El estado por defecto será "activo"

    $sql = "INSERT INTO usuarios (nombre_completo, telefono, correo_electronico, direccion, usuario, contrasena, rol, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssssi", $nombre_completo, $telefono, $correo_electronico, $direccion, $usuario, $contrasena, $rol, $estadoDB);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Usuario creado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al crear usuario: ' . $stmt->error]);
    }

    $stmt->close();
}

function actualizarUsuario() {
    global $conexion;

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre_completo = sanitizar($_POST['nombre_completo'] ?? '');
    $telefono = sanitizar($_POST['telefono'] ?? '');
    $correo_electronico = sanitizar($_POST['correo_electronico'] ?? '');
    $direccion = sanitizar($_POST['direccion'] ?? '');
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $contrasena = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : null;  // puede ser null (sin escapar: se cifra)
    $rol = sanitizar($_POST['rol'] ?? '');
    $estado = sanitizar($_POST['estado'] ?? '');

    $roles_validos = ['administrador', 'bodeguero', 'vendedor'];
    if (!in_array($rol, $roles_validos)) {
        echo json_encode(['error' => 'Rol no válido']);
        return;
    }

    $estados_validos = ['activo', 'inactivo'];
    if (!in_array($estado, $estados_validos)) {
        echo json_encode(['error' => 'Estado no válido']);
        return;
    }

    if ($id === 0 || empty($nombre_completo) || empty($usuario)) {
        echo json_encode(['error' => 'Faltan datos requeridos']);
        return;
    }

    if ($contrasena !== null && $contrasena !== '') {
        if (strlen($contrasena) < 6) {
            echo json_encode(['error' => 'La contraseña debe tener al menos 6 caracteres']);
            return;
        }
        // SEGURIDAD: contraseña cifrada
        $contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
        //  Actualizar también la contraseña
        $sql = "UPDATE usuarios 
                SET nombre_completo=?, telefono=?, correo_electronico=?, direccion=?, usuario=?, contrasena=?, rol=?, estado=?
                WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssssssi", $nombre_completo, $telefono, $correo_electronico, $direccion, $usuario, $contrasena, $rol, $estado, $id);
    } else {
        //  No actualizar la contraseña
        $sql = "UPDATE usuarios 
                SET nombre_completo=?, telefono=?, correo_electronico=?, direccion=?, usuario=?, rol=?, estado=?
                WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssi", $nombre_completo, $telefono, $correo_electronico, $direccion, $usuario, $rol, $estado, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Usuario actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar usuario: ' . $stmt->error]);
    }

    $stmt->close();
}




function eliminarUsuario() {
    global $conexion;

    if (!isset($_POST['id'])) {
        echo json_encode(['error' => 'ID de usuario no especificado']);
        return;
    }

    $id = (int)$_POST['id'];

    $sql = "UPDATE usuarios SET estado = 'inactivo' WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Usuario eliminado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al eliminar usuario: ' . $stmt->error]);
    }

    $stmt->close();
}
