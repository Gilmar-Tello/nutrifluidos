let pedidoActual = null;
let productosDisponibles = [];
let contadorFilas = 0;
let tablaPedidos;

document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión y permisos requeridos para pedidos (solo admin)
    initAuth('pedidos');
    
    // Ocultar opciones del menú inmediatamente según permisos
    if (!checkSession()) {
        window.location.href = '/index.php';
        return;
    }
    
    // Configurar menú según permisos
    setupMenuPermissions();

  

    // Inicializar DataTable con datos del servidor

tablaPedidos = $('#tablaPedidos').DataTable({
    ajax: {
        url: `php/pedidos.php?usuario_id=${JSON.parse(localStorage.getItem('userData')).id}&rol=${JSON.parse(localStorage.getItem('userData')).rol}`,
        type: 'GET',
        dataSrc: 'data'
    },
    columns: [
        { 
            data: 0,
            orderable: false
        },
        { 
            data: 1,
            orderable: true
        },
        { 
            data: 2, // Mostrar fecha en formato DD/MM/YYYY
            orderable: true,
            orderData: [7] // ✅ CLAVE: Ordenar usando la columna oculta (índice 7)
        },
        { 
            data: 3,
            orderable: false
        },
        { 
            data: 4,
            orderable: false
        },
        { 
            data: 5,
            orderable: false
        },
        { 
            data: 6, 
            orderable: false
        },
        { 
            data: 7, // ✅ COLUMNA OCULTA con fecha ISO para ordenamiento
            visible: false, // No mostrar esta columna
            searchable: false // No buscar en esta columna
        }
    ],
    language: {
        "decimal": "",
        "emptyTable": "No hay datos disponibles en la tabla",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
        "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
        "infoFiltered": "(filtrado de _MAX_ entradas totales)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "No se encontraron registros coincidentes",
        "paginate": {
            "first": "Primero",
            "last": "Último",
            "next": "Siguiente",
            "previous": "Anterior"
        }
    },
    order: [[2, 'desc']], // Ordenar por fecha (más recientes primero)
    pageLength: 10,
    columnDefs: [
        {
            targets: 3, // Columna del total
            className: 'text-end'
        }
    ]
});

    // Manejar filtros por estado
    $('.filter-bar .btn-group button').click(function() {
    // Remover clase active de todos los botones
    $('.filter-bar .btn-group button').removeClass('active');
    // Agregar clase active al botón clickeado
    $(this).addClass('active');
    
    const filtroTexto = $(this).text().trim();
    console.log('Filtro seleccionado:', filtroTexto); // Para debug
    
    // Mapear el texto del botón al valor exacto en la base de datos
    let valorFiltro = '';
    
    switch(filtroTexto) {
        case 'Todos':
            valorFiltro = '';
            break;
        case 'Pendientes':
            valorFiltro = 'Pendiente'; // Debe coincidir con el badge en PHP
            break;
        case 'Empacados':
            valorFiltro = 'Empacado'; // Debe coincidir con el badge en PHP
            break;
        case 'Enviados':
            valorFiltro = 'Enviado'; // Debe coincidir con el badge en PHP
            break;
        case 'Entregados':
            valorFiltro = 'Entregado'; // Debe coincidir con el badge en PHP
            break;
        default:
            valorFiltro = '';
    }
    
    console.log('Valor de filtro aplicado:', valorFiltro); // Para debug
    
    if (valorFiltro === '') {
        // Mostrar todos los registros
        tablaPedidos.column(4).search('').draw();
    } else {
        // Filtrar por estado específico
        tablaPedidos.column(4).search(valorFiltro).draw();
    }
});

    // Establecer fecha actual en campos de fecha
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];
    $('#fechaPedidoNuevo').val(formattedDate);
    
    // Formatear fecha y hora actual para campos datetime-local
    const now = new Date();
    const formattedDateTime = now.toISOString().slice(0, 16);
    $('#fechaEmpaque').val(formattedDateTime);
    $('#fechaEnvio').val(formattedDateTime);
    $('#fechaEntrega').val(formattedDateTime);

    // Botón para agregar fila de producto en nuevo pedido
    $('#btnAgregarProducto').click(function() {
        const newRow = `
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
        `;
        $('#tablaProductosNuevoPedido').append(newRow);
    });

    // Eliminar fila de producto
    $(document).on('click', '.btn-eliminar-producto', function() {
        $(this).closest('tr').remove();
        calcularTotales();
    });

    // Simulación de cambio de producto para mostrar precio
    $(document).on('change', '.producto-select', function() {
        const productoId = $(this).val();
        let precio = 0;
        
        // Simulación de precios
        switch(productoId) {
            case '1': precio = 595.00; break;
            case '2': precio = 55.00; break;
            case '3': precio = 4.50; break;
            case '4': precio = 65.00; break;
            case '5': precio = 270.00; break;
        }
        
        const row = $(this).closest('tr');
        row.find('.precio-input').val(precio.toFixed(2));
        
        // Actualizar total
        const cantidad = parseInt(row.find('.cantidad-input').val()) || 0;
        const total = cantidad * precio;
        row.find('.total-input').val(total.toFixed(2));
        
        calcularTotales();
    });

    // Actualizar total al cambiar cantidad
    $(document).on('input', '.cantidad-input', function() {
        const row = $(this).closest('tr');
        const cantidad = parseInt($(this).val()) || 0;
        const precio = parseFloat(row.find('.precio-input').val()) || 0;
        const total = cantidad * precio;
        row.find('.total-input').val(total.toFixed(2));
        
        calcularTotales();
    });

    // Función para calcular subtotal, IVA y total
    function calcularTotales() {
        let subtotal = 0;
        
        // Sumar todos los totales de productos
        $('.total-input').each(function() {
            subtotal += parseFloat($(this).val()) || 0;
        });
        
        const iva = subtotal * 0.12;
        const total = subtotal + iva;
        
        // Actualizar campos
        $('#subtotalNuevo').val(subtotal.toFixed(2));
        $('#ivaNuevo').val(iva.toFixed(2));
        $('#totalNuevo').val(total.toFixed(2));
    }

    // Limpiar datos al cerrar el modal de modificación
    $('#modalModificarPedido').on('hidden.bs.modal', function () {
        pedidoActual = null;
        const tbody = document.getElementById('tablaProductosModificar');
        if (tbody) {
            tbody.innerHTML = '';
        }
        ocultarAlerta();
        contadorFilas = 0;
    });
});


// ========== FUNCIONES PARA DETALLE DE PEDIDO ==========

// Función mejorada para ver detalle del pedido
function verDetallePedido(id) {
    // Mostrar el modal primero con un indicador de carga
    $('#modalDetallePedido').modal('show');
    mostrarCargandoDetalle();
    
    // Cargar datos reales del pedido
    cargarDetalleCompletoPedido(id);
}

// Mostrar indicador de carga en el modal de detalle
function mostrarCargandoDetalle() {
    const modalBody = document.querySelector('#modalDetallePedido .modal-body');
    modalBody.innerHTML = `
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 text-muted">Cargando información del pedido...</p>
        </div>
    `;
}

async function cargarDetalleCompletoPedido(pedidoId) {
    try {
        // CORRECCIÓN: Agregar los parámetros de usuario y rol a la URL
        const userData = JSON.parse(localStorage.getItem('userData'));
        const url = `php/pedidos.php?action=obtener_detalle_completo&pedido_id=${pedidoId}&usuario_id=${userData.id}&rol=${userData.rol}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            mostrarDetalleCompletoPedido(data);
        } else {
            mostrarErrorDetalle(data.message || data.error || 'Error al cargar el pedido');
        }
    } catch (error) {
        console.error('Error al cargar detalle del pedido:', error);
        mostrarErrorDetalle('Error al cargar los datos del pedido');
    }
}

// Mostrar error en el modal de detalle
function mostrarErrorDetalle(mensaje) {
    const modalBody = document.querySelector('#modalDetallePedido .modal-body');
    modalBody.innerHTML = `
        <div class="alert alert-danger text-center">
            <i class="fas fa-exclamation-triangle mb-2"></i>
            <h5>Error al cargar el pedido</h5>
            <p>${mensaje}</p>
        </div>
    `;
}

// Mostrar detalle completo del pedido
function mostrarDetalleCompletoPedido(data) {
    const { pedido, detalles, historial, empaque, envio, entrega, permisos } = data;
    
    // Actualizar título del modal
    document.getElementById('modalDetallePedidoLabel').innerHTML = `<i class="fas fa-eye"></i> Detalle del Pedido ${pedido.numero_pedido}`;
    
    // Formatear fechas
    const fechaPedido = new Date(pedido.fecha_pedido).toLocaleDateString('es-GT');
    const fechaRegistro = new Date(pedido.fecha_registro).toLocaleDateString('es-GT');
    
    // Crear contenido del modal
    const modalBody = document.querySelector('#modalDetallePedido .modal-body');
    modalBody.innerHTML = `
        <!-- Información del Cliente y Pedido -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-user"></i> Información del Cliente</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Cliente:</strong> ${pedido.cliente_nombre || 'N/A'}
                        </div>
                        <div class="mb-2">
                            <strong>NIT:</strong> ${pedido.cliente_nit || 'N/A'}
                        </div>
                        <div class="mb-2">
                            <strong>Contacto:</strong> ${pedido.cliente_contacto || 'N/A'}
                        </div>
                        <div class="mb-2">
                            <strong>Teléfono:</strong> ${pedido.cliente_telefono || 'N/A'}
                        </div>
                        <div class="mb-2">
                            <strong>Correo:</strong> ${pedido.cliente_email || 'N/A'}
                        </div>
                        <div class="mb-0">
                            <strong>Dirección:</strong> ${pedido.cliente_direccion || 'N/A'}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Información del Pedido</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>No. Pedido:</strong> ${pedido.numero_pedido}
                        </div>
                        <div class="mb-2">
                            <strong>Fecha de pedido:</strong> ${fechaPedido}
                        </div>
                        <div class="mb-2">
                            <strong>Método de pago:</strong> ${pedido.tipo_venta === 'credito' ? 'Crédito' : 'Contado'}
                        </div>
                        <div class="mb-2">
                            <strong>Estado actual:</strong> ${crearBadgeEstado(pedido.estado)}
                        </div>
                        <div class="mb-2">
                            <strong>Vendedor:</strong> ${pedido.vendedor_nombre || 'N/A'}
                        </div>
                        <div class="mb-0">
                            <strong>Fecha registro:</strong> ${fechaRegistro}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progreso del Pedido -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-tasks"></i> Progreso del Pedido</h6>
                    </div>
                    <div class="card-body">
                        ${crearProgresoVisual(pedido.estado)}
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos del Pedido -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="fas fa-boxes"></i> Productos del Pedido</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Código</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${crearFilasProductos(detalles)}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumen y Historial -->
        <div class="row">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="fas fa-history"></i> Historial del Pedido</h6>
                    </div>
                    <div class="card-body">
                        ${crearHistorialPedido(historial)}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-calculator"></i> Resumen del Pedido</h6>
                    </div>
                    <div class="card-body">
                        ${crearResumenPedido(pedido)}
                        
                        <!-- Dirección de Entrega -->
                        <div class="mt-3">
                            <h6><i class="fas fa-map-marker-alt"></i> Dirección de Entrega</h6>
                            <p class="text-muted">${pedido.direccion_entrega || pedido.cliente_direccion || 'No especificada'}</p>
                        </div>
                        
                        <!-- Notas -->
                        ${pedido.notas ? `
                        <div class="mt-3">
                            <h6><i class="fas fa-sticky-note"></i> Notas</h6>
                            <p class="text-muted">${pedido.notas}</p>
                        </div>
                        ` : ''}
                        
                        <!-- Información adicional según estado -->
                        ${crearInformacionAdicional(pedido, empaque, envio, entrega)}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Actualizar botones del footer según el estado
    actualizarBotonesDetalle(pedido, permisos);
}

// ========== FUNCIONES PARA MODIFICACIÓN DE PEDIDOS ==========

// Buscar productos por código o nombre
function buscarProductos(termino) {
    if (!termino || termino.length < 2) return [];
    
    termino = termino.toLowerCase();
    return productosDisponibles.filter(producto => {
        return producto.codigo.toLowerCase().includes(termino) ||
               producto.nombre.toLowerCase().includes(termino);
    }).slice(0, 10);
}

// Función mejorada para editar pedido
function editarPedido(id) {
    console.log('Editar pedido:', id);
    
    // Mostrar loading
    mostrarAlerta('Cargando información del pedido...', 'info');
    
    // Cargar productos disponibles
    cargarProductosDisponibles().then(() => {
        // Cargar detalle del pedido
        cargarDetallePedido(id);
    }).catch(error => {
        console.error('Error al cargar productos:', error);
        mostrarAlerta('Error al cargar productos disponibles', 'error');
    });
}

// Función simplificada para empacar pedido
function empacarPedido(id) {
    console.log('Empacar pedido:', id);
    
    // Configurar modal con datos del pedido
    configurarModalEmpaque(id);
    // Mostrar modal directamente
    $('#modalEmpacarPedido').modal('show');
}

function enviarPedido(id) {
    console.log('Enviar pedido:', id);
    $('#modalEnviarPedido').modal('show');
}

function entregarPedido(id) {
    console.log('Entregar pedido:', id);
    $('#modalEntregarPedido').modal('show');
}

function imprimirPedido(id) {
    console.log('Imprimir pedido:', id);
    $('#modalImprimirFactura').modal('show');
}

/*function cancelarPedido(id) {
    if (confirm('¿Está seguro que desea cancelar este pedido?')) {
        console.log('Cancelar pedido:', id);
        alert('Funcionalidad de cancelar pedido en desarrollo');
    }
}*/

// Cargar productos disponibles para el select
async function cargarProductosDisponibles() {
    try {
        const response = await fetch('php/pedidos.php?action=obtener_productos');
        const data = await response.json();
        
        if (data.success) {
            productosDisponibles = data.productos;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error al cargar productos:', error);
        throw error;
    }
}

// Cargar detalle del pedido a modificar
async function cargarDetallePedido(pedidoId) {
    try {
        const response = await fetch(`php/pedidos.php?action=obtener_detalle&pedido_id=${pedidoId}`);
        const data = await response.json();
        
        if (data.success) {
            pedidoActual = data.pedido;
            mostrarModalModificacion(data.pedido, data.detalles);
            ocultarAlerta();
        } else {
            mostrarAlerta(data.message, 'error');
        }
    } catch (error) {
        console.error('Error al cargar pedido:', error);
        mostrarAlerta('Error al cargar los datos del pedido', 'error');
    }
}

// Mostrar modal con los datos del pedido
function mostrarModalModificacion(pedido, detalles) {
    // Llenar información del pedido
    document.getElementById('pedidoModificarNumero').textContent = pedido.numero_pedido;
    document.getElementById('pedidoModificarCliente').textContent = pedido.cliente_nombre;
    document.getElementById('pedidoModificarFecha').textContent = new Date(pedido.fecha_pedido).toLocaleDateString('es-GT');
    // NUEVO: Llenar campo DTE
document.getElementById('pedidoModificarDte').value = pedido.dte || '';

    // ✅ DESCUENTO: cargar el descuento actual de la venta para poder editarlo
    const descuentoActual = parseFloat(pedido.descuento_venta ?? pedido.descuento) || 0;
    document.getElementById('descuentoModificar').value = descuentoActual.toFixed(2);
    const infoDescuento = document.getElementById('descuentoOriginalInfo');
    if (infoDescuento) {
        infoDescuento.textContent = descuentoActual > 0 ? `Descuento actual: Q${descuentoActual.toFixed(2)}` : '';
        infoDescuento.classList.toggle('d-none', descuentoActual <= 0);
    }

    // Limpiar tabla de productos
    const tbody = document.getElementById('tablaProductosModificar');
    tbody.innerHTML = '';
    contadorFilas = 0;
    
    detalles.forEach(detalle => {
    // ✅ NUEVO: Obtener es_complemento del detalle
    const esComplemento = detalle.es_complemento === 1 || detalle.es_complemento === '1';
    
    agregarFilaProducto(
        detalle.producto_id, 
        detalle.cantidad, 
        detalle.precio_unitario, // Precio original de la venta
        detalle.nombre,
        true, // mantenerCantidad
        esComplemento // ✅ NUEVO PARÁMETRO
    );
});
    
    // Calcular totales iniciales
    calcularTotalesModificacion();
    
    // Mostrar modal
    $('#modalModificarPedido').modal('show');
}

// REEMPLAZAR la función configurarBusquedaProducto en pedidos.js

function configurarBusquedaProducto(inputElement) {
    // ✅ Buscar el dropdown de resultados (hermano siguiente)
    const searchResults = inputElement.nextElementSibling;
    
    // ✅ Validar que existe
    if (!searchResults || !searchResults.classList.contains('search-results')) {
        console.error('Error: No se encontró el dropdown de resultados de búsqueda');
        return;
    }
    
    let timeoutId;
    
    inputElement.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const termino = this.value.trim();
        
        if (termino.length < 2) {
            searchResults.style.display = 'none';
            // NO limpiar el data-producto-id si ya está establecido
            // (solo si el usuario borra todo el texto)
            if (termino.length === 0) {
                this.dataset.productoId = '';
                actualizarFilaDesdeBusqueda(this);
            }
            return;
        }
        
        timeoutId = setTimeout(() => {
            const productos = buscarProductos(termino);
            mostrarResultadosBusqueda(productos, searchResults, inputElement);
        }, 300);
    });
    
    // Cerrar dropdown al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!inputElement.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
}

//mostrar resultados de busqueda
function mostrarResultadosBusqueda(productos, resultsContainer, inputElement) {
    resultsContainer.innerHTML = '';
    
    if (productos.length === 0) {
        resultsContainer.innerHTML = '<div class="dropdown-item text-muted">No se encontraron productos</div>';
        resultsContainer.style.display = 'block';
        return;
    }
    
    productos.forEach(producto => {
        const item = document.createElement('div');
        item.className = 'dropdown-item cursor-pointer';
        item.innerHTML = `
            <div class="d-flex justify-content-between">
                <div>
                    <strong>${producto.codigo}</strong> - ${producto.nombre}
                </div>
                <div class="text-muted">
                    Q${parseFloat(producto.precio_venta_sugerido).toFixed(2)} 
                    <span class="${producto.stock > 0 ? 'text-success' : 'text-danger'}">
                        (Stock: ${producto.stock})
                    </span>
                </div>
            </div>
        `;
        
        if (producto.stock <= 0) {
            item.classList.add('disabled');
            item.style.opacity = '0.6';
            item.style.cursor = 'not-allowed';
        } else {
            item.addEventListener('click', function() {
                seleccionarProducto(producto, inputElement, resultsContainer);
            });
        }
        
        resultsContainer.appendChild(item);
    });
    
    resultsContainer.style.display = 'block';
}

// Seleccionar un producto de los resultados
// REEMPLAZAR la función seleccionarProducto en pedidos.js

function seleccionarProducto(producto, inputElement, resultsContainer) {
    // Establecer el texto visible
    inputElement.value = `${producto.codigo} - ${producto.nombre}`;
    
    // ✅ Establecer el data-producto-id
    inputElement.dataset.productoId = producto.id.toString();
    inputElement.setAttribute('data-producto-id', producto.id.toString());
    
    // Cerrar dropdown
    resultsContainer.style.display = 'none';
    
    // Actualizar la fila con la información del producto
    // El segundo parámetro es el producto, el tercero es false porque es un producto NUEVO que se está agregando
    actualizarFilaDesdeBusqueda(inputElement, producto, false);
}

// REEMPLAZAR ESTA FUNCIÓN COMPLETA EN pedidos.js

function actualizarFilaDesdeBusqueda(inputElement, producto = null, esProductoExistente = false) {
    const row = inputElement.closest('tr');
    
    // Buscar elementos con validación
    const stockInfo = inputElement.parentElement.querySelector('.stock-info');
    const cantidadInput = row.querySelector('.cantidad-input-mod');
    const precioInput = row.querySelector('.precio-input-mod');
    
    // Validar que existan los elementos necesarios
    if (!cantidadInput || !precioInput) {
        console.error('Error: No se encontraron los inputs de cantidad o precio en la fila');
        return;
    }
    
    if (producto) {
        // ✅ CRÍTICO: Guardar valores ANTES de cualquier modificación
        const cantidadOriginal = parseInt(cantidadInput.value);
        const precioOriginal = parseFloat(precioInput.value);
        
        console.log(`📋 Actualizando fila: Producto ${producto.codigo}, EsExistente: ${esProductoExistente}, CantOriginal: ${cantidadOriginal}, PrecioOriginal: ${precioOriginal}`);
        
        // =====================================================
        // PASO 1: ACTUALIZAR PRECIO (solo para productos NUEVOS)
        // =====================================================
        if (esProductoExistente) {
            // ✅ PRODUCTOS EXISTENTES: MANTENER PRECIO ORIGINAL
            console.log(`  ✓ Producto existente: Manteniendo precio original Q${precioOriginal}`);
            // NO tocar precioInput.value - ya tiene el precio correcto
        } else {
            // ✅ PRODUCTOS NUEVOS: Usar precio_venta_sugerido
            const precioSugerido = parseFloat(producto.precio_venta_sugerido);
            console.log(`  ✓ Producto nuevo: Aplicando precio sugerido Q${precioSugerido}`);
            precioInput.value = precioSugerido.toFixed(2);
        }
        
        // Hacer el campo editable y agregar validación de precio mínimo
        precioInput.removeAttribute('readonly');
        precioInput.min = parseFloat(producto.precio_venta_real).toFixed(2);
        precioInput.step = "0.00";
        precioInput.dataset.precioMinimo = producto.precio_venta_real;
        
        // Agregar evento de validación al campo de precio si no existe
        if (!precioInput.hasAttribute('data-validation-added')) {
            precioInput.addEventListener('change', function() {
                validarPrecioMinimo(this);
            });
            precioInput.setAttribute('data-validation-added', 'true');
        }
        
        // =====================================================
        // PASO 2: ACTUALIZAR STOCK INFO
        // =====================================================
        if (stockInfo) {
            if (esProductoExistente) {
                // Para productos existentes del pedido
                const stockAdicionalDisponible = producto.stock;
                const maxCantidadPermitida = cantidadOriginal + stockAdicionalDisponible;
                
                stockInfo.innerHTML = `
                    <i class="fas fa-info-circle text-info"></i>
                    <strong>Cantidad original:</strong> ${cantidadOriginal} unidades | 
                    <strong>Stock adicional:</strong> ${stockAdicionalDisponible} | 
                    <strong>Máximo permitido:</strong> ${maxCantidadPermitida} | 
                    <strong>Precio mínimo:</strong> Q${parseFloat(producto.precio_venta_real).toFixed(2)}
                `;
                stockInfo.className = 'text-info stock-info mt-1';
                
                console.log(`  ℹ️ Stock info: Original=${cantidadOriginal}, Adicional=${stockAdicionalDisponible}, Max=${maxCantidadPermitida}`);
            } else {
                // Para productos nuevos
                stockInfo.innerHTML = `
                    <i class="fas fa-box text-primary"></i>
                    <strong>Stock disponible:</strong> ${producto.stock} unidades | 
                    <strong>Precio mínimo:</strong> Q${parseFloat(producto.precio_venta_real).toFixed(2)}
                `;
                stockInfo.className = producto.stock > 0 ? 'text-primary stock-info mt-1' : 'text-danger stock-info mt-1';
                
                console.log(`  ℹ️ Stock info: Disponible=${producto.stock}`);
            }
        }
        
        // =====================================================
        // PASO 3: ACTUALIZAR LÍMITES DE CANTIDAD
        // =====================================================
        if (esProductoExistente) {
            // ✅ PRODUCTOS EXISTENTES: Permitir cantidad original + stock adicional
            const maxCantidad = cantidadOriginal + producto.stock;
            cantidadInput.max = maxCantidad;
            cantidadInput.min = '1'; // Mínimo 1
            
            console.log(`  ✓ Límites de cantidad: Min=1, Max=${maxCantidad}`);
            
            // ✅✅✅ CRÍTICO: NO MODIFICAR LA CANTIDAD ORIGINAL
            // Mantener la cantidad tal como está
            console.log(`  ✅ MANTENIENDO cantidad original: ${cantidadOriginal}`);
            
        } else {
            // ✅ PRODUCTOS NUEVOS: Solo el stock disponible
            cantidadInput.max = producto.stock;
            cantidadInput.min = '1';
            
            console.log(`  ✓ Límites de cantidad: Min=1, Max=${producto.stock}`);
            
            // Solo validar si la cantidad supera el stock para productos NUEVOS
            if (parseInt(cantidadInput.value) > producto.stock) {
                const nuevaCantidad = Math.min(producto.stock, 1);
                console.log(`  ⚠️ Cantidad ${cantidadInput.value} supera stock. Ajustando a ${nuevaCantidad}`);
                cantidadInput.value = nuevaCantidad;
            }
        }
        
        // =====================================================
        // PASO 4: RECALCULAR SUBTOTAL
        // =====================================================
        actualizarSubtotalModificacion(cantidadInput);
        
    } else {
        // Si no hay producto seleccionado
        precioInput.value = '0.00';
        precioInput.setAttribute('readonly', true);
        precioInput.removeAttribute('min');
        precioInput.removeAttribute('data-precio-minimo');
        
        if (stockInfo) {
            stockInfo.textContent = '';
        }
        
        actualizarSubtotalModificacion(cantidadInput);
    }
    
    calcularTotalesModificacion();
}

// Validar que el precio no sea menor al mínimo permitido
function validarPrecioMinimo(input) {
    const precioMinimo = parseFloat(input.dataset.precioMinimo);
    const precioActual = parseFloat(input.value);
    
    // ✅ Validar que los valores sean números válidos
    if (isNaN(precioMinimo) || isNaN(precioActual)) {
        return;
    }
    
   if (precioActual < precioMinimo) {
    mostrarAlerta(`El precio no puede ser menor a Q${precioMinimo.toFixed(2)} (precio de venta real)`, 'warning');
    input.value = precioMinimo.toFixed(2);
    input.focus();
}
    
    // Recalcular subtotal después de la validación
    actualizarSubtotalModificacion(input);
}


function agregarFilaProducto(productoId = '', cantidad = 1, precioOriginal = 0, nombreProducto = '', mantenerCantidad = true, esComplemento = false) {
    contadorFilas++;
    const tbody = document.getElementById('tablaProductosModificar');
    
    // ✅ CRÍTICO: Usar el productoId DIRECTAMENTE, no depender de find()
    const productoIdNum = productoId ? parseInt(productoId) : null;
    
    let productoSeleccionado = null;
    let precioAUsar = precioOriginal;
    
    // Intentar encontrar el producto en la lista disponible (para stock, precios, etc)
    if (productoIdNum) {
        productoSeleccionado = productosDisponibles.find(p => parseInt(p.id) === productoIdNum);
        
        if (productoSeleccionado) {
            if (precioOriginal > 0) {
                precioAUsar = precioOriginal;
            } else {
                precioAUsar = productoSeleccionado.precio_venta_real;
            }
        } else {
            // ⚠️ IMPORTANTE: Si no se encuentra, usar el precio original
            // (puede ser un producto sin stock o inactivo)
            precioAUsar = precioOriginal > 0 ? precioOriginal : 0;
        }
    }
    
    const cantidadAUsar = mantenerCantidad ? cantidad : 1;
    
    // ✅ CREAR FILA CON JAVASCRIPT PURO (NO innerHTML)
    const row = document.createElement('tr');
    row.setAttribute('data-fila', contadorFilas);
    row.setAttribute('data-es-existente', precioOriginal > 0 ? 'true' : 'false');
    
    // === COLUMNA 1: PRODUCTO ===
    const colProducto = document.createElement('td');
    const divContainer = document.createElement('div');
    divContainer.className = 'position-relative';
    
    // ✅ CREAR INPUT CON JAVASCRIPT PURO Y ESTABLECER data-producto-id DIRECTAMENTE
    const inputBusqueda = document.createElement('input');
    inputBusqueda.type = 'text';
    inputBusqueda.className = 'form-control form-control-sm producto-search';
    inputBusqueda.placeholder = 'Buscar por código o nombre...';
    inputBusqueda.autocomplete = 'off';
    
    // ✅✅✅ CRÍTICO: Establecer SIEMPRE el data-producto-id, incluso si no está en productosDisponibles
    if (productoIdNum) {
        inputBusqueda.setAttribute('data-producto-id', productoIdNum.toString());
        inputBusqueda.dataset.productoId = productoIdNum.toString();
    }
    
    // Establecer valor visible
    if (productoSeleccionado) {
        inputBusqueda.value = `${productoSeleccionado.codigo} - ${productoSeleccionado.nombre}`;
    } else if (nombreProducto && productoIdNum) {
        // Si no se encontró pero tenemos nombre (del detalle), usarlo
        inputBusqueda.value = nombreProducto;
    }
    
    // Crear dropdown de resultados
    const dropdownResultados = document.createElement('div');
    dropdownResultados.className = 'dropdown-menu search-results position-absolute w-100';
    dropdownResultados.style.display = 'none';
    dropdownResultados.style.maxHeight = '200px';
    dropdownResultados.style.overflowY = 'auto';
    dropdownResultados.style.zIndex = '1050';
    
    // Crear info de stock
    const stockInfo = document.createElement('small');
    stockInfo.className = 'text-muted stock-info';
    if (productoSeleccionado) {
        stockInfo.textContent = `Stock disponible: ${productoSeleccionado.stock} | Precio mínimo: Q${parseFloat(productoSeleccionado.precio_venta_real).toFixed(2)}`;
        stockInfo.className = 'text-info';
    } else if (productoIdNum) {
        stockInfo.textContent = 'Producto cargado del pedido';
        stockInfo.className = 'text-info';
    }
    
    divContainer.appendChild(inputBusqueda);
    divContainer.appendChild(dropdownResultados);
    divContainer.appendChild(stockInfo);
    colProducto.appendChild(divContainer);
    
    // === COLUMNA 2: CANTIDAD ===
    const colCantidad = document.createElement('td');
    const inputCantidad = document.createElement('input');
    inputCantidad.type = 'number';
    inputCantidad.className = 'form-control form-control-sm cantidad-input-mod';
    inputCantidad.value = cantidadAUsar;
    inputCantidad.min = '1';
    if (productoSeleccionado) {
        inputCantidad.max = productoSeleccionado.stock;
    }
    inputCantidad.onchange = function() { actualizarSubtotalModificacion(this); };
    inputCantidad.onkeyup = function() { actualizarSubtotalModificacion(this); };
    colCantidad.appendChild(inputCantidad);
    
    // === COLUMNA 3: PRECIO UNITARIO ===
    const colPrecio = document.createElement('td');
    const inputPrecio = document.createElement('input');
    inputPrecio.type = 'number';
    inputPrecio.className = 'form-control form-control-sm precio-input-mod';
    inputPrecio.value = precioAUsar;
    inputPrecio.step = '0.00';
    inputPrecio.onchange = function() { actualizarSubtotalModificacion(this); };
    inputPrecio.onkeyup = function() { actualizarSubtotalModificacion(this); };
    
    if (productoSeleccionado) {
    inputPrecio.removeAttribute('readonly');
    inputPrecio.min = parseFloat(productoSeleccionado.precio_venta_real).toFixed(2);
    inputPrecio.dataset.precioMinimo = productoSeleccionado.precio_venta_real;
} else {
        inputPrecio.setAttribute('readonly', true);
    }
    
    colPrecio.appendChild(inputPrecio);
    
    // === COLUMNA 4: SUBTOTAL ===
    const colSubtotal = document.createElement('td');
    const inputSubtotal = document.createElement('input');
    inputSubtotal.type = 'number';
    inputSubtotal.className = 'form-control form-control-sm subtotal-input-mod';
    inputSubtotal.value = (cantidadAUsar * precioAUsar).toFixed(2);
    inputSubtotal.readOnly = true;
    colSubtotal.appendChild(inputSubtotal);
    
    // === COLUMNA 5: ACCIONES ===
    const colAcciones = document.createElement('td');
    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.className = 'btn btn-sm btn-danger';
    btnEliminar.title = 'Eliminar producto';
    btnEliminar.onclick = function() { eliminarFilaModificacion(this); };
    btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';
    colAcciones.appendChild(btnEliminar);
    
    // Agregar todas las columnas a la fila
    row.appendChild(colProducto);
    row.appendChild(colCantidad);
    row.appendChild(colPrecio);
    row.appendChild(colSubtotal);
    row.appendChild(colAcciones);
    
    // Agregar fila al tbody
    tbody.appendChild(row);
    
    // ✅ LOG para verificar
    console.log(`✓ Fila ${contadorFilas} creada: ProductoID=${productoIdNum}, Nombre="${inputBusqueda.value}", data-producto-id="${inputBusqueda.dataset.productoId}"`);
    
    // Configurar búsqueda
    configurarBusquedaProducto(inputBusqueda);
    
    // ✅ CORRECCIÓN: Pasar el flag correcto a actualizarFilaDesdeBusqueda
    if (productoSeleccionado) {
        // Determinar si es producto existente:
        // - Si precioOriginal > 0 AND mantenerCantidad = true → es existente
        const esExistente = (precioOriginal > 0 && mantenerCantidad);
        
        console.log(`📦 agregarFilaProducto: ID=${productoIdNum}, EsExistente=${esExistente}, Cantidad=${cantidadAUsar}, Precio=${precioAUsar}`);
        
        actualizarFilaDesdeBusqueda(inputBusqueda, productoSeleccionado, esExistente);
    }
}


// Actualizar subtotal de una fila
// Actualizar subtotal de una fila
function actualizarSubtotalModificacion(input) {
    const row = input.closest('tr');
    const cantidadInput = row.querySelector('.cantidad-input-mod');
    const precioInput = row.querySelector('.precio-input-mod');
    
    const cantidad = parseInt(cantidadInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;
    const subtotal = cantidad * precio;
    
    row.querySelector('.subtotal-input-mod').value = subtotal.toFixed(2);
    calcularTotalesModificacion();
}

// Eliminar fila de modificación
function eliminarFilaModificacion(button) {
    button.closest('tr').remove();
    calcularTotalesModificacion();
}

// Agregar nueva fila vacía
function agregarNuevaFilaModificacion() {
    agregarFilaProducto('', 1, 0, '', false); // false = nueva fila, cantidad = 1
}

/**
 * Calcular totales de modificación
 * MODIFICADO: Excluir productos complemento del cálculo de subtotal/total
 */
function calcularTotalesModificacion() {
    let subtotal = 0;
    
    // ✅ MODIFICADO: Recorrer filas y verificar si son complemento
    const filas = document.querySelectorAll('#tablaProductosModificar tr');
    
    filas.forEach(fila => {
        const subtotalInput = fila.querySelector('.subtotal-input-mod');
        const precioInput = fila.querySelector('.precio-input-mod');
        
        if (!subtotalInput || !precioInput) return;
        
        const subtotalProducto = parseFloat(subtotalInput.value) || 0;
        const precio = parseFloat(precioInput.value) || 0;
        
        // ✅ CRÍTICO: Solo sumar si el precio NO es 0 (no es complemento)
        // Los complementos tienen precio 0, así que no se suman
        if (precio > 0) {
            subtotal += subtotalProducto;
        }
    });
    
    subtotal = Math.round(subtotal * 100) / 100;

    // ✅ DESCUENTO: el total es subtotal - descuento
    const descuentoInput = document.getElementById('descuentoModificar');
    let descuento = descuentoInput ? (parseFloat(descuentoInput.value) || 0) : 0;
    const descuentoInvalido = descuento < 0 || descuento > subtotal;

    if (descuentoInput) {
        descuentoInput.classList.toggle('is-invalid', descuentoInvalido);
    }

    const total = Math.round((subtotal - Math.max(0, Math.min(descuento, subtotal))) * 100) / 100;

    document.getElementById('subtotalModificar').value = subtotal.toFixed(2);
    document.getElementById('totalModificar').value = total.toFixed(2);
}

// REEMPLAZAR guardarModificacionesPedido en pedidos.js

async function guardarModificacionesPedido() {
    if (!pedidoActual) {
        mostrarAlerta('No hay pedido seleccionado', 'error');
        return;
    }
    
    const filas = document.querySelectorAll('#tablaProductosModificar tr');
    if (filas.length === 0) {
        mostrarAlerta('Debe agregar al menos un producto', 'error');
        return;
    }
    
    const dte = document.getElementById('pedidoModificarDte').value.trim();
    
    console.log('=== VALIDANDO PRODUCTOS PARA GUARDAR ===');
    console.log('Total de filas:', filas.length);
    
    const productos = [];
    let errorEncontrado = false;
    let mensajeError = '';
    
    // Validar cada fila
    filas.forEach((fila, index) => {
        if (errorEncontrado) return; // Si ya hay error, saltar
        
        const inputBusqueda = fila.querySelector('.producto-search');
        const cantidadInput = fila.querySelector('.cantidad-input-mod');
        const precioInput = fila.querySelector('.precio-input-mod');
        
        // ✅ Obtener el data-producto-id de AMBAS formas
        const productoId = inputBusqueda.dataset.productoId || 
                          inputBusqueda.getAttribute('data-producto-id');
        
        const cantidad = parseInt(cantidadInput.value);
        const precio = parseFloat(precioInput.value);
        
        console.log(`Fila ${index + 1}:`, {
            productoId,
            valorVisible: inputBusqueda.value,
            cantidad,
            precio
        });
        
        // Validar producto ID
        if (!productoId || productoId === '' || productoId === 'null' || productoId === 'undefined') {
            mensajeError = `Fila ${index + 1}: No se identificó el producto.\n\n${inputBusqueda.value ? 'Producto mostrado: ' + inputBusqueda.value : 'Fila vacía'}\n\nPor favor elimine esta fila o seleccione un producto válido.`;
            errorEncontrado = true;
            fila.style.backgroundColor = '#ffebee';
            fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // Validar cantidad
        if (!cantidad || cantidad <= 0) {
            mensajeError = `Fila ${index + 1}: La cantidad debe ser mayor a 0`;
            errorEncontrado = true;
            fila.style.backgroundColor = '#ffebee';
            fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // Validar precio
if (precio === '' || precio < 0 || isNaN(precio)) {
    mensajeError = `Fila ${index + 1}: El precio no puede ser negativo ni estar vacío`;
    errorEncontrado = true;
    fila.style.backgroundColor = '#ffebee';
    fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
}

        
        // Validar stock (si el producto existe en productosDisponibles)
        const producto = productosDisponibles.find(p => parseInt(p.id) === parseInt(productoId));
        if (producto && cantidad > producto.stock) {
            mensajeError = `Fila ${index + 1}: Stock insuficiente\n\nProducto: ${producto.nombre}\nDisponible: ${producto.stock}\nSolicitado: ${cantidad}`;
            errorEncontrado = true;
            fila.style.backgroundColor = '#ffebee';
            fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        // ✅ NUEVO: Determinar si es complemento (precio = 0)
const esComplemento = (precio === 0 || precio === 0.00) ? 1 : 0;

// Producto válido - INCLUIR es_complemento
productos.push({
    producto_id: parseInt(productoId),
    cantidad: cantidad,
    precio_unitario: precio,
    es_complemento: esComplemento // ✅ NUEVO CAMPO
});
    });
    
    // Si hay error, mostrar y detener
    if (errorEncontrado) {
        console.error('❌ Error de validación:', mensajeError);
        mostrarAlerta(mensajeError, 'error');
        
        // Limpiar fondo rojo después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('#tablaProductosModificar tr').forEach(f => {
                f.style.backgroundColor = '';
            });
        }, 5000);
        
        return;
    }
    
    // Verificar que haya productos
    if (productos.length === 0) {
        mostrarAlerta('No hay productos válidos para guardar', 'error');
        return;
    }
    
    // ✅ DESCUENTO: validar contra el subtotal (sin complementos)
    calcularTotalesModificacion();
    const subtotalNuevo = parseFloat(document.getElementById('subtotalModificar').value) || 0;
    const descuentoTexto = document.getElementById('descuentoModificar').value.trim();
    const descuento = descuentoTexto === '' ? 0 : Math.round(parseFloat(descuentoTexto) * 100) / 100;

    if (isNaN(descuento) || descuento < 0) {
        mostrarAlerta('El descuento no puede ser negativo', 'error');
        return;
    }
    if (descuento > subtotalNuevo) {
        mostrarAlerta(`El descuento (Q${descuento.toFixed(2)}) no puede ser mayor al subtotal (Q${subtotalNuevo.toFixed(2)})`, 'error');
        return;
    }
    const totalNuevo = Math.round((subtotalNuevo - descuento) * 100) / 100;

    console.log('✓ Validación exitosa');
    console.log('Productos a guardar:', productos, 'Descuento:', descuento);

    // Confirmar
    if (!confirm(`¿Está seguro de guardar las modificaciones?\n\nPedido: ${pedidoActual.numero_pedido}\nProductos: ${productos.length}\nSubtotal: Q${subtotalNuevo.toFixed(2)}\nDescuento: Q${descuento.toFixed(2)}\nTotal: Q${totalNuevo.toFixed(2)}`)) {
        return;
    }
    
    // Deshabilitar botón
    const botonGuardar = document.getElementById('btnGuardarModificacion');
    const textoOriginal = botonGuardar.innerHTML;
    botonGuardar.disabled = true;
    botonGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    
    try {
        mostrarAlerta('Guardando modificaciones...', 'info');
        
        const response = await fetch('php/pedidos.php?action=modificar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: pedidoActual.id,
                productos: productos,
                descuento: descuento,
                dte: dte,
                usuario_id: JSON.parse(localStorage.getItem('userData')).id
            })
        });
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("La respuesta del servidor no es JSON válida");
        }
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✓✓✓ Pedido modificado exitosamente');
            mostrarAlerta('¡Pedido modificado exitosamente!', 'success');
            
            setTimeout(() => {
                $('#modalModificarPedido').modal('hide');
                ocultarAlerta();
                recargarTablaPedidos();
            }, 2000);
            
        } else {
            console.error('Error del servidor:', data);
            mostrarAlerta(data.message || 'Error desconocido', 'error');
        }
        
    } catch (error) {
        console.error('Error al modificar pedido:', error);
        mostrarAlerta('Error al guardar: ' + error.message, 'error');
    } finally {
        botonGuardar.disabled = false;
        botonGuardar.innerHTML = textoOriginal;
    }
}

// Función para generar comprobante del pedido modificado
function generarComprobantePedido() {
    if (!pedidoActual) {
        mostrarAlerta('No hay pedido seleccionado', 'error');
        return;
    }
    
    const url = `php/comprobante.php?id=${pedidoActual.venta_id}`;
    window.open(url, '_blank');
}

// ========== FUNCIONES AUXILIARES PARA DETALLE ==========

function crearBadgeEstado(estado) {
    const badges = {
        'pendiente': '<span class="badge bg-warning text-dark">Pendiente</span>',
        'empacado': '<span class="badge bg-info">Empacado</span>',
        'enviado': '<span class="badge bg-primary">Enviado</span>',
        'entregado': '<span class="badge bg-success">Entregado</span>', // FINAL
        'cancelado': '<span class="badge bg-danger">Cancelado</span>'
    };
    return badges[estado] || '<span class="badge bg-secondary">Sin Estado</span>';
}

function crearProgresoVisual(estado) {
    // ACTUALIZADO: Eliminamos 'completado' del array
    const estados = ['pendiente', 'empacado', 'enviado', 'entregado'];
    const iconos = ['fa-file-alt', 'fa-box', 'fa-truck', 'fa-check-circle'];
    const nombres = ['Recibido', 'Empacado', 'Enviado', 'Entregado'];
    
    let html = '<div class="order-progress d-flex justify-content-between position-relative">';
    
    estados.forEach((est, index) => {
        const isCompleted = estados.indexOf(estado) > index;
        const isActive = estado === est;
        const isCanceled = estado === 'cancelado';
        
        let claseEstado = '';
        if (isCanceled) {
            claseEstado = index === 0 ? 'completed' : '';
        } else if (isCompleted) {
            claseEstado = 'completed';
        } else if (isActive) {
            claseEstado = 'active';
        }
        
        html += `
            <div class="order-step text-center">
                <div class="step-icon ${claseEstado} mb-2">
                    <i class="fas ${iconos[index]}"></i>
                </div>
                <div class="step-text ${claseEstado}">${nombres[index]}</div>
            </div>
        `;
    });
    
    html += '</div>';
    
    if (!document.getElementById('progress-styles')) {
        const styles = `
            <style id="progress-styles">
            .order-progress .order-step {
                flex: 1;
                position: relative;
            }
            .order-progress .step-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto;
                background-color: #e9ecef;
                color: #6c757d;
                font-size: 16px;
            }
            .order-progress .step-icon.completed {
                background-color: #28a745;
                color: white;
            }
            .order-progress .step-icon.active {
                background-color: #ffc107;
                color: #212529;
            }
            .order-progress .step-text {
                font-size: 12px;
                color: #6c757d;
                margin-top: 5px;
            }
            .order-progress .step-text.completed,
            .order-progress .step-text.active {
                color: #212529;
                font-weight: bold;
            }
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
            .timeline {
                position: relative;
                padding-left: 20px;
            }
            .timeline::before {
                content: '';
                position: absolute;
                left: 8px;
                top: 0;
                bottom: 0;
                width: 2px;
                background-color: #dee2e6;
            }
            .timeline-item {
                position: relative;
            }
            .timeline-marker {
                position: absolute;
                left: -24px;
                top: 2px;
                width: 16px;
                height: 16px;
                border-radius: 50%;
                border: 2px solid white;
            }
            .timeline-content {
                padding-left: 10px;
            }
            .timeline-title {
                font-weight: 500;
                margin-bottom: 2px;
            }
            </style>
        `;
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    return html;
}

/**
 * Crear filas de productos para el detalle del pedido
 * MODIFICADO: Mostrar badge "Complemento" para productos con es_complemento = 1
 * @param {Array} detalles - Array de detalles del pedido
 */
function crearFilasProductos(detalles) {
    if (!detalles || detalles.length === 0) {
        return '<tr><td colspan="5" class="text-center text-muted">No hay productos en este pedido</td></tr>';
    }
    
    return detalles.map(detalle => {
        // ✅ NUEVO: Verificar si es complemento
        const esComplemento = detalle.es_complemento === 1 || detalle.es_complemento === '1';
        
        // ✅ NUEVO: Crear badge si es complemento
        const badgeComplemento = esComplemento 
            ? ' <span class="badge bg-success"><i class="fas fa-gift"></i> Complemento</span>' 
            : '';
        
        // ✅ NUEVO: Fondo verde claro para complementos
        const estiloFila = esComplemento ? ' style="background-color: #d4edda;"' : '';
        
        return `
        <tr${estiloFila}>
            <td><code>${detalle.producto_codigo || 'N/A'}</code></td>
            <td>${detalle.producto_nombre || 'Producto sin nombre'}${badgeComplemento}</td>
            <td class="text-center">${detalle.cantidad}</td>
            <td class="text-end">Q${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
            <td class="text-end"><strong>Q${parseFloat(detalle.subtotal).toFixed(2)}</strong></td>
        </tr>
    `;
    }).join('');
}

function crearHistorialPedido(historial) {
    if (!historial || historial.length === 0) {
        return '<p class="text-muted">No hay historial disponible para este pedido.</p>';
    }
    
    let html = '<div class="timeline">';
    
    historial.forEach(item => {
        const fecha = new Date(item.fecha_cambio).toLocaleString('es-GT');
        const estadoAnterior = item.estado_anterior ? item.estado_anterior : 'nuevo';
        const estadoNuevo = item.estado_nuevo;
        
        html += `
            <div class="timeline-item mb-3">
                <div class="timeline-marker bg-primary"></div>
                <div class="timeline-content">
                    <div class="timeline-date text-muted small">${fecha}</div>
                    <div class="timeline-title">
                        Estado cambiado: ${estadoAnterior} → <strong>${estadoNuevo}</strong>
                    </div>
                    ${item.notas ? `<div class="timeline-description text-muted small">${item.notas}</div>` : ''}
                    <div class="timeline-user text-muted small">
                        <i class="fas fa-user"></i> ${item.usuario_nombre || 'Sistema'}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

function crearResumenPedido(pedido) {
    const subtotal = parseFloat(pedido.subtotal);
    const descuento = parseFloat(pedido.descuento) || 0;
    const total = parseFloat(pedido.total);
    
    return `
        <div class="order-summary">
            <div class="d-flex justify-content-between mb-2">
                <span>Subtotal:</span>
                <span>Q${subtotal.toFixed(2)}</span>
            </div>
            ${descuento > 0 ? `
            <div class="d-flex justify-content-between mb-2 text-success">
                <span>Descuento:</span>
                <span>-Q${descuento.toFixed(2)}</span>
            </div>
            ` : ''}
            <hr>
            <div class="d-flex justify-content-between">
                <span><strong>Total:</strong></span>
                <span><strong class="text-success">Q${total.toFixed(2)}</strong></span>
            </div>
        </div>
    `;
}

function crearInformacionAdicional(pedido, empaque, envio, entrega) {
    let html = '';
    
    if (empaque) {
        const fechaEmpaque = new Date(empaque.fecha_empaque).toLocaleString('es-GT');
        html += `
            <div class="mt-3">
                <h6><i class="fas fa-box text-info"></i> Información de Empaque</h6>
                <div class="small text-muted">
                    <div><strong>Empacado por:</strong> ${empaque.empacador_nombre}</div>
                    <div><strong>Fecha:</strong> ${fechaEmpaque}</div>
                    ${empaque.notas_empaque ? `<div><strong>Notas:</strong> ${empaque.notas_empaque}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (envio) {
        const fechaEnvio = new Date(envio.fecha_envio).toLocaleString('es-GT');
        html += `
            <div class="mt-3">
                <h6><i class="fas fa-truck text-primary"></i> Información de Envío</h6>
                <div class="small text-muted">
                    ${envio.transportista ? `<div><strong>Transportista:</strong> ${envio.transportista}</div>` : ''}
                    ${envio.numero_guia ? `<div><strong>No. Guía:</strong> ${envio.numero_guia}</div>` : ''}
                    <div><strong>Fecha de envío:</strong> ${fechaEnvio}</div>
                    ${envio.costo_envio > 0 ? `<div><strong>Costo envío:</strong> Q${parseFloat(envio.costo_envio).toFixed(2)}</div>` : ''}
                    ${envio.notas_envio ? `<div><strong>Notas:</strong> ${envio.notas_envio}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (entrega) {
        const fechaEntrega = new Date(entrega.fecha_entrega).toLocaleString('es-GT');
        html += `
            <div class="mt-3">
                <h6><i class="fas fa-check-circle text-success"></i> Información de Entrega</h6>
                <div class="small text-muted">
                    <div><strong>Recibido por:</strong> ${entrega.recibido_por}</div>
                    <div><strong>Fecha:</strong> ${fechaEntrega}</div>
                    ${entrega.notas_entrega ? `<div><strong>Notas:</strong> ${entrega.notas_entrega}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    return html;
}

function actualizarBotonesDetalle(pedido) {
    const modalFooter = document.querySelector('#modalDetallePedido .modal-footer');
    
    // Obtener datos del usuario actual
    const userData = JSON.parse(localStorage.getItem('userData'));
    const rolUsuario = userData.rol;
    
    // Verificar si el usuario puede gestionar pedidos (solo admin y bodeguero)
    const puedeGestionar = ['administrador', 'bodeguero'].includes(rolUsuario);
    
    // Botón cerrar - siempre visible
    let botones = `
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cerrar
        </button>
    `;
    
    // Solo agregar botones de gestión si el usuario tiene permisos
    if (puedeGestionar) {
        // **CAMBIO PRINCIPAL: Botón Modificar disponible en todos los estados excepto cancelado**
        if (pedido.estado !== 'cancelado') {
            botones += `
                <button type="button" class="btn btn-info" onclick="editarPedido(${pedido.id})" data-bs-dismiss="modal">
                    <i class="fas fa-edit"></i> Modificar
                </button>
            `;
        }
        
        // Botones específicos según el estado
        if (pedido.estado === 'pendiente') {
            botones += `
                <button type="button" class="btn btn-success" onclick="empacarPedido(${pedido.id})" data-bs-dismiss="modal">
                    <i class="fas fa-box"></i> Marcar como Empacado
                </button>
            `;
        } else if (pedido.estado === 'empacado') {
            botones += `
                <button type="button" class="btn btn-warning" onclick="enviarPedido(${pedido.id})" data-bs-dismiss="modal">
                    <i class="fas fa-truck"></i> Marcar como Enviado
                </button>
            `;
        } else if (pedido.estado === 'enviado') {
            botones += `
                <button type="button" class="btn btn-success" onclick="entregarPedido(${pedido.id})" data-bs-dismiss="modal">
                    <i class="fas fa-check-circle"></i> Marcar como Entregado
                </button>
            `;
        
        }
    }
    
    // Botón imprimir comprobante - visible para todos los roles
    botones += `
        <button type="button" class="btn btn-outline-primary" onclick="imprimirComprobantePedido(${pedido.venta_id})">
            <i class="fas fa-print"></i> Imprimir Comprobante
        </button>
    `;
    
    modalFooter.innerHTML = botones;
}

function imprimirComprobantePedido(ventaId) {
    const url = `php/comprobante.php?id=${ventaId}`;
    window.open(url, '_blank');
}

// ========== FUNCIONES DE UTILIDAD ==========

function mostrarAlerta(mensaje, tipo = 'info') {
    const alerta = document.getElementById('alertaModificacion');
    const mensajeSpan = document.getElementById('mensajeAlerta');
    
    if (!alerta || !mensajeSpan) {
        console.warn('Elementos de alerta no encontrados');
        console.log('Mensaje:', mensaje, 'Tipo:', tipo);
        return;
    }
    
    alerta.className = 'alert mt-3';
    switch(tipo) {
        case 'success':
            alerta.classList.add('alert-success');
            break;
        case 'error':
            alerta.classList.add('alert-danger');
            break;
        case 'warning':
            alerta.classList.add('alert-warning');
            break;
        default:
            alerta.classList.add('alert-info');
    }
    
    mensajeSpan.textContent = mensaje;
    alerta.classList.remove('d-none');
}

function ocultarAlerta() {
    const alerta = document.getElementById('alertaModificacion');
    if (alerta) {
        alerta.classList.add('d-none');
    }
}

function recargarTablaPedidos() {
    if (typeof tablaPedidos !== 'undefined') {
        tablaPedidos.ajax.reload();
    }
}

// REEMPLAZAR esta función en pedidos.js

// Función para obtener fecha/hora actual en Guatemala
function obtenerFechaHoraGuatemala() {
    const ahora = new Date();
    
    // Crear fecha en zona horaria de Guatemala (GMT-6)
    const fechaGuatemala = new Date(ahora.toLocaleString("en-US", {timeZone: "America/Guatemala"}));
    
    // Formatear para datetime-local (YYYY-MM-DDTHH:MM)
    const year = fechaGuatemala.getFullYear();
    const month = String(fechaGuatemala.getMonth() + 1).padStart(2, '0');
    const day = String(fechaGuatemala.getDate()).padStart(2, '0');
    const hours = String(fechaGuatemala.getHours()).padStart(2, '0');
    const minutes = String(fechaGuatemala.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// REEMPLAZAR en la función configurarModalEmpaque:
function configurarModalEmpaque(pedidoId) {
    // Guardar ID del pedido en el modal
    document.getElementById('modalEmpacarPedido').dataset.pedidoId = pedidoId;
    
    // Establecer fecha y hora actual de Guatemala - CORREGIDO
    const fechaGuatemala = obtenerFechaHoraGuatemala();
    document.getElementById('fechaEmpaque').value = fechaGuatemala;
    
    // Limpiar notas
    document.getElementById('notasEmpaque').value = '';
    
    // Actualizar título del modal
    document.querySelector('#modalEmpacarPedido .modal-title').textContent = `Marcar Pedido como Empacado`;
    
    // Ocultar alerta si existe
    const alerta = document.getElementById('alertaEmpaque');
    if (alerta) {
        alerta.style.display = 'none';
    }
}

// TAMBIÉN REEMPLAZAR la validación de fecha en confirmarEmpaquePedido:
async function confirmarEmpaquePedido() {
    const modal = document.getElementById('modalEmpacarPedido');
    const pedidoId = modal.dataset.pedidoId;
    
    // Obtener datos del formulario
    const fechaEmpaque = document.getElementById('fechaEmpaque').value;
    const notasEmpaque = document.getElementById('notasEmpaque').value;
    
    // Validaciones simplificadas - SIN validación de fecha futura
    if (!fechaEmpaque) {
        mostrarAlertaEmpaque('La fecha de empaque es requerida', 'error');
        return;
    }
    
    // Solo validar que sea una fecha válida
    try {
        const fechaTest = new Date(fechaEmpaque);
        if (isNaN(fechaTest.getTime())) {
            mostrarAlertaEmpaque('Por favor ingrese una fecha válida', 'error');
            return;
        }
    } catch (error) {
        mostrarAlertaEmpaque('Formato de fecha inválido', 'error');
        return;
    }
    
    // Confirmar acción
    if (!confirm('¿Está seguro de marcar este pedido como empacado?\n\nEsta acción registrará que usted empacó el pedido.')) {
        return;
    }
    
    // Resto de la función continúa igual...
    const botonConfirmar = document.querySelector('#modalEmpacarPedido .btn-success');
    const textoOriginal = botonConfirmar.innerHTML;
    botonConfirmar.disabled = true;
    botonConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Empacando...';
    
    try {
        mostrarAlertaEmpaque('Procesando empaque...', 'info');
        
        const response = await fetch('php/pedidos.php?action=empacar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: parseInt(pedidoId),
                fecha_empaque: fechaEmpaque,
                notas_empaque: notasEmpaque,
                usuario_id: JSON.parse(localStorage.getItem('userData')).id
            })
        });
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("La respuesta del servidor no es JSON válida");
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarAlertaEmpaque('¡Pedido empacado exitosamente!', 'success');
            
            setTimeout(() => {
                $('#modalEmpacarPedido').modal('hide');
                recargarTablaPedidos();
            }, 2000);
            
        } else {
            mostrarAlertaEmpaque(data.message || 'Error desconocido al empacar', 'error');
        }
        
    } catch (error) {
        console.error('Error al empacar pedido:', error);
        mostrarAlertaEmpaque('Error al procesar el empaque: ' + error.message, 'error');
    } finally {
        botonConfirmar.disabled = false;
        botonConfirmar.innerHTML = textoOriginal;
    }
}

// Mostrar alertas en el modal de empaque
function mostrarAlertaEmpaque(mensaje, tipo = 'info') {
    // Crear o actualizar alerta en el modal
    let alerta = document.getElementById('alertaEmpaque');
    
    if (!alerta) {
        // Crear elemento de alerta si no existe
        alerta = document.createElement('div');
        alerta.id = 'alertaEmpaque';
        
        // Insertar al inicio del modal body
        const modalBody = document.querySelector('#modalEmpacarPedido .modal-body');
        modalBody.insertBefore(alerta, modalBody.firstChild);
    }
    
    // Configurar clases según el tipo
    alerta.className = 'alert';
    switch(tipo) {
        case 'success':
            alerta.classList.add('alert-success');
            break;
        case 'error':
            alerta.classList.add('alert-danger');
            break;
        case 'warning':
            alerta.classList.add('alert-warning');
            break;
        default:
            alerta.classList.add('alert-info');
    }
    
    // Iconos según el tipo
    let icono = 'fa-info-circle';
    switch(tipo) {
        case 'success': icono = 'fa-check-circle'; break;
        case 'error': icono = 'fa-exclamation-triangle'; break;
        case 'warning': icono = 'fa-exclamation-triangle'; break;
    }
    
    alerta.innerHTML = `<i class="fas ${icono}"></i> ${mensaje}`;
    alerta.style.display = 'block';
}

// Limpiar modal al cerrarlo
$('#modalEmpacarPedido').on('hidden.bs.modal', function () {
    // Limpiar datos
    this.removeAttribute('data-pedido-id');
    
    // Ocultar alerta si existe
    const alerta = document.getElementById('alertaEmpaque');
    if (alerta) {
        alerta.style.display = 'none';
    }
    
    // Limpiar formulario
    const form = document.getElementById('formEmpacarPedido');
    if (form) {
        form.reset();
    }
});

// REEMPLAZAR la función recargarTablaPedidos al final de pedidos.js:

function recargarTablaPedidos() {
    try {
        if (typeof tablaPedidos !== 'undefined' && tablaPedidos !== null) {
            console.log('Recargando tabla de pedidos...');
            tablaPedidos.ajax.reload(null, false); // false = mantener paginación actual
        } else {
            console.warn('tablaPedidos no está definida, recargando página...');
            // Como alternativa, recargar la página completa
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    } catch (error) {
        console.error('Error al recargar tabla:', error);
        // Como último recurso, recargar la página
        setTimeout(() => {
            location.reload();
        }, 1000);
    }
}

// ========== FUNCIONES PARA ENVÍO DE PEDIDOS ==========

// Función mejorada para enviar pedido
function enviarPedido(id) {
    console.log('Enviar pedido:', id);
    
    // Configurar modal con datos del pedido
    configurarModalEnvio(id);
    // Mostrar modal directamente
    $('#modalEnviarPedido').modal('show');
}

// Configurar modal de envío con datos del pedido
function configurarModalEnvio(pedidoId) {
    // Guardar ID del pedido en el modal
    document.getElementById('modalEnviarPedido').dataset.pedidoId = pedidoId;
    
    // Establecer fecha y hora actual de Guatemala
    const fechaGuatemala = obtenerFechaHoraGuatemala();
    document.getElementById('fechaEnvio').value = fechaGuatemala;
    
    // Limpiar campos
    document.getElementById('transportista').value = '';
    document.getElementById('numeroGuia').value = '';
    document.getElementById('costoEnvio').value = '0.00';
    document.getElementById('fechaEntregaEstimada').value = '';
    document.getElementById('notasEnvio').value = '';
    
    // Actualizar título del modal
    document.querySelector('#modalEnviarPedido .modal-title').textContent = `Marcar Pedido como Enviado`;
    
    // Ocultar alerta si existe
    const alerta = document.getElementById('alertaEnvio');
    if (alerta) {
        alerta.style.display = 'none';
    }
}

// Confirmar envío del pedido
async function confirmarEnvioPedido() {
    const modal = document.getElementById('modalEnviarPedido');
    const pedidoId = modal.dataset.pedidoId;
    
    // Obtener datos del formulario
    const fechaEnvio = document.getElementById('fechaEnvio').value;
    const transportista = document.getElementById('transportista').value;
    const numeroGuia = document.getElementById('numeroGuia').value;
    const costoEnvio = parseFloat(document.getElementById('costoEnvio').value) || 0.00;
    const fechaEntregaEstimada = document.getElementById('fechaEntregaEstimada').value;
    const notasEnvio = document.getElementById('notasEnvio').value;
    
    // Validaciones
    if (!fechaEnvio) {
        mostrarAlertaEnvio('La fecha de envío es requerida', 'error');
        return;
    }
    
    // CORRECCIÓN: Validar fecha de entrega estimada con mayor flexibilidad
    if (fechaEntregaEstimada) {
        try {
            const fechaEnv = new Date(fechaEnvio);
            const fechaEst = new Date(fechaEntregaEstimada);
            
            // Permitir que la fecha estimada sea el mismo día
            // Solo validar que no sea ANTES del día de envío
            const fechaEnvSoloDia = new Date(fechaEnv.getFullYear(), fechaEnv.getMonth(), fechaEnv.getDate());
            const fechaEstSoloDia = new Date(fechaEst.getFullYear(), fechaEst.getMonth(), fechaEst.getDate());
            
            if (fechaEstSoloDia < fechaEnvSoloDia) {
                mostrarAlertaEnvio('La fecha de entrega estimada no puede ser anterior a la fecha de envío', 'warning');
                return;
            }
        } catch (error) {
            mostrarAlertaEnvio('Formato de fecha de entrega estimada inválido', 'error');
            return;
        }
    }
    
    // Validar costo de envío
    if (costoEnvio < 0) {
        mostrarAlertaEnvio('El costo de envío no puede ser negativo', 'warning');
        return;
    }
    
    // Confirmar acción
    if (!confirm('¿Está seguro de marcar este pedido como enviado?\n\nEsta acción registrará que el pedido ha sido entregado al transportista.')) {
        return;
    }
    
    // Deshabilitar botón mientras se procesa
    const botonConfirmar = document.querySelector('#modalEnviarPedido .btn-warning');
    const textoOriginal = botonConfirmar.innerHTML;
    botonConfirmar.disabled = true;
    botonConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    
    try {
        mostrarAlertaEnvio('Procesando envío...', 'info');
        
        const response = await fetch('php/pedidos.php?action=enviar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                pedido_id: parseInt(pedidoId),
                fecha_envio: fechaEnvio,
                transportista: transportista,
                numero_guia: numeroGuia,
                costo_envio: costoEnvio,
                fecha_entrega_estimada: fechaEntregaEstimada || null,
                notas_envio: notasEnvio,
                usuario_id: JSON.parse(localStorage.getItem('userData')).id
            })
        });
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("La respuesta del servidor no es JSON válida");
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarAlertaEnvio('¡Pedido enviado exitosamente!', 'success');
            
            // Cerrar modal después de un breve delay
            setTimeout(() => {
                $('#modalEnviarPedido').modal('hide');
                
                // Recargar tabla de pedidos para mostrar el nuevo estado
                recargarTablaPedidos();
            }, 2000);
            
        } else {
            mostrarAlertaEnvio(data.message || 'Error desconocido al enviar', 'error');
        }
        
    } catch (error) {
        console.error('Error al enviar pedido:', error);
        mostrarAlertaEnvio('Error al procesar el envío: ' + error.message, 'error');
    } finally {
        // Rehabilitar botón
        botonConfirmar.disabled = false;
        botonConfirmar.innerHTML = textoOriginal;
    }
}

// Mostrar alertas en el modal de envío
function mostrarAlertaEnvio(mensaje, tipo = 'info') {
    // Crear o actualizar alerta en el modal
    let alerta = document.getElementById('alertaEnvio');
    
    if (!alerta) {
        // Crear elemento de alerta si no existe
        alerta = document.createElement('div');
        alerta.id = 'alertaEnvio';
        
        // Insertar al inicio del modal body
        const modalBody = document.querySelector('#modalEnviarPedido .modal-body');
        modalBody.insertBefore(alerta, modalBody.firstChild);
    }
    
    // Configurar clases según el tipo
    alerta.className = 'alert';
    switch(tipo) {
        case 'success':
            alerta.classList.add('alert-success');
            break;
        case 'error':
            alerta.classList.add('alert-danger');
            break;
        case 'warning':
            alerta.classList.add('alert-warning');
            break;
        default:
            alerta.classList.add('alert-info');
    }
    
    // Iconos según el tipo
    let icono = 'fa-info-circle';
    switch(tipo) {
        case 'success': icono = 'fa-check-circle'; break;
        case 'error': icono = 'fa-exclamation-triangle'; break;
        case 'warning': icono = 'fa-exclamation-triangle'; break;
    }
    
    alerta.innerHTML = `<i class="fas ${icono}"></i> ${mensaje}`;
    alerta.style.display = 'block';
}

// Limpiar modal al cerrarlo
$('#modalEnviarPedido').on('hidden.bs.modal', function () {
    // Limpiar datos
    this.removeAttribute('data-pedido-id');
    
    // Ocultar alerta si existe
    const alerta = document.getElementById('alertaEnvio');
    if (alerta) {
        alerta.style.display = 'none';
    }
    
    // Limpiar formulario
    const form = document.getElementById('formEnviarPedido');
    if (form) {
        form.reset();
    }
});

// ========== FUNCIONES PARA ENTREGA DE PEDIDOS ==========

// Función mejorada para entregar pedido
function entregarPedido(id) {
    console.log('Entregar pedido:', id);
    
    // Configurar modal con datos del pedido
    configurarModalEntrega(id);
    // Mostrar modal directamente
    $('#modalEntregarPedido').modal('show');
}

// Configurar modal de entrega con datos del pedido
function configurarModalEntrega(pedidoId) {
    // Guardar ID del pedido en el modal
    document.getElementById('modalEntregarPedido').dataset.pedidoId = pedidoId;
    
    // Establecer fecha y hora actual de Guatemala
    const fechaGuatemala = obtenerFechaHoraGuatemala();
    document.getElementById('fechaEntrega').value = fechaGuatemala;
    
    // Limpiar campos
    document.getElementById('recibidoPor').value = '';
    document.getElementById('firmaPedido').value = '';
    document.getElementById('notasEntrega').value = '';
    
    // Actualizar título del modal
    document.querySelector('#modalEntregarPedido .modal-title').textContent = `Marcar Pedido como Entregado`;
    
    // Ocultar alerta si existe
    const alerta = document.getElementById('alertaEntrega');
    if (alerta) {
        alerta.style.display = 'none';
    }
}

// Confirmar entrega del pedido
// En pedidos.js, REEMPLAZAR la función confirmarEntregaPedido completa:

async function confirmarEntregaPedido() {
    const modal = document.getElementById('modalEntregarPedido');
    const pedidoId = modal.dataset.pedidoId;
    
    // Obtener datos del formulario
    const fechaEntrega = document.getElementById('fechaEntrega').value;
    const recibidoPor = document.getElementById('recibidoPor').value.trim();
    const notasEntrega = document.getElementById('notasEntrega').value.trim();
    const firmaPedido = document.getElementById('firmaPedido').files[0];
    
    // Validaciones
    if (!fechaEntrega) {
        mostrarAlertaEntrega('La fecha de entrega es requerida', 'error');
        return;
    }
    
    if (!recibidoPor) {
        mostrarAlertaEntrega('El nombre de quien recibió el pedido es requerido', 'error');
        return;
    }
    
    if (recibidoPor.length < 3) {
        mostrarAlertaEntrega('El nombre debe tener al menos 3 caracteres', 'warning');
        return;
    }
    
    // CORRECCIÓN: Validación de fecha más flexible para mismo día
    try {
        const fechaSeleccionada = new Date(fechaEntrega);
        
        // Obtener fecha/hora actual
        const ahora = new Date();
        
        // Crear fecha límite más flexible: permitir hasta 30 minutos en el futuro
        // Esto compensa diferencias de sincronización de reloj y zona horaria
        const fechaLimite = new Date(ahora.getTime() + (30 * 60 * 1000)); // +30 minutos
        
        // Fecha mínima: hace 30 días para dar flexibilidad
        const fechaMinima = new Date(ahora);
        fechaMinima.setDate(fechaMinima.getDate() - 30);
        
        console.log('Fecha seleccionada:', fechaSeleccionada);
        console.log('Fecha límite (+30 min):', fechaLimite);
        console.log('Fecha mínima (-30 días):', fechaMinima);
        
        if (fechaSeleccionada > fechaLimite) {
            // Mensaje más claro sobre el límite
            const fechaLimiteStr = fechaLimite.toLocaleString('es-GT');
            mostrarAlertaEntrega(`La fecha de entrega no puede ser futura. Límite: ${fechaLimiteStr}`, 'warning');
            return;
        }
        
        if (fechaSeleccionada < fechaMinima) {
            mostrarAlertaEntrega('La fecha de entrega no puede ser anterior a 30 días', 'warning');
            return;
        }
        
    } catch (error) {
        console.error('Error al validar fecha:', error);
        mostrarAlertaEntrega('Formato de fecha inválido', 'error');
        return;
    }
    
    // Confirmar acción
    if (!confirm(`¿Está seguro de marcar este pedido como entregado?\n\nRecibido por: ${recibidoPor}\n\nEsta acción registrará que el pedido ha sido completamente entregado al cliente.`)) {
        return;
    }
    
    // Deshabilitar botón mientras se procesa
    const botonConfirmar = document.querySelector('#modalEntregarPedido .btn-success');
    const textoOriginal = botonConfirmar.innerHTML;
    botonConfirmar.disabled = true;
    botonConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entregando...';
    
    try {
        mostrarAlertaEntrega('Procesando entrega...', 'info');
        
        // Preparar datos para envío
        const formData = new FormData();
        formData.append('pedido_id', parseInt(pedidoId));
        formData.append('fecha_entrega', fechaEntrega);
        formData.append('recibido_por', recibidoPor);
        formData.append('notas_entrega', notasEntrega);
        formData.append('usuario_id', JSON.parse(localStorage.getItem('userData')).id);
        
        // Agregar archivo si se seleccionó
        if (firmaPedido) {
            // Validar tipo de archivo
            const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!tiposPermitidos.includes(firmaPedido.type)) {
                mostrarAlertaEntrega('Solo se permiten archivos de imagen (JPG, PNG) o PDF', 'warning');
                return;
            }
            
            // Validar tamaño (máximo 5MB)
            if (firmaPedido.size > 5 * 1024 * 1024) {
                mostrarAlertaEntrega('El archivo no puede ser mayor a 5MB', 'warning');
                return;
            }
            
            formData.append('comprobante_entrega', firmaPedido);
        }
        
        const response = await fetch('php/pedidos.php?action=entregar', {
            method: 'POST',
            body: formData // Usar FormData para manejar archivos
        });
        
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("La respuesta del servidor no es JSON válida");
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarAlertaEntrega('¡Pedido entregado exitosamente!', 'success');
            
            // Cerrar modal después de un breve delay
            setTimeout(() => {
                $('#modalEntregarPedido').modal('hide');
                
                // Recargar tabla de pedidos para mostrar el nuevo estado
                recargarTablaPedidos();
            }, 2000);
            
        } else {
            mostrarAlertaEntrega(data.message || 'Error desconocido al entregar', 'error');
        }
        
    } catch (error) {
        console.error('Error al entregar pedido:', error);
        mostrarAlertaEntrega('Error al procesar la entrega: ' + error.message, 'error');
    } finally {
        // Rehabilitar botón
        botonConfirmar.disabled = false;
        botonConfirmar.innerHTML = textoOriginal;
    }
}

// Mostrar alertas en el modal de entrega
function mostrarAlertaEntrega(mensaje, tipo = 'info') {
    // Crear o actualizar alerta en el modal
    let alerta = document.getElementById('alertaEntrega');
    
    if (!alerta) {
        // Crear elemento de alerta si no existe
        alerta = document.createElement('div');
        alerta.id = 'alertaEntrega';
        
        // Insertar al inicio del modal body
        const modalBody = document.querySelector('#modalEntregarPedido .modal-body');
        modalBody.insertBefore(alerta, modalBody.firstChild);
    }
    
    // Configurar clases según el tipo
    alerta.className = 'alert';
    switch(tipo) {
        case 'success':
            alerta.classList.add('alert-success');
            break;
        case 'error':
            alerta.classList.add('alert-danger');
            break;
        case 'warning':
            alerta.classList.add('alert-warning');
            break;
        default:
            alerta.classList.add('alert-info');
    }
    
    // Iconos según el tipo
    let icono = 'fa-info-circle';
    switch(tipo) {
        case 'success': icono = 'fa-check-circle'; break;
        case 'error': icono = 'fa-exclamation-triangle'; break;
        case 'warning': icono = 'fa-exclamation-triangle'; break;
    }
    
    alerta.innerHTML = `<i class="fas ${icono}"></i> ${mensaje}`;
    alerta.style.display = 'block';
}

// Limpiar modal al cerrarlo
$('#modalEntregarPedido').on('hidden.bs.modal', function () {
    // Limpiar datos
    this.removeAttribute('data-pedido-id');
    
    // Ocultar alerta si existe
    const alerta = document.getElementById('alertaEntrega');
    if (alerta) {
        alerta.style.display = 'none';
    }
    
    // Limpiar formulario
    const form = document.getElementById('formEntregarPedido');
    if (form) {
        form.reset();
    }
});

// Mostrar vista previa del archivo seleccionado
document.addEventListener('DOMContentLoaded', function() {
    const inputArchivo = document.getElementById('firmaPedido');
    if (inputArchivo) {
        inputArchivo.addEventListener('change', function(e) {
            const archivo = e.target.files[0];
            let info = '';
            
            if (archivo) {
                const tamaño = (archivo.size / 1024 / 1024).toFixed(2);
                info = `Archivo seleccionado: ${archivo.name} (${tamaño} MB)`;
                
                // Validar tamaño
                if (archivo.size > 5 * 1024 * 1024) {
                    info += ' - ⚠️ Archivo muy grande (máximo 5MB)';
                }
                
                // Validar tipo
                const tiposPermitidos = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!tiposPermitidos.includes(archivo.type)) {
                    info += ' - ⚠️ Tipo de archivo no permitido';
                }
            }
            
            // Mostrar información del archivo
            let infoElement = document.getElementById('info-archivo-entrega');
            if (!infoElement) {
                infoElement = document.createElement('small');
                infoElement.id = 'info-archivo-entrega';
                infoElement.className = 'form-text text-muted';
                inputArchivo.parentNode.appendChild(infoElement);
            }
            infoElement.textContent = info;
        });
    }
});

// AGREGAR estas funciones al final de pedidos.js, antes del cierre del archivo

// ========== FUNCIONES PARA MOSTRAR COMPROBANTES CON ZOOM ==========

// Actualizar la función crearInformacionAdicional para incluir comprobantes
function crearInformacionAdicional(pedido, empaque, envio, entrega) {
    let html = '';
    
    if (empaque) {
        const fechaEmpaque = new Date(empaque.fecha_empaque).toLocaleString('es-GT');
        html += `
            <div class="mt-3">
                <h6><i class="fas fa-box text-info"></i> Información de Empaque</h6>
                <div class="small text-muted">
                    <div><strong>Empacado por:</strong> ${empaque.empacador_nombre}</div>
                    <div><strong>Fecha:</strong> ${fechaEmpaque}</div>
                    ${empaque.notas_empaque ? `<div><strong>Notas:</strong> ${empaque.notas_empaque}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (envio) {
        const fechaEnvio = new Date(envio.fecha_envio).toLocaleString('es-GT');
        html += `
            <div class="mt-3">
                <h6><i class="fas fa-truck text-primary"></i> Información de Envío</h6>
                <div class="small text-muted">
                    ${envio.transportista ? `<div><strong>Transportista:</strong> ${envio.transportista}</div>` : ''}
                    ${envio.numero_guia ? `<div><strong>No. Guía:</strong> ${envio.numero_guia}</div>` : ''}
                    <div><strong>Fecha de envío:</strong> ${fechaEnvio}</div>
                    ${envio.costo_envio > 0 ? `<div><strong>Costo envío:</strong> Q${parseFloat(envio.costo_envio).toFixed(2)}</div>` : ''}
                    ${envio.notas_envio ? `<div><strong>Notas:</strong> ${envio.notas_envio}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    if (entrega) {
        const fechaEntrega = new Date(entrega.fecha_entrega).toLocaleString('es-GT');
        html += `
            <div class="mt-3">
                <h6><i class="fas fa-check-circle text-success"></i> Información de Entrega</h6>
                <div class="small text-muted">
                    <div><strong>Recibido por:</strong> ${entrega.recibido_por}</div>
                    <div><strong>Fecha:</strong> ${fechaEntrega}</div>
                    ${entrega.notas_entrega ? `<div><strong>Notas:</strong> ${entrega.notas_entrega}</div>` : ''}
                    
                    <!-- NUEVO: Mostrar comprobante si existe -->
                    ${entrega.comprobante_entrega ? crearVisorComprobante(entrega.comprobante_entrega) : ''}
                </div>
            </div>
        `;
    }
    
    return html;
}

// NUEVA: Función para crear el visor de comprobantes
function crearVisorComprobante(nombreArchivo) {
    const rutaArchivo = `php/archivo.php?tipo=entrega&f=${encodeURIComponent(nombreArchivo)}`;
    const esImagen = /\.(jpg|jpeg|png|gif|webp)$/i.test(nombreArchivo);
    const esPDF = /\.pdf$/i.test(nombreArchivo);
    
    let html = '<div class="mt-2"><strong>Comprobante de Entrega:</strong><br>';
    
    if (esImagen) {
        // Para imágenes: mostrar miniatura clickeable con zoom
        html += `
            <div class="comprobante-container mt-2">
                <img src="${rutaArchivo}" 
                     alt="Comprobante de entrega" 
                     class="comprobante-miniatura img-thumbnail cursor-pointer" 
                     onclick="mostrarImagenZoom('${rutaArchivo}')"
                     style="max-width: 150px; max-height: 100px; object-fit: cover;"
                     title="Click para ver en tamaño completo">
                <div class="small text-muted mt-1">
                    <i class="fas fa-search-plus"></i> Click para ampliar
                </div>
            </div>
        `;
    } else if (esPDF) {
        // Para PDFs: botón para abrir en nueva pestaña
        html += `
            <div class="comprobante-container mt-2">
                <button type="button" 
                        class="btn btn-outline-danger btn-sm" 
                        onclick="abrirPDF('${rutaArchivo}')">
                    <i class="fas fa-file-pdf"></i> Ver PDF
                </button>
            </div>
        `;
    } else {
        // Para otros archivos: enlace de descarga
        html += `
            <div class="comprobante-container mt-2">
                <a href="${rutaArchivo}" 
                   target="_blank" 
                   class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-download"></i> Descargar archivo
                </a>
            </div>
        `;
    }
    
    html += '</div>';
    return html;
}

// NUEVA: Función para mostrar imagen con zoom
function mostrarImagenZoom(rutaImagen) {
    // Crear modal de zoom
    const modalZoom = `
        <div class="modal fade" id="modalImagenZoom" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-image"></i> Comprobante de Entrega
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-2">
                        <img src="${rutaImagen}" 
                             alt="Comprobante de entrega" 
                             class="img-fluid"
                             style="max-width: 100%; height: auto; cursor: zoom-in;"
                             onclick="toggleZoomCompleto(this)">
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Click en la imagen para zoom completo | 
                                <a href="${rutaImagen}" target="_blank" class="text-decoration-none">
                                    <i class="fas fa-external-link-alt"></i> Abrir en nueva pestaña
                                </a>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-primary" onclick="descargarImagen('${rutaImagen}')">
                            <i class="fas fa-download"></i> Descargar
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior si existe
    const modalExistente = document.getElementById('modalImagenZoom');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Agregar nuevo modal al body
    document.body.insertAdjacentHTML('beforeend', modalZoom);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalImagenZoom'));
    modal.show();
    
    // Limpiar modal cuando se cierre
    document.getElementById('modalImagenZoom').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// NUEVA: Función para toggle de zoom completo
function toggleZoomCompleto(img) {
    if (img.style.cursor === 'zoom-out') {
        // Volver a tamaño normal
        img.style.width = 'auto';
        img.style.height = 'auto';
        img.style.cursor = 'zoom-in';
        img.parentElement.style.overflow = 'visible';
    } else {
        // Zoom completo
        img.style.width = '100%';
        img.style.height = 'auto';
        img.style.cursor = 'zoom-out';
        img.parentElement.style.overflow = 'auto';
    }
}

// NUEVA: Función para abrir PDF
function abrirPDF(rutaPDF) {
    window.open(rutaPDF, '_blank');
}

// NUEVA: Función para descargar imagen
function descargarImagen(rutaImagen) {
    const enlace = document.createElement('a');
    enlace.href = rutaImagen;
    enlace.download = rutaImagen.split('/').pop(); // Obtener solo el nombre del archivo
    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);
}

// NUEVOS: Estilos CSS para los comprobantes (agregar al head del HTML o al CSS)
const estilosComprobantes = `
    <style>
    .comprobante-miniatura {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: 2px solid #dee2e6;
    }
    
    .comprobante-miniatura:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        border-color: #0d6efd;
    }
    
    .comprobante-container {
        display: inline-block;
        text-align: center;
    }
    
    .cursor-pointer {
        cursor: pointer;
    }
    
    #modalImagenZoom .modal-body img {
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    
    /* Responsive para móviles */
    @media (max-width: 768px) {
        .comprobante-miniatura {
            max-width: 120px !important;
            max-height: 80px !important;
        }
        
        #modalImagenZoom .modal-xl {
            margin: 10px;
        }
    }
    </style>
`;

// Agregar estilos automáticamente
if (!document.getElementById('estilos-comprobantes')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'estilos-comprobantes';
    styleElement.innerHTML = estilosComprobantes;
    document.head.appendChild(styleElement);
}