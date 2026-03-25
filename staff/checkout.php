<?php
$pageTitle = 'Check-out Guests';
require_once '../includes/config.php';

// Check if user is staff (admin, manager, or receptionist)
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Get booking ID from URL
$bookingId = $_GET['id'] ?? null;

// If no booking ID provided, show list of all checked-in guests
if (!$bookingId) {
    // Get all checked-in guests (current guests who can be checked out)
    $guestsStmt = $db->query("
        SELECT b.*, u.first_name, u.last_name, u.email, u.phone, rc.category_name, r.room_number,
               p.status as payment_status
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        LEFT JOIN payments p ON b.booking_id = p.booking_id AND p.status = 'completed'
        WHERE b.status = 'checked_in'
        ORDER BY b.check_out ASC
    ");
    $guests = $guestsStmt ? $guestsStmt->fetchAll() : [];
    
    // Get today's departures
    $today = date('Y-m-d');
    $departuresStmt = $db->query("
        SELECT b.*, u.first_name, u.last_name, u.email, rc.category_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.check_out = '$today'
        AND b.status = 'checked_in'
        ORDER BY b.check_out
    ");
    $departures = $departuresStmt ? $departuresStmt->fetchAll() : [];
    
    require_once '../includes/staff-header.php';
    ?>
    
    <!-- Check-out List Content -->
    <section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-sign-out-alt"></i> Guest Check-out</h2>
                <a href="staff-dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            
            <!-- Today's Departures -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-calendar-day" style="color: var(--warning-color); margin-right: 10px;"></i>Today's Departures</h3>
                    <span style="background-color: var(--warning-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo count($departures); ?> guests</span>
                </div>
                
                <?php if (count($departures) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--gray-light);">
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Type</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Checkout Date</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departures as $guest): ?>
                            <tr style="border-bottom: 1px solid var(--gray-light);">
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($guest['email']); ?></div>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($guest['category_name']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($guest['room_number']): ?>
                                        Room <?php echo htmlspecialchars($guest['room_number']); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo formatDate($guest['check_out']); ?></td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <a href="checkout.php?id=<?php echo $guest['booking_id']; ?>" class="btn btn-sm btn-danger" style="padding: 6px 15px; font-size: 12px; background-color: #dc3545; color: white;">Check Out</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <p style="color: #666; margin: 0;">No departures scheduled for today</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- All Current Guests -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-users" style="color: var(--info-color); margin-right: 10px;"></i>All Current Guests</h3>
                    <span style="background-color: var(--info-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo count($guests); ?> guests</span>
                </div>
                
                <?php if (count($guests) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--gray-light);">
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Type</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Check-in</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Check-out</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($guests as $guest): ?>
                            <tr style="border-bottom: 1px solid var(--gray-light);">
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($guest['email']); ?></div>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($guest['category_name']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($guest['room_number']): ?>
                                        Room <?php echo htmlspecialchars($guest['room_number']); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo formatDate($guest['check_in']); ?></td>
                                <td style="padding: 15px 20px;"><?php echo formatDate($guest['check_out']); ?></td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <a href="checkout.php?id=<?php echo $guest['booking_id']; ?>" class="btn btn-sm btn-danger" style="padding: 6px 15px; font-size: 12px; background-color: #dc3545; color: white;">Check Out</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding: 60px; text-align: center;">
                    <i class="fas fa-users" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">No guests currently checked in</h3>
                    <p style="color: #999;">All rooms are empty or guests have checked out.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <?php require_once '../includes/staff-footer.php'; ?>
    <?php exit;
}

// If booking ID provided, show individual check-out detail
if (!is_numeric($bookingId)) {
    $_SESSION['error'] = 'Invalid booking ID';
    redirect('checkout.php');
}

$db = getDB();

// Get booking details with room information
$stmt = $db->prepare("
    SELECT b.*, u.first_name, u.last_name, u.email, u.phone,
           rc.category_name,
           r.room_number, r.floor,
           b.payment_status, p.amount as paid_amount
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN payments p ON b.booking_id = p.booking_id AND p.status = 'completed'
    WHERE b.booking_id = ? AND b.status IN ('confirmed', 'checked_in')
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or not eligible for check-out';
    redirect('checkout.php');
}

// Get all payments for this booking
$paymentsStmt = $db->prepare("
    SELECT * FROM payments 
    WHERE booking_id = ? AND status = 'completed'
    ORDER BY payment_date DESC
");
$paymentsStmt->execute([$bookingId]);
$payments = $paymentsStmt->fetchAll() ?: [];

// Calculate total paid
$totalPaid = array_sum(array_column($payments, 'amount'));
$balanceDue = $booking['total_amount'] - $totalPaid;

// Get any additional charges (if table exists)
$charges = [];
$totalCharges = 0;
try {
    $chargesStmt = $db->prepare("
        SELECT * FROM booking_charges 
        WHERE booking_id = ? AND status = 'active'
        ORDER BY created_at DESC
    ");
    $chargesStmt->execute([$bookingId]);
    $charges = $chargesStmt->fetchAll();
    $totalCharges = array_sum(array_column($charges, 'amount'));
} catch (PDOException $e) {
    // Table doesn't exist yet, skip charges
    $charges = [];
    $totalCharges = 0;
}

// Process check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? false;
    $finalPayment = $_POST['final_payment'] ?? 0;
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = $_POST['notes'] ?? '';
    
    if ($confirm) {
        try {
            $db->beginTransaction();
            
            // Process final payment if provided
            if ($finalPayment > 0) {
                $stmt = $db->prepare("
                    INSERT INTO payments (booking_id, user_id, amount, payment_method, status, payment_date, notes)
                    VALUES (?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP, ?)
                ");
                $stmt->execute([$bookingId, $booking['user_id'], $finalPayment, $paymentMethod, $notes]);
            }
            
            // Update booking status to checked_out
            $stmt = $db->prepare("
                UPDATE bookings 
                SET status = 'checked_out', checked_out_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE booking_id = ?
            ");
            $stmt->execute([$bookingId]);
            
            // Update room status to cleaning (or available if no room assigned)
            if ($booking['room_id']) {
                $stmt = $db->prepare("
                    UPDATE rooms 
                    SET status = 'cleaning' 
                    WHERE room_id = ?
                ");
                $stmt->execute([$booking['room_id']]);
            }
            
            // Update payment status if fully paid
            $newTotalPaid = $totalPaid + $finalPayment;
            if ($newTotalPaid >= $booking['total_amount']) {
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET payment_status = 'paid' 
                    WHERE booking_id = ?
                ");
                $stmt->execute([$bookingId]);
            }
            
            $db->commit();
            
            // Send notification to guest about check-out
            require_once '../includes/notifications.php';
            notifyBookingUpdate($booking['user_id'], $bookingId, 'checked_out');
            
            $_SESSION['success'] = 'Guest checked out successfully';
            redirect('checkout.php');
            
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Error during check-out: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Check-out cancelled';
        redirect('checkout.php');
    }
}

require_once '../includes/staff-header.php';
?>

<!-- Check-out Detail Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-sign-out-alt"></i> Guest Check-out</h2>
            <a href="checkout.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div style="max-width: 1000px; margin: 0 auto;">
            <!-- Guest & Booking Summary -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-user"></i> Guest & Booking Summary
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 25px;">
                    <!-- Guest Info -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Guest Information</h4>
                        <div style="margin-bottom: 10px;">
                            <strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone'] ?: 'Not provided'); ?>
                        </div>
                    </div>
                    
                    <!-- Booking Info -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Booking Details</h4>
                        <div style="margin-bottom: 10px;">
                            <strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Room:</strong> <?php echo htmlspecialchars($booking['category_name']); ?>
                            <?php if ($booking['room_number']): ?>
                                (Room <?php echo htmlspecialchars($booking['room_number']); ?>)
                            <?php endif; ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Check-in:</strong> <?php echo formatDate($booking['check_in']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Check-out:</strong> <?php echo formatDate($booking['check_out']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Nights:</strong> <?php echo $booking['nights']; ?>
                        </div>
                    </div>
                    
                    <!-- Status Info -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Current Status</h4>
                        <div style="margin-bottom: 10px;">
                            <strong>Booking Status:</strong> 
                            <span style="padding: 4px 10px; border-radius: 15px; font-size: 12px; background-color: #cce5ff; color: #004085;">
                                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Payment Status:</strong> 
                            <span style="padding: 4px 10px; border-radius: 15px; font-size: 12px; background-color: #fff3cd; color: #856404;">
                                <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Guests:</strong> <?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Summary -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-credit-card"></i> Payment Summary
                </h2>
                
                <div style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--gray-light);">
                                <th style="padding: 12px; text-align: left; font-weight: 600;">Description</th>
                                <th style="padding: 12px; text-align: right; font-weight: 600;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--gray-light);">
                                <td style="padding: 12px;">Room Charges (<?php echo $booking['nights']; ?> nights)</td>
                                <td style="padding: 12px; text-align: right; font-weight: 600;"><?php echo formatPrice($booking['total_amount']); ?></td>
                            </tr>
                            <?php if (!empty($charges)): ?>
                                <?php foreach ($charges as $charge): ?>
                                <tr style="border-bottom: 1px solid var(--gray-light);">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($charge['description']); ?></td>
                                    <td style="padding: 12px; text-align: right;"><?php echo formatPrice($charge['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr style="border-bottom: 1px solid var(--gray-light);">
                                <td style="padding: 12px;"><strong>Total Charges</strong></td>
                                <td style="padding: 12px; text-align: right; font-weight: 600;"><?php echo formatPrice($booking['total_amount'] + $totalCharges); ?></td>
                            </tr>
                            <?php if (!empty($payments)): ?>
                                <?php foreach ($payments as $payment): ?>
                                <tr style="border-bottom: 1px solid var(--gray-light);">
                                    <td style="padding: 12px;">Payment (<?php echo formatDate($payment['payment_date'], 'M d, Y'); ?>)</td>
                                    <td style="padding: 12px; text-align: right; color: #28a745;">-<?php echo formatPrice($payment['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr style="background-color: var(--gray-light);">
                                <td style="padding: 12px; font-weight: 600;">Balance Due</td>
                                <td style="padding: 12px; text-align: right; font-size: 18px; font-weight: 700; color: <?php echo ($balanceDue + $totalCharges) > 0 ? '#dc3545' : '#28a745'; ?>">
                                    <?php echo formatPrice($balanceDue + $totalCharges); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Final Payment & Check-out -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-sign-out-alt"></i> Final Payment & Check-out
                </h2>
                
                <form method="POST">
                    <?php if (($balanceDue + $totalCharges) > 0): ?>
                    <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 15px 0; color: #856404;">Outstanding Balance</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Final Payment Amount</label>
                                <input type="number" name="final_payment" step="0.01" min="0" value="<?php echo number_format($balanceDue + $totalCharges, 2, '.', ''); ?>" 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Payment Method</label>
                                <select name="payment_method" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="cash">Cash</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="gcash">GCash</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-top: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600;">Payment Notes (Optional)</label>
                            <textarea name="notes" rows="3" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" placeholder="Enter any payment notes or special instructions..."></textarea>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 10px 0; color: #155724;">Payment Complete</h4>
                        <p style="margin: 0; color: #155724;">All charges have been paid. Ready for check-out.</p>
                    </div>
                    <?php endif; ?>
                    
                    <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 10px 0; color: #721c24;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Confirm Check-out
                        </h4>
                        <p style="margin: 0; color: #721c24;">
                            Are you ready to check-out this guest? This will mark the booking as checked-out and set the room status to cleaning. This action cannot be undone.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" name="confirm" value="1" 
                                class="btn" 
                                style="background-color: #dc3545; color: white; padding: 12px 30px;">
                            <i class="fas fa-sign-out-alt"></i>
                            Confirm Check-out
                        </button>
                        <a href="staff-dashboard.php" class="btn btn-outline" style="padding: 12px 30px;">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/staff-footer.php'; ?>
