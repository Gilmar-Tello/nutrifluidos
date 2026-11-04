<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada de administrador
require_once __DIR__ . '/auth.php';
requerirSesion(['administrador']);
require_once __DIR__ . '/../../../privado/conexion.php';

header('Content-Type: application/json');

function sanitizar($dato) {
    global $conexion;
    // SEGURIDAD (16-09-2026): quita < y > para que no se pueda guardar <script> ni otras
    // etiquetas (mitiga XSS almacenado en nombres de cliente, producto, etc.).
    $dato = str_replace(array('<', '>'), '', trim((string)$dato));
    return $conexion->real_escape_string($dato);
}

// SEGURIDAD: identidad desde la sesión, no desde la URL
$userData = usuarioActual();

// Verificar que solo administradores puedan gestionar permisos
if (!$userData || $userData['rol'] !== 'administrador') {
    echo json_encode(['error' => 'Solo los administradores pueden gestionar permisos']);
    exit;
}

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'listar_vendedores':
        listarVendedores();
        break;
    case 'buscar_clientes':
        buscarClientes();
        break;
    case 'buscar_vendedores':
        buscarVendedores();
        break;
    case 'listar_permisos':
        listarPermisos();
        break;
    case 'conceder_permiso':
        concederPermiso();
        break;
    case 'revocar_permiso':
        revocarPermiso();
        break;
    case 'obtener_permisos_vendedor':
        obtenerPermisosVendedor();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

function listarVendedores() {
    global $conexion;
    
    $sql = "SELECT id, nombre_completo, email 
            FROM usuarios 
            WHERE rol = 'vendedor' AND estado = 1 
            ORDER BY nombre_completo";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $vendedores = [];
    while ($fila = $resultado->fetch_assoc()) {
        $vendedores[] = [
            'id' => $fila['id'],
            'nombre' => $fila['nombre_completo'],
            'email' => $fila['email']
        ];
    }
    
    echo json_encode($vendedores);
}

function buscarClientes() {
    global $conexion;
    
    $termino = $_GET['termino'] ?? '';
    $termino = trim($termino);
    
    if (strlen($termino) < 2) {
        echo json_encode([]);
        return;
    }
    
    // Buscar por empresa o NIT
    $terminoBusqueda = "%{$termino}%";
    
    $sql = "SELECT c.id, c.empresa, c.nit, c.id_vendedor, u.nombre_completo as nombre_vendedor
            FROM clientes c
            LEFT JOIN usuarios u ON c.id_vendedor = u.id
            WHERE c.estado = 1 
            AND (c.empresa LIKE ? OR c.nit LIKE ?)
            ORDER BY c.empresa
            LIMIT 10";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ss', $terminoBusqueda, $terminoBusqueda);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $clientes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = [
            'id' => $fila['id'],
            'empresa' => $fila['empresa'],
            'nit' => $fila['nit'],
            'id_vendedor' => $fila['id_vendedor'],
            'nombre_vendedor' => $fila['nombre_vendedor'] ?? 'Sin asignar'
        ];
    }
    
    echo json_encode($clientes);
}

function buscarVendedores() {
    global $conexion;
    
    $termino = $_GET['termino'] ?? '';
    $vendedorExcluir = $_GET['excluir'] ?? '';
    $termino = trim($termino);
    
    if (strlen($termino) < 2) {
        echo json_encode([]);
        return;
    }
    
    // Buscar por nombre completo o email
    $terminoBusqueda = "%{$termino}%";
    
    $sql = "SELECT id, nombre_completo, correo_electronico, usuario
            FROM usuarios 
            WHERE rol = 'vendedor' 
            AND estado = 'activo' 
            AND (nombre_completo LIKE ? OR correo_electronico LIKE ? OR usuario LIKE ?)";
    
    // Excluir un vendedor específico si se proporciona
    if (!empty($vendedorExcluir)) {
        $sql .= " AND id != ?";
    }
    
    $sql .= " ORDER BY nombre_completo LIMIT 10";
    
    $stmt = $conexion->prepare($sql);
    
    if (!empty($vendedorExcluir)) {
        $stmt->bind_param('sssi', $terminoBusqueda, $terminoBusqueda, $terminoBusqueda, $vendedorExcluir);
    } else {
        $stmt->bind_param('sss', $terminoBusqueda, $terminoBusqueda, $terminoBusqueda);
    }
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $vendedores = [];
    while ($fila = $resultado->fetch_assoc()) {
        $vendedores[] = [
            'id' => $fila['id'],
            'nombre' => $fila['nombre_completo'],
            'email' => $fila['correo_electronico'],
            'usuario' => $fila['usuario']
        ];
    }
    
    echo json_encode($vendedores);
}

function listarPermisos() {
    global $conexion;
    
    $sql = "SELECT p.*, 
                   ua.nombre_completo as vendedor_autorizado,
                   up.nombre_completo as vendedor_propietario,
                   admin.nombre_completo as administrador
            FROM permisos_vendedores p
            JOIN usuarios ua ON p.id_vendedor_autorizado = ua.id
            JOIN usuarios up ON p.id_vendedor_propietario = up.id
            JOIN usuarios admin ON p.id_administrador = admin.id
            WHERE p.estado = 1
            ORDER BY p.fecha_concesion DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $permisos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $permisos[] = [
            'id' => $fila['id'],
            'vendedor_autorizado' => $fila['vendedor_autorizado'],
            'vendedor_propietario' => $fila['vendedor_propietario'],
            'administrador' => $fila['administrador'],
            'fecha_concesion' => $fila['fecha_concesion'],
            'motivo' => $fila['motivo'],
            'observaciones' => $fila['observaciones']
        ];
    }
    
    echo json_encode($permisos);
}

function concederPermiso() {
    global $conexion, $userData;
    
    $idVendedorAutorizado = (int)$_POST['id_vendedor_autorizado'];
    $idVendedorPropietario = (int)$_POST['id_vendedor_propietario'];
    $motivo = sanitizar($_POST['motivo'] ?? '');
    $observaciones = sanitizar($_POST['observaciones'] ?? '');
    
    // Validaciones
    if ($idVendedorAutorizado === 0 || $idVendedorPropietario === 0) {
        echo json_encode(['error' => 'Debe seleccionar ambos vendedores']);
        return;
    }
    
    if ($idVendedorAutorizado === $idVendedorPropietario) {
        echo json_encode(['error' => 'No puede otorgar permisos sobre sus propios clientes']);
        return;
    }
    
    if (empty($motivo)) {
        echo json_encode(['error' => 'El motivo es requerido']);
        return;
    }
    
    // Verificar que no existe un permiso activo igual
    $sqlVerificar = "SELECT id FROM permisos_vendedores 
                     WHERE id_vendedor_autorizado = ? 
                     AND id_vendedor_propietario = ? 
                     AND estado = 1";
    
    $stmtVerificar = $conexion->prepare($sqlVerificar);
    $stmtVerificar->bind_param('ii', $idVendedorAutorizado, $idVendedorPropietario);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();
    
    if ($resultadoVerificar->num_rows > 0) {
        echo json_encode(['error' => 'Ya existe un permiso activo entre estos vendedores']);
        return;
    }
    
    // Crear el permiso
    $sql = "INSERT INTO permisos_vendedores 
            (id_vendedor_autorizado, id_vendedor_propietario, id_administrador, motivo, observaciones) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('iiiss', 
        $idVendedorAutorizado, 
        $idVendedorPropietario, 
        $userData['id'], 
        $motivo, 
        $observaciones
    );
    
    if ($stmt->execute()) {
        // Obtener nombres para el mensaje de confirmación
        $sqlNombres = "SELECT ua.nombre_completo as autorizado, up.nombre_completo as propietario
                       FROM usuarios ua, usuarios up 
                       WHERE ua.id = ? AND up.id = ?";
        $stmtNombres = $conexion->prepare($sqlNombres);
        $stmtNombres->bind_param('ii', $idVendedorAutorizado, $idVendedorPropietario);
        $stmtNombres->execute();
        $nombres = $stmtNombres->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => "Permiso concedido correctamente. {$nombres['autorizado']} ahora puede gestionar clientes de {$nombres['propietario']}"
        ]);
    } else {
        echo json_encode(['error' => 'Error al conceder el permiso: ' . $stmt->error]);
    }
}

function revocarPermiso() {
    global $conexion, $userData;
    
    $idPermiso = (int)$_POST['id_permiso'];
    
    if ($idPermiso === 0) {
        echo json_encode(['error' => 'ID de permiso no válido']);
        return;
    }
    
    // Verificar que el permiso existe y está activo
    $sqlVerificar = "SELECT p.*, ua.nombre_completo as autorizado, up.nombre_completo as propietario
                     FROM permisos_vendedores p
                     JOIN usuarios ua ON p.id_vendedor_autorizado = ua.id
                     JOIN usuarios up ON p.id_vendedor_propietario = up.id
                     WHERE p.id = ? AND p.estado = 1";
    
    $stmtVerificar = $conexion->prepare($sqlVerificar);
    $stmtVerificar->bind_param('i', $idPermiso);
    $stmtVerificar->execute();
    $resultado = $stmtVerificar->get_result();
    
    if ($resultado->num_rows === 0) {
        echo json_encode(['error' => 'Permiso no encontrado o ya está revocado']);
        return;
    }
    
    $permiso = $resultado->fetch_assoc();
    
    // Revocar el permiso
    $sql = "UPDATE permisos_vendedores 
            SET estado = 0, fecha_revocacion = CURRENT_TIMESTAMP 
            WHERE id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idPermiso);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => "Permiso revocado correctamente. {$permiso['autorizado']} ya no puede gestionar clientes de {$permiso['propietario']}"
        ]);
    } else {
        echo json_encode(['error' => 'Error al revocar el permiso: ' . $stmt->error]);
    }
}

function obtenerPermisosVendedor() {
    global $conexion;
    
    $idVendedor = (int)$_GET['id_vendedor'];
    
    if ($idVendedor === 0) {
        echo json_encode(['error' => 'ID de vendedor no válido']);
        return;
    }
    
    // Obtener vendedores cuyos clientes puede gestionar este vendedor
    $sql = "SELECT DISTINCT p.id_vendedor_propietario, u.nombre_completo
            FROM permisos_vendedores p
            JOIN usuarios u ON p.id_vendedor_propietario = u.id
            WHERE p.id_vendedor_autorizado = ? AND p.estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idVendedor);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $vendedoresPropietarios = [];
    while ($fila = $resultado->fetch_assoc()) {
        $vendedoresPropietarios[] = $fila['id_vendedor_propietario'];
    }
    
    echo json_encode($vendedoresPropietarios);
}
?>