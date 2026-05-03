<?php
 //Router Class
 //Analiza enlaces y delega al controlador correcto.
class Router {
    private $routes = [];

    public function get($uri, $controllerAction) {
        $this->add('GET', $uri, $controllerAction);
    }

    public function post($uri, $controllerAction) {
        $this->add('POST', $uri, $controllerAction);
    }

    private function add($method, $uri, $controllerAction) {
        $uri = trim($uri, '/');
        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $controllerAction
        ];
    }

    public function dispatch($uri, $method) {
        $uri = trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $route['method'] === $method) {
                // Separamos "Controlador@Metodo"
                list($controller, $action) = explode('@', $route['action']);
                
                $controllerFile = "app/Controllers/{$controller}.php";
                
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    $controllerInstance = new $controller();
                    
                    if (method_exists($controllerInstance, $action)) {
                        // Llamamos al método del controlador
                        $controllerInstance->$action();
                        return;
                    } else {
                        die("Error crítico: El método <b>{$action}</b> no existe en el controlador <b>{$controller}</b>.");
                    }
                } else {
                    die("Error crítico: El controlador <b>{$controller}</b> no existe en app/Controllers/.");
                }
            }
        }

        // Si la ruta no se encuentra en nuestro MVC
        http_response_code(404);
        echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
        echo "<h2>404 - Ruta no encontrada en el MVC</h2>";
        echo "<p>No existe ninguna ruta registrada para: <b>/{$uri}</b></p>";
        echo "</div>";
    }
}
