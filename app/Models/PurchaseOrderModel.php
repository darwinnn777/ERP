<?php

class PurchaseOrderModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllOrders() {
        $stmt = $this->pdo->query("SELECT * FROM public.purchase_orders ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllProducts() {
        $stmt = $this->pdo->query("SELECT id, name, price_buy FROM public.products ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createOrderHeader($providerName, $totalAmount) {
        $sql = "INSERT INTO public.purchase_orders (provider_name, total_amount, status) VALUES (?, ?, 'Pendiente')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$providerName, $totalAmount]);
        return $this->pdo->lastInsertId();
    }

    public function createOrderItem($orderId, $productId, $quantity, $price) {
        $sql = "INSERT INTO public.purchase_order_items (po_id, product_id, quantity, price, received) VALUES (?, ?, ?, ?, FALSE)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$orderId, $productId, $quantity, $price]);
        return $stmt->rowCount() > 0;
    }

    public function getOrderById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM public.purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPendingItems($poId) {
        $sql = "SELECT poi.*, p.name, p.unit_of_measure 
                FROM public.purchase_order_items poi
                JOIN public.products p ON poi.product_id = p.id
                WHERE poi.po_id = ? AND poi.received = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$poId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getExpectedQuantity($itemId, $poId) {
        $stmt = $this->pdo->prepare("SELECT quantity FROM public.purchase_order_items WHERE id = ? AND po_id = ?");
        $stmt->execute([$itemId, $poId]);
        return $stmt->fetchColumn();
    }

    public function markItemReceived($itemId, $poId) {
        $sql = "UPDATE public.purchase_order_items SET received = TRUE WHERE id = ? AND po_id = ? AND received = FALSE";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$itemId, $poId]);
        return $stmt->rowCount() > 0;
    }

    public function countPendingItems($poId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM public.purchase_order_items WHERE po_id = ? AND received = FALSE");
        $stmt->execute([$poId]);
        return (int)$stmt->fetchColumn();
    }

    public function closeOrder($poId) {
        $stmt = $this->pdo->prepare("UPDATE public.purchase_orders SET status = 'Recibido' WHERE id = ?");
        $stmt->execute([$poId]);
        return $stmt->rowCount() > 0;
    }
}
