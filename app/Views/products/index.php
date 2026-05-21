<?php
require_role(['admin', 'obrador']);
$page_title = 'Catálogo - BAKERP';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-bakery mb-0">Catálogo de Productos</h4>
    </div>

    <div class="card card-login mb-4">
        <div class="card-body p-4">
            <form action="productos/save" method="POST" class="row g-2 ajax-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="col-md-2"><input type="text" name="sku" class="form-control" placeholder="SKU" required></div>
                <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nombre del producto" required></div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <?php foreach($product_types as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="unit" class="form-select" required>
                        <?php foreach($units as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1"><input type="number" name="price_buy" step="0.01" min="0" class="form-control" placeholder="Compra"></div>
                <div class="col-md-1"><input type="number" name="price_sell" step="0.01" min="0" class="form-control" placeholder="Venta"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-bakery w-100 fw-bold">Añadir</button></div>
            </form>
        </div>
    </div>

    <div class="mb-4">
        <input type="text" id="liveSearch" class="form-control rounded-pill shadow-sm px-4" placeholder="Buscar por nombre, ingrediente o SKU...">
    </div>

    <div class="card card-table shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-bakery text-center text-white">
                    <tr>
                        <th>SKU</th>
                        <th>Imagen</th>
                        <th class="text-start">Nombre</th>
                        <th>Tipo</th>
                        <th>Medida</th>
                        <th>C/V(€)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-center bg-white" id="productsTable">
                <?php foreach ($all_products as $p):
                    $img_src = !empty($p['image_url']) ? "assets/" . $p['image_url'] : "assets/img/no-image.png";
                ?>
                    <tr class="product-item">
                        <td class="sku-text text-muted small"><?= $p['sku'] ?></td>
                        <td>
                            <div class="position-relative d-inline-block" style="width: 45px; height: 45px;">
                                <?php if (strpos($img_src, 'no-image.png') !== false): ?>
                                    <button class="btn btn-bakery btn-sm rounded-circle position-absolute top-50 start-50 translate-middle p-0 border border-white shadow-sm"
                                            style="width:30px; height:30px;"
                                            onclick="prepare_image_modal(<?= $p['id'] ?>)"
                                            data-bs-toggle="modal"
                                            data-bs-target="#imageModal">
                                        <i class="bi bi-plus-lg" style="font-size: 16px;"></i>
                                    </button>
                                <?php else: ?>
                                    <img src="<?= $img_src ?>" width="45" height="45" class="rounded border shadow-sm" style="object-fit: cover;">
                                    <button class="btn btn-bakery btn-sm rounded-circle position-absolute top-100 start-100 translate-middle p-0 border border-white shadow-sm"
                                            style="width:22px; height:22px;"
                                            onclick="prepare_image_modal(<?= $p['id'] ?>)"
                                            data-bs-toggle="modal"
                                            data-bs-target="#imageModal">
                                        <i class="bi bi-plus-lg" style="font-size: 12px;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-start fw-bold name-text"><?= $p['name'] ?></td>
                        <td>
                            <span class="badge rounded-pill <?= ($p['product_type'] === 'Final Product') ? 'badge-final' : 'badge-ingredient' ?>">
                                <?= $product_types[$p['product_type']] ?? $p['product_type'] ?>
                            </span>
                        </td>
                        <td class="small"><?= $units[$p['unit_of_measure']] ?? $p['unit_of_measure'] ?></td>
                        <td class="text-nowrap small">
                            <span class="text-danger fw-bold"><?= number_format($p['price_buy'], 2) ?></span> /
                            <span class="text-success fw-bold"><?= number_format($p['price_sell'], 2) ?></span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-link text-dark p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical fs-5"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                    <?php if ($p['product_type'] === 'Final Product'): ?>
                                        <li><a class="dropdown-item py-2" href="recipe?id=<?= $p['id'] ?>"><i class="bi bi-journal-text me-2 text-bakery"></i> Ver Receta</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                    <?php endif; ?>
                                    <li>
                                        <button class="dropdown-item py-2" onclick='prepare_edit_modal(<?= json_encode($p) ?>)' data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="bi bi-pencil me-2 text-primary"></i> Editar producto
                                        </button>
                                    </li>
                                    <li>
                                        <form action="productos/delete" method="POST" class="ajax-form delete-form">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <button type="button" class="dropdown-item py-2 text-danger btn-delete">
                                                <i class="bi bi-trash me-2"></i> Eliminar
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header thead-bakery text-white">
                <h5 class="modal-title fw-bold">Editar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="productos/save" method="POST" class="ajax-form">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-12"><label class="small fw-bold">Nombre</label><input type="text" name="name" id="edit_name" class="form-control" required></div>
                        <div class="col-6"><label class="small fw-bold">SKU</label><input type="text" name="sku" id="edit_sku" class="form-control" required></div>
                        <div class="col-6">
                            <label class="small fw-bold">Unidad</label>
                            <select name="unit" id="edit_unit" class="form-select">
                                <?php foreach($units as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold">Tipo</label>
                            <select name="type" id="edit_type" class="form-select">
                                <?php foreach($product_types as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="small fw-bold">Compra</label><input type="number" step="0.01" min="0" name="price_buy" id="edit_buy" class="form-control"></div>
                        <div class="col-6"><label class="small fw-bold">Venta</label><input type="number" step="0.01" min="0" name="price_sell" id="edit_sell" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer border-0"><button type="submit" class="btn btn-bakery w-100 rounded-pill py-2 fw-bold">Guardar Cambios</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Imagen -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow text-center">
            <form action="productos/upload-image" method="POST" enctype="multipart/form-data" class="ajax-form">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="modal_product_id">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <h6 class="fw-bold text-bakery mb-3">Actualizar Imagen</h6>
                    <input type="file" name="product_image" class="form-control" accept="image/*" required>
                </div>
                <div class="modal-footer border-0 pb-4"><button type="submit" class="btn btn-bakery w-100 rounded-pill">Subir</button></div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('liveSearch').addEventListener('input', function(e) {
    let term = e.target.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(row => {
        let name = row.querySelector('.name-text').textContent.toLowerCase();
        let sku  = row.querySelector('.sku-text').textContent.toLowerCase();
        row.style.display = (name.includes(term) || sku.includes(term)) ? '' : 'none';
    });
});

function prepare_edit_modal(p) {
    document.getElementById('edit_id').value   = p.id;
    document.getElementById('edit_sku').value  = p.sku;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_unit').value = p.unit_of_measure;
    document.getElementById('edit_type').value = p.product_type;
    document.getElementById('edit_buy').value  = p.price_buy;
    document.getElementById('edit_sell').value = p.price_sell;
}

function prepare_image_modal(id) {
    document.getElementById('modal_product_id').value = id;
}

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = this.closest('form');
        Swal.fire({
            title: '¿Estás seguro?', text: "¡No podrás revertir esto!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, bórralo', cancelButtonText: 'Cancelar'
        }).then(result => { if (result.isConfirmed) submitAjaxForm(form); });
    });
});

document.querySelectorAll('.ajax-form:not(.delete-form)').forEach(form => {
    form.addEventListener('submit', function(e) { e.preventDefault(); submitAjaxForm(this); });
});

function submitAjaxForm(formElement) {
    const formData  = new FormData(formElement);
    const actionUrl = formElement.getAttribute('action');
    const btnSubmit = formElement.querySelector('button[type="submit"]');
    let originalText = '';
    if (btnSubmit) {
        originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
        btnSubmit.disabled = true;
    }
    fetch(actionUrl, { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Error de Red', text: 'Ocurrió un error al contactar con el servidor.' }))
    .finally(() => { if (btnSubmit) { btnSubmit.innerHTML = originalText; btnSubmit.disabled = false; } });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
