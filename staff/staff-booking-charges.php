<?php
$pageTitle = 'Manage Booking Charges - Staff';
require_once '../includes/config.php';

// Check permission for booking_charges page
checkStaffPermission('booking_charges');

$db = getDB();

// Handle add charge
if (isset($_POST['add_charge'])) {
    $bookingId = $_POST['booking_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $chargeType = $_POST['charge_type'] ?? 'other';
    $userId = getUserId();

    if ($bookingId && $description && $amount > 0) {
        // Get booking reference for message
        $bookStmt = $db->prepare("SELECT booking_ref FROM bookings WHERE booking_id = ?");
        $bookStmt->execute([$bookingId]);
        $bookingRef = $bookStmt->fetchColumn() ?? 'Booking';
        
        $stmt = $db->prepare("INSERT INTO booking_charges (booking_id, description, amount, charge_type, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$bookingId, $description, $amount, $chargeType, $userId]);
        $_SESSION['success'] = 'Charge "' . $description . '" (' . formatPrice($amount) . ') added to ' . $bookingRef;
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('staff-booking-charges.php');
}

// Handle waive charge
if (isset($_POST['waive_charge'])) {
    $chargeId = $_POST['charge_id'] ?? 0;
    if ($chargeId) {
        // Get charge details for message
        $chargeStmt = $db->prepare("SELECT description, amount FROM booking_charges WHERE charge_id = ?");
        $chargeStmt->execute([$chargeId]);
        $chargeData = $chargeStmt->fetch();
        $chargeDesc = $chargeData['description'] ?? 'Charge';
        $chargeAmount = $chargeData['amount'] ?? 0;
        
        $stmt = $db->prepare("UPDATE booking_charges SET status = 'waived' WHERE charge_id = ?");
        $stmt->execute([$chargeId]);
        $_SESSION['success'] = 'Charge "' . $chargeDesc . '" (' . formatPrice($chargeAmount) . ') waived';
    }
    redirect('staff-booking-charges.php');
}

// Handle mark as paid
if (isset($_POST['mark_paid'])) {
    $chargeId = $_POST['charge_id'] ?? 0;
    if ($chargeId) {
        // Get charge details for message
        $chargeStmt = $db->prepare("SELECT description, amount FROM booking_charges WHERE charge_id = ?");
        $chargeStmt->execute([$chargeId]);
        $chargeData = $chargeStmt->fetch();
        $chargeDesc = $chargeData['description'] ?? 'Charge';
        $chargeAmount = $chargeData['amount'] ?? 0;
        
        $stmt = $db->prepare("UPDATE booking_charges SET status = 'paid' WHERE charge_id = ?");
        $stmt->execute([$chargeId]);
        $_SESSION['success'] = 'Charge "' . $chargeDesc . '" (' . formatPrice($chargeAmount) . ') marked as paid';
    }
    redirect('staff-booking-charges.php');
}

// Get filter parameters
$bookingFilter = $_GET['booking'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';

// Build query
$sql = "
    SELECT bc.*, b.booking_id, b.check_in, b.check_out, u.first_name, u.last_name, u.email, creator.first_name as creator_name,
           gsr.request_id, gsr.request_ref, gsr.service_id, s.service_name
    FROM booking_charges bc
    JOIN bookings b ON bc.booking_id = b.booking_id
    JOIN users u ON b.user_id = u.user_id
    LEFT JOIN users creator ON bc.created_by = creator.user_id
    LEFT JOIN guest_service_requests gsr ON bc.charge_id = gsr.charge_id
    LEFT JOIN additional_services s ON gsr.service_id = s.service_id
    WHERE 1=1
";
$params = [];

if ($bookingFilter) {
    $sql .= " AND bc.booking_id = ?";
    $params[] = $bookingFilter;
}

if ($statusFilter) {
    $sql .= " AND bc.status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter) {
    $sql .= " AND bc.charge_type = ?";
    $params[] = $typeFilter;
}

$sql .= " ORDER BY bc.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$charges = $stmt->fetchAll();

// Get active bookings for dropdown
$bookings = $db->query("
    SELECT b.booking_id, b.check_in, b.check_out, u.first_name, u.last_name, u.email
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.status IN ('confirmed', 'checked_in')
    ORDER BY b.check_in DESC
")->fetchAll();

// Charge types
$chargeTypes = ['minibar', 'room_service', 'laundry', 'damage', 'late_checkout', 'other'];

// Status counts
$statusCounts = $db->query("SELECT status, COUNT(*) as count, SUM(amount) as total FROM booking_charges GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);

// Service charges count (charges from guest_service_requests)
$serviceChargesCount = $db->query("
    SELECT COUNT(*) as count, SUM(bc.amount) as total
    FROM booking_charges bc
    JOIN guest_service_requests gsr ON bc.charge_id = gsr.charge_id
    WHERE bc.status = 'active'
")->fetch();

require_once '../includes/staff-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="staff-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="document.getElementById('chargeModal').style.display='flex'" class="btn btn-primary">Add New Charge</button>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php
            $totalActive = 0;
            $totalAmount = 0;
            foreach ($statusCounts as $stat) {
                if ($stat['status'] === 'active') {
                    $totalActive = $stat['count'];
                    $totalAmount = $stat['total'];
                }
            }
            $serviceCount = $serviceChargesCount['count'] ?? 0;
            $serviceTotal = $serviceChargesCount['total'] ?? 0;
            ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $totalActive; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Active Charges</p>
                    </div>
                    <i class="fas fa-file-invoice" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo formatPrice($totalAmount); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Outstanding</p>
                    </div>
                    <i class="fas fa-money-bill" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #9b59b6;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $serviceCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Service Charges</p>
                    </div>
                    <i class="fas fa-concierge-bell" style="font-size: 32px; color: #9b59b6;"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo count($charges); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Charges</p>
                    </div>
                    <i class="fas fa-receipt" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Booking</label>
                    <select name="booking" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Bookings</option>
                        <?php foreach ($bookings as $b): ?>
                        <option value="<?php echo $b['booking_id']; ?>" <?php echo $bookingFilter == $b['booking_id'] ? 'selected' : ''; ?>>
                            #<?php echo $b['booking_id']; ?> - <?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="waived" <?php echo $statusFilter === 'waived' ? 'selected' : ''; ?>>Waived</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Type</label>
                    <select name="type" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Types</option>
                        <?php foreach ($chargeTypes as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $typeFilter === $type ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $type)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="staff-booking-charges.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Charges Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Booking Charges (<?php echo count($charges); ?>)</h3>
            </div>

            <?php if (count($charges) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Booking</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Charge Type</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Description</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Amount</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Created By</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($charges as $charge):
                        $statusColors = [
                            'active' => ['#fff3cd', '#856404'],
                            'paid' => ['#d4edda', '#155724'],
                            'waived' => ['#f8d7da', '#721c24']
                        ];
                        $color = $statusColors[$charge['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;">#<?php echo $charge['booking_id']; ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($charge['first_name'] . ' ' . $charge['last_name']); ?></div>
                                <div style="font-size: 11px; color: #999;"><?php echo formatDate($charge['check_in'], 'M d'); ?> - <?php echo formatDate($charge['check_out'], 'M d'); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $charge['charge_type']); ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="max-width: 250px;">
                                    <?php echo htmlspecialchars($charge['description']); ?>
                                    <?php if ($charge['request_id']): ?>
                                    <div style="margin-top: 5px;">
                                        <a href="staff-service-requests.php" style="font-size: 11px; color: var(--primary-color); text-decoration: none;">
                                            <i class="fas fa-concierge-bell"></i> Service Request #<?php echo htmlspecialchars($charge['request_ref']); ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($charge['amount']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $charge['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px; font-size: 12px;">
                                <?php echo htmlspecialchars($charge['creator_name']); ?>
                                <div style="color: #999;"><?php echo formatDate($charge['created_at'], 'M d, Y'); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($charge['status'] === 'active'): ?>
                                <div style="display: flex; gap: 5px;">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="charge_id" value="<?php echo $charge['charge_id']; ?>">
                                        <button type="submit" name="mark_paid" class="btn btn-sm btn-success" style="padding: 5px 12px; font-size: 11px;">Paid</button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;" id="waiveChargeForm<?php echo $charge['charge_id']; ?>">
                                        <input type="hidden" name="charge_id" value="<?php echo $charge['charge_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('waiveChargeForm<?php echo $charge['charge_id']; ?>', 'Waive Charge', 'Are you sure you want to waive this charge for <?php echo htmlspecialchars($charge['description']); ?> (₱<?php echo number_format($charge['amount'], 2); ?>)?', null, 'waive_charge')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 11px;">Waive</button>
                                    </form>
                                </div>
                                <?php else: ?>
                                <span style="color: #999; font-size: 12px;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-file-invoice" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No charges found</h3>
                <p style="color: #999;">Add your first booking charge</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Add Charge Modal -->
<div id="chargeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">Add New Charge</h3>
            <button onclick="document.getElementById('chargeModal').style.display='none'" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Booking *</label>
                <select name="booking_id" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Booking</option>
                    <?php foreach ($bookings as $b): ?>
                    <option value="<?php echo $b['booking_id']; ?>">#<?php echo $b['booking_id']; ?> - <?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?> (<?php echo formatDate($b['check_in'], 'M d'); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Charge Type *</label>
                <select name="charge_type" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Type</option>
                    <?php foreach ($chargeTypes as $type): ?>
                    <option value="<?php echo $type; ?>"><?php echo ucwords(str_replace('_', ' ', $type)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description *</label>
                <input type="text" name="description" required placeholder="e.g., Minibar consumption" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Amount (PHP) *</label>
                <input type="number" name="amount" required step="0.01" min="0.01" placeholder="0.00" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('chargeModal').style.display='none'" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="add_charge" class="btn btn-primary">Add Charge</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/staff-footer.php'; ?>
