<?php
$pageTitle = 'Request Additional Services';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Get user's active bookings for associating service requests
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

// Get all available additional services grouped by subcategory
$servicesStmt = $db->query("
    SELECT * FROM additional_services 
    WHERE is_available = 1 
    ORDER BY category, sort_order, service_name
");
$services = $servicesStmt->fetchAll();

// Group services by subcategory
$groupedServices = [];
foreach ($services as $service) {
    $key = $service['subcategory'] ?: $service['category'];
    $groupedServices[$key][] = $service;
}

// Category icons
$categoryIcons = [
    'laundry' => 'fa-tshirt',
    'spa' => 'fa-spa',
    'wellness' => 'fa-om',
    'other' => 'fa-concierge-bell'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = intval($_POST['service_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $bookingId = !empty($_POST['booking_id']) ? intval($_POST['booking_id']) : null;
    $roomNumber = $_POST['room_number'] ?? null;
    $specialInstructions = $_POST['special_instructions'] ?? '';
    $preferredDate = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
    $preferredTime = !empty($_POST['preferred_time']) ? $_POST['preferred_time'] : null;

    // Validate
    if (!$serviceId || $quantity < 1) {
        showAlert('Please select a service and valid quantity', 'danger');
    } else {
        // Get service details
        $serviceStmt = $db->prepare("SELECT * FROM additional_services WHERE service_id = ? AND is_available = 1");
        $serviceStmt->execute([$serviceId]);
        $service = $serviceStmt->fetch();

        if (!$service) {
            showAlert('Selected service is not available', 'danger');
        } else {
            $unitPrice = $service['price'];
            $totalPrice = $unitPrice * $quantity;

            try {
                // Generate request reference
                $requestRef = 'SRV' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));

                // Insert service request
                $insertStmt = $db->prepare("
                    INSERT INTO guest_service_requests 
                    (request_ref, user_id, booking_id, room_number, service_id, quantity, unit_price, total_price, 
                     special_instructions, preferred_date, preferred_time, status, payment_status, requested_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
                ");

                $insertStmt->execute([
                    $requestRef,
                    $userId,
                    $bookingId,
                    $roomNumber,
                    $serviceId,
                    $quantity,
                    $unitPrice,
                    $totalPrice,
                    $specialInstructions,
                    $preferredDate,
                    $preferredTime
                ]);

                $requestId = $db->lastInsertId();

                // Send notification to staff
                require_once '../includes/notifications.php';
                $serviceName = $service['service_name'];
                notifyStaffNewServiceRequest($requestId, $serviceName, $roomNumber);

                showAlert('Service request submitted successfully! Reference: ' . $requestRef, 'success');
                redirect('my-service-requests.php');
            } catch (PDOException $e) {
                error_log("Service request error: " . $e->getMessage());
                showAlert('Error submitting request. Please try again.', 'danger');
            }
        }
    }
}
?>

<style>
.service-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s;
    border: 1px solid #e0e0e0;
}
.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.service-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 20px;
    text-align: center;
}
.service-header i {
    font-size: 40px;
    margin-bottom: 10px;
}
.service-body {
    padding: 25px;
}
.service-price {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary-color);
}
.category-section {
    margin-bottom: 50px;
}
.category-title {
    font-size: 28px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 3px solid var(--primary-color);
    display: flex;
    align-items: center;
    gap: 15px;
}
.category-title i {
    color: var(--primary-color);
}
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}
.modal-content {
    background: white;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}
@keyframes slideUp {
    from { transform: translateY(50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.modal-header {
    padding: 25px 30px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-body {
    padding: 30px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}
.quantity-selector {
    display: flex;
    align-items: center;
    gap: 15px;
}
.quantity-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 2px solid var(--primary-color);
    background: white;
    color: var(--primary-color);
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s;
}
.quantity-btn:hover {
    background: var(--primary-color);
    color: white;
}
.quantity-input {
    width: 60px;
    text-align: center;
    font-size: 18px;
    font-weight: 600;
}
</style>

<section>
    <div class="container">
        <?php if (empty($userBookings)): ?>
        <!-- No Active Booking Warning -->
        <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 40px; border-radius: 12px; text-align: center; margin-bottom: 40px;">
            <i class="fas fa-bed" style="font-size: 50px; margin-bottom: 20px;"></i>
            <h3 style="margin: 0 0 15px 0; color: white;">No Active Room Booking</h3>
            <p style="margin: 0 0 25px 0; opacity: 0.9; font-size: 16px;">You need an active room booking to request additional services. Please book a room first.</p>
            <a href="../rooms.php" class="btn btn-light" style="background: white; color: var(--primary-color); font-weight: 600; padding: 12px 30px;">
                <i class="fas fa-search"></i> Browse Rooms
            </a>
        </div>

        <!-- Services shown but disabled -->
        <?php if (!empty($groupedServices)): ?>
        <div style="opacity: 0.6; pointer-events: none; filter: grayscale(0.8);">
            <p style="text-align: center; color: #666; margin-bottom: 20px; font-style: italic;">
                <i class="fas fa-lock"></i> Services are available but require an active booking to request
            </p>
        <?php endif; ?>

        <?php endif; ?>

        <?php if (empty($groupedServices)): ?>
        <div style="text-align: center; padding: 60px;">
            <i class="fas fa-concierge-bell" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="color: #666;">No services available</h3>
            <p style="color: #999;">Please check back later for available services.</p>
        </div>
        <?php else: // Services exist, show them (enabled or disabled based on bookings) ?>

            <?php foreach ($groupedServices as $category => $categoryServices): 
                $firstService = $categoryServices[0];
                $catKey = $firstService['category'];
                $icon = $categoryIcons[$catKey] ?? 'fa-star';
            ?>
            <div class="category-section">
                <h2 class="category-title">
                    <i class="fas <?php echo $icon; ?>"></i>
                    <?php echo htmlspecialchars($category); ?>
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
                    <?php foreach ($categoryServices as $service): ?>
                    <div class="service-card">
                        <div class="service-header">
                            <i class="fas <?php echo $icon; ?>"></i>
                            <h3 style="margin: 0; font-size: 20px;"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        </div>
                        <div class="service-body">
                            <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
                                <?php echo htmlspecialchars($service['description'] ?? 'Premium service for our valued guests.'); ?>
                            </p>
                            
                            <?php if ($service['duration_minutes']): ?>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #666;">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $service['duration_minutes']; ?> minutes</span>
                            </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                                <div class="service-price"><?php echo formatPrice($service['price']); ?></div>
                                <button type="button" onclick="openRequestModal(<?php echo htmlspecialchars(json_encode($service)); ?>)" class="btn btn-primary">
                                    Request Now
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <?php if (empty($userBookings) && !empty($groupedServices)): ?>
        </div><!-- Close disabled overlay div -->
        <?php endif; ?>

    </div>
</section>

<!-- Request Modal -->
<div id="requestModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h3 id="modalServiceName" style="margin: 0;">Request Service</h3>
                <p id="modalCategory" style="margin: 5px 0 0 0; color: #666; font-size: 14px;"></p>
            </div>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666;">&times;</button>
        </div>
        <form method="POST" action="" class="modal-body">
            <input type="hidden" name="service_id" id="serviceId">
            
            <div class="form-group">
                <label>Quantity</label>
                <div class="quantity-selector">
                    <button type="button" class="quantity-btn" onclick="adjustQuantity(-1)">-</button>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="10" class="quantity-input" readonly>
                    <button type="button" class="quantity-btn" onclick="adjustQuantity(1)">+</button>
                </div>
            </div>

            <?php if (!empty($userBookings)): ?>
            <div class="form-group">
                <label>Associate with Booking (Optional)</label>
                <select name="booking_id" id="bookingId" onchange="updateRoomNumber()">
                    <option value="">-- Select Booking --</option>
                    <?php foreach ($userBookings as $booking): ?>
                    <option value="<?php echo $booking['booking_id']; ?>" data-room="<?php echo htmlspecialchars($booking['room_number']); ?>">
                        Room <?php echo htmlspecialchars($booking['room_number']); ?> - 
                        <?php echo htmlspecialchars($booking['room_name']); ?> 
                        (<?php echo formatDate($booking['check_in']); ?> - <?php echo formatDate($booking['check_out']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Room Number *</label>
                <input type="text" name="room_number" id="roomNumber" required placeholder="e.g., 101">
            </div>

            <div id="schedulingFields" style="display: none;">
                <div class="form-group">
                    <label>Preferred Date</label>
                    <input type="date" name="preferred_date" id="preferredDate" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Preferred Time</label>
                    <input type="time" name="preferred_time" id="preferredTime">
                </div>
            </div>

            <div class="form-group">
                <label>Special Instructions</label>
                <textarea name="special_instructions" rows="3" placeholder="Any special requests or instructions..."></textarea>
            </div>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 600;">Total Amount:</span>
                    <span id="totalAmount" class="service-price">₱0.00</span>
                </div>
                <p style="margin: 10px 0 0 0; font-size: 13px; color: #666;">
                    <i class="fas fa-info-circle"></i> This amount will be added to your account/bill.
                </p>
            </div>

            <div style="display: flex; gap: 15px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary" style="flex: 1;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="flex: 1;">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentService = null;
let basePrice = 0;

<?php if (empty($userBookings)): ?>
function openRequestModal(service) {
    // Prevent request - user has no active booking
    alert('You need an active room booking to request services. Please book a room first.');
    return;
}
<?php else: ?>
function openRequestModal(service) {
    currentService = service;
    basePrice = parseFloat(service.price);
    
    document.getElementById('serviceId').value = service.service_id;
    document.getElementById('modalServiceName').textContent = service.service_name;
    document.getElementById('modalCategory').textContent = service.subcategory || service.category;
    
    // Show/hide scheduling fields based on requires_booking
    const schedulingFields = document.getElementById('schedulingFields');
    if (service.requires_booking == 1) {
        schedulingFields.style.display = 'block';
    } else {
        schedulingFields.style.display = 'none';
    }
    
    updateTotal();
    document.getElementById('requestModal').style.display = 'flex';
}
<?php endif; ?>

function closeModal() {
    document.getElementById('requestModal').style.display = 'none';
    document.getElementById('quantity').value = 1;
    document.getElementById('roomNumber').value = '';
    document.getElementById('bookingId').value = '';
}

function adjustQuantity(delta) {
    const input = document.getElementById('quantity');
    let value = parseInt(input.value) + delta;
    if (value < 1) value = 1;
    if (value > 10) value = 10;
    input.value = value;
    updateTotal();
}

function updateTotal() {
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    const total = basePrice * quantity;
    document.getElementById('totalAmount').textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function updateRoomNumber() {
    const bookingSelect = document.getElementById('bookingId');
    const roomInput = document.getElementById('roomNumber');
    
    if (bookingSelect && bookingSelect.value) {
        const option = bookingSelect.options[bookingSelect.selectedIndex];
        const roomNumber = option.getAttribute('data-room');
        if (roomNumber) {
            roomInput.value = roomNumber;
        }
    }
}

// Close modal on outside click
document.getElementById('requestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Update total on quantity change
document.getElementById('quantity').addEventListener('change', updateTotal);
</script>

