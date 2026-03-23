<?php
$pageTitle = 'Manage Bookings - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle status update
if (isset($_POST['update_status'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
    
    if ($bookingId && in_array($newStatus, $validStatuses)) {
        $stmt = $db->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        if ($stmt->execute([$newStatus, $bookingId])) {
            $_SESSION['success'] = 'Booking status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update booking status';
        }
    }
    redirect('admin-bookings.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT b.*, 
           rc.category_name, 
           r.room_number,
           u.first_name, u.last_name, u.email, u.phone
    FROM bookings b 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN users u ON b.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}

if ($dateFrom) {
    $sql .= " AND b.check_in >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND b.check_out <= ?";
    $params[] = $dateTo;
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR b.booking_id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get status counts for filter
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM bookings 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<!-- Bookings Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or booking ID..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending (<?php echo $statusCounts['pending'] ?? 0; ?>)</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed (<?php echo $statusCounts['confirmed'] ?? 0; ?>)</option>
                        <option value="checked_in" <?php echo $statusFilter === 'checked_in' ? 'selected' : ''; ?>>Checked In (<?php echo $statusCounts['checked_in'] ?? 0; ?>)</option>
                        <option value="checked_out" <?php echo $statusFilter === 'checked_out' ? 'selected' : ''; ?>>Checked Out (<?php echo $statusCounts['checked_out'] ?? 0; ?>)</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled (<?php echo $statusCounts['cancelled'] ?? 0; ?>)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-bookings.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">All Bookings (<?php echo count($bookings); ?>)</h3>
            </div>
            
            <?php if (count($bookings) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Booking ID</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Check In</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Check Out</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Amount</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): 
                            $statusColors = [
                                'pending' => ['#fff3cd', '#856404'],
                                'confirmed' => ['#d4edda', '#155724'],
                                'checked_in' => ['#cce5ff', '#004085'],
                                'checked_out' => ['#e2e3e5', '#383d41'],
                                'cancelled' => ['#f8d7da', '#721c24']
                            ];
                            $color = $statusColors[$booking['status']] ?? ['#fff3cd', '#856404'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">#<?php echo $booking['booking_id']; ?></td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($booking['email']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo htmlspecialchars($booking['category_name']); ?>
                                <?php if ($booking['room_number']): ?>
                                    <div style="font-size: 12px; color: #666;">Room <?php echo $booking['room_number']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;"><?php echo formatDate($booking['check_in'], 'M d, Y'); ?></td>
                            <td style="padding: 15px 20px;"><?php echo formatDate($booking['check_out'], 'M d, Y'); ?></td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($booking['total_amount']); ?></td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <a href="admin-booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">View</a>
                                    
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 5px 10px; font-size: 12px; border: 1px solid var(--gray-light); border-radius: 4px;">
                                            <option value="">Change Status</option>
                                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'disabled' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'disabled' : ''; ?>>Confirmed</option>
                                            <option value="checked_in" <?php echo $booking['status'] === 'checked_in' ? 'disabled' : ''; ?>>Checked In</option>
                                            <option value="checked_out" <?php echo $booking['status'] === 'checked_out' ? 'disabled' : ''; ?>>Checked Out</option>
                                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'disabled' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-calendar-times" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No bookings found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
