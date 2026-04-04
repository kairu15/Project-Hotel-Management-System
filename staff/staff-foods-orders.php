<?php
$pageTitle = 'Manage Food Orders';
require_once '../includes/config.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle status update - MUST be before header include
if (isset($_POST['update_status']) && is_numeric($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['status'] ?? '';
    
    $allowedStatuses = ['pending', 'preparing', 'ready', 'delivered', 'cancelled'];
    if (in_array($newStatus, $allowedStatuses)) {
        $updateFields = ['status = ?'];
        $params = [$newStatus];
        
        if ($newStatus === 'preparing') {
            $updateFields[] = 'prepared_at = NOW()';
        } elseif ($newStatus === 'delivered') {
            $updateFields[] = 'delivered_at = NOW()';
        }
        
        $params[] = $orderId;
        $sql = "UPDATE food_orders SET " . implode(', ', $updateFields) . " WHERE order_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        // Send notifications to user, staff, and admin
        require_once '../includes/notifications.php';
        
        // Get order details for notifications
        $orderStmt = $db->prepare("
            SELECT fo.*, u.first_name, u.last_name, u.user_id, mi.item_name, r.room_number
            FROM food_orders fo
            JOIN users u ON fo.user_id = u.user_id
            JOIN menu_items mi ON fo.food_id = mi.item_id
            LEFT JOIN bookings b ON fo.booking_id = b.booking_id
            LEFT JOIN rooms r ON b.room_id = r.room_id
            WHERE fo.order_id = ?
        ");
        $orderStmt->execute([$orderId]);
        $orderData = $orderStmt->fetch();
        
        if ($orderData) {
            $userId = $orderData['user_id'];
            $guestName = $orderData['first_name'] . ' ' . $orderData['last_name'];
            $roomNumber = $orderData['room_number'] ?? '';
            $foodItems = $orderData['item_name'] ?? '';
            
            // Notify user about order status update
            if (in_array($newStatus, ['preparing', 'ready', 'delivered', 'cancelled'])) {
                notifyFoodOrderUpdate($userId, $orderId, $newStatus);
                
                // Enhanced user notification with more details
                notifyUserFoodOrderStatus($userId, $orderId, $newStatus, $foodItems, $roomNumber);
            }
            
            // Notify admin about order status changes
            $processType = $newStatus === 'preparing' ? 'updated' : 
                          ($newStatus === 'delivered' ? 'completed' : 'updated');
            notifyAdminFoodOrderUpdate($orderId, $processType, $guestName, "Status changed to: {$newStatus}");
            
            // Notify staff about order status changes
            if (in_array($newStatus, ['preparing', 'ready', 'delivered'])) {
                $staffProcessType = $newStatus === 'preparing' ? 'new_order' :
                                   ($newStatus === 'ready' ? 'preparation_ready' : 'delivered');
                notifyStaffFoodOrderAssignment($orderId, $staffProcessType, $guestName, $roomNumber, $foodItems);
            }
        }
        
        showAlert('Order status updated successfully', 'success');
    } else {
        showAlert('Invalid status', 'danger');
    }
    redirect('staff-foods-orders.php');
}

// Handle delete order - MUST be before header include
if (isset($_POST['delete_order']) && is_numeric($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    $stmt = $db->prepare("DELETE FROM food_orders WHERE order_id = ? AND status IN ('cancelled', 'delivered')");
    $stmt->execute([$orderId]);
    
    if ($stmt->rowCount() > 0) {
        showAlert('Order deleted successfully', 'success');
    } else {
        showAlert('Cannot delete order. Only cancelled or delivered orders can be deleted.', 'danger');
    }
    redirect('staff-foods-orders.php');
}

// Now include header after all POST handling
require_once '../includes/staff-header.php';

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$orderTypeFilter = $_GET['order_type'] ?? '';
$userSearch = $_GET['user'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$sql = "
    SELECT fo.*, 
           mi.item_name, mi.image,
           mc.category_name,
           u.first_name, u.last_name, u.email, u.phone,
           r.room_number as booking_room
    FROM food_orders fo
    JOIN menu_items mi ON fo.food_id = mi.item_id
    JOIN menu_categories mc ON mi.cat_id = mc.cat_id
    JOIN users u ON fo.user_id = u.user_id
    LEFT JOIN bookings b ON fo.booking_id = b.booking_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE 1=1
";

$params = [];

if ($statusFilter) {
    $sql .= " AND fo.status = ?";
    $params[] = $statusFilter;
}

if ($orderTypeFilter) {
    $sql .= " AND fo.order_type = ?";
    $params[] = $orderTypeFilter;
}

if ($userSearch) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$userSearch%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($dateFrom) {
    $sql .= " AND DATE(fo.created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(fo.created_at) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY FIELD(fo.status, 'pending', 'preparing', 'ready', 'delivered', 'cancelled'), fo.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Status color mapping
$statusColors = [
    'pending' => ['#fff3cd', '#856404', 'clock', 'Pending'],
    'preparing' => ['#cce5ff', '#004085', 'fire', 'Preparing'],
    'ready' => ['#d4edda', '#155724', 'check', 'Ready'],
    'delivered' => ['#e2e3e5', '#383d41', 'check-circle', 'Delivered'],
    'cancelled' => ['#f8d7da', '#721c24', 'times-circle', 'Cancelled']
];

// Payment status color mapping
$paymentStatusColors = [
    'pending' => ['#fff3cd', '#856404', 'hourglass-half', 'Pending'],
    'paid' => ['#d4edda', '#155724', 'check-circle', 'Paid'],
    'partial' => ['#cce5ff', '#004085', 'adjust', 'Partial']
];

// Calculate stats
$pendingCount = count(array_filter($orders, function($o) { return $o['status'] === 'pending'; }));
$preparingCount = count(array_filter($orders, function($o) { return $o['status'] === 'preparing'; }));
$readyCount = count(array_filter($orders, function($o) { return $o['status'] === 'ready'; }));
$deliveredToday = count(array_filter($orders, function($o) { 
    return $o['status'] === 'delivered' && date('Y-m-d', strtotime($o['created_at'])) === date('Y-m-d'); 
}));
$totalRevenue = array_sum(array_map(function($o) { return $o['status'] !== 'cancelled' ? $o['total_price'] : 0; }, $orders));
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
                <h3 style="font-size: 28px; margin: 0;"><?php echo $preparingCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Preparing</p>
            </div>
            <i class="fas fa-fire" style="font-size: 28px; color: var(--info-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $readyCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Ready</p>
            </div>
            <i class="fas fa-check" style="font-size: 28px; color: var(--success-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $deliveredToday; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Delivered Today</p>
            </div>
            <i class="fas fa-check-circle" style="font-size: 28px; color: var(--primary-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #6f42c1;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 24px; margin: 0;"><?php echo formatPrice($totalRevenue); ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Total Revenue</p>
            </div>
            <i class="fas fa-peso-sign" style="font-size: 28px; color: #6f42c1;"></i>
        </div>
    </div>
</div>

<!-- Filters -->
<div style="background-color: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <form method="GET" action="" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; align-items: end;">
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Status</label>
            <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All Status</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="preparing" <?php echo $statusFilter === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                <option value="ready" <?php echo $statusFilter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Order Type</label>
            <select name="order_type" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All Types</option>
                <option value="room_service" <?php echo $orderTypeFilter === 'room_service' ? 'selected' : ''; ?>>Room Service</option>
                <option value="dine_in" <?php echo $orderTypeFilter === 'dine_in' ? 'selected' : ''; ?>>Dine In</option>
                <option value="takeaway" <?php echo $orderTypeFilter === 'takeaway' ? 'selected' : ''; ?>>Takeaway</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Search User</label>
            <input type="text" name="user" value="<?php echo htmlspecialchars($userSearch); ?>" placeholder="Name or email..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Date From</label>
            <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Date To</label>
            <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;"><i class="fas fa-filter"></i> Filter</button>
            <a href="staff-foods-orders.php" class="btn btn-outline" style="padding: 10px 20px;"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
    <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px;"><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 10px;"></i>Food Orders</h3>
        <span style="background-color: var(--primary-color); color: white; padding: 5px 15px; border-radius: 20px; font-size: 14px;"><?php echo count($orders); ?> Orders</span>
    </div>
    
    <?php if (count($orders) > 0): ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: var(--gray-light);">
                    <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 600; color: #666;">Order #</th>
                    <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 600; color: #666;">Customer</th>
                    <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 600; color: #666;">Food Item</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Qty</th>
                    <th style="padding: 15px; text-align: right; font-size: 13px; font-weight: 600; color: #666;">Total</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Type</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Status</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Payment</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Ordered</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): 
                    $status = $statusColors[$order['status']] ?? $statusColors['pending'];
                    $paymentStatus = $paymentStatusColors[$order['payment_status']] ?? $paymentStatusColors['pending'];
                    $roomDisplay = $order['room_number'] ?? ($order['booking_room'] ?? 'N/A');
                ?>
                <tr style="border-bottom: 1px solid var(--gray-light);" data-order-id="<?php echo $order['order_id']; ?>">
                    <td style="padding: 15px;">
                        <span style="font-weight: 600; color: var(--primary-color);">#<?php echo $order['order_id']; ?></span>
                    </td>
                    <td style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 35px; height: 35px; border-radius: 50%; background-color: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;">
                                <?php echo strtoupper(substr($order['first_name'], 0, 1) . substr($order['last_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <p style="margin: 0; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                <p style="margin: 0; font-size: 12px; color: #666;"><?php echo htmlspecialchars($order['phone'] ?? $order['email']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 15px;">
                        <p style="margin: 0; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($order['item_name']); ?></p>
                        <p style="margin: 0; font-size: 12px; color: #666;"><?php echo htmlspecialchars($order['category_name']); ?></p>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="font-weight: 600;"><?php echo $order['quantity']; ?></span>
                    </td>
                    <td style="padding: 15px; text-align: right;">
                        <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($order['total_price']); ?></span>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="padding: 5px 12px; border-radius: 15px; font-size: 12px; background-color: var(--gray-light); text-transform: capitalize;">
                            <i class="fas fa-<?php echo $order['order_type'] === 'room_service' ? 'hotel' : ($order['order_type'] === 'dine_in' ? 'utensils' : 'shopping-bag'); ?>"></i>
                            <?php echo str_replace('_', ' ', $order['order_type']); ?>
                        </span>
                        <?php if ($order['order_type'] === 'room_service' && $roomDisplay !== 'N/A'): ?>
                        <p style="margin: 5px 0 0; font-size: 11px; color: #666;">Room: <?php echo htmlspecialchars($roomDisplay); ?></p>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; background-color: <?php echo $status[0]; ?>; color: <?php echo $status[1]; ?>">
                            <i class="fas fa-<?php echo $status[2]; ?>"></i>
                            <?php echo $status[3]; ?>
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; background-color: <?php echo $paymentStatus[0]; ?>; color: <?php echo $paymentStatus[1]; ?>">
                            <i class="fas fa-<?php echo $paymentStatus[2]; ?>"></i>
                            <?php echo $paymentStatus[3]; ?>
                        </span>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="font-size: 13px; color: #666;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                        <p style="margin: 0; font-size: 11px; color: #999;"><?php echo date('h:i A', strtotime($order['created_at'])); ?></p>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <!-- Status Update Form -->
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="status" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 12px; cursor: pointer;" <?php echo in_array($order['status'], ['cancelled', 'delivered']) ? 'disabled' : ''; ?>>
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                    <option value="ready" <?php echo $order['status'] === 'ready' ? 'selected' : ''; ?>>Ready</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                            
                            <?php if (in_array($order['status'], ['cancelled', 'delivered'])): ?>
                            <form method="POST" action="" style="display: inline;" id="deleteOrderForm<?php echo $order['order_id']; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="button" onclick="openDeleteModal('deleteOrderForm<?php echo $order['order_id']; ?>', 'Delete Order', 'Are you sure you want to delete Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>?', null, 'delete_order')" class="btn btn-danger btn-sm" style="padding: 6px 12px;"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="padding: 50px; text-align: center;">
        <i class="fas fa-utensils" style="font-size: 50px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h3 style="font-size: 20px; margin-bottom: 10px;">No Orders Found</h3>
        <p style="color: #666;">No food orders match your current filters.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/staff-footer.php'; ?>

<script>
// Highlight scanned food order row
function highlightScannedFoodOrder() {
    const scannedOrderId = localStorage.getItem('scannedFoodOrderId');
    if (scannedOrderId) {
        const row = document.querySelector(`tr[data-order-id="${scannedOrderId}"]`);
        if (row) {
            row.style.backgroundColor = '#d4edda';
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        localStorage.removeItem('scannedFoodOrderId');
    }
}

document.addEventListener('DOMContentLoaded', highlightScannedFoodOrder);
</script>
