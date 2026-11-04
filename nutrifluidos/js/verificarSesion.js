// verificarSesion.js - Verificación de sesión para páginas protegidas
// Este archivo debe incluirse en todas las páginas que requieren autenticación
function obtenerCookie(nombre) {
    const valor = `; ${document.cookie}`;
    const partes = valor.split(`; ${nombre}=`);
    if (partes.length === 2) return partes.pop().split(';').shift();
    return null;
}
console.log('Iniciando verificación de sesión...');

// Función principal para verificar si la sesión está activa
async function verificarSesionActiva() {
    
    try {
        const response = await fetch('/nutrifluidos/php/session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache'
            },
            body: JSON.stringify({
                accion: 'verificar'
            })
        });
        
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        
        const resultado = await response.json();
        
        if (!resultado.success || !resultado.sesion_activa) {
            console.log('Sesión no activa, redirigiendo al login...');
            redirigirAlLogin();
            return false;
        }
        
        console.log('Sesión verificada correctamente');
        return true;
        
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        // En caso de error, redirigir al login por seguridad
        redirigirAlLogin();
        return false;
    }
}

// Función para redirigir al login de manera segura
function redirigirAlLogin() {
    // Limpiar cualquier dato local
    if (typeof(Storage) !== "undefined") {
        localStorage.clear();
        sessionStorage.clear();
    }
    
    // Usar replace para evitar que puedan volver con el botón atrás
    window.location.replace('/index.php');
}

// Función para cerrar sesión de manera segura
async function cerrarSesionSegura() {
    try {
        // Mostrar mensaje de carga
        if (document.body) {
            document.body.style.cursor = 'wait';
        }
        
        const response = await fetch('/nutrifluidos/php/session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                accion: 'cerrar'
            })
        });
        
        const resultado = await response.json();
        
        if (resultado.success) {
            console.log('Sesión cerrada correctamente');
        }
        
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
    } finally {
        // Limpiar datos locales y redirigir
        if (typeof(Storage) !== "undefined") {
            localStorage.clear();
            sessionStorage.clear();
        }
        
        // Redirigir al login usando replace
        window.location.replace('/index.php');
    }
}

// Verificar sesión cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada, esperando cookie de sesión...');
    
    // Esperar a que PHP establezca la cookie de sesión antes de verificar
    const intervalo = setInterval(() => {
        if (obtenerCookie('PHPSESSID')) {
            clearInterval(intervalo);
            console.log('Cookie PHPSESSID detectada, verificando sesión...');
            verificarSesionActiva();
        } else {
            console.log('Esperando cookie PHPSESSID...');
        }
    }, 300); // verifica cada 300ms
});

// Verificar sesión cuando la página se vuelve visible
// Esto previene el problema del botón atrás
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('Página visible, verificando sesión...');
        verificarSesionActiva();
    }
});

// Verificar sesión cuando se muestra la página desde el caché
// Esto es especialmente importante para Safari y Firefox
window.addEventListener('pageshow', function(event) {
    if (event.persisted) {
        console.log('Página cargada desde caché, verificando sesión...');
        verificarSesionActiva();
    }
});

// Verificar sesión cuando se enfoca la ventana
window.addEventListener('focus', function() {
    console.log('Ventana enfocada, verificando sesión...');
    verificarSesionActiva();
});

// Prevenir el uso del botón atrás después del logout
window.addEventListener('load', function() {
    // Verificar sesión al cargar
    verificarSesionActiva();
    
    // Agregar entrada al historial para controlar la navegación
    history.pushState(null, null, window.location.href);
    
    // Manejar el evento popstate (botón atrás)
    window.addEventListener('popstate', function(event) {
        console.log('Usuario intentó usar botón atrás, verificando sesión...');
        // Verificar sesión cuando intentan usar el botón atrás
        verificarSesionActiva().then(function(sesionActiva) {
            if (sesionActiva) {
                // Si la sesión está activa, mantener en la página actual
                history.pushState(null, null, window.location.href);
            }
        });
    });
});

// Verificación periódica de sesión (opcional, cada 5 minutos)
setInterval(function() {
    verificarSesionActiva();
}, 300000); // 5 minutos

// Exponer funciones globalmente para poder usarlas desde HTML
window.verificarSesionActiva = verificarSesionActiva;
window.cerrarSesionSegura = cerrarSesionSegura;