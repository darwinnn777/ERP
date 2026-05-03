<?php
require_once 'app/Core/Model.php';

class ProductModel extends Model {
    
    // Saca todos los productos ordenaditos por nombre
    public function getAllProducts() {
        $sql = "SELECT * FROM products ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Busca un producto concreto por su ID
    public function getProductById($id) {
        $sql = "SELECT * FROM products WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Mete un producto nuevo en la base de datos
    public function createProduct($sku, $name, $type, $unit, $price_sell, $price_buy) {
        $sql = "INSERT INTO products (sku, name, product_type, unit_of_measure, price_sell, price_buy) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sku, $name, $type, $unit, $price_sell, $price_buy]);
    }

    // Actualiza los datos de un producto que ya existía
    public function updateProduct($id, $sku, $name, $type, $unit, $price_sell, $price_buy) {
        $sql = "UPDATE products SET sku = ?, name = ?, product_type = ?, unit_of_measure = ?, price_sell = ?, price_buy = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$sku, $name, $type, $unit, $price_sell, $price_buy, $id]);
    }

    // Se carga un producto de la tabla
    public function deleteProduct($id) {
        // si el producto se usa en otro lado, esto saltará por los aires (el controlador lo captura)
        $sql = "DELETE FROM products WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Le pone la ruta de la foto a un producto
    public function updateImage($id, $imagePath) {
        $sql = "UPDATE products SET image_url = :image_url WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':image_url' => $imagePath, ':id' => $id]);
    }
}