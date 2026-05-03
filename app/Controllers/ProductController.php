<?php
require_once 'app/Models/ProductModel.php';
require_once 'app/Services/ProductService.php'; 
require_once 'config/functions.php';

class ProductController {
    private $productModel;
    private $productService;

    public function __construct() {
        // Arrancamos el modelo y el servicio
        $this->productModel = new ProductModel();
        $this->productService = new ProductService();
    }

    public function index() {
        // Pillamos todos los productos de la BD
        $all_products = $this->productModel->getAllProducts();
        // Traemos las listas de medidas (kg, litros, etc) y los tipos de producto (ingrediente, final)
        $units = get_units();
        $product_types = get_product_types();

        // Cargamos la pantalla principal del catálogo
        require_once 'app/Views/products/index.php';
    }

    public function save() {
        // Como esto va por AJAX, avisamos que devolvemos datos en formato JSON
        header('Content-Type: application/json');
        require_role(['admin', 'obrador']);
        csrf_check($_POST['csrf_token'] ?? '');

        try {
            // Le pasamos el marrón de validar y guardar los datos al Servicio
            $message = $this->productService->saveProduct($_POST);
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            // Si algo falla (ej. faltan campos), mandamos el error
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function delete() {
        header('Content-Type: application/json');
        require_role(['admin', 'obrador']);
        csrf_check($_POST['csrf_token'] ?? '');

        // Pillamos la ID del producto, si no nos mandan nada le ponemos un 0
        $product_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($product_id > 0) {
            try {
                // Si se borra bien en la BD, damos el OK
                if ($this->productModel->deleteProduct($product_id)) {
                    echo json_encode(['success' => true, 'message' => 'Producto eliminado correctamente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error: El producto no existe.']);
                }
            } catch (PDOException $ex) {
                // Si peta, lo normal es porque este producto se está usando en alguna receta o venta y la BD no deja borrarlo
                echo json_encode(['success' => false, 'message' => 'Error: El producto está siendo usado en recetas o pedidos.']);
            }
        }
        exit;
    }

    public function uploadImage() {
        header('Content-Type: application/json');
        require_role(['admin', 'obrador']);
        csrf_check($_POST['csrf_token'] ?? '');

        $product_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        try {
            // Le pedimos al Servicio que se encargue de guardar la imagen en la carpeta y actualizar la BD
            $message = $this->productService->uploadImage($product_id, $_FILES['product_image'] ?? null);
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}