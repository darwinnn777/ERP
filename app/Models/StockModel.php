<?php
require_once 'app/Core/Model.php';

class StockModel extends Model {
    public function getInventory() {
        // Obtener lotes con información de producto y almacén
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
            WHERE NOT (p.product_type = 'Final Product' AND sl.quantity <= 0)
            -- Ordenar perennes primero, vigentes en FIFO y caducados al final
            ORDER BY
                CASE
                    WHEN sl.expiration_date IS NULL THEN 0
                    WHEN sl.expiration_date < CURRENT_DATE THEN 2
                    ELSE 1
                END,
                CASE
                    WHEN sl.expiration_date IS NULL OR sl.expiration_date >= CURRENT_DATE THEN sl.expiration_date
                END ASC NULLS FIRST,
                CASE
                    WHEN sl.expiration_date < CURRENT_DATE THEN sl.expiration_date
                END DESC NULLS LAST,
                p.name ASC,
                sl.id ASC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        // Devolver resultados en formato asociativo
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function applyDiscount($lotId) {
        // Marcar lote como descontado
        $stmt = $this->db->prepare("UPDATE public.stock_lots SET is_discounted = TRUE WHERE id = ?");
        $stmt->execute([$lotId]);
        
        // Confirmar si se ha modificado alguna fila
        return $stmt->rowCount() > 0;
    }
}