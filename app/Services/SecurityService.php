<?php
namespace App\Services;

class SecurityService {
    private static $nonce = null;
    private static $nonceLength = 32;
    
    public static function init() {
        self::generateNonce();
        self::setSecurityHeaders();
    }
    
    // CSP & Security Headers
    public static function setSecurityHeaders() {
        $csp = self::getCSP();
        
        $headers = [
            "Content-Security-Policy: " . $csp,
            "X-Frame-Options: DENY",
            "X-Content-Type-Options: nosniff",
            "Referrer-Policy: strict-origin-when-cross-origin",
            "X-XSS-Protection: 0",
            "Permissions-Policy: geolocation=(), microphone=(), camera=()",
        ];
        
        // HSTS for HTTPS
        if (self::isSecureConnection()) {
            $headers[] = "Strict-Transport-Security: max-age=31536000; includeSubDomains; preload";
        }
        
        foreach ($headers as $header) {
            header($header);
        }
        
        header("Content-Type: text/html; charset=utf-8");
    }
    
    private static function getCSP() {
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
        $csp .= "require-trusted-types-for 'script'; ";
        $csp .= "trusted-types default";
        
        return $csp;
    }
    
    // Nonce Management
    private static function generateNonce() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['script_nonce'])) {
                $_SESSION['script_nonce'] = base64_encode(random_bytes(self::$nonceLength));
                $_SESSION['script_nonce_generated'] = time();
            }
            
            // Rotate nonce every hour
            if (isset($_SESSION['script_nonce_generated']) && 
                time() - $_SESSION['script_nonce_generated'] > 3600) {
                $_SESSION['script_nonce'] = base64_encode(random_bytes(self::$nonceLength));
                $_SESSION['script_nonce_generated'] = time();
            }
            
            self::$nonce = $_SESSION['script_nonce'];
        } else {
            self::$nonce = base64_encode(random_bytes(self::$nonceLength));
        }
    }
    
    public static function getNonce() {
        if (!self::$nonce) {
            self::generateNonce();
        }
        return self::$nonce;
    }
    
    // Client IP Detection (Cloudflare aware)
    public static function getClientIP() {
        $trustedProxies = [];
        
        // Cloudflare IPs
        $cloudflareIPs = [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22',
            '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22',
            '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22'
        ];
        
        $trustedProxies = array_merge($trustedProxies, $cloudflareIPs);
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $isTrustedProxy = false;
        foreach ($trustedProxies as $proxy) {
            if (self::ipInRange($remoteAddr, $proxy)) {
                $isTrustedProxy = true;
                break;
            }
        }
        
        // Cloudflare
        if ($isTrustedProxy && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        
        // X-Forwarded-For
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) && $isTrustedProxy) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        
        // X-Real-IP
        if (!empty($_SERVER['HTTP_X_REAL_IP']) && $isTrustedProxy) {
            $ip = trim($_SERVER['HTTP_X_REAL_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        
        if (filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return $remoteAddr;
        }
        
        return '0.0.0.0';
    }
    
    private static function ipInRange($ip, $range) {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        $subnetLong &= $maskLong;
        
        return ($ipLong & $maskLong) == $subnetLong;
    }
    
    // Session Security
    public static function validateSession($namespace) {
        if (!isset($_SESSION[$namespace])) {
            return false;
        }
        
        $sessionData = $_SESSION[$namespace];
        $currentIP = self::getClientIP();
        $sessionIP = $sessionData['ip'] ?? '';
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionUserAgent = $sessionData['user_agent'] ?? '';
        
        // Skip IP check for localhost
        $isLocalhost = in_array($currentIP, ['127.0.0.1', '::1']);
        
        if (!$isLocalhost && $sessionIP && $sessionIP !== $currentIP) {
            return false;
        }
        
        if ($sessionUserAgent && !hash_equals($sessionUserAgent, $currentUserAgent)) {
            return false;
        }
        
        return true;
    }
    
    // Asset URL with cache busting
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
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    }
}