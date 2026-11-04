/**
 * Script para la funcionalidad de la página de login
 */
document.addEventListener('DOMContentLoaded', function() {

    
    // Eliminar cualquier elemento de diagnóstico existente
    const diagnosticoElement = document.getElementById('diagnostico-respuesta');
    if (diagnosticoElement) {
        diagnosticoElement.remove();
    }
    
    // Limpiar datos previos de sesión si existen
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userData');
    
    // Referencia al formulario de login
    const loginForm = document.getElementById('loginForm');

    // 28-10-2026: si los sacaron porque el servicio fue suspendido por falta de pago, se explica aquí
    // (el mismo texto y teléfonos están en nf_mensajeSuspension() de php/auth.php)
    if (loginForm && new URLSearchParams(window.location.search).get('suspendido') === '1') {
        const aviso = document.createElement('div');
        aviso.className = 'alert-message alert-error';
        aviso.textContent = 'El servicio se encuentra suspendido por pago pendiente. Comuníquese con SOLTEC: 5492-5395 / 4256-1960 para reactivarlo.';
        loginForm.insertAdjacentElement('beforebegin', aviso);
    }
    
    // Configurar botón para mostrar/ocultar contraseña
    const togglePasswordBtn = document.getElementById('togglePassword');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function() {
            const passwordField = document.getElementById('Contraseña');
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            // Cambiar el ícono
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // Manejar el envío del formulario
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevenir el envío tradicional del formulario
            
            const username = document.getElementById('usuario').value.trim();
            const password = document.getElementById('Contraseña').value.trim();
            
            // Validación de campos
            if (!username || !password) {
                mostrarMensaje('Por favor complete todos los campos', 'error');
                return false;
            }
            
            // Mostrar indicador de carga
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            
            // Enviar datos al servidor mediante fetch API
            fetch('/nutrifluidos/php/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            })
            .then(response => {
                return response.json().catch(() => {
                    throw new Error('La respuesta del servidor no es un JSON válido');
                });
            })
            .then(data => {
                if (data.status === 'success') {
                    mostrarMensaje('Inicio de sesión exitoso', 'success');
                    
                    // Guardar información de la sesión
                    localStorage.setItem('isLoggedIn', 'true');
                    localStorage.setItem('userData', JSON.stringify(data.data.usuario));
                    // El aviso de pago se muestra una sola vez, al iniciar sesión
                    localStorage.setItem('nfAvisoPagoPendiente', 'true');
                    
                    // Redirigir después de un breve retraso
                    setTimeout(() => {
                        window.location.href = '/nutrifluidos/menu.php';
                    }, 800);
                } else {
                    // Mostrar mensaje de error del servidor
                    throw new Error(data.message || 'Error al iniciar sesión');
                }
            })
            .catch(error => {
                console.error('Error en la solicitud:', error);
                
                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                // Mostrar mensaje de error adecuado
                let errorMessage = error.message;
                
                if (error.message.includes('Failed to fetch')) {
                    errorMessage = 'Error de conexión. Verifique su conexión a internet o si el servidor está activo.';
                }
                
                mostrarMensaje(errorMessage, 'error');
            });
            
            // Asegurarse de eliminar cualquier diagnóstico que pudiera haberse creado
            setTimeout(() => {
                const diagnosticoElement = document.getElementById('diagnostico-respuesta');
                if (diagnosticoElement) {
                    diagnosticoElement.remove();
                }
            }, 100);
        });
    }
    
    /**
     * Muestra mensajes al usuario en la interfaz
     * @param {string} mensaje - Texto del mensaje a mostrar
     * @param {string} tipo - Tipo de mensaje ('error' o 'success')
     */
    function mostrarMensaje(mensaje, tipo) {
        // Eliminar mensajes anteriores
        const mensajesAnteriores = document.querySelectorAll('.alert-message');
        mensajesAnteriores.forEach(msg => msg.remove());
        
        // Eliminar cualquier diagnóstico que pudiera haberse creado
        const diagnosticoElement = document.getElementById('diagnostico-respuesta');
        if (diagnosticoElement) {
            diagnosticoElement.remove();
        }
        
        // Crear elemento de mensaje
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert-message alert-${tipo}`;
        alertDiv.textContent = mensaje;
        
        // Insertar antes del formulario
        if (loginForm) {
            loginForm.insertAdjacentElement('beforebegin', alertDiv);
        }
        
        // Remover el mensaje después de 4 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 4000);
    }
    
    // Verificar si hay algún diagnóstico en el DOM al cargar la página y eliminarlo
    setInterval(() => {
        const diagnosticoElement = document.getElementById('diagnostico-respuesta');
        if (diagnosticoElement) {
            diagnosticoElement.remove();
        }
        // También eliminar cualquier elemento que contenga "Diagnóstico de respuesta"
        document.querySelectorAll('div').forEach(div => {
            if (div.innerHTML && div.innerHTML.includes('Diagnóstico de respuesta')) {
                div.remove();
            }
        });
    }, 1000);
});