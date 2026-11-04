document.addEventListener('DOMContentLoaded', function () {

    // Verificar sesión y permisos requeridos para ventas
    initAuth('usuarios');

    const tbodyUsuarios = document.getElementById('tbodyUsuarios');
    const modalEditar = document.getElementById('modalEditarUsuario');
    const modalEditarInstance = new bootstrap.Modal(modalEditar);

    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        cargarUsuario(id);
    });

    tbodyUsuarios.addEventListener('click', function (e) {
        if (e.target.closest('button')?.classList.contains('btn-primary')) {
            const id = e.target.closest('button').dataset.id;
            cargarUsuario(id);
        }
    });

    cargarUsuarios();

    function cargarUsuarios() {
        if ($.fn.dataTable.isDataTable('#tablaUsuarios')) {
            $('#tablaUsuarios').DataTable().clear().destroy();
        }

        fetch('php/usuarios.php?accion=listar')
            .then(response => {
                if (!response.ok) throw new Error('Respuesta del servidor no fue exitosa');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    mostrarAlerta(data.error, 'error');
                    return;
                }
                renderizarTablaUsuarios(data);
                inicializarDataTable();
            })
            .catch(() => {
                mostrarAlerta('Error al cargar los usuarios', 'error');
            });
    }

    function renderizarTablaUsuarios(usuarios) {
        tbodyUsuarios.innerHTML = '';
    
        if (usuarios.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="10" class="text-center">No hay usuarios registrados</td>';
            tbodyUsuarios.appendChild(fila);
        } else {
            usuarios.forEach(item => {
                const estadoTexto = item.estado === 'activo' ? 'Activo' : 'Inactivo';
                const claseEstado = item.estado === 'activo' ? 'badge-active' : 'badge-inactivo';
    
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="d-none">${item.id || ''}</td> 
                    <td>${item.nombre_completo || ''}</td>
                    <td>${item.telefono || ''}</td>
                    <td>${item.correo_electronico || ''}</td>
                    <td>${item.direccion || ''}</td>
                    <td>${item.usuario || ''}</td>
                    <td id="pass-${item.id}">••••••••</td> <!-- Contraseña cifrada: no se puede ver -->
                    <td>
                        <span class="text-muted" title="Las contraseñas están cifradas. Para cambiarla, edite el usuario.">
                            <i class="fas fa-lock"></i>
                        </span>
                    </td>
                    <td>${item.rol || ''}</td>
                   
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary rounded-circle" data-id="${item.id}" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarUsuario">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger rounded-circle" data-id="${item.id}" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminarUsuario">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
                tbodyUsuarios.appendChild(tr);
    
                // SEGURIDAD: se eliminó "ver contraseña" (ahora se guardan cifradas)
            });
        }
    }

    function inicializarDataTable() {
        let tabla = $('#tablaUsuarios').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            ordering: false,
            lengthChange: false,
            paging: false,
            info: false,
            dom: 't'
        });

        tabla.on('draw', function () {
            $('#tablaUsuarios tbody tr').css('height', '68px');
        });

        let input = $('<input type="search">')
            .attr('placeholder', 'Buscar usuario...')
            .addClass('form-control')
            .css({
                'width': '100%',
                'max-width': '400px',
                'height': '38px',
                'padding': '6px 10px',
                'font-size': '1rem'
            })
            .on('keyup', function () {
                tabla.search(this.value).draw();
            });

        let label = $('<label>').text('Buscar:').css({
            'margin-right': '10px',
            'font-weight': '500'
        });

        let customSearch = $('<div>')
            .addClass('d-flex align-items-center gap-2')
            .css({
                'margin-bottom': '10px'
            })
            .append(label)
            .append(input);

        $('#customSearchContainer').empty().append(customSearch);
    }

//Cargar Datos en la tabla Usuarios
    function cargarUsuario(id) {
        fetch(`php/usuarios.php?accion=obtener&id=${id}`)
            .then(res => {
                if (!res.ok) throw new Error('Error en la respuesta del servidor');
                return res.json();
            })
            .then(data => {
                if (data.error) {
                    mostrarAlerta(data.error, 'error');
                    modalEditarInstance.hide();
                } else {
                    document.getElementById('editUsuarioId').value = data.id;
                    document.getElementById('editUsuarioNombreCompleto').value = data.nombre_completo;
                    document.getElementById('editUsuarioTelefono').value = data.telefono;
                    document.getElementById('editUsuarioCorreo').value = data.correo_electronico;
                    document.getElementById('editUsuarioDireccion').value = data.direccion;
                    document.getElementById('editUsuarioUsuario').value = data.usuario;
                    document.getElementById('editUsuarioContrasena').value = '';  // No mostramos la contraseña
                    document.getElementById('editUsuarioRol').value = data.rol ? data.rol.toLowerCase() : '';
                    document.getElementById('editUsuarioEstado').value = data.estado === 0 ? 'activo' : (data.estado === 1 ? 'inactivo' : '');
                

                    setTimeout(() => {
                        document.getElementById('editUsuarioNombreCompleto').focus();
                    }, 100);
                }
            })
            .catch(() => {
                mostrarAlerta('Error al cargar datos del usuario', 'error');
                modalEditarInstance.hide();
            });
    }

    //Editar Usuario
    document.getElementById('formEditarUsuario').addEventListener('submit', function (e) {
        e.preventDefault();
        document.getElementById('btnGuardarCambiosUsuario').click();
    });

    document.getElementById('btnGuardarCambiosUsuario').addEventListener('click', function() {
        const btnGuardar = this;
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        const formData = new FormData();
        formData.append('accion', 'actualizar');
        formData.append('id', document.getElementById('editUsuarioId').value);
        formData.append('nombre_completo', document.getElementById('editUsuarioNombreCompleto').value);
        formData.append('telefono', document.getElementById('editUsuarioTelefono').value);
        formData.append('correo_electronico', document.getElementById('editUsuarioCorreo').value);
        formData.append('direccion', document.getElementById('editUsuarioDireccion').value);
        formData.append('usuario', document.getElementById('editUsuarioUsuario').value);
        
        
        // Solo enviar contraseña si se ingresó una nueva
        const newPassword = document.getElementById('editUsuarioContrasena').value;
        if (newPassword) {
            formData.append('contrasena', newPassword);
        }
        
        formData.append('rol', document.getElementById('editUsuarioRol').value);
        formData.append('estado', document.getElementById('editUsuarioEstado').value);

        fetch('php/usuarios.php', {
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
                    mostrarAlerta('Usuario actualizado correctamente', 'success');
                    cargarUsuarios();
                    modalEditarInstance.hide();
                }
            })
            .catch(() => {
                mostrarAlerta('Error al actualizar usuario', 'error');
            })
            .finally(() => {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = 'Guardar Cambios';
            });
    });
togglePassword('togglePassword', 'editUsuarioContrasena', 'iconoOjo'); // editar usuario

//Eliminar Usuario
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-danger[data-bs-target="#modalEliminarUsuario"]')) {
            const button = e.target.closest('button');
            const id = button.dataset.id;
            const fila = button.closest('tr');
            const nombre = fila.querySelectorAll('td')[1]?.textContent || '';  // Nombre Completo está en la 2da columna

            document.getElementById('idUsuarioEliminar').value = id;
            document.getElementById('nombreUsuarioEliminar').textContent = nombre;
        }
    });

    document.getElementById('btnConfirmarEliminarUsuario').addEventListener('click', function () {
        const idUsuario = document.getElementById('idUsuarioEliminar').value;

        fetch('php/usuarios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `accion=eliminar&id=${encodeURIComponent(idUsuario)}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarUsuario'));
                    modal.hide();
                    mostrarAlerta('Usuario eliminado correctamente', 'success');
                    cargarUsuarios();
                } else {
                    mostrarAlerta('Error al eliminar el usuario', 'danger');
                }
            })
            .catch(() => {
                mostrarAlerta('Error al eliminar el usuario', 'danger');
            });
    });

    //Nuevo Usuario
    document.getElementById('guardarUsuario').addEventListener('click', guardarUsuario);


    function guardarUsuario(e) {
        e.preventDefault();
        
        const nombre_completo = document.getElementById('nombreUsuario').value.trim();
        const telefono = document.getElementById('telefonoUsuario').value.trim();
        const correo_electronico = document.getElementById('correoUsuario').value.trim();
        const direccion = document.getElementById('direccionUsuario').value.trim();
        const usuario = document.getElementById('usuarioUsuario').value.trim();
        const contrasena = document.getElementById('contrasenaUsuario').value.trim();
        const rol = document.getElementById('rolUsuario').value.trim();
        const estado = document.getElementById('estadoUsuario').value.trim();

        if (!nombre_completo || !telefono || !correo_electronico || !direccion || !usuario || !contrasena || !rol || !estado) {
            mostrarAlerta('Todos los campos son obligatorios', 'danger');
            return;
        }

        const formData = new FormData();
        formData.append('nombre_completo', nombre_completo);
        formData.append('telefono', telefono);
        formData.append('correo_electronico', correo_electronico);
        formData.append('direccion', direccion);
        formData.append('usuario', usuario);
        formData.append('contrasena', contrasena);
        formData.append('rol', rol);
        formData.append('estado', estado);
        formData.append('accion', 'crear');

        fetch('php/usuarios.php', {
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
                    mostrarAlerta('Usuario registrado correctamente', 'success');
                    cargarUsuarios();
                    
                    // Limpiar formulario
                    document.getElementById('nombreUsuario').value = '';
                    document.getElementById('telefonoUsuario').value = '';
                    document.getElementById('correoUsuario').value = '';
                    document.getElementById('direccionUsuario').value = '';
                    document.getElementById('usuarioUsuario').value = '';
                    document.getElementById('contrasenaUsuario').value = '';
                    document.getElementById('rolUsuario').value = '';
                    document.getElementById('estadoUsuario').value = 'activo';
                    
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarUsuario'));
                    modal.hide();
                }
            })
            .catch(() => {
                mostrarAlerta('Error al registrar usuario', 'error');
            });

    }
    togglePassword('togglePasswordNuevoUsuario', 'contrasenaUsuario', 'iconoOjoNuevoUsuario'); // nuevo usuario

    function togglePassword(buttonId, inputId, iconId) {
    const button = document.getElementById(buttonId);
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    button.addEventListener('click', function () {
        const tipo = input.type === 'password' ? 'text' : 'password';
        input.type = tipo;
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    document.getElementById('modalAgregarUsuario').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formUsuario').reset();
     // Restablecer input de contraseña a tipo password
    const input = document.getElementById('contrasenaUsuario');
    input.type = 'password';

    // Restablecer icono del ojo
    const icon = document.getElementById('iconoOjoNuevoUsuario');
    icon.classList.add('fa-eye');
    icon.classList.remove('fa-eye-slash');
});
}


    
    
    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo) {
        // Crear alerta
        const alertaDiv = document.createElement('div');
        alertaDiv.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show`;
        alertaDiv.setAttribute('role', 'alert');
        alertaDiv.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insertar alerta al principio del contenido principal
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(alertaDiv, mainContent.firstChild);
        
        // Auto-eliminar después de 3 segundos
        setTimeout(() => {
            alertaDiv.classList.remove('show');
            setTimeout(() => alertaDiv.remove(), 300);
        }, 3000);
    }
});