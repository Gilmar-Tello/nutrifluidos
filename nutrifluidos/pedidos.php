<?php require_once('functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriFluidos - Gestión de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="css/style.css<?php echo getFileVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="css/pedidos.css<?php echo getFileVersion('css/pedidos.css'); ?>">
    
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
    <a href="pedidos.php" class="menu-item active">
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
                <h4 class="mb-0">Gestión de Pedidos</h4>
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
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex flex-wrap">
                            <div class="btn-group me-2 mb-2">
                                <button type="button" class="btn btn-outline-primary active">Todos</button>
                                <button type="button" class="btn btn-outline-primary">Pendientes</button>
                                <button type="button" class="btn btn-outline-primary">Empacados</button>
                                <button type="button" class="btn btn-outline-primary">Enviados</button>
                                <button type="button" class="btn btn-outline-primary">Entregados</button>
                            </div>
                            <!--
                            <div class="input-group mb-2" style="width: 300px;">
                                <input type="text" class="form-control" placeholder="Buscar pedido...">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            -->
                        </div>
                    </div>
                    <!-- ocultado
                    <div class="col-lg-4 text-lg-end">
                        <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#modalNuevoPedido">
                            <i class="fas fa-plus me-2"></i>Nuevo Pedido
                        </button>
                    </div>
                -->
                </div>
            </div>

            <!-- Orders List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Lista de Pedidos</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaPedidos" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No. Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Progreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

  
    <!-- Modal Detalle de Pedido - REEMPLAZAR EL EXISTENTE -->
<div class="modal fade" id="modalDetallePedido" tabindex="-1" aria-labelledby="modalDetallePedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalDetallePedidoLabel">
                    <i class="fas fa-eye"></i> Detalle del Pedido
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- El contenido se genera dinámicamente con JavaScript -->
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando información del pedido...</p>
                </div>
            </div>
            <div class="modal-footer">
                <!-- Los botones se generan dinámicamente según el estado del pedido -->
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Modificar Pedido -->
<!-- Modal Modificar Pedido - REEMPLAZAR EL EXISTENTE -->
<div class="modal fade" id="modalModificarPedido" tabindex="-1" aria-labelledby="modalModificarPedidoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalModificarPedidoLabel">
                    <i class="fas fa-edit"></i> Modificar Pedido
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Información del Pedido -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-info">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información del Pedido</h6>
                            </div>
                            <div class="card-body">
                               <!-- Información del Pedido NUEVA -->
<div class="row">
    <div class="col-md-2">
        <strong>Número de Pedido:</strong><br>
        <span id="pedidoModificarNumero" class="text-primary">-</span>
    </div>
    <div class="col-md-3">
        <strong>Cliente:</strong><br>
        <span id="pedidoModificarCliente">-</span>
    </div>
    <div class="col-md-2">
        <strong>Fecha:</strong><br>
        <span id="pedidoModificarFecha">-</span>
    </div>
    <div class="col-md-3">
        <strong>DTE (Opcional):</strong><br>
        <input type="text" 
               id="pedidoModificarDte" 
               class="form-control form-control-sm" 
               placeholder="Ingrese DTE si aplica"
               maxlength="20">
        <small class="text-muted">Documento Tributario Electrónico</small>
    </div>
    <div class="col-md-2">
        <span class="badge bg-warning text-dark fs-6">PENDIENTE</span>
    </div>
</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Productos del Pedido -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-boxes"></i> Productos del Pedido</h6>
                                <button type="button" class="btn btn-sm btn-success" onclick="agregarNuevaFilaModificacion()">
                                    <i class="fas fa-plus"></i> Agregar Producto
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Búsqueda de productos:</strong> Escriba el código o nombre del producto para buscarlo. Mínimo 2 caracteres.
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th width="35%">Producto</th>
                                                <th width="15%">Cantidad</th>
                                                <th width="20%">Precio Unit.</th>
                                                <th width="20%">Subtotal</th>
                                                <th width="10%">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tablaProductosModificar">
                                            <!-- Las filas se generan dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Instrucciones de uso -->
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="card border-light bg-light">
                                            <div class="card-body p-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-lightbulb text-warning"></i>
                                                    <strong>Instrucciones:</strong>
                                                    <ul class="mb-0 small">
                                                        <li>Para buscar productos, escriba al menos 2 caracteres del código o nombre</li>
                                                        <li>Los productos sin stock aparecen deshabilitados</li>
                                                        <li>La cantidad máxima se ajusta automáticamente al stock disponible</li>
                                                        <li>Use el botón "Agregar Producto" para añadir más productos</li>
                                                    </ul>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totales -->
                <div class="row mt-3">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 text-success"><i class="fas fa-calculator"></i> Totales</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label class="form-label">Subtotal:</label>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Q</span>
                                            <input type="text" class="form-control text-end" id="subtotalModificar" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Descuento editable -->
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <label class="form-label" for="descuentoModificar">Descuento:</label>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text text-danger">- Q</span>
                                            <input type="number" class="form-control text-end" id="descuentoModificar"
                                                   min="0" step="0.01" value="0.00"
                                                   oninput="calcularTotalesModificacion()">
                                        </div>
                                        <small id="descuentoOriginalInfo" class="text-muted d-none"></small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label"><strong>Total:</strong></label>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-success text-white">Q</span>
                                            <input type="text" class="form-control text-end fw-bold" id="totalModificar" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Área de alertas -->
                <div id="alertaModificacion" class="alert alert-warning d-none mt-3" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="mensajeAlerta"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-info" onclick="generarComprobantePedido()">
                    <i class="fas fa-print"></i> Ver Comprobante
                </button>
                <button type="button" class="btn btn-success" id="btnGuardarModificacion" onclick="guardarModificacionesPedido()">
                    <i class="fas fa-save"></i> Guardar Modificaciones
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales para la búsqueda -->
<style>
.cursor-pointer {
    cursor: pointer;
}

.search-results .dropdown-item:hover {
    background-color: #f8f9fa;
}

.search-results .dropdown-item.disabled:hover {
    background-color: transparent;
}

.producto-search:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.search-results {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: white;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.position-relative {
    position: relative !important;
}
</style>

    <!-- Modal Empacar Pedido -->
    <!-- Modal Empacar Pedido - ACTUALIZAR -->
<div class="modal fade" id="modalEmpacarPedido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marcar Pedido como Empacado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Está a punto de cambiar el estado del pedido a "Empacado". Esta acción registrará que los productos han sido verificados y empacados, listos para su envío.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    Asegúrese de haber verificado todos los productos del pedido.
                </div>
                
                <form id="formEmpacarPedido">
                    <div class="mb-3">
                        <label for="fechaEmpaque" class="form-label">Fecha y Hora de Empaque</label>
                        <input type="datetime-local" class="form-control" id="fechaEmpaque" required>
                    </div>
                    <div class="mb-3">
                        <label for="notasEmpaque" class="form-label">Notas de Empaque (Opcional)</label>
                        <textarea class="form-control" id="notasEmpaque" rows="3" 
                                  placeholder="Ingrese notas adicionales sobre el empaque..."></textarea>
                    </div>
                </form>
                
                <div class="alert alert-success d-none" id="alertaEmpaqueInfo">
                    <i class="fas fa-user"></i> 
                    <strong>Empacado por:</strong> Se registrará automáticamente el usuario actual
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarEmpaquePedido()">
                    <i class="fas fa-box"></i> Confirmar Empaque
                </button>
            </div>
        </div>
    </div>
</div>

   <!-- Modal Enviar Pedido - REEMPLAZAR EL EXISTENTE -->
<div class="modal fade" id="modalEnviarPedido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marcar Pedido como Enviado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Está a punto de cambiar el estado del pedido a "Enviado". Esta acción registrará que el pedido ha sido entregado al transportista para su envío.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    Asegúrese de tener toda la información de envío correcta.
                </div>
                
                <form id="formEnviarPedido">
                    <div class="mb-3">
                        <label for="fechaEnvio" class="form-label">Fecha y Hora de Envío <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fechaEnvio" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transportista" class="form-label">Transportista</label>
                        <select class="form-select" id="transportista">
                            <option value="">Seleccione un transportista</option>
                            <option value="Cargo Expreso">Cargo Expreso</option>
                            <option value="Guatex">Guatex</option>
                            <option value="Transportes Unidos">Transportes Unidos</option>
                            <option value="Envío Propio">Envío Propio</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="numeroGuia" class="form-label">Número de Guía</label>
                        <input type="text" class="form-control" id="numeroGuia" 
                               placeholder="Ingrese el número de guía o tracking">
                    </div>
                    
                    <div class="mb-3">
                        <label for="costoEnvio" class="form-label">Costo de Envío</label>
                        <div class="input-group">
                            <span class="input-group-text">Q</span>
                            <input type="number" step="0.01" class="form-control" id="costoEnvio" 
                                   value="0.00" min="0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
    <label for="fechaEntregaEstimada" class="form-label">Fecha de Entrega Estimada</label>
    <input type="datetime-local" class="form-control" id="fechaEntregaEstimada">
</div>
                    
                    <div class="mb-3">
                        <label for="notasEnvio" class="form-label">Notas de Envío</label>
                        <textarea class="form-control" id="notasEnvio" rows="3" 
                                  placeholder="Ingrese notas adicionales sobre el envío..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning text-white" onclick="confirmarEnvioPedido()">
                    <i class="fas fa-truck"></i> Confirmar Envío
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Entregar Pedido - REEMPLAZAR EL EXISTENTE -->
<div class="modal fade" id="modalEntregarPedido" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Marcar Pedido como Entregado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Está a punto de cambiar el estado del pedido a "Entregado". Esta acción registrará que el pedido ha sido recibido completamente por el cliente.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> 
                    Esta acción requiere confirmación de recepción por parte del cliente.
                </div>
                
                <form id="formEntregarPedido">
                    <div class="mb-3">
                        <label for="fechaEntrega" class="form-label">Fecha y Hora de Entrega <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fechaEntrega" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="recibidoPor" class="form-label">Recibido por <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="recibidoPor" 
                               placeholder="Nombre completo de la persona que recibió" 
                               required minlength="3" maxlength="100">
                        <div class="form-text">Ingrese el nombre completo de quien recibió el pedido</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="firmaPedido" class="form-label">Comprobante de Entrega</label>
                        <input type="file" class="form-control" id="firmaPedido" 
                               accept="image/*,.pdf">
                        <div class="form-text">
                            Adjunte una imagen de la firma, recibo o documento que confirme la entrega.
                            <br><small class="text-muted">Formatos permitidos: JPG, PNG, PDF. Tamaño máximo: 5MB</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notasEntrega" class="form-label">Notas de Entrega</label>
                        <textarea class="form-control" id="notasEntrega" rows="3" 
                                  placeholder="Ingrese notas adicionales sobre la entrega, condiciones especiales, etc."></textarea>
                    </div>
                </form>
                
                <div class="alert alert-success d-none" id="alertaEntregaInfo">
                    <i class="fas fa-user"></i> 
                    <strong>Registrado por:</strong> Se registrará automáticamente el usuario actual
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmarEntregaPedido()">
                    <i class="fas fa-check-circle"></i> Confirmar Entrega
                </button>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Nuevo Pedido -->
    <div class="modal fade" id="modalNuevoPedido" tabindex="-1" aria-labelledby="modalNuevoPedidoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoPedidoLabel">Crear Nuevo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoPedido">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="metodoPagoNuevo" class="form-label">Método de Pago</label>
                                <select class="form-select" id="metodoPagoNuevo" required>
                                    <option value="contado">Contado</option>
                                    <option value="credito">Crédito (30 días)</option>
                                    <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                                    <option value="transferencia">Transferencia Bancaria</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vendedorNuevo" class="form-label">Vendedor</label>
                                <select class="form-select" id="vendedorNuevo" required>
                                    <option value="">Seleccione un vendedor</option>
                                    <option value="1">Juan Pérez</option>
                                    <option value="2">María Rodríguez</option>
                                    <option value="3">Carlos Martínez</option>
                                </select>
                            </div>
                        </div>
                        
                        <h6 class="mt-4">Productos</h6>
                        <!-- Tabla de productos -->
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Total</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProductosNuevoPedido">
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm producto-select">
                                                <option value="">Seleccione un producto</option>
                                                <option value="1">Cinta de Goteo 16mm</option>
                                                <option value="2">Microaspersor TORO</option>
                                                <option value="3">Goteros Autocompensados</option>
                                                <option value="4">Válvula de Riego 1/2"</option>
                                                <option value="5">Filtro de disco 3/4"</option>
                                            </select>
                                        </td>
                                        <td><input type="number" class="form-control form-control-sm cantidad-input" value="1" min="1"></td>
                                        <td><input type="text" class="form-control form-control-sm precio-input" value="0.00" readonly></td>
                                        <td><input type="text" class="form-control form-control-sm total-input" value="0.00" readonly></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger btn-eliminar-producto">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="btnAgregarProducto">
                            <i class="fas fa-plus me-2"></i>Agregar Producto
                        </button>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Subtotal</h6>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Q</span>
                                    <input type="text" class="form-control" id="subtotalNuevo" value="0.00" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>IVA (12%)</h6>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Q</span>
                                    <input type="text" class="form-control" id="ivaNuevo" value="0.00" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6>Total</h6>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">Q</span>
                                    <input type="text" class="form-control" id="totalNuevo" value="0.00" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Dirección de Entrega</h6>
                                <div class="mb-3">
                                    <label for="direccionEntregaNuevo" class="form-label">Dirección</label>
                                    <textarea class="form-control" id="direccionEntregaNuevo" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Notas de Pedido</h6>
                                <div class="mb-3">
                                    <label for="notasPedidoNuevo" class="form-label">Notas</label>
                                    <textarea class="form-control" id="notasPedidoNuevo" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary">Crear Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Imprimir Factura -->
    <div class="modal fade" id="modalImprimirFactura" tabindex="-1" aria-labelledby="modalImprimirFacturaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalImprimirFacturaLabel">Imprimir Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Seleccione el tipo de documento que desea imprimir para el pedido <strong>PED-2025-004</strong>:</p>
                    
                    <div class="list-group mb-3">
                        <button type="button" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-invoice me-2"></i>Factura (Original)
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-invoice me-2"></i>Factura (Copia)
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">
                            <i class="fas fa-clipboard-list me-2"></i>Orden de Entrega
                        </button>
                        <button type="button" class="list-group-item list-group-item-action">
                            <i class="fas fa-truck me-2"></i>Guía de Remisión
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- JavaScript con versioning -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/plug-ins/1.11.5/sorting/date-eu.js"></script>
    <script src="js/auth.js<?php echo getFileVersion('js/auth.js'); ?>"></script>
    <script src="js/pedidos.js<?php echo getFileVersion('js/pedidos.js'); ?>"></script>
</body>
</html>