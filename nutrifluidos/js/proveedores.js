document.addEventListener('DOMContentLoaded', function () {
    const tbodyProveedores = document.getElementById('tbodyProveedores');
    // Modal de edición
    const modalEditar = document.getElementById('modalEditarProveedor');
    const modalEditarInstance = new bootstrap.Modal(modalEditar);
    initAuth('proveedores');

    
    // Evento para cargar datos al abrir el modal
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Botón que disparó el modal
        const id = button.getAttribute('data-id');
        cargarProveedor(id);
    });

    tbodyProveedores.addEventListener('click', function (e) {
        if (e.target.closest('button')?.classList.contains('btn-primary')) {
            const id = e.target.closest('button').dataset.id;
            cargarProveedor(id);
        }
    });
 

    cargarProveedores();

    function cargarProveedores() {
        // Destruir DataTable antes de manipular DOM
        if ($.fn.dataTable.isDataTable('#tablaProveedores')) {
            $('#tablaProveedores').DataTable().clear().destroy();
        }
    
        fetch('php/proveedores.php?accion=listar')
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
    
                renderizarTablaProveedores(data); // <- recién ahora se actualiza la tabla y el DOM
                inicializarDataTable(); // <- y aquí volvés a levantar DataTable
            })
            .catch(error => {
                mostrarAlerta('Error al cargar los proveedores', 'error');
            });
    }
    
    function renderizarTablaProveedores(proveedores) {
        const tbodyProveedores = document.getElementById('tbodyProveedores');
        tbodyProveedores.innerHTML = '';
    
        if (proveedores.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="9" class="text-center">No hay proveedores registrados</td>';
            tbodyProveedores.appendChild(fila);
        } else {
            proveedores.forEach(item => {
                const tr = document.createElement('tr');
                const estadoTexto = item.estado == 1 ? 'Activo' : 'Inactivo';
                const claseEstado = item.estado == 1 ? 'badge-active' : 'badge-inactivo';
    
                tr.innerHTML = `
                    <td>${item.empresa || ''}</td>
                    <td>${item.contacto || ''}</td>
                    <td>${item.telefono || ''}</td>
                    <td>${item.contacto2 || ''}</td>
                    <td>${item.telefono2 || ''}</td>
                    <td>${item.email || ''}</td>
                    <td>${item.direccion || ''}</td>
                    <td>${item.pais_origen || ''}</td>
                    <!-- ESTADO ESTA COMENTADO -->
                    <!--<td><span class="badge badge-status ${claseEstado}">${estadoTexto}</span></td> -->
                    <td>${item.fecha_registro || ''}</td>
                    <td>
                        <div class="action-buttons">
                             <button 
                            class="btn btn-sm rounded-circle btn-favorito ${item.favorito ? 'btn-warning' : 'btn-outline-secondary'}" 
                            data-id="${item.id}" 
                            data-favorito="${item.favorito ? 1 : 0}" 
                            title="${item.favorito ? 'Favorito actual' : 'Marcar como favorito'}">
                            <i class="${item.favorito ? 'fas' : 'far'} fa-star"></i>
                        </button>
                            <button class="btn btn-sm btn-primary rounded-circle" data-id="${item.id}" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarProveedor">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger rounded-circle" data-id="${item.id}" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminarProveedor">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
    
                tbodyProveedores.appendChild(tr);
            });
        }

    }

    function inicializarDataTable() {  
        let tabla = $('#tablaProveedores').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'  // Usar HTTPS
            },
            ordering: false,
            lengthChange: false,
            paging: false,
            info: false,
            dom: 't' // Solo muestra la tabla (oculta todos los controles)
            
        });
        // Ajustar la altura de las filas después de cada actualización de la tabla
    tabla.on('draw', function () {
        $('#tablaProveedores tbody tr').css('height', '68px'); // Ajusta este valor según tu necesidad
    });

    
    
        // Crear buscador personalizado
        let input = $('<input type="search">')
            .attr('placeholder', 'Buscar proveedor...')
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
    

    // Función para cargar datos del proveedor
    function cargarProveedor(id) {
        fetch(`php/proveedores.php?accion=obtener&id=${id}`)
            .then(res => {
                if (!res.ok) throw new Error('Error en la respuesta del servidor');
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    mostrarAlerta(data.error, 'error');
                    modalEditarInstance.hide();
                } else {
                    // Llenar el formulario
                    document.getElementById('editProveedorId').value = data.id;
                    document.getElementById('editProveedorEmpresa').value = data.empresa;
                    document.getElementById('editProveedorContacto').value = data.contacto;
                    document.getElementById('editProveedorTelefono').value = data.telefono;
                    document.getElementById('editProveedorEmail').value = data.email;
                    document.getElementById('editProveedorDireccion').value = data.direccion;
                    document.getElementById('editProveedorPais').value = data.pais_origen;
                    document.getElementById('editProveedorContacto2').value = data.contacto2;
                    document.getElementById('editProveedorTelefono2').value = data.telefono2;


                    //document.getElementById('editProveedorEstado').value = data.estado == 1 ? 'Activo' : 'Inactivo';
                    
                    // Enfocar el primer campo
                    setTimeout(() => {
                        document.getElementById('editProveedorEmpresa').focus();
                    }, 100);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error al cargar datos del proveedor', 'error');
                modalEditarInstance.hide();
            });
    }
    
    // Manejo del envío del formulario
    document.getElementById('formEditarProveedor').addEventListener('submit', function (e) {
        e.preventDefault();
        
        const btnGuardar = document.getElementById('btnGuardarCambios');
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
        
        const formData = new FormData(this);
        formData.append('accion', 'actualizar');

        // Verificar los datos antes de enviarlos
    //console.log("Datos enviados al servidor:", Array.from(formData.entries()));  // Muestra los datos que se van a enviar
    
        
        fetch('php/proveedores.php', {
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            return res.json();
        })
        .then(data => {
            if (data.error) {
                mostrarAlerta(data.error, 'error');
            } else {
                mostrarAlerta('Proveedor actualizado correctamente', 'success');
                cargarProveedores();
                modalEditarInstance.hide();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al actualizar proveedor', 'error');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = 'Guardar Cambios';
        });
    });
    

    // Capturar datos al hacer clic en el botón de eliminar
document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-danger[data-bs-target="#modalEliminarProveedor"]')) {
        const button = e.target.closest('button');
        const id = button.dataset.id;
        const fila = button.closest('tr');
        const nombre = fila.querySelector('td').textContent;

        document.getElementById('idProveedorEliminar').value = id;
        document.getElementById('nombreProveedorEliminar').textContent = nombre;
    }
});

// Confirmar eliminación
document.getElementById('btnConfirmarEliminarProveedor').addEventListener('click', function () {
    const idProveedor = document.getElementById('idProveedorEliminar').value;

    fetch('php/proveedores.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `accion=eliminar&id=${encodeURIComponent(idProveedor)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarProveedor'));
            modal.hide();
            mostrarAlerta('Proveedor eliminado correctamente', 'success');
            cargarProveedores(); // vuelve a llamar a tu función que lista proveedores
        } else {
            mostrarAlerta('Error al eliminar el proveedor', 'danger');
        }
    })
    .catch(() => {
        mostrarAlerta('Error al eliminar el proveedor', 'danger');
    });
});



    document.getElementById('guardarProveedor').addEventListener('click', guardarProveedor);

function guardarProveedor(e) {
    e.preventDefault();

    const id = document.getElementById('proveedorId').value;
    const empresa = document.getElementById('empresaProveedor').value.trim().toUpperCase();
    const contacto = document.getElementById('nombreProveedor').value.trim().toUpperCase();
    const telefono = document.getElementById('telefonoProveedor').value.trim();
    const email = document.getElementById('correoProveedor').value.trim().toLowerCase();
    const direccion = document.getElementById('direccionProveedor').value.trim().toUpperCase();
    const pais_origen = document.getElementById('ciudadOrigen').value.trim().toUpperCase();

    const contacto2 = document.getElementById('nombreProveedor2').value.trim().toUpperCase();
    const telefono2 = document.getElementById('telefonoProveedor2').value.trim();

    // Validar solo los campos obligatorios
    if (!empresa || !contacto || !telefono || !email || !direccion || !pais_origen) {
        mostrarAlerta('Todos los campos obligatorios deben completarse', 'danger');
        return;
    }

    const formData = new FormData();
    formData.append('empresa', empresa);
    formData.append('contacto', contacto);
    formData.append('telefono', telefono);
    formData.append('email', email);
    formData.append('direccion', direccion);
    formData.append('pais_origen', pais_origen);
    formData.append('contacto2', contacto2);
    formData.append('telefono2', telefono2);

    if (id) {
        formData.append('id', id);
        formData.append('accion', 'actualizar');
    } else {
        formData.append('accion', 'crear');
    }

    fetch('php/proveedores.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            mostrarAlerta(data.error, 'error');
            return;
        }

        // ✅ Cierra el modal correctamente
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalProveedor'));
        if (modal) modal.hide();

        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();

        document.body.classList.remove('modal-open');
        document.body.style = '';

        mostrarAlerta('Proveedor guardado correctamente', 'success');
        cargarProveedores();
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarAlerta('Error al guardar el proveedor', 'danger');
    });
}


// Limpiar formulario cuando se cierre el modal
const modalProveedor = document.getElementById('modalProveedor');
modalProveedor.addEventListener('hidden.bs.modal', function () {
    limpiarFormularioProveedor(); // Limpiar los campos al cerrar el modal
});

// Limpiar los campos del formulario
function limpiarFormularioProveedor() {
    document.getElementById('proveedorId').value = '';
    document.getElementById('empresaProveedor').value = '';
    document.getElementById('nombreProveedor').value = '';
    document.getElementById('telefonoProveedor').value = '';
    document.getElementById('correoProveedor').value = '';
    document.getElementById('direccionProveedor').value = '';
    document.getElementById('ciudadOrigen').value = '';
    document.getElementById('nombreProveedor2').value = '';
    document.getElementById('telefonoProveedor2').value = '';
}

    function mostrarAlerta(mensaje, tipo = 'success') {
        // Eliminar alerta anterior si existe
        const alertaExistente = document.querySelector('.alert');
        if (alertaExistente) {
            alertaExistente.remove();
        }
    
        // Crear contenedor de la alerta
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show mb-3`;
        alerta.setAttribute('role', 'alert');
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
    
        // Insertar la alerta justo debajo del body o del header principal
        const contenedor = document.querySelector('.main-content') || document.body;
        contenedor.prepend(alerta);
    
        // Desaparecer automáticamente en 5 segundos
        setTimeout(() => {
            const alertaBootstrap = bootstrap.Alert.getOrCreateInstance(alerta);
            alertaBootstrap.close();
        }, 5000);
    }
    

    document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-favorito');
    if (!btn) return;

    const proveedorId = btn.getAttribute('data-id');
    const esFavorito = btn.getAttribute('data-favorito') === '1';

    const mensaje = esFavorito
        ? '¿Deseas quitar este proveedor de tus favoritos?'
        : '¿Quieres marcar este proveedor como favorito?';

    if (!confirm(mensaje)) return;

    fetch('php/proveedores.php', {
        method: 'POST',
        body: new URLSearchParams({
            accion: 'actualizarFavorito',
            id: proveedorId,
            favorito: esFavorito ? 0 : 1
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
             mostrarAlerta(data.success, 'success');
             cargarProveedores();
            /*
            // 🔄 Actualizar apariencia del botón sin recargar toda la tabla
            btn.setAttribute('data-favorito', esFavorito ? '0' : '1');
            btn.classList.toggle('btn-warning', !esFavorito);
            btn.classList.toggle('btn-outline-secondary', esFavorito);

            const icono = btn.querySelector('i');
            icono.classList.toggle('fas', !esFavorito); // estrella llena
            icono.classList.toggle('far', esFavorito);  // estrella vacía

            btn.setAttribute('title', esFavorito ? 'Marcar como favorito' : 'Favorito actual');*/
        } else {
            mostrarAlerta(data.error || 'Error al actualizar favorito', 'danger');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        mostrarAlerta('Error de red', 'danger');
    });
});







    
});
