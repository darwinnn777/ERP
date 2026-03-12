<?php
// Requerir el archivo de conexión
// Require the connection file
require_once 'db_erp.php'; 
require_once 'functions.php';

// Iniciar sesión si no está iniciada para comprobar roles
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensaje = "";

// 1. RESTRICCIÓN: Solo el administrador puede entrar a esta página
// 1. RESTRICTION: Only the admin can access this page
// Asegúrate de que require_role() esté definida en functions.php o usa:
if (get_user_role() !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Obtener roles disponibles de la base de datos
// Fetch available roles from the database
try {
    // PostgreSQL no usa backticks
    $stmtRoles = $pdo->query("SELECT id, name FROM roles");
    $roles = $stmtRoles->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Procesar el formulario al recibir un POST
// Process the form upon receiving a POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar y preparar datos usando ?? para evitar errores de "Undefined array key"
    // Clean and prepare data using ?? to avoid "Undefined array key" errors
    $user = mb_strtoupper(trim($_POST['user'] ?? ''));
    $full_name = mb_strtoupper(trim($_POST['full_name'] ?? ''));
    $pass = trim($_POST['password'] ?? '');
    $pass2 = trim($_POST['confirm_password'] ?? '');
    $rol_id = $_POST['rol_id'] ?? '';

    // Validar que los campos obligatorios no estén vacíos
    // Validate that required fields are not empty
    if (empty($user) || empty($full_name) || empty($pass) || empty($rol_id)) {
        $mensaje = "<div class='alert alert-warning'>Por favor, rellene todos los campos obligatorios.</div>";
    } elseif ($pass !== $pass2) {
        $mensaje = "<div class='alert alert-danger'>Las contraseñas no coinciden.</div>";
    } else {
        $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);
        
        try {
            // Insertar el nuevo usuario incluyendo el nombre completo
            // Insert the new user including the full name
            $sql = "INSERT INTO users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user, $full_name, $pass_hashed, $rol_id]);
            
            $mensaje = "<div class='alert alert-success'>Usuario <b>$user</b> registrado con éxito.</div>";
        } catch (PDOException $e) {
            // CAMBIO PARA POSTGRESQL: El código de error para duplicados es '23505' (no 23000)
            // HANDLE DUPLICATES: PostgreSQL use '23505' for unique violations
            if($e->getCode() == '23505' || $e->getCode() == '23000'){
                $mensaje = "<div class='alert alert-danger'>El usuario ya está registrado.</div>";
            } else {
                // Error crítico
                // Critical error
                $mensaje = "<div class='alert alert-danger'>Error técnico: " . $e->getMessage() . "</div>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - ERP Bakery</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-login p-4 shadow-sm">
                <div class="text-center mb-4">
                    <h3 class="text-bakery">Gestión de Usuarios</h3>
                    <p class="text-muted small">Registrar personal del sistema</p>
                </div>

                <?= $mensaje ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nombre de Usuario (Login)</label>
                        <input type="text" name="user" class="form-control" placeholder="Ej: JPEREZ" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre y Apellidos</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Ej: Juan Pérez" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Confirmar Contraseña</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Rol del Empleado</label>
                        <select name="rol_id" class="form-select" required>
                            <option value="">Seleccione un puesto...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize_input($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary w-50">Volver</a>
                        <button type="submit" class="btn btn-bakery w-50 py-2">Dar de Alta</button>
                    </div>
                </form>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">ERP Gestión Comercial © <?= date('Y') ?></small>
            </div>
        </div>
    </div>
</div>

</body>
</html>