<?php
/**
 * Font Downloader Script
 * Downloads font files extracted from Google Fonts CSS and localizes references
 * 
 * Usage: php download_fonts.php
 */

// Configuration
define('BASE_DIR', __DIR__);
define('CSS_DIR', BASE_DIR . '/public/assets/local/css');
define('FONTS_DIR', BASE_DIR . '/public/assets/local/fonts');
define('FONT_URLS_FILE', FONTS_DIR . '/font-urls.json');

// Colors for CLI output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
define('COLOR_BOLD', "\033[1m");

class FontDownloader {
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    private $downloadedFonts = [];
    private $failedDownloads = [];
    
    /**
     * Display banner
     */
    public function showBanner() {
        echo COLOR_BLUE . str_repeat('=', 60) . COLOR_RESET . "\n";
        echo COLOR_BOLD . COLOR_BLUE . "   Inter Font Localization Script\n" . COLOR_RESET;
        echo COLOR_BLUE . "   SOFTNET SSC Election System\n" . COLOR_RESET;
        echo COLOR_BLUE . str_repeat('=', 60) . COLOR_RESET . "\n\n";
    }
    
    /**
     * Check prerequisites
     */
    public function checkPrerequisites() {
        echo "🔍 Checking prerequisites...\n";
        
        // Check if font URLs file exists
        if (!file_exists(FONT_URLS_FILE)) {
            echo COLOR_RED . "❌ Font URLs file not found: " . FONT_URLS_FILE . COLOR_RESET . "\n";
            echo "   Please run download_assets.php first.\n";
            return false;
        }
        
        // Check if Inter CSS exists
        $interCssPath = CSS_DIR . '/inter-font.css';
        if (!file_exists($interCssPath)) {
            echo COLOR_RED . "❌ Inter CSS file not found: " . $interCssPath . COLOR_RESET . "\n";
            echo "   Please run download_assets.php first.\n";
            return false;
        }
        
        // Ensure fonts directory exists
        if (!is_dir(FONTS_DIR)) {
            echo "📁 Creating fonts directory...\n";
            if (!mkdir(FONTS_DIR, 0755, true)) {
                echo COLOR_RED . "❌ Failed to create fonts directory: " . FONTS_DIR . COLOR_RESET . "\n";
                return false;
            }
        }
        
        echo COLOR_GREEN . "✓ Prerequisites check passed\n" . COLOR_RESET;
        return true;
    }
    
    /**
     * Extract font URLs from CSS file
     */
    public function extractFontUrlsFromCss() {
        echo "\n📄 Extracting font URLs from CSS...\n";
        
        $cssPath = CSS_DIR . '/inter-font.css';
        $cssContent = file_get_contents($cssPath);
        
        // Pattern to match font URLs
        preg_match_all('/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/i', $cssContent, $matches);
        
        if (empty($matches[1])) {
            // Try alternative pattern
            preg_match_all('/url\("(https:\/\/fonts\.gstatic\.com\/[^"]+)"\)/i', $cssContent, $matches);
        }
        
        if (empty($matches[1])) {
            echo COLOR_YELLOW . "⚠ No font URLs found in CSS. Checking existing URLs file...\n" . COLOR_RESET;
            
            if (file_exists(FONT_URLS_FILE)) {
                $fontConfig = json_decode(file_get_contents(FONT_URLS_FILE), true);
                $fontUrls = $fontConfig['font_urls'] ?? [];
                
                if (!empty($fontUrls)) {
                    echo COLOR_GREEN . "✓ Found " . count($fontUrls) . " font URLs in config file\n" . COLOR_RESET;
                    return $fontUrls;
                }
            }
            
            echo COLOR_RED . "❌ No font URLs found anywhere\n" . COLOR_RESET;
            return [];
        }
        
        $fontUrls = array_unique($matches[1]);
        echo COLOR_GREEN . "✓ Found " . count($fontUrls) . " unique font URLs\n" . COLOR_RESET;
        
        // Save URLs to file for reference
        $this->saveFontUrls($fontUrls);
        
        return $fontUrls;
    }
    
    /**
     * Save font URLs to JSON file
     */
    private function saveFontUrls($fontUrls) {
        $fontConfig = [
            'extracted_at' => date('Y-m-d H:i:s'),
            'total_urls' => count($fontUrls),
            'font_urls' => $fontUrls
        ];
        
        file_put_contents(FONT_URLS_FILE, json_encode($fontConfig, JSON_PRETTY_PRINT));
        echo "💾 Saved font URLs to: " . FONT_URLS_FILE . "\n";
    }
    
    /**
     * Download a single font file
     */
    public function downloadFont($url, $index, $total) {
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $savePath = FONTS_DIR . '/' . $filename;
        
        echo "\n" . ($index + 1) . "/{$total} ";
        echo COLOR_BOLD . "{$filename}" . COLOR_RESET . "\n";
        echo "   From: " . $url . "\n";
        
        // Skip if already downloaded
        if (file_exists($savePath)) {
            $size = filesize($savePath);
            echo COLOR_GREEN . "   ✓ Already exists: " . number_format($size / 1024, 2) . " KB\n" . COLOR_RESET;
            $this->downloadedFonts[$filename] = $savePath;
            return true;
        }
        
        try {
            // Use cURL for better control
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_ENCODING => '',
                CURLOPT_REFERER => 'https://fonts.googleapis.com/',
            ]);
            
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("HTTP {$httpCode}: {$error}");
            }
            
            if (empty($content)) {
                throw new Exception("Empty response");
            }
            
            // Save file
            $bytes = file_put_contents($savePath, $content);
            
            if ($bytes === false) {
                throw new Exception("Failed to write file");
            }
            
            // Calculate integrity hash
            $hash = hash('sha384', $content, true);
            $integrity = 'sha384-' . base64_encode($hash);
            
            echo COLOR_GREEN . "   ✓ Downloaded: " . number_format(strlen($content) / 1024, 2) . " KB\n" . COLOR_RESET;
            echo "   🔐 Integrity: " . substr($integrity, 0, 40) . "...\n";
            
            $this->downloadedFonts[$filename] = [
                'path' => $savePath,
                'size' => strlen($content),
                'integrity' => $integrity
            ];
            
            return true;
            
        } catch (Exception $e) {
            echo COLOR_RED . "   ✗ Failed: " . $e->getMessage() . "\n" . COLOR_RESET;
            $this->failedDownloads[$filename] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * Update CSS to use local font files
     */
    public function updateCssWithLocalFonts() {
        echo "\n📝 Updating CSS to use local fonts...\n";
        
        $cssPath = CSS_DIR . '/inter-font.css';
        $cssContent = file_get_contents($cssPath);
        
        $originalCss = $cssContent;
        $replacements = 0;
        
        // Replace each external URL with local path
        foreach ($this->downloadedFonts as $filename => $info) {
            // Find URLs that point to this filename
            $patterns = [
                '/url\(["\']?https:\/\/fonts\.gstatic\.com\/[^"\')]*' . preg_quote($filename, '/') . '["\']?\)/i',
                '/url\("https:\/\/fonts\.gstatic\.com\/[^"]*' . preg_quote($filename, '/') . '"\)/i',
                '/url\(\'https:\/\/fonts\.gstatic\.com\/[^\']*' . preg_quote($filename, '/') . '\'\)/i',
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $cssContent)) {
                    $cssContent = preg_replace(
                        $pattern,
                        "url('../fonts/" . $filename . "')",
                        $cssContent
                    );
                    $replacements++;
                    echo COLOR_GREEN . "   ✓ Localized: " . $filename . "\n" . COLOR_RESET;
                }
            }
        }
        
        // Create backup of original CSS
        $backupPath = CSS_DIR . '/inter-font.css.backup-' . date('Ymd-His');
        file_put_contents($backupPath, $originalCss);
        
        // Save updated CSS
        file_put_contents($cssPath, $cssContent);
        
        echo COLOR_GREEN . "✓ Updated CSS: " . $replacements . " replacements made\n" . COLOR_RESET;
        echo "   Backup saved to: " . $backupPath . "\n";
        
        return $replacements;
    }
    
    /**
     * Create fonts.json with metadata
     */
    public function createFontsManifest() {
        $manifestPath = FONTS_DIR . '/fonts.json';
        
        $manifest = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_fonts' => count($this->downloadedFonts),
            'total_size' => 0,
            'fonts' => []
        ];
        
        foreach ($this->downloadedFonts as $filename => $info) {
            if (is_array($info)) {
                $manifest['total_size'] += $info['size'];
                $manifest['fonts'][] = [
                    'filename' => $filename,
                    'size' => $info['size'],
                    'integrity' => $info['integrity'],
                    'path' => 'fonts/' . $filename
                ];
            } else {
                // Already existed
                $size = filesize($info);
                $manifest['total_size'] += $size;
                $manifest['fonts'][] = [
                    'filename' => $filename,
                    'size' => $size,
                    'status' => 'existing',
                    'path' => 'fonts/' . $filename
                ];
            }
        }
        
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
        echo "\n📋 Created font manifest: " . $manifestPath . "\n";
        
        return $manifest;
    }
    
    /**
     * Update local_assets.php with font integrity hashes
     */
    public function updateLocalAssets() {
        $localAssetsPath = BASE_DIR . '/config/local_assets.php';
        
        if (!file_exists($localAssetsPath)) {
            echo COLOR_YELLOW . "⚠ local_assets.php not found, skipping update\n" . COLOR_RESET;
            return;
        }
        
        $content = file_get_contents($localAssetsPath);
        
        // Update Inter font CSS integrity if we have new fonts
        if (!empty($this->downloadedFonts)) {
            $cssPath = CSS_DIR . '/inter-font.css';
            $cssContent = file_get_contents($cssPath);
            $newIntegrity = 'sha384-' . base64_encode(hash('sha384', $cssContent, true));
            
            // Find and replace the integrity line
            $content = preg_replace(
                "/define\('LOCAL_INTER_FONT_CSS_INTEGRITY', '[^']+'\);/",
                "define('LOCAL_INTER_FONT_CSS_INTEGRITY', '" . $newIntegrity . "');",
                $content
            );
            
            echo COLOR_GREEN . "✓ Updated Inter CSS integrity hash\n" . COLOR_RESET;
        }
        
        file_put_contents($localAssetsPath, $content);
        echo "📝 Updated local_assets.php\n";
    }
    
    /**
     * Generate report
     */
    public function generateReport($fontUrls, $successCount, $total) {
        $reportPath = BASE_DIR . '/font-download-report.txt';
        
        $report = "FONT DOWNLOAD REPORT\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat('=', 60) . "\n\n";
        
        $report .= "TOTAL URLS FOUND: " . count($fontUrls) . "\n";
        $report .= "SUCCESSFULLY DOWNLOADED: " . $successCount . "\n";
        $report .= "FAILED: " . count($this->failedDownloads) . "\n";
        $report .= "\n" . str_repeat('-', 60) . "\n\n";
        
        if (!empty($this->downloadedFonts)) {
            $report .= "DOWNLOADED FONTS:\n";
            foreach ($this->downloadedFonts as $filename => $info) {
                if (is_array($info)) {
                    $report .= "✓ " . $filename . " (" . number_format($info['size'] / 1024, 2) . " KB)\n";
                } else {
                    $report .= "✓ " . $filename . " (already existed)\n";
                }
            }
        }
        
        if (!empty($this->failedDownloads)) {
            $report .= "\nFAILED DOWNLOADS:\n";
            foreach ($this->failedDownloads as $filename => $error) {
                $report .= "✗ " . $filename . " - " . $error . "\n";
            }
        }
        
        $report .= "\n" . str_repeat('=', 60) . "\n";
        $report .= "NEXT STEPS:\n";
        $report .= "1. Fonts are now stored in: assets/local/fonts/\n";
        $report .= "2. CSS has been updated to use local font paths\n";
        $report .= "3. Update CSP in bootstrap.php to remove fonts.gstatic.com\n";
        $report .= "4. Test the application to ensure fonts load correctly\n";
        
        file_put_contents($reportPath, $report);
        echo "\n📊 Report generated: " . $reportPath . "\n";
    }
    
    /**
     * Show summary
     */
    public function showSummary($successCount, $total) {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo COLOR_BOLD . "DOWNLOAD SUMMARY\n" . COLOR_RESET;
        echo str_repeat('-', 60) . "\n";
        echo "Total font URLs found: " . $total . "\n";
        echo COLOR_GREEN . "Successfully processed: " . $successCount . "\n" . COLOR_RESET;
        
        if (!empty($this->failedDownloads)) {
            echo COLOR_RED . "Failed: " . count($this->failedDownloads) . "\n" . COLOR_RESET;
        }
        
        $totalSize = 0;
        foreach ($this->downloadedFonts as $info) {
            if (is_array($info)) {
                $totalSize += $info['size'];
            }
        }
        
        echo "Total font size: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n";
        echo str_repeat('=', 60) . "\n";
    }
}

// Main execution
$downloader = new FontDownloader();
$downloader->showBanner();

// Step 1: Check prerequisites
if (!$downloader->checkPrerequisites()) {
    exit(1);
}

// Step 2: Extract font URLs
$fontUrls = $downloader->extractFontUrlsFromCss();

if (empty($fontUrls)) {
    echo COLOR_RED . "❌ No font URLs to download.\n" . COLOR_RESET;
    exit(1);
}

// Step 3: Download fonts
echo "\n" . str_repeat('-', 60) . "\n";
echo COLOR_BOLD . "DOWNLOADING FONT FILES\n" . COLOR_RESET;
echo str_repeat('-', 60) . "\n";

$successCount = 0;
$total = count($fontUrls);

foreach ($fontUrls as $index => $url) {
    if ($downloader->downloadFont($url, $index, $total)) {
        $successCount++;
    }
}

// Step 4: Update CSS with local font paths
echo "\n" . str_repeat('-', 60) . "\n";
echo COLOR_BOLD . "UPDATING CSS REFERENCES\n" . COLOR_RESET;
echo str_repeat('-', 60) . "\n";

$replacements = $downloader->updateCssWithLocalFonts();

// Step 5: Create manifest
$manifest = $downloader->createFontsManifest();

// Step 6: Update local assets
$downloader->updateLocalAssets();

// Step 7: Generate report
$downloader->generateReport($fontUrls, $successCount, $total);

// Step 8: Show summary
$downloader->showSummary($successCount, $total);

// Step 9: Show next steps
echo "\n" . COLOR_BOLD . COLOR_GREEN . "✅ FONT LOCALIZATION COMPLETE!\n" . COLOR_RESET;
echo "\n" . COLOR_BOLD . "NEXT STEPS:\n" . COLOR_RESET;
echo "1. " . COLOR_BOLD . "Update CSP in bootstrap.php" . COLOR_RESET . ":\n";
echo "   Remove 'https://fonts.gstatic.com' from font-src directive\n";
echo "   font-src should only be: 'self'\n\n";
echo "2. " . COLOR_BOLD . "Test the application" . COLOR_RESET . ":\n";
echo "   - Load index.php in browser\n";
echo "   - Check console for errors\n";
echo "   - Verify fonts are loading locally\n\n";
echo "3. " . COLOR_BOLD . "Optional cleanup" . COLOR_RESET . ":\n";
echo "   - Remove font-urls.json if no longer needed\n";
echo "   - Keep backup CSS files for reference\n";
echo "\n" . str_repeat('=', 60) . "\n";