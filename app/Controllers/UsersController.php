<?php

require_once __DIR__ . '/../Models/UserModel.php';

class UsersController {
    private $model;

    public function __construct($pdo) {
        $this->model = new UserModel($pdo);
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            return;
        }

        header('Content-Type: application/json');

        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'create_user':
                    $this->createUser();
                    break;
                case 'update_role':
                    $this->updateUser();
                    break;
                case 'delete':
                    $this->deleteUser();
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function createUser() {
        $user = mb_strtoupper(trim($_POST['user'] ?? ''));
        $full_name = mb_strtoupper(trim($_POST['full_name'] ?? ''));
        $pass = trim($_POST['password'] ?? '');
        $pass2 = trim($_POST['confirm_password'] ?? '');
        $rol_id = (int)($_POST['rol_id'] ?? 0);

        // Input Validation
        if (empty($user) || empty($full_name) || empty($pass) || $rol_id <= 0) {
            throw new Exception("Todos los campos son obligatorios.");
        }
        if (strlen($pass) < 5) {
            throw new Exception("La contraseña es muy corta (mínimo 5 caracteres).");
        }
        if ($pass !== $pass2) {
            throw new Exception("Las contraseñas no coinciden.");
        }

        // Security: Hash the password
        $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);

        try {
            $this->model->createUser($user, $full_name, $pass_hashed, $rol_id);
            echo json_encode(['status' => 'success', 'message' => 'Usuario creado correctamente.']);
        } catch (PDOException $ex) {
            // Error code 23505 is PostgreSQL's code for Unique Violation (username already exists)
            if ($ex->getCode() == '23505') {
                throw new Exception("El nombre de usuario ya existe en el sistema.");
            }
            throw new Exception("Error técnico al crear el usuario.");
        }
    }

    private function updateUser() {
        $id = (int)($_POST['id'] ?? 0);
        $new_role = (int)($_POST['role_id'] ?? 0);
        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if ($id <= 0 || $new_role <= 0) {
            throw new Exception("Datos inválidos.");
        }

        // If they provided a new password, we validate and hash it
        if (!empty($new_pass)) {
            if (strlen($new_pass) < 5) {
                throw new Exception("La nueva contraseña debe tener al menos 5 caracteres.");
            }
            if ($new_pass !== $confirm_pass) {
                throw new Exception("Las contraseñas nuevas no coinciden.");
            }
            
            $pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $this->model->updateUserRoleAndPassword($id, $new_role, $pass_hashed);
            echo json_encode(['status' => 'success', 'message' => 'Rol y contraseña actualizados correctamente.']);
        } else {
            // Otherwise, just update the role
            $this->model->updateUserRole($id, $new_role);
            echo json_encode(['status' => 'success', 'message' => 'Rol actualizado correctamente.']);
        }
    }

    private function deleteUser() {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception("ID de usuario inválido.");
        }

        try {
            $success = $this->model->deleteUser($id);
            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado correctamente.']);
            } else {
                throw new Exception("No se pudo eliminar el usuario. (Nota: El administrador principal no puede ser eliminado).");
            }
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    // For the standard HTML View (GET request)
    public function getUsersForView() {
        return $this->model->getAllUsers();
    }

    public function getRolesForView() {
        return $this->model->getAllRoles();
    }
}
