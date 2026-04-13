<?php
/**
 * Procesador de Inserción de Usuarios - ERP Bakery
 * User Insertion Processor - ERP Bakery
 */
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

// SEGURIDAD Y RESTRICCIÓN
// Solo administradores pueden crear nuevos usuarios
require_role('admin');

$errors = [];

// PROCESAR EL FORMULARIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // VALIDACIÓN CSRF: Fundamental para evitar inserciones maliciosas externas
    csrf_check($_POST['csrf_token'] ?? '');
    
    // Captura y limpieza de datos
    // mb_strtoupper para consistencia en la base de datos (nombres en mayúsculas)
    $user = mb_strtoupper(trim($_POST['user'] ?? ''));
    $full_name = mb_strtoupper(trim($_POST['full_name'] ?? ''));
    $pass = trim($_POST['password'] ?? '');
    $pass2 = trim($_POST['confirm_password'] ?? '');
    $rol_id = $_POST['rol_id'] ?? '';

    // Validaciones básicas
    if (empty($user) || empty($full_name) || empty($pass) || empty($rol_id)) {
        $errors[] = "Todos los campos son obligatorios.";
    } 
    if (strlen($pass) < 5) {
        $errors[] = "La contraseña es muy corta (mínimo 5 caracteres).";
    }
    if ($pass !== $pass2) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    // Si hay errores volvemos a la gestión de usuarios y los mostramos
    if (!empty($errors)) {
        $_SESSION['registry_errors'] = $errors;
        header("Location: ../pages/users_management.php");
        exit;
    }

    // Hash de la contraseña: Nunca guardamos texto plano
    $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);
    
    try {
        // INSERCIÓN EN LA BASE DE DATOS
        $sql = "INSERT INTO users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user, $full_name, $pass_hashed, $rol_id]);
        
        // ÉXITO: Redirigimos con mensaje de confirmación
        header("Location: ../pages/users_management.php?msg=success");
        exit;

    } catch (PDOException $ex) {
        // Manejo de duplicados (PostgreSQL: 23505)
        // Ejemplo: el nombre de usuario ya existe en el sistema
        if($ex->getCode() == '23505'){
            header("Location: ../pages/users_management.php?msg=error_exists");
        } else {
            // Error técnico genérico
            header("Location: ../pages/users_management.php?msg=error_tech");
        }
        exit;
    }
} else {
    // Si se accede directamente por URL sin POST, redirigir
    header('Location: ../pages/users_management.php');
    exit;
}