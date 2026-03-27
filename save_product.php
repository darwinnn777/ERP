<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 * Controlador para guardar productos y subir imágenes
 */
session_start();
require_once 'db_erp.php';
require_once 'functions.php';
require_role(['admin','obrador']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    //Subir imagen desde el modal
    //UPLOAD IMAGE FROM MODAL
    if(isset($_POST['action']) && $_POST['action']==='upload_image'){
        $product_id=$_POST['id'];
        
        //Carpeta donde están las imágenes de productos
        $directory="img_products/";
        //En caso de que no exista se crea la carpeta
        if(!is_dir($directory)){
            mkdir($directory, 0777,true);
        }
        //Procesar el archivo de imagen
        //Process image files
        $image_name=$_FILES["product_image"]["name"];
        $tmp_name=$_FILES["product_image"]["tmp_name"];
        $extension= pathinfo($image_name, PATHINFO_EXTENSION);
        //Validar extensiones para imagen
        $allowed_extensions=['jpg','jpeg','png','webp'];
        if(!in_array(strtolower($extension), $allowed_extensions)){
            die("Tipo de archivo inválido, solo se permite extensiones como: jpg, png, jpeg, webp");
        }
        
        //Generar ruta única
        //Generate unique path
        $path=$directory . uniqid() . "." . $extension;
        
        //Mover el archivo subido a la carpeta
        //Move upload file to the folder
        if(move_uploaded_file($tmp_name, $path)){
            //Actualizar la base de datos con la nueva ruta.
            //Update database with the new path
            $sql="UPDATE products SET image_url = :image_url WHERE id = :id";
            $stmt=$pdo->prepare($sql);
            if($stmt->execute([
                ':image_url'=> $path,
                ':id'=> $product_id
            ])){
                header("Location: products_management.php?msg=success");
            }else{
                echo "Error al guardar en la base de datos.";
            }
        }else{
            echo "Error al subir el archivo.";
        }
        exit;
    }
        //Registrar o editar producto
        //Register or edit product
    $sku= strtoupper(trim($_POST['sku'] ?? ''));
    $name=trim($_POST['name'] ?? '');
    $type= $_POST['type'] ?? 'Ingredient';
    $unit= $_POST['unit'] ?? 'unit';
    $id= $_POST['id'] ?? null;
    
    if(empty($sku) || empty($name)){
        die("Error: SKU y nombre tiene que tener datos.");
    }
    try{
        if($id){
            //Actualizar producto
            //Update product
            $sql="UPDATE products SET sku=?, name=?, product_type=?, unit_of_measure = ? WHERE id =?";
            $stmt=$pdo->prepare($sql);
            $stmt->execute([$sku,$name,$type,$unit,$id]);
        }else{
            //Insertar nuevo producto
            //Insert new product
            $sql="INSERT INTO products (sku, name, product_type, unit_of_measure,price_sell,price_buy) 
                  VALUES (?,?,?,?,0,0)";
            $stmt=$pdo->prepare($sql);
            $stmt->execute([$sku, $name, $type,$unit]);
        }
        header("Location: products_management.php?msg=ok");
    } catch (PDOException $ex) {
        die("Error en la base de datos: ". $ex->getMessage());
    }
}else{
    header("Location: products_management.php");
    exit;
}
