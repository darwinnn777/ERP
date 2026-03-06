<?php
//Funciones de Control de Acceso basado en Roles.
//Role-based Access Control Functions.

//1-Comprueba si la sesión está iniciada
//1-Check if the session is started.
function has_role($required_role): bool {
    if (!is_logged_in()) {
        return false;
    }
    
    //Limpiar el rol actual del usuario (Usa ?? para evitar errores si no existe la clave)
    //Clear the user's current role (Use ?? to avoid errors if the key does not exist)
    $rol_sesion = $_SESSION['rol'] ?? '';
    $current_role = strtolower(filter_var($rol_sesion, FILTER_SANITIZE_SPECIAL_CHARS));
    
    //Si el rol es admin devuelve verdadero y tiene permiso para todo.
    //If the role is admin, it returns true and has permission for everything.
    if ($current_role === 'admin') {
        return true;
    }
    
    //Si pasa una lista de roles permitidos (Array).
    //If a list of allowed roles (Array) is passed.
    if (is_array($required_role)) {
        $required_role = array_map('strtolower', $required_role);
        return in_array($current_role, $required_role);
    }
    
    //Si es un solo rol.
    //If it is a single role.
    return $current_role === strtolower($required_role);
}

//2-Función para verificar y sanear si el usuario está logueado.
//2-Function to verify and clean up if the user is logged in.
function is_logged_in(): bool {
    //Asegura que session_start se ha llamado
    //Ensure that session_start has been called
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['autorizado']) && $_SESSION['autorizado'] === true;
}

//3-Función para que un rol específico pueda acceder a la página funciona 
//porque has_role() incluye el bypass de admin.
//3-Function so that a specific role can access the page works 
//because has_role() includes the admin bypass.
function require_role($required_role, string $redirect_page = 'dashboard.php') {
    if (!is_logged_in()) {
        //Si no está logueado, lo enviamos al login
        //If you are not logged in, we will send you to the login page.
        header('Location: login.php');
        exit;
    }
    
    //Si está logueado pero no tiene el rol requerido 
    //(has_role ahora chequea admin).
    //If you are logged in but do not have the required role 
    //(has_role now checks admin).
    if (!has_role($required_role)) {
        //Redirige con un mensaje de "acceso denegado".
        //Redirect with an “access denied” message.
        $_SESSION['error_permiso'] = "Tu rol no tiene acceso a esta sección.";
        header("Location: $redirect_page");
        exit;
    }
}

//4-Función que devuelve el rol del usuario actual para interfaz.
//4-Function that returns the role of the current user for the interface.
function get_user_role(): string {
    if (is_logged_in() && isset($_SESSION['rol'])) {
        return strtolower($_SESSION['rol']);
    }
    return 'invitado';
}

//5-Función para cerrar sesión.
//5-Function to log out.
function log_out() {
    //Si la sesión no estaba abierta, la abrimos 
    //para poder cerrar bien.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    //Vacia el array de sesión y destruye en el servidor.
    //Empty the session array and destroy it on the server.
    $_SESSION = []; 
    session_destroy();
    
    //Redirigir al login
    //Redirect to login
    header('Location: login.php');
    exit;
}

//6-Función para limpiar entrada.
//6-Function to clear input.
function sanitize_input($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}
?>
//Esto es un comentario para ver si me funciona;