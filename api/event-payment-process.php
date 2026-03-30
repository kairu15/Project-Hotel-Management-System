<?php
/**
 * Event Booking Payment Processing Endpoint
 * Handles all payment methods for event bookings: GCash, PayPal, Credit Card, Pay at Hotel
 */

// Start output buffering to prevent any stray output
ob_start();

require_once '../includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$paymentMethod = $input['payment_method'] ?? '';
$eventBookingId = (int)($input['event_booking_id'] ?? 0);
$paymentData = $input['payment_data'] ?? [];

// Validate required data
if (empty($paymentMethod) || empty($eventBookingId)) {
    echo json_encode(['success' => false, 'message' => 'Missing required payment information']);
    exit();
}

$db = getDB();
$userId = getUserId();

try {
    $db->beginTransaction();
    
    // Get event booking details and verify it belongs to the current user
    $bookingStmt = $db->prepare("
        SELECT eb.*, es.space_name 
        FROM event_bookings eb 
        JOIN event_spaces es ON eb.space_id = es.space_id 
        WHERE eb.event_booking_id = ? AND eb.user_id = ?
    ");
    $bookingStmt->execute([$eventBookingId, $userId]);
    $booking = $bookingStmt->fetch();
    
    if (!$booking) {
        throw new Exception('Event booking not found or unauthorized');
    }
    
    // Check if booking has a quoted price
    if (empty($booking['quoted_price']) || $booking['quoted_price'] <= 0) {
        throw new Exception('No quoted price available for this booking. Please contact the hotel for pricing.');
    }
    
    // Check if booking is in a payable status
    if (!in_array($booking['status'], ['pending', 'confirmed'])) {
        throw new Exception('This booking cannot be paid for at this time. Current status: ' . $booking['status']);
    }
    
    // Check if already fully paid
    if ($booking['payment_status'] === 'paid') {
        throw new Exception('This booking has already been fully paid.');
    }
    
    $totalAmount = (float)$booking['quoted_price'];
    $alreadyPaid = (float)($booking['amount_paid'] ?? 0);
    $remainingAmount = $totalAmount - $alreadyPaid;
    
    // Initialize payment variables
    $paymentStatus = 'pending';
    $transactionId = null;
    $amountPaid = 0;
    $receiptData = [];
    
    // Process based on payment method
    switch ($paymentMethod) {
        case 'gcash':
            $result = processGCashPayment($paymentData, $remainingAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        case 'paypal':
            $result = processPayPalPayment($paymentData, $remainingAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        case 'credit_card':
            $result = processCreditCardPayment($paymentData, $remainingAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        case 'pay_at_hotel':
            $result = processPayAtHotel($paymentData, $remainingAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        default:
            throw new Exception('Invalid payment method');
    }
    
    // Calculate new totals
    $newTotalPaid = $alreadyPaid + $amountPaid;
    $newPaymentStatus = $paymentStatus;
    $newBookingStatus = $booking['status'];
    
    // If fully paid, update booking status to confirmed and payment_status to paid
    if ($newTotalPaid >= $totalAmount) {
        $newPaymentStatus = 'paid';
        $newBookingStatus = 'confirmed';
    } elseif ($newTotalPaid > 0 && $newTotalPaid < $totalAmount) {
        $newPaymentStatus = 'partial';
    }
    
    // Update event booking
    $updateStmt = $db->prepare("
        UPDATE event_bookings 
        SET payment_status = ?, 
            payment_method = ?, 
            amount_paid = ?, 
            transaction_id = ?,
            status = ?,
            paid_at = CASE WHEN ? >= ? THEN NOW() ELSE paid_at END,
            updated_at = NOW()
        WHERE event_booking_id = ?
    ");
    $updateStmt->execute([
        $newPaymentStatus,
        $paymentMethod,
        $newTotalPaid,
        $transactionId,
        $newBookingStatus,
        $newTotalPaid,
        $totalAmount,
        $eventBookingId
    ]);
    
    // Create payment record in a new event_payments table or use existing payments table
    // Using existing payments table with event_booking_id reference
    $paymentStmt = $db->prepare("
        INSERT INTO payments 
        (event_booking_id, user_id, amount, payment_method, transaction_id, status, notes, payment_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $paymentStmt->execute([
        $eventBookingId,
        $userId,
        $amountPaid,
        $paymentMethod,
        $transactionId,
        $paymentStatus === 'paid' || $paymentStatus === 'partial' ? 'completed' : 'pending',
        $receiptData['notes'] ?? "Event booking payment via $paymentMethod"
    ]);
    $paymentId = $db->lastInsertId();
    
    $db->commit();
    
    // Send notifications
    require_once '../includes/notifications.php';
    require_once '../includes/email_notifications.php';
    
    // Get user details for notifications
    $userStmt = $db->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch();
    $guestName = $userData ? $userData['first_name'] . ' ' . $userData['last_name'] : 'Guest';
    $guestEmail = $userData ? $userData['email'] : '';
    
    // Send payment confirmation email
    if ($guestEmail && ($paymentStatus === 'paid' || $paymentStatus === 'partial')) {
        try {
            $paymentDetails = [
                'event_booking_id' => $eventBookingId,
                'space_name' => $booking['space_name'],
                'event_date' => $booking['event_date'],
                'quoted_price' => $totalAmount,
                'amount_paid' => $amountPaid,
                'total_paid' => $newTotalPaid,
                'remaining' => $totalAmount - $newTotalPaid,
                'payment_method' => $paymentMethod,
                'payment_status' => $newPaymentStatus,
                'transaction_id' => $transactionId,
                'guest_name' => $guestName
            ];
            sendEventPaymentConfirmationEmail($guestEmail, $paymentDetails);
        } catch (Exception $emailError) {
            error_log('Failed to send event payment confirmation email: ' . $emailError->getMessage());
        }
    }
    
    // Notify user about payment
    if ($paymentStatus === 'paid' || $paymentStatus === 'partial') {
        notifyPaymentUpdate($userId, $paymentId, 'completed', $amountPaid);
    } elseif ($paymentStatus === 'failed') {
        notifyPaymentUpdate($userId, $paymentId, 'failed', $remainingAmount);
    } else {
        notifyPaymentUpdate($userId, $paymentId, 'pending', $remainingAmount);
    }
    
    // Notify admin about payment
    $paymentProcessType = ($paymentStatus === 'paid' || $paymentStatus === 'partial') ? 'made' : 
                         ($paymentStatus === 'failed' ? 'failed' : 'pending');
    notifyAdminPaymentUpdate($paymentId, $paymentProcessType, $guestName, $amountPaid, $paymentMethod);
    
    // Clear any buffered output before sending JSON
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'event_booking_id' => $eventBookingId,
        'payment_status' => $newPaymentStatus,
        'booking_status' => $newBookingStatus,
        'transaction_id' => $transactionId,
        'amount_paid' => $amountPaid,
        'total_paid' => $newTotalPaid,
        'remaining_amount' => $totalAmount - $newTotalPaid,
        'receipt' => $receiptData,
        'redirect' => 'my-event-bookings.php'
    ]);
    exit();
    
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(400);
    
    // Clear any buffered output before sending JSON
    ob_clean();
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

/**
 * Process GCash Payment
 * Simulates OTP verification
 */
function processGCashPayment($data, $totalAmount) {
    $mobileNumber = $data['mobile_number'] ?? '';
    $accountName = $data['account_name'] ?? '';
    $referenceNote = $data['reference_note'] ?? '';
    
    // Validate required fields
    if (empty($mobileNumber) || empty($accountName)) {
        throw new Exception('Mobile number and account name are required');
    }
    
    // Validate mobile number format (Philippines)
    if (!preg_match('/^09\d{9}$/', $mobileNumber)) {
        throw new Exception('Invalid mobile number format. Use 09XXXXXXXXX');
    }
    
    // Simulate payment processing (90% success rate for demo)
    $isSuccess = (mt_rand(1, 100) <= 90);
    
    $transactionId = 'GCASH-' . strtoupper(substr(uniqid(), -6));
    
    if ($isSuccess) {
        return [
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'amount_paid' => $totalAmount,
            'mobile_number' => $mobileNumber,
            'account_name' => $accountName,
            'reference_note' => $referenceNote,
            'payment_method' => 'GCash',
            'message' => 'Payment successful via GCash',
            'notes' => "GCash payment from $accountName ($mobileNumber)"
        ];
    } else {
        return [
            'status' => 'pending',
            'transaction_id' => null,
            'amount_paid' => 0,
            'mobile_number' => $mobileNumber,
            'account_name' => $accountName,
            'reference_note' => $referenceNote,
            'payment_method' => 'GCash',
            'message' => 'Payment verification failed. Please try again.',
            'notes' => "GCash payment failed from $accountName ($mobileNumber)"
        ];
    }
}

/**
 * Process PayPal Payment
 * Simulates PayPal approval
 */
function processPayPalPayment($data, $totalAmount) {
    $email = $data['paypal_email'] ?? '';
    $password = $data['paypal_password'] ?? '';
    
    // Validate required fields
    if (empty($email) || empty($password)) {
        throw new Exception('PayPal email and password are required');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Simulate PayPal processing (85% success rate for demo)
    $isApproved = (mt_rand(1, 100) <= 85);
    
    if ($isApproved) {
        $transactionId = 'PAYPAL-' . strtoupper(substr(uniqid(), -6));
        return [
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'amount_paid' => $totalAmount,
            'paypal_email' => $email,
            'payment_method' => 'PayPal',
            'message' => 'Payment approved via PayPal',
            'notes' => "PayPal payment from $email"
        ];
    } else {
        return [
            'status' => 'pending',
            'transaction_id' => null,
            'amount_paid' => 0,
            'paypal_email' => $email,
            'payment_method' => 'PayPal',
            'message' => 'Payment cancelled or declined by PayPal',
            'notes' => "PayPal payment cancelled by $email"
        ];
    }
}

/**
 * Process Credit Card Payment
 * Simulates 3D Secure / OTP verification
 */
function processCreditCardPayment($data, $totalAmount) {
    $cardNumber = $data['card_number'] ?? '';
    $cardHolder = $data['card_holder'] ?? '';
    $expiryDate = $data['expiry_date'] ?? '';
    $cvv = $data['cvv'] ?? '';
    
    // Validate required fields
    if (empty($cardNumber) || empty($cardHolder) || empty($expiryDate) || empty($cvv)) {
        throw new Exception('All card details are required');
    }
    
    // Validate card number (16 digits)
    $cardNumber = preg_replace('/\s+/', '', $cardNumber);
    if (!preg_match('/^\d{16}$/', $cardNumber)) {
        throw new Exception('Card number must be 16 digits');
    }
    
    // Validate expiry date (MM/YY)
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) {
        throw new Exception('Invalid expiry date format. Use MM/YY');
    }
    
    // Validate CVV (3 digits)
    if (!preg_match('/^\d{3}$/', $cvv)) {
        throw new Exception('CVV must be 3 digits');
    }
    
    // Check if card is expired
    list($month, $year) = explode('/', $expiryDate);
    $expiryDateTime = DateTime::createFromFormat('y-m', $year . '-' . $month);
    $now = new DateTime();
    if ($expiryDateTime < $now) {
        throw new Exception('Card has expired');
    }
    
    // Simulate bank verification (88% success rate for demo)
    $isVerified = (mt_rand(1, 100) <= 88);
    
    // Mask card number for security
    $maskedCard = '**** **** **** ' . substr($cardNumber, -4);
    
    if ($isVerified) {
        $transactionId = 'CC-' . strtoupper(substr(uniqid(), -6));
        return [
            'status' => 'paid',
            'transaction_id' => $transactionId,
            'amount_paid' => $totalAmount,
            'card_number' => $maskedCard,
            'card_holder' => $cardHolder,
            'payment_method' => 'Credit Card',
            'message' => 'Payment verified and approved',
            'notes' => "Credit card payment from $cardHolder - $maskedCard"
        ];
    } else {
        return [
            'status' => 'failed',
            'transaction_id' => null,
            'amount_paid' => 0,
            'card_number' => $maskedCard,
            'card_holder' => $cardHolder,
            'payment_method' => 'Credit Card',
            'message' => 'Payment declined by bank. Please check your card details.',
            'notes' => "Credit card payment declined - $maskedCard"
        ];
    }
}

/**
 * Process Pay at Hotel
 * Allows partial payment
 */
function processPayAtHotel($data, $totalAmount) {
    $fullName = $data['full_name'] ?? '';
    $mobileNumber = $data['mobile_number'] ?? '';
    $email = $data['email'] ?? '';
    $arrivalTime = $data['arrival_time'] ?? '';
    $specialNotes = $data['special_notes'] ?? '';
    $paymentAmount = $data['payment_amount'] ?? 'full';
    $partialAmount = (float)($data['partial_amount'] ?? 0);
    
    // Validate required fields
    if (empty($fullName) || empty($mobileNumber) || empty($email)) {
        throw new Exception('Full name, mobile number, and email are required');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Calculate amount to pay
    if ($paymentAmount === 'partial' && $partialAmount > 0 && $partialAmount < $totalAmount) {
        $amountPaid = $partialAmount;
        $status = 'partial';
        $message = "Partial payment of ₱" . number_format($partialAmount, 2) . " recorded. Remaining balance: ₱" . number_format($totalAmount - $partialAmount, 2) . " to be paid at hotel.";
    } else {
        $amountPaid = $totalAmount;
        $status = 'paid';
        $message = "Full payment confirmed. No balance remaining.";
    }
    
    $transactionId = 'HOTEL-' . strtoupper(substr(uniqid(), -6));
    
    $notes = "Pay at Hotel - $fullName ($mobileNumber)";
    if ($arrivalTime) {
        $notes .= " | Arrival: $arrivalTime";
    }
    if ($specialNotes) {
        $notes .= " | Notes: $specialNotes";
    }
    
    return [
        'status' => $status,
        'transaction_id' => $transactionId,
        'amount_paid' => $amountPaid,
        'total_amount' => $totalAmount,
        'remaining_amount' => $totalAmount - $amountPaid,
        'full_name' => $fullName,
        'mobile_number' => $mobileNumber,
        'email' => $email,
        'arrival_time' => $arrivalTime,
        'special_notes' => $specialNotes,
        'payment_method' => 'Pay at Hotel',
        'message' => $message,
        'notes' => $notes
    ];
}

/**
 * Send event payment confirmation email
 */
function sendEventPaymentConfirmationEmail($email, $paymentDetails) {
    // This is a placeholder - implement based on your existing email system
    // You can use the existing sendBookingConfirmationEmail as reference
    return true;
}
