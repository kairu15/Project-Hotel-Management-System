<?php
$pageTitle = 'My Bookings';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    
    // Verify booking belongs to current user
    $verifyStmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
    $verifyStmt->execute([$bookingId, $userId]);
    $booking = $verifyStmt->fetch();
    
    if (!$booking) {
        $error = 'Booking not found or unauthorized.';
    } else {
        if ($action === 'cancel' && $booking['status'] === 'pending') {
            // Process cancellation with refund
            $paymentMethod = $_POST['refund_method'] ?? '';
            $refundAmount = (float)($booking['total_amount']);
            
            try {
                $db->beginTransaction();
                
                // Update booking status
                $cancelStmt = $db->prepare("UPDATE bookings SET status = 'cancelled', payment_status = 'refunded', updated_at = NOW() WHERE booking_id = ?");
                $cancelStmt->execute([$bookingId]);
                
                // Free up the room if assigned
                if ($booking['room_id']) {
                    $roomStmt = $db->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
                    $roomStmt->execute([$booking['room_id']]);
                }
                
                // Record refund payment
                $refundStmt = $db->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, status, notes) VALUES (?, ?, ?, ?, 'refunded', ?)");
                $refundStmt->execute([
                    $bookingId,
                    $userId,
                    $refundAmount,
                    $paymentMethod,
                    "Refund processed via $paymentMethod for cancelled booking"
                ]);
                
                // Log the activity
                $logStmt = $db->prepare("INSERT INTO booking_logs (booking_id, action, details, created_by) VALUES (?, 'cancelled', ?, ?)");
                $logStmt->execute([$bookingId, "Booking cancelled with refund of ₱" . number_format($refundAmount, 2) . " via $paymentMethod", $userId]);
                
                $db->commit();
                $message = 'Your booking has been cancelled successfully. Refund of ' . formatPrice($refundAmount) . ' will be processed via ' . ucfirst(str_replace('_', ' ', $paymentMethod)) . ' within 5-7 business days.';
                
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Failed to cancel booking. Please try again.';
            }
        }
        
        if ($action === 'reschedule' && $booking['status'] === 'pending') {
            // Process reschedule
            $newCheckIn = $_POST['new_check_in'] ?? '';
            $newCheckOut = $_POST['new_check_out'] ?? '';
            $paymentMethod = $_POST['payment_method'] ?? '';
            
            if (empty($newCheckIn) || empty($newCheckOut)) {
                $error = 'Please select new check-in and check-out dates.';
            } elseif (strtotime($newCheckIn) >= strtotime($newCheckOut)) {
                $error = 'Check-out date must be after check-in date.';
            } elseif (strtotime($newCheckIn) < strtotime('today')) {
                $error = 'Check-in date cannot be in the past.';
            } else {
                try {
                    $db->beginTransaction();
                    
                    // Calculate new nights and amount
                    $newNights = calculateNights($newCheckIn, $newCheckOut);
                    $newTotal = $booking['room_rate'] * $newNights;
                    $oldTotal = (float)$booking['total_amount'];
                    $difference = $newTotal - $oldTotal;
                    
                    // Check room availability for new dates
                    $availStmt = $db->prepare("
                        SELECT room_id FROM rooms 
                        WHERE category_id = ? 
                        AND room_id NOT IN (
                            SELECT room_id FROM bookings 
                            WHERE status IN ('pending', 'confirmed', 'checked_in') 
                            AND booking_id != ?
                            AND ((check_in <= ? AND check_out > ?) OR (check_in < ? AND check_out >= ?))
                        )
                        AND status = 'available'
                        LIMIT 1
                    ");
                    
                    $roomId = $booking['room_id'];
                    if ($roomId) {
                        // Check if same room is available
                        $availStmt->execute([$booking['category_id'], $bookingId, $newCheckOut, $newCheckIn, $newCheckOut, $newCheckIn]);
                        $availableRoom = $availStmt->fetch();
                        
                        if (!$availableRoom) {
                            // Try to find another room in same category
                            $availStmt = $db->prepare("
                                SELECT room_id FROM rooms 
                                WHERE category_id = ? 
                                AND room_id NOT IN (
                                    SELECT room_id FROM bookings 
                                    WHERE status IN ('pending', 'confirmed', 'checked_in') 
                                    AND ((check_in <= ? AND check_out > ?) OR (check_in < ? AND check_out >= ?))
                                )
                                AND status = 'available'
                                LIMIT 1
                            ");
                            $availStmt->execute([$booking['category_id'], $newCheckOut, $newCheckIn, $newCheckOut, $newCheckIn]);
                            $availableRoom = $availStmt->fetch();
                            
                            if (!$availableRoom) {
                                throw new Exception('No rooms available for the selected dates in this category.');
                            }
                            $roomId = $availableRoom['room_id'];
                        }
                    }
                    
                    // Update booking
                    $updateStmt = $db->prepare("
                        UPDATE bookings 
                        SET check_in = ?, check_out = ?, nights = ?, total_amount = ?, room_id = ?, updated_at = NOW() 
                        WHERE booking_id = ?
                    ");
                    $updateStmt->execute([$newCheckIn, $newCheckOut, $newNights, $newTotal, $roomId, $bookingId]);
                    
                    // Handle additional charges if needed
                    if ($difference > 0) {
                        // Record additional charge payment
                        $paymentStmt = $db->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_method, status, notes) VALUES (?, ?, ?, ?, 'completed', ?)");
                        $paymentStmt->execute([
                            $bookingId,
                            $userId,
                            $difference,
                            $paymentMethod,
                            "Additional charge for reschedule: $newNights nights @ ₱" . number_format($booking['room_rate'], 2)
                        ]);
                        
                        // Add to booking charges
                        $chargeStmt = $db->prepare("INSERT INTO booking_charges (booking_id, description, amount, charge_type, status) VALUES (?, ?, ?, 'other', 'paid')");
                        $chargeStmt->execute([$bookingId, "Reschedule additional charge ($newNights nights)", $difference]);
                    }
                    
                    // Log the activity
                    $logStmt = $db->prepare("INSERT INTO booking_logs (booking_id, action, details, created_by) VALUES (?, 'rescheduled', ?, ?)");
                    $logStmt->execute([$bookingId, "Rescheduled from {$booking['check_in']} - {$booking['check_out']} to $newCheckIn - $newCheckOut", $userId]);
                    
                    $db->commit();
                    
                    if ($difference > 0) {
                        $message = "Booking rescheduled successfully. Additional charge of " . formatPrice($difference) . " has been processed via " . ucfirst(str_replace('_', ' ', $paymentMethod)) . ".";
                    } else {
                        $message = "Booking rescheduled successfully." . ($difference < 0 ? " A credit of " . formatPrice(abs($difference)) . " will be applied to your account." : "");
                    }
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

// Get user's bookings
$stmt = $db->prepare("
    SELECT b.*, rc.category_name, r.room_number 
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

// Filter bookings
$filter = $_GET['filter'] ?? 'all';
$filteredBookings = $bookings;

if ($filter === 'upcoming') {
    $filteredBookings = array_filter($bookings, function($b) {
        return in_array($b['status'], ['confirmed', 'pending']) && strtotime($b['check_in']) >= strtotime('today');
    });
} elseif ($filter === 'past') {
    $filteredBookings = array_filter($bookings, function($b) {
        return in_array($b['status'], ['checked_out', 'cancelled']) || strtotime($b['check_out']) < strtotime('today');
    });
}
?>

<?php if ($message): ?>
<div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
    <i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error" style="background-color: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
    <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div style="background-color: white; padding: 5px; border-radius: 10px; margin-bottom: 30px; display: inline-flex;">
    <a href="?filter=all" style="padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'all' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">All Bookings</a>
    <a href="?filter=upcoming" style="padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'upcoming' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Upcoming</a>
    <a href="?filter=past" style="padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'past' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Past</a>
</div>

<!-- Bookings List -->
<?php if (count($filteredBookings) > 0): ?>
<div style="display: flex; flex-direction: column; gap: 25px;">
    <?php foreach ($filteredBookings as $booking): 
        $statusColors = [
            'pending' => ['#fff3cd', '#856404', 'clock'],
            'confirmed' => ['#d4edda', '#155724', 'check-circle'],
            'checked_in' => ['#cce5ff', '#004085', 'door-open'],
            'checked_out' => ['#e2e3e5', '#383d41', 'sign-out-alt'],
            'cancelled' => ['#f8d7da', '#721c24', 'times-circle']
        ];
        $status = $statusColors[$booking['status']] ?? $statusColors['pending'];
        
        // Check if actions available (only for pending status)
        $canCancel = $booking['status'] === 'pending';
        $canReschedule = $booking['status'] === 'pending';
    ?>
    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <div style="display: flex;">
            <!-- Image -->
            <div style="width: 250px; min-height: 200px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white;">
                <i class="fas fa-bed" style="font-size: 60px; opacity: 0.5;"></i>
            </div>
            
            <!-- Content -->
            <div style="flex: 1; padding: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                    <div>
                        <h3 style="font-size: 22px; margin-bottom: 5px;"><?php echo htmlspecialchars($booking['category_name']); ?></h3>
                        <?php if ($booking['room_number']): ?>
                        <p style="color: #666; font-size: 14px;">Room <?php echo htmlspecialchars($booking['room_number']); ?></p>
                        <?php endif; ?>
                    </div>
                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; background-color: <?php echo $status[0]; ?>; color: <?php echo $status[1]; ?>;">
                        <i class="fas fa-<?php echo $status[2]; ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                    </span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                    <div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Check-in</p>
                        <p style="font-size: 16px; font-weight: 600;"><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 8px;"></i><?php echo formatDate($booking['check_in']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Check-out</p>
                        <p style="font-size: 16px; font-weight: 600;"><i class="fas fa-calendar-times" style="color: var(--primary-color); margin-right: 8px;"></i><?php echo formatDate($booking['check_out']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Guests</p>
                        <p style="font-size: 16px; font-weight: 600;"><i class="fas fa-user-friends" style="color: var(--primary-color); margin-right: 8px;"></i><?php echo $booking['adults'] + $booking['children']; ?> Guest<?php echo ($booking['adults'] + $booking['children']) > 1 ? 's' : ''; ?></p>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid var(--gray-light);">
                    <div>
                        <p style="font-size: 12px; color: #666; margin-bottom: 3px;">Total Amount</p>
                        <p style="font-size: 24px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($booking['total_amount']); ?></p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-outline btn-sm">View Details</a>
                        <?php if ($canReschedule): ?>
                        <button type="button" class="btn btn-sm" style="background-color: #17a2b8; color: white;" onclick="openRescheduleModal(<?php echo $booking['booking_id']; ?>, '<?php echo $booking['check_in']; ?>', '<?php echo $booking['check_out']; ?>', <?php echo $booking['total_amount']; ?>, <?php echo $booking['room_rate']; ?>)">
                            <i class="fas fa-calendar-alt" style="margin-right: 5px;"></i>Reschedule
                        </button>
                        <?php endif; ?>
                        <?php if ($canCancel): ?>
                        <button type="button" class="btn btn-sm" style="background-color: #dc3545; color: white;" onclick="openCancelModal(<?php echo $booking['booking_id']; ?>, <?php echo $booking['total_amount']; ?>)">
                            <i class="fas fa-times" style="margin-right: 5px;"></i>Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div style="background-color: white; padding: 80px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <i class="fas fa-calendar-times" style="font-size: 80px; color: var(--gray-medium); margin-bottom: 30px;"></i>
    <h3 style="font-size: 24px; margin-bottom: 15px;">No Bookings Found</h3>
    <p style="color: #666; margin-bottom: 30px;">You don't have any <?php echo $filter !== 'all' ? $filter : ''; ?> bookings yet.</p>
    <a href="../rooms.php" class="btn btn-primary">Browse Rooms</a>
</div>
<?php endif; ?>

<!-- Cancel Booking Modal -->
<div id="cancelModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); animation: modalSlideIn 0.3s;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 20px; color: #333;"><i class="fas fa-times-circle" style="color: #dc3545; margin-right: 10px;"></i>Cancel Booking</h3>
            <button type="button" onclick="closeModal('cancelModal')" style="background: none; border: none; font-size: 24px; color: #999; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" id="cancelForm">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="booking_id" id="cancelBookingId">
            
            <div style="padding: 25px;">
                <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #856404;"><i class="fas fa-info-circle" style="margin-right: 8px;"></i>Cancelling a pending booking will free up the room for other guests.</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 14px; color: #666; margin-bottom: 10px;">Refund Amount:</p>
                    <p style="font-size: 28px; font-weight: 700; color: var(--primary-color); margin: 0;" id="cancelRefundAmount">₱0.00</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Select Refund Method <span style="color: #dc3545;">*</span></label>
                    
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;" class="payment-option" onclick="selectRefundMethod('gcash')" data-method="gcash">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="refund_method" value="gcash" style="margin-right: 12px;" required>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333;">GCash</div>
                                <div style="font-size: 12px; color: #666;">Refund to your GCash account</div>
                            </div>
                            <i class="fas fa-mobile-alt" style="font-size: 24px; color: #007bff;"></i>
                        </div>
                        <div class="gcash-details" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                            <label style="display: block; font-size: 13px; margin-bottom: 5px;">GCash Mobile Number</label>
                            <input type="text" name="gcash_number" placeholder="09XXXXXXXXX" pattern="09\d{9}" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <label style="display: block; font-size: 13px; margin: 10px 0 5px;">Account Name</label>
                            <input type="text" name="gcash_name" placeholder="Full Name" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                        </div>
                    </div>
                    
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;" class="payment-option" onclick="selectRefundMethod('paypal')" data-method="paypal">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="refund_method" value="paypal" style="margin-right: 12px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333;">PayPal</div>
                                <div style="font-size: 12px; color: #666;">Refund to your PayPal account</div>
                            </div>
                            <i class="fab fa-paypal" style="font-size: 24px; color: #003087;"></i>
                        </div>
                        <div class="paypal-details" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                            <label style="display: block; font-size: 13px; margin-bottom: 5px;">PayPal Email</label>
                            <input type="email" name="paypal_email" placeholder="your@email.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                        </div>
                    </div>
                    
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;" class="payment-option" onclick="selectRefundMethod('credit_card')" data-method="credit_card">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="refund_method" value="credit_card" style="margin-right: 12px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333;">Credit Card</div>
                                <div style="font-size: 12px; color: #666;">Refund to your credit card (5-7 business days)</div>
                            </div>
                            <i class="fas fa-credit-card" style="font-size: 24px; color: #333;"></i>
                        </div>
                        <div class="credit-card-details" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                            <label style="display: block; font-size: 13px; margin-bottom: 5px;">Card Number (last 4 digits for verification)</label>
                            <input type="text" name="card_last4" placeholder="XXXX" pattern="\d{4}" maxlength="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                            <label style="display: block; font-size: 13px; margin: 10px 0 5px;">Cardholder Name</label>
                            <input type="text" name="card_holder" placeholder="Name on card" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="padding: 20px 25px; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('cancelModal')" class="btn btn-outline">Close</button>
                <button type="submit" class="btn" style="background-color: #dc3545; color: white;" onclick="return confirmCancel()">
                    <i class="fas fa-check" style="margin-right: 5px;"></i>Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="rescheduleModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 550px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); animation: modalSlideIn 0.3s;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 20px; color: #333;"><i class="fas fa-calendar-alt" style="color: #17a2b8; margin-right: 10px;"></i>Reschedule Booking</h3>
            <button type="button" onclick="closeModal('rescheduleModal')" style="background: none; border: none; font-size: 24px; color: #999; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" id="rescheduleForm">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="booking_id" id="rescheduleBookingId">
            <input type="hidden" id="currentRate" value="0">
            
            <div style="padding: 25px;">
                <div style="background-color: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #0c5460;"><i class="fas fa-info-circle" style="margin-right: 8px;"></i>You can change your check-in and check-out dates. Additional charges may apply based on the new duration.</p>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
                    <p style="margin: 0 0 5px; font-size: 13px; color: #666;">Current Booking:</p>
                    <p style="margin: 0; font-weight: 600;" id="currentDates">-</p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">New Check-in Date <span style="color: #dc3545;">*</span></label>
                        <input type="date" name="new_check_in" id="newCheckIn" required min="<?php echo date('Y-m-d'); ?>" 
                               style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;" 
                               onchange="calculateNewTotal()">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">New Check-out Date <span style="color: #dc3545;">*</span></label>
                        <input type="date" name="new_check_out" id="newCheckOut" required 
                               style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;" 
                               onchange="calculateNewTotal()">
                    </div>
                </div>
                
                <div style="background-color: #e8f5e9; border: 1px solid #4caf50; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: none;" id="priceSummary">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #666;">Original Total:</span>
                        <span style="font-weight: 500;" id="originalTotal">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #666;">New Total (<span id="newNights">0</span> nights):</span>
                        <span style="font-weight: 500;" id="newTotal">₱0.00</span>
                    </div>
                    <div style="border-top: 1px solid #4caf50; margin-top: 10px; padding-top: 10px; display: flex; justify-content: space-between;">
                        <span style="font-weight: 600;" id="differenceLabel">Difference:</span>
                        <span style="font-weight: 700; font-size: 18px;" id="differenceAmount">₱0.00</span>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px; display: none;" id="paymentSection">
                    <label style="display: block; font-weight: 600; margin-bottom: 10px; color: #333;">Select Payment Method for Additional Charges <span style="color: #dc3545;">*</span></label>
                    
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer;" class="payment-option-reschedule" onclick="selectPaymentMethod('gcash')" data-method="gcash">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="gcash" style="margin-right: 12px;" required>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333;">GCash</div>
                            </div>
                            <i class="fas fa-mobile-alt" style="font-size: 24px; color: #007bff;"></i>
                        </div>
                    </div>
                    
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer;" class="payment-option-reschedule" onclick="selectPaymentMethod('paypal')" data-method="paypal">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="paypal" style="margin-right: 12px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333;">PayPal</div>
                            </div>
                            <i class="fab fa-paypal" style="font-size: 24px; color: #003087;"></i>
                        </div>
                    </div>
                    
                    <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 10px; cursor: pointer;" class="payment-option-reschedule" onclick="selectPaymentMethod('credit_card')" data-method="credit_card">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="credit_card" style="margin-right: 12px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333;">Credit Card</div>
                            </div>
                            <i class="fas fa-credit-card" style="font-size: 24px; color: #333;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="padding: 20px 25px; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('rescheduleModal')" class="btn btn-outline">Close</button>
                <button type="submit" class="btn" style="background-color: #17a2b8; color: white;" id="rescheduleSubmitBtn">
                    <i class="fas fa-check" style="margin-right: 5px;"></i>Confirm Reschedule
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-content {
    max-height: 85vh;
    overflow-y: auto;
}

.payment-option:hover, .payment-option-reschedule:hover {
    border-color: var(--primary-color) !important;
}

.payment-option.selected, .payment-option-reschedule.selected {
    border-color: var(--primary-color) !important;
    background-color: #f8f9ff;
}
</style>

<script>
// Modal Functions
function openCancelModal(bookingId, refundAmount) {
    document.getElementById('cancelBookingId').value = bookingId;
    document.getElementById('cancelRefundAmount').textContent = '₱' + parseFloat(refundAmount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('cancelModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function openRescheduleModal(bookingId, checkIn, checkOut, totalAmount, roomRate) {
    document.getElementById('rescheduleBookingId').value = bookingId;
    document.getElementById('currentRate').value = roomRate;
    document.getElementById('originalTotal').textContent = '₱' + parseFloat(totalAmount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('currentDates').textContent = formatDate(checkIn) + ' - ' + formatDate(checkOut);
    
    // Set min dates
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('newCheckIn').min = tomorrow.toISOString().split('T')[0];
    
    document.getElementById('rescheduleModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Reset forms
    if (modalId === 'cancelModal') {
        document.getElementById('cancelForm').reset();
        document.querySelectorAll('.payment-option').forEach(el => {
            el.classList.remove('selected');
            el.style.borderColor = '#e0e0e0';
        });
        document.querySelectorAll('[class$="-details"]').forEach(el => el.style.display = 'none');
    } else if (modalId === 'rescheduleModal') {
        document.getElementById('rescheduleForm').reset();
        document.getElementById('priceSummary').style.display = 'none';
        document.getElementById('paymentSection').style.display = 'none';
        document.querySelectorAll('.payment-option-reschedule').forEach(el => {
            el.classList.remove('selected');
            el.style.borderColor = '#e0e0e0';
        });
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        closeModal(event.target.id);
    }
}

// Cancel Modal Functions
function selectRefundMethod(method) {
    // Update radio buttons
    document.querySelectorAll('input[name="refund_method"]').forEach(radio => {
        radio.checked = (radio.value === method);
    });
    
    // Update visual selection
    document.querySelectorAll('.payment-option').forEach(el => {
        if (el.dataset.method === method) {
            el.classList.add('selected');
            el.style.borderColor = 'var(--primary-color)';
        } else {
            el.classList.remove('selected');
            el.style.borderColor = '#e0e0e0';
        }
    });
    
    // Show/hide details
    document.querySelectorAll('.gcash-details, .paypal-details, .credit-card-details').forEach(el => {
        el.style.display = 'none';
    });
    
    const detailsClass = method + '-details';
    const detailsEl = document.querySelector('.' + detailsClass);
    if (detailsEl) {
        detailsEl.style.display = 'block';
    }
}

function confirmCancel() {
    const method = document.querySelector('input[name="refund_method"]:checked');
    if (!method) {
        alert('Please select a refund method.');
        return false;
    }
    return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');
}

// Reschedule Modal Functions
let originalTotal = 0;
let currentRate = 0;

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function calculateNewTotal() {
    const checkIn = document.getElementById('newCheckIn').value;
    const checkOut = document.getElementById('newCheckOut').value;
    currentRate = parseFloat(document.getElementById('currentRate').value);
    originalTotal = parseFloat(document.getElementById('originalTotal').textContent.replace(/[₱,]/g, ''));
    
    if (checkIn && checkOut) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        
        if (end > start) {
            const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            const newTotal = nights * currentRate;
            const difference = newTotal - originalTotal;
            
            document.getElementById('newNights').textContent = nights;
            document.getElementById('newTotal').textContent = '₱' + newTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('differenceAmount').textContent = (difference >= 0 ? '₱' : '-₱') + Math.abs(difference).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('differenceAmount').style.color = difference >= 0 ? '#dc3545' : '#28a745';
            document.getElementById('differenceLabel').textContent = difference >= 0 ? 'Additional Charge:' : 'Credit Amount:';
            
            document.getElementById('priceSummary').style.display = 'block';
            
            // Show payment section only if there's an additional charge
            if (difference > 0) {
                document.getElementById('paymentSection').style.display = 'block';
            } else {
                document.getElementById('paymentSection').style.display = 'none';
                document.querySelectorAll('input[name="payment_method"]').forEach(radio => radio.required = false);
            }
        }
    }
}

function selectPaymentMethod(method) {
    // Update radio buttons
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.checked = (radio.value === method);
    });
    
    // Update visual selection
    document.querySelectorAll('.payment-option-reschedule').forEach(el => {
        if (el.dataset.method === method) {
            el.classList.add('selected');
            el.style.borderColor = 'var(--primary-color)';
        } else {
            el.classList.remove('selected');
            el.style.borderColor = '#e0e0e0';
        }
    });
}

// Form validation
document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    const difference = parseFloat(document.getElementById('differenceAmount').textContent.replace(/[₱,-]/g, ''));
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    if (document.getElementById('priceSummary').style.display !== 'none' && 
        document.getElementById('differenceAmount').textContent.includes('Additional') &&
        !paymentMethod) {
        e.preventDefault();
        alert('Please select a payment method for the additional charges.');
        return false;
    }
});
</script>

<?php require_once '../includes/user-footer.php'; ?>
