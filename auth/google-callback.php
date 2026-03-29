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
    
    header('Location: ' . SITE_URL . '/index.php');
    exit;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
