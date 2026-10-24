<?php
namespace App\Core;

class View {
    private static $layout = 'main';
    private static $sections = [];
    private static $currentSection;
    
    public static function render($view, $data = []) {
        // Extract data for view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = APP_PATH . '/Views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View '$view' not found");
        }
        
        require $viewFile;
        
        // Get content
        $content = ob_get_clean();
        
        // If we have a layout, wrap it
        if (self::$layout) {
            $layoutFile = APP_PATH . '/Views/layouts/' . self::$layout . '.php';
            if (file_exists($layoutFile)) {
                ob_start();
                require $layoutFile;
                return ob_get_clean();
            }
        }
        
        return $content;
    }
    
    public static function setLayout($layout) {
        self::$layout = $layout;
    }
    
    public static function error($code, $message = '') {
        http_response_code($code);
        echo "<h1>Error $code</h1>";
        echo "<p>$message</p>";
    }
    
    public static function asset($path) {
        return APP_URL . '/assets/' . $path;
    }
}