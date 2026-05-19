<?php
$pageTitle = 'Order Now';
require_once 'includes/config.php';
require_once 'includes/header.php';

$db = getDB();
$userId = $_SESSION['user_id'] ?? 0;

// Get user's active bookings for room service option
$bookingsStmt = $db->prepare("
    SELECT b.booking_id, b.check_in, b.check_out, r.room_number, rc.category_name as room_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.room_id
    JOIN room_categories rc ON r.category_id = rc.category_id
    WHERE b.user_id = ? 
    AND b.status IN ('confirmed', 'checked_in')
    AND b.check_out >= CURDATE()
    ORDER BY b.check_in DESC
");
$bookingsStmt->execute([$userId]);
$userBookings = $bookingsStmt->fetchAll();

// Get all available menu items from foods table
$itemsStmt = $db->query("
    SELECT f.*, mc.category_name 
    FROM foods f 
    JOIN menu_categories mc ON f.category_id = mc.cat_id 
    WHERE f.is_available = 1 AND f.stock_quantity > 0
    ORDER BY mc.sort_order, mc.category_name, f.food_name
");
$menuItems = $itemsStmt->fetchAll();

// Group items by category
$groupedItems = [];
foreach ($menuItems as $item) {
    $groupedItems[$item['category_name']][] = $item;
}

// Get pre-selected item if provided
$preSelectedItemId = isset($_GET['food_id']) ? intval($_GET['food_id']) : (isset($_GET['item_id']) ? intval($_GET['item_id']) : 0);

// Handle form submission (only for logged in users)
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = intval($_POST['item_id'] ?? $_POST['food_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $orderType = $_POST['order_type'] ?? 'dine_in';
    $bookingId = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
    $roomNumber = $_POST['room_number'] ?? null;
    $specialInstructions = $_POST['special_instructions'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'pay_at_hotel';

    // Validate
    if (!$itemId || $quantity < 1) {
        showAlert('Please select a food item and valid quantity', 'danger');
    } else {
        // Get item price and stock from foods table
        $itemStmt = $db->prepare("SELECT food_name, price, stock_quantity FROM foods WHERE food_id = ? AND is_available = 1");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch();
        
        if (!$item) {
            showAlert('Selected food item is not available', 'danger');
        } elseif ($item['stock_quantity'] < $quantity) {
            showAlert('Insufficient stock available. Only ' . $item['stock_quantity'] . ' items remaining.', 'danger');
        } else {
            $unitPrice = $item['price'];
            $totalPrice = $unitPrice * $quantity;
            
            // Insert order with food_id
            $insertStmt = $db->prepare("
                INSERT INTO food_orders 
                (user_id, booking_id, food_id, quantity, unit_price, total_price, status, order_type, payment_method, payment_status, room_number, special_instructions, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW())
            ");
            
            try {
                $insertStmt->execute([
                    $userId, 
                    $bookingId, 
                    $itemId, 
                    $quantity, 
                    $unitPrice, 
                    $totalPrice, 
                    $orderType, 
                    $paymentMethod,
                    'pending',
                    $roomNumber, 
                    $specialInstructions
                ]);
                
                // Decrease stock quantity
                $updateStockStmt = $db->prepare("UPDATE foods SET stock_quantity = stock_quantity - ? WHERE food_id = ?");
                $updateStockStmt->execute([$quantity, $itemId]);
                
                // Send notification to user, staff, and admin
                require_once 'includes/notifications.php';
                $orderId = $db->lastInsertId();
                
                // Generate and update Order Reference (FODYYYYMMDDXXXXXX)
                $orderRef = 'FOD' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
                $refStmt = $db->prepare("UPDATE food_orders SET order_ref = ? WHERE order_id = ?");
                $refStmt->execute([$orderRef, $orderId]);
                
                // Notify user about order placed
                notifyFoodOrderUpdate($userId, $orderId, 'pending');
                
                // Enhanced user notification
                $foodName = $item['food_name'] ?? '';
                notifyUserFoodOrderStatus($userId, $orderId, 'placed', $foodName, $roomNumber);
                
                // Get user info for staff/admin notifications
                $userStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
                $userStmt->execute([$userId]);
                $userData = $userStmt->fetch();
                
                if ($userData) {
                    $guestName = $userData['first_name'] . ' ' . $userData['last_name'];
                    
                    // Notify staff about new order
                    notifyStaffNewFoodOrder($orderId, $guestName, $roomNumber);
                    
                    // Notify admin about new food order
                    notifyAdminFoodOrderUpdate($orderId, 'placed', $guestName, "Order: {$foodName}, Qty: {$quantity}, Type: {$orderType}");
                    
                    // Notify staff about food order assignment
                    notifyStaffFoodOrderAssignment($orderId, 'new_order', $guestName, $roomNumber, $foodName);
                }
                
                showAlert('Order placed successfully! You can track your order in My Food Orders.', 'success');
                redirect('user/my-food-orders.php');
            } catch (PDOException $e) {
                error_log("Food order error: " . $e->getMessage());
                showAlert('Error placing order. Please try again.', 'danger');
            }
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Order Food</h1>
        <p>Place your order for dine-in or room service</p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li><a href="dining.php">Dining</a></li>
            <li>/</li>
            <li>Order Now</li>
        </ul>
    </div>
</div>

<!-- Order Form Section -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="max-width: 900px; margin: 0 auto;">
            <div style="background-color: white; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; position: relative;" id="orderFormContainer">
                <?php if (!isLoggedIn()): ?>
                <!-- Login Required Overlay -->
                <div id="loginOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.7); z-index: 10; display: flex; align-items: center; justify-content: center; border-radius: 20px; cursor: pointer;" onclick="showLoginForOrderForm();">
                    <div style="text-align: center; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 2px solid var(--primary-color);">
                        <i class="fas fa-lock" style="font-size: 48px; color: var(--primary-color); margin-bottom: 15px;"></i>
                        <h3 style="font-size: 24px; margin-bottom: 10px; color: var(--dark-color);">Login Required</h3>
                        <p style="color: #666;">Please login first in order to place a food order.</p>
                    </div>
                </div>

                <!-- Blurred Form -->
                <div style="filter: blur(5px); pointer-events: none; user-select: none;">
                <?php endif; ?>

                <!-- Form Header -->
                <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: 40px; text-align: center; color: white;">
                    <h2 style="color: white; font-size: 32px; margin-bottom: 10px;"><i class="fas fa-utensils"></i> Place Your Order</h2>
                    <p style="opacity: 0.9;">Choose from our delicious menu and enjoy our culinary delights</p>
                </div>

                <!-- Form -->
                <form method="POST" style="padding: 40px;" id="foodOrderForm">
                    <!-- Order Type Selection -->
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 15px; font-size: 18px;">Order Type</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                            <label style="cursor: pointer;">
                                <input type="radio" name="order_type" value="dine_in" checked 
                                       style="display: none;" 
                                       onchange="toggleRoomService(false)">
                                <div id="type-dine_in" style="padding: 20px; border: 2px solid var(--primary-color); border-radius: 10px; text-align: center; background-color: rgba(201, 168, 99, 0.1);" onclick="selectOrderType('dine_in')">
                                    <i class="fas fa-utensils" style="font-size: 24px; color: var(--primary-color); margin-bottom: 10px; display: block;"></i>
                                    <span style="font-weight: 600;">Dine In</span>
                                </div>
                            </label>
                            
                            <label style="cursor: pointer;">
                                <input type="radio" name="order_type" value="room_service" 
                                       style="display: none;" 
                                       onchange="toggleRoomService(true)">
                                <div id="type-room_service" style="padding: 20px; border: 2px solid #ddd; border-radius: 10px; text-align: center;" onclick="selectOrderType('room_service')">
                                    <i class="fas fa-bed" style="font-size: 24px; color: #666; margin-bottom: 10px; display: block;"></i>
                                    <span style="font-weight: 600;">Room Service</span>
                                </div>
                            </label>
                            
                            <label style="cursor: pointer;">
                                <input type="radio" name="order_type" value="takeaway" 
                                       style="display: none;">
                                <div id="type-takeaway" style="padding: 20px; border: 2px solid #ddd; border-radius: 10px; text-align: center;" onclick="selectOrderType('takeaway')">
                                    <i class="fas fa-shopping-bag" style="font-size: 24px; color: #666; margin-bottom: 10px; display: block;"></i>
                                    <span style="font-weight: 600;">Takeaway</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Room Selection (for room service) -->
                    <div id="room-selection" style="margin-bottom: 30px; display: none;">
                        <label style="display: block; font-weight: 600; margin-bottom: 15px; font-size: 18px;">Select Room</label>
                        <?php if (!empty($userBookings)): ?>
                        <select name="booking_id" id="booking_id" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 10px; font-size: 16px;" onchange="updateRoomNumber()">
                            <option value="">-- Select Your Room --</option>
                            <?php foreach ($userBookings as $booking): ?>
                            <option value="<?php echo $booking['booking_id']; ?>" data-room="<?php echo htmlspecialchars($booking['room_number']); ?>">
                                Room <?php echo htmlspecialchars($booking['room_number']); ?> (<?php echo htmlspecialchars($booking['room_name']); ?>) - 
                                Check-in: <?php echo formatDate($booking['check_in']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="room_number" id="room_number" value="">
                        <?php else: ?>
                        <div style="padding: 15px; background-color: #fff3cd; border-radius: 10px; color: #856404;">
                            <i class="fas fa-info-circle"></i> You don't have any active room bookings. Room service requires an active booking.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Food Item Selection -->
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 15px; font-size: 18px;">Select Food Item *</label>
                        <select name="food_id" id="food_id" required 
                                style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 10px; font-size: 16px;"
                                onchange="updatePrice()">
                            <option value="">-- Select Food Item --</option>
                            <?php foreach ($groupedItems as $category => $items): ?>
                            <optgroup label="<?php echo htmlspecialchars($category); ?>">
                                <?php foreach ($items as $item): ?>
                                <option value="<?php echo $item['food_id']; ?>" 
                                        data-price="<?php echo $item['price']; ?>"
                                        data-stock="<?php echo $item['stock_quantity']; ?>"
                                        <?php echo ($preSelectedItemId == $item['food_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['food_name']); ?> - <?php echo formatPrice($item['price']); ?> (Stock: <?php echo $item['stock_quantity']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <p id="stock-warning" style="color: #dc3545; font-size: 14px; margin-top: 8px; display: none;"></p>
                    </div>
                    
                    <!-- Quantity -->
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 15px; font-size: 18px;">Quantity *</label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <button type="button" onclick="changeQuantity(-1)" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--primary-color); background-color: transparent; color: var(--primary-color); font-size: 20px; cursor: pointer;">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="20" required
                                   style="width: 100px; padding: 15px; border: 2px solid #ddd; border-radius: 10px; font-size: 18px; text-align: center;">
                            <button type="button" onclick="changeQuantity(1)" style="width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--primary-color); background-color: transparent; color: var(--primary-color); font-size: 20px; cursor: pointer;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Price Summary -->
                    <div style="background-color: var(--gray-light); padding: 25px; border-radius: 15px; margin-bottom: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 18px;">Unit Price:</span>
                            <span id="unit-price" style="font-size: 18px; font-weight: 600;">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <span style="font-size: 18px;">Quantity:</span>
                            <span id="quantity-display" style="font-size: 18px; font-weight: 600;">1</span>
                        </div>
                        <div style="border-top: 2px solid #ddd; margin: 15px 0; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 22px; font-weight: 700;">Total:</span>
                            <span id="total-price" style="font-size: 28px; font-weight: 700; color: var(--primary-color);">₱0.00</span>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 15px; font-size: 18px;">Payment Method</label>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                            <label class="payment-method-card" data-method="gcash" style="border: 2px solid var(--primary-color); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; background-color: rgba(201, 168, 99, 0.1);">
                                <input type="radio" name="payment_method" value="gcash" checked style="display: none;">
                                <i class="fas fa-mobile-alt" style="font-size: 30px; color: var(--primary-color); margin-bottom: 10px; display: block;"></i>
                                <span style="font-size: 14px; font-weight: 600;">GCash</span>
                            </label>
                            <label class="payment-method-card" data-method="paypal" style="border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                                <input type="radio" name="payment_method" value="paypal" style="display: none;">
                                <i class="fab fa-paypal" style="font-size: 30px; color: #003087; margin-bottom: 10px; display: block;"></i>
                                <span style="font-size: 14px; font-weight: 600;">PayPal</span>
                            </label>
                            <label class="payment-method-card" data-method="credit_card" style="border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                                <input type="radio" name="payment_method" value="credit_card" style="display: none;">
                                <i class="far fa-credit-card" style="font-size: 30px; color: var(--primary-color); margin-bottom: 10px; display: block;"></i>
                                <span style="font-size: 14px; font-weight: 600;">Credit Card</span>
                            </label>
                            <label class="payment-method-card" data-method="pay_at_hotel" style="border: 2px solid #ddd; border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                                <input type="radio" name="payment_method" value="pay_at_hotel" style="display: none;">
                                <i class="fas fa-money-bill-wave" style="font-size: 30px; color: var(--success-color); margin-bottom: 10px; display: block;"></i>
                                <span style="font-size: 14px; font-weight: 600;">Pay at Hotel</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Special Instructions -->
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 15px; font-size: 18px;">Special Instructions (Optional)</label>
                        <textarea name="special_instructions" rows="3" placeholder="Any allergies, dietary requirements, or special requests..."
                                  style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 10px; font-size: 16px; resize: vertical;"></textarea>
                    </div>
                    
                    <!-- Submit Button -->
                    <div style="display: flex; gap: 20px;">
                        <?php if (isLoggedIn()): ?>
                        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 18px 40px; font-size: 18px; font-weight: 600;">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                        <?php else: ?>
                        <button type="button" disabled class="btn btn-primary" style="flex: 1; padding: 18px 40px; font-size: 18px; font-weight: 600; opacity: 0.6; cursor: not-allowed;">
                            <i class="fas fa-check"></i> Place Order
                        </button>
                        <?php endif; ?>
                        <a href="dining.php" class="btn btn-outline" style="padding: 18px 40px; font-size: 18px;">
                            Cancel
                        </a>
                    </div>
                </form>

                <?php if (!isLoggedIn()): ?>
                </div> <!-- End blurred form wrapper -->
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
function selectOrderType(type) {
    // Update radio button
    document.querySelectorAll('input[name="order_type"]').forEach(input => {
        input.checked = (input.value === type);
    });
    
    // Update visual styling
    ['dine_in', 'room_service', 'takeaway'].forEach(t => {
        const el = document.getElementById('type-' + t);
        if (t === type) {
            el.style.borderColor = 'var(--primary-color)';
            el.style.backgroundColor = 'rgba(201, 168, 99, 0.1)';
            el.querySelector('i').style.color = 'var(--primary-color)';
        } else {
            el.style.borderColor = '#ddd';
            el.style.backgroundColor = 'transparent';
            el.querySelector('i').style.color = '#666';
        }
    });
    
    // Toggle room service section
    toggleRoomService(type === 'room_service');
}

function toggleRoomService(show) {
    const roomSection = document.getElementById('room-selection');
    roomSection.style.display = show ? 'block' : 'none';
    
    if (show) {
        const select = document.getElementById('booking_id');
        if (select && select.options.length <= 1) {
            alert('You need an active room booking to use room service.');
            selectOrderType('dine_in');
        }
    }
}

function updateRoomNumber() {
    const bookingSelect = document.getElementById('booking_id');
    const roomInput = document.getElementById('room_number');
    if (bookingSelect && bookingSelect.selectedOptions[0]) {
        roomInput.value = bookingSelect.selectedOptions[0].getAttribute('data-room') || '';
    }
}

function changeQuantity(delta) {
    const input = document.getElementById('quantity');
    const foodSelect = document.getElementById('food_id');
    let newValue = parseInt(input.value) + delta;
    if (newValue < 1) newValue = 1;
    
    // Check stock limit
    if (foodSelect.selectedOptions[0] && foodSelect.value) {
        const stock = parseInt(foodSelect.selectedOptions[0].getAttribute('data-stock')) || 0;
        if (newValue > stock) {
            newValue = stock;
            document.getElementById('stock-warning').textContent = 'Maximum available stock is ' + stock;
            document.getElementById('stock-warning').style.display = 'block';
        } else {
            document.getElementById('stock-warning').style.display = 'none';
        }
    }
    
    input.value = newValue;
    updatePrice();
}

function updatePrice() {
    const foodSelect = document.getElementById('food_id');
    const quantityInput = document.getElementById('quantity');
    const stockWarning = document.getElementById('stock-warning');
    
    if (foodSelect.selectedOptions[0] && foodSelect.value) {
        const price = parseFloat(foodSelect.selectedOptions[0].getAttribute('data-price')) || 0;
        const stock = parseInt(foodSelect.selectedOptions[0].getAttribute('data-stock')) || 0;
        const quantity = parseInt(quantityInput.value) || 1;
        const total = price * quantity;
        
        // Validate quantity against stock
        if (quantity > stock) {
            quantityInput.value = stock;
            stockWarning.textContent = 'Quantity adjusted to available stock: ' + stock;
            stockWarning.style.display = 'block';
        } else {
            stockWarning.style.display = 'none';
        }
        
        document.getElementById('unit-price').textContent = '\u20B1' + price.toFixed(2);
        document.getElementById('quantity-display').textContent = quantityInput.value;
        document.getElementById('total-price').textContent = '\u20B1' + total.toFixed(2);
    } else {
        document.getElementById('unit-price').textContent = '\u20B10.00';
        document.getElementById('quantity-display').textContent = quantityInput.value;
        document.getElementById('total-price').textContent = '\u20B10.00';
    }
}

// Initialize price on page load
updatePrice();

// Update quantity display when input changes
document.getElementById('quantity').addEventListener('change', updatePrice);
document.getElementById('quantity').addEventListener('input', updatePrice);

// Payment method selection
document.querySelectorAll('.payment-method-card').forEach(card => {
    card.addEventListener('click', function() {
        // Update radio button
        const method = this.getAttribute('data-method');
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.checked = (input.value === method);
        });
        
        // Update visual styling
        document.querySelectorAll('.payment-method-card').forEach(c => {
            c.style.borderColor = '#ddd';
            c.style.backgroundColor = 'transparent';
            c.querySelector('i').style.color = '#666';
        });
        
        // Style selected card
        this.style.borderColor = 'var(--primary-color)';
        this.style.backgroundColor = 'rgba(201, 168, 99, 0.1)';
        
        // Restore brand colors for icons
        const icon = this.querySelector('i');
        if (method === 'paypal') {
            icon.style.color = '#003087';
        } else if (method === 'pay_at_hotel') {
            icon.style.color = 'var(--success-color)';
        } else {
            icon.style.color = 'var(--primary-color)';
        }
    });
});

// Handle form submission with payment
let currentOrderData = {};

document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const foodSelect = document.getElementById('food_id');
    if (!foodSelect.value) {
        alert('Please select a food item');
        return;
    }
    
    // Check stock before submitting
    const stock = parseInt(foodSelect.selectedOptions[0].getAttribute('data-stock')) || 0;
    const quantity = parseInt(document.getElementById('quantity').value) || 0;
    if (quantity > stock) {
        alert('Insufficient stock available. Maximum available: ' + stock);
        return;
    }
    
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const totalPrice = document.getElementById('total-price').textContent;
    
    // Store order data
    currentOrderData = {
        food_id: document.getElementById('food_id').value,
        quantity: document.getElementById('quantity').value,
        order_type: document.querySelector('input[name="order_type"]:checked').value,
        booking_id: document.getElementById('booking_id')?.value || '',
        room_number: document.getElementById('room_number')?.value || '',
        special_instructions: document.querySelector('textarea[name="special_instructions"]').value,
        payment_method: paymentMethod,
        total_price: totalPrice
    };
    
    // Show payment modal based on method
    if (paymentMethod === 'pay_at_hotel') {
        processPayAtHotel();
    } else {
        showPaymentModal(paymentMethod, totalPrice);
    }
});

function showPaymentModal(method, amount) {
    document.getElementById('paymentModalOverlay').style.display = 'flex';
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
    
    const amountFormatted = amount.replace('₱', '').replace(',', '');
    
    switch(method) {
        case 'gcash':
            document.getElementById('gcashAmount').value = amount;
            document.getElementById('gcashModal').style.display = 'block';
            break;
        case 'paypal':
            document.getElementById('paypalAmount').value = amount;
            document.getElementById('paypalModal').style.display = 'block';
            break;
        case 'credit_card':
            document.getElementById('ccAmount').value = amount;
            document.getElementById('creditCardModal').style.display = 'block';
            break;
    }
}

function closePaymentModal() {
    document.getElementById('paymentModalOverlay').style.display = 'none';
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
}

document.getElementById('paymentModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});

// GCash Payment
function processGCashPayment() {
    const mobile = document.getElementById('gcashMobile').value;
    const name = document.getElementById('gcashName').value;
    if (!mobile || !name) { alert('Please fill in all required fields'); return; }
    if (!/^09\d{9}$/.test(mobile)) { alert('Please enter a valid mobile number (09XXXXXXXXX)'); return; }
    
    document.getElementById('gcashForm').style.display = 'none';
    document.getElementById('gcashOtpSection').style.display = 'block';
    setTimeout(() => document.querySelector('.otp-input').focus(), 100);
}

function showGCashForm() {
    document.getElementById('gcashForm').style.display = 'block';
    document.getElementById('gcashOtpSection').style.display = 'none';
}

function resendGCashOTP() { alert('OTP resent to your mobile number'); }

function verifyGCashOTP() {
    let otp = '';
    document.querySelectorAll('.otp-input').forEach(input => otp += input.value);
    if (otp.length !== 6) { alert('Please enter the complete 6-digit OTP'); return; }
    
    showProcessing('Verifying GCash payment...');
    submitOrderPayment('gcash', {
        mobile_number: document.getElementById('gcashMobile').value,
        account_name: document.getElementById('gcashName').value,
        otp: otp
    });
}

// PayPal Payment
function processPayPalPayment() {
    const email = document.getElementById('paypalEmail').value;
    const password = document.getElementById('paypalPassword').value;
    if (!email || !password) { alert('Please fill in all required fields'); return; }
    if (!email.includes('@')) { alert('Please enter a valid email address'); return; }
    
    document.getElementById('paypalForm').style.display = 'none';
    document.getElementById('paypalLoadingSection').style.display = 'block';
    
    setTimeout(() => {
        showProcessing('Processing PayPal payment...');
        submitOrderPayment('paypal', { paypal_email: email });
    }, 2000);
}

// Credit Card Payment
function processCreditCardPayment() {
    const cardNumber = document.getElementById('ccNumber').value.replace(/\s/g, '');
    const cardHolder = document.getElementById('ccHolder').value;
    const expiry = document.getElementById('ccExpiry').value;
    const cvv = document.getElementById('ccCVV').value;
    
    if (!cardNumber || !cardHolder || !expiry || !cvv) { alert('Please fill in all required fields'); return; }
    if (!/^\d{16}$/.test(cardNumber.replace(/\s/g, ''))) { alert('Please enter a valid 16-digit card number'); return; }
    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) { alert('Please enter expiry date in MM/YY format'); return; }
    if (!/^\d{3}$/.test(cvv)) { alert('Please enter a valid 3-digit CVV'); return; }
    
    document.getElementById('creditCardForm').style.display = 'none';
    document.getElementById('ccOtpSection').style.display = 'block';
    setTimeout(() => document.querySelector('.cc-otp-input').focus(), 100);
}

function showCreditCardForm() {
    document.getElementById('creditCardForm').style.display = 'block';
    document.getElementById('ccOtpSection').style.display = 'none';
}

function verifyCCOTP() {
    let otp = '';
    document.querySelectorAll('.cc-otp-input').forEach(input => otp += input.value);
    if (otp.length !== 6) { alert('Please enter the complete 6-digit OTP'); return; }
    
    showProcessing('Verifying card payment...');
    submitOrderPayment('credit_card', {
        card_number: document.getElementById('ccNumber').value,
        card_holder: document.getElementById('ccHolder').value,
        expiry_date: document.getElementById('ccExpiry').value
    });
}

// Pay at Hotel
function processPayAtHotel() {
    showProcessing('Placing order...');
    submitOrderPayment('pay_at_hotel', {});
}

function showProcessing(text) {
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
    document.getElementById('processingLoader').style.display = 'block';
    document.getElementById('processingText').textContent = text;
}

function submitOrderPayment(method, paymentData) {
    fetch('food-order-payment-process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            order_data: currentOrderData, 
            payment_method: method, 
            payment_data: paymentData 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showReceipt(data);
        } else {
            const message = data.message || 'Payment processing failed. Please try again.';
            if (isEmailConnectionMessage(message)) {
                showEmailConnectionModal(message);
            } else {
                alert(message);
                closePaymentModal();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your order. Please try again.');
        closePaymentModal();
    });
}

function isEmailConnectionMessage(message) {
    return message.toLowerCase().includes('no internet connection detected');
}

function showEmailConnectionModal(message) {
    closePaymentModal();

    let modal = document.getElementById('emailConnectionModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'emailConnectionModal';
        modal.style.cssText = 'display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px); z-index:1001; justify-content:center; align-items:center; padding:20px;';
        modal.innerHTML = `
            <div style="background:white; border-radius:20px; max-width:440px; width:100%; padding:36px; text-align:center; box-shadow:0 25px 80px rgba(0,0,0,0.35); animation:modalPop 0.35s ease;">
                <div style="width:82px; height:82px; background:linear-gradient(135deg, #dc3545, #b02a37); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 22px; box-shadow:0 10px 28px rgba(220,53,69,0.28);">
                    <i class="fas fa-wifi" style="font-size:34px; color:white;"></i>
                </div>
                <h3 style="font-size:24px; margin:0 0 12px; color:#333; font-weight:700;">Connection Required</h3>
                <p id="emailConnectionMessage" style="font-size:16px; color:#666; margin:0 0 28px; line-height:1.55;"></p>
                <button type="button" onclick="closeEmailConnectionModal()" style="width:100%; padding:15px; background:linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color:white; border:none; border-radius:10px; font-size:16px; cursor:pointer; font-weight:600;">
                    OK
                </button>
            </div>
        `;
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeEmailConnectionModal();
        });
        document.body.appendChild(modal);
    }

    document.getElementById('emailConnectionMessage').textContent = message;
    modal.style.display = 'flex';
}

function closeEmailConnectionModal() {
    const modal = document.getElementById('emailConnectionModal');
    if (modal) modal.style.display = 'none';
}

function showReceipt(data) {
    const receipt = data.receipt || {};
    let statusColor = data.payment_status === 'paid' ? '#28a745' : '#ffc107';
    let statusText = data.payment_status === 'paid' ? 'PAID' : 'PENDING';
    
    const receiptHtml = `
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 80px; height: 80px; background: ${data.payment_status === 'paid' ? '#28a745' : '#ffc107'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-${data.payment_status === 'paid' ? 'check' : 'clock'}" style="font-size: 40px; color: white;"></i>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;">${data.payment_status === 'paid' ? 'Payment Successful!' : 'Order Placed'}</h3>
                <p style="font-size: 14px; color: #666;">Order #${receipt.order_id || 'N/A'}</p>
            </div>
            <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h4 style="font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6;">Order Details</h4>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Food Item:</span><span style="float: right; font-weight: 600;">${receipt.item_name || 'N/A'}</span></div>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Quantity:</span><span style="float: right; font-weight: 600;">${receipt.quantity || 1}</span></div>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Payment Method:</span><span style="float: right; font-weight: 600;">${receipt.payment_method || 'N/A'}</span></div>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Status:</span><span style="float: right; font-weight: 700; color: ${statusColor}; text-transform: uppercase;">${statusText}</span></div>
                <div style="border-top: 2px solid #dee2e6; margin-top: 15px; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 700;">
                        <span>Total:</span>
                        <span style="color: var(--primary-color);">₱${parseFloat(receipt.total_amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
            <button onclick="window.location.href='user/my-food-orders.php'" style="width: 100%; padding: 15px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-arrow-right"></i> View My Orders
            </button>
        </div>
    `;
    
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
    document.getElementById('processingLoader').style.display = 'none';
    document.getElementById('receiptContent').innerHTML = receiptHtml;
    document.getElementById('receiptModal').style.display = 'block';
}
</script>

<!-- Payment Modals -->
<div id="paymentModalOverlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    
    <!-- GCash Modal -->
    <div id="gcashModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; width: 90%; max-width: 450px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="fas fa-mobile-alt" style="font-size: 50px; color: #0047bb;"></i>
                <h3 style="margin-top: 15px;">Pay with GCash</h3>
            </div>
            <div id="gcashForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Amount</label>
                    <input type="text" id="gcashAmount" readonly style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 18px; text-align: center; font-weight: 600;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">GCash Mobile Number *</label>
                    <input type="text" id="gcashMobile" placeholder="09XXXXXXXXX" maxlength="11" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Account Name *</label>
                    <input type="text" id="gcashName" placeholder="Full Name" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                </div>
                <button onclick="processGCashPayment()" style="width: 100%; padding: 15px; background: #0047bb; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
            </div>
            <div id="gcashOtpSection" style="display: none;">
                <p style="text-align: center; margin-bottom: 20px; color: #666;">Enter the 6-digit OTP sent to your mobile</p>
                <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 25px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                </div>
                <div style="display: flex; gap: 15px;">
                    <button onclick="showGCashForm()" style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">Back</button>
                    <button onclick="verifyGCashOTP()" style="flex: 2; padding: 12px; background: #0047bb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Verify OTP</button>
                </div>
                <p style="text-align: center; margin-top: 15px; font-size: 13px;">
                    <a href="#" onclick="resendGCashOTP(); return false;" style="color: #0047bb;">Resend OTP</a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- PayPal Modal -->
    <div id="paypalModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; width: 90%; max-width: 450px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="fab fa-paypal" style="font-size: 50px; color: #003087;"></i>
                <h3 style="margin-top: 15px;">Pay with PayPal</h3>
            </div>
            <div id="paypalForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Amount</label>
                    <input type="text" id="paypalAmount" readonly style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 18px; text-align: center; font-weight: 600;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">PayPal Email *</label>
                    <input type="email" id="paypalEmail" placeholder="your@email.com" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Password *</label>
                    <input type="password" id="paypalPassword" placeholder="PayPal Password" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                </div>
                <button onclick="processPayPalPayment()" style="width: 100%; padding: 15px; background: #003087; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                    <i class="fab fa-paypal"></i> Pay Now
                </button>
            </div>
            <div id="paypalLoadingSection" style="display: none; text-align: center; padding: 40px;">
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #003087; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <p>Connecting to PayPal...</p>
            </div>
        </div>
    </div>
    
    <!-- Credit Card Modal -->
    <div id="creditCardModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; width: 90%; max-width: 450px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <i class="far fa-credit-card" style="font-size: 50px; color: var(--primary-color);"></i>
                <h3 style="margin-top: 15px;">Pay with Credit Card</h3>
            </div>
            <div id="creditCardForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Amount</label>
                    <input type="text" id="ccAmount" readonly style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 18px; text-align: center; font-weight: 600;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Card Number *</label>
                    <input type="text" id="ccNumber" placeholder="0000 0000 0000 0000" maxlength="19" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Card Holder Name *</label>
                    <input type="text" id="ccHolder" placeholder="Name on Card" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Expiry (MM/YY) *</label>
                        <input type="text" id="ccExpiry" placeholder="MM/YY" maxlength="5" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">CVV *</label>
                        <input type="text" id="ccCVV" placeholder="123" maxlength="3" style="width: 100%; padding: 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 16px;">
                    </div>
                </div>
                <button onclick="processCreditCardPayment()" style="width: 100%; padding: 15px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-lock"></i> Pay Securely
                </button>
            </div>
            <div id="ccOtpSection" style="display: none;">
                <p style="text-align: center; margin-bottom: 20px; color: #666;">Enter the 6-digit OTP sent to your registered mobile</p>
                <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 25px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #ddd; border-radius: 8px;">
                </div>
                <div style="display: flex; gap: 15px;">
                    <button onclick="showCreditCardForm()" style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer;">Back</button>
                    <button onclick="verifyCCOTP()" style="flex: 2; padding: 12px; background: var(--primary-color); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Verify OTP</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Processing Loader -->
    <div id="processingLoader" style="display: none; background: white; border-radius: 15px; padding: 50px; text-align: center; max-width: 300px;">
        <div style="border: 4px solid #f3f3f3; border-top: 4px solid var(--primary-color); border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
        <p id="processingText">Processing...</p>
    </div>
    
    <!-- Receipt Modal -->
    <div id="receiptModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; width: 90%; max-width: 450px; max-height: 90vh; overflow-y: auto;">
        <div id="receiptContent"></div>
    </div>
</div>

<style>
@keyframes modalPop {
    0% { transform: scale(0.6); opacity: 0; }
    60% { transform: scale(1.04); }
    100% { transform: scale(1); opacity: 1; }
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Show login toast for order form with 3 second countdown
function showLoginForOrderForm() {
    requireLoginForBooking('food_order', window.location.href);
    return false;
}

// Disable all form inputs when not logged in
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!isLoggedIn()): ?>
    const form = document.getElementById('foodOrderForm');
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea, button');
        inputs.forEach(function(input) {
            input.disabled = true;
            input.style.cursor = 'not-allowed';
        });
    }
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
