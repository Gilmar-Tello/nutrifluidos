/**
 * ventas.js - Sistema de Punto de Venta
 * Implementa las funcionalidades de gestión de ventas
 */

// Variables globales
let carrito = [];
let tablaVentas;
let datosVentas = [];

document.addEventListener('DOMContentLoaded', function() {
    // Verificar sesión y permisos requeridos para ventas
    initAuth('ventas');
    
    // Inicializar fecha automática
    establecerFechaAutomatica();
    
    // Configurar comportamiento del tipo de venta
    configurarTipoVenta();
    
    // Inicializar búsqueda de clientes
    inicializarBusquedaClientes();
    
    // Inicializar búsqueda de productos
    inicializarBusquedaProductos();
    
    // Configurar eventos para finalizar venta
    const btnFinalizarVenta = document.querySelector('#btnFinalizarVenta');
    if (btnFinalizarVenta) {
        btnFinalizarVenta.addEventListener('click', finalizarVenta);
    }

    const historialTab = document.getElementById('historial-tab');
    if (historialTab) {
        historialTab.addEventListener('click', function() {
            inicializarHistorialVentas();
        });
    }

});

/**
 * Establece la fecha actual en Guatemala y permite seleccionar 3 días atrás y 3 días adelante
 */
function establecerFechaAutomatica() {
    // Obtener el campo de fecha
    const fechaInput = document.querySelector('#fechaVenta');
    
    if (fechaInput) {
        // Obtener la fecha actual en Guatemala (UTC-6)
        const fechaActual = obtenerFechaGuatemala();
        
        // Calcular fechas límite (3 días atrás y 3 días adelante)
        const fechaMinima = new Date(fechaActual);
        fechaMinima.setDate(fechaActual.getDate() - 3);
        
        const fechaMaxima = new Date(fechaActual);
        fechaMaxima.setDate(fechaActual.getDate() + 3);
        
        // Formatear fechas para el campo de entrada (YYYY-MM-DD)
        const fechaActualFormateada = fechaActual.toISOString().split('T')[0];
        const fechaMinimaFormateada = fechaMinima.toISOString().split('T')[0];
        const fechaMaximaFormateada = fechaMaxima.toISOString().split('T')[0];
        
        // Configurar el campo de fecha
        fechaInput.value = fechaActualFormateada; // Fecha actual por defecto
        fechaInput.min = fechaMinimaFormateada;   // Mínimo: 3 días atrás
        fechaInput.max = fechaMaximaFormateada;   // Máximo: 3 días adelante
        
        // QUITAR readonly - ahora el campo es editable
        fechaInput.removeAttribute('readonly');
        fechaInput.style.backgroundColor = '#ffffff'; // Fondo blanco para indicar que es editable
        
        // Actualizar el tooltip con información del rango permitido
        const fechaMinimaTexto = formatearFechaParaTooltip(fechaMinima);
        const fechaMaximaTexto = formatearFechaParaTooltip(fechaMaxima);
        
        fechaInput.setAttribute('title', 
            `Rango permitido: ${fechaMinimaTexto} - ${fechaMaximaTexto}`
        );
        
        // Agregar validación en tiempo real
        fechaInput.addEventListener('change', function() {
            validarFechaSeleccionada(this);
        });
        
        // Quitar eventos anteriores que bloqueaban la edición
        fechaInput.removeEventListener('keydown', bloquearTeclado);
        fechaInput.removeEventListener('click', bloquearClick);
        
        // Agregar estilo visual para indicar el rango
        fechaInput.style.borderLeft = '3px solid #28a745'; // Verde para indicar rango válido
        
        console.log(`Fecha configurada: ${fechaActualFormateada}`);
        console.log(`Rango permitido: ${fechaMinimaFormateada} a ${fechaMaximaFormateada}`);
    }
}

/**
 * Valida que la fecha seleccionada esté dentro del rango permitido
 * @param {HTMLInputElement} input - Campo de fecha
 */
function validarFechaSeleccionada(input) {
    const fechaSeleccionada = new Date(input.value);
    const fechaMinima = new Date(input.min);
    const fechaMaxima = new Date(input.max);
    
    if (fechaSeleccionada < fechaMinima || fechaSeleccionada > fechaMaxima) {
        // Fecha fuera del rango permitido
        input.style.borderLeft = '3px solid #dc3545'; // Rojo para error
        input.style.backgroundColor = '#fff5f5';
        
        const fechaMinimaTexto = formatearFechaParaTooltip(fechaMinima);
        const fechaMaximaTexto = formatearFechaParaTooltip(fechaMaxima);
        
        alert(`Fecha no válida. Solo puede seleccionar fechas entre:\n${fechaMinimaTexto} y ${fechaMaximaTexto}`);
        
        // Restablecer a la fecha actual
        const fechaActual = obtenerFechaGuatemala();
        input.value = fechaActual.toISOString().split('T')[0];
        
        // Restaurar estilo normal
        input.style.borderLeft = '3px solid #28a745';
        input.style.backgroundColor = '#ffffff';
    } else {
        // Fecha válida
        input.style.borderLeft = '3px solid #28a745'; // Verde
        input.style.backgroundColor = '#ffffff';
    }
}

/**
 * Formatea una fecha para mostrar en tooltip de manera legible
 * @param {Date} fecha - Fecha a formatear
 * @returns {string} Fecha formateada (dd/mm/yyyy)
 */
function formatearFechaParaTooltip(fecha) {
    const dia = fecha.getDate().toString().padStart(2, '0');
    const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
    const anio = fecha.getFullYear();
    
    return `${dia}/${mes}/${anio}`;
}

// Funciones auxiliares para bloqueo (se pueden eliminar si existen)
function bloquearTeclado(e) {
    e.preventDefault();
}

function bloquearClick(e) {
    e.preventDefault();
}

/**
 * Obtiene la fecha actual en la zona horaria de Guatemala (UTC-6)
 * @returns {Date} Fecha actual en Guatemala
 */
function obtenerFechaGuatemala() {
    // Crear fecha con la configuración explícita para Guatemala
    const ahora = new Date();
    
    // Método directo: crear la fecha usando la fecha local pero configurando hora a 0
    // para evitar problemas con cambios de día debido a zonas horarias
    const fechaGuatemala = new Date(
        ahora.getFullYear(),
        ahora.getMonth(),
        ahora.getDate()
    );
    
    return fechaGuatemala;
}

/**
 * Configura el comportamiento del selector de tipo de venta (crédito/contado)
 */
function configurarTipoVenta() {
    const tipoVentaSelect = document.querySelector('#tipoVenta');
    const opcionesCreditoContainer = document.querySelector('#opcionesCredito');
    
    if (tipoVentaSelect && opcionesCreditoContainer) {
        // Verificar estado inicial al cargar la página
        actualizarOpcionesCredito(tipoVentaSelect.value);
        
        // Agregar evento para detectar cambios en el tipo de venta
        tipoVentaSelect.addEventListener('change', function() {
            actualizarOpcionesCredito(this.value);
        });
    }
    
    /**
     * Muestra u oculta las opciones de crédito según el tipo de venta seleccionado
     * @param {string} tipoVenta - Tipo de venta seleccionado (credito/contado)
     */
    function actualizarOpcionesCredito(tipoVenta) {
        if (tipoVenta.toLowerCase() === 'credito') {
            // Mostrar opciones de crédito
            opcionesCreditoContainer.classList.remove('d-none');
            
            // Verificar que los campos tengan valores por defecto
            const anticipoInput = document.querySelector('#anticipo');
            const plazoPagoInput = document.querySelector('#plazoPago');
            
            if (anticipoInput && anticipoInput.value === '') {
                anticipoInput.value = '0';
            }
            
            if (plazoPagoInput && plazoPagoInput.value === '') {
                plazoPagoInput.value = '1';
            }
        } else {
            // Ocultar opciones de crédito
            opcionesCreditoContainer.classList.add('d-none');
        }
    }
}
/**
 * Inicializa la funcionalidad de búsqueda de clientes
 */
function inicializarBusquedaClientes() {
    const clienteInput = document.querySelector('#buscarCliente');
    const buscarClienteBtn = document.querySelector('#btnBuscarCliente');
    const sugerenciasContainer = document.querySelector('#clientSuggestions');

    // Configurar eventos para la búsqueda y autocompletado
    if (clienteInput) {
        // Estilizar el contenedor de sugerencias
        if (sugerenciasContainer) {
            sugerenciasContainer.style.cssText = `
                position: absolute;
                width: 100%;
                max-height: 200px;
                overflow-y: auto;
                border: 1px solid #ccc;
                border-top: none;
                background-color: white;
                z-index: 1000;
                border-radius: 0 0 4px 4px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            `;
        }

        // Mostrar sugerencias mientras escribe
        clienteInput.addEventListener('input', function() {
            if (this.value.length >= 2) {
                // Solo buscar si hay al menos 2 caracteres
                buscarClienteSugerencias(this.value);
            } else {
                if (sugerenciasContainer) {
                    sugerenciasContainer.classList.add('d-none');
                }
            }
        });

        // Método 1: Al hacer clic en el botón de búsqueda
        if (buscarClienteBtn) {
            buscarClienteBtn.addEventListener('click', function() {
                if (clienteInput.value.trim() !== '') {
                    buscarClienteCompleto(clienteInput.value);
                }
            });
        }

        // Método 2: Al presionar Enter en el campo de búsqueda
        clienteInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    buscarClienteCompleto(this.value);
                }
            }
        });

        // Cerrar sugerencias al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (e.target !== clienteInput && e.target !== sugerenciasContainer) {
                if (sugerenciasContainer) {
                    sugerenciasContainer.classList.add('d-none');
                }
            }
        });
    }
}

/**
 * Busca clientes según el término ingresado para mostrar sugerencias
 * @param {string} termino - Término de búsqueda (nombre o NIT)
 */
function buscarClienteSugerencias(termino) {
    if (!termino || termino.trim() === '') {
        const sugerenciasContainer = document.querySelector('#clientSuggestions');
        if (sugerenciasContainer) {
            sugerenciasContainer.classList.add('d-none');
        }
        return;
    }

    // Obtener datos del usuario actual desde localStorage
    const userData = JSON.parse(localStorage.getItem('userData'));
    if (!userData) {
        console.error('No se encontraron datos de usuario');
        return;
    }

    // Realizar petición al servidor
    fetch('php/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            accion: 'buscarCliente',
            termino: termino,
            idVendedor: userData.id,
            rol: userData.rol // Enviamos el rol para filtrar en el servidor
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }
        
        // Mostrar sugerencias
        if (data.clientes && data.clientes.length > 0) {
            mostrarSugerencias(data.clientes);
        } else {
            const sugerenciasContainer = document.querySelector('#clientSuggestions');
            if (sugerenciasContainer) {
                sugerenciasContainer.classList.add('d-none');
            }
        }
    })
    .catch(error => {
        console.error('Error al buscar sugerencias de clientes:', error);
    });
}

/**
 * Busca el cliente completo cuando se selecciona o confirma la búsqueda
 * @param {string} termino - Término de búsqueda exacto
 */
function buscarClienteCompleto(termino) {
    if (!termino || termino.trim() === '') {
        return;
    }

    // Obtener datos del usuario actual desde localStorage
    const userData = JSON.parse(localStorage.getItem('userData'));
    if (!userData) {
        console.error('No se encontraron datos de usuario');
        return;
    }

    // Realizar petición al servidor
    fetch('php/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            accion: 'buscarCliente',
            termino: termino,
            idVendedor: userData.id,
            rol: userData.rol,
            busquedaExacta: true // Indicamos que es una búsqueda exacta
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        
        // Cerrar sugerencias
        const sugerenciasContainer = document.querySelector('#clientSuggestions');
        if (sugerenciasContainer) {
            sugerenciasContainer.classList.add('d-none');
        }
        
        // Seleccionar cliente si existe
        if (data.clientes && data.clientes.length > 0) {
            seleccionarCliente(data.clientes[0]);
        } else {
            alert('No se encontró el cliente específico');
        }
    })
    .catch(error => {
        console.error('Error al buscar cliente:', error);
        alert('Ocurrió un error al buscar el cliente. Por favor intente nuevamente.');
    });
}

/**
 * Muestra las sugerencias de clientes como un dropdown
 * @param {Array} clientes - Lista de clientes encontrados
 */
function mostrarSugerencias(clientes) {
    // Verificar que el contenedor existe
    const sugerenciasContainer = document.querySelector('#clientSuggestions');
    if (!sugerenciasContainer) return;
    
    // Limpiar contenedor de sugerencias
    sugerenciasContainer.innerHTML = '';
    
    // Mostrar el contenedor
    sugerenciasContainer.classList.remove('d-none');

    // Crear elementos para cada cliente
    clientes.forEach(cliente => {
        const sugerencia = document.createElement('div');
        sugerencia.classList.add('sugerencia-item');
        sugerencia.style.cssText = `
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        `;
        
        // Información del cliente en la sugerencia
        const nombreEmpresa = cliente.empresa || 'Sin nombre de empresa';
        const contacto = cliente.contacto1 || '';
        const nit = cliente.nit ? ` - NIT: ${formatearNIT(cliente.nit)}` : '';
        
        // Crear contenido de la sugerencia
        sugerencia.innerHTML = `
            <div><strong>${nombreEmpresa}</strong>${nit}</div>
            <div style="font-size: 0.9em; color: #555;">${contacto} ${cliente.telefono1 ? '- ' + cliente.telefono1 : ''}</div>
        `;
        
        // Agregar evento al hacer clic en una sugerencia
        sugerencia.addEventListener('click', function() {
            seleccionarCliente(cliente);
            const clienteInput = document.querySelector('#buscarCliente');
            if (clienteInput) {
                clienteInput.value = nombreEmpresa;
            }
            sugerenciasContainer.classList.add('d-none');
        });
        
        // Agregar efecto hover
        sugerencia.addEventListener('mouseover', function() {
            this.style.backgroundColor = '#f0f0f0';
        });
        
        sugerencia.addEventListener('mouseout', function() {
            this.style.backgroundColor = 'white';
        });
        
        // Agregar al contenedor de sugerencias
        sugerenciasContainer.appendChild(sugerencia);
    });
}

/**
 * Selecciona un cliente y muestra su información
 * @param {Object} cliente - Datos del cliente seleccionado
 */
function seleccionarCliente(cliente) {
    // Guardar el cliente seleccionado en una variable global o sessionStorage para uso posterior
    sessionStorage.setItem('clienteSeleccionado', JSON.stringify(cliente));
    
    // Actualizar el campo de búsqueda con el nombre del cliente
    const clienteInput = document.querySelector('#buscarCliente');
    if (clienteInput) {
        clienteInput.value = cliente.empresa || cliente.contacto1;
    }
    
    // Mostrar la información del cliente en la sección correspondiente
    const infoClienteContainer = document.querySelector('#infoCliente');
                                
    if (infoClienteContainer) {
        // Formatear NIT si existe - MANTENER COMO STRING
        const nitFormateado = cliente.nit ? formatearNIT(String(cliente.nit)) : 'N/A';
        
        // Preparar dirección principal
        const direccionPrincipal = cliente.direccion && cliente.direccion.trim() !== '' 
            ? cliente.direccion 
            : 'N/A';
        
        // Preparar dirección alternativa si existe
        const direccionAlternativa = cliente.direccion2 && cliente.direccion2.trim() !== ''
            ? `<p class="mb-2"><strong>Dirección alternativa:</strong> ${cliente.direccion2}</p>`
            : '';
        
        // Actualizar con los datos del cliente
        infoClienteContainer.innerHTML = `
            <div class="cliente-info">
                <p class="mb-2"><strong>Empresa:</strong> ${cliente.empresa || 'N/A'}</p>
                <p class="mb-2"><strong>NIT:</strong> ${nitFormateado}</p>
                <p class="mb-2"><strong>Contacto:</strong> ${cliente.contacto1 || 'N/A'}</p>
                ${cliente.contacto2 ? `<p class="mb-2"><strong>Contacto alternativo:</strong> ${cliente.contacto2}</p>` : ''}
                <p class="mb-2"><strong>Teléfono:</strong> ${cliente.telefono1 || 'N/A'}</p>
                ${cliente.telefono2 ? `<p class="mb-2"><strong>Teléfono alternativo:</strong> ${cliente.telefono2}</p>` : ''}
                <p class="mb-2"><strong>Email:</strong> ${cliente.email || 'N/A'}</p>
                <p class="mb-2"><strong>Dirección:</strong> ${direccionPrincipal}</p>
                ${direccionAlternativa}
                ${cliente.region ? `<p class="mb-2"><strong>Región:</strong> ${cliente.region}</p>` : ''}
            </div>
        `;
    } else {
        console.warn('No se encontró el contenedor para mostrar información del cliente');
    }
}

/**
 * Formatea un NIT para mejor legibilidad manteniendo el tipo string
 * @param {string|number} nit - El NIT a formatear
 * @returns {string} NIT formateado
 */
function formatearNIT(nit) {
    if (!nit) return 'N/A';
    
    // IMPORTANTE: Convertir a string y mantener como string
    const nitStr = String(nit);
    
    // Manejar casos especiales
    if (nitStr === '0' || nitStr.toLowerCase() === 'n/a') {
        return nitStr;
    }
    
    // Si ya tiene guiones o tiene menos de 8 dígitos, devolverlo como está
    if (nitStr.includes('-') || nitStr.length < 8) {
        return nitStr;
    }
    
    // Formatear NIT (formato típico guatemalteco: #######-#)
    const base = nitStr.slice(0, -1);
    const verificador = nitStr.slice(-1);
    
    return `${base}-${verificador}`;
}

/**
 * Inicializa la funcionalidad de búsqueda de productos
 */
function inicializarBusquedaProductos() {
    // Referencias a elementos del DOM para productos
    const productoInput = document.querySelector('#buscarProducto');
    const buscarProductoBtn = document.querySelector('#btnBuscarProducto');
    const sugerenciasProductoContainer = document.querySelector('#productSuggestions');
    const carritoItemsContainer = document.querySelector('#carritoItems');
    const subtotalVentaElement = document.querySelector('#subtotalVenta');
    const descuentoInput = document.querySelector('#descuento');
    const totalVentaElement = document.querySelector('#totalVenta');

    // Verificar que los elementos existen
    if (productoInput && buscarProductoBtn) {
        // Evento para mostrar sugerencias mientras se escribe
        productoInput.addEventListener('input', function() {
            if (this.value.length >= 2) {
                buscarProductoSugerencias(this.value);
            } else {
                if (sugerenciasProductoContainer) {
                    sugerenciasProductoContainer.classList.add('d-none');
                }
            }
        });

        // Evento al hacer clic en el botón de búsqueda
        buscarProductoBtn.addEventListener('click', function() {
            if (productoInput.value.trim() !== '') {
                buscarProductoCompleto(productoInput.value);
            }
        });

        // Evento al presionar Enter en el campo de búsqueda
        productoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim() !== '') {
                    buscarProductoCompleto(this.value);
                }
            }
        });

        // Cerrar sugerencias al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (e.target !== productoInput && e.target !== sugerenciasProductoContainer) {
                if (sugerenciasProductoContainer) {
                    sugerenciasProductoContainer.classList.add('d-none');
                }
            }
        });
    }

    // Evento para actualizar totales cuando se cambia el descuento
    if (descuentoInput) {
        descuentoInput.addEventListener('input', calcularTotales);
    }
}

/**
 * Busca productos según el término ingresado para mostrar sugerencias
 * @param {string} termino - Término de búsqueda (código o nombre)
 */
function buscarProductoSugerencias(termino) {
    const sugerenciasProductoContainer = document.querySelector('#productSuggestions');
    
    if (!termino || termino.trim() === '') {
        if (sugerenciasProductoContainer) {
            sugerenciasProductoContainer.classList.add('d-none');
        }
        return;
    }

    // Realizar petición al servidor
    fetch('php/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            accion: 'buscarProducto',
            termino: termino
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }
        
        // Mostrar sugerencias de productos
        if (data.productos && data.productos.length > 0) {
            mostrarSugerenciasProducto(data.productos);
        } else {
            if (sugerenciasProductoContainer) {
                sugerenciasProductoContainer.classList.add('d-none');
            }
        }
    })
    .catch(error => {
        console.error('Error al buscar sugerencias de productos:', error);
    });
}

// Funciones adicionales para productos vendrán en la siguiente parte...
/**
 * Busca un producto completo cuando se selecciona o confirma la búsqueda
 * @param {string} termino - Término de búsqueda exacto
 */
function buscarProductoCompleto(termino) {
    if (!termino || termino.trim() === '') {
        return;
    }

    // Realizar petición al servidor
    fetch('php/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            accion: 'buscarProducto',
            termino: termino,
            busquedaExacta: true
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        
        // Cerrar sugerencias
        const sugerenciasProductoContainer = document.querySelector('#productSuggestions');
        if (sugerenciasProductoContainer) {
            sugerenciasProductoContainer.classList.add('d-none');
        }
        
        // Seleccionar producto si existe
        if (data.productos && data.productos.length > 0) {
            mostrarModalSeleccionProducto(data.productos[0]);
        } else {
            alert('No se encontró el producto específico');
        }
    })
    .catch(error => {
        console.error('Error al buscar producto:', error);
        alert('Ocurrió un error al buscar el producto. Por favor intente nuevamente.');
    });
}

/**
 * Muestra las sugerencias de productos como un dropdown
 * @param {Array} productos - Lista de productos encontrados
 */
function mostrarSugerenciasProducto(productos) {
    // Verificar que el contenedor existe
    const sugerenciasProductoContainer = document.querySelector('#productSuggestions');
    if (!sugerenciasProductoContainer) return;
    
    // Limpiar contenedor de sugerencias
    sugerenciasProductoContainer.innerHTML = '';
    
    // Mostrar el contenedor
    sugerenciasProductoContainer.classList.remove('d-none');

    // Crear elementos para cada producto
    productos.forEach(producto => {
        const sugerencia = document.createElement('div');
        sugerencia.classList.add('sugerencia-item');
        sugerencia.style.cssText = `
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        `;
        
        // Información del producto en la sugerencia
        const codigoProducto = producto.codigo || '';
        const nombreProducto = producto.nombre || 'Producto sin nombre';
        const precioProducto = formatearPrecio(producto.precio_venta_sugerido);
        const stockProducto = producto.stock || 0;
        
        // Crear contenido de la sugerencia
        sugerencia.innerHTML = `
            <div><strong>${nombreProducto}</strong> (${codigoProducto})</div>
            <div style="font-size: 0.9em; color: #555;">Precio: ${precioProducto} - Stock: ${stockProducto}</div>
        `;
        
        // Agregar evento al hacer clic en una sugerencia
        sugerencia.addEventListener('click', function() {
            mostrarModalSeleccionProducto(producto);
            const productoInput = document.querySelector('#buscarProducto');
            if (productoInput) {
                productoInput.value = '';  // Limpiar el campo después de seleccionar
            }
            sugerenciasProductoContainer.classList.add('d-none');
        });
        
        // Agregar efecto hover
        sugerencia.addEventListener('mouseover', function() {
            this.style.backgroundColor = '#f0f0f0';
        });
        
        sugerencia.addEventListener('mouseout', function() {
            this.style.backgroundColor = 'white';
        });
        
        // Agregar al contenedor de sugerencias
        sugerenciasProductoContainer.appendChild(sugerencia);
    });
}

/**
 * Muestra un diálogo para seleccionar la cantidad del producto antes de agregar al carrito
 * MODIFICADO: Incluye checkbox "Es complemento"
 * @param {Object} producto - Datos del producto seleccionado
 */
function mostrarModalSeleccionProducto(producto) {
    // Crear un elemento modal dinámicamente
    const modalContainer = document.createElement('div');
    modalContainer.classList.add('modal', 'fade');
    modalContainer.id = 'modalSeleccionProducto';
    modalContainer.setAttribute('tabindex', '-1');
    modalContainer.setAttribute('aria-hidden', 'true');
    
    // Stock disponible para mostrar en el modal
    const stockDisponible = producto.stock || 0;
    
    // Verificar si el producto ya está en el carrito para mostrar la cantidad actual
    const productoEnCarrito = carrito.find(item => item.id === producto.id);
    const cantidadActual = productoEnCarrito ? productoEnCarrito.cantidad : 1;
    
    // Preparar la URL de la imagen o una imagen por defecto
    const imagenURL = producto.imagen && producto.imagen !== '' 
        ? `imagenes/productos/${producto.imagen}` 
        : 'imagenes/no-image.png';
    
    // ✅ NUEVO: Precio sugerido original (guardarlo para restablecer)
    const precioOriginal = parseFloat(producto.precio_venta_sugerido);
    
    // Contenido del modal
    modalContainer.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Agregar Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>${producto.nombre}</h5>
                            <p><strong>Código:</strong> ${producto.codigo || 'N/A'}</p>
                            <p><strong>Stock disponible:</strong> ${stockDisponible}</p>
                        </div>
                        <div class="col-md-6 text-center">
                            <h5 class="text-primary" id="precioProductoModal">${formatearPrecio(precioOriginal)}</h5>
                            <div class="product-image-container mt-2" style="width: 100%; height: 150px;">
                                <img src="${imagenURL}" alt="${producto.nombre}" class="img-fluid" style="cursor: pointer; max-height: 100%; max-width: 100%; object-fit: contain;" id="thumbnailProducto" onerror="this.src='imagenes/no-image.png'">
                            </div>
                        </div>
                    </div>
                    
                    <!-- ✅ NUEVO: Checkbox Es Complemento -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="esComplementoCheck">
                                <label class="form-check-label" for="esComplementoCheck">
                                    <i class="fas fa-gift text-success"></i> <strong>Es producto complemento</strong> (Precio Q0.00)
                                </label>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Los productos complemento no suman al total de la venta
                            </small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <label for="cantidadProducto" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidadProducto" value="${cantidadActual}" min="1" max="${stockDisponible}">
                            <div class="invalid-feedback">
                                La cantidad debe ser mayor a 0 y no exceder el stock disponible (${stockDisponible}).
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnAgregarProducto">Agregar al carrito</button>
                </div>
            </div>
        </div>
    `;
    
    // Agregar el modal al DOM
    document.body.appendChild(modalContainer);
    
    // Crear una instancia del modal de Bootstrap
    const modalInstance = new bootstrap.Modal(modalContainer);
    
    // Mostrar el modal
    modalInstance.show();
    
    // ✅ NUEVO: Agregar evento al checkbox de complemento
    const esComplementoCheck = document.getElementById('esComplementoCheck');
    const precioProductoModal = document.getElementById('precioProductoModal');
    
    if (esComplementoCheck && precioProductoModal) {
        esComplementoCheck.addEventListener('change', function() {
            if (this.checked) {
                // Marcado como complemento - precio 0
                precioProductoModal.textContent = 'Q0.00';
                precioProductoModal.classList.add('text-success');
                precioProductoModal.classList.remove('text-primary');
            } else {
                // Desmarcado - precio original
                precioProductoModal.textContent = formatearPrecio(precioOriginal);
                precioProductoModal.classList.remove('text-success');
                precioProductoModal.classList.add('text-primary');
            }
        });
    }
    
    // Agregar evento al botón de agregar al carrito
    const btnAgregarProducto = document.getElementById('btnAgregarProducto');
    if (btnAgregarProducto) {
        btnAgregarProducto.addEventListener('click', function() {
            const cantidadInput = document.getElementById('cantidadProducto');
            const esComplementoCheckbox = document.getElementById('esComplementoCheck');
            
            if (!cantidadInput) return;
            
            const cantidad = parseInt(cantidadInput.value);
            
            // ✅ MODIFICADO: Determinar precio según si es complemento
            const esComplemento = esComplementoCheckbox ? esComplementoCheckbox.checked : false;
            const precio = esComplemento ? 0 : precioOriginal;
            
            // Validar cantidad
            if (isNaN(cantidad) || cantidad <= 0 || cantidad > stockDisponible) {
                cantidadInput.classList.add('is-invalid');
                return;
            }
            
            // ✅ MODIFICADO: Agregar al carrito con el flag es_complemento
            agregarAlCarrito(producto, cantidad, precio, esComplemento);
            
            // Cerrar modal
            modalInstance.hide();
            
            // Eliminar modal del DOM después de cerrarlo
            modalContainer.addEventListener('hidden.bs.modal', function() {
                modalContainer.remove();
            });
        });
    }
    
    // Agregar evento para mostrar imagen en grande al hacer clic
    const thumbnailProducto = document.getElementById('thumbnailProducto');
    if (thumbnailProducto) {
        thumbnailProducto.addEventListener('click', function() {
            // Obtener la URL de la imagen
            const imagenURL = this.src;
            
            // Crear modal para visualización de imagen grande
            const modalImagenGrande = document.createElement('div');
            modalImagenGrande.classList.add('modal', 'fade');
            modalImagenGrande.id = 'modalImagenGrande';
            modalImagenGrande.setAttribute('tabindex', '-1');
            modalImagenGrande.setAttribute('aria-hidden', 'true');
            
            // Contenido del modal de imagen
            modalImagenGrande.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${producto.nombre}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img src="${imagenURL}" alt="${producto.nombre}" class="img-fluid" style="max-height: 80vh;">
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar al DOM
            document.body.appendChild(modalImagenGrande);
            
            // Crear instancia del modal
            const modalImagenInstance = new bootstrap.Modal(modalImagenGrande);
            
            // Mostrar el modal
            modalImagenInstance.show();
            
            // Limpiar DOM al cerrar
            modalImagenGrande.addEventListener('hidden.bs.modal', function() {
                modalImagenGrande.remove();
            });
        });
    }
    
    // Limpiar modal al cerrarse
    modalContainer.addEventListener('hidden.bs.modal', function() {
        modalContainer.remove();
    });
}
/**
 * Agrega un producto al carrito o actualiza su cantidad si ya existe
 * MODIFICADO: Soporta productos complemento
 * @param {Object} producto - Datos del producto
 * @param {number} cantidad - Cantidad a agregar
 * @param {number} precio - Precio unitario
 * @param {boolean} esComplemento - Indica si es producto complemento (nuevo parámetro)
 */
function agregarAlCarrito(producto, cantidad, precio, esComplemento = false) {
    // ✅ MODIFICADO: Buscar por id Y es_complemento para permitir mismo producto como normal y complemento
    const index = carrito.findIndex(item => 
        item.id === producto.id && item.es_complemento === (esComplemento ? 1 : 0)
    );
    
    if (index !== -1) {
        // Actualizar cantidad y precio si ya existe con el mismo tipo
        carrito[index].cantidad = cantidad;
        carrito[index].precio = precio;
    } else {
        // ✅ MODIFICADO: Agregar nuevo producto al carrito con es_complemento
        carrito.push({
            id: producto.id,
            codigo: producto.codigo,
            nombre: producto.nombre,
            cantidad: cantidad,
            precio: precio,
            precio_venta_real: parseFloat(producto.precio_venta_real) || 0, // Guardar el precio mínimo
            stock: producto.stock,
            imagen: producto.imagen,
            es_complemento: esComplemento ? 1 : 0  // ✅ NUEVO CAMPO
        });
    }
    
    // Actualizar la visualización del carrito
    actualizarCarritoUI();
    
    // Calcular totales
    calcularTotales();
}

/**
 * Actualiza la interfaz del carrito con los productos seleccionados
 */
function actualizarCarritoUI() {
    const carritoItemsContainer = document.querySelector('#carritoItems');
    if (!carritoItemsContainer) return;
    
    // Limpiar contenedor del carrito
    carritoItemsContainer.innerHTML = '';
    
    // Verificar si el carrito está vacío
    if (carrito.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = `
            <td colspan="5" class="text-center text-muted">No hay productos en el carrito</td>
        `;
        carritoItemsContainer.appendChild(emptyRow);
        return;
    }
    
    // Agregar cada producto al carrito
    carrito.forEach((item, index) => {
        const fila = document.createElement('tr');
        
        const subtotalItem = item.cantidad * item.precio;
        const precioMinimo = parseFloat(item.precio_venta_real) || 0;
        
        // Preparar la URL de la imagen o una imagen por defecto
        const imagenURL = item.imagen && item.imagen !== '' 
            ? `imagenes/productos/${item.imagen}` 
            : 'imagenes/no-image.png';
        
            // ✅ MODIFICADO: Agregar indicador visual para productos complemento
        const esComplemento = item.es_complemento === 1;
        const badgeComplemento = esComplemento 
            ? '<span class="badge bg-success ms-2"><i class="fas fa-gift"></i> Complemento</span>' 
            : '';
        
        // ✅ MODIFICADO: Fondo verde claro para productos complemento
        const estiloFila = esComplemento ? 'background-color: #d4edda;' : '';
        
        fila.setAttribute('style', estiloFila);
        
        fila.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <img src="${imagenURL}" alt="${item.nombre}" class="product-mini-thumbnail me-2" style="width: 40px; height: 40px; object-fit: contain; cursor: pointer;" onerror="this.src='imagenes/no-image.png'">
                    <div>
                        <div>${item.nombre}${badgeComplemento}</div>
                        <small class="text-muted">${item.codigo}</small>
                    </div>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <button class="btn btn-outline-secondary btn-sm btn-cantidad-menos" data-index="${index}">-</button>
                    <input type="number" class="form-control form-control-sm text-center cantidad-producto" value="${item.cantidad}" min="1" max="${item.stock}" data-index="${index}">
                    <button class="btn btn-outline-secondary btn-sm btn-cantidad-mas" data-index="${index}">+</button>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">Q</span>
                    <input type="number" class="form-control form-control-sm precio-producto" value="${item.precio.toFixed(2)}" step="0.01" min="${esComplemento ? 0 : precioMinimo}" data-index="${index}" ${esComplemento ? 'readonly style="background-color: #e9ecef;"' : ''}>
                </div>
                ${esComplemento ? '<small class="text-success"><i class="fas fa-info-circle"></i> Precio fijo Q0.00</small>' : ''}
            </td>
            <td>Q${subtotalItem.toFixed(2)}</td>
            <td>
                <button class="btn btn-danger btn-sm btn-eliminar-producto" data-index="${index}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        
        carritoItemsContainer.appendChild(fila);
    });
    
    // Agregar eventos a los botones y campos del carrito
    agregarEventosCarrito();
    
    // Agregar eventos para expandir imágenes
    agregarEventosImagenesCarrito();
}

/**
 * Agrega eventos a los elementos del carrito (botones de cantidad, eliminar, etc.)
 */
function agregarEventosCarrito() {
    // Botones para disminuir cantidad
    const botonesMenos = document.querySelectorAll('.btn-cantidad-menos');
    botonesMenos.forEach(boton => {
        boton.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            if (carrito[index].cantidad > 1) {
                carrito[index].cantidad--;
                actualizarCarritoUI();
                calcularTotales();
            }
        });
    });
    
    // Botones para aumentar cantidad
    const botonesMas = document.querySelectorAll('.btn-cantidad-mas');
    botonesMas.forEach(boton => {
        boton.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            if (carrito[index].cantidad < carrito[index].stock) {
                carrito[index].cantidad++;
                actualizarCarritoUI();
                calcularTotales();
            }
        });
    });
    
    // Campos de cantidad
    const camposCantidad = document.querySelectorAll('.cantidad-producto');
    camposCantidad.forEach(campo => {
        campo.addEventListener('change', function() {
            const index = parseInt(this.getAttribute('data-index'));
            let cantidad = parseInt(this.value);
            
            // Validar que la cantidad sea válida
            if (isNaN(cantidad) || cantidad < 1) {
                cantidad = 1;
            } else if (cantidad > carrito[index].stock) {
                cantidad = carrito[index].stock;
            }
            
            carrito[index].cantidad = cantidad;
            actualizarCarritoUI();
            calcularTotales();
        });
    });
    
    // Campos de precio
    const camposPrecio = document.querySelectorAll('.precio-producto');
    camposPrecio.forEach(campo => {
        campo.addEventListener('change', function() {
            const index = parseInt(this.getAttribute('data-index'));
            let precio = parseFloat(this.value);
            
            // Obtener el precio mínimo del producto (precio_venta_real)
            const precioMinimo = parseFloat(carrito[index].precio_venta_real) || 0;
            
            // Validar que el precio no sea menor que precio_venta_real
            if (isNaN(precio) || precio < precioMinimo) {
                // Mostrar alerta al usuario
                alert(`El precio no puede ser menor a Q${precioMinimo.toFixed(2)}`);
                
                // Restablecer el valor al precio mínimo
                precio = precioMinimo;
                this.value = precioMinimo.toFixed(2);
            }
            
            // Actualizar precio en el carrito
            carrito[index].precio = precio;
            
            // Actualizar UI y totales
            actualizarCarritoUI();
            calcularTotales();
        });
    });
    
    // Botones para eliminar productos
    const botonesEliminar = document.querySelectorAll('.btn-eliminar-producto');
    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function() {
            const index = parseInt(this.getAttribute('data-index'));
            carrito.splice(index, 1);
            actualizarCarritoUI();
            calcularTotales();
        });
    });
}

/**
 * Agrega eventos a las imágenes de productos en el carrito
 */
function agregarEventosImagenesCarrito() {
    const imagenesMiniatura = document.querySelectorAll('.product-mini-thumbnail');
    
    imagenesMiniatura.forEach(imagen => {
        imagen.addEventListener('click', function() {
            // Obtener la URL de la imagen
            const imagenURL = this.src;
            const nombreProducto = this.closest('tr').querySelector('div > div').textContent.trim();
            
            // Crear modal para visualización de imagen grande
            const modalImagenGrande = document.createElement('div');
            modalImagenGrande.classList.add('modal', 'fade');
            modalImagenGrande.id = 'modalImagenGrande';
            modalImagenGrande.setAttribute('tabindex', '-1');
            modalImagenGrande.setAttribute('aria-hidden', 'true');
            
            // Contenido del modal de imagen
            modalImagenGrande.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${nombreProducto}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center p-0">
                            <img src="${imagenURL}" alt="${nombreProducto}" class="img-fluid" style="max-height: 80vh;">
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar al DOM
            document.body.appendChild(modalImagenGrande);
            
            // Crear instancia del modal
            const modalImagenInstance = new bootstrap.Modal(modalImagenGrande);
            
            // Mostrar el modal
            modalImagenInstance.show();
            
            // Limpiar DOM al cerrar
            modalImagenGrande.addEventListener('hidden.bs.modal', function() {
                modalImagenGrande.remove();
            });
        });
    });
}
/**
 * Calcula los totales de la venta (subtotal, descuento, total)
 * MODIFICADO: Excluye productos complemento del subtotal
 */
function calcularTotales() {
    let subtotal = 0;
    
    // Elementos del DOM para los totales
    const subtotalVentaElement = document.querySelector('#subtotalVenta');
    const descuentoInput = document.querySelector('#descuento');
    const totalVentaElement = document.querySelector('#totalVenta');
    
    // ✅ MODIFICADO: Sumar subtotales SOLO de productos NO complemento
    carrito.forEach(item => {
        // Solo sumar al subtotal si NO es complemento
        if (item.es_complemento !== 1) {
            subtotal += item.cantidad * item.precio;
        }
    });
    
    // Obtener descuento
    const descuento = descuentoInput && !isNaN(parseFloat(descuentoInput.value)) 
        ? parseFloat(descuentoInput.value) 
        : 0;
    
    // Calcular total
    const total = Math.max(0, subtotal - descuento);
    
    // Actualizar UI
    if (subtotalVentaElement) {
        subtotalVentaElement.textContent = `Q${subtotal.toFixed(2)}`;
    }
    
    if (totalVentaElement) {
        totalVentaElement.textContent = `Q${total.toFixed(2)}`;
    }
}

/**
 * Finaliza la venta actual
 */
function finalizarVenta() {
    // Verificar que hay productos en el carrito
    if (carrito.length === 0) {
        alert('No hay productos en el carrito. Agregue productos para finalizar la venta.');
        return;
    }
    
    // Verificar que hay un cliente seleccionado
    const clienteSeleccionado = sessionStorage.getItem('clienteSeleccionado');
    if (!clienteSeleccionado) {
        alert('Debe seleccionar un cliente para finalizar la venta.');
        return;
    }
    
    // Obtener elementos del DOM con verificación
    const tipoVentaElement = document.querySelector('#tipoVenta');
    const fechaElement = document.querySelector('#fechaVenta');
    const descuentoElement = document.querySelector('#descuento');
    const dteElement = document.querySelector('#dteVenta');
    const ordenCompraElement = document.querySelector('#ordenCompra');
    
    // Verificar que los elementos existen
    if (!tipoVentaElement) {
        console.error('No se encontró el elemento #tipoVenta');
        alert('Error al finalizar venta: Elemento de tipo de venta no encontrado.');
        return;
    }
    
    if (!fechaElement) {
        console.error('No se encontró el elemento #fechaVenta');
        alert('Error al finalizar venta: Elemento de fecha no encontrado.');
        return;
    }
    
    // Obtener valores con seguridad
    const tipoVenta = tipoVentaElement.value;
    const fecha = fechaElement.value;
    const descuento = descuentoElement ? parseFloat(descuentoElement.value) || 0 : 0;
    const dte = dteElement ? dteElement.value.trim() : '';
    const ordenCompra = ordenCompraElement ? ordenCompraElement.value.trim() : '';
    
      
    // Armar datos de la venta
    // Obtener datos del usuario primero
const userData = JSON.parse(localStorage.getItem('userData'));
if (!userData) {
    alert('Error: No se pudieron obtener los datos del usuario. Por favor, inicie sesión nuevamente.');
    return;
}

// Armar datos de la venta
const cliente = JSON.parse(clienteSeleccionado);

// Obtener datos adicionales para ventas a crédito
let datosCredito = null;
if (tipoVenta.toLowerCase() === 'credito') {
    const anticipoElement = document.querySelector('#anticipo');
    const plazoPagoElement = document.querySelector('#plazoPago');
    
    if (!anticipoElement || !plazoPagoElement) {
        alert('Error: No se encontraron los campos necesarios para venta a crédito.');
        return;
    }
    
    const anticipo = parseFloat(anticipoElement.value) || 0;
    const plazoPago = parseInt(plazoPagoElement.value) || 1;
    
    datosCredito = {
        anticipo: anticipo,
        plazos: plazoPago,
        usuario_id: userData.id // Ahora es seguro usar userData.id
    };
}

// Continuar con el resto del código de la función finalizarVenta()
const ventaData = {
    accion: 'registrarVenta',
    cliente_id: cliente.id,
    usuario_id: userData.id,
    fecha: fecha,
    tipo_venta: tipoVenta,
    descuento: descuento,
    dte: dte,
    ordenCompra: ordenCompra,
    credito: datosCredito,
    productos: carrito.map(item => ({
        id: item.id,
        cantidad: item.cantidad,
        precio: item.precio,
        es_complemento: item.es_complemento || 0  // ✅ NUEVO CAMPO
    }))
};
    
    // Confirmar con el usuario
    if (confirm('¿Está seguro que desea finalizar la venta?')) {
        // Mostrar indicador de carga
        mostrarCargando(true);
        
        // Enviar datos al servidor
        fetch('php/ventas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(ventaData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
    // Ocultar indicador de carga
    mostrarCargando(false);
    
    if (data.success) {
        // Venta registrada con éxito
        console.log("Venta registrada. Código:", data.codigo);
        console.log("Datos completos de la respuesta:", data);
        
        alert(`Venta registrada con éxito. Código: ${data.codigo}`);
        
        // Generar y guardar el PDF automáticamente
        fetch(`php/comprobante.php?id=${data.venta_id}&guardarPDF=1`)
            .then(response => {
                if (!response.ok) {
                    console.error('Error en la respuesta del servidor');
                    return null;
                }
                return response.json();
            })
            .then(pdfData => {
                if (pdfData && pdfData.success) {
                    console.log(`PDF guardado en: ${pdfData.ruta}`);
                } else {
                    console.error('Error al generar PDF:', pdfData ? pdfData.error : 'Respuesta no válida');
                }
            })
            .catch(error => {
                console.error('Error al generar PDF:', error);
            });    
        // Preguntar si desea imprimir comprobante
        if (confirm('¿Desea imprimir el comprobante de venta?')) {
            imprimirComprobante(data.venta_id);
        }
        
        // Limpiar el carrito y la interfaz
        carrito = [];
        actualizarCarritoUI();
        calcularTotales();
        
        // Limpiar cliente seleccionado
        sessionStorage.removeItem('clienteSeleccionado');
        const infoClienteContainer = document.querySelector('#infoCliente');
        if (infoClienteContainer) {
            infoClienteContainer.innerHTML = '<p class="text-center text-muted">Seleccione un cliente para ver su información</p>';
        }
        
        const clienteInput = document.querySelector('#buscarCliente');
        if (clienteInput) {
            clienteInput.value = '';
        }
        
        // Restablecer tipo de venta a crédito (valor predefinido)
        const tipoVentaSelect = document.querySelector('#tipoVenta');
        if (tipoVentaSelect) {
            tipoVentaSelect.value = 'credito';
            
            // Actualizar opciones de crédito
            const opcionesCreditoContainer = document.querySelector('#opcionesCredito');
            if (opcionesCreditoContainer) {
                opcionesCreditoContainer.classList.remove('d-none');
            }
        }
        
        // Restablecer descuento
        const descuentoInput = document.querySelector('#descuento');
        if (descuentoInput) {
            descuentoInput.value = '0';
        }
        const dteInput = document.querySelector('#dteVenta');
        if (dteInput) {
            dteInput.value = '';
        }
        const ordenCompraInput = document.querySelector('#ordenCompra');
if (ordenCompraInput) {
    ordenCompraInput.value = '';
}
        } else {
            // Error al registrar venta
            alert(`Error al registrar la venta: ${data.error}`);
        }
})
        .catch(error => {
            // Ocultar indicador de carga
            mostrarCargando(false);
            
            console.error('Error al finalizar venta:', error);
            alert('Ocurrió un error al finalizar la venta. Por favor intente nuevamente.');
        });
    }
}

/**
 * Muestra u oculta el indicador de carga
 * @param {boolean} mostrar - Indica si se debe mostrar el indicador
 * @param {string} mensaje - Mensaje personalizado a mostrar
 */
function mostrarCargando(mostrar, mensaje = 'Procesando...') {
    // Verificar si ya existe el elemento
    let cargandoElement = document.getElementById('indicadorCargando');
    
    if (mostrar) {
        if (!cargandoElement) {
            // Crear elemento de carga
            cargandoElement = document.createElement('div');
            cargandoElement.id = 'indicadorCargando';
            cargandoElement.classList.add('overlay-cargando');
            
            // Estilos para el overlay
            cargandoElement.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(255, 255, 255, 0.9);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            `;
            
            // Agregar al DOM
            document.body.appendChild(cargandoElement);
        }
        
        // Actualizar contenido con mensaje personalizado
        cargandoElement.innerHTML = `
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3 fw-bold">${mensaje}</p>
        `;
        
        cargandoElement.style.display = 'flex';
    } else if (cargandoElement) {
        cargandoElement.style.display = 'none';
    }
}

/**
 * Imprime un comprobante de venta
 * @param {number} venta_id - ID de la venta
 */
function imprimirComprobante(venta_id) {
    // Abrir ventana para impresión
    window.open(`php/comprobante.php?id=${venta_id}`, '_blank', 'width=800,height=600');
}

/**
 * Formatea un precio para mostrar en la interfaz
 * @param {number|string} precio - Precio a formatear
 * @returns {string} Precio formateado con Q y dos decimales
 */
function formatearPrecio(precio) {
    const precioNum = parseFloat(precio);
    if (isNaN(precioNum)) return 'Q0.00';
    return `Q${precioNum.toFixed(2)}`;
}

/**
 * Inicializa la funcionalidad del historial de ventas
 * Carga los datos y configura los eventos de filtrado
 */
function inicializarHistorialVentas() {
    // Referenciar elementos del historial
    const btnBuscar = document.getElementById('btnBuscar');
    const tipoVentaBusqueda = document.getElementById('tipoVentaBusqueda');
    const clienteBusqueda = document.getElementById('clienteBusqueda');
    const fechaInicioBusqueda = document.getElementById('fechaInicioBusqueda');
const fechaFinBusqueda = document.getElementById('fechaFinBusqueda');
const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    const btnExportar = document.getElementById('btnExportar');
    
    // Verificar si ya se inicializó la tabla DataTable
    if (tablaVentas) {
        // Si ya existe, recargar con los filtros actuales
        cargarDatosHistorial();
    } else {
        // Inicializar tabla al cambiar a la pestaña de historial
        inicializarTablaVentas();
        
        // Configurar eventos de búsqueda manual (botón)
        if (btnBuscar) {
            btnBuscar.addEventListener('click', function() {
                cargarDatosHistorial();
            });
        }
        
        // Evento para exportar
        if (btnExportar) {
            btnExportar.addEventListener('click', exportarHistorial);
        }
        
        // Cargar datos iniciales
        cargarDatosHistorial();
    }
    
    // Configurar búsqueda dinámica en tiempo real
    // Solo agregar eventos si no están ya configurados
    if (tipoVentaBusqueda && !tipoVentaBusqueda.hasAttribute('data-dynamic-configured')) {
        tipoVentaBusqueda.addEventListener('change', function() {
            cargarDatosHistorial();
        });
        tipoVentaBusqueda.setAttribute('data-dynamic-configured', 'true');
    }

    if (clienteBusqueda && !clienteBusqueda.hasAttribute('data-dynamic-configured')) {
        // Búsqueda con delay para evitar muchas peticiones
        let timeoutId;
        clienteBusqueda.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                cargarDatosHistorial();
            }, 500); // Esperar 500ms después de que el usuario deje de escribir
        });
        clienteBusqueda.setAttribute('data-dynamic-configured', 'true');
    }

    if (fechaInicioBusqueda && !fechaInicioBusqueda.hasAttribute('data-dynamic-configured')) {
    fechaInicioBusqueda.addEventListener('change', function() {
        validarRangoFechas();
        cargarDatosHistorial();
    });
    fechaInicioBusqueda.setAttribute('data-dynamic-configured', 'true');
}

if (fechaFinBusqueda && !fechaFinBusqueda.hasAttribute('data-dynamic-configured')) {
    fechaFinBusqueda.addEventListener('change', function() {
        validarRangoFechas();
        cargarDatosHistorial();
    });
    fechaFinBusqueda.setAttribute('data-dynamic-configured', 'true');
}

// Evento para limpiar filtros
if (btnLimpiarFiltros) {
    btnLimpiarFiltros.addEventListener('click', function() {
        limpiarFiltrosHistorial();
    });
}
    
    // Agregar evento Enter en el campo de cliente para búsqueda inmediata
    if (clienteBusqueda && !clienteBusqueda.hasAttribute('data-enter-configured')) {
        clienteBusqueda.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cargarDatosHistorial();
            }
        });
        clienteBusqueda.setAttribute('data-enter-configured', 'true');
    }
}

/**
 * Inicializa la tabla DataTable para las ventas
 */
function inicializarTablaVentas() {
    const tabla = document.getElementById('tablaVentas');
    if (!tabla) return;
    
    // Destruir instancia previa si existe
    if (tablaVentas) {
        tablaVentas.destroy();
    }
    
    // Inicializar DataTable con configuración personalizada
    tablaVentas = new DataTable('#tablaVentas', {
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                titleAttr: 'Exportar a PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Excel',
                titleAttr: 'Exportar a Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> Imprimir',
                titleAttr: 'Imprimir',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ],
        columns: [
            { data: 'codigo' },
            { data: 'fecha' },
            { data: 'cliente' },
            { data: 'vendedor' },
            { 
                data: 'tipo_venta',
                render: function(data) {
                    if (data === 'credito') {
                        return '<span class="badge bg-warning">Crédito</span>';
                    } else {
                        return '<span class="badge bg-success">Contado</span>';
                    }
                }
            },
            { 
                data: 'total',
                render: function(data) {
                    return `Q${parseFloat(data).toFixed(2)}`;
                }
            },
            { 
                data: 'estado',
                render: function(data) {
                    if (parseInt(data) === 1) {
                        return '<span class="badge bg-success">Activa</span>';
                    } else {
                        return '<span class="badge bg-danger">Anulada</span>';
                    }
                }
            },
            { 
                data: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-ver-detalle" data-id="${data}" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success btn-imprimir" data-id="${data}" title="Imprimir">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'desc']], // Ordenar por fecha descendente
        createdRow: function(row, data) {
            // Agregar clase a las filas según el tipo de venta
            if (data.tipo_venta === 'credito') {
                $(row).addClass('table-warning-light');
            }
        }
    });
    
    // Agregar eventos para los botones de acciones
    $('#tablaVentas tbody').on('click', '.btn-ver-detalle', function() {
        const id = $(this).data('id');
        mostrarDetalleVenta(id);
    });
    
    $('#tablaVentas tbody').on('click', '.btn-imprimir', function() {
        const id = $(this).data('id');
        imprimirComprobante(id);
    });
}

/**
 * Carga los datos del historial de ventas aplicando los filtros
 */
function cargarDatosHistorial() {
    // Obtener valores de los filtros
    const tipoVenta = document.getElementById('tipoVentaBusqueda')?.value || '';
    const clienteTermino = document.getElementById('clienteBusqueda')?.value || '';
    const fechaInicio = document.getElementById('fechaInicioBusqueda')?.value || '';
const fechaFin = document.getElementById('fechaFinBusqueda')?.value || '';
    
    // Obtener datos del usuario actual
    const userData = JSON.parse(localStorage.getItem('userData'));
    if (!userData) {
        console.error('No se encontraron datos de usuario');
        return;
    }
    
    // Mostrar indicador de carga
    mostrarCargandoHistorial(true);
    
    // Realizar petición al servidor
    fetch('php/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
    accion: 'obtenerHistorialVentas',
    tipo_venta: tipoVenta,
    cliente: clienteTermino,
    fecha_inicio: fechaInicio,
    fecha_fin: fechaFin,
    usuario_id: userData.id,
    rol: userData.rol
})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error(data.error);
            mostrarCargandoHistorial(false);
            return;
        }
        
        // Almacenar los datos recibidos
        datosVentas = data.ventas || [];
        
        // Actualizar la tabla de ventas
        if (tablaVentas) {
            tablaVentas.clear().rows.add(datosVentas).draw();
        }
        
        // Ocultar indicador de carga
        mostrarCargandoHistorial(false);
    })
    .catch(error => {
        console.error('Error al cargar historial de ventas:', error);
        alert('Ocurrió un error al cargar el historial. Por favor intente nuevamente.');
        mostrarCargandoHistorial(false);
    });
}

/**
 * Muestra u oculta indicador de carga para el historial
 * @param {boolean} mostrar - Indica si se debe mostrar el indicador
 */
function mostrarCargandoHistorial(mostrar) {
    const tablaContainer = document.querySelector('.table-responsive');
    
    if (!tablaContainer) return;
    
    // Verificar si ya existe el indicador
    let spinnerElement = document.getElementById('spinnerHistorial');
    
    if (mostrar) {
        if (!spinnerElement) {
            // Crear elemento de carga
            spinnerElement = document.createElement('div');
            spinnerElement.id = 'spinnerHistorial';
            spinnerElement.classList.add('text-center', 'my-4');
            spinnerElement.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2">Cargando datos del historial...</p>
            `;
            
            // Insertar antes de la tabla
            tablaContainer.insertBefore(spinnerElement, tablaContainer.firstChild);
        } else {
            spinnerElement.style.display = 'block';
        }
        
        // Ocultar la tabla mientras carga
        const tabla = document.getElementById('tablaVentas');
        if (tabla) {
            tabla.style.opacity = '0.5';
        }
    } else if (spinnerElement) {
        // Ocultar spinner y mostrar tabla
        spinnerElement.style.display = 'none';
        
        const tabla = document.getElementById('tablaVentas');
        if (tabla) {
            tabla.style.opacity = '1';
        }
    }
}

/**
 * Muestra el detalle de una venta específica
 * @param {number} ventaId - ID de la venta
 */
function mostrarDetalleVenta(ventaId) {
    // Verificar que existe un ID válido
    if (!ventaId) return;
    
    // Realizar petición al servidor para obtener detalles
    fetch('php/ventas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            accion: 'obtenerDetalleVenta',
            venta_id: ventaId
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            console.error(data.error);
            alert('Error al obtener detalle de la venta: ' + data.error);
            return;
        }
        
        // Mostrar modal con el detalle
        mostrarModalDetalleVenta(data);
    })
    .catch(error => {
        console.error('Error al obtener detalle de la venta:', error);
        alert('Ocurrió un error al cargar el detalle. Por favor intente nuevamente.');
    });
}

/**
 * Muestra un modal con el detalle de la venta
 * @param {Object} datosVenta - Datos completos de la venta y sus detalles
 */
function mostrarModalDetalleVenta(datosVenta) {
    // Obtener información principal
    const venta = datosVenta.venta || {};
    const detalles = datosVenta.detalles || [];
    const credito = datosVenta.credito || null;
    
    // Verificar que existe el modal
    const modalDetalleVenta = document.getElementById('modalDetalleVenta');
    
    if (modalDetalleVenta) {
        // Llenar información principal de la venta
        const infoVentaDetalle = document.getElementById('infoVentaDetalle');
        if (infoVentaDetalle) {
            infoVentaDetalle.innerHTML = `
                <p><strong>No. Factura:</strong> ${venta.codigo || 'N/A'}</p>
                <p><strong>Fecha:</strong> ${formatearFecha(venta.fecha) || 'N/A'}</p>
                <p><strong>Cliente:</strong> ${venta.cliente || 'N/A'}</p>
                <p><strong>Vendedor:</strong> ${venta.vendedor || 'N/A'}</p>
                <p><strong>Tipo de Venta:</strong> ${venta.tipo_venta === 'credito' ? 'Crédito' : 'Contado'}</p>
            `;
        }
        
        // Llenar información de totales
        const totalesVentaDetalle = document.getElementById('totalesVentaDetalle');
        if (totalesVentaDetalle) {
            // Preparar información de crédito si existe
            let infoCredito = '';
            if (venta.tipo_venta === 'credito' && credito) {
                infoCredito = `
                    <p><strong>Anticipo:</strong> Q${parseFloat(credito.anticipo).toFixed(2)}</p>
                    <p><strong>Saldo pendiente:</strong> Q${parseFloat(credito.saldo_pendiente).toFixed(2)}</p>
                    <p><strong>Plazos:</strong> ${credito.plazos}</p>
                `;
            }
            
            totalesVentaDetalle.innerHTML = `
                <p><strong>Subtotal:</strong> Q${parseFloat(venta.subtotal).toFixed(2)}</p>
                <p><strong>Descuento:</strong> Q${parseFloat(venta.descuento).toFixed(2)}</p>
                <p><strong>Total:</strong> Q${parseFloat(venta.total).toFixed(2)}</p>
                ${infoCredito}
            `;
        }
        
        // Llenar tabla de productos
        const productosVentaDetalle = document.getElementById('productosVentaDetalle');
        if (productosVentaDetalle) {
            productosVentaDetalle.innerHTML = ''; // Limpiar tabla
            
            // Agregar cada producto
            detalles.forEach(detalle => {
                const fila = document.createElement('tr');
                fila.innerHTML = `
                    <td>${detalle.codigo || 'N/A'}</td>
                    <td>${detalle.nombre || 'N/A'}</td>
                    <td>${detalle.cantidad}</td>
                    <td>Q${parseFloat(detalle.precio_unitario).toFixed(2)}</td>
                    <td>Q${parseFloat(detalle.subtotal).toFixed(2)}</td>
                `;
                productosVentaDetalle.appendChild(fila);
            });
        }
        
        // Configurar botón de impresión
        const btnImprimirDetalleVenta = document.getElementById('btnImprimirDetalleVenta');
        if (btnImprimirDetalleVenta) {
            btnImprimirDetalleVenta.onclick = function() {
                imprimirComprobante(venta.id);
            };
        }
        
        // Mostrar el modal
        const modalInstance = new bootstrap.Modal(modalDetalleVenta);
        modalInstance.show();
    }
}

/**
 * Exporta el historial de ventas actual a PDF
 */
function exportarHistorial() {
    // Verificar que hay datos para exportar
    if (!datosVentas || datosVentas.length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    
    // Obtener valores de los filtros actuales
    const tipoVenta = document.getElementById('tipoVentaBusqueda')?.value || '';
    const clienteTermino = document.getElementById('clienteBusqueda')?.value || '';
    const fechaInicio = document.getElementById('fechaInicioBusqueda')?.value || '';
const fechaFin = document.getElementById('fechaFinBusqueda')?.value || '';
    
    // Obtener datos del usuario actual
    const userData = JSON.parse(localStorage.getItem('userData'));
    if (!userData) {
        alert('Error: No se pudieron obtener los datos del usuario');
        return;
    }
    
    // Mostrar indicador de carga
    mostrarCargando(true, 'Generando reporte PDF...');
    
    // Preparar datos para enviar
    const exportData = {
    accion: 'exportarHistorialPDF',
    filtros: {
        tipo_venta: tipoVenta,
        cliente: clienteTermino,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        usuario_id: userData.id,
        rol: userData.rol
    },
    ventas: datosVentas
};
    
    // Realizar petición al servidor
    fetch('exportar_historial.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(exportData)
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
        const tipoTexto = tipoVenta ? `_${tipoVenta}` : '';
        let fechaTexto = '';
if (fechaInicio && fechaFin) {
    fechaTexto = `_${fechaInicio}_a_${fechaFin}`;
} else if (fechaInicio) {
    fechaTexto = `_desde_${fechaInicio}`;
} else if (fechaFin) {
    fechaTexto = `_hasta_${fechaFin}`;
}
        
        a.download = `historial_ventas${tipoTexto}${fechaTexto}_${fechaActual}.pdf`;
        
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        // Ocultar indicador de carga
        mostrarCargando(false);
        
        // Mostrar mensaje de éxito
        alert('Reporte PDF generado y descargado con éxito');
    })
    .catch(error => {
        console.error('Error al exportar historial:', error);
        mostrarCargando(false);
        alert('Ocurrió un error al generar el reporte. Por favor intente nuevamente.');
    });
}

/**
 * Formatea una fecha para mostrar en la interfaz
 * @param {string} fecha - Fecha en formato ISO o similar
 * @returns {string} Fecha formateada dd/mm/yyyy
 */
function formatearFecha(fecha) {
    if (!fecha) return '';
    
    // Crear objeto Date
    const date = new Date(fecha);
    
    // Verificar si es una fecha válida
    if (isNaN(date.getTime())) {
        return fecha; // Devolver el valor original si no es válido
    }
    
    // Formatear como dd/mm/yyyy
    const dia = date.getDate().toString().padStart(2, '0');
    const mes = (date.getMonth() + 1).toString().padStart(2, '0');
    const anio = date.getFullYear();
    
    return `${dia}/${mes}/${anio}`;
}

/**
 * Valida que el rango de fechas sea coherente
 */
function validarRangoFechas() {
    const fechaInicio = document.getElementById('fechaInicioBusqueda');
    const fechaFin = document.getElementById('fechaFinBusqueda');
    
    if (!fechaInicio || !fechaFin) return;
    
    const fechaInicioVal = fechaInicio.value;
    const fechaFinVal = fechaFin.value;
    
    if (fechaInicioVal && fechaFinVal) {
        const dateInicio = new Date(fechaInicioVal);
        const dateFin = new Date(fechaFinVal);
        
        if (dateInicio > dateFin) {
            alert('La fecha de inicio no puede ser mayor que la fecha de fin.');
            fechaFin.value = '';
            fechaFin.focus();
        }
    }
}

/**
 * Limpia todos los filtros del historial
 */
function limpiarFiltrosHistorial() {
    const tipoVentaBusqueda = document.getElementById('tipoVentaBusqueda');
    const clienteBusqueda = document.getElementById('clienteBusqueda');
    const fechaInicioBusqueda = document.getElementById('fechaInicioBusqueda');
    const fechaFinBusqueda = document.getElementById('fechaFinBusqueda');
    
    if (tipoVentaBusqueda) tipoVentaBusqueda.value = '';
    if (clienteBusqueda) clienteBusqueda.value = '';
    if (fechaInicioBusqueda) fechaInicioBusqueda.value = '';
    if (fechaFinBusqueda) fechaFinBusqueda.value = '';
    
    cargarDatosHistorial();
}
