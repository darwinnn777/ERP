<?php
require_role(['admin']);
$page_title = 'Recibir Pedido #' . $po_id . ' - ERP Bakery';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start flex-column flex-lg-row gap-3 mb-3">
        <div>
            <h4 class="fw-bold text-bakery mb-2 d-flex align-items-center flex-wrap gap-2">
                <span class="receive-goods-icon d-inline-flex align-items-center justify-content-center rounded-3 bg-success bg-opacity-10 text-success flex-shrink-0">
                    <i class="bi bi-truck"></i>
                </span>
                <span>Recibir mercancía</span>
                <span class="badge bg-secondary rounded-pill align-middle">#<?= (int) $po_id ?></span>
            </h4>
            <p class="text-muted small mb-0">
                <?php if (!empty($order['provider_name'])): ?>
                    Proveedor: <strong><?= sanitize_input($order['provider_name']) ?></strong>
                    <span class="mx-1">·</span>
                <?php endif; ?>
                Registra cantidad recibida, lote y caducidad; el stock se actualizará al confirmar.
            </p>
        </div>
        <a href="purchase-orders" class="btn btn-outline-secondary rounded-pill btn-sm fw-bold px-3 align-self-stretch align-self-lg-center text-center">
            <i class="bi bi-arrow-left me-1"></i> Volver a órdenes
        </a>
    </div>

    <div class="alert alert-success border-0 rounded-3 py-2 px-3 mb-4 d-flex align-items-start gap-2 small shadow-sm">
        <i class="bi bi-box-seam fs-5 flex-shrink-0"></i>
        <div>
            <strong class="d-block text-success">Entrada de almacén</strong>
            Cada línea genera un lote en inventario. Revisa que el número de lote y la fecha de caducidad coincidan con el envío físico.
        </div>
    </div>

    <form action="purchase-orders/receive/process" method="POST" class="ajax-form">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="po_id" value="<?= $po_id ?>">

        <div class="card card-login border-0 shadow-sm rounded-4 overflow-hidden receive-goods-card">
            <div class="card-header bg-light border-0 py-3 px-4 d-flex align-items-center gap-2">
                <i class="bi bi-clipboard-check text-success fs-5"></i>
                <div>
                    <span class="fw-bold text-secondary text-uppercase small d-block" style="letter-spacing: 0.04em;">Líneas pendientes de recepción</span>
                    <span class="text-muted small">Completa los campos marcados como obligatorios en cada fila.</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase fw-bold text-secondary text-center">
                            <th class="px-4 py-3 text-start">Producto</th>
                            <th class="py-3">Pedido</th>
                            <th class="py-3">Medida</th>
                            <th class="py-3">Recibido</th>
                            <th class="py-3 text-start">Lote</th>
                            <th class="pe-4 py-3 text-start">Caducidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td class="px-4 text-start">
                                <span class="fw-bold d-block"><?= sanitize_input($item['name']) ?></span>
                                <input type="hidden" name="items[<?= $index ?>][item_id]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                                    <?= sanitize_input((string) $item['quantity']) ?>
                                </span>
                            </td>
                            <td class="text-center text-muted small">
                                <?= sanitize_input($item['unit_of_measure'] ?? '') ?>
                            </td>
                            <td class="text-center">
                                <label class="visually-hidden" for="qty-<?= $index ?>">Cantidad recibida</label>
                                <input id="qty-<?= $index ?>"
                                       type="number"
                                       step="0.01"
                                       name="items[<?= $index ?>][qty_received]"
                                       class="form-control form-control-sm text-end mx-auto receive-input-qty"
                                       value="<?= htmlspecialchars((string) $item['quantity'], ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                            </td>
                            <td class="text-start">
                                <label class="visually-hidden" for="lot-<?= $index ?>">Número de lote</label>
                                <input id="lot-<?= $index ?>"
                                       type="text"
                                       name="items[<?= $index ?>][lot_number]"
                                       class="form-control form-control-sm receive-input-lot"
                                       placeholder="Ej. L2026-001"
                                       required>
                            </td>
                            <td class="pe-4 text-start">
                                <label class="visually-hidden" for="exp-<?= $index ?>">Fecha de caducidad</label>
                                <input id="exp-<?= $index ?>"
                                       type="date"
                                       name="items[<?= $index ?>][expiration_date]"
                                       class="form-control form-control-sm receive-input-date"
                                       required>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top p-3 px-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <p class="text-muted small mb-0">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Validación en servidor antes de crear lotes y cerrar la orden.
                </p>
                <button type="submit" class="btn btn-bakery px-4 px-lg-5 py-2 rounded-pill fw-bold shadow-sm text-nowrap">
                    <i class="bi bi-check2-circle me-1"></i> Confirmar y actualizar stock
                </button>
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
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Procesando...';
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
