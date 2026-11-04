document.addEventListener('DOMContentLoaded', function () {

    // Verificar sesión y permisos requeridos para ventas
     initAuth('productos');

    const nombreInput = document.getElementById('nombreProducto');
    const codigoInput = document.getElementById('codigo');
    const btnGuardar = document.getElementById('btnGuardarProducto');
    const form = document.getElementById('formProducto');

    const tbodyProductos = document.getElementById('tbodyProductos');
    // Modal de edición de producto
    const modalEditarProducto = document.getElementById('modalEditarProducto');
    const modalEditarProductoInstance = new bootstrap.Modal(modalEditarProducto);

    // Evento al abrir el modal: carga datos    
    modalEditarProducto.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    cargarProducto(id);
    });

    // Evento sobre la tabla para detectar clic en botón editar
    tbodyProductos.addEventListener('click', function (e) {
    if (e.target.closest('button')?.classList.contains('btn-primary')) {
        const id = e.target.closest('button').dataset.id;
        cargarProducto(id);
    }
    });

    const modalLlenarStock = document.getElementById('modalLlenarStock');
const modalLlenarStockInstance = new bootstrap.Modal(modalLlenarStock);

// Detectar clic en botón de llenar stock
tbodyProductos.addEventListener('click', function (e) {
    if (e.target.closest('button')?.classList.contains('btn-llenar-stock')) {
        const id = e.target.closest('button').dataset.id;
        cargarStockProducto(id);
    }
});





   // 🧩 Generar código automáticamente
   nombreInput.addEventListener('input', () => {
    const nombreOriginal = nombreInput.value;
    const nombre = nombreOriginal.trim().toUpperCase();

    const palabras = nombre.split(' ').filter(p => p.length > 0);
    let codigoGenerado = '';

    palabras.forEach(p => {
        if (!isNaN(p)) {
            codigoGenerado += p.toUpperCase();
        } else {
            codigoGenerado += p.substring(0, 3).toUpperCase();
        }
    });

    // Limitar a 10 caracteres
    codigoInput.value = codigoGenerado.substring(0, 15);
    });



    // 🖱️ Guardar producto
    btnGuardar.addEventListener('click', () => {
        // Validar campos requeridos
        const campos = ['nombreProducto', 'codigo', 'stock', 'stockminimo', 'proveedor'];
for (let id of campos) {
    const input = document.getElementById(id);
    if (!input.value || (input.type === 'number' && parseFloat(input.value) < 0)) {
        alert('Por favor completa todos los campos requeridos correctamente.');
        input.focus();
        return;
    }
}

        // Construir FormData
        const formData = new FormData(form);
        formData.append('accion', 'crear');

        // Convertir valores de texto a mayúsculas
        formData.set('nombre', formData.get('nombre').toUpperCase());
        formData.set('codigo', formData.get('codigo').toUpperCase());

        fetch('php/productos.php', {
            method: 'POST',
            body: formData
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                alert(data.success);
                form.reset();
                document.getElementById('codigo').value = '';
                document.getElementById('previewContainer').innerHTML = '';
                cargarProductos();
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalProducto'));
                modal.hide();
            } else {
                alert(data.error || 'Ocurrió un error');
            }
        })
        .catch(err => {
            console.error('Error en la petición:', err);
            //alert('Error al enviar los datos.');
        });
    });

    // Vista previa de imagen
    document.getElementById('fotoProducto').addEventListener('change', function (e) {
        const preview = document.getElementById('previewContainer');
        preview.innerHTML = '';
        const file = e.target.files[0];
        if (file) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.maxWidth = '150px';
            img.classList.add('img-thumbnail');
            preview.appendChild(img);
        }
    });

    cargarProductos();
    function cargarProductos() {
        // Destruir DataTable antes de manipular DOM
        if ($.fn.dataTable.isDataTable('#tablaProductos')) {
            $('#tablaProductos').DataTable().clear().destroy();
        }
    
        fetch('php/productos.php?accion=listar') // Aquí debes apuntar a tu archivo PHP que obtiene los productos
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
    
                renderizarTablaProductos(data); // Llamamos a la función para renderizar la tabla con los productos
                inicializarTablaProductos(); // Re-inicializamos la tabla con DataTable
            })
            .catch(error => {
                mostrarAlerta('Error al cargar los productos', 'error');
            });
    }
    
    function renderizarTablaProductos(productos) {
        const tbodyProductos = document.getElementById('tbodyProductos');
        tbodyProductos.innerHTML = '';
    
        if (productos.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = '<td colspan="11" class="text-center">No hay productos registrados</td>';
            tbodyProductos.appendChild(fila);
        } else {
            productos.forEach(item => {
                const tr = document.createElement('tr');

                // Determinar clase según el stock
                let badgeClass = '';
                const stock = parseInt(item.stock);
                const stockMinimo = parseInt(item.stock_minimo);

                // Usamos el mínimo real del producto como base
                if (stock > stockMinimo * 1.5) {
                    badgeClass = 'badge-stock-ok'; // 👍 Muy por encima del mínimo
                } else if (stock >= stockMinimo) {
                    badgeClass = 'badge-stock-low'; // ⚠️ En el rango mínimo
                } else {
                    badgeClass = 'badge-stock-critical'; // 🔴 Por debajo del mínimo
                }
               
    
                tr.innerHTML = `
                    <td>${item.codigo || ''}</td>
                    <td>
    <img src="imagenes/productos/${item.imagen || 'default.png'}" alt="Imagen" style="width: 50px; height: 50px; object-fit: cover;">
 </td>
                    <td>${item.nombre || ''}</td>
                    <td>${item.precio_compra || ''}</td>
                    <td>${item.precio_venta_real || ''}</td>
                    <td>${item.precio_venta_sugerido || ''}</td>
                    <td>
                    <span class="badge ${badgeClass}">${item.stock}</span>
                    <button class="btn btn-sm btn-success rounded-circle btn-llenar-stock ms-2" 
                    data-id="${item.id}" title="Llenar Stock" 
                    data-bs-toggle="modal" data-bs-target="#modalLlenarStock">
                    <i class="fas fa-edit"></i>
                    </button>
                    </td>
                    <td>${item.stock_minimo || ''}</td>
                    <td>${item.proveedor || ''}</td>
                    <td>${item.fecha_registro || ''}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary rounded-circle" data-id="${item.id}" title="Editar" data-bs-toggle="modal" data-bs-target="#modalEditarProducto">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger rounded-circle" data-id="${item.id}" title="Eliminar" data-bs-toggle="modal" data-bs-target="#modalEliminarProducto">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
    
                tbodyProductos.appendChild(tr);
            });
        }
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



function cargarProducto(id) {
    fetch(`php/productos.php?accion=obtener&id=${id}`)
        .then(res => {
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            return res.json();
        })
        .then(data => {
            if (data.error) {
                mostrarAlerta(data.error, 'error');
                modalEditarProductoInstance.hide();
            } else {
                // Llenar los demás campos del formulario
                document.getElementById('editIdProducto').value = data.id;
                document.getElementById('editNombreProducto').value = data.nombre;
                document.getElementById('editCodigo').value = data.codigo;
                document.getElementById('editPrecioCompra').value = data.precio_compra;
                document.getElementById('editPrecioVentaSugerido').value = data.precio_venta_sugerido;
                document.getElementById('editPrecioVentaReal').value = data.precio_venta_real;
                document.getElementById('editstockminimo').value = data.stock_minimo;
                document.getElementById('proveedorEditar').value = data.proveedor_nombre;

                // Limpiar input de nueva imagen
                document.getElementById('editFotoProducto').value = '';

                // Mostrar imagen actual si existe
                const previewContainer = document.getElementById('editPreviewContainer');
                previewContainer.innerHTML = '';
                
                if (data.imagen) {
                    const label = document.createElement('p');
                    label.textContent = 'Imagen actual:';
                    label.classList.add('mb-1', 'fw-bold');
                    
                    const img = document.createElement('img');
                    img.src = `imagenes/productos/${data.imagen}`;
                    img.alt = 'Imagen actual';
                    img.classList.add('img-thumbnail');
                    img.style.maxWidth = '150px';
                    
                    previewContainer.appendChild(label);
                    previewContainer.appendChild(img);
                } else {
                    const noImage = document.createElement('p');
                    noImage.textContent = 'Sin imagen';
                    noImage.classList.add('text-muted', 'fst-italic');
                    previewContainer.appendChild(noImage);
                }

                // Enfocar campo
                setTimeout(() => {
                    document.getElementById('editNombreProducto').focus();
                }, 100);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al cargar datos del producto', 'error');
            modalEditarProductoInstance.hide();
        });
}
    
    const formEditar = document.getElementById('formEditarProducto');
const btnActualizar = document.getElementById('btnActualizarProducto');

btnActualizar.addEventListener('click', () => {
    // Validar todos los campos incluyendo nombre y código
    const campos = ['editNombreProducto', 'editCodigo', 'editstockminimo'];

for (let id of campos) {
    const input = document.getElementById(id);
    
    if (id === 'editNombreProducto' || id === 'editCodigo') {
        if (!input.value.trim()) {
            alert('Por favor completa el campo ' + (id === 'editNombreProducto' ? 'Nombre' : 'Código'));
            input.focus();
            return;
        }
    } else if (id === 'editstockminimo') {
        if (!input.value || parseFloat(input.value) < 0) {
            alert('Por favor completa el Stock Mínimo correctamente.');
            input.focus();
            return;
        }
    }
}

    // CAMBIO: Usar FormData en lugar de datos individuales
    const formData = new FormData();
    formData.append('accion', 'actualizarProducto');
    formData.append('id', document.getElementById('editIdProducto').value);
    formData.append('nombre', document.getElementById('editNombreProducto').value.trim());
    formData.append('codigo', document.getElementById('editCodigo').value.trim());
    formData.append('precio_compra', document.getElementById('editPrecioCompra').value);
    formData.append('precio_venta_sugerido', document.getElementById('editPrecioVentaSugerido').value);
    formData.append('precio_venta_real', document.getElementById('editPrecioVentaReal').value);
    formData.append('stock_minimo', document.getElementById('editstockminimo').value);

    // NUEVO: Agregar archivo si se seleccionó uno nuevo
    const archivoImagen = document.getElementById('editFotoProducto').files[0];
    if (archivoImagen) {
        formData.append('editFotoProducto', archivoImagen);
    }

    fetch('php/productos.php', {
        method: 'POST',
        body: formData // CAMBIO: FormData en lugar de application/x-www-form-urlencoded
    })
    .then(resp => resp.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta(data.success);
            formEditar.reset();
            document.getElementById('editPreviewContainer').innerHTML = '';
            modalEditarProductoInstance.hide();
            cargarProductos();
        } else {
            alert(data.error || 'Ocurrió un error al actualizar el producto.');
        }
    })
    .catch(err => {
        console.error('Error en la petición:', err);
        alert('Error al actualizar el producto.');
    });
});


// NUEVO: Vista previa de imagen al editar producto
document.getElementById('editFotoProducto').addEventListener('change', function (e) {
    const preview = document.getElementById('editPreviewContainer');
    const file = e.target.files[0];
    
    if (file) {
        // Crear vista previa de la nueva imagen
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.style.maxWidth = '150px';
        img.classList.add('img-thumbnail');
        
        const label = document.createElement('p');
        label.textContent = 'Nueva imagen:';
        label.classList.add('mb-1', 'fw-bold', 'text-success');
        
        preview.innerHTML = '';
        preview.appendChild(label);
        preview.appendChild(img);
    }
});    







function cargarStockProducto(id) {
    fetch(`php/productos.php?accion=obtener&id=${id}`)
        .then(res => {
            if (!res.ok) throw new Error('Error en la respuesta del servidor');
            return res.json();
        })
        .then(data => {
            if (data.error) {
                mostrarAlerta(data.error, 'error');
                modalLlenarStockInstance.hide();
            } else {
                // Llena los campos del modal correctamente
                document.getElementById('llenarStockIdProducto').value = data.id;
                document.getElementById('llenarStockNombreProducto').value = data.nombre;
                document.getElementById('llenarStockActual').value = data.stock;
                document.getElementById('cantidadStock').value = '';
                document.getElementById('accionStock').value = '';
                // Precarga el precio de compra actual del producto como sugerencia
                document.getElementById('precioCompraStock').value = data.precio_compra ?? '';
                document.getElementById('wrapperPrecioCompraStock').style.display = 'none';

                // Mostrar el modal
                modalLlenarStockInstance.show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarAlerta('Error al cargar datos del producto', 'error');
            modalLlenarStockInstance.hide();
        });
}


// Mostrar el campo de precio de compra solo cuando la acción es "agregar" (es una compra)
document.getElementById('accionStock').addEventListener('change', function () {
    const wrapperPrecio = document.getElementById('wrapperPrecioCompraStock');
    if (this.value === 'agregar') {
        wrapperPrecio.style.display = 'block';
    } else {
        wrapperPrecio.style.display = 'none';
        document.getElementById('precioCompraStock').value = '';
    }
});

document.getElementById('btnConfirmarLlenadoStock').addEventListener('click', function () {
    const form = document.getElementById('formLlenarStock');

    const id = document.getElementById('llenarStockIdProducto').value;
    const accion = document.getElementById('accionStock').value;
    const cantidad = parseInt(document.getElementById('cantidadStock').value);
    const precioCompra = parseFloat(document.getElementById('precioCompraStock').value);

    // Obtener nombre del usuario desde localStorage
    const userData = JSON.parse(localStorage.getItem('userData'));
    const nombreUsuario = userData?.nombre || 'Desconocido';

    if (!accion || isNaN(cantidad) || cantidad <= 0) {
        mostrarAlerta('Completa todos los campos correctamente', 'warning');
        return;
    }

    // El precio de compra solo aplica (y es obligatorio) cuando se agrega stock, porque es una compra
    if (accion === 'agregar' && (isNaN(precioCompra) || precioCompra <= 0)) {
        mostrarAlerta('Ingresa el precio de compra unitario', 'warning');
        return;
    }

    const datos = new FormData();
    datos.append('accion', 'actualizar_stock');
    datos.append('id', id);
    datos.append('operacion', accion);
    datos.append('cantidad', cantidad);
    datos.append('nombre_usuario', nombreUsuario); // Aquí mandas el nombre del usuario
    if (accion === 'agregar') {
        datos.append('precio_compra_unitario', precioCompra);
    }

    fetch('php/productos.php', {
        method: 'POST',
        body: datos
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            mostrarAlerta(data.error, 'error');
        } else {
            mostrarAlerta(data.mensaje, 'success');
            modalLlenarStockInstance.hide();
            cargarProductos(); // Vuelve a cargar la lista de productos
        }
    })
    .catch(err => {
        console.error(err);
        mostrarAlerta('Error al actualizar el stock', 'error');
    });
});




    
    // Capturar datos al hacer clic en el botón de eliminar producto
document.addEventListener('click', function (e) {
    if (e.target.closest('.btn-danger[data-bs-target="#modalEliminarProducto"]')) {
        const button = e.target.closest('button');
        const id = button.dataset.id;
        const fila = button.closest('tr');
        const nombre = fila.querySelectorAll('td')[2].textContent;

        document.getElementById('idProductoEliminar').value = id;
        document.getElementById('nombreProductoEliminar').textContent = nombre;
    }
});

// Confirmar eliminación de producto
document.getElementById('btnConfirmarEliminarProducto').addEventListener('click', function () {
    const idProducto = document.getElementById('idProductoEliminar').value;

    fetch('php/productos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `accion=eliminar&id=${encodeURIComponent(idProducto)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarProducto'));
            modal.hide();
            mostrarAlerta('Producto eliminado correctamente', 'success');
            cargarProductos(); // función que actualiza la tabla de productos
        } else {
            mostrarAlerta('Error al eliminar el producto', 'danger');
        }
    })
    .catch(() => {
        mostrarAlerta('Error al eliminar el producto', 'danger');
    });
});








});




    function inicializarTablaProductos() {
    // 🔧 Destruir tabla si ya existe
    if ($.fn.dataTable.isDataTable('#tablaProductos')) {
        $('#tablaProductos').DataTable().clear().destroy();
    }
    // Inicializar DataTable
    let tabla = $('#tablaProductos').DataTable({
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
    },
    ordering: true,
    order: [[2, 'asc']],  // Aquí está el orden alfabético por nombre de producto
    lengthChange: false,
    paging: false,
    info: false,
    dom: 't'
});

    tabla.on('draw', function () {
        $('#tablaProductos tbody tr').css('height', '68px');
    });

    // Buscador personalizado
    let input = $('<input type="search">')
        .attr('placeholder', 'Buscar producto...')
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
        'font-weight': '500',
        'margin-left': '0'
    });

    let customSearch = $('<div>')
        .addClass('d-flex align-items-center gap-2')
        .css({
            'margin-bottom': '10px',
            'justify-content': 'flex-start'
        })
        .append(label)
        .append(input);

    $('#customSearchContainerProductos').empty().append(customSearch);

    // Vista previa de imagen nuevo producto
    $('#fotoProducto').change(function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#previewContainer').html(`<img src="${e.target.result}" alt="Vista previa" class="preview-image">`);
            };
            reader.readAsDataURL(file);
        }
    });

    // Vista previa de imagen al editar producto
    $('#editFotoProducto').change(function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#editPreviewContainer').html(`<img src="${e.target.result}" alt="Vista previa" class="preview-image">`);
            };
            reader.readAsDataURL(file);
        }
    });
    }

        $('#modalProducto').on('show.bs.modal', function () {
            cargarProveedores('proveedor');
        });

/*
        $('#modalEditarProducto').on('show.bs.modal', function () {
            cargarProveedores('proveedorEditar');
        });*/
        
        
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
        
    // Capturar el modal del producto
const modalProducto = document.getElementById('modalProducto');

// Limpiar formulario cuando se cierre el modal
modalProducto.addEventListener('hidden.bs.modal', function () {
    limpiarFormularioProducto(); // Limpiar los campos al cerrar el modal
});

// Limpiar los campos del formulario
function limpiarFormularioProducto() {
    document.getElementById('nombreProducto').value = '';
    document.getElementById('codigo').value = '';
    document.getElementById('precioCompra').value = '';
    document.getElementById('precioVentaSugerido').value = '';
    document.getElementById('precioVentaReal').value = '';
    document.getElementById('stock').value = '';
    document.getElementById('stockminimo').value = '';
    document.getElementById('proveedor').value = '';
    document.getElementById('fotoProducto').value = '';
    document.getElementById('previewContainer').innerHTML = '';
}

    
        
        

        
        