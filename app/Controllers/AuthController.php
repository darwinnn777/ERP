<?php
// Cargamos las dependencias y configuraciones necesarias.
require_once 'app/Models/AuthModel.php';
require_once 'app/Services/AuthService.php';
require_once 'config/functions.php';


//Controlador encargado de gestionar la autenticación del usuario
//mostrar el login, procesar las crednciales y cerrar la sesión.

class AuthController {
    
    // Almacena la instancia del servicio que contiene la lógica de negocio real
    private $authService;

    public function __construct() {
        // Inicializamos el servicio de autenticación a instanciar el controlador
        $this->authService = new AuthService();
    }

    /**
     * Muestra la vista del formulario de login.
     */
    public function showLogin() {
        // Comprobamos si el usuario ya tiene una sesión activa.
        if (is_logged_in()) {
            header('Location: ' . BASE_URL . 'dashboard');
            exit; 
        }
        
        // Si no está logueado, cargamos la vista del login.
        require_once 'app/Views/auth/login.php';
    }


     //Procesa la solicitud de inicio de sesión cuando se envía el formulario

    public function processLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // Saneamos los datos de entrada
            $user = mb_strtoupper(trim($_POST['usuario'] ?? ''));
            $password = trim($_POST['password'] ?? '');

            try {
                // Delegamos la lógica de verificación al servicio.
                // si las credenciaes son incorrectas, lanzar una Exception.
                $this->authService->attemptLogin($user, $password);
                
                // redirigimos al área privada.
                header("Location: " . BASE_URL . "dashboard");
                exit;
                
            } catch (Exception $e) {
                // Si el servicio lanza una excepcion
                // capturamos el mensaje de error.
                $errors = $e->getMessage();
                
                // Volvemos a cargar la vista del login, y la vista podrá mostrar la variable $errors.
                require_once 'app/Views/auth/login.php';
            }
        }
    }

    
     // Cierra la sesión activa del usuario y lo devuelve al inicio.
    public function logout() {
        // Limpiamos las variables de sesión y destruimos la cookie a través del servicio.
        $this->authService->logout();
        
        // Redirigimos de vuelta a la pantalla de login.
        header("Location: " . BASE_URL . "login");
        exit;
    }
}