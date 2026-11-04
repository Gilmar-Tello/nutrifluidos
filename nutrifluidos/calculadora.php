<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Facturas por Pagar</title>
    <!-- CSS externos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    
    
    
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
    <a href="calculadora.php" class="menu-item active">
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
                <h4 class="mb-0">Facturas por Pagar</h4>
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
            


             <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Facturas por Pagar</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFactura">
                        <i class="fas fa-plus me-2"></i>Nueva Factura
                    </button>
                </div>
                <div class="card-body">
                    <div id="customSearchContainer" class="mb-2"></div> <!-- MOVER AQUÍ -->
                    <div class="table-responsive">
                        <table id="tablaFacturas" class="table table-striped table-hover">
                            <thead>
                                <tr>

                    <th>Numero Factura</th>
                    <th>Proveedor</th>
                    <th>Monto Total</th>
                    <th>Pagado</th>
                    <th>Saldo Pendiente</th>
                    <th>Fecha Emision</th>
                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyFacturas">
                                <!-- Aquí se insertarán los proveedores con JS -->
                            </tbody>
                            
                        </table>
                    </div>
                </div>
            </div>

            </div>
            </div>


<!-- Modal Nueva Factura -->
<div class="modal fade" id="modalFactura" tabindex="-1" aria-labelledby="modalFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalFacturaLabel">Nueva Factura por Pagar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <form id="formFactura" enctype="multipart/form-data">
                    <input type="hidden" id="facturaId" name="facturaId">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="proveedor" class="form-label">Proveedor</label>
                            <select class="form-select" id="proveedor" name="proveedor_id" required>
                                <option value="">Seleccione un proveedor</option>
                                <!-- Opciones se llenarán dinámicamente -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="numeroFactura" class="form-label">Número de Factura</label>
                            <input type="text" class="form-control" id="numeroFactura" name="numeroFactura" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="montoTotal" class="form-label">Monto Total</label>
                            <input type="number" step="0.01" class="form-control" id="montoTotal" name="montoTotal" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pagado" class="form-label">Monto Pagado</label>
                            <input type="number" step="0.01" class="form-control" id="pagado" name="pagado" value="0.00" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fechaEmision" class="form-label">Fecha de Emisión</label>
                            <input type="date" class="form-control" id="fechaEmision" name="fechaEmision" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="factura" class="form-label">Adjuntar Factura</label>
                            <input type="file" class="form-control" id="factura" name="factura" accept="image/*,application/pdf">
                            <div id="previewContainer" class="mt-2"></div>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarFactura">Guardar</button>
            </div>

        </div>
    </div>
</div>



<div class="modal fade" id="modalAbono" tabindex="-1" aria-labelledby="modalAbonoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formAbono" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Abono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="factura_id" id="abonoFacturaId">
                    <div class="mb-2">
                        <label>Proveedor:</label>
                        <input type="text" id="abonoProveedor" class="form-control" disabled>
                    </div>
                    <div class="mb-2">
                        <label>Número Factura:</label>
                        <input type="text" id="abonoNumero" class="form-control" disabled>
                    </div>
                    <div class="mb-2">
                        <label>Saldo Pendiente:</label>
                        <input type="text" id="abonoSaldo" class="form-control" disabled>
                    </div>
                    <div class="mb-2">
                        <label>Monto a Abonar:</label>
                        <input type="number" step="0.01" min="0" name="monto_abono" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Comprobante o factura del abono:</label>
                        <input type="file" name="archivo_abono" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Abono</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>


<div class="modal fade" id="modalComprobantes" tabindex="-1" aria-labelledby="modalComprobantesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comprobantes de Factura <span id="numeroFacturaTitulo"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="contenedorComprobantes" class="row gy-3"></div>
            </div>
        </div>
    </div>
</div>


<!-- Modal Editar Factura -->
<div class="modal fade" id="modalEditarFactura" tabindex="-1" aria-labelledby="modalEditarFacturaLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="formEditarFactura" enctype="multipart/form-data">
      <input type="hidden" name="factura_id" id="editFacturaId">
      <input type="hidden" name="proveedor_id" id="editProveedorId"> <!-- agregado -->
      <input type="hidden" name="pagado" id="editPagado"> <!-- agregado -->
      
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Factura</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Proveedor (solo lectura) -->
          <div class="mb-3">
            <label for="editProveedor" class="form-label">Proveedor:</label>
            <input type="text" class="form-control" id="editProveedor" disabled>
          </div>

          <!-- Número de Factura -->
          <div class="mb-3">
            <label for="editNumeroFactura" class="form-label">Número de Factura:</label>
            <input type="text" class="form-control" id="editNumeroFactura" name="numeroFactura" required>
          </div>

          <!-- Fecha de emisión -->
          <div class="mb-3">
            <label for="editFechaEmision" class="form-label">Fecha de Emisión:</label>
            <input type="date" class="form-control" id="editFechaEmision" name="fechaEmision" required>
          </div>

          <!-- Monto Total -->
          <div class="mb-3">
            <label for="editMontoTotal" class="form-label">Monto Total:</label>
            <input type="number" step="0.01" min="0" class="form-control" id="editMontoTotal" name="montoTotal" required>
            <div class="alert alert-warning mt-2 d-none" id="alertaMonto">
              Esta factura ya tiene abonos. El monto total no se puede modificar.
            </div>
          </div>

          <!-- Reemplazar archivo -->
          <div class="mb-3">
            <label for="editArchivoFactura" class="form-label">Reemplazar archivo de factura (PDF/JPG/PNG):</label>
            <input type="file" class="form-control" id="editArchivoFactura" name="factura" accept=".pdf,.jpg,.jpeg,.png">
            <div class="form-text">Deja vacío si no deseas reemplazar el archivo actual.</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Agregar este modal de zoom al final de tu HTML, antes del cierre de </body> -->

<!-- Modal de Zoom para Comprobantes -->
<div class="modal fade" id="modalZoomComprobante" tabindex="-1" aria-labelledby="modalZoomComprobanteLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
            <div class="modal-header border-secondary">
                <h5 class="modal-title text-white" id="modalZoomComprobanteLabel">
                    <i class="fas fa-search-plus me-2"></i>Vista Ampliada del Comprobante
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center p-4">
                <!-- Contenedor de la imagen/PDF -->
                <div id="contenedorZoom" class="position-relative">
                    <!-- Aquí se insertará dinámicamente la imagen o PDF -->
                </div>
                
                <!-- Información del comprobante -->
                <div class="mt-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <small><strong>Archivo:</strong></small>
                                    <div id="nombreArchivo" class="text-info">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small><strong>Fecha:</strong></small>
                                    <div id="fechaArchivo" class="text-info">-</div>
                                </div>
                                <div class="col-md-4">
                                    <small><strong>Tamaño:</strong></small>
                                    <div id="tipoArchivo" class="text-info">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light" onclick="descargarComprobante()">
                    <i class="fas fa-download me-2"></i>Descargar
                </button>
                <button type="button" class="btn btn-outline-light" onclick="abrirEnNuevaVentana()">
                    <i class="fas fa-external-link-alt me-2"></i>Abrir en Nueva Ventana
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para el modal de zoom */
#modalZoomComprobante .modal-xl {
    max-width: 95vw;
}

#contenedorZoom img {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    cursor: zoom-in;
    transition: transform 0.3s ease;
}

#contenedorZoom img:hover {
    transform: scale(1.05);
}

#contenedorZoom img.zoomed {
    cursor: zoom-out;
    transform: scale(1.5);
}

#contenedorZoom embed {
    width: 100%;
    height: 70vh;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

/* Spinner de carga */
.loading-spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Estilos para archivos no compatibles */
.archivo-no-compatible {
    padding: 40px;
    text-align: center;
    color: #6c757d;
}

.archivo-no-compatible i {
    font-size: 4rem;
    margin-bottom: 20px;
    color: #dc3545;
}
</style>

   <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="js/calculadora.js<?php echo getFileVersion('js/calculadora.js'); ?>"></script>    

</body>
</html>