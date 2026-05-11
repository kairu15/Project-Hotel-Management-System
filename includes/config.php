<?php
/**
 * Bayawan Bai Hotel - Configuration File
 * Database and application settings
 */

// Load environment variables from .env file
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Start session immediately - must be before any output
session_start();

// Handle language change (must be before any output)
if (isset($_GET['lang']) && preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $_GET['lang'])) {
    $_SESSION['user_language'] = $_GET['lang'];
    // Redirect to remove lang parameter from URL
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: {$currentUrl}");
    exit;
}

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'bayawan_hotel');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Site configuration
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Bayawan Bai Hotel');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/bayawanhotel');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'bayawanbaiminihotel@gmail.com');

// Email configuration for OTP
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? 'bayawanbaiminihotel@gmail.com');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'bayawanbaiminihotel@gmail.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Bayawan Bai Hotel');

// Google Gemini API Configuration
// Get your free API key from: https://aistudio.google.com/app/apikey
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

// Groq API Configuration
// Get your free API key from: https://console.groq.com/keys
define('GROQ_API_KEY', $_ENV['GROQ_API_KEY'] ?? '');

// Langbly Translation API Configuration
// Free tier: ~500,000 characters/month
// Get your free API key from: https://langbly.com
define('LANGBLY_API_KEY', $_ENV['LANGBLY_API_KEY'] ?? '');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost/bayawanhotel/auth/google-callback.php');

// Facebook OAuth Configuration
define('FACEBOOK_APP_ID', $_ENV['FACEBOOK_APP_ID'] ?? '');
define('FACEBOOK_APP_SECRET', $_ENV['FACEBOOK_APP_SECRET'] ?? '');
define('FACEBOOK_REDIRECT_URI', $_ENV['FACEBOOK_REDIRECT_URI'] ?? 'http://localhost/bayawanhotel/auth/facebook-callback.php');

// Import PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Application Settings
define('CURRENCY', 'PHP');
define('CURRENCY_SYMBOL', '₱');

// Session Settings - Timeout after 30 minutes of inactivity (in seconds)
define('SESSION_TIMEOUT', 1800);
define('SESSION_WARNING_TIME', 300); // Warning 5 minutes before timeout

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $conn;
    
    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->dbname . ";charset=utf8mb4",
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}

// Helper Functions
function getDB() {
    $database = new Database();
    return $database->connect();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStaff() {
    return isLoggedIn() && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager', 'receptionist']);
}

function isReceptionist() {
    return isLoggedIn() && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager', 'receptionist']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

function getUserName() {
    return $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] ?? 'Guest';
}

/**
 * Check and handle session timeout
 * Returns true if session is valid, false if expired (and logs user out)
 */
function checkSessionTimeout() {
    // Only check for logged-in users
    if (!isLoggedIn()) {
        return true;
    }

    $currentTime = time();

    // Check if last_activity is set
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $currentTime;
        return true;
    }

    $inactiveTime = $currentTime - $_SESSION['last_activity'];

    // Check if session has expired
    if ($inactiveTime > SESSION_TIMEOUT) {
        // Session expired - log user out
        $userId = getUserId();

        // Update database to mark user offline
        try {
            $db = getDB();
            if ($db && $userId) {
                $stmt = $db->prepare("UPDATE users SET active_status = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);

                // Remove session record
                $sessionStmt = $db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
                $sessionStmt->execute([session_id()]);
            }
        } catch (Exception $e) {
            error_log('Session timeout cleanup error: ' . $e->getMessage());
        }

        // Store timeout message
        $_SESSION['timeout_message'] = 'Your session has expired due to inactivity. Please sign in again.';

        // Clear session data
        $_SESSION = array();

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();

        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = $currentTime;
    return true;
}

/**
 * Get remaining session time in seconds
 * Returns 0 if not logged in or session expired
 */
function getSessionRemainingTime() {
    if (!isLoggedIn() || !isset($_SESSION['last_activity'])) {
        return 0;
    }

    $remaining = SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
    return max(0, $remaining);
}

/**
 * Check if session warning should be shown (within 5 minutes of timeout)
 */
function shouldShowSessionWarning() {
    if (!isLoggedIn()) {
        return false;
    }

    $remaining = getSessionRemainingTime();
    return $remaining > 0 && $remaining <= SESSION_WARNING_TIME;
}

function redirect($url) {
    // Check if headers have already been sent
    if (headers_sent()) {
        // Use JavaScript redirect as fallback
        echo "<script>window.location.href = '" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "';</script>";
        echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "\"></noscript>";
        echo "<p>Redirecting... <a href=\"" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "\">Click here if not redirected</a></p>";
        exit();
    }
    header("Location: " . $url);
    exit();
}

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

function formatPrice($amount) {
    if ($amount === null || $amount === '') {
        $amount = 0;
    }
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Staff Permission Functions
function getStaffPermissionSetting($settingName) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM staff_permission_settings WHERE setting_name = ?");
    $stmt->execute([$settingName]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : 'false';
}

function setStaffPermissionSetting($settingName, $settingValue) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO staff_permission_settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$settingName, $settingValue, $settingValue]);
}

function hasStaffPermission($userId, $pageName) {
    // First check global settings
    $globalSetting = getStaffPermissionSetting('allow_all_staff_' . $pageName);
    
    // Debug logging
    error_log("hasStaffPermission - Page: $pageName, GlobalSetting value: " . var_export($globalSetting, true));
    
    // Check if global setting allows all staff (handle both 'true' string and '1')
    if ($globalSetting === 'true' || $globalSetting === '1' || $globalSetting === true || $globalSetting == 1) {
        error_log("hasStaffPermission - Global setting allows all staff for: $pageName");
        return true;
    }
    
    // Check individual permission for the logged-in user
    $db = getDB();
    $stmt = $db->prepare("SELECT can_access FROM staff_permissions WHERE user_id = ? AND page_name = ?");
    $stmt->execute([$userId, $pageName]);
    $result = $stmt->fetch();
    
    error_log("hasStaffPermission - Individual check for UserID: $userId, Page: $pageName, Result: " . var_export($result, true));
    
    // can_access is stored as BOOLEAN/TINYINT(1), check if it's 1 or true
    $hasPermission = $result && ($result['can_access'] == 1 || $result['can_access'] === true);
    error_log("hasStaffPermission - Final result for UserID: $userId on Page: $pageName: " . ($hasPermission ? 'true' : 'false'));
    
    return $hasPermission;
}

function setStaffPermission($userId, $pageName, $canAccess) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO staff_permissions (user_id, page_name, can_access) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE can_access = ?");
    return $stmt->execute([$userId, $pageName, $canAccess ? 1 : 0, $canAccess ? 1 : 0]);
}

function checkStaffPermission($pageName) {
    $userId = getUserId();
    $userRole = getUserRole();
    
    // Debug: Log the check attempt
    error_log("Permission check - UserID: $userId, Role: $userRole, Page: $pageName");
    
    // Admin always has access
    if ($userRole === 'admin') {
        return true;
    }
    
    // Check if user is staff (receptionist or manager)
    if (!in_array($userRole, ['receptionist', 'manager'])) {
        showAlert('Access denied. Staff privileges required.', 'danger');
        redirect('../index.php');
        return false;
    }
    
    // Check permission for the logged-in user
    if (!hasStaffPermission($userId, $pageName)) {
        error_log("Permission denied for UserID: $userId on Page: $pageName");
        showAlert('Access denied. You do not have permission to access this page.', 'danger');
        redirect('staff-dashboard.php');
        return false;
    }
    
    error_log("Permission granted for UserID: $userId on Page: $pageName");
    return true;
}

function getAllStaffPermissions($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT page_name, can_access FROM staff_permissions WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getPermissionPages() {
    return [
        'inventory' => ['name' => 'Inventory Management', 'icon' => 'fa-boxes', 'file' => 'staff-inventory.php'],
        'maintenance' => ['name' => 'Maintenance Requests', 'icon' => 'fa-tools', 'file' => 'staff-maintenance.php'],
        'booking_charges' => ['name' => 'Booking Charges', 'icon' => 'fa-file-invoice-dollar', 'file' => 'staff-booking-charges.php'],
        'contact_messages' => ['name' => 'Contact Messages', 'icon' => 'fa-envelope', 'file' => 'staff-contact-messages.php']
    ];
}

function generateBookingRef() {
    return 'BBH' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

function sendEmail($to, $subject, $body) {
    // Basic email function - in production, use PHPMailer
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $body, $headers);
}

// Calculate nights between two dates
function calculateNights($checkIn, $checkOut) {
    $start = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    $interval = $start->diff($end);
    return $interval->days;
}

// Check room availability
function checkAvailability($checkIn, $checkOut, $categoryId = null) {
    $db = getDB();
    
    $sql = "
        SELECT r.*, rc.category_name, rc.base_price, rc.max_occupancy, rc.bed_type, rc.room_size_sqm, rc.amenities
        FROM rooms r
        JOIN room_categories rc ON r.category_id = rc.category_id
        WHERE r.status = 'available'
        AND r.room_id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status IN ('confirmed', 'checked_in', 'reserved')
            AND (
                (check_in <= :check_out AND check_out >= :check_in)
            )
        )
    ";
    
    if ($categoryId) {
        $sql .= " AND r.category_id = :category_id";
    }
    
    $sql .= " ORDER BY r.floor, r.room_number";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':check_in', $checkIn);
    $stmt->bindParam(':check_out', $checkOut);
    
    if ($categoryId) {
        $stmt->bindParam(':category_id', $categoryId);
    }
    
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get room categories with availability count
function getCategoriesWithAvailability($checkIn, $checkOut) {
    $db = getDB();
    
    $sql = "
        SELECT rc.*,
        (SELECT COUNT(*) FROM rooms r 
         WHERE r.category_id = rc.category_id 
         AND r.status = 'available'
         AND r.room_id NOT IN (
            SELECT room_id FROM bookings 
            WHERE status IN ('confirmed', 'checked_in', 'reserved')
            AND (check_in <= :check_out AND check_out >= :check_in)
         )) as available_count
        FROM room_categories rc
        WHERE rc.status = 'active'
        ORDER BY rc.base_price
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':check_out', $checkOut);
    $stmt->bindParam(':check_in', $checkIn);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get settings value
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : $default;
}

// Log activity
function logActivity($action, $details = '') {
    // Activity logging implementation
    $logFile = __DIR__ . '/logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = getUserId() ?? 'guest';
    $logEntry = "[$timestamp] [User: $userId] $action - $details" . PHP_EOL;
    
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Password validation
function validatePassword($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number";
    }
    return true;
}

// Generate OTP
function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Send OTP email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code - Bayawan Bai Hotel';
        $mail->Body    = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Email Verification - Bayawan Bai Hotel</title>
                <style>
                    body {
                        font-family: "Georgia", "Times New Roman", serif;
                        margin: 0;
                        padding: 0;
                        background-color: #f8f9fa;
                        color: #333;
                    }
                    .container {
                        max-width: 650px;
                        margin: 30px auto;
                        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                        border-radius: 15px;
                        overflow: hidden;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                        border: 1px solid #e9ecef;
                    }
                    .header {
                        background: linear-gradient(135deg, #367D8A 0%, #285F6B 100%);
                        padding: 40px 30px;
                        text-align: center;
                        position: relative;
                    }
                    .header::before {
                        content: "";
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                    }
                    .logo {
                        font-size: 32px;
                        font-weight: 700;
                        color: #ffffff;
                        margin-bottom: 10px;
                        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
                        letter-spacing: 1px;
                    }
                    .logo span {
                        color: #ffffff;
                        font-style: italic;
                    }
                    .tagline {
                        color: rgba(255,255,255,0.9);
                        font-size: 14px;
                        font-style: italic;
                        margin: 0;
                    }
                    .content {
                        padding: 50px 40px;
                        text-align: center;
                    }
                    .title {
                        font-size: 28px;
                        color: #367D8A;
                        margin-bottom: 10px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 2px;
                    }
                    .subtitle {
                        font-size: 16px;
                        color: #6c757d;
                        margin-bottom: 40px;
                        font-style: italic;
                    }
                    .otp-container {
                        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                        border: 2px solid #367D8A;
                        border-radius: 15px;
                        padding: 30px;
                        margin: 35px 0;
                        position: relative;
                        box-shadow: 0 5px 15px rgba(54,125,138,0.2);
                    }
                    .otp-label {
                        font-size: 14px;
                        color: #6c757d;
                        margin-bottom: 15px;
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        font-weight: 600;
                    }
                    .otp-code {
                        font-size: 42px;
                        font-weight: 700;
                        color: #367D8A;
                        letter-spacing: 12px;
                        margin: 20px 0;
                        font-family: "Courier New", monospace;
                        text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                        position: relative;
                    }
                    .otp-code::before {
                        content: "🔐";
                        position: absolute;
                        left: -40px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 24px;
                    }
                    .otp-code::after {
                        content: "🔐";
                        position: absolute;
                        right: -40px;
                        top: 50%;
                        transform: translateY(-50%);
                        font-size: 24px;
                    }
                    .expiry {
                        font-size: 13px;
                        color: #dc3545;
                        font-weight: 600;
                        margin-top: 15px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 5px;
                    }
                    .expiry::before {
                        content: "⏰";
                    }
                    .instructions {
                        background-color: #f8f9fa;
                        border-left: 4px solid #367D8A;
                        padding: 20px 25px;
                        margin: 30px 0;
                        text-align: left;
                        border-radius: 0 8px 8px 0;
                    }
                    .instructions h4 {
                        color: #367D8A;
                        margin-bottom: 15px;
                        font-size: 16px;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    .instructions h4::before {
                        content: "📋";
                    }
                    .instructions ul {
                        margin: 0;
                        padding-left: 20px;
                    }
                    .instructions li {
                        margin-bottom: 8px;
                        color: #495057;
                        font-size: 14px;
                    }
                    .security-notice {
                        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
                        border: 1px solid #ffeaa7;
                        border-radius: 8px;
                        padding: 20px;
                        margin: 30px 0;
                        text-align: left;
                    }
                    .security-notice h4 {
                        color: #856404;
                        margin-bottom: 10px;
                        font-size: 14px;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    .security-notice h4::before {
                        content: "🛡️";
                    }
                    .security-notice p {
                        color: #856404;
                        margin: 0;
                        font-size: 13px;
                    }
                    .footer {
                        background-color: #2c3e50;
                        color: #ecf0f1;
                        padding: 30px;
                        text-align: center;
                        border-top: 3px solid #367D8A;
                    }
                    .footer-content {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 20px;
                    }
                    .footer-left {
                        text-align: left;
                    }
                    .footer-right {
                        text-align: right;
                    }
                    .footer h5 {
                        color: #367D8A;
                        margin-bottom: 10px;
                        font-size: 16px;
                    }
                    .footer p {
                        margin: 5px 0;
                        font-size: 13px;
                        opacity: 0.9;
                    }
                    .footer .contact-info {
                        font-size: 12px;
                        opacity: 0.8;
                    }
                    .footer .divider {
                        border-top: 1px solid rgba(255,255,255,0.1);
                        margin: 20px 0;
                    }
                    .footer .copyright {
                        font-size: 11px;
                        opacity: 0.7;
                        margin: 0;
                    }
                    .social-links {
                        margin-top: 10px;
                    }
                    .social-links a {
                        color: #ecf0f1;
                        text-decoration: none;
                        margin: 0 5px;
                        font-size: 18px;
                        transition: color 0.3s;
                    }
                    .social-links a:hover {
                        color: #367D8A;
                    }
                    @media (max-width: 600px) {
                        .container {
                            margin: 10px;
                            border-radius: 10px;
                        }
                        .content {
                            padding: 30px 20px;
                        }
                        .otp-code {
                            font-size: 32px;
                            letter-spacing: 8px;
                        }
                        .otp-code::before,
                        .otp-code::after {
                            display: none;
                        }
                        .footer-content {
                            flex-direction: column;
                            text-align: center;
                        }
                        .footer-left,
                        .footer-right {
                            text-align: center;
                            margin-bottom: 15px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <div class="logo">Bayawan <span>Bai</span> Hotel</div>
                        <p class="tagline">Experience Luxury and Comfort</p>
                    </div>
                    
                    <div class="content">
                        <h1 class="title">Email Verification Code</h1>
                        <p class="subtitle">Thank you for registering with Bayawan Bai Hotel!</p>
                        
                        <div class="otp-container">
                            <div class="otp-label">Your One-Time Password (OTP)</div>
                            <div class="otp-code">' . $otp . '</div>
                            <div class="expiry">This code will expire in 5 minutes</div>
                        </div>
                        
                        <div class="instructions">
                            <h4>Verification Instructions</h4>
                            <ul>
                                <li>Enter the 6-digit code above in the verification field</li>
                                <li>Ensure you complete verification within 5 minutes</li>
                                <li>Keep this code confidential and do not share it</li>
                                <li>If you did not request this code, please contact us immediately</li>
                            </ul>
                        </div>
                        
                        <div class="security-notice">
                            <h4>Security Notice</h4>
                            <p>For your protection, never share this verification code with anyone. Our staff will never ask for your OTP code via phone or email. This code can only be used once and will automatically expire after 5 minutes.</p>
                        </div>
                    </div>
                    
                    <div class="footer">
                        <div class="footer-content">
                            <div class="footer-left">
                                <h5>Contact Information</h5>
                                <p>📍 Bayawan City, Negros Oriental</p>
                                <p>📞 +63 (32) 123-4567</p>
                                <p>✉️ ' . SMTP_FROM_EMAIL . '</p>
                            </div>
                            <div class="footer-right">
                                <h5>Business Hours</h5>
                                <p>Monday - Friday: 8:00 AM - 8:00 PM</p>
                                <p>Saturday - Sunday: 9:00 AM - 6:00 PM</p>
                                <p>24/7 Emergency Support Available</p>
                            </div>
                        </div>
                        
                        <div class="social-links">
                            <a href="#" title="Facebook">📘</a>
                            <a href="#" title="Instagram">📷</a>
                            <a href="#" title="Twitter">🐦</a>
                            <a href="#" title="Website">🌐</a>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <p class="copyright">
                            © ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved. | 
                            Privacy Policy | Terms of Service | 
                            This is an automated message. Please do not reply.
                        </p>
                    </div>
                </div>
            </body>
            </html>
        ';
        $mail->AltBody = 'Your OTP code is: ' . $otp . '. This code will expire in 5 minutes.';
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email sending failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Check if a user has already rated a specific item
 * 
 * @param string $serviceType - 'room', 'event', or 'food'
 * @param int $itemId - The ID of the booking, event booking, or food order
 * @param int|null $userId - User ID (defaults to current user)
 * @return bool - True if already rated, false otherwise
 */
function isItemRated($serviceType, $itemId, $userId = null) {
    if (!$userId) {
        $userId = getUserId();
    }
    
    if (!$userId || !$itemId) {
        return false;
    }
    
    $db = getDB();
    
    // Determine the ID column based on service type
    $idColumn = '';
    switch ($serviceType) {
        case 'room':
            $idColumn = 'booking_id';
            break;
        case 'event':
            $idColumn = 'event_booking_id';
            break;
        case 'food':
            $idColumn = 'food_order_id';
            break;
        default:
            return false;
    }
    
    $stmt = $db->prepare("
        SELECT rating_id FROM ratings 
        WHERE user_id = ? AND {$idColumn} = ?
    ");
    $stmt->execute([$userId, $itemId]);

    return (bool) $stmt->fetch();
}

// Check session timeout for logged-in users (must be at end after constants/functions are defined)
if (isLoggedIn()) {
    if (!checkSessionTimeout()) {
        // Session expired - redirect to login with timeout message
        if (!headers_sent()) {
            header("Location: " . SITE_URL . "/auth/login.php?timeout=1");
            exit;
        }
    }
}
