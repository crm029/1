<?php
class Router {
    private $routes = [];

    public function add($route, $params = []) {
        $this->routes[$route] = $params;
    }

    public function dispatch() {
        $url = $_GET['url'] ?? '';
        $url = rtrim($url, '/');
        $url = filter_var($url, FILTER_SANITIZE_URL);

        foreach ($this->routes as $route => $params) {
            if (preg_match("#^{$route}$#", $url, $matches)) {
                array_shift($matches);
                $controller = $params['controller'];
                $method = $params['method'] ?? 'index';
                $file = __DIR__ . '/../app/controllers/' . $controller . '.php';
                if (!file_exists($file)) die("Controller not found: {$controller}");
                require_once $file;
                if (!class_exists($controller)) die("Class not found: {$controller}");
                $obj = new $controller();
                if (!method_exists($obj, $method)) die("Method not found: {$method}");
                call_user_func_array([$obj, $method], $matches);
                return;
            }
        }
        http_response_code(404);
        die('404 - صفحه یافت نشد');
    }
}