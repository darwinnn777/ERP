<?php

class ProductModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllProducts() {
        $sql = "SELECT * FROM public.products ORDER BY name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createProduct($sku, $name, $type, $unit, $price_sell, $price_buy) {
        $sql = "INSERT INTO public.products (sku, name, product_type, unit_of_measure, price_sell, price_buy) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sku, $name, $type, $unit, $price_sell, $price_buy]);
        return $this->pdo->lastInsertId();
    }

    public function updateProduct($id, $sku, $name, $type, $unit, $price_sell, $price_buy) {
        $sql = "UPDATE public.products 
                SET sku = ?, name = ?, product_type = ?, unit_of_measure = ?, price_sell = ?, price_buy = ? 
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$sku, $name, $type, $unit, $price_sell, $price_buy, $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteProduct($id) {
        $sql = "DELETE FROM public.products WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function updateImage($id, $imagePath) {
        $sql = "UPDATE public.products SET image_url = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$imagePath, $id]);
        return $stmt->rowCount() > 0;
    }
}
