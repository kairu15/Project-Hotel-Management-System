<?php
$pageTitle = 'Walk-in Booking';
require_once '../includes/config.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$userRole = getUserRole();

// Get room categories for dropdown
$categories = $db->query("SELECT * FROM room_categories WHERE status = 'active' ORDER BY category_name")->fetchAll();

// Get available rooms
$availableRooms = $db->query("
    SELECT r.*, rc.category_name 
    FROM rooms r 
    JOIN room_categories rc ON r.category_id = rc.category_id 
    WHERE r.status = 'available'
    ORDER BY r.room_number
")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roomId = $_POST['room_id'] ?? null;
    $categoryId = $_POST['category_id'] ?? null;
    $checkIn = $_POST['check_in'] ?? '';
    $checkOut = $_POST['check_out'] ?? '';
    $adults = intval($_POST['adults'] ?? 1);
    $children = intval($_POST['children'] ?? 0);
    $specialRequests = trim($_POST['special_requests'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $paidAmount = floatval($_POST['paid_amount'] ?? 0);
    
    // Convert empty room_id to NULL
    $roomId = empty($roomId) ? null : $roomId;

    // Validation
    $errors = [];
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (empty($checkIn)) $errors[] = 'Check-in date is required';
    if (empty($checkOut)) $errors[] = 'Check-out date is required';
    if (empty($categoryId)) $errors[] = 'Room category is required';
    if (strtotime($checkOut) <= strtotime($checkIn)) $errors[] = 'Check-out must be after check-in';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Check if user exists, if not create one
            $userStmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
            $userStmt->execute([$email]);
            $user = $userStmt->fetch();

            if ($user) {
                $userId = $user['user_id'];
                // Update user info
                $updateStmt = $db->prepare("
                    UPDATE users 
                    SET first_name = ?, last_name = ?, phone = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ?
                ");
                $updateStmt->execute([$firstName, $lastName, $phone, $userId]);
            } else {
                // Create new user
                $password = bin2hex(random_bytes(4)); // Random 8-char password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                
                $insertStmt = $db->prepare("
                    INSERT INTO users (first_name, last_name, email, phone, password, role, created_at)
                    VALUES (?, ?, ?, ?, ?, 'guest', CURRENT_TIMESTAMP)
                ");
                $insertStmt->execute([$firstName, $lastName, $email, $phone, $hash]);
                $userId = $db->lastInsertId();
            }

            // Get category price
            $priceStmt = $db->prepare("SELECT base_price FROM room_categories WHERE category_id = ?");
            $priceStmt->execute([$categoryId]);
            $basePrice = $priceStmt->fetchColumn();

            // Calculate nights and total
            $checkInDate = new DateTime($checkIn);
            $checkOutDate = new DateTime($checkOut);
            $nights = $checkInDate->diff($checkOutDate)->days;
            $totalAmount = $basePrice * $nights;

            // Create booking
            $bookingStmt = $db->prepare("
                INSERT INTO bookings 
                (user_id, room_id, category_id, check_in, check_out, nights, adults, children, 
                 special_requests, total_amount, status, payment_status, booking_source, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, 'walk_in', CURRENT_TIMESTAMP)
            ");
            
            $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'pending');
            
            $bookingStmt->execute([
                $userId, $roomId, $categoryId, $checkIn, $checkOut, $nights, 
                $adults, $children, $specialRequests, $totalAmount, $paymentStatus
            ]);
            $bookingId = $db->lastInsertId();

            // If room assigned, update room status
            if ($roomId) {
                $roomUpdate = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
                $roomUpdate->execute([$roomId]);
            }

            // Record payment if amount provided
            if ($paidAmount > 0) {
                $paymentStmt = $db->prepare("
                    INSERT INTO payments (booking_id, user_id, amount, payment_method, status, payment_date, notes)
                    VALUES (?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP, 'Walk-in booking payment')
                ");
                $paymentStmt->execute([$bookingId, $userId, $paidAmount, $paymentMethod]);
            }

            $db->commit();
            
            // Send notification to user about their booking
            require_once '../includes/notifications.php';
            notifyBookingUpdate($userId, $bookingId, 'confirmed');
            
            $_SESSION['success'] = 'Walk-in booking created successfully! Booking #' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
            redirect('staff-dashboard.php');

        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Error creating booking: ' . $e->getMessage();
        }
    }
}

require_once '../includes/staff-header.php';
?>

<!-- Walk-in Booking Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="margin: 0;">Walk-in Booking</h1>
            <a href="staff-dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (!empty($errors)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px;">
            <?php foreach ($errors as $error): ?>
            <p style="margin: 0;"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Guest Information -->
                <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                        <i class="fas fa-user"></i> Guest Information
                    </h2>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">First Name *</label>
                        <input type="text" name="first_name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Last Name *</label>
                        <input type="text" name="last_name" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Email *</label>
                        <input type="email" name="email" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Phone</label>
                        <input type="tel" name="phone" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Booking Details -->
                <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                        <i class="fas fa-calendar-check"></i> Booking Details
                    </h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Check-in *</label>
                            <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                                   value="<?php echo htmlspecialchars($_POST['check_in'] ?? date('Y-m-d')); ?>">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Check-out *</label>
                            <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                                   value="<?php echo htmlspecialchars($_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day'))); ?>">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Adults</label>
                            <select name="adults" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($_POST['adults'] ?? 2) == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 600; margin-bottom: 8px;">Children</label>
                            <select name="children" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                                <?php for ($i = 0; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($_POST['children'] ?? 0) == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Room Category *</label>
                        <select name="category_id" required style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?> - ₱<?php echo number_format($cat['base_price']); ?>/night
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Assign Room (Optional)</label>
                        <select name="room_id" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="">Auto-assign</option>
                            <?php foreach ($availableRooms as $room): ?>
                            <option value="<?php echo $room['room_id']; ?>" <?php echo ($_POST['room_id'] ?? '') == $room['room_id'] ? 'selected' : ''; ?>>
                                Room <?php echo htmlspecialchars($room['room_number']); ?> - <?php echo htmlspecialchars($room['category_name']); ?> (Floor <?php echo $room['floor']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 30px;">
                <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-credit-card"></i> Payment
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Payment Method</label>
                        <select name="payment_method" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="gcash">GCash</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Amount Paid</label>
                        <input type="number" name="paid_amount" step="0.01" min="0" 
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                               value="<?php echo htmlspecialchars($_POST['paid_amount'] ?? ''); ?>"
                               placeholder="0.00">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Special Requests</label>
                        <input type="text" name="special_requests" 
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px;"
                               value="<?php echo htmlspecialchars($_POST['special_requests'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-plus-circle"></i> Create Booking
                </button>
                <a href="staff-dashboard.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 16px;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</section>

<?php require_once '../includes/staff-footer.php'; ?>
