# BAKERP - Sistema ERP para Panaderías y Pastelerías

Este es BAKERP, una aplicación web integral (tipo ERP) orientada a la gestión de panaderías y pastelerías. Lo hemos desarrollado como proyecto final de segundo año del ciclo de Desarrollo de Aplicaciones Web (DAW). 

La idea principal surgió porque muchas panaderías de barrio todavía usan métodos muy manuales para llevar su control de stock y ventas. Quería hacer algo realista, aplicando todo lo que hemos visto en clase sobre arquitectura MVC, bases de datos relacionales y seguridad, logrando trazar el proceso completo: desde la compra de harina hasta la venta de una barra de pan en el mostrador.

## Características Principales

El proyecto está dividido en varios módulos que se conectan entre sí para llevar el control total del negocio:

*   Gestión de Acceso y Roles (RBAC): Hemos implementado un sistema de login propio con control de acceso basado en roles. Tenemos el Administrador (acceso total), el Obrador (gestiona recetas y producción) y el Dependiente (solo TPV). Dependiendo del rol, el middleware bloquea o permite el acceso a ciertas rutas.
*   Catálogo de Productos: Hemos separado los productos en "Materias Primas / Ingredientes" y "Productos Finales / Venta". Esto permite controlar de forma independiente sus SKUs, las unidades de medida (kg, g, ud), y los precios de compra y venta.
*   Gestión de Recetas (Escandallos): Esto me costó un poco, pero conseguí vincular los productos finales con sus ingredientes. Sirve para calcular el coste exacto de hacer un producto y descontar automáticamente la harina o la levadura del almacén cuando se vende o se fabrica algo.
*   Órdenes de Compra (Pedidos a Proveedores): Permite crear pedidos de ingredientes a los proveedores y registrar cuándo llega la mercancía. Al recibirla, el sistema crea automáticamente los lotes de entrada en el almacén.
*   Gestión Avanzada de Inventario y Lotes: Todo el stock se controla mediante lotes y fechas de caducidad. Hemos implementado el método FIFO (First In, First Out) para que al vender o producir, el sistema reste siempre primero del lote más antiguo. También guarda un historial de todos los movimientos.
*   Terminal Punto de Venta (TPV): Es la pantalla para el mostrador. Está pensada para ser muy rápida, maneja el ticket actual, permite seleccionar pago en efectivo o tarjeta, y calcula el cambio automáticamente. Funciona casi todo con peticiones asíncronas para no recargar la página.
*   Dashboard Analítico: Es la pantalla principal donde se ven los KPIs. Muestra alertas de bajo stock, qué productos están a punto de caducar y un resumen de las ventas del día.

## Arquitectura y Tecnologías Usadas

Para este proyecto decidí no tirar de frameworks pesados tipo Laravel, sino construir un patrón MVC (Modelo-Vista-Controlador) desde cero con Vanilla PHP. Quería entender bien las bases del enrutamiento y la separación de lógica.

*   Backend: PHP 8.2 puro.
*   Base de Datos: PostgreSQL. Hemos usado la extensión PDO con consultas preparadas en todas partes para evitar inyecciones SQL.
*   Frontend: HTML5, CSS3 y JavaScript nativo. Para la interfaz me hemos apoyado en Bootstrap 5 (para asegurar el responsive sin perder demasiado tiempo en CSS) y jQuery para las peticiones AJAX del TPV.
*   Alertas: Hemos integrado SweetAlert2 porque los alert() por defecto del navegador quedan muy feos en un proyecto final.
*   Despliegue: Lo hemos dejado preparado para Docker. Hemos incluido un archivo Dockerfile basado en la imagen de php:8.2-apache para que sea fácil de levantar en cualquier máquina.

## Requisitos del Sistema

Para poder ejecutar el proyecto, el servidor necesita cumplir con:
*   PHP versión 8.1 o superior.
*   Extensiones PDO y PDO_PGSQL habilitadas.
*   Servidor web Apache con el módulo mod_rewrite activado (imprescindible para el enrutador que he programado).
*   PostgreSQL versión 13 o superior.

## Instalación y Configuración

Servidor Local (XAMPP / LAMP)
1. Clona el repositorio dentro de la carpeta htdocs de tu XAMPP (por ejemplo en htdocs/ERP).
2. Copia el archivo .env.example, llámalo .env y pon las credenciales de tu base de datos PostgreSQL local:
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_NAME=tu_base_de_datos
   DB_USER=tu_usuario
   DB_PASS=tu_contrasena
3. Asegúrate de que el módulo rewrite de Apache está activado en tu httpd.conf.
4. Importa el esquema SQL que viene en el repositorio a tu PostgreSQL.
5. Entra desde el navegador a http://localhost/ERP.

## Estructura del Proyecto

Hemos organizado el código de la siguiente manera para mantenerlo limpio:

*   /app: Aquí está el núcleo de la aplicación, separado en Controllers, Models y Views.
    *   /Controllers: Reciben las peticiones, validan cosas y llaman a los modelos.
    *   /Models: Todas las consultas a la base de datos PostgreSQL usando PDO.
    *   /Services: Lógica de negocio algo más compleja o transacciones largas (como las ventas del TPV) para no ensuciar los controladores.
    *   /Views: Todo el HTML mezclado con los datos que manda el controlador.
*   /assets: Los archivos estáticos (imágenes, hojas de estilo CSS propias, etc.).
*   /config: Archivos de configuración general y un archivo functions.php con funciones de ayuda que uso por todo el proyecto.
*   index.php: Es el Front Controller. Todas las peticiones del navegador pasan primero por aquí y este archivo decide a qué controlador llamar.
*   .env: Las variables de entorno para que las contraseñas de la base de datos no se suban a GitHub.

## Seguridad

Le hemos dado bastante prioridad a la seguridad porque es algo que los profesores valoran mucho en la evaluación:

*   Tokens CSRF: Hemos programado un sistema de tokens CSRF que se incluye en todos los formularios para evitar que nos cuelen peticiones falsificadas desde otras webs.
*   Sanitización de Datos: Uso htmlspecialchars en todas las salidas por pantalla para evitar ataques XSS (Cross-Site Scripting).
*   Consultas Preparadas: Como ya comenté, todo pasa por PDO con bind param, así que es inmune a la inyección SQL.
*   Protección de Rutas: Una función require_role() comprueba la sesión y el rol del usuario antes de cargar cualquier controlador. Si un dependiente intenta entrar a la vista de usuarios, el sistema lo echa.


