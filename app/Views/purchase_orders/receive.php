<?php
require_role(['admin']);
$page_title = 'Recibir Pedido #' . $po_id . ' - ERP Bakery';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Recibir Mercancía #<?= $po_id ?> <span class="badge bg-success">AJAX</span></h4>
        <a href="purchase-orders" class="btn btn-outline-secondary rounded-pill btn-sm">← Cancelar</a>
    </div>

    <form action="purchase-orders/receive/process" method="POST" class="ajax-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="po_id" value="<?= $po_id ?>">

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <table class="table align-middle mb-0">
                <thead class="bg-white">
                    <tr class="small text-uppercase fw-bold text-muted">
                        <th class="ps-4">Producto</th>
                        <th>Pedido</th>
                        <th>Recibido</th>
                        <th>Lote</th>
                        <th class="pe-4">Caducidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td class="ps-4">
                            <strong><?= sanitize_input($item['name']) ?></strong>
                            <input type="hidden" name="items[<?= $index ?>][item_id]"    value="<?= $item['id'] ?>">
                            <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                        </td>
                        <td class="text-primary fw-bold"><?= $item['quantity'] ?></td>
                        <td><input type="number" step="0.01" name="items[<?= $index ?>][qty_received]" class="form-control" value="<?= $item['quantity'] ?>" required></td>
                        <td><input type="text"   name="items[<?= $index ?>][lot_number]"       class="form-control" placeholder="Lote..." required></td>
                        <td class="pe-4"><input type="date" name="items[<?= $index ?>][expiration_date]" class="form-control" required></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="card-footer bg-white p-3 text-end">
                <button type="submit" class="btn btn-bakery px-5 rounded-pill fw-bold">Confirmar y Actualizar Stock</button>
            </div>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const btnSubmit  = this.querySelector('button[type="submit"]');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = 'Procesando...';
        btnSubmit.disabled = true;

        fetch(this.getAttribute('action'), { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: '¡Recibido!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.href = 'purchase-orders');
            } else {
                Swal.fire('Error', data.message, 'error');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            }
        })
        .catch(() => {
            Swal.fire('Error', 'Fallo de conexión', 'error');
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
