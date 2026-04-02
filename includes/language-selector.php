<?php
/**
 * Language Selector Component
 * Include this in header files to allow users to switch languages
 * 
 * Usage:
 *   include __DIR__ . '/language-selector.php';
 */

require_once __DIR__ . '/TranslationEngine.php';

// Get translator instance
$translator = TranslationEngine::getInstance();
$supportedLanguages = $translator->getSupportedLanguages();
$currentLang = getUserLanguage();

// Note: Language change is handled in config.php (before any output)
?>

<style>
    .language-selector {
        position: relative;
        display: inline-block;
    }
    
    .language-btn {
        background: transparent;
        border: 1px solid var(--gray-medium, #ddd);
        border-radius: 20px;
        padding: 6px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: var(--text-color, #333);
        transition: all 0.3s;
    }
    
    .language-btn:hover {
        border-color: var(--primary-color, #367D8A);
        color: var(--primary-color, #367D8A);
    }
    
    .language-btn i {
        font-size: 14px;
    }
    
    .language-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        min-width: 160px;
        max-height: 300px;
        overflow-y: auto;
        display: none;
        z-index: 1000;
        margin-top: 5px;
    }
    
    .language-dropdown.active {
        display: block;
    }
    
    .language-dropdown a {
        display: block;
        padding: 10px 15px;
        color: var(--text-color, #333);
        text-decoration: none;
        font-size: 13px;
        border-bottom: 1px solid var(--gray-light, #f5f5f5);
        transition: background 0.2s;
    }
    
    .language-dropdown a:hover {
        background: var(--gray-light, #f5f5f5);
        color: var(--primary-color, #367D8A);
    }
    
    .language-dropdown a:last-child {
        border-bottom: none;
    }
    
    .language-dropdown a.active {
        background: var(--primary-color, #367D8A);
        color: white;
    }
    
    .lang-flag {
        margin-right: 8px;
    }
</style>

<div class="language-selector">
    <button class="language-btn" onclick="toggleLanguageMenu()">
        <i class="fas fa-globe"></i>
        <span><?php echo $supportedLanguages[$currentLang] ?? 'English'; ?></span>
        <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
    </button>
    <div class="language-dropdown" id="languageDropdown">
        <?php foreach ($supportedLanguages as $code => $name): ?>
            <a href="?lang=<?php echo $code; ?>">
                <?php echo htmlspecialchars($name); ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function toggleLanguageMenu() {
        document.getElementById('languageDropdown').classList.toggle('active');
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const selector = document.querySelector('.language-selector');
        const dropdown = document.getElementById('languageDropdown');
        if (selector && !selector.contains(event.target)) {
            dropdown.classList.remove('active');
        }
    });
</script>
