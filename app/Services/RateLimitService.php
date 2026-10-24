<?php
namespace App\Services;

class RateLimitService {
    private $maxAttempts;
    private $lockoutDuration;
    private $storageDir;
    
    public function __construct($maxAttempts = null, $lockoutDuration = null) {
        $this->maxAttempts = $maxAttempts ?? MAX_LOGIN_ATTEMPTS;
        $this->lockoutDuration = $lockoutDuration ?? LOCKOUT_DURATION;
        $this->storageDir = STORAGE_PATH . '/rate_limits';
        
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }
    
    public function checkRateLimit($identifier) {
        $data = $this->getAttemptData($identifier);
        
        if ($data['count'] >= $this->maxAttempts) {
            $timeSinceLastAttempt = time() - $data['last_attempt'];
            if ($timeSinceLastAttempt < $this->lockoutDuration) {
                return [
                    'is_locked_out' => true,
                    'lockout_end_time' => $data['last_attempt'] + $this->lockoutDuration,
                    'remaining_time' => $this->lockoutDuration - $timeSinceLastAttempt,
                    'remaining_attempts' => 0
                ];
            } else {
                // Lockout period expired, reset
                $this->resetAttempts($identifier);
                return [
                    'is_locked_out' => false,
                    'lockout_end_time' => 0,
                    'remaining_time' => 0,
                    'remaining_attempts' => $this->maxAttempts
                ];
            }
        }
        
        return [
            'is_locked_out' => false,
            'lockout_end_time' => 0,
            'remaining_time' => 0,
            'remaining_attempts' => $this->maxAttempts - $data['count']
        ];
    }
    
    public function incrementAttempts($identifier) {
        $data = $this->getAttemptData($identifier);
        $data['count']++;
        $data['last_attempt'] = time();
        $this->saveAttemptData($identifier, $data);
    }
    
    public function resetAttempts($identifier) {
        $file = $this->getStorageFile($identifier);
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    private function getAttemptData($identifier) {
        $file = $this->getStorageFile($identifier);
        
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data) {
                return $data;
            }
        }
        
        return ['count' => 0, 'last_attempt' => 0];
    }
    
    private function saveAttemptData($identifier, $data) {
        $file = $this->getStorageFile($identifier);
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    private function getStorageFile($identifier) {
        $hash = md5($identifier);
        return $this->storageDir . '/' . $hash . '.json';
    }
    
    public function cleanup() {
        $files = glob($this->storageDir . '/*.json');
        $now = time();
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            
            if ($data && isset($data['last_attempt'])) {
                if (($now - $data['last_attempt']) > $this->lockoutDuration * 2) {
                    unlink($file);
                }
            }
        }
    }
}