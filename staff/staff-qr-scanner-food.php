<?php
$pageTitle = 'QR Code Scanner - Food Orders';
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

$order = null;
$error = null;
$success = null;

// Handle manual reference lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_reference'])) {
    $reference = trim($_POST['reference_number'] ?? '');
    
    if (empty($reference)) {
        $error = 'Please enter a reference number';
    } else {
        // Search by order_ref column directly - use LEFT JOINs to avoid missing data issues
        $stmt = $db->prepare("
            SELECT fo.*, 
                   COALESCE(mi.item_name, f.food_name) as item_name, 
                   COALESCE(mi.description, f.description) as item_description,
                   mc.category_name,
                   u.first_name, u.last_name, u.email, u.phone,
                   r.room_number as booking_room
            FROM food_orders fo 
            LEFT JOIN menu_items mi ON fo.food_id = mi.item_id 
            LEFT JOIN foods f ON fo.food_id = f.food_id
            LEFT JOIN menu_categories mc ON mi.cat_id = mc.cat_id
            JOIN users u ON fo.user_id = u.user_id
            LEFT JOIN bookings b ON fo.booking_id = b.booking_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE fo.order_ref = ?
        ");
        $stmt->execute([$reference]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $error = 'Food order not found. Please check the reference number.';
        }
    }
}

// Handle status update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $action = $_POST['action'];
    
    $newStatus = '';
    $timestampField = '';
    
    if ($action === 'preparing') {
        $newStatus = 'preparing';
    } elseif ($action === 'ready') {
        $newStatus = 'ready';
    } elseif ($action === 'deliver') {
        $newStatus = 'delivered';
        $timestampField = ', delivered_at = NOW()';
    } elseif ($action === 'cancel') {
        $newStatus = 'cancelled';
    }
    
    if ($newStatus) {
        $stmt = $db->prepare("UPDATE food_orders SET status = ?{$timestampField} WHERE order_id = ?");
        if ($stmt->execute([$newStatus, $orderId])) {
            $success = 'Order status updated to ' . ucfirst($newStatus);
            // Refresh order data
            $stmt = $db->prepare("
                SELECT fo.*, 
                       COALESCE(mi.item_name, f.food_name) as item_name, 
                       COALESCE(mi.description, f.description) as item_description,
                       mc.category_name,
                       u.first_name, u.last_name, u.email, u.phone,
                       r.room_number as booking_room
                FROM food_orders fo 
                LEFT JOIN menu_items mi ON fo.food_id = mi.item_id 
                LEFT JOIN foods f ON fo.food_id = f.food_id
                LEFT JOIN menu_categories mc ON mi.cat_id = mc.cat_id
                JOIN users u ON fo.user_id = u.user_id
                LEFT JOIN bookings b ON fo.booking_id = b.booking_id
                LEFT JOIN rooms r ON b.room_id = r.room_id
                WHERE fo.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
        } else {
            $error = 'Failed to update order status';
        }
    }
}

// Status colors
$statusColors = [
    'pending' => ['#fff3cd', '#856404', 'clock'],
    'preparing' => ['#cce5ff', '#004085', 'utensils'],
    'ready' => ['#d4edda', '#155724', 'check-circle'],
    'delivered' => ['#e2e3e5', '#383d41', 'flag-checkered'],
    'cancelled' => ['#f8d7da', '#721c24', 'times-circle']
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
    
    .order-result {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    }
    
    .order-header {
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
    
    .order-details-grid {
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
        flex-wrap: wrap;
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
    
    .btn-preparing {
        background: #17a2b8;
        color: white;
    }
    
    .btn-ready {
        background: #ffc107;
        color: #000;
    }
    
    .btn-deliver {
        background: #28a745;
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
        Food Order QR Code Scanner
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
    
    <?php if (!$order): ?>
        <!-- Scanner Section -->
        <div class="scanner-box">
            <h2 class="scanner-title">Scan Food Order QR Code</h2>
            <p class="scanner-subtitle">Position the QR code within the frame to scan<br><small>Reference format: FODYYYYMMDDXXXXXX</small></p>
            
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
                               placeholder="FOD2026033094BA65"
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
        <!-- Order Details Section -->
        <div class="order-result">
            <div class="order-header">
                <div>
                    <h2>Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                    <p style="color: #666; margin: 5px 0 0 0;">
                        <i class="fas fa-utensils"></i> 
                        <?php echo htmlspecialchars($order['item_name']); ?>
                        (Qty: <?php echo $order['quantity']; ?>)
                    </p>
                </div>
                <?php 
                $status = $order['status'];
                $statusColor = $statusColors[$status] ?? $statusColors['pending'];
                ?>
                <span class="status-badge" style="background-color: <?php echo $statusColor[0]; ?>; color: <?php echo $statusColor[1]; ?>">
                    <i class="fas fa-<?php echo $statusColor[2]; ?>"></i>
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
            
            <!-- Order Info -->
            <div class="order-details-grid">
                <div class="detail-card">
                    <label>Menu Item</label>
                    <div class="value"><?php echo htmlspecialchars($order['item_name']); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Category</label>
                    <div class="value"><?php echo htmlspecialchars($order['category_name']); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Quantity</label>
                    <div class="value"><?php echo $order['quantity']; ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Total Price</label>
                    <div class="value">₱<?php echo number_format($order['total_price'], 2); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Order Type</label>
                    <div class="value"><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></div>
                </div>
                
                <div class="detail-card">
                    <label>Room Number</label>
                    <div class="value"><?php echo $order['room_number'] ?: ($order['booking_room'] ?: 'N/A'); ?></div>
                </div>
            </div>
            
            <!-- Payment Info -->
            <div class="detail-card" style="margin-bottom: 20px;">
                <label>Payment Information</label>
                <div style="margin-top: 10px;">
                    <strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?><br>
                    <strong>Status:</strong> <?php echo ucfirst($order['payment_status']); ?>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="detail-card" style="margin-bottom: 20px;">
                <label>Guest Information</label>
                <div style="margin-top: 10px;">
                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong><br>
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($order['email']); ?><br>
                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone'] ?: 'N/A'); ?>
                </div>
            </div>
            
            <?php if ($order['special_instructions']): ?>
                <div class="detail-card" style="margin-bottom: 25px;">
                    <label>Special Instructions</label>
                    <div style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($order['status'] === 'preparing'): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <input type="hidden" name="action" value="ready">
                        <button type="submit" class="btn-action btn-ready">
                            <i class="fas fa-check"></i> Mark Ready
                        </button>
                    </form>
                <?php elseif ($order['status'] === 'ready'): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <input type="hidden" name="action" value="deliver">
                        <button type="submit" class="btn-action btn-deliver">
                            <i class="fas fa-motorcycle"></i> Mark Delivered
                        </button>
                    </form>
                <?php endif; ?>
                
                <button type="button" class="btn-action btn-scan-new" onclick="viewFullDetails(<?php echo $order['order_id']; ?>, 'food')">
                    <i class="fas fa-external-link-alt"></i> View Full Details
                </button>
                
                <a href="staff-qr-scanner-food.php" class="btn-action btn-scan-new" style="text-decoration: none;">
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
    function viewFullDetails(id, type) {
        localStorage.setItem('scannedFoodOrderId', id);
        window.location.href = 'staff-foods-orders.php?id=' + id;
    }
    
    <?php if (!$order): ?>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(startScanning, 500);
    });
    <?php endif; ?>
</script>

<?php require_once '../includes/staff-header.php'; ?>
