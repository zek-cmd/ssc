<?php
define('BASE_DIR', __DIR__);
define('ASSETS_DIR', BASE_DIR . '/public/assets/local');
define('CSS_DIR', ASSETS_DIR . '/css');
define('JS_DIR', ASSETS_DIR . '/js');
define('FONTS_DIR', ASSETS_DIR . '/fonts');
define('MAX_RETRIES', 3);
define('RETRY_DELAY_SECONDS', 2);

$assets = [
    'tailwind' => [
        'url' => '',
        'path' => CSS_DIR . '/tailwind.min.css',
        'integrity' => '',
        'type' => 'css',
        'description' => 'Tailwind CSS v4 (Built Locally via CLI)',
        'retry_count' => 0,
        'success' => true
    ],
    'chartjs' => [
        'url' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        'path' => JS_DIR . '/chart.umd.min.js',
        'integrity' => 'sha384-eOKzE4UxLPQ3yxE10Zk7aJzdF3PJc+Qa55AYJdKoWmdFENJ9Soe6FARJUKvG4VBa',
        'type' => 'js',
        'description' => 'Chart.js Library (for results page)',
        'retry_count' => 0,
        'success' => false
    ],
    'chartjs_alt' => [
        'url' => 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js',
        'path' => JS_DIR . '/chart.umd.cdnjs.min.js',
        'type' => 'js',
        'description' => 'Chart.js from CDNJS (backup)',
        'retry_count' => 0,
        'success' => false
    ],
    'inter_font_css' => [
        'url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
        'path' => CSS_DIR . '/inter-font.css',
        'type' => 'css',
        'description' => 'Inter Font CSS',
        'retry_count' => 0,
        'success' => false
    ],
    'sweetalert2' => [
        'url' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js',
        'path' => JS_DIR . '/sweetalert2-11.min.js',
        'integrity' => 'sha384-7O5j5pKgZezw3O4v6dQJTG0wO+CJ1Hrby8L7gpLwVdUjeukLbR+7rRcJjaiU74r5',
        'type' => 'js',
        'description' => 'SweetAlert2 Library',
        'retry_count' => 0,
        'success' => false
    ],
    'sweetalert2_css' => [
        'url' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
        'path' => CSS_DIR . '/sweetalert2-11.min.css',
        'type' => 'css',
        'description' => 'SweetAlert2 CSS',
        'retry_count' => 0,
        'success' => false
    ],
    'sortablejs' => [
        'url' => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js',
        'path' => JS_DIR . '/Sortable.min.js',
        'integrity' => 'sha384-yS8vYDmAdM4B2EIKcpvqkdGYgqZHBZ6X9Zbzs6w1SxrRlHcgGMqT29kS/VV1qNv5',
        'type' => 'js',
        'description' => 'SortableJS Library (CSP-compatible drag-and-drop)',
        'retry_count' => 0,
        'success' => false
    ]
];

class AssetDownloader {
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

    public function createDirectories() {
        $dirs = [ASSETS_DIR, CSS_DIR, JS_DIR, FONTS_DIR];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    echo "✓ Created directory: $dir\n";
                } else {
                    echo "✗ Failed to create directory: $dir\n";
                }
            } else {
                echo "✓ Directory exists: $dir\n";
            }
        }
        echo str_repeat('-', 60) . "\n";
    }

    public function downloadAsset($name, &$config) {
        if ($config['success']) {
            echo "\n⏭️  Skipping: {$config['description']} (already marked as success)\n";
            return ['success' => true, 'size' => 0, 'integrity' => '', 'path' => $config['path'], 'attempts' => 0];
        }
        if (empty($config['url'])) {
            echo "\n⏭️  Skipping: {$config['description']} (no URL provided)\n";
            return ['success' => false, 'error' => 'No URL provided', 'attempts' => 0];
        }
        $result = null;
        while ($config['retry_count'] < MAX_RETRIES && !$config['success']) {
            echo "\n";
            if ($config['retry_count'] > 0) {
                echo "🔄 Retry #{$config['retry_count']} for: {$config['description']}\n";
            } else {
                echo "📥 Downloading: {$config['description']}\n";
            }
            echo "Source: {$config['url']}\n";
            echo "Target: {$config['path']}\n";
            $result = $this->attemptDownload($config);
            if ($result['success']) {
                $config['success'] = true;
                break;
            } else {
                $config['retry_count']++;
                if ($config['retry_count'] < MAX_RETRIES) {
                    echo "⏳ Waiting " . RETRY_DELAY_SECONDS . " seconds before retry...\n";
                    sleep(RETRY_DELAY_SECONDS);
                }
            }
        }
        return $result;
    }

    private function attemptDownload($config) {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $config['url'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_ENCODING => '',
            ]);
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            curl_close($ch);
            if ($httpCode !== 200) {
                throw new Exception("HTTP $httpCode: $error (Time: {$totalTime}s)");
            }
            if (empty($content)) {
                throw new Exception("Empty response (Time: {$totalTime}s)");
            }
            $bytes = file_put_contents($config['path'], $content);
            if ($bytes === false) {
                throw new Exception("Failed to write file to disk");
            }
            $hash = hash('sha384', $content, true);
            $integrity = 'sha384-' . base64_encode($hash);
            echo "✓ Downloaded: " . number_format(strlen($content) / 1024, 2) . " KB\n";
            echo "✓ Saved to: {$config['path']}\n";
            echo "🔐 Integrity hash: $integrity\n";
            if (strpos($config['description'], 'Inter Font') !== false) {
                $this->extractFontUrls($content);
            }
            return [
                'success' => true,
                'size' => strlen($content),
                'integrity' => $integrity,
                'path' => $config['path'],
                'attempts' => $config['retry_count'] + 1
            ];
        } catch (Exception $e) {
            echo "✗ Failed: " . $e->getMessage() . "\n";
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'attempts' => $config['retry_count'] + 1
            ];
        }
    }

    private function extractFontUrls($cssContent) {
        echo "\n🔍 Extracting font URLs from Inter CSS...\n";
        preg_match_all('/url\((https:\/\/[^)]+\.(woff2|woff|ttf))\)/i', $cssContent, $matches);
        if (!empty($matches[1])) {
            $fontUrls = array_unique($matches[1]);
            $fontCount = count($fontUrls);
            echo "Found $fontCount font file(s):\n";
            $fontConfig = [
                'extracted_at' => date('Y-m-d H:i:s'),
                'font_urls' => $fontUrls
            ];
            file_put_contents(FONTS_DIR . '/font-urls.json', json_encode($fontConfig, JSON_PRETTY_PRINT));
            foreach ($fontUrls as $index => $url) {
                echo " " . ($index + 1) . ". $url\n";
            }
            echo "\n💡 Tip: To download fonts, run: php download_fonts.php\n";
        } else {
            echo "No font URLs found in CSS.\n";
        }
    }

    public function downloadAllAssets(&$assets) {
        $results = [];
        $totalAssets = count($assets);
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "STARTING BATCH DOWNLOAD WITH RETRY MECHANISM\n";
        echo "Maximum retries: " . MAX_RETRIES . "\n";
        echo "Retry delay: " . RETRY_DELAY_SECONDS . " seconds\n";
        echo str_repeat('=', 60) . "\n";
        foreach ($assets as $name => &$config) {
            $results[$name] = $this->downloadAsset($name, $config);
        }
        return $results;
    }

    public function checkAllDownloads($assets) {
        $successCount = 0;
        $failedCount = 0;
        $failedAssets = [];
        foreach ($assets as $name => $config) {
            if ($config['success']) {
                $successCount++;
            } else {
                $failedCount++;
                $failedAssets[] = [
                    'name' => $name,
                    'description' => $config['description'],
                    'url' => $config['url'],
                    'attempts' => $config['retry_count']
                ];
            }
        }
        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'failed_assets' => $failedAssets,
            'total' => count($assets)
        ];
    }

    public function createAssetHelper($assets, $results) {
        $helperPath = BASE_DIR . '/config/local_assets.php';
        $phpCode = "<?php\n";
        $phpCode .= "/**\n";
        $phpCode .= " * Local Asset Paths\n";
        $phpCode .= " * Auto-generated by download_assets.php on " . date('Y-m-d H:i:s') . "\n";
        $phpCode .= " */\n\n";
        foreach ($assets as $name => $config) {
            if ($config['success'] && isset($results[$name])) {
                $relativePath = str_replace(BASE_DIR . '/', '', $config['path']);
                $varName = strtoupper(preg_replace('/[^a-z0-9]/i', '_', $name));
                $integrity = isset($results[$name]['integrity']) ? $results[$name]['integrity'] : '';
                $phpCode .= "// {$config['description']}\n";
                $phpCode .= "define('LOCAL_{$varName}_PATH', '{$relativePath}');\n";
                $phpCode .= "define('LOCAL_{$varName}_INTEGRITY', '{$integrity}');\n\n";
            }
        }
        $phpCode .= "/**\n";
        $phpCode .= " * Get local asset URL\n";
        $phpCode .= " */\n";
        $phpCode .= "function local_asset(\$path) {\n";
        $phpCode .= "    return rtrim(\$_SERVER['REQUEST_SCHEME'] . '://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']), '/') . '/' . ltrim(\$path, '/');\n";
        $phpCode .= "}\n";
        if (file_put_contents($helperPath, $phpCode)) {
            echo "\n📝 Created asset helper: $helperPath\n";
        } else {
            echo "\n✗ Failed to create asset helper\n";
        }
    }

    public function createHtaccess() {
        $htaccessPath = ASSETS_DIR . '/.htaccess';
        $htaccessContent = <<<HTACCESS
# Local Assets Directory
<IfModule mod_headers.c>
    <FilesMatch "\.(css)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    <FilesMatch "\.(js)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
    <FilesMatch "\.(woff2|woff|ttf)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
        Header set Access-Control-Allow-Origin "*"
    </FilesMatch>
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE font/woff2
    AddOutputFilterByType DEFLATE font/woff
    AddOutputFilterByType DEFLATE font/ttf
</IfModule>
HTACCESS;
        if (file_put_contents($htaccessPath, $htaccessContent)) {
            echo "\n🔒 Created .htaccess for caching: $htaccessPath\n";
        }
    }

    public function generateReport($assets, $results) {
        $reportPath = BASE_DIR . '/asset-download-report.txt';
        $report = "ASSET DOWNLOAD REPORT WITH RETRY MECHANISM\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= "Max retries: " . MAX_RETRIES . "\n";
        $report .= "Retry delay: " . RETRY_DELAY_SECONDS . " seconds\n";
        $report .= str_repeat('=', 80) . "\n\n";
        $totalSize = 0;
        $successCount = 0;
        $failCount = 0;
        $totalAttempts = 0;
        foreach ($assets as $name => $config) {
            if ($config['success']) {
                $successCount++;
                $totalAttempts += $config['retry_count'] + 1;
                if (isset($results[$name]['size'])) {
                    $totalSize += $results[$name]['size'];
                }
                $report .= "✓ SUCCESS: {$config['description']}\n";
                $report .= "  Attempts: " . ($config['retry_count'] + 1) . "\n";
                $report .= "  Size: " . (isset($results[$name]['size']) ? number_format($results[$name]['size'] / 1024, 2) . " KB" : "N/A") . "\n";
                $report .= "  Path: {$config['path']}\n";
                $report .= "  Integrity: " . (isset($results[$name]['integrity']) ? $results[$name]['integrity'] : "N/A") . "\n";
            } else {
                $failCount++;
                $report .= "✗ FAILED: {$config['description']}\n";
                $report .= "  Attempts: " . ($config['retry_count'] + 1) . "\n";
                $report .= "  URL: {$config['url']}\n";
                $report .= "  Error: " . (isset($results[$name]['error']) ? $results[$name]['error'] : "Unknown error") . "\n";
            }
            $report .= "\n";
        }
        $report .= str_repeat('-', 80) . "\n";
        $report .= "SUMMARY:\n";
        $report .= "Total assets: " . count($assets) . "\n";
        $report .= "Successful: $successCount\n";
        $report .= "Failed: $failCount\n";
        $report .= "Success rate: " . round(($successCount / count($assets)) * 100, 1) . "%\n";
        $report .= "Total attempts: $totalAttempts\n";
        $report .= "Total size: " . number_format($totalSize / 1024, 2) . " KB\n";
        $report .= "\n";
        if ($failCount > 0) {
            $report .= "FAILED ASSETS:\n";
            $report .= str_repeat('-', 40) . "\n";
            foreach ($assets as $name => $config) {
                if (!$config['success']) {
                    $report .= "- {$config['description']}\n";
                    $report .= "  URL: {$config['url']}\n";
                    $report .= "  Attempts: " . ($config['retry_count'] + 1) . "\n";
                    $report .= "\n";
                }
            }
        }
        file_put_contents($reportPath, $report);
        echo "\n📊 Report generated: $reportPath\n";
    }
}

echo "========================================\n";
echo "CDN Asset Localization Script\n";
echo "WITH RETRY MECHANISM\n";
echo "SOFTNET SSC Election System\n";
echo "========================================\n\n";

$downloader = new AssetDownloader();

echo "📁 Setting up directory structure...\n";
$downloader->createDirectories();

$results = $downloader->downloadAllAssets($assets);

$status = $downloader->checkAllDownloads($assets);
echo "\n" . str_repeat('=', 60) . "\n";
echo "DOWNLOAD SUMMARY:\n";
echo "Total assets: {$status['total']}\n";
echo "Successful: {$status['success_count']}\n";
echo "Failed: {$status['failed_count']}\n";
echo "Success rate: " . round(($status['success_count'] / $status['total']) * 100, 1) . "%\n";

if ($status['failed_count'] > 0) {
    $realFailures = array_filter($status['failed_assets'], function($f) {
        return !empty($f['url']);
    });
    if (!empty($realFailures)) {
        echo "\n⚠️  WARNING: Some assets failed to download:\n";
        foreach ($realFailures as $failed) {
            echo "  - {$failed['description']} (Attempts: {$failed['attempts']})\n";
        }
        echo "\n🔁 Do you want to manually retry failed downloads? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $answer = trim(fgets($handle));
        fclose($handle);
        if (strtolower($answer) === 'y') {
            echo "\nRetrying failed downloads...\n";
            foreach ($realFailures as $failed) {
                $name = $failed['name'];
                if (isset($assets[$name])) {
                    echo "\nRetrying: {$assets[$name]['description']}\n";
                    $results[$name] = $downloader->downloadAsset($name, $assets[$name]);
                }
            }
            $status = $downloader->checkAllDownloads($assets);
            echo "\nUpdated Status:\n";
            echo "Successful: {$status['success_count']}\n";
            echo "Failed: {$status['failed_count']}\n";
        }
    }
}

echo "\n" . str_repeat('=', 60) . "\n";

$downloader->createHtaccess();
$downloader->createAssetHelper($assets, $results);
$downloader->generateReport($assets, $results);

echo "\n🎯 NEXT STEPS:\n";
echo "1. Build Tailwind CSS locally:\n";
echo "   ./tailwindcss -i assets/local/css/tailwind-input.css -o assets/local/css/tailwind.min.css --minify\n";
echo "\n2. Test the application\n";
echo "\n3. Optional: Download font files:\n";
echo "   - Check fonts/font-urls.json for URLs\n";
echo "   - Run: php download_fonts.php\n";

if ($status['failed_count'] > 0) {
    $realFailures = array_filter($status['failed_assets'], function($f) {
        return !empty($f['url']);
    });
    if (!empty($realFailures)) {
        echo "\n⚠️  IMPORTANT: Some assets are still missing!\n";
        echo "Consider:\n";
        echo "  - Checking your internet connection\n";
        echo "  - Verifying the CDN URLs are correct\n";
        echo "  - Manually downloading the missing files\n";
    }
}

echo "\n✅ Script completed!\n";