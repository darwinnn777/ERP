<?php
require_once 'app/Core/Model.php';

class StockModel extends Model {
    public function getInventory() {
        // La query tocha. Juntamos la tabla de lotes con productos y almacenes.
        //calculamos los días que le quedan de vida al producto restando la fecha de caducidad con la de hoy.
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
            -- Perennes arriba; vigentes FIFO (caduca antes = antes); caducados al final (historial reciente primero)
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
        // Devolvemos todo el tinglado en formato array asociativo para manejarlo fácil en la vista
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function applyDiscount($lotId) {
        // le clavamos un TRUE al campo is_discounted del lote que nos pasen
        $stmt = $this->db->prepare("UPDATE public.stock_lots SET is_discounted = TRUE WHERE id = ?");
        $stmt->execute([$lotId]);
        
        // Devolvemos true si hemos modificado alguna fila (es decir, si el descuento no estaba ya aplicado)
        return $stmt->rowCount() > 0;
    }
}