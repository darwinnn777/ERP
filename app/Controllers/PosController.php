<?php

require_once __DIR__ . '/../Services/CheckoutService.php';

class PosController {
    private $pdo;
    private $checkoutService;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->checkoutService = new CheckoutService($pdo);
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            return;
        }

        header('Content-Type: application/json');

        // Handle both standard form POST and raw JSON payload
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? '';

        try {
            switch ($action) {
                case 'add':
                    $this->addToCart($input);
                    break;
                case 'checkout':
                    $this->checkout();
                    break;
                case 'clear':
                    $this->clearCart();
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function addToCart($input) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $prodId = (int)($input['product_id'] ?? 0);
        $qtyRequested = (int)($input['quantity'] ?? 0);
        $productName = $input['product_name'] ?? 'Producto';

        if ($qtyRequested <= 0) {
            throw new Exception('Cantidad inválida');
        }

        // We rely on get_product_data from functions.php which should be loaded by the caller
        $prodInfo = get_product_data($this->pdo, $prodId);

        if ($qtyRequested > $prodInfo['stock']) {
            throw new Exception("Solo hay {$prodInfo['stock']} unidades disponibles");
        }

        $finalPrice = $prodInfo['on_sale'] ? ($prodInfo['price'] * 0.5) : $prodInfo['price'];
        $cartKey = $prodId . '_' . ($prodInfo['on_sale'] ? 'sale' : 'normal');

        if (isset($_SESSION['cart'][$cartKey])) {
            $newQty = $_SESSION['cart'][$cartKey]['quantity'] + $qtyRequested;

            if ($newQty > $prodInfo['stock']) {
                throw new Exception('Stock insuficiente');
            } else {
                $_SESSION['cart'][$cartKey]['quantity'] = $newQty;
            }
        } else {
            $_SESSION['cart'][$cartKey] = [
                'id' => $prodId,
                'name' => htmlspecialchars($productName) . ($prodInfo['on_sale'] ? ' (OFERTA)' : ''),
                'price' => $finalPrice,
                'quantity' => $qtyRequested,
                'is_sale_item' => $prodInfo['on_sale']
            ];
        }

        $this->sendCartResponse('Producto añadido');
    }

    private function checkout() {
        if (empty($_SESSION['cart'])) {
            throw new Exception("El ticket está vacío");
        }

        $this->checkoutService->processCheckout($_SESSION['cart']);
        
        $_SESSION['cart'] = [];

        $this->sendCartResponse('Venta realizada correctamente', true);
    }

    private function clearCart() {
        $_SESSION['cart'] = [];
        $this->sendCartResponse('Ticket vaciado');
    }

    private function sendCartResponse($message, $isSuccess = true) {
        // Calculate totals for UI updates
        $grandTotal = 0;
        $cartItems = [];
        
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $grandTotal += $subtotal;
                $item['subtotal'] = $subtotal;
                $cartItems[] = $item;
            }
        }

        echo json_encode([
            'status' => $isSuccess ? 'success' : 'error',
            'message' => $message,
            'cart' => $cartItems,
            'grandTotal' => $grandTotal
        ]);
    }
}
