<?php
$pageTitle = 'My Event Bookings';
require_once '../includes/config.php';

// Authentication check must happen before any output
if (!isLoggedIn()) {
    redirect('../auth/login.php');
    exit();
}

$db = getDB();
$userId = getUserId();

// Handle cancel booking - do this before including header to avoid header conflicts
if (isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    if ($bookingId) {
        // Verify booking belongs to user and can be cancelled
        $checkStmt = $db->prepare("SELECT status FROM event_bookings WHERE event_booking_id = ? AND user_id = ?");
        $checkStmt->execute([$bookingId, $userId]);
        $booking = $checkStmt->fetch();

        if ($booking && in_array($booking['status'], ['pending', 'confirmed'])) {
            $stmt = $db->prepare("UPDATE event_bookings SET status = 'cancelled' WHERE event_booking_id = ?");
            $stmt->execute([$bookingId]);
            $_SESSION['success'] = 'Event booking cancelled successfully';
        } else {
            $_SESSION['error'] = 'Cannot cancel this booking';
        }
    }
    redirect('my-event-bookings.php');
    exit(); // Ensure script stops after redirect
}

// Get user's event bookings
$stmt = $db->prepare("
    SELECT eb.*, es.space_name, es.capacity, es.price_per_day
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE eb.user_id = ?
    ORDER BY eb.event_date DESC, eb.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

// Define status colors for event bookings
$statusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'confirmed' => ['#d4edda', '#155724'],
    'completed' => ['#cce5ff', '#004085'],
    'cancelled' => ['#f8d7da', '#721c24']
];

require_once '../includes/user-header.php'; ?>

        <!-- Page Header Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h2 style="margin: 0; color: var(--dark-color);">My Event Bookings</h2>
            </div>
            <div style="display: flex; gap: 15px;">
                <a href="../events.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus" style="margin-right: 5px;"></i> Book New Event
                </a>
            </div>
        </div>

        <!-- Event Bookings -->
        <?php if (count($bookings) > 0): ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($bookings as $booking):
            $color = $statusColors[$booking['status']] ?? ['#e2e3e5', '#383d41'];
            ?>
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="display: flex;">
                    <!-- Left: Date Display -->
                    <div style="width: 100px; background-color: var(--primary-color); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: 700;"><?php echo date('d', strtotime($booking['event_date'])); ?></div>
                        <div style="font-size: 14px; text-transform: uppercase;"><?php echo date('M', strtotime($booking['event_date'])); ?></div>
                        <div style="font-size: 12px; opacity: 0.8;"><?php echo date('Y', strtotime($booking['event_date'])); ?></div>
                    </div>

                    <!-- Right: Details -->
                    <div style="flex: 1; padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($booking['space_name']); ?></h3>
                                <p style="margin: 0; color: #666;"><i class="fas fa-building"></i> Capacity: <?php echo number_format($booking['capacity']); ?> guests</p>
                            </div>
                            <span style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                <?php echo $booking['status']; ?>
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-clock"></i> Time</div>
                                <div style="font-weight: 500;">
                                    <?php if ($booking['start_time']): ?>
                                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
                                        <?php if ($booking['end_time']): ?>
                                            - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">To be confirmed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-users"></i> Guests</div>
                                <div style="font-weight: 500;"><?php echo $booking['guests_count'] ? number_format($booking['guests_count']) : 'Not specified'; ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-utensils"></i> Catering</div>
                                <div style="font-weight: 500;"><?php echo $booking['catering_required'] ? 'Yes' : 'No'; ?></div>
                            </div>
                        </div>

                        <?php if ($booking['event_type']): ?>
                        <div style="margin-bottom: 15px;">
                            <span style="padding: 4px 12px; border-radius: 15px; font-size: 12px; background-color: #e3f2fd; color: #1976d2;">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($booking['event_type']); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if ($booking['special_requests']): ?>
                        <div style="background-color: var(--gray-light); padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-comment"></i> Special Requests</div>
                            <div style="font-size: 14px;"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid var(--gray-light);">
                            <div>
                                <div style="font-size: 12px; color: #666;">
                                    <i class="fas fa-calendar"></i> Booked on: <?php echo formatDate($booking['created_at'], 'M d, Y'); ?>
                                </div>
                                <?php if ($booking['quoted_price']): ?>
                                <div style="font-size: 14px; color: var(--primary-color); font-weight: 600; margin-top: 5px;">
                                    Quoted Price: <?php echo formatPrice($booking['quoted_price']); ?>
                                </div>
                                <?php else: ?>
                                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                                    <i class="fas fa-info-circle"></i> Price quote pending
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this event booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['event_booking_id']; ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-danger" style="padding: 10px 20px;">Cancel Booking</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 80px 20px; background-color: white; border-radius: 10px;">
            <i class="fas fa-calendar-times" style="font-size: 64px; color: var(--gray-light); margin-bottom: 20px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">No Event Bookings Yet</h3>
            <p style="color: #999; margin-bottom: 30px;">You haven't booked any event spaces yet. Start planning your next event!</p>
            <a href="../events.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">Browse Event Spaces</a>
        </div>
        <?php endif; ?>
    </div>
        <?php require_once '../includes/user-footer.php'; ?>
