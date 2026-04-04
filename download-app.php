<?php
/**
 * Mobile App Download Handler
 * Serves the APK file with proper headers for Android download
 */

require_once 'includes/config.php';
require_once 'includes/TranslationEngine.php';

// APK file path
$apkFile = __DIR__ . '/apps/bayawanbai-hotel-app.apk';
$apkVersion = '1.0.0';
$apkSize = '15 MB';

// Check if running on mobile
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isAndroid = stripos($userAgent, 'Android') !== false;
$isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/', $userAgent);

// If APK exists and user requested direct download
if (isset($_GET['file']) && file_exists($apkFile)) {
    // Serve the file with proper headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="' . basename($apkFile) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($apkFile));
    
    ob_clean();
    flush();
    readfile($apkFile);
    exit;
}

$pageTitle = __('Download Mobile App');
require_once 'includes/header.php';
?>

<section style="padding: 80px 0; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); min-height: calc(100vh - 200px);">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; text-align: center;">
            <!-- App Icon -->
            <div style="width: 120px; height: 120px; background: white; border-radius: 24px; margin: 0 auto 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                <img src="assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel" style="width: 80px; height: 80px; object-fit: contain;">
            </div>
            
            <h1 style="font-size: 42px; color: white; margin-bottom: 15px;"><?php echo __('Bayawan Bai Hotel App'); ?></h1>
            <p style="font-size: 18px; color: rgba(255,255,255,0.9); margin-bottom: 40px;">
                <?php echo __('Book rooms, order food, and manage your stay - all from your mobile device!'); ?>
            </p>
            
            <!-- Features -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; color: white;">
                    <i class="fas fa-bed" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <h3 style="font-size: 16px; margin-bottom: 8px;"><?php echo __('Easy Booking'); ?></h3>
                    <p style="font-size: 13px; opacity: 0.8;"><?php echo __('Book rooms in seconds'); ?></p>
                </div>
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; color: white;">
                    <i class="fas fa-utensils" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <h3 style="font-size: 16px; margin-bottom: 8px;"><?php echo __('Food Ordering'); ?></h3>
                    <p style="font-size: 13px; opacity: 0.8;"><?php echo __('Order from our restaurant'); ?></p>
                </div>
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 25px; border-radius: 15px; color: white;">
                    <i class="fas fa-qrcode" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <h3 style="font-size: 16px; margin-bottom: 8px;"><?php echo __('Digital Key'); ?></h3>
                    <p style="font-size: 13px; opacity: 0.8;"><?php echo __('QR code room access'); ?></p>
                </div>
            </div>
            
            <!-- Download Section -->
            <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.2);">
                <h2 style="font-size: 24px; color: var(--dark-color); margin-bottom: 10px;"><?php echo __('Download for Android'); ?></h2>
                <p style="font-size: 14px; color: #666; margin-bottom: 25px;">
                    <?php echo sprintf(__('Version %s | %s | Android 6.0+'), $apkVersion, $apkSize); ?>
                </p>
                
                <?php if (file_exists($apkFile)): ?>
                    <!-- APK Available -->
                    <a href="download-app.php?file=1" 
                       class="btn-download" 
                       style="display: inline-flex; align-items: center; gap: 15px; background: linear-gradient(135deg, #3DDC84, #2E7D32); color: white; padding: 18px 40px; border-radius: 12px; text-decoration: none; font-size: 18px; font-weight: 600; transition: all 0.3s; box-shadow: 0 8px 25px rgba(61, 220, 132, 0.4);"
                       onclick="gtag('event', 'download', { 'event_category': 'app', 'event_label': 'apk' });">
                        <i class="fab fa-android" style="font-size: 28px;"></i>
                        <span><?php echo __('Download APK'); ?></span>
                    </a>
                    
                    <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 10px; text-align: left;">
                        <h4 style="font-size: 14px; color: var(--dark-color); margin-bottom: 10px;">
                            <i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 8px;"></i>
                            <?php echo __('How to Install:'); ?>
                        </h4>
                        <ol style="font-size: 13px; color: #666; line-height: 1.8; margin-left: 20px;">
                            <li><?php echo __('Download the APK file'); ?></li>
                            <li><?php echo __('Open Settings → Security → Enable "Unknown Sources"'); ?></li>
                            <li><?php echo __('Tap the downloaded file to install'); ?></li>
                            <li><?php echo __('Open the app and start booking!'); ?></li>
                        </ol>
                    </div>
                <?php else: ?>
                    <!-- APK Not Available -->
                    <div style="padding: 30px; background: #f8f9fa; border-radius: 10px;">
                        <i class="fas fa-clock" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <h3 style="font-size: 20px; color: var(--dark-color); margin-bottom: 10px;"><?php echo __('Coming Soon!'); ?></h3>
                        <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                            <?php echo __('Our mobile app is under development. Leave your email to be notified when it\'s ready!'); ?>
                        </p>
                        <form action="subscribe.php" method="POST" style="display: flex; max-width: 400px; margin: 0 auto;">
                            <input type="email" name="email" placeholder="<?php echo __('Enter your email'); ?>" required 
                                   style="flex: 1; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px 0 0 8px; font-size: 14px; outline: none;">
                            <button type="submit" style="background-color: var(--primary-color); color: white; border: none; padding: 12px 25px; border-radius: 0 8px 8px 0; cursor: pointer; font-weight: 600;">
                                <?php echo __('Notify Me'); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- iOS Notice -->
                <div style="margin-top: 25px; padding: 15px; background: #f0f0f0; border-radius: 10px;">
                    <p style="font-size: 13px; color: #666;">
                        <i class="fab fa-apple" style="margin-right: 8px;"></i>
                        <?php echo __('iOS version coming soon to the App Store'); ?>
                    </p>
                </div>
            </div>
            
            <!-- QR Code for easy mobile access -->
            <div style="margin-top: 30px; color: white;">
                <p style="font-size: 14px; opacity: 0.8; margin-bottom: 15px;">
                    <?php echo __('Scan to download on your phone:'); ?>
                </p>
                <div style="background: white; padding: 15px; border-radius: 15px; display: inline-block;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . '/bayawanhotel/download-app.php'); ?>" 
                         alt="Download QR Code" 
                         style="width: 150px; height: 150px;">
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Mobile Responsive */
@media (max-width: 768px) {
    section[style*="padding: 80px 0"] {
        padding: 40px 0 !important;
    }
    
    h1 {
        font-size: 28px !important;
    }
    
    section .container > div > div[style*="display: grid"] {
        grid-template-columns: 1fr !important;
    }
    
    section .container > div > div[style*="background: white"] {
        padding: 25px !important;
        margin: 0 15px;
    }
}

/* Button hover effect */
.btn-download:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(61, 220, 132, 0.5) !important;
}
</style>

<?php require_once 'includes/footer.php'; ?>
