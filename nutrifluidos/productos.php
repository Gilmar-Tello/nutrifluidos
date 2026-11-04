<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Gestión de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="css/productos.css<?php echo getFileVersion('css/productos.css'); ?>">
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
    <a href="productos.php" class="menu-item active">
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
                <h4 class="mb-0">Gestión de Productos</h4>
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
        <h5 class="mb-0">Lista de Productos</h5>
        <div class="d-flex gap-2">
            <a href="php/reporte_productos_existencia.php" target="_blank" class="btn btn-success">
    <i class="fa fa-file-pdf me-2"></i>Existencias
</a>
            
           <a href="php/reporte_stock_bajo.php" target="_blank" class="btn btn-danger">
        <i class="fa fa-file-pdf me-2"></i>Stock Bajo
        </a>

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto">
                <i class="fas fa-plus me-2"></i>Nuevo Producto
            </button>
        </div>
    </div>
    <div class="card-body">
        <div id="customSearchContainerProductos" class="mb-2"></div>
        <div class="table-responsive">
            <table id="tablaProductos" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Imagen</th>
                        <th>Nombre de Producto</th>
                        <th>Precio Compra</th>
                        <th>Precio Venta Real</th>
                        <th>Precio Venta Sugerido</th>
                        <th>Stock</th>
                        <th>Stock Mínimo</th>
                        <th>Proveedor</th>
                        <th>Fecha Recepción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyProductos">
                    <!-- Aquí se llenarán los productos dinámicamente -->
                </tbody>
            </table>
        </div>
    </div>
</div>

            

<!-- Modal Nuevo Producto -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-labelledby="modalProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalProductoLabel">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formProducto" enctype="multipart/form-data">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="nombreProducto" class="form-label">Nombre del Producto</label>
                            <input type="text" class="form-control" id="nombreProducto" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="precioCompra" class="form-label">Precio de Compra</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="precioCompra" name="precio_compra" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="precioVentaReal" class="form-label">Precio Venta Real</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="precioVentaReal" name="precio_venta_real" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="precioVentaSugerido" class="form-label">Precio Venta Sugerido</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="precioVentaSugerido" name="precio_venta_sugerido" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" step="1" required>
                    </div>
                    <div class="col-md-3">
                        <label for="stockminimo" class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" id="stockminimo" name="stockminimo" min="0" step="1" required>
                    </div>
                    <div class="col-md-6">
                        <label for="proveedor" class="form-label">Proveedor</label>
                        <select class="form-select" id="proveedor" name="proveedor_id" required>
                            <option value="">Seleccione un proveedor</option>
                        </select>
                    </div>
                </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="fotoProducto" class="form-label">Foto del Producto</label>
                            <input type="file" class="form-control" id="fotoProducto" name="fotoProducto" accept="image/*">
                            <div id="previewContainer" class="mt-2"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarProducto">Guardar</button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Editar Producto -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarProductoLabel">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarProducto" enctype="multipart/form-data">
                    <input type="hidden" id="editIdProducto" name="id">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="editNombreProducto" class="form-label">Nombre del Producto</label>
                            <input type="text" class="form-control" id="editNombreProducto" name="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label for="editCodigo" class="form-label">Código</label>
                            <input type="text" class="form-control" id="editCodigo" name="codigo" required="">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="editPrecioCompra" class="form-label">Precio de Compra</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="editPrecioCompra" name="precio_compra">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="editPrecioVentaReal" class="form-label">Precio Venta Real</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="editPrecioVentaReal" name="precio_venta_real">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="editPrecioVentaSugerido" class="form-label">Precio Venta Sugerido</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="editPrecioVentaSugerido" name="precio_venta_sugerido">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="editstockminimo" class="form-label">Stock Mínimo</label>
                            <input type="number" class="form-control" id="editstockminimo" name="editstockminimo" required>
                        </div>
                        <div class="col-md-6">
                            <label for="proveedorEditar" class="form-label">Proveedor</label>
                            <input type="text" class="form-control" id="proveedorEditar" name="proveedor" readonly>
                        </div>
                        <!-- NUEVO: Campo para cambiar imagen -->
                        <div class="col-md-3">
                            <label for="editFotoProducto" class="form-label">Cambiar Imagen</label>
                            <input type="file" class="form-control" id="editFotoProducto" name="editFotoProducto" accept="image/*">
                        </div>
                    </div> 
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Imagen Actual</label>
                            <div id="editPreviewContainer" class="mt-2"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnActualizarProducto">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Eliminar Producto -->
<div class="modal fade" id="modalEliminarProducto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el producto <strong id="nombreProductoEliminar"></strong>?</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Esta acción no se puede deshacer.
                </p>
                <input type="hidden" id="idProductoEliminar">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarEliminarProducto">
                    <i class="fas fa-trash-alt me-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

     <!-- Modal Actualizar Stock -->
<div class="modal fade" id="modalLlenarStock" tabindex="-1" aria-labelledby="modalLlenarStockLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLlenarStockLabel">Actualizar Stock</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="formLlenarStock">
          <!-- ID del producto oculto -->
          <input type="hidden" id="llenarStockIdProducto" name="id">

          <!-- Nombre del producto (solo lectura) -->
          <div class="mb-3">
            <label class="form-label">Producto</label>
            <input type="text" class="form-control" id="llenarStockNombreProducto" name="nombre" readonly>
          </div>

          <!-- Stock actual (solo lectura) -->
          <div class="mb-3">
            <label class="form-label">Stock Actual</label>
            <input type="number" class="form-control" id="llenarStockActual" readonly>
          </div>

          <!-- Acción a realizar -->
          <div class="mb-3">
            <label class="form-label">Acción</label>
            <select class="form-select" id="accionStock" name="accion" required>
              <option value="">Seleccione una acción</option>
              <option value="agregar">Agregar Stock</option>
              <option value="reducir">Reducir Stock</option>
            </select>
          </div>

          <!-- Cantidad a modificar -->
          <div class="mb-3">
            <label class="form-label">Cantidad</label>
            <input type="number" class="form-control" id="cantidadStock" name="cantidad" min="1" required>
          </div>

          <!-- Precio de compra unitario (solo aplica al agregar stock, es una compra) -->
          <div class="mb-3" id="wrapperPrecioCompraStock" style="display: none;">
            <label class="form-label">Precio de Compra Unitario</label>
            <div class="input-group">
              <span class="input-group-text">Q</span>
              <input type="number" step="0.01" min="0.01" class="form-control" id="precioCompraStock" name="precio_compra_unitario">
            </div>
            <small class="form-text text-muted">Precio al que se compró esta entrada de mercancía.</small>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnConfirmarLlenadoStock">Actualizar Stock</button>
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
    <script src="js/productos.js<?php echo getFileVersion('js/productos.js'); ?>"></script>
    
</body>
</html>