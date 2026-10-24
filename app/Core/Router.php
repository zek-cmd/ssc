<?php
namespace App\Core;

class Router {
    private $routes = [];
    
    public function __construct($routes) {
        $this->routes = $routes;
    }
    
    public function dispatch($method, $uri) {
        try {
            $uri = parse_url($uri, PHP_URL_PATH);
            
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $scriptDir = dirname($scriptName);
            
            if ($scriptDir !== '/' && $scriptDir !== '\\') {
                $uri = str_replace($scriptDir, '', $uri);
            }
            
            $uri = '/' . trim($uri, '/');
            if (empty($uri) || $uri === '/') {
                $uri = '/';
            }
            
            foreach ($this->routes as $route => $handler) {
                $parts = explode(' ', $route, 2);
                if (count($parts) !== 2) continue;
                
                $routeMethod = $parts[0];
                $routePattern = $parts[1];
                
                if ($method !== $routeMethod) continue;
                
                $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $routePattern);
                $pattern = '#^' . $pattern . '$#';
                
                if (preg_match($pattern, $uri, $matches)) {
                    if (!isset($handler['handler'])) continue;
                    
                    list($controller, $action) = $handler['handler'];
                    $controllerClass = "App\\Controllers\\{$controller}";
                    
                    if (!class_exists($controllerClass)) {
                        throw new \Exception("Controller not found", 500);
                    }
                    
                    $controllerInstance = new $controllerClass();
                    
                    if (!method_exists($controllerInstance, $action)) {
                        throw new \Exception("Method not found", 500);
                    }
                    
                    if (isset($handler['middleware'])) {
                        foreach ((array)$handler['middleware'] as $middleware) {
                            $middlewareClass = "App\\Middleware\\{$middleware}";
                            if (class_exists($middlewareClass)) {
                                (new $middlewareClass())->handle();
                            }
                        }
                    }
                    
                    $params = array_filter($matches, function($key) {
                        return is_string($key);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    call_user_func_array([$controllerInstance, $action], $params);
                    return;
                }
            }
            
            throw new \Exception("Route not found", 404);
            
        } catch (\Exception $e) {
            $code = (int)$e->getCode() ?: 500;
            
            // Ensure valid HTTP status code
            if ($code < 100 || $code > 599) {
                $code = 500;
            }
            
            http_response_code($code);
            
            // Log the actual error for debugging
            error_log("Router Error [{$code}]: {$e->getMessage()} | URI: {$method} {$uri}");
            
            // Show generic error page - never expose details
            self::showError($code);
        }
    }
    
    private static function showError($code) {
        // Clear any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        
        $nonce = \App\Services\SecurityService::getNonce();
        
        // Simple error pages
        switch ($code) {
            case 404:
                echo '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>404 - Page Not Found</title>
                    <style nonce="' . htmlspecialchars($nonce) . '">
                        body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
                        .error { text-align: center; padding: 40px; }
                        h1 { font-size: 72px; margin: 0; color: #666; }
                        p { color: #888; margin: 10px 0 30px; }
                        a { color: #0066cc; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="error">
                        <h1>404</h1>
                        <p>The page you requested could not be found.</p>
                    </div>
                </body>
                </html>';
                break;
                
            default:
                echo '<!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Error</title>
                    <style nonce="' . htmlspecialchars($nonce) . '">
                        body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
                        .error { text-align: center; padding: 40px; }
                        h1 { font-size: 72px; margin: 0; color: #cc0000; }
                        p { color: #666; margin: 10px 0 30px; }
                        a { color: #0066cc; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class="error">
                        <h1>Error</h1>
                        <p>Something went wrong. Please try again later.</p>
                        <a href="' . APP_URL . '">Go Home</a>
                    </div>
                </body>
                </html>';
                break;
        }
        exit;
    }
}