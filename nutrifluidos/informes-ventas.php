<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Informes de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="css/informes-ventas.css<?php echo getFileVersion('css/informes-ventas.css'); ?>">
    
    
    
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
    <a href="usuarios.php" class="menu-item">
        <i class="fas fa-users"></i>
        <span class="sidebar-text">Usuarios</span>
    </a>

    <!-- Informes -->
    <a href="informes-ventas.php" class="menu-item active">
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
                <h4 class="mb-0">Informes de Ventas</h4>
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

            <!-- Filtros -->
            <div class="filter-card">
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label for="dateRange" class="form-label">Período de Tiempo</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <input type="text" class="form-control" id="dateRange" placeholder="Seleccione un período">
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="clienteFilter" class="form-label">Cliente</label>
                        <select class="form-select" id="clienteFilter">
                            <option value="">Todos los clientes</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="vendedorFilter" class="form-label">Vendedor</label>
                        <select class="form-select" id="vendedorFilter">
                            <option value="">Todos los vendedores</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="button" class="btn btn-primary w-100" id="btnFiltrar">
                            <i class="fas fa-filter me-2"></i>Filtrar
                        </button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12 text-end">
                        <div class="btn-group" role="group">
                            <!--<button type="button" class="btn btn-outline-secondary" id="btnExportExcel">
                                <i class="fas fa-file-excel me-2"></i>Excel
                            </button> -->
                            <button type="button" class="btn btn-outline-secondary" id="btnExportPDF">
                                <i class="fas fa-file-pdf me-2"></i>PDF
                            </button>
                            <!--<button type="button" class="btn btn-outline-secondary" id="btnImprimir">
                                <i class="fas fa-print me-2"></i>Imprimir
                            </button>-->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Resumen -->
            <div class="row mb-4" id="estadisticasResumen">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value" id="totalVentas">Q0.00</div>
                        <div class="stat-label">Ventas Totales</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-success">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-value" id="totalProductos">0</div>
                        <div class="stat-label">Productos Vendidos</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-warning">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-value" id="totalClientes">0</div>
                        <div class="stat-label">Clientes Atendidos</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon stat-info">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-value" id="crecimiento">+0%</div>
                        <div class="stat-label">vs Mes Anterior</div>
                    </div>
                </div>
            </div>

            <!-- Gráficas y Tablas -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-pills" id="informesTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="resumen-tab" data-bs-toggle="pill" data-bs-target="#resumen-content" type="button" role="tab" aria-controls="resumen-content" aria-selected="true">Resumen</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="productos-tab" data-bs-toggle="pill" data-bs-target="#productos-content" type="button" role="tab" aria-controls="productos-content" aria-selected="false">Productos</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="clientes-tab" data-bs-toggle="pill" data-bs-target="#clientes-content" type="button" role="tab" aria-controls="clientes-content" aria-selected="false">Clientes</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="anuladas-tab" data-bs-toggle="pill" data-bs-target="#anuladas-content" type="button" role="tab" aria-controls="anuladas-content" aria-selected="false">
                                <i class="fas fa-ban me-2"></i>Ventas Anuladas
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="informesTabContent">
                        <!-- Pestaña de Resumen -->
                        <div class="tab-pane fade show active" id="resumen-content" role="tabpanel" aria-labelledby="resumen-tab">
                            <div class="row">
                                <!-- Gráfico de ventas por mes -->
                                <div class="col-md-8 mb-4">
                                    <h5>Tendencia de Ventas</h5>
                                    <div class="chart-container">
                                        <canvas id="ventasMensualesChart"></canvas>
                                    </div>
                                </div>
                                <!-- Gráfico de distribución de ventas -->
                                <div class="col-md-4 mb-4">
                                    <h5>Distribución de Ventas</h5>
                                    <div class="chart-container">
                                        <canvas id="distribucionVentasChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mt-3">Detalle de Ventas del Período</h5>
                            <div class="table-responsive">
                                <table id="tablaVentasResumen" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Factura</th>
                                            <th>Cliente</th>
                                            <th>Vendedor</th>
                                            <th>Subtotal</th>
                                            <th>Descuento</th>
                                            <th>Total</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los datos se cargan dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pestaña de Productos -->
                        <div class="tab-pane fade" id="productos-content" role="tabpanel" aria-labelledby="productos-tab">
                            <div class="row">
                                <!-- Gráfico de productos más vendidos -->
                                <div class="col-md-6 mb-4">
                                    <h5>Productos Más Vendidos</h5>
                                    <div class="chart-container">
                                        <canvas id="productosMasVendidosChart"></canvas>
                                    </div>
                                </div>
                                <!-- Gráfico de ingresos por producto -->
                                <div class="col-md-6 mb-4">
                                    <h5>Ingresos por Producto</h5>
                                    <div class="chart-container">
                                        <canvas id="ingresosPorProductoChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mt-3">Detalle de Ventas por Producto</h5>
                            <div class="table-responsive">
                                <table id="tablaVentasProductos" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Cantidad Total</th>
                                            <th>Precio Promedio</th>
                                            <th>Ingreso Total</th>
                                            <th>% del Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los datos se cargan dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Pestaña de Clientes -->
                        <div class="tab-pane fade" id="clientes-content" role="tabpanel" aria-labelledby="clientes-tab">
                            <div class="row">
                                <!-- Gráfico de clientes por ventas -->
                                <div class="col-md-6 mb-4">
                                    <h5>Principales Clientes por Volumen</h5>
                                    <div class="chart-container">
                                        <canvas id="clientesVentasChart"></canvas>
                                    </div>
                                </div>
                                <!-- Gráfico de frecuencia de compra -->
                                <div class="col-md-6 mb-4">
                                    <h5>Frecuencia de Compra</h5>
                                    <div class="chart-container">
                                        <canvas id="frecuenciaCompraChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mt-3">Detalle de Ventas por Cliente</h5>
                            <div class="table-responsive">
                                <table id="tablaVentasClientes" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Total Compras</th>
                                            <th>Valor Total</th>
                                            <th>Ticket Promedio</th>
                                            <th>Última Compra</th>
                                            <th>Producto Favorito</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Los datos se cargan dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- REEMPLAZAR la pestaña de anuladas en tu HTML con este código corregido -->
<div class="tab-pane fade" id="anuladas-content" role="tabpanel" aria-labelledby="anuladas-tab">
    <!-- Estadísticas de anulaciones -->
    <div class="row mb-4" id="estadisticasAnuladas">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon stat-danger">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="stat-value" id="totalVentasAnuladas">0</div>
                <div class="stat-label">Ventas Anuladas</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon stat-warning">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-value" id="montoTotalAnulado">Q0.00</div>
                <div class="stat-label">Monto Total Perdido</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon stat-info">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-value" id="porcentajeAnuladas">0%</div>
                <div class="stat-label">% de Ventas Anuladas</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon stat-secondary">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value" id="tiempoPromedioAnulacion">0h</div>
                <div class="stat-label">Tiempo Promedio</div>
            </div>
        </div>
    </div>
    
    <!-- Gráficos de anulaciones -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                        Motivos de Anulación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="motivosAnulacionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2 text-danger"></i>
                        Tendencia de Anulaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="tendenciaAnulacionesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-times me-2 text-primary"></i>
                        Anulaciones por Usuario
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="usuariosAnulacionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-box-open me-2 text-purple"></i>
                        Productos Más Anulados
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="productosAnuladosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla detallada de anulaciones -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2 text-info"></i>
                        Detalle de Ventas Anuladas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaVentasAnuladas" class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha Anulación</th>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Motivo</th>
                                    <th>Anulada por</th>
                                    <th>Monto</th>
                                    <th>Tiempo Transcurrido</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargan dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para la pestaña de anuladas */
.stat-card {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    transition: transform 0.2s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
    margin-bottom: 1rem;
}

.stat-icon.stat-danger { background-color: #dc3545; }
.stat-icon.stat-warning { background-color: #ffc107; }
.stat-icon.stat-info { background-color: #0dcaf0; }
.stat-icon.stat-secondary { background-color: #6c757d; }
.stat-icon.stat-primary { background-color: #0d6efd; }

.stat-value {
    font-size: 1.75rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #2c3e50;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    font-weight: 500;
}

.chart-container {
    position: relative;
    width: 100%;
    min-height: 300px;
}

.card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.5rem;
}

.text-purple {
    color: #6f42c1 !important;
}

.badge {
    font-size: 0.75em;
    padding: 0.375rem 0.75rem;
    border-radius: 0.375rem;
}

.table-responsive {
    border-radius: 0.5rem;
    overflow: hidden;
}

.table thead th {
    border-top: none;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.table tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .chart-container {
        min-height: 250px;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>

    <!-- Modal Detalle de Venta -->
    <div class="modal fade" id="modalDetalleVenta" tabindex="-1" aria-labelledby="modalDetalleVentaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleVentaLabel">Detalle de Venta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetalleVentaBody">
                    <!-- El contenido se carga dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnImprimirDetalle">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle de Cliente -->
    <div class="modal fade" id="modalDetalleCliente" tabindex="-1" aria-labelledby="modalDetalleClienteLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleClienteLabel">Perfil de Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetalleClienteBody">
                    <!-- El contenido se carga dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary">
                        <i class="fas fa-file-export me-2"></i>Exportar Informe
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Anular Venta -->
<div class="modal fade" id="modalAnularVenta" tabindex="-1" aria-labelledby="modalAnularVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalAnularVentaLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Anular Venta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>¿Está seguro que desea anular esta venta?</strong>
                    <br>Esta acción no se puede deshacer y afectará:
                    <ul class="mb-0 mt-2">
                        <li>El stock de productos será restaurado</li>
                        <li>Los reportes e informes</li>
                        <li>Los pedidos asociados serán cancelados</li>
                        <li>Los créditos asociados serán cancelados</li>
                    </ul>
                </div>
                
                <div class="mb-3" id="infoVentaAnular">
                    <!-- Se llenará dinámicamente -->
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="motivoTipoAnulacion" class="form-label">Motivo principal *</label>
                        <select class="form-select" id="motivoTipoAnulacion" required>
                            <option value="">Seleccione un motivo</option>
                            <option value="Error en productos">Error en productos</option>
                            <option value="Error en precios">Error en precios</option>
                            <option value="Cliente se retracto">Cliente se retractó</option>
                            <option value="Error del vendedor">Error del vendedor</option>
                            <option value="Venta duplicada">Venta duplicada</option>
                            <option value="Producto defectuoso">Producto defectuoso</option>
                            <option value="Cancelacion por cliente">Cancelación por cliente</option>
                            <option value="Error administrativo">Error administrativo</option>
                            <option value="Otro motivo">Otro motivo</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tiempo transcurrido</label>
                        <div class="alert alert-info py-2 px-3 mb-0" id="tiempoTranscurrido">
                            <!-- Se calculará dinámicamente -->
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="detalleAnulacion" class="form-label">Descripción detallada *</label>
                    <textarea class="form-control" id="detalleAnulacion" rows="3" 
                              placeholder="Describa detalladamente el motivo de la anulación..." required></textarea>
                    <div class="form-text">Mínimo 10 caracteres. Sea específico sobre el motivo.</div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmarAnulacion" required>
                    <label class="form-check-label" for="confirmarAnulacion">
                        <strong>Confirmo que deseo anular esta venta y acepto la responsabilidad</strong>
                    </label>
                </div>
                
                <div class="alert alert-info">
                    <strong><i class="fas fa-info-circle me-2"></i>Información importante:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Solo se pueden anular ventas con máximo 15 dias (360 horas) de antigüedad</li>
                        <li>La anulación quedará registrada en el historial del sistema</li>
                        <li>Se restaurará automáticamente el stock de los productos</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarAnulacion" disabled>
                    <i class="fas fa-ban me-2"></i>Anular Venta
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="js/informes-ventas.js<?php echo getFileVersion('js/informes-ventas.js'); ?>"></script>
</body>
</html>