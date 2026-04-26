<?php

require_once __DIR__ . '/../Models/PurchaseOrderModel.php';
require_once __DIR__ . '/../Services/PurchaseOrderService.php';

class PurchaseOrderController {
    private $model;
    private $service;

    public function __construct($pdo) {
        $this->model = new PurchaseOrderModel($pdo);
        $this->service = new PurchaseOrderService($pdo);
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            return;
        }

        header('Content-Type: application/json');

        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'create_order':
                    $this->createOrder();
                    break;
                case 'receive_stock':
                    $this->receiveStock();
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function createOrder() {
        $provider = trim($_POST['provider'] ?? '');
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $price_unit = (float)($_POST['price_unit'] ?? 0);

        if (empty($provider)) {
            throw new Exception("El nombre del proveedor es obligatorio.");
        }
        if ($product_id <= 0) {
            throw new Exception("Debe seleccionar un producto.");
        }
        if ($quantity <= 0) {
            throw new Exception("La cantidad debe ser mayor que cero.");
        }

        $this->service->createOrder($provider, $product_id, $quantity, $price_unit);
        
        echo json_encode(['status' => 'success', 'message' => 'Orden de compra generada correctamente.']);
    }

    private function receiveStock() {
        $po_id = (int)($_POST['po_id'] ?? 0);
        $items = $_POST['items'] ?? [];

        $this->service->receiveStock($po_id, $items);
        
        echo json_encode(['status' => 'success', 'message' => '¡Éxito! La mercancía ha sido integrada en el stock.']);
    }

    // --- GET Methods for the View ---

    public function getOrdersForView() {
        return $this->model->getAllOrders();
    }

    public function getProductsForView() {
        return $this->model->getAllProducts();
    }

    public function getReceiveOrderViewData($poId) {
        $order = $this->model->getOrderById($poId);
        
        if (!$order || $order['status'] === 'Recibido') {
            throw new Exception("Orden no encontrada o ya recibida.");
        }

        $items = $this->model->getPendingItems($poId);

        return [
            'order' => $order,
            'items' => $items
        ];
    }
}
