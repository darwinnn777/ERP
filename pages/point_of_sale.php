<?php
/**
 * TPV / Point of Sale - ERP Bakery 2026
 * Model: Intelligent Lot Prioritization (Discounted first)
 * Modelo: Priorización inteligente de lotes (Ofertas primero)
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

// SECURITY / SEGURIDAD
require_role(['admin', 'dependiente']);

if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    // 1. AÑADIR AL CARRITO (Add to cart)
    if ($action === 'add') {
        $prodId = (int)$_POST['product_id'];
        $qtyRequested = (int)$_POST['quantity'];
        
        // Get data from our helper in functions.php
        $prodInfo = get_product_data($pdo, $prodId);
        
        if ($qtyRequested > $prodInfo['stock']) {
            $message = "<div class='alert alert-warning text-center'>¡Stock insuficiente! Solo quedan {$prodInfo['stock']} unidades.</div>";
        } else {
            // PRICE LOGIC: If there's discounted stock, apply 50%
            // LÓGICA DE PRECIO: Si hay stock rebajado disponible, aplicamos el 50%
            $finalPrice = $prodInfo['on_sale'] ? ($prodInfo['price'] * 0.5) : $prodInfo['price'];
            $name = sanitize_input($_POST['product_name']);

            // Unique key to separate offer vs normal in the cart
            $cartKey = $prodId . '_' . ($prodInfo['on_sale'] ? 'sale' : 'normal');

            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $qtyRequested;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'id' => $prodId,
                    'name' => $name . ($prodInfo['on_sale'] ? ' (OFERTA)' : ''), 
                    'price' => $finalPrice, 
                    'quantity' => $qtyRequested,
                    'is_sale_item' => $prodInfo['on_sale']
                ];
            }
            header("Location: point_of_sale.php");
            exit;
        }
    }

    // 2. PROCESAR VENTA (Checkout with Lot Prioritization)
    if ($action === 'checkout' && !empty($_SESSION['cart'])) {
        try {
            $pdo->beginTransaction();

            foreach ($_SESSION['cart'] as $item) {
                $toDeduct = $item['quantity'];
                $prodId = $item['id'];

                // STEP A: If it's a sale item, take from discounted lots first
                // PASO A: Si es item de oferta, tomar de lotes rebajados primero
                if ($item['is_sale_item']) {
                    $stmtLots = $pdo->prepare("SELECT id, quantity FROM public.stock_lots WHERE product_id = ? AND quantity > 0 AND is_discounted = TRUE ORDER BY expiration_date ASC");
                    $stmtLots->execute([$prodId]);
                    while ($toDeduct > 0 && $lot = $stmtLots->fetch()) {
                        $take = min($toDeduct, $lot['quantity']);
                        $pdo->prepare("UPDATE public.stock_lots SET quantity = quantity - ? WHERE id = ?")->execute([$take, $lot['id']]);
                        $toDeduct -= $take;
                    }
                }

                // STEP B: Deduct remaining from normal lots (or all if not a sale item)
                // PASO B: Descontar el resto de lotes normales
                if ($toDeduct > 0) {
                    $stmtNormal = $pdo->prepare("SELECT id, quantity FROM public.stock_lots WHERE product_id = ? AND quantity > 0 AND is_discounted = FALSE ORDER BY expiration_date ASC");
                    $stmtNormal->execute([$prodId]);
                    while ($toDeduct > 0 && $lot = $stmtNormal->fetch()) {
                        $take = min($toDeduct, $lot['quantity']);
                        $pdo->prepare("UPDATE public.stock_lots SET quantity = quantity - ? WHERE id = ?")->execute([$take, $lot['id']]);
                        $toDeduct -= $take;
                    }
                }
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            $message = "<div class='alert alert-success fw-bold text-center'>Venta completada. Lotes actualizados correctamente.</div>";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header("Location: point_of_sale.php");
        exit;
    }
}

// Load products for the UI
$productsList = $pdo->query("SELECT id, name FROM public.products WHERE product_type = 'Final Product' ORDER BY name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>TPV Panadería - Gestión de Lotes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-product { transition: transform 0.2s; border-radius: 15px; }
        .card-product:hover { transform: translateY(-5px); }
        .btn-pay { border-radius: 30px; font-weight: bold; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">TPV Panadería</h2>
        <a href="dashboard.php" class="btn btn-secondary rounded-pill">Volver</a>
    </div>

    <?= $message ?>

    <div class="row">
        <div class="col-md-8">
            <div class="row g-3">
                <?php foreach ($productsList as $p): 
                    $data = get_product_data($pdo, $p['id']); 
                ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm card-product">
                        <div class="card-body text-center">
                            <h6 class="fw-bold"><?= sanitize_input($p['name']) ?></h6>
                            
                            <div class="mb-2">
                                <span class="badge bg-info text-dark"><?= $data['stock'] ?> uds totales</span>
                                <?php if ($data['discounted_stock'] > 0): ?>
                                    <span class="badge bg-danger"><?= $data['discounted_stock'] ?> en oferta</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($data['on_sale']): ?>
                                <div class="text-danger fw-bold fs-5">
                                    <?= number_format($data['price'] * 0.5, 2) ?> €
                                    <small class="d-block text-muted text-decoration-line-through" style="font-size: 0.7rem;"><?= number_format($data['price'], 2) ?> €</small>
                                </div>
                            <?php else: ?>
                                <div class="text-primary fw-bold fs-5">
                                    <?= number_format($data['price'], 2) ?> €
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="mt-3">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="product_name" value="<?= sanitize_input($p['name']) ?>">
                                <input type="number" name="quantity" class="form-control form-control-sm mb-2 text-center" value="1" min="1" max="<?= $data['stock'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm w-100 rounded-pill" <?= ($data['stock'] <= 0) ? 'disabled' : '' ?>>
                                    <?= ($data['stock'] > 0) ? 'Añadir' : 'Agotado' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white text-center fw-bold py-3">Resumen de Venta</div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php 
                        $total = 0;
                        foreach ($_SESSION['cart'] as $item): 
                            $sub = $item['price'] * $item['quantity'];
                            $total += $sub;
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <span class="fw-bold d-block"><?= $item['name'] ?></span>
                                <small><?= $item['quantity'] ?> x <?= number_format($item['price'], 2) ?>€</small>
                            </div>
                            <span class="fw-bold"><?= number_format($sub, 2) ?>€</span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="card-footer bg-white p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5">Total a Pagar</span>
                        <span class="h3 fw-bold text-success"><?= number_format($total, 2) ?> €</span>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-success btn-lg w-100 btn-pay" <?= empty($_SESSION['cart']) ? 'disabled' : '' ?>>COBRAR</button>
                    </form>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-link btn-sm w-100 text-danger text-decoration-none">Vaciar Carrito</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>