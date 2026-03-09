<?php
// logout.php - Script cortito para matar la sesión

// Cargamos el archivo de funciones donde ya programamos el log_out()
require_once 'functions.php';

// Llamamos a la función directamente. 
// Esto vacía el $_SESSION, destruye la sesión en el servidor y nos manda a login.php
log_out();
?>