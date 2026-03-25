<?php
/**
 * Chatbot API Endpoint
 * Handles chat messages and returns bot responses
 */

require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Only allow logged-in users to use chatbot
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please log in to use the chatbot.']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = getUserId();
$db = getDB();

switch ($action) {
    case 'send_message':
        handleSendMessage($db, $userId);
        break;
    case 'get_history':
        handleGetHistory($db, $userId);
        break;
    case 'get_session':
        handleGetSession($db, $userId);
        break;
    case 'close_session':
        handleCloseSession($db, $userId);
        break;
    case 'mark_read':
        handleMarkRead($db, $userId);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Handle sending a new message
 */
function handleSendMessage($db, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    $sessionToken = $input['session_token'] ?? null;
    
    if (empty($message)) {
        echo json_encode(['error' => 'Message cannot be empty']);
        return;
    }
    
    // Get or create session
    $session = getOrCreateSession($db, $userId, $sessionToken);
    
    // Save user message
    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, user_id, message_type, message) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$session['session_id'], $userId, $message]);
    
    // Generate bot response
    $botResponse = generateBotResponse($db, $message, $userId);
    
    // Save bot response
    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, message_type, message, intent) VALUES (?, 'bot', ?, ?)");
    $stmt->execute([$session['session_id'], $botResponse['message'], $botResponse['intent']]);
    
    // Update session last message time
    $stmt = $db->prepare("UPDATE chat_sessions SET last_message_at = NOW() WHERE session_id = ?");
    $stmt->execute([$session['session_id']]);
    
    echo json_encode([
        'success' => true,
        'user_message' => $message,
        'bot_response' => $botResponse['message'],
        'intent' => $botResponse['intent'],
        'session_token' => $session['session_token'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Get or create chat session
 */
function getOrCreateSession($db, $userId, $sessionToken = null) {
    // Try to find existing active session
    if ($sessionToken) {
        $stmt = $db->prepare("SELECT * FROM chat_sessions WHERE session_token = ? AND user_id = ? AND status = 'active'");
        $stmt->execute([$sessionToken, $userId]);
        $session = $stmt->fetch();
        if ($session) {
            return $session;
        }
    }
    
    // Check for any recent active session
    $stmt = $db->prepare("SELECT * FROM chat_sessions WHERE user_id = ? AND status = 'active' ORDER BY last_message_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $session = $stmt->fetch();
    if ($session) {
        return $session;
    }
    
    // Create new session
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("INSERT INTO chat_sessions (user_id, session_token, status) VALUES (?, ?, 'active')");
    $stmt->execute([$userId, $token]);
    
    return [
        'session_id' => $db->lastInsertId(),
        'session_token' => $token,
        'user_id' => $userId,
        'status' => 'active'
    ];
}

/**
 * Generate bot response based on user message
 */
function generateBotResponse($db, $message, $userId) {
    $messageLower = strtolower($message);
    
    // Get all active knowledge base entries
    $stmt = $db->prepare("SELECT * FROM chatbot_knowledge WHERE is_active = 1 ORDER BY priority DESC");
    $stmt->execute();
    $knowledge = $stmt->fetchAll();
    
    // Try to match patterns
    foreach ($knowledge as $entry) {
        $pattern = '/' . $entry['question_pattern'] . '/i';
        if (preg_match($pattern, $messageLower)) {
            return [
                'message' => $entry['answer'],
                'intent' => $entry['category']
            ];
        }
    }
    
    // Check for specific contextual queries
    $contextResponse = checkContextualQueries($db, $messageLower, $userId);
    if ($contextResponse) {
        return $contextResponse;
    }
    
    // Fallback responses
    $fallbacks = [
        "I'm not sure I understand. Could you rephrase that? I can help with bookings, room information, dining, amenities, and general hotel inquiries.",
        "I didn't quite catch that. Try asking about our rooms, dining options, amenities, or how to make a booking!",
        "Hmm, I'm not familiar with that request. I can assist you with:\n• Room bookings and reservations\n• Room types and amenities\n• Dining options\n• Event spaces\n• Hotel policies\n• Contact information",
        "I'm still learning! For now, I can help with common hotel questions. What would you like to know about our services?"
    ];
    
    return [
        'message' => $fallbacks[array_rand($fallbacks)],
        'intent' => 'unknown'
    ];
}

/**
 * Check for contextual queries (user-specific data)
 */
function checkContextualQueries($db, $message, $userId) {
    // Check for booking-related queries
    if (preg_match('/my.*booking|my.*reservation|show.*booking/', $message)) {
        $stmt = $db->prepare("
            SELECT b.*, rc.category_name 
            FROM bookings b 
            JOIN room_categories rc ON b.category_id = rc.category_id 
            WHERE b.user_id = ? AND b.status IN ('confirmed', 'checked_in', 'reserved')
            ORDER BY b.check_in DESC LIMIT 3
        ");
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll();
        
        if (empty($bookings)) {
            return [
                'message' => "You don't have any active bookings at the moment. Would you like to make a reservation?",
                'intent' => 'my_bookings_none'
            ];
        }
        
        $response = "Here are your current bookings:\n\n";
        foreach ($bookings as $booking) {
            $response .= "📅 Booking #{$booking['booking_ref']}\n";
            $response .= "🏨 {$booking['category_name']}\n";
            $response .= "📆 Check-in: " . date('M d, Y', strtotime($booking['check_in'])) . "\n";
            $response .= "📆 Check-out: " . date('M d, Y', strtotime($booking['check_out'])) . "\n";
            $response .= "📊 Status: " . ucfirst($booking['status']) . "\n\n";
        }
        $response .= "View all your bookings in the My Bookings section of your dashboard.";
        
        return [
            'message' => $response,
            'intent' => 'my_bookings'
        ];
    }
    
    // Check for food order queries
    if (preg_match('/my.*order|food.*order|show.*order/', $message)) {
        $stmt = $db->prepare("
            SELECT fo.*, mi.item_name 
            FROM food_orders fo 
            JOIN menu_items mi ON fo.food_id = mi.item_id 
            WHERE fo.user_id = ? AND fo.status IN ('pending', 'preparing', 'ready')
            ORDER BY fo.created_at DESC LIMIT 3
        ");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();
        
        if (empty($orders)) {
            return [
                'message' => "You don't have any active food orders. Would you like to order something from our restaurant?",
                'intent' => 'my_orders_none'
            ];
        }
        
        $response = "Here are your current food orders:\n\n";
        foreach ($orders as $order) {
            $response .= "🍽️ Order #{$order['order_id']}\n";
            $response .= "🍴 {$order['item_name']} x{$order['quantity']}\n";
            $response .= "📊 Status: " . ucfirst($order['status']) . "\n";
            $response .= "💰 Total: ₱" . number_format($order['total_price'], 2) . "\n\n";
        }
        
        return [
            'message' => $response,
            'intent' => 'my_orders'
        ];
    }
    
    // Check for loyalty points
    if (preg_match('/point|loyalty|reward/', $message)) {
        $stmt = $db->prepare("SELECT loyalty_points FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $points = $user['loyalty_points'] ?? 0;
        
        return [
            'message' => "You currently have **" . number_format($points) . "** loyalty points! 🎉\n\nEarn more points with every booking and redeem them for discounts on future stays. Visit your profile to see your rewards history.",
            'intent' => 'loyalty_points'
        ];
    }
    
    // Check for time/date
    if (preg_match('/what.*time|current.*time|what.*day|what.*date/', $message)) {
        return [
            'message' => "The current time is " . date('h:i A') . " on " . date('l, F j, Y') . ". Our front desk is available 24/7 for any assistance you need!",
            'intent' => 'time_query'
        ];
    }
    
    return null;
}

/**
 * Get chat history
 */
function handleGetHistory($db, $userId) {
    $sessionToken = $_GET['session_token'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    
    if (!$sessionToken) {
        echo json_encode(['messages' => []]);
        return;
    }
    
    $stmt = $db->prepare("
        SELECT cm.* FROM chat_messages cm
        JOIN chat_sessions cs ON cm.session_id = cs.session_id
        WHERE cs.session_token = ? AND cs.user_id = ?
        ORDER BY cm.created_at ASC
        LIMIT ?
    ");
    $stmt->execute([$sessionToken, $userId, $limit]);
    $messages = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'session_token' => $sessionToken
    ]);
}

/**
 * Get or create session
 */
function handleGetSession($db, $userId) {
    $session = getOrCreateSession($db, $userId);
    
    // Get unread count
    $stmt = $db->prepare("
        SELECT COUNT(*) as unread_count 
        FROM chat_messages cm
        JOIN chat_sessions cs ON cm.session_id = cs.session_id
        WHERE cs.user_id = ? AND cm.message_type = 'bot' AND cm.is_read = 0
    ");
    $stmt->execute([$userId]);
    $unread = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'session_token' => $session['session_token'],
        'unread_count' => $unread['unread_count'] ?? 0
    ]);
}

/**
 * Close chat session
 */
function handleCloseSession($db, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionToken = $input['session_token'] ?? null;
    
    if (!$sessionToken) {
        echo json_encode(['error' => 'Session token required']);
        return;
    }
    
    $stmt = $db->prepare("UPDATE chat_sessions SET status = 'closed' WHERE session_token = ? AND user_id = ?");
    $stmt->execute([$sessionToken, $userId]);
    
    echo json_encode(['success' => true, 'message' => 'Session closed']);
}

/**
 * Mark messages as read
 */
function handleMarkRead($db, $userId) {
    $stmt = $db->prepare("
        UPDATE chat_messages cm
        JOIN chat_sessions cs ON cm.session_id = cs.session_id
        SET cm.is_read = 1
        WHERE cs.user_id = ? AND cm.message_type = 'bot' AND cm.is_read = 0
    ");
    $stmt->execute([$userId]);
    
    echo json_encode(['success' => true]);
}
