<?php 

require_role(['admin']); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibir Pedido #<?= $po_id ?> - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <base href="<?= BASE_URL ?>">
</head>
<body class="admin-layout p-4">
<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="fw-bold">Recibir Mercancía #<?= $po_id ?> <span class="badge bg-success fs-6">AJAX</span></h2>
        <a href="purchase-orders" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
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
                    <?php foreach ($items as $index => $item) { ?>
                    <tr>
                        <td class="ps-4">
                            <strong><?= sanitize_input($item['name']) ?></strong>
                            <input type="hidden" name="items[<?= $index ?>][item_id]" value="<?= $item['id'] ?>">
                            <input type="hidden" name="items[<?= $index ?>][product_id]" value="<?= $item['product_id'] ?>">
                        </td>
                        <td class="text-primary fw-bold"><?= $item['quantity'] ?></td>
                        <td><input type="number" step="0.01" name="items[<?= $index ?>][qty_received]" class="form-control" value="<?= $item['quantity'] ?>" required></td>
                        <td><input type="text" name="items[<?= $index ?>][lot_number]" class="form-control" placeholder="Lote..." required></td>
                        <td class="pe-4"><input type="date" name="items[<?= $index ?>][expiration_date]" class="form-control" required></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="card-footer bg-white p-3 text-end">
                <button type="submit" class="btn btn-bakery px-5 rounded-pill fw-bold">Confirmar y Actualizar Stock</button>
            </div>
        </div>
    </form>
</div>

<script>
// El mismo rollo de AJAX de antes, pero este redirige al índice cuando acaba
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = 'Procesando...'; // Feedback visual pal usuario impaciente
        btnSubmit.disabled = true;

        const formData = new FormData(this);
        fetch(this.getAttribute('action'), { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Todo OK, chivatazo verde y pa' la lista de órdenes
                Swal.fire({ icon: 'success', title: '¡Recibido!', text: data.message, timer: 1500, showConfirmButton: false })
                .then(() => window.location.href = 'purchase-orders');
            } else {
                // Algo ha crujido en PHP (cantidades raras, sin lote...)
                Swal.fire('Error', data.message, 'error');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            }
        })
        .catch(() => {
            // El servidor no responde, F en el chat
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