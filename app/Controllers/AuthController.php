<?php

class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login() {
        if (is_logged_in()) {
            header('Location: dashboard.php');
            exit;
        }

        $errors = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $user = mb_strtoupper(trim($_POST['usuario'] ?? ''));
                $password = trim($_POST['password'] ?? '');

                if (empty($user) || empty($password)) {
                    $errors = "Por favor, rellene todos los campos.";
                } else {
                    $sql = "SELECT u.id, u.username, u.password_hash, u.full_name, r.name as role_name 
                            FROM users u 
                            JOIN roles r ON u.role_id = r.id 
                            WHERE u.username = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$user]);
                    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($fila && password_verify($password, $fila['password_hash'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_browser'] = $_SERVER['HTTP_USER_AGENT'];
                        $_SESSION['user_id'] = $fila['id'];
                        $_SESSION['usuario'] = $fila['username'];
                        $_SESSION['full_name'] = $fila['full_name'] ?? $fila['username'];
                        $_SESSION['rol'] = strtolower($fila['role_name']);
                        $_SESSION['autorizado'] = true;
                        
                        header("Location: dashboard.php");
                        exit;
                    } else {
                        $errors = "Usuario o contraseña incorrectos.";
                    }
                }
            } catch (PDOException $ex) {
                $errors = "Error en la base de datos: " . $ex->getMessage();
            }
        }

        // Pass errors to the view
        require __DIR__ . '/../Views/login.php';
    }

    public function logout() {
        log_out(); // Uses the function from functions.php
        exit;
    }
}
