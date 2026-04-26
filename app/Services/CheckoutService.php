<?php

class CheckoutService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function processCheckout($cart) {
        if (empty($cart)) {
            throw new Exception("El ticket está vacío");
        }

        try {
            $this->pdo->beginTransaction();

            // Calculate total
            $grandTotal = 0;
            foreach ($cart as $item) {
                $grandTotal += $item['price'] * $item['quantity'];
            }

            // Create sale header
            $stmtSale = $this->pdo->prepare("
                INSERT INTO public.sales (total_amount, created_at)
                VALUES (?, NOW())
                RETURNING id
            ");
            $stmtSale->execute([$grandTotal]);
            $sale_id = $stmtSale->fetchColumn();

            // Process each product
            foreach ($cart as $item) {
                $prodId = $item['id'];
                $toDeduct = $item['quantity'];

                // Validate real stock
                $stmtCheck = $this->pdo->prepare("
                    SELECT COALESCE(SUM(quantity),0)
                    FROM public.stock_lots
                    WHERE product_id = ?
                ");
                $stmtCheck->execute([$prodId]);
                $realStock = (float)$stmtCheck->fetchColumn();

                if ($realStock < $toDeduct) {
                    throw new Exception("Stock insuficiente para {$item['name']}");
                }

                // Save sale item
                $stmtItem = $this->pdo->prepare("
                    INSERT INTO public.sales_items (sale_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtItem->execute([
                    $sale_id,
                    $prodId,
                    $item['quantity'],
                    $item['price']
                ]);

                // ===== FIFO LOTES EN OFERTA =====
                if ($item['is_sale_item']) {
                    $stmtLots = $this->pdo->prepare("
                        SELECT id, quantity
                        FROM public.stock_lots
                        WHERE product_id = ?
                        AND quantity > 0
                        AND is_discounted = TRUE
                        ORDER BY expiration_date ASC
                        FOR UPDATE
                    ");
                    $stmtLots->execute([$prodId]);

                    while ($toDeduct > 0 && $lot = $stmtLots->fetch()) {
                        $take = min($toDeduct, $lot['quantity']);

                        $this->pdo->prepare("
                            UPDATE public.stock_lots
                            SET quantity = quantity - ?
                            WHERE id = ? AND quantity >= ?
                        ")->execute([$take, $lot['id'], $take]);

                        // Register stock movement
                        $this->pdo->prepare("
                            INSERT INTO public.stock_movements
                            (product_id, quantity, movement_type, reference_id)
                            VALUES (?, ?, 'OUT', ?)
                        ")->execute([$prodId, $take, $sale_id]);

                        $toDeduct -= $take;
                    }
                }

                // ===== FIFO LOTES NORMALES =====
                if ($toDeduct > 0) {
                    $stmtNormal = $this->pdo->prepare("
                        SELECT id, quantity
                        FROM public.stock_lots
                        WHERE product_id = ?
                        AND quantity > 0
                        AND is_discounted = FALSE
                        ORDER BY expiration_date ASC
                        FOR UPDATE
                    ");
                    $stmtNormal->execute([$prodId]);

                    while ($toDeduct > 0 && $lot = $stmtNormal->fetch()) {
                        $take = min($toDeduct, $lot['quantity']);

                        $this->pdo->prepare("
                            UPDATE public.stock_lots
                            SET quantity = quantity - ?
                            WHERE id = ? AND quantity >= ?
                        ")->execute([$take, $lot['id'], $take]);

                        // Register movement
                        $this->pdo->prepare("
                            INSERT INTO public.stock_movements
                            (product_id, quantity, movement_type, reference_id)
                            VALUES (?, ?, 'OUT', ?)
                        ")->execute([$prodId, $take, $sale_id]);

                        $toDeduct -= $take;
                    }
                }
            }

            // Commit transaction
            $this->pdo->commit();
            return $sale_id;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("ERROR TPV: " . $e->getMessage());
            throw $e;
        }
    }
}
