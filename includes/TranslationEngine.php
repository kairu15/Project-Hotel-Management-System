<?php
/**
 * TranslationEngine.php - Langbly Translation API Integration
 * 
 * Free tier: ~500,000 characters/month
 * Compatible with Google Translate v2 format
 * Supports ~100+ languages
 * 
 * Usage:
 *   require_once 'TranslationEngine.php';
 *   $translator = TranslationEngine::getInstance();
 *   $translated = $translator->translate('Hello', 'es'); // Spanish
 */

class TranslationEngine {
    private static $instance = null;
    private $apiKey;
    private $apiEndpoint = 'https://api.langbly.com/language/translate/v2';
    private $cacheDir;
    private $cacheEnabled = true;
    private $defaultSourceLang = 'en';
    
    // Supported languages (common hotel/lodge relevant languages)
    private $supportedLanguages = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'zh' => 'Chinese (Simplified)',
        'zh-TW' => 'Chinese (Traditional)',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'th' => 'Thai',
        'vi' => 'Vietnamese',
        'id' => 'Indonesian',
        'ms' => 'Malay',
        'tl' => 'Filipino (Tagalog)',
        'nl' => 'Dutch',
        'pl' => 'Polish',
        'tr' => 'Turkish',
        'sv' => 'Swedish',
        'da' => 'Danish',
        'no' => 'Norwegian',
        'fi' => 'Finnish',
        'el' => 'Greek',
        'cs' => 'Czech',
        'hu' => 'Hungarian',
        'ro' => 'Romanian',
        'he' => 'Hebrew',
        'uk' => 'Ukrainian',
    ];
    
    private function __construct() {
        // Set API key from config or environment
        $this->apiKey = defined('LANGBLY_API_KEY') ? LANGBLY_API_KEY : $_ENV['LANGBLY_API_KEY'] ?? '';
        
        // Cache directory
        $this->cacheDir = __DIR__ . '/../cache/translations/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Set API key
     */
    public function setApiKey(string $apiKey): self {
        $this->apiKey = $apiKey;
        return $this;
    }
    
    /**
     * Enable/disable caching
     */
    public function setCacheEnabled(bool $enabled): self {
        $this->cacheEnabled = $enabled;
        return $this;
    }
    
    /**
     * Set default source language
     */
    public function setDefaultSourceLang(string $lang): self {
        $this->defaultSourceLang = $lang;
        return $this;
    }
    
    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array {
        return $this->supportedLanguages;
    }
    
    /**
     * Check if language is supported
     */
    public function isLanguageSupported(string $lang): bool {
        return isset($this->supportedLanguages[$lang]);
    }
    
    /**
     * Translate text
     * 
     * @param string|array $text Text to translate (string or array of strings)
     * @param string $targetLang Target language code (e.g., 'es', 'fr')
     * @param string|null $sourceLang Source language (null for auto-detect)
     * @return string|array Translated text
     */
    public function translate($text, string $targetLang, ?string $sourceLang = null) {
        // Validate target language
        if (!$this->isLanguageSupported($targetLang)) {
            error_log("TranslationEngine: Unsupported target language: {$targetLang}");
            return $text;
        }
        
        $sourceLang = $sourceLang ?? $this->defaultSourceLang;
        
        // Handle array of texts
        if (is_array($text)) {
            return $this->translateBatch($text, $targetLang, $sourceLang);
        }
        
        // Check cache first
        if ($this->cacheEnabled) {
            $cached = $this->getCached($text, $targetLang, $sourceLang);
            if ($cached !== null) {
                return $cached;
            }
        }
        
        // Make API call
        $result = $this->callApi($text, $targetLang, $sourceLang);
        
        // Cache result
        if ($this->cacheEnabled && $result !== null) {
            $this->setCached($text, $targetLang, $sourceLang, $result);
        }
        
        return $result ?? $text;
    }
    
    /**
     * Translate multiple texts
     */
    private function translateBatch(array $texts, string $targetLang, string $sourceLang): array {
        $results = [];
        foreach ($texts as $text) {
            $results[] = $this->translate($text, $targetLang, $sourceLang);
        }
        return $results;
    }
    
    /**
     * Call Langbly API
     */
    private function callApi(string $text, string $targetLang, string $sourceLang): ?string {
        if (empty($this->apiKey)) {
            error_log('TranslationEngine: No API key configured');
            return null;
        }
        
        $params = [
            'q' => $text,
            'target' => $targetLang,
        ];
        
        if ($sourceLang !== 'auto') {
            $params['source'] = $sourceLang;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Reduced from 30 to 5 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verify for speed
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'x-api-key: ' . $this->apiKey,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("TranslationEngine: cURL error: {$error}");
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("TranslationEngine: HTTP error: {$httpCode}");
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('TranslationEngine: JSON decode error: ' . json_last_error_msg());
            return null;
        }
        
        // Langbly uses Google Translate v2 format
        if (isset($data['data']['translations'][0]['translatedText'])) {
            return $data['data']['translations'][0]['translatedText'];
        }
        
        error_log('TranslationEngine: Unexpected API response format');
        return null;
    }
    
    /**
     * Get cached translation
     */
    private function getCached(string $text, string $targetLang, string $sourceLang): ?string {
        $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
        $cacheFile = $this->cacheDir . $cacheKey . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $cache = json_decode(file_get_contents($cacheFile), true);
        
        // Cache expires after 30 days
        if ($cache && isset($cache['timestamp']) && (time() - $cache['timestamp']) < (30 * 24 * 60 * 60)) {
            return $cache['translation'];
        }
        
        // Expired cache, delete it
        @unlink($cacheFile);
        return null;
    }
    
    /**
     * Store translation in cache
     */
    private function setCached(string $text, string $targetLang, string $sourceLang, string $translation): void {
        $cacheKey = $this->getCacheKey($text, $targetLang, $sourceLang);
        $cacheFile = $this->cacheDir . $cacheKey . '.json';
        
        $cache = [
            'source_text' => $text,
            'translation' => $translation,
            'source_lang' => $sourceLang,
            'target_lang' => $targetLang,
            'timestamp' => time(),
        ];
        
        file_put_contents($cacheFile, json_encode($cache), LOCK_EX);
    }
    
    /**
     * Generate cache key
     */
    private function getCacheKey(string $text, string $targetLang, string $sourceLang): string {
        return md5($text . '|' . $sourceLang . '|' . $targetLang);
    }
    
    /**
     * Clear all translation cache
     */
    public function clearCache(): void {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array {
        $files = glob($this->cacheDir . '*.json');
        $totalSize = 0;
        $count = count($files);
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'file_count' => $count,
            'total_size_bytes' => $totalSize,
            'cache_dir' => $this->cacheDir,
        ];
    }
    
    /**
     * Auto-detect language
     */
    public function detectLanguage(string $text): ?string {
        if (empty($this->apiKey)) {
            return null;
        }
        
        $params = [
            'q' => $text,
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.langbly.com/language/translate/v2/detect');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'x-api-key: ' . $this->apiKey,
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['data']['detections'][0][0]['language'])) {
            return $data['data']['detections'][0][0]['language'];
        }
        
        return null;
    }
}

/**
 * Global helper function for quick translation
 * 
 * @param string $text Text to translate
 * @param string|null $lang Target language (uses session or default if null)
 * @return string Translated text
 */
function __($text, ?string $lang = null): string {
    $translator = TranslationEngine::getInstance();
    
    // Get language from session or parameter
    if ($lang === null) {
        $lang = $_SESSION['user_language'] ?? 'en';
    }
    
    // Don't translate if already in target language
    if ($lang === 'en') {
        return $text;
    }
    
    return $translator->translate($text, $lang);
}

/**
 * Set user's preferred language
 */
function setUserLanguage(string $lang): void {
    $_SESSION['user_language'] = $lang;
}

/**
 * Get user's current language
 */
function getUserLanguage(): string {
    return $_SESSION['user_language'] ?? 'en';
}
