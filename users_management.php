<?php
//Siempre iniciar sesion por si no está iniciada
// Always start the session in case it hasn't been started
session_start();
//Utilizar  los archivos de funciones y conexión a BBDD
    require_once 'functions.php';
    require_once 'db_erp.php';
//SECURITY: Restrict access to administrators only
//SEGURIDAD: Restringir el acceso solo a administradores
require_role('admin');

$alert_message='';
//Lógica para actualizar
//Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $target_id = $_POST['id'];
    $new_role = $_POST['role_id'];

    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([$new_role, $target_id]);
    
    header("Location: users_management.php?msg=updated");
    exit;
}

/*
 DELETE LOGIC 
 LÓGICA DE BORRADO 
 We use POST instead of GET to prevent CSRF and unauthorized deletions via URL.
 Usamos POST en lugar de GET para prevenir CSRF y borrados no autorizados mediante la URL.
 */
 if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) 
         && $_POST['action']==='delete'){
     //Sanear el id para que sea un número entero
     //Sanitize the ID to be an integer
     $id_delete=filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
     
     if($id_delete){
         try{
             //Sentencia preparada para borrar con seguridad el id protegiendo al admin.
             //Prepared statement to delete securely, protecting the main ADMIN
             $delete_query="DELETE FROM users WHERE id = :id AND role_id != 1 ";
             $stmt_delete=$pdo->prepare($delete_query);
             $stmt_delete->execute(['id'=>$id_delete]);
             //Redirigir a user_management
             //Redirect after processing to user_management
             header("Location: users_management.php?msg=deleted");
         } catch (PDOException $ex) {
             $alert_message="<div class='alert alert-danger shadow-sm'> Error al eliminar usuario.</div>";
         }
     }
 }
//Obtener datos para tabla 
//FETCH DATA FOR THE TABLE.
 // La búsqueda (si existe)
 //The search (if exist)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search !== '') {
        // Consulta con filtro de búsqueda.
        //Query with search filter.
        $users_query = "SELECT u.id, u.username, u.full_name, u.role_id, r.name AS role_name
                        FROM users u
                        JOIN roles r ON u.role_id = r.id
                        WHERE u.username ILIKE ? 
                        ORDER BY u.id DESC";
        $stmt = $pdo->prepare($users_query);
        $stmt->execute([$search . '%']); 
    } else {
        // Consulta para mostrar todos los datos
        //Query to show all data
        $users_query = "SELECT u.id, u.username, u.full_name, u.role_id, r.name AS role_name
                        FROM users u
                        JOIN roles r ON u.role_id = r.id
                        ORDER BY u.id DESC";
        $stmt = $pdo->prepare($users_query);
        $stmt->execute();
    }
    
    // Guarda los resultados ya sea de usando filtro de búsqueda o no.
    //Save the results whether using search filter or not.
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    die("Error SQL: " . $ex->getMessage());
}
 //Obtener roles para el formulario.
 //Get roles for the form.
 $roles_query="SELECT id, name FROM roles ORDER BY id";
 $stmt_roles=$pdo->prepare($roles_query);
 $stmt_roles->execute();
 $roles_list=$stmt_roles->fetchAll(PDO::FETCH_ASSOC);
 
//Mensajes de retroalimentación
//Feedback messages
$alert_message = '';

//Errores múltiples de la sesión (vienen de insert_users.php)
if (isset($_SESSION['registry_errors'])) {
    $alert_message = "<div class='alert alert-danger border-0 shadow-sm'>
                        <p class='mb-1 fw-bold small text-uppercase'>Errores de validación:</p>
                        <ul class='mb-0 small'>";
    foreach ($_SESSION['registry_errors'] as $error) {
        //".=" concatena string
        $alert_message .= "<li>" . $error . "</li>";
    }
    $alert_message .= "</ul></div>";
    
    // Importante: Limpiar la sesión
    unset($_SESSION['registry_errors']);

} 
//Mensajes individuales por URL (si no hay errores en sesión)
elseif (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    
    if ($msg === 'success') {
        $alert_message = "<div class='alert alert-success border-0 shadow-sm'>Usuario creado con éxito.</div>";
    } elseif ($msg === 'error_exists') {
        $alert_message = "<div class='alert alert-warning border-0 shadow-sm'><strong>Aviso:</strong> El usuario ya está registrado.</div>";
    } elseif ($msg === 'deleted') {
        $alert_message = "<div class='alert alert-dark border-0 shadow-sm'>Usuario eliminado.</div>";
    } elseif ($msg === 'updated') {
        $alert_message = "<div class='alert alert-info border-0 shadow-sm'>Rol actualizado correctamente.</div>";
    } elseif ($msg === 'error_tech') {
        $alert_message = "<div class='alert alert-danger border-0 shadow-sm'><strong>Error:</strong> Error técnico en el servidor.</div>";
    }
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
        <title>Gestión de Usuarios - ERP Bakery</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="flex-column align-items-stretch p-4">

<div class="container mt-4">
    
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white p-2 rounded shadow-sm border">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-bakery text-decoration-none">Inicio</a></li>
            <li class="breadcrumb-item active text-muted">Usuarios</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="text-dark fw-bold mb-0">Gestión de Personal</h2>
            <form action="users_management.php" method="GET" class="mt-3 mb-4">
                <div class="input-group">
                    <input type="text" name="search" id="user_search_input" class="form-control rounded-start-pill ps-3" 
                           placeholder="Nombre de usuario..." list="user_suggestions" autocomplete="off"
                           value="<?= isset($_GET['search']) ? sanitize_input($_GET['search']) : '' ?>">

                    <datalist id="user_suggestions">
                        <?php if (!empty($users_list)): ?>
                            <?php foreach ($users_list as $user_opt): ?>
                                <option value="<?= sanitize_input($user_opt['username']) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>

                    <button class="btn btn-outline-secondary rounded-end-pill px-3 d-flex align-items-center" type="submit">
                        <img src="icons/search.svg" alt="Buscar" style="width: 16px; height: 16px;">
                    </button>
                    <?php if (!empty($search)): ?>
                        <button type="button" class="btn btn-outline-danger ms-2 rounded-pill shadow-sm" 
                                onclick="window.location.href='users_management.php'">
                            Mostrar tabla
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <button type="button" class="btn btn-bakery px-4 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
            + Nuevo Usuario
        </button>
    </div>

    <?= $alert_message ?>

    <div class="card card-login border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr class="text-muted">
                <th class="ps-4 py-3 fw-medium">USUARIO</th>
                <th class="py-3 fw-medium">NOMBRE COMPLETO</th>
                <th class="py-3 fw-medium text-center">ROL</th>
                <th class="py-3 fw-medium text-center">ACCIONES</th>
            </tr>
        </thead>
        <tbody class="border-top-0">
            <?php if (!empty($users_list)): ?>
                <?php foreach ($users_list as $row): ?>
                <tr>
                    <td class="ps-4 py-3 text-dark fw-semibold"><?= sanitize_input($row['username']) ?></td>
                    <td class="py-3 text-secondary"><?= sanitize_input($row['full_name']) ?></td>
                    <td class="py-3 text-center">
                        <span class="text-uppercase small fw-bold border px-2 py-1 rounded text-secondary">
                            <?= sanitize_input($row['role_name']) ?>
                        </span>
                    </td>
                    
                    <td class="py-3 text-center">
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <?php if ($row['id'] === $_SESSION['user_id']): ?>
                                <span class="fw-bold text-muted small text-uppercase">En sesión</span>
                            <?php else: ?>
                                <form action="users_management.php" method="POST" onsubmit="return confirm('¡ATENCIÓN! Si este usuario está trabajando, perderá su conexión. ¿Confirmar eliminación?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 small fw-bold">Eliminar</button>
                                </form>

                                <span class="text-black-50">|</span>

                                <button type="button" 
                                        class="btn btn-link text-primary text-decoration-none p-0 small fw-bold" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal"
                                        data-bs-id="<?= $row['id'] ?>"
                                        data-bs-user="<?= sanitize_input($row['username']) ?>"
                                        data-bs-role="<?= $row['role_id'] ?>">
                                    Editar
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" class="text-center py-5 text-muted">No hay datos disponibles.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <!-- Formulario nuevo usuario -->
            <form action="insert_users.php" method="POST">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Nuevo Registro de Usuario</h5>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Nombre de usuario</label>
                        <input type="text" name="user" class="form-control rounded-3 py-2" required placeholder="Ej: JSMITH.TORRES">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Nombre y apellido</label>
                        <input type="text" name="full_name" class="form-control rounded-3 py-2" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-bold">Contraseña</label>
                            <input type="password" name="password" class="form-control rounded-3 py-2" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-bold">Confirmar contraseña</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3 py-2" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted fw-bold">ROL</label>
                        <select name="rol_id" class="form-select rounded-3 py-2" required>
                            <option value="" disabled selected>Seleccione un rol...</option>
                            <?php foreach ($roles_list as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-bakery rounded-pill px-4 shadow-sm">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="users_management.php" method="POST">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="id" id="edit_user_id">
                
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Edit User Role</h5>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">Nombre de usuario</label>
                        <input type="text" id="edit_username" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted fw-bold">Nuevo rol</label>
                        <select name="role_id" id="edit_role_id" class="form-select rounded-3 py-2" required>
                            <?php foreach ($roles_list as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-bakery rounded-pill px-4 shadow-sm">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
//Script to fill the modal 
//Script para rellenar el modal (cuando da clic a editar)
var miModal = document.getElementById('editUserModal');
miModal.addEventListener('show.bs.modal', function (event) {
    var boton = event.relatedTarget; // El botón que pulsamos
    
    // Sacamos los datos de los atributos data-bs
    document.getElementById('edit_user_id').value = boton.getAttribute('data-bs-id');
    document.getElementById('edit_username').value = boton.getAttribute('data-bs-user');
    document.getElementById('edit_role_id').value = boton.getAttribute('data-bs-role');
});
//Envío automático al seleccionar una sugerencia
//Auto-submit form when a suggestion is selected
var searchInput = document.getElementById('user_search_input');
var suggestions = document.getElementById('user_suggestions');

searchInput.addEventListener('input', function() {
    var options = suggestions.options;
    for (var i = 0; i < options.length; i++) {
        //Si el texto escrito coincide exactamente con una opción, se envía
        //If the typed text matches exactly an option, submit
        if (options[i].value === this.value) {
            this.form.submit();
            break;
        }
    }
});
</script>
</body>
</html>
