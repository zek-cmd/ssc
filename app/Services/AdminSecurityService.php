<?php
namespace App\Services;

class AdminSecurityService {
    private static $nonce = null;
    
    public static function init() {
        \App\Services\SessionService::refreshAdminSession();
        self::generateNonce();
        self::setAdminSecurityHeaders();
    }
    
    public static function setAdminSecurityHeaders() {
        $csp = self::getAdminCSP();
        
        $headers = [
            "Content-Security-Policy: " . $csp,
            "X-Frame-Options: DENY",
            "X-Content-Type-Options: nosniff",
            "Referrer-Policy: strict-origin-when-cross-origin",
            "Permissions-Policy: geolocation=(), microphone=(), camera=()",
            "X-XSS-Protection: 0",
        ];
        
        if (self::isSecureConnection()) {
            $headers[] = "Strict-Transport-Security: max-age=31536000; includeSubDomains; preload";
        }
        
        foreach ($headers as $header) {
            header($header);
        }
        
        header("Content-Type: text/html; charset=utf-8");
    }
    
    private static function getAdminCSP() {
        $nonce = self::getNonce();
        
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'nonce-{$nonce}' https://challenges.cloudflare.com; ";
        $csp .= "style-src 'self' 'unsafe-inline' 'nonce-{$nonce}'; ";
        $csp .= "style-src-attr 'self' 'unsafe-inline'; ";
        $csp .= "font-src 'self'; ";
        $csp .= "img-src 'self' data: blob: https:; ";
        $csp .= "connect-src 'self' https://challenges.cloudflare.com; ";
        $csp .= "frame-src https://challenges.cloudflare.com; ";
        $csp .= "object-src 'none'; ";
        $csp .= "base-uri 'self'; ";
        $csp .= "form-action 'self'; ";
        $csp .= "frame-ancestors 'none'; ";
        $csp .= "upgrade-insecure-requests; ";
        $csp .= "block-all-mixed-content; ";
        
        return $csp;
    }
    
    public static function generateNonce() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['admin_nonce'])) {
                $_SESSION['admin_nonce'] = base64_encode(random_bytes(32));
                $_SESSION['admin_nonce_time'] = time();
            }
            
            if (isset($_SESSION['admin_nonce_time']) && time() - $_SESSION['admin_nonce_time'] > 3600) {
                $_SESSION['admin_nonce'] = base64_encode(random_bytes(32));
                $_SESSION['admin_nonce_time'] = time();
            }
            
            self::$nonce = $_SESSION['admin_nonce'];
        } else {
            self::$nonce = base64_encode(random_bytes(32));
        }
    }
    
    public static function getNonce() {
        if (!self::$nonce) {
            self::generateNonce();
        }
        return self::$nonce;
    }
    
    public static function getClientIP() {
        return SecurityService::getClientIP();
    }
    
    public static function asset($path) {
        $relativePath = ltrim($path, '/');
        if (strpos($relativePath, '..') !== false || strpos($relativePath, './') === 0) {
            return APP_URL . '/';
        }
        $fullPath = PUBLIC_PATH . '/assets/' . $relativePath;
        if (!file_exists($fullPath)) {
            return APP_URL . '/assets/' . $relativePath;
        }
        $mtime = filemtime($fullPath);
        return APP_URL . '/assets/' . $relativePath . '?v=' . $mtime;
    }
    
    private static function isSecureConnection() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}