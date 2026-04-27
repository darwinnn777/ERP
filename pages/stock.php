<?php
/**
 * Gestión de Stock e Inventario - ERP Bakery 2026
 * Using StockController (MVC)
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

require_role(['admin', 'obrador', 'dependiente']);

require_once '../app/Controllers/StockController.php';
$controller = new StockController($pdo);

// Handle POST actions via Controller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleRequest();
    exit;
}

$inventory = $controller->getInventoryForView();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1055; }
    </style>
</head>
<body class="admin-layout">

<!-- Toast Container for AJAX feedback -->
<div class="toast-container"></div>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Control de Inventario</h2>
            <p class="text-muted small">Visualización de lotes y caducidades</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Volver</a>
    </div>

    <div class="card card-login border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-uppercase fw-bold text-secondary text-center">
                        <th class="px-4 text-start">Producto</th>
                        <th>Almacén</th>
                        <th>Stock</th>
                        <th>Caducidad</th>
                        <th class="text-end px-4">Estado / Acción</th>
                    </tr>
                </thead>
                <tbody id="stock-table-body">
                    <?php foreach ($inventory as $item): ?>
                    <tr class="<?= $item['bg_class'] ?> text-center" id="lot-row-<?= $item['lot_id'] ?>">
                        <td class="px-4 text-start">
                            <div class="fw-bold"><?= sanitize_input($item['product_name']) ?></div>
                            <div class="text-muted small">Lote: <?= sanitize_input($item['lot_number']) ?></div>
                        </td>
                        <td><?= sanitize_input($item['warehouse_name']) ?></td>
                        <td>
                            <span class="fw-bold"><?= $item['quantity'] ?></span>
                            <small class="text-muted"><?= $item['unit_of_measure'] ?></small>
                        </td>
                        <td>
                            <span class="fw-bold"><?= $item['expiration_date'] ? date('d/m/Y', strtotime($item['expiration_date'])) : '--' ?></span>
                        </td>
                        <td class="text-end px-4">
                            <span id="status-badge-<?= $item['lot_id'] ?>"><?= $item['status_html'] ?></span>

                            <span id="discount-area-<?= $item['lot_id'] ?>" class="ms-2">
                                <?php if ($item['lot_is_discounted']): ?>
                                    <span class="badge border border-danger text-danger">-%50 OK</span>
                                <?php elseif (has_role('admin') && $item['product_type'] === 'Final Product' && isset($item['days_left']) && $item['days_left'] <= 3 && $item['days_left'] >= 0): ?>
                                    <button onclick="applyDiscount(<?= $item['lot_id'] ?>)" class="btn btn-sm btn-danger py-0 px-2 fw-bold" style="font-size: 0.75rem;">
                                        -50%
                                    </button>
                                <?php endif; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toastHtml = `
            <div class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="d-flex">
                <div class="toast-body fw-bold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
              </div>
            </div>`;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    async function applyDiscount(lotId) {
        if (!confirm('¿Aplicar descuento del 50% a este lote?')) return;
        
        try {
            const response = await fetch('stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    action: 'apply_discount', 
                    lot_id: lotId, 
                    csrf_token: '<?= csrf_token() ?>' 
                })
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showToast(result.message, 'success');
                // Update UI without reload
                document.getElementById('status-badge-' + lotId).innerHTML = "<span class='badge bg-white text-dark border'>En oferta</span>";
                document.getElementById('discount-area-' + lotId).innerHTML = "<span class='badge border border-danger text-danger'>-%50 OK</span>";
                document.getElementById('lot-row-' + lotId).classList.replace('table-warning', 'table-danger');
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('Error de conexión', 'error');
        }
    }
</script>
</body>
</html>