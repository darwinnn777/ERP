<?php
// Siempre iniciamos sesion por si acaso no esta iniciada
// Always start the session in case it hasn't been started
session_start();

require_once 'db_erp.php';
require_once 'functions.php';

// Si no estas logueado, te manda al login directo
// If you are not logged in, it sends you directly to the login
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$rol_actual = get_user_role();
$nombre_usu = $_SESSION['usuario'] ?? 'Compañero'; 

// TODO: Mejorar esta consulta luego. 
// Saca los productos con poco stock para las alertas del PDF.
// TODO: Improve this query later.
// Get products with low stock for PDF alerts.
// Cambio para PostgreSQL: quitamos las comillas inclinadas (backticks)
$sql_stock = "SELECT COUNT(*) as faltan FROM stock_lots WHERE quantity < 10";
try {
    $stmt = $pdo->query($sql_stock);
    $resultado = $stmt->fetch();
    $alertas_stock = $resultado['faltan'];
} catch (Exception $e) {
    $alertas_stock = 0; // Si falla que no rompa la pagina / If it fails, don't break the page
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - TFG ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: block; height: auto; background-color: var(--color-fondo);">

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm" style="background-color: var(--color-bakery);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">ERP Bakery TFG</a>
    <div class="d-flex align-items-center text-white">
        <span class="me-3">Hola, <b><?= sanitize_input($nombre_usu) ?></b> (Rol: <?= strtoupper($rol_actual) ?>)</span>
        <a href="logout.php" class="btn btn-sm btn-outline-light" 
            onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?');">
            Cerrar Sesión
        </a>
    </div>
  </div>
</nav>

<div class="container mt-5">
    
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="text-bakery">Panel de Control General</h2>
            <p class="text-muted">Bienvenido al sistema de gestión. Selecciona una opción abajo.</p>
        </div>
    </div>

    <?php if($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
        <?php if($alertas_stock > 0): ?>
            <div class="alert alert-warning">
                Atención: Hay <?= $alertas_stock ?> lotes con stock bajo. <a href="stock.php">Revisar almacén</a>.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row g-4">
        
        <?php if($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4>Gestión de Stock</h4>
                    <p class="card-text text-muted">Ver lotes, almacenes y caducidades.</p>
                    <a href="stock.php" class="btn btn-bakery w-100">Entrar a Stock</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4>Catálogo de productos</h4>
                    <p class="card-text text-muted">Añadir productos, quitar productos.</p>
                    <a href="products_management.php" class="btn btn-bakery w-100">Entrar a catálogo</a>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <?php if($rol_actual == 'admin'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm"> 
                <div class="card-body text-center">
                    <h4>Empleados</h4>
                    <p class="card-text text-muted">Dar de alta usuarios y asignar roles.</p>
                    <a href="users_management.php" class="btn btn-bakery w-100">Gestionar Usuarios</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($rol_actual == 'admin' || $rol_actual == 'dependiente'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4>Punto de Venta</h4>
                    <p class="card-text text-muted">Atender clientes y registrar ventas.</p>
                    <button class="btn btn-secondary w-100" disabled>En desarrollo...</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if($rol_actual == 'admin'): ?>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h4>Estadísticas</h4>
                    <p class="card-text text-muted">Beneficios y ventas diarias.</p>
                    <button class="btn btn-secondary w-100" disabled>Próximamente...</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>