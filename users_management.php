<?php
/**
 * Gestión de Usuarios / User Management
 * ERP Bakery - 2026
 */
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// SEGURIDAD: Restringir el acceso solo a administradores
// SECURITY: Restrict access to administrators only
require_role('admin');

$alert_message = '';

// --- LÓGICA DE ACTUALIZACIÓN / UPDATE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    
    // Validar CSRF / Validate CSRF
    csrf_check($_POST['csrf_token'] ?? '');

    $target_id = (int)$_POST['id'];
    $new_role = (int)$_POST['role_id'];

    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([$new_role, $target_id]);
    
    header("Location: users_management.php?msg=updated");
    exit;
}

// --- LÓGICA DE BORRADO / DELETE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    
    // Validar CSRF / Validate CSRF
    csrf_check($_POST['csrf_token'] ?? '');

    $id_delete = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    
    if ($id_delete) {
        try {
            // Protección: No borrar al administrador principal (ID 1 o Rol Admin)
            // Protection: Do not delete the main admin (ID 1 or Admin role)
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

// --- OBTENER DATOS PARA TABLA / FETCH DATA FOR THE TABLE ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search !== '') {
        // Consulta con filtro / Query with filter
        $users_query = "SELECT u.id, u.username, u.full_name, u.role_id, r.name AS role_name
                        FROM users u
                        JOIN roles r ON u.role_id = r.id
                        WHERE u.username ILIKE ? 
                        ORDER BY u.id DESC";
        $stmt = $pdo->prepare($users_query);
        $stmt->execute([$search . '%']); 
    } else {
        // Consulta general / General query
        $users_query = "SELECT u.id, u.username, u.full_name, u.role_id, r.name AS role_name
                        FROM users u
                        JOIN roles r ON u.role_id = r.id
                        ORDER BY u.id DESC";
        $stmt = $pdo->prepare($users_query);
        $stmt->execute();
    }
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener roles para formularios / Get roles for forms
    $roles_query = "SELECT id, name FROM roles ORDER BY id";
    $stmt_roles = $pdo->prepare($roles_query);
    $stmt_roles->execute();
    $roles_list = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $ex) {
    die("Error SQL: " . $ex->getMessage());
}

// --- MENSAJES DE FEEDBACK / FEEDBACK MESSAGES ---
if (isset($_SESSION['registry_errors'])) {
    $alert_message = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>
                        <p class='mb-1 fw-bold small text-uppercase'>Errores de validación:</p>
                        <ul class='mb-0 small'>";
    foreach ($_SESSION['registry_errors'] as $error) {
        $alert_message .= "<li>" . $error . "</li>";
    }
    $alert_message .= "</ul></div>";
    unset($_SESSION['registry_errors']);
} elseif (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    switch ($msg) {
        case 'success': $alert_message = "<div class='alert alert-success border-0 shadow-sm rounded-4'>Usuario creado correctamente.</div>"; break;
        case 'error_exists': $alert_message = "<div class='alert alert-warning border-0 shadow-sm rounded-4'>El usuario ya existe.</div>"; break;
        case 'deleted': $alert_message = "<div class='alert alert-dark border-0 shadow-sm rounded-4'>Usuario eliminado.</div>"; break;
        case 'updated': $alert_message = "<div class='alert alert-info border-0 shadow-sm rounded-4'>Rol actualizado correctamente.</div>"; break;
        case 'error_tech': $alert_message = "<div class='alert alert-danger border-0 shadow-sm rounded-4'>Error técnico del sistema.</div>"; break;
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
            <form action="users_management.php" method="GET" class="mt-3">
                <div class="input-group shadow-sm rounded-pill overflow-hidden border">
                    <input type="text" name="search" id="user_search_input" class="form-control border-0 ps-3" 
                           placeholder="Buscar usuario..." list="user_suggestions" autocomplete="off"
                           value="<?= sanitize_input($search) ?>">
                    <datalist id="user_suggestions">
                        <?php foreach ($users_list as $user_opt): ?>
                            <option value="<?= sanitize_input($user_opt['username']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    
                    <button class="btn btn-white text-secondary border-start" type="submit">Buscar</button>
                    
                    <?php if ($search !== ''): ?>
                        <a href="users_management.php" class="btn btn-light text-danger border-start px-3">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <button type="button" class="btn btn-bakery px-4 rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            + Nuevo Usuario
        </button>
    </div>

    <?= $alert_message ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="text-muted small text-uppercase">
                        <th class="ps-4 py-3">Usuario / User</th>
                        <th class="py-3">Nombre / Full Name</th>
                        <th class="py-3 text-center">Rol / Role</th>
                        <th class="py-3 text-center">Acciones / Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users_list)): ?>
                        <?php foreach ($users_list as $row): ?>
                        <tr>
                            <td class="ps-4 py-3 fw-semibold"><?= sanitize_input($row['username']) ?></td>
                            <td class="py-3 text-secondary"><?= sanitize_input($row['full_name']) ?></td>
                            <td class="py-3 text-center">
                                <span class="badge border text-secondary text-uppercase"><?= sanitize_input($row['role_name']) ?></span>
                            </td>
                            <td class="py-3 text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if ($row['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                        <span class="text-muted small fw-bold">ONLINE</span>
                                    <?php else: ?>
                                        <form action="users_management.php" method="POST" onsubmit="return confirm('¿Confirmar eliminación?')">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-link text-danger p-0 small fw-bold text-decoration-none">Borrar</button>
                                        </form>
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
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">No se encontraron resultados.</td></tr>
                    <?php endif; ?>
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
                    <h5 class="fw-bold text-bakery">Registrar Nuevo Empleado</h5>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Nombre de Usuario</label>
                        <input type="text" name="user" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Nombre Completo</label>
                        <input type="text" name="full_name" class="form-control rounded-3" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-bold text-uppercase">Contraseña</label>
                            <input type="password" name="password" class="form-control rounded-3" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-bold text-uppercase">Confirmar</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted fw-bold text-uppercase">Rol Asignado</label>
                        <select name="rol_id" class="form-select rounded-3" required>
                            <?php foreach ($roles_list as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-bakery rounded-pill px-4 fw-bold">Guardar Usuario</button>
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
                    <h5 class="fw-bold text-bakery">Editar Rol de Usuario</h5>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold text-uppercase">Usuario Seleccionado</label>
                        <input type="text" id="edit_username" class="form-control bg-light" readonly>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted fw-bold text-uppercase">Nuevo Rol</label>
                        <select name="role_id" id="edit_role_id" class="form-select rounded-3" required>
                            <?php foreach ($roles_list as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= $role['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-2">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-bakery rounded-pill px-4 fw-bold">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Rellenar modal de edición / Fill edit modal
var miModal = document.getElementById('editUserModal');
miModal.addEventListener('show.bs.modal', function (event) {
    var boton = event.relatedTarget;
    document.getElementById('edit_user_id').value = boton.getAttribute('data-bs-id');
    document.getElementById('edit_username').value = boton.getAttribute('data-bs-user');
    document.getElementById('edit_role_id').value = boton.getAttribute('data-bs-role');
});
</script>
</body>
</html>