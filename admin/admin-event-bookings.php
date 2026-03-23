<?php
$pageTitle = 'Manage Event Bookings - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle update status
if (isset($_POST['update_status'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $quotedPrice = $_POST['quoted_price'] ?? null;

    if ($bookingId && $status) {
        if ($quotedPrice !== null) {
            $stmt = $db->prepare("UPDATE event_bookings SET status = ?, quoted_price = ? WHERE event_booking_id = ?");
            $stmt->execute([$status, $quotedPrice, $bookingId]);
        } else {
            $stmt = $db->prepare("UPDATE event_bookings SET status = ? WHERE event_booking_id = ?");
            $stmt->execute([$status, $bookingId]);
        }
        $_SESSION['success'] = 'Booking status updated successfully';
    }
    redirect('admin-event-bookings.php');
}

// Handle delete booking
if (isset($_POST['delete_booking'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    if ($bookingId) {
        $stmt = $db->prepare("DELETE FROM event_bookings WHERE event_booking_id = ?");
        if ($stmt->execute([$bookingId])) {
            $_SESSION['success'] = 'Event booking deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete booking';
        }
    }
    redirect('admin-event-bookings.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$spaceFilter = $_GET['space'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT eb.*, u.first_name, u.last_name, u.email, u.phone, es.space_name
    FROM event_bookings eb
    JOIN users u ON eb.user_id = u.user_id
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $sql .= " AND eb.status = ?";
    $params[] = $statusFilter;
}

if ($spaceFilter) {
    $sql .= " AND eb.space_id = ?";
    $params[] = $spaceFilter;
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR es.space_name LIKE ? OR eb.event_type LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY eb.event_date DESC, eb.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get event spaces for filter
$spaces = $db->query("SELECT space_id, space_name FROM event_spaces ORDER BY space_name")->fetchAll();

// Status counts
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count FROM event_bookings GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <a href="admin-event-spaces.php" class="btn btn-primary">Manage Event Spaces</a>
        </div>

        <!-- Status Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php
            $statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
            $statusColors = [
                'pending' => ['#fff3cd', '#856404'],
                'confirmed' => ['#d4edda', '#155724'],
                'completed' => ['#cce5ff', '#004085'],
                'cancelled' => ['#f8d7da', '#721c24']
            ];
            foreach ($statuses as $status):
                $count = $statusCounts[$status] ?? 0;
                $color = $statusColors[$status];
            ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid <?php echo $color[1]; ?>;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $count; ?></h3>
                        <p style="color: #666; margin: 5px 0 0; text-transform: capitalize;"><?php echo $status; ?></p>
                    </div>
                    <i class="fas fa-calendar-check" style="font-size: 32px; color: <?php echo $color[1]; ?>"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, space, event type..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Event Space</label>
                    <select name="space" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Spaces</option>
                        <?php foreach ($spaces as $space): ?>
                        <option value="<?php echo $space['space_id']; ?>" <?php echo $spaceFilter == $space['space_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($space['space_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-event-bookings.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Event Bookings (<?php echo count($bookings); ?>)</h3>
            </div>

            <?php if (count($bookings) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Client</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Event Details</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Space</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Date & Time</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guests</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking):
                            $color = $statusColors[$booking['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($booking['email']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($booking['phone']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['event_type'] ?: 'General Event'); ?></div>
                                <?php if ($booking['catering_required']): ?>
                                <div style="font-size: 12px; color: var(--success-color);"><i class="fas fa-utensils"></i> Catering Required</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo htmlspecialchars($booking['space_name']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div><?php echo formatDate($booking['event_date'], 'M d, Y'); ?></div>
                                <div style="font-size: 12px; color: #666;">
                                    <?php echo $booking['start_time'] ? date('g:i A', strtotime($booking['start_time'])) : 'TBD'; ?>
                                    <?php echo $booking['end_time'] ? ' - ' . date('g:i A', strtotime($booking['end_time'])) : ''; ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo number_format($booking['guests_count'] ?: 0); ?> guests
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo $booking['quoted_price'] ? formatPrice($booking['quoted_price']) : 'Pending Quote'; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $booking['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>)" class="btn btn-sm btn-secondary" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-eye"></i> View</button>
                                    <button type="button" onclick="editBooking(<?php echo htmlspecialchars(json_encode($booking)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Update</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['event_booking_id']; ?>">
                                        <button type="submit" name="delete_booking" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-calendar-alt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No event bookings found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- View Details Modal -->
<div id="detailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">Booking Details</h3>
            <button onclick="closeDetailsModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div id="detailsContent" style="padding: 30px;">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Booking Modal -->
<div id="bookingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">Update Booking</h3>
            <button onclick="closeBookingModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="booking_id" id="booking_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="booking_status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Quoted Price (PHP)</label>
                <input type="number" name="quoted_price" id="quoted_price" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeBookingModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
function editBooking(booking) {
    document.getElementById('bookingModal').style.display = 'flex';
    document.getElementById('booking_id').value = booking.event_booking_id;
    document.getElementById('booking_status').value = booking.status;
    document.getElementById('quoted_price').value = booking.quoted_price || '';
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function viewDetails(booking) {
    const content = document.getElementById('detailsContent');
    const cateringIcon = booking.catering_required ? '<i class="fas fa-check" style="color: var(--success-color);"></i> Yes' : '<i class="fas fa-times" style="color: #999;"></i> No';
    const statusColors = {
        'pending': ['#fff3cd', '#856404'],
        'confirmed': ['#d4edda', '#155724'],
        'completed': ['#cce5ff', '#004085'],
        'cancelled': ['#f8d7da', '#721c24']
    };
    const color = statusColors[booking.status] || ['#e2e3e5', '#383d41'];
    
    content.innerHTML = `
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Customer Information</h4>
            <p style="margin: 0; font-size: 16px; font-weight: 500;">${booking.first_name} ${booking.last_name}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-envelope"></i> ${booking.email}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-phone"></i> ${booking.phone || 'N/A'}</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Event Information</h4>
            <p style="margin: 0; font-size: 16px; font-weight: 500;">${booking.event_type ? booking.event_type.charAt(0).toUpperCase() + booking.event_type.slice(1) : 'General Event'}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-calendar"></i> ${booking.event_date}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-clock"></i> ${booking.start_time || 'TBD'} - ${booking.end_time || 'TBD'}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-users"></i> ${booking.guests_count || 0} guests</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-utensils"></i> Catering Required: ${cateringIcon}</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Venue</h4>
            <p style="margin: 0; font-size: 16px; font-weight: 500;">${booking.space_name}</p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Status</h4>
            <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: ${color[0]}; color: ${color[1]}; text-transform: capitalize;">
                ${booking.status}
            </span>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Quoted Price</h4>
            <p style="margin: 0; font-size: 18px; font-weight: 600; color: var(--primary-color);">
                ${booking.quoted_price ? '₱' + parseFloat(booking.quoted_price).toLocaleString('en-PH', {minimumFractionDigits: 2}) : 'Pending Quote'}
            </p>
        </div>
        
        ${booking.special_requests ? `
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Special Requests</h4>
            <p style="margin: 0; font-size: 14px; color: #666; white-space: pre-wrap;">${booking.special_requests}</p>
        </div>
        ` : ''}
        
        <div style="margin-bottom: 0;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Booking Details</h4>
            <p style="margin: 0; font-size: 14px; color: #666;"><i class="fas fa-hashtag"></i> Booking ID: ${booking.event_booking_id}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-clock"></i> Submitted: ${booking.created_at}</p>
        </div>
    `;
    
    document.getElementById('detailsModal').style.display = 'flex';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookingModal();
    }
});

document.getElementById('detailsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailsModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
