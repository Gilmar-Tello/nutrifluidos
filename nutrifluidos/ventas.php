<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Gestión de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/ventas.css<?php echo getFileVersion('css/ventas.css'); ?>">
    
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
    <a href="ventas.php" class="menu-item active">
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
                <h4 class="mb-0">Gestión de Ventas</h4>
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

            <div class="row mb-4">
                <div class="col-md-12">
                    <ul class="nav nav-tabs" id="ventasTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="nueva-venta-tab" data-bs-toggle="tab" data-bs-target="#nueva-venta" type="button" role="tab" aria-controls="nueva-venta" aria-selected="true">Nueva Venta</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-controls="historial" aria-selected="false">Historial de Ventas</button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="tab-content" id="ventasTabsContent">
                <!-- Pestaña Nueva Venta -->
                <div class="tab-pane fade show active" id="nueva-venta" role="tabpanel" aria-labelledby="nueva-venta-tab">
                    <div class="two-columns">
                        <!-- Columna Izquierda - Productos -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Datos de la Venta</h5>
                            </div>
                            <div class="card-body">
                                <form id="formVenta">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="fechaVenta" class="form-label">Fecha</label>
                                            <input type="date" class="form-control" id="fechaVenta" value="" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="tipoVenta" class="form-label">Tipo de Venta</label>
                                            <select class="form-select" id="tipoVenta" required>
                                                <option value="contado">Contado</option>
                                                <option value="credito" selected>Crédito</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="dteVenta" class="form-label">DTE (Documento Tributario Electrónico)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-file-invoice"></i>
                                            </span>
                                            <input type="text" class="form-control" id="dteVenta" placeholder="Ingrese el número de DTE" maxlength="10">
                                            <span class="input-group-text text-muted">
                                                <small>Opcional</small>
                                            </span>
                                        </div>
                                        <div class="form-text">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i>
                                                Máximo 10 caracteres. Este campo es opcional y puede ser agregado posteriormente.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- NUEVO CAMPO: Orden de Compra -->
<div class="row mb-3">
    <div class="col-md-12">
        <label for="ordenCompra" class="form-label">Orden de Compra</label>
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-clipboard-list"></i>
            </span>
            <input type="text" class="form-control" id="ordenCompra" placeholder="Ingrese el número de orden de compra" maxlength="15">
            <span class="input-group-text text-muted">
                <small>Opcional</small>
            </span>
        </div>
        <div class="form-text">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Máximo 15 caracteres. Este campo es opcional y puede ser agregado posteriormente.
            </small>
        </div>
    </div>
</div>
                                    
                                    <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="buscarCliente" class="form-label">Cliente</label>
                                        <div class="client-search">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="buscarCliente" placeholder="Nombre o NIT del cliente">
                                                <button class="btn btn-primary" type="button" id="btnBuscarCliente">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            <div class="client-suggestions d-none" id="clientSuggestions">
                                                <!-- Las sugerencias de clientes se cargarán dinámicamente -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <label for="buscarProducto" class="form-label">Buscar Producto</label>
                                            <div class="product-search">
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="buscarProducto" placeholder="Código o nombre del producto">
                                                    <button class="btn btn-primary" type="button" id="btnBuscarProducto">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                                <div class="product-suggestions d-none" id="productSuggestions">
                                                    <!-- Las sugerencias de productos se cargarán dinámicamente -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="cart-items mt-4">
                                        <table class="table table-sm">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Producto</th>
                                                    <th>Cantidad</th>
                                                    <th>Precio</th>
                                                    <th>Subtotal</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="carritoItems">
                                                <!-- Los items del carrito se cargarán dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Columna Derecha - Resumen -->
                        <div>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5 class="mb-0">Resumen de la Venta</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotalVenta">Q0.00</span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Descuento:</span>
                                        <div class="input-group input-group-sm" style="width: 120px;">
                                            <span class="input-group-text">Q</span>
                                            <input type="number" class="form-control" id="descuento" value="0" min="0">
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span id="totalVenta">Q0.00</span>
                                    </div>

                                    <div id="opcionesCredito" class="mt-3 d-none">
                                        <div class="mb-3">
                                            <label for="anticipo" class="form-label">Anticipo</label>
                                            <div class="input-group">
                                                <span class="input-group-text">Q</span>
                                                <input type="number" class="form-control" id="anticipo" value="0">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="plazoPago" class="form-label">Plazo de Pago (meses)</label>
                                            <input type="number" class="form-control" id="plazoPago" value="1" min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-primary" id="btnFinalizarVenta">
                                            <i class="fas fa-save me-2"></i>Finalizar Venta
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Información del Cliente</h5>
                                </div>
                                <div class="card-body">
                                    <div id="infoCliente">
                                        <!-- La información del cliente se cargará dinámicamente -->
                                        <p class="text-center text-muted">Seleccione un cliente para ver su información</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pestaña Historial de Ventas -->
<div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Historial de Ventas</h5>
            <div>
                <button class="btn btn-outline-primary" id="btnExportar">
                    <i class="fas fa-file-export me-2"></i>Exportar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
    <div class="col-md-3">
        <div class="input-group">
            <label class="input-group-text" for="tipoVentaBusqueda">Tipo:</label>
            <select class="form-select" id="tipoVentaBusqueda">
                <option value="">Todos</option>
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-user"></i></span>
            <input type="text" class="form-control" id="clienteBusqueda" placeholder="Nombre o NIT del cliente">
        </div>
    </div>
    <div class="col-md-2">
        <div class="input-group">
            <label class="input-group-text" for="fechaInicioBusqueda"><i class="fas fa-calendar"></i></label>
            <input type="date" class="form-control" id="fechaInicioBusqueda" title="Fecha de inicio">
        </div>
    </div>
    <div class="col-md-2">
        <div class="input-group">
            <label class="input-group-text" for="fechaFinBusqueda"><i class="fas fa-calendar"></i></label>
            <input type="date" class="form-control" id="fechaFinBusqueda" title="Fecha de fin">
        </div>
    </div>
    <div class="col-md-1">
        <button class="btn btn-primary w-100" id="btnBuscar">
            <i class="fas fa-search"></i>
        </button>
    </div>
    <div class="col-md-1">
        <button class="btn btn-secondary w-100" id="btnLimpiarFiltros" title="Limpiar filtros">
            <i class="fas fa-eraser"></i>
        </button>
    </div>
</div>

<!-- Agregar esta línea después del div anterior -->
<div class="row mb-2">
    <div class="col-md-12">
        <small class="text-muted">
            <i class="fas fa-info-circle"></i>
            <strong>Filtros de fecha:</strong> Selecciona fecha de inicio y/o fecha de fin para buscar ventas en un período específico. 
            Deja vacío para mostrar todas las ventas.
        </small>
    </div>
</div>
            <div class="table-responsive">
                <table id="tablaVentas" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No. Factura</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Vendedor</th>
                            <th>Tipo</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Las ventas se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle de Venta - Mantener esto en la parte principal del HTML -->
<div class="modal fade" id="modalDetalleVenta" tabindex="-1" aria-labelledby="modalDetalleVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleVentaLabel">Detalle de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Información de la Venta</h6>
                        <div id="infoVentaDetalle">
                            <!-- La información de la venta se cargará dinámicamente -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Totales</h6>
                        <div id="totalesVentaDetalle">
                            <!-- Los totales de la venta se cargarán dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <h6>Productos</h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="productosVentaDetalle">
                            <!-- Los productos de la venta se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnImprimirDetalleVenta">
                    <i class="fas fa-print me-2"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- JavaScript personalizado con versioning -->
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="js/ventas.js<?php echo getFileVersion('js/ventas.js'); ?>"></script>
</body>
</html>