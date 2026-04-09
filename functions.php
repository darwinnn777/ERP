<?php
// Funciones de Control de Acceso basado en Roles.
// Role-based Access Control Functions.

// 1. Comprueba si el usuario tiene el rol necesario.
// 1. Check if the user has the required role.
function has_role($required_role): bool {
    if (!is_logged_in()) {
        return false;
    }
    
    // Obtenemos el rol de la sesion (ya normalizado en login.php)
    // Get the role from the session (already normalized in login.php)
    $rol_sesion = $_SESSION['rol'] ?? '';
    $current_role = strtolower(trim($rol_sesion));
    
    // Si el rol es admin devuelve verdadero (permiso total).
    // If the role is admin, it returns true (full permission).
    if ($current_role === 'admin') {
        return true;
    }
    
    // Si se pasa una lista de roles (Array).
    // If a list of roles (Array) is passed.
    if (is_array($required_role)) {
        $required_role = array_map('strtolower', $required_role);
        return in_array($current_role, $required_role);
    }
    
    // Si es un solo rol.
    // If it is a single role.
    return $current_role === strtolower($required_role);
}

// 2. Verifica si el usuario esta logueado.
// 2. Verify if the user is logged in.
function is_logged_in(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    //Verificamos: 1. Flag de OK, 2. ID de usuario, 3. Que el navegador no haya cambiado
    return isset($_SESSION['autorizado']) && 
           $_SESSION['autorizado'] === true && 
           isset($_SESSION['user_id']) &&
           isset($_SESSION['user_browser']) && 
           $_SESSION['user_browser'] === $_SERVER['HTTP_USER_AGENT'];
}

// 3. Restringe el acceso a una pagina segun el rol.
// 3. Restrict access to a page according to the role.
function require_role($required_role, string $redirect_page = 'dashboard.php') {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    
    if (!has_role($required_role)) {
        // Guardamos error en sesion para mostrarlo en el dashboard si quieres
        $_SESSION['error_permiso'] = "No tienes permiso para acceder a esta seccion.";
        header("Location: $redirect_page");
        exit;
    }
}

// 4. Devuelve el rol actual.
// 4. Returns current role.
function get_user_role(): string {
    if (is_logged_in() && isset($_SESSION['rol'])) {
        return strtolower($_SESSION['rol']);
    }
    return 'invitado';
}

// 5. Cierra la sesion de forma segura.
// 5. Log out securely.
function log_out() {
            // Si la sesión no se ha iniciado todavía, la abrimos para poder manipularla y cerrarla.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Limpiamos todas las variables de sesión (usuario, rol, id, etc.) poniéndolas en un array vacío.
        // Es como vaciar las maletas antes de irte del hotel.
        $_SESSION = []; 

        //  Este bloque borra la "cookie" de sesión que se guarda en el navegador del usuario.
        // Si no borras la cookie, el navegador podría intentar reconectarse automáticamente.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            // Seteamos una cookie con el mismo nombre pero que caducó hace 42,000 segundos (en el pasado).
            // Esto obliga al navegador a borrarla inmediatamente.
            //Objetivo:
            //Borrar la cookie por seguridad, para asegurar que el cliente no
            // mantenga identificadores de sesión obsoletos
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }

        // Finalmente, destruimos la sesión en el servidor. 
        // El servidor olvida que ese usuario estuvo conectado.
        session_destroy();

        // 5. Redirigimos al usuario a la página de login.
        header('Location: login.php');
        exit;
}

// 6. Limpia entradas de texto.
// 6. Sanitize text inputs.
function sanitize_input($input) {
    if ($input === null){
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
//7. CSRF token para seguridad de formularios
//7. CSRF toekn to security of form.
function csrf_token() {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check($token) {
    if (!isset($_SESSION['csrf']) || $token !== $_SESSION['csrf']) {
        die("CSRF inválido");
    }
}
//8.UNIDADES
function get_units() {
    return [
        'kg' => 'Kilogramos (kg)',
        'g'  => 'Gramos (g)',
        'l'  => 'Litros (L)',
        'ud' => 'Unidades (ud)'
    ];
}
//9.Tipos de producto
function get_product_types() {
    return [
        'Ingredient'    => 'Materia Prima / Ingrediente',
        'Final Product' => 'Producto Final / Venta'
    ];
}
?>