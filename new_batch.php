<?php
require_once 'functions.php';
require_once 'db_erp.php';

// Protegemos: Solo admin puede añadir lotes
require_role(['admin']);

$mensaje = "";

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto = $_POST['producto'];
    $almacen = $_POST['almacen'];
    $lote = sanitize_input($_POST['lote']);
    $cantidad = $_POST['cantidad'];
    $caducidad = !empty($_POST['caducidad']) ? $_POST['caducidad'] : null;

    try {
        $sql = "INSERT INTO stock_lots (product_id, warehouse_id, lot_number, quantity, expiration_date) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$producto, $almacen, $lote, $cantidad, $caducidad]);
        
        $mensaje = "<div class='alert alert-success'>✅ Lote añadido correctamente.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='alert alert-danger'>❌ Error al guardar: " . $e->getMessage() . "</div>";
    }
}

// Obtenemos los productos para el desplegable
$stmt_prod = $pdo->query("SELECT id, name FROM products ORDER BY name ASC");
$productos = $stmt_prod->fetchAll();

// Obtenemos los almacenes para el desplegable
$stmt_alm = $pdo->query("SELECT id, name FROM warehouses ORDER BY name ASC");
$almacenes = $stmt_alm->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Lote - ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <style>
        body { display: block !important; height: auto !important; padding-top: 3rem; }
    </style>
</head>
<body class="bg-light">

<div class="container" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-bakery fw-bold">Registrar Entrada de Lote</h2>
        <a href="stock.php" class="btn btn-secondary btn-sm">Volver al Stock</a>
    </div>

    <?= $mensaje ?>

    <div class="card card-login p-4">
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Producto</label>
                <select name="producto" class="form-select" required>
                    <option value="">-- Selecciona un producto --</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Almacén de destino</label>
                <select name="almacen" class="form-select" required>
                    <?php foreach ($almacenes as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Número de Lote (Código)</label>
                <input type="text" name="lote" class="form-control" placeholder="Ej: LOTE-2026-X" required>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Cantidad</label>
                    <input type="number" step="0.01" name="cantidad" class="form-control" placeholder="Ej: 50.5" required>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="form-label fw-bold">Fecha de Caducidad</label>
                    <input type="date" name="caducidad" class="form-control">
                    <small class="text-muted">Dejar vacío si no caduca (ej. sal)</small>
                </div>
            </div>

            <button type="submit" class="btn btn-bakery w-100 fw-bold py-2">💾 Guardar Lote en Base de Datos</button>
        </form>
    </div>
</div>

</body>
</html>