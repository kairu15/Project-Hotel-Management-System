<?php
$pageTitle = 'Check-in Guests';
require_once '../includes/config.php';

// Check if user is staff (admin, manager, or receptionist)
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Get booking ID from URL
$bookingId = $_GET['id'] ?? null;

// If no booking ID provided, show list of all arrivals
if (!$bookingId) {
    $today = date('Y-m-d');
    
    // Get today's arrivals
    $arrivals = $db->query("
        SELECT b.*, u.first_name, u.last_name, u.email, u.phone, rc.category_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.check_in = '$today'
        AND b.status IN ('confirmed', 'checked_in')
        ORDER BY b.check_in
    ")->fetchAll();
    
    require_once '../includes/staff-header.php';
    ?>
    
    <!-- Check-in List Content -->
    <section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-sign-in-alt"></i> Today's Arrivals</h2>
                <a href="staff-dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
            
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;">All Arrivals (<?php echo count($arrivals); ?>)</h3>
                    <span style="background-color: var(--info-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo date('M d, Y'); ?></span>
                </div>
                
                <?php if (count($arrivals) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: var(--gray-light);">
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Type</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Check-in Date</th>
                                <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                                <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($arrivals as $arrival): 
                                $statusColors = [
                                    'confirmed' => ['#fff3cd', '#856404'],
                                    'checked_in' => ['#d4edda', '#155724']
                                ];
                                $color = $statusColors[$arrival['status']] ?? ['#fff3cd', '#856404'];
                            ?>
                            <tr style="border-bottom: 1px solid var(--gray-light);">
                                <td style="padding: 15px 20px;">
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($arrival['first_name'] . ' ' . $arrival['last_name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($arrival['email']); ?></div>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo htmlspecialchars($arrival['category_name']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <?php if ($arrival['room_number']): ?>
                                        Room <?php echo htmlspecialchars($arrival['room_number']); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px 20px;"><?php echo formatDate($arrival['check_in']); ?></td>
                                <td style="padding: 15px 20px;">
                                    <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $arrival['status'])); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px 20px; text-align: center;">
                                    <?php if ($arrival['status'] === 'checked_in'): ?>
                                        <span style="color: #28a745;"><i class="fas fa-check-circle"></i> Completed</span>
                                    <?php else: ?>
                                        <a href="checkin.php?id=<?php echo $arrival['booking_id']; ?>" class="btn btn-sm btn-primary" style="padding: 6px 15px; font-size: 12px;">Check In</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding: 60px; text-align: center;">
                    <i class="fas fa-calendar-times" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                    <h3 style="color: #666;">No arrivals scheduled for today</h3>
                    <p style="color: #999;">All guests have been checked in or no bookings for today.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <?php require_once '../includes/staff-footer.php'; ?>
    <?php exit;
}

// If booking ID provided, show individual check-in detail
if (!is_numeric($bookingId)) {
    $_SESSION['error'] = 'Invalid booking ID';
    redirect('checkin.php');
}

$db = getDB();

// Get booking details with room information
$stmt = $db->prepare("
    SELECT b.*, u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.country,
           rc.category_name, rc.max_occupancy,
           r.room_number, r.floor
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.booking_id = ? AND b.status = 'confirmed'
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = 'Booking not found or not eligible for check-in';
    redirect('checkin.php');
}

// Get available rooms for this category if no room assigned
if (!$booking['room_id']) {
    $availableRoomsStmt = $db->prepare("
        SELECT room_id, room_number, floor 
        FROM rooms 
        WHERE category_id = ? AND status = 'available'
        ORDER BY room_number
    ");
    $availableRoomsStmt->execute([$booking['category_id']]);
    $availableRooms = $availableRoomsStmt->fetchAll();
} else {
    $availableRooms = [];
}

// Process check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? false;
    $selectedRoomId = $_POST['room_id'] ?? null;
    
    if ($confirm) {
        try {
            $db->beginTransaction();
            
            // Assign room if not already assigned
            if (!$booking['room_id'] && $selectedRoomId) {
                $booking['room_id'] = $selectedRoomId;
                
                // Update booking with room assignment
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET room_id = ?, status = 'checked_in', checked_in_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE booking_id = ?
                ");
                $stmt->execute([$selectedRoomId, $bookingId]);
                
                // Update room status to occupied
                $stmt = $db->prepare("
                    UPDATE rooms 
                    SET status = 'occupied' 
                    WHERE room_id = ?
                ");
                $stmt->execute([$selectedRoomId]);
                
            } else {
                // Just update booking status if room already assigned
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET status = 'checked_in', checked_in_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE booking_id = ?
                ");
                $stmt->execute([$bookingId]);
                
                // Update room status to occupied
                $stmt = $db->prepare("
                    UPDATE rooms 
                    SET status = 'occupied' 
                    WHERE room_id = ?
                ");
                $stmt->execute([$booking['room_id']]);
            }
            
            $db->commit();
            
            // Send notification to guest about check-in
            require_once '../includes/notifications.php';
            notifyBookingUpdate($booking['user_id'], $bookingId, 'checked_in');
            
            $_SESSION['success'] = 'Guest checked in successfully';
            redirect('checkin.php');
            
        } catch (Exception $e) {
            $db->rollback();
            $_SESSION['error'] = 'Error during check-in: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Check-in cancelled';
        redirect('checkin.php');
    }
}

require_once '../includes/staff-header.php';
?>

<!-- Check-in Detail Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-sign-in-alt"></i> Guest Check-in</h2>
            <a href="checkin.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>
        <div style="max-width: 900px; margin: 0 auto;">
            <!-- Guest Information -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-user"></i> Guest Information
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <div>
                        <div style="margin-bottom: 15px;">
                            <strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone'] ?: 'Not provided'); ?>
                        </div>
                    </div>
                    <div>
                        <div style="margin-bottom: 15px;">
                            <strong>Address:</strong> <?php echo htmlspecialchars($booking['address'] ?: 'Not provided'); ?>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>City:</strong> <?php echo htmlspecialchars($booking['city'] ?: 'Not provided'); ?>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <strong>Country:</strong> <?php echo htmlspecialchars($booking['country'] ?: 'Not provided'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Information -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-calendar-check"></i> Booking Details
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div>
                        <div style="margin-bottom: 10px;">
                            <strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Room Type:</strong> <?php echo htmlspecialchars($booking['category_name']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Check-in:</strong> <?php echo formatDate($booking['check_in']); ?>
                        </div>
                    </div>
                    <div>
                        <div style="margin-bottom: 10px;">
                            <strong>Check-out:</strong> <?php echo formatDate($booking['check_out']); ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Nights:</strong> <?php echo $booking['nights']; ?>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Guests:</strong> <?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children
                        </div>
                    </div>
                    <div>
                        <div style="margin-bottom: 10px;">
                            <strong>Status:</strong> 
                            <span style="padding: 4px 10px; border-radius: 15px; font-size: 12px; background-color: #cce5ff; color: #004085;">
                                <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                            </span>
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
            
            <!-- Room Assignment -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 25px; color: var(--primary-color);">
                    <i class="fas fa-bed"></i> Room Assignment
                </h3>
                
                <?php if ($booking['room_id']): ?>
                <div style="background-color: #d4edda; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #155724;">Room Already Assigned</h4>
                    <p style="margin: 0; color: #155724;">
                        <strong>Room <?php echo htmlspecialchars($booking['room_number']); ?></strong>
                        <?php if ($booking['floor']): ?>
                        (Floor <?php echo htmlspecialchars($booking['floor']); ?>)
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #856404;">Select Room for Assignment</h4>
                    <p style="margin: 0; color: #856404;">
                        Please select a room from the available rooms below.
                    </p>
                </div>
                
                <?php if (!empty($availableRooms)): ?>
                <form method="POST" id="checkinForm">
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                        <?php foreach ($availableRooms as $room): ?>
                        <label style="display: block; border: 2px solid var(--gray-light); border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;" 
                               onmouseover="this.style.borderColor='var(--primary-color)'; this.style.backgroundColor='var(--gray-light)';"
                               onmouseout="this.style.borderColor='var(--gray-light)'; this.style.backgroundColor='white';">
                            <input type="radio" name="room_id" value="<?php echo $room['room_id']; ?>" required style="margin-right: 10px;">
                            <div style="font-weight: 600; color: var(--primary-color);">Room <?php echo htmlspecialchars($room['room_number']); ?></div>
                            <?php if ($room['floor']): ?>
                            <div style="font-size: 14px; color: #666;">Floor <?php echo htmlspecialchars($room['floor']); ?></div>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" name="confirm" value="1" 
                                class="btn" 
                                style="background-color: #28a745; color: white; padding: 12px 30px;">
                            <i class="fas fa-sign-in-alt"></i>
                            Confirm Check-in
                        </button>
                        <a href="checkin.php" class="btn btn-outline" style="padding: 12px 30px;">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #721c24;">No Available Rooms</h4>
                    <p style="margin: 0; color: #721c24;">
                        There are no available rooms for this category. Please make rooms available or contact management.
                    </p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Check-in Confirmation -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <?php if ($booking['room_id']): ?>
                <form method="POST">
                    <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 10px 0; color: #155724;">
                            <i class="fas fa-check-circle"></i>
                            Confirm Check-in
                        </h4>
                        <p style="margin: 0; color: #155724;">
                            This guest has already been assigned a room. Confirm check-in to mark them as checked in.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" name="confirm" value="1" 
                                class="btn" 
                                style="background-color: #28a745; color: white; padding: 12px 30px;">
                            <i class="fas fa-sign-in-alt"></i>
                            Confirm Check-in
                        </button>
                        <a href="checkin.php" class="btn btn-outline" style="padding: 12px 30px;">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                    </div>
                </form>
                <?php else: ?>
                <form method="POST">
                    <div style="background-color: #d4edda; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                        <h4 style="margin: 0 0 10px 0; color: #155724;">
                            <i class="fas fa-check-circle"></i>
                            Confirm Check-in
                        </h4>
                        <p style="margin: 0; color: #155724;">
                            Are you ready to check-in this guest? Select a room above and confirm to complete check-in.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <button type="submit" name="confirm" value="1" 
                                class="btn" 
                                style="background-color: #28a745; color: white; padding: 12px 30px;"
                                <?php echo empty($availableRooms) ? 'disabled' : ''; ?>>
                            <i class="fas fa-sign-in-alt"></i>
                            Confirm Check-in
                        </button>
                        <a href="checkin.php" class="btn btn-outline" style="padding: 12px 30px;">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/staff-footer.php'; ?>
