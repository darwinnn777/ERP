<?php
// Iniciar sesión y cargar configuración
// Start session and load configuration
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// SEGURIDAD-Solo administradores pueden aplicar descuentos
// SECURITY-Only administrators can apply discounts
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //VERIFY CSRF
    csrf_check($_POST['csrf_token'] ?? '');
    
    // Obtener y castear el ID del producto
    // Get and cast product ID
    $product_id = (int)($_POST['product_id'] ?? 0);

    if ($product_id > 0) {
        try {
            // Actualizar precio y marcar como descontado solo si no lo estaba
            // Update price and mark as discounted only if it wasn't already
            $sql = "UPDATE products 
                    SET price_sell = price_sell / 2, 
                        is_discounted = TRUE 
                    WHERE id = ? 
                      AND is_discounted = FALSE 
                      AND product_type = 'Final Product'";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$product_id]);

            // Verificar si hubo cambios para dar feedback preciso
            // Check if any row was affected to provide precise feedback
            if ($stmt->rowCount() > 0) {
                header("Location: stock.php?msg=discount_ok");
            } else {
                header("Location: stock.php?msg=no_change");
            }
            exit;

        } catch (PDOException $e) {
            header("Location: stock.php?msg=error");
            exit;
        }
    }
}

// Redirección de seguridad si se accede directamente
// Security redirect if accessed directly
header("Location: stock.php");
exit;