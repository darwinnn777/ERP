<?php
session_start();
/*
  Interfaz de Recepción de Mercancía
  ERP Bakery - 2026
 */
require_once '../config/functions.php'; 
require_once '../config/db_erp.php';

require_role(['admin']);

$po_id = (int)($_GET['id'] ?? 0);

// Pillamos la orden para saber quién es el proveedor
$stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ?");
$stmt->execute([$po_id]);
$order = $stmt->fetch();

if (!$order || $order['status'] === 'Recibido') {
    header("Location: purchase_orders.php?msg=error");
    exit;
}

// Traemos los items que faltan por recibir
$stmtItems = $pdo->prepare("
    SELECT poi.*, p.name, p.unit_of_measure 
    FROM purchase_order_items poi
    JOIN products p ON poi.product_id = p.id
    WHERE poi.po_id = ? AND poi.received = FALSE
");
$stmtItems->execute([$po_id]);
$items = $stmtItems->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibir Pedido #<?= $po_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css"> </head>
<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between mb-4">
        <h2 class="fw-bold">Recibir Mercancía #<?= $po_id ?></h2>
        <a href="purchase_orders.php" class="btn btn-outline-secondary rounded-pill">Cancelar</a>
    </div>

    <form action="../actions/process_receive_stock.php" method="POST">
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
</body>
</html>