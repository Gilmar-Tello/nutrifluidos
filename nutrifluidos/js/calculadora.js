// Variables globales para el modal de zoom
let archivoActualZoom = '';
let fechaActualZoom = '';

// Función para mostrar alertas (que faltaba en tu código)
function mostrarAlerta(mensaje, tipo) {
    if (typeof Swal !== 'undefined') {
        const iconType = tipo === 'error' ? 'error' : 'info';
        Swal.fire({
            icon: iconType,
            title: tipo === 'error' ? 'Error' : 'Información',
            text: mensaje
        });
    } else {
        alert(mensaje);
    }
}

// Función para abrir el modal de zoom
function abrirModalZoom(nombreArchivo, fechaSubida) {
    archivoActualZoom = nombreArchivo;
    fechaActualZoom = fechaSubida;
    
    // Actualizar información del archivo
    document.getElementById('nombreArchivo').textContent = nombreArchivo;
    document.getElementById('fechaArchivo').textContent = new Date(fechaSubida).toLocaleDateString('es-GT');
    
    // Determinar tipo de archivo
    const extension = nombreArchivo.split('.').pop().toLowerCase();
    const rutaArchivo = `php/archivo.php?tipo=abono_prov&f=${encodeURIComponent(nombreArchivo)}`;
    
    // Mostrar spinner de carga
    const contenedorZoom = document.getElementById('contenedorZoom');
    contenedorZoom.innerHTML = `
        <div class="text-center py-5">
            <div class="loading-spinner"></div>
            <p class="text-white mt-3">Cargando comprobante...</p>
        </div>
    `;
    
    // Actualizar tipo de archivo
    let tipoArchivo = '';
    switch(extension) {
        case 'pdf':
            tipoArchivo = 'Documento PDF';
            break;
        case 'jpg':
        case 'jpeg':
            tipoArchivo = 'Imagen JPEG';
            break;
        case 'png':
            tipoArchivo = 'Imagen PNG';
            break;
        case 'webp':
            tipoArchivo = 'Imagen WebP';
            break;
        default:
            tipoArchivo = 'Archivo ' + extension.toUpperCase();
    }
    document.getElementById('tipoArchivo').textContent = tipoArchivo;
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('modalZoomComprobante'));
    modal.show();
    
    // Cargar contenido después de mostrar el modal
    setTimeout(() => {
        cargarContenidoZoom(rutaArchivo, extension);
    }, 300);
}

// Función para cargar el contenido en el modal de zoom
function cargarContenidoZoom(rutaArchivo, extension) {
    const contenedorZoom = document.getElementById('contenedorZoom');
    
    if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(extension)) {
        // Es una imagen
        const img = new Image();
        
        img.onload = function() {
            contenedorZoom.innerHTML = `
                <img src="${rutaArchivo}" 
                     alt="Comprobante" 
                     class="img-fluid"
                     onclick="toggleZoomImagen(this)"
                     title="Clic para ampliar/reducir">
            `;
        };
        
        img.onerror = function() {
            mostrarErrorCarga();
        };
        
        img.src = rutaArchivo;
        
    } else if (extension === 'pdf') {
        // Es un PDF
        contenedorZoom.innerHTML = `
            <embed src="${rutaArchivo}" 
                   type="application/pdf" 
                   width="100%" 
                   height="70vh"
                   title="Comprobante PDF">
        `;
        
    } else {
        // Archivo no compatible para vista previa
        contenedorZoom.innerHTML = `
            <div class="archivo-no-compatible">
                <i class="fas fa-file-alt"></i>
                <h4 class="text-white">Vista previa no disponible</h4>
                <p class="text-muted">
                    Este tipo de archivo (${extension.toUpperCase()}) no se puede previsualizar.<br>
                    Puedes descargarlo para verlo en tu dispositivo.
                </p>
                <button class="btn btn-primary mt-3" onclick="descargarComprobante()">
                    <i class="fas fa-download me-2"></i>Descargar Archivo
                </button>
            </div>
        `;
    }
}

// Función para mostrar error de carga
function mostrarErrorCarga() {
    const contenedorZoom = document.getElementById('contenedorZoom');
    contenedorZoom.innerHTML = `
        <div class="archivo-no-compatible">
            <i class="fas fa-exclamation-triangle"></i>
            <h4 class="text-white">Error al cargar el archivo</h4>
            <p class="text-muted">
                No se pudo cargar el comprobante.<br>
                El archivo puede estar dañado o no existir.
            </p>
            <button class="btn btn-warning mt-3" onclick="abrirEnNuevaVentana()">
                <i class="fas fa-external-link-alt me-2"></i>Intentar abrir en nueva ventana
            </button>
        </div>
    `;
}

// Función para hacer zoom en imágenes
function toggleZoomImagen(img) {
    img.classList.toggle('zoomed');
}

// Función para descargar el comprobante
function descargarComprobante() {
    if (archivoActualZoom) {
        const link = document.createElement('a');
        link.href = `php/archivo.php?tipo=abono_prov&f=${encodeURIComponent(archivoActualZoom)}`;
        link.download = archivoActualZoom;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Función para abrir en nueva ventana
function abrirEnNuevaVentana() {
    if (archivoActualZoom) {
        window.open(`php/archivo.php?tipo=abono_prov&f=${encodeURIComponent(archivoActualZoom)}`, '_blank');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión y permisos requeridos para abonos (solo admin)
    initAuth('usuarios');

   // Función Guardar
document.getElementById('guardarFactura').addEventListener('click', function () { 
    const form = document.getElementById('formFactura');
    const formData = new FormData(form);

    // Obtener valores
    const proveedor = formData.get('proveedor_id');
    const numeroFactura = formData.get('numeroFactura');
    const montoTotal = parseFloat(formData.get('montoTotal'));
    const pagado = parseFloat(formData.get('pagado'));
    const fechaEmision = formData.get('fechaEmision');
    const facturaArchivo = form.querySelector('#factura').files[0]; // Archivo cargado

    // Validar campos obligatorios
    if (!proveedor || !numeroFactura || isNaN(montoTotal) || isNaN(pagado) || !fechaEmision) {
        alert('Por favor, complete todos los campos obligatorios.');
        return;
    }

    // Validar montos no negativos
    if (montoTotal < 0) {
        alert('El monto total no puede ser negativo.');
        return;
    }
    if (pagado < 0) {
        alert('El monto pagado no puede ser negativo.');
        return;
    }

    if (pagado > montoTotal) {
    alert('El monto pagado no puede ser mayor al monto total.');
    return;
}

    // Validar que haya un archivo cargado
    if (!facturaArchivo) {
        alert('Debe adjuntar un archivo de la factura.');
        return;
    }

    // 👉 Acción para el backend
    formData.append('accion', 'crear');
    formData.set('numeroFactura', formData.get('numeroFactura').toUpperCase());

    fetch('php/facturas_pagar.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Factura guardada exitosamente.');
            form.reset();
            document.getElementById('previewContainer').innerHTML = '';
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFactura'));
            modal.hide();
            cargarFacturas();
            // Aquí podrías recargar la tabla si usas DataTable u otro método
        } else {
            alert('Error: ' + (data.message || 'No se pudo guardar la factura.'));
        }
    })
    .catch(error => {
        console.error('Error en el envío:', error);
        alert('Ocurrió un error al guardar la factura.');
    });
});

cargarFacturas();

// FUNCIÓN CORREGIDA: cargarFacturas
function cargarFacturas() {
    // Destruir DataTable antes de manipular DOM
    if ($.fn.dataTable.isDataTable('#tablaFacturas')) {
        $('#tablaFacturas').DataTable().clear().destroy();
    }

    fetch('php/facturas_pagar.php?accion=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('Respuesta del servidor no fue exitosa');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                mostrarAlerta(data.error, 'error');
                return;
            }

            // PRIMERO renderizar la tabla en el DOM
            renderizarTablaFacturas(data);
            
            // DESPUÉS inicializar DataTable solo si hay datos
            if (data.length > 0) {
                inicializarDataTable();
            } else {
                // Si no hay datos, limpiar el buscador personalizado
                $('#customSearchContainer').empty();
            }
        })
        .catch(error => {
            console.error('Error al cargar las facturas:', error);
            mostrarAlerta('Error al cargar las facturas', 'error');
            
            // En caso de error, mostrar mensaje en la tabla
            const tbodyFacturas = document.getElementById('tbodyFacturas');
            tbodyFacturas.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos</td></tr>';
        });
}

// FUNCIÓN CORREGIDA: renderizarTablaFacturas
function renderizarTablaFacturas(facturas) {
    const tbodyFacturas = document.getElementById('tbodyFacturas');
    tbodyFacturas.innerHTML = '';

    if (facturas.length === 0) {
        const fila = document.createElement('tr');
        fila.innerHTML = '<td colspan="7" class="text-center text-muted">No hay facturas registradas</td>';
        tbodyFacturas.appendChild(fila);
    } else {
        facturas.forEach(item => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${item.numero_factura || ''}</td>
                <td>${item.proveedor || ''}</td>
                <td>Q ${parseFloat(item.monto_total).toFixed(2)}</td>
                <td>Q ${parseFloat(item.pagado).toFixed(2)}</td>
                <td>Q ${parseFloat(item.saldo_pendiente).toFixed(2)}</td>
                <td>${item.fecha_emision || ''}</td>
                <td>
                   <div class="action-buttons">
                        <button class="btn btn-sm btn-info rounded-circle btn-ver-factura" data-archivo="${item.archivo}" title="Ver factura">
                            <i class="fas fa-file-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-success rounded-circle btn-abonar" 
                                data-id="${item.id}" 
                                data-proveedor="${item.proveedor}" 
                                data-numero="${item.numero_factura}" 
                                data-saldo="${item.saldo_pendiente}" 
                                title="Abonar a factura" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalAbono">
                            <i class="fas fa-dollar-sign"></i>
                        </button>
                        <button class="btn btn-sm btn-warning rounded-circle btn-ver-comprobantes"
                                data-id="${item.id}" 
                                data-numero="${item.numero_factura}" 
                                title="Ver comprobantes"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalComprobantes">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
            `;
            tbodyFacturas.appendChild(tr);
        });
    }
}
 
document.addEventListener('click', function (e) {
    const btnVer = e.target.closest('.btn-ver-factura');
    if (btnVer) {
        const archivo = btnVer.getAttribute('data-archivo');
        if (!archivo) {
            alert('No hay archivo asociado a esta factura.');
            return;
        }

        // Ruta pública completa (ajusta si cambias el nombre del proyecto)
        const ruta = `php/archivo.php?tipo=factura_prov&f=${encodeURIComponent(archivo)}`;
        const extension = archivo.split('.').pop().toLowerCase();

        // Abrir imagen o PDF en una nueva pestaña
        if (['pdf', 'jpg', 'jpeg', 'png', 'webp'].includes(extension)) {
            window.open(ruta, '_blank');
        } else {
            alert('Formato de archivo no compatible para previsualización.');
        }
    }
});

document.addEventListener('click', function (e) {
    const btnAbono = e.target.closest('.btn-abonar');
    if (!btnAbono) return;

    // Rellenar el modal con los datos
    document.getElementById('abonoFacturaId').value = btnAbono.getAttribute('data-id');
    document.getElementById('abonoProveedor').value = btnAbono.getAttribute('data-proveedor');
    document.getElementById('abonoNumero').value = btnAbono.getAttribute('data-numero');
    document.getElementById('abonoSaldo').value = btnAbono.getAttribute('data-saldo');
});

//funcion de abonar
document.getElementById('formAbono').addEventListener('submit', function (e) {
    e.preventDefault();
    const form = e.target;
     const saldo = parseFloat(document.getElementById('abonoSaldo').value);
    const monto = parseFloat(form.monto_abono.value);
    if (monto > saldo) {
        alert('El monto del abono no puede ser mayor al saldo pendiente.');
        return;
    }

    const formData = new FormData(form);
    formData.append('accion', 'abonar');
   
    fetch('php/facturas_pagar.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Abono registrado correctamente.');
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('modalAbono')).hide();
            cargarFacturas();
            // refrescar tabla si necesario
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Ocurrió un error al guardar el abono.');
    });
});

//funcion de mostrar abonos - MODIFICADA PARA INCLUIR MODAL DE ZOOM
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-ver-comprobantes');
    if (!btn) return;

    const facturaId = btn.getAttribute('data-id');
    const numeroFactura = btn.getAttribute('data-numero');
    document.getElementById('numeroFacturaTitulo').textContent = numeroFactura;

    fetch(`php/facturas_pagar.php?accion=ver_comprobantes&factura_id=${facturaId}`)
        .then(res => res.json())
        .then(data => {
            const contenedor = document.getElementById('contenedorComprobantes');
            contenedor.innerHTML = '';

            if (data.length === 0) {
                contenedor.innerHTML = '<div class="col-12 text-center text-muted">No hay comprobantes disponibles.</div>';
                return;
            }

            data.forEach(comp => {
                const ext = comp.comprobante.split('.').pop().toLowerCase();
                let vista = '';

                if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
                    vista = `
                        <img src="php/archivo.php?tipo=abono_prov&f=${encodeURIComponent(comp.comprobante)}" 
                             class="img-fluid rounded shadow comprobante-imagen" 
                             style="max-height: 200px; cursor: pointer;" 
                             onclick="abrirModalZoom('${comp.comprobante}', '${comp.fecha_subida}')"
                             title="Clic para ampliar">
                    `;
                } else if (ext === 'pdf') {
                    vista = `
                        <div class="pdf-preview text-center" 
                             style="cursor: pointer; padding: 20px; border: 2px dashed #ccc; border-radius: 8px;"
                             onclick="abrirModalZoom('${comp.comprobante}', '${comp.fecha_subida}')">
                            <i class="fas fa-file-pdf text-danger" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">PDF - Clic para ver</p>
                        </div>
                    `;
                } else {
                    vista = `
                        <div class="file-preview text-center" 
                             style="cursor: pointer; padding: 20px; border: 2px dashed #ccc; border-radius: 8px;"
                             onclick="abrirModalZoom('${comp.comprobante}', '${comp.fecha_subida}')">
                            <i class="fas fa-file text-secondary" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0">${ext.toUpperCase()} - Clic para ver</p>
                        </div>
                    `;
                }

                const card = `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        ${new Date(comp.fecha_subida).toLocaleDateString('es-GT')}
                                    </small>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="abrirModalZoom('${comp.comprobante}', '${comp.fecha_subida}')"
                                            title="Ver en pantalla completa">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                                <div class="text-center">
                                    ${vista}
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted d-block text-truncate" title="${comp.comprobante}">
                                        <i class="fas fa-file-alt me-1"></i>
                                        ${comp.comprobante}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                contenedor.insertAdjacentHTML('beforeend', card);
            });
        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar comprobantes.');
        });
});

//funcion de editar facturas
// Ejemplo de carga al hacer clic en botón editar
// Abrir el modal con datos de la factura
document.addEventListener('click', function (e) {
  const btnEditar = e.target.closest('.btn-editar-factura');
  if (!btnEditar) return;

  const id = btnEditar.getAttribute('data-id');
  const proveedor = btnEditar.getAttribute('data-proveedor');
  const numero = btnEditar.getAttribute('data-numero');
  const fecha = btnEditar.getAttribute('data-fecha');
  const monto = parseFloat(btnEditar.getAttribute('data-monto'));
  const pagado = parseFloat(btnEditar.getAttribute('data-pagado'));

  document.getElementById('editFacturaId').value = id;
  document.getElementById('editProveedor').value = proveedor;
  document.getElementById('editNumeroFactura').value = numero;
  document.getElementById('editFechaEmision').value = fecha;
  document.getElementById('editMontoTotal').value = monto;
  

  // Si ya hay abonos, desactivar campo de monto
  if (pagado > 0) {
    document.getElementById('editMontoTotal').disabled = true;
    document.getElementById('alertaMonto').classList.remove('d-none');
  } else {
    document.getElementById('editMontoTotal').disabled = false;
    document.getElementById('alertaMonto').classList.add('d-none');
  }

  const modal = new bootstrap.Modal(document.getElementById('modalEditarFactura'));
  modal.show();
});

// Enviar formulario de edición
document.getElementById('formEditarFactura').addEventListener('submit', function (e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  formData.append('accion', 'editarFactura');

  fetch('php/facturas_pagar.php', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Factura editada correctamente.');
        form.reset();
        bootstrap.Modal.getInstance(document.getElementById('modalEditarFactura')).hide();
        // Puedes recargar o actualizar la tabla si lo necesitas
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error al editar la factura.');
    });
});

// FUNCIÓN CORREGIDA: inicializarDataTable
function inicializarDataTable() {  
    let tabla = $('#tablaFacturas').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        ordering: false,
        lengthChange: false,
        paging: false,
        info: false,
        dom: 't', // Solo muestra la tabla
        // Evitar errores con tablas dinámicas
        destroy: true,
        responsive: true
    });
    
    // Ajustar la altura de las filas después de cada actualización de la tabla
    tabla.on('draw', function () {
        $('#tablaFacturas tbody tr').css('height', '68px');
    });
    
    // Crear buscador personalizado
    let input = $('<input type="search">')
        .attr('placeholder', 'Buscar facturas...')
        .addClass('form-control')
        .css({
            'width': '100%',
            'max-width': '400px',
            'height': '38px',
            'padding': '6px 10px',
            'font-size': '1rem'
        })
        .on('keyup', function() {
            tabla.search(this.value).draw();
        });

    let label = $('<label>').text('Buscar:').css({
        'margin-right': '10px',
        'font-weight': '500',
        'margin-left': '0'
    });

    let customSearch = $('<div>')
        .addClass('d-flex align-items-center gap-2')
        .css({
            'margin-top': '0',
            'margin-bottom': '10px',
            'justify-content': 'flex-start'
        })
        .append(label)
        .append(input);

    $('#customSearchContainer').empty().append(customSearch);
}

$('#modalFactura').on('show.bs.modal', function () {
            cargarProveedores('proveedor');
        });

    function cargarProveedores(selectId) {
            $.ajax({
                url: 'php/proveedores.php',
                method: 'GET',
                data: { accion: 'listar' },
                dataType: 'json',
                success: function (data) {
                    const $select = $('#' + selectId);
                    $select.empty();
                    $select.append('<option value="">Seleccione un proveedor</option>');
                    data.forEach(function (proveedor) {
                        $select.append(`<option value="${proveedor.id}">${proveedor.empresa}</option>`);
                    });
                },
                error: function () {
                    alert('Error al cargar los proveedores');
                }
            });
        }

        document.getElementById('factura').addEventListener('change', function (event) {
    const archivo = event.target.files[0];
    const previewContainer = document.getElementById('previewContainer');
    previewContainer.innerHTML = ''; // Limpiar preview anterior

    if (!archivo) return;

    const tipoArchivo = archivo.type;

    if (tipoArchivo.startsWith('image/')) {
        // Es una imagen
        const imgPreview = document.createElement('img');
        imgPreview.src = URL.createObjectURL(archivo);
        imgPreview.classList.add('img-thumbnail', 'mt-2');
        imgPreview.style.maxWidth = '200px';
        imgPreview.onload = () => URL.revokeObjectURL(imgPreview.src);
        previewContainer.appendChild(imgPreview);
    } else if (tipoArchivo === 'application/pdf') {
        // Es un PDF
        const icon = document.createElement('div');
        icon.innerHTML = `
            <div class="d-flex align-items-center mt-2">
                <i class="bi bi-file-earmark-pdf-fill text-danger fs-3 me-2"></i>
                <span>${archivo.name}</span>
            </div>
        `;
        previewContainer.appendChild(icon);
    } else {
        // Otro tipo de archivo
        previewContainer.textContent = `Archivo seleccionado: ${archivo.name}`;
    }
});

});

// Cerrar zoom al hacer clic fuera de la imagen (opcional)
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('zoomed') && !e.target.contains(e.relatedTarget)) {
        e.target.classList.remove('zoomed');
    }
});