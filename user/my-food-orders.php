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
        showAlert('Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . ' cancelled successfully', 'success');
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
                        <button type="button" class="btn btn-sm" style="background-color: #dc3545; color: white;" onclick="openDeleteModal(null, 'Cancel Order', 'Are you sure you want to cancel Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?>? This action cannot be undone.', '<?php echo SITE_URL; ?>/user/my-food-orders.php?cancel=<?php echo $order['order_id']; ?>')">
                            <i class="fas fa-times"></i> Cancel Order
                        </button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'delivered' && !isItemRated('food', $order['order_id'], $userId)): ?>
                        <button type="button" class="btn btn-sm" style="background-color: #ffc107; color: #000;" onclick="openRateNowModal('food', <?php echo $order['order_id']; ?>, '<?php echo htmlspecialchars($order['item_name']); ?>')">
                            <i class="fas fa-star" style="margin-right: 5px;"></i>Rate Now
                        </button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'delivered' && isItemRated('food', $order['order_id'], $userId)): ?>
                        <span class="btn btn-sm" style="background-color: #28a745; color: white; cursor: default;">
                            <i class="fas fa-check" style="margin-right: 5px;"></i>Rated
                        </span>
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

<?php require_once '../includes/rating-prompt.php'; ?>

<script>
// Rate Now Modal Functions
function openRateNowModal(serviceType, itemId, itemName) {
    let modal = document.getElementById('rateNowModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'rateNowModal';
        modal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.6);z-index:10000;justify-content:center;align-items:center;';
        modal.innerHTML = `
            <div style="background:white;border-radius:16px;width:90%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;">
                <div style="background:linear-gradient(135deg,#367D8A,#285F6B);color:white;padding:20px 25px;display:flex;justify-content:space-between;align-items:center;">
                    <h3 style="margin:0;font-size:18px;font-weight:600;"><i class="fas fa-star" style="margin-right:8px;color:#ffc107;"></i> Rate Your Experience</h3>
                    <button type="button" onclick="closeRateNowModal()" style="background:none;border:none;color:white;font-size:28px;cursor:pointer;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;">&times;</button>
                </div>
                <div style="padding:25px;">
                    <div style="display:flex;align-items:center;gap:15px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #eee;">
                        <div style="width:60px;height:60px;background:linear-gradient(135deg,#f8f9fa,#e9ecef);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas" id="rateNowIcon" style="font-size:28px;color:#367D8A;"></i>
                        </div>
                        <div>
                            <h4 id="rateNowItemName" style="margin:0 0 5px 0;font-size:16px;color:#333;"></h4>
                            <p id="rateNowServiceTypeLabel" style="margin:0 0 3px 0;font-size:13px;color:#666;text-transform:uppercase;letter-spacing:0.5px;"></p>
                        </div>
                    </div>
                    <p style="text-align:center;color:#555;margin-bottom:25px;font-size:15px;line-height:1.5;">How would you rate your experience?</p>
                    <form id="rateNowForm" onsubmit="submitRateNow(event)">
                        <input type="hidden" name="service_type" id="rateNowServiceTypeInput">
                        <input type="hidden" name="item_id" id="rateNowItemId">
                        <div style="text-align:center;margin-bottom:25px;">
                            <div class="star-rating" style="display:flex;flex-direction:row-reverse;justify-content:center;gap:8px;margin-bottom:10px;">
                                <input type="radio" id="rnstar5" name="rating" value="5" required style="display:none;">
                                <label for="rnstar5" title="5 stars" style="cursor:pointer;font-size:36px;color:#ddd;transition:all 0.2s;padding:5px;"><i class="fas fa-star"></i></label>
                                <input type="radio" id="rnstar4" name="rating" value="4" style="display:none;">
                                <label for="rnstar4" title="4 stars" style="cursor:pointer;font-size:36px;color:#ddd;transition:all 0.2s;padding:5px;"><i class="fas fa-star"></i></label>
                                <input type="radio" id="rnstar3" name="rating" value="3" style="display:none;">
                                <label for="rnstar3" title="3 stars" style="cursor:pointer;font-size:36px;color:#ddd;transition:all 0.2s;padding:5px;"><i class="fas fa-star"></i></label>
                                <input type="radio" id="rnstar2" name="rating" value="2" style="display:none;">
                                <label for="rnstar2" title="2 stars" style="cursor:pointer;font-size:36px;color:#ddd;transition:all 0.2s;padding:5px;"><i class="fas fa-star"></i></label>
                                <input type="radio" id="rnstar1" name="rating" value="1" style="display:none;">
                                <label for="rnstar1" title="1 star" style="cursor:pointer;font-size:36px;color:#ddd;transition:all 0.2s;padding:5px;"><i class="fas fa-star"></i></label>
                            </div>
                            <div id="rateNowRatingLabel" style="font-size:14px;color:#666;min-height:20px;">Select a rating</div>
                        </div>
                        <div style="margin-bottom:25px;">
                            <label for="rateNowComment" style="display:block;font-size:14px;font-weight:500;color:#333;margin-bottom:8px;">Add a comment (optional)</label>
                            <textarea id="rateNowComment" name="comment" rows="3" placeholder="Tell us about your experience..." maxlength="500" style="width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:10px;font-size:14px;font-family:inherit;resize:vertical;"></textarea>
                            <div style="text-align:right;font-size:12px;color:#999;margin-top:5px;"><span id="rateNowCharCount">0</span>/500</div>
                        </div>
                        <div style="display:flex;gap:12px;justify-content:space-between;">
                            <button type="button" onclick="closeRateNowModal()" style="flex:1;padding:14px 24px;background:#f5f5f5;border:2px solid #ddd;border-radius:10px;font-size:14px;font-weight:500;color:#666;cursor:pointer;">Cancel</button>
                            <button type="submit" id="rateNowSubmitBtn" disabled style="flex:2;padding:14px 24px;background:linear-gradient(135deg,#367D8A,#285F6B);border:none;border-radius:10px;font-size:14px;font-weight:600;color:white;cursor:pointer;box-shadow:0 4px 15px rgba(54,125,138,0.3);">Submit Rating</button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        const style = document.createElement('style');
        style.textContent = '.star-rating label:hover, .star-rating label:hover ~ label, .star-rating input:checked ~ label { color: #ffc107 !important; transform: scale(1.1); } .star-rating label:hover { text-shadow: 0 0 10px rgba(255,193,7,0.5); } #rateNowRatingLabel.rated { color: #367D8A; font-weight: 600; }';
        document.head.appendChild(style);
        
        const ratingInputs = modal.querySelectorAll('.star-rating input');
        const ratingLabel = document.getElementById('rateNowRatingLabel');
        const submitBtn = document.getElementById('rateNowSubmitBtn');
        const ratingLabels = { 1: 'Poor - 1 star', 2: 'Fair - 2 stars', 3: 'Good - 3 stars', 4: 'Very Good - 4 stars', 5: 'Excellent - 5 stars' };
        
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                ratingLabel.textContent = ratingLabels[this.value];
                ratingLabel.classList.add('rated');
                submitBtn.disabled = false;
            });
        });
        
        const commentTextarea = document.getElementById('rateNowComment');
        const charCount = document.getElementById('rateNowCharCount');
        commentTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    document.getElementById('rateNowServiceTypeInput').value = serviceType;
    document.getElementById('rateNowItemId').value = itemId;
    document.getElementById('rateNowItemName').textContent = itemName;
    
    const serviceLabels = { 'room': 'Room Booking', 'event': 'Event Booking', 'food': 'Food Order' };
    const serviceIcons = { 'room': 'fa-bed', 'event': 'fa-calendar-alt', 'food': 'fa-utensils' };
    document.getElementById('rateNowServiceTypeLabel').textContent = serviceLabels[serviceType];
    document.getElementById('rateNowIcon').className = 'fas ' + serviceIcons[serviceType];
    
    document.getElementById('rateNowForm').reset();
    document.getElementById('rateNowRatingLabel').textContent = 'Select a rating';
    document.getElementById('rateNowRatingLabel').classList.remove('rated');
    document.getElementById('rateNowCharCount').textContent = '0';
    document.getElementById('rateNowSubmitBtn').disabled = true;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRateNowModal() {
    const modal = document.getElementById('rateNowModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function submitRateNow(event) {
    event.preventDefault();
    
    const form = document.getElementById('rateNowForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('rateNowSubmitBtn');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    const data = {
        action: 'submit',
        service_type: formData.get('service_type'),
        item_id: formData.get('item_id'),
        rating: formData.get('rating'),
        comment: formData.get('comment')
    };
    
    fetch('<?php echo SITE_URL; ?>/api/submit-rating.php', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Thank You!';
            submitBtn.style.background = '#28a745';
            setTimeout(() => {
                closeRateNowModal();
                location.reload();
            }, 1500);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit Rating';
            alert(data.message || 'Failed to submit rating. Please try again.');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Rating';
        alert('An error occurred. Please try again.');
    });
}

window.onclick = function(event) {
    if (event.target.id === 'rateNowModal') {
        closeRateNowModal();
    }
}
</script>

<?php require_once '../includes/user-footer.php'; ?>
