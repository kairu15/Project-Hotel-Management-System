<?php
$pageTitle = 'QR Code Scanner';
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

$booking = null;
$error = null;
$success = null;

// Handle manual reference lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_reference'])) {
    $reference = trim($_POST['reference_number'] ?? '');
    
    if (empty($reference)) {
        $error = 'Please enter a reference number';
    } else {
        // Search by booking_ref column directly
        $stmt = $db->prepare("
            SELECT b.*, 
                   rc.category_name, rc.description as category_description,
                   r.room_number, r.floor,
                   u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.country
            FROM bookings b 
            JOIN room_categories rc ON b.category_id = rc.category_id 
            LEFT JOIN rooms r ON b.room_id = r.room_id
            JOIN users u ON b.user_id = u.user_id
            WHERE b.booking_ref = ?
        ");
        $stmt->execute([$reference]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            $error = 'Booking not found. Please check the reference number.';
        }
    }
}

// Handle check-in/check-out actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $bookingId = intval($_POST['booking_id']);
    $action = $_POST['action'];
    
    if (in_array($action, ['check_in', 'check_out'])) {
        try {
            $db->beginTransaction();
            
            // Get current booking info
            $stmt = $db->prepare("SELECT * FROM bookings WHERE booking_id = ?");
            $stmt->execute([$bookingId]);
            $currentBooking = $stmt->fetch();
            
            if (!$currentBooking) {
                throw new Exception('Booking not found');
            }
            
            if ($action === 'check_in') {
                // Update booking status
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET status = 'checked_in', checked_in_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE booking_id = ?
                ");
                $stmt->execute([$bookingId]);
                
                // Update room status if room assigned
                if ($currentBooking['room_id']) {
                    $stmt = $db->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
                    $stmt->execute([$currentBooking['room_id']]);
                }
                
                $success = 'Guest checked in successfully!';
            } elseif ($action === 'check_out') {
                // Update booking status
                $stmt = $db->prepare("
                    UPDATE bookings 
                    SET status = 'checked_out', checked_out_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                    WHERE booking_id = ?
                ");
                $stmt->execute([$bookingId]);
                
                // Update room status if room assigned
                if ($currentBooking['room_id']) {
                    $stmt = $db->prepare("UPDATE rooms SET status = 'cleaning' WHERE room_id = ?");
                    $stmt->execute([$currentBooking['room_id']]);
                }
                
                $success = 'Guest checked out successfully!';
            }
            
            // Log the action
            $logStmt = $db->prepare("
                INSERT INTO booking_logs (booking_id, action, details, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $logStmt->execute([$bookingId, $action, "Staff performed {$action} via QR scanner", $_SESSION['user_id']]);
            
            $db->commit();
            
            // Refresh booking data
            $stmt = $db->prepare("
                SELECT b.*, 
                       rc.category_name, rc.description as category_description,
                       r.room_number, r.floor,
                       u.first_name, u.last_name, u.email, u.phone, u.address, u.city, u.country
                FROM bookings b 
                JOIN room_categories rc ON b.category_id = rc.category_id 
                LEFT JOIN rooms r ON b.room_id = r.room_id
                JOIN users u ON b.user_id = u.user_id
                WHERE b.booking_id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Status colors
$statusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'confirmed' => ['#d4edda', '#155724'],
    'checked_in' => ['#cce5ff', '#004085'],
    'checked_out' => ['#e2e3e5', '#383d41'],
    'cancelled' => ['#f8d7da', '#721c24'],
    'no_show' => ['#f8d7da', '#721c24']
];

$paymentStatusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'partial' => ['#cce5ff', '#004085'],
    'paid' => ['#d4edda', '#155724'],
    'refunded' => ['#f8d7da', '#721c24']
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
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        text-align: center;
        margin-bottom: 30px;
    }
    
    .scanner-title {
        font-size: 24px;
        color: var(--primary-color);
        margin-bottom: 10px;
    }
    
    .scanner-subtitle {
        color: #666;
        margin-bottom: 25px;
    }
    
    #reader {
        width: 100%;
        max-width: 500px;
        margin: 0 auto 20px;
        border: 3px dashed var(--primary-color);
        border-radius: 10px;
        padding: 20px;
        background: #f8f9fa;
    }
    
    .manual-input-section {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px dashed var(--gray-medium);
    }
    
    .manual-input-section h3 {
        color: #666;
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
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
        text-align: center;
        text-transform: uppercase;
    }
    
    .input-group input:focus {
        outline: none;
        border-color: var(--primary-color);
    }
    
    .btn-scan {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s;
    }
    
    .btn-scan:hover {
        background: var(--secondary-color);
    }
    
    .booking-result {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .booking-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 2px solid var(--gray-light);
    }
    
    .booking-header h2 {
        margin: 0;
        color: var(--dark-color);
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .booking-details-grid {
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
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .detail-card .value {
        font-size: 18px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .guest-info {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 25px;
    }
    
    .guest-info h3 {
        color: white;
        margin-bottom: 15px;
        font-size: 18px;
    }
    
    .guest-info p {
        margin: 5px 0;
        opacity: 0.9;
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
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    .btn-checkin {
        background: var(--success-color);
        color: white;
    }
    
    .btn-checkout {
        background: var(--info-color);
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
        border: 1px solid #f5c6cb;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .scan-again-btn {
        margin-top: 20px;
        padding: 12px 25px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
    }
    
    @media (max-width: 600px) {
        .booking-details-grid {
            grid-template-columns: 1fr;
        }
        
        .booking-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="scanner-container">
    <h1 style="text-align: center; margin-bottom: 30px;">
        <i class="fas fa-qrcode" style="color: var(--primary-color);"></i>
        QR Code Scanner
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
    
    <?php if (!$booking): ?>
        <!-- Scanner Section -->
        <div class="scanner-box">
            <h2 class="scanner-title">Scan Booking QR Code</h2>
            <p class="scanner-subtitle">Position the QR code within the frame to scan<br><small>Reference format: BBHYYYYMMDDXXXXXX</small></p>
            
            <div id="reader"></div>
            
            <button class="scan-again-btn" onclick="startScanning()" style="display: none;" id="startScanBtn">
                <i class="fas fa-camera"></i> Start Camera
            </button>
            
            <!-- Manual Input Fallback -->
            <div class="manual-input-section">
                <h3><i class="fas fa-keyboard"></i> Or Enter Reference Number</h3>
                <form method="POST" action="">
                    <div class="input-group">
                        <input type="text" 
                               name="reference_number" 
                               placeholder="BBH20260330464CFE"
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
        <!-- Booking Details Section -->
        <div class="booking-result">
            <div class="booking-header">
                <h2>Booking #<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                <?php 
                $status = $booking['status'];
                $statusColor = $statusColors[$status] ?? $statusColors['pending'];
                ?>
                <span class="status-badge" style="background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>">
                    <i class="fas fa-<?php echo $status === 'checked_in' ? 'door-open' : ($status === 'checked_out' ? 'sign-out-alt' : 'calendar-check'); ?>"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                </span>
            </div>
            
            <!-- Guest Information -->
            <div class="guest-info">
                <h3><i class="fas fa-user"></i> Guest Information</h3>
                <p><strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong></p>
                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['phone'] ?: 'N/A'); ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['email']); ?></p>
                <?php if ($booking['address']): ?>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['address'] . ', ' . $booking['city'] . ', ' . $booking['country']); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Booking Details -->
            <div class="booking-details-grid">
                <div class="detail-card">
                    <label>Room Type</label>
                    <div class="value"><?php echo htmlspecialchars($booking['category_name']); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Room Number</label>
                    <div class="value"><?php echo $booking['room_number'] ? htmlspecialchars($booking['room_number']) : 'Not Assigned'; ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Check-in Date</label>
                    <div class="value"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Check-out Date</label>
                    <div class="value"><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Nights</label>
                    <div class="value"><?php echo $booking['nights']; ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Guests</label>
                    <div class="value"><?php echo $booking['adults'] + $booking['children']; ?> (<?php echo $booking['adults']; ?> adults, <?php echo $booking['children']; ?> children)</div>
                </div>
                
                <div class="detail-card">
                    <label>Total Amount</label>
                    <div class="value">₱<?php echo number_format($booking['total_amount'], 2); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Payment Status</label>
                    <div class="value" style="color: <?php echo $paymentStatusColors[$booking['payment_status']][1] ?? '#856404'; ?>">
                        <?php echo ucfirst($booking['payment_status']); ?>
                    </div>
                </div>
            </div>
            
            <?php if ($booking['special_requests']): ?>
                <div class="detail-card" style="margin-bottom: 25px;">
                    <label>Special Requests</label>
                    <div style="margin-top: 10px; color: #333;"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($booking['status'] === 'confirmed'): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                        <input type="hidden" name="action" value="check_in">
                        <button type="submit" class="btn-action btn-checkin" onclick="return confirm('Confirm check-in for this guest?')">
                            <i class="fas fa-door-open"></i> Check In Guest
                        </button>
                    </form>
                <?php elseif ($booking['status'] === 'checked_in'): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                        <input type="hidden" name="action" value="check_out">
                        <button type="submit" class="btn-action btn-checkout" onclick="return confirm('Confirm check-out for this guest?')">
                            <i class="fas fa-sign-out-alt"></i> Check Out Guest
                        </button>
                    </form>
                <?php endif; ?>
                
                <button type="button" class="btn-action btn-scan-new" onclick="viewFullDetails(<?php echo $booking['booking_id']; ?>)">
                    <i class="fas fa-external-link-alt"></i> View Full Details
                </button>
                
                <a href="staff-qr-scanner.php" class="btn-action btn-scan-new" style="text-decoration: none;">
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
        // Stop scanning
        if (html5QrCode) {
            html5QrCode.stop().then(() => {
                // Process the scanned QR code
                processScannedCode(decodedText);
            });
        }
    }
    
    function onScanFailure(error) {
        // console.warn(`Code scan error = ${error}`);
    }
    
    function processScannedCode(code) {
        // Show loading message
        const reader = document.getElementById('reader');
        reader.innerHTML = '<p style="color: var(--primary-color); padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Processing QR Code...</p>';
        
        // Create a form and submit it
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
    
    // JavaScript function to navigate to staff-bookings.php with scanned booking highlighting
    function viewFullDetails(bookingId) {
        // Store the scanned booking ID in localStorage
        localStorage.setItem('scannedBookingId', bookingId);
        // Navigate to staff-bookings.php with the booking ID
        window.location.href = 'staff-bookings.php?id=' + bookingId;
    }
    
    // Start scanning when page loads (if no booking is displayed)
    <?php if (!$booking): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Small delay to ensure DOM is ready
        setTimeout(startScanning, 500);
    });
    <?php endif; ?>
</script>

<?php require_once '../includes/staff-footer.php'; ?>
