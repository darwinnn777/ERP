<?php
session_start();
require_once 'db_erp.php';
require_once 'functions.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$rol_actual = get_user_role();
$nombre_usu = $_SESSION['usuario'] ?? 'Compañero'; 

$alertas_stock = 0;
try {
    $sql_stock = "SELECT COUNT(*) as faltan FROM stock_lots WHERE quantity < 10";
    $stmt = $pdo->query($sql_stock);
    $resultado = $stmt->fetch();
    $alertas_stock = $resultado['faltan'] ?? 0;
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
<body style="background-color: var(--color-fondo);">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: var(--color-bakery);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">ERP Bakery</a>
    <div class="d-flex align-items-center text-white">
        <span class="me-3">Usuario: <b><?= sanitize_input($nombre_usu) ?></b> (<?= strtoupper($rol_actual) ?>)</span>
        <form action="logout.php" method="POST" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-sm btn-outline-light" onclick="return confirm('¿Cerrar sesión?');">
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
            <hr>
        </div>
    </div>

    <?php if(($rol_actual == 'admin' || $rol_actual == 'obrador') && $alertas_stock > 0): ?>
        <div class="alert alert-warning border-1 shadow-sm">
            Atención: Existen <?= $alertas_stock ?> lotes con stock bajo. 
            <a href="stock.php" class="alert-link">Revisar almacén</a>.
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="fw-bold">Gestión de Stock</h4>
                    <p class="text-muted small">Inventario, lotes y caducidades.</p>
                    <a href="stock.php" class="btn btn-bakery w-100">Acceder</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="fw-bold">Catálogo</h4>
                    <p class="text-muted small">Productos, ingredientes y recetas.</p>
                    <a href="products_management.php" class="btn btn-bakery w-100">Acceder</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($rol_actual == 'admin'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4 class="fw-bold">Usuarios</h4>
                    <p class="text-muted small">Gestión de empleados y roles.</p>
                    <a href="users_management.php" class="btn btn-bakery w-100">Acceder</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>