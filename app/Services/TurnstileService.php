<?php
namespace App\Services;

class TurnstileService {
    private $secretKey;
    
    public function __construct() {
        $this->secretKey = TURNSTILE_SECRET_KEY;
    }
    
    public function verifyToken($token, $remoteIP = null) {
        if ($this->isOfflineBypassed()) {
            return ['success' => true];
        }
        
        if (empty($token)) {
            return ['success' => false, 'error' => 'Missing verification token'];
        }
        
        $data = [
            'secret' => $this->secretKey,
            'response' => $token,
        ];
        
        if ($remoteIP) {
            $data['remoteip'] = $remoteIP;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Turnstile CURL Error: $curlError");
            return ['success' => false, 'error' => 'Verification service unavailable'];
        }
        
        if ($httpCode !== 200) {
            error_log("Turnstile HTTP Error: $httpCode");
            return ['success' => false, 'error' => 'Verification service unavailable'];
        }
        
        $result = json_decode($response, true);
        // error_log("Turnstile API Response: " . json_encode($result));
        
        return [
            'success' => $result['success'] ?? false,
            'error' => $result['error-codes'][0] ?? 'Verification failed'
        ];
    }
    
    public function isOfflineBypassed() {
        // Only bypass if explicitly set in config
        if (defined('TURNSTILE_OFFLINE') && TURNSTILE_OFFLINE === true) {
            return true;
        }
        
        // Bypass in development if keys are test keys
        if (APP_ENV === 'development' && 
            (TURNSTILE_SITE_KEY === '1x00000000000000000000AA' || 
             TURNSTILE_SECRET_KEY === '1x0000000000000000000000000000000AA')) {
            return true;
        }
        
        return false;
    }
}