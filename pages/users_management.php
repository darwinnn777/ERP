<?php
/**
 * Gestión de Usuarios / User Management
 * ERP Bakery - 2026
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

// SEGURIDAD: Restringir el acceso solo a administradores
require_role('admin');

$alert_message = '';

// --- LÓGICA DE ACTUALIZACIÓN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    csrf_check($_POST['csrf_token'] ?? '');
    
    $target_id = (int)$_POST['id'];
    $new_role = (int)$_POST['role_id'];
    $new_pass = trim($_POST['new_password'] ?? '');
    $confirm_pass = trim($_POST['confirm_password'] ?? '');

    if (!empty($new_pass)) {
        if (strlen($new_pass) < 5) {
            $_SESSION['registry_errors'] = ["La nueva contraseña debe tener al menos 5 caracteres."];
            header("Location: users_management.php");
            exit;
        }

        if ($new_pass === $confirm_pass) {
            $pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET role_id = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$new_role, $pass_hashed, $target_id]);
            header("Location: users_management.php?msg=updated_pass");
            exit;
        } else {
            $_SESSION['registry_errors'] = ["Las contraseñas nuevas no coinciden."];
            header("Location: users_management.php");
            exit;
        }
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$new_role, $target_id]);
        header("Location: users_management.php?msg=updated");
        exit;
    }
}

// --- LÓGICA DE BORRADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    csrf_check($_POST['csrf_token'] ?? '');
    $id_delete = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    
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

// --- OBTENER DATOS ---
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
    foreach ($_SESSION['registry_errors'] as $error) { 
        $alert_message .= "<li>" . sanitize_input($error) . "</li>"; 
    }
    $alert_message .= "</ul></div>";
    unset($_SESSION['registry_errors']);
} elseif (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    switch ($msg) {
        case 'success': {
            $alert_message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>Usuario creado correctamente.</div>"; 
            break;
        }
        case 'deleted': {
            $alert_message = "<div class='alert alert-dark border-0 shadow-sm rounded-4'>Usuario eliminado.</div>"; 
            break;
        }
        case 'updated': {
            $alert_message = "<div class='alert alert-info border-0 shadow-sm rounded-4'>Rol actualizado correctamente.</div>"; 
            break;
        }
        case 'updated_pass': {
            $alert_message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>Rol y Contraseña actualizados correctamente.</div>"; 
            break;
        }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-bakery-light p-4">

<div class="container">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-white p-2 rounded shadow-sm border">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-bakery text-decoration-none">Inicio</a></li>
            <li class="breadcrumb-item active text-muted">Gestión de Usuarios</li>
        </ol>
    </nav>

    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h2 class="text-bakery-dark fw-bold mb-0">Gestión de Personal</h2>
            <p class="text-muted mb-0">Administra los accesos y roles de la panadería.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button type="button" class="btn btn-bakery px-4 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-2"></i> Nuevo Usuario
            </button>
        </div>
    </div>

    <div class="mb-4">
        <div class="input-group shadow-sm rounded-pill overflow-hidden border bg-white">
            <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="user_live_search" class="form-control border-0" 
                   placeholder="Buscar por nombre de usuario..." autocomplete="off"
                   onkeyup="filterTable()">
            <button class="btn btn-link text-secondary text-decoration-none border-start" type="button" onclick="clearSearch()">Limpiar</button>
        </div>
    </div>

    <?= $alert_message ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="bg-bakery-dark text-white">
                    <tr class="small text-uppercase">
                        <th class="ps-4 py-3">Usuario</th>
                        <th class="py-3">Nombre Completo</th>
                        <th class="py-3 text-center">Rol</th>
                        <th class="py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $row): ?>
                    <tr class="user-row">
                        <td class="ps-4 py-3 fw-bold text-bakery-dark username-cell"><?= sanitize_input($row['username']) ?></td>
                        <td class="py-3 text-secondary"><?= sanitize_input($row['full_name']) ?></td>
                        <td class="py-3 text-center">
                            <span class="badge border border-bakery text-bakery text-uppercase rounded-pill px-3"><?= sanitize_input($row['role_name']) ?></span>
                        </td>
                        <td class="py-3 text-center">
                            <?php if ($row['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                <span class="badge bg-success text-white rounded-pill px-3">
                                    <i class="bi bi-person-check-fill me-1"></i> Tú
                                </span>
                            <?php else: ?>
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle border shadow-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                        <li>
                                            <button type="button" class="dropdown-item py-2" 
                                                    data-bs-toggle="modal" data-bs-target="#infoUserModal"
                                                    data-bs-id="<?= $row['id'] ?>" 
                                                    data-bs-user="<?= sanitize_input($row['username']) ?>"
                                                    data-bs-full="<?= sanitize_input($row['full_name']) ?>"
                                                    data-bs-role="<?= sanitize_input($row['role_name']) ?>"
                                                    data-bs-hash="<?= sanitize_input($row['password_hash']) ?>">
                                                <i class="bi bi-info-circle text-info me-2"></i> Detalles
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item py-2" 
                                                    data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                    data-bs-id="<?= $row['id'] ?>" 
                                                    data-bs-user="<?= sanitize_input($row['username']) ?>"
                                                    data-bs-role="<?= $row['role_id'] ?>">
                                                <i class="bi bi-pencil-square text-primary me-2"></i> Editar Rol/Clave
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="users_management.php" method="POST" onsubmit="return confirm('¿Eliminar permanentemente a este usuario?')">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="dropdown-item py-2 text-danger">
                                                    <i class="bi bi-trash3-fill me-2"></i> Eliminar
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr id="noResultsRow" class="d-none">
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-person-exclamation fs-1 d-block mb-2"></i>
                            No se encontraron usuarios que coincidan.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="../database/insert_users.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="modal-header bg-bakery-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i> Registrar Empleado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-bakery-dark">Usuario de acceso</label>
                        <input type="text" name="user" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-bakery-dark">Nombre Completo</label>
                        <input type="text" name="full_name" class="form-control rounded-3" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-bakery-dark">Contraseña</label>
                            <input type="password" name="password" class="form-control rounded-3" required minlength="5">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-bakery-dark">Confirmar</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3" required minlength="5">
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-bold text-bakery-dark">Asignar Rol</label>
                        <select name="rol_id" class="form-select rounded-3" required>
                            <?php foreach ($roles_list as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize_input($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-bakery w-100 fw-bold py-2 rounded-pill shadow">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="users_management.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="id" id="edit_user_id">
                <div class="modal-header bg-bakery-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Editar Credenciales</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ID / Login</label>
                        <input type="text" id="edit_username" class="form-control bg-light border-0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-bakery-dark">Rol del Usuario</label>
                        <select name="role_id" id="edit_role_id" class="form-select rounded-3" required>
                            <?php foreach ($roles_list as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize_input($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr class="my-4">
                    <p class="text-muted small fw-bold mb-3"><i class="bi bi-key me-1"></i> Actualizar Contraseña (Opcional)</p>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-bakery-dark">Nueva Clave</label>
                            <input type="password" name="new_password" id="edit_pass1" class="form-control rounded-3" placeholder="Mín. 5 carac.">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold text-bakery-dark">Confirmar</label>
                            <input type="password" name="confirm_password" id="edit_pass2" class="form-control rounded-3" placeholder="Confirmar">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-bakery w-100 fw-bold py-2 rounded-pill shadow">Actualizar Información</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="infoUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header bg-bakery text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-card-list me-2"></i> Perfil del Empleado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <div class="bg-bakery-light d-inline-block p-3 rounded-circle border border-bakery mb-2">
                        <i class="bi bi-person-fill text-bakery fs-1"></i>
                    </div>
                    <h4 id="info_username" class="fw-bold text-bakery-dark mb-0"></h4>
                    <span id="info_role" class="badge bg-bakery text-white rounded-pill px-3 mt-1"></span>
                </div>
                <div class="list-group list-group-flush border-top border-bottom">
                    <div class="list-group-item d-flex justify-content-between py-3 border-0">
                        <span class="text-muted fw-bold">ID Sistema</span>
                        <span id="info_id"></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between py-3 border-0">
                        <span class="text-muted fw-bold">Nombre Completo</span>
                        <span id="info_full" class="text-bakery-dark fw-bold"></span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-light rounded-3 border">
                    <label class="small fw-bold text-muted d-block mb-1">Hash de Seguridad (Cifrado):</label>
                    <code id="info_hash" class="text-break small text-secondary"></code>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary w-100 rounded-pill fw-bold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterTable() {
    let input = document.getElementById('user_live_search');
    let filter = input.value.toLowerCase();
    let rows = document.getElementsByClassName('user-row');
    let noResults = document.getElementById('noResultsRow');
    let visibleCount = 0;

    for (let i = 0; i < rows.length; i++) {
        let username = rows[i].getElementsByClassName('username-cell')[0].innerText.toLowerCase();
        if (username.indexOf(filter) > -1) {
            rows[i].classList.remove('d-none');
            visibleCount++;
        } else {
            rows[i].classList.add('d-none');
        }
    }
    visibleCount === 0 ? noResults.classList.remove('d-none') : noResults.classList.add('d-none');
}

function clearSearch() {
    document.getElementById('user_live_search').value = "";
    filterTable();
}

var editModal = document.getElementById('editUserModal');
editModal.addEventListener('show.bs.modal', function (event) {
    var boton = event.relatedTarget;
    document.getElementById('edit_user_id').value = boton.getAttribute('data-bs-id');
    document.getElementById('edit_username').value = boton.getAttribute('data-bs-user');
    document.getElementById('edit_role_id').value = boton.getAttribute('data-bs-role');
    document.getElementById('edit_pass1').value = '';
    document.getElementById('edit_pass2').value = '';
});

var infoModal = document.getElementById('infoUserModal');
infoModal.addEventListener('show.bs.modal', function (event) {
    var boton = event.relatedTarget;
    document.getElementById('info_id').innerText = boton.getAttribute('data-bs-id');
    document.getElementById('info_username').innerText = boton.getAttribute('data-bs-user');
    var fullName = boton.getAttribute('data-bs-full');
    document.getElementById('info_full').innerText = fullName ? fullName : 'No registrado';
    document.getElementById('info_role').innerText = boton.getAttribute('data-bs-role');
    document.getElementById('info_hash').innerText = boton.getAttribute('data-bs-hash');
});
</script>
</body>
</html>