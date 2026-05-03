<?php
require_once 'app/Models/UserModel.php';

// El servicio encapsula las reglas de negocio, manteniendo el controlador limpio.
class UserService {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    // Centraliza la lógica tanto para crear como para editar usuarios
    public function saveUser($data) {
        // Evaluamos el ID. Si es mayor que 0, es un UPDATE. Si es 0 o null, es un INSERT.
        $id = (int)($data['id'] ?? 0);
        
        if ($id > 0) {
            // === LÓGICA DE EDICIÓN ===
            $new_role = (int)$data['role_id'];
            $new_pass = trim($data['new_password'] ?? '');
            $confirm_pass = trim($data['confirm_password'] ?? '');

            // Si el campo de nueva contraseña no está vacío, validamos y actualizamos
            if (!empty($new_pass)) {
                if (strlen($new_pass) < 5) throw new Exception("La nueva contraseña debe tener al menos 5 caracteres.");
                if ($new_pass !== $confirm_pass) throw new Exception("Las contraseñas nuevas no coinciden.");
                
                // Encriptamos la clave usando bcrypt (por defecto en PHP)
                $pass_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $this->userModel->updateRoleAndPass($id, $new_role, $pass_hashed);
                return "Rol y contraseña actualizados.";
            } else {
                // Si viene vacío, solo actualizamos los permisos/rol
                $this->userModel->updateRole($id, $new_role);
                return "Rol actualizado correctamente.";
            }
        } else {
           
            
            // Limpieza básica y conversión a mayúsculas para estandarizar la info en BD
            $user = mb_strtoupper(trim($data['user'] ?? ''));
            $full_name = mb_strtoupper(trim($data['full_name'] ?? ''));
            $pass = trim($data['password'] ?? '');
            $pass2 = trim($data['confirm_password'] ?? '');
            $rol_id = (int)($data['rol_id'] ?? 0);

            // Validaciones iniciales
            if (empty($user) || empty($full_name) || empty($pass) || empty($rol_id)) {
                throw new Exception("Todos los campos son obligatorios.");
            } 
            if (strlen($pass) < 5) throw new Exception("La contraseña es muy corta (mínimo 5 caracteres).");
            if ($pass !== $pass2) throw new Exception("Las contraseñas no coinciden.");

            $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);
            try {
                // Intentamos registrar en la base de datos
                $this->userModel->createUser($user, $full_name, $pass_hashed, $rol_id);
                return "Usuario creado correctamente.";
            } catch (PDOException $ex) {
                // Capturamos el código 23505 (violación de clave única - UNIQUE constraint)
                if ($ex->getCode() == '23505') throw new Exception("El nombre de usuario ya existe en el sistema.");
                
                // Fallback para cualquier otro error de PDO
                throw new Exception("Error técnico en la base de datos al crear usuario.");
            }
        }
    }

  public function deleteUser($id) {
        if ($id === 1) throw new Exception("No puedes eliminar al administrador principal del sistema.");
        
        try {
            $deleted = $this->userModel->deleteUser($id);
            
            // Catch the silent failure (0 rows deleted)
            if (!$deleted) {
                throw new Exception("No se pudo eliminar. El usuario no existe o es un Administrador protegido.");
            }
            
            return "Usuario eliminado permanentemente.";
            
        } catch (PDOException $ex) {
            throw new Exception("Error al eliminar. ¿El usuario tiene registros (ventas/stock) vinculados?");
        }
    }
}