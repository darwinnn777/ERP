<?php
/**
 * Panel de Control Principal / Main Dashboard
 * ERP Bakery - 2026
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';
 
// Si no estás logueado, te manda al login directo
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
 
$rol_actual = get_user_role();
// Ajuste de variable de sesión para el nombre
$nombre_usu = $_SESSION['full_name'] ?? $_SESSION['usuario'] ?? 'Compañero';
 
// Saca los productos con poco stock para las alertas
$sql_stock = "SELECT COUNT(*) as faltan FROM stock_lots WHERE quantity < 10";
try {
    $stmt = $pdo->query($sql_stock);
    $resultado = $stmt->fetch();
    $alertas_stock = (int)($resultado['faltan'] ?? 0);
} catch (Exception $e) {
    $alertas_stock = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons: librería de iconos oficial de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
 
<!-- admin-layout sobreescribe el body del login para que no quede centrado -->
<body class="admin-layout">
 
<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm navbar-dashboard"
     style="background-color: var(--color-bakery);">
    <div class="container">
 
        <!-- Logo: la imagen está en la carpeta img/ del proyecto -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="#">
            <img src="../img_products/logo.png"
                 alt="Logo ERP Bakery"
                 class="navbar-logo">
            ERP Bakery
        </a>
 
        <!-- Info del usuario a la derecha -->
        <div class="d-flex align-items-center gap-3 text-white">
 
            <!-- Nombre y rol -->
            <div class="text-end d-none d-md-block">
                <div class="fw-semibold" style="font-size: 0.9rem;">
                    <?= sanitize_input($nombre_usu) ?>
                </div>
                <span class="badge-rol"><?= strtoupper($rol_actual) ?></span>
            </div>
 
            <!-- Botón de cerrar sesión -->
            <form action="../actions/logout.php" method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-sm btn-outline-light rounded-pill px-3"
                        onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?');">
                    Cerrar sesión
                </button>
            </form>
        </div>
 
    </div>
</nav>
<!-- ================================================== -->
 
 
<div class="container mt-5">
 
    <!-- Título de la página -->
    <div class="mb-4">
        <h2 class="fw-bold text-bakery">Panel de Control</h2>
        <p class="text-muted">Bienvenido, <strong><?= sanitize_input($nombre_usu) ?></strong>.
            Selecciona un módulo para empezar.</p>
        <hr>
    </div>
 
 
    <!-- Alerta de stock bajo (solo admin y obrador) -->
    <?php if (($rol_actual == 'admin' || $rol_actual == 'obrador') && $alertas_stock > 0): ?>
        <div class="alert d-flex justify-content-between align-items-center mb-4 stock-alert-custom">
            <span>
                <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> <strong>Atención:</strong>
                Hay <?= $alertas_stock ?> lote(s) con stock bajo en el almacén.
            </span>
            <a href="stock.php" class="btn btn-sm btn-warning fw-bold rounded-pill">
                Revisar →
            </a>
        </div>
    <?php endif; ?>
 
 
    <!-- ============ TARJETAS DE MÓDULOS ============ -->
    <div class="row g-4">
 
 
        <!-- STOCK — visible para admin y obrador -->
        <?php if ($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h5 class="card-title fw-bold">Gestión de Stock</h5>
                    <p class="card-text text-muted">
                        Control de lotes, almacenes y fechas de caducidad.
                    </p>
                    <a href="stock.php" class="btn btn-bakery w-100 rounded-pill mt-2">
                        Entrar a Stock
                    </a>
                </div>
            </div>
        </div>
 
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap">
                        <i class="bi bi-journal-richtext"></i>
                    </div>
                    <h5 class="card-title fw-bold">Catálogo y Recetas</h5>
                    <p class="card-text text-muted">
                        Administración de productos, ingredientes y fórmulas.
                    </p>
                    <a href="products_management.php" class="btn btn-bakery w-100 rounded-pill mt-2">
                        Entrar al Catálogo
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
 
 
        <!-- TPV — visible para admin y dependiente -->
        <?php if ($rol_actual == 'admin' || $rol_actual == 'dependiente'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap" style="background-color: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h5 class="card-title fw-bold">Punto de Venta (TPV)</h5>
                    <p class="card-text text-muted">
                        Atender clientes, caja registradora y ventas en tiempo real.
                    </p>
                    <a href="ventas.php" class="btn btn-success w-100 rounded-pill mt-2">
                        Abrir Caja (Vender)
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
 
 
        <!-- EMPLEADOS y COMPRAS — solo admin -->
        <?php if ($rol_actual == 'admin'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap" style="background-color: rgba(33, 37, 41, 0.08); color: #212529;">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="card-title fw-bold">Empleados</h5>
                    <p class="card-text text-muted">
                        Control de acceso, alta de usuarios y roles del sistema.
                    </p>
                    <a href="users_management.php" class="btn btn-dark w-100 rounded-pill mt-2">
                        Gestionar Usuarios
                    </a>
                </div>
            </div>
        </div>
 
        <div class="col-md-4">
            <div class="card h-100 shadow-sm module-card">
                <div class="card-body p-4">
                    <div class="card-icon-wrap" style="background-color: rgba(108, 117, 125, 0.1); color: #6c757d;">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h5 class="card-title fw-bold">Órdenes de Compra</h5>
                    <p class="card-text text-muted">
                        Pedir suministros a proveedores y recibir mercancía.
                    </p>
                    <a href="purchase_orders.php" class="btn btn-outline-secondary w-100 rounded-pill mt-2">
                        Ver Pedidos
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
 
 
    </div>
    <!-- ============================================= -->
 
</div>
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>