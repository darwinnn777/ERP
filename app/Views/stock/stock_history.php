<?php
require_role(['admin', 'obrador', 'dependiente']);
$page_title = 'Stock History - ERP Bakery';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-bakery mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-clock-history"></i>
                <span>Stock History</span>
            </h4>
            <p class="text-muted small mb-0">Consulta de lotes caducados y movimientos históricos de stock.</p>
        </div>
        <a href="stock" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="bi bi-box-seam me-1"></i> Volver a Stock
        </a>
    </div>

    <div class="mb-4">
        <input type="text" id="stock_history_search" class="form-control rounded-pill shadow-sm px-4"
               placeholder="Buscar por producto, lote, almacén o medida..." onkeyup="filterStockHistoryTable()">
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-light py-3 px-4">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-archive text-secondary"></i>
                <div>
                    <span class="fw-bold text-secondary text-uppercase small d-block" style="letter-spacing: 0.05em;">Lotes caducados</span>
                    <span class="text-muted small">Solo lectura. No se permiten acciones directas desde este historial.</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-uppercase fw-bold text-secondary text-center">
                        <th class="px-4 text-start">Producto</th>
                        <th>Almacén</th>
                        <th>Stock</th>
                        <th>Medida</th>
                        <th>Caducidad</th>
                        <th class="text-end px-4">Estado</th>
                    </tr>
                </thead>
                <tbody class="text-secondary">
                    <?php if (empty($inventoryHistory)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted small">
                                No hay lotes caducados en el historial.
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($inventoryHistory as $item): ?>
                    <tr class="text-center stock-history-row">
                        <td class="px-4 text-start product-cell">
                            <div class="fw-bold"><?= sanitize_input($item['product_name']) ?></div>
                            <div class="text-muted small lot-cell">Lote: <?= sanitize_input($item['lot_number']) ?></div>
                        </td>
                        <td class="warehouse-cell"><?= sanitize_input($item['warehouse_name']) ?></td>
                        <td class="fw-bold"><?= sanitize_input((string) $item['quantity']) ?></td>
                        <td class="measure-cell"><?= sanitize_input($item['unit_of_measure'] ?? '') ?></td>
                        <td>
                            <span class="fw-bold">
                                <?= $item['expiration_date'] ? date('d/m/Y', strtotime($item['expiration_date'])) : '--' ?>
                            </span>
                        </td>
                        <td class="text-end px-4">
                            <span class="badge bg-secondary">
                                <i class="bi bi-exclamation-circle me-1"></i> Caducado
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterStockHistoryTable() {
    // Filtrar historial por datos principales del lote
    let filter = document.getElementById('stock_history_search').value.toLowerCase();
    document.querySelectorAll('.stock-history-row').forEach(row => {
        let product = row.querySelector('.product-cell')?.innerText.toLowerCase() || '';
        let lot = row.querySelector('.lot-cell')?.innerText.toLowerCase() || '';
        let warehouse = row.querySelector('.warehouse-cell')?.innerText.toLowerCase() || '';
        let measure = row.querySelector('.measure-cell')?.innerText.toLowerCase() || '';
        row.style.display = (product.includes(filter) || lot.includes(filter) || warehouse.includes(filter) || measure.includes(filter)) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

