<?php
require_once 'app/Core/Model.php';

// Este colega es el único que tiene permiso para hablar con la base de datos
class PurchaseOrderModel extends Model {
    
    // Trae todas las órdenes ordenadas por la más reciente (para el listado)
    public function getOrders() {
        return $this->db->query("SELECT * FROM purchase_orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Saca la lista de productos para rellenar el <select> del formulario
    public function getProducts() {
        return $this->db->query("SELECT id, name, price_buy FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Inserta la cabecera del pedido. 
    // OJO: El "RETURNING id" es canela en rama (postgres), nos devuelve el ID recién creado sin tener que hacer otra consulta
    public function createOrder($provider_name, $total_amount) {
        $stmt = $this->db->prepare("INSERT INTO purchase_orders (provider_name, total_amount, status) VALUES (?, ?, 'Pendiente') RETURNING id");
        $stmt->execute([$provider_name, $total_amount]);
        return $stmt->fetchColumn();
    }
    
    // Mete los detalles (la "chicha") del pedido
    public function createOrderItem($po_id, $product_id, $quantity, $price) {
        $stmt = $this->db->prepare("INSERT INTO purchase_order_items (po_id, product_id, quantity, price, received) VALUES (?, ?, ?, ?, FALSE)");
        return $stmt->execute([$po_id, $product_id, $quantity, $price]);
    }
    
    // Busca una orden en concreto, útil para validaciones
    public function getOrderById($id) {
        $stmt = $this->db->prepare("SELECT * FROM purchase_orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Pilla los items que todavía estamos esperando que nos traiga el camión
    public function getPendingItems($po_id) {
        $stmt = $this->db->prepare("
            SELECT poi.*, p.name, p.unit_of_measure 
            FROM purchase_order_items poi
            JOIN products p ON poi.product_id = p.id
            WHERE poi.po_id = ? AND poi.received = FALSE
        ");
        $stmt->execute([$po_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Un poco cutre pero funciona: coge el primer almacén que exista en la BD
    public function getDefaultWarehouse() {
        return $this->db->query("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1")->fetchColumn();
    }
    
    // Nos dice cuánta cantidad pedimos originalmente para evitar que nos cuelen goles
    public function getExpectedItemQuantity($item_id, $po_id) {
        $stmt = $this->db->prepare("SELECT quantity FROM purchase_order_items WHERE id = ? AND po_id = ?");
        $stmt->execute([$item_id, $po_id]);
        return $stmt->fetchColumn();
    }
    
    // Aquí es donde engordamos nuestro stock real. Magia pura.
    public function insertStockLot($product_id, $warehouse_id, $lot, $expiry, $qty) {
        $stmt = $this->db->prepare("INSERT INTO stock_lots (product_id, warehouse_id, lot_number, expiration_date, quantity) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$product_id, $warehouse_id, $lot, $expiry, $qty]);
    }
    
    // Le pone el tick verde al item, diciendo "ya lo tengo"
    public function markItemReceived($item_id, $po_id) {
        $stmt = $this->db->prepare("UPDATE purchase_order_items SET received = TRUE WHERE id = ? AND po_id = ? AND received = FALSE");
        return $stmt->execute([$item_id, $po_id]);
    }
    
    // Cuenta cuántos items faltan por recibir de una orden concreta
    public function countPendingItems($po_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM purchase_order_items WHERE po_id = ? AND received = FALSE");
        $stmt->execute([$po_id]);
        return (int)$stmt->fetchColumn();
    }
    
    // Cambia la chapa de la orden de "Pendiente" a "Recibido"
    public function updateOrderStatus($po_id, $status) {
        $stmt = $this->db->prepare("UPDATE purchase_orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $po_id]);
    }
    
    // Utilidades para transacciones (para no dejar datos a medias si algo revienta) 
    public function beginTransaction() { $this->db->beginTransaction(); }
    public function commit() { $this->db->commit(); }
    public function rollBack() { $this->db->rollBack(); }
    public function inTransaction() { return $this->db->inTransaction(); }
}