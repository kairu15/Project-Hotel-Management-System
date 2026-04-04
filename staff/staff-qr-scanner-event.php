<?php
$pageTitle = 'QR Code Scanner - Inquiries';
require_once '../includes/config.php';
require_once '../includes/qr_code_helper.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/staff-header.php';

$db = getDB();
$userRole = getUserRole();

$event = null;
$error = null;
$success = null;

// Handle manual reference lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_reference'])) {
    $reference = trim($_POST['reference_number'] ?? '');
    
    if (empty($reference)) {
        $error = 'Please enter a reference number';
    } else {
        // Search by event_ref column directly
        $stmt = $db->prepare("
            SELECT eb.*, 
                   es.space_name, es.capacity, es.area_sqm,
                   u.first_name, u.last_name, u.email, u.phone
            FROM event_bookings eb 
            JOIN event_spaces es ON eb.space_id = es.space_id 
            LEFT JOIN users u ON eb.user_id = u.user_id
            WHERE eb.event_ref = ?
        ");
        $stmt->execute([$reference]);
        $event = $stmt->fetch();
        
        if (!$event) {
            $error = 'Event booking not found. Please check the reference number.';
        }
    }
}

// Handle status update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['event_booking_id'])) {
    $eventBookingId = intval($_POST['event_booking_id']);
    $action = $_POST['action'];
    
    $newStatus = '';
    if ($action === 'confirm') {
        $newStatus = 'confirmed';
    } elseif ($action === 'complete') {
        $newStatus = 'completed';
    } elseif ($action === 'cancel') {
        $newStatus = 'cancelled';
    }
    
    if ($newStatus) {
        $stmt = $db->prepare("UPDATE event_bookings SET status = ? WHERE event_booking_id = ?");
        if ($stmt->execute([$newStatus, $eventBookingId])) {
            $success = 'Event status updated to ' . ucfirst($newStatus);
            // Refresh event data
            $stmt = $db->prepare("
                SELECT eb.*, 
                       es.space_name, es.capacity, es.area_sqm,
                       u.first_name, u.last_name, u.email, u.phone
                FROM event_bookings eb 
                JOIN event_spaces es ON eb.space_id = es.space_id 
                LEFT JOIN users u ON eb.user_id = u.user_id
                WHERE eb.event_booking_id = ?
            ");
            $stmt->execute([$eventBookingId]);
            $event = $stmt->fetch();
        } else {
            $error = 'Failed to update event status';
        }
    }
}

// Status colors
$statusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'confirmed' => ['#d4edda', '#155724'],
    'completed' => ['#cce5ff', '#004085'],
    'cancelled' => ['#f8d7da', '#721c24']
];
?>

<style>
    .scanner-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .scanner-box {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .scanner-title {
        color: var(--primary-color);
        margin-bottom: 10px;
        font-size: 24px;
    }
    
    .scanner-subtitle {
        color: #666;
        margin-bottom: 25px;
    }
    
    #reader {
        width: 100%;
        max-width: 400px;
        margin: 0 auto 20px;
    }
    
    .manual-input-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px dashed var(--gray-light);
    }
    
    .manual-input-section h3 {
        color: var(--dark-color);
        margin-bottom: 15px;
        font-size: 18px;
    }
    
    .input-group {
        display: flex;
        gap: 10px;
        max-width: 400px;
        margin: 0 auto;
    }
    
    .input-group input {
        flex: 1;
        padding: 12px 15px;
        border: 2px solid var(--gray-light);
        border-radius: 8px;
        font-size: 16px;
    }
    
    .btn-scan {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
    }
    
    .event-result {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .event-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--gray-light);
    }
    
    .status-badge {
        padding: 8px 20px;
        border-radius: 25px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .event-details-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .detail-card {
        background: var(--gray-light);
        padding: 15px;
        border-radius: 10px;
    }
    
    .detail-card label {
        display: block;
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    
    .detail-card .value {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 25px;
    }
    
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 15px 30px;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: transform 0.2s;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
    }
    
    .btn-confirm {
        background: #28a745;
        color: white;
    }
    
    .btn-complete {
        background: #007bff;
        color: white;
    }
    
    .btn-cancel {
        background: #dc3545;
        color: white;
    }
    
    .btn-scan-new {
        background: var(--gray-medium);
        color: var(--dark-color);
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .alert-error {
        background: #f8d7da;
        color: #721c24;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
    }
</style>

<div class="scanner-container">
    <h1 style="text-align: center; margin-bottom: 30px;">
        <i class="fas fa-qrcode" style="color: var(--primary-color);"></i>
        Inquiry QR Code Scanner
    </h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$event): ?>
        <!-- Scanner Section -->
        <div class="scanner-box">
            <h2 class="scanner-title">Scan Inquiry QR Code</h2>
            <p class="scanner-subtitle">Position the QR code within the frame to scan<br><small>Reference format: INQ-000009</small></p>
            
            <div id="reader"></div>
            
            <button class="scan-again-btn" onclick="startScanning()" style="display: none; margin-top: 20px; padding: 12px 25px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer;" id="startScanBtn">
                <i class="fas fa-camera"></i> Start Camera
            </button>
            
            <!-- Manual Input Fallback -->
            <div class="manual-input-section">
                <h3><i class="fas fa-keyboard"></i> Or Enter Reference Number</h3>
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" 
                               name="reference_number" 
                               placeholder="INQ-000009"
                               required
                               maxlength="20">
                        <button type="submit" name="lookup_reference" class="btn-scan">
                            <i class="fas fa-search"></i> Lookup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Event Details Section -->
        <div class="event-result">
            <div class="event-header">
                <div>
                    <h2>Event #<?php echo str_pad($event['event_booking_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                    <p style="color: #666; margin: 5px 0 0 0;">
                        <i class="fas fa-calendar"></i> 
                        <?php echo date('F d, Y', strtotime($event['event_date'])); ?>
                        <?php if ($event['start_time']): ?>
                            at <?php echo date('h:i A', strtotime($event['start_time'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php 
                $status = $event['status'];
                $statusColor = $statusColors[$status] ?? $statusColors['pending'];
                ?>
                <span class="status-badge" style="background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>">
                    <i class="fas fa-<?php echo $status === 'confirmed' ? 'check-circle' : ($status === 'completed' ? 'flag-checkered' : ($status === 'cancelled' ? 'times-circle' : 'clock')); ?>"></i>
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
            
            <!-- Event Info -->
            <div class="event-details-grid">
                <div class="detail-card">
                    <label>Event Type</label>
                    <div class="value"><?php echo htmlspecialchars($event['event_type'] ?: 'Not Specified'); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Venue</label>
                    <div class="value"><?php echo htmlspecialchars($event['space_name']); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Guest Count</label>
                    <div class="value"><?php echo $event['guests_count'] ?: 'Not Specified'; ?> guests</div>
                </div>
                
                <div class="detail-card">
                    <label>Catering Required</label>
                    <div class="value"><?php echo $event['catering_required'] ? 'Yes' : 'No'; ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Quoted Price</label>
                    <div class="value">₱<?php echo number_format($event['quoted_price'] ?? 0, 2); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Venue Capacity</label>
                    <div class="value"><?php echo $event['capacity']; ?> people</div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="detail-card" style="margin-bottom: 20px;">
                <label>Contact Information</label>
                <div style="margin-top: 10px;">
                    <strong><?php echo htmlspecialchars(($event['first_name'] ?? $event['inquiry_name']) . ' ' . ($event['last_name'] ?? '')); ?></strong><br>
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($event['email'] ?? $event['inquiry_email']); ?><br>
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($event['phone'] ?? $event['inquiry_phone'] ?: 'N/A'); ?>
                </div>
            </div>
            
            <?php if ($event['special_requests']): ?>
                <div class="detail-card" style="margin-bottom: 25px;">
                    <label>Special Requests</label>
                    <div style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($event['special_requests'])); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($event['status'] === 'confirmed'): ?>
                    <form method="POST" action="" style="display: inline;" id="completeEventForm<?php echo $event['event_booking_id']; ?>">
                        <input type="hidden" name="event_booking_id" value="<?php echo $event['event_booking_id']; ?>">
                        <input type="hidden" name="action" value="complete">
                        <button type="button" class="btn-action btn-complete" onclick="openDeleteModal('completeEventForm<?php echo $event['event_booking_id']; ?>', 'Complete Event', 'Are you sure you want to mark this event as completed?', null, 'action')">
                            <i class="fas fa-flag-checkered"></i> Complete Event
                        </button>
                    </form>
                <?php endif; ?>
                
                <button type="button" class="btn-action btn-scan-new" onclick="viewFullDetails(<?php echo $event['event_booking_id']; ?>, 'event')">
                    <i class="fas fa-external-link-alt"></i> View Full Details
                </button>
                
                <a href="staff-qr-scanner-event.php" class="btn-action btn-scan-new" style="text-decoration: none;">
                    <i class="fas fa-qrcode"></i> Scan Another
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Include QR Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let html5QrCode;
    
    function startScanning() {
        const reader = document.getElementById('reader');
        const startBtn = document.getElementById('startScanBtn');
        
        if (startBtn) {
            startBtn.style.display = 'none';
        }
        
        reader.innerHTML = '';
        
        html5QrCode = new Html5Qrcode("reader");
        
        const config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanFailure
        ).catch(err => {
            console.error('Camera error:', err);
            reader.innerHTML = '<p style="color: #dc3545; padding: 20px;"><i class="fas fa-exclamation-triangle"></i> Camera access denied or not available. Please use the manual entry below.</p>';
            if (startBtn) {
                startBtn.style.display = 'inline-block';
                startBtn.innerHTML = '<i class="fas fa-redo"></i> Retry Camera';
            }
        });
    }
    
    function onScanSuccess(decodedText, decodedResult) {
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                processScannedCode(decodedText);
            });
        }
    }
    
    function onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    }
    
    function processScannedCode(code) {
        const reader = document.getElementById('reader');
        reader.innerHTML = '<p style="color: var(--primary-color); padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Processing QR Code...</p>';
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'reference_number';
        input.value = code.trim();
        
        const submit = document.createElement('input');
        submit.type = 'hidden';
        submit.name = 'lookup_reference';
        submit.value = '1';
        
        form.appendChild(input);
        form.appendChild(submit);
        document.body.appendChild(form);
        form.submit();
    }
    
    // JavaScript function to navigate to details page with highlighting
    function viewFullDetails(id) {
        localStorage.setItem('scannedEventId', id);
        window.location.href = 'staff-event-bookings.php?id=' + id;
    }
    
    <?php if (!$event): ?>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(startScanning, 500);
    });
    <?php endif; ?>
</script>

<?php require_once '../includes/staff-footer.php'; ?>
