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
        // Avisamos de que vamos a devolver JSON porque esto funciona por AJAX (sin recargar la página)
        header('Content-Type: application/json');
        require_role(['admin', 'dependiente']);
        // Comprobamos el token de seguridad para que no nos cuelen peticiones falsas
        csrf_check($_POST['csrf_token'] ?? '');

        $prodId = (int)$_POST['product_id'];
        $qtyRequested = (int)$_POST['quantity'];
        
        global $pdo;
        $prodInfo = get_product_data($pdo, $prodId);

        // Verificamos que no intenten meter cantidades raras o más de lo que hay en stock
        if ($qtyRequested <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
            exit;
        } elseif ($qtyRequested > $prodInfo['stock']) {
            echo json_encode(['success' => false, 'message' => "Solo hay {$prodInfo['stock']} uds disponibles"]);
            exit;
        }

        // Si está en oferta, le metemos el descuento del 50%. Si no, precio normal.
        $finalPrice = $prodInfo['on_sale'] ? ($prodInfo['price'] * 0.5) : $prodInfo['price'];
        // Creamos una clave única en el carrito (separando si es de oferta o normal)
        $cartKey = $prodId . '_' . ($prodInfo['on_sale'] ? 'sale' : 'normal');

        // Si ya estaba en el ticket, le sumamos la cantidad (comprobando stock otra vez)
        if (isset($_SESSION['cart'][$cartKey])) {
            $newQty = $_SESSION['cart'][$cartKey]['quantity'] + $qtyRequested;
            if ($newQty > $prodInfo['stock']) {
                echo json_encode(['success' => false, 'message' => 'Stock insuficiente al sumar al ticket']);
                exit;
            }
            $_SESSION['cart'][$cartKey]['quantity'] = $newQty;
        } else {
            // Si es nuevo, lo metemos al carrito con todos sus datos
            $_SESSION['cart'][$cartKey] = [
                'id' => $prodId,
                'name' => sanitize_input($_POST['product_name']) . ($prodInfo['on_sale'] ? ' (OFERTA)' : ''),
                'price' => $finalPrice,
                'quantity' => $qtyRequested,
                'is_sale_item' => $prodInfo['on_sale']
            ];
        }

        // Devolvemos OK al AJAX para que la pantalla se actualice
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

        try {
            // Aquí viene lo bueno: el controlador le pasa el marrón (la lógica compleja) al SERVICIO
            $this->posService->processCheckout($_SESSION['cart'], $grandTotal);
            
            // Si todo va bien, vaciamos el carrito y damos el OK
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'Venta procesada con éxito']);
        } catch (Exception $e) {
            // Si peta algo en el servicio (ej. falta stock de repente), mandamos el error para avisar al usuario
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}