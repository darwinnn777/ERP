<?php
/**
 * Panel de Control Principal / Main Dashboard
 * ERP Bakery - 2026
 */
session_start();
require_once 'db_erp.php';
require_once 'functions.php';

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
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: block !important; background-color: var(--color-fondo); min-height: 100vh;">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: var(--color-bakery);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">🍞 ERP BAKERY</a>
    
    <div class="d-flex align-items-center text-white">
        <span class="me-3">
            Hola, <b><?= sanitize_input($nombre_usu) ?></b> 
            <small>(<?= strtoupper($rol_actual) ?>)</small>
        </span>
        
        <form action="logout.php" method="POST" class="m-0 d-inline">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-sm btn-outline-light" 
                    onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?');">
                Cerrar Sesión
            </button>
        </form>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-bakery fw-bold">Panel de Control General</h2>
            <p class="text-muted">Bienvenido al sistema de gestión. Selecciona una opción abajo.</p>
            <hr>
        </div>
    </div>

    <?php if(($rol_actual == 'admin' || $rol_actual == 'obrador') && $alertas_stock > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center rounded-4">
            <span>
                ⚠️ <strong>Atención:</strong> Hay <?= $alertas_stock ?> lotes con stock bajo en el almacén.
            </span>
            <a href="stock.php" class="btn btn-sm btn-warning fw-bold text-uppercase rounded-pill">Revisar</a>
        </div>
    <?php endif; ?>

    <div class="row g-4 text-center">
        
        <?php if($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h1 class="mb-3">📦</h1>
                    <h4 class="fw-bold">Gestión de Stock</h4>
                    <p class="card-text text-muted small">Control de lotes, almacenes y fechas de caducidad.</p>
                    <a href="stock.php" class="btn btn-bakery w-100 rounded-pill fw-bold">Entrar a Stock</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4">
                <div class="card-body p-4">
                    <h1 class="mb-3">🥐</h1>
                    <h4 class="fw-bold">Catálogo y Recetas</h4>
                    <p class="card-text text-muted small">Administración de productos, ingredientes y fórmulas.</p>
                    <a href="products_management.php" class="btn btn-bakery w-100 rounded-pill fw-bold">Entrar a Catálogo</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($rol_actual == 'admin' || $rol_actual == 'dependiente'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4 border-bakery">
                <div class="card-body p-4">
                    <h1 class="mb-3">🛒</h1>
                    <h4 class="fw-bold">Punto de Venta (TPV)</h4>
                    <p class="card-text text-muted small">Atender clientes, caja registradora y ventas en tiempo real.</p>
                    <a href="ventas.php" class="btn btn-success w-100 rounded-pill fw-bold">Abrir Caja (Vender)</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($rol_actual == 'admin'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4"> 
                <div class="card-body p-4">
                    <h1 class="mb-3">👥</h1>
                    <h4 class="fw-bold">Empleados</h4>
                    <p class="card-text text-muted small">Control de acceso, alta de usuarios y roles del sistema.</p>
                    <a href="users_management.php" class="btn btn-dark w-100 rounded-pill fw-bold">Gestionar Usuarios</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 rounded-4"> 
                <div class="card-body p-4">
                    <h1 class="mb-3">🚚</h1>
                    <h4 class="fw-bold">Órdenes de Compra</h4>
                    <p class="card-text text-muted small">Pedir suministros a proveedores y recibir mercancía.</p>
                    <a href="purchase_orders.php" class="btn btn-outline-secondary w-100 rounded-pill fw-bold">Ver Pedidos</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>