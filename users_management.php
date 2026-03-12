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
             //
         } catch (PDOException $ex) {
             $alert_message="<div class='alert alert-danger shadow-sm'> Error al eliminar usuario.</div>";
         }
     }
 }
//Obtener datos para tabla 
//FETCH DATA FOR THE TABLE.
 try{
     
     //Unir usuarios y roles para obtener el nombre del 
     //rol en lugar de solo el id
     //Join users and roles to get the role name instead of just the ID
     $users_query="SELECT u.id, u.username, u.full_name, r.name as role_name"
             . "FROM users u JOIN roles r ON u.role_id =r.id"
             . "ORDER BY u.id DESC";
 } catch (PDOException $ex) {
     die("<div class='alert alert-danger m-4'>Error crítico de base de datos.</div>");
 }
 //Mensajes de retroalimentación
 //Feedback de retroalimentación
if (isset($_GET['msg'])) {
    $msg_type = sanitize_input($_GET['msg']);
    $msg_text = ($msg_type === 'deleted') ? 'Registro eliminado.' : 'Registro guardado.';
    $alert_message = "<div class='alert alert-dark alert-dismissible fade show border-0 shadow-sm' role='alert'>
                        $msg_text
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
            <p class="text-muted small mb-0">Administración de accesos al sistema ERP</p>
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
                            <th class="pe-4 py-3 text-end fw-medium">ACCIONES</th>
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
                                <td class="pe-4 py-3 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <?php if ($row['username'] !== 'ADMIN'): ?>
                                            <form action="user_management.php" method="POST" onsubmit="return confirm('¿Confirmar eliminación?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 small">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5">No hay datos disponibles.</td></tr>
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
            <form action="insertar.php" method="POST">
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="fw-bold text-bakery">Nuevo Registro de Usuario</h5>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">USERNAME</label>
                        <input type="text" name="user" class="form-control rounded-3 py-2" required placeholder="Ej: JSMITH">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-bold">FULL NAME</label>
                        <input type="text" name="full_name" class="form-control rounded-3 py-2" required>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-bold">PASSWORD</label>
                            <input type="password" name="password" class="form-control rounded-3 py-2" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-bold">CONFIRM</label>
                            <input type="password" name="confirm_password" class="form-control rounded-3 py-2" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted fw-bold">ROLE</label>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
