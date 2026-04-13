<?php
// Datos sacados de tu Connection Pooler de Supabase
$host     = 'aws-1-eu-north-1.pooler.supabase.com';
$port     = '5432';
$dbname   = 'postgres';
$user     = 'postgres.hpofymuytqppyvxmkwgy';
$password = 'ERPb4k3r1database'; 

try {
    // Cadena de conexión DSN para PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    
    // Creamos la conexión PDO
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
} catch (PDOException $e) {
    die("Error de conexión a la nube: " . $e->getMessage());
}
?>