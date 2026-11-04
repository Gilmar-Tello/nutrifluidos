<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Gestión de Usuarios</title>
    <!-- CSS externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/usuarios.css<?php echo getFileVersion('css/usuarios.css'); ?>">
    
    
    
        <script>
        // Verificar sesión y evitar retroceso
        const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
        const userData = localStorage.getItem('userData');

        if (!isLoggedIn || !userData) {
            window.location.href = '/index.php';
        }

        window.history.forward();
        window.onunload = () => null;
    </script>
    
    <!-- icono pestaña -->
    <link rel="icon" href="imagenes/nutrifluidos.jpg" type="image/x-icon">
    <link rel="shortcut icon" href="imagenes/nutrifluidos.jpg" type="image/x-icon">
    <link rel="apple-touch-icon" href="imagenes/nutrifluidos.jpg">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-container">
                <div class="sidebar-header">
                    <img src="imagenes/nutrifluidos.jpg" alt="Agrofluidos Logo" class="sidebar-logo">
                    <h6 class="mt-2 mb-0">Sistema de Punto de Venta</h6>
                </div>
                <div class="sidebar-menu">
    <!-- Dashboard -->
    <a href="menu.php" class="menu-item">
        <i class="fas fa-tachometer-alt"></i>
        <span class="sidebar-text">Dashboard</span>
    </a>

    <!-- Ventas -->
    <a href="ventas.php" class="menu-item">
        <i class="fas fa-shopping-cart"></i>
        <span class="sidebar-text">Ventas</span>
    </a>

    <!-- Productos -->
    <a href="productos.php" class="menu-item">
        <i class="fas fa-box"></i>
        <span class="sidebar-text">Productos</span>
    </a>

    <!-- Kardex -->
    <a href="kardex.php" class="menu-item">
        <i class="fas fa-chart-line"></i>
        <span class="sidebar-text">Ver Kardex</span>
    </a>

    <!-- Clientes -->
    <a href="clientes.php" class="menu-item">
        <i class="fas fa-user-tie"></i>
        <span class="sidebar-text">Clientes</span>
    </a>

    <!-- Pedidos -->
    <a href="pedidos.php" class="menu-item">
        <i class="fas fa-clipboard-list"></i>
        <span class="sidebar-text">Pedidos</span>
    </a>

    <!-- Abonos -->
    <a href="abonos.php" class="menu-item">
        <i class="fas fa-money-bill-wave"></i>
        <span class="sidebar-text">Abonos</span>
    </a>

    <!-- Facturas por Pagar -->
    <a href="calculadora.php" class="menu-item">
        <i class="fas fa-file-invoice-dollar"></i>
        <span class="sidebar-text">Facturas por Pagar</span>
    </a>

    <!-- Proveedores -->
    <a href="proveedores.php" class="menu-item">
        <i class="fas fa-truck"></i>
        <span class="sidebar-text">Proveedores</span>
    </a>

    <!-- Usuarios -->
    <a href="usuarios.php" class="menu-item active">
        <i class="fas fa-users"></i>
        <span class="sidebar-text">Usuarios</span>
    </a>

    <!-- Informes -->
    <a href="informes-ventas.php" class="menu-item">
        <i class="fas fa-chart-bar"></i>
        <span class="sidebar-text">Informes</span>
    </a>
</div>

                <div class="logout-section mt-auto">
                    <a href="/index.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="sidebar-text">Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h4 class="mb-0">Gestión de Usuarios</h4>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="welcome-message">
                        <h6>Bienvenido, Admin</h6>
                        <span>admin@agrofluidos.com</span>
                    </div>
                </div>
            </div>

            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Usuarios</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarUsuario">
                    <i class="fas fa-plus me-2"></i>Nuevo Usuario
                    </button>
            </div>
    <div id="customSearchContainer" class="mt-3"></div>
</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaUsuarios" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th class="d-none">ID</th>
                                    <th>Nombre Completo</th>
                                    <th>Teléfono</th>
                                    <th>Correo Electrónico</th>
                                    <th>Dirección</th>
                                    <th>Usuario</th>
                                    <th>Contraseña</th>
                                    <th></th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyUsuarios">
                                <!--usuarios desde js-->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Usuario -->
    <div class="modal fade" id="modalAgregarUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formUsuario">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nombreUsuario" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombreUsuario" required>
                            </div>
                            <div class="col-md-6">
                                <label for="telefonoUsuario" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefonoUsuario" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="correoUsuario" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="correoUsuario" required>
                            </div>
                            <div class="col-md-6">
                                <label for="direccionUsuario" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccionUsuario" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="usuarioUsuario" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="usuarioUsuario" required>
                            </div>
                            <div class="col-md-6">
                            <label for="contrasenaUsuario" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="contrasenaUsuario" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordNuevoUsuario">
                                    <i class="fa fa-eye" id="iconoOjoNuevoUsuario"></i>

                                </button>
                            </div>
                        </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="rolUsuario" class="form-label">Rol</label>
                                <select class="form-select" id="rolUsuario" required>
                                    <option value="">Seleccione un rol</option>
                                    <option value="administrador">Administrador</option>
                                    <option value="bodeguero">Bodeguero</option>
                                    <option value="vendedor">Vendedor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="estadoUsuario" class="form-label">Estado</label>
                                <select class="form-select" id="estadoUsuario" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="guardarUsuario">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarUsuarioLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarUsuario">
                        <input type="hidden" id="editUsuarioId">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editUsuarioNombreCompleto" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="editUsuarioNombreCompleto" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editUsuarioTelefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="editUsuarioTelefono" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editUsuarioCorreo" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="editUsuarioCorreo" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editUsuarioDireccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="editUsuarioDireccion" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editUsuarioUsuario" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="editUsuarioUsuario" required>
                            </div>
                            <div class="col-md-6">
                        <label for="editUsuarioContrasena" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="editUsuarioContrasena" placeholder="Dejar en blanco para mantener la actual">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fa fa-eye" id="iconoOjo"></i>
                            </button>
                        </div>
                    </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editUsuarioRol" class="form-label">Rol</label>
                                <select class="form-select" id="editUsuarioRol" required>
                                    <option value="administrador">Administrador</option>
                                    <option value="bodeguero">Bodeguero</option>
                                    <option value="vendedor">Vendedor</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editUsuarioEstado" class="form-label">Estado</label>
                                <select class="form-select" id="editUsuarioEstado" required>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnGuardarCambiosUsuario">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Usuario -->
    <div class="modal fade" id="modalEliminarUsuario" tabindex="-1" aria-labelledby="modalEliminarUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarUsuarioLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar al usuario <strong id="nombreUsuarioEliminar"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                    <input type="hidden" id="idUsuarioEliminar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminarUsuario">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
    


    <!-- JavaScript Bootstrap y jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="js/usuarios.js<?php echo getFileVersion('js/usuarios.js'); ?>"></script>

</body>
</html>