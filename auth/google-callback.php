<?php
/**
 * Google OAuth Callback Handler
 */

require_once __DIR__ . '/../includes/config.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    die('Authorization code not received from Google');
}

$code = $_GET['code'];
$db = getDB();

try {
    // Exchange code for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        die('Failed to get access token from Google');
    }
    
    $tokenData = json_decode($response, true);
    $accessToken = $tokenData['access_token'];
    
    // Get user info from Google
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($response, true);
    
    if (!isset($userInfo['email'])) {
        die('Failed to get user information from Google');
    }
    
    // Handle user login/registration
    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? 'Google User';
    $googleId = $userInfo['id'];
    $profilePic = $userInfo['picture'] ?? null;
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, google_id, profile_picture, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $name,
            $email,
            hash('sha256', 'social_login'), // Dummy password
            $googleId,
            $profilePic
        ]);
        
        $userId = $db->lastInsertId();
    } else {
        $userId = $user['user_id'];
        
        // Update google_id if not set
        if (empty($user['google_id'])) {
            $stmt = $db->prepare("UPDATE users SET google_id = ? WHERE user_id = ?");
            $stmt->execute([$googleId, $userId]);
        }
    }
    
    // Set session and redirect
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = empty($user) ? 'user' : $user['role'];

    // Split name into first and last name for header compatibility
    $nameParts = explode(' ', $name, 2);
    $_SESSION['first_name'] = $nameParts[0] ?? $name;
    $_SESSION['last_name'] = $nameParts[1] ?? '';

    // Update active_status to 1 (online)
    $updateStmt = $db->prepare("UPDATE users SET active_status = 1, last_login = NOW() WHERE user_id = ?");
    $updateStmt->execute([$userId]);

    // Create user session record for tracking
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours

    $sessionStmt = $db->prepare("INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, created_at, expires_at) VALUES (?, ?, ?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), user_agent = VALUES(user_agent), created_at = VALUES(created_at), expires_at = VALUES(expires_at)");
    $sessionStmt->execute([$sessionId, $userId, $ipAddress, $userAgent, $expiresAt]);

    // Check for intended redirect first (for booking actions)
    if (isset($_SESSION['intended_redirect']) && !empty($_SESSION['intended_redirect'])) {
        $redirectUrl = $_SESSION['intended_redirect'];
        unset($_SESSION['intended_action']);
        unset($_SESSION['intended_redirect']);
        unset($_SESSION['intended_action_timestamp']);
        header('Location: ' . SITE_URL . '/' . $redirectUrl);
        exit;
    }

    header('Location: ' . SITE_URL . '/index.php');
    exit;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
