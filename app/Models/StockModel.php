<?php

class StockModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllStock() {
        $sql = "SELECT 
                sl.id AS lot_id, 
                sl.lot_number, 
                sl.quantity, 
                sl.expiration_date,
                sl.is_discounted AS lot_is_discounted,
                p.id AS product_id,
                p.name AS product_name,
                p.product_type,
                p.unit_of_measure,
                p.price_sell,
                w.name AS warehouse_name,
                (sl.expiration_date - CURRENT_DATE) AS days_left
            FROM public.stock_lots sl
            JOIN public.products p ON sl.product_id = p.id
            JOIN public.warehouses w ON sl.warehouse_id = w.id
            ORDER BY sl.expiration_date DESC NULLS LAST";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function applyDiscount($lotId) {
        $stmt = $this->pdo->prepare("UPDATE public.stock_lots SET is_discounted = TRUE WHERE id = ?");
        $stmt->execute([$lotId]);
        return $stmt->rowCount() > 0;
    }

    public function getDefaultWarehouse() {
        $stmt = $this->pdo->query("SELECT id FROM public.warehouses ORDER BY id ASC LIMIT 1");
        return $stmt->fetchColumn();
    }

    public function addStockLot($productId, $warehouseId, $lotNumber, $expirationDate, $quantity) {
        $sql = "INSERT INTO public.stock_lots (product_id, warehouse_id, lot_number, expiration_date, quantity) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$productId, $warehouseId, $lotNumber, $expirationDate, $quantity]);
        return $this->pdo->lastInsertId();
    }
}
