<?php
require_once 'conexion.php';
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = mb_strtoupper(trim($_POST['usuario']));
    $pass = trim($_POST['password']);
    $rol  = $_POST['rol'];

    // 1. Encriptamos la contraseña con el algoritmo oficial de PHP
    $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO usuarios (usuario, password, rol, estado) VALUES (?, ?, ?, 'Activo')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user, $pass_hashed, $rol]);
        
        $mensaje = "<div class='alert alert-success'>Usuario <b>$user</b> creado con éxito. Ya puedes ir al login.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Creador de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow">
            <div class="card-header bg-primary text-white"><h4>Registrar Usuario Nuevo</h4></div>
            <div class="card-body">
                <?= $mensaje ?>
                <form method="POST">
                    <div class="mb-3">
                        <label>Usuario (9 caracteres):</label>
                        <input type="text" name="usuario" class="form-control" maxlength="9" placeholder="Ej: ADMIN1234" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Rol:</label>
                        <select name="rol" class="form-select">
                            <option value="Administrador">Administrador</option>
                            <option value="Panadero">Panadero</option>
                            <option value="Dependiente">Dependiente</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Crear Usuario</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>