<?php
$pageTitle = 'My Food Orders';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle cancel order
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $orderId = intval($_GET['cancel']);
    
    // Verify the order belongs to the current user and is pending
    $stmt = $db->prepare("SELECT status FROM food_orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();
    
    if ($order && $order['status'] === 'pending') {
        $stmt = $db->prepare("UPDATE food_orders SET status = 'cancelled' WHERE order_id = ?");
        $stmt->execute([$orderId]);
        showAlert('Order cancelled successfully', 'success');
    } else {
        showAlert('Unable to cancel this order', 'danger');
    }
    redirect('my-food-orders.php');
}

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query for user's food orders
$sql = "
    SELECT fo.*, mi.item_name, mi.image, mc.category_name,
           r.room_number as booking_room
    FROM food_orders fo
    JOIN menu_items mi ON fo.food_id = mi.item_id
    JOIN menu_categories mc ON mi.cat_id = mc.cat_id
    LEFT JOIN bookings b ON fo.booking_id = b.booking_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE fo.user_id = ?
";
$params = [$userId];

// Apply status filter
if ($filter === 'pending') {
    $sql .= " AND fo.status IN ('pending', 'preparing')";
} elseif ($filter === 'delivered') {
    $sql .= " AND fo.status = 'delivered'";
} elseif ($filter === 'cancelled') {
    $sql .= " AND fo.status = 'cancelled'";
}

// Apply search filter
if ($search) {
    $sql .= " AND (mi.item_name LIKE ? OR mc.category_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY fo.created_at DESC";

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

// Calculate stats
$pendingCount = count(array_filter($orders, function($o) { return in_array($o['status'], ['pending', 'preparing']); }));
$deliveredCount = count(array_filter($orders, function($o) { return $o['status'] === 'delivered'; }));
$totalSpent = array_sum(array_map(function($o) { return $o['status'] !== 'cancelled' ? $o['total_price'] : 0; }, $orders));
?>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
    <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 32px; margin: 0; color: var(--dark-color);"><?php echo $pendingCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0;">Pending Orders</p>
            </div>
            <i class="fas fa-clock" style="font-size: 40px; color: var(--warning-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 32px; margin: 0; color: var(--dark-color);"><?php echo $deliveredCount; ?></h3>
                <p style="color: #666; margin: 5px 0 0;">Delivered</p>
            </div>
            <i class="fas fa-check-circle" style="font-size: 40px; color: var(--success-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 32px; margin: 0; color: var(--dark-color);"><?php echo formatPrice($totalSpent); ?></h3>
                <p style="color: #666; margin: 5px 0 0;">Total Spent</p>
            </div>
            <i class="fas fa-wallet" style="font-size: 40px; color: var(--primary-color);"></i>
        </div>
    </div>
</div>

<!-- Filter Tabs & Search -->
<div style="background-color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="display: inline-flex; background-color: var(--gray-light); padding: 5px; border-radius: 10px;">
            <a href="?filter=all" style="padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'all' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">All Orders</a>
            <a href="?filter=pending" style="padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'pending' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Pending</a>
            <a href="?filter=delivered" style="padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'delivered' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Delivered</a>
            <a href="?filter=cancelled" style="padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $filter === 'cancelled' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Cancelled</a>
        </div>
        
        <form method="GET" action="" style="display: flex; gap: 10px;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search food items..." style="padding: 10px 15px; border: 1px solid var(--gray-light); border-radius: 5px; width: 250px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;"><i class="fas fa-search"></i></button>
            <?php if ($search): ?>
            <a href="?filter=<?php echo $filter; ?>" class="btn btn-outline" style="padding: 10px 20px;"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Orders List -->
<?php if (count($orders) > 0): ?>
<div style="display: flex; flex-direction: column; gap: 25px;">
    <?php foreach ($orders as $order): 
        $status = $statusColors[$order['status']] ?? $statusColors['pending'];
        $canCancel = $order['status'] === 'pending';
        $roomDisplay = $order['room_number'] ?? ($order['booking_room'] ?? 'N/A');
    ?>
    <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <div style="display: flex;">
            <!-- Food Image -->
            <div style="width: 200px; min-height: 180px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white;">
                <?php if ($order['image']): ?>
                <img src="../assets/<?php echo htmlspecialchars($order['image']); ?>" alt="<?php echo htmlspecialchars($order['item_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                <i class="fas fa-utensils" style="font-size: 50px; opacity: 0.5;"></i>
                <?php endif; ?>
            </div>
            
            <!-- Content -->
            <div style="flex: 1; padding: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <div>
                        <span style="display: inline-block; padding: 4px 12px; background-color: var(--gray-light); border-radius: 15px; font-size: 12px; color: #666; margin-bottom: 8px;">
                            Order #<?php echo $order['order_id']; ?>
                        </span>
                        <h3 style="font-size: 20px; margin-bottom: 5px;"><?php echo htmlspecialchars($order['item_name']); ?></h3>
                        <p style="color: #666; font-size: 14px;"><i class="fas fa-tag" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo htmlspecialchars($order['category_name']); ?></p>
                    </div>
                    <span style="display: inline-flex; align-items: center; gap: 5px; padding: 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; background-color: <?php echo $status[0]; ?>; color: <?php echo $status[1]; ?>">
                        <i class="fas fa-<?php echo $status[2]; ?>"></i>
                        <?php echo $status[3]; ?>
                    </span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                    <div>
                        <p style="font-size: 11px; color: #666; margin-bottom: 3px;">Quantity</p>
                        <p style="font-size: 16px; font-weight: 600;"><i class="fas fa-shopping-basket" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo $order['quantity']; ?></p>
                    </div>
                    <div>
                        <p style="font-size: 11px; color: #666; margin-bottom: 3px;">Unit Price</p>
                        <p style="font-size: 16px; font-weight: 600;"><?php echo formatPrice($order['unit_price']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 11px; color: #666; margin-bottom: 3px;">Total</p>
                        <p style="font-size: 16px; font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($order['total_price']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 11px; color: #666; margin-bottom: 3px;">Order Type</p>
                        <p style="font-size: 14px; font-weight: 600; text-transform: capitalize;"><i class="fas fa-<?php echo $order['order_type'] === 'room_service' ? 'hotel' : ($order['order_type'] === 'dine_in' ? 'utensils' : 'shopping-bag'); ?>" style="color: var(--primary-color); margin-right: 5px;"></i><?php echo str_replace('_', ' ', $order['order_type']); ?></p>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <div>
                            <p style="font-size: 12px; color: #666; margin-bottom: 3px;"><i class="fas fa-calendar" style="color: var(--primary-color); margin-right: 5px;"></i>Ordered</p>
                            <p style="font-size: 14px; font-weight: 500;"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <?php if ($order['order_type'] === 'room_service' && $roomDisplay !== 'N/A'): ?>
                        <div>
                            <p style="font-size: 12px; color: #666; margin-bottom: 3px;"><i class="fas fa-door-open" style="color: var(--primary-color); margin-right: 5px;"></i>Room</p>
                            <p style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($roomDisplay); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($order['special_instructions']): ?>
                        <div>
                            <p style="font-size: 12px; color: #666; margin-bottom: 3px;"><i class="fas fa-comment" style="color: var(--primary-color); margin-right: 5px;"></i>Special Instructions</p>
                            <p style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($order['special_instructions']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <?php if ($canCancel): ?>
                        <a href="?cancel=<?php echo $order['order_id']; ?>" class="btn btn-sm" style="background-color: #dc3545; color: white;" onclick="return confirm('Are you sure you want to cancel this order?');">
                            <i class="fas fa-times"></i> Cancel Order
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div style="background-color: white; padding: 60px; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <i class="fas fa-utensils" style="font-size: 60px; color: var(--gray-medium); margin-bottom: 25px;"></i>
    <h3 style="font-size: 22px; margin-bottom: 10px;">No Food Orders Found</h3>
    <p style="color: #666; margin-bottom: 25px;">You haven't placed any <?php echo $filter !== 'all' ? $filter : ''; ?> food orders yet.</p>
    <a href="../dining.php" class="btn btn-primary">Browse Menu</a>
</div>
<?php endif; ?>

<?php require_once '../includes/user-footer.php'; ?>
