<?php
/**
 * TPV / Point of Sale - ERP Bakery 2026
 * Gestión de ventas con control de stock FIFO y trazabilidad
 */

session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

// SEGURIDAD: solo usuarios autorizados
// SECURITY: only authorized roles
require_role(['admin', 'dependiente']);

// Inicializar carrito
// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = "";

// =========================
// PROCESAMIENTO DE ACCIONES
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    // =========================
    // AÑADIR PRODUCTO
    // =========================
    if ($action === 'add') {

        $prodId = (int)$_POST['product_id'];
        $qtyRequested = (int)$_POST['quantity'];
        $prodInfo = get_product_data($pdo, $prodId);

        if ($qtyRequested <= 0) {
            $message = "<div class='alert alert-warning text-center'>Cantidad inválida</div>";
        } elseif ($qtyRequested > $prodInfo['stock']) {
            $message = "<div class='alert alert-warning text-center'>
                            Solo hay {$prodInfo['stock']} unidades disponibles
                        </div>";
        } else {

            $finalPrice = $prodInfo['on_sale'] ? ($prodInfo['price'] * 0.5) : $prodInfo['price'];
            $cartKey = $prodId . '_' . ($prodInfo['on_sale'] ? 'sale' : 'normal');

            if (isset($_SESSION['cart'][$cartKey])) {

                $newQty = $_SESSION['cart'][$cartKey]['quantity'] + $qtyRequested;

                if ($newQty > $prodInfo['stock']) {
                    $message = "<div class='alert alert-warning text-center'>Stock insuficiente</div>";
                } else {
                    $_SESSION['cart'][$cartKey]['quantity'] = $newQty;
                }

            } else {
                $_SESSION['cart'][$cartKey] = [
                    'id' => $prodId,
                    'name' => sanitize_input($_POST['product_name']) . ($prodInfo['on_sale'] ? ' (OFERTA)' : ''),
                    'price' => $finalPrice,
                    'quantity' => $qtyRequested,
                    'is_sale_item' => $prodInfo['on_sale']
                ];
            }

            header("Location: point_of_sale.php");
            exit;
        }
    }

    // =========================
    // COBRAR VENTA
    // =========================
    if ($action === 'checkout' && !empty($_SESSION['cart'])) {

        try {
            $pdo->beginTransaction();

            // Calcular total
            // Calculate total
            $grandTotal = 0;
            foreach ($_SESSION['cart'] as $item) {
                $grandTotal += $item['price'] * $item['quantity'];
            }

            // Crear cabecera de venta
            // Create sale header
            $stmtSale = $pdo->prepare("
                INSERT INTO public.sales (total_amount, created_at)
                VALUES (?, NOW())
                RETURNING id
            ");
            $stmtSale->execute([$grandTotal]);
            $sale_id = $stmtSale->fetchColumn();

            // Procesar cada producto
            // Process each product
            foreach ($_SESSION['cart'] as $item) {

                $prodId = $item['id'];
                $toDeduct = $item['quantity'];

                // Validar stock real
                // Validate real stock
                $stmtCheck = $pdo->prepare("
                    SELECT COALESCE(SUM(quantity),0)
                    FROM public.stock_lots
                    WHERE product_id = ?
                ");
                $stmtCheck->execute([$prodId]);
                $realStock = (float)$stmtCheck->fetchColumn();

                if ($realStock < $toDeduct) {
                    throw new Exception("Stock insuficiente para {$item['name']}");
                }

                // Guardar detalle de venta
                // Save sale item
                $stmtItem = $pdo->prepare("
                    INSERT INTO public.sales_items (sale_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtItem->execute([
                    $sale_id,
                    $prodId,
                    $item['quantity'],
                    $item['price']
                ]);

                // ===== FIFO LOTES EN OFERTA =====
                if ($item['is_sale_item']) {

                    $stmtLots = $pdo->prepare("
                        SELECT id, quantity
                        FROM public.stock_lots
                        WHERE product_id = ?
                        AND quantity > 0
                        AND is_discounted = TRUE
                        ORDER BY expiration_date ASC
                        FOR UPDATE
                    ");
                    $stmtLots->execute([$prodId]);

                    while ($toDeduct > 0 && $lot = $stmtLots->fetch()) {

                        $take = min($toDeduct, $lot['quantity']);

                        $pdo->prepare("
                            UPDATE public.stock_lots
                            SET quantity = quantity - ?
                            WHERE id = ? AND quantity >= ?
                        ")->execute([$take, $lot['id'], $take]);

                        // Registrar movimiento
                        // Register stock movement
                        $pdo->prepare("
                            INSERT INTO public.stock_movements
                            (product_id, quantity, movement_type, reference_id)
                            VALUES (?, ?, 'OUT', ?)
                        ")->execute([$prodId, $take, $sale_id]);

                        $toDeduct -= $take;
                    }
                }

                // ===== FIFO LOTES NORMALES =====
                if ($toDeduct > 0) {

                    $stmtNormal = $pdo->prepare("
                        SELECT id, quantity
                        FROM public.stock_lots
                        WHERE product_id = ?
                        AND quantity > 0
                        AND is_discounted = FALSE
                        ORDER BY expiration_date ASC
                        FOR UPDATE
                    ");
                    $stmtNormal->execute([$prodId]);

                    while ($toDeduct > 0 && $lot = $stmtNormal->fetch()) {

                        $take = min($toDeduct, $lot['quantity']);

                        $pdo->prepare("
                            UPDATE public.stock_lots
                            SET quantity = quantity - ?
                            WHERE id = ? AND quantity >= ?
                        ")->execute([$take, $lot['id'], $take]);

                        // Registrar movimiento
                        $pdo->prepare("
                            INSERT INTO public.stock_movements
                            (product_id, quantity, movement_type, reference_id)
                            VALUES (?, ?, 'OUT', ?)
                        ")->execute([$prodId, $take, $sale_id]);

                        $toDeduct -= $take;
                    }
                }
            }

            // Confirmar transacción
            // Commit transaction
            $pdo->commit();

            $_SESSION['cart'] = [];

            $message = "<div class='alert alert-success text-center fw-bold'>
                            Venta realizada correctamente
                        </div>";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("ERROR TPV: " . $e->getMessage());

            $message = "<div class='alert alert-danger text-center'>
                            Error al procesar la venta
                        </div>";
        }
    }

    // =========================
    // VACIAR CARRITO
    // =========================
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header("Location: point_of_sale.php");
        exit;
    }
}

// Obtener productos
// Get product list
$productsList = $pdo->query("
    SELECT id, name 
    FROM public.products 
    WHERE product_type = 'Final Product'
    ORDER BY name ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TPV - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-layout p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold mb-0">Terminal de Venta (TPV)</h2>
            <p class="text-muted small">Seleccione los productos para el pedido actual</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Volver al Menú</a>
    </div>

    <?= $message ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="row g-3">
                <?php foreach ($productsList as $p): 
                    $data = get_product_data($pdo, $p['id']); 
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
                                    <div class="h5 fw-bold text-danger mb-0">
                                        <?= number_format($data['price'] * 0.5, 2) ?> €
                                    </div>
                                    <small class="text-muted text-decoration-line-through"><?= number_format($data['price'], 2) ?> €</small>
                                <?php else: ?>
                                    <div class="h5 fw-bold text-dark mb-0">
                                        <?= number_format($data['price'], 2) ?> €
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST" class="mt-auto">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="add">
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

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card card-login border-0 shadow rounded-4 sticky-top" style="top: 2rem;">
                <div class="card-header bg-white border-0 py-3 text-center">
                    <h5 class="fw-bold text-bakery mb-0">Ticket Actual</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" style="min-height: 150px;">
                        <?php 
                        $grandTotal = 0;
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
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-bakery w-100 py-3 fw-bold rounded-pill shadow-sm" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>
                            COBRAR VENTA
                        </button>
                    </form>
                    
                    <form method="POST" class="mt-2 text-center">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none">Vaciar Ticket</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>