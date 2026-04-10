<?php
/**
 * Facebook OAuth Callback Handler
 */

require_once __DIR__ . '/../includes/config.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    die('Authorization code not received from Facebook');
}

$code = $_GET['code'];
$db = getDB();

try {
    // Exchange code for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v18.0/oauth/access_token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $code
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        die('Failed to get access token from Facebook');
    }
    
    $accessToken = $tokenData['access_token'];
    
    // Get user info from Facebook
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=' . urlencode($accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($response, true);
    
    if (!isset($userInfo['email'])) {
        die('Failed to get user information from Facebook');
    }
    
    // Handle user login/registration
    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? 'Facebook User';
    $facebookId = $userInfo['id'];
    $profilePic = $userInfo['picture']['data']['url'] ?? null;
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, facebook_id, profile_picture, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $name,
            $email,
            hash('sha256', 'social_login'), // Dummy password
            $facebookId,
            $profilePic
        ]);
        
        $userId = $db->lastInsertId();
    } else {
        $userId = $user['user_id'];
        
        // Update facebook_id if not set
        if (empty($user['facebook_id'])) {
            $stmt = $db->prepare("UPDATE users SET facebook_id = ? WHERE user_id = ?");
            $stmt->execute([$facebookId, $userId]);
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
