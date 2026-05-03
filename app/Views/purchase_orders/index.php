<?php 

require_role(['admin']); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Compra - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <base href="<?= BASE_URL ?>">
</head>
<body class="admin-layout p-4"> 

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">Órdenes de Compra <span class="badge bg-success fs-6">AJAX</span></h2>
            <p class="text-muted small">Gestión de pedidos y flujo de entrada de mercancía</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard" class="btn btn-outline-secondary px-3 rounded-pill fw-bold">Volver</a>
            <button class="btn btn-bakery px-4 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                + Nueva Orden
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-white">
                    <tr class="text-muted small text-uppercase fw-bold">
                        <th class="ps-4 py-3">ID</th>
                        <th>Proveedor</th>
                        <th>Fecha de Pedido</th>
                        <th>Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)) { ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No hay órdenes registradas todavía.</td>
                        </tr>
                    <?php } ?>

                    <?php foreach ($orders as $o) { ?>
                    <tr>
                        <td class="ps-4 fw-bold text-bakery">#<?= $o['id'] ?></td>
                        <td><?= sanitize_input($o['provider_name']) ?></td>
                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($o['order_date'])) ?></td>
                        <td class="fw-bold"><?= number_format($o['total_amount'], 2) ?> €</td>
                        <td class="text-center">
                            <?php if ($o['status'] === 'Pendiente') { ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3">Pendiente</span>
                            <?php } else { ?>
                                <span class="badge bg-success rounded-pill px-3">Recibido</span>
                            <?php } ?>
                        </td>
                        <td class="text-end pe-4">
                            <?php if ($o['status'] === 'Pendiente') { ?>
                                <a href="purchase-orders/receive?id=<?= $o['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
                                    Recibir Mercancía
                                </a>
                            <?php } else { ?>
                                <button class="btn btn-sm btn-light rounded-pill px-3 border disabled">Completada</button>
                            <?php } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="newOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="purchase-orders/create" method="POST" class="ajax-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Nueva Orden de Compra</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Nombre del Proveedor</label>
                        <input type="text" name="provider" class="form-control rounded-3" placeholder="Ej: Harinas García S.L." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Producto del Catálogo</label>
                        <select name="product_id" id="prod_select" class="form-select rounded-3" required onchange="updatePrice()">
                            <option value="">-- Seleccionar producto --</option>
                            <?php foreach($products_list as $prod) { ?>
                                <option value="<?= $prod['id'] ?>" data-price="<?= $prod['price_buy'] ?>">
                                    <?= sanitize_input($prod['name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Cantidad</label>
                            <input type="number" step="0.01" name="quantity" id="iqty" class="form-control rounded-3" required oninput="re()">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Precio Unit.</label>
                            <input type="number" step="0.01" name="price_unit" id="ipri" class="form-control rounded-3" required oninput="re()">
                        </div>
                    </div>

                    <div class="alert alert-secondary border-0 py-2 mt-2 d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">TOTAL ESTIMADO:</span>
                        <span class="fw-bold fs-5 text-dark"><span id="dtot">0.00</span> €</span>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="submit" class="btn btn-bakery rounded-pill w-100 fw-bold py-2 shadow-sm">Generar Pedido</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pilla el precio del option seleccionado y lo mete en el input
function updatePrice() {
    const select = document.getElementById('prod_select');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    document.getElementById('ipri').value = price;
    re(); // Llamamos a la otra función para que recalcule el total
}

// Multiplica cantidad por precio. Matemáticas básicas de primaria.
function re() {
    const q = document.getElementById('iqty').value || 0;
    const p = document.getElementById('ipri').value || 0;
    document.getElementById('dtot').innerText = (q * p).toFixed(2);
}

// Magia AJAX para enviar el formulario sin que la página pegue pantallazo
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Paramos el envío normal
        
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '...'; // Efecto visual de que está pensando
        btnSubmit.disabled = true; // Bloqueamos el botón para que el usuario no haga spam de clics

        const formData = new FormData(this);
        fetch(this.getAttribute('action'), { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Si todo guay, mensajito verde y recargamos a lo bruto
                Swal.fire({ icon: 'success', title: 'Éxito', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
            } else {
                // Si ha petado el backend, avisamos
                Swal.fire('Error', data.message, 'error');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            }
        })
        .catch(() => {
            // Esto es por si se cae internet o el server muere
            Swal.fire('Error', 'Fallo de conexión', 'error');
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>