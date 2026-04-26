<?php

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllUsers() {
        $sql = "SELECT u.id, u.username, u.full_name, u.password_hash, u.role_id, r.name AS role_name
                FROM public.users u
                JOIN public.roles r ON u.role_id = r.id
                ORDER BY u.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRoles() {
        $sql = "SELECT id, name FROM public.roles ORDER BY id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser($username, $fullName, $passwordHash, $roleId) {
        $sql = "INSERT INTO public.users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username, $fullName, $passwordHash, $roleId]);
        return $this->pdo->lastInsertId();
    }

    public function updateUserRole($id, $roleId) {
        $sql = "UPDATE public.users SET role_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$roleId, $id]);
        return $stmt->rowCount() > 0;
    }

    public function updateUserRoleAndPassword($id, $roleId, $passwordHash) {
        $sql = "UPDATE public.users SET role_id = ?, password_hash = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$roleId, $passwordHash, $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteUser($id) {
        // SAFETY LOCK: Cannot delete user ID 1 or any user with Role ID 1
        $sql = "DELETE FROM public.users WHERE id = ? AND id != 1 AND role_id != 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
