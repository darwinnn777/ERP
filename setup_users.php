<?php
require_once 'db_erp.php'; 

try {
    // 1. Define the users you want to create
    $usuarios_a_crear = [
        [
            'user' => 'ADMIN',
            'pass' => 'admin123',
            'role' => 1 // ID for Admin in our setup.sql
        ],
        [
            'user' => 'OPER',
            'pass' => 'operador123',
            'role' => 2 // ID for Operator in our setup.sql
        ]
    ];

    $sql = "INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    foreach ($usuarios_a_crear as $u) {
        // We encrypt the password before saving
        $hash = password_hash($u['pass'], PASSWORD_DEFAULT);
        
        $stmt->execute([$u['user'], $hash, $u['role']]);
        echo "Usuario <b>{$u['user']}</b> creado correctamente.<br>";
    }

    echo "<br><b style='color:green;'>¡Listo! Ya puedes borrar este archivo y loguearte.</b>";

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "<b style='color:red;'>Error:</b> Los usuarios ya existen en la base de datos.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>