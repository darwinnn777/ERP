<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <base href="<?= BASE_URL ?>">
    <!-- Cancela los estilos de body que usa el resto de la app -->
    <style>
        body { display: block !important; background: transparent !important; height: auto !important; }
    </style>
</head>
<body>

<div style="display:flex; height:100vh; width:100%; overflow:hidden;">

    <!-- Panel izquierdo oscuro -->
    <div class="login-left d-flex flex-column align-items-center justify-content-center text-center p-4">
        <img src="assets/img_products/logoB.png" alt="Logo" style="width:350px;" class="mb-0">
        <h1 class="text-white fw-bold fs-4 mb-1" style="margin-top:-30px;">ERP Bakery</h1>
        <div class="login-divider my-2"></div>
        <p class="login-tagline mb-0">Sistema de gestion comercial<br>y produccion para panaderia</p>
    </div>

    <!-- Panel derecho gris -->
    <div style="flex:1; background:#f0f2f5; display:flex; align-items:center; justify-content:center;">
        <div class="bg-white rounded-4 p-4 shadow-sm" style="width:100%;max-width:380px;">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2 text-center" style="font-size:0.9rem;">
                    <?= sanitize_input($errors) ?>
                </div>
            <?php endif; ?>

            <h2 class="fw-bold mb-1" style="color:#1a1a2e;">Bienvenido</h2>
            <p class="text-muted mb-4" style="font-size:0.875rem;">Introduce tus credenciales para acceder</p>

            <form action="login/process" method="POST">

                <label class="form-label fw-semibold small">Usuario</label>
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-secondary"></i></span>
                    <input type="text" name="usuario" class="form-control border-start-0 ps-0" placeholder="Tu usuario" required>
                </div>

                <label class="form-label fw-semibold small">Contraseña</label>
                <div class="input-group mb-4">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-bakery w-100 rounded-pill py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Entrar al Sistema
                </button>

            </form>

            <p class="text-center text-muted mt-3 mb-0" style="font-size:0.78rem;">
                &copy; <?= date('Y') ?> ERP Comercial — Panadería
            </p>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
