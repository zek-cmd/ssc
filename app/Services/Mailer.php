<?php
namespace App\Services;

/**
 * Email Service with multiple providers
 * Tries SendGrid API first, falls back to PHPMailer SMTP
 */
class Mailer {
    private $sendgridApiKey;
    
    public function __construct($sendgridApiKey = null) {
        $this->sendgridApiKey = $sendgridApiKey ?? (defined('SENDGRID_API_KEY') ? SENDGRID_API_KEY : '');
    }
    
    /**
     * Send single email - tries SendGrid API first, falls back to PHPMailer
     */
    public function sendEmail($from, $to, $subject, $bodyHtml, $bodyText = '') {
        // Method 1: SendGrid API
        $result = $this->sendViaSendGrid($from, $to, $subject, $bodyHtml, $bodyText);
        if ($result) {
            error_log("Mailer: Sent via SendGrid to: $to");
            return true;
        }
        
        // Method 2: PHPMailer SMTP fallback
        // $result = $this->sendViaPHPMailer($from, $to, $subject, $bodyHtml, $bodyText);
        // if ($result) {
        //     error_log("Mailer: Sent via PHPMailer fallback to: $to");
        //     return true;
        // }
        
        error_log("Mailer: All sending methods failed for: $to");
        return false;
    }
    
    /**
     * Send multiple personalized emails in parallel using curl_multi
     * Returns array of [queue_id => true/false]
     */
    public function sendParallelEmails($emails) {
        if (empty($this->sendgridApiKey) || empty($emails)) {
            $results = [];
            foreach ($emails as $e) {
                $results[$e['queue_id']] = false;
            }
            return $results;
        }
        
        $fromName = defined('SENDGRID_FROM_NAME') ? SENDGRID_FROM_NAME : SMTP_FROM_NAME;
        $fromEmail = defined('SENDGRID_FROM_EMAIL') ? SENDGRID_FROM_EMAIL : '';
        
        $multiHandle = curl_multi_init();
        $handles = [];
        
        foreach ($emails as $email) {
            $payload = json_encode([
                'personalizations' => [[
                    'to' => [['email' => $email['email']]],
                    'custom_args' => ['queue_id' => (string)$email['queue_id']]
                ]],
                'from' => [
                    'email' => $fromEmail,
                    'name' => $fromName
                ],
                'subject' => $email['subject'],
                'content' => [
                    ['type' => 'text/plain', 'value' => $email['text_body']],
                    ['type' => 'text/html', 'value' => $email['html_body']]
                ]
            ]);
            
            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->sendgridApiKey,
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload)
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);
            
            curl_multi_add_handle($multiHandle, $ch);
            $handles[] = [
                'handle' => $ch,
                'queue_id' => $email['queue_id']
            ];
        }
        
        // Execute all in parallel
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);
        
        // Collect per-handle results
        $results = [];
        foreach ($handles as $h) {
            $httpCode = curl_getinfo($h['handle'], CURLINFO_HTTP_CODE);
            $curlError = curl_error($h['handle']);
            
            $success = !$curlError && ($httpCode === 200 || $httpCode === 202);
            $results[$h['queue_id']] = $success;
            
            if (!$success) {
                error_log("Mailer parallel: Failed for queue_id {$h['queue_id']} - HTTP $httpCode" . ($curlError ? " - $curlError" : ""));
            }
            
            curl_multi_remove_handle($multiHandle, $h['handle']);
            curl_close($h['handle']);
        }
        
        curl_multi_close($multiHandle);
        
        $successCount = count(array_filter($results));
        error_log("Mailer parallel: $successCount sent, " . (count($results) - $successCount) . " failed out of " . count($results));
        
        return $results;
    }
    
    /**
     * Method 1: SendGrid API (single recipient)
     */
    private function sendViaSendGrid($from, $to, $subject, $bodyHtml, $bodyText) {
        if (empty($this->sendgridApiKey)) {
            error_log("Mailer SendGrid: API key not configured, skipping");
            return false;
        }
        
        $fromName = defined('SENDGRID_FROM_NAME') ? SENDGRID_FROM_NAME : SMTP_FROM_NAME;
        $fromEmail = defined('SENDGRID_FROM_EMAIL') ? SENDGRID_FROM_EMAIL : $from;
        
        $payload = json_encode([
            'personalizations' => [[
                'to' => [['email' => $to]]
            ]],
            'from' => [
                'email' => $fromEmail,
                'name' => $fromName
            ],
            'subject' => $subject,
            'content' => [
                ['type' => 'text/plain', 'value' => $bodyText ?: strip_tags($bodyHtml)],
                ['type' => 'text/html', 'value' => $bodyHtml]
            ]
        ]);
        
        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->sendgridApiKey,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Mailer SendGrid - cURL Error: $curlError");
            return false;
        }
        
        if ($httpCode === 202 || $httpCode === 200) {
            return true;
        }
        
        error_log("Mailer SendGrid - HTTP $httpCode: " . substr($response, 0, 300));
        return false;
    }
    
    /**
     * Method 2: PHPMailer SMTP Fallback
     */
    private function sendViaPHPMailer($from, $to, $subject, $bodyHtml, $bodyText) {
        $phpmailerPath = BASE_PATH . '/PHPMailer/PHPMailer.php';
        $smtpPath = BASE_PATH . '/PHPMailer/SMTP.php';
        $exceptionPath = BASE_PATH . '/PHPMailer/Exception.php';
        
        if (!file_exists($phpmailerPath) || !file_exists($smtpPath) || !file_exists($exceptionPath)) {
            error_log("Mailer: PHPMailer files not found, skipping SMTP fallback");
            return false;
        }
        
        try {
            require_once $exceptionPath;
            require_once $phpmailerPath;
            require_once $smtpPath;
            
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($from, SMTP_FROM_NAME);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText ?: strip_tags($bodyHtml);
            $mail->Timeout = 30;
            
            $mail->send();
            return true;
            
        } catch (\Exception $e) {
            error_log("Mailer PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }
}