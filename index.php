<?php
/*
Controlador frontal: punto de entrada para la aplicación MVC
*/
session_start();


require_once 'config/functions.php'; // Agregado para que las funciones estén disponibles en todo el MVC
require_once 'app/Core/Router.php';

$router = new Router();

// DEFINICIÓN DE RUTAS - ROUTES


// Autenticación
$router->get('', 'AuthController@showLogin');
$router->get('login', 'AuthController@showLogin');
$router->post('login/process', 'AuthController@processLogin');
$router->get('logout', 'AuthController@logout');

// Dashboard
$router->get('dashboard', 'DashboardController@index'); // Ruta /dashboard

// Productos
$router->get('productos', 'ProductController@index'); 
$router->post('productos/save', 'ProductController@save');
$router->post('productos/delete', 'ProductController@delete');
$router->post('productos/upload-image', 'ProductController@uploadImage');

// Stock
$router->get('stock', 'StockController@index');
$router->post('stock/discount', 'StockController@applyDiscount');

// TPV (Point of Sale)
$router->get('pos', 'POSController@index');
$router->post('pos/add', 'POSController@add');
$router->post('pos/clear', 'POSController@clear');
$router->post('pos/checkout', 'POSController@checkout');

// Usuarios
$router->get('users', 'UserController@index');
$router->post('users/save', 'UserController@save');
$router->post('users/delete', 'UserController@delete');

// Recetas
$router->get('recipe', 'RecipeController@index');
$router->post('recipe/save', 'RecipeController@save');
$router->post('recipe/delete', 'RecipeController@delete');

// Órdenes de Compra
$router->get('purchase-orders', 'PurchaseOrderController@index');
$router->post('purchase-orders/create', 'PurchaseOrderController@create');
$router->get('purchase-orders/receive', 'PurchaseOrderController@receiveForm');
$router->post('purchase-orders/receive/process', 'PurchaseOrderController@receiveStock');


// DESPACHO DE LA RUTA
$url = isset($_GET['url']) ? $_GET['url'] : '';
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($url, $method);
