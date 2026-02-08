<?php
// We use 'db.php' as created in previous steps
require_once 'includes/db.php'; 
$mensaje = "";

// 1. Fetch available roles from the database to populate the dropdown [cite: 8, 10]
try {
    $stmtRoles = $pdo->query("SELECT id, name FROM roles");
    $roles = $stmtRoles->fetchAll();
} catch (PDOException $e) {
    die("Error fetching roles: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = mb_strtoupper(trim($_POST['usuario']));
    $pass = trim($_POST['password']);
    $rol_id = $_POST['rol_id']; // This is now an ID (1 or 2)

    // Encriptamos la contraseña con el algoritmo oficial de PHP
    $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);

    try {
        // Table updated to 'users' and columns matched to our setup.sql
        $sql = "INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user, $pass_hashed, $rol_id]);
        
        $mensaje = "<div class='alert alert-success'>Usuario <b>$user</b> creado con éxito.</div>";
    } catch (PDOException $e) {
        // Handle duplicate usernames 
        if ($e->getCode() == 23000) {
            $mensaje = "<div class='alert alert-danger'>Error: El usuario ya existe.</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración Inicial - Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow border-0">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Registrar Usuario</h4>
            </div>
            <div class="card-body p-4">
                <?= $mensaje ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario:</label>
                        <input type="text" name="usuario" class="form-control" maxlength="50" placeholder="ADMIN_LOG" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol del Sistema:</label>
                        <select name="rol_id" class="form-select" required>
                            <option value="">Seleccione un rol...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        [cite_start]<div class="form-text">Define los permisos de administración o producción[cite: 8].</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Finalizar Registro</button>
                </form>
            </div>
            <div class="card-footer text-center py-3">
                [cite_start]<small class="text-muted">ERP Gestión Comercial - Configuración Inicial [cite: 1]</small>
            </div>
        </div>
    </div>
</body>
</html>