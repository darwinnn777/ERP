<?php

require_once __DIR__ . '/../Models/RecipeModel.php';

class RecipeController {
    private $model;

    public function __construct($pdo) {
        $this->model = new RecipeModel($pdo);
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
                case 'add':
                    $this->addIngredient();
                    break;
                case 'delete':
                    $this->deleteIngredient();
                    break;
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function addIngredient() {
        $final_product_id = (int)($_POST['final_product_id'] ?? 0);
        $ingredient_id = (int)($_POST['ingredient_id'] ?? 0);
        $quantity_needed = (float)($_POST['quantity_needed'] ?? 0);

        if ($final_product_id <= 0 || $ingredient_id <= 0) {
            throw new Exception("Datos de producto inválidos.");
        }

        if ($quantity_needed <= 0) {
            throw new Exception("La cantidad debe ser mayor que cero.");
        }

        // Check if ingredient is already in recipe
        $existing = $this->model->getRecipeItem($final_product_id, $ingredient_id);

        if ($existing) {
            // Update
            $this->model->updateIngredientQuantity($existing['id'], $quantity_needed);
            echo json_encode(['status' => 'success', 'message' => 'Cantidad de ingrediente actualizada.']);
        } else {
            // Insert
            $this->model->addIngredient($final_product_id, $ingredient_id, $quantity_needed);
            echo json_encode(['status' => 'success', 'message' => 'Ingrediente añadido a la receta.']);
        }
    }

    private function deleteIngredient() {
        $recipe_id = (int)($_POST['recipe_id'] ?? 0);
        
        if ($recipe_id <= 0) {
            throw new Exception("ID de receta inválido.");
        }

        $success = $this->model->deleteIngredient($recipe_id);
        
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Ingrediente eliminado de la receta.']);
        } else {
            throw new Exception("No se pudo eliminar el ingrediente.");
        }
    }

    // --- GET Methods for the View ---

    public function getFinalProduct($id) {
        $product = $this->model->getFinalProduct($id);
        if (!$product) {
            throw new Exception("Producto no encontrado o no es un producto final.");
        }
        return $product;
    }

    public function getAvailableIngredients() {
        return $this->model->getAvailableIngredients();
    }

    public function getRecipe($finalProductId) {
        return $this->model->getRecipeByFinalProduct($finalProductId);
    }
}
