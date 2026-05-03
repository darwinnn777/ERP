<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP Bakery</title>
    <!-- Cargamos Bootstrap 5 por CDN para agilizar el desarrollo -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Fija la ruta base para que CSS/JS e imágenes siempre carguen bien sin importar la URL actual -->
    <base href="<?= BASE_URL ?>">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold text-bakery">SISTEMA ERP</h2>
                <p class="text-muted">Gestión Comercial y Producción</p>
                <span class="badge bg-success">MVC Layer</span>
            </div>

            <div class="card card-login p-4 shadow-sm">
                <div class="card-body">
                    
                    <!-- Aquí recibimos y mostramos los errores del AuthController (ej. "Contraseña incorrecta") -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 text-center" style="font-size: 0.9rem;">
                            <!-- Clave para el TFG: sanitize_input previene ataques XSS (Cross-Site Scripting) -->
                            <?= sanitize_input($errors) ?>
                        </div>
                    <?php endif; ?>

                    <!-- El form apunta a la ruta que dispara processLogin() en el controlador -->
                    <form action="login/process" method="POST">
                        <div class="mb-3">
                            <label for="usuario" class="form-label fw-bold">Usuario:</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Su usuario" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Contraseña:</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        
                        <button type="submit" class="btn btn-bakery w-100 py-2 fw-bold">
                            Entrar al Sistema
                        </button>
                    </form>

                </div>
            </div>

            <!-- Año dinámico con PHP para que no se quede desactualizado -->
            <p class="text-center mt-4 text-muted small">&copy; <?= date('Y') ?> ERP Comercial - Panadería</p>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>