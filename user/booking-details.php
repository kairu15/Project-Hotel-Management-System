<?php
$pageTitle = 'Booking Details';
require_once '../includes/config.php';
require_once '../includes/qr_code_helper.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

// Get booking ID from URL
$bookingId = $_GET['id'] ?? null;
if (!$bookingId || !is_numeric($bookingId)) {
    $_SESSION['error'] = 'Invalid booking ID';
    redirect('my-bookings.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Get booking details with related information
$stmt = $db->prepare("
    SELECT b.*, 
           rc.category_name, rc.description as category_description, rc.amenities as category_amenities,
           r.room_number,
           u.first_name, u.last_name, u.email, u.phone
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN users u ON b.user_id = u.user_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = 'Booking not found';
    redirect('my-bookings.php');
}

// Get payment history
$paymentsStmt = $db->prepare("
    SELECT * FROM payments 
    WHERE booking_id = ? 
    ORDER BY payment_date DESC
");
$paymentsStmt->execute([$bookingId]);
$payments = $paymentsStmt->fetchAll();

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

// Check if can cancel (48 hours before check-in)
$canCancel = in_array($booking['status'], ['pending', 'confirmed']) && 
             strtotime($booking['check_in']) > strtotime('+48 hours');
?>

<!-- Booking Header -->
<div class="card" style="margin-bottom: 30px;">
    <div class="card-body">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; flex-wrap: wrap; gap: 20px;">
            <div>
                <h2 style="font-size: 28px; margin-bottom: 10px;">Booking #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                <p style="color: #666; margin-bottom: 15px;">Made on <?php echo formatDate($booking['created_at'], 'F d, Y \a\t h:i A'); ?></p>
                
                <!-- QR Code at bottom of booking info -->
                <?php 
                $qrCodeUrl = generateSimpleQRCode($booking['booking_ref'] ?? '', 100, $booking['booking_id']);
                if ($qrCodeUrl): 
                ?>
                <div style="margin-top: 15px;">
                    <div style="display: inline-block; background: white; padding: 12px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 2px solid var(--primary-color);">
                        <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" style="width: 100px; height: 100px; display: block;">
                    </div>
                    <p style="font-size: 11px; color: #666; margin-top: 8px; margin-bottom: 0;"><i class="fas fa-qrcode"></i> Scan at hotel for quick check-in</p>
                </div>
                <?php endif; ?>
            </div>
            <div style="text-align: right;">
                <span style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 25px; font-size: 14px; font-weight: 600; background-color: <?php echo $status[0]; ?>; color: <?php echo $status[1]; ?>; margin-bottom: 10px;">
                    <i class="fas fa-<?php echo $status[2]; ?>"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                </span>
                <br>
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 15px; border-radius: 15px; font-size: 12px; font-weight: 600; background-color: <?php echo $paymentStatus[0]; ?>; color: <?php echo $paymentStatus[1]; ?>">
                    Payment: <?php echo ucfirst($booking['payment_status']); ?>
                </span>
            </div>
        </div>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="my-bookings.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
            <?php if ($canCancel): ?>
            <button type="button" class="btn btn-danger" onclick="openDeleteModal(null, 'Cancel Booking', 'Are you sure you want to cancel Booking #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?>? This action cannot be undone.', '<?php echo SITE_URL; ?>/user/cancel-booking.php?id=<?php echo $booking['booking_id']; ?>')">
                <i class="fas fa-times"></i> Cancel Booking
            </button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-outline">
                <i class="fas fa-print"></i> Print Details
            </button>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
    <!-- Room Information -->
    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
            <h3 style="margin: 0; font-size: 18px;">
                <i class="fas fa-bed"></i> Room Information
            </h3>
        </div>
        <div style="padding: 25px;">
            <div style="margin-bottom: 20px;">
                <h4 style="color: var(--primary-color); margin-bottom: 5px;"><?php echo htmlspecialchars($booking['category_name']); ?></h4>
                <?php if ($booking['room_number']): ?>
                <p style="color: #666; font-size: 14px;">Room Number: <?php echo htmlspecialchars($booking['room_number']); ?></p>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
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
            
            <?php if ($booking['category_amenities']): ?>
            <div>
                <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Room Amenities</p>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php 
                    $amenities = explode(',', $booking['category_amenities']);
                    foreach ($amenities as $amenity): ?>
                    <span style="background-color: var(--gray-light); padding: 4px 10px; border-radius: 15px; font-size: 12px;">
                        <?php echo trim($amenity); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Guest Information -->
    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
            <h3 style="margin: 0; font-size: 18px;">
                <i class="fas fa-user"></i> Guest Information
            </h3>
        </div>
        <div style="padding: 25px;">
            <div style="margin-bottom: 15px;">
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Name</p>
                <p style="font-weight: 600;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
            </div>
            <div style="margin-bottom: 15px;">
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Email</p>
                <p style="font-weight: 600;"><?php echo htmlspecialchars($booking['email']); ?></p>
            </div>
            <?php if ($booking['phone']): ?>
            <div style="margin-bottom: 15px;">
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Phone</p>
                <p style="font-weight: 600;"><?php echo htmlspecialchars($booking['phone']); ?></p>
            </div>
            <?php endif; ?>
            <?php if ($booking['special_requests']): ?>
            <div>
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Special Requests</p>
                <p style="font-style: italic;"><?php echo htmlspecialchars($booking['special_requests']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Payment Information -->
<div class="card" style="margin-top: 30px;">
    <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px;">
        <h3 style="margin: 0; font-size: 18px;">
            <i class="fas fa-credit-card"></i> Payment Information
        </h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 25px;">
            <div>
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Room Rate</p>
                <p style="font-size: 18px; font-weight: 600;"><?php echo formatPrice($booking['room_rate']); ?> / night</p>
            </div>
            <div>
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Payment Method</p>
                <p style="font-size: 18px; font-weight: 600;"><?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?></p>
            </div>
            <div>
                <p style="font-size: 12px; color: #666; margin-bottom: 5px;">Total Amount</p>
                <p style="font-size: 24px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($booking['total_amount']); ?></p>
            </div>
        </div>
        
        <?php if (!empty($payments)): ?>
        <div>
            <h4 style="margin-bottom: 15px; color: var(--primary-color);">Payment History</h4>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Date</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Amount</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Method</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; font-size: 14px;">Status</th>
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
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/user-footer.php'; ?>
