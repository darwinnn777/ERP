<?php
//Siempre iniciar sesion por si no está iniciada
// Always start the session in case it hasn't been started
session_start();
//Utilizar  los archivos de funciones y conexión a BBDD
    require_once 'functions.php';
    require_once 'db_erp.php';
//SECURITY: Restrict access to administrators only
//SEGURIDAD: Restringir el acceso solo a administradores
require_role(['admin','obrador']);
//Gestión de catálago y recetas
//Master product and recipe Management
//Obtener el término de búsqueda de la URL
//Get search term from the URL
$search_term=isset($_GET['search']) ? trim($_GET['search']) :'';
try{
    if($search_term!==''){
        $sql="SELECT * FROM products WHERE name ILIKE ? OR sku ILIKE ?
                ORDER BY name ASC"; 
        $stmt=$pdo->prepare($sql);
        $stmt->execute(['%'. $search_term . '%','%'. $search_term . '%']);
        
    }else{
        $sql="SELECT * FROM products ORDER BY name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    $all_products=$stmt->fetchAll();
} catch (PDOException $ex) {
    die("Error en la base de datos: ".$ex->getMessage());
}
//Mostrar el mensaje de éxito o error 
//según el controlador save_product
// Solo procesamos el mensaje si existe en la URL
$message_html = "";
if (isset($_GET['msg'])) { 
    $message = "";
    $class = "alert-info";

    switch ($_GET['msg']) {
        case 'ok': {
            $message = "Producto guardado correctamente.";
            $class = "alert-success";
            break;
        }
        case 'success': {
            $message = "Imagen subida con éxito.";
            $class = "alert-success";
            break;
        }
        case 'deleted': {
            $message = "Producto eliminado del catálogo.";
            $class = "alert-warning";
            break;
        }
        case 'error_in_use': {
            $message = "No se puede eliminar: el producto está en una receta.";
            $class = "alert-danger";
            break;
        }
        default: {
            $message = "Acción realizada.";
            $class = "alert-primary";
            break;
        }
    }
    //Guarda el HTML en una variable para mostrarlo luego
    $message_html = "<div class='alert $class alert-dismissible fade show' role='alert'>
                        <strong>Aviso:</strong> $message
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                     </div>";
}
?>
<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestión de Catálogo y Recetas - ERP Bakery</title>
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
                <div class="col-md-2">
                    <label class="small fw-bold">Código SKU</label>
                    <input type="text" name="sku" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Nombre</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">Tipo de Producto</label>
                    <select name="type" class="form-select">
                        <option value="Final Product">Producto Final (Venta)</option>
                        <option value="Ingredient">Ingrediente (Materia Prima)</option>
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
                   placeholder="Buscar por nombre o código..." value="<?= sanitize_input($search_term) ?>">
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
                        <th class="text-start">Nombre del Producto</th>
                        <th>Tipo</th>
                        <th>Medida</th>
                        <th>Opciones</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <?php if (count($all_products) > 0): ?>
                        <?php foreach ($all_products as $p): 
                            $is_final = ($p['product_type'] === 'Final Product');
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $p['sku'] ?></td>
                            <td>
                                <?php if ($p['image_url']): ?>
                                    <img src="<?= $p['image_url'] ?>" width="50" class="rounded shadow-sm">
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#imageModal" 
                                            onclick="prepare_modal('<?= $p['id'] ?>')">
                                        + Subir
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="text-start fw-bold"><?= sanitize_input($p['name']) ?></td>
                            <td>
                                <span class="badge <?= $is_final ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ($is_final) ? 'VENTA' : 'MATERIA PRIMA' ?>
                                </span>
                            </td>
                            <td><?= $p['unit_of_measure'] ?></td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if($is_final): ?>
                                        <a href="recipe_details.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Receta</a>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-sm btn-outline-dark" 
                                            data-bs-toggle="modal" data-bs-target="#editModal" 
                                            onclick="prepare_edit_modal('<?= $p['id'] ?>', '<?= $p['sku'] ?>', '<?= addslashes($p['name']) ?>', '<?= $p['product_type'] ?>', '<?= $p['unit_of_measure'] ?>')">
                                        Editar
                                    </button>

                                    <form action="save_product.php" method="POST" style="display:inline;" 
                                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar el producto: <?= addslashes($p['name']) ?>?');">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <input type="hidden" name="action" value="delete_product">
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

<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Imagen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_product.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <label class="form-label fw-bold">Selecciona el archivo:</label>
                    <input type="file" name="product_image" class="form-control" accept="image/*" required>
                    <input type="hidden" name="id" id="modal_product_id">
                    <input type="hidden" name="action" value="upload_image">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Imagen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="save_product.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold">SKU</label>
                        <input type="text" name="sku" id="edit_sku" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo</label>
                        <select name="type" id="edit_type" class="form-select">
                            <option value="Final Product">Producto Final (Venta)</option>
                            <option value="Ingredient">Ingrediente (Materia Prima)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Unidad de Medida</label>
                        <input type="text" name="unit" id="edit_unit" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Cambios</button>
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
    function prepare_edit_modal(id, sku, name, type, unit) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_sku').value = sku;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_unit').value = unit;
    }
</script>
</body>
</html>
