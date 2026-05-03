<?php
require_once 'app/Models/PurchaseOrderModel.php';

class PurchaseOrderService {
    private $poModel;

    public function __construct() {
        $this->poModel = new PurchaseOrderModel();
    }

    // Aquí cocinamos la creación de la orden
    public function processCreateOrder($provider, $product_id, $quantity, $price_unit) {
        // Un poco de validación cutre pero efectiva para que no metan basura
        if (empty($provider) || $product_id <= 0 || $quantity <= 0) {
            throw new Exception("Datos incompletos para crear el pedido.");
        }

        $total = $quantity * $price_unit;

        try {
            // Abrimos transacción por si algo peta a la mitad, que no se quede la BD coja
            $this->poModel->beginTransaction();
            
            // 1. Guardamos la orden padre
            $po_id = $this->poModel->createOrder($provider, $total);
            
            // 2. Le metemos el hijo (el item)
            $this->poModel->createOrderItem($po_id, $product_id, $quantity, $price_unit);
            
            // Si llegamos vivos aquí, hacemos el commit (guardar de verdad)
            $this->poModel->commit();
            return "Orden de compra generada correctamente.";
        } catch (Exception $e) {
            // Ups, ha reventado algo. Rollback y hacemos como si no hubiese pasado nada
            if ($this->poModel->inTransaction()) {
                $this->poModel->rollBack();
            }
            throw new Exception("Fallo al crear la orden de compra en la base de datos.");
        }
    }

    // La movida de cuando el repartidor nos trae las cajas
    public function processReceiveStock($po_id, $items) {
        // Chequeo rápido de idioteces
        if ($po_id <= 0 || empty($items)) {
            throw new Exception("Datos de recepción inválidos.");
        }

        try {
            // Otra transacción por si las moscas, que aquí tocamos stock y es sagrado
            $this->poModel->beginTransaction();

            // Pillamos el almacén (siempre pilla el primero que pilla, ojo con esto a futuro si metemos más)
            $warehouse_id = $this->poModel->getDefaultWarehouse();
            if (!$warehouse_id) {
                throw new Exception("No hay almacenes definidos en el sistema.");
            }

            // Recorremos todo lo que nos dicen que ha llegado
            foreach ($items as $item) {
                $qty_received = (float)($item['qty_received'] ?? 0);
                $item_id      = (int)($item['item_id'] ?? 0);
                $product_id   = (int)($item['product_id'] ?? 0);
                $lot          = trim($item['lot_number'] ?? '');
                $expiry       = $item['expiration_date'] ?? '';

                // Más validaciones paranoicas
                if ($qty_received <= 0) throw new Exception("Cantidad recibida inválida.");
                if (empty($lot) || empty($expiry)) throw new Exception("Lote o caducidad incompletos.");

                // Comprobamos cuánto pedimos realmente para que no nos la cuelen
                $expected_qty = $this->poModel->getExpectedItemQuantity($item_id, $po_id);
                if ($expected_qty === false) throw new Exception("Ítem no válido o manipulado.");
                
                // Si nos traen más de lo pedido... no amigo, aquí no somos tontos.
                if ($qty_received > (float)$expected_qty) throw new Exception("Cantidad recibida mayor a la solicitada.");

                // Magia: lo metemos como lote nuevo en el almacén
                $this->poModel->insertStockLot($product_id, $warehouse_id, $lot, $expiry, $qty_received);

                // Y tachamos esto de la lista de tareas
                $this->poModel->markItemReceived($item_id, $po_id);
            }

            // A ver si ya hemos recibido todos los items de este pedido
            $pending = $this->poModel->countPendingItems($po_id);
            if ($pending === 0) {
                // Si ya no quedan hijos rebeldes, cerramos la orden padre entera
                $this->poModel->updateOrderStatus($po_id, 'Recibido');
            }

            // Todo guay, cerramos la caja y a correr
            $this->poModel->commit();
            return "¡Mercancía recibida e integrada en el stock!";
        } catch (Exception $e) {
            // Si algo explota (ej: la BD peta), deshacemos todo el fregao
            if ($this->poModel->inTransaction()) {
                $this->poModel->rollBack();
            }
            throw new Exception($e->getMessage());
        }
    }
}