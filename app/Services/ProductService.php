<?php
require_once 'app/Models/ProductModel.php';

class ProductService {
    private $productModel;

    public function __construct() {
        $this->productModel = new ProductModel();
    }

    // Esta función vale tanto para crear como para editar, según si nos pasan la ID o no
    public function saveProduct($data) {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        
        // Limpiamos los datos. Al SKU (el código) le ponemos mayúsculas y le quitamos espacios
        $sku = strtoupper(trim($data['sku'] ?? ''));
        $name = trim($data['name'] ?? '');
        $type = $data['type'] ?? 'Ingredient';
        $unit = $data['unit'] ?? '';
        $price_sell = (float)($data['price_sell'] ?? 0);
        $price_buy = (float)($data['price_buy'] ?? 0);

        // Si nos intentan colar un producto sin nombre o sin SKU, cortamos el rollo
        if (empty($sku) || empty($name) || empty($unit)) {
            throw new Exception("Por favor, completa los campos requeridos.");
        }

        try {
            // Si tiene ID, es que estamos editando uno que ya existe
            if ($id > 0) {
                $this->productModel->updateProduct($id, $sku, $name, $type, $unit, $price_sell, $price_buy);
                return 'Producto actualizado correctamente.';
            } else {
                // Si no tiene ID, es uno nuevo de paquete
                $this->productModel->createProduct($sku, $name, $type, $unit, $price_sell, $price_buy);
                return 'Nuevo producto añadido al catálogo.';
            }
        } catch (PDOException $ex) {
            // Si la base de datos se queja (por ejemplo, si intentas repetir un SKU)
            throw new Exception("Error de base de datos. ¿El SKU ya existe?");
        }
    }

    // La función que se encarga del follón de las imágenes
    public function uploadImage($productId, $fileArray) {
        // Si no hay foto, o hubo error al subirla, a casa
        if ($productId <= 0 || !isset($fileArray) || $fileArray['error'] !== 0) {
            throw new Exception("No se recibió ninguna imagen válida.");
        }

        // Si la carpeta de imágenes no existe, la creamos
        $directory = "assets/img_products/"; 
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Antes de subir la nueva foto, borramos la vieja para no llenar el disco duro a lo tonto
        $oldProduct = $this->productModel->getProductById($productId);
        if ($oldProduct && !empty($oldProduct['image_url'])) {
            $oldPath = "assets/" . $oldProduct['image_url'];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Pillamos los datos de la foto nueva
        $image_name = $fileArray["name"];
        $tmp_name = $fileArray["tmp_name"];
        $extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        // Lista VIP de extensiones permitidas
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        // Si intentan subir un PDF o un virus... ¡Alto ahí!
        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception("Formato inválido. Solo JPG, PNG, WEBP.");
        }

        // Le ponemos un nombre único inventado (prod_1234.jpg) para que no haya dos iguales
        $relativePath = "img_products/" . uniqid('prod_') . "." . $extension;
        $fullPath = "assets/" . $relativePath;

        // Movemos la foto de su escondite temporal a la carpeta oficial
        if (move_uploaded_file($tmp_name, $fullPath)) {
            // Guardamos la ruta nueva en la base de datos
            $this->productModel->updateImage($productId, $relativePath);
            return 'Imagen actualizada con éxito.';
        } else {
            throw new Exception("Fallo al mover el archivo de imagen.");
        }
    }
}