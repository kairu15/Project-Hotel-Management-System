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
 * Generate bot response using Google Gemini AI
 */
function generateBotResponse($db, $message, $userId) {
    $messageLower = strtolower($message);
    
    // Check for specific contextual queries first (bookings, orders, loyalty, time)
    $contextResponse = checkContextualQueries($db, $messageLower, $userId);
    if ($contextResponse) {
        return $contextResponse;
    }
    
    // Use Groq AI for general queries
    try {
        $groqResponse = callGroqAPI($message);
        if ($groqResponse) {
            return [
                'message' => $groqResponse,
                'intent' => 'groq_ai'
            ];
        }
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log("Groq API Error: " . $errorMsg);
        
        // Return error details for debugging (remove in production)
        return [
            'message' => "[Debug Mode] I couldn't process your question due to: " . $errorMsg . "\n\nPlease check your Groq API key in config.php or try asking about your bookings, orders, or loyalty points which don't require AI.",
            'intent' => 'api_error'
        ];
    }
    
    return [
        'message' => "I'm sorry, I couldn't generate a response at this time. Please try asking about:\n• Your bookings (type 'my bookings')\n• Your food orders (type 'my orders')\n• Your loyalty points (type 'my points')",
        'intent' => 'fallback'
    ];
}

/**
 * Call Groq API
 */
function callGroqAPI($userMessage) {
    $apiKey = GROQ_API_KEY;
    
    // Check if API key is set
    if ($apiKey === 'gsk_YOUR_GROQ_API_KEY_HERE' || empty($apiKey)) {
        throw new Exception("Groq API key not configured. Set GROQ_API_KEY in config.php");
    }
    
    // Build the system prompt with hotel context
    $systemPrompt = "You are a helpful hotel assistant for Bayawan Bai Hotel. You are knowledgeable about:
- Room types, rates, and availability
- Hotel amenities and facilities
- Restaurant and dining services
- Event spaces and hosting
- Booking and check-in procedures
- Hotel policies and guidelines
- Local attractions and information

This system was developed by Kylle Ian D. Acibron (Contact: kylleacibron@gmail.com).

Be friendly, professional, and concise in your responses. Keep replies under 200 words. If asked about something outside your scope, politely redirect to appropriate hotel services.";
    
    // Prepare API request
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    
    $payload = [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userMessage
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1024
    ];
    
    // Make API call
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Groq API cURL Error: " . $curlError);
        throw new Exception("cURL Error: " . $curlError);
    }
    
    if ($httpCode !== 200) {
        error_log("Groq API Error: HTTP $httpCode - URL: $url - Response: $response");
        throw new Exception("Groq API returned HTTP $httpCode - Response: " . substr($response, 0, 500));
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        error_log("Groq API Error: " . json_encode($result['error']));
        throw new Exception("Groq API error: " . ($result['error']['message'] ?? 'Unknown error'));
    }
    
    // Extract the text response
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }
    
    // Log unexpected response format for debugging
    error_log("Groq API unexpected response: " . json_encode($result));
    throw new Exception("Invalid response from Groq API");
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
