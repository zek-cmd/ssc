<?php
namespace App\Core;

class Controller {
    protected function view($view, $data = []) {
        return View::render($view, $data);
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    // ===== STANDARDIZED INPUT VALIDATION =====
    
    protected function getInt($key, $default = 0, $source = 'POST') {
        $value = $default;
        
        if ($source === 'POST') {
            $value = $_POST[$key] ?? $default;
        } elseif ($source === 'GET') {
            $value = $_GET[$key] ?? $default;
        } elseif ($source === 'REQUEST') {
            $value = $_REQUEST[$key] ?? $default;
        }
        
        return (int)$value;
    }
    
    protected function getString($key, $default = '', $source = 'POST') {
        $value = $default;
        
        if ($source === 'POST') {
            $value = $_POST[$key] ?? $default;
        } elseif ($source === 'GET') {
            $value = $_GET[$key] ?? $default;
        } elseif ($source === 'REQUEST') {
            $value = $_REQUEST[$key] ?? $default;
        }
        
        return trim((string)$value);
    }
    
    protected function getPage() {
        return max(1, (int)($_GET['page'] ?? 1));
    }
    
    protected function getLimit($default = 15, $max = 100) {
        return min($max, max(1, (int)($_GET['limit'] ?? $default)));
    }
    
    protected function getSearch() {
        return trim($_GET['search'] ?? '');
    }
    
    // ===== STANDARDIZED JSON RESPONSES =====
    
    protected function jsonSuccess($data = [], $message = null, $statusCode = 200) {
        $response = ['success' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        return $this->json(array_merge($response, $data), $statusCode);
    }
    
    protected function jsonError($message, $statusCode = 400) {
        return $this->json([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
    
    protected function jsonPaginated($data, $total, $page, $limit) {
        return $this->json([
            'success' => true,
            'data' => $data,
            'total' => (int)$total,
            'page' => (int)$page,
            'limit' => (int)$limit,
            'total_pages' => (int)ceil($total / $limit)
        ]);
    }
    
    // ===== BULK OPERATION VALIDATION =====
    
    protected function getBulkIds($maxItems = 100) {
        $ids = $_POST['ids'] ?? '[]';
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        
        if (!is_array($ids) || empty($ids)) {
            return ['success' => false, 'error' => 'No items selected'];
        }
        
        if (count($ids) > $maxItems) {
            return ['success' => false, 'error' => "Maximum {$maxItems} records per request"];
        }
        
        $ids = array_filter(array_map('intval', $ids), function($id) {
            return $id > 0;
        });
        
        if (empty($ids)) {
            return ['success' => false, 'error' => 'Invalid IDs provided'];
        }
        
        return ['success' => true, 'ids' => array_values($ids)];
    }
}