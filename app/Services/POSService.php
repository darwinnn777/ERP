<?php
require_once 'app/Models/POSModel.php';


 //Aquí metemos toda la lógica chunga (transacciones y vaciar lotes por caducidad - FIFO).
class POSService {
    private $posModel;

    public function __construct() {
        $this->posModel = new POSModel();
    }

    public function processCheckout($cart, $grandTotal) {
        try {
            // Empezamos la transacción: si algo falla a medias, deshacemos todo para no romper la BD
            $this->posModel->beginTransaction();

            // 1. Creamos la venta general
            $saleId = $this->posModel->createSale($grandTotal);

            // 2. Pasamos por cada producto del ticket
            foreach ($cart as $item) {
                $prodId = $item['id'];
                $toDeduct = $item['quantity'];

                // Comprobamos si de verdad nos queda stock de esto en general
                $realStock = $this->posModel->getRealStock($prodId);
                if ($realStock < $toDeduct) {
                    throw new Exception("Stock insuficiente para {$item['name']}");
                }

                // Guardamos la línea de este producto en el ticket de la BD
                $this->posModel->createSaleItem($saleId, $prodId, $item['quantity'], $item['price']);

                // Lógica FIFO: Si era de oferta, gastamos primero los lotes rebajados
                if ($item['is_sale_item']) {
                    $toDeduct = $this->deductFromLots($prodId, $toDeduct, true, $saleId);
                }

                // Lógica FIFO: Y gastamos también de los lotes normales si hace falta (o si no era oferta)
                if ($toDeduct > 0) {
                    $toDeduct = $this->deductFromLots($prodId, $toDeduct, false, $saleId);
                }

                // Failsafe: Si aún quedan cosas por restar y no hay lotes, algo ha ido muy mal
                if ($toDeduct > 0) {
                     throw new Exception("Inconsistencia de stock. No se pudieron deducir todas las unidades.");
                }
            }

            // Si hemos llegado hasta aquí sin que explote nada, guardamos los cambios de verdad
            $this->posModel->commit();
            return true;

        } catch (Exception $e) {
            // Si ha fallado algo, deshacemos todo lo que llevábamos (rollback) para dejar la BD como estaba
            if ($this->posModel->inTransaction()) {
                $this->posModel->rollBack();
            }
            throw $e; // Y le pasamos el error al controlador
        }
    }


     //Función privada para ir vaciando los lotes uno por uno (FIFO - First In First Out)

    private function deductFromLots($prodId, $toDeduct, $isDiscounted, $saleId) {
        // Pillamos los lotes ordenados por caducidad (del que caduca antes al que caduca después)
        $lots = $this->posModel->getLotsForProduct($prodId, $isDiscounted);
        
        foreach ($lots as $lot) {
            // Si ya hemos restado todo, cortamos el bucle, ya hemos acabado
            if ($toDeduct <= 0) break;

            // Restamos lo que podamos de este lote sin pasarnos
            $take = min($toDeduct, $lot['quantity']);
            
            $updated = $this->posModel->deductLotQuantity($lot['id'], $take);
            if ($updated) {
                // Si se ha restado bien en BD, guardamos el movimiento de salida para el historial
                $this->posModel->registerStockMovement($prodId, $take, $saleId);
                $toDeduct -= $take; // Y actualizamos lo que nos falta por restar
            }
        }
        return $toDeduct;
    }
}