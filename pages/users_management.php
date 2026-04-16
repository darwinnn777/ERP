<?php
/**
 * Gestión de Usuarios - ERP Bakery 2026
 * Funcionalidad completa con diseño de Dropdowns
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

require_role('admin');

$alert_message = '';

// --- LÓGICA DE ACTUALIZACIÓN (Tu código original) ---
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

// --- LÓGICA DE BORRADO (Tu código original) ---
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
    $alert_message = "<div class='alert alert-danger border-0 shadow-sm rounded-4 alert-dismissible fade show'>
                        <p class='mb-1 fw-bold small text-uppercase'>Errores:</p>
                        <ul class='mb-0 small'>";
    foreach ($_SESSION['registry_errors'] as $error) { $alert_message .= "<li>" . sanitize_input($error) . "</li>"; }
    $alert_message .= "</ul><button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    unset($_SESSION['registry_errors']);
} elseif (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $text = "";
    switch ($msg) {
        case 'success': $text = "Usuario creado correctamente."; break;
        case 'deleted': $text = "Usuario eliminado."; break;
        case 'updated': $text = "Rol actualizado correctamente."; break;
        case 'updated_pass': $text = "Rol y contraseña actualizados."; break;
    }
    if($text) $alert_message = "<div class='alert alert-info border-0 shadow-sm rounded-4 alert-dismissible fade show'>$text <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
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
<body class="admin-layout">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="text-bakery fw-bold mb-0">Gestión de Personal</h2>
            <p class="text-muted small">Administra los accesos y roles de la panadería.</p>
        </div>
        <div>
            <button type="button" class="btn btn-bakery rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-2"></i> Nuevo Usuario
            </button>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill ms-2">Volver</a>
        </div>
    </div>

    <?= $alert_message ?>

    <div class="mb-4">
        <input type="text" id="user_live_search" class="form-control rounded-pill shadow-sm px-4" 
               placeholder="Buscar por usuario o nombre completo..." onkeyup="filterTable()">
    </div>

    <div class="card card-table shadow-sm rounded-4 overflow-hidden border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="thead-bakery text-center text-white">
                    <tr>
                        <th class="ps-4">Usuario</th>
                        <th class="text-start">Nombre Completo</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-center bg-white">
                    <?php foreach ($users_list as $row): ?>
                    <tr class="user-row">
                        <td class="ps-4 fw-bold text-muted username-cell small"><?= sanitize_input($row['username']) ?></td>
                        <td class="text-start fw-bold name-cell"><?= sanitize_input($row['full_name']) ?></td>
                        <td>
                            <span class="badge rounded-pill border border-bakery text-bakery px-3">
                                <?= sanitize_input($row['role_name']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                <span class="badge bg-success text-white rounded-pill px-3">
                                    <i class="bi bi-person-check-fill me-1"></i> Tú
                                </span>
                            <?php else: ?>
                                <div class="dropdown">
                                    <button class="btn btn-link text-dark p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical fs-5"></i>
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
                                            <form action="users_management.php" method="POST" id="deleteForm<?= $row['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="button" class="dropdown-item py-2 text-danger" 
                                                        onclick="if(confirm('¿Eliminar permanentemente a este usuario?')) document.getElementById('deleteForm<?= $row['id'] ?>').submit();">
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
                        <label class="form-label small fw-bold">Usuario de acceso</label>
                        <input type="text" name="user" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nombre Completo</label>
                        <input type="text" name="full_name" class="form-control rounded-3" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Contraseña</label>
                            <input type="password" name="password" class="form-control rounded-3" required minlength="5">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-bold">Confirmar</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3" required minlength="5">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Asignar Rol</label>
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
                        <label class="form-label small fw-bold">Usuario / Login</label>
                        <input type="text" id="edit_username" class="form-control bg-light border-0" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Rol del Usuario</label>
                        <select name="role_id" id="edit_role_id" class="form-select rounded-3" required>
                            <?php foreach ($roles_list as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize_input($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr class="my-4">
                    <p class="text-muted small fw-bold mb-3"><i class="bi bi-key me-1"></i> Cambiar Contraseña (Opcional)</p>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <input type="password" name="new_password" id="edit_pass1" class="form-control rounded-3" placeholder="Nueva clave">
                        </div>
                        <div class="col-6 mb-3">
                            <input type="password" name="confirm_password" id="edit_pass2" class="form-control rounded-3" placeholder="Repetir">
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
                        <span class="text-muted fw-bold small">ID SISTEMA</span>
                        <span id="info_id" class="small"></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between py-3 border-0">
                        <span class="text-muted fw-bold small">NOMBRE COMPLETO</span>
                        <span id="info_full" class="text-bakery-dark fw-bold small"></span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-light rounded-3 border">
                    <label class="small fw-bold text-muted d-block mb-1">HASH DE SEGURIDAD:</label>
                    <code id="info_hash" class="text-break small text-secondary" style="font-size: 0.75rem;"></code>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// LIVE SEARCH
function filterTable() {
    let filter = document.getElementById('user_live_search').value.toLowerCase();
    let rows = document.querySelectorAll('.user-row');
    rows.forEach(row => {
        let user = row.querySelector('.username-cell').innerText.toLowerCase();
        let name = row.querySelector('.name-cell').innerText.toLowerCase();
        row.style.display = (user.includes(filter) || name.includes(filter)) ? '' : 'none';
    });
}

// LÓGICA DE CARGA DE MODALES (Restaurada de tu original)
var editModal = document.getElementById('editUserModal');
if(editModal){
    editModal.addEventListener('show.bs.modal', function (event) {
        var boton = event.relatedTarget;
        document.getElementById('edit_user_id').value = boton.getAttribute('data-bs-id');
        document.getElementById('edit_username').value = boton.getAttribute('data-bs-user');
        document.getElementById('edit_role_id').value = boton.getAttribute('data-bs-role');
        document.getElementById('edit_pass1').value = '';
        document.getElementById('edit_pass2').value = '';
    });
}

var infoModal = document.getElementById('infoUserModal');
if(infoModal){
    infoModal.addEventListener('show.bs.modal', function (event) {
        var boton = event.relatedTarget;
        document.getElementById('info_id').innerText = boton.getAttribute('data-bs-id');
        document.getElementById('info_username').innerText = boton.getAttribute('data-bs-user');
        document.getElementById('info_full').innerText = boton.getAttribute('data-bs-full') || 'No registrado';
        document.getElementById('info_role').innerText = boton.getAttribute('data-bs-role');
        document.getElementById('info_hash').innerText = boton.getAttribute('data-bs-hash');
    });
}
</script>
</body>
</html>