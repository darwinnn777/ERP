<?php

class RecipeModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getFinalProduct($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM public.products WHERE id = ? AND product_type = 'Final Product'");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAvailableIngredients() {
        $stmt = $this->pdo->query("SELECT id, name, unit_of_measure FROM public.products WHERE product_type = 'Ingredient' ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecipeByFinalProduct($finalProductId) {
        $sql = "SELECT r.id as recipe_id, r.quantity_needed, p.name, p.unit_of_measure 
                FROM public.recipes r
                JOIN public.products p ON r.ingredient_id = p.id 
                WHERE r.final_product_id = ?
                ORDER BY p.name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$finalProductId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecipeItem($finalProductId, $ingredientId) {
        $sql = "SELECT id FROM public.recipes WHERE final_product_id = ? AND ingredient_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$finalProductId, $ingredientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addIngredient($finalProductId, $ingredientId, $quantityNeeded) {
        $sql = "INSERT INTO public.recipes (final_product_id, ingredient_id, quantity_needed) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$finalProductId, $ingredientId, $quantityNeeded]);
        return $this->pdo->lastInsertId();
    }

    public function updateIngredientQuantity($recipeId, $addedQuantity) {
        $sql = "UPDATE public.recipes SET quantity_needed = quantity_needed + ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$addedQuantity, $recipeId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteIngredient($recipeId) {
        $sql = "DELETE FROM public.recipes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$recipeId]);
        return $stmt->rowCount() > 0;
    }
}
