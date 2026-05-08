<?php
require_role(['admin', 'obrador', 'dependiente']);
$page_title = 'Stock - ERP Bakery';
require_once __DIR__ . '/../../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-bakery mb-0">Control de Inventario <span class="badge bg-success">AJAX</span></h4>
            <p class="text-muted small mb-0">Visualización de lotes y caducidades</p>
        </div>
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
                        <th class="text-end px-4">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item):
                        $days    = $item['days_left'];
                        $is_final = ($item['product_type'] === 'Final Product');

                        $bg_class = "";
                        if ($days !== null) {
                            if ($days <= 3)               $bg_class = "table-danger";
                            elseif ($days >= 4 && $days <= 7) $bg_class = "table-warning";
                            else                          $bg_class = "table-success opacity-75";
                        }
                    ?>
                    <tr class="<?= $bg_class ?> text-center">
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
                            <?php
                            if ($days === null)  echo "<span class='text-muted small'>Perenne</span>";
                            elseif ($days < 0)   echo "<span class='text-danger fw-bold small'>CADUCADO</span>";
                            else                 echo "<span class='badge bg-white text-dark border'>$days días</span>";
                            ?>

                            <?php if (has_role('admin') && $is_final && $days !== null && $days <= 3 && $days >= 0): ?>
                                <?php if ($item['lot_is_discounted']): ?>
                                    <span class="ms-2 badge border border-danger text-danger">-%50 OK</span>
                                <?php else: ?>
                                    <form action="stock/discount" method="POST" class="d-inline ms-2 ajax-discount-form">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="lot_id" value="<?= $item['lot_id'] ?>">
                                        <button type="button" class="btn btn-sm btn-danger py-0 px-2 fw-bold btn-discount" style="font-size: 0.75rem;">
                                            -50%
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-discount').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = this.closest('form');
        Swal.fire({
            title: '¿Aplicar descuento?', text: "Se aplicará un 50% de descuento a este lote por caducidad próxima.",
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, aplicar', cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                const formData  = new FormData(form);
                const actionUrl = form.getAttribute('action');
                const originalText = this.innerHTML;
                this.innerHTML = '...';
                this.disabled = true;

                fetch(actionUrl, { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '¡Descuento Aplicado!', text: data.message, timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(() => {
                    Swal.fire('Error', 'Fallo de conexión al aplicar descuento', 'error');
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
