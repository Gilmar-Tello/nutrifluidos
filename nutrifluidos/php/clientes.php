<?php
// SEGURIDAD (16-09-2026): requiere sesión iniciada
require_once __DIR__ . '/auth.php';
requerirSesion();
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

// Si no se proporcionaron los datos de usuario, devolver error
if (!$userData) {
    // Solo rechazar si la acción requiere autenticación
    $accion = $_REQUEST['accion'] ?? '';
    if (in_array($accion, ['crear', 'actualizar', 'eliminar'])) {
        echo json_encode(['error' => 'Usuario no autenticado']);
        exit;
    }
}

$accion = $_REQUEST['accion'] ?? '';

switch ($accion) {
    case 'listar':
        listarClientes();
        break;
    case 'obtener':
        obtenerCliente();
        break;
    case 'crear':
        crearCliente();
        break;
    case 'actualizar':
        actualizarCliente();
        break;
    case 'eliminar':
        eliminarCliente();
        break;
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

function listarClientes() {
    global $conexion, $userData;
    
    $idUsuario = $userData['id'] ?? 0;
    $rolUsuario = $userData['rol'] ?? '';
    
    // Obtenemos el ID de usuario del parámetro GET si existe (para filtros manuales)
    $idFiltro = $_GET['id_usuario'] ?? null;
    
    // NOTA: La consulta ya incluye todas las columnas con c.* incluyendo direccion2
    $sql = "SELECT c.*, u.nombre_completo as nombre_vendedor 
            FROM clientes c
            LEFT JOIN usuarios u ON c.id_vendedor = u.id
            WHERE c.estado = 1";
    
    // LÓGICA DE PERMISOS CORREGIDA
    if ($rolUsuario === 'vendedor') {
        // Para vendedores: obtener IDs de vendedores cuyos clientes puede gestionar
        $vendedoresConAcceso = obtenerVendedoresConAccesoClientes($idUsuario);
        
        // Crear placeholders para la consulta IN
        $placeholders = str_repeat('?,', count($vendedoresConAcceso) - 1) . '?';
        $sql .= " AND c.id_vendedor IN ($placeholders)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($vendedoresConAcceso)), ...$vendedoresConAcceso);
    } elseif ($idFiltro) {
        // Filtro manual para administradores
        $sql .= " AND c.id_vendedor = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $idFiltro);
    } else {
        // Sin filtro para administradores y bodegueros (ven todos los clientes)
        $stmt = $conexion->prepare($sql);
    }
    
    $stmt->execute();
    $resultado = $stmt->get_result();

    $clientes = [];
    while ($fila = $resultado->fetch_assoc()) {
        // Determinar si puede gestionar este cliente
        $puedeGestionar = false;
        if ($rolUsuario === 'administrador') {
            $puedeGestionar = true;
        } elseif ($rolUsuario === 'vendedor') {
            // Es suyo o tiene permiso temporal
            $puedeGestionar = ($fila['id_vendedor'] == $idUsuario) || 
                              tienePermisoTemporal($idUsuario, $fila['id_vendedor']);
        }
        
        // Indicador de permiso temporal
        $tienePermisoTemporal = false;
        if ($rolUsuario === 'vendedor' && $fila['id_vendedor'] != $idUsuario) {
            $tienePermisoTemporal = tienePermisoTemporalClientes($idUsuario, $fila['id_vendedor']);
        }

        $clientes[] = [
            'id' => $fila['id'],
            'empresa' => $fila['empresa'],
            'nit' => $fila['nit'],
            'contacto1' => $fila['contacto1'],
            'telefono1' => $fila['telefono1'],
            'contacto2' => $fila['contacto2'],
            'telefono2' => $fila['telefono2'],
            'email' => $fila['email'],
            'direccion' => $fila['direccion'],
            'direccion2' => $fila['direccion2'], // AGREGADO: direccion2 al array de respuesta
            'region' => $fila['region'],
            'id_vendedor' => $fila['id_vendedor'],
            'nombre_vendedor' => $fila['nombre_vendedor'] ?? 'Sin asignar',
            'puede_gestionar' => $puedeGestionar,
            'es_propio' => ($fila['id_vendedor'] == $idUsuario),
            'permiso_temporal' => $tienePermisoTemporal
        ];
    }

    echo json_encode($clientes);
}

// Función auxiliar para verificar permisos temporales
function tienePermisoTemporal($idVendedorAutorizado, $idVendedorPropietario) {
    global $conexion;
    
    $sql = "SELECT id FROM permisos_vendedores 
            WHERE id_vendedor_autorizado = ? 
            AND id_vendedor_propietario = ? 
            AND estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $idVendedorAutorizado, $idVendedorPropietario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->num_rows > 0;
}

function obtenerCliente() {
    global $conexion, $userData;

    // Verificar permisos - Solo administradores pueden obtener datos para editar
    if ($userData['rol'] !== 'administrador') {
        echo json_encode(['error' => 'No tienes permisos para acceder a estos datos']);
        return;
    }

    if (!isset($_GET['id'])) {
        echo json_encode(['error' => 'ID de cliente no especificado']);
        return;
    }

    $id = (int)$_GET['id'];

    // MODIFICADO: Agregar direccion2 a la consulta
    $sql = "SELECT id, empresa, nit, contacto1, telefono1, contacto2, telefono2, 
            email, direccion, direccion2, region, id_vendedor, estado, fecha_registro 
            FROM clientes 
            WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 0) {
        echo json_encode(['error' => 'Cliente no encontrado']);
        return;
    }

    $cliente = $resultado->fetch_assoc();

    $cliente['empresa'] = strtoupper($cliente['empresa']);
    $cliente['contacto1'] = strtoupper($cliente['contacto1']);
    $cliente['contacto2'] = strtoupper($cliente['contacto2']);
    $cliente['estado'] = (int)$cliente['estado'];

    echo json_encode($cliente);
}

function actualizarCliente() {
    global $conexion, $userData;

    if (!$userData) {
        echo json_encode(['error' => 'Usuario no autenticado']);
        return;
    }

    // Solo administradores pueden actualizar clientes
    if ($userData['rol'] !== 'administrador') {
        echo json_encode(['error' => 'No tienes permisos para actualizar clientes. Solo los administradores pueden editar.']);
        return;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $empresa = sanitizar($_POST['empresa'] ?? '');
    $nit = sanitizar($_POST['nit'] ?? '');
    $contacto1 = sanitizar($_POST['contacto1'] ?? '');
    $telefono1 = sanitizar($_POST['telefono1'] ?? '');
    $contacto2 = sanitizar($_POST['contacto2'] ?? '');
    $telefono2 = sanitizar($_POST['telefono2'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $direccion = sanitizar($_POST['direccion'] ?? '');
    $direccion2 = sanitizar($_POST['direccion2'] ?? ''); // AGREGAR DIRECCION2
    $region = sanitizar($_POST['region'] ?? '');
    
    // CORREGIDO: Validar campos requeridos - NIT ya no es obligatorio
    if ($id === 0 || empty($empresa) || empty($contacto1) || empty($region)) {
        echo json_encode(['error' => 'Los campos ID, Empresa, Contacto 1 y Región son requeridos']);
        return;
    }

    // Convertir NIT vacío a NULL para consistencia
    if (empty($nit) || $nit === '0') {
        $nit = null;
    }

    // Verificar que el cliente existe
    $sqlVerificar = "SELECT id FROM clientes WHERE id = ? AND estado = 1";
    $stmtVerificar = $conexion->prepare($sqlVerificar);
    $stmtVerificar->bind_param('i', $id);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();
    
    if ($resultadoVerificar->num_rows === 0) {
        echo json_encode(['error' => 'Cliente no encontrado']);
        return;
    }

    // MODIFICADO: Al actualizar, incluir direccion2
    $sql = "UPDATE clientes 
            SET empresa=?, nit=?, contacto1=?, telefono1=?, contacto2=?, telefono2=?, 
                email=?, direccion=?, direccion2=?, region=?
            WHERE id=?";
    
    $stmt = $conexion->prepare($sql);
    
    // MODIFICADO: bind_param para 11 parámetros: 10 strings y 1 integer
    $stmt->bind_param("ssssssssssi", 
                      $empresa, 
                      $nit, 
                      $contacto1, 
                      $telefono1, 
                      $contacto2, 
                      $telefono2, 
                      $email, 
                      $direccion, 
                      $direccion2, // AGREGADO: direccion2
                      $region, 
                      $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Cliente actualizado correctamente']);
    } else {
        echo json_encode(['error' => 'Error al actualizar cliente: ' . $stmt->error]);
    }

    $stmt->close();
}


function crearCliente() {
    global $conexion, $userData;

    // Verificar que tengamos datos de usuario
    if (!$userData) {
        echo json_encode(['error' => 'Usuario no autenticado']);
        return;
    }

    $idUsuario = $userData['id'] ?? 0;
    $rolUsuario = $userData['rol'] ?? '';

    // Todos los roles pueden crear clientes
    if (!in_array($rolUsuario, ['administrador', 'vendedor', 'bodeguero'])) {
        echo json_encode(['error' => 'No tienes permisos para crear clientes']);
        return;
    }

    $empresa = sanitizar($_POST['empresa'] ?? '');
    $nit = sanitizar($_POST['nit'] ?? '');
    $contacto1 = sanitizar($_POST['contacto1'] ?? '');
    $telefono1 = sanitizar($_POST['telefono1'] ?? '');
    $contacto2 = sanitizar($_POST['contacto2'] ?? '');
    $telefono2 = sanitizar($_POST['telefono2'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $direccion = sanitizar($_POST['direccion'] ?? '');
    $direccion2 = sanitizar($_POST['direccion2'] ?? ''); // AGREGAR DIRECCION2
    $region = sanitizar($_POST['region'] ?? '');
    
    // Usar el ID del vendedor del formulario o el del usuario actual
    $id_vendedor = isset($_POST['id_vendedor']) && !empty($_POST['id_vendedor']) ? (int)$_POST['id_vendedor'] : $idUsuario;
    // SEGURIDAD: solo el administrador puede asignar clientes a otro vendedor
    if (($userData['rol'] ?? '') !== 'administrador') {
        $id_vendedor = (int)$idUsuario;
    }
    
    // CORREGIDO: Validar campos requeridos - NIT ya no es obligatorio
    if (empty($empresa) || empty($contacto1) || empty($telefono1) || empty($region)) {
        echo json_encode(['error' => 'Los campos Empresa, Contacto 1, Teléfono 1 y Región son requeridos']);
        return;
    }

    // Convertir NIT vacío a NULL para consistencia
    if (empty($nit) || $nit === '0') {
        $nit = null;
    }

    // MODIFICADO: SQL para insertar el cliente con direccion2
    $sql = "INSERT INTO clientes (empresa, nit, contacto1, telefono1, contacto2, telefono2, 
            email, direccion, direccion2, region, id_vendedor, estado, fecha_registro)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURDATE())";
            
    $stmt = $conexion->prepare($sql);
    
    // Verificar si la preparación fue exitosa
    if (!$stmt) {
        echo json_encode(['error' => 'Error al preparar la consulta: ' . $conexion->error]);
        return;
    }
    
    // MODIFICADO: bind_param para 11 parámetros: 10 strings y 1 integer
    $stmt->bind_param("ssssssssssi", 
                      $empresa, 
                      $nit, 
                      $contacto1, 
                      $telefono1, 
                      $contacto2, 
                      $telefono2, 
                      $email, 
                      $direccion,
                      $direccion2, // AGREGADO: direccion2
                      $region,
                      $id_vendedor);

    if ($stmt->execute()) {
        echo json_encode(['success' => 'Cliente agregado correctamente']);
    } else {
        echo json_encode([
            'error' => 'Error al crear cliente: ' . $stmt->error,
            'sql_error' => $conexion->error
        ]);
    }

    $stmt->close();
}


function eliminarCliente() {
    global $conexion, $userData;
    
    // Añadir log para depuración
    error_log("Función eliminarCliente() invocada");
    
    // 1. Validar autenticación
    if (!$userData) {
        error_log("Error: Usuario no autenticado");
        echo json_encode(['error' => 'Usuario no autenticado']);
        return;
    }

    // 2. Validar permisos de administrador - SOLO ADMINISTRADORES PUEDEN ELIMINAR
    $rolUsuario = $userData['rol'] ?? '';
    $idUsuario = $userData['id'] ?? 0;
    
    error_log("Rol de usuario: " . $rolUsuario . ", ID: " . $idUsuario);
    
    if ($rolUsuario !== 'administrador') {
        error_log("Error: Usuario no es administrador. Rol: " . $rolUsuario);
        echo json_encode(['error' => 'No tienes permisos para eliminar clientes. Solo los administradores pueden eliminar.']);
        return;
    }

    // 3. Validar que se recibió un ID
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        error_log("Error: ID de cliente no especificado");
        echo json_encode(['error' => 'ID de cliente no especificado']);
        return;
    }

    $id = (int)$_POST['id'];
    error_log("ID de cliente a eliminar: " . $id);
    
    // 4. Verificar que el cliente existe y está activo
    $sqlVerificar = "SELECT id, empresa FROM clientes WHERE id = ? AND estado = 1";
    $stmtVerificar = $conexion->prepare($sqlVerificar);
    
    if (!$stmtVerificar) {
        error_log("Error al preparar consulta: " . $conexion->error);
        echo json_encode(['error' => 'Error al preparar consulta: ' . $conexion->error]);
        return;
    }
    
    $stmtVerificar->bind_param("i", $id);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();
    
    if ($resultadoVerificar->num_rows === 0) {
        error_log("Cliente no existe o ya ha sido eliminado. ID: " . $id);
        echo json_encode(['error' => 'El cliente no existe o ya ha sido eliminado']);
        return;
    }
    
    $clienteData = $resultadoVerificar->fetch_assoc();
    $stmtVerificar->close();
    
    error_log("Cliente encontrado: " . $clienteData['empresa']);

    // 5. Ejecutar la desactivación (cambiar estado a 0)
    $sql = "UPDATE clientes SET estado = 0 WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        error_log("Error al preparar consulta de actualización: " . $conexion->error);
        echo json_encode(['error' => 'Error al preparar consulta de actualización: ' . $conexion->error]);
        return;
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Verificar que se actualizó al menos un registro
        if ($stmt->affected_rows > 0) {
            error_log("Cliente eliminado correctamente. ID: " . $id);
            echo json_encode([
                'success' => 'Cliente "' . htmlspecialchars($clienteData['empresa']) . '" eliminado correctamente',
                'id' => $id
            ]);
        } else {
            error_log("No se pudo eliminar el cliente. No se actualizó ningún registro.");
            echo json_encode(['error' => 'No se pudo eliminar el cliente. No se actualizó ningún registro.']);
        }
    } else {
        error_log("Error al eliminar cliente: " . $stmt->error);
        echo json_encode(['error' => 'Error al eliminar cliente: ' . $stmt->error]);
    }

    $stmt->close();
}

// NUEVAS FUNCIONES PARA PERMISOS TEMPORALES
function obtenerVendedoresConAccesoClientes($idVendedorActual) {
    global $conexion;
    
    // Siempre incluir al vendedor actual
    $vendedoresAcceso = [$idVendedorActual];
    
    // Obtener vendedores cuyos clientes puede gestionar temporalmente
    $sql = "SELECT id_vendedor_propietario 
            FROM permisos_vendedores 
            WHERE id_vendedor_autorizado = ? AND estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idVendedorActual);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    while ($permiso = $resultado->fetch_assoc()) {
        $vendedoresAcceso[] = $permiso['id_vendedor_propietario'];
    }
    
    return array_unique($vendedoresAcceso);
}

function tienePermisoTemporalClientes($idVendedorAutorizado, $idVendedorPropietario) {
    global $conexion;
    
    $sql = "SELECT id FROM permisos_vendedores 
            WHERE id_vendedor_autorizado = ? 
            AND id_vendedor_propietario = ? 
            AND estado = 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $idVendedorAutorizado, $idVendedorPropietario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    return $resultado->num_rows > 0;
}
?>