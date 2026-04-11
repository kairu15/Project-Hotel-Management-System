<?php
require_once '../includes/config.php';

// Set active_status to 0 (offline) before logout
if (isLoggedIn()) {
    $db = getDB();
    $userId = getUserId();
    $stmt = $db->prepare("UPDATE users SET active_status = 0 WHERE user_id = ?");
    $stmt->execute([$userId]);
}

// Clear all session data
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    showAlert('You have been logged out successfully.', 'success');
}

// Destroy session
session_destroy();

// Redirect to home page
redirect('../index.php');
?>
