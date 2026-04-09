<?php
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// Restringir acceso solo a administradores y obrador
require_role(['admin','obrador']);

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search_term !== '') {
        $sql = "SELECT * FROM products WHERE name ILIKE ? OR sku ILIKE ? ORDER BY name ASC"; 
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['%'. $search_term . '%','%'. $search_term . '%']);
    } else {
        $sql = "SELECT * FROM products ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $all_products = $stmt->fetchAll();
} catch (PDOException $ex) {
    die("Error en la base de datos: " . $ex->getMessage());
}

$message_html = "";
if (isset($_GET['msg'])) { 
    $message = "";
    $class = "alert-info";

    switch ($_GET['msg']) {
        case 'ok': 
            $message = "Producto guardado correctamente."; 
            $class = "alert-success"; 
            break;
        case 'success': 
            $message = "Imagen subida con éxito."; 
            $class = "alert-success"; 
            break;
        case 'deleted': 
            $message = "Producto eliminado del catálogo."; 
            $class = "alert-warning"; 
            break;
        case 'error_in_use': 
            $message = "No se puede eliminar: el producto está en una receta."; 
            $class = "alert-danger"; 
            break;
        default: 
            $message = "Acción realizada."; 
            $class = "alert-primary"; 
            break;
    }

    $message_html = "<div class='alert $class alert-dismissible fade show' role='alert'>
                        <strong>Aviso:</strong> $message
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                     </div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Catálogo - ERP Bakery</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body class="admin-layout">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-bakery fw-bold">Catálogo de Productos</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Volver al Inicio</a>
    </div>

    <?= $message_html ?>

    <div class="card card-login mb-4 border-1">
        <div class="card-body">
            <h5 class="mb-3">Registrar Nuevo Producto</h5>
            <form action="save_product.php" method="POST" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="col-md-2">
                    <label class="small fw-bold">Código SKU</label>
                    <input type="text" name="sku" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold">Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold">Tipo</label>
                    <select name="type" class="form-select">
                        <option value="Final Product">Producto Final</option>
                        <option value="Ingredient">Ingrediente</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="small fw-bold">Unidad Medida</label>
                    <select name="unit" class="form-select" required>
                        <option value="" disabled selected>Elegir...</option>
                        <optgroup label="Peso/Volumen">
                            <option value="kg">Kilogramos (kg)</option>
                            <option value="g">Gramos (g)</option>
                            <option value="l">Litros (L)</option>
                            <option value="ml">Mililitros (ml)</option>
                        </optgroup>
                        <optgroup label="Unidades">
                            <option value="ud">Unidades (ud)</option>
                            <option value="docena">Docena</option>
                            <option value="paquete">Paquete</option>
                        </optgroup>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-bakery w-100 fw-bold">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-4">
        <form action="products_management.php" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2"
                   placeholder="Buscar por nombre o código..."
                   value="<?= sanitize_input($search_term) ?>">
            <button class="btn btn-bakery" type="submit">Buscar</button>
            <?php if ($search_term !== ''): ?>
                <a href="products_management.php" class="btn btn-light ms-2">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card card-login">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="thead-bakery text-center">
                    <tr>
                        <th>SKU</th>
                        <th>Imagen</th>
                        <th class="text-start">Nombre</th>
                        <th>Tipo</th>
                        <th>Medida</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                <?php if (count($all_products) > 0): ?>
                    <?php foreach ($all_products as $p):
                        $is_final = ($p['product_type'] === 'Final Product');
                        $safe_id = (int)$p['id'];
                    ?>
                    <tr>
                        <td class="text-muted small"><?= sanitize_input($p['sku']) ?></td>
                        <td>
                            <?php if (!empty($p['image_url'])): ?>
                                <img src="<?= sanitize_input($p['image_url']) ?>" width="40" class="rounded shadow-sm">
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="prepare_modal('<?= $safe_id ?>')"
                                        data-bs-toggle="modal" data-bs-target="#imageModal">+</button>
                            <?php endif; ?>
                        </td>
                        <td class="text-start fw-bold"><?= sanitize_input($p['name']) ?></td>
                        <td>
                            <span class="badge <?= $is_final ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ($is_final) ? 'VENTA' : 'MATERIA PRIMA' ?>
                            </span>
                        </td>
                        <td class="small fw-bold"><?= sanitize_input($p['unit_of_measure']) ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <?php if($is_final): ?>
                                    <a href="recipe_details.php?id=<?= $safe_id ?>" class="btn btn-sm btn-primary">Receta</a>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-outline-dark"
                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                    onclick="prepare_edit_modal(
                                        '<?= $safe_id ?>',
                                        '<?= htmlspecialchars($p['sku'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($p['product_type'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($p['unit_of_measure'], ENT_QUOTES) ?>'
                                    )">
                                    Editar
                                </button>

                                <form action="save_product.php" method="POST" style="display:inline;"
                                      onsubmit="return confirm('¿Borrar <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?');">
                                    <input type="hidden" name="id" value="<?= $safe_id ?>">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Borrar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="py-4 text-muted">No se encontraron productos.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals y scripts para editar y subir imagen -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function prepare_modal(id){
    document.getElementById('modal_product_id').value = id;
}

function prepare_edit_modal(id, sku, name, type, unit){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_unit').value = unit;
}
</script>
</body>
</html>