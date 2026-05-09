<?php
require_once 'app/Models/POSModel.php';
require_once 'app/Services/POSService.php'; 
require_once 'config/functions.php';

class POSController {
    private $posModel;
    private $posService;

    public function __construct() {
        // Instanciamos el modelo y el servicio para tenerlos a mano
        $this->posModel = new POSModel();
        $this->posService = new POSService();
        
        // Arrancamos la sesión si no estaba ya funcionando
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Preparamos el carrito (ticket) en la sesión si está vacío
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function index() {
        // Solo jefes y dependientes pueden entrar a cobrar
        require_role(['admin', 'dependiente']);
        
        // El controlador pide la lista de productos al Modelo para pintarlos en la pantalla
        $productsList = $this->posModel->getFinalProducts();
        
        global $pdo; 
        
        // Cargamos la vista de la caja
        require_once 'app/Views/pos/index.php';
    }

    public function add() {
        // Devolver respuesta JSON para actualización del carrito
        header('Content-Type: application/json');
        require_role(['admin', 'dependiente']);
        // Validar token CSRF
        csrf_check($_POST['csrf_token'] ?? '');

        $prodId = (int)$_POST['product_id'];
        $qtyRequested = (int)$_POST['quantity'];
        
        global $pdo;
        $prodInfo = get_product_data($pdo, $prodId);

        // Validar cantidad solicitada
        if ($qtyRequested <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
            exit;
        } elseif ($qtyRequested > $prodInfo['stock']) {
            echo json_encode(['success' => false, 'message' => "Solo hay {$prodInfo['stock']} uds disponibles"]);
            exit;
        }

        // Separar unidades con oferta y unidades a precio normal
        $productName = sanitize_input($_POST['product_name']);
        $saleKey = $prodId . '_sale';
        $normalKey = $prodId . '_normal';
        $currentSaleQty = isset($_SESSION['cart'][$saleKey]) ? (int) $_SESSION['cart'][$saleKey]['quantity'] : 0;
        $currentNormalQty = isset($_SESSION['cart'][$normalKey]) ? (int) $_SESSION['cart'][$normalKey]['quantity'] : 0;

        $discountedStock = (int) floor((float) ($prodInfo['discounted_stock'] ?? 0));
        $totalStock = (int) floor((float) ($prodInfo['stock'] ?? 0));
        $currentTotalQty = $currentSaleQty + $currentNormalQty;

        if (($currentTotalQty + $qtyRequested) > $totalStock) {
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente al sumar al ticket']);
            exit;
        }

        $saleRoom = max(0, $discountedStock - $currentSaleQty);
        $qtySale = min($qtyRequested, $saleRoom);
        $qtyNormal = $qtyRequested - $qtySale;

        if ($qtySale > 0) {
            if (isset($_SESSION['cart'][$saleKey])) {
                $_SESSION['cart'][$saleKey]['quantity'] += $qtySale;
            } else {
                $_SESSION['cart'][$saleKey] = [
                    'id' => $prodId,
                    'name' => $productName . ' (OFERTA)',
                    'price' => round((float) $prodInfo['price'] * 0.5, 2),
                    'quantity' => $qtySale,
                    'is_sale_item' => true
                ];
            }
        }

        if ($qtyNormal > 0) {
            if (isset($_SESSION['cart'][$normalKey])) {
                $_SESSION['cart'][$normalKey]['quantity'] += $qtyNormal;
            } else {
                $_SESSION['cart'][$normalKey] = [
                    'id' => $prodId,
                    'name' => $productName,
                    'price' => round((float) $prodInfo['price'], 2),
                    'quantity' => $qtyNormal,
                    'is_sale_item' => false
                ];
            }
        }

        // Informar inserción correcta en carrito
        echo json_encode(['success' => true, 'message' => 'Añadido al ticket']);
        exit;
    }

    public function clear() {
        header('Content-Type: application/json');
        require_role(['admin', 'dependiente']);
        csrf_check($_POST['csrf_token'] ?? '');

        // Vaciamos el carrito cargándonos la variable de sesión
        $_SESSION['cart'] = [];
        echo json_encode(['success' => true, 'message' => 'Ticket vaciado']);
        exit;
    }

    public function remove() {
        // Devolver respuesta JSON para eliminación de línea del ticket
        header('Content-Type: application/json');
        require_role(['admin', 'dependiente']);
        // Validar token CSRF
        csrf_check($_POST['csrf_token'] ?? '');

        // Obtener clave de línea en carrito
        $cartKey = $_POST['cart_key'] ?? '';
        if ($cartKey === '' || !isset($_SESSION['cart'][$cartKey])) {
            echo json_encode(['success' => false, 'message' => 'Línea de ticket no válida']);
            exit;
        }

        // Eliminar línea seleccionada del carrito
        unset($_SESSION['cart'][$cartKey]);
        echo json_encode(['success' => true, 'message' => 'Línea eliminada del ticket']);
        exit;
    }

    public function checkout() {
        header('Content-Type: application/json');
        require_role(['admin', 'dependiente']);
        csrf_check($_POST['csrf_token'] ?? '');

        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
            exit;
        }

        // Calculamos cuánto cuesta todo el ticket
        $grandTotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $grandTotal += $item['price'] * $item['quantity'];
        }

        // Validar método de pago recibido
        $paymentMethod = $_POST['payment_method'] ?? '';
        if (!in_array($paymentMethod, ['cash', 'card'], true)) {
            echo json_encode(['success' => false, 'message' => 'Método de pago no válido']);
            exit;
        }

        // Validar importes de pago según método
        if ($paymentMethod === 'cash') {
            $amountPaid = round((float) ($_POST['amount_paid'] ?? 0), 2);
            if ($amountPaid < $grandTotal) {
                echo json_encode(['success' => false, 'message' => 'Importe insuficiente para pago en efectivo']);
                exit;
            }
        } else {
            $amountPaid = $grandTotal;
        }

        try {
            // Delegar proceso de venta al servicio
            $this->posService->processCheckout($_SESSION['cart'], $grandTotal, $paymentMethod, $amountPaid);
            
            // Limpiar carrito al completar venta
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'Venta procesada con éxito']);
        } catch (Exception $e) {
            // Informar error de proceso
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}