<?php

require_once __DIR__ . '/../Models/ProductModel.php';

class ProductsController {
    private $model;

    public function __construct($pdo) {
        $this->model = new ProductModel($pdo);
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            return;
        }

        header('Content-Type: application/json');

        // We use $_POST directly because AJAX JS FormData sends data as multipart/form-data
        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'save_product':
                    $this->saveProduct();
                    break;
                case 'delete_product':
                    $this->deleteProduct();
                    break;
                case 'upload_image':
                    $this->uploadImage();
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function saveProduct() {
        $id = (int)($_POST['id'] ?? 0);
        $sku = strtoupper(trim($_POST['sku'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'Ingredient';
        $unit = $_POST['unit'] ?? '';
        $price_sell = (float)($_POST['price_sell'] ?? 0);
        $price_buy = (float)($_POST['price_buy'] ?? 0);

        // 1. Controller Input Validation
        if (empty($sku) || empty($name) || empty($unit)) {
            throw new Exception("Por favor, rellene todos los campos obligatorios (SKU, Nombre, Unidad).");
        }

        // 2. Controller tells Model to update DB
        if ($id > 0) {
            $this->model->updateProduct($id, $sku, $name, $type, $unit, $price_sell, $price_buy);
            echo json_encode(['status' => 'success', 'message' => 'Producto actualizado correctamente.']);
        } else {
            $this->model->createProduct($sku, $name, $type, $unit, $price_sell, $price_buy);
            echo json_encode(['status' => 'success', 'message' => 'Producto creado correctamente.']);
        }
    }

    private function deleteProduct() {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception("ID de producto inválido.");
        }

        try {
            $this->model->deleteProduct($id);
            echo json_encode(['status' => 'success', 'message' => 'Producto eliminado correctamente.']);
        } catch (Exception $ex) {
            // If the database complains (e.g. Foreign Key constraint because it's used in a recipe)
            throw new Exception("No se puede eliminar el producto porque está en uso (ej. en recetas o stock).");
        }
    }

    private function uploadImage() {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception("ID de producto inválido.");
        }

        // Controller Input Validation (Is it a valid file?)
        if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== 0) {
            throw new Exception("Por favor, seleccione una imagen válida.");
        }

        $directory = __DIR__ . "/../../actions/img_products/"; 
        $public_path = "img_products/"; // Relative path stored in DB

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $image_name = $_FILES["product_image"]["name"];
        $tmp_name = $_FILES["product_image"]["tmp_name"];
        $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        // Controller Business Rule Validation
        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception("Formato de imagen no permitido. Use JPG, PNG o WEBP.");
        }

        $fileName = uniqid('prod_') . "." . $extension;
        $absolutePath = $directory . $fileName;
        $dbPath = $public_path . $fileName;

        if (move_uploaded_file($tmp_name, $absolutePath)) {
            $this->model->updateImage($id, $dbPath);
            echo json_encode(['status' => 'success', 'message' => 'Imagen actualizada correctamente.']);
        } else {
            throw new Exception("Error al guardar la imagen en el servidor.");
        }
    }

    // For the standard HTML View (GET request)
    public function getAllForView() {
        return $this->model->getAllProducts();
    }
}
