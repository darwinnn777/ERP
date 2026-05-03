<?php
require_once 'app/Models/AuthModel.php';

class AuthService {
    private $authModel;

    public function __construct() {
        // Pillamos el modelo para poder buscar en la base de datos
        $this->authModel = new AuthModel();
    }

    public function attemptLogin($username, $password) {
        // Si nos mandan los campos vacíos, nos quejamos rápido
        if (empty($username) || empty($password)) {
            throw new Exception("Por favor, rellene todos los campos.");
        }

        // Buscamos si el usuario existe
        $user = $this->authModel->getUserByUsername($username);

        // Comprobamos si existe y si la contraseña cuadra (con el hash de la BD)
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Regeneramos la ID de la sesión por seguridad (para que no nos la roben)
            session_regenerate_id(true); 
            
            // Guardamos los datos que vamos a necesitar luego en otras vistas
            $_SESSION['user_browser'] = $_SERVER['HTTP_USER_AGENT']; 
            $_SESSION['user_id'] = $user['id']; 
            $_SESSION['usuario'] = $user['username'];
            $_SESSION['rol'] = strtolower($user['role_name']); 
            $_SESSION['autorizado'] = true;
            
            return true;
        } else {
            // Error genérico para no dar pistas de si falló el usuario o la clave
            throw new Exception("Usuario o contraseña incorrectos.");
        }
    }

    public function logout() {
        // Si no hay sesión, la arrancamos para poder cargárnosla
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vaciamos todos los datos guardados
        $_SESSION = []; 
        
        // Si usamos cookies para la sesión, forzamos que se borre del navegador
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000, // Le ponemos una fecha del pasado para que caduque ya
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Y por último, destruimos la sesión del servidor del todo
        session_destroy();
    }
}