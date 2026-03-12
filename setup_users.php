<?php
// Requerir la conexión a la base de datos de la nube
// Require the cloud database connection
require_once 'db_erp.php'; 

try {
    // 1. Define los usuarios que quieres crear
    // 1. Define the users you want to create
    $usuarios_a_crear = [
        [
            'user' => 'ADMIN',
            'pass' => 'admin123',
            'full' => 'ADMINISTRADOR SISTEMA',
            'role' => 1 // ID for Admin in our setup.sql
        ],
        [
            'user' => 'OPER',
            'pass' => 'operador123',
            'full' => 'OPERADOR PRUEBAS',
            'role' => 2 // ID for Operator in our setup.sql
        ]
    ];

    // PostgreSQL: quitamos backticks y añadimos full_name si tu tabla lo requiere
    $sql = "INSERT INTO users (username, password_hash, role_id, full_name) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($usuarios_a_crear as $u) {
        // Encriptamos la contraseña antes de guardar
        // We encrypt the password before saving
        $hash = password_hash($u['pass'], PASSWORD_DEFAULT);
        
        $stmt->execute([$u['user'], $hash, $u['role'], $u['full']]);
        echo "Usuario <b>{$u['user']}</b> creado correctamente.<br>";
    }

    echo "<br><b style='color:green;'>Listo. Ya puedes borrar este archivo y loguearte.</b>";

} catch (PDOException $e) {
    // PostgreSQL usa el código 23505 para violación de unicidad (usuario duplicado)
    // PostgreSQL uses code 23505 for unique violation
    if ($e->getCode() == '23505' || $e->getCode() == '23000') {
        echo "<b style='color:red;'>Error:</b> Los usuarios ya existen en la base de datos.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>