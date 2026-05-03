<?php
// Cargamos el modelo y las funciones globales que nos hagan falta
require_once 'app/Models/DashboardModel.php';
require_once 'config/functions.php';

class DashboardController {
    private $dashboardModel;

    public function __construct() {
        // Arrancamos el modelo del dashboard para poder pedirle datos a la BD
        $this->dashboardModel = new DashboardModel();
    }

    public function index() {
        // Filtro de seguridad: solo dejamos pasar a estos roles. Si intenta entrar otro, lo echamos.
        require_role(['admin', 'obrador', 'dependiente']);

        // Guardamos un par de datos en variables para usarlos luego en la vista HTML
        $rol_actual = get_user_role(); // Pillamos el rol
        
        // Intentamos sacar su nombre, si falla usamos el de usuario, y si todo falla le llamamos 'Compañero'
        $nombre_usu = $_SESSION['full_name'] ?? $_SESSION['usuario'] ?? 'Compañero';
        
        // Le pedimos al modelo que nos traiga los productos que se están quedando sin stock
        $alertas_stock = $this->dashboardModel->getLowStockAlerts();

        // Y por último, cargamos la vista principal (que ya podrá usar todas las variables de arriba)
        require_once 'app/Views/dashboard/index.php';
    }
}