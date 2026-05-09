<?php
// Cargamos las variables de entorno desde el archivo .env
// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Saltar comentarios
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Leemos las credenciales desde .env (nunca hardcodeadas en el código)
// Read credentials from .env (never hardcoded in source code)
$host     = $_ENV['DB_HOST']     ?? '';
$port     = $_ENV['DB_PORT']     ?? '5432';
$dbname   = $_ENV['DB_NAME']     ?? '';
$user     = $_ENV['DB_USER']     ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

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