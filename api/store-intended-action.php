<?php
/**
 * Store Intended Action API
 * Stores the user's intended action in session for post-login redirect
 */

require_once __DIR__ . '/../includes/config.php';

// Set JSON content type
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$action = $input['action'] ?? null;
$redirectUrl = $input['redirect_url'] ?? null;

if ($action && $redirectUrl) {
    $_SESSION['intended_action'] = $action;
    $_SESSION['intended_redirect'] = $redirectUrl;
    $_SESSION['intended_action_timestamp'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Action stored successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing action or redirect URL'
    ]);
}
