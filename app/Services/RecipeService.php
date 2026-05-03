<?php
require_once 'app/Models/RecipeModel.php';

class RecipeService {
    private $recipeModel;

    public function __construct() {
        $this->recipeModel = new RecipeModel();
    }

    // Lógica para añadir un ingrediente a la receta gestionando duplicados
    public function saveRecipeItem($final_product_id, $ingredient_id, $quantity_needed) {
        // Primera barrera: no permitimos guardar datos vacíos o negativos
        if ($final_product_id <= 0 || $ingredient_id <= 0 || $quantity_needed <= 0) {
            throw new Exception("Datos inválidos para la receta. Revisa las cantidades.");
        }

        try {
            // Comprobamos si este ingrediente ya estaba en la receta previamente
            $existing = $this->recipeModel->findRecipeItem($final_product_id, $ingredient_id);

            if ($existing) {
                // Si ya está, actualizamos sumando la cantidad para no duplicar filas
                $this->recipeModel->updateRecipeQuantity($existing['id'], $quantity_needed);
                return "Cantidad de ingrediente aumentada en la receta.";
            } else {
                // Si no está, lo insertamos como una entrada nueva
                $this->recipeModel->addRecipeItem($final_product_id, $ingredient_id, $quantity_needed);
                return "Ingrediente nuevo añadido a la receta.";
            }
        } catch (PDOException $e) {
            // Si la base de datos falla por algo, lanzamos un error claro para el frontend
            throw new Exception("Error técnico al guardar el ingrediente en la base de datos.");
        }
    }

    // Lógica para quitar un ingrediente
    public function deleteRecipeItem($recipe_id) {
        // Verificamos que el ID tenga sentido antes de intentar borrar
        if ($recipe_id <= 0) {
            throw new Exception("ID de receta inválido.");
        }

        try {
            $this->recipeModel->deleteRecipeItem($recipe_id);
            return "Ingrediente eliminado de la receta correctamente.";
        } catch (PDOException $e) {
            throw new Exception("Error al intentar eliminar el ingrediente.");
        }
    }
}