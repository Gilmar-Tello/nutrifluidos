document.addEventListener('DOMContentLoaded', function () {

    initAuth('productos');

    let modoActual = 'global'; // 'global' | 'producto'
    let productoActualNombre = null;
    let chartPrecio = null;
    let chartMovimiento = null;
    let datosActuales = null; // último payload cargado, se usa para exportar PDF

    const inputBuscador = document.getElementById('buscadorProducto');
    const resultadosDiv = document.getElementById('resultadosBusquedaProducto');
    let debounceTimer = null;

    // ===================== Buscador de producto =====================

    inputBuscador.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(debounceTimer);
        if (q.length < 2) {
            resultadosDiv.style.display = 'none';
            resultadosDiv.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(() => buscarProductos(q), 300);
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('buscadorProductoWrapper').contains(e.target)) {
            resultadosDiv.style.display = 'none';
        }
    });

    function buscarProductos(q) {
        fetch(`php/kardex.php?accion=buscarProductos&q=${encodeURIComponent(q)}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    mostrarAlertaKardex(data.error, 'danger');
                    return;
                }
                renderResultadosBusqueda(data.productos || []);
            })
            .catch(err => {
                console.error('Error buscando productos:', err);
                mostrarAlertaKardex('Error al buscar productos', 'danger');
            });
    }

    function renderResultadosBusqueda(productos) {
        if (productos.length === 0) {
            resultadosDiv.innerHTML = '<div class="p-3 text-muted">Sin resultados</div>';
            resultadosDiv.style.display = 'block';
            return;
        }

        resultadosDiv.innerHTML = productos.map(p => `
            <div class="resultado-item" data-id="${p.id}" data-nombre="${escapeHtml(p.nombre)}">
                <span>${escapeHtml(p.nombre)}</span>
                <small>${escapeHtml(p.codigo)} &middot; Stock: ${p.stock}</small>
            </div>
        `).join('');
        resultadosDiv.style.display = 'block';

        resultadosDiv.querySelectorAll('.resultado-item').forEach(item => {
            item.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                inputBuscador.value = nombre;
                resultadosDiv.style.display = 'none';
                cargarKardexProducto(id);
            });
        });
    }

    document.getElementById('btnVerGlobal').addEventListener('click', function () {
        inputBuscador.value = '';
        resultadosDiv.style.display = 'none';
        cargarResumenGlobal();
    });

    document.getElementById('btnExportarPDF').addEventListener('click', exportarPDF);

    // ===================== Vista global =====================

    function cargarResumenGlobal() {
        modoActual = 'global';
        productoActualNombre = null;

        document.getElementById('cardProductoInfo').style.display = 'none';
        document.getElementById('badgeModoVista').innerHTML = '<i class="fas fa-globe me-1"></i>Vista global: todos los productos';
        document.getElementById('thProducto').style.display = '';
        document.getElementById('tituloTablaMovimientos').textContent = 'Movimientos Recientes (todos los productos)';
        document.getElementById('tituloGraficaPrecio').innerHTML = '<i class="fas fa-chart-line me-2"></i>Costo Total Comprado por Mes';
        document.getElementById('tituloGraficaMovimiento').innerHTML = '<i class="fas fa-boxes me-2"></i>Entradas vs Salidas por Mes';

        fetch('php/kardex.php?accion=resumenGlobal')
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    mostrarAlertaKardex(data.error, 'danger');
                    return;
                }
                datosActuales = data;

                pintarStats([
                    { valor: data.stats.total_movimientos, label: 'Movimientos Totales', icon: 'fa-list', esMoneda: false },
                    { valor: data.stats.total_comprado, label: 'Total Comprado', icon: 'fa-shopping-cart', esMoneda: true },
                    { valor: data.stats.productos_con_compras, label: 'Productos con Compras', icon: 'fa-boxes', esMoneda: false },
                    { valor: data.stats.precio_promedio_general, label: 'Precio Promedio General', icon: 'fa-tag', esMoneda: true }
                ]);

                pintarGraficaLineaGlobal(data.serie_costos || []);
                pintarGraficaMovimientoGlobal(data.serie_movimientos || []);
                pintarTabla(data.movimientos || [], true);
            })
            .catch(err => {
                console.error(err);
                mostrarAlertaKardex('Error al cargar el resumen global', 'danger');
            });
    }

    // ===================== Vista de un producto =====================

    function cargarKardexProducto(id) {
        modoActual = 'producto';

        fetch(`php/kardex.php?accion=kardexProducto&producto_id=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    mostrarAlertaKardex(data.error, 'danger');
                    return;
                }
                datosActuales = data;
                productoActualNombre = data.producto.nombre;

                document.getElementById('cardProductoInfo').style.display = 'flex';
                document.getElementById('productoInfoNombre').textContent = data.producto.nombre;
                document.getElementById('productoInfoCodigo').textContent = data.producto.codigo;
                document.getElementById('productoInfoStock').textContent = data.producto.stock;

                document.getElementById('badgeModoVista').innerHTML = `<i class="fas fa-box me-1"></i>Producto: ${escapeHtml(data.producto.nombre)}`;
                document.getElementById('thProducto').style.display = 'none';
                document.getElementById('tituloTablaMovimientos').textContent = 'Movimientos de este Producto';
                document.getElementById('tituloGraficaPrecio').innerHTML = '<i class="fas fa-chart-line me-2"></i>Evolución del Precio de Compra';
                document.getElementById('tituloGraficaMovimiento').innerHTML = '<i class="fas fa-boxes me-2"></i>Movimiento de Stock';

                pintarStats([
                    { valor: data.stats.precio_actual, label: 'Precio Actual', icon: 'fa-tag', esMoneda: true },
                    { valor: data.stats.precio_promedio, label: 'Precio Promedio', icon: 'fa-chart-line', esMoneda: true },
                    { valor: data.stats.precio_minimo, label: 'Precio Mínimo', icon: 'fa-arrow-down', esMoneda: true },
                    { valor: data.stats.precio_maximo, label: 'Precio Máximo', icon: 'fa-arrow-up', esMoneda: true }
                ]);

                pintarGraficaLineaProducto(data.serie_precios || []);
                pintarGraficaStockProducto(data.serie_stock || []);
                pintarTabla(data.movimientos || [], false);
            })
            .catch(err => {
                console.error(err);
                mostrarAlertaKardex('Error al cargar el kardex del producto', 'danger');
            });
    }

    // ===================== Tarjetas de estadísticas =====================

    function pintarStats(items) {
        items.forEach((item, idx) => {
            const i = idx + 1;
            document.getElementById(`statLabel${i}`).textContent = item.label;
            document.getElementById(`iconStat${i}`).className = `fas ${item.icon}`;
            const valor = (item.valor === null || item.valor === undefined) ? 0 : item.valor;
            document.getElementById(`statValor${i}`).textContent = item.esMoneda
                ? 'Q' + Number(valor).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                : Number(valor).toLocaleString('es-GT');
        });
    }

    // ===================== Gráficas =====================

    function chartOptionsBase() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        };
    }

    function pintarGraficaLineaGlobal(serie) {
        const ctx = document.getElementById('graficaPrecio').getContext('2d');
        if (chartPrecio) chartPrecio.destroy();
        chartPrecio = new Chart(ctx, {
            type: 'line',
            data: {
                labels: serie.map(s => s.mes),
                datasets: [{
                    label: 'Total Comprado (Q)',
                    data: serie.map(s => parseFloat(s.total)),
                    borderColor: '#1e3a8a',
                    backgroundColor: 'rgba(30, 58, 138, 0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: chartOptionsBase()
        });
    }

    function pintarGraficaLineaProducto(serie) {
        const ctx = document.getElementById('graficaPrecio').getContext('2d');
        if (chartPrecio) chartPrecio.destroy();
        chartPrecio = new Chart(ctx, {
            type: 'line',
            data: {
                labels: serie.map(s => formatFecha(s.fecha)),
                datasets: [{
                    label: 'Precio de Compra (Q)',
                    data: serie.map(s => parseFloat(s.precio_compra_unitario)),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4
                }]
            },
            options: chartOptionsBase()
        });
    }

    function pintarGraficaMovimientoGlobal(serie) {
        const ctx = document.getElementById('graficaMovimiento').getContext('2d');
        if (chartMovimiento) chartMovimiento.destroy();

        const meses = [...new Set(serie.map(s => s.mes))].sort();
        const entradas = meses.map(mes => {
            const f = serie.find(s => s.mes === mes && s.operacion === 'agregar');
            return f ? parseInt(f.total_cantidad) : 0;
        });
        const salidas = meses.map(mes => {
            const f = serie.find(s => s.mes === mes && s.operacion === 'reducir');
            return f ? parseInt(f.total_cantidad) : 0;
        });

        chartMovimiento = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [
                    { label: 'Entradas', data: entradas, backgroundColor: '#10b981' },
                    { label: 'Salidas', data: salidas, backgroundColor: '#ef4444' }
                ]
            },
            options: chartOptionsBase()
        });
    }

    function pintarGraficaStockProducto(serie) {
        const ctx = document.getElementById('graficaMovimiento').getContext('2d');
        if (chartMovimiento) chartMovimiento.destroy();
        chartMovimiento = new Chart(ctx, {
            type: 'line',
            data: {
                labels: serie.map(s => formatFecha(s.fecha)),
                datasets: [{
                    label: 'Stock Resultante',
                    data: serie.map(s => parseInt(s.stock_nuevo)),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.2,
                    stepped: true,
                    fill: true
                }]
            },
            options: chartOptionsBase()
        });
    }

    // ===================== Tabla de movimientos =====================

    function pintarTabla(movimientos, mostrarProducto) {
        const tbody = document.getElementById('tbodyKardex');

        if ($.fn.DataTable.isDataTable('#tablaKardex')) {
            $('#tablaKardex').DataTable().destroy();
        }

        tbody.innerHTML = movimientos.map(m => `
            <tr>
                <td data-order="${escapeHtml(m.fecha || '')}">${formatFechaHora(m.fecha)}</td>
                <td${mostrarProducto ? '' : ' style="display:none;"'}>${escapeHtml(m.producto_nombre || '')}</td>
                <td><span class="badge ${m.operacion === 'agregar' ? 'bg-success' : 'bg-danger'}">${m.operacion === 'agregar' ? 'Entrada' : 'Salida'}</span></td>
                <td>${m.cantidad}</td>
                <td>${m.precio_compra_unitario !== null ? 'Q' + Number(m.precio_compra_unitario).toFixed(2) : '-'}</td>
                <td>${m.costo_total !== null ? 'Q' + Number(m.costo_total).toFixed(2) : '-'}</td>
                <td>${m.stock_nuevo}</td>
                <td>${escapeHtml(m.usuario_responsable || '')}</td>
            </tr>
        `).join('');

        document.getElementById('thProducto').style.display = mostrarProducto ? '' : 'none';

        $('#tablaKardex').DataTable({
            language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json' },
            order: [[0, 'desc']],
            pageLength: 25
        });
    }

    // ===================== Exportar PDF =====================

    function exportarPDF() {
        if (!datosActuales) {
            mostrarAlertaKardex('Primero carga algún dato antes de exportar', 'warning');
            return;
        }

        const payload = {
            accion: 'exportarKardexPDF',
            modo: modoActual,
            producto: modoActual === 'producto' ? datosActuales.producto : null,
            stats: datosActuales.stats,
            movimientos: datosActuales.movimientos
        };

        fetch('php/kardex_pdf.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(res => {
                if (!res.ok) throw new Error('Error al generar el PDF');
                return res.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = modoActual === 'producto'
                    ? `kardex_${(productoActualNombre || 'producto').replace(/\s+/g, '_')}.pdf`
                    : 'kardex_global.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
            })
            .catch(err => {
                console.error(err);
                mostrarAlertaKardex('Error al exportar el PDF', 'danger');
            });
    }

    // ===================== Utilidades =====================

    function formatFecha(fechaStr) {
        const d = new Date(fechaStr);
        if (isNaN(d.getTime())) return fechaStr;
        return d.toLocaleDateString('es-GT', { day: '2-digit', month: '2-digit', year: '2-digit' });
    }

    function formatFechaHora(fechaStr) {
        const d = new Date(fechaStr);
        if (isNaN(d.getTime())) return fechaStr;
        return d.toLocaleDateString('es-GT', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
               d.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function mostrarAlertaKardex(mensaje, tipo = 'info') {
        const alertaExistente = document.querySelector('.alert');
        if (alertaExistente) {
            alertaExistente.remove();
        }

        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show mb-3`;
        alerta.setAttribute('role', 'alert');
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        const contenedor = document.querySelector('.main-content') || document.body;
        contenedor.prepend(alerta);

        setTimeout(() => {
            const alertaBootstrap = bootstrap.Alert.getOrCreateInstance(alerta);
            alertaBootstrap.close();
        }, 5000);
    }

    // Carga inicial: vista global
    cargarResumenGlobal();
});
