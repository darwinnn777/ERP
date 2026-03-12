<?php
// Incluir funciones de seguridad y conexión a la base de datos
require_once 'functions.php'; 
require_once 'db_erp.php';

// Proteger la página permitiendo acceso solo a Admin, Obrador o Dependiente
require_role(['admin', 'obrador', 'dependiente']);

try {
    // Realizar consulta SQL adaptada a PostgreSQL
    // Restar fechas directamente en PostgreSQL para calcular días restantes
    $sql = "SELECT 
                sl.id, 
                sl.lot_number, 
                sl.quantity, 
                sl.expiration_date,
                p.name AS nombre_producto,
                w.name AS nombre_almacen,
                (sl.expiration_date - CURRENT_DATE) AS dias_restantes
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
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Control de Stock y Caducidades</h2>
        
        <?php if (has_role('admin')): ?>
            <a href="nuevo_lote.php" class="btn btn-primary">Añadir Nuevo Lote</a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <table class="table table-hover table-bordered text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Producto</th>
                        <th>Nº Lote</th>
                        <th>Almacén</th>
                        <th>Cantidad</th>
                        <th>Fecha Caducidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($lotes) > 0): ?>
                        <?php foreach ($lotes as $lote): ?>
                            
                            <?php 
                            // Aplicar lógica de colores según días restantes
                            $dias = $lote['dias_restantes'];
                            $clase_color = "";
                            $mensaje_estado = "";

                            if ($dias === null) {
                                $clase_color = "";
                                $mensaje_estado = "Sin caducidad";
                            } elseif ($dias <= 3) {
                                // Marcar en rojo cuando faltan tres días o menos
                                $clase_color = "table-danger"; 
                                $mensaje_estado = ($dias < 0) ? "CADUCADO" : "Critico ($dias dias)";
                            } elseif ($dias <= 6) {
                                // Marcar en amarillo cuando faltan entre cuatro y seis días
                                $clase_color = "table-warning";
                                $mensaje_estado = "Atencion ($dias dias)";
                            } else {
                                // Marcar en verde cuando el estado es correcto
                                $clase_color = "table-success";
                                $mensaje_estado = "Correcto ($dias dias)";
                            }
                            ?>

                            <tr class="<?= $clase_color ?>">
                                <td class="fw-bold"><?= htmlspecialchars($lote['nombre_producto']) ?></td>
                                <td><?= htmlspecialchars($lote['lot_number']) ?></td>
                                <td><?= htmlspecialchars($lote['nombre_almacen']) ?></td>
                                <td><?= htmlspecialchars($lote['quantity']) ?> und/kg</td>
                                <td>
                                    <?= $lote['expiration_date'] ? date('d-m-Y', strtotime($lote['expiration_date'])) : '-' ?>
                                </td>
                                <td class="fw-bold"><?= $mensaje_estado ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No hay lotes registrados en el stock.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-3">
        <a href="dashboard.php" class="btn btn-secondary">Volver al Panel</a>
    </div>
</div>

</body>
</html>