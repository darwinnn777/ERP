<?php

 //Clase base para todos los Modelos. Maneja la conexión a la base de datos.

class Model {
    protected $db;

    public function __construct() {
        // Aprovechamos tu archivo de conexión existente
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/../../config/db_erp.php';
        }
        $this->db = $pdo;
    }
}
