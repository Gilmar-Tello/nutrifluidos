document.addEventListener('DOMContentLoaded', function () {
    // Verificar sesión y permisos requeridos para abonos (solo admin)
    initAuth('abonos');
    
    // Variables globales
    const tbody = document.getElementById('tbodyCreditosPendientes');
    const modalCreditos = document.getElementById('modalCreditosPagados');
    const modalRegistrarPago = document.getElementById('modalRegistrarPago');
    // --- NUEVAS DECLARACIONES PARA EL REPORTE ---
    const formReporte = document.getElementById('formReporteAbonos');
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const alertasReporte = document.getElementById('alertasReporte');

    // Event listeners principales
    if (modalRegistrarPago) {
        modalRegistrarPago.addEventListener('show.bs.modal', function(event) {
    const relatedTarget = event.relatedTarget;

    // 🧽 Limpiar campos manuales al abrir el modal
    document.getElementById('observacionMetodoPago').value = '';
    document.getElementById('numeroReferencia').value = '';
    document.getElementById('descuentoPago').value = '';
    document.getElementById('observacionDescuento').value = '';
    document.getElementById('observaciones').value = '';
    document.getElementById('comprobantePago').value = '';
    document.getElementById('previewComprobante').innerHTML = '';

    // Si se abrió desde el botón general, cargar facturas disponibles
    if (!relatedTarget || !relatedTarget.classList.contains('registrar-pago')) {
        cargarFacturasPendientes();
    }
});

    }
    
    const facturaSelect = document.getElementById('facturaSelect');
    if (facturaSelect) {
        facturaSelect.addEventListener('change', actualizarSaldoPendiente);
    }
    
    const btnGuardarPago = document.getElementById('btnGuardarPago');
    if (btnGuardarPago) {
        btnGuardarPago.addEventListener('click', guardarPago);
    }
    
    const comprobantePago = document.getElementById('comprobantePago');
    if (comprobantePago) {
        comprobantePago.addEventListener('change', previsualizarComprobante);
    }
    
    // Cargar datos cuando se abre modal de créditos pagados
    if (modalCreditos) {
        modalCreditos.addEventListener('show.bs.modal', function() {
            console.log('Modal de créditos pagados abierto, cargando datos...');
            cargarCreditosPagados();
        });
    }
    
    // Establecer la fecha actual por defecto
    const fechaPago = document.getElementById('fechaPago');
    if (fechaPago) {
        const hoy = new Date();
        const fechaFormateada = hoy.toISOString().split('T')[0];
        fechaPago.value = fechaFormateada;
    }

    formatearQuetzal();
    cargarCreditosPendientes();
    cargarHistorialPagos();

     // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo = 'info') {
        const alerta = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertasReporte.innerHTML = alerta;
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            const alertElement = alertasReporte.querySelector('.alert');
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }

    // Función para validar fechas
    function validarFechas() {
        const fechaInicio = document.getElementById('fechaInicio').value;
        const fechaFin = document.getElementById('fechaFin').value;
        
        if (fechaInicio && fechaFin) {
            if (new Date(fechaInicio) > new Date(fechaFin)) {
                mostrarAlerta('La fecha de inicio no puede ser mayor a la fecha fin', 'warning');
                return false;
            }
        }
        return true;
    }


    // Función para formatear fecha
    function formatearFecha(fecha) {
        const opciones = { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit' 
        };
        return new Date(fecha).toLocaleDateString('es-GT', opciones);
    }

    // Función para generar reporte PDF
    btnGenerarReporte.addEventListener('click', function() {
        if (!validarFechas()) return;

        const formData = new FormData(formReporte);
        const params = new URLSearchParams();
        
        // Agregar parámetros a la URL
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                params.append(key, value);
            }
        }

        btnGenerarReporte.disabled = true;
        btnGenerarReporte.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...';

        // Hacer petición AJAX para generar reporte
        fetch(`php/abonos.php?accion=generar_reporte&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(`Reporte generado exitosamente. ${data.total_registros} registros procesados.`, 'success');
                    
                    // Descargar el PDF automáticamente
                    const nombreArchivo = data.pdf_url.split('/').pop();
                    const linkDescarga = document.createElement('a');
                    linkDescarga.href = `php/abonos.php?accion=descargar_reporte&archivo=${nombreArchivo}`;
                    linkDescarga.download = nombreArchivo;
                    document.body.appendChild(linkDescarga);
                    linkDescarga.click();
                    document.body.removeChild(linkDescarga);
                    
                    // Cerrar modal después de un breve delay
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalReporteAbonos'));
                        modal.hide();
                    }, 2000);
                } else {
                    mostrarAlerta(data.mensaje || 'Error al generar reporte', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error de conexión al generar reporte', 'danger');
            })
            .finally(() => {
                btnGenerarReporte.disabled = false;
                btnGenerarReporte.innerHTML = '<i class="fas fa-file-pdf me-2"></i>Generar PDF';
            });
    });

    // Validación en tiempo real de fechas
    document.getElementById('fechaInicio').addEventListener('change', validarFechas);
    document.getElementById('fechaFin').addEventListener('change', validarFechas);

    // NUEVA LÓGICA DE LIMPIEZA CUANDO SE CIERRA EL MODAL DE REPORTE
    const modalReporteAbonos = document.getElementById('modalReporteAbonos');
    if (modalReporteAbonos) {
        modalReporteAbonos.addEventListener('hidden.bs.modal', function () {
            // 1. Resetear el formulario de reporte
            if (formReporte) {
                formReporte.reset(); // Esto limpia todos los campos del formulario
            }

            // 2. Limpiar el área de alertas
            if (alertasReporte) {
                alertasReporte.innerHTML = ''; // Elimina cualquier alerta mostrada
            }

            // Opcional: Si el campo de cliente es un input de texto, podrías querer limpiarlo si no se resetea con form.reset()
            const clienteFiltro = document.getElementById('clienteFiltro');
            if (clienteFiltro) {
                clienteFiltro.value = '';
            }

            // Opcional: Si tienes flatpickr u otros componentes de fecha que no se resetean bien
            // Podrías reiniciarlos explícitamente si `form.reset()` no es suficiente para ellos
            const fechaInicioInput = document.getElementById('fechaInicio');
            const fechaFinInput = document.getElementById('fechaFin');
            if (fechaInicioInput && fechaInicioInput._flatpickr) {
                fechaInicioInput._flatpickr.clear();
            }
            if (fechaFinInput && fechaFinInput._flatpickr) {
                fechaFinInput._flatpickr.clear();
            }

            // Nota: Ya eliminamos cualquier referencia a 'areaVistaPrevia' de las funciones de JS y del HTML,
            // así que no necesitamos una línea para ocultarla aquí.
        });
    }

});


function formatearQuetzal(valor) {
    if (!valor) return 'Q0.00';
    return 'Q' + parseFloat(valor).toLocaleString('es-GT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
   //.ver-comprobante
function cargarCreditosPendientes() {
    console.log('Cargando créditos pendientes...');
    
    fetch('php/abonos.php?accion=creditos_pendientes')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);

            if (!Array.isArray(data)) {
                console.error('Respuesta inesperada del servidor:', data);
                mostrarErrorEnTabla('tbodyCreditosPendientes', 'Error en el formato de datos');
                return;
            }

            // Destruir tabla existente
            if ($.fn.DataTable.isDataTable('#tablaCreditosPendientes')) {
                $('#tablaCreditosPendientes').DataTable().destroy();
            }   

            const tbody = document.getElementById('tbodyCreditosPendientes');
            if (!tbody) {
                console.error('Elemento tbodyCreditosPendientes no encontrado');
                return;
            }
            
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center">
                            <i class="fas fa-info-circle text-muted"></i>
                            <p class="mt-2 text-muted">No hay créditos pendientes</p>
                        </td>
                    </tr>
                `;
                return;
            }

            // Insertar los datos en la tabla
            data.forEach(item => {
                const progreso = calcularProgreso(item.monto_pagado, item.monto_total);
                const clienteNombre = item.cliente || 'Sin cliente asignado';
                const row = `
                    <tr>
                        <td>${item.factura || 'N/A'}</td>
                        <td>${clienteNombre}</td>
                        <td>${item.fecha_venta || 'N/A'}</td>
                        <td>${formatearQuetzal(item.monto_total || 0)}</td>
                        <td>${formatearQuetzal(item.monto_pagado || 0)}</td>
                        <td>${formatearQuetzal(item.saldo || 0)}</td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar" style="width: ${progreso}%;">${progreso}%</div>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary ver-comprobante" 
                                        data-comprobante="${item.comprobante || ''}" 
                                        title="Ver comprobante">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success registrar-pago" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalRegistrarPago"
                                        data-factura="${item.factura}"
                                        data-id-credito="${item.id_credito || ''}"
                                        title="Registrar pago">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                                <button class="btn btn-sm btn-info descargar-pdf" 
                                        data-factura="${item.factura}"
                                        title="Descargar comprobante PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });

            // Inicializar DataTable
            $('#tablaCreditosPendientes').DataTable({
                paging: false,
                ordering: false,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                }
            });
        })
        .catch(err => {
            console.error('Error al cargar créditos pendientes:', err);
            mostrarErrorEnTabla('tbodyCreditosPendientes', 'Error al cargar los datos');
        });
}

function cargarHistorialPagos() {
    console.log('Cargando historial de pagos...');
    
    fetch('php/abonos.php?accion=historial_pagos')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Historial recibido:', data);

            if ($.fn.DataTable.isDataTable('#tablaHistorialPagos')) {
                $('#tablaHistorialPagos').DataTable().destroy();
            }

            const tbody = document.getElementById('tbodyHistorialPagos');
            if (!tbody) {
                console.error('Elemento tbodyHistorialPagos no encontrado');
                return;
            }
            
            tbody.innerHTML = '';

            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center">
                            <i class="fas fa-info-circle text-muted"></i>
                            <p class="mt-2 text-muted">No hay pagos registrados</p>
                        </td>
                    </tr>
                `;
                return;
            }

            data.forEach(item => {
                const row = `
                    <tr>
                        <td>${item.id_pago}</td>
                        <td>${item.factura}</td>
                        <td>${item.cliente}</td>
                        <td>${item.fecha_pago}</td>
                        <td>${formatearQuetzal(item.monto)}</td>
                        <td>${item.metodo_pago}</td>
                        <td>${item.comprobante ?? 'Sin comprobante'}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-primary ver-comprobante" 
                                        data-comprobante="${item.comprobante}" 
                                        title="Ver comprobante">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-success imprimir-comprobante" 
                                        data-comprobante="${item.comprobante}" 
                                        title="Imprimir comprobante">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button class="btn btn-sm btn-info descargar-pdf" 
                                        data-factura="${item.factura}"
                                        title="Descargar comprobante PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', row);
            });


            $('#tablaHistorialPagos').DataTable({
                paging: false,
                //ordering: false,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                }
            });
        })
        .catch(err => {
            console.error('Error al cargar historial de pagos:', err);
            mostrarErrorEnTabla('tbodyHistorialPagos', 'Error al cargar los datos');
        });
}

function calcularProgreso(pagado, total) {
    if (!total || total == 0) return 0;
    return Math.round((pagado / total) * 100);
}

function cargarFacturasPendientes() {
    console.log('Cargando facturas pendientes...');
    
    return fetch('php/abonos.php?accion=obtenerFacturasPendientes')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Facturas pendientes recibidas:', data);
            
            const selectFactura = document.getElementById('facturaSelect');
            if (!selectFactura) {
                console.error('Elemento facturaSelect no encontrado');
                return;
            }
            
            // Limpiar opciones existentes (excepto la primera)
            while (selectFactura.options.length > 1) {
                selectFactura.options.remove(1);
            }
            
            if (!Array.isArray(data) || data.length === 0) {
                console.warn('No hay facturas pendientes disponibles');
                return;
            }
            
            // Agregar nuevas opciones
            data.forEach(factura => {
                const option = document.createElement('option');
                option.value = factura.factura;
                option.dataset.idCredito = factura.id_credito;
                option.dataset.saldo = factura.saldo;
                option.dataset.cliente = factura.cliente;
                option.textContent = `${factura.factura} - ${factura.cliente} (${formatearQuetzal(factura.saldo)})`;
                selectFactura.appendChild(option);
            });
            
            return data; // Retornar los datos para usar en el .then()
        })
        .catch(error => {
            console.error('Error al cargar facturas:', error);
            alert('Error al cargar las facturas pendientes');
            throw error;
        });
}

function actualizarSaldoPendiente() {
    const selectFactura = document.getElementById('facturaSelect');
    const selectedOption = selectFactura.options[selectFactura.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const saldo = selectedOption.dataset.saldo;
        const saldoPendienteEl = document.getElementById('saldoPendiente');
        
        if (saldoPendienteEl) {
            saldoPendienteEl.textContent = `Saldo pendiente: ${formatearQuetzal(saldo)}`;
        }
        
        // Establecer el monto máximo del pago igual al saldo pendiente
        const inputMonto = document.getElementById('montoPago');
        if (inputMonto) {
            inputMonto.max = saldo;
            inputMonto.value = saldo;
        }
        
        // Guardar el ID del crédito en el campo oculto
        const idCreditoEl = document.getElementById('idCredito');
        if (idCreditoEl) {
            idCreditoEl.value = selectedOption.dataset.idCredito;
        }
    } else {
        const saldoPendienteEl = document.getElementById('saldoPendiente');
        if (saldoPendienteEl) {
            saldoPendienteEl.textContent = 'Saldo pendiente: Q0.00';
        }
        
        const inputMonto = document.getElementById('montoPago');
        if (inputMonto) {
            inputMonto.value = '';
        }
        
        const idCreditoEl = document.getElementById('idCredito');
        if (idCreditoEl) {
            idCreditoEl.value = '';
        }
    }
}

// Event listener para botones de registrar pago específicos
document.addEventListener('click', function(e) {
    if (e.target.closest('.registrar-pago')) {
        const btn = e.target.closest('.registrar-pago');
        const factura = btn.dataset.factura;
        const idCredito = btn.dataset.idCredito;
        
        console.log('Botón registrar pago clickeado:', { factura, idCredito });
        
        // Cargar las facturas y preseleccionar la específica
        cargarFacturasPendientes().then(() => {
            preseleccionarFactura(factura, idCredito);
        });
    }
});

// Nueva función para preseleccionar una factura específica
function preseleccionarFactura(facturaTarget, idCreditoTarget) {
    const selectFactura = document.getElementById('facturaSelect');
    
    if (!selectFactura || !facturaTarget) {
        console.warn('No se puede preseleccionar la factura:', { selectFactura, facturaTarget });
        return;
    }
    
    // Buscar y seleccionar la opción correcta
    for (let i = 0; i < selectFactura.options.length; i++) {
        const option = selectFactura.options[i];
        if (option.value === facturaTarget) {
            selectFactura.selectedIndex = i;
            console.log('Factura preseleccionada:', facturaTarget);
            
            // Actualizar el saldo pendiente automáticamente
            actualizarSaldoPendiente();
            break;
        }
    }
}

// Event listener para el botón de imprimir
document.addEventListener('click', function(e) {
    if (e.target.closest('.imprimir-comprobante')) {
        const comprobante = e.target.closest('.imprimir-comprobante').dataset.comprobante;
        imprimirComprobante(comprobante);
    }
});

function imprimirComprobante(rutaComprobante) {
    if (!rutaComprobante || rutaComprobante === 'null') {
        alert('No hay comprobante disponible para imprimir');
        return;
    }
    
    // Si es un PDF
    if (rutaComprobante.toLowerCase().includes('.pdf')) {
        window.open(rutaComprobante, '_blank');
    } 
    // Si es una imagen
    else {
        const ventanaImpresion = window.open('', '_blank');
        ventanaImpresion.document.write(`
            <html>
                <head><title>Comprobante de Pago</title></head>
                <body onload="window.print(); window.close();">
                    <img src="${rutaComprobante}" style="max-width: 100%; height: auto;">
                </body>
            </html>
        `);
    }
}

function guardarPago() {
    const form = document.getElementById('formRegistrarPago');
    if (!form || !form.checkValidity()) {
        if (form) form.reportValidity();
        return;
    }

    // Mostrar indicador de carga
    const btnGuardar = document.getElementById('btnGuardarPago');
    const textoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    // Obtener los datos del formulario
    const idCredito = document.getElementById('idCredito').value;
    const factura = document.getElementById('facturaSelect').value;
    const selectFactura = document.getElementById('facturaSelect');
    const cliente = selectFactura.options[selectFactura.selectedIndex].dataset.cliente;
    const fechaPago = document.getElementById('fechaPago').value;
    const monto = document.getElementById('montoPago').value;
    const metodoPago = document.getElementById('metodoPago').value;
    const numeroReferencia = document.getElementById('numeroReferencia').value;
    const observaciones = document.getElementById('observaciones').value;
    const obPago = document.getElementById('observacionMetodoPago').value.trim();
    const descuento = document.getElementById('descuentoPago').value.trim();
    const ob_descuento = document.getElementById('observacionDescuento').value.trim();


    const formData = new FormData();
    formData.append('accion', 'guardarPago');
    formData.append('id_credito', idCredito);
    formData.append('factura', factura);
    formData.append('cliente', cliente);
    formData.append('fecha_pago', fechaPago);
    formData.append('monto', monto);
    formData.append('metodo_pago', metodoPago);
    formData.append('numero_referencia', numeroReferencia);
    formData.append('observaciones', observaciones);
    formData.append('ObPago', obPago);
    formData.append('descuento', descuento);
    formData.append('ob_descuento', ob_descuento);
    
    formData.append('observaciones', observaciones);

// CORREGIDO: Obtener usuario actual del sistema de autenticación
let userData = {};
try {
    userData = JSON.parse(localStorage.getItem('userData') || '{}');
} catch (e) {
    console.error('Error al obtener datos del usuario:', e);
    userData = {};
}

const nombreUsuario = userData.nombre || 'Usuario'; // Cambiar nombre_completo por nombre
formData.append('usuario_abono', nombreUsuario);

const comprobante = document.getElementById('comprobantePago').files[0];

    //const comprobante = document.getElementById('comprobantePago').files[0];
    if (comprobante) {
        formData.append('comprobante', comprobante);
    }

    // Enviar los datos
    fetch('php/abonos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Respuesta cruda del servidor:', text);
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Error parsing JSON:', e);
            throw new Error("Respuesta no es JSON válido: " + text);
        }

        if (data.success) {
            if (data.pdf_url) {
                // SEGURIDAD (16-09-2026): el PDF se sirve por php/archivo.php (con sesión).
                const urlPdf = data.pdf_url;

                // Abrir el PDF en una nueva pestaña para que el usuario pueda
                // verlo, imprimirlo o descargarlo desde el visor del navegador.
                const pdfWindow = window.open(urlPdf, '_blank');
                if (!pdfWindow) {
                    alert('Pago registrado correctamente.\n\nEl navegador bloqueó la apertura automática del comprobante. Puedes descargarlo desde el Historial de Pagos.');
                } else {
                    // Notificación suave (no bloquea la nueva pestaña ya abierta)
                    console.log('Pago registrado y comprobante abierto en nueva pestaña.');
                }
            } else {
                alert('Pago registrado correctamente');
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarPago'));
            if (modal) modal.hide();
            location.reload();
        } else {
            // El backend respondió con success:false — mostrar el mensaje
            // en vez de fallar silenciosamente.
            const msgError = data.message || data.mensaje || 'Error desconocido al guardar el pago';
            console.error('El servidor rechazó el pago:', msgError);
            alert('No se pudo guardar el abono.\n\nDetalle del servidor:\n' + msgError);
        }
    })
    .catch(error => {
        console.error('Error al guardar el pago:', error);
        alert('Ha ocurrido un error al guardar el pago: ' + error.message);
    })
    .finally(() => {
        // Restaurar botón
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = textoOriginal;
    });
}

function previsualizarComprobante(event) {
    const file = event.target.files[0];
    const previewContainer = document.getElementById('previewComprobante');
    
    if (!previewContainer) {
        console.error('Elemento previewComprobante no encontrado');
        return;
    }
    
    previewContainer.innerHTML = '';
    
    if (file) {
        // Si es una imagen
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'img-thumbnail mt-2';
            img.style.maxHeight = '150px';
            previewContainer.appendChild(img);
        } 
        // Si es un PDF
        else if (file.type === 'application/pdf') {
            const icon = document.createElement('div');
            icon.innerHTML = '<i class="fas fa-file-pdf fa-3x text-danger"></i>';
            icon.className = 'text-center mt-2';
            
            const fileName = document.createElement('p');
            fileName.textContent = file.name;
            fileName.className = 'text-center';
            
            previewContainer.appendChild(icon);
            previewContainer.appendChild(fileName);
        }
    }
}

function cargarCreditosPagados() {
    console.log('Iniciando carga de créditos pagados...');
    
    // Mostrar indicador de carga
    const tbody = document.getElementById('tablaCreditosPagados');
    if (!tbody) {
        console.error('Elemento tablaCreditosPagados no encontrado');
        return;
    }
    
    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando créditos pagados...</p>
            </td>
        </tr>
    `;

    fetch('php/abonos.php?accion=creditos_pagados', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(creditos => {
        console.log('Créditos pagados recibidos:', creditos);
        
        if (creditos.error) {
            mostrarError(creditos.mensaje || 'Error desconocido');
        } else {
            mostrarCreditosEnTabla(creditos);
        }
    })
    .catch(error => {
        console.error('Error al cargar créditos pagados:', error);
        mostrarError('Error al cargar los datos: ' + error.message);
    });
}

function mostrarCreditosEnTabla(creditos) {
    const tbody = document.getElementById('tablaCreditosPagados');
    
    if (!Array.isArray(creditos) || creditos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <i class="fas fa-info-circle text-muted"></i>
                    <p class="mt-2 text-muted">No hay créditos completamente pagados</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = creditos.map(credito => `
        <tr>
            <td><strong>${credito.numero_factura || 'N/A'}</strong></td>
            <td>${credito.cliente || 'Sin cliente'}</td>
            <td><strong>${formatearQuetzal(credito.monto_original)}</strong></td>
            <td>${formatearFecha(credito.fecha_credito)}</td>
            <td>
                <span class="badge bg-success">
                    <i class="fas fa-check"></i> Pagado
                </span>
            </td>
        </tr>
    `).join('');
}

function mostrarError(mensaje) {
    const tbody = document.getElementById('tablaCreditosPagados');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="mt-2">${mensaje}</p>
                </td>
            </tr>
        `;
    }
}

function mostrarErrorEnTabla(tbodyId, mensaje) {
    const tbody = document.getElementById(tbodyId);
    if (tbody) {
        const columnCount = tbody.closest('table').querySelectorAll('thead th').length;
        tbody.innerHTML = `
            <tr>
                <td colspan="${columnCount}" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p class="mt-2">${mensaje}</p>
                </td>
            </tr>
        `;
    }
}

function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const opciones = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Función para visualizar el comprobante
document.addEventListener('click', function (e) {
    if (e.target.closest('.ver-comprobante')) {
        const btn = e.target.closest('.ver-comprobante');
        
        // Verificar si estamos en la tabla de créditos pendientes o historial
        const tabla = btn.closest('table');
        const tablaId = tabla ? tabla.id : '';
        
        if (tablaId === 'tablaCreditosPendientes') {
            // Para créditos pendientes, mostrar vista previa
            const fila = btn.closest('tr');
            const factura = fila.querySelector('td:first-child').textContent.trim();
            
            console.log('Ver comprobante (vista previa) clickeado para factura:', factura);
            mostrarVistaPrevia(factura);
        } else {
            // Para historial de pagos, mostrar comprobante tradicional
            const archivo = btn.dataset.comprobante;
            console.log('Ver comprobante tradicional, archivo:', archivo);
            
            const nombreComp = String(archivo).split('/').pop();
            const ruta = `php/archivo.php?tipo=comprobante_subido&f=${encodeURIComponent(nombreComp)}`;
            const contenedor = document.getElementById('comprobante-container');
            const linkDescarga = document.getElementById('linkDescargaComprobante');

            if (!contenedor) {
                console.error('Elemento comprobante-container no encontrado');
                return;
            }

            contenedor.innerHTML = '';

            if (!archivo || archivo === 'null' || archivo === '') {
                contenedor.innerHTML = '<p class="text-center text-muted">No hay comprobante disponible.</p>';
                if (linkDescarga) {
                    linkDescarga.style.display = 'none';
                }
            } else {
                const extension = archivo.split('.').pop().toLowerCase();

                if (extension === 'pdf') {
                    contenedor.innerHTML = `
                        <iframe src="${ruta}" width="100%" height="600px" style="border: none;"></iframe>
                    `;
                } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
                    contenedor.innerHTML = `
                        <img src="${ruta}" alt="Comprobante de Pago" class="img-fluid" 
                             style="max-height: 600px; max-width: 100%;">
                    `;
                } else {
                    contenedor.innerHTML = `<p class="text-center text-warning">Tipo de archivo no soportado: ${extension}</p>`;
                }

                if (linkDescarga) {
                    linkDescarga.href = ruta;
                    linkDescarga.download = archivo;
                    linkDescarga.style.display = 'inline-block';
                }
            }

            const modal = new bootstrap.Modal(document.getElementById('modalVerComprobante'));
            modal.show();
        }
    }
});

// Event listener para botones de descargar PDF
document.addEventListener('click', function(e) {
    if (e.target.closest('.descargar-pdf')) {
        const btn = e.target.closest('.descargar-pdf');
        const factura = btn.dataset.factura;
        descargarComprobantePDF(factura);
    }
});

// Función para descargar el comprobante PDF
function descargarComprobantePDF(factura) {
    if (!factura) {
        alert('No se puede descargar el comprobante: factura no válida');
        return;
    }
    
    // Mostrar indicador de descarga
    const link = document.createElement('a');
    link.href = `php/abonos.php?accion=descargar_comprobante&factura=${encodeURIComponent(factura)}`;
    link.download = `comprobante_abono_${factura}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Función principal para mostrar vista previa del comprobante
function mostrarVistaPrevia(factura) {
    if (!factura || factura === 'N/A') {
        alert('No se puede mostrar la vista previa: factura no válida');
        return;
    }
    
    // Obtener los datos de la factura para generar la vista previa
    obtenerDatosFactura(factura)
        .then(datos => {
            if (datos) {
                generarVistaPrevia(datos);
            } else {
                mostrarErrorVistaPrevia('No se encontraron datos para esta factura');
            }
        })
        .catch(error => {
            console.error('Error al obtener datos de la factura:', error);
            mostrarErrorVistaPrevia('Error al cargar los datos de la factura');
        });
}

// Función para obtener los datos de la factura desde el servidor
function obtenerDatosFactura(factura) {
    return fetch(`php/abonos.php?accion=obtener_datos_factura&factura=${encodeURIComponent(factura)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.mensaje || 'Error desconocido');
            }
            return data;
        });
}

/**
 * Función CORREGIDA para generar la vista previa HTML del comprobante
 * Incluye manejo correcto de pagos anticipados y descuentos
 */
function generarVistaPrevia(datos) {
    const contenedor = document.getElementById('comprobante-container');
    
    if (!contenedor) {
        console.error('Elemento comprobante-container no encontrado');
        return;
    }
    
    // Calcular valores correctamente
    const montoTotal = parseFloat(datos.monto_total);
    const anticipo = parseFloat(datos.anticipo || 0);
    const montoPendienteInicial = parseFloat(datos.monto_pendiente_inicial || (montoTotal - anticipo));
    const totalAbonos = parseFloat(datos.total_abonos || 0);
    const totalDescuentos = parseFloat(datos.total_descuentos || 0);
    const saldoPendiente = parseFloat(datos.saldo_pendiente || 0);
    
    // Generar HTML de la vista previa CORREGIDA
    const vistaPrevia = `
        <div class="comprobante-preview" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); font-family: Arial, sans-serif;">
            <div class="text-center mb-4">
                <h3 style="color: #2c3e50; font-weight: bold; margin-bottom: 5px;">COMPROBANTE DE ABONOS</h3>
                <h4 style="color: #34495e; font-weight: bold;">FACTURA: ${datos.factura}</h4>
                <hr style="border-top: 2px solid #3498db; margin: 20px 0;">
            </div>
            
            <div class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong style="color: #2c3e50;">Cliente:</strong> ${datos.cliente}</p>
                        <p><strong style="color: #2c3e50;">Fecha de Generación:</strong> ${new Date().toLocaleDateString('es-GT')}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong style="color: #2c3e50;">Monto Total Factura:</strong> <span style="color: #27ae60; font-size: 1.1em;">${formatearQuetzal(montoTotal)}</span></p>
                        ${anticipo > 0 ? `<p><strong style="color: #2c3e50;">Pago Anticipado:</strong> <span style="color: #3498db; font-size: 1.0em;">${formatearQuetzal(anticipo)}</span></p>` : ''}
                        <p><strong style="color: #2c3e50;">Monto a Financiar:</strong> <span style="color: #f39c12; font-size: 1.0em;">${formatearQuetzal(montoPendienteInicial)}</span></p>
                        <p><strong style="color: #2c3e50;">Saldo Pendiente:</strong> <span style="color: #e74c3c; font-size: 1.1em;">${formatearQuetzal(saldoPendiente)}</span></p>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <h5 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px;">Historial de Abonos</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" style="font-size: 0.9em;">
                        <thead style="background-color: #3498db; color: white;">
                            <tr>
                                <th class="text-center">Fecha</th>
                                <th class="text-center">Monto Abono</th>
                                <th class="text-center">Descuento</th>
                                <th class="text-center">Saldo Restante</th>
                                <th class="text-center">Método</th>
                                <th class="text-center">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${generarFilasHistorialCorregido(datos.historial_pagos, montoPendienteInicial, anticipo)}
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div style="background-color: #ecf0f1; padding: 15px; border-radius: 5px;">
                        <h6 style="color: #2c3e50; margin-bottom: 10px;">Resumen de Pagos</h6>
                        ${anticipo > 0 ? `<p><strong>Pago Anticipado:</strong> <span style="color: #3498db;">${formatearQuetzal(anticipo)}</span></p>` : ''}
                        <p><strong>Total de Abonos:</strong> <span style="color: #27ae60;">${formatearQuetzal(totalAbonos)}</span></p>
                        ${totalDescuentos > 0 ? `<p><strong>Descuentos Aplicados:</strong> <span style="color: #e67e22;">${formatearQuetzal(totalDescuentos)}</span></p>` : ''}
                        <p><strong>Total Aplicado:</strong> <span style="color: #8e44ad;">${formatearQuetzal(anticipo + totalAbonos + totalDescuentos)}</span></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background-color: #fdf2e9; padding: 15px; border-radius: 5px;">
                        <h6 style="color: #2c3e50; margin-bottom: 10px;">Estado de la Cuenta</h6>
                        <p><strong>Estado:</strong> ${saldoPendiente > 0 ? '<span style="color: #e67e22;">Pendiente</span>' : '<span style="color: #27ae60;">Pagado</span>'}</p>
                        <p><strong>Días Transcurridos:</strong> ${calcularDiasTranscurridos(datos.fecha_venta)}</p>
                        <p><strong>Porcentaje Pagado:</strong> <span style="color: #3498db;">${calcularPorcentajePagadoCorregido(anticipo + totalAbonos + totalDescuentos, montoTotal)}%</span></p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4" style="border-top: 1px solid #bdc3c7; padding-top: 15px;">
                <small style="color: #7f8c8d;">
                    <i class="fas fa-clock"></i> Generado automáticamente el ${new Date().toLocaleString('es-GT')}
                </small>
            </div>
        </div>
    `;
    
    contenedor.innerHTML = vistaPrevia;
    
    // Ocultar el link de descarga ya que es una vista previa
    const linkDescarga = document.getElementById('linkDescargaComprobante');
    if (linkDescarga) {
        linkDescarga.style.display = 'none';
    }
    
    // Agregar botones para descargar PDF e imprimir
    const modalFooter = document.querySelector('#modalVerComprobante .modal-footer');
    if (modalFooter) {
        // Remover botones existentes si los hay
        const btnExistentePDF = modalFooter.querySelector('.btn-descargar-pdf-preview');
        const btnExistenteImprimir = modalFooter.querySelector('.btn-imprimir-historial');
        if (btnExistentePDF) btnExistentePDF.remove();
        if (btnExistenteImprimir) btnExistenteImprimir.remove();
        
        // Crear botón de impresión
        const btnImprimirHistorial = document.createElement('button');
        btnImprimirHistorial.className = 'btn btn-info btn-imprimir-historial';
        btnImprimirHistorial.innerHTML = '<i class="fas fa-print"></i> Imprimir Historial';
        btnImprimirHistorial.onclick = () => imprimirHistorialAbonos(datos.factura);
        
        // Crear botón de descarga PDF
        const btnDescargarPDF = document.createElement('button');
        btnDescargarPDF.className = 'btn btn-success btn-descargar-pdf-preview';
        btnDescargarPDF.innerHTML = '<i class="fas fa-file-pdf"></i> Descargar PDF Completo';
        btnDescargarPDF.onclick = () => descargarComprobantePDF(datos.factura);
        
        // Insertar ambos botones
        modalFooter.insertBefore(btnImprimirHistorial, modalFooter.firstChild);
        modalFooter.insertBefore(btnDescargarPDF, modalFooter.firstChild);
    }
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalVerComprobante'));
    modal.show();
}
/**
 * Función auxiliar CORREGIDA para generar las filas del historial con cálculos correctos
 */
function generarFilasHistorialCorregido(historial, montoPendienteInicial, anticipo = 0) {
    if (!Array.isArray(historial) || historial.length === 0) {
        return `
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-info-circle"></i> No hay pagos registrados aún
                </td>
            </tr>
        `;
    }
    
    let saldoTemp = montoPendienteInicial;
    let filas = '';
    
    // Si hay anticipo, mostrar una fila especial para el anticipo
    if (anticipo > 0) {
        filas += `
            <tr style="background-color: #e8f4fd;">
                <td class="text-center"><strong>ANTICIPO</strong></td>
                <td class="text-center" style="color: #3498db; font-weight: bold;">${formatearQuetzal(anticipo)}</td>
                <td class="text-center">Q0.00</td>
                <td class="text-center" style="color: #2c3e50; font-weight: bold;">${formatearQuetzal(saldoTemp)}</td>
                <td class="text-center">
                    <span class="badge" style="background-color: #3498db;">INICIAL</span>
                </td>
                <td class="text-center" style="font-size: 0.8em; font-style: italic;">Pago realizado al momento de la venta</td>
            </tr>
        `;
    }
    
    historial.forEach((pago, index) => {
        const montoPago = parseFloat(pago.monto);
        const descuento = parseFloat(pago.descuento || 0);
        const totalAplicado = montoPago + descuento;
        
        // Restar del saldo el monto + descuento
        saldoTemp -= totalAplicado;
        saldoTemp = Math.max(0, saldoTemp); // No puede ser negativo
        
        // Preparar observaciones combinadas
        let observacionesCompletas = [];
        if (pago.ObPago && pago.ObPago.trim()) {
            observacionesCompletas.push(pago.ObPago.trim());
        }
        if (pago.ob_descuento && pago.ob_descuento.trim()) {
            observacionesCompletas.push(`Desc: ${pago.ob_descuento.trim()}`);
        }
        if (pago.observaciones && pago.observaciones.trim()) {
            observacionesCompletas.push(pago.observaciones.trim());
        }
        
        const observacionesFinal = observacionesCompletas.join(' | ') || 'N/A';
        
        filas += `
            <tr>
                <td class="text-center">${formatearFecha(pago.fecha_pago)}</td>
                <td class="text-center" style="color: #27ae60; font-weight: bold;">${formatearQuetzal(montoPago)}</td>
                <td class="text-center" style="color: #e67e22; font-weight: bold;">${formatearQuetzal(descuento)}</td>
                <td class="text-center" style="color: ${saldoTemp <= 0 ? '#27ae60' : '#e74c3c'}; font-weight: bold;">${formatearQuetzal(saldoTemp)}</td>
                <td class="text-center">
                    <span class="badge" style="background-color: #3498db;">${pago.metodo_pago || 'N/A'}</span>
                </td>
                <td class="text-center" style="font-size: 0.8em;">${observacionesFinal}</td>
            </tr>
        `;
    });
    
    return filas;
}


// Función auxiliar para calcular porcentaje pagado
function calcularPorcentajePagado(totalAbonos, montoTotal) {
    if (!montoTotal || montoTotal === 0) return 0;
    return Math.round((totalAbonos / montoTotal) * 100);
}

// Función auxiliar para calcular días transcurridos
function calcularDiasTranscurridos(fechaVenta) {
    if (!fechaVenta) return 'N/A';
    
    const fecha1 = new Date(fechaVenta);
    const fecha2 = new Date();
    const diferencia = Math.abs(fecha2 - fecha1);
    const dias = Math.ceil(diferencia / (1000 * 60 * 60 * 24));
    
    return dias;
}

// Función para mostrar error en la vista previa
function mostrarErrorVistaPrevia(mensaje) {
    const contenedor = document.getElementById('comprobante-container');
    if (contenedor) {
        contenedor.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <h5>Error al cargar la vista previa</h5>
                <p class="text-muted">${mensaje}</p>
            </div>
        `;
    }
    
    // Mostrar el modal aunque haya error
    const modal = new bootstrap.Modal(document.getElementById('modalVerComprobante'));
    modal.show();
}

    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo = 'info') {
        const alerta = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertasReporte.innerHTML = alerta;
        
        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            const alertElement = alertasReporte.querySelector('.alert');
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }

    // Función para validar fechas
    function validarFechas() {
        const fechaInicio = document.getElementById('fechaInicio').value;
        const fechaFin = document.getElementById('fechaFin').value;
        
        if (fechaInicio && fechaFin) {
            if (new Date(fechaInicio) > new Date(fechaFin)) {
                mostrarAlerta('La fecha de inicio no puede ser mayor a la fecha fin', 'warning');
                return false;
            }
        }
        return true;
    }


    // Función para formatear fecha
    function formatearFecha(fecha) {
        const opciones = { 
            year: 'numeric', 
            month: '2-digit', 
            day: '2-digit' 
        };
        return new Date(fecha).toLocaleDateString('es-GT', opciones);
    }

    // Función para generar el reporte
    function generarReporte() {
     // Ahora formReporte SÍ está disponible aquí
    if (!formReporte || !(formReporte instanceof HTMLFormElement)) {
        console.error('El formulario de reporte (formReporteAbonos) no existe o no es válido.');
        return;
    }

    if (!formReporte || !(formReporte instanceof HTMLFormElement)) {
        console.error('formReporteAbonos no existe o no es un formulario válido');
        return;
    }

    if (!validarFechas()) return;

    const formData = new FormData(formReporte);
    const params = new URLSearchParams();

    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            params.append(key, value);
        }
    }

    btnGenerarReporte.disabled = true;
    btnGenerarReporte.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Generando PDF...';

    fetch(`abonos.php?accion=generar_reporte&${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(`Reporte generado exitosamente. ${data.total_registros} registros procesados.`, 'success');

                const nombreArchivo = data.pdf_url.split('/').pop();
                const linkDescarga = document.createElement('a');
                linkDescarga.href = `php/abonos.php?accion=descargar_reporte&archivo=${nombreArchivo}`;
                linkDescarga.download = nombreArchivo;
                document.body.appendChild(linkDescarga);
                linkDescarga.click();
                document.body.removeChild(linkDescarga);

                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalReporteAbonos'));
                    if (modal) modal.hide();
                }, 2000);
            } else {
                mostrarAlerta(data.mensaje || 'Error al generar reporte', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión al generar reporte', 'danger');
        })
        .finally(() => {
            btnGenerarReporte.disabled = false;
            btnGenerarReporte.innerHTML = '<i class="fas fa-file-pdf me-2"></i>Generar PDF';
        });
}

    // Validación en tiempo real de fechas
    document.getElementById('fechaInicio').addEventListener('change', validarFechas);
    document.getElementById('fechaFin').addEventListener('change', validarFechas);

// Limpiar el contenido del modal al cerrarlo
const modalElement = document.getElementById('modalVerComprobante');
if (modalElement) {
    modalElement.addEventListener('hidden.bs.modal', function () {
        const contenedor = document.getElementById('comprobante-container');
        if (contenedor) {
            contenedor.innerHTML = '';
        }
        
        // Limpiar botones agregados dinámicamente
        const modalFooter = document.querySelector('#modalVerComprobante .modal-footer');
        if (modalFooter) {
            const btnImprimir = modalFooter.querySelector('.btn-imprimir-historial');
            const btnDescargar = modalFooter.querySelector('.btn-descargar-pdf-preview');
            if (btnImprimir) btnImprimir.remove();
            if (btnDescargar) btnDescargar.remove();
        }
    });
}

// Función para imprimir solo el historial de abonos
function imprimirHistorialAbonos(factura) {
    // Obtener el contenido de la vista previa
    const contenedor = document.getElementById('comprobante-container');
    
    if (!contenedor) {
        alert('No se puede imprimir: contenido no encontrado');
        return;
    }
    
    const contenidoCompleto = contenedor.innerHTML;
    
    // Crear ventana de impresión
    const ventanaImpresion = window.open('', '_blank', 'width=800,height=600');
    
    if (!ventanaImpresion) {
        alert('No se pudo abrir la ventana de impresión. Verifique que no esté bloqueada por el navegador.');
        return;
    }
    
    ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Historial de Abonos - ${factura}</title>
            <meta charset="UTF-8">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                @media print {
                    .no-print { display: none !important; }
                    body { margin: 0; }
                    .comprobante-preview { 
                        box-shadow: none !important; 
                        margin: 0 !important;
                        padding: 20px !important;
                    }
                    @page {
                        margin: 0.5in;
                    }
                }
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                    background: white;
                }
                .comprobante-preview {
                    background: white !important;
                }
            </style>
        </head>
        <body>
            ${contenidoCompleto}
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                    
                    window.onafterprint = function() {
                        window.close();
                    }
                }
            </script>
        </body>
        </html>
    `);
    
    ventanaImpresion.document.close();
}
/**
 * Función auxiliar para calcular porcentaje pagado CORREGIDA
 */
function calcularPorcentajePagadoCorregido(totalAplicado, montoTotal) {
    if (!montoTotal || montoTotal === 0) return 0;
    return Math.round((totalAplicado / montoTotal) * 100);
}

