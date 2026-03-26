<?php
$pageTitle = 'Manage Bookings';
require_once '../includes/config.php';

// Check if user is staff (admin, manager, or receptionist)
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Get booking ID and action from URL
$bookingId = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

// If no booking ID provided, show list of all pending bookings
if (!$bookingId) {
    // Get all pending bookings
    $pendingBookings = $db->query("
        SELECT b.*, u.first_name, u.last_name, u.email, u.phone, rc.category_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        WHERE b.status = 'pending'
        ORDER BY b.created_at DESC
    ")->fetchAll();
    
    require_once '../includes/staff-header.php';
    ?>
    
    <!-- Pending Bookings List Content -->
    <section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-clock"></i> Pending Bookings</h2>
                <a href="staff-dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;">All Pending Bookings (<?php echo count($pendingBookings); ?>)</h3>
                    <span style="background-color: var(--danger-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">Requires Confirmation</span>
                </div>
                
                <?php if (count($pendingBookings) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--gray-light);">
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Booking ID</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Type</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Dates</th>
                                <th style="padding: 15px 20px; text-align: right; font-size: 13px; color: #666; font-weight: 600;">Amount</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingBookings as $booking): ?>
                            <tr style="border-bottom: 1px solid var(--gray-light);">
                                <td style="padding: 15px 20px;">#<?php echo $booking['booking_id']; ?></td>
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($booking['email']); ?></div>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($booking['category_name']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <?php echo formatDate($booking['check_in']); ?> - <?php echo formatDate($booking['check_out']); ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: right; font-weight: 600; color: var(--primary-color);">
                                    <?php echo formatPrice($booking['total_amount']); ?>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <a href="confirm-booking.php?id=<?php echo $booking['booking_id']; ?>&action=confirm" class="btn btn-sm btn-success" style="padding: 5px 12px; font-size: 12px; background-color: #28a745; color: white; margin-right: 5px;">Confirm</a>
                                    <a href="confirm-booking.php?id=<?php echo $booking['booking_id']; ?>&action=cancel" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px; background-color: #dc3545; color: white;">Cancel</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding: 60px; text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">No pending bookings</h3>
                    <p style="color: #999;">All bookings have been confirmed or cancelled.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <?php require_once '../includes/staff-footer.php'; ?>
    <?php exit;
}

// If booking ID provided but no action, redirect to list
if (!$action || !in_array($action, ['confirm', 'cancel'])) {
    $_SESSION['error'] = 'Invalid action';
    redirect('confirm-booking.php');
}

if (!is_numeric($bookingId)) {
    $_SESSION['error'] = 'Invalid booking ID';
    redirect('confirm-booking.php');
}

$db = getDB();

// Get booking details
$stmt = $db->prepare("
    SELECT b.*, u.first_name, u.last_name, u.email, 
           rc.category_name, r.room_number
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    redirect('confirm-booking.php');
}

// Process the action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? false;
    
    if ($confirm) {
        try {
            $db->beginTransaction();
            
            if ($action === 'confirm') {
                // Confirm booking
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET status = 'confirmed', updated_at = CURRENT_TIMESTAMP 
                    WHERE booking_id = ?
                ");
                $stmt->execute([$bookingId]);
                
                $_SESSION['success'] = 'Booking confirmed successfully';
                
            } elseif ($action === 'cancel') {
                // Cancel booking
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET status = 'cancelled', updated_at = CURRENT_TIMESTAMP 
                    WHERE booking_id = ?
                ");
                $stmt->execute([$bookingId]);
                
                // Update room status if assigned
                if ($booking['room_id']) {
                    $stmt = $db->prepare("
                        UPDATE rooms 
                        SET status = 'available' 
                        WHERE room_id = ?
                    ");
                    $stmt->execute([$booking['room_id']]);
                }
                
                $_SESSION['success'] = 'Booking cancelled successfully';
            }
            
            $db->commit();
            
            // Send notifications to guest and admin
            require_once '../includes/notifications.php';
            
            $guestName = $booking['first_name'] . ' ' . $booking['last_name'];
            $checkInDate = $booking['check_in'];
            
            if ($action === 'confirm') {
                // Notify user about confirmation
                notifyBookingUpdate($booking['user_id'], $bookingId, 'confirmed');
                // Notify admin about confirmation
                notifyAdminBookingUpdate($bookingId, 'confirmed', $guestName, "Check-in: " . date('M d, Y', strtotime($checkInDate)));
            } elseif ($action === 'cancel') {
                // Notify user about cancellation
                notifyBookingUpdate($booking['user_id'], $bookingId, 'cancelled');
                // Notify admin about cancellation
                notifyAdminBookingUpdate($bookingId, 'cancelled', $guestName, "Booking was cancelled by staff");
            }
            
            redirect('confirm-booking.php');
            
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Action cancelled';
        redirect('confirm-booking.php');
    }
}

require_once '../includes/staff-header.php';
?>

<!-- Booking Management Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: var(--primary-color);">
                <i class="fas fa-<?php echo $action === 'confirm' ? 'check-circle' : 'times-circle'; ?>"></i>
                <?php echo ucfirst($action); ?> Booking
            </h2>
            <a href="confirm-booking.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div style="max-width: 800px; margin: 0 auto;">
            <!-- Booking Summary -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-<?php echo $action === 'confirm' ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo ucfirst($action); ?> Booking
                </h2>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <!-- Guest Information -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Guest Information</h4>
                        <div style="margin-bottom: 10px;">
                            <strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?>
                        </div>
                    </div>
                    
                    <!-- Booking Information -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: var(--primary-color);">Booking Information</h4>
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
                            <strong>Guests:</strong> <?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Total Amount:</strong> <span style="color: var(--primary-color); font-weight: 600;"><?php echo formatPrice($booking['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($booking['special_requests']): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--gray-light);">
                    <h4 style="margin-bottom: 10px; color: var(--primary-color);">Special Requests</h4>
                    <p style="font-style: italic;"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Confirmation Form -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <form method="POST">
                    <div style="background-color: <?php echo $action === 'confirm' ? '#d4edda' : '#f8d7da'; ?>; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 10px 0; color: <?php echo $action === 'confirm' ? '#155724' : '#721c24'; ?>">
                            <i class="fas fa-exclamation-triangle"></i>
                            Confirmation Required
                        </h4>
                        <p style="margin: 0; color: <?php echo $action === 'confirm' ? '#155724' : '#721c24'; ?>">
                            Are you sure you want to <?php echo $action; ?> this booking?
                            <?php if ($action === 'confirm'): ?>
                                This will confirm the reservation and the guest will be notified.
                            <?php else: ?>
                                This will cancel the reservation and may incur cancellation fees.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" name="confirm" value="1" 
                                class="btn" 
                                style="background-color: <?php echo $action === 'confirm' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 12px 30px;">
                            <i class="fas fa-<?php echo $action === 'confirm' ? 'check' : 'times'; ?>"></i>
                            Yes, <?php echo ucfirst($action); ?> Booking
                        </button>
                        <a href="confirm-booking.php" class="btn btn-outline" style="padding: 12px 30px;">
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
