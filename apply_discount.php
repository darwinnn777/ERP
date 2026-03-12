<?php
require_once 'functions.php';
require_once 'db_erp.php';

// Solo el admin o supervisor debería poder cambiar precios
require_role(['admin']); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_producto'])) {
    $id_producto = $_POST['id_producto'];

    try {
        // Dividimos el precio actual a la mitad en la tabla products
        $sql = "UPDATE products SET price_sell = (price_sell / 2) WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_producto]);

        // Volvemos a la página de stock
        header("Location: stock.php?mensaje=descuento_ok");
        exit();

    } catch (PDOException $e) {
        die("Error al aplicar descuento: " . $e->getMessage());
    }
} else {
    header("Location: stock.php");
    exit();
}
?>