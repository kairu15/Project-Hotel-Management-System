<?php
require_once '../includes/config.php';

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
