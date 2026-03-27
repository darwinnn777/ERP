<?php
// Iniciar la sesión para guardar datos del usuario
// Start the session to store user data
session_start();

// Requerir la conexión a la base de datos
// Require the database connection
require_once 'db_erp.php'; 

$errors = ""; 

try {
    // Procesar el formulario al recibir un POST
    // Process the form upon receiving a POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Limpiar los datos del formulario
        // Clean form data
        $user = mb_strtoupper(trim($_POST['usuario'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        // Validar si los campos están vacíos
        // Validate if the fields are empty
        if(empty($user) || empty($password)){
            $errors = "Por favor, rellene todos los campos.";
        } else {
            // Obtener el usuario, su ID y su rol mediante un JOIN
            // Get the user, their ID and their role using a JOIN
            // PostgreSQL: quitamos backticks y añadimos u.id para las funciones de sesión
            $sql = "SELECT u.id, u.username, u.password_hash, r.name as role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.username = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar la contraseña con el hash almacenado
            // Verify the password with the stored hash
            if ($fila && password_verify($password, $fila['password_hash'])) {
                
                // Cambia la llave vieja por una nueva
                session_regenerate_id(true); 
                
                //Guarda la huella del navegador
                 $_SESSION['user_browser'] = $_SERVER['HTTP_USER_AGENT']; 
                // Guardar datos en la sesión
                // Store data in the session
                 //Necesario para get_user_role()
                $_SESSION['user_id'] = $fila['id']; 
                $_SESSION['usuario'] = $fila['username'];
                $_SESSION['rol'] = strtolower($fila['role_name']); 
                $_SESSION['autorizado'] = true;
                
                // Redirigir al panel principal
                // Redirect to the main dashboard
                header("Location: dashboard.php");
                exit; 
            } else {
                // Notificar error de credenciales
                // Notify credentials error
                $errors = "Usuario o contraseña incorrectos."; 
            }
        }
    }
} catch (PDOException $ex) {
    // Capturar errores del sistema
    // Catch system errors
    $errors = "Error en la base de datos: ". $ex->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP Bakery</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold text-bakery">SISTEMA ERP</h2>
                <p class="text-muted">Gestión Comercial y Producción</p>
            </div>

            <div class="card card-login p-4 shadow-sm">
                <div class="card-body">
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger py-2 text-center" style="font-size: 0.9rem;">
                            <?= sanitize_input($errors) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="usuario" class="form-label fw-bold">Usuario:</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Su usuario" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Contraseña:</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        
                        <button type="submit" class="btn btn-bakery w-100 py-2 fw-bold">
                            Entrar al Sistema
                        </button>
                    </form>

                </div>
            </div>

            <p class="text-center mt-4 text-muted small">&copy; <?= date('Y') ?> ERP Comercial - Panadería</p>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>