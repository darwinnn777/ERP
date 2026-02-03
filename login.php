<?php
session_start();
require_once 'conexion.php';

$errors = ""; 

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = mb_strtoupper(trim($_POST['usuario'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        
        if(mb_strlen($user) !== 9){
            $errors = "El usuario debe ser de 9 caracteres.";
        } else {
            $stmt = $pdo->prepare("SELECT usuario, password, rol, estado FROM usuarios WHERE BINARY usuario = ?");
            $stmt->execute([$user]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fila && password_verify($password, $fila['password'])) {
                if ($fila['estado'] === "Activo") {
                    $_SESSION['usuario'] = $user;
                    $_SESSION['rol'] = $fila['rol'];
                    header("Location: gestion.php");
                    exit; 
                } else {
                    $errors = "Cuenta inactiva. Contacta con el administrador."; 
                }
            } else {
                $errors = "Error en usuario o contraseña."; 
            }
        }
    }
} catch (PDOException $ex) {
    $errors = "Error con la base de datos."; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panadería</title>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold" style="color: #a35d45;">PANADERÍA</h2>
                <p class="text-muted">Gestión de Inventario y Ventas</p>
            </div>

            <div class="card card-login p-4">
                <div class="card-body">
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 text-center" style="font-size: 0.9rem;">
                            <?= htmlspecialchars($errors) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="usuario" class="form-label fw-bold">NIF/NIE (Usuario):</label>
                            <input type="text" class="form-control shadow-sm" id="usuario" name="usuario" placeholder="Ej: 12345678A" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Contraseña:</label>
                            <input type="password" class="form-control shadow-sm" id="password" name="password" placeholder="Contraseña" required>
                        </div>
                        
                        <button type="submit" class="btn btn-bakery w-100 py-2 fw-bold shadow-sm">
                            Iniciar Sesión
                        </button>
                    </form>

                </div>
            </div>

            <p class="text-center mt-4 text-muted small">&copy; 2026 Sistema de Gestión Artesanal</p>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>