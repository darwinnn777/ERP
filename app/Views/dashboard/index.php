<?php
$page_title = 'Panel Principal - BAKERP';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="mb-4">
        <h4 class="fw-bold text-bakery mb-0">Panel de Control</h4>
        <p class="text-muted small mb-0">Bienvenido, <strong><?= sanitize_input($nombre_usu) ?></strong>.</p>
    </div>

    <?php if (($rol_actual == 'admin' || $rol_actual == 'obrador') && $alertas_stock > 0): ?>
        <div class="alert d-flex justify-content-between align-items-center mb-4 stock-alert-custom">
            <span><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i> <strong>Atención:</strong> Hay <?= $alertas_stock ?> lote(s) con stock bajo.</span>
            <a href="stock" class="btn btn-sm btn-warning fw-bold rounded-pill">Revisar →</a>
        </div>
    <?php endif; ?>

    <div class="row g-4">

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
                    <div class="card-icon-wrap" style="background-color: rgba(108, 117, 125, 0.12); color: #6c757d;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 class="card-title fw-bold">Historial de Stock</h5>
                    <p class="card-text text-muted">Consulta de lotes caducados y seguimiento de rotación.</p>
                    <a href="stock/history" class="btn btn-outline-secondary w-100 rounded-pill mt-2">Ver Historial</a>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
