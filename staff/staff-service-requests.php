<?php
$pageTitle = 'Manage Guest Service Requests';
require_once '../includes/config.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$userId = getUserId();

// Handle status update
if (isset($_POST['update_status']) && is_numeric($_POST['request_id'])) {
    $requestId = intval($_POST['request_id']);
    $newStatus = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    $allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'declined'];
    if (in_array($newStatus, $allowedStatuses)) {
        $updateFields = ['status = ?'];
        $params = [$newStatus];
        
        if ($newStatus === 'confirmed') {
            $updateFields[] = 'confirmed_at = NOW()';
        } elseif ($newStatus === 'completed') {
            $updateFields[] = 'completed_at = NOW()';
        }
        
        if ($notes) {
            $updateFields[] = 'notes = ?';
            $params[] = $notes;
        }
        
        $updateFields[] = 'processed_by = ?';
        $params[] = $userId;
        
        $params[] = $requestId;
        $sql = "UPDATE guest_service_requests SET " . implode(', ', $updateFields) . " WHERE request_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Send notifications
        require_once '../includes/notifications.php';
        
        // Get request details for notifications
        $requestStmt = $db->prepare("
            SELECT gsr.*, s.service_name, u.first_name, u.last_name, u.user_id
            FROM guest_service_requests gsr
            JOIN additional_services s ON gsr.service_id = s.service_id
            JOIN users u ON gsr.user_id = u.user_id
            WHERE gsr.request_id = ?
        ");
        $requestStmt->execute([$requestId]);
        $requestData = $requestStmt->fetch();
        
        if ($requestData) {
            // Notify user about status update
            notifyUserServiceRequestStatus(
                $requestData['user_id'], 
                $requestId, 
                $newStatus, 
                $requestData['service_name'],
                $requestData['room_number']
            );
            
            // Log activity
            logActivity(
                $_SESSION['user_id'],
                'service_request_updated',
                "Updated service request #{$requestId} ({$requestData['service_name']}) status to: {$newStatus}"
            );
        }
        
        showAlert('Request status updated successfully', 'success');
    } else {
        showAlert('Invalid status', 'danger');
    }
    redirect('staff-service-requests.php');
}

// Handle add to bill
if (isset($_POST['add_to_bill']) && is_numeric($_POST['request_id'])) {
    $requestId = intval($_POST['request_id']);
    
    // Get request details
    $requestStmt = $db->prepare("
        SELECT gsr.*, s.service_name 
        FROM guest_service_requests gsr
        JOIN additional_services s ON gsr.service_id = s.service_id
        WHERE gsr.request_id = ?
    ");
    $requestStmt->execute([$requestId]);
    $request = $requestStmt->fetch();
    
    if ($request && $request['booking_id'] && $request['payment_status'] === 'pending') {
        try {
            // Add charge to booking_charges
            $chargeStmt = $db->prepare("
                INSERT INTO booking_charges (booking_id, description, amount, charge_type, status, created_by) 
                VALUES (?, ?, ?, 'room_service', 'active', ?)
            ");
            $chargeDesc = $request['service_name'] . ' (x' . $request['quantity'] . ')';
            $chargeStmt->execute([
                $request['booking_id'], 
                $chargeDesc, 
                $request['total_price'],
                $userId
            ]);
            
            $chargeId = $db->lastInsertId();
            
            // Update request with charge reference and payment status
            $updateStmt = $db->prepare("
                UPDATE guest_service_requests 
                SET payment_status = 'added_to_bill', charge_id = ? 
                WHERE request_id = ?
            ");
            $updateStmt->execute([$chargeId, $requestId]);
            
            showAlert('Service charge added to guest bill successfully', 'success');
        } catch (Exception $e) {
            showAlert('Error adding charge to bill: ' . $e->getMessage(), 'danger');
        }
    } else {
        showAlert('Cannot add to bill. Request must have an associated booking and pending payment status.', 'danger');
    }
    redirect('staff-service-requests.php');
}

// Handle mark as paid
if (isset($_POST['mark_paid']) && is_numeric($_POST['request_id'])) {
    $requestId = intval($_POST['request_id']);
    
    $stmt = $db->prepare("
        UPDATE guest_service_requests 
        SET payment_status = 'paid' 
        WHERE request_id = ?
    ");
    $stmt->execute([$requestId]);
    
    showAlert('Request marked as paid', 'success');
    redirect('staff-service-requests.php');
}

// Now include header after all POST handling
require_once '../includes/staff-header.php';

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$paymentFilter = $_GET['payment'] ?? '';
$userSearch = $_GET['user'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$sql = "
    SELECT gsr.*, 
           s.service_name, s.category, s.subcategory, s.duration_minutes,
           u.first_name, u.last_name, u.email, u.phone,
           b.booking_ref, b.check_in, b.check_out,
           r.room_number as booking_room,
           processor.first_name as processor_name
    FROM guest_service_requests gsr
    JOIN additional_services s ON gsr.service_id = s.service_id
    JOIN users u ON gsr.user_id = u.user_id
    LEFT JOIN bookings b ON gsr.booking_id = b.booking_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    LEFT JOIN users processor ON gsr.processed_by = processor.user_id
    WHERE 1=1
";

$params = [];

if ($statusFilter) {
    $sql .= " AND gsr.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter) {
    $sql .= " AND s.category = ?";
    $params[] = $categoryFilter;
}

if ($paymentFilter) {
    $sql .= " AND gsr.payment_status = ?";
    $params[] = $paymentFilter;
}

if ($userSearch) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR gsr.room_number LIKE ?)";
    $searchTerm = "%$userSearch%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($dateFrom) {
    $sql .= " AND DATE(gsr.requested_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(gsr.requested_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY FIELD(gsr.status, 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'declined'), gsr.requested_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Status color mapping
$statusColors = [
    'pending' => ['#fff3cd', '#856404', 'clock'],
    'confirmed' => ['#cce5ff', '#004085', 'check'],
    'in_progress' => ['#d1ecf1', '#0c5460', 'spinner'],
    'completed' => ['#d4edda', '#155724', 'check-circle'],
    'cancelled' => ['#f8d7da', '#721c24', 'times-circle'],
    'declined' => ['#f8d7da', '#721c24', 'times-circle']
];

$paymentStatusColors = [
    'pending' => ['#fff3cd', '#856404', 'hourglass-half'],
    'added_to_bill' => ['#cce5ff', '#004085', 'file-invoice'],
    'paid' => ['#d4edda', '#155724', 'check-circle'],
    'waived' => ['#f8d7da', '#721c24', 'times-circle']
];

// Calculate stats
$pendingCount = count(array_filter($requests, function($r) { return $r['status'] === 'pending'; }));
$confirmedCount = count(array_filter($requests, function($r) { return $r['status'] === 'confirmed'; }));
$inProgressCount = count(array_filter($requests, function($r) { return $r['status'] === 'in_progress'; }));
$completedToday = count(array_filter($requests, function($r) { 
    return $r['status'] === 'completed' && date('Y-m-d', strtotime($r['requested_at'])) === date('Y-m-d'); 
}));
$totalRevenue = array_sum(array_map(function($r) { return in_array($r['status'], ['completed', 'in_progress', 'confirmed']) ? $r['total_price'] : 0; }, $requests));

// Categories for filter
$categories = ['laundry', 'spa', 'wellness', 'other'];
?>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $pendingCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Pending</p>
            </div>
            <i class="fas fa-clock" style="font-size: 28px; color: var(--warning-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--info-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $confirmedCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Confirmed</p>
            </div>
            <i class="fas fa-check" style="font-size: 28px; color: var(--info-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $inProgressCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">In Progress</p>
            </div>
            <i class="fas fa-spinner" style="font-size: 28px; color: var(--primary-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $completedToday; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Completed Today</p>
            </div>
            <i class="fas fa-check-circle" style="font-size: 28px; color: var(--success-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 24px; margin: 0;"><?php echo formatPrice($totalRevenue); ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Total Revenue</p>
            </div>
            <i class="fas fa-money-bill-wave" style="font-size: 28px; color: var(--primary-color);"></i>
        </div>
    </div>
</div>

<!-- Filters -->
<div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <form method="GET" action="" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; align-items: end;">
        <div>
            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
            <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
            <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat; ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Payment</label>
            <select name="payment" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All</option>
                <option value="pending" <?php echo $paymentFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="added_to_bill" <?php echo $paymentFilter === 'added_to_bill' ? 'selected' : ''; ?>>Added to Bill</option>
                <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search Guest/Room</label>
            <input type="text" name="user" value="<?php echo htmlspecialchars($userSearch); ?>" placeholder="Name, email, room..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
            <a href="staff-service-requests.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
        </div>
    </form>
</div>

<!-- Requests Table -->
<div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
    <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size: 20px; margin: 0;">Guest Service Requests (<?php echo count($requests); ?>)</h3>
    </div>

    <?php if (count($requests) > 0): ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: var(--gray-light);">
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Ref / Date</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Guest</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Service</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Amount</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Payment</th>
                    <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): 
                $statusInfo = $statusColors[$request['status']] ?? ['#e2e3e5', '#383d41', 'question'];
                $paymentInfo = $paymentStatusColors[$request['payment_status']] ?? ['#e2e3e5', '#383d41', 'question'];
                ?>
                <tr style="border-bottom: 1px solid var(--gray-light);">
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($request['request_ref']); ?></div>
                        <div style="font-size: 12px; color: #666;"><?php echo formatDate($request['requested_at'], 'M d, Y H:i'); ?></div>
                        <?php if ($request['preferred_date']): ?>
                        <div style="font-size: 11px; color: #999; margin-top: 3px;">
                            <i class="fas fa-calendar"></i> <?php echo formatDate($request['preferred_date']); ?>
                            <?php if ($request['preferred_time']): ?>
                            <?php echo date('h:i A', strtotime($request['preferred_time'])); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($request['email']); ?></div>
                        <div style="font-size: 11px; color: #999;"><?php echo htmlspecialchars($request['phone'] ?? 'No phone'); ?></div>
                    </td>
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 500;"><?php echo htmlspecialchars($request['service_name']); ?></div>
                        <div style="font-size: 12px; color: #666;">
                            <?php echo htmlspecialchars($request['subcategory'] ?? ucfirst($request['category'])); ?>
                            <?php if ($request['duration_minutes']): ?>
                            <span style="margin-left: 10px;"><i class="fas fa-clock"></i> <?php echo $request['duration_minutes']; ?> min</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 11px; color: #999;">Qty: <?php echo $request['quantity']; ?></div>
                    </td>
                    <td style="padding: 15px 20px;">
                        <div style="font-weight: 600;">Room <?php echo htmlspecialchars($request['room_number']); ?></div>
                        <?php if ($request['booking_ref']): ?>
                        <div style="font-size: 11px; color: #666;">
                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($request['booking_ref']); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                        <?php echo formatPrice($request['total_price']); ?>
                        <div style="font-size: 11px; color: #666; font-weight: normal;">
                            <?php echo $request['quantity']; ?> x <?php echo formatPrice($request['unit_price']); ?>
                        </div>
                    </td>
                    <td style="padding: 15px 20px;">
                        <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $statusInfo[0]; ?>; color: <?php echo $statusInfo[1]; ?>; text-transform: capitalize;">
                            <i class="fas fa-<?php echo $statusInfo[2]; ?>"></i> <?php echo str_replace('_', ' ', $request['status']); ?>
                        </span>
                        <?php if ($request['processor_name']): ?>
                        <div style="font-size: 11px; color: #999; margin-top: 5px;">
                            By: <?php echo htmlspecialchars($request['processor_name']); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px 20px;">
                        <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $paymentInfo[0]; ?>; color: <?php echo $paymentInfo[1]; ?>; text-transform: capitalize;">
                            <i class="fas fa-<?php echo $paymentInfo[2]; ?>"></i> <?php echo str_replace('_', ' ', $request['payment_status']); ?>
                        </span>
                    </td>
                    <td style="padding: 15px 20px;">
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if ($request['status'] === 'pending'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="confirmed">
                                <button type="submit" name="update_status" class="btn btn-sm btn-success" style="padding: 5px 12px; font-size: 11px;">Confirm</button>
                            </form>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="declined">
                                <button type="submit" name="update_status" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 11px;">Decline</button>
                            </form>
                            <?php elseif ($request['status'] === 'confirmed'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="in_progress">
                                <button type="submit" name="update_status" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 11px;">Start</button>
                            </form>
                            <?php elseif ($request['status'] === 'in_progress'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" name="update_status" class="btn btn-sm btn-success" style="padding: 5px 12px; font-size: 11px;">Complete</button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($request['payment_status'] === 'pending' && $request['booking_id'] && in_array($request['status'], ['confirmed', 'in_progress', 'completed'])): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <button type="submit" name="add_to_bill" class="btn btn-sm btn-warning" style="padding: 5px 12px; font-size: 11px;">Add to Bill</button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($request['payment_status'] === 'pending' && !in_array($request['status'], ['cancelled', 'declined'])): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <button type="submit" name="mark_paid" class="btn btn-sm btn-info" style="padding: 5px 12px; font-size: 11px;">Mark Paid</button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if (!in_array($request['status'], ['completed', 'cancelled', 'declined'])): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" name="update_status" class="btn btn-sm btn-secondary" style="padding: 5px 12px; font-size: 11px;" onclick="return confirm('Cancel this request?')">Cancel</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($request['special_instructions']): ?>
                        <div style="margin-top: 8px;">
                            <button type="button" onclick="alert('<?php echo htmlspecialchars(addslashes($request['special_instructions'])); ?>')" class="btn btn-sm btn-outline" style="padding: 3px 8px; font-size: 10px;">
                                <i class="fas fa-comment"></i> View Notes
                            </button>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="padding: 60px; text-align: center;">
        <i class="fas fa-concierge-bell" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
        <h3 style="color: #666;">No service requests found</h3>
        <p style="color: #999;">Try adjusting your filters</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/staff-footer.php'; ?>
