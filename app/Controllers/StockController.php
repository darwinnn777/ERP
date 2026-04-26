<?php

require_once __DIR__ . '/../Models/StockModel.php';

class StockController {
    private $model;

    public function __construct($pdo) {
        // Instantiate the worker (Model)
        $this->model = new StockModel($pdo);
    }

    // Handles AJAX POST requests
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            return;
        }

        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? '';

        try {
            if ($action === 'apply_discount') {
                $this->applyDiscount($input);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function applyDiscount($input) {
        $lotId = (int)($input['lot_id'] ?? 0);

        // Input Validation
        if ($lotId <= 0) {
            throw new Exception("Lote inválido");
        }

        // Ask the Model to update the database
        $success = $this->model->applyDiscount($lotId);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Descuento del 50% aplicado correctamente.']);
        } else {
            // Throw exception to trigger the error response automatically
            throw new Exception("El descuento ya estaba aplicado o el lote no existe.");
        }
    }

    // Prepare data for the View (GET request)
    public function getInventoryForView() {
        // 1. Get raw data from the Model
        $inventory = $this->model->getAllStock();

        // 2. Apply Business Logic: Calculate colors and status badges
        foreach ($inventory as &$item) {
            $days = $item['days_left'];
            $item['bg_class'] = "";
            $item['status_html'] = "";

            if ($days !== null) {
                if ($days <= 3) {
                    $item['bg_class'] = "table-danger"; // Red
                } elseif ($days >= 4 && $days <= 7) {
                    $item['bg_class'] = "table-warning"; // Yellow
                } else {
                    $item['bg_class'] = "table-success opacity-75"; // Green
                }
            }

            // Status Badge HTML
            if ($days === null) {
                $item['status_html'] = "<span class='text-muted small'>Perenne</span>";
            } elseif ($days < 0) {
                $item['status_html'] = "<span class='text-danger fw-bold small'>CADUCADO</span>";
            } else {
                $item['status_html'] = "<span class='badge bg-white text-dark border'>$days días</span>";
            }
        }

        return $inventory;
    }
}
