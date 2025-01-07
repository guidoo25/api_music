<?php
class SlimPHP {
    private $routes = [];
    private $basePath = '/api_music'; // Cambia según el prefijo base necesario

    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }
    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }

    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }

    private function getCurrentUri() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Eliminar múltiples slashes y slash final
        $uri = preg_replace('#/+#', '/', $uri);
        return rtrim($uri, '/');
    }

    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getCurrentUri();

        // Debug
        error_log("Method: " . $method);
        error_log("URI: " . $uri);

        // Configurar headers para API
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        // header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, enctype');

        header('Access-Control-Expose-Headers: Content-Disposition');

        // Manejar preflight requests
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        foreach ($this->routes as $route) {
            $routePath = $this->basePath . $route['path'];

            // Debug
            error_log("Checking route: " . $routePath);

            if ($route['method'] === $method && $uri === $routePath) {
                if (is_array($route['callback'])) {
                    $controller = $route['callback'][0];
                    $method = $route['callback'][1];
                    if (method_exists($controller, $method)) {
                        $controller->$method();
                        return;
                    } else {
                        error_log("Método $method no existe en " . get_class($controller));
                    }
                } elseif (is_callable($route['callback'])) {
                    call_user_func($route['callback']);
                    return;
                } elseif (is_string($route['callback'])) {
                    list($controller, $method) = explode('@', $route['callback']);
                    $controllerClass = "App\\Controllers\\{$controller}";
                    if (class_exists($controllerClass)) {
                        $controllerInstance = new $controllerClass();
                        if (method_exists($controllerInstance, $method)) {
                            $controllerInstance->$method();
                            return;
                        } else {
                            error_log("Método $method no encontrado en controlador $controllerClass");
                        }
                    } else {
                        error_log("Controlador $controllerClass no encontrado");
                    }
                }
            }
        }

        // No se encontró la ruta
        header("HTTP/1.0 404 Not Found");
        echo json_encode([
            'error' => 'Route not found',
            'requested_uri' => $uri,
            'requested_method' => $method,
            'available_routes' => array_map(function($route) {
                return [
                    'method' => $route['method'],
                    'path' => $this->basePath . $route['path']
                ];
            }, $this->routes)
        ]);
    }
}
