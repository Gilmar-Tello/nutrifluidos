<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Gestión de clientes</title>
    <!-- CSS externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/clientes.css<?php echo getFileVersion('css/clientes.css'); ?>">
   
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
    <a href="clientes.php" class="menu-item active">
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
            <!-- Top Bar -->
            <div class="top-bar">
                <h4 class="mb-0">Gestión de clientes</h4>
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
                    <h5 class="mb-0">Lista de clientes</h5>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                            <i class="fas fa-plus me-2"></i>Nuevo cliente
                        </button>
                        <button id="btnGestionarPermisos" class="btn btn-warning ms-2" data-bs-toggle="modal" data-bs-target="#modalPermisos" style="display: none;">
                            <i class="fas fa-key me-2"></i>Gestionar Permisos
                        </button>
                        
                        <button id="btnGenerarReporte" class="btn btn-success ms-2" style="display: none;">
                            <i class="fas fa-file-pdf me-2"></i>Generar Reporte
                        </button>
                        
                    </div>
                </div>
                <div class="card-body">
                    <div id="customSearchContainer" class="mb-3"></div>

                        <div class="table-responsive">
                            <table id="tablaClientes" class="table table-striped table-hover">
                                <thead>
    <tr>
        <th>Empresa</th>
        <th>NIT</th>
        <th>Contacto 1</th>
        <th>Teléfono 1</th>
        <th>Contacto 2</th>
        <th>Teléfono 2</th>
        <th>Email</th>
        <th>Dirección 1</th>
        <th>Dirección 2</th> <!-- ✅ AGREGAR ESTA COLUMNA -->
        <th>Región</th>
        <th>Vendedor</th>
        <th>Acciones</th>
    </tr>
</thead>
                                <tbody id="tbodyClientes">
                                </tbody>
                            </table>
                </div>
            </div>

           <!-- Modal Nuevo Cliente -->
           <div class="modal fade" id="modalCliente" tabindex="-1" aria-labelledby="modalClienteLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalClienteLabel">Nuevo Cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formCliente">
                            <input type="hidden" id="clienteId">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="empresaCliente" class="form-label">Empresa*</label>
                                    <input type="text" class="form-control" id="empresaCliente" required>
                                </div>
                                <div class="col-md-6">
        <!-- ✅ QUITAR required del NIT -->
        <label for="nitCliente" class="form-label">NIT</label>
        <input type="text" class="form-control" id="nitCliente" placeholder="Opcional - Ej: 1234567-K">
    </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contacto1Cliente" class="form-label">Contacto 1*</label>
                                    <input type="text" class="form-control" id="contacto1Cliente" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono1Cliente" class="form-label">Teléfono 1*</label>
                                    <input type="tel" class="form-control" id="telefono1Cliente" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contacto2Cliente" class="form-label">Contacto 2</label>
                                    <input type="text" class="form-control" id="contacto2Cliente" name="contacto2">
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono2Cliente" class="form-label">Teléfono 2</label>
                                    <input type="tel" class="form-control" id="telefono2Cliente" name="telefono2">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="emailCliente" class="form-label">Correo Electrónico</label>
                                    <input type="email" class="form-control" id="emailCliente" name="email">
                                </div>
                                <div class="col-md-6">
                                    <label for="direccionCliente" class="form-label">Dirección 1</label>
                                    <input type="text" class="form-control" id="direccionCliente" name="direccion">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="direccion2Cliente" class="form-label">Dirección 2</label>
                                    <input type="text" class="form-control" id="direccion2Cliente" name="direccion2">
                                </div>
                                <!-- NUEVO CAMPO REGIÓN -->
                                <div class="col-md-6">
                                    <label for="regionCliente" class="form-label">Región*</label>
                                    <select class="form-control" id="regionCliente" name="region" required>
                                        <option value="">Seleccionar región...</option>
                                        <option value="Metropolitana">Metropolitana</option>
                                        <option value="Norte">Norte</option>
                                        <option value="Nororiente">Nororiente</option>
                                        <option value="Suroriente">Suroriente</option>
                                        <option value="Central">Central</option>
                                        <option value="Suroccidente">Suroccidente</option>
                                        <option value="Noroccidente">Noroccidente</option>
                                        <option value="Petén">Petén</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" id="guardarCliente" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </div>
        </div>

            <!-- Modal para editar cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" aria-labelledby="modalEditarClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formEditarCliente">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarClienteLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editClienteId" name="id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editClienteEmpresa" class="form-label">Empresa</label>
                            <input type="text" class="form-control" id="editClienteEmpresa" name="empresa" required>
                        </div>
                        <div class="col-md-6">
        <!-- ✅ QUITAR required del NIT -->
        <label for="editClienteNit" class="form-label">NIT</label>
        <input type="text" class="form-control" id="editClienteNit" name="nit" placeholder="Opcional">
    </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editClienteContacto1" class="form-label">Contacto 1</label>
                            <input type="text" class="form-control" id="editClienteContacto1" name="contacto1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editClienteTelefono1" class="form-label">Teléfono 1</label>
                            <input type="text" class="form-control" id="editClienteTelefono1" name="telefono1" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editClienteContacto2" class="form-label">Contacto 2</label>
                            <input type="text" class="form-control" id="editClienteContacto2" name="contacto2">
                        </div>
                        <div class="col-md-6">
                            <label for="editClienteTelefono2" class="form-label">Teléfono 2</label>
                            <input type="text" class="form-control" id="editClienteTelefono2" name="telefono2">
                        </div>
                        <div class="col-md-6">
                            <label for="editClienteEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editClienteEmail" name="email">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="editClienteDireccion" class="form-label">Dirección 1</label>
                            <input type="text" class="form-control" id="editClienteDireccion" name="direccion">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="editClienteDireccion2" class="form-label">Dirección 2</label>
                            <input type="text" class="form-control" id="editClienteDireccion2" name="direccion2">
                        </div>
                        <!-- NUEVO CAMPO REGIÓN PARA EDITAR -->
                        <div class="col-md-6">
                            <label for="editClienteRegion" class="form-label">Región</label>
                            <select class="form-control" id="editClienteRegion" name="region" required>
                                <option value="">Seleccionar región...</option>
                                <option value="Metropolitana">Metropolitana</option>
                                <option value="Norte">Norte</option>
                                <option value="Nororiente">Nororiente</option>
                                <option value="Suroriente">Suroriente</option>
                                <option value="Central">Central</option>
                                <option value="Suroccidente">Suroccidente</option>
                                <option value="Noroccidente">Noroccidente</option>
                                <option value="Petén">Petén</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarCambiosCliente">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

            <!-- Modal para eliminar cliente -->
            <div class="modal fade" id="modalEliminarCliente" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Confirmar Eliminación</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p>¿Está seguro que desea eliminar al cliente <strong id="nombreClienteEliminar"></strong>?</p>
                            <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Esta acción no se puede deshacer.</p>
                            <input type="hidden" id="idClienteEliminar">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-danger" id="btnConfirmarEliminarCliente">
                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para gestionar permisos -->
            <div class="modal fade" id="modalPermisos" tabindex="-1" aria-labelledby="modalPermisosLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalPermisosLabel">
                                <i class="fas fa-key me-2"></i>Gestión de Permisos Temporales
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Tabs para organizar mejor -->
                            <ul class="nav nav-tabs" id="permisosTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="conceder-tab" data-bs-toggle="tab" data-bs-target="#conceder" type="button" role="tab">
                                        <i class="fas fa-plus-circle me-1"></i>Conceder Permiso
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="listar-tab" data-bs-toggle="tab" data-bs-target="#listar" type="button" role="tab">
                                        <i class="fas fa-list me-1"></i>Permisos Activos
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="permisosTabContent">
                                <!-- Tab para conceder permisos -->
                                <div class="tab-pane fade show active" id="conceder" role="tabpanel">
                                    <div class="mt-3">
                                        <form id="formConcederPermiso">
                                            <!-- Búsqueda de cliente -->
                                            <div class="row mb-4">
                                                <div class="col-md-12">
                                                    <label for="buscarCliente" class="form-label">
                                                        <i class="fas fa-search me-1"></i>Buscar cliente*
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="buscarCliente" 
                                                               placeholder="Escriba el nombre de la empresa o NIT del cliente..."
                                                               autocomplete="off">
                                                        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div id="resultadosBusqueda" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;">
                                                        <!-- Resultados de búsqueda aparecerán aquí -->
                                                    </div>
                                                    <small class="form-text text-muted">Busque y seleccione el cliente que necesita ser gestionado por otro vendedor</small>
                                                </div>
                                            </div>

                                            <!-- Cliente seleccionado -->
                                            <div id="clienteSeleccionado" class="row mb-4" style="display: none;">
                                                <div class="col-md-12">
                                                    <div class="card border-info">
                                                        <div class="card-body">
                                                            <h6 class="card-title text-info">
                                                                <i class="fas fa-building me-2"></i>Cliente Seleccionado
                                                            </h6>
                                                            <div class="row">
                                                                <div class="col-md-8">
                                                                    <p class="mb-1"><strong id="nombreEmpresaSeleccionada">-</strong></p>
                                                                    <p class="mb-1 text-muted">NIT: <span id="nitSeleccionado">-</span></p>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <p class="mb-1"><small class="text-muted">Vendedor actual:</small></p>
                                                                    <p class="mb-0"><strong id="vendedorActualSeleccionado">-</strong></p>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" id="clienteSeleccionadoId">
                                                            <input type="hidden" id="vendedorPropietarioId">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- BÚSQUEDA DE VENDEDOR -->
                                            <div class="row mb-4">
                                                <div class="col-md-12">
                                                    <label for="buscarVendedor" class="form-label">
                                                        <i class="fas fa-user-search me-1"></i>Buscar vendedor que gestionará el cliente*
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="buscarVendedor" 
                                                               placeholder="Escriba el nombre del vendedor..."
                                                               autocomplete="off" disabled>
                                                        <button class="btn btn-outline-secondary" type="button" id="btnLimpiarVendedor" disabled>
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div id="resultadosBusquedaVendedor" class="list-group mt-2" style="display: none; max-height: 200px; overflow-y: auto;">
                                                        <!-- Resultados de búsqueda de vendedores aparecerán aquí -->
                                                    </div>
                                                    <small class="form-text text-muted">Busque y seleccione el vendedor que podrá gestionar este cliente</small>
                                                </div>
                                            </div>

                                            <!-- Vendedor seleccionado -->
                                            <div id="vendedorSeleccionado" class="row mb-4" style="display: none;">
                                                <div class="col-md-12">
                                                    <div class="card border-success">
                                                        <div class="card-body">
                                                            <h6 class="card-title text-success">
                                                                <i class="fas fa-user-check me-2"></i>Vendedor Autorizado
                                                            </h6>
                                                            <div class="row">
                                                                <div class="col-md-8">
                                                                    <p class="mb-1"><strong id="nombreVendedorSeleccionado">-</strong></p>
                                                                    <p class="mb-1 text-muted">Email: <span id="emailVendedorSeleccionado">-</span></p>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <p class="mb-1"><small class="text-muted">Podrá gestionar clientes de:</small></p>
                                                                    <p class="mb-0"><strong id="propietarioParaVendedor">-</strong></p>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" id="vendedorSeleccionadoId">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="motivoPermiso" class="form-label">
                                                        <i class="fas fa-comment me-1"></i>Motivo del permiso*
                                                    </label>
                                                    <input type="text" class="form-control" id="motivoPermiso" required 
                                                           placeholder="Ej: Cobertura durante vacaciones, Apoyo temporal, etc.">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="observacionesPermiso" class="form-label">
                                                        <i class="fas fa-sticky-note me-1"></i>Observaciones adicionales
                                                    </label>
                                                    <textarea class="form-control" id="observacionesPermiso" rows="3" 
                                                              placeholder="Detalles adicionales sobre el permiso..."></textarea>
                                                </div>
                                            </div>
                                            
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-success" id="btnConcederPermiso" disabled>
                                                    <i class="fas fa-check me-2"></i>Conceder Permiso
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Tab para listar permisos activos -->
                                <div class="tab-pane fade" id="listar" role="tabpanel">
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6>Permisos Activos</h6>
                                            <button class="btn btn-outline-primary btn-sm" id="btnActualizarPermisos">
                                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                                            </button>
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped" id="tablaPermisos">
                                                <thead>
                                                    <tr>
                                                        <th>Vendedor Autorizado</th>
                                                        <th>Puede gestionar clientes de</th>
                                                        <th>Motivo</th>
                                                        <th>Fecha Concesión</th>
                                                        <th>Administrador</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbodyPermisos">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">
                                                            <i class="fas fa-hourglass-half me-2"></i>Cargando permisos...
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript con versioning -->
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/clientes.js<?php echo getFileVersion('js/clientes.js'); ?>"></script>
</body>
</html>