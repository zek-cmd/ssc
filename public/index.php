<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

define('BASE_PATH', dirname(__DIR__));

use App\Services\SessionService;
use App\Services\SecurityService;
use App\Core\Router;

try {
    // Bootstrap already loads phpdotenv
    require_once BASE_PATH . '/bootstrap.php';
    
    // Initialize session
    SessionService::init();
    
    // Initialize security (CSP, headers, nonce)
    SecurityService::init();
    
    // Load routes
    $routesFile = BASE_PATH . '/config/routes.php';
    if (!file_exists($routesFile)) {
        throw new \Exception("Routes file not found: $routesFile");
    }
    
    $routes = require $routesFile;
    
    // Check master site switch
    $pdo = \App\Core\Model::db();
    $stmt = $pdo->query("SELECT enable_site FROM votingswitch LIMIT 1");
    $siteEnabled = $stmt->fetchColumn();
    
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Check if admin is authenticated
    $isAdminAuthenticated = SessionService::isAdminAuthenticated();
    
    // Check if superadmin
    $isSuperAdmin = false;
    if ($isAdminAuthenticated) {
        $adminData = SessionService::getAdminData();
        $isSuperAdmin = ($adminData['user_role'] ?? '') === 'superadmin';
    }
    
    // Allow these paths even when site is DOWN
    $allowedPaths = [
        '/admin',
        '/admin/login',
        '/admin/maintenance',
        '/admin/maintenance/toggle',
        '/admin/logout',
        '/admin/heartbeat'
    ];
    
    // Allow dashboard for authenticated superadmin only
    if ($isAdminAuthenticated && $isSuperAdmin) {
        $allowedPaths[] = '/admin/dashboard';
    }
    
    if (!$siteEnabled && !in_array($requestUri, $allowedPaths)) {
        http_response_code(503);
        $nonce = \App\Services\SecurityService::getNonce();
        $appUrl = APP_URL;
        $csrfToken = SessionService::getCSRFToken();
        $showLogout = $isAdminAuthenticated && !$isSuperAdmin;
        
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>503 - Service Unavailable</title>
            <style nonce="' . htmlspecialchars($nonce) . '">
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: system-ui, -apple-system, sans-serif; 
                    display: flex; 
                    justify-content: center; 
                    align-items: center; 
                    height: 100vh; 
                    margin: 0; 
                    background: #f5f5f5; 
                } 
                .error { 
                    text-align: center; 
                    padding: 40px; 
                    max-width: 400px;
                    width: 90%;
                } 
                h1 { 
                    font-size: 72px; 
                    margin: 0; 
                    color: #cc0000; 
                } 
                p { 
                    color: #666; 
                    margin: 10px 0 30px; 
                    font-size: 16px;
                }
                .logout-form {
                    display: inline-block;
                }
                .logout-btn {
                    display: inline-block;
                    padding: 12px 24px;
                    background: #dc2626;
                    color: white;
                    text-decoration: none;
                    border: none;
                    border-radius: 8px;
                    font-weight: 600;
                    font-size: 14px;
                    cursor: pointer;
                    transition: background 0.3s;
                }
                .logout-btn:hover {
                    background: #b91c1c;
                }
            </style>
        </head>
        <body>
            <div class="error">
                <h1>503</h1>
                <p>Service Unavailable</p>';
        
        if ($showLogout) {
            echo '<form method="POST" action="' . htmlspecialchars($appUrl) . '/admin/logout" class="logout-form">';
            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">';
            echo '<button type="submit" class="logout-btn">Logout</button>';
            echo '</form>';
        }
        
        echo '</div>
        </body>
        </html>';
        exit;
    }
    
    // Dispatch
    $router = new Router($routes);
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    
} catch (\Throwable $e) {
    // Log actual error server-side
    error_log('Index error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return generic error to client
    http_response_code(500);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>500 - Internal Server Error</title>
        <style>
            body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
            .error { text-align: center; padding: 40px; }
            h1 { font-size: 72px; margin: 0; color: #cc0000; }
            p { color: #666; margin: 10px 0 30px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>500</h1>
            <p>Internal Server Error</p>
        </div>
    </body>
    </html>';
}