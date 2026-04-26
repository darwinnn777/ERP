<?php
session_start();
/*
 * Registrar Pedidos - ERP Bakery
 * Purchase Order Registration
 */

session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

// Limitar el paso solo a los jefes 
// Restrict access to admins only
require_role(['admin']);

// Validar el token de seguridad para evitar ataques
// Check CSRF token for security
csrf_check($_POST['csrf_token'] ?? '');

// Capturar los datos que vienen del formulario 
// Capture form data
$proveedor = $_POST['provider'] ?? '';
$id_prod   = (int)($_POST['product_id'] ?? 0);
$cantidad  = (float)($_POST['quantity'] ?? 0);
$precio    = (float)($_POST['price_unit'] ?? 0);

// Comprobar que no falte nada importante 
// Validate mandatory fields
if (!$proveedor || $id_prod <= 0 || $cantidad <= 0) {
    header("Location: ../pages/purchase_orders.php?msg=invalid_data");
    exit;
}

// Calcular el total de la jugada 
// Calculate total amount
$total = $cantidad * $precio;

try {
    // Empezar con la transacción (tenerlo en memoria, definitivo de momento no)
    // Start DB transaction
    $pdo->beginTransaction();

    // Insertar la cabecera del pedido  
    // Insert order header
    $sql1 = "INSERT INTO purchase_orders (provider_name, total_amount, status) VALUES (?, ?, 'Pendiente')";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute([$proveedor, $total]);
    
    // Pillar el ID que acaba de nacer para el detalle 
    // Get last inserted ID
    $id_orden = $pdo->lastInsertId();

    // Insertar el producto dentro de esa orden 
    // Insert order line items
    $sql2 = "INSERT INTO purchase_order_items (po_id, product_id, quantity, price, received) VALUES (?, ?, ?, ?, FALSE)";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute([$id_orden, $id_prod, $cantidad, $precio]);

    // Confirmar los cambios si no ha petado nada 
    // Commit changes to database
    $pdo->commit();

    // Mandar al usuario de vuelta con el mensaje de éxito 
    // Redirect with success message
    header("Location: ../pages/purchase_orders.php?msg=po_created");
    exit;

} catch (Exception $e) {
    // Deshacer todo el rastro si algo ha salido mal 
    // Rollback changes on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Anotar el fallo en el cuaderno de errores 
    // Log system error
    error_log("Fallo al crear pedido: " . $e->getMessage());
    
    // Escapar al listado con aviso de error 
    // Redirect with error status
    header("Location: ../pages/purchase_orders.php?msg=error");
    exit;
}