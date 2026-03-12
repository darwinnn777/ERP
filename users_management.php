<?php
//Siempre iniciar sesion por si no está iniciada
// Always start the session in case it hasn't been started
session_start();
//Utilizar  los archivos de funciones y conexión a BBDD
    require_once 'functions.php';
    require_once 'db_erp.php';
//SECURITY: Restrict access to administrators only
//SEGURIDAD: Restringir el acceso solo a administradores
require_role('admin');

$alert_message='';

    
    
    
?>
<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        
    </body>
</html>
