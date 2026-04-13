<?php
/**
 * Gestión de Stock e Inventario / Stock and Inventory Management
 * ERP Bakery - 2026
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';
// SEGURIDAD: Acceso para Admin, Obrador o Dependiente
// SECURITY: Access for Admin, Bakery or Shop staff
require_role(['admin', 'obrador', 'dependiente']);

try {
    // Consulta principal: incluimos is_discounted y product_type
    // Main query: including is_discounted and product_type
    $sql = "SELECT 
                sl.id AS lot_id, 
                sl.lot_number, 
                sl.quantity, 
                sl.expiration_date,
                p.id AS product_id,
                p.name AS product_name,
                p.product_type,
                p.unit_of_measure,
                p.price_sell,
                p.is_discounted,
                w.name AS warehouse_name,
                (sl.expiration_date - CURRENT_DATE) AS days_left
            FROM stock_lots sl
            JOIN products p ON sl.product_id = p.id
            JOIN warehouses w ON sl.warehouse_id = w.id
            ORDER BY sl.expiration_date ASC NULLS LAST"; 
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Error de base de datos / Database error
    die("Error en la base de datos: " . $e->getMessage());
}

// Mensajes de feedback para el usuario / User feedback messages
$msg_text = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'discount_ok': $msg_text = "Descuento del 50% aplicado correctamente / 50% discount applied."; break;
        case 'no_change': $msg_text = "El descuento ya estaba aplicado / Discount already applied."; break;
        case 'error': $msg_text = "Error al procesar el descuento / Error processing discount."; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-layout">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Control de Inventario / Inventory Control</h2>
            <p class="text-muted small">Visualización de lotes y caducidades / Batches and expiration dates</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Volver / Back</a>
    </div>

    <?php if ($msg_text): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <?= $msg_text ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card card-login border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-uppercase fw-bold text-secondary text-center">
                        <th class="px-4 text-start">Producto / Product</th>
                        <th>Almacén / Warehouse</th>
                        <th>Stock</th>
                        <th>Caducidad / Expiry</th>
                        <th class="text-end px-4">Estado / Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): 
                        $days = $item['days_left'];
                        $is_final = ($item['product_type'] === 'Final Product');
                        
                        // Lógica de color de fila (Semáforo) / Row color logic (Traffic Light)
                        $bg_class = "";
                        if ($days !== null) {
                            if ($days < 0) $bg_class = "table-danger opacity-75"; 
                            elseif ($days <= 3) $bg_class = "table-warning";
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
                            <?= $item['expiration_date'] ? date('d/m/Y', strtotime($item['expiration_date'])) : '--' ?>
                        </td>
                        <td class="text-end px-4">
                            <?php 
                            // Mostrar estado de caducidad / Show expiration status
                            if ($days === null) echo "<span class='text-muted small'>Perenne</span>";
                            elseif ($days < 0) echo "<span class='text-danger fw-bold small'>CADUCADO / EXPIRED</span>";
                            else echo "<span class='badge bg-white text-dark border'>$days días</span>";
                            ?>

                            <?php 
                            // Acción: Aplicar descuento (Solo Admin y productos finales pronto a caducar)
                            // Action: Apply discount (Admin only and final products near expiry)
                            if (has_role('admin') && $is_final && $days !== null && $days <= 3 && $days >= 0): ?>
                                <?php if ($item['is_discounted']): ?>
                                    <span class="ms-2 badge border border-danger text-danger">-%50 OK</span>
                                <?php else: ?>
                                    <form action="../actions/apply_discount.php" method="POST" class="d-inline ms-2">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger py-0 px-2 fw-bold" style="font-size: 0.75rem;">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>