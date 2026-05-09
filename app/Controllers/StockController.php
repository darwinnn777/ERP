<?php
require_once 'app/Models/StockModel.php';
require_once 'config/functions.php';

class StockController {
    private $stockModel;

    public function __construct() {
        // Inicializar modelo de stock
        $this->stockModel = new StockModel();
    }

    public function index() {
        require_role(['admin', 'obrador', 'dependiente']);
        
        // Pedir al modelo el inventario completo
        $inventory = $this->stockModel->getInventory();

        $inventoryActive  = [];
        foreach ($inventory as $row) {
            $daysLeft = $row['days_left'];
            $isExpired = $daysLeft !== null && $daysLeft !== '' && (int) $daysLeft < 0;
            if (!$isExpired) {
                $inventoryActive[] = $row;
            }
        }

        // Cargar vista principal de stock
        require_once 'app/Views/stock/index.php';
    }

    public function history() {
        require_role(['admin', 'obrador', 'dependiente']);

        // Pedir al modelo los lotes con caducidad vencida
        $inventory = $this->stockModel->getInventory();

        $inventoryHistory = [];
        foreach ($inventory as $row) {
            $daysLeft = $row['days_left'];
            $isExpired = $daysLeft !== null && $daysLeft !== '' && (int) $daysLeft < 0;
            if ($isExpired) {
                $inventoryHistory[] = $row;
            }
        }

        // Cargar vista de historial de stock
        require_once 'app/Views/stock/stock_history.php';
    }

    public function applyDiscount() {
        // Devolver respuesta JSON para peticiones de descuento
        header('Content-Type: application/json');
        
        // Validar acceso de administrador
        require_role(['admin']);
        
        // Validar token CSRF
        csrf_check($_POST['csrf_token'] ?? '');

        // Obtener identificador de lote desde POST
        $lotId = (int)($_POST['lot_id'] ?? 0);
        
        if ($lotId > 0) {
            try {
                // Aplicar descuento sobre el lote seleccionado
                $changed = $this->stockModel->applyDiscount($lotId);
                
                if ($changed) {
                    // Informar actualización correcta
                    echo json_encode(['success' => true, 'message' => 'Descuento del 50% aplicado correctamente.']);
                } else {
                    // Informar descuento previamente aplicado
                    echo json_encode(['success' => false, 'message' => 'El descuento ya estaba aplicado.']);
                }
            } catch (PDOException $e) {
                // Informar error de base de datos
                echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
            }
        } else {
            // Informar identificador no válido
            echo json_encode(['success' => false, 'message' => 'Lote no válido.']);
        }
        exit; 
    }
}