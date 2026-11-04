<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriFluidos - Menú Principal</title>
    <!-- CSS Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/menu.css<?php echo getFileVersion('css/menu.css'); ?>">
   
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
    <a href="menu.php" class="menu-item active">
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
                <h4 class="mb-0">Dashboard</h4>
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

            <div class="row">
  <div class="col-md-6 col-lg-3 mb-4" id="tarjeta-clientes">
    <a href="clientes.php" class="text-decoration-none text-dark">
        <div class="dashboard-card">
            <div class="card-icon bg-gradient-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-info">
                <p>Total Clientes</p>
                <h4></h4>
            </div>
        </div>
    </a>
</div>
    
    <!-- Tarjeta Productos -->
<div class="col-md-6 col-lg-3 mb-4" id="tarjeta-productos">
    <a href="productos.php" class="text-decoration-none text-dark">
        <div class="dashboard-card">
            <div class="card-icon bg-gradient-success">
                <i class="fas fa-box"></i>
            </div>
            <div class="card-info">
                <p>Total Productos</p>
                <h4></h4>
            </div>
        </div>
    </a>
</div>

<!-- Tarjeta Ventas -->
<div class="col-md-6 col-lg-3 mb-4" id="tarjeta-ventas">
    <a href="ventas.php" class="text-decoration-none text-dark">
        <div class="dashboard-card">
            <div class="card-icon bg-gradient-warning">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="card-info">
                <p>Ventas del Mes</p>
                <h4></h4>
            </div>
        </div>
    </a>
</div>

<!-- Stock Bajo -->
    <div class="col-md-6 col-lg-3 mb-4" id="tarjeta-stock-bajo">
    <a href="productos.php" class="text-decoration-none text-dark">
        <div class="dashboard-card">
            <div class="card-icon bg-gradient-danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="card-info">
                <p>Productos en Stock Bajo</p>
                <h4></h4>
            </div>
        </div>
    </a>
</div>

<!-- Tarjeta Pedidos -->
<div class="col-md-6 col-lg-3 mb-4" id="tarjeta-pedidos">
    <a href="pedidos.php" class="text-decoration-none text-dark">
        <div class="dashboard-card">
            <div class="card-icon bg-gradient-primary">
                <i class="fas fa-box"></i>
            </div>
            <div class="card-info">
                <p>Pedidos Pendientes</p>
                <h4></h4> 
            </div>
        </div>
    </a>
</div>

</div>

       <!--
            <div class="row">
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actividad Reciente</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-shopping-cart text-primary me-2"></i>
                                        <span>Nueva venta registrada</span>
                                    </div>
                                    <small class="text-muted">Hace 10 minutos</small>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-user-plus text-success me-2"></i>
                                        <span>Nuevo cliente agregado</span>
                                    </div>
                                    <small class="text-muted">Hace 2 horas</small>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-truck text-warning me-2"></i>
                                        <span>Productos recibidos</span>
                                    </div>
                                    <small class="text-muted">Hace 5 horas</small>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-money-bill-wave text-info me-2"></i>
                                        <span>Pago recibido</span>
                                    </div>
                                    <small class="text-muted">Hace 1 día</small>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-box text-danger me-2"></i>
                                        <span>Stock bajo de producto</span>
                                    </div>
                                    <small class="text-muted">Hace 2 días</small>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Acceso Rápido</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="ventas.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Nueva Venta
                                </a>
                                <a href="clientes.php" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>Nuevo Cliente
                                </a>
                                <a href="productos.php" class="btn btn-warning text-white">
                                    <i class="fas fa-box me-2"></i>Nuevo Producto
                                </a>
                                <a href="abonos.php" class="btn btn-info text-white">
                                    <i class="fas fa-money-bill-wave me-2"></i>Registrar Pago
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        -->
            
        </div>
    </div>
<!--AQUI BORRE CODIGO-->
    <!-- JavaScript personalizado con versioning -->
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>

    <!-- JavaScript Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript personalizado con versioning -->
    <script src="js/menu.js<?php echo getFileVersion('js/menu.js'); ?>"></script>
    
</body>
</html>