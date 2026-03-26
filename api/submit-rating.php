<?php
/**
 * API Endpoint for Submitting Ratings
 * Bayawan Bai Hotel
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$action = $input['action'] ?? '';
$userId = getUserId();
$db = getDB();

try {
    if ($action === 'submit') {
        // Validate required fields
        $serviceType = $input['service_type'] ?? '';
        $itemId = intval($input['item_id'] ?? 0);
        $rating = intval($input['rating'] ?? 0);
        $comment = sanitizeInput($input['comment'] ?? '');
        
        if (!in_array($serviceType, ['room', 'event', 'food'])) {
            throw new Exception('Invalid service type');
        }
        
        if ($itemId <= 0) {
            throw new Exception('Invalid item ID');
        }
        
        if ($rating < 1 || $rating > 5) {
            throw new Exception('Rating must be between 1 and 5');
        }
        
        // Determine the column names based on service type
        $idColumn = '';
        $valueColumn = '';
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
        }
        
        // Check if already rated
        $checkStmt = $db->prepare("
            SELECT rating_id FROM ratings 
            WHERE user_id = ? AND {$idColumn} = ?
        ");
        $checkStmt->execute([$userId, $itemId]);
        
        if ($checkStmt->fetch()) {
            throw new Exception('You have already rated this item');
        }
        
        // Verify the user owns this item and it's in the correct status
        $verifyStmt = null;
        switch ($serviceType) {
            case 'room':
                $verifyStmt = $db->prepare("
                    SELECT 1 FROM bookings 
                    WHERE booking_id = ? AND user_id = ? AND status = 'checked_out'
                ");
                break;
            case 'event':
                $verifyStmt = $db->prepare("
                    SELECT 1 FROM event_bookings 
                    WHERE event_booking_id = ? AND user_id = ? AND status = 'completed'
                ");
                break;
            case 'food':
                $verifyStmt = $db->prepare("
                    SELECT 1 FROM food_orders 
                    WHERE order_id = ? AND user_id = ? AND status = 'delivered'
                ");
                break;
        }
        
        $verifyStmt->execute([$itemId, $userId]);
        if (!$verifyStmt->fetch()) {
            throw new Exception('Item not found or not eligible for rating');
        }
        
        // Insert the rating
        $insertData = [
            'user_id' => $userId,
            'service_type' => $serviceType,
            'rating_value' => $rating,
            'comment' => $comment,
            $idColumn => $itemId
        ];
        
        $columns = implode(', ', array_keys($insertData));
        $placeholders = implode(', ', array_fill(0, count($insertData), '?'));
        
        $insertStmt = $db->prepare("INSERT INTO ratings ({$columns}) VALUES ({$placeholders})");
        $insertStmt->execute(array_values($insertData));
        
        // Update eligibility status
        $updateEligibility = $db->prepare("
            UPDATE rating_eligibility 
            SET status = 'completed', completed_at = NOW()
            WHERE user_id = ? AND {$idColumn} = ?
        ");
        $updateEligibility->execute([$userId, $itemId]);
        
        // Award loyalty points for rating (optional feature)
        $loyaltyStmt = $db->prepare("
            UPDATE users 
            SET loyalty_points = loyalty_points + 5 
            WHERE user_id = ?
        ");
        $loyaltyStmt->execute([$userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your rating!',
            'points_earned' => 5
        ]);
        
    } elseif ($action === 'skip') {
        $serviceType = $input['service_type'] ?? '';
        $itemId = intval($input['item_id'] ?? 0);
        
        if (!in_array($serviceType, ['room', 'event', 'food'])) {
            throw new Exception('Invalid service type');
        }
        
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
        }
        
        // Mark as skipped
        $skipStmt = $db->prepare("
            UPDATE rating_eligibility 
            SET status = 'skipped'
            WHERE user_id = ? AND {$idColumn} = ?
        ");
        $skipStmt->execute([$userId, $itemId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Rating skipped'
        ]);
        
    } elseif ($action === 'check') {
        // Check if user has any pending ratings
        $checkStmt = $db->prepare("
            SELECT COUNT(*) FROM rating_eligibility 
            WHERE user_id = ? AND status = 'pending'
        ");
        $checkStmt->execute([$userId]);
        $pendingCount = $checkStmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'has_pending' => $pendingCount > 0,
            'pending_count' => $pendingCount
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
