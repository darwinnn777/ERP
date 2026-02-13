<?php
// Inicia la sesión para poder guardar los datos del usuario.
// Start the session to be able to store user data.
session_start();
// Importar la conexión a la base de datos.
// Import the database connection.
require_once 'conexion.php'; 

$errors = ""; 

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recoger y limpiar los datos del formulario.
        // Collect and clean form data.
        $user = mb_strtoupper(trim($_POST['usuario'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        // Comprobar si los campos están vacíos.
        // Check if the fields are empty.
        if(empty($user) || empty($password)){
            $errors = "Por favor, rellene todos los campos.";
        } else {
            //Consulta SQL para obtener el usuario y su rol mediante un JOIN.
            //SQL query to get the user and their role using a JOIN.
            $sql = "SELECT u.username, u.password_hash, r.name as role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.username = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            
            //Verificar la contraseña usando el hash almacenado.
            //Verify the password using the stored hash.
            if ($fila && password_verify($password, $fila['password_hash'])) {
                // Guardar datos en la sesión si el login es correcto.
                // Store data in the session if login is correct.
                $_SESSION['usuario'] = $fila['username'];
                $_SESSION['rol'] = $fila['role_name'];
                
                //Activa el interruptor de seguridad
                // Esto lee la función is_logged_in()
                //Activate the safety switch
                //This reads the is_logged_in() function
                $_SESSION['autorizado']=true;
                // Redirigir al panel de gestión principal.
                // Redirect to the main management dashboard.
                header("Location: dashboard.php");
                exit; 
            } else {
                // Mensaje si las credenciales no coinciden.
                // Message if credentials do not match.
                $errors = "Usuario o contraseña incorrectos."; 
            }
        }
    }
} catch (PDOException $ex) {
    // Capturar errores de la base de datos.
    // Catch database errors.
    $errors = "Error de conexión con el sistema."; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP Gestión Comercial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .card-login { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-erp { background-color: #0d6efd; color: white; transition: 0.3s; }
        .btn-erp:hover { background-color: #0b5ed7; }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">SISTEMA ERP</h2>
                <p class="text-muted">Gestión Comercial y Producción</p>
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
                            <label for="usuario" class="form-label fw-bold">Usuario:</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold">Contraseña:</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        
                        <button type="submit" class="btn btn-erp w-100 py-2 fw-bold">
                            Entrar al Sistema
                        </button>
                    </form>

                </div>
            </div>

            <p class="text-center mt-4 text-muted small">&copy; [cite_start]2026 ERP Comercial - Puesta en Marcha [cite: 13]</p>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>