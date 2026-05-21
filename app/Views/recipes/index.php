<?php
require_role(['admin', 'obrador']);
$page_title = 'Receta - ' . sanitize_input($main_product['name']) . ' - BAKERP';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-bakery mb-0">Gestión de Receta</h4>
            <p class="text-muted mb-0 small">Producto: <strong><?= sanitize_input($main_product['name']) ?></strong> (<?= $main_product['sku'] ?>)</p>
        </div>
        <a href="productos" class="btn btn-outline-secondary btn-sm rounded-pill">← Volver al Catálogo</a>
    </div>

    <div class="row g-4">
        <!-- Formulario añadir ingrediente -->
        <div class="col-md-4">
            <div class="card card-login border-1 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Añadir Ingrediente</h5>
                    <form action="recipe/save" method="POST" class="ajax-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="final_product_id" value="<?= $final_product_id ?>">

                        <div class="mb-3">
                            <label class="small fw-bold">Seleccionar Ingrediente</label>
                            <select name="ingredient_id" class="form-select" required>
                                <option value="">-- Buscar --</option>
                                <?php foreach ($ingredients_options as $ing): ?>
                                    <option value="<?= $ing['id'] ?>">
                                        <?= sanitize_input($ing['name']) ?> (<?= $ing['unit_of_measure'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold">Cantidad Necesaria</label>
                            <div class="input-group">
                                <input type="number" name="quantity_needed" step="0.001" min="0.001" class="form-control" required placeholder="0.000">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-bakery w-100 fw-bold">Agregar a Receta</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla receta actual -->
        <div class="col-md-8">
            <div class="card card-login shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="thead-bakery text-center">
                            <tr>
                                <th class="text-start">Ingrediente</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php if (count($current_recipe) > 0): ?>
                                <?php foreach ($current_recipe as $row): ?>
                                    <tr>
                                        <td class="text-start fw-bold"><?= sanitize_input($row['name']) ?></td>
                                        <td><?= number_format($row['quantity_needed'], 3) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['unit_of_measure'] ?></span></td>
                                        <td>
                                            <form action="recipe/delete" method="POST" class="ajax-form d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="recipe_id" value="<?= $row['recipe_id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete">Quitar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-4 text-muted">No hay ingredientes en esta receta.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const isDelete = e.submitter && e.submitter.classList.contains('btn-delete');
        if (isDelete) {
            Swal.fire({
                title: '¿Quitar Ingrediente?', text: "Se eliminará este ingrediente de la receta.", icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, quitar', cancelButtonText: 'Cancelar'
            }).then(result => { if (result.isConfirmed) processAjax(this); });
        } else {
            processAjax(this);
        }
    });
});

function processAjax(form) {
    const formData  = new FormData(form);
    const actionUrl = form.getAttribute('action');
    fetch(actionUrl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Éxito', text: data.message, timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Fallo de conexión', 'error'));
}

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const form  = this.closest('form');
        const event = new Event('submit', { cancelable: true });
        event.submitter = this;
        form.dispatchEvent(event);
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
