<?php
require_once 'app/Models/PurchaseOrderModel.php';
require_once 'app/Services/PurchaseOrderService.php';
require_once 'config/functions.php';

// Este tío es el que recibe los HTTP requests y decide a quién llamar
class PurchaseOrderController {
    private $poModel;
    private $poService;

    public function __construct() {
        $this->poModel = new PurchaseOrderModel();
        $this->poService = new PurchaseOrderService();
    }

    // Ruta por defecto: Carga la página principal
    public function index() {
        // Si no eres admin, ni lo intentes
        require_role(['admin']);
        
        // Pillamos datos para mandárselos a la vista
        $orders = $this->poModel->getOrders();
        $products_list = $this->poModel->getProducts();
        
        // Escupimos el HTML
        require_once 'app/Views/purchase_orders/index.php';
    }

    // Ruta por POST que llama el AJAX para crear pedidos
    public function create() {
        // Le decimos al navegador: "oye, que te voy a mandar un JSON"
        header('Content-Type: application/json');
        require_role(['admin']);
        
        // Comprobamos el token pa evitar que nos manden posts desde fuera
        csrf_check($_POST['csrf_token'] ?? '');

        // Recogemos la basura del POST
        $provider = $_POST['provider'] ?? '';
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (float)($_POST['quantity'] ?? 0);
        $price_unit = (float)($_POST['price_unit'] ?? 0);

        try {
            // Le pasamos el marrón al Service
            $message = $this->poService->processCreateOrder($provider, $product_id, $quantity, $price_unit);
            // Si todo guay, mandamos sonrisas al frontend
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            // Si reventó, chivamos el error al JS para que el SweetAlert llore
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit; // Cortamos el rollo para no imprimir nada más raro
    }

    // Carga la pantallita para recepcionar stock
    public function receiveForm() {
        require_role(['admin']);
        
        // Pillamos la id de la URL (?id=x)
        $po_id = (int)($_GET['id'] ?? 0);
        $order = $this->poModel->getOrderById($po_id);

        // Si la orden no existe, o ya está recibida... te mando de vuelta a casa, crack
        if (!$order || $order['status'] === 'Recibido') {
            header("Location: " . BASE_URL . "purchase-orders");
            exit;
        }

        // Sacamos lo que nos falta por recibir y cargamos la vista
        $items = $this->poModel->getPendingItems($po_id);
        require_once 'app/Views/purchase_orders/receive.php';
    }

    // Ruta por POST donde el AJAX manda los datos de lo que acaba de recibir
    public function receiveStock() {
        header('Content-Type: application/json');
        require_role(['admin']);
        csrf_check($_POST['csrf_token'] ?? '');

        // Arrayacos que vienen del form
        $po_id = (int)($_POST['po_id'] ?? 0);
        $items = $_POST['items'] ?? [];

        try {
            // Se lo damos al Service para que actualice el stock y haga la magia
            $message = $this->poService->processReceiveStock($po_id, $items);
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            // Si nos pillan intentando timar, el Service tira un error y nosotros lo pasamos p'alante
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}