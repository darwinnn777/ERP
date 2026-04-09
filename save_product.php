<?php
session_start();
/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 * Controlador para guardar productos y subir imágenes
 */

require_once 'db_erp.php';
require_once 'functions.php';
require_role(['admin', 'obrador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    //VERIFY CSRF
    csrf_check($_POST['csrf_token'] ?? '');
    // UPLOAD IMAGE 
    if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        $product_id = $_POST['id'];
        $directory = "img_products/";

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $image_name = $_FILES["product_image"]["name"];
        $tmp_name = $_FILES["product_image"]["tmp_name"];
        $extension = pathinfo($image_name, PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array(strtolower($extension), $allowed_extensions)) {
            // Error: Extensión no permitida
            header("Location: products_management.php?msg=invalid_format");
            exit;
        }

        $path = $directory . uniqid() . "." . $extension;

        if (move_uploaded_file($tmp_name, $path)) {
            $sql = "UPDATE products SET image_url = :image_url WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':image_url' => $path, ':id' => $product_id])) {
                header("Location: products_management.php?msg=success");
            } else {
                header("Location: products_management.php?msg=error_db");
            }
        } else {
            header("Location: products_management.php?msg=error_upload");
        }
        exit;
    }

    //DELETE PRODUCT 
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
        $product_id = $_POST['id'];
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':id' => $product_id])) {
                header("Location: products_management.php?msg=deleted");
            } else {
                header("Location: products_management.php?msg=error_deleted");
            }
        } catch (PDOException $ex) {
            // Error de clave foránea (producto en uso)
            header("Location: products_management.php?msg=error_in_use");
        }
        exit;
    }

    // REGISTER OR EDIT PRODUCT 
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'Ingredient';
    $unit = $_POST['unit'] ?? 'unit';
    $id = $_POST['id'] ?? null;

    if (empty($sku) || empty($name)) {
        header("Location: products_management.php?msg=empty_fields");
        exit;
    }

    try {
        if ($id) {
            $sql = "UPDATE products SET sku=?, name=?, product_type=?, unit_of_measure = ? WHERE id =?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $type, $unit, $id]);
        } else {
            $sql = "INSERT INTO products (sku, name, product_type, unit_of_measure, price_sell, price_buy) 
                    VALUES (?, ?, ?, ?, 0, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $type, $unit]);
        }
        header("Location: products_management.php?msg=ok");
    } catch (PDOException $ex) {
        header("Location: products_management.php?msg=error_db");
    }
    exit;

} else {
    header("Location: products_management.php");
    exit;
}
