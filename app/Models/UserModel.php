<?php
require_once 'app/Core/Model.php';

class UserModel extends Model {
    
    // Recupera todos los usuarios combinando datos con la tabla de roles (JOIN)
    public function getAllUsers() {
        return $this->db->query("
            SELECT u.id, u.username, u.full_name, u.password_hash, u.role_id, r.name AS role_name
            FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id DESC
        ")->fetchAll(PDO::FETCH_ASSOC); // Fetch as asociative array para iterar cómodamente
    }

    // Recupera la lista de roles disponibles para los menús desplegables
    public function getRoles() {
        return $this->db->query("SELECT id, name FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Inserta un nuevo usuario en el sistema. Usamos sentencias preparadas (?) para evitar SQL Injection
    public function createUser($user, $full_name, $pass_hashed, $rol_id) {
        $stmt = $this->db->prepare("INSERT INTO users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user, $full_name, $pass_hashed, $rol_id]);
    }

    // Actualiza tanto el rol como la contraseña (si el usuario decidió cambiarla)
    public function updateRoleAndPass($id, $rol_id, $pass_hashed) {
        $stmt = $this->db->prepare("UPDATE users SET role_id = ?, password_hash = ? WHERE id = ?");
        return $stmt->execute([$rol_id, $pass_hashed, $id]);
    }

    // Actualiza solo el rol, manteniendo la contraseña intacta
    public function updateRole($id, $rol_id) {
        $stmt = $this->db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        return $stmt->execute([$rol_id, $id]);
    }

    // Elimina un usuario. 
    // Añadimos en la propia query la condición de no borrar al id 1 (Admin Root)
    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ? AND id != 1 AND role_id != 1");
        return $stmt->execute([$id]);
    }
}