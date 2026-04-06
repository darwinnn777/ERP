<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */
// Iniciar sesión y seguridad
// Start session and security
session_start();
require_once 'functions.php';
require_once 'db_erp.php';

require_role(['admin', 'obrador']);
//Verificar si la petición es POST
//Check if the request is POST
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action'] ?? '';
    $final_product_id=(int)($_POST['final_product_id']??0);
    
    try{
        if($action==='add'){
            //Añadir o actualizar ingrediente
            //Add or update ingredient
            $ingredient_id=(int)$_POST['ingredient_id'];
            $quantity_needed=(float)$_POST['quantity_needed'];
            if ($final_product_id > 0 && $ingredient_id > 0 && $quantity_needed > 0) {
                // Verificar si el ingrediente ya existe en la receta
                // Check if the ingredient already exists in the recipe
                $check_sql = "SELECT id FROM recipes WHERE final_product_id = ? AND ingredient_id = ?";
                $stmt = $pdo->prepare($check_sql);
                $stmt->execute([$final_product_id, $ingredient_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Actualizar cantidad si ya existe
                    // Update quantity if it exists
                    $update_sql = "UPDATE recipes SET quantity_needed = quantity_needed + ? WHERE id = ?";
                    $stmt = $pdo->prepare($update_sql);
                    $stmt->execute([$quantity_needed, $existing['id']]);
                } else {
                    // Insertar nuevo ingrediente
                    // Insert new ingredient
                    $insert_sql = "INSERT INTO recipes (final_product_id, ingredient_id, quantity_needed) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($insert_sql);
                    $stmt->execute([$final_product_id, $ingredient_id, $quantity_needed]);
                }
                header("Location: recipe_details.php?id=$final_product_id&msg=ok");
                exit;
            }
        }elseif ($action === 'delete') {
            // Eliminar ingrediente de la receta
            // Delete ingredient from recipe
            $recipe_id = (int)$_POST['recipe_id'];
            
            $delete_sql = "DELETE FROM recipes WHERE id = ?";
            $stmt = $pdo->prepare($delete_sql);
            $stmt->execute([$recipe_id]);

            header("Location: recipe_details.php?id=$final_product_id&msg=ok");
            exit;
        }
    } catch (PDOException $ex) {
        // En caso de error, redirigir con mensaje
        // In case of error, redirect with message
        header("Location: recipe_details.php?id=$final_product_id&msg=error");
        exit;
    }
}
// Redirección por defecto si algo falla
// Default redirection if something fails
header("Location: products_management.php");
exit;
