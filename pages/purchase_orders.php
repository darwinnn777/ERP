<?php
session_start();
/**
 * Gestión de Órdenes de Compra - ERP Bakery
 * Purchase Order Management
 * ERP Bakery - 2026
 */
require_once '../config/db_erp.php';
require_once '../config/functions.php';

// SEGURIDAD: Solo los jefes pueden entrar aquí
// SECURITY: Admin access only
require_role(['admin']);

// LÓGICA DE MENSAJES DE FEEDBACK
// FEEDBACK MESSAGES LOGIC
$msg_text = "";
$msg_type = "info";

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'po_created': {
            $msg_text = "Orden de compra generada correctamente.";
            $msg_type = "success";
            break;
        }
        case 'received_ok': {
            $msg_text = "¡Éxito! La mercancía ha sido integrada en el stock con su lote.";
            $msg_type = "success";
            break;
        }
        case 'invalid_token': {
            $msg_text = "Error de seguridad: El salvoconducto (token) no es válido.";
            $msg_type = "danger";
            break;
        }
        case 'error': {
            $msg_text = "Hubo un error crítico en el servidor. Revisa el log.";
            $msg_type = "danger";
            break;
        }
        default: {
            $msg_text = "Operación finalizada.";
            $msg_type = "primary";
            break;
        }
    }
}

// FETCH DATA 
// Pillar los datos para la vista
$stmt = $pdo->query("SELECT * FROM purchase_orders ORDER BY id DESC");
$orders = $stmt->fetchAll();

// Traer la lista de productos con su precio de compra real (price_buy)
$stmt_products = $pdo->query("SELECT id, name, price_buy FROM products ORDER BY name ASC");
$products_list = $stmt_products->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Compra - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light p-4"> 

<div class="container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">Órdenes de Compra</h2>
            <p class="text-muted small">Gestión de pedidos y flujo de entrada de mercancía</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary px-3 rounded-pill fw-bold">Volver</a>
            <button class="btn btn-bakery px-4 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newOrderModal">
                + Nueva Orden
            </button>
        </div>
    </div>

    <?php if ($msg_text) { ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm rounded-4 border-0" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $msg_text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

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
                                <a href="receive_order.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">
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
            <form action="../actions/create_purchase_order.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Nueva Orden de Compra</h5>
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
/**
 * Actualizar el precio automáticamente al elegir producto / Auto-fill price
 */
function updatePrice() {
    const select = document.getElementById('prod_select');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    
    // Rellenamos el input con el precio del catálogo
    document.getElementById('ipri').value = price;
    re(); // Recalcular el total
}

/**
 * Calcular el total sobre la marcha / Real-time calculation
 */
function re() {
    const q = document.getElementById('iqty').value || 0;
    const p = document.getElementById('ipri').value || 0;
    document.getElementById('dtot').innerText = (q * p).toFixed(2);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>