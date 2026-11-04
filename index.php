
<?php
require_once('nutrifluidos/functions.php'); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrifluidos - Login</title>
    <!-- CSS Bootstrap y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS personalizado con versioning -->
    <link rel="stylesheet" href="nutrifluidos/css/login.css<?php echo getFileVersion('css/login.css'); ?>">
    <!-- icono pestaña -->
    <link rel="icon" href="nutrifluidos/imagenes/nutrifluidos.jpg" type="image/x-icon">
    <link rel="shortcut icon" href="nutrifluidos/imagenes/nutrifluidos.jpg" type="image/x-icon">
    <link rel="apple-touch-icon" href="nutrifluidos/imagenes/nutrifluidos.jpg">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center">
                <img src="nutrifluidos/imagenes/nutrifluidos.jpg" alt="Agrofluidos Logo" class="login-logo">
                <h4 class="mb-4">Iniciar Sesión</h4>
            </div>
            <form id="loginForm">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingrese su usuario" required autocomplete="username">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="Contraseña" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="Contraseña" name="contrasena" placeholder="Ingrese su contraseña" required autocomplete="current-password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-sign-in-alt me-2"></i>Ingresar
                </button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">¿Olvidó su contraseña? Contacte al administrador</small>
            </div>
        </div>
    </div>
    <!-- JavaScript Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript personalizado con versioning -->
    <script src="./nutrifluidos/js/login.js<?php echo getFileVersion('js/login.js'); ?>"></script>
    <script>
</script>
</body>
</html>
