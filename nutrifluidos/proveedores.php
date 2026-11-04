<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Gestión de proveedores</title>
    <!-- CSS externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/proveedores.css<?php echo getFileVersion('css/proveedores.css'); ?>">
   
    
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
    <a href="proveedores.php" class="menu-item active">
        <i class="fas fa-truck"></i>
        <span class="sidebar-text">Proveedores</span>
    </a>

    <!-- Usuarios -->
    <a href="usuarios.php" class="menu-item">
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
                <h4 class="mb-0">Gestión de Proveedores</h4>
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
            <div id="alertaContainer" class="container mt-3"></div>

            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lista de Proveedores</h5>
                <div class="d-flex gap-2">
                    <a href="php/exporta_proveedores.php" target="_blank" class="btn btn-success">
                        <i class="fa fa-file-pdf me-2"></i>Exportar PDF
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProveedor">
                        <i class="fas fa-plus me-2"></i>Nuevo Proveedor
                    </button>
                </div>
            </div>

                <div class="card-body">
                    <div id="customSearchContainer" class="mb-2"></div> <!-- MOVER AQUÍ -->
                    <div class="table-responsive">
                        <table id="tablaProveedores" class="table table-striped table-hover">
                            <thead>
                                <tr>

                    <th>Empresa</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Contacto 2</th>
                    <th>Teléfono 2</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th>País de Origen</th>
                    <!--<th>Estado</th> -->
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyProveedores">
                                <!-- Aquí se insertarán los proveedores con JS -->
                            </tbody>
                            
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Nuevo Proveedor -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProveedorLabel">Nuevo Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formProveedor">
                    <input type="hidden" id="proveedorId" name="proveedorId">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="empresaProveedor" class="form-label">Empresa</label>
                            <input type="text" class="form-control" id="empresaProveedor" name="empresaProveedor" required>
                        </div>
                        <div class="col-md-6">
                            <label for="correoProveedor" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correoProveedor" name="correoProveedor" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombreProveedor" class="form-label">Nombre del Proveedor</label>
                            <input type="text" class="form-control" id="nombreProveedor" name="nombreProveedor" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefonoProveedor" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefonoProveedor" name="telefonoProveedor" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombreProveedor2" class="form-label">Nombre del Proveedor 2</label>
                            <input type="text" class="form-control" id="nombreProveedor2" name="nombreProveedor2">
                        </div>
                        <div class="col-md-6">
                            <label for="telefonoProveedor2" class="form-label">Teléfono 2</label>
                            <input type="text" class="form-control" id="telefonoProveedor2" name="telefonoProveedor2">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                           <label for="direccionProveedor" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccionProveedor" name="direccionProveedor" required>
                        </div>
                        <div class="col-md-6">
                            <label for="ciudadOrigen" class="form-label">Ciudad de Origen</label>
                            <input type="text" class="form-control" id="ciudadOrigen" name="ciudadOrigen"  required>
                        </div>
                    </div>
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarProveedor">Guardar</button>
            </div>
        </div>
    </div>
</div>



<!-- Modal para editar proveedor -->
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-labelledby="modalEditarProveedorLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarProveedorLabel">Editar Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarProveedor">
                    <input type="hidden" id="editProveedorId" name="id">

                    <div class="mb-3">
                        <label for="editProveedorEmpresa" class="form-label">Empresa</label>
                        <input type="text" class="form-control" id="editProveedorEmpresa" name="empresa" required>
                    </div>

                    <div class="mb-3">
                        <label for="editProveedorContacto" class="form-label">Contacto</label>
                        <input type="text" class="form-control" id="editProveedorContacto" name="contacto" required>
                    </div>

                    <div class="mb-3">
                        <label for="editProveedorTelefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="editProveedorTelefono" name="telefono" required>
                    </div>
                    
                        <div class="mb-3">
                        <label for="editProveedorContacto" class="form-label">Contacto 2</label>
                        <input type="text" class="form-control" id="editProveedorContacto2" name="contacto2">
                    </div>

                    <div class="mb-3">
                        <label for="editProveedorTelefono" class="form-label">Teléfono 2</label>
                        <input type="text" class="form-control" id="editProveedorTelefono2" name="telefono2">
                    </div>

                    <div class="mb-3">
                        <label for="editProveedorEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editProveedorEmail" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="editProveedorDireccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="editProveedorDireccion" name="direccion" required>
                    </div>

                    <div class="mb-3">
                        <label for="editProveedorPais" class="form-label">País de Origen</label>
                        <input type="text" class="form-control" id="editProveedorPais" name="pais_origen" required>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnGuardarCambios">Guardar Cambios</button>
            </div>
        </form>
        </div>
    </div>
</div>



<div class="modal fade" id="modalEliminarProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar al proveedor <strong id="nombreProveedorEliminar"></strong>?</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Esta acción no se puede deshacer.</p>
                <input type="hidden" id="idProveedorEliminar">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminarProveedor">
                    <i class="fas fa-trash-alt me-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <!-- JavaScript Bootstrap y jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- JavaScript personalizado -->
    <script src="js/proveedores.js<?php echo getFileVersion('js/proveedores.js'); ?>"></script>

</body>
</html>