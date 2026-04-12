
<?php
/**
 * Gestión de Usuarios / User Management
 * ERP Bakery - 2026
 */
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// SEGURIDAD: Restringir el acceso solo a administradores
require_role('admin');

$alert_message = '';

// --- LÓGICA DE ACTUALIZACIÓN / UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    csrf_check($_POST['csrf_token'] ?? '');
    
    $target_id = (int)$_POST['id'];
    $new_role = (int)$_POST['role_id'];
    $new_pass = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    // ¿El admin escribió una nueva contraseña?
    // Did the admin type a new password?
    if (!empty($new_pass)) {
        if ($new_pass === $confirm_pass) {
            // Hashear la nueva contraseña y guardarla junto con el rol
            $pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET role_id = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$new_role, $pass_hashed, $target_id]);
            header("Location: users_management.php?msg=updated_pass");
            exit;
        } else {
            // Si no coinciden, devolvemos un error
            $_SESSION['registry_errors'] = ["Las contraseñas nuevas no coinciden."];
            header("Location: users_management.php");
            exit;
        }
    } else {
        // Si dejó la contraseña en blanco, SOLO actualizamos el rol (como antes)
        // If password is blank, ONLY update the role
        $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$new_role, $target_id]);
        header("Location: users_management.php?msg=updated");
        exit;
    }
}

// --- LÓGICA DE BORRADO / DELETE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_check($_POST['csrf_token'] ?? '');
    $id_delete = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Evitamos que el admin principal (ID 1) se borre a sí mismo por error
    if ($id_delete) {
        try {
            $delete_query = "DELETE FROM users WHERE id = :id AND id != 1 AND role_id != 1";
            $stmt_delete = $pdo->prepare($delete_query);
            $stmt_delete->execute(['id' => $id_delete]);
            header("Location: users_management.php?msg=deleted");
            exit;
        } catch (PDOException $ex) {
            $alert_message = "<div class='alert alert-danger shadow-sm rounded-4'>Error al eliminar usuario.</div>";
        }
    }
}

// --- OBTENER DATOS / FETCH DATA ---
// Traemos el password_hash también para que el botón de Info lo pueda mostrar
try {
    $users_query = "SELECT u.id, u.username, u.full_name, u.password_hash, u.role_id, r.name AS role_name
                    FROM users u
                    JOIN roles r ON u.role_id = r.id
                    ORDER BY u.id DESC";
    $stmt = $pdo->prepare($users_query);
    $stmt->execute();
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $roles_query = "SELECT id, name FROM roles ORDER BY id";
    $stmt_roles = $pdo->prepare($roles_query);
    $stmt_roles->execute();
    $roles_list = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    die("Error SQL: " . $ex->getMessage());
}

// --- MENSAJES DE FEEDBACK ---
if (isset($_SESSION['registry_errors'])) {
    $alert_message = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>
                        <p class='mb-1 fw-bold small text-uppercase'>Errores de validación:</p>
                        <ul class='mb-0 small'>";
    foreach ($_SESSION['registry_errors'] as $error) { $alert_message .= "<li>" . sanitize_input($error) . "</li>"; }
    $alert_message .= "</ul></div>";
    unset($_SESSION['registry_errors']);
} elseif (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    switch ($msg) {
        case 'success': $alert_message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>Usuario creado correctamente.</div>"; break;
        case 'deleted': $alert_message = "<div class='alert alert-dark border-0 shadow-sm rounded-4'>Usuario eliminado.</div>"; break;
        case 'updated': $alert_message = "<div class='alert alert-info border-0 shadow-sm rounded-4'>Rol actualizado correctamente.</div>"; break;
        case 'updated_pass': $alert_message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>Rol y Contraseña actualizados correctamente.</div>"; break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - ERP Bakery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="flex-column align-items-stretch p-4 bg-light">

<div class="container mt-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white p-2 rounded shadow-sm border">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-bakery text-decoration-none">Inicio / Home</a></li>
            <li class="breadcrumb-item active text-muted">Usuarios / Users</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="text-dark fw-bold mb-0">Gestión de Personal</h2>
            <div class="mt-3">
                <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                    <input type="text" id="user_live_search" class="form-control border-0 ps-3" 
                           placeholder="Nombre de usuario..." autocomplete="off"
                           onkeyup="filterTable()">
                    <button class="btn btn-white text-secondary border-start" type="button" onclick="clearSearch()">Limpiar</button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-bakery px-4 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            + Nuevo Usuario
        </button>
    </div>

    <?= $alert_message ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="ps-4 py-3">Usuario / User</th>
                        <th class="py-3">Nombre / Full Name</th>
                        <th class="py-3 text-center">Rol / Role</th>
                        <th class="py-3 text-center">Acciones / Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $row): ?>
                    <tr class="user-row">
                        <td class="ps-4 py-3 fw-semibold username-cell"><?= sanitize_input($row['username']) ?></td>
                        <td class="py-3 text-secondary"><?= sanitize_input($row['full_name']) ?></td>
                        <td class="py-3 text-center">
                            <span class="badge border text-secondary text-uppercase"><?= sanitize_input($row['role_name']) ?></span>
                        </td>
                        <td class="py-3 text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <?php if ($row['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                    <span class="text-muted small fw-bold">ONLINE (Tú)</span>
                                <?php else: ?>
                                    <form action="users_management.php" method="POST" onsubmit="return confirm('¿Confirmar eliminación?')">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-link text-danger p-0 small fw-bold text-decoration-none">Borrar</button>
                                    </form>
                                    <span class="text-black-50">|</span>
                                    
                                    <button type="button" class="btn btn-link text-info p-0 small fw-bold text-decoration-none" 
                                            data-bs-toggle="modal" data-bs-target="#infoUserModal"
                                            data-bs-id="<?= $row['id'] ?>" 
                                            data-bs-user="<?= sanitize_input($row['username']) ?>"
                                            data-bs-full="<?= sanitize_input($row['full_name']) ?>"
                                            data-bs-role="<?= sanitize_input($row['role_name']) ?>"
                                            data-bs-hash="<?= sanitize_input($row['password_hash']) ?>">Info</button>
                                    <span class="text-black-50">|</span>

                                    <button type="button" class="btn btn-link text-primary p-0 small fw-bold text-decoration-none" 
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-bs-id="<?= $row['id'] ?>" data-bs-user="<?= sanitize_input($row['username']) ?>"
                                            data-bs-role="<?= $row['role_id'] ?>">Editar</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="4" class="text-center py-5 text-muted">No se encontraron usuarios con ese nombre.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="insert_users.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Dar de Alta Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Usuario (Login)</label>
                        <input type="text" name="user" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Nombre Completo</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Confirmar</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Rol</label>
                        <select name="rol_id" class="form-select" required>
                            <?php foreach ($roles_list as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize_input($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="submit" class="btn btn-bakery w-100 fw-bold rounded-pill">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="users_management.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="id" id="edit_user_id">
                
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Editar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="small fw-bold">Usuario</label>
                        <input type="text" id="edit_username" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-bold">Rol de Acceso</label>
                        <select name="role_id" id="edit_role_id" class="form-select" required>
                            <?php foreach ($roles_list as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize_input($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <hr class="my-4 text-muted">
                    <p class="small text-muted fw-bold mb-2">Cambio de Contraseña (Opcional)</p>
                    <p class="small text-muted mb-3" style="font-size: 0.8rem;">⚠️ Deja estos campos vacíos si no deseas modificar la contraseña actual.</p>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Nueva Clave</label>
                            <input type="password" name="new_password" id="edit_pass1" class="form-control" placeholder="****">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="small fw-bold">Confirmar</label>
                            <input type="password" name="confirm_password" id="edit_pass2" class="form-control" placeholder="****">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="submit" class="btn btn-bakery w-100 fw-bold rounded-pill">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="infoUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pt-4 px-4 bg-info text-white rounded-top-4">
                <h5 class="fw-bold mb-0">ℹ️ Ficha del Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 mt-3">
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <b>ID en Base de Datos:</b> <span id="info_id" class="text-muted"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <b>Usuario (Login):</b> <span id="info_username" class="text-dark fw-bold"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <b>Nombre Completo:</b> <span id="info_full" class="text-muted"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <b>Rol Asignado:</b> <span id="info_role" class="badge bg-secondary text-uppercase"></span>
                    </li>
                </ul>
                <div class="p-3 bg-light rounded border border-warning-subtle">
                    <label class="small fw-bold text-danger">🔑 Contraseña (Hash de Seguridad):</label>
                    <p id="info_hash" class="small text-break mb-1 text-muted" style="font-family: monospace;"></p>
                    <small class="text-muted" style="font-size: 0.7rem;">* Por políticas de ciberseguridad, la contraseña original es invisible.</small>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0 mt-2">
                <button type="button" class="btn btn-outline-secondary w-100 fw-bold rounded-pill" data-bs-dismiss="modal">Cerrar Ficha</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- BUSCADOR EN VIVO ---
function filterTable() {
    let input = document.getElementById('user_live_search');
    let filter = input.value.toLowerCase();
    let rows = document.getElementsByClassName('user-row');
    let noResults = document.getElementById('noResultsRow');
    let visibleCount = 0;

    for (let i = 0; i < rows.length; i++) {
        let username = rows[i].getElementsByClassName('username-cell')[0].innerText.toLowerCase();
        if (username.indexOf(filter) > -1) {
            rows[i].style.display = "";
            visibleCount++;
        } else {
            rows[i].style.display = "none";
        }
    }

    noResults.style.display = (visibleCount === 0) ? "" : "none";
}

function clearSearch() {
    let input = document.getElementById('user_live_search');
    input.value = "";
    filterTable();
}

// --- RELLENAR MODAL EDITAR ---
var editModal = document.getElementById('editUserModal');
editModal.addEventListener('show.bs.modal', function (event) {
    var boton = event.relatedTarget;
    document.getElementById('edit_user_id').value = boton.getAttribute('data-bs-id');
    document.getElementById('edit_username').value = boton.getAttribute('data-bs-user');
    document.getElementById('edit_role_id').value = boton.getAttribute('data-bs-role');
    
    // Limpiamos los campos de contraseña cada vez que se abre el modal
    document.getElementById('edit_pass1').value = '';
    document.getElementById('edit_pass2').value = '';
});

// --- RELLENAR MODAL INFO ---
var infoModal = document.getElementById('infoUserModal');
infoModal.addEventListener('show.bs.modal', function (event) {
    var boton = event.relatedTarget;
    document.getElementById('info_id').innerText = boton.getAttribute('data-bs-id');
    document.getElementById('info_username').innerText = boton.getAttribute('data-bs-user');
    
    var fullName = boton.getAttribute('data-bs-full');
    document.getElementById('info_full').innerText = fullName ? fullName : 'Sin registrar';
    
    document.getElementById('info_role').innerText = boton.getAttribute('data-bs-role');
    document.getElementById('info_hash').innerText = boton.getAttribute('data-bs-hash');
});
</script>
</body>
</html>