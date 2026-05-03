<?php 
require_role(['admin', 'obrador', 'dependiente']); 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - ERP Bakery (MVC + AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <base href="<?= BASE_URL ?>">
</head>
<body class="admin-layout">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Control de Inventario <span class="badge bg-success fs-6">AJAX</span></h2>
            <p class="text-muted small">Visualización de lotes y caducidades</p>
        </div>
        <a href="dashboard" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Volver al Dashboard</a>
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
                    <?php 
                    // para sacar todo el stock que nos mandó el controlador
                    foreach ($inventory as $item): 
                        $days = $item['days_left'];
                        $is_final = ($item['product_type'] === 'Final Product');
                        
                        $bg_class = "";
                        // Lógica de semáforo para ponerle colorines a las filas según lo jodido que esté el producto
                        if ($days !== null) {
                            if ($days <= 3) {
                                $bg_class = "table-danger"; // Alerta roja, que se pasa
                            } elseif ($days >= 4 && $days <= 7) {
                                $bg_class = "table-warning"; //le queda una semanita
                            } else {
                                $bg_class = "table-success opacity-75";
                            }
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
                            // Chivato del estado del producto
                            if ($days === null) {
                                echo "<span class='text-muted small'>Perenne</span>"; // Esto no caduca 
                            } elseif ($days < 0) {
                                echo "<span class='text-danger fw-bold small'>CADUCADO</span>"; 
                            } else {
                                echo "<span class='badge bg-white text-dark border'>$days días</span>"; // Cuenta atrás
                            }
                            ?>

                            <?php 
                            // Condiciones para mostrar el botón de descuento: 
                            // 1. Eres el admin. 2. Es un producto final. 3. Le quedan 3 días o menos (pero no está caducado)
                            if (has_role('admin') && $is_final && $days !== null && $days <= 3 && $days >= 0): ?>
                                <?php if ($item['lot_is_discounted']): ?>
                                    <!-- Si ya tiene el descuento, mostramos la etiquetita y listos -->
                                    <span class="ms-2 badge border border-danger text-danger">-%50 OK</span>
                                <?php else: ?>
                                    <!-- Si no lo tiene, plantamos el formulario -->
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
        
        // Sacamos un popup para preguntar antes de liarla
        Swal.fire({
            title: '¿Aplicar descuento?',
            text: "Se aplicará un 50% de descuento a este lote por caducidad próxima.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, aplicar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Si el usuario dice que pa'lante, preparamos los datos del formulario
                const formData = new FormData(form);
                const actionUrl = form.getAttribute('action');

                // Cambiamos el texto del botón y lo bloqueamos para que no le den mas
                const originalText = this.innerHTML;
                this.innerHTML = '...';
                this.disabled = true;

                // Mandamos la petición por lo bajini con Fetch API 
                fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json()) // Parseamos la respuesta a JSON
                .then(data => {
                    if (data.success) {
                        // Si el controlador nos da el OK, recargamos la página
                        Swal.fire({
                            icon: 'success',
                            title: '¡Descuento Aplicado!',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        // Si algo fue mal (ej. ya estaba aplicado), avisamos y desbloqueamos el botón
                        Swal.fire('Error', data.message, 'error');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    // Si se cae el internet o peta el servidor, lo capturamos aquí
                    Swal.fire('Error', 'Fallo de conexión al aplicar descuento', 'error');
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            }
        });
    });
});
</script>
</body>
</html>