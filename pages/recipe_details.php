<?php
    // Iniciar sesión y cargar dependencias
    session_start();
    require_once '../config/functions.php';
    require_once '../config/db_erp.php';

    // Seguridad: restringir acceso a administradores u obrador.
    require_role(['admin','obrador']);
    
    // Obtener el ID del producto final desde la URL
    $final_product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($final_product_id <= 0) {
        header("Location: products_management.php");
        exit;
    }

    try {
        // Obtener datos del producto principal
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND product_type = 'Final Product'");
        $stmt->execute([$final_product_id]);
        $main_product = $stmt->fetch();

        if (!$main_product) {
            die("Producto no encontrado o no es un producto final.");
        }

        // Obtener lista de ingredientes disponibles (Materia Prima)
        $stmt_ing = $pdo->query("SELECT id, name, unit_of_measure FROM products WHERE product_type = 'Ingredient' ORDER BY name ASC");
        $ingredients_options = $stmt_ing->fetchAll();

        // Obtener los ingredientes actuales de la receta
        $sql_recipe = "SELECT r.id as recipe_id, r.quantity_needed, p.name, p.unit_of_measure 
                       FROM recipes r
                       JOIN products p ON r.ingredient_id = p.id 
                       WHERE r.final_product_id = ?
                       ORDER BY p.name ASC";
        $stmt_res = $pdo->prepare($sql_recipe);
        $stmt_res->execute([$final_product_id]);
        $current_recipe = $stmt_res->fetchAll();

    } catch (PDOException $ex) {
        die("Error en la base de datos: " . $ex->getMessage());
    }

    // Configurar mensajes de feedback
    $msg_html = "";
    if (isset($_GET['msg'])) {
        $text = ($_GET['msg'] == 'ok') ? "Operación realizada con éxito." : "Hubo un error en el proceso.";
        $color = ($_GET['msg'] == 'ok') ? "alert-success" : "alert-danger";
        $msg_html = "<div class='alert $color alert-dismissible fade show' role='alert'>
                        $text
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                     </div>";
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta - <?= sanitize_input($main_product['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-layout">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Gestión de Receta</h2>
            <p class="text-muted mb-0">Producto: <strong><?= sanitize_input($main_product['name']) ?></strong> (<?= $main_product['sku'] ?>)</p>
        </div>
        <a href="products_management.php" class="btn btn-outline-secondary btn-sm">Volver al Catálogo</a>
    </div>

    <?= $msg_html ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-login border-1 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Añadir Ingrediente</h5>
                    <form action="../actions/save_recipe_item.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="final_product_id" value="<?= $final_product_id ?>">
                        
                        <div class="mb-3">
                            <label class="small fw-bold">Seleccionar Ingrediente</label>
                            <select name="ingredient_id" class="form-select" required>
                                <option value="">-- Buscar --</option>
                                <?php foreach ($ingredients_options as $ing): ?>
                                    <option value="<?= $ing['id'] ?>">
                                        <?= sanitize_input($ing['name']) ?> (<?= $ing['unit_of_measure'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold">Cantidad Necesaria</label>
                            <div class="input-group">
                                <input type="number" name="quantity_needed" step="0.001" class="form-control" required placeholder="0.000">
                            </div>
                        </div>

                        <button type="submit" name="action" value="add" class="btn btn-bakery w-100 fw-bold">Agregar a Receta</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-login shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="thead-bakery text-center">
                            <tr>
                                <th class="text-start">Ingrediente</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            <?php if (count($current_recipe) > 0): ?>
                                <?php foreach ($current_recipe as $row): ?>
                                    <tr>
                                        <td class="text-start fw-bold"><?= sanitize_input($row['name']) ?></td>
                                        <td><?= number_format($row['quantity_needed'], 3) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['unit_of_measure'] ?></span></td>
                                        <td>
                                            <form action="../actions/save_recipe_item.php" method="POST" onsubmit="return confirm('¿Eliminar este ingrediente de la receta?');">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="recipe_id" value="<?= $row['recipe_id'] ?>">
                                                <input type="hidden" name="final_product_id" value="<?= $final_product_id ?>">
                                                <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Quitar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-4 text-muted">No hay ingredientes en esta receta.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>