<?php
require_once 'app/Core/Model.php';


class AuthModel extends Model {
    

     // Busca y devuelve los datos de un usuario a partir de su nombre de usuario.
    public function getUserByUsername($username) {

        $sql = "SELECT u.id, u.username, u.password_hash, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.username = ?";
        
        // Preparamos la sentencia usando el objeto de conexión PDO heredado de la clase Model.
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}