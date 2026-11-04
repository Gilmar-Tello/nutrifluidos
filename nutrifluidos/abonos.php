<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriFluidos - Gestión de Abonos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="css/abonos.css<?php echo getFileVersion('css/abonos.css'); ?>">
    
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
    <a href="abonos.php" class="menu-item active">
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
                <h4 class="mb-0">Gestión de Abonos</h4>
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

            <div class="card"><!--créditos-->
                   <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Créditos Pendientes</h5>
                     <div class="btn-group" role="group">
                    <!-- Botón para abrir modal de créditos pagados -->
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCreditosPagados">
                            <i class="fas fa-check-circle me-2"></i>Créditos Pagados
                        </button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalReporteAbonos">                             
                            <i class="fas fa-file-pdf me-2"></i>Reporte de Abonos                         
                        </button> 
                     </div>
                    
                </div>
                <div class="card-body">
                    <div id="customSearchContainer" class="mb-2"></div> <!-- Puedes usar esto si agregas una búsqueda personalizada -->
                    <div class="table-responsive">
                        <table id="tablaCreditosPendientes" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Fecha Venta</th>
                                    <th>Monto Total</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
                                    <th>Progreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyCreditosPendientes">
                                <!-- Aquí se insertan los créditos con JS o PHP -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!--créditos-->



            <div class="card"><!--HistorialPagos-->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historial de Pagos</h5>
                    <!-- Si en algún momento quieres agregar un botón (como "Agregar Pago Manual"), lo colocas aquí -->
                </div>
                <div class="card-body">
                    <div id="customSearchContainerPagos" class="mb-2"></div> <!-- Opcional: para buscador -->
                    <div class="table-responsive">
                        <table id="tablaHistorialPagos" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID Pago</th>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Fecha Pago</th>
                                    <th>Monto</th>
                                    <th>Método de Pago</th>
                                    <th>Comprobante</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyHistorialPagos">
                                <!-- Aquí se insertan  los pagos con JS o PHP -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!--HistorialPagos-->
        </div>


    <!-- Modal Registrar Pago -->
    <div class="modal fade" id="modalRegistrarPago" tabindex="-1" aria-labelledby="modalRegistrarPagoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRegistrarPagoLabel">Registrar Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formRegistrarPago">
                        <div class="mb-3">
                            <label for="facturaSelect" class="form-label">Factura</label>
                            <select class="form-select" id="facturaSelect" required>
                                <option value="">Seleccione una factura</option>
                                 <!-- Las opciones se cargarán dinámicamente -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="fechaPago" class="form-label">Fecha de Pago</label>
                            <input type="date" class="form-control" id="fechaPago" required>
                        </div>
                        <div class="mb-3">
                            <label for="montoPago" class="form-label">Monto del Pago</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="montoPago" required>
                            </div>
                                <div class="form-text" id="saldoPendiente">Saldo pendiente: Q0.00</div>
                        </div>
                        <div class="mb-3">
                            <label for="metodoPago" class="form-label">Método de Pago</label>
                            <select class="form-select" id="metodoPago" name="metodo_pago" required>
                                <option value="">Seleccione un método</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="cheque">Cheque</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="observacionMetodoPago" class="form-label">Observaciones del Método de Pago</label>
                            <textarea class="form-control" id="observacionMetodoPago" rows="2" placeholder="Detalle el método del pago."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="numeroReferencia" class="form-label">Número de Referencia</label>
                            <input type="text" class="form-control" id="numeroReferencia">
                            <div class="form-text">Opcional.</div>
                        </div>
                        <div class="mb-3">
                            <label for="comprobantePago" class="form-label">Comprobante de Pago</label>
                            <div class="file-upload btn btn-outline-primary w-100">
                                <i class="fas fa-upload me-2"></i>Subir Comprobante
                                <input type="file" id="comprobantePago" accept="image/*, application/pdf">
                            </div>
                            <div class="form-text">Formatos aceptados: JPG, PNG, PDF. Máximo 5MB.</div>
                            <div id="previewComprobante" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label for="descuentoPago" class="form-label">Descuento Aplicado (Q)</label>
                            <input type="number" class="form-control" id="descuentoPago" step="0.01" placeholder="0.00">
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacionDescuento" class="form-label">Observación del Descuento</label>
                            <textarea class="form-control" id="observacionDescuento" rows="2" placeholder="Ej. Descuento por fidelidad o pronto pago."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="observaciones" class="form-label">Observaciones Generales</label>
                            <textarea class="form-control" id="observaciones" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="idCredito" name="idCredito">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarPago">Guardar Pago</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Comprobante -->
    <div class="modal fade" id="modalVerComprobante" tabindex="-1" aria-labelledby="modalVerComprobanteLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerComprobanteLabel">Comprobante de Pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="comprobante-container">
                    <!-- Aquí se insertará dinámicamente IMG o IFRAME -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <a href="#" id="linkDescargaComprobante" download class="btn btn-primary" style="display: none;">
                        <i class="fas fa-download me-2"></i>Descargar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créditos Pagados -->
<div class="modal fade" id="modalCreditosPagados" tabindex="-1" aria-labelledby="modalCreditosPagadosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreditosPagadosLabel">
                    <i class="fas fa-check-circle me-2"></i>Créditos Completamente Pagados
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Número de Factura</th>
                            <th>Cliente</th>
                            <th>Monto Original</th>
                            <th>Fecha de Crédito</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCreditosPagados">
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando créditos pagados...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Reporte de Abonos -->
<div class="modal fade" id="modalReporteAbonos" tabindex="-1" aria-labelledby="modalReporteAbonosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalReporteAbonosLabel">
                    <i class="fas fa-file-pdf me-2"></i>Generar Reporte de Abonos
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formReporteAbonos">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fechaInicio" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha Inicio
                                </label>
                                <input type="date" class="form-control" id="fechaInicio" name="fecha_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fechaFin" class="form-label">
                                    <i class="fas fa-calendar-alt me-1"></i>Fecha Fin
                                </label>
                                <input type="date" class="form-control" id="fechaFin" name="fecha_fin">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="clienteFiltro" class="form-label">
                                    <i class="fas fa-user me-1"></i>Cliente (opcional)
                                </label>
                                <input type="text" class="form-control" id="clienteFiltro" name="cliente" 
                                       placeholder="Ingrese nombre del cliente para filtrar">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="tipoReporteDisplay" class="form-label">
                                    <i class="fas fa-chart-bar me-1"></i>Tipo de Reporte
                                </label>
                                <input type="hidden" id="tipoReporte" name="tipo_reporte" value="detallado">
                                <div class="form-control-plaintext" id="tipoReporteDisplay">
                                    Reporte Detallado
                                </div>
                                </div>
                        </div>
                    </div>
                    </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-info" id="btnGenerarReporte">
                    <i class="fas fa-file-pdf me-2"></i>Generar PDF
                </button>
            </div>
        </div>
    </div>
    <div id="alertasReporte"></div>
</div>

    <!-- JavaScript con versioning y orden correcto -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="js/abonos.js<?php echo getFileVersion('js/abonos.js'); ?>"></script>

</body>
</html>