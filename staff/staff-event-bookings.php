<?php
$pageTitle = 'Event Bookings - Staff';
require_once '../includes/config.php';

// Check if user is staff (admin, manager, or receptionist)
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle confirm booking
if (isset($_POST['confirm_booking'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    $quotedPrice = $_POST['quoted_price'] ?? null;
    
    if ($bookingId) {
        if ($quotedPrice !== null && $quotedPrice !== '') {
            $stmt = $db->prepare("UPDATE event_bookings SET status = 'confirmed', quoted_price = ? WHERE event_booking_id = ?");
            $stmt->execute([$quotedPrice, $bookingId]);
        } else {
            $stmt = $db->prepare("UPDATE event_bookings SET status = 'confirmed' WHERE event_booking_id = ?");
            $stmt->execute([$bookingId]);
        }
        $_SESSION['success'] = 'Event booking confirmed successfully';
    }
    redirect('staff-event-bookings.php');
}

// Handle complete booking
if (isset($_POST['complete_booking'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    if ($bookingId) {
        $stmt = $db->prepare("UPDATE event_bookings SET status = 'completed' WHERE event_booking_id = ?");
        $stmt->execute([$bookingId]);
        $_SESSION['success'] = 'Event booking marked as completed successfully';
    }
    redirect('staff-event-bookings.php');
}

// Handle set quoted price
if (isset($_POST['set_price'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    $quotedPrice = $_POST['quoted_price'] ?? null;
    
    if ($bookingId && $quotedPrice !== null && $quotedPrice !== '') {
        $stmt = $db->prepare("UPDATE event_bookings SET quoted_price = ? WHERE event_booking_id = ?");
        $stmt->execute([$quotedPrice, $bookingId]);
        $_SESSION['success'] = 'Quoted price updated successfully';
    }
    redirect('staff-event-bookings.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT eb.*, es.space_name, es.capacity as space_capacity
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $sql .= " AND eb.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (eb.inquiry_name LIKE ? OR eb.inquiry_email LIKE ? OR eb.event_type LIKE ? OR es.space_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY eb.event_date ASC, eb.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Status counts
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count FROM event_bookings GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/staff-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <a href="staff-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
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
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, event type..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
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
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="staff-event-bookings.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
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
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Event Type</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Date & Time</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest Count</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Customer</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Space</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Quoted Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Payment</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking):
                            $color = $statusColors[$booking['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);" data-event-id="<?php echo $booking['event_booking_id']; ?>">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars(ucfirst($booking['event_type'] ?: 'General Event')); ?></div>
                                <?php if ($booking['catering_required']): ?>
                                <div style="font-size: 12px; color: var(--success-color);"><i class="fas fa-utensils"></i> Catering</div>
                                <?php endif; ?>
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
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['inquiry_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($booking['inquiry_email']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($booking['inquiry_phone']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo htmlspecialchars($booking['space_name']); ?>
                                <div style="font-size: 12px; color: #666;">Capacity: <?php echo $booking['space_capacity']; ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $booking['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo $booking['quoted_price'] ? formatPrice($booking['quoted_price']) : 'Pending'; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php 
                                $paymentStatus = $booking['payment_status'] ?? 'pending';
                                $paymentColors = [
                                    'pending' => ['#fff3cd', '#856404'],
                                    'paid' => ['#d4edda', '#155724'],
                                    'partial' => ['#cce5ff', '#004085'],
                                    'failed' => ['#f8d7da', '#721c24'],
                                    'refunded' => ['#e2e3e5', '#383d41']
                                ];
                                $payColor = $paymentColors[$paymentStatus] ?? ['#e2e3e5', '#383d41'];
                                ?>
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $payColor[0]; ?>; color: <?php echo $payColor[1]; ?>; text-transform: capitalize;">
                                    <?php echo $paymentStatus; ?>
                                    <?php if ($paymentStatus === 'partial' && $booking['amount_paid'] > 0): ?>
                                        (<?php echo formatPrice($booking['amount_paid']); ?>)
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                    <button type="button" onclick="openConfirmModal(<?php echo $booking['event_booking_id']; ?>)" class="btn btn-sm btn-success" style="padding: 5px 12px; font-size: 12px;">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                    <button type="button" onclick="openRejectModal(<?php echo $booking['event_booking_id']; ?>)" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['event_booking_id']; ?>">
                                        <button type="submit" name="complete_booking" class="btn btn-sm" style="padding: 5px 12px; font-size: 12px; background-color: #007bff; color: white;" onclick="return confirm('Mark this event booking as completed?');">
                                            <i class="fas fa-check-double"></i> Complete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <button type="button" onclick="openPriceModal(<?php echo $booking['event_booking_id']; ?>, <?php echo $booking['quoted_price'] ?: 'null'; ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">
                                        <i class="fas fa-tag"></i> Set Price
                                    </button>
                                    <button type="button" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>)" class="btn btn-sm btn-secondary" style="padding: 5px 12px; font-size: 12px;">
                                        <i class="fas fa-eye"></i> View
                                    </button>
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

<!-- Confirm Modal -->
<div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 400px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
            <h3 style="font-size: 20px; margin: 0;"><i class="fas fa-check-circle" style="color: var(--success-color);"></i> Confirm Booking</h3>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="booking_id" id="confirm_booking_id">
            <p style="margin-bottom: 20px;">Are you sure you want to confirm this event booking?</p>
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Quoted Price (PHP) - Optional</label>
                <input type="number" name="quoted_price" step="0.01" min="0" placeholder="Enter price..." style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeConfirmModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="confirm_booking" class="btn btn-success">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 400px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
            <h3 style="font-size: 20px; margin: 0;"><i class="fas fa-times-circle" style="color: var(--danger-color);"></i> Reject Booking</h3>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="booking_id" id="reject_booking_id">
            <p style="margin-bottom: 20px;">Are you sure you want to reject this event booking?</p>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeRejectModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="reject_booking" class="btn btn-danger">Reject Booking</button>
            </div>
        </form>
    </div>
</div>

<!-- Set Price Modal -->
<div id="priceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 400px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
            <h3 style="font-size: 20px; margin: 0;"><i class="fas fa-tag" style="color: var(--primary-color);"></i> Set Quoted Price</h3>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="booking_id" id="price_booking_id">
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Quoted Price (PHP)</label>
                <input type="number" name="quoted_price" id="price_input" step="0.01" min="0" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closePriceModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="set_price" class="btn btn-primary">Set Price</button>
            </div>
        </form>
    </div>
</div>

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

<script>
function openConfirmModal(bookingId) {
    document.getElementById('confirmModal').style.display = 'flex';
    document.getElementById('confirm_booking_id').value = bookingId;
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function openRejectModal(bookingId) {
    document.getElementById('rejectModal').style.display = 'flex';
    document.getElementById('reject_booking_id').value = bookingId;
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function openPriceModal(bookingId, currentPrice) {
    document.getElementById('priceModal').style.display = 'flex';
    document.getElementById('price_booking_id').value = bookingId;
    document.getElementById('price_input').value = currentPrice || '';
}

function closePriceModal() {
    document.getElementById('priceModal').style.display = 'none';
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
            <p style="margin: 0; font-size: 16px; font-weight: 500;">${booking.inquiry_name}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-envelope"></i> ${booking.inquiry_email}</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;"><i class="fas fa-phone"></i> ${booking.inquiry_phone || 'N/A'}</p>
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
                ${booking.quoted_price ? '₱' + parseFloat(booking.quoted_price).toLocaleString('en-PH', {minimumFractionDigits: 2}) : 'Pending'}
            </p>
        </div>
        
        ${booking.special_requests ? `
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 14px; color: #666; margin-bottom: 5px;">Special Requests</h4>
            <p style="margin: 0; font-size: 14px; color: #666; white-space: pre-wrap;">${booking.special_requests}</p>
        </div>
        ` : ''}
    `;
    
    document.getElementById('detailsModal').style.display = 'flex';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

// Close modals when clicking outside
['confirmModal', 'rejectModal', 'priceModal', 'detailsModal'].forEach(modalId => {
    document.getElementById(modalId).addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

// Highlight scanned event row
function highlightScannedEvent() {
    const scannedEventId = localStorage.getItem('scannedEventId');
    if (scannedEventId) {
        const row = document.querySelector(`tr[data-event-id="${scannedEventId}"]`);
        if (row) {
            row.style.backgroundColor = '#d4edda';
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        localStorage.removeItem('scannedEventId');
    }
}

document.addEventListener('DOMContentLoaded', highlightScannedEvent);
</script>

<?php require_once '../includes/staff-footer.php'; ?>
