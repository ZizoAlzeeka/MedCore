<?php
/**
 * Router — simple regex-based router
 */
class Router
{
    private $routes = [];
    private $ajaxRoutes = [];

    public function add($method, $pattern, $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $this->compilePattern($pattern),
            'handler' => $handler,
        ];
    }

    public function ajax($method, $pattern, $handler)
    {
        $this->ajaxRoutes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $this->compilePattern($pattern),
            'handler' => $handler,
            'ajax' => true,
        ];
    }

    private function compilePattern($pattern)
    {
        // Convert {param} to named regex groups
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    public function dispatch($method, $uri)
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if (empty($uri)) $uri = '/';

        $allRoutes = array_merge($this->routes, $this->ajaxRoutes);

        foreach ($allRoutes as $route) {
            if ($route['method'] !== strtoupper($method)) continue;
            if (preg_match($route['regex'], $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $val) {
                    if (is_string($key)) $params[$key] = $val;
                }
                $isAjax = !empty($route['ajax']);
                return $this->callHandler($route['handler'], $params, $isAjax);
            }
        }

        // 404
        http_response_code(404);
        view('errors/404', [], 404);
    }

    private function callHandler($handler, $params, $isAjax)
    {
        // Support Closure handlers
        if ($handler instanceof Closure) {
            try {
                $result = call_user_func_array($handler, $params);
                if ($isAjax && is_array($result)) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                return $result;
            } catch (Exception $e) {
                if ($isAjax) {
                    http_response_code(500);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                throw $e;
            }
        }

        // Controller@method handler
        if (!is_string($handler) || strpos($handler, '@') === false) {
            throw new Exception("Invalid route handler: " . (is_string($handler) ? $handler : gettype($handler)));
        }
        list($controllerName, $methodName) = explode('@', $handler, 2);
        if (!class_exists($controllerName)) {
            throw new Exception("Controller class not found: {$controllerName}");
        }
        $controller = new $controllerName();
        if (!method_exists($controller, $methodName)) {
            throw new Exception("Method not found: {$controllerName}@{$methodName}");
        }
        if ($isAjax) {
            // For AJAX routes, return JSON
            try {
                // Pass positional params (values only, not associative keys)
                $positionalParams = array_values($params);
                $result = call_user_func_array([$controller, $methodName], $positionalParams);
                if (is_array($result)) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode($result, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                return $result;
            } catch (Exception $e) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        return call_user_func_array([$controller, $methodName], array_values($params));
    }
}
