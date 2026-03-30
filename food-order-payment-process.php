<?php
/**
 * Food Order Payment Processing Endpoint
 * Handles payment processing for food orders
 */

require_once 'includes/config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to place an order']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$orderData = $input['order_data'] ?? [];
$paymentMethod = $input['payment_method'] ?? 'pay_at_hotel';
$paymentData = $input['payment_data'] ?? [];

$userId = $_SESSION['user_id'] ?? 0;

// Validate required fields - support both food_id and item_id for compatibility
$foodId = $orderData['food_id'] ?? $orderData['item_id'] ?? null;
$quantity = intval($orderData['quantity'] ?? 0);

if (empty($foodId) || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Missing required order information']);
    exit;
}

$db = getDB();

try {
    // Start transaction for stock consistency
    $db->beginTransaction();
    
    // Get item details from foods table with stock
    $itemStmt = $db->prepare("SELECT food_id, food_name, price, stock_quantity FROM foods WHERE food_id = ? AND is_available = 1");
    $itemStmt->execute([$foodId]);
    $item = $itemStmt->fetch();
    
    if (!$item) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Food item not found or unavailable']);
        exit;
    }
    
    // Check stock availability
    if ($item['stock_quantity'] < $quantity) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $item['stock_quantity'] . ' items available.']);
        exit;
    }
    
    $unitPrice = $item['price'];
    $totalPrice = $unitPrice * $quantity;
    
    // Determine payment status based on method
    $paymentStatus = 'pending';
    if (in_array($paymentMethod, ['gcash', 'paypal', 'credit_card'])) {
        $paymentStatus = 'paid';
    }
    
    // Insert food order
    $insertStmt = $db->prepare("
        INSERT INTO food_orders 
        (user_id, booking_id, food_id, quantity, unit_price, total_price, status, order_type, 
         payment_method, payment_status, room_number, special_instructions, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW())
    ");
    
    $bookingId = !empty($orderData['booking_id']) ? $orderData['booking_id'] : null;
    $roomNumber = !empty($orderData['room_number']) ? $orderData['room_number'] : null;
    $specialInstructions = $orderData['special_instructions'] ?? '';
    
    $insertStmt->execute([
        $userId,
        $bookingId,
        $foodId,
        $quantity,
        $unitPrice,
        $totalPrice,
        $orderData['order_type'] ?? 'dine_in',
        $paymentMethod,
        $paymentStatus,
        $roomNumber,
        $specialInstructions
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Decrease stock quantity
    $updateStockStmt = $db->prepare("UPDATE foods SET stock_quantity = stock_quantity - ? WHERE food_id = ?");
    $updateStockStmt->execute([$quantity, $foodId]);
    
    // Commit transaction
    $db->commit();
    
    // Generate Order Reference (FODYYYYMMDDXXXXXX) and update
    $orderRef = 'FOD' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
    $refStmt = $db->prepare("UPDATE food_orders SET order_ref = ? WHERE order_id = ?");
    $refStmt->execute([$orderRef, $orderId]);
    
    // Generate transaction reference (different from order_ref)
    $transactionRef = 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    
    // Send food order confirmation email
    require_once 'includes/email_notifications.php';
    
    // Get user email
    $userStmt = $db->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch();
    
    if ($userData) {
        // Calculate estimated time based on order type
        $estimatedTime = '20-30 minutes';
        if (($orderData['order_type'] ?? 'dine_in') === 'room_service') {
            $estimatedTime = '30-45 minutes';
        } elseif (($orderData['order_type'] ?? 'dine_in') === 'takeaway') {
            $estimatedTime = '15-25 minutes';
        }
        
        $emailData = [
            'order_id' => $orderId,
            'order_ref' => $orderRef,
            'transaction_ref' => $transactionRef,
            'item_name' => $item['food_name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'order_type' => $orderData['order_type'] ?? 'dine_in',
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'room_number' => $roomNumber,
            'special_instructions' => $specialInstructions,
            'estimated_time' => $estimatedTime
        ];
        sendFoodOrderConfirmationEmail($userData['email'], $emailData);
    }
    
    // Log activity
    logActivity('Food Order Placed', "Order ID: $orderId, User: $userId, Amount: $totalPrice, Method: $paymentMethod");
    
    // Prepare receipt data
    $receipt = [
        'order_id' => $orderId,
        'item_name' => $item['food_name'],
        'quantity' => $quantity,
        'total_amount' => $totalPrice,
        'payment_method' => strtoupper(str_replace('_', ' ', $paymentMethod)),
        'transaction_ref' => $transactionRef,
        'order_type' => $orderData['order_type'] ?? 'dine_in'
    ];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'payment_status' => $paymentStatus,
        'order_id' => $orderId,
        'transaction_id' => $transactionRef,
        'receipt' => $receipt,
        'message' => $paymentStatus === 'paid' ? 'Payment successful' : 'Order placed - payment pending'
    ]);
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Food order payment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error processing order. Please try again.']);
    exit;
}
