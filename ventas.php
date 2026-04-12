<?php
/**
 * TPV / Punto de Venta - Pantalla del Dependiente
 * ERP Bakery - 2026
 */
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// Seguridad: Solo Dependiente y Admin pueden vender
require_role(['admin', 'dependiente']);

// Inicializamos el carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$mensaje = "";

// LÓGICA DEL CARRITO Y VENTAS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // 1. Añadir al carrito
    if ($accion === 'add') {
        $id_prod = (int)$_POST['id_producto'];
        $nombre = sanitize_input($_POST['nombre']);
        $precio = (float)$_POST['precio'];
        $cantidad = (int)($_POST['cantidad']);

        if ($cantidad > 0) {
            // Si ya está en el carrito, sumamos cantidad
            if (isset($_SESSION['carrito'][$id_prod])) {
                $_SESSION['carrito'][$id_prod]['cantidad'] += $cantidad;
            } else {
                $_SESSION['carrito'][$id_prod] = [
                    'nombre' => $nombre,
                    'precio' => $precio,
                    'cantidad' => $cantidad
                ];
            }
        }
        header("Location: ventas.php");
        exit;
    }

    // 2. Vaciar carrito
    if ($accion === 'clear') {
        $_SESSION['carrito'] = [];
        header("Location: ventas.php");
        exit;
    }

    // 3. PROCESAR LA VENTA (La magia del ERP)
    if ($accion === 'checkout' && !empty($_SESSION['carrito'])) {
        try {
            // Iniciamos transacción: si algo falla, no se guarda nada a medias
            $pdo->beginTransaction();

            foreach ($_SESSION['carrito'] as $id_final => $item) {
                $cantidad_vendida = $item['cantidad'];

                // Buscamos la receta de este producto final
                $stmt_receta = $pdo->prepare("SELECT ingredient_id, quantity_needed FROM recipes WHERE final_product_id = ?");
                $stmt_receta->execute([$id_final]);
                $ingredientes = $stmt_receta->fetchAll();

                // Si no tiene receta, lanzamos aviso (el Obrador olvidó crearla)
                if (empty($ingredientes)) {
                    throw new Exception("El producto '{$item['nombre']}' no tiene receta configurada. ¡Avisa al obrador!");
                }

                // Descontamos cada ingrediente del stock (Lógica FIFO)
                foreach ($ingredientes as $ing) {
                    $total_a_descontar = $ing['quantity_needed'] * $cantidad_vendida;
                    
                    // Buscamos los lotes de este ingrediente ordenados por caducidad (el más viejo primero)
                    $stmt_lotes = $pdo->prepare("SELECT id, quantity FROM stock_lots WHERE product_id = ? AND quantity > 0 ORDER BY expiration_date ASC");
                    $stmt_lotes->execute([$ing['ingredient_id']]);
                    $lotes = $stmt_lotes->fetchAll();

                    foreach ($lotes as $lote) {
                        if ($total_a_descontar <= 0) break; // Ya descontamos todo

                        if ($lote['quantity'] >= $total_a_descontar) {
                            // Este lote tiene suficiente para cubrir lo que falta
                            $sql_upd = "UPDATE stock_lots SET quantity = quantity - ? WHERE id = ?";
                            $pdo->prepare($sql_upd)->execute([$total_a_descontar, $lote['id']]);
                            $total_a_descontar = 0; 
                        } else {
                            // Este lote no tiene suficiente, lo vaciamos entero y seguimos buscando
                            $total_a_descontar -= $lote['quantity'];
                            $sql_upd = "UPDATE stock_lots SET quantity = 0 WHERE id = ?";
                            $pdo->prepare($sql_upd)->execute([$lote['id']]);
                        }
                    }

                    // Si después de buscar por todos los lotes, aún falta por descontar...
                    if ($total_a_descontar > 0) {
                        throw new Exception("Stock insuficiente de materias primas para vender '{$item['nombre']}'. Faltan ingredientes en el almacén.");
                    }
                }
            }

            // Si llegamos aquí, todo salió bien. Confirmamos los cambios en la BD.
            $pdo->commit();
            $_SESSION['carrito'] = []; // Vaciamos carrito
            $mensaje = "<div class='alert alert-success fw-bold shadow-sm'>✅ ¡Venta procesada con éxito! El stock de ingredientes se ha actualizado.</div>";

        } catch (Exception $e) {
            // Si hubo error, deshacemos todo para no corromper el stock
            $pdo->rollBack();
            $mensaje = "<div class='alert alert-danger fw-bold shadow-sm'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Cargar productos finales para mostrarlos en pantalla
$stmt_prod = $pdo->query("SELECT id, name, price_sell, image_url FROM products WHERE product_type = 'Final Product' ORDER BY name ASC");
$productos_venta = $stmt_prod->fetchAll();

// Calcular total del carrito
$total_carrito = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total_carrito += ($item['precio'] * $item['cantidad']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta (TPV) - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-layout bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Terminal Punto de Venta (TPV)</h2>
            <p class="text-muted mb-0">Atención al cliente y cobro</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Volver al Inicio</a>
    </div>

    <?= $mensaje ?>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <h5 class="fw-bold mb-3">Productos Disponibles</h5>
                <div class="row g-3">
                    <?php if(empty($productos_venta)): ?>
                        <div class="col-12 text-muted">No hay productos finales registrados en el catálogo.</div>
                    <?php endif; ?>

                    <?php foreach ($productos_venta as $p): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 border text-center shadow-sm" style="border-radius: 15px; overflow: hidden;">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-dark mb-1"><?= sanitize_input($p['name']) ?></h6>
                                <p class="text-bakery fw-bold fs-5 mb-2"><?= number_format($p['price_sell'], 2) ?> €</p>
                                
                                <form method="POST" action="ventas.php">
                                    <input type="hidden" name="accion" value="add">
                                    <input type="hidden" name="id_producto" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="nombre" value="<?= sanitize_input($p['name']) ?>">
                                    <input type="hidden" name="precio" value="<?= $p['price_sell'] ?>">
                                    
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text bg-light text-muted">Cant.</span>
                                        <input type="number" name="cantidad" class="form-control text-center" value="1" min="1" required>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-bakery w-100 fw-bold rounded-pill">Añadir al Ticket</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow rounded-4 bg-white sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white border-0 pt-3 pb-3 rounded-top-4">
                    <h5 class="mb-0 fw-bold text-center">🧾 Ticket de Compra</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($_SESSION['carrito'])): ?>
                            <li class="list-group-item text-center py-4 text-muted small">El ticket está vacío.</li>
                        <?php else: ?>
                            <?php foreach ($_SESSION['carrito'] as $id => $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h6 class="mb-0 fw-bold"><?= sanitize_input($item['nombre']) ?></h6>
                                    <small class="text-muted"><?= $item['cantidad'] ?> x <?= number_format($item['precio'], 2) ?> €</small>
                                </div>
                                <span class="fw-bold text-dark"><?= number_format($item['cantidad'] * $item['precio'], 2) ?> €</span>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="card-footer bg-light border-0 p-4 rounded-bottom-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted fw-bold">TOTAL</span>
                        <span class="fs-3 fw-bold text-bakery"><?= number_format($total_carrito, 2) ?> €</span>
                    </div>

                    <?php if (!empty($_SESSION['carrito'])): ?>
                    <form method="POST" action="ventas.php" class="mb-2">
                        <input type="hidden" name="accion" value="checkout">
                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 rounded-pill shadow-sm fs-5">
                            Cobrar Ticket
                        </button>
                    </form>
                    <form method="POST" action="ventas.php">
                        <input type="hidden" name="accion" value="clear">
                        <button type="submit" class="btn btn-outline-danger w-100 fw-bold py-1 rounded-pill small">
                            Cancelar Venta
                        </button>
                    </form>
                    <?php else: ?>
                        <button class="btn btn-secondary w-100 fw-bold py-2 rounded-pill disabled">Cobrar Ticket</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>