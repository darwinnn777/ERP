<?php
// Iniciar sesión y cargar dependencias
// Start session and load dependencies
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

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
    die("Error en la base de datos: " . $e->getMessage());
}

// Mensajes de feedback para el usuario
// User feedback messages
$msg_text = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'discount_ok': $msg_text = "Descuento del 50% aplicado correctamente."; break;
        case 'no_change': $msg_text = "El descuento ya estaba aplicado o el producto no es válido."; break;
        case 'error': $msg_text = "Error interno al procesar el descuento."; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - ERP Panadería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-layout">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Control de Inventario</h2>
            <p class="text-muted small">Visualización de lotes y caducidades</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Volver</a>
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
                    <tr class="small text-uppercase fw-bold text-secondary">
                        <th class="px-4">Producto</th>
                        <th>Almacén</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Caducidad</th>
                        <th class="text-end px-4">Estado / Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory as $item): 
                        $days = $item['days_left'];
                        $is_final = ($item['product_type'] === 'Final Product');
                        
                        // Lógica de color de fila (Semáforo)
                        // Row color logic (Traffic Light)
                        $bg_class = "";
                        if ($days !== null) {
                            if ($days < 0) $bg_class = "table-danger opacity-75"; 
                            elseif ($days <= 3) $bg_class = "table-warning";
                        }
                    ?>
                    <tr class="<?= $bg_class ?>">
                        <td class="px-4">
                            <div class="fw-bold"><?= sanitize_input($item['product_name']) ?></div>
                            <div class="text-muted small">Lote: <?= sanitize_input($item['lot_number']) ?></div>
                        </td>
                        <td><?= sanitize_input($item['warehouse_name']) ?></td>
                        <td class="text-center">
                            <span class="fw-bold"><?= $item['quantity'] ?></span>
                            <small class="text-muted"><?= $item['unit_of_measure'] ?></small>
                        </td>
                        <td class="text-center">
                            <?= $item['expiration_date'] ? date('d/m/Y', strtotime($item['expiration_date'])) : '--' ?>
                        </td>
                        <td class="text-end px-4">
                            <?php 
                            // Mostrar estado de caducidad
                            if ($days === null) echo "<span class='text-muted small'>Perenne</span>";
                            elseif ($days < 0) echo "<span class='text-danger fw-bold small'>CADUCADO</span>";
                            else echo "<span class='badge bg-white text-dark border'>$days días</span>";
                            ?>

                            <?php if (has_role('admin') && $is_final && $days !== null && $days <= 3 && $days >= 0): ?>
                                <?php if ($item['is_discounted']): ?>
                                    <span class="ms-2 badge border border-danger text-danger">Descuento Aplicado</span>
                                <?php else: ?>
                                    <form action="apply_discount.php" method="POST" class="d-inline ms-2">
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