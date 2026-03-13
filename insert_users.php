<?php
/**
 * Procesador de Inserción de Usuarios - ERP Bakery
 * User Insertion Processor - ERP Bakery
 */
session_start();
require_once 'db_erp.php'; 
require_once 'functions.php';

//SEGURIDAD Y RESTRICCIÓN
// Usamos require_role que ya creamos en functions.php para centralizar la seguridad
require_role('admin');
$errors=[];
//PROCESAR EL FORMULARIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura y limpieza de datos
    // mb_strtoupper para consistencia en la base de datos
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
    //Si hay errores volvemos a users_management.php
    //If there are errors we return to users_management.php
    if (!empty($errors)) {
        $_SESSION['registry_errors'] = $errors;
        header("Location: users_management.php");
        exit;
    }
    //Hash de la contraseña
    $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);
    
    try {
        //INSERCIÓN EN POSTGRESQL
        $sql = "INSERT INTO users (username, full_name, password_hash, role_id) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user, $full_name, $pass_hashed, $rol_id]);
        
        // ÉXITO: Redirigimos a la tabla con mensaje de éxito
        header("Location: users_management.php?msg=success");
        exit;

    } catch (PDOException $ex) {
        // Manejo de duplicados (PostgreSQL: 23505) EJ: nombre de usuario duplicado.
        if($ex->getCode() == '23505' || $ex->getCode() == '23000'){
            header("Location: users_management.php?msg=error_exists");
        } else {
            // Error técnico 
            header("Location: users_management.php?msg=error_tech");
        }
        exit;
    }
} else {
    // Si alguien intenta entrar a este archivo por URL sin enviar datos, lo echamos
    header('Location: users_management.php');
    exit;
}