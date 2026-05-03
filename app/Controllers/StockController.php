<?php
require_once 'app/Models/StockModel.php';
require_once 'config/functions.php';

class StockController {
    private $stockModel;

    public function __construct() {
        // instanciamos el modelo para poder pedirle cositas a la base de datos
        $this->stockModel = new StockModel();
    }

    public function index() {
        require_role(['admin', 'obrador', 'dependiente']);
        
        // Le pedimos al modelo que nos traiga todo lo que hay en  el inventario
        $inventory = $this->stockModel->getInventory();
        
        // Y se lo escupimos a la vista
        require_once 'app/Views/stock/index.php';
    }

    public function applyDiscount() {
        // Vamos a devolver un JSON, que esto va por AJAX (sin recargar la página)
        header('Content-Type: application/json');
        
        // el hachazo del 50% solo lo puede meter el admin
        require_role(['admin']);
        
        // Comprobamos el token de seguridad para ataques CSRF
        csrf_check($_POST['csrf_token'] ?? '');

        // Pillamos el ID del lote que nos llega por POST. Lo forzamos a entero por si las moscas.
        $lotId = (int)($_POST['lot_id'] ?? 0);
        
        if ($lotId > 0) {
            try {
                // Intentamos aplicar la rebajita en la base de datos
                $changed = $this->stockModel->applyDiscount($lotId);
                
                if ($changed) {
                    // Si se ha actualizado algo
                    echo json_encode(['success' => true, 'message' => 'Descuento del 50% aplicado correctamente.']);
                } else {
                    // Si no, es que alguien ya le había dado al botón antes
                    echo json_encode(['success' => false, 'message' => 'El descuento ya estaba aplicado.']);
                }
            } catch (PDOException $e) {
                // Si la base de datos se cae, avisamos sin romper la web entera
                echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
            }
        } else {
            // Si nos mandan un ID raro o un 0
            echo json_encode(['success' => false, 'message' => 'Lote no válido.']);
        }
        exit; 
    }
}