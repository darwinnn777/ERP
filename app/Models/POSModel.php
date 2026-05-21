<?php
require_once 'app/Core/Model.php';

class POSModel extends Model {
    
    // Nos trae los productos finales para vender (nada de ingredientes crudos)
    public function getFinalProducts() {
        return $this->db->query("
            SELECT id, name 
            FROM public.products 
            WHERE product_type = 'Final Product'
            ORDER BY name ASC
        ")->fetchAll();
    }

    // Trae los productos finales con su precio, stock total y stock con descuento en una sola consulta
    public function getFinalProductsWithData() {
        return $this->db->query("
            SELECT 
                p.id, 
                p.name, 
                p.price_sell as price,
                COALESCE(SUM(sl.quantity), 0) as stock,
                COALESCE(SUM(CASE WHEN sl.is_discounted = TRUE THEN sl.quantity ELSE 0 END), 0) as discounted_stock
            FROM public.products p
            LEFT JOIN public.stock_lots sl ON p.id = sl.product_id AND sl.quantity > 0
            WHERE p.product_type = 'Final Product'
            GROUP BY p.id, p.name, p.price_sell
            ORDER BY p.name ASC
        ")->fetchAll();
    }

    // Mira cuánto stock real nos queda de un producto sumando todos sus lotes
    public function getRealStock($productId) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(quantity),0)
            FROM public.stock_lots
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        return (float)$stmt->fetchColumn();
    }

    //  Helpers para las Transacciones (para que no se quede a medias si hay un error) ---
    public function beginTransaction() { $this->db->beginTransaction(); }
    public function commit() { $this->db->commit(); }
    public function rollBack() { $this->db->rollBack(); }
    public function inTransaction() { return $this->db->inTransaction(); }


    //Consultas SQL Puras para crear la venta 
    
    // Registrar venta con datos de pago y devolver identificador
    public function createSale($totalAmount, $paymentMethod, $amountPaid, $changeAmount) {
        $stmt = $this->db->prepare("
            INSERT INTO public.sales (total_amount, created_at, payment_method, amount_paid, change_amount)
            VALUES (?, NOW(), ?, ?, ?)
            RETURNING id
        ");
        $stmt->execute([$totalAmount, $paymentMethod, $amountPaid, $changeAmount]);
        return $stmt->fetchColumn();
    }

    // Guarda cada línea del ticket (producto, cantidad y precio)
    public function createSaleItem($saleId, $productId, $quantity, $price) {
        $stmt = $this->db->prepare("INSERT INTO public.sales_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$saleId, $productId, $quantity, $price]);
    }

    // Nos trae los lotes de un producto ordenados por fecha de caducidad para ir gastando los más viejos primero (FIFO)
    public function getLotsForProduct($productId, $isDiscounted) {
        $stmt = $this->db->prepare("
            SELECT id, quantity
            FROM public.stock_lots
            WHERE product_id = ? AND quantity > 0 AND is_discounted = ?
            ORDER BY expiration_date ASC FOR UPDATE
        ");
        $stmt->execute([$productId, $isDiscounted ? 'TRUE' : 'FALSE']);
        return $stmt->fetchAll();
    }

    // Le resta la cantidad vendida al lote específico
    public function deductLotQuantity($lotId, $amountToDeduct) {
        $stmt = $this->db->prepare("UPDATE public.stock_lots SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        $stmt->execute([$amountToDeduct, $lotId, $amountToDeduct]);
        return $stmt->rowCount();
    }

    // Deja un registro en el historial de movimientos de que ha salido stock por una venta
    public function registerStockMovement($productId, $quantity, $saleId) {
        $stmt = $this->db->prepare("INSERT INTO public.stock_movements (product_id, quantity, movement_type, reference_id) VALUES (?, ?, 'OUT', ?)");
        return $stmt->execute([$productId, $quantity, $saleId]);
    }
}