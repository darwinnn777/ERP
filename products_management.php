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
        case 'ok': $message = "Producto guardado correctamente."; $class = "alert-success"; break;
        case 'success': $message = "Imagen subida con éxito."; $class = "alert-success"; break;
        case 'deleted': $message = "Producto eliminado del catálogo."; $class = "alert-warning"; break;
        case 'error_in_use': $message = "No se puede eliminar: el producto está en una receta."; $class = "alert-danger"; break;
        default: $message = "Acción realizada."; $class = "alert-primary"; break;
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
                        <?php foreach(get_product_types() as $val => $text): ?>
                            <option value="<?= $val ?>"><?= $text ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="small fw-bold">Unidad Medida</label>
                    <select name="unit" class="form-select" required>
                        <option value="" disabled selected>Elegir...</option>
                        <?php foreach(get_units() as $val => $text): ?>
                            <option value="<?= $val ?>"><?= $text ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="small fw-bold">P. Compra</label>
                    <input type="number" name="price_buy" step="0.01" class="form-control" placeholder="0.00" required>
                </div>
                <div class="col-md-1">
                    <label class="small fw-bold">P. Venta</label>
                    <input type="number" name="price_sell" step="0.01" class="form-control" placeholder="0.00" required>
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-bakery w-100 fw-bold">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-4">
        <form action="products_management.php" method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Buscar por nombre o código..." value="<?= sanitize_input($search_term) ?>">
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
                        <th>P. Venta</th>
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
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="prepare_modal('<?= $safe_id ?>')" data-bs-toggle="modal" data-bs-target="#imageModal">+</button>
                            <?php endif; ?>
                        </td>
                        <td class="text-start fw-bold"><?= sanitize_input($p['name']) ?></td>
                        <td>
                            <span class="badge <?= $is_final ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ($is_final) ? 'VENTA' : 'MATERIA PRIMA' ?>
                            </span>
                        </td>
                        <td class="small fw-bold"><?= sanitize_input($p['unit_of_measure']) ?></td>
                        <td class="fw-bold text-success"><?= number_format($p['price_sell'], 2) ?> €</td>
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
                                        '<?= htmlspecialchars($p['unit_of_measure'], ENT_QUOTES) ?>',
                                        '<?= $p['price_buy'] ?>',
                                        '<?= $p['price_sell'] ?>'
                                    )">
                                    Editar
                                </button>
                                    
                                <form action="save_product.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Borrar <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?');">
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
                    <tr><td colspan="7" class="py-4 text-muted">No se encontraron productos.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Editar Producto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_product.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold">Código SKU</label>
                        <input type="text" name="sku" id="edit_sku" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold">Nombre</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Tipo</label>
                            <select name="type" id="edit_type" class="form-select">
                                <?php foreach(get_product_types() as $val => $text): ?>
                                    <option value="<?= $val ?>"><?= $text ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Unidad Medida</label>
                            <select name="unit" id="edit_unit" class="form-select" required>
                                <option value="" disabled selected>Elegir...</option>
                                <?php foreach(get_units() as $val => $text): ?>
                                    <option value="<?= $val ?>"><?= $text ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Precio de Compra (€)</label>
                            <input type="number" name="price_buy" id="edit_price_buy" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Precio de Venta (€)</label>
                            <input type="number" name="price_sell" id="edit_price_sell" step="0.01" class="form-control" required>
                        </div>
                    </div>
                    
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-bakery fw-bold">Actualizar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>
 
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background-color: var(--color-bakery);">
                <h5 class="modal-title fw-bold">Actualizar Imagen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_product.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 text-center">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="upload_image">
                    <input type="hidden" name="id" id="modal_product_id">
                    
                    <label class="form-label d-block small fw-bold text-muted mb-3">Formatos: JPG, PNG o WEBP</label>
                    <input type="file" name="product_image" class="form-control border-bakery" accept="image/*" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-bakery rounded-pill px-4 fw-bold">Subir Imagen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function prepare_modal(id){
    document.getElementById('modal_product_id').value = id;
}

// Ahora el script JavaScript también coge los precios para meterlos en el popup de editar
function prepare_edit_modal(id, sku, name, type, unit, price_buy, price_sell){
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_sku').value = sku;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_unit').value = unit;
    document.getElementById('edit_price_buy').value = price_buy;
    document.getElementById('edit_price_sell').value = price_sell;
}
</script>
</body>
</html>