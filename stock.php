<?php
// 1. Incluimos las funciones de seguridad y la conexión
require_once 'functions.php'; 
require_once 'db_erp.php';       

// 2. Protegemos la página
require_role(['admin', 'obrador', 'dependiente']);

try {
    // 3. Consulta SQL
    $sql = "SELECT 
                sl.id, 
                sl.lot_number, 
                sl.quantity, 
                sl.expiration_date,
                p.name AS nombre_producto,
                w.name AS nombre_almacen,
                DATEDIFF(sl.expiration_date, CURDATE()) AS dias_restantes
            FROM stock_lots sl
            JOIN products p ON sl.product_id = p.id
            JOIN warehouses w ON sl.warehouse_id = w.id
            ORDER BY sl.expiration_date ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar el stock: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Stock - ERP Panadería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="estilos.css" rel="stylesheet">

    <style>
        /* Corrección del body para páginas con contenido largo (tablas) */
        body {
            display: block !important; /* Desactiva el centrado del login */
            height: auto !important;   /* Permite hacer scroll */
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
        
        /* Estilo personalizado para la cabecera de la tabla usando tus variables */
        .thead-bakery th {
            background-color: var(--color-bakery) !important;
            color: white !important;
            border-bottom: 2px solid var(--color-bakery-hover) !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-bakery fw-bold">Control de Stock y Caducidades</h2>
        
        <?php if (has_role('admin')): ?>
            <a href="nuevo_lote.php" class="btn btn-bakery fw-bold px-4 py-2">
                ➕ Añadir Nuevo Lote
            </a>
        <?php endif; ?>
    </div>

    <div class="card card-login mb-5">
        <div class="card-body p-0"> <div class="table-responsive">
                <table class="table table-hover text-center align-middle mb-0">
                    <thead class="thead-bakery">
                        <tr>
                            <th class="py-3">Producto</th>
                            <th class="py-3">Nº Lote</th>
                            <th class="py-3">Almacén</th>
                            <th class="py-3">Cantidad</th>
                            <th class="py-3">Fecha Caducidad</th>
                            <th class="py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($lotes) > 0): ?>
                            <?php foreach ($lotes as $lote): ?>
                                
                                <?php 
                                // LÓGICA DE COLORES
                                $dias = $lote['dias_restantes'];
                                $clase_color = "";
                                $mensaje_estado = "";

                                if ($dias === null) {
                                    $clase_color = "";
                                    $mensaje_estado = "Sin caducidad";
                                } elseif ($dias <= 3) {
                                    $clase_color = "table-danger"; 
                                    $mensaje_estado = ($dias < 0) ? "¡CADUCADO!" : "Crítico ($dias días)";
                                } elseif ($dias <= 6) {
                                    $clase_color = "table-warning";
                                    $mensaje_estado = "Atención ($dias días)";
                                } else {
                                    $clase_color = "table-success";
                                    $mensaje_estado = "Correcto ($dias días)";
                                }
                                ?>

                                <tr class="<?= $clase_color ?>">
                                    <td class="fw-bold py-3"><?= htmlspecialchars($lote['nombre_producto']) ?></td>
                                    <td class="py-3"><?= htmlspecialchars($lote['lot_number']) ?></td>
                                    <td class="py-3"><?= htmlspecialchars($lote['nombre_almacen']) ?></td>
                                    <td class="py-3"><?= htmlspecialchars($lote['quantity']) ?> und/kg</td>
                                    <td class="py-3">
                                        <?= $lote['expiration_date'] ? date('d-m-Y', strtotime($lote['expiration_date'])) : '-' ?>
                                    </td>
                                    <td class="fw-bold py-3"><?= $mensaje_estado ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-4 text-muted">No hay lotes registrados en el stock.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>