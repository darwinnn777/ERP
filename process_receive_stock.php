<?php
/**
 * Procesador de Recepción de Stock y Trazabilidad
 * Stock Receipt Processor with Traceability
 * ERP Bakery - 2026
 */

session_start();
require_once 'functions.php';
require_once 'db_erp.php';

// SEGURIDAD: Solo administradores
// SECURITY: Only admin users allowed
require_role(['admin']);

// Validar CSRF
// Validate CSRF token
csrf_check($_POST['csrf_token'] ?? '');

// Obtener datos del formulario
// Get form data
$po_id = (int)($_POST['po_id'] ?? 0);
$items = $_POST['items'] ?? [];

// Validación básica
// Basic validation
if ($po_id <= 0 || empty($items)) {
    header("Location: purchase_orders.php?msg=invalid_data");
    exit;
}

try {
    // Iniciar transacción para asegurar que no haya datos huérfanos
    // Start database transaction
    $pdo->beginTransaction();

    // Obtener almacén por defecto
    // Get default warehouse
    $stmtWh = $pdo->query("SELECT id FROM warehouses ORDER BY id ASC LIMIT 1");
    $warehouse_id = $stmtWh->fetchColumn();

    if (!$warehouse_id) {
        throw new Exception("No hay almacenes definidos");
    }

    foreach ($items as $item) {
        // Casteo de datos para evitar errores de tipo
        // Sanitize and cast values
        $qty_received = (float)($item['qty_received'] ?? 0);
        $item_id      = (int)($item['item_id'] ?? 0);
        $product_id   = (int)($item['product_id'] ?? 0);
        $lot          = trim($item['lot_number'] ?? '');
        $expiry       = $item['expiration_date'] ?? '';

        // Validaciones de negocio
        if ($qty_received <= 0) {
            throw new Exception("Cantidad inválida detectada");
        }

        if (empty($lot) || empty($expiry)) {
            throw new Exception("Lote o fecha de caducidad incompletos");
        }

        // Verificar que el item pertenece a la orden
        $stmtCheck = $pdo->prepare("SELECT quantity FROM purchase_order_items WHERE id = ? AND po_id = ?");
        $stmtCheck->execute([$item_id, $po_id]);
        $expected_qty = $stmtCheck->fetchColumn();

        if ($expected_qty === false) {
            throw new Exception("Item no válido o manipulado");
        }

        if ($qty_received > (float)$expected_qty) {
            throw new Exception("Cantidad mayor a la solicitada");
        }

        // 1. Insertar en stock_lots (TRAZABILIDAD)
        $stmtStock = $pdo->prepare("
            INSERT INTO stock_lots (product_id, warehouse_id, lot_number, expiration_date, quantity) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtStock->execute([$product_id, $warehouse_id, $lot, $expiry, $qty_received]);

        // 2. Marcar el item como recibido
        $stmtUpdate = $pdo->prepare("
            UPDATE purchase_order_items 
            SET received = TRUE 
            WHERE id = ? AND po_id = ? AND received = FALSE
        ");
        $stmtUpdate->execute([$item_id, $po_id]);
    }

    // 3. Comprobar si quedan items pendientes en la orden
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM purchase_order_items WHERE po_id = ? AND received = FALSE");
    $stmtPending->execute([$po_id]);
    $pending = (int)$stmtPending->fetchColumn();

    // Si no hay pendientes, cerrar la orden principal
    if ($pending === 0) {
        $stmtClose = $pdo->prepare("UPDATE purchase_orders SET status = 'Recibido' WHERE id = ?");
        $stmtClose->execute([$po_id]);
    }

    // Si todo ha ido bien, guardamos cambios permanentemente
    $pdo->commit();
    header("Location: purchase_orders.php?msg=received_ok");
    exit;

} catch (Exception $e) {
    // Si algo falla, deshacemos todo lo hecho en esta ejecución
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("ERROR RECEPCION: " . $e->getMessage());
    header("Location: purchase_orders.php?msg=error");
    exit;
}