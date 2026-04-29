<?php
$pageTitle = 'Maintenance Request';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    $issueType = $_POST['issue_type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    
    // Validate
    if (!$bookingId || empty($issueType) || empty($description)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
    } else {
        // Verify booking belongs to current user and is active
        $verifyStmt = $db->prepare("
            SELECT b.*, r.room_id, r.room_number 
            FROM bookings b 
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE b.booking_id = ? AND b.user_id = ? 
            AND b.status IN ('confirmed', 'checked_in')
        ");
        $verifyStmt->execute([$bookingId, $userId]);
        $booking = $verifyStmt->fetch();
        
        if (!$booking) {
            $_SESSION['error'] = 'Invalid booking or you do not have permission to submit a request for this booking.';
        } else {
            try {
                // Insert maintenance request
                $stmt = $db->prepare("
                    INSERT INTO maintenance_requests 
                    (room_id, reported_by, issue_type, description, priority, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $booking['room_id'],
                    $userId,
                    $issueType,
                    $description,
                    $priority
                ]);
                
                $requestId = $db->lastInsertId();
                
                // Send notification to admin and staff
                require_once '../includes/notifications.php';
                notifyMaintenanceRequest($requestId, $issueType, $priority, $booking['room_number'] ?? '');
                notifyAdminMaintenanceUpdate($requestId, 'submitted', $issueType, $priority, $booking['room_number'] ?? '');
                
                $_SESSION['success'] = 'Maintenance request submitted successfully. Our team will address it shortly.';
                redirect('maintenance-request.php');
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to submit request. Please try again.';
            }
        }
    }
}

// Get user's active bookings (confirmed or checked_in) for dropdown
$bookingsStmt = $db->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, b.status,
           r.room_number, rc.category_name as room_name
    FROM bookings b
    LEFT JOIN rooms r ON b.room_id = r.room_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.user_id = ? 
    AND b.status IN ('confirmed', 'checked_in')
    AND b.check_out >= CURDATE()
    ORDER BY b.check_in DESC
");
$bookingsStmt->execute([$userId]);
$activeBookings = $bookingsStmt->fetchAll();

// Get user's maintenance requests
$requestsStmt = $db->prepare("
    SELECT mr.*, r.room_number, rc.category_name as room_name
    FROM maintenance_requests mr
    LEFT JOIN rooms r ON mr.room_id = r.room_id
    LEFT JOIN bookings b ON r.room_id = b.room_id
    LEFT JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE mr.reported_by = ?
    GROUP BY mr.request_id
    ORDER BY mr.created_at DESC
    LIMIT 20
");
$requestsStmt->execute([$userId]);
$maintenanceRequests = $requestsStmt->fetchAll();

// Issue types
$issueTypes = [
    'plumbing' => ['icon' => 'fa-faucet', 'label' => 'Plumbing'],
    'electrical' => ['icon' => 'fa-bolt', 'label' => 'Electrical'],
    'hvac' => ['icon' => 'fa-snowflake', 'label' => 'Air Conditioning/Heating'],
    'furniture' => ['icon' => 'fa-couch', 'label' => 'Furniture'],
    'appliance' => ['icon' => 'fa-tv', 'label' => 'Appliance'],
    'other' => ['icon' => 'fa-tools', 'label' => 'Other']
];

// Priority labels
$priorityLabels = [
    'low' => ['label' => 'Low', 'color' => '#6c757d'],
    'medium' => ['label' => 'Medium', 'color' => '#ffc107'],
    'high' => ['label' => 'High', 'color' => '#fd7e14'],
    'urgent' => ['label' => 'Urgent', 'color' => '#dc3545']
];

// Status labels
$statusLabels = [
    'pending' => ['label' => 'Pending', 'bg' => '#fff3cd', 'color' => '#856404', 'icon' => 'fa-clock'],
    'in_progress' => ['label' => 'In Progress', 'bg' => '#cce5ff', 'color' => '#004085', 'icon' => 'fa-spinner fa-spin'],
    'completed' => ['label' => 'Completed', 'bg' => '#d4edda', 'color' => '#155724', 'icon' => 'fa-check-circle'],
    'cancelled' => ['label' => 'Cancelled', 'bg' => '#f8d7da', 'color' => '#721c24', 'icon' => 'fa-times-circle']
];
?>

<!-- Page Header -->
<div style="margin-bottom: 30px;">
    <h1 style="font-size: 28px; margin-bottom: 10px;">
        <i class="fas fa-tools" style="color: var(--primary-color); margin-right: 10px;"></i>
        Maintenance Request
    </h1>
    <p style="color: #666;">Report maintenance issues in your room for prompt assistance.</p>
</div>

<?php if ($successMessage): ?>
<div style="background-color: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #28a745;">
    <i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($successMessage); ?>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div style="background-color: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #dc3545;">
    <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($errorMessage); ?>
</div>
<?php endif; ?>

<?php if (empty($activeBookings)): ?>
<!-- No Active Bookings -->
<div style="background-color: white; padding: 60px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <i class="fas fa-bed" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 20px;"></i>
    <h3 style="font-size: 22px; margin-bottom: 10px;">No Active Bookings</h3>
    <p style="color: #666; margin-bottom: 25px;">You need an active booking to submit a maintenance request.</p>
    <a href="../rooms.php" class="btn btn-primary">Browse Rooms</a>
</div>
<?php else: ?>

<!-- Request Form -->
<div style="background-color: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <h3 style="font-size: 20px; margin-bottom: 25px;">
        <i class="fas fa-plus-circle" style="color: var(--primary-color); margin-right: 10px;"></i>
        Submit New Request
    </h3>
    
    <form method="POST" action="">
        <div style="display: grid; gap: 20px;">
            <!-- Booking Selection -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                    Select Room/Booking <span style="color: #dc3545;">*</span>
                </label>
                <select name="booking_id" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    <option value="">-- Select your room --</option>
                    <?php foreach ($activeBookings as $booking): ?>
                    <option value="<?php echo $booking['booking_id']; ?>">
                        Room <?php echo htmlspecialchars($booking['room_number'] ?: 'TBA'); ?> 
                        (<?php echo htmlspecialchars($booking['room_name']); ?>)
                        - Check-in: <?php echo date('M d, Y', strtotime($booking['check_in'])); ?>
                        <?php if ($booking['status'] === 'checked_in'): ?>[Currently Staying]<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Issue Type -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                    Issue Type <span style="color: #dc3545;">*</span>
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;">
                    <?php foreach ($issueTypes as $key => $type): ?>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 10px; padding: 15px; border: 2px solid #e0e0e0; border-radius: 8px; transition: all 0.2s;" class="issue-type-option" onclick="selectIssueType(this, '<?php echo $key; ?>')">
                        <input type="radio" name="issue_type" value="<?php echo $key; ?>" required style="display: none;">
                        <i class="fas <?php echo $type['icon']; ?>" style="font-size: 20px; color: var(--primary-color);"></i>
                        <span style="font-weight: 500;"><?php echo $type['label']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Priority -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                    Priority Level <span style="color: #dc3545;">*</span>
                </label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($priorityLabels as $key => $priority): ?>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; padding: 10px 20px; border: 2px solid #e0e0e0; border-radius: 20px; transition: all 0.2s;" class="priority-option" onclick="selectPriority(this, '<?php echo $key; ?>')">
                        <input type="radio" name="priority" value="<?php echo $key; ?>" <?php echo $key === 'medium' ? 'checked' : ''; ?> style="display: none;">
                        <span style="width: 12px; height: 12px; border-radius: 50%; background-color: <?php echo $priority['color']; ?>"></span>
                        <span style="font-weight: 500;"><?php echo $priority['label']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Description -->
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #333;">
                    Description <span style="color: #dc3545;">*</span>
                </label>
                <textarea name="description" required rows="4" placeholder="Please describe the issue in detail... (e.g., 'The air conditioner is not cooling properly' or 'The bathroom sink is leaking')"
                    style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
            </div>
            
            <!-- Submit Button -->
            <div style="text-align: right;">
                <button type="submit" name="submit_request" class="btn btn-primary" style="padding: 12px 30px;">
                    <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>
                    Submit Request
                </button>
            </div>
        </div>
    </form>
</div>

<?php endif; ?>

<!-- My Maintenance Requests -->
<div style="background-color: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <h3 style="font-size: 20px; margin-bottom: 25px;">
        <i class="fas fa-history" style="color: var(--primary-color); margin-right: 10px;"></i>
        My Maintenance Requests
    </h3>
    
    <?php if (empty($maintenanceRequests)): ?>
    <div style="text-align: center; padding: 40px; color: #666;">
        <i class="fas fa-clipboard-list" style="font-size: 50px; color: var(--gray-medium); margin-bottom: 15px;"></i>
        <p>No maintenance requests submitted yet.</p>
    </div>
    <?php else: ?>
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <?php foreach ($maintenanceRequests as $request): 
            $status = $statusLabels[$request['status']] ?? $statusLabels['pending'];
            $priority = $priorityLabels[$request['priority']] ?? $priorityLabels['medium'];
            $issueType = $issueTypes[$request['issue_type']] ?? $issueTypes['other'];
        ?>
        <div style="border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; display: flex; gap: 20px; align-items: flex-start;">
            <!-- Icon -->
            <div style="width: 50px; height: 50px; background-color: var(--gray-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas <?php echo $issueType['icon']; ?>" style="font-size: 24px; color: var(--primary-color);"></i>
            </div>
            
            <!-- Content -->
            <div style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h4 style="font-size: 16px; margin-bottom: 5px;">
                            <?php echo $issueType['label']; ?> Issue
                            <?php if ($request['room_number']): ?>
                            <span style="color: #666; font-weight: normal;">- Room <?php echo htmlspecialchars($request['room_number']); ?></span>
                            <?php endif; ?>
                        </h4>
                        <p style="font-size: 13px; color: #666;">
                            Request #<?php echo str_pad($request['request_id'], 6, '0', STR_PAD_LEFT); ?> • 
                            Submitted <?php echo date('M d, Y \a\t h:i A', strtotime($request['created_at'])); ?>
                        </p>
                    </div>
                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $status['bg']; ?>; color: <?php echo $status['color']; ?>">
                        <i class="fas <?php echo $status['icon']; ?>"></i>
                        <?php echo $status['label']; ?>
                    </span>
                </div>
                
                <p style="color: #333; margin-bottom: 10px; line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                </p>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <span style="display: flex; align-items: center; gap: 5px; font-size: 13px; color: #666;">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?php echo $priority['color']; ?>"></span>
                        <?php echo $priority['label']; ?> Priority
                    </span>
                    <?php if ($request['resolved_at']): ?>
                    <span style="font-size: 13px; color: #28a745;">
                        <i class="fas fa-check" style="margin-right: 5px;"></i>
                        Resolved <?php echo date('M d, Y', strtotime($request['resolved_at'])); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.issue-type-option:hover,
.priority-option:hover {
    border-color: var(--primary-color) !important;
}

.issue-type-option.selected,
.priority-option.selected {
    border-color: var(--primary-color) !important;
    background-color: #f8f9ff;
}
</style>

<script>
function selectIssueType(element, value) {
    // Remove selected class from all options
    document.querySelectorAll('.issue-type-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    // Add selected class to clicked option
    element.classList.add('selected');
    // Check the radio button
    element.querySelector('input[type="radio"]').checked = true;
}

function selectPriority(element, value) {
    // Remove selected class from all options
    document.querySelectorAll('.priority-option').forEach(opt => {
        opt.classList.remove('selected');
    });
    // Add selected class to clicked option
    element.classList.add('selected');
    // Check the radio button
    element.querySelector('input[type="radio"]').checked = true;
}

// Initialize default selections
document.addEventListener('DOMContentLoaded', function() {
    // Select medium priority by default
    const mediumPriority = document.querySelector('input[name="priority"][value="medium"]');
    if (mediumPriority) {
        mediumPriority.closest('.priority-option').classList.add('selected');
    }
});
</script>

<?php require_once '../includes/user-footer.php'; ?>
