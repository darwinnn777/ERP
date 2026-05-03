<?php
require_once 'app/Core/Model.php';

class RecipeModel extends Model {
    
    // Obtiene los datos del producto final asegurándose de que sea del tipo correcto
    public function getFinalProduct($id) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ? AND product_type = 'Final Product'");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Devuelve todos los ingredientes disponibles para el desplegable, ordenados alfabéticamente
    public function getAvailableIngredients() {
        return $this->db->query("SELECT id, name, unit_of_measure FROM products WHERE product_type = 'Ingredient' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cruza la tabla de recetas con la de productos para ver qué ingredientes y cantidades componen la receta actual
    public function getRecipeItems($final_product_id) {
        $sql = "SELECT r.id as recipe_id, r.quantity_needed, p.name, p.unit_of_measure 
                FROM recipes r
                JOIN products p ON r.ingredient_id = p.id 
                WHERE r.final_product_id = ?
                ORDER BY p.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$final_product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve array con todos los ingredientes encontrados
    }

    // Busca si un ingrediente específico ya está añadido en la receta del producto
    public function findRecipeItem($final_product_id, $ingredient_id) {
        $stmt = $this->db->prepare("SELECT id FROM recipes WHERE final_product_id = ? AND ingredient_id = ?");
        $stmt->execute([$final_product_id, $ingredient_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Si el ingrediente ya existía en la receta, le suma la nueva cantidad introducida
    public function updateRecipeQuantity($recipe_id, $quantity_needed) {
        $stmt = $this->db->prepare("UPDATE recipes SET quantity_needed = quantity_needed + ? WHERE id = ?");
        return $stmt->execute([$quantity_needed, $recipe_id]);
    }

    // Si es un ingrediente nuevo para esta receta, crea el registro desde cero
    public function addRecipeItem($final_product_id, $ingredient_id, $quantity_needed) {
        $stmt = $this->db->prepare("INSERT INTO recipes (final_product_id, ingredient_id, quantity_needed) VALUES (?, ?, ?)");
        return $stmt->execute([$final_product_id, $ingredient_id, $quantity_needed]);
    }

    // Elimina una fila concreta de la receta (quita el ingrediente)
    public function deleteRecipeItem($recipe_id) {
        $stmt = $this->db->prepare("DELETE FROM recipes WHERE id = ?");
        return $stmt->execute([$recipe_id]);
    }
}