<?php
session_start();
require_once '../config/db_erp.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check($_POST['csrf_token'] ?? '');

    // Recibimos el lot_id
    $lotId = (int)($_POST['lot_id'] ?? 0);

    if ($lotId > 0) {
        // Actualizamos el LOTE específico en la base de datos
        $stmt = $pdo->prepare("UPDATE public.stock_lots SET is_discounted = TRUE WHERE id = ?");
        $stmt->execute([$lotId]);

        if ($stmt->rowCount() > 0) {
            header("Location: ../pages/stock.php?msg=discount_ok");
        } else {
            header("Location: ../pages/stock.php?msg=no_change");
        }
    } else {
        header("Location: ../pages/stock.php?msg=error");
    }
    exit;
}