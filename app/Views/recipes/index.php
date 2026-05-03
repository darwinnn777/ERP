<?php require_role(['admin','obrador']);  ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta - <?= sanitize_input($main_product['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Librería para alertas bonitas -->
    <link rel="stylesheet" href="assets/css/style.css">
    <base href="<?= BASE_URL ?>">
</head>
<body class="admin-layout">

<div class="container mt-4">
    <!-- Cabecera de la página con el nombre del producto -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold">Gestión de Receta <span class="badge bg-success fs-6">AJAX</span></h2>
            <p class="text-muted mb-0">Producto: <strong><?= sanitize_input($main_product['name']) ?></strong> (<?= $main_product['sku'] ?>)</p>
        </div>
        <a href="productos" class="btn btn-outline-secondary btn-sm">Volver al Catálogo</a>
    </div>

    <div class="row g-4">
        <!--Formulario para añadir ingredientes -->
        <div class="col-md-4">
            <div class="card card-login border-1 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Añadir Ingrediente</h5>
                    <!-- Al usar la clase "ajax-form", interceptaremos el envío con JS más abajo -->
                    <form action="recipe/save" method="POST" class="ajax-form">
                        <!-- Campos ocultos necesarios para el backend -->
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="final_product_id" value="<?= $final_product_id ?>">
                        
                        <div class="mb-3">
                            <label class="small fw-bold">Seleccionar Ingrediente</label>
                            <select name="ingredient_id" class="form-select" required>
                                <option value="">-- Buscar --</option>
                                <!-- Rellenamos el select iterando sobre los ingredientes disponibles -->
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
                                <!-- Permite decimales para cantidades exactas  -->
                                <input type="number" name="quantity_needed" step="0.001" min="0.001" class="form-control" required placeholder="0.000">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-bakery w-100 fw-bold">Agregar a Receta</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DERECHA: Tabla con la receta actual -->
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
                            <!-- Si hay ingredientes en la receta, los mostramos; si no, mensaje informativo -->
                            <?php if (count($current_recipe) > 0): ?>
                                <?php foreach ($current_recipe as $row): ?>
                                    <tr>
                                        <td class="text-start fw-bold"><?= sanitize_input($row['name']) ?></td>
                                        <td><?= number_format($row['quantity_needed'], 3) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['unit_of_measure'] ?></span></td>
                                        <td>
                                            <!-- Cada botón "Quitar" es un mini formulario AJAX independiente -->
                                            <form action="recipe/delete" method="POST" class="ajax-form d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="recipe_id" value="<?= $row['recipe_id'] ?>">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete">Quitar</button>
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

<!-- Lógica JavaScript para procesar los formularios sin recargar la página -->
<script>
// Seleccionamos todos los formularios marcados con .ajax-form
document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Evitamos que el navegador envíe el formulario por defecto
        
        // Comprobamos si el botón que disparó el evento fue el de "Eliminar"
        const isDelete = e.submitter && e.submitter.classList.contains('btn-delete');
        
        if (isDelete) {
            // Si es borrar, pedimos confirmación con un modal bonito de SweetAlert
            Swal.fire({
                title: '¿Quitar Ingrediente?',
                text: "Se eliminará este ingrediente de la receta.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                // Solo si el usuario confirma, procesamos el AJAX
                if (result.isConfirmed) {
                    processAjax(this);
                }
            });
        } else {
            // Si es el formulario de guardar, enviamos directo sin confirmar
            processAjax(this);
        }
    });
});

// Función central que manda los datos al controlador usando la API Fetch
function processAjax(form) {
    const formData = new FormData(form); // Empaqueta los datos del form
    const actionUrl = form.getAttribute('action'); // Lee a qué ruta hay que enviarlos
    
    fetch(actionUrl, { method: 'POST', body: formData })
    .then(response => response.json()) // Esperamos un JSON de vuelta (lo que hace el controlador)
    .then(data => {
        if (data.success) {
            // Si fue bien, mostramos mensaje y recargamos para ver los cambios
            Swal.fire({ icon: 'success', title: 'Éxito', text: data.message, timer: 1500, showConfirmButton: false })
            .then(() => location.reload());
        } else {
            // Si el controlador nos devolvió success: false (ej. error de validación)
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Fallo de conexión', 'error')); // Por si se cae el servidor
}

// Parche necesario: el evento 'submit' a veces no sabe qué botón lo disparó en JS.
// Esto asocia el botón de borrar específicamente a su formulario.
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const form = this.closest('form');
        const event = new Event('submit', { cancelable: true });
        event.submitter = this;
        form.dispatchEvent(event);
    });
});
</script>
</body>
</html>