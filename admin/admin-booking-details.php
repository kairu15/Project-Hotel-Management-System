<?php
$pageTitle = 'Booking Details - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

// Get booking ID from URL
$bookingId = $_GET['id'] ?? null;
if (!$bookingId || !is_numeric($bookingId)) {
    $_SESSION['error'] = 'Invalid booking ID';
    redirect('admin-dashboard.php');
}

$db = getDB();

// Get booking details with all related information
$stmt = $db->prepare("
    SELECT b.*, 
           rc.category_name, rc.description as category_description, rc.amenities as category_amenities, rc.base_price,
           r.room_number, r.floor, r.status as room_status,
           u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.country, u.member_since
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

// Check if booking exists - MUST be before header include
if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    redirect('admin-dashboard.php');
}

// Now include header after all validation
require_once '../includes/admin-header.php';

// Get payment history
$paymentsStmt = $db->prepare("
    SELECT * FROM payments 
    WHERE booking_id = ? 
    ORDER BY payment_date DESC
");
$paymentsStmt->execute([$bookingId]);
$payments = $paymentsStmt->fetchAll();

// Calculate payment totals
$totalPaid = array_sum(array_column($payments, 'amount'));
$balanceDue = $booking['total_amount'] - $totalPaid;

// Get booking logs/history if exists
$logsStmt = $db->prepare("
    SELECT * FROM booking_logs 
    WHERE booking_id = ? 
    ORDER BY created_at DESC
");
$logsStmt->execute([$bookingId]);
$logs = $logsStmt->fetchAll();

// Status styling
$statusColors = [
    'pending' => ['#fff3cd', '#856404', 'clock'],
    'confirmed' => ['#d4edda', '#155724', 'check-circle'],
    'checked_in' => ['#cce5ff', '#004085', 'door-open'],
    'checked_out' => ['#e2e3e5', '#383d41', 'sign-out-alt'],
    'cancelled' => ['#f8d7da', '#721c24', 'times-circle'],
    'no_show' => ['#f8d7da', '#721c24', 'user-slash']
];
$status = $statusColors[$booking['status']] ?? $statusColors['pending'];

// Payment status styling
$paymentStatusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'partial' => ['#cce5ff', '#004085'],
    'paid' => ['#d4edda', '#155724'],
    'refunded' => ['#f8d7da', '#721c24']
];
$paymentStatus = $paymentStatusColors[$booking['payment_status']] ?? $paymentStatusColors['pending'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['new_status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (in_array($newStatus, ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])) {
        try {
            $db->beginTransaction();
            
            // Update booking status
            $stmt = $db->prepare("
                UPDATE bookings 
                SET status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE booking_id = ?
            ");
            $stmt->execute([$newStatus, $bookingId]);
            
            // Log the status change
            $logStmt = $db->prepare("
                INSERT INTO booking_logs (booking_id, action, details, created_by) 
                VALUES (?, 'status_change', ?, ?)
            ");
            $logStmt->execute([$bookingId, "Status changed to {$newStatus}. Notes: {$notes}", $_SESSION['user_id']]);
            
            // Update room status if checking in or out
            if ($newStatus === 'checked_in' && $booking['room_id']) {
                $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
                $stmt->execute([$booking['room_id']]);
            } elseif ($newStatus === 'checked_out' && $booking['room_id']) {
                $stmt = $db->prepare("UPDATE rooms SET status = 'cleaning' WHERE room_id = ?");
                $stmt->execute([$booking['room_id']]);
            } elseif ($newStatus === 'cancelled' && $booking['room_id']) {
                $stmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
                $stmt->execute([$booking['room_id']]);
            }
            
            $db->commit();
            $_SESSION['success'] = 'Booking status updated successfully';
            
            // Refresh booking data
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Error updating status: ' . $e->getMessage();
        }
    }
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $amount = $_POST['amount'] ?? 0;
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = $_POST['payment_notes'] ?? '';
    
    if ($amount > 0) {
        try {
            $db->beginTransaction();
            
            // Add payment
            $stmt = $db->prepare("
                INSERT INTO payments (booking_id, user_id, amount, payment_method, status, payment_date, notes)
                VALUES (?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP, ?)
            ");
            $stmt->execute([$bookingId, $booking['user_id'], $amount, $paymentMethod, $notes]);
            
            // Update booking payment status
            $newTotalPaid = $totalPaid + $amount;
            $newPaymentStatus = ($newTotalPaid >= $booking['total_amount']) ? 'paid' : 'partial';
            
            $stmt = $db->prepare("
                UPDATE bookings 
                SET payment_status = ?, updated_at = CURRENT_TIMESTAMP
                WHERE booking_id = ?
            ");
            $stmt->execute([$newPaymentStatus, $bookingId]);
            
            // Log payment
            $logStmt = $db->prepare("
                INSERT INTO booking_logs (booking_id, action, details, created_by) 
                VALUES (?, 'payment_added', 'Payment of {$amount} added via {$paymentMethod}', ?)
            ");
            $logStmt->execute([$bookingId, $_SESSION['user_id']]);
            
            $db->commit();
            $_SESSION['success'] = 'Payment added successfully';
            
            // Refresh data
            $paymentsStmt->execute([$bookingId]);
            $payments = $paymentsStmt->fetchAll();
            $totalPaid = array_sum(array_column($payments, 'amount'));
            $balanceDue = $booking['total_amount'] - $totalPaid;
            
            // Refresh booking data
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Error adding payment: ' . $e->getMessage();
        }
    }
}
?>

<!-- Admin Booking Details Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 300px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <div>
                <!-- Booking Header -->
                <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                        <div>
                            <h2 style="font-size: 28px; margin-bottom: 10px;">Booking #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                            <p style="color: #666;">Made on <?php echo formatDate($booking['created_at'], 'F d, Y \a\t h:i A'); ?></p>
                        </div>
                        <div style="text-align: right;">
                            <span style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 25px; font-size: 14px; font-weight: 600; background-color: <?php echo $status[0]; ?>; color: <?php echo $status[1]; ?>; margin-bottom: 10px;">
                                <i class="fas fa-<?php echo $status[2]; ?>"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                            </span>
                            <br>
                            <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 15px; border-radius: 15px; font-size: 12px; font-weight: 600; background-color: <?php echo $paymentStatus[0]; ?>; color: <?php echo $paymentStatus[1]; ?>;">
                                Payment: <?php echo ucfirst($booking['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <a href="admin-dashboard.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <a href="admin-bookings.php" class="btn btn-outline">
                            <i class="fas fa-list"></i> All Bookings
                        </a>
                        <button onclick="window.print()" class="btn btn-outline">
                            <i class="fas fa-print"></i> Print Details
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Guest Information -->
                    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
                            <h3 style="margin: 0; font-size: 18px;">
                                <i class="fas fa-user"></i> Guest Information
                            </h3>
                        </div>
                        <div style="padding: 25px;">
                            <div style="margin-bottom: 15px;">
                                <h4 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h4>
                                <p style="font-size: 12px; color: #666;">Member since <?php echo formatDate($booking['member_since'], 'F Y'); ?></p>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone'] ?: 'Not provided'); ?>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>Address:</strong> <?php echo htmlspecialchars($booking['address'] ?: 'Not provided'); ?>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <strong>City:</strong> <?php echo htmlspecialchars($booking['city'] ?: 'Not provided'); ?>
                            </div>
                            <div>
                                <strong>Country:</strong> <?php echo htmlspecialchars($booking['country'] ?: 'Not provided'); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Information -->
                    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
                            <h3 style="margin: 0; font-size: 18px;">
                                <i class="fas fa-calendar-check"></i> Booking Information
                            </h3>
                        </div>
                        <div style="padding: 25px;">
                            <div style="margin-bottom: 15px;">
                                <h4 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($booking['category_name']); ?></h4>
                                <?php if ($booking['room_number']): ?>
                                <p style="color: #666; font-size: 14px;">Room <?php echo htmlspecialchars($booking['room_number']); ?> (Floor <?php echo htmlspecialchars($booking['floor']); ?>)</p>
                                <?php endif; ?>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Check-in</p>
                                    <p style="font-weight: 600;"><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo formatDate($booking['check_in']); ?></p>
                                </div>
                                <div>
                                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Check-out</p>
                                    <p style="font-weight: 600;"><i class="fas fa-calendar-times" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo formatDate($booking['check_out']); ?></p>
                                </div>
                                <div>
                                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Nights</p>
                                    <p style="font-weight: 600;"><i class="fas fa-moon" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo $booking['nights']; ?></p>
                                </div>
                                <div>
                                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Guests</p>
                                    <p style="font-weight: 600;"><i class="fas fa-user-friends" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children</p>
                                </div>
                            </div>
                            <?php if ($booking['special_requests']): ?>
                            <div>
                                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Special Requests</p>
                                <p style="font-style: italic;"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Admin Actions -->
                <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 30px;">
                    <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
                        <h3 style="margin: 0; font-size: 18px;">
                            <i class="fas fa-cogs"></i> Admin Actions
                        </h3>
                    </div>
                    <div style="padding: 25px;">
                        <!-- Status Update Form -->
                        <form method="POST" style="margin-bottom: 30px;">
                            <h4 style="margin-bottom: 15px; color: var(--primary-color);">Update Booking Status</h4>
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 15px;">
                                <select name="new_status" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="">Select New Status</option>
                                    <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="checked_in" <?php echo $booking['status'] === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                    <option value="checked_out" <?php echo $booking['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                    <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="no_show" <?php echo $booking['status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                </select>
                                <input type="text" name="notes" placeholder="Add notes about this status change..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <button type="submit" name="update_status" class="btn" style="background-color: var(--primary-color); color: white;">
                                <i class="fas fa-sync"></i> Update Status
                            </button>
                        </form>

                        <!-- Payment Form -->
                        <form method="POST">
                            <h4 style="margin-bottom: 15px; color: var(--primary-color);">Add Payment</h4>
                            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                    <div>
                                        <strong>Total Amount:</strong> <?php echo formatPrice($booking['total_amount']); ?>
                                    </div>
                                    <div>
                                        <strong>Paid:</strong> <?php echo formatPrice($totalPaid); ?>
                                    </div>
                                    <div>
                                        <strong>Balance:</strong> <span style="color: <?php echo $balanceDue > 0 ? '#dc3545' : '#28a745'; ?>;"><?php echo formatPrice($balanceDue); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 15px; margin-bottom: 15px;">
                                <input type="number" name="amount" step="0.01" min="0" placeholder="Amount" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                <select name="payment_method" required style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                                    <option value="">Payment Method</option>
                                    <option value="cash">Cash</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="gcash">GCash</option>
                                    <option value="paypal">PayPal</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                                <input type="text" name="payment_notes" placeholder="Payment notes (optional)..." style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            </div>
                            <button type="submit" name="add_payment" class="btn" style="background-color: #28a745; color: white;">
                                <i class="fas fa-plus"></i> Add Payment
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Payment History -->
                <?php if (!empty($payments)): ?>
                <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 30px;">
                    <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
                        <h3 style="margin: 0; font-size: 18px;">
                            <i class="fas fa-credit-card"></i> Payment History
                        </h3>
                    </div>
                    <div style="padding: 25px;">
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: var(--gray-light);">
                                        <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Date</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Amount</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Method</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Status</th>
                                        <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr style="border-bottom: 1px solid var(--gray-light);">
                                        <td style="padding: 12px; font-size: 14px;"><?php echo formatDate($payment['payment_date'], 'M d, Y h:i A'); ?></td>
                                        <td style="padding: 12px; font-size: 14px; font-weight: 600;"><?php echo formatPrice($payment['amount']); ?></td>
                                        <td style="padding: 12px; font-size: 14px;"><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                        <td style="padding: 12px; font-size: 14px;">
                                            <span style="padding: 4px 10px; border-radius: 15px; font-size: 12px; background-color: var(--gray-light);">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px; font-size: 14px;"><?php echo htmlspecialchars($payment['notes'] ?: ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
