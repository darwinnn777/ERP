<?php
// 1. Incluimos las funciones de seguridad y la conexión
// Include security functions and connection
require_once 'functions.php'; 
require_once 'db_erp.php';

// 2. Protegemos la página: Solo pueden entrar Admin, Obrador o Dependiente
// Protect the page: Only Admin, Baker or Shop Assistant can enter
require_role(['admin', 'obrador', 'dependiente']);

try {
    // 3. Consulta SQL adaptada a PostgreSQL
    // PostgreSQL no usa DATEDIFF(a, b). Se restan las fechas directamente: (a - CURRENT_DATE)
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
                            // LOGICA DE COLORES
                            $dias = $lote['dias_restantes'];
                            $clase_color = "";
                            $mensaje_estado = "";

                            if ($dias === null) {
                                $clase_color = "";
                                $mensaje_estado = "Sin caducidad";
                            } elseif ($dias <= 3) {
                                // ROJO: Faltan 3 días o menos
                                $clase_color = "table-danger"; 
                                $mensaje_estado = ($dias < 0) ? "CADUCADO" : "Critico ($dias dias)";
                            } elseif ($dias <= 6) {
                                // AMARILLO: Faltan entre 4 y 6 días
                                $clase_color = "table-warning";
                                $mensaje_estado = "Atencion ($dias dias)";
                            } else {
                                // VERDE: Correcto
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