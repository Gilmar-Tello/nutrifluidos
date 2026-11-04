/**
 * Script para la funcionalidad del dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    

    // Inicializar autenticación sin requerir permisos específicos (solo verificar sesión)
    // Esto ya incluye loadUserInfo(), setupMenuPermissions() y setupLogout()
    initAuth();
    
  
    //Modificar en producción 
    const userData = JSON.parse(localStorage.getItem('userData'));
    const permisos = userData ? userData.permisos : null;

    // Mostrar u ocultar tarjetas según permisos
    setupDashboardCards(permisos);
//Hasta aca
    initializeDashboard();
   
});


/**
 * Inicializa los componentes del dashboard
 */
function initializeDashboard() {
    // Cargar datos del dashboard
    fetchDashboardData();
}

/**
 * Obtiene los datos del dashboard desde el servidor o localStorage
 */
function fetchDashboardData() {
    // Primero verificamos si ya tenemos datos guardados en localStorage
    const cachedData = localStorage.getItem('dashboardData');
    
    if (cachedData) {
        try {
            const dashboardData = JSON.parse(cachedData);
            updateDashboardUI(dashboardData);
        } catch (e) {
            console.error('Error al parsear datos del dashboard:', e);
        }
    }
    
    // Realizar la petición al servidor para obtener datos actualizados
    fetch('php/menu.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(response => {
            if (response.status === 'success' && response.data) {
                // Guardar datos en localStorage para uso futuro
                localStorage.setItem('dashboardData', JSON.stringify(response.data));
                // Actualizar UI
                updateDashboardUI(response.data);
            } else {
                console.error('Error en la respuesta:', response.message);
                // Si hay un error y no tenemos datos en caché, cargar datos de respaldo
                if (!cachedData) {
                    loadFallbackData();
                }
            }
        })
        .catch(error => {
            console.error('Error al obtener datos del dashboard:', error);
            // Si hay un error y no tenemos datos en caché, cargar datos de respaldo
            if (!cachedData) {
                loadFallbackData();
            }
        });
}

/**
 * Carga datos de respaldo para el dashboard en caso de error
 */
function loadFallbackData() {
    // Datos estáticos de respaldo
    const dashboardData = {
        totalClientes: 125,
        totalProductos: 284,
        ventasMes: 'Q 45,230',
        stockBajo: 8,
        actividadReciente: [
            { 
                tipo: 'venta', 
                descripcion: 'Nueva venta registrada', 
                tiempo: 'Hace 10 minutos',
                icono: 'shopping-cart',
                colorClase: 'text-primary'
            },
            { 
                tipo: 'cliente', 
                descripcion: 'Nuevo cliente agregado', 
                tiempo: 'Hace 2 horas',
                icono: 'user-plus',
                colorClase: 'text-success'
            },
            { 
                tipo: 'producto', 
                descripcion: 'Productos recibidos', 
                tiempo: 'Hace 5 horas',
                icono: 'truck',
                colorClase: 'text-warning'
            },
            { 
                tipo: 'pago', 
                descripcion: 'Pago recibido', 
                tiempo: 'Hace 1 día',
                icono: 'money-bill-wave',
                colorClase: 'text-info'
            },
            { 
                tipo: 'stock', 
                descripcion: 'Stock bajo de producto', 
                tiempo: 'Hace 2 días',
                icono: 'box',
                colorClase: 'text-danger'
            }
        ]
    };
    
    // Actualizar la UI con los datos de respaldo
    updateDashboardUI(dashboardData);
}

/**
 * Actualiza la interfaz del dashboard con los datos obtenidos
 */
function updateDashboardUI(data) {
    // Actualizar tarjetas de estadísticas
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    
    if (dashboardCards.length >= 4) {
        // Tarjeta de clientes
        const clientesCard = dashboardCards[0].querySelector('.card-info h4');
        if (clientesCard && data.totalClientes !== undefined) {
            clientesCard.textContent = data.totalClientes;
        }
        
        // Tarjeta de productos
        const productosCard = dashboardCards[1].querySelector('.card-info h4');
        if (productosCard && data.totalProductos !== undefined) {
            productosCard.textContent = data.totalProductos;
        }
        
        // Tarjeta de ventas
        const ventasCard = dashboardCards[2].querySelector('.card-info h4');
        if (ventasCard && data.ventasMes) {
            ventasCard.textContent = data.ventasMes;
        }
        
        // Tarjeta de stock bajo
        const stockCard = dashboardCards[3].querySelector('.card-info h4');
        if (stockCard && data.stockBajo !== undefined) {
            stockCard.textContent = data.stockBajo;
        }

        // Tarjeta de pedidos
const pedidosCard = document.querySelector('#tarjeta-pedidos .card-info h4');
if (pedidosCard && data.pedidosPendientes !== undefined) {
    pedidosCard.textContent = data.pedidosPendientes;
}
    }

    
    
    // Actualizar lista de actividad reciente
    if (data.actividadReciente && data.actividadReciente.length > 0) {
        const actividadList = document.querySelector('.list-group');
        if (actividadList) {
            // Limpiar lista actual
            actividadList.innerHTML = '';
            
            // Agregar actividades actualizadas
            data.actividadReciente.forEach(actividad => {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                
                const iconoClase = actividad.colorClase || 'text-primary';
                const icono = actividad.icono || 'info-circle';
                
                listItem.innerHTML = `
                    <div>
                        <i class="fas fa-${icono} ${iconoClase} me-2"></i>
                        <span>${actividad.descripcion}</span>
                    </div>
                    <small class="text-muted">${actividad.tiempo}</small>
                `;
                
                actividadList.appendChild(listItem);
            });
        }
    }
}

/**
 * Configura los botones de acceso rápido según permisos
 * Esta función será llamada desde setupMenuPermissions en auth.js
 */
function setupQuickAccessButtons(permisos) {
    if (!permisos) return;
    
    // Mapeo de botones de acceso rápido con los permisos correspondientes
    const quickAccessButtons = document.querySelectorAll('.card-body .btn');
    quickAccessButtons.forEach(button => {
        const href = button.getAttribute('href');
        if (href) {
            // Verificar permisos según la URL
            if (href.includes('ventas') && !permisos.ventas) {
                button.style.display = 'none';
            } else if (href.includes('clientes') && !permisos.clientes) {
                button.style.display = 'none';
            } else if (href.includes('productos') && !permisos.productos) {
                button.style.display = 'none';
            } else if (href.includes('abonos') && !permisos.usuarios) { // Cambio importante aquí: ahora usa permisos.usuarios
                button.style.display = 'none';
            }
        }
    });
    
    // Agregar efectos visuales a los botones
    const allButtons = document.querySelectorAll('.btn');
    allButtons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.classList.add('shadow-sm');
        });
        
        button.addEventListener('mouseleave', function() {
            this.classList.remove('shadow-sm');
        });
    });
}



/**
 * Formatea segundos en formato HH:MM:SS
 */
function formatTime(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    return [
        hours.toString().padStart(2, '0'),
        minutes.toString().padStart(2, '0'),
        secs.toString().padStart(2, '0')
    ].join(':');
}