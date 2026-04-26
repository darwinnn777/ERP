<?php
/**
 * Interfaz de Recepción de Mercancía - ERP Bakery 2026
 * Using PurchaseOrderController (MVC)
 */
session_start();
require_once '../config/functions.php';
require_once '../config/db_erp.php';

require_role(['admin']);

require_once '../app/Controllers/PurchaseOrderController.php';
$controller = new PurchaseOrderController($pdo);

// Handle POST Request (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->handleRequest();
    exit;
}

// Handle GET Request (Prepare View)
$po_id = (int)($_GET['id'] ?? 0);

try {
    $data = $controller->getReceiveOrderViewData($po_id);
    $order = $data['order'];
    $items = $data['items'];
} catch (Exception $e) {
    header("Location: purchase_orders.php?msg=error");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibir Pedido #<?= $po_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 1055; }
    </style>
</head>
<body class="bg-light p-4">

<!-- Notification Toast Container -->
<div class="toast-container"></div>

<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="fw-bold">Recibir Mercancía #<?= $po_id ?></h2>
        <a href="purchase_orders.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">Volver</a>
    </div>

    <form id="receiveOrderForm" onsubmit="submitFormAjax(event, 'receiveOrderForm')">
        <input type="hidden" name="action" value="receive_stock">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="po_id" value="<?= $po_id ?>">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-white">
                        <tr class="small text-uppercase fw-bold text-muted text-center">
                            <th class="ps-4 text-start">Producto</th>
                            <th>Pedido</th>
                            <th>Recibido</th>
                            <th>Lote</th>
                            <th class="pe-4">Caducidad</th>
                        </tr>
                    </thead>
                    <tbody class="text-center bg-white">
                        <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td class="ps-4 text-start">
                                <strong><?= sanitize_input($item['name']) ?></strong>
                                <input type="hidden" name="items[<?= $index ?>][item_id]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                            </td>
                            <td class="text-primary fw-bold"><?= $item['quantity'] ?> <?= sanitize_input($item['unit_of_measure']) ?></td>
                            <td>
                                <input type="number" step="0.01" name="items[<?= $index ?>][qty_received]" class="form-control text-center mx-auto" style="max-width: 120px;" value="<?= $item['quantity'] ?>" required>
                            </td>
                            <td>
                                <input type="text" name="items[<?= $index ?>][lot_number]" class="form-control text-center mx-auto" style="max-width: 150px;" placeholder="Lote..." required>
                            </td>
                            <td class="pe-4">
                                <input type="date" name="items[<?= $index ?>][expiration_date]" class="form-control text-center mx-auto" style="max-width: 160px;" required>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer border-0 bg-white p-4 text-end">
                <button type="submit" class="btn btn-bakery px-5 py-2 rounded-pill fw-bold shadow-sm">Confirmar y Actualizar Stock</button>
            </div>
        </div>
    </form>
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

async function submitFormAjax(event, formId) {
    event.preventDefault();
    const formData = new FormData(document.getElementById(formId));

    try {
        const response = await fetch('receive_order.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            showToast(result.message, 'success');
            setTimeout(() => {
                window.location.href = 'purchase_orders.php?msg=received_ok';
            }, 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error de conexión', 'error');
    }
}
</script>
</body>
</html>