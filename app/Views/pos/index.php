<?php require_role(['admin', 'dependiente']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPV - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Traemos SweetAlert2 para sacar ventanitas de alerta más bonitas que el alert() feo por defecto -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-layout p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold mb-0">Terminal de Venta (TPV) <span class="badge bg-success fs-6">AJAX</span></h2>
            <p class="text-muted small">Seleccione los productos para el pedido actual</p>
        </div>
        <a href="dashboard" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Volver al Menú</a>
    </div>

    <div class="row">
        <!-- Columna izquierda: Todos los productos disponibles para vender -->
        <div class="col-lg-8">
            <div class="row g-3">
                <!-- Iteramos sobre los productos que nos mandó el controlador -->
                <?php foreach ($productsList as $p): 
                    $data = get_product_data($pdo, $p['id']); 
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 card-login border-0 shadow-sm">
                        <div class="card-body d-flex flex-column text-center">
                            <h6 class="fw-bold text-bakery mb-2"><?= sanitize_input($p['name']) ?></h6>
                            
                            <div class="mb-3">
                                <span class="badge badge-ingredient small"><?= $data['stock'] ?> uds</span>
                                <!-- Si hay lotes que caducan pronto, ponemos aviso llamativo de OFERTA -->
                                <?php if ($data['discounted_stock'] > 0): ?>
                                    <span class="badge bg-danger small pulse">OFERTA (<?= $data['discounted_stock'] ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <!-- Aplicamos el descuento visualmente tachando el precio original si toca -->
                                <?php if ($data['on_sale']): ?>
                                    <div class="h5 fw-bold text-danger mb-0"><?= number_format($data['price'] * 0.5, 2) ?> €</div>
                                    <small class="text-muted text-decoration-line-through"><?= number_format($data['price'], 2) ?> €</small>
                                <?php else: ?>
                                    <div class="h5 fw-bold text-dark mb-0"><?= number_format($data['price'], 2) ?> €</div>
                                <?php endif; ?>
                            </div>

                            <!-- Formulario con clase "ajax-form" para no recargar la página entera al añadir productos -->
                            <form action="pos/add" method="POST" class="mt-auto ajax-form">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="product_name" value="<?= sanitize_input($p['name']) ?>">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-white border-0 small">Cant.</span>
                                    <input type="number" name="quantity" class="form-control border-0 text-center fw-bold bg-light" value="1" min="1" max="<?= $data['stock'] ?>">
                                </div>
                                <!-- Si no hay stock, desactivamos el botón para que no puedan darle por error -->
                                <button type="submit" class="btn btn-bakery btn-sm w-100 rounded-pill py-2" <?= ($data['stock'] <= 0) ? 'disabled' : '' ?>>
                                    <?= ($data['stock'] > 0) ? 'Añadir al Ticket' : 'Sin Stock' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Columna derecha: El ticket de la compra en tiempo real -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card card-login border-0 shadow rounded-4 sticky-top" style="top: 2rem;">
                <div class="card-header bg-white border-0 py-3 text-center">
                    <h5 class="fw-bold text-bakery mb-0">Ticket Actual</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="min-height: 150px;">
                        <?php 
                        $grandTotal = 0;
                        // Si no hay nada, sacamos mensaje de vacío. Si hay, iteramos la sesión del carrito.
                        if(empty($_SESSION['cart'])): ?>
                            <p class="text-center text-muted py-5 small">El ticket está vacío</p>
                        <?php else: ?>
                            <?php foreach ($_SESSION['cart'] as $item): 
                                $subtotal = $item['price'] * $item['quantity'];
                                $grandTotal += $subtotal;
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                                <div>
                                    <span class="fw-bold d-block small"><?= $item['name'] ?></span>
                                    <small class="text-muted"><?= $item['quantity'] ?> x <?= number_format($item['price'], 2) ?>€</small>
                                </div>
                                <span class="fw-bold text-bakery"><?= number_format($subtotal, 2) ?>€</span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-top pt-3">
                        <span class="h5 fw-bold mb-0">TOTAL</span>
                        <span class="h3 fw-bold text-bakery"><?= number_format($grandTotal, 2) ?> €</span>
                    </div>
                    
                    <!-- Botón de cobrar (también va por AJAX para no perder fluidez) -->
                    <form action="pos/checkout" method="POST" class="ajax-form checkout-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-bakery w-100 py-3 fw-bold rounded-pill shadow-sm" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                            COBRAR VENTA
                        </button>
                    </form>
                    
                    <!-- Botón para vaciar si el cliente se arrepiente y se va -->
                    <form action="pos/clear" method="POST" class="mt-2 text-center ajax-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none">Vaciar Ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Aquí interceptamos todos los formularios para que actúen en segundo plano (AJAX)
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Paramos el envío normal de toda la vida
        
        const isCheckout = this.classList.contains('checkout-form');
        const formData = new FormData(this);
        const actionUrl = this.getAttribute('action');
        
        // Ponemos el botón en modo "cargando..." para que no le den dos veces sin querer
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '...';
        btnSubmit.disabled = true;

        // Enviamos los datos por detrás usando fetch al controlador
        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (isCheckout) {
                    // Si era cobrar y ha ido bien, sacamos el pop-up chulo de éxito de SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: '¡Venta procesada!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => location.reload()); // Y refrescamos
                } else {
                    // Si era solo añadir al carrito o vaciarlo, refrescamos directamente
                    location.reload(); 
                }
            } else {
                // Si falla algo (ej. no hay stock), sacamos alerta y reactivamos el botón
                Swal.fire('Atención', data.message, 'warning');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            }
        })
        .catch(error => {
            // Por si se cae internet o el servidor
            Swal.fire('Error', 'Fallo de conexión', 'error');
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
        });
    });
});
</script>
</body>
</html>