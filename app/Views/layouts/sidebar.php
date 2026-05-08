<?php
$_s_user = $_SESSION['full_name'] ?? $_SESSION['usuario'] ?? 'Usuario';
$_s_role = get_user_role();
$_s_uri  = $_SERVER['REQUEST_URI'];

function sidebar_active(string $segment): string {
    global $_s_uri;
    return (strpos($_s_uri, $segment) !== false) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'ERP Bakery' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <base href="<?= BASE_URL ?>">
</head>
<body>

<header class="topbar">
    <div class="d-flex align-items-center gap-3 ps-3">
        <button id="sidebarToggle" class="btn btn-link text-white p-0 fs-4 lh-1" title="Menú">
            <i class="bi bi-list"></i>
        </button>
        <a class="topbar-brand" href="dashboard">
            <img src="assets/img_products/logo.png" alt="Logo" class="navbar-logo">
            ERP Bakery
        </a>
    </div>

    <div class="d-flex align-items-center gap-3 pe-3">
        <div class="text-end d-none d-md-block text-white">
            <div class="fw-semibold" style="font-size:0.875rem"><?= sanitize_input($_s_user) ?></div>
            <span class="badge-rol"><?= strtoupper($_s_role) ?></span>
        </div>
        <div class="topbar-avatar" title="<?= sanitize_input($_s_user) ?>">
            <?= strtoupper(substr($_s_user, 0, 2)) ?>
        </div>
    </div>
</header>

<div id="sidebar">
    <nav class="sidebar-nav">

        <div class="sidebar-section">Principal</div>
        <a href="dashboard" class="sidebar-link <?= sidebar_active('dashboard') ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <?php if (has_role('admin') || has_role('obrador')): ?>
        <div class="sidebar-section">Produccion</div>
        <a href="stock" class="sidebar-link <?= sidebar_active('stock') ?>">
            <i class="bi bi-box-seam-fill"></i> Gestion de Stock
        </a>
        <a href="productos" class="sidebar-link <?= (strpos($_s_uri, 'productos') !== false || strpos($_s_uri, 'recipe') !== false) ? 'active' : '' ?>">
            <i class="bi bi-journal-richtext"></i> Catalogo y Recetas
        </a>
        <?php endif; ?>

        <?php if (has_role('admin') || has_role('dependiente') || has_role('obrador')): ?>
        <div class="sidebar-section">Ventas</div>
        <?php endif; ?>

        <?php if (has_role('admin') || has_role('dependiente')): ?>
        <a href="pos" class="sidebar-link <?= sidebar_active('/pos') ?>">
            <i class="bi bi-shop-window"></i> Punto de Venta
        </a>
        <?php endif; ?>

        <?php if (has_role('admin')): ?>
        <div class="sidebar-section">Administracion</div>
        <a href="users" class="sidebar-link <?= sidebar_active('users') ?>">
            <i class="bi bi-people-fill"></i> Empleados
        </a>
        <a href="purchase-orders" class="sidebar-link <?= sidebar_active('purchase-orders') ?>">
            <i class="bi bi-cart3"></i> Ordenes de Compra
        </a>
        <?php endif; ?>

    </nav>

    <div class="sidebar-footer">
        <div class="mb-2"><?= date('d M Y') ?></div>
        <form action="logout" method="GET" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn-sm btn-outline-light w-100 rounded-pill"
                    onclick="return confirm('¿Cerrar sesión?');">
                <i class="bi bi-box-arrow-left me-1"></i> Cerrar sesion
            </button>
        </form>
    </div>
</div>

<main id="main-content">
