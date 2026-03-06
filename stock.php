<?php
// 1. Incluimos las funciones de seguridad y la conexión
require_once 'functions.php'; // Cambia la ruta si está en otra carpeta
require_once 'db_erp.php';       // Tu archivo de conexión PDO

// 2. Protegemos la página: Solo pueden entrar Admin, Obrador o Dependiente
require_role(['admin', 'obrador', 'dependiente']);

try {
    // 3. Consulta SQL nivel estudiante: Hacemos JOIN para traer los nombres 
    // y usamos DATEDIFF para saber los días que faltan para caducar.
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
            ORDER BY sl.expiration_date ASC"; // Ordenamos para ver los próximos a caducar primero
            
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
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Control de Stock y Caducidades</h2>
        
        <?php if (has_role('admin')): ?>
            <a href="nuevo_lote.php" class="btn btn-primary">➕ Añadir Nuevo Lote</a>
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
                            // LÓGICA DE COLORES
                            $dias = $lote['dias_restantes'];
                            $clase_color = "";
                            $mensaje_estado = "";

                            if ($dias === null) {
                                // Por si algún producto no tiene fecha de caducidad
                                $clase_color = "";
                                $mensaje_estado = "Sin caducidad";
                            } elseif ($dias <= 3) {
                                // ROJO: Faltan 3 días o menos (o ya está caducado)
                                $clase_color = "table-danger"; 
                                $mensaje_estado = ($dias < 0) ? "¡CADUCADO!" : "Crítico ($dias días)";
                            } elseif ($dias <= 6) {
                                // AMARILLO: Faltan entre 4 y 6 días
                                $clase_color = "table-warning";
                                $mensaje_estado = "Atención ($dias días)";
                            } else {
                                // VERDE: Faltan más de 6 días
                                $clase_color = "table-success";
                                $mensaje_estado = "Correcto ($dias días)";
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
</div>

</body>
</html>