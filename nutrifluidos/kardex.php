<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Kardex de Inventario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/kardex.css<?php echo getFileVersion('css/kardex.css'); ?>">

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
    <a href="kardex.php" class="menu-item active">
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
                <h4 class="mb-0">Kardex de Inventario</h4>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="welcome-message">
                        <h6 id="topbarNombreUsuario">Bienvenido, Admin</h6>
                        <span id="topbarEmailUsuario">admin@agrofluidos.com</span>
                    </div>
                </div>
            </div>

            <!-- Buscador -->
            <div class="filter-card">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-3">
                        <label for="buscadorProducto" class="form-label">Buscar Producto</label>
                        <div id="buscadorProductoWrapper">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="buscadorProducto"
                                       placeholder="Escribe el nombre o código del producto...">
                            </div>
                            <div id="resultadosBusquedaProducto"></div>
                        </div>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btnVerGlobal">
                            <i class="fas fa-globe me-2"></i>Ver Todos
                        </button>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="button" class="btn btn-primary w-100" id="btnExportarPDF">
                            <i class="fas fa-file-pdf me-2"></i>Exportar PDF
                        </button>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-12">
                        <span class="badge bg-primary badge-modo-vista" id="badgeModoVista">
                            <i class="fas fa-globe me-1"></i>Vista global: todos los productos
                        </span>
                    </div>
                </div>
            </div>

            <!-- Encabezado del producto seleccionado (oculto en vista global) -->
            <div class="card mb-4" id="cardProductoInfo" style="display:none;">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-1" id="productoInfoNombre">-</h5>
                        <small class="text-muted" id="productoInfoCodigo">-</small>
                    </div>
                    <div class="text-end">
                        <div class="stat-label">Stock Actual</div>
                        <div class="stat-value" id="productoInfoStock">0</div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Resumen -->
            <div class="row mb-4" id="estadisticasResumen">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-primary">
                            <i class="fas fa-tag" id="iconStat1"></i>
                        </div>
                        <div class="stat-value" id="statValor1">Q0.00</div>
                        <div class="stat-label" id="statLabel1">Precio Actual</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-success">
                            <i class="fas fa-chart-line" id="iconStat2"></i>
                        </div>
                        <div class="stat-value" id="statValor2">Q0.00</div>
                        <div class="stat-label" id="statLabel2">Precio Promedio</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-info">
                            <i class="fas fa-arrow-down" id="iconStat3"></i>
                        </div>
                        <div class="stat-value" id="statValor3">Q0.00</div>
                        <div class="stat-label" id="statLabel3">Precio Mínimo</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-warning">
                            <i class="fas fa-arrow-up" id="iconStat4"></i>
                        </div>
                        <div class="stat-value" id="statValor4">Q0.00</div>
                        <div class="stat-label" id="statLabel4">Precio Máximo</div>
                    </div>
                </div>
            </div>

            <!-- Gráficas -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0" id="tituloGraficaPrecio"><i class="fas fa-chart-line me-2"></i>Evolución del Costo de Compra</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="graficaPrecio"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0" id="tituloGraficaMovimiento"><i class="fas fa-boxes me-2"></i>Movimiento de Inventario</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="graficaMovimiento"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de movimientos -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="tituloTablaMovimientos">Movimientos Recientes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaKardex" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th id="thProducto">Producto</th>
                                    <th>Operación</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Costo Total</th>
                                    <th>Stock Resultante</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyKardex">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/kardex.js<?php echo getFileVersion('js/kardex.js'); ?>"></script>
</body>
</html>
