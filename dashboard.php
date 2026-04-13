<?php
/**
 * Panel de Control Principal / Main Dashboard
 * ERP Bakery - 2026
 */
session_start();
require_once 'db_erp.php';
require_once 'functions.php';
 
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}
 
$rol_actual = get_user_role();
$nombre_usu = $_SESSION['full_name'] ?? $_SESSION['usuario'] ?? 'Compañero';
 
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
    <title>Panel Principal — ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
 
    <!-- Grain overlay -->
    <div class="grain-overlay"></div>
 
    <!-- NAVBAR -->
    <nav class="dash-navbar">
        <div class="nav-inner">
            <div class="nav-brand">
                <div class="brand-icon">🥐</div>
                <span class="brand-text">ERP <em>Bakery</em></span>
            </div>
            <div class="nav-user">
                <div class="user-info">
                    <span class="user-greeting">Hola de nuevo,</span>
                    <span class="user-name"><?= sanitize_input($nombre_usu) ?></span>
                </div>
                <div class="role-badge"><?= strtoupper($rol_actual) ?></div>
                <form action="logout.php" method="POST" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <button type="submit" class="btn-logout"
                            onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?');">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Salir
                    </button>
                </form>
            </div>
        </div>
    </nav>
 
    <!-- MAIN CONTENT -->
    <main class="dash-main">
 
        <!-- Header section -->
        <div class="dash-header">
            <div class="header-text">
                <h1 class="dash-title">Panel de Control</h1>
                <p class="dash-subtitle">Gestiona tu obrador desde un solo lugar.</p>
            </div>
            <div class="header-date">
                <span id="live-date"></span>
            </div>
        </div>
 
        <!-- Stock alert -->
        <?php if(($rol_actual == 'admin' || $rol_actual == 'obrador') && $alertas_stock > 0): ?>
        <div class="stock-alert">
            <div class="alert-left">
                <div class="alert-icon">⚠️</div>
                <div>
                    <strong><?= $alertas_stock ?> lote<?= $alertas_stock > 1 ? 's' : '' ?></strong> con stock bajo en el almacén
                </div>
            </div>
            <a href="stock.php" class="alert-btn">Revisar ahora →</a>
        </div>
        <?php endif; ?>
 
        <!-- MODULE GRID -->
        <div class="module-grid">
 
            <?php if($rol_actual == 'admin' || $rol_actual == 'obrador'): ?>
 
            <a href="stock.php" class="module-card" style="--card-accent: #a35d45; --delay: 0.05s">
                <div class="module-icon">📦</div>
                <div class="module-body">
                    <h3 class="module-title">Gestión de Stock</h3>
                    <p class="module-desc">Control de lotes, almacenes y fechas de caducidad.</p>
                </div>
                <div class="module-footer">
                    <span>Entrar</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="card-glow"></div>
            </a>
 
            <a href="products_management.php" class="module-card" style="--card-accent: #b87057; --delay: 0.1s">
                <div class="module-icon">📋</div>
                <div class="module-body">
                    <h3 class="module-title">Catálogo y Recetas</h3>
                    <p class="module-desc">Administración de productos, ingredientes y fórmulas.</p>
                </div>
                <div class="module-footer">
                    <span>Entrar</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="card-glow"></div>
            </a>
 
            <?php endif; ?>
 
            <?php if($rol_actual == 'admin' || $rol_actual == 'dependiente'): ?>
 
            <a href="ventas.php" class="module-card module-card--highlight" style="--card-accent: #4a9c6f; --delay: 0.15s">
                <div class="module-icon">🏪</div>
                <div class="module-body">
                    <h3 class="module-title">Punto de Venta</h3>
                    <p class="module-desc">Atender clientes, caja registradora y ventas en tiempo real.</p>
                </div>
                <div class="module-footer">
                    <span>Abrir Caja</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="card-glow"></div>
            </a>
 
            <?php endif; ?>
 
            <?php if($rol_actual == 'admin'): ?>
 
            <a href="users_management.php" class="module-card" style="--card-accent: #5a6472; --delay: 0.2s">
                <div class="module-icon">👥</div>
                <div class="module-body">
                    <h3 class="module-title">Empleados</h3>
                    <p class="module-desc">Control de acceso, alta de usuarios y roles del sistema.</p>
                </div>
                <div class="module-footer">
                    <span>Gestionar</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="card-glow"></div>
            </a>
 
            <a href="purchase_orders.php" class="module-card" style="--card-accent: #7a6248; --delay: 0.25s">
                <div class="module-icon">🚚</div>
                <div class="module-body">
                    <h3 class="module-title">Órdenes de Compra</h3>
                    <p class="module-desc">Pedir suministros a proveedores y recibir mercancía.</p>
                </div>
                <div class="module-footer">
                    <span>Ver Pedidos</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="card-glow"></div>
            </a>
 
            <?php endif; ?>
 
        </div>
    </main>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live date
        function updateDate() {
            const now = new Date();
            const opts = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('live-date').textContent =
                now.toLocaleDateString('es-ES', opts);
        }
        updateDate();
 
        // Staggered card entrance
        document.querySelectorAll('.module-card').forEach((card, i) => {
            card.style.animationDelay = card.style.getPropertyValue('--delay');
        });
    </script>
</body>
</html>