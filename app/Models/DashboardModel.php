<?php
require_once 'app/Core/Model.php';

class DashboardModel extends Model {
    public function getLowStockAlerts() {
        try {
            // Contamos cuántos lotes de stock están tiritando (menos de 10 unidades)
            $sql = "SELECT COUNT(*) as faltan FROM stock_lots WHERE quantity < 10";
            $stmt = $this->db->query($sql);
            $resultado = $stmt->fetch();
            
            // Devolvemos el número. Si no viene nada, le casamos un 0 y a correr
            return (int)($resultado['faltan'] ?? 0);
            
        } catch (PDOException $e) {
            // Si peta la base de datos por lo que sea, devolvemos 0 para no romper todo el panel
            return 0;
        }
    }
}