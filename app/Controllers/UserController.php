<?php
require_once 'app/Models/UserModel.php';
require_once 'app/Services/UserService.php';
require_once 'config/functions.php';

class UserController {
    // Definimos las propiedades privadas para instanciar nuestras clases de apoyo
    private $userModel;
    private $userService;

    public function __construct() {
        // Inicializamos el modelo y el servicio en el constructor para tenerlos disponibles
        $this->userModel = new UserModel();
        $this->userService = new UserService();
    }

// Carga la vista con la tabla de usuarios
    public function index() {
        // solo administradores pueden acceder
        require_role('admin');
        
        // Solicitamos los datos al modelo para enviárselos a la vista
        $users_list = $this->userModel->getAllUsers();
        $roles_list = $this->userModel->getRoles();
        
        // Renderizamos la vista (donde se pintarán las variables obtenidas)
        require_once 'app/Views/users/index.php';
    }

    // Método para crear o actualizar un usuario (es llamado vía AJAX)
    public function save() {
        // Configuramos la cabecera para que el frontend sepa que enviamos un JSON
        header('Content-Type: application/json');
        require_role('admin');
        
        // Validación contra ataques Cross-Site Request Forgery (CSRF)
        csrf_check($_POST['csrf_token'] ?? '');

        try {
            // Delegamos toda la lógica compleja de validación e inserción al servicio
            $message = $this->userService->saveUser($_POST);
            
            // Si todo va bien, devolvemos success true y el mensaje de éxito
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            // Si el servicio lanza una excepción (ej. "Contraseña muy corta"), capturamos el error
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Método para borrar un usuario (también vía AJAX)
    public function delete() {
        header('Content-Type: application/json');
        require_role('admin');
        csrf_check($_POST['csrf_token'] ?? '');

        // Aseguramos que el ID sea un número entero para evitar inyección en la lógica posterior
        $id = (int)($_POST['id'] ?? 0);
        try {
            // Solicitamos al servicio la eliminación
            $message = $this->userService->deleteUser($id);
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}