<?php
// includes/db.php - Conexión a Supabase Cloud

$host = 'db.hpofymuytqppyvxmkwgy.supabase.co';
$db   = 'postgres';
$user = 'postgres';
$pass = 'ERPb4k3r1database'; 
$port = '5432';

// CAMBIO CRÍTICO: Usamos pgsql en lugar de mysql
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Si quieres probar si funciona, puedes poner un: echo "Conectado a la nube";
} catch (\PDOException $e) {
    // Si falla, nos dirá por qué (ej: contraseña incorrecta o falta driver)
    die("Error de conexión a Supabase: " . $e->getMessage());
}
?>