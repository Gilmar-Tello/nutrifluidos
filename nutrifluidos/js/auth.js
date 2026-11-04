/**
 * Sistema de Autenticación y Control de Acceso
 */

// =====================================================================
// SEGURIDAD (16-09-2026): si el servidor responde 401 (sesión vencida o
// inexistente) en cualquier petición, se limpia el navegador y se vuelve
// al login.
// =====================================================================
function nfRedirigirLogin(servicioSuspendido) {
    if (window.__nfRedirigiendo) return;
    window.__nfRedirigiendo = true;
    try { localStorage.removeItem('isLoggedIn'); localStorage.removeItem('userData'); } catch (e) {}
    // 28-10-2026: si el servicio fue suspendido por falta de pago, el login explica el motivo
    window.location.replace('/index.php' + (servicioSuspendido ? '?suspendido=1' : ''));
}

/**
 * 28-10-2026: aviso de pago (faltan N días / vence hoy / vencido).
 * Lo decide el servidor (session.php); aquí solo se muestra, como una tarjeta
 * flotante abajo a la derecha para no tapar el menú ni los botones.
 */
function nfMostrarAvisoPago(aviso) {
    if (!aviso || !aviso.mensaje || document.getElementById('nf-aviso-pago')) return;
    const vencido = aviso.nivel === 'vencido';
    const caja = document.createElement('div');
    caja.id = 'nf-aviso-pago';
    caja.setAttribute('role', 'alert');
    caja.textContent = (vencido ? '\u26A0\uFE0F ' : '\uD83D\uDD14 ') + aviso.mensaje;
    caja.style.cssText = 'position:fixed;right:12px;bottom:12px;z-index:1100;'
        + 'max-width:min(360px,calc(100% - 24px));padding:10px 14px;border-radius:8px;'
        + 'font-size:14px;font-weight:600;box-shadow:0 4px 14px rgba(0,0,0,.2);'
        + (vencido
            ? 'background:#f8d7da;color:#842029;border:1px solid #f5c2c7;'
            : 'background:#fff3cd;color:#664d03;border:1px solid #ffecb5;');
    document.body.appendChild(caja);
}

(function instalarInterceptorSesion() {
    if (window.__nfInterceptorSesion || !window.fetch) return;
    window.__nfInterceptorSesion = true;
    const fetchOriginal = window.fetch.bind(window);
    window.fetch = function (...args) {
        return fetchOriginal(...args).then(response => {
            if (response.status === 401) {
                // Se lee una copia de la respuesta para saber si fue por servicio suspendido
                response.clone().json()
                    .then(j => nfRedirigirLogin(!!(j && j.servicio_suspendido)))
                    .catch(() => nfRedirigirLogin(false));
            }
            return response;
        });
    };
    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery) {
            window.jQuery(document).ajaxError(function (event, jqxhr) {
                if (jqxhr && jqxhr.status === 401) {
                    nfRedirigirLogin(!!(jqxhr.responseJSON && jqxhr.responseJSON.servicio_suspendido));
                }
            });
        }
    });
})();

/**
 * Verifica si el usuario tiene una sesión activa
 * @returns {boolean} - Estado de la sesión
 */
function checkSession() {
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    const userData = localStorage.getItem('userData');
    
    return isLoggedIn && userData;
}

/**
 * Verifica si el usuario tiene permiso para acceder a la página actual
 * @param {string} requiredPermission - El permiso requerido para la página
 * @returns {boolean} - True si el usuario tiene permiso
 */
function checkPermission(requiredPermission) {
    try {
        const userData = JSON.parse(localStorage.getItem('userData'));
        
        if (!userData || !userData.permisos) {
            return false;
        }
        
        return userData.permisos[requiredPermission] === true;
    } catch (error) {
        console.error('Error al verificar permisos:', error);
        return false;
    }
}

/**
 * Inicializa la autenticación y control de acceso en las páginas
 * @param {string} requiredPermission - El permiso requerido para la página
 */
function initAuth(requiredPermission) {
    // Verificar autenticación y permisos
    if (!checkSession()) {
        // No hay sesión activa, redirigir al login
        window.location.href = '/index.php';
        return;
    }
    
    if (requiredPermission && !checkPermission(requiredPermission)) {
        // No tiene permisos para esta página, redirigir al dashboard
        window.location.href = 'menu.php';
        alert('No tiene permisos para acceder a esta página');
        return;
    }
    
  
    
    // Sincronizar sesión con el servidor de forma automática
    sincronizarSesionConServidor().then(() => {
        console.log('Sesión sincronizada con el servidor');
    }).catch(error => {
        console.error('Error al sincronizar sesión:', error);
    });
    
    // Cargar información del usuario
    loadUserInfo();
    
    // Configurar menú según permisos
    setupMenuPermissions();
    
    // Configurar evento de cierre de sesión
    setupLogout();
}

/**
 * SEGURIDAD (16-09-2026): ya NO se envían datos del usuario al servidor.
 * Solo se pregunta si la sesión del servidor (creada en el login) sigue activa.
 * Si no lo está, se limpia el navegador y se vuelve al login.
 * @returns {Promise}
 */
function sincronizarSesionConServidor() {
    return fetch('php/session.php', {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'verificar' })
    })
    .then(response => response.json().catch(() => ({})))
    .then(data => {
        if (!data.success || !data.sesion_activa) {
            nfRedirigirLogin(!!data.servicio_suspendido);
            throw new Error('Sesión no activa');
        }
        nfMostrarAvisoPago(data.aviso_pago);
        return data;
    });
}

/**
 * Carga la información del usuario en la interfaz
 */
function loadUserInfo() {
    try {
        const userData = JSON.parse(localStorage.getItem('userData'));
        
        if (userData) {
            // Actualizar nombre y correo en la barra superior
            const welcomeMessage = document.querySelector('.welcome-message h6');
            const userEmail = document.querySelector('.welcome-message span');
            
            if (welcomeMessage) {
                welcomeMessage.textContent = `Bienvenido, ${userData.nombre || 'Usuario'}`;
            }
            
            if (userEmail) {
                // Utilizar el correo del usuario o su nombre de usuario como respaldo
                userEmail.textContent = userData.correo_electronico || userData.usuario || '';
            }
        }
    } catch (error) {
        console.error('Error al cargar información del usuario:', error);
    }
}

/**
 * Configura el menú según los permisos del usuario
 */
function setupMenuPermissions() {
    try {
        const userData = JSON.parse(localStorage.getItem('userData'));
        
        if (!userData || !userData.permisos) return;
        
        const permisos = userData.permisos;
        
        // Mapeo de enlaces del menú con los permisos correspondientes
        const menuPermisos = {
            'usuarios.php': 'usuarios',        // Solo admin
            'proveedores.php': 'proveedores',  // Solo admin
            'clientes.php': 'clientes',        // Todos
            'productos.php': 'productos',      // Solo admin
            'ventas.php': 'ventas',            // Todos
            'abonos.php': 'usuarios',          // Solo admin
            'calculadora.php': 'usuarios',     // Solo admin
            'pedidos.php': 'pedidos',          // Todos (vendedor solo ve)
            'informes-ventas.php': 'informes'  // Solo admin
        };
        
        // Ocultar elementos del menú según permisos
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && href !== 'menu.php' && href !== 'index.php') {
                const permiso = menuPermisos[href];
                
                if (permiso && !permisos[permiso]) {
                    item.style.display = 'none';
                }
            }
        });
    } catch (error) {
        console.error('Error al configurar permisos del menú:', error);
    }
}
//Agregar al de producción 
function setupDashboardCards() {
    try {
        const userData = JSON.parse(localStorage.getItem('userData'));
        if (!userData || !userData.permisos) return;

        const permisos = userData.permisos;
        const rol = userData.rol;

        // Mapeo de tarjetas con permisos y roles
        const tarjetasPermisos = {
            'tarjeta-clientes': 'clientes',
            'tarjeta-productos': 'productos',
            //'tarjeta-ventas': 'ventas', // ORIGINAL DESCOMENTAR SI BODEGUERO Y VENDEDOR QUIEREN VER SUS VENTAS
            'tarjeta-ventas': 'rol:administrador', //SOLO EL ADMINISTRADOR PUEDE VER LAS VENTAS 28/11/2025
            'tarjeta-stock-bajo': 'productos',
            'tarjeta-pedidos': 'rol:bodeguero' // solo visible para rol bodeguero
        };

        // Mostrar u ocultar según permisos o rol
        for (const [id, permiso] of Object.entries(tarjetasPermisos)) {
            const tarjeta = document.getElementById(id);

            if (!tarjeta) continue;

            if (permiso.startsWith('rol:')) {
                const rolRequerido = permiso.split(':')[1];
                if (rol !== rolRequerido) {
                    tarjeta.style.display = 'none';
                }
            } else {
                if (!permisos[permiso]) {
                    tarjeta.style.display = 'none';
                }
            }
        }

    } catch (error) {
        console.error('Error al configurar las tarjetas del dashboard:', error);
    }
}


/**
 * Configura evento para cerrar sesión
 */
function setupLogout() {
    const logoutLinks = document.querySelectorAll('a[href="/index.php"], a[href="index.php"]');
    
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('¿Está seguro que desea cerrar sesión?')) {
                // Limpiar localStorage
                localStorage.removeItem('isLoggedIn');
                localStorage.removeItem('userData');
                localStorage.removeItem('dashboardData');
                localStorage.clear();
                
                // Llamar al logout del servidor
                const timestamp = new Date().getTime();
                
                fetch(`php/logout.php?_=${timestamp}`, {
                    method: 'GET',
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                }).then(() => {
                    // Redirigir con timestamp para evitar caché
                    window.location.href = `/index.php?_=${timestamp}`;
                }).catch(error => {
                    console.error('Error:', error);
                    // Redirigir de todos modos
                    window.location.href = `/index.php?_=${timestamp}`;
                });
            }
        });
    });
}



