<?php
/**
 * Gestión de ítems de receta 
 *  Recipe items management
 */
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// Solo personal autorizado 
// Authorized personnel only
require_role(['admin', 'obrador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Seguridad CSRF 
    // CSRF Security
    csrf_check($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? '';
    $final_product_id = (int)($_POST['final_product_id'] ?? 0);
    
    try {
        if ($action === 'add') {
            // Añadir o actualizar ingrediente 
            // Add or update ingredient
            $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
            $quantity_needed = (float)($_POST['quantity_needed'] ?? 0);

            if ($final_product_id > 0 && $ingredient_id > 0 && $quantity_needed > 0) {
                
                // Comprobar si ya existe en la receta 
                // Check if already in recipe
                $check_sql = "SELECT id FROM recipes WHERE final_product_id = ? AND ingredient_id = ?";
                $stmt = $pdo->prepare($check_sql);
                $stmt->execute([$final_product_id, $ingredient_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Actualizar cantidad 
                    // Update quantity
                    $update_sql = "UPDATE recipes SET quantity_needed = quantity_needed + ? WHERE id = ?";
                    $stmt = $pdo->prepare($update_sql);
                    $stmt->execute([$quantity_needed, $existing['id']]);
                } else {
                    // Insertar nuevo ítem 
                    // Insert new item
                    $insert_sql = "INSERT INTO recipes (final_product_id, ingredient_id, quantity_needed) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($insert_sql);
                    $stmt->execute([$final_product_id, $ingredient_id, $quantity_needed]);
                }
                
                header("Location: recipe_details.php?id=$final_product_id&msg=ok");
                exit;
            }
        } elseif ($action === 'delete') {
            // Eliminar de la receta 
            // Delete from recipe
            $recipe_id = (int)($_POST['recipe_id'] ?? 0);
            
            if ($recipe_id > 0) {
                $delete_sql = "DELETE FROM recipes WHERE id = ?";
                $stmt = $pdo->prepare($delete_sql);
                $stmt->execute([$recipe_id]);
                
                header("Location: recipe_details.php?id=$final_product_id&msg=ok");
                exit;
            }
        }
    } catch (PDOException $ex) {
        header("Location: recipe_details.php?id=$final_product_id&msg=error");
        exit;
    }
}

// Redirección por defecto 
// Default redirect
header("Location: products_management.php");
exit;