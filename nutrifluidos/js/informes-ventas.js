// js/informes-ventas.js

$(document).ready(function() {
    // Inicializar autenticación
    initAuth('informes');
    
    // Variables globales
    let ventasMensualesChart, distribucionVentasChart, productosMasVendidosChart;
    let ingresosPorProductoChart, clientesVentasChart, frecuenciaCompraChart;
    let tablaVentasResumen, tablaVentasProductos, tablaVentasClientes;
    
    // Inicializar componentes
    inicializarDateRangePicker();
    cargarFiltros();
    inicializarTablas();
    cargarDatos();
    configurarEventos();
    
    //Función inicializarDateRangePicker mejorada
    function inicializarDateRangePicker() {
    // Obtener el mes y año actual dinámicamente
    const today = moment();
    const currentMonthStart = today.clone().startOf('month');
    const currentMonthEnd = today.clone().endOf('month');
    
    $('#dateRange').daterangepicker({
        startDate: currentMonthStart.subtract('month'), // Último mes por defecto
        endDate: currentMonthEnd.subtract('month'),
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ]
        },
        ranges: {
            'Hoy': [moment(), moment()],
            'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            'Este año': [moment().startOf('year'), moment().endOf('year')]
        },
        opens: 'left',
        drops: 'down'
    }, function(start, end) {
        console.log('Nuevo rango seleccionado:', start.format('DD/MM/YYYY'), 'a', end.format('DD/MM/YYYY'));
    });
}

function verificarDatos(response, seccion) {
    if (!response.success) {
        console.error(`Error en ${seccion}:`, response.error);
        return false;
    }
    
    if (response.data.length === 0) {
        console.warn(`No hay datos para ${seccion} con los filtros actuales`);
        return false;
    }
    
    return true;
}
    
    /**
     * Cargar filtros de clientes y vendedores
     */
    function cargarFiltros() {
        // Cargar clientes
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_clientes' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const clienteSelect = $('#clienteFilter');
                    clienteSelect.empty().append('<option value="">Todos los clientes</option>');
                    response.data.forEach(cliente => {
                        clienteSelect.append(`<option value="${cliente.id}">${cliente.empresa}</option>`);
                    });
                }
            }
        });
        
        // Cargar vendedores
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_vendedores' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const vendedorSelect = $('#vendedorFilter');
                    vendedorSelect.empty().append('<option value="">Todos los vendedores</option>');
                    response.data.forEach(vendedor => {
                        vendedorSelect.append(`<option value="${vendedor.id}">${vendedor.nombre_completo}</option>`);
                    });
                }
            }
        });
    }
    
    /**
 * Inicializar DataTables
 */
function inicializarTablas() {
    // Configuración de idioma español
    const idiomaEspanol = {
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
        },
        "aria": {
            "sortAscending": ": activar para ordenar la columna ascendente",
            "sortDescending": ": activar para ordenar la columna descendente"
        }
    };
    
    // Tabla de resumen de ventas
    tablaVentasResumen = $('#tablaVentasResumen').DataTable({
        language: idiomaEspanol,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [8], orderable: false }
        ]
    });
    
    // Tabla de productos
    tablaVentasProductos = $('#tablaVentasProductos').DataTable({
        language: idiomaEspanol,
        order: [[4, 'desc']]
    });
    
    // Tabla de clientes
    tablaVentasClientes = $('#tablaVentasClientes').DataTable({
        language: idiomaEspanol,
        order: [[2, 'desc']],
        columnDefs: [
            { targets: [6], orderable: false }
        ]
    });
}
    
    function cargarDatos() {
    const fechas = $('#dateRange').data('daterangepicker');
    const filtros = {
        fecha_inicio: fechas.startDate.format('YYYY-MM-DD'),
        fecha_fin: fechas.endDate.format('YYYY-MM-DD'),
        cliente_id: $('#clienteFilter').val(),
        vendedor_id: $('#vendedorFilter').val()
    };
    
    // Validar rango de fechas
    if (fechas.endDate.diff(fechas.startDate, 'days') > 365) {
        if (!confirm('Estás solicitando un rango mayor a 1 año. ¿Deseas continuar?')) {
            return;
        }
    }
    
    // Mostrar loading
    $('.stat-value').html('<i class="fas fa-spinner fa-spin"></i>');
    
    // Cargar datos
    cargarEstadisticas(filtros);
    cargarDatosResumen(filtros);
    cargarDatosProductos(filtros);
    cargarDatosClientes(filtros);
    cargarGraficos(filtros);
}
    
   function cargarEstadisticas(filtros) {
    mostrarCarga('#estadisticasResumen', true);
    
    $.ajax({
        url: 'php/informes-ventas.php',
        method: 'POST',
        data: { accion: 'obtener_estadisticas', ...filtros },
        dataType: 'json',
        success: function(response) {
            if (verificarDatos(response, 'estadísticas')) {
                const stats = response.data;
                $('#totalVentas').text(`Q${parseFloat(stats.total_ventas || 0).toLocaleString('es-GT', {minimumFractionDigits: 2})}`);
                $('#totalProductos').text(stats.total_productos || 0);
                $('#totalClientes').text(stats.total_clientes || 0);
                
                const crecimiento = stats.crecimiento || 0;
                $('#crecimiento').text(`${crecimiento > 0 ? '+' : ''}${crecimiento}%`);
                $('#crecimiento').removeClass('text-danger text-success')
                                .addClass(crecimiento >= 0 ? 'text-success' : 'text-danger');
            } else {
                // Mostrar valores por defecto cuando no hay datos
                $('#totalVentas').text('Q0.00');
                $('#totalProductos').text('0');
                $('#totalClientes').text('0');
                $('#crecimiento').text('+0%').removeClass('text-danger text-success');
            }
        },
        complete: function() {
            mostrarCarga('#estadisticasResumen', false);
        }
    });
}
    
    /**
     * Cargar datos para la tabla de resumen
     */
    function cargarDatosResumen(filtros) {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_ventas_resumen', ...filtros },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    tablaVentasResumen.clear();
                    response.data.forEach(venta => {
                        const estadoBadge = venta.estado == 1 ? 
                            '<span class="badge badge-status badge-completed">Completada</span>' :
                            '<span class="badge badge-status badge-pending">Anulada</span>';
                            
                        const acciones = `
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="verDetalleVenta(${venta.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${venta.estado == 1 ? `
                                <button class="btn btn-sm btn-danger" onclick="anularVenta(${venta.id})">
                                    <i class="fas fa-times"></i>
                                </button>` : ''}
                            </div>
                        `;
                        
                        tablaVentasResumen.row.add([
                            moment(venta.fecha).format('DD/MM/YYYY'),
                            venta.codigo,
                            venta.cliente_nombre,
                            venta.vendedor_nombre,
                            `Q${parseFloat(venta.subtotal).toFixed(2)}`,
                            `Q${parseFloat(venta.descuento).toFixed(2)}`,
                            `Q${parseFloat(venta.total).toFixed(2)}`,
                            estadoBadge,
                            acciones
                        ]);
                    });
                    tablaVentasResumen.draw();
                }
            }
        });
    }
    
    /**
     * Cargar datos de productos
     */
    function cargarDatosProductos(filtros) {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_productos_vendidos', ...filtros },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    tablaVentasProductos.clear();
                    response.data.forEach(producto => {
                        tablaVentasProductos.row.add([
                            producto.codigo,
                            producto.nombre,
                            producto.cantidad_total,
                            `Q${parseFloat(producto.precio_promedio).toFixed(2)}`,
                            `Q${parseFloat(producto.ingreso_total).toFixed(2)}`,
                            `${producto.porcentaje_total}%`
                        ]);
                    });
                    tablaVentasProductos.draw();
                }
            }
        });
    }
    
    /**
     * Cargar datos de clientes
     */
    function cargarDatosClientes(filtros) {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_clientes_ventas', ...filtros },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    tablaVentasClientes.clear();
                    response.data.forEach(cliente => {
                        const acciones = `
                            <button class="btn btn-sm btn-primary" onclick="verDetalleCliente(${cliente.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                        
                        tablaVentasClientes.row.add([
                            cliente.empresa,
                            cliente.total_compras,
                            `Q${parseFloat(cliente.valor_total).toFixed(2)}`,
                            `Q${parseFloat(cliente.ticket_promedio).toFixed(2)}`,
                            moment(cliente.ultima_compra).format('DD/MM/YYYY'),
                            cliente.producto_favorito || 'N/A',
                            acciones
                        ]);
                    });
                    tablaVentasClientes.draw();
                }
            }
        });
    }
    
    /**
     * Cargar gráficos
     */
    function cargarGraficos(filtros) {
        // Cargar datos para gráficos
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_datos_graficos', ...filtros },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const datos = response.data;
                    
                    // Gráfico de ventas mensuales
                    crearGraficoVentasMensuales(datos.ventas_mensuales);
                    
                    // Gráfico de distribución de ventas
                    crearGraficoDistribucionVentas(datos.distribucion_ventas);
                    
                    // Gráficos de productos
                    crearGraficoProductosMasVendidos(datos.productos_mas_vendidos);
                    crearGraficoIngresosPorProducto(datos.ingresos_por_producto);
                    
                    // Gráficos de clientes
                    crearGraficoClientesVentas(datos.clientes_ventas);
                    crearGraficoFrecuenciaCompra(datos.frecuencia_compra);
                }
            }
        });
    }
    
    /**
     * Crear gráfico de ventas mensuales
     */
    function crearGraficoVentasMensuales(datos) {
        const ctx = document.getElementById('ventasMensualesChart');
        if (ventasMensualesChart) {
            ventasMensualesChart.destroy();
        }
        
        ventasMensualesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: datos.map(d => d.mes),
                datasets: [{
                    label: 'Ventas (Q)',
                    data: datos.map(d => parseFloat(d.total)),
                    borderColor: '#1e3a8a',
                    backgroundColor: 'rgba(30, 58, 138, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Q ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Q ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Crear gráfico de distribución de ventas
     */
    function crearGraficoDistribucionVentas(datos) {
        const ctx = document.getElementById('distribucionVentasChart');
        if (distribucionVentasChart) {
            distribucionVentasChart.destroy();
        }
        
        distribucionVentasChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: datos.map(d => d.tipo_venta === 'contado' ? 'Contado' : 'Crédito'),
                datasets: [{
                    data: datos.map(d => parseFloat(d.total)),
                    backgroundColor: ['#1e3a8a', '#10b981']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    /**
     * Crear gráfico de productos más vendidos
     */
    function crearGraficoProductosMasVendidos(datos) {
        const ctx = document.getElementById('productosMasVendidosChart');
        if (productosMasVendidosChart) {
            productosMasVendidosChart.destroy();
        }
        
        productosMasVendidosChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datos.map(d => d.nombre.substring(0, 15) + '...'),
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: datos.map(d => parseInt(d.cantidad)),
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * Crear gráfico de ingresos por producto
     */
    function crearGraficoIngresosPorProducto(datos) {
        const ctx = document.getElementById('ingresosPorProductoChart');
        if (ingresosPorProductoChart) {
            ingresosPorProductoChart.destroy();
        }
        
        ingresosPorProductoChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datos.map(d => d.nombre.substring(0, 15) + '...'),
                datasets: [{
                    label: 'Ingresos (Q)',
                    data: datos.map(d => parseFloat(d.total)),
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Q ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Q ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Crear gráfico de clientes por ventas
     */
    function crearGraficoClientesVentas(datos) {
        const ctx = document.getElementById('clientesVentasChart');
        if (clientesVentasChart) {
            clientesVentasChart.destroy();
        }
        
        clientesVentasChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: datos.map(d => d.empresa),
                datasets: [{
                    data: datos.map(d => parseFloat(d.total)),
                    backgroundColor: ['#1e3a8a', '#10b981', '#3b82f6', '#f59e0b', '#6b7280']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': Q ' + context.parsed.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    /**
     * Crear gráfico de frecuencia de compra
     */
    function crearGraficoFrecuenciaCompra(datos) {
        const ctx = document.getElementById('frecuenciaCompraChart');
        if (frecuenciaCompraChart) {
            frecuenciaCompraChart.destroy();
        }
        
        frecuenciaCompraChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: datos.map(d => d.frecuencia),
                datasets: [{
                    data: datos.map(d => parseInt(d.cantidad)),
                    backgroundColor: ['#1e3a8a', '#10b981', '#3b82f6', '#f59e0b', '#6b7280']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    /**
     * Configurar eventos
     */
    function configurarEventos() {
    // Evento de filtrar
    $('#btnFiltrar').click(function() {
        cargarDatos();
    });
    
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).data('bs-target');
        
        if (target === '#anuladas-content') {
            cargarDatosAnuladas();
        }
        // Aquí puedes agregar eventos para otras pestañas si es necesario
    });
    
    // Solo evento de PDF (quitamos Excel e Imprimir)
    $('#btnExportPDF').click(function() {
        exportarPDFCompleto();
    });
    
    // QUITAR o COMENTAR estas líneas si existen:
    /*
    $('#btnExportExcel').click(function() {
        exportarExcel();
    });
    
    $('#btnImprimir').click(function() {
        window.print();
    });
    */
}

    
    /**
     * Ver detalle de venta
     */
    window.verDetalleVenta = function(ventaId) {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_detalle_venta', venta_id: ventaId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const venta = response.data;
                    let html = `
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Información de la Venta</h6>
                                <div class="mb-2"><strong>Factura:</strong> ${venta.codigo}</div>
                                <div class="mb-2"><strong>Fecha:</strong> ${moment(venta.fecha).format('DD/MM/YYYY')}</div>
                                <div class="mb-2"><strong>Vendedor:</strong> ${venta.vendedor_nombre}</div>
                                <div class="mb-2"><strong>Tipo:</strong> ${venta.tipo_venta === 'contado' ? 'Contado' : 'Crédito'}</div>
                                <div class="mb-2"><strong>Estado:</strong> 
                                    ${venta.estado == 1 ? 
                                        '<span class="badge badge-status badge-completed">Completada</span>' :
                                        '<span class="badge badge-status badge-pending">Anulada</span>'}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Información del Cliente</h6>
                                <div class="mb-2"><strong>Cliente:</strong> ${venta.cliente_empresa}</div>
                                <div class="mb-2"><strong>NIT:</strong> ${venta.cliente_nit}</div>
                                <div class="mb-2"><strong>Contacto:</strong> ${venta.cliente_contacto}</div>
                                <div class="mb-2"><strong>Teléfono:</strong> ${venta.cliente_telefono || 'N/A'}</div>
                                <div class="mb-2"><strong>Email:</strong> ${venta.cliente_email || 'N/A'}</div>
                            </div>
                        </div>
                        
                        <h6>Productos Vendidos</h6>
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
                                <tbody>
                    `;
                    
                    venta.detalles.forEach(detalle => {
                        html += `
                            <tr>
                                <td>${detalle.codigo}</td>
                                <td>${detalle.nombre}</td>
                                <td>${detalle.cantidad}</td>
                                <td>Q${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                                <td>Q${parseFloat(detalle.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                        <td>Q${parseFloat(venta.subtotal).toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Descuento:</strong></td>
                                        <td>Q${parseFloat(venta.descuento).toFixed(2)}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>Q${parseFloat(venta.total).toFixed(2)}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    
                    $('#modalDetalleVentaLabel').text(`Detalle de Venta ${venta.codigo}`);
                    $('#modalDetalleVentaBody').html(html);
                    $('#modalDetalleVenta').modal('show');
                }
            }
        });
    };
    
    /**
     * Ver detalle de cliente
     */
    window.verDetalleCliente = function(clienteId) {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_detalle_cliente', cliente_id: clienteId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const cliente = response.data;
                    let html = `
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Información del Cliente</h6>
                                <div class="mb-2"><strong>Cliente:</strong> ${cliente.empresa}</div>
                                <div class="mb-2"><strong>NIT:</strong> ${cliente.nit}</div>
                                <div class="mb-2"><strong>Contacto:</strong> ${cliente.contacto1}</div>
                                <div class="mb-2"><strong>Teléfono:</strong> ${cliente.telefono1 || 'N/A'}</div>
                                <div class="mb-2"><strong>Email:</strong> ${cliente.email || 'N/A'}</div>
                                <div class="mb-2"><strong>Dirección:</strong> ${cliente.direccion || 'N/A'}</div>
                            </div>
                            <div class="col-md-6">
                                <h6>Indicadores</h6>
                                <div class="mb-2"><strong>Total Compras:</strong> ${cliente.estadisticas.total_compras}</div>
                                <div class="mb-2"><strong>Valor Total:</strong> Q${parseFloat(cliente.estadisticas.valor_total).toFixed(2)}</div>
                                <div class="mb-2"><strong>Ticket Promedio:</strong> Q${parseFloat(cliente.estadisticas.ticket_promedio).toFixed(2)}</div>
                                <div class="mb-2"><strong>Última Compra:</strong> ${moment(cliente.estadisticas.ultima_compra).format('DD/MM/YYYY')}</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8 mb-4">
                                <h6>Historial de Compras</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Factura</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;
                    
                    cliente.historial.forEach(compra => {
                        const estadoBadge = compra.estado == 1 ? 
                            '<span class="badge badge-status badge-completed">Completada</span>' :
                            '<span class="badge badge-status badge-pending">Anulada</span>';
                            
                        html += `
                            <tr>
                                <td>${moment(compra.fecha).format('DD/MM/YYYY')}</td>
                                <td>${compra.codigo}</td>
                                <td>Q${parseFloat(compra.total).toFixed(2)}</td>
                                <td>${estadoBadge}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <h6>Productos Favoritos</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th>Cantidad</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;
                    
                    cliente.productos_favoritos.forEach(producto => {
                        html += `
                            <tr>
                                <td>${producto.nombre}</td>
                                <td>${producto.cantidad}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('#modalDetalleClienteLabel').text(`Perfil de Cliente: ${cliente.empresa}`);
                    $('#modalDetalleClienteBody').html(html);
                    $('#modalDetalleCliente').modal('show');
                }
            }
        });
    };
    
    /**
     * Exportar a Excel
     
    function exportarExcel() {
        const fechas = $('#dateRange').data('daterangepicker');
        const filtros = {
            fecha_inicio: fechas.startDate.format('YYYY-MM-DD'),
            fecha_fin: fechas.endDate.format('YYYY-MM-DD'),
            cliente_id: $('#clienteFilter').val(),
            vendedor_id: $('#vendedorFilter').val()
        };
        
        window.open(`informes-ventas.php?accion=exportar_excel&${$.param(filtros)}`, '_blank');
    }*/
    
    /**
     * Exportar a PDF
     */
    function exportarPDFCompleto() {
    const fechas = $('#dateRange').data('daterangepicker');
    const filtros = {
        fecha_inicio: fechas.startDate.format('YYYY-MM-DD'),
        fecha_fin: fechas.endDate.format('YYYY-MM-DD'),
        cliente_id: $('#clienteFilter').val(),
        vendedor_id: $('#vendedorFilter').val()
    };
    
    // Mostrar loading
    const originalText = $('#btnExportPDF').html();
    $('#btnExportPDF').html('<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...');
    $('#btnExportPDF').prop('disabled', true);
    
    // Crear URL con parámetros
    const params = new URLSearchParams(filtros);
    const url = `php/generar-pdf-ventas.php?${params.toString()}`;
    
    // Abrir en nueva ventana para descarga
    window.open(url, '_blank');
    
    // Restaurar botón después de un momento
    setTimeout(function() {
        $('#btnExportPDF').html(originalText);
        $('#btnExportPDF').prop('disabled', false);
    }, 2000);
}

    
    /**
     * Anular venta
     */
    window.anularVenta = function(ventaId) {
        if (confirm('¿Está seguro que desea anular esta venta? Esta acción no se puede deshacer.')) {
            $.ajax({
                url: 'php/informes-ventas.php',
                method: 'POST',
                data: { 
                    accion: 'anular_venta', 
                    venta_id: ventaId,
                    motivo: 'Anulación desde informes'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Venta anulada correctamente');
                        cargarDatos();
                    } else {
                        alert('Error al anular la venta: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error de conexión al anular la venta');
                }
            });
        }
    };
    
    /**
     * Mostrar/ocultar indicadores de carga
     */
    function mostrarCarga(elemento, mostrar = true) {
        if (mostrar) {
            $(elemento).addClass('loading');
        } else {
            $(elemento).removeClass('loading');
        }
    }
    
    /**
     * Formatear números como moneda guatemalteca
     */
    function formatearMoneda(valor) {
        return `Q${parseFloat(valor || 0).toLocaleString('es-GT', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    }
    
    /**
     * Formatear fechas
     */
    function formatearFecha(fecha) {
        return moment(fecha).format('DD/MM/YYYY');
    }
    
    /**
     * Validar datos antes de enviar
     */
    function validarFiltros() {
        const fechas = $('#dateRange').data('daterangepicker');
        const fechaInicio = fechas.startDate;
        const fechaFin = fechas.endDate;
        
        // Validar que la fecha de inicio no sea mayor que la fecha fin
        if (fechaInicio.isAfter(fechaFin)) {
            alert('La fecha de inicio no puede ser mayor que la fecha fin');
            return false;
        }
        
        // Validar que el rango no sea mayor a 1 año
        const diferenciaMeses = fechaFin.diff(fechaInicio, 'months');
        if (diferenciaMeses > 12) {
            alert('El rango de fechas no puede ser mayor a 12 meses');
            return false;
        }
        
        return true;
    }
    
    /**
     * Actualizar URL con parámetros de filtro (para compartir enlaces)
     */
    function actualizarURL() {
        const fechas = $('#dateRange').data('daterangepicker');
        const params = new URLSearchParams({
            fecha_inicio: fechas.startDate.format('YYYY-MM-DD'),
            fecha_fin: fechas.endDate.format('YYYY-MM-DD'),
            cliente_id: $('#clienteFilter').val() || '',
            vendedor_id: $('#vendedorFilter').val() || ''
        });
        
        const nuevaURL = `${window.location.pathname}?${params.toString()}`;
        window.history.replaceState({}, '', nuevaURL);
    }
    
    /**
     * Cargar filtros desde URL (al cargar la página)
     */
    function cargarFiltrosDesdeURL() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('fecha_inicio') && params.has('fecha_fin')) {
            const fechaInicio = moment(params.get('fecha_inicio'));
            const fechaFin = moment(params.get('fecha_fin'));
            
            if (fechaInicio.isValid() && fechaFin.isValid()) {
                $('#dateRange').data('daterangepicker').setStartDate(fechaInicio);
                $('#dateRange').data('daterangepicker').setEndDate(fechaFin);
            }
        }
        
        if (params.has('cliente_id')) {
            $('#clienteFilter').val(params.get('cliente_id'));
        }
        
        if (params.has('vendedor_id')) {
            $('#vendedorFilter').val(params.get('vendedor_id'));
        }
    }
    
    /**
     * Manejar errores de AJAX
     */
    function manejarErrorAjax(xhr, status, error) {
        console.error('Error AJAX:', status, error);
        
        let mensaje = 'Error de conexión';
        
        if (xhr.status === 403) {
            mensaje = 'No tiene permisos para realizar esta acción';
        } else if (xhr.status === 404) {
            mensaje = 'Recurso no encontrado';
        } else if (xhr.status === 500) {
            mensaje = 'Error interno del servidor';
        }
        
        alert(mensaje);
    }
    
    /**
     * Configuración global de AJAX
     */
    $.ajaxSetup({
        error: manejarErrorAjax,
        beforeSend: function() {
            // Mostrar indicador de carga global si es necesario
        },
        complete: function() {
            // Ocultar indicador de carga global
        }
    });
    
    /**
     * Actualizar datos automáticamente cada 5 minutos (opcional)
     */
    function iniciarActualizacionAutomatica() {
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                cargarEstadisticas(obtenerFiltrosActuales());
            }
        }, 300000); // 5 minutos
    }
    
    /**
     * Obtener filtros actuales
     */
    function obtenerFiltrosActuales() {
        const fechas = $('#dateRange').data('daterangepicker');
        return {
            fecha_inicio: fechas.startDate.format('YYYY-MM-DD'),
            fecha_fin: fechas.endDate.format('YYYY-MM-DD'),
            cliente_id: $('#clienteFilter').val(),
            vendedor_id: $('#vendedorFilter').val()
        };
    }
    
    /**
     * Exportar datos de tabla específica
     */
    function exportarTabla(tabla, nombreArchivo) {
        // Obtener datos de la tabla
        const datos = tabla.rows().data().toArray();
        const headers = tabla.columns().header().toArray().map(th => $(th).text());
        
        // Crear CSV
        let csv = headers.join(',') + '\n';
        datos.forEach(fila => {
            // Limpiar datos HTML y formatear
            const filaLimpia = fila.map(celda => {
                if (typeof celda === 'string') {
                    return '"' + celda.replace(/<[^>]*>/g, '').replace(/"/g, '""') + '"';
                }
                return celda;
            });
            csv += filaLimpia.join(',') + '\n';
        });
        
        // Descargar archivo
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', nombreArchivo + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Agregar eventos para exportar tablas individuales
     */
    function configurarExportacionTablas() {
        // Agregar botones de exportación a cada tabla
        $('.table-responsive').each(function() {
            const tabla = $(this).find('table');
            if (tabla.length) {
                const btnGroup = $(`
                    <div class="btn-group btn-group-sm mb-2" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-export-csv">
                            <i class="fas fa-download me-1"></i>CSV
                        </button>
                    </div>
                `);
                
                $(this).prepend(btnGroup);
                
                btnGroup.find('.btn-export-csv').click(function() {
                    const tablaId = tabla.attr('id');
                    let nombreArchivo = 'datos';
                    
                    if (tablaId === 'tablaVentasResumen') {
                        nombreArchivo = 'resumen_ventas';
                    } else if (tablaId === 'tablaVentasProductos') {
                        nombreArchivo = 'productos_vendidos';
                    } else if (tablaId === 'tablaVentasClientes') {
                        nombreArchivo = 'clientes_ventas';
                    }
                    
                    const dataTable = $('#' + tablaId).DataTable();
                    exportarTabla(dataTable, nombreArchivo);
                });
            }
        });
    }
    
    /**
     * Inicializar tooltips
     */
    function inicializarTooltips() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
    
    /**
     * Configurar eventos de teclado
     */
    function configurarEventosTeclado() {
        // Filtrar con Enter
        $('#clienteFilter, #vendedorFilter').keypress(function(e) {
            if (e.which === 13) { // Enter
                cargarDatos();
            }
        });
        
        // Atajos de teclado
        $(document).keydown(function(e) {
            // Ctrl + F para enfocar filtros
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                $('#dateRange').focus();
            }
            
            // Ctrl + R para recargar datos
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                cargarDatos();
            }
            
            // Ctrl + E para exportar Excel
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportarExcel();
            }
            
            // Ctrl + P para imprimir
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    }
    
    // Inicializar funciones adicionales
    setTimeout(function() {
        cargarFiltrosDesdeURL();
        //configurarExportacionTablas();
        inicializarTooltips();
        configurarEventosTeclado();
        // iniciarActualizacionAutomatica(); // Descomentar si se desea actualización automática
    }, 1000);
    
    // Actualizar URL cuando cambien los filtros
    $('#dateRange').on('apply.daterangepicker', function() {
        actualizarURL();
    });
    
    $('#clienteFilter, #vendedorFilter').change(function() {
        actualizarURL();
    });
    
    // Manejar visibilidad de la página para pausar/reanudar actualizaciones
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            // Página visible - reanudar
        } else {
            // Página oculta - pausar
        }
    });
    
    function exportarPDFCompleto() {
    const fechas = $('#dateRange').data('daterangepicker');
    const filtros = {
        fecha_inicio: fechas.startDate.format('YYYY-MM-DD'),
        fecha_fin: fechas.endDate.format('YYYY-MM-DD'),
        cliente_id: $('#clienteFilter').val(),
        vendedor_id: $('#vendedorFilter').val()
    };
    
    // Mostrar loading
    const originalText = $('#btnExportPDF').html();
    $('#btnExportPDF').html('<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...');
    $('#btnExportPDF').prop('disabled', true);
    
    // Crear URL con parámetros
    const params = new URLSearchParams(filtros);
    const url = `php/generar-pdf-ventas.php?${params.toString()}`;
    
    // Abrir en nueva ventana para descarga
    window.open(url, '_blank');
    
    // Restaurar botón después de un momento
    setTimeout(function() {
        $('#btnExportPDF').html(originalText);
        $('#btnExportPDF').prop('disabled', false);
    }, 2000);
}

/**
 * Ver detalle de venta - PASO 2
 */
window.verDetalleVenta = function(ventaId) {
    // Mostrar loading en el modal
    $('#modalDetalleVentaLabel').text('Cargando...');
    $('#modalDetalleVentaBody').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Cargando detalle...</div>');
    $('#modalDetalleVenta').modal('show');
    
    $.ajax({
        url: 'php/informes-ventas.php',
        method: 'POST',
        data: { accion: 'obtener_detalle_venta', venta_id: ventaId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const venta = response.data;
                construirModalDetalleVenta(venta);
            } else {
                $('#modalDetalleVentaBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error: ${response.error}
                    </div>
                `);
            }
        },
        error: function() {
            $('#modalDetalleVentaBody').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error de conexión al cargar el detalle
                </div>
            `);
        }
    });
};

/**
 * Construir contenido del modal de detalle de venta - PASO 2
 */
function construirModalDetalleVenta(venta) {
    const estadoBadge = venta.estado == 1 ? 
        '<span class="badge bg-success">Completada</span>' :
        '<span class="badge bg-danger">Anulada</span>';
    
    const tipoVenta = venta.tipo_venta === 'contado' ? 'Contado' : 'Crédito';
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-receipt me-2"></i>Información de la Venta</h6>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Factura:</strong> 
                        <span class="badge bg-primary">${venta.codigo}</span>
                    </div>
                    <div class="info-item">
                        <strong>Fecha:</strong> ${moment(venta.fecha).format('DD/MM/YYYY')}
                    </div>
                    <div class="info-item">
                        <strong>Vendedor:</strong> ${venta.vendedor_nombre}
                    </div>
                    <div class="info-item">
                        <strong>Tipo:</strong> 
                        <span class="badge ${venta.tipo_venta === 'contado' ? 'bg-success' : 'bg-warning'}">${tipoVenta}</span>
                    </div>
                    <div class="info-item">
                        <strong>Estado:</strong> ${estadoBadge}
                    </div>
                    <div class="info-item">
                        <strong>Registrada:</strong> ${moment(venta.fecha_registro).format('DD/MM/YYYY HH:mm')}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-user-tie me-2"></i>Información del Cliente</h6>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Cliente:</strong> ${venta.cliente_empresa}
                    </div>
                    <div class="info-item">
                        <strong>NIT:</strong> ${venta.cliente_nit || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Contacto:</strong> ${venta.cliente_contacto || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Teléfono:</strong> ${venta.cliente_telefono || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Email:</strong> ${venta.cliente_email || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Dirección:</strong> ${venta.cliente_direccion || 'No especificado'}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Mostrar información de anulación si aplica
    if (venta.estado == 0 && venta.anulacion) {
        html += `
            <div class="row mb-3">
                <div class="col-12">
                    <div class="alert alert-danger">
                        <h6 class="alert-heading"><i class="fas fa-ban me-2"></i>Información de Anulación</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Motivo:</strong> ${venta.anulacion.motivo}<br>
                                <strong>Anulada por:</strong> ${venta.anulacion.usuario_anulo_nombre}
                            </div>
                            <div class="col-md-6">
                                <strong>Fecha/Hora:</strong> ${moment(venta.anulacion.fecha_anulacion).format('DD/MM/YYYY HH:mm:ss')}
                            </div>
                        </div>
                        ${venta.anulacion.detalle_adicional ? `
                        <hr>
                        <strong>Detalle:</strong> ${venta.anulacion.detalle_adicional}
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    html += `
        <div class="row">
            <div class="col-12">
                <h6 class="text-primary"><i class="fas fa-box me-2"></i>Productos Vendidos</h6>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th>Stock Actual</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    let totalCantidad = 0;
    venta.detalles.forEach(detalle => {
        totalCantidad += parseInt(detalle.cantidad);
        html += `
            <tr>
                <td><span class="badge bg-light text-dark">${detalle.codigo}</span></td>
                <td>
                    <strong>${detalle.nombre}</strong>
                    <br><small class="text-muted">Costo: Q${parseFloat(detalle.precio_compra).toFixed(2)}</small>
                    <br><small class="text-muted">PVP: Q${parseFloat(detalle.precio_venta_real || 0).toFixed(2)}</small>
                </td>
                <td><span class="badge bg-primary">${detalle.cantidad}</span></td>
                <td>Q${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                <td class="fw-bold">Q${parseFloat(detalle.subtotal).toFixed(2)}</td>
                <td>
                    <span class="badge ${detalle.stock <= detalle.stock_minimo ? 'bg-danger' : 'bg-success'}">${detalle.stock}</span>
                    ${detalle.stock <= detalle.stock_minimo ? '<br><small class="text-danger">Stock bajo</small>' : ''}
                </td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end"><strong>Totales:</strong></td>
                                <td><span class="badge bg-primary">${totalCantidad}</span></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-8"></div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>Q${parseFloat(venta.subtotal).toFixed(2)}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Descuento:</span>
                            <span>-Q${parseFloat(venta.descuento).toFixed(2)}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span class="text-primary">Q${parseFloat(venta.total).toFixed(2)}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#modalDetalleVentaLabel').text(`Detalle de Venta ${venta.codigo}`);
    $('#modalDetalleVentaBody').html(html);
    
    // Actualizar botón de imprimir con el ID de la venta
    $('#btnImprimirDetalle').attr('data-venta-id', venta.id);
}

// Agregar estilos CSS para el modal
const estilosModalDetalle = `
<style>
.info-grid .info-item {
    margin-bottom: 8px;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-grid .info-item:last-child {
    border-bottom: none;
}

.table-responsive {
    border-radius: 0.375rem;
    overflow: hidden;
}

.badge {
    font-size: 0.75em;
}
</style>
`;

// Inyectar estilos solo una vez
if (!document.getElementById('estilos-modal-detalle')) {
    const styleElement = document.createElement('style');
    styleElement.id = 'estilos-modal-detalle';
    styleElement.innerHTML = estilosModalDetalle;
    document.head.appendChild(styleElement);
}
/**
 * Configurar el botón imprimir del modal de detalle de venta
 */
$(document).ready(function() {
    // Evento para el botón imprimir del modal
    $('#btnImprimirDetalle').click(function() {
        const ventaId = $(this).attr('data-venta-id');
        
        if (!ventaId) {
            alert('Error: No se pudo obtener el ID de la venta');
            return;
        }
        
        // Abrir comprobante.php en nueva ventana para imprimir
        const url = `comprobante.php?id=${ventaId}`;
        window.open(url, 'imprimirComprobante', 'width=900,height=700,scrollbars=yes,resizable=yes');
    });
});

/**
 * Ver detalle de cliente - PASO 3
 */
window.verDetalleCliente = function(clienteId) {
    // Mostrar loading en el modal
    $('#modalDetalleClienteLabel').text('Cargando...');
    $('#modalDetalleClienteBody').html('<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Cargando perfil del cliente...</div>');
    $('#modalDetalleCliente').modal('show');
    
    $.ajax({
        url: 'informes-ventas.php',
        method: 'POST',
        data: { accion: 'obtener_detalle_cliente', cliente_id: clienteId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const cliente = response.data;
                construirModalDetalleCliente(cliente);
            } else {
                $('#modalDetalleClienteBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error: ${response.error}
                    </div>
                `);
            }
        },
        error: function() {
            $('#modalDetalleClienteBody').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error de conexión al cargar el perfil del cliente
                </div>
            `);
        }
    });
};

/**
 * Construir contenido del modal de detalle de cliente - PASO 3
 */
function construirModalDetalleCliente(cliente) {
    const estadoCliente = cliente.estado == 1 ? 
        '<span class="badge bg-success">Activo</span>' : 
        '<span class="badge bg-danger">Inactivo</span>';
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-building me-2"></i>Información General</h6>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Empresa:</strong> ${cliente.empresa}
                    </div>
                    <div class="info-item">
                        <strong>NIT:</strong> ${cliente.nit || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Estado:</strong> ${estadoCliente}
                    </div>
                    <div class="info-item">
                        <strong>Contacto Principal:</strong> ${cliente.contacto1 || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Teléfono:</strong> ${cliente.telefono1 || 'No especificado'}
                    </div>
                    ${cliente.contacto2 ? `
                    <div class="info-item">
                        <strong>Contacto Secundario:</strong> ${cliente.contacto2}
                    </div>` : ''}
                    ${cliente.telefono2 ? `
                    <div class="info-item">
                        <strong>Teléfono 2:</strong> ${cliente.telefono2}
                    </div>` : ''}
                    <div class="info-item">
                        <strong>Email:</strong> ${cliente.email || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Dirección:</strong> ${cliente.direccion || 'No especificado'}
                    </div>
                    <div class="info-item">
                        <strong>Vendedor asignado:</strong> ${cliente.vendedor_asignado || 'No asignado'}
                    </div>
                    <div class="info-item">
                        <strong>Cliente desde:</strong> ${moment(cliente.fecha_registro).format('DD/MM/YYYY')}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary"><i class="fas fa-chart-line me-2"></i>Estadísticas de Compras</h6>
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">${cliente.estadisticas.total_compras || 0}</h4>
                                <small>Total Compras</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">Q${parseFloat(cliente.estadisticas.valor_total || 0).toFixed(2)}</h4>
                                <small>Valor Total</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">Q${parseFloat(cliente.estadisticas.ticket_promedio || 0).toFixed(2)}</h4>
                                <small>Ticket Promedio</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center p-3">
                                <h4 class="mb-1">${cliente.estadisticas.ultima_compra ? moment(cliente.estadisticas.ultima_compra).fromNow() : 'N/A'}</h4>
                                <small>Última Compra</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${cliente.estadisticas.compras_contado || cliente.estadisticas.compras_credito ? `
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Contado: ${cliente.estadisticas.compras_contado || 0}</small>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Crédito: ${cliente.estadisticas.compras_credito || 0}</small>
                    </div>
                </div>` : ''}
            </div>
        </div>
    `;
    
    // Mostrar créditos pendientes si los hay
    if (cliente.creditos_pendientes && cliente.creditos_pendientes.length > 0) {
        html += `
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading"><i class="fas fa-credit-card me-2"></i>Créditos Pendientes</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Venta</th>
                                        <th>Fecha</th>
                                        <th>Monto Total</th>
                                        <th>Saldo Pendiente</th>
                                        <th>Cuota</th>
                                        <th>Próximo Pago</th>
                                    </tr>
                                </thead>
                                <tbody>
        `;
        
        cliente.creditos_pendientes.forEach(credito => {
            html += `
                <tr>
                    <td>${credito.venta_codigo}</td>
                    <td>${moment(credito.fecha_venta).format('DD/MM/YYYY')}</td>
                    <td>Q${parseFloat(credito.venta_total).toFixed(2)}</td>
                    <td class="fw-bold text-danger">Q${parseFloat(credito.saldo_pendiente).toFixed(2)}</td>
                    <td>Q${parseFloat(credito.monto_cuota).toFixed(2)}</td>
                    <td>${moment(credito.fecha_primer_pago).format('DD/MM/YYYY')}</td>
                </tr>
            `;
        });
        
        html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    html += `
        <div class="row">
            <div class="col-md-8">
                <h6 class="text-primary"><i class="fas fa-history me-2"></i>Historial de Compras Recientes</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Factura</th>
                                <th>Tipo</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    if (cliente.historial && cliente.historial.length > 0) {
        cliente.historial.forEach(compra => {
            const estadoBadge = compra.estado == 1 ? 
                '<span class="badge bg-success">Completada</span>' :
                '<span class="badge bg-danger">Anulada</span>';
            
            const tipoBadge = compra.tipo_venta === 'contado' ?
                '<span class="badge bg-primary">Contado</span>' :
                '<span class="badge bg-warning">Crédito</span>';
                
            html += `
                <tr>
                    <td>${moment(compra.fecha).format('DD/MM/YYYY')}</td>
                    <td>${compra.codigo}</td>
                    <td>${tipoBadge}</td>
                    <td class="fw-bold">Q${parseFloat(compra.total).toFixed(2)}</td>
                    <td>${estadoBadge}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalleVenta(${compra.id})" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    } else {
        html += `
            <tr>
                <td colspan="6" class="text-center text-muted">No hay compras registradas</td>
            </tr>
        `;
    }
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-4">
                <h6 class="text-primary"><i class="fas fa-star me-2"></i>Productos Favoritos</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Veces</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    if (cliente.productos_favoritos && cliente.productos_favoritos.length > 0) {
        cliente.productos_favoritos.forEach(producto => {
            html += `
                <tr>
                    <td class="small" title="${producto.nombre}">${producto.nombre.length > 20 ? producto.nombre.substring(0, 20) + '...' : producto.nombre}</td>
                    <td><span class="badge bg-primary">${producto.cantidad_total}</span></td>
                    <td><span class="badge bg-secondary">${producto.veces_comprado}</span></td>
                </tr>
            `;
        });
    } else {
        html += `
            <tr>
                <td colspan="3" class="text-center text-muted">Sin productos comprados</td>
            </tr>
        `;
    }
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    $('#modalDetalleClienteLabel').text(`Perfil de Cliente: ${cliente.empresa}`);
    $('#modalDetalleClienteBody').html(html);
}

// Estilos adicionales para el modal de cliente
const estilosModalCliente = `
<style>
.info-grid .info-item {
    margin-bottom: 8px;
    padding: 4px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-grid .info-item:last-child {
    border-bottom: none;
}

.card.bg-primary, .card.bg-success, .card.bg-info, .card.bg-warning {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card h4 {
    font-size: 1.2rem;
    font-weight: bold;
}

.table-sm th {
    font-size: 0.8rem;
    padding: 0.3rem;
}

.table-sm td {
    font-size: 0.8rem;
    padding: 0.3rem;
}

.alert-heading {
    margin-bottom: 0.5rem;
}
</style>
`;

// Inyectar estilos solo una vez
if (!document.getElementById('estilos-modal-cliente')) {
    const styleElement = document.createElement('style');
    styleElement.id = 'estilos-modal-cliente';
    styleElement.innerHTML = estilosModalCliente;
    document.head.appendChild(styleElement);
}

/**
 * Configurar botón exportar informe del modal cliente
 */
$(document).ready(function() {
    // Variable global para almacenar el ID del cliente actual
    let clienteActualId = null;
    
    // Actualizar el verDetalleCliente para guardar el ID
    const originalVerDetalleCliente = window.verDetalleCliente;
    window.verDetalleCliente = function(clienteId) {
        clienteActualId = clienteId; // Guardar ID para usar en exportar
        originalVerDetalleCliente(clienteId);
    };
    
    // Evento para el botón exportar informe
    $('#modalDetalleCliente .btn-primary').click(function() {
        if (!clienteActualId) {
            alert('Error: No se pudo obtener el ID del cliente');
            return;
        }
        
        exportarInformeCliente(clienteActualId);
    });
});

/**
 * Función para exportar informe del cliente
 */
function exportarInformeCliente(clienteId) {
    // Mostrar loading en el botón
    const btnExportar = $('#modalDetalleCliente .btn-primary');
    const originalText = btnExportar.html();
    btnExportar.html('<i class="fas fa-spinner fa-spin me-2"></i>Generando...');
    btnExportar.prop('disabled', true);
    
    // Realizar petición para generar el informe
    fetch(`php/informe-cliente.php?cliente_id=${clienteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Abrir el PDF generado en nueva ventana
                window.open(data.archivo, '_blank');
                
                // Mostrar mensaje de éxito
                mostrarNotificacion('success', `Informe de ${data.cliente} generado correctamente`);
                
                // Opcional: Cerrar el modal después de exportar
                // $('#modalDetalleCliente').modal('hide');
            } else {
                mostrarNotificacion('error', 'Error al generar informe: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarNotificacion('error', 'Error de conexión al generar el informe');
        })
        .finally(() => {
            // Restaurar el botón
            btnExportar.html(originalText);
            btnExportar.prop('disabled', false);
        });
}

/**
 * Función para mostrar notificaciones
 */
function mostrarNotificacion(tipo, mensaje) {
    // Si tienes un sistema de notificaciones, úsalo aquí
    // Por ahora usaremos alert simple
    if (tipo === 'success') {
        alert('✅ ' + mensaje);
    } else {
        alert('❌ ' + mensaje);
    }
    
    // Alternativa con toast de Bootstrap si lo prefieres:
    
    const toast = `
        <div class="toast align-items-center text-white bg-${tipo === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${mensaje}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Agregar toast al DOM y mostrarlo
    $('body').append(toast);
    $('.toast').last().toast('show');
    
}

/**
 * Preparar anulación de venta - PASO 4
 */
window.anularVenta = function(ventaId, ventaCodigo, ventaFecha, ventaTotal) {
    // Si no se proporcionan todos los parámetros, obtenerlos de la fila
    if (!ventaCodigo || !ventaFecha || !ventaTotal) {
        console.warn('Parámetros incompletos para anular venta, obteniendo de la tabla...');
        // Aquí podrías obtener los datos de la fila de la tabla si es necesario
    }
    
    const fechaVenta = moment(ventaFecha);
    const horasTranscurridas = moment().diff(fechaVenta, 'hours');
    
    // Validar si se puede anular (360 horas = 15 dias)
    if (horasTranscurridas > 360) {
        mostrarAlerta('error', 
            'No se puede anular', 
            `Esta venta tiene ${horasTranscurridas} horas de antigüedad. Solo se pueden anular ventas con máximo 15 días.`
        );
        return;
    }
    
    // Construir información de la venta
    const infoVenta = `
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Información de la Venta</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <strong>Factura:</strong><br>
                        <span class="badge bg-primary fs-6">${ventaCodigo}</span>
                    </div>
                    <div class="col-6">
                        <strong>Fecha:</strong><br>
                        ${fechaVenta.format('DD/MM/YYYY HH:mm')}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <strong>Total:</strong><br>
                        <span class="fw-bold text-danger fs-5">Q${parseFloat(ventaTotal).toFixed(2)}</span>
                    </div>
                    <div class="col-6">
                        <strong>Antigüedad:</strong><br>
                        <span class="badge ${horasTranscurridas < 24 ? 'bg-success' : 'bg-warning'}">${horasTranscurridas} horas</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const tiempoInfo = horasTranscurridas < 24 
        ? `<i class="fas fa-check-circle text-success me-2"></i>${horasTranscurridas} horas (Dentro del límite)`
        : `<i class="fas fa-exclamation-triangle text-warning me-2"></i>${horasTranscurridas} horas (Límite: 360h)`;
    
    // Limpiar y configurar modal
    $('#infoVentaAnular').html(infoVenta);
    $('#tiempoTranscurrido').html(tiempoInfo);
    $('#motivoTipoAnulacion').val('');
    $('#detalleAnulacion').val('');
    $('#confirmarAnulacion').prop('checked', false);
    $('#btnConfirmarAnulacion').prop('disabled', true).attr('data-venta-id', ventaId);
    
    // Mostrar modal
    $('#modalAnularVenta').modal('show');
};

/**
 * Eventos para el modal de anulación - PASO 4
 */
$(document).ready(function() {
    // Habilitar/deshabilitar botón de confirmación
    function validarFormularioAnulacion() {
        const motivo = $('#motivoTipoAnulacion').val();
        const detalle = $('#detalleAnulacion').val().trim();
        const confirmado = $('#confirmarAnulacion').is(':checked');
        
        const valido = motivo && detalle.length >= 10 && confirmado;
        $('#btnConfirmarAnulacion').prop('disabled', !valido);
    }
    
    // Eventos de validación
    $('#motivoTipoAnulacion, #detalleAnulacion, #confirmarAnulacion').on('change keyup', validarFormularioAnulacion);
    
    // Auto-llenar detalle según motivo seleccionado
    $('#motivoTipoAnulacion').change(function() {
        const motivo = $(this).val();
        const detalle = $('#detalleAnulacion');
        
        if (motivo && motivo !== 'Otro motivo') {
            const sugerencias = {
                'Error en productos': 'Se seleccionaron productos incorrectos en la venta. ',
                'Error en precios': 'Los precios aplicados no corresponden a los acordados. ',
                'Cliente se retracto': 'El cliente decidió cancelar la compra. ',
                'Error del vendedor': 'Error humano en el proceso de venta. ',
                'Venta duplicada': 'Esta venta fue registrada por duplicado. ',
                'Producto defectuoso': 'Los productos entregados presentan defectos. ',
                'Cancelacion por cliente': 'Cancelación solicitada por el cliente. ',
                'Error administrativo': 'Error en el procesamiento administrativo. '
            };
            
            if (sugerencias[motivo]) {
                detalle.val(sugerencias[motivo]);
                detalle.focus();
            }
        } else if (motivo === 'Otro motivo') {
            detalle.val('').focus();
        }
        
        validarFormularioAnulacion();
    });
    
    // Confirmar anulación
    $('#btnConfirmarAnulacion').click(function() {
        const ventaId = $(this).attr('data-venta-id');
        const motivo = $('#motivoTipoAnulacion').val();
        const detalle = $('#detalleAnulacion').val().trim();
        
        if (!ventaId || !motivo || detalle.length < 10) {
            mostrarAlerta('error', 'Error', 'Complete todos los campos correctamente');
            return;
        }
        
        // Confirmar acción
        if (!confirm('¿Está completamente seguro de anular esta venta? Esta acción NO se puede deshacer.')) {
            return;
        }
        
        ejecutarAnulacion(ventaId, motivo, detalle);
    });
});

/**
 * Ejecutar anulación de venta - PASO 4
 */
function ejecutarAnulacion(ventaId, motivo, detalleAdicional) {
    // Mostrar loading
    const btnConfirmar = $('#btnConfirmarAnulacion');
    const originalText = btnConfirmar.html();
    btnConfirmar.html('<i class="fas fa-spinner fa-spin me-2"></i>Anulando...').prop('disabled', true);
    
    $.ajax({
        url: 'php/informes-ventas.php',
        method: 'POST',
        data: { 
            accion: 'anular_venta', 
            venta_id: ventaId,
            motivo: motivo,
            detalle_adicional: detalleAdicional
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#modalAnularVenta').modal('hide');
                
                // Mostrar resultado detallado
                mostrarResultadoAnulacion(response.data);
                
                // Recargar datos para reflejar cambios
                setTimeout(() => {
                    cargarDatos();
                }, 2000);
                
            } else {
                mostrarAlerta('error', 'Error al anular', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', error);
            mostrarAlerta('error', 'Error de conexión', 'No se pudo conectar con el servidor para anular la venta');
        },
        complete: function() {
            btnConfirmar.html(originalText).prop('disabled', false);
        }
    });
}

/**
 * Mostrar resultado de anulación - PASO 4
 */
function mostrarResultadoAnulacion(data) {
    let mensaje = `
        <div class="alert alert-success">
            <h5><i class="fas fa-check-circle me-2"></i>Venta Anulada Correctamente</h5>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <strong>Factura:</strong> ${data.codigo}<br>
                    <strong>Total:</strong> Q${parseFloat(data.total).toFixed(2)}<br>
                    <strong>Cliente:</strong> ${data.cliente}
                </div>
                <div class="col-md-6">
                    <strong>Anulada por:</strong> ${data.usuario_anulo}<br>
                    <strong>Fecha/Hora:</strong> ${data.fecha_anulacion}<br>
                    <strong>Tiempo transcurrido:</strong> ${data.horas_transcurridas}h
                </div>
            </div>
    `;
    
    if (data.productos_restaurados && data.productos_restaurados.length > 0) {
        mensaje += `
            <hr>
            <h6><i class="fas fa-box me-2"></i>Stock Restaurado:</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Stock Anterior</th>
                            <th>Stock Nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        data.productos_restaurados.forEach(producto => {
            mensaje += `
                <tr>
                    <td>${producto.nombre}</td>
                    <td><span class="badge bg-primary">+${producto.cantidad}</span></td>
                    <td>${producto.stock_anterior}</td>
                    <td><strong>${producto.stock_nuevo}</strong></td>
                </tr>
            `;
        });
        
        mensaje += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    mensaje += '</div>';
    
    // Mostrar en modal o alert mejorado
    mostrarModalPersonalizado('Anulación Exitosa', mensaje, 'success');
}

/**
 * Funciones auxiliares para alertas y modales - PASO 4
 */
function mostrarAlerta(tipo, titulo, mensaje) {
    // Puedes usar SweetAlert2 si lo tienes, o alert simple
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: tipo,
            title: titulo,
            text: mensaje,
            confirmButtonText: 'Entendido'
        });
    } else {
        alert(`${titulo}: ${mensaje}`);
    }
}

function mostrarModalPersonalizado(titulo, contenido, tipo) {
    // Crear modal dinámico para mostrar resultados
    const modalId = 'modalResultadoAnulacion';
    let modal = $(`#${modalId}`);
    
    if (modal.length === 0) {
        const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-${tipo}">
                            <h5 class="modal-title text-white" id="${modalId}Label"></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="${modalId}Body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHtml);
        modal = $(`#${modalId}`);
    }
    
    $(`#${modalId}Label`).text(titulo);
    $(`#${modalId}Body`).html(contenido);
    modal.modal('show');
}

/**
 * Actualizar la función cargarDatosResumen para usar las nuevas acciones - PASO 4
 */
function cargarDatosResumen(filtros) {
    $.ajax({
        url: 'php/informes-ventas.php',
        method: 'POST',
        data: { accion: 'obtener_ventas_resumen', ...filtros },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                tablaVentasResumen.clear();
                response.data.forEach(venta => {
                    const estadoBadge = venta.estado == 1 ? 
                        '<span class="badge bg-success">Completada</span>' :
                        '<span class="badge bg-danger">Anulada</span>';
                        
                    const acciones = `
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary" onclick="verDetalleVenta(${venta.id})" 
                                    title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${venta.estado == 1 ? `
                            <button class="btn btn-sm btn-danger" 
                                    onclick="anularVenta(${venta.id}, '${venta.codigo}', '${venta.fecha}', '${venta.total}')"
                                    title="Anular venta">
                                <i class="fas fa-ban"></i>
                            </button>` : `
                            <button class="btn btn-sm btn-secondary" disabled title="Venta ya anulada">
                                <i class="fas fa-times"></i>
                            </button>`}
                        </div>
                    `;
                    
                    tablaVentasResumen.row.add([
                        moment(venta.fecha).format('DD/MM/YYYY'),
                        venta.codigo,
                        venta.cliente_nombre,
                        venta.vendedor_nombre,
                        `Q${parseFloat(venta.subtotal).toFixed(2)}`,
                        `Q${parseFloat(venta.descuento).toFixed(2)}`,
                        `Q${parseFloat(venta.total).toFixed(2)}`,
                        estadoBadge,
                        acciones
                    ]);
                });
                tablaVentasResumen.draw();
            }
        }
    });
}

// REEMPLAZAR ESTAS FUNCIONES EN informes-ventas.js

// Variables globales para gráficos de anuladas
let motivosChart, tendenciaAnulacionesChart, usuariosAnulacionChart, productosAnuladosChart;

// FUNCIÓN PRINCIPAL CORREGIDA
function cargarDatosAnuladas() {
    console.log('Cargando datos de ventas anuladas...');
    
    // Verificar elementos esenciales
    if (!document.getElementById('estadisticasAnuladas')) {
        console.error('Elemento estadisticasAnuladas no encontrado');
        mostrarErrorHTML('La pestaña de anuladas no está configurada correctamente en el HTML');
        return;
    }
    
    try {
        // Obtener filtros usando los elementos existentes
        const fechas = $('#dateRange').data('daterangepicker');
        const filtros = {
            fecha_inicio: fechas ? fechas.startDate.format('YYYY-MM-DD') : moment().subtract(1, 'month').format('YYYY-MM-DD'),
            fecha_fin: fechas ? fechas.endDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'),
            cliente_id: $('#clienteFilter').val() || '',
            vendedor_id: $('#vendedorFilter').val() || ''
        };
        
        console.log('Filtros aplicados:', filtros);
        
        // Mostrar loading
        mostrarLoadingAnuladas();
        
        // Cargar datos de forma secuencial
        cargarEstadisticasAnuladas(filtros)
            .then(() => cargarGraficosAnuladas(filtros))
            .then(() => cargarTablaAnuladas(filtros))
            .catch(error => {
                console.error('Error general:', error);
                mostrarErrorHTML('Error al cargar datos: ' + error.message);
            });
        
    } catch (error) {
        console.error('Error en cargarDatosAnuladas:', error);
        mostrarErrorHTML('Error crítico: ' + error.message);
    }
}

// FUNCIONES DE CARGA CON PROMESAS
function cargarEstadisticasAnuladas(filtros) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_estadisticas_anuladas', ...filtros },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log('Estadísticas anuladas:', response);
                if (response.success) {
                    actualizarEstadisticasAnuladas(response.data);
                    resolve(response.data);
                } else {
                    reject(new Error('Error del servidor: ' + response.error));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX estadísticas:', {xhr, status, error});
                reject(new Error('Error de conexión: ' + error));
            }
        });
    });
}

function cargarGraficosAnuladas(filtros) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_datos_graficos_anuladas', ...filtros },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log('Gráficos anuladas:', response);
                if (response.success) {
                    const datos = response.data;
                    
                    // Crear gráficos con delay para asegurar DOM listo
                    setTimeout(() => {
                        try {
                            if (datos.motivos) crearGraficoMotivosAnulacion(datos.motivos);
                            if (datos.tendencia) crearGraficoTendenciaAnulaciones(datos.tendencia);
                            if (datos.usuarios) crearGraficoUsuariosAnulacion(datos.usuarios);
                            if (datos.productos) crearGraficoProductosAnulados(datos.productos);
                            resolve(datos);
                        } catch (error) {
                            console.error('Error creando gráficos:', error);
                            resolve(datos); // No fallar por errores de gráficos
                        }
                    }, 300);
                } else {
                    reject(new Error('Error del servidor: ' + response.error));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX gráficos:', {xhr, status, error});
                reject(new Error('Error de conexión: ' + error));
            }
        });
    });
}

function cargarTablaAnuladas(filtros) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'php/informes-ventas.php',
            method: 'POST',
            data: { accion: 'obtener_detalle_anuladas', ...filtros },
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                console.log('Tabla anuladas:', response);
                if (response.success) {
                    llenarTablaVentasAnuladas(response.data);
                    resolve(response.data);
                } else {
                    reject(new Error('Error del servidor: ' + response.error));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX tabla:', {xhr, status, error});
                reject(new Error('Error de conexión: ' + error));
            }
        });
    });
}

// FUNCIONES AUXILIARES
function mostrarLoadingAnuladas() {
    // Loading para estadísticas
    $('#estadisticasAnuladas .stat-value').html('<i class="fas fa-spinner fa-spin"></i>');
    
    // Loading para tabla
    const tabla = document.getElementById('tablaVentasAnuladas');
    if (tabla) {
        const tbody = tabla.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Cargando datos...</td></tr>';
        }
    }
}

function mostrarErrorHTML(mensaje) {
    const contenedor = document.getElementById('anuladas-content');
    if (contenedor) {
        contenedor.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <h5><i class="fas fa-exclamation-circle me-2"></i>Error</h5>
                <p>${mensaje}</p>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="cargarDatosAnuladas()">
                    <i class="fas fa-refresh me-1"></i>Intentar de nuevo
                </button>
            </div>
        `;
    }
}

// FUNCIONES DE ACTUALIZACIÓN
function actualizarEstadisticasAnuladas(estadisticas) {
    const elementos = {
        'totalVentasAnuladas': estadisticas.total_anuladas || 0,
        'montoTotalAnulado': `Q${parseFloat(estadisticas.monto_total_anulado || 0).toLocaleString('es-GT', {minimumFractionDigits: 2})}`,
        'porcentajeAnuladas': `${estadisticas.porcentaje_anuladas || 0}%`,
        'tiempoPromedioAnulacion': `${estadisticas.tiempo_promedio_horas || 0}h`
    };
    
    Object.keys(elementos).forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = elementos[id];
            elemento.style.color = '#000'; // Quitar loading color
        } else {
            console.warn(`Elemento ${id} no encontrado`);
        }
    });
}

// FUNCIONES DE GRÁFICOS MEJORADAS
function crearGraficoMotivosAnulacion(datos) {
    const canvas = document.getElementById('motivosAnulacionChart');
    if (!canvas) {
        console.warn('Canvas motivosAnulacionChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        canvas.parentNode.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de motivos de anulación</p>';
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfico anterior
    if (window.motivosChart && typeof window.motivosChart.destroy === 'function') {
        window.motivosChart.destroy();
    }
    
    window.motivosChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: datos.map(item => item.motivo || 'Sin motivo'),
            datasets: [{
                data: datos.map(item => parseInt(item.cantidad) || 0),
                backgroundColor: [
                    '#e74c3c', '#3498db', '#f39c12', '#27ae60', 
                    '#9b59b6', '#e67e22', '#1abc9c', '#34495e'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                            return `${context.label}: ${context.raw} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

function crearGraficoTendenciaAnulaciones(datos) {
    const canvas = document.getElementById('tendenciaAnulacionesChart');
    if (!canvas) {
        console.warn('Canvas tendenciaAnulacionesChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        canvas.parentNode.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de tendencia</p>';
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    if (window.tendenciaAnulacionesChart && typeof window.tendenciaAnulacionesChart.destroy === 'function') {
        window.tendenciaAnulacionesChart.destroy();
    }
    
    window.tendenciaAnulacionesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.map(item => {
                const fecha = new Date(item.mes + '-01');
                return fecha.toLocaleDateString('es-GT', { month: 'short', year: 'numeric' });
            }),
            datasets: [{
                label: 'Cantidad Anuladas',
                data: datos.map(item => parseInt(item.cantidad) || 0),
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#e74c3c',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
}

function crearGraficoUsuariosAnulacion(datos) {
    const canvas = document.getElementById('usuariosAnulacionChart');
    if (!canvas) {
        console.warn('Canvas usuariosAnulacionChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        canvas.parentNode.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de usuarios</p>';
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    if (window.usuariosAnulacionChart && typeof window.usuariosAnulacionChart.destroy === 'function') {
        window.usuariosAnulacionChart.destroy();
    }
    
    window.usuariosAnulacionChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.slice(0, 8).map(item => {
                const usuario = item.usuario || 'Usuario';
                return usuario.length > 15 ? usuario.substring(0, 15) + '...' : usuario;
            }),
            datasets: [{
                label: 'Anulaciones',
                data: datos.slice(0, 8).map(item => parseInt(item.cantidad) || 0),
                backgroundColor: 'rgba(52, 152, 219, 0.8)',
                borderColor: '#3498db',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return datos[context[0].dataIndex].usuario || 'Usuario';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function crearGraficoProductosAnulados(datos) {
    const canvas = document.getElementById('productosAnuladosChart');
    if (!canvas) {
        console.warn('Canvas productosAnuladosChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        canvas.parentNode.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de productos</p>';
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    if (window.productosAnuladosChart && typeof window.productosAnuladosChart.destroy === 'function') {
        window.productosAnuladosChart.destroy();
    }
    
    window.productosAnuladosChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: datos.slice(0, 8).map(item => {
                const producto = item.producto || 'Producto';
                return producto.length > 20 ? producto.substring(0, 20) + '...' : producto;
            }),
            datasets: [{
                label: 'Anulaciones',
                data: datos.slice(0, 8).map(item => parseInt(item.cantidad_anulaciones) || 0),
                backgroundColor: 'rgba(155, 89, 182, 0.8)',
                borderColor: '#9b59b6',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return datos[context[0].dataIndex].producto || 'Producto';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)'
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// FUNCIÓN PARA LLENAR TABLA
function llenarTablaVentasAnuladas(datos) {
    const tabla = document.getElementById('tablaVentasAnuladas');
    if (!tabla) {
        console.warn('Tabla tablaVentasAnuladas no encontrada');
        return;
    }
    
    const tbody = tabla.querySelector('tbody');
    if (!tbody) {
        console.warn('tbody de tablaVentasAnuladas no encontrado');
        return;
    }
    
    tbody.innerHTML = '';
    
    if (!datos || datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay ventas anuladas en el período seleccionado</td></tr>';
        return;
    }
    
    datos.forEach(venta => {
        const fila = document.createElement('tr');
        
        const fechaAnulacion = venta.fecha_anulacion ? 
            moment(venta.fecha_anulacion).format('DD/MM/YYYY HH:mm') : 'N/A';
        
        const tiempoTranscurrido = venta.horas_transcurridas ? 
            (venta.horas_transcurridas < 24 ? 
                `${venta.horas_transcurridas}h` : 
                `${Math.floor(venta.horas_transcurridas / 24)}d ${venta.horas_transcurridas % 24}h`
            ) : 'N/A';
        
        const motivoCorto = venta.motivo && venta.motivo.length > 30 ? 
            venta.motivo.substring(0, 30) + '...' : 
            (venta.motivo || 'Sin motivo');
        
        fila.innerHTML = `
            <td>${fechaAnulacion}</td>
            <td><span class="badge bg-danger">${venta.numero_factura || 'N/A'}</span></td>
            <td>${venta.cliente_nombre || 'Sin cliente'}</td>
            <td>
                <span class="badge bg-warning text-dark" title="${venta.motivo || ''}" style="cursor: help;">
                    ${motivoCorto}
                </span>
                ${venta.detalle_adicional ? '<br><small class="text-muted">Ver detalle</small>' : ''}
            </td>
            <td>${venta.usuario_anulo_nombre || 'N/A'}</td>
            <td class="text-end fw-bold text-danger">
                Q${parseFloat(venta.total_venta || 0).toLocaleString('es-GT', {minimumFractionDigits: 2})}
            </td>
            <td>
                <span class="badge ${venta.horas_transcurridas < 24 ? 'bg-success' : 'bg-warning'}">
                    ${tiempoTranscurrido}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-info" onclick="verDetalleVentaAnulada(${venta.venta_id || venta.id})" title="Ver detalle">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(fila);
    });
    
    // Inicializar DataTable
    if ($.fn.DataTable.isDataTable('#tablaVentasAnuladas')) {
        $('#tablaVentasAnuladas').DataTable().destroy();
    }
    
    $('#tablaVentasAnuladas').DataTable({
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
        order: [[0, 'desc']],
        pageLength: 10,
        columnDefs: [
            { targets: [7], orderable: false }
        ],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm'
            }
        ]
    });
}

// FUNCIÓN PARA VER DETALLE DE VENTA ANULADA
function verDetalleVentaAnulada(ventaId) {
    // Reutilizar la función existente de detalle de venta
    if (typeof window.verDetalleVenta === 'function') {
        window.verDetalleVenta(ventaId);
    } else {
        console.error('Función verDetalleVenta no encontrada');
        alert(`Ver detalle de venta anulada ID: ${ventaId}`);
    }
}

// FUNCIÓN CORREGIDA PARA CONFIGURAR EVENTOS
function configurarEventos() {
    // Evento de filtrar
    $('#btnFiltrar').click(function() {
        cargarDatos();
    });
    
    // Evento mejorado para pestañas con validación completa
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function(e) {
        const target = $(e.target).data('bs-target');
        console.log('Cambiando a pestaña:', target);
        
        if (target === '#anuladas-content') {
            // Verificar elementos críticos antes de cargar
            const elementosCriticos = [
                'anuladas-content',
                'estadisticasAnuladas',
                'tablaVentasAnuladas'
            ];
            
            let faltantes = [];
            elementosCriticos.forEach(id => {
                if (!document.getElementById(id)) {
                    faltantes.push(id);
                }
            });
            
            if (faltantes.length > 0) {
                console.error('Elementos faltantes:', faltantes);
                mostrarErrorHTML(`Elementos requeridos no encontrados: ${faltantes.join(', ')}`);
                return;
            }
            
            // Cargar datos con delay para asegurar DOM listo
            setTimeout(() => {
                cargarDatosAnuladas();
            }, 100);
        }
    });
    
    // Evento de exportar PDF
    $('#btnExportPDF').click(function() {
        exportarPDFCompleto();
    });
}

// FUNCIÓN DE DEBUG MEJORADA
function debugSistemaAnuladas() {
    console.log('=== DIAGNÓSTICO COMPLETO SISTEMA ANULADAS ===');
    
    const elementosRequeridos = [
        'anuladas-content',
        'estadisticasAnuladas', 
        'totalVentasAnuladas',
        'montoTotalAnulado',
        'porcentajeAnuladas',
        'tiempoPromedioAnulacion',
        'motivosAnulacionChart',
        'tendenciaAnulacionesChart', 
        'usuariosAnulacionChart',
        'productosAnuladosChart',
        'tablaVentasAnuladas'
    ];
    
    console.log('--- Verificación de elementos HTML ---');
    let elementosFaltantes = [];
    elementosRequeridos.forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            console.log(`✅ ${id}: ENCONTRADO`);
        } else {
            console.log(`❌ ${id}: NO ENCONTRADO`);
            elementosFaltantes.push(id);
        }
    });
    
    console.log('--- Verificación de librerías ---');
    console.log(`jQuery: ${typeof $ !== 'undefined' ? '✅' : '❌'}`);
    console.log(`Chart.js: ${typeof Chart !== 'undefined' ? '✅' : '❌'}`);
    console.log(`Moment.js: ${typeof moment !== 'undefined' ? '✅' : '❌'}`);
    console.log(`DataTables: ${typeof $.fn.DataTable !== 'undefined' ? '✅' : '❌'}`);
    
    console.log('--- Verificación de funciones ---');
    const funcionesRequeridas = [
        'cargarDatosAnuladas',
        'actualizarEstadisticasAnuladas',
        'crearGraficoMotivosAnulacion',
        'llenarTablaVentasAnuladas'
    ];
    
    funcionesRequeridas.forEach(func => {
        console.log(`${func}: ${typeof window[func] === 'function' ? '✅' : '❌'}`);
    });
    
    // Probar conectividad
    console.log('--- Prueba de conectividad PHP ---');
    $.ajax({
        url: 'informes-ventas.php',
        method: 'POST',
        data: { accion: 'obtener_estadisticas_anuladas', fecha_inicio: '2024-01-01', fecha_fin: '2024-12-31' },
        dataType: 'json',
        success: function(response) {
            console.log('✅ Conectividad PHP: OK');
            console.log('Respuesta:', response);
        },
        error: function(xhr, status, error) {
            console.log('❌ Conectividad PHP: ERROR');
            console.log('Status:', status);
            console.log('Error:', error);
            console.log('ResponseText:', xhr.responseText);
        }
    });
    
    console.log('--- Resumen ---');
    if (elementosFaltantes.length > 0) {
        console.log(`❌ Elementos faltantes: ${elementosFaltantes.join(', ')}`);
        console.log('SOLUCIÓN: Actualizar el HTML con la estructura correcta');
    } else {
        console.log('✅ Todos los elementos HTML están presentes');
    }
    
    console.log('=== FIN DIAGNÓSTICO ===');
    
    return {
        elementosFaltantes,
        todoOK: elementosFaltantes.length === 0
    };
}

// Hacer funciones disponibles globalmente
window.debugSistemaAnuladas = debugSistemaAnuladas;
window.cargarDatosAnuladas = cargarDatosAnuladas;
window.verDetalleVentaAnulada = verDetalleVentaAnulada;

// AGREGAR al final de informes-ventas.js
function actualizarEstadisticasAnuladas(estadisticas) {
    const elementos = {
        'totalVentasAnuladas': estadisticas.total_anuladas || 0,
        'montoTotalAnulado': `Q${parseFloat(estadisticas.monto_total_anulado || 0).toLocaleString('es-GT', {minimumFractionDigits: 2})}`,
        'porcentajeAnuladas': `${estadisticas.porcentaje_anuladas || 0}%`,
        'tiempoPromedioAnulacion': `${estadisticas.tiempo_promedio_horas || 0}h`
    };
    
    Object.keys(elementos).forEach(id => {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = elementos[id];
        }
    });
}

function crearGraficoMotivosAnulacion(datos) {
    const canvas = document.getElementById('motivosAnulacionChart');
    if (!canvas) {
        console.warn('Canvas motivosAnulacionChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de motivos de anulación</p>';
        }
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfico anterior de forma segura
    if (typeof window.motivosChart !== 'undefined' && window.motivosChart !== null) {
        try {
            window.motivosChart.destroy();
        } catch (e) {
            console.warn('Error destruyendo gráfico motivos:', e);
        }
    }
    
    try {
        window.motivosChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: datos.map(item => item.motivo || 'Sin motivo'),
                datasets: [{
                    data: datos.map(item => parseInt(item.cantidad) || 0),
                    backgroundColor: [
                        '#e74c3c', '#3498db', '#f39c12', '#27ae60', 
                        '#9b59b6', '#e67e22', '#1abc9c', '#34495e'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.raw / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        console.log('Gráfico motivos creado exitosamente');
    } catch (error) {
        console.error('Error creando gráfico motivos:', error);
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-danger mt-4">Error cargando gráfico</p>';
        }
    }
}

function crearGraficoTendenciaAnulaciones(datos) {
    const canvas = document.getElementById('tendenciaAnulacionesChart');
    if (!canvas) {
        console.warn('Canvas tendenciaAnulacionesChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de tendencia</p>';
        }
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfico anterior de forma segura
    if (typeof window.tendenciaAnulacionesChart !== 'undefined' && window.tendenciaAnulacionesChart !== null) {
        try {
            window.tendenciaAnulacionesChart.destroy();
        } catch (e) {
            console.warn('Error destruyendo gráfico tendencia:', e);
        }
    }
    
    try {
        window.tendenciaAnulacionesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: datos.map(item => {
                    try {
                        const fecha = new Date(item.mes + '-01');
                        return fecha.toLocaleDateString('es-GT', { month: 'short', year: 'numeric' });
                    } catch (e) {
                        return item.mes;
                    }
                }),
                datasets: [{
                    label: 'Cantidad Anuladas',
                    data: datos.map(item => parseInt(item.cantidad) || 0),
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#e74c3c',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
        console.log('Gráfico tendencia creado exitosamente');
    } catch (error) {
        console.error('Error creando gráfico tendencia:', error);
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-danger mt-4">Error cargando gráfico</p>';
        }
    }
}

function crearGraficoUsuariosAnulacion(datos) {
    const canvas = document.getElementById('usuariosAnulacionChart');
    if (!canvas) {
        console.warn('Canvas usuariosAnulacionChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de usuarios</p>';
        }
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfico anterior de forma segura
    if (typeof window.usuariosAnulacionChart !== 'undefined' && window.usuariosAnulacionChart !== null) {
        try {
            window.usuariosAnulacionChart.destroy();
        } catch (e) {
            console.warn('Error destruyendo gráfico usuarios:', e);
        }
    }
    
    try {
        window.usuariosAnulacionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datos.slice(0, 8).map(item => {
                    const usuario = item.usuario || 'Usuario';
                    return usuario.length > 15 ? usuario.substring(0, 15) + '...' : usuario;
                }),
                datasets: [{
                    label: 'Anulaciones',
                    data: datos.slice(0, 8).map(item => parseInt(item.cantidad) || 0),
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: '#3498db',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                return datos[index] ? datos[index].usuario || 'Usuario' : 'Usuario';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        console.log('Gráfico usuarios creado exitosamente');
    } catch (error) {
        console.error('Error creando gráfico usuarios:', error);
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-danger mt-4">Error cargando gráfico</p>';
        }
    }
}

function crearGraficoProductosAnulados(datos) {
    const canvas = document.getElementById('productosAnuladosChart');
    if (!canvas) {
        console.warn('Canvas productosAnuladosChart no encontrado');
        return;
    }
    
    if (!datos || datos.length === 0) {
        canvas.style.display = 'none';
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-muted mt-4">No hay datos de productos</p>';
        }
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfico anterior de forma segura
    if (typeof window.productosAnuladosChart !== 'undefined' && window.productosAnuladosChart !== null) {
        try {
            window.productosAnuladosChart.destroy();
        } catch (e) {
            console.warn('Error destruyendo gráfico productos:', e);
        }
    }
    
    try {
        window.productosAnuladosChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datos.slice(0, 8).map(item => {
                    const producto = item.producto || 'Producto';
                    return producto.length > 20 ? producto.substring(0, 20) + '...' : producto;
                }),
                datasets: [{
                    label: 'Anulaciones',
                    data: datos.slice(0, 8).map(item => parseInt(item.cantidad_anulaciones) || 0),
                    backgroundColor: 'rgba(155, 89, 182, 0.8)',
                    borderColor: '#9b59b6',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                return datos[index] ? datos[index].producto || 'Producto' : 'Producto';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        console.log('Gráfico productos creado exitosamente');
    } catch (error) {
        console.error('Error creando gráfico productos:', error);
        const container = canvas.closest('.chart-container');
        if (container) {
            container.innerHTML = '<p class="text-center text-danger mt-4">Error cargando gráfico</p>';
        }
    }
}

function llenarTablaVentasAnuladas(datos) {
    const tabla = document.getElementById('tablaVentasAnuladas');
    if (!tabla) {
        console.warn('Tabla tablaVentasAnuladas no encontrada');
        return;
    }
    
    const tbody = tabla.querySelector('tbody');
    if (!tbody) {
        console.warn('tbody de tablaVentasAnuladas no encontrado');
        return;
    }
    
    tbody.innerHTML = '';
    
    if (!datos || datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No hay ventas anuladas en el período seleccionado</td></tr>';
        return;
    }
    
    datos.forEach(venta => {
        const fila = document.createElement('tr');
        
        const fechaAnulacion = venta.fecha_anulacion ? 
            moment(venta.fecha_anulacion).format('DD/MM/YYYY HH:mm') : 'N/A';
        
        const tiempoTranscurrido = venta.horas_transcurridas ? 
            (venta.horas_transcurridas < 24 ? 
                `${venta.horas_transcurridas}h` : 
                `${Math.floor(venta.horas_transcurridas / 24)}d ${venta.horas_transcurridas % 24}h`
            ) : 'N/A';
        
        const motivoCorto = venta.motivo && venta.motivo.length > 30 ? 
            venta.motivo.substring(0, 30) + '...' : 
            (venta.motivo || 'Sin motivo');
        
        fila.innerHTML = `
            <td>${fechaAnulacion}</td>
            <td><span class="badge bg-danger">${venta.numero_factura || 'N/A'}</span></td>
            <td>${venta.cliente_nombre || 'Sin cliente'}</td>
            <td>
                <span class="badge bg-warning text-dark" title="${venta.motivo || ''}" style="cursor: help;">
                    ${motivoCorto}
                </span>
                ${venta.detalle_adicional ? '<br><small class="text-muted">Ver detalle</small>' : ''}
            </td>
            <td>${venta.usuario_anulo_nombre || 'N/A'}</td>
            <td class="text-end fw-bold text-danger">
                Q${parseFloat(venta.total_venta || 0).toLocaleString('es-GT', {minimumFractionDigits: 2})}
            </td>
            <td>
                <span class="badge ${venta.horas_transcurridas < 24 ? 'bg-success' : 'bg-warning'}">
                    ${tiempoTranscurrido}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline-info" onclick="verDetalleVenta(${venta.venta_id || venta.id})" title="Ver detalle">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(fila);
    });
    
    // Inicializar DataTable con configuración local
    if ($.fn.DataTable.isDataTable('#tablaVentasAnuladas')) {
        $('#tablaVentasAnuladas').DataTable().destroy();
    }
    
    $('#tablaVentasAnuladas').DataTable({
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
        order: [[0, 'desc']],
        pageLength: 10,
        columnDefs: [
            { targets: [7], orderable: false }
        ]
    });
    
    console.log('Tabla de anuladas inicializada correctamente');
}


});