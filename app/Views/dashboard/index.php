<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - ERP Bakery (MVC)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <base href="<?= BASE_URL ?>">
</head>
<body class="admin-layout">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm navbar-dashboard" style="background-color: var(--color-bakery);">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="dashboard">
            <img src="assets/img_products/logo.png" alt="Logo ERP Bakery" class="navbar-logo">
            ERP Bakery
        </a>

        <div class="d-flex align-items-center gap-3 text-white">
            <div class="text-end d-none d-md-block">
                <!-- Pintamos el nombre del usuario y su rol bien grande -->
                <div class="fw-semibold" style="font-size: 0.9rem;"><?= sanitize_input($nombre_usu) ?></div>
                <span class="badge-rol"><?= strtoupper($rol_actual) ?></span>
            </div>
            
            <!-- El botón de salir ahora es un mini-formulario por temas de seguridad (token CSRF) -->
            <form action="logout" method="GET" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-sm btn-outline-light rounded-pill px-3" onclick="return confirm('¿Cerrar sesión?');">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="mb-4">
        <h2 class="fw-bold text-bakery">Panel de Control</h2>
        <p class="text-muted">Bienvenido, <strong><?= sanitize_input($nombre_usu) ?></strong>. Selecciona un módulo para empezar.</p>
        <hr>
    </div>

    <!-- Si eres admin o de obrador, Y ADEMÁS faltan cosas en el almacén, te sacamos la alerta naranja -->
    <?php if (($rol_actual == 'admin' || $rol_actual == 'obrador') && $alertas_stock > 0): ?>
        <div class="alert d-flex justify-content-between align-items-center mb-4 stock-alert-custom">
            <span><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> <strong>Atención:</strong> Hay <?= $alertas_stock ?> lote(s) con stock bajo.</span>
            <a href="stock" class="btn btn-sm btn-warning fw-bold rounded-pill">Revisar →</a>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <!-- Estas dos tarjetas solo las ven los jefes y los del obrador (cocina) -->
        <?php if ($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap"><i class="bi bi-box-seam"></i></div>
                    <h5 class="card-title fw-bold">Gestión de Stock</h5>
                    <p class="card-text text-muted">Control de lotes, almacenes y fechas de caducidad.</p>
                    <a href="stock" class="btn btn-bakery w-100 rounded-pill mt-2">Entrar a Stock</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap"><i class="bi bi-journal-richtext"></i></div>
                    <h5 class="card-title fw-bold">Catálogo y Recetas</h5>
                    <p class="card-text text-muted">Administración de productos, ingredientes y fórmulas.</p>
                    <a href="productos" class="btn btn-bakery w-100 rounded-pill mt-2">Entrar al Catálogo</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- El TPV (la caja para cobrar) lo ven los dependientes y los jefes -->
        <?php if ($rol_actual == 'admin' || $rol_actual == 'dependiente'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap" style="background-color: rgba(25, 135, 84, 0.1); color: #198754;"><i class="bi bi-shop"></i></div>
                    <h5 class="card-title fw-bold">Punto de Venta (TPV)</h5>
                    <p class="card-text text-muted">Atender clientes, caja registradora y ventas.</p>
                    <a href="pos" class="btn btn-success w-100 rounded-pill mt-2">Abrir Caja (Vender)</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Las cosas serias de personal y compras, solo para el admin -->
        <?php if ($rol_actual == 'admin'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap" style="background-color: rgba(33, 37, 41, 0.08); color: #212529;"><i class="bi bi-people"></i></div>
                    <h5 class="card-title fw-bold">Empleados</h5>
                    <p class="card-text text-muted">Control de acceso, alta de usuarios y roles del sistema.</p>
                    <a href="users" class="btn btn-dark w-100 rounded-pill mt-2">Gestionar Usuarios</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap" style="background-color: rgba(25, 135, 84, 0.08); color: #198754;"><i class="bi bi-cart-plus"></i></div>
                    <h5 class="card-title fw-bold">Compras</h5>
                    <p class="card-text text-muted">Gestión de proveedores, pedidos y recepción de mercancía.</p>
                    <a href="purchase-orders" class="btn btn-outline-success w-100 rounded-pill mt-2">Gestionar Compras</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>