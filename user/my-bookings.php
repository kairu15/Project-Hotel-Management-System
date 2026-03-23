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
        
        // Check if can cancel (48 hours before check-in)
        $canCancel = in_array($booking['status'], ['pending', 'confirmed']) && 
                     strtotime($booking['check_in']) > strtotime('+48 hours');
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
                        <?php if ($canCancel): ?>
                        <a href="cancel-booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm" style="background-color: #dc3545; color: white;" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
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

<?php require_once '../includes/user-footer.php'; ?>
