<?php
require_role(['admin', 'dependiente']);
$page_title = 'TPV - BAKERP';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-bakery mb-0">Terminal de Venta (TPV)</h4>
            <p class="text-muted small mb-0">Seleccione los productos para el pedido actual</p>
        </div>
    </div>

    <div class="row">
        <!-- Columna izquierda: productos disponibles -->
        <div class="col-lg-8" id="pos_products_panel">
            <div class="row g-3">
                <?php foreach ($productsList as $p):
                    $data = [
                        'price' => (float)$p['price'],
                        'stock' => (float)$p['stock'],
                        'discounted_stock' => (float)$p['discounted_stock'],
                        'on_sale' => ((float)$p['discounted_stock'] > 0)
                    ];
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100 card-login border-0 shadow-sm">
                        <div class="card-body d-flex flex-column text-center">
                            <h6 class="fw-bold text-bakery mb-2"><?= sanitize_input($p['name']) ?></h6>

                            <div class="mb-3">
                                <span class="badge badge-ingredient small"><?= $data['stock'] ?> uds</span>
                                <?php if ($data['discounted_stock'] > 0): ?>
                                    <span class="badge bg-danger small pulse">OFERTA (<?= $data['discounted_stock'] ?>)</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <?php if ($data['on_sale']): ?>
                                    <div class="h5 fw-bold text-danger mb-0"><?= number_format($data['price'] * 0.5, 2) ?> €</div>
                                    <small class="text-muted text-decoration-line-through"><?= number_format($data['price'], 2) ?> €</small>
                                <?php else: ?>
                                    <div class="h5 fw-bold text-dark mb-0"><?= number_format($data['price'], 2) ?> €</div>
                                <?php endif; ?>
                            </div>

                            <form action="pos/add" method="POST" class="mt-auto ajax-form">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="product_name" value="<?= sanitize_input($p['name']) ?>">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-white border-0 small">Cant.</span>
                                    <input type="number" name="quantity" class="form-control border-0 text-center fw-bold bg-light" value="1" min="1" max="<?= $data['stock'] ?>">
                                </div>
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

        <!-- Columna derecha: ticket actual -->
        <div class="col-lg-4 mt-4 mt-lg-0" id="pos_ticket_panel">
            <div class="card card-login border-0 shadow rounded-4 sticky-top" style="top: 1.5rem;">
                <div class="card-header bg-white border-0 py-3 text-center">
                    <h5 class="fw-bold text-bakery mb-0">Ticket Actual</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="min-height: 150px;">
                        <?php
                        $grandTotal = 0;
                        if (empty($_SESSION['cart'])): ?>
                            <p class="text-center text-muted py-5 small">El ticket está vacío</p>
                        <?php else: ?>
                            <?php foreach ($_SESSION['cart'] as $key => $item):
                                $subtotal = $item['price'] * $item['quantity'];
                                $grandTotal += $subtotal;
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                                <div>
                                    <span class="fw-bold d-block small"><?= $item['name'] ?></span>
                                    <small class="text-muted"><?= $item['quantity'] ?> x <?= number_format($item['price'], 2) ?>€</small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-bakery d-block"><?= number_format($subtotal, 2) ?>€</span>
                                    <form action="pos/remove" method="POST" class="ajax-form remove-line-form d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="cart_key" value="<?= sanitize_input((string) $key) ?>">
                                        <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none p-0 fw-bold" title="Quitar línea">
                                            X
                                        </button>
                                    </form>
                                </div>
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

                    <form action="pos/checkout" method="POST" class="ajax-form checkout-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" id="checkout_total_raw" value="<?= number_format($grandTotal, 2, '.', '') ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Método de pago</label>
                            <select name="payment_method" id="payment_method" class="form-select form-select-sm" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                            </select>
                        </div>

                        <div class="mb-3" id="cash_amount_group">
                            <label class="form-label small fw-bold text-uppercase text-muted">Importe recibido</label>
                            <input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" class="form-control form-control-sm" value="<?= number_format($grandTotal, 2, '.', '') ?>" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                            <small class="text-muted d-block mt-1">Vuelto: <span id="change_preview">0.00</span> €</small>
                        </div>

                        <button type="submit" class="btn btn-bakery w-100 py-3 fw-bold rounded-pill shadow-sm" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                            COBRAR VENTA
                        </button>
                    </form>

                    <form action="pos/clear" method="POST" class="mt-2 text-center ajax-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none">Vaciar Ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updatePaymentUi() {
    // Ajustar campos de pago según método seleccionado
    const methodEl = document.getElementById('payment_method');
    const cashGroup = document.getElementById('cash_amount_group');
    const amountEl = document.getElementById('amount_paid');
    const changeEl = document.getElementById('change_preview');
    const totalEl = document.getElementById('checkout_total_raw');
    if (!methodEl || !cashGroup || !amountEl || !changeEl || !totalEl) return;

    const total = parseFloat(totalEl.value || '0');
    const amount = parseFloat(amountEl.value || '0');

    if (methodEl.value === 'card') {
        cashGroup.classList.add('d-none');
        amountEl.value = total.toFixed(2);
        changeEl.innerText = '0.00';
    } else {
        cashGroup.classList.remove('d-none');
        const change = Math.max(0, amount - total);
        changeEl.innerText = change.toFixed(2);
    }
}

// Actualizar paneles de productos y ticket sin recargar toda la página
function refreshPosPanels() {
    // Realizar una sola petición y reutilizar el HTML para ambos paneles
    $.get(window.location.href, function (html) {
        const $doc = $('<div>').append($.parseHTML(html));
        const productsHtml = $doc.find('#pos_products_panel').html();
        const ticketHtml = $doc.find('#pos_ticket_panel').html();

        if (typeof productsHtml !== 'undefined') {
            $('#pos_products_panel').html(productsHtml);
        }
        if (typeof ticketHtml !== 'undefined') {
            $('#pos_ticket_panel').html(ticketHtml);
        }
        updatePaymentUi();
    });
}

$(document).on('change', '#payment_method', updatePaymentUi);
$(document).on('input', '#amount_paid', updatePaymentUi);
$(function () { updatePaymentUi(); });

$(document).on('submit', '.ajax-form', function (e) {
    e.preventDefault();

    const $form = $(this);
    const isCheckout = $form.hasClass('checkout-form');
    const $btnSubmit = $form.find('button[type="submit"]').first();
    const originalText = $btnSubmit.html();

    // Mostrar estado de proceso en botón enviado
    if ($btnSubmit.length) {
        $btnSubmit.html('...');
        $btnSubmit.prop('disabled', true);
    }

    $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json'
    }).done(function (data) {
        if (data.success) {
            if (isCheckout) {
                Swal.fire({ icon: 'success', title: 'Venta procesada', text: data.message, timer: 1800, showConfirmButton: false });
            }
            refreshPosPanels();
        } else {
            Swal.fire('Atención', data.message, 'warning');
        }
    }).fail(function () {
        Swal.fire('Error', 'Fallo de conexión', 'error');
    }).always(function () {
        if ($btnSubmit.length) {
            $btnSubmit.html(originalText);
            $btnSubmit.prop('disabled', false);
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
