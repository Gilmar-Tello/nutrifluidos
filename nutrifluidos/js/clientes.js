document.addEventListener('DOMContentLoaded', function() {
    initAuth('clientes');
    
    // Variables globales
    const tbodyClientes = document.getElementById('tbodyClientes');
    const modalEditar = document.getElementById('modalEditarCliente');
    const modalEliminar = document.getElementById('modalEliminarCliente');
    let modalEditarInstance = null;
    let modalEliminarInstance = null;
    let modalNuevo = null;
    let dataTable = null;
    
    // Inicializar modales de forma segura
    try {
        if (modalEditar) modalEditarInstance = new bootstrap.Modal(modalEditar);
        if (modalEliminar) modalEliminarInstance = new bootstrap.Modal(modalEliminar);
        
        const modalClienteElement = document.getElementById('modalCliente');
        if (modalClienteElement) modalNuevo = new bootstrap.Modal(modalClienteElement);
        
        console.log("Modales inicializados correctamente");
    } catch (error) {
        console.error("Error al inicializar modales:", error);
    }
    
    // Verificar datos de usuario
    const userDataString = localStorage.getItem('userData');
    if (!userDataString) {
        mostrarAlerta('No hay datos de usuario. Redirigiendo al login...', 'error');
        setTimeout(() => { window.location.href = 'index.php'; }, 2000);
        return;
    }
    
    const userData = JSON.parse(userDataString);
    if (!userData.id || !userData.rol) {
        mostrarAlerta('Datos de usuario incompletos. Redirigiendo al login...', 'error');
        setTimeout(() => { window.location.href = 'index.php'; }, 2000);
        return;
    }
    
    const esAdministrador = userData.rol === 'administrador';
    const esVendedor = userData.rol === 'vendedor';
    const esBodeguero = userData.rol === 'bodeguero';
    
    console.log("Usuario cargado:", userData.id, "- Rol:", userData.rol, "- Es admin:", esAdministrador);

    // Mostrar botones solo para administradores
    if (esAdministrador) {
        const btnGestionarPermisos = document.getElementById('btnGestionarPermisos');
        if (btnGestionarPermisos) {
            btnGestionarPermisos.style.display = 'inline-block';
        }
        
        const btnGenerarReporte = document.getElementById('btnGenerarReporte');
        if (btnGenerarReporte) {
            btnGenerarReporte.style.display = 'inline-block';
            btnGenerarReporte.addEventListener('click', function() {
                generarReporteClientes();
            });
        }
    }

    // Inicialización
    cargarClientes();
    
    // Inicializar gestión de permisos si es administrador
    if (esAdministrador) {
        inicializarGestionPermisos();
    }

    // Event Listeners para los modales - Solo para administradores
    if (esAdministrador && modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) {
                console.error("No se encontró el botón que activó el modal de editar");
                return;
            }
            const id = button.getAttribute('data-id');
            if (id) {
                cargarCliente(id);
            } else {
                console.error("No se encontró el ID en el botón de editar");
            }
        });
    }
    
    // Event listener para el modal de eliminar - Solo para administradores
    if (esAdministrador && modalEliminar) {
        modalEliminar.addEventListener('show.bs.modal', function(event) {
            console.log("Evento show.bs.modal activado para modalEliminarCliente");
            const button = event.relatedTarget;
            if (!button) {
                console.error("No se encontró el botón que activó el modal de eliminar");
                return;
            }
            
            const id = button.getAttribute('data-id');
            if (!id) {
                console.error("No se encontró el ID en el botón de eliminar");
                return;
            }
            
            console.log("ID cliente a eliminar:", id);
            document.getElementById('idClienteEliminar').value = id;
            
            // Obtener datos del cliente para mostrar el nombre
            let url = `php/clientes.php?accion=obtener&id=${id}&usuario_id=${userData.id}&rol=${userData.rol}`;
            
            fetch(url)
                .then(handleResponse)
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    const nombreElement = document.getElementById('nombreClienteEliminar');
                    if (nombreElement) {
                        nombreElement.textContent = data.empresa || 'este cliente';
                    } else {
                        console.error("Elemento nombreClienteEliminar no encontrado");
                    }
                })
                .catch(error => {
                    console.error('Error al obtener datos del cliente:', error);
                    const nombreElement = document.getElementById('nombreClienteEliminar');
                    if (nombreElement) {
                        nombreElement.textContent = 'este cliente';
                    }
                });
        });
    }

    // Función para renderizar la tabla de clientes
    function renderizarTablaClientes(data) {
    tbodyClientes.innerHTML = '';
    
    data.forEach(cliente => {
        const tr = document.createElement('tr');
        
        // Determinar el indicador visual para clientes con permisos temporales
        let indicadorPermiso = '';
        if (esVendedor && cliente.permiso_temporal) {
            indicadorPermiso = '<i class="fas fa-key text-warning ms-1" title="Acceso temporal autorizado"></i>';
        } else if (esVendedor && !cliente.es_propio && cliente.puede_gestionar) {
            indicadorPermiso = '<i class="fas fa-key text-info ms-1" title="Cliente con acceso especial"></i>';
        }
        
        tr.innerHTML = `
            <td>${cliente.empresa}${indicadorPermiso}</td>
            <td>${cliente.nit || '-'}</td>
            <td>${cliente.contacto1}</td>
            <td>${cliente.telefono1}</td>
            <td>${cliente.contacto2 || '-'}</td>
            <td>${cliente.telefono2 || '-'}</td>
            <td>${cliente.email || '-'}</td>
            <td>${cliente.direccion || '-'}</td>
<td>${cliente.direccion2 || '-'}</td>
<td><span class="badge bg-primary">${cliente.region || 'Sin asignar'}</span></td>
            <td>${cliente.nombre_vendedor || 'Sin asignar'}</td>
            <td>
                ${esAdministrador ? `
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                    data-bs-target="#modalEditarCliente" data-id="${cliente.id}">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger btn-eliminar-directo" 
                    data-id="${cliente.id}" data-nombre="${cliente.empresa}">
                    <i class="fas fa-trash-alt"></i>
                </button>` : `
                <span class="text-muted">Solo visualización</span>`}
            </td>
        `;
        tbodyClientes.appendChild(tr);
    });

    // Configurar evento para botones de eliminar - Solo para administradores
    if (esAdministrador) {
        document.querySelectorAll('.btn-eliminar-directo').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nombre = this.getAttribute('data-nombre');
                eliminarClienteDirecto(id, nombre);
            });
        });
    }
}

    // Función para cargar clientes según el rol
    function cargarClientes() {
        if (dataTable) {
            dataTable.destroy();
            tbodyClientes.innerHTML = '';
        }
    
        // Construir URL según el rol
        let url = 'php/clientes.php?accion=listar';
        
        // Solo agregamos el parámetro si es vendedor (vendedores solo ven sus clientes)
        if (esVendedor) {
            url += `&id_usuario=${userData.id}`;
        }
        // Bodegueros y administradores ven todos los clientes
        
        // Agregar datos de usuario a la URL para autenticación
        url += `&usuario_id=${userData.id}&rol=${userData.rol}`;

        console.log("Cargando clientes desde:", url);
        
        fetch(url)
            .then(handleResponse)
            .then(data => {
                if (!Array.isArray(data)) {
                    console.error("Datos recibidos no son un array:", data);
                    throw new Error('Formato de datos inválido');
                }
                console.log(`Clientes cargados: ${data.length}`);
                renderizarTablaClientes(data);
                inicializarDataTable();
            })
            .catch(error => {
                console.error('Error al cargar clientes:', error);
                mostrarAlerta('Error al cargar clientes: ' + error.message, 'error');
                renderizarTablaClientes([]);
                inicializarDataTable();
            });
    }

    // Función para inicializar DataTable
    function inicializarDataTable() {
        dataTable = $('#tablaClientes').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            responsive: true,
            columnDefs: [
                { orderable: false, targets: [11] } // Índice de la columna de acciones
            ]
        });
    }

    // Función para cargar datos de un cliente específico - Solo para administradores
    function cargarCliente(id) {
    if (!esAdministrador) {
        mostrarAlerta('No tienes permisos para editar clientes', 'error');
        return;
    }

    let url = `php/clientes.php?accion=obtener&id=${id}&usuario_id=${userData.id}&rol=${userData.rol}`;
    
    fetch(url)
        .then(handleResponse)
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Llenar el formulario de edición
            document.getElementById('editClienteId').value = data.id;
            document.getElementById('editClienteEmpresa').value = data.empresa;
            document.getElementById('editClienteNit').value = data.nit || '';
            document.getElementById('editClienteContacto1').value = data.contacto1;
            document.getElementById('editClienteTelefono1').value = data.telefono1;
            
            // Campos opcionales
            const campos = ['contacto2', 'telefono2', 'email', 'direccion', 'direccion2', 'region']; // AGREGAR 'region'
            campos.forEach(campo => {
                const elemento = document.getElementById(`editCliente${campo.charAt(0).toUpperCase() + campo.slice(1)}`);
                if (elemento) {
                    elemento.value = data[campo] || '';
                }
            });
        })
        .catch(error => {
            console.error('Error al cargar cliente:', error);
            mostrarAlerta('Error al cargar datos del cliente: ' + error.message, 'error');
        });
}

    // Manejo del formulario de nuevo cliente - Todos los roles pueden agregar
   const guardarClienteBtn = document.getElementById('guardarCliente');
if (guardarClienteBtn) {
    guardarClienteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // 1. Validar campos requeridos
        const camposRequeridos = {
    'empresaCliente': 'Empresa',
    'contacto1Cliente': 'Contacto 1', 
    'telefono1Cliente': 'Teléfono 1',
    'regionCliente': 'Región'
};
        let formularioValido = true;
        const valores = {};
        
        // Validar campos y recolectar valores
        for (const [id, nombre] of Object.entries(camposRequeridos)) {
            const elemento = document.getElementById(id);
            if (!elemento) {
                console.error(`Elemento con ID ${id} no encontrado en el DOM`);
                mostrarAlerta(`Error: Campo ${nombre} no encontrado en el formulario`, 'error');
                formularioValido = false;
                continue;
            }
            
            valores[id] = elemento.value.trim();
            
            if (!valores[id]) {
                elemento.classList.add('is-invalid');
                mostrarAlerta(`El campo ${nombre} es requerido`, 'error');
                formularioValido = false;
            } else {
                elemento.classList.remove('is-invalid');
            }
        }

        if (!formularioValido) return;

        // 2. Obtener valores de campos no requeridos
        const camposAdicionales = [
    'nitCliente',
    'contacto2Cliente',
    'telefono2Cliente',
    'emailCliente',
    'direccionCliente',
    'direccion2Cliente'
];
        
        camposAdicionales.forEach(id => {
            const elemento = document.getElementById(id);
            valores[id] = elemento ? elemento.value.trim() : '';
        });

        // 3. Obtener ID del usuario actual para asignarlo como vendedor
        const idVendedor = userData.id;
        
        // 4. Preparar los datos para enviar
        const formData = new FormData();
        formData.append('empresa', valores.empresaCliente);
        formData.append('nit', valores.nitCliente);
        formData.append('contacto1', valores.contacto1Cliente);
        formData.append('telefono1', valores.telefono1Cliente);
        formData.append('contacto2', valores.contacto2Cliente || '');
        formData.append('telefono2', valores.telefono2Cliente || '');
        formData.append('email', valores.emailCliente || '');
        formData.append('direccion', valores.direccionCliente || '');
        formData.append('direccion2', valores.direccion2Cliente || '');
        formData.append('region', valores.regionCliente); // AGREGAR REGIÓN
        formData.append('id_vendedor', idVendedor);
        formData.append('accion', 'crear');
        
        // Agregar datos del usuario para autenticación
        formData.append('usuario_id', userData.id);
        formData.append('rol', userData.rol);

        // 5. Enviar los datos
        enviarDatosCliente(formData, '#modalCliente', '#guardarCliente');
    });
}

    // Event listener para el botón de editar cliente - Solo para administradores
    const btnGuardarCambiosCliente = document.getElementById('btnGuardarCambiosCliente');
if (btnGuardarCambiosCliente && esAdministrador) {
    btnGuardarCambiosCliente.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Obtener valores del formulario
        const id = document.getElementById('editClienteId').value;
        const empresa = document.getElementById('editClienteEmpresa').value.trim();
        const nit = document.getElementById('editClienteNit').value.trim();
        const contacto1 = document.getElementById('editClienteContacto1').value.trim();
        const telefono1 = document.getElementById('editClienteTelefono1').value.trim();
        
        // Para los campos opcionales, verificamos que existan antes de acceder a ellos
        const contacto2Element = document.getElementById('editClienteContacto2');
        const telefono2Element = document.getElementById('editClienteTelefono2');
        const emailElement = document.getElementById('editClienteEmail');
        const direccionElement = document.getElementById('editClienteDireccion');
        const direccion2Element = document.getElementById('editClienteDireccion2');
        const regionElement = document.getElementById('editClienteRegion'); // AGREGAR REGIÓN
        
        const contacto2 = contacto2Element ? contacto2Element.value.trim() : '';
        const telefono2 = telefono2Element ? telefono2Element.value.trim() : '';
        const email = emailElement ? emailElement.value.trim() : '';
        const direccion = direccionElement ? direccionElement.value.trim() : '';
        const direccion2 = direccion2Element ? direccion2Element.value.trim() : '';
        const region = regionElement ? regionElement.value.trim() : ''; // OBTENER REGIÓN
        
        // Validar campos requeridos
        if (!empresa || !contacto1 || !telefono1 || !region) { // INCLUIR REGIÓN EN VALIDACIÓN
            mostrarAlerta('Los campos Empresa, Contacto 1, Teléfono 1 y Región son requeridos', 'error');
            return;
        }
        
        // Preparar datos para enviar
        const formData = new FormData();
        formData.append('id', id);
        formData.append('empresa', empresa);
        formData.append('nit', nit);
        formData.append('contacto1', contacto1);
        formData.append('telefono1', telefono1);
        formData.append('contacto2', contacto2);
        formData.append('telefono2', telefono2);
        formData.append('email', email);
        formData.append('direccion', direccion);
        formData.append('direccion2', direccion2);
        formData.append('region', region); // AGREGAR REGIÓN AL ENVÍO
        formData.append('accion', 'actualizar');
        
        // Agregar datos del usuario para autenticación
        formData.append('usuario_id', userData.id);
        formData.append('rol', userData.rol);
        
        // Enviar los datos
        enviarDatosCliente(formData, '#modalEditarCliente', '#btnGuardarCambiosCliente');
    });
}

    // Función para manejar respuestas del servidor
    function handleResponse(response) {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Respuesta no es JSON válido:', text.substring(0, 200));
                throw new Error('La respuesta del servidor no es JSON válido');
            }
        });
    }

    // Función para mostrar alertas
    function mostrarAlerta(mensaje, tipo = 'success') {
        const alerta = document.createElement('div');
        alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
        alerta.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const contenedor = document.getElementById('alertaContainer');
        if (!contenedor) {
            console.error("Contenedor de alertas no encontrado");
            return;
        }
        
        contenedor.innerHTML = '';
        contenedor.appendChild(alerta);
        
        setTimeout(() => {
            const alertInstance = bootstrap.Alert.getOrCreateInstance(alerta);
            if (alertInstance) alertInstance.close();
        }, 5000);
    }

    // Limpiar formulario al cerrar modal
    const modalCliente = document.getElementById('modalCliente');
    if (modalCliente) {
        modalCliente.addEventListener('hidden.bs.modal', function() {
            const formCliente = document.getElementById('formCliente');
            if (formCliente) formCliente.reset();
        });
    }

    // Función para enviar datos al servidor
    function enviarDatosCliente(formData, modalId, botonId) {
        const btn = document.querySelector(botonId);
        if (!btn) {
            console.error(`Botón con selector ${botonId} no encontrado`);
            mostrarAlerta('Error interno: Botón no encontrado', 'error');
            return;
        }
        
        const modalElement = document.querySelector(modalId);
        const modal = modalElement ? bootstrap.Modal.getInstance(modalElement) : null;
        
        // Verificar si hay datos de usuario válidos
        if (!userData || !userData.id || !userData.rol) {
            mostrarAlerta('Error: No hay datos de usuario válidos', 'error');
            return;
        }
        
        // Asegurarse de que los datos de usuario estén incluidos para autenticación
        if (!formData.has('usuario_id')) {
            formData.append('usuario_id', userData.id);
        }
        if (!formData.has('rol')) {
            formData.append('rol', userData.rol);
        }
        
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';

        // Para debugging - mostrar los datos que se envían
        console.log('Enviando datos:', {
            'usuario_id': userData.id,
            'rol': userData.rol,
            'accion': formData.get('accion')
        });

        fetch('php/clientes.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            return response.text().then(text => {
                console.log('Respuesta del servidor:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta no es JSON válido:', text.substring(0, 200));
                    throw new Error('Error al procesar la respuesta del servidor');
                }
            });
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            
            mostrarAlerta(data.success || 'Cliente guardado correctamente', 'success');
            cargarClientes();
            
            if (modal) {
                modal.hide();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error: ' + error.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = 'Guardar';
        });
    }

    // Evento para el botón de confirmar eliminación - Solo para administradores
    const btnConfirmarEliminarCliente = document.getElementById('btnConfirmarEliminarCliente');
    if (btnConfirmarEliminarCliente && esAdministrador) {
        btnConfirmarEliminarCliente.addEventListener('click', function() {
            const idClienteEliminar = document.getElementById('idClienteEliminar');
            if (!idClienteEliminar) {
                console.error("Campo idClienteEliminar no encontrado");
                mostrarAlerta('Error interno: Campo de ID no encontrado', 'error');
                return;
            }
            
            const idCliente = idClienteEliminar.value;
            
            if (!idCliente) {
                mostrarAlerta('Error: ID de cliente no válido', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('id', idCliente);
            formData.append('accion', 'eliminar');
            
            // Agregar datos del usuario para autenticación
            formData.append('usuario_id', userData.id);
            formData.append('rol', userData.rol);
            
            // Referencia al botón para cambiar su estado
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
            
            // Log para debugging
            console.log('Enviando solicitud de eliminación para cliente ID:', idCliente);
            
            fetch('php/clientes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    console.log('Respuesta del servidor:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Respuesta no es JSON válido:', text);
                        throw new Error('Error al procesar la respuesta del servidor');
                    }
                });
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                mostrarAlerta(data.success || 'Cliente eliminado correctamente', 'success');
                
                // Recargar la tabla después de eliminar
                cargarClientes();
                
                // Cerrar el modal
                if (modalEliminarInstance) {
                    modalEliminarInstance.hide();
                }
            })
            .catch(error => {
                console.error('Error al eliminar el cliente:', error);
                mostrarAlerta('Error: ' + error.message, 'error');
            })
            .finally(() => {
                // Restaurar el botón
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash-alt me-1"></i> Eliminar';
            });
        });
    }

    // Función para eliminar cliente directo - Solo para administradores
    function eliminarClienteDirecto(id, nombre) {
        if (!esAdministrador) {
            mostrarAlerta('No tienes permisos para eliminar clientes', 'error');
            return;
        }

        if (!id) {
            mostrarAlerta('Error: ID de cliente no válido', 'error');
            return;
        }

        console.log('Solicitando confirmación para eliminar cliente ID:', id, 'Nombre:', nombre);
        
        // Usar confirm nativo
        if (confirm(`¿Está seguro que desea eliminar al cliente "${nombre}"? Esta acción no se puede deshacer.`)) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('accion', 'eliminar');
            formData.append('usuario_id', userData.id);
            formData.append('rol', userData.rol);
            
            console.log('Enviando solicitud de eliminación para cliente ID:', id);
            
            // Mostrar indicador de procesamiento
            mostrarAlerta('Procesando eliminación...', 'info');
            
            fetch('php/clientes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                return response.text().then(text => {
                    console.log('Respuesta del servidor:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Respuesta no es JSON válido:', text);
                        throw new Error('Error al procesar la respuesta del servidor');
                    }
                });
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                mostrarAlerta(data.success || `Cliente "${nombre}" eliminado correctamente`, 'success');
                cargarClientes(); // Recargar la tabla
            })
            .catch(error => {
                console.error('Error al eliminar el cliente:', error);
                mostrarAlerta('Error: ' + error.message, 'error');
            });
        }
    }

    // Funciones para gestión de permisos
    function inicializarGestionPermisos() {
        console.log("Inicializando gestión de permisos para administrador");
        
        // Event listener para cuando se abre el modal de permisos
        const modalPermisos = document.getElementById('modalPermisos');
        if (modalPermisos) {
            modalPermisos.addEventListener('show.bs.modal', function() {
                cargarPermisosActivos();
                limpiarFormularioPermisos();
            });
        }
        
        // Event listener para el buscador de clientes
        const buscarCliente = document.getElementById('buscarCliente');
        if (buscarCliente) {
            let timeoutBusqueda;
            buscarCliente.addEventListener('input', function() {
                clearTimeout(timeoutBusqueda);
                const termino = this.value.trim();
                
                if (termino.length < 2) {
                    ocultarResultadosBusqueda();
                    return;
                }
                
                // Debounce para evitar demasiadas consultas
                timeoutBusqueda = setTimeout(() => {
                    buscarClientesEnTiempoReal(termino);
                }, 300);
            });
        }
        
        // Event listener para limpiar búsqueda
        const btnLimpiarBusqueda = document.getElementById('btnLimpiarBusqueda');
        if (btnLimpiarBusqueda) {
            btnLimpiarBusqueda.addEventListener('click', function() {
                limpiarBusquedaCliente();
            });
        }
        
        // Event listener para el buscador de vendedores
        const buscarVendedor = document.getElementById('buscarVendedor');
        if (buscarVendedor) {
            let timeoutBusquedaVendedor;
            buscarVendedor.addEventListener('input', function() {
                clearTimeout(timeoutBusquedaVendedor);
                const termino = this.value.trim();
                
                if (termino.length < 2) {
                    ocultarResultadosBusquedaVendedor();
                    return;
                }
                
                // Debounce para evitar demasiadas consultas
                timeoutBusquedaVendedor = setTimeout(() => {
                    const vendedorExcluir = document.getElementById('vendedorPropietarioId').value;
                    buscarVendedoresEnTiempoReal(termino, vendedorExcluir);
                }, 300);
            });
        }
        
        // Event listener para limpiar búsqueda de vendedor
        const btnLimpiarVendedor = document.getElementById('btnLimpiarVendedor');
        if (btnLimpiarVendedor) {
            btnLimpiarVendedor.addEventListener('click', function() {
                limpiarBusquedaVendedor();
            });
        }
        
        // Event listener para el formulario de conceder permiso
        const formConcederPermiso = document.getElementById('formConcederPermiso');
        if (formConcederPermiso) {
            formConcederPermiso.addEventListener('submit', function(e) {
                e.preventDefault();
                concederPermisoActualizado();
            });
        }
        
        // Event listener para actualizar permisos
        const btnActualizarPermisos = document.getElementById('btnActualizarPermisos');
        if (btnActualizarPermisos) {
            btnActualizarPermisos.addEventListener('click', function() {
                cargarPermisosActivos();
            });
        }
    }
    
    function buscarClientesEnTiempoReal(termino) {
        const url = `php/permisos.php?accion=buscar_clientes&termino=${encodeURIComponent(termino)}&usuario_id=${userData.id}&rol=${userData.rol}`;
        
        fetch(url)
            .then(handleResponse)
            .then(data => {
                if (Array.isArray(data)) {
                    mostrarResultadosBusqueda(data);
                } else {
                    console.error('Error en búsqueda:', data);
                    ocultarResultadosBusqueda();
                }
            })
            .catch(error => {
                console.error('Error al buscar clientes:', error);
                ocultarResultadosBusqueda();
            });
    }
    
    function mostrarResultadosBusqueda(clientes) {
        const contenedor = document.getElementById('resultadosBusqueda');
        if (!contenedor) return;
        
        if (clientes.length === 0) {
            contenedor.innerHTML = `
                <div class="list-group-item text-muted text-center">
                    <i class="fas fa-search me-2"></i>No se encontraron clientes
                </div>
            `;
        } else {
            contenedor.innerHTML = clientes.map(cliente => `
                <button type="button" class="list-group-item list-group-item-action cliente-resultado" 
                        data-id="${cliente.id}"
                        data-empresa="${cliente.empresa}"
                        data-nit="${cliente.nit}"
                        data-vendedor-id="${cliente.id_vendedor}"
                        data-vendedor-nombre="${cliente.nombre_vendedor}">
                    <div class="d-flex w-100 justify-content-between">
                        <div>
                            <h6 class="mb-1">${cliente.empresa}</h6>
                            <small class="text-muted">NIT: ${cliente.nit}</small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">Vendedor:</small>
                            <div><strong>${cliente.nombre_vendedor}</strong></div>
                        </div>
                    </div>
                </button>
            `).join('');
            
            // Event listeners para seleccionar cliente
            contenedor.querySelectorAll('.cliente-resultado').forEach(btn => {
                btn.addEventListener('click', function() {
                    seleccionarCliente({
                        id: this.getAttribute('data-id'),
                        empresa: this.getAttribute('data-empresa'),
                        nit: this.getAttribute('data-nit'),
                        vendedor_id: this.getAttribute('data-vendedor-id'),
                        vendedor_nombre: this.getAttribute('data-vendedor-nombre')
                    });
                });
            });
        }
        
        contenedor.style.display = 'block';
    }
    
    function ocultarResultadosBusqueda() {
        const contenedor = document.getElementById('resultadosBusqueda');
        if (contenedor) {
            contenedor.style.display = 'none';
        }
    }
    
    function seleccionarCliente(cliente) {
        // Ocultar resultados de búsqueda
        ocultarResultadosBusqueda();
        
        // Llenar información del cliente seleccionado
        document.getElementById('nombreEmpresaSeleccionada').textContent = cliente.empresa;
        document.getElementById('nitSeleccionado').textContent = cliente.nit;
        document.getElementById('vendedorActualSeleccionado').textContent = cliente.vendedor_nombre;
        document.getElementById('clienteSeleccionadoId').value = cliente.id;
        document.getElementById('vendedorPropietarioId').value = cliente.vendedor_id;
        
        // Mostrar la tarjeta del cliente seleccionado
        const clienteSeleccionado = document.getElementById('clienteSeleccionado');
        if (clienteSeleccionado) {
            clienteSeleccionado.style.display = 'block';
        }
        
        // Habilitar búsqueda de vendedores
        const buscarVendedor = document.getElementById('buscarVendedor');
        if (buscarVendedor) {
            buscarVendedor.disabled = false;
        }
        
        const btnLimpiarVendedor = document.getElementById('btnLimpiarVendedor');
        if (btnLimpiarVendedor) {
            btnLimpiarVendedor.disabled = false;
        }
        
        // Limpiar el campo de búsqueda
        const buscarCliente = document.getElementById('buscarCliente');
        if (buscarCliente) {
            buscarCliente.value = `${cliente.empresa} (${cliente.nit})`;
        }
    }
    
    function limpiarBusquedaCliente() {
        // Limpiar campo de búsqueda
        const buscarCliente = document.getElementById('buscarCliente');
        if (buscarCliente) {
            buscarCliente.value = '';
        }
        
        // Ocultar resultados
        ocultarResultadosBusqueda();
        
        // Ocultar cliente seleccionado
        const clienteSeleccionado = document.getElementById('clienteSeleccionado');
        if (clienteSeleccionado) {
            clienteSeleccionado.style.display = 'none';
        }
        
        // Deshabilitar búsqueda de vendedores
        const buscarVendedor = document.getElementById('buscarVendedor');
        if (buscarVendedor) {
            buscarVendedor.disabled = true;
            buscarVendedor.value = '';
        }
        
        const btnLimpiarVendedor = document.getElementById('btnLimpiarVendedor');
        if (btnLimpiarVendedor) {
            btnLimpiarVendedor.disabled = true;
        }
        
        // Ocultar vendedor seleccionado
        const vendedorSeleccionado = document.getElementById('vendedorSeleccionado');
        if (vendedorSeleccionado) {
            vendedorSeleccionado.style.display = 'none';
        }
        
        // Deshabilitar botón
        const btnConcederPermiso = document.getElementById('btnConcederPermiso');
        if (btnConcederPermiso) {
            btnConcederPermiso.disabled = true;
        }
    }
    
    function limpiarFormularioPermisos() {
        limpiarBusquedaCliente();
        limpiarBusquedaVendedor();
        
        // Limpiar campos del formulario
        const motivoPermiso = document.getElementById('motivoPermiso');
        if (motivoPermiso) motivoPermiso.value = '';
        
        const observacionesPermiso = document.getElementById('observacionesPermiso');
        if (observacionesPermiso) observacionesPermiso.value = '';
    }
    
    function buscarVendedoresEnTiempoReal(termino, vendedorExcluir) {
        let url = `php/permisos.php?accion=buscar_vendedores&termino=${encodeURIComponent(termino)}&usuario_id=${userData.id}&rol=${userData.rol}`;
        
        if (vendedorExcluir) {
            url += `&excluir=${vendedorExcluir}`;
        }
        
        fetch(url)
            .then(handleResponse)
            .then(data => {
                if (Array.isArray(data)) {
                    mostrarResultadosBusquedaVendedor(data);
                } else {
                    console.error('Error en búsqueda de vendedores:', data);
                    ocultarResultadosBusquedaVendedor();
                }
            })
            .catch(error => {
                console.error('Error al buscar vendedores:', error);
                ocultarResultadosBusquedaVendedor();
            });
    }
    
    function mostrarResultadosBusquedaVendedor(vendedores) {
        const contenedor = document.getElementById('resultadosBusquedaVendedor');
        if (!contenedor) return;
        
        if (vendedores.length === 0) {
            contenedor.innerHTML = `
                <div class="list-group-item text-muted text-center">
                    <i class="fas fa-user-times me-2"></i>No se encontraron vendedores
                </div>
            `;
        } else {
            contenedor.innerHTML = vendedores.map(vendedor => `
                <button type="button" class="list-group-item list-group-item-action vendedor-resultado" 
                        data-id="${vendedor.id}"
                        data-nombre="${vendedor.nombre}"
                        data-email="${vendedor.email}"
                        data-usuario="${vendedor.usuario}">
                    <div class="d-flex w-100 justify-content-between">
                        <div>
                            <h6 class="mb-1">${vendedor.nombre}</h6>
                            <small class="text-muted">@${vendedor.usuario}</small>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">${vendedor.email}</small>
                        </div>
                    </div>
                </button>
            `).join('');
            
            // Event listeners para seleccionar vendedor
            contenedor.querySelectorAll('.vendedor-resultado').forEach(btn => {
                btn.addEventListener('click', function() {
                    seleccionarVendedor({
                        id: this.getAttribute('data-id'),
                        nombre: this.getAttribute('data-nombre'),
                        email: this.getAttribute('data-email'),
                        usuario: this.getAttribute('data-usuario')
                    });
                });
            });
        }
        
        contenedor.style.display = 'block';
    }
    
    function ocultarResultadosBusquedaVendedor() {
        const contenedor = document.getElementById('resultadosBusquedaVendedor');
        if (contenedor) {
            contenedor.style.display = 'none';
        }
    }
    
    function seleccionarVendedor(vendedor) {
        // Ocultar resultados de búsqueda
        ocultarResultadosBusquedaVendedor();
        
        // Llenar información del vendedor seleccionado
        document.getElementById('nombreVendedorSeleccionado').textContent = vendedor.nombre;
        document.getElementById('emailVendedorSeleccionado').textContent = vendedor.email;
        document.getElementById('vendedorSeleccionadoId').value = vendedor.id;
        
        // Mostrar para quién podrá gestionar
        const propietarioNombre = document.getElementById('vendedorActualSeleccionado').textContent;
        document.getElementById('propietarioParaVendedor').textContent = propietarioNombre;
        
        // Mostrar la tarjeta del vendedor seleccionado
        const vendedorSeleccionado = document.getElementById('vendedorSeleccionado');
        if (vendedorSeleccionado) {
            vendedorSeleccionado.style.display = 'block';
        }
        
        // Verificar si todos los campos están completos para habilitar botón
        verificarCamposCompletos();
        
        // Limpiar el campo de búsqueda
        const buscarVendedor = document.getElementById('buscarVendedor');
        if (buscarVendedor) {
            buscarVendedor.value = `${vendedor.nombre} (@${vendedor.usuario})`;
        }
    }
    
    function limpiarBusquedaVendedor() {
        // Limpiar campo de búsqueda
        const buscarVendedor = document.getElementById('buscarVendedor');
        if (buscarVendedor) {
            buscarVendedor.value = '';
        }
        
        // Ocultar resultados
        ocultarResultadosBusquedaVendedor();
        
        // Ocultar vendedor seleccionado
        const vendedorSeleccionado = document.getElementById('vendedorSeleccionado');
        if (vendedorSeleccionado) {
            vendedorSeleccionado.style.display = 'none';
        }
        
        // Limpiar campo oculto
        const vendedorSeleccionadoId = document.getElementById('vendedorSeleccionadoId');
        if (vendedorSeleccionadoId) {
            vendedorSeleccionadoId.value = '';
        }
        
        // Verificar campos
        verificarCamposCompletos();
    }
    
    function verificarCamposCompletos() {
        const clienteId = document.getElementById('clienteSeleccionadoId').value;
        const vendedorId = document.getElementById('vendedorSeleccionadoId').value;
        const btnConcederPermiso = document.getElementById('btnConcederPermiso');
        
        if (btnConcederPermiso) {
            btnConcederPermiso.disabled = !(clienteId && vendedorId);
        }
    }
    
    function cargarPermisosActivos() {
        const url = `php/permisos.php?accion=listar_permisos&usuario_id=${userData.id}&rol=${userData.rol}`;
        
        fetch(url)
            .then(handleResponse)
            .then(data => {
                if (Array.isArray(data)) {
                    renderizarTablaPermisos(data);
                } else {
                    throw new Error(data.error || 'Error al cargar permisos');
                }
            })
            .catch(error => {
                console.error('Error al cargar permisos:', error);
                mostrarAlerta('Error al cargar permisos activos: ' + error.message, 'error');
                renderizarTablaPermisos([]);
            });
    }
    
    function renderizarTablaPermisos(permisos) {
        const tbody = document.getElementById('tbodyPermisos');
        if (!tbody) {
            console.error('Tabla de permisos no encontrada');
            return;
        }
        
        tbody.innerHTML = '';
        
        if (permisos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        <i class="fas fa-info-circle me-2"></i>No hay permisos activos
                    </td>
                </tr>
            `;
            return;
        }
        
        permisos.forEach(permiso => {
            const tr = document.createElement('tr');
            const fechaConcesion = new Date(permiso.fecha_concesion).toLocaleDateString('es-ES');
            
            tr.innerHTML = `
                <td>
                    <i class="fas fa-user me-1"></i>${permiso.vendedor_autorizado}
                </td>
                <td>
                    <i class="fas fa-user-tie me-1"></i>${permiso.vendedor_propietario}
                </td>
                <td>
                    <span class="badge bg-info">${permiso.motivo}</span>
                </td>
                <td>${fechaConcesion}</td>
                <td>${permiso.administrador}</td>
                <td>
                    <button class="btn btn-sm btn-danger btn-revocar-permiso" 
                            data-id="${permiso.id}" 
                            data-autorizado="${permiso.vendedor_autorizado}"
                            data-propietario="${permiso.vendedor_propietario}">
                        <i class="fas fa-times me-1"></i>Revocar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Event listeners para botones de revocar
        document.querySelectorAll('.btn-revocar-permiso').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const autorizado = this.getAttribute('data-autorizado');
                const propietario = this.getAttribute('data-propietario');
                revocarPermiso(id, autorizado, propietario);
            });
        });
    }
    
    function concederPermisoActualizado() {
        const clienteId = document.getElementById('clienteSeleccionadoId').value;
        const idVendedorPropietario = document.getElementById('vendedorPropietarioId').value;
        const idVendedorAutorizado = document.getElementById('vendedorSeleccionadoId').value;
        const motivo = document.getElementById('motivoPermiso').value.trim();
        const observaciones = document.getElementById('observacionesPermiso').value.trim();
        
        // Validaciones
        if (!clienteId || !idVendedorPropietario) {
            mostrarAlerta('Debe seleccionar un cliente primero', 'error');
            return;
        }
        
        if (!idVendedorAutorizado) {
            mostrarAlerta('Debe seleccionar el vendedor que recibirá el permiso', 'error');
            return;
        }
        
        if (idVendedorAutorizado === idVendedorPropietario) {
            mostrarAlerta('No puede seleccionar el mismo vendedor para ambos campos', 'error');
            return;
        }
        
        if (!motivo) {
            mostrarAlerta('El motivo es requerido', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('accion', 'conceder_permiso');
        formData.append('id_vendedor_autorizado', idVendedorAutorizado);
        formData.append('id_vendedor_propietario', idVendedorPropietario);
        formData.append('motivo', motivo);
        formData.append('observaciones', observaciones);
        formData.append('usuario_id', userData.id);
        formData.append('rol', userData.rol);
        
        const btn = document.getElementById('btnConcederPermiso');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        
        fetch('php/permisos.php', {
            method: 'POST',
            body: formData
        })
        .then(handleResponse)
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            mostrarAlerta(data.success, 'success');
            
            // Limpiar formulario
            limpiarFormularioPermisos();
            
            // Recargar permisos activos
            cargarPermisosActivos();
            
            // Recargar clientes para reflejar cambios
            cargarClientes();
        })
        .catch(error => {
            console.error('Error al conceder permiso:', error);
            mostrarAlerta('Error: ' + error.message, 'error');
        })
        .finally(() => {
            btn.disabled = true; // Se mantiene deshabilitado hasta seleccionar otro cliente
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Conceder Permiso';
        });
    }
    
    function revocarPermiso(idPermiso, vendedorAutorizado, vendedorPropietario) {
        const confirmacion = confirm(
            `¿Está seguro que desea revocar el permiso de "${vendedorAutorizado}" ` +
            `para gestionar clientes de "${vendedorPropietario}"?`
        );
        
        if (!confirmacion) return;
        
        const formData = new FormData();
        formData.append('accion', 'revocar_permiso');
        formData.append('id_permiso', idPermiso);
        formData.append('usuario_id', userData.id);
        formData.append('rol', userData.rol);
        
        mostrarAlerta('Revocando permiso...', 'info');
        
        fetch('php/permisos.php', {
            method: 'POST',
            body: formData
        })
        .then(handleResponse)
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            mostrarAlerta(data.success, 'success');
            
            // Recargar permisos activos
            cargarPermisosActivos();
            
            // Recargar clientes para reflejar cambios
            cargarClientes();
        })
        .catch(error => {
            console.error('Error al revocar permiso:', error);
            mostrarAlerta('Error: ' + error.message, 'error');
        });
    }
    
    /**
     * Genera reporte PDF de todos los clientes
     * Solo disponible para administradores
     */
    function generarReporteClientes() {
        const esAdministrador = userData.rol === 'administrador';
        
        if (!esAdministrador) {
            mostrarAlerta('No tienes permisos para generar reportes', 'error');
            return;
        }
        
        // Mostrar indicador de carga
        const btn = document.getElementById('btnGenerarReporte');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';
        }
        
        // Preparar datos para el reporte
        const reporteData = {
            accion: 'generar_reporte',
            usuario_id: userData.id,
            rol: userData.rol
        };
        
        // Realizar petición al servidor
        fetch('php/exportar_clientes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(reporteData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.blob();
        })
        .then(blob => {
            // Crear URL del blob y descargar
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            
            // Generar nombre del archivo con fecha actual
            const fechaActual = new Date().toISOString().slice(0, 10);
            a.download = `reporte_clientes_${fechaActual}.pdf`;
            
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            // Mostrar mensaje de éxito
            mostrarAlerta('Reporte generado y descargado con éxito', 'success');
        })
        .catch(error => {
            console.error('Error al generar reporte:', error);
            mostrarAlerta('Error al generar el reporte. Por favor intente nuevamente.', 'error');
        })
        .finally(() => {
            // Restaurar el botón
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-file-pdf me-2"></i>Generar Reporte';
            }
        });
    }

}); // FIN del document.addEventListener('DOMContentLoaded')