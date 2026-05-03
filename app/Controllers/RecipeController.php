<?php
require_once 'app/Models/RecipeModel.php';
require_once 'app/Services/RecipeService.php';
require_once 'config/functions.php';

class RecipeController {
    private $recipeModel;
    private $recipeService;

    public function __construct() {
        // Inicializamos las clases que vamos a usar para acceder a datos y lógica
        $this->recipeModel = new RecipeModel();
        $this->recipeService = new RecipeService();
    }

    // Método principal para cargar la página de la receta
    public function index() {
        // solo dejamos entrar a administradores y obrador
        require_role(['admin', 'obrador']);
        
        // Recogemos el ID del producto final por la URL (GET) de forma segura
        $final_product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        // Si no hay ID o no es válido, redirigimos al catálogo de productos
        if ($final_product_id <= 0) {
            header("Location: " . BASE_URL . "productos");
            exit;
        }

        // Buscamos el producto en la base de datos para asegurarnos de que existe
        $main_product = $this->recipeModel->getFinalProduct($final_product_id);
        if (!$main_product) {
            die("Producto no encontrado o no es un producto final.");
        }

        // Preparamos los datos que necesita la vista: lista de ingredientes y la receta actual
        $ingredients_options = $this->recipeModel->getAvailableIngredients();
        $current_recipe = $this->recipeModel->getRecipeItems($final_product_id);

        // Cargamos la vista pasándole las variables anteriores
        require_once 'app/Views/recipes/index.php';
    }

    // Método para guardar un ingrediente nuevo (o sumar cantidad) vía AJAX
    public function save() {
        // Indicamos que vamos a devolver una respuesta en formato JSON
        header('Content-Type: application/json');
        require_role(['admin', 'obrador']); // Verificamos permisos otra vez
        
        // Comprobación de seguridad para evitar ataques CSRF
        csrf_check($_POST['csrf_token'] ?? '');

        // Recogemos y saneamos los datos que nos llegan por POST desde el formulario
        $final_product_id = (int)($_POST['final_product_id'] ?? 0);
        $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
        $quantity_needed = (float)($_POST['quantity_needed'] ?? 0);

        try {
            // Pasamos los datos al servicio para que haga la lógica de negocio
            $message = $this->recipeService->saveRecipeItem($final_product_id, $ingredient_id, $quantity_needed);
            // Si todo va bien, devolvemos success true y el mensaje
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            // Si el servicio lanza una excepción, devolvemos el error al frontend
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit; // Detenemos la ejecución tras enviar el JSON
    }

    // Método para quitar un ingrediente de la receta vía AJAX
    public function delete() {
        header('Content-Type: application/json');
        require_role(['admin', 'obrador']);
        csrf_check($_POST['csrf_token'] ?? '');

        // Solo necesitamos el ID de la línea de la receta para borrarla
        $recipe_id = (int)($_POST['recipe_id'] ?? 0);

        try {
            // Mandamos borrar y devolvemos confirmación
            $message = $this->recipeService->deleteRecipeItem($recipe_id);
            echo json_encode(['success' => true, 'message' => $message]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}