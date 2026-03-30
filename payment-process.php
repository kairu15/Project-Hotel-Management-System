<?php
/**
 * Payment Processing Endpoint
 * Handles all payment methods: GCash, PayPal, Credit Card, Pay at Hotel
 */

// Start output buffering to prevent any stray output
ob_start();

require_once 'includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$paymentMethod = $input['payment_method'] ?? '';
$bookingData = $input['booking_data'] ?? [];
$paymentData = $input['payment_data'] ?? [];

// Validate required booking data
if (empty($bookingData['check_in']) || empty($bookingData['check_out']) || empty($bookingData['room_category'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking information']);
    exit();
}

$db = getDB();

try {
    $db->beginTransaction();
    
    // Extract booking data
    $checkIn = $bookingData['check_in'];
    $checkOut = $bookingData['check_out'];
    $categoryId = $bookingData['room_category'];
    $adults = (int)($bookingData['adults'] ?? 1);
    $children = (int)($bookingData['children'] ?? 0);
    $specialRequests = sanitizeInput($bookingData['special_requests'] ?? '');
    $promoCode = sanitizeInput($bookingData['promo_code'] ?? '');
    
    // Guest info
    $guestFirstName = sanitizeInput($bookingData['guest_first_name'] ?? '');
    $guestLastName = sanitizeInput($bookingData['guest_last_name'] ?? '');
    $guestEmail = sanitizeInput($bookingData['guest_email'] ?? '');
    $guestPhone = sanitizeInput($bookingData['guest_phone'] ?? '');
    
    // Calculate nights and amount
    $nights = calculateNights($checkIn, $checkOut);
    
    // Get room category details
    $categoryStmt = $db->prepare("SELECT * FROM room_categories WHERE category_id = ? AND status = 'active'");
    $categoryStmt->execute([$categoryId]);
    $category = $categoryStmt->fetch();
    
    if (!$category) {
        throw new Exception('Selected room type is not available');
    }
    
    // Check availability
    $availableRooms = checkAvailability($checkIn, $checkOut, $categoryId);
    
    if (count($availableRooms) === 0) {
        throw new Exception('No rooms available for the selected dates');
    }
    
    $room = $availableRooms[0];
    $roomRate = $category['base_price'];
    $totalAmount = $roomRate * $nights;
    
    // Get or create user
    if (!isLoggedIn()) {
        $userStmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $userStmt->execute([$guestEmail]);
        $existingUser = $userStmt->fetch();
        
        if ($existingUser) {
            $userId = $existingUser['user_id'];
        } else {
            $createUserStmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, phone, role) VALUES (?, ?, ?, ?, ?, 'guest')");
            $tempPassword = password_hash(uniqid(), PASSWORD_DEFAULT);
            $createUserStmt->execute([$guestEmail, $tempPassword, $guestFirstName, $guestLastName, $guestPhone]);
            $userId = $db->lastInsertId();
        }
    } else {
        $userId = getUserId();
    }
    
    // Initialize payment variables
    $paymentStatus = 'pending';
    $transactionId = null;
    $amountPaid = 0;
    $receiptData = [];
    
    // Process based on payment method
    switch ($paymentMethod) {
        case 'gcash':
            $result = processGCashPayment($paymentData, $totalAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        case 'paypal':
            $result = processPayPalPayment($paymentData, $totalAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        case 'credit_card':
            $result = processCreditCardPayment($paymentData, $totalAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        case 'pay_at_hotel':
            $result = processPayAtHotel($paymentData, $totalAmount);
            $paymentStatus = $result['status'];
            $transactionId = $result['transaction_id'];
            $amountPaid = $result['amount_paid'];
            $receiptData = $result;
            break;
            
        default:
            throw new Exception('Invalid payment method');
    }
    
    // Create booking
    $bookingRef = generateBookingRef();
    $bookingStmt = $db->prepare("INSERT INTO bookings 
        (user_id, room_id, category_id, check_in, check_out, adults, children, nights, room_rate, total_amount, special_requests, booking_source, status, payment_status, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'website', 'pending', ?, ?)");
    $bookingStmt->execute([
        $userId, 
        $room['room_id'], 
        $categoryId, 
        $checkIn, 
        $checkOut, 
        $adults, 
        $children, 
        $nights, 
        $roomRate, 
        $totalAmount, 
        $specialRequests, 
        $paymentStatus,
        $paymentMethod
    ]);
    $bookingId = $db->lastInsertId();
    
    // Create payment record
    $paymentStmt = $db->prepare("INSERT INTO payments 
        (booking_id, user_id, amount, payment_method, transaction_id, status, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $paymentStmt->execute([
        $bookingId,
        $userId,
        $amountPaid,
        $paymentMethod,
        $transactionId,
        $paymentStatus === 'paid' || $paymentStatus === 'partial' ? 'completed' : 'pending',
        $receiptData['notes'] ?? ''
    ]);
    
    // Update room status if fully paid
    if ($paymentStatus === 'paid') {
        $updateRoomStmt = $db->prepare("UPDATE rooms SET status = 'reserved' WHERE room_id = ?");
        $updateRoomStmt->execute([$room['room_id']]);
    }
    
    $db->commit();
    
    // Send notifications
    require_once 'includes/notifications.php';
    require_once 'includes/email_notifications.php';
    
    // Get user details for notifications
    $userStmt = $db->prepare("SELECT first_name, last_name, email FROM bookings b JOIN users u ON b.user_id = u.user_id WHERE b.booking_id = ?");
    $userStmt->execute([$bookingId]);
    $userData = $userStmt->fetch();
    $guestName = $userData ? $userData['first_name'] . ' ' . $userData['last_name'] : 'Guest';
    $guestEmail = $userData ? $userData['email'] : $guestEmail;
    $checkInDate = $userData ? $userData['check_in'] : '';
    
    // Send booking confirmation email to user
    if ($guestEmail) {
        try {
            $bookingData = [
                'booking_ref' => $bookingRef,
                'room_type' => $category['category_name'],
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'guests' => $adults + $children,
                'total_amount' => $totalAmount,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'guest_name' => $guestName
            ];
            sendBookingConfirmationEmail($guestEmail, $bookingData);
        } catch (Exception $emailError) {
            error_log('Failed to send booking confirmation email: ' . $emailError->getMessage());
        }
    }
    
    // Notify user about their booking
    notifyBookingUpdate($userId, $bookingId, 'pending');
    
    // Notify user about payment status
    if ($paymentStatus === 'paid' || $paymentStatus === 'partial') {
        notifyPaymentUpdate($userId, $paymentId ?? $bookingId, 'completed', $amountPaid);
    } elseif ($paymentStatus === 'failed') {
        notifyPaymentUpdate($userId, $paymentId ?? $bookingId, 'failed', $totalAmount);
    } else {
        notifyPaymentUpdate($userId, $paymentId ?? $bookingId, 'pending', $totalAmount);
    }
    
    // Notify staff about new booking
    if ($userData) {
        notifyStaffNewBooking($bookingId, $guestName, $checkInDate);
    }
    
    // Notify admin about payment
    $paymentProcessType = ($paymentStatus === 'paid' || $paymentStatus === 'partial') ? 'made' : 
                         ($paymentStatus === 'failed' ? 'failed' : 'pending');
    notifyAdminPaymentUpdate($paymentId ?? $bookingId, $paymentProcessType, $guestName, $amountPaid, $paymentMethod);
    
    // Notify admin about new booking
    notifyAdminBookingUpdate($bookingId, 'created', $guestName, "Check-in: " . date('M d, Y', strtotime($checkInDate ?: 'today')));
    
    // Store booking info in session
    $_SESSION['booking_confirmation'] = [
        'booking_id' => $bookingId,
        'booking_ref' => $bookingRef,
        'room_name' => $category['category_name'],
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'nights' => $nights,
        'guests' => $adults + $children,
        'total' => $totalAmount,
        'amount_paid' => $amountPaid,
        'remaining_amount' => $totalAmount - $amountPaid,
        'payment_method' => $paymentMethod,
        'payment_status' => $paymentStatus,
        'transaction_id' => $transactionId,
        'receipt_data' => $receiptData
    ];
    
    // Log activity
    logActivity('Payment processed', "Booking ID: $bookingId, Method: $paymentMethod, Status: $paymentStatus");
    
    // Clear any buffered output before sending JSON
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'booking_id' => $bookingId,
        'booking_ref' => $bookingRef,
        'payment_status' => $paymentStatus,
        'transaction_id' => $transactionId,
        'amount_paid' => $amountPaid,
        'remaining_amount' => $totalAmount - $amountPaid,
        'receipt' => $receiptData,
        'redirect' => 'booking-confirmation.php'
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
            'notes' => "GCash payment failed from $accountName ($mobile_number)"
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
        $transactionId = 'PAY-' . strtoupper(substr(uniqid(), -6));
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
