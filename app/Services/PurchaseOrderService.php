<?php

require_once __DIR__ . '/../Models/PurchaseOrderModel.php';
require_once __DIR__ . '/../Models/StockModel.php';

class PurchaseOrderService {
    private $pdo;
    private $model;
    private $stockModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->model = new PurchaseOrderModel($pdo);
        $this->stockModel = new StockModel($pdo);
    }

    public function createOrder($providerName, $productId, $quantity, $price) {
        $totalAmount = $quantity * $price;

        try {
            $this->pdo->beginTransaction();

            // 1. Create Order Header
            $orderId = $this->model->createOrderHeader($providerName, $totalAmount);

            // 2. Create Order Item
            $this->model->createOrderItem($orderId, $productId, $quantity, $price);

            $this->pdo->commit();
            return $orderId;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Fallo al crear pedido en Service: " . $e->getMessage());
            throw new Exception("Error interno al crear el pedido. Revisa los logs.");
        }
    }

    public function receiveStock($poId, $items) {
        if ($poId <= 0 || empty($items)) {
            throw new Exception("Datos de recepción inválidos.");
        }

        try {
            $this->pdo->beginTransaction();

            $warehouseId = $this->stockModel->getDefaultWarehouse();
            if (!$warehouseId) {
                throw new Exception("No hay almacenes definidos.");
            }

            foreach ($items as $item) {
                $qtyReceived = (float)($item['qty_received'] ?? 0);
                $itemId = (int)($item['item_id'] ?? 0);
                $productId = (int)($item['product_id'] ?? 0);
                $lot = trim($item['lot_number'] ?? '');
                $expiry = $item['expiration_date'] ?? '';

                if ($qtyReceived <= 0) {
                    throw new Exception("Cantidad inválida detectada.");
                }
                if (empty($lot) || empty($expiry)) {
                    throw new Exception("Lote o fecha de caducidad incompletos.");
                }

                $expectedQty = $this->model->getExpectedQuantity($itemId, $poId);
                if ($expectedQty === false) {
                    throw new Exception("Item no válido o manipulado.");
                }
                if ($qtyReceived > (float)$expectedQty) {
                    throw new Exception("Cantidad mayor a la solicitada.");
                }

                // 1. Insert into stock_lots
                $this->stockModel->addStockLot($productId, $warehouseId, $lot, $expiry, $qtyReceived);

                // 2. Mark item as received
                $this->model->markItemReceived($itemId, $poId);
            }

            // 3. Close order if all items are received
            $pending = $this->model->countPendingItems($poId);
            if ($pending === 0) {
                $this->model->closeOrder($poId);
            }

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("ERROR RECEPCION en Service: " . $e->getMessage());
            throw new Exception("Error al recibir el stock: " . $e->getMessage());
        }
    }
}
