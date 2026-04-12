<?php
/**
 * Controlador para guardar productos y subir imágenes - ERP Bakery
 */
session_start();
require_once 'db_erp.php';
require_once 'functions.php';

// Seguridad: Solo admin y obrador pueden manipular el catálogo
require_role(['admin', 'obrador']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // VERIFICACIÓN CSRF - Obligatorio para todas las acciones POST
    csrf_check($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // --- ACCIÓN: SUBIR IMAGEN ---
    if ($action === 'upload_image' && $product_id > 0) {
        $directory = "img_products/";

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $image_name = $_FILES["product_image"]["name"];
            $tmp_name = $_FILES["product_image"]["tmp_name"];
            $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $allowed_extensions)) {
                header("Location: products_management.php?msg=invalid_format");
                exit;
            }

            $path = $directory . uniqid('prod_') . "." . $extension;

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
        }
        exit;
    }

    // ELIMINAR PRODUCTO
    if ($action === 'delete_product' && $product_id > 0) {
        try {
            $sql = "DELETE FROM products WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':id' => $product_id])) {
                header("Location: products_management.php?msg=deleted");
            } else {
                header("Location: products_management.php?msg=error_deleted");
            }
        } catch (PDOException $ex) {
            header("Location: products_management.php?msg=error_in_use");
        }
        exit;
    }

    // REGISTRAR O EDITAR PRODUCTO 
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'Ingredient';
    $unit = $_POST['unit'] ?? '';
    
    // NUENO: CAPTURAMOS LOS PRECIOS
    $price_sell = (float)($_POST['price_sell'] ?? 0);
    $price_buy = (float)($_POST['price_buy'] ?? 0);

    if (empty($sku) || empty($name) || empty($unit)) {
        header("Location: products_management.php?msg=empty_fields");
        exit;
    }

    try {
        if ($product_id > 0) {
            // EDITAR: Actualizamos con los nuevos precios
            $sql = "UPDATE products SET sku = ?, name = ?, product_type = ?, unit_of_measure = ?, price_sell = ?, price_buy = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $type, $unit, $price_sell, $price_buy, $product_id]);
        } else {
            // INSERTAR NUEVO: Guardamos los precios reales en lugar de 0, 0
            $sql = "INSERT INTO products (sku, name, product_type, unit_of_measure, price_sell, price_buy) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku, $name, $type, $unit, $price_sell, $price_buy]);
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