<?php
$pageTitle = 'My Event Bookings';
require_once '../includes/config.php';

// Authentication check must happen before any output
if (!isLoggedIn()) {
    redirect('../auth/login.php');
    exit();
}

$db = getDB();
$userId = getUserId();

// Handle cancel booking - do this before including header to avoid header conflicts
if (isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'] ?? 0;
    if ($bookingId) {
        // Verify booking belongs to user and can be cancelled
        $checkStmt = $db->prepare("SELECT status FROM event_bookings WHERE event_booking_id = ? AND user_id = ?");
        $checkStmt->execute([$bookingId, $userId]);
        $booking = $checkStmt->fetch();

        if ($booking && in_array($booking['status'], ['pending', 'confirmed'])) {
            // Get event name before cancelling
            $nameStmt = $db->prepare("SELECT event_type FROM event_bookings WHERE event_booking_id = ?");
            $nameStmt->execute([$bookingId]);
            $eventName = $nameStmt->fetchColumn() ?? 'Event';
            
            $stmt = $db->prepare("UPDATE event_bookings SET status = 'cancelled' WHERE event_booking_id = ?");
            $stmt->execute([$bookingId]);
            $_SESSION['success'] = 'Event booking "' . $eventName . '" cancelled successfully';
        } else {
            $_SESSION['error'] = 'Cannot cancel this booking';
        }
    }
    redirect('my-event-bookings.php');
    exit(); // Ensure script stops after redirect
}

// Get user's event bookings
$stmt = $db->prepare("
    SELECT eb.*, es.space_name, es.capacity, es.price_per_day
    FROM event_bookings eb
    JOIN event_spaces es ON eb.space_id = es.space_id
    WHERE eb.user_id = ?
    ORDER BY eb.event_date DESC, eb.created_at DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll();

// Define status colors for event bookings
$statusColors = [
    'pending' => ['#fff3cd', '#856404'],
    'confirmed' => ['#d4edda', '#155724'],
    'completed' => ['#cce5ff', '#004085'],
    'cancelled' => ['#f8d7da', '#721c24']
];

// Define payment status colors
$paymentStatusColors = [
    'pending' => ['#fff3cd', '#856404', 'Payment Pending'],
    'paid' => ['#d4edda', '#155724', 'Fully Paid'],
    'partial' => ['#cce5ff', '#004085', 'Partially Paid'],
    'failed' => ['#f8d7da', '#721c24', 'Payment Failed'],
    'refunded' => ['#e2e3e5', '#383d41', 'Refunded']
];

require_once '../includes/user-header.php'; ?>

        <!-- Page Header Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h2 style="margin: 0; color: var(--dark-color);">My Event Bookings</h2>
            </div>
            <div style="display: flex; gap: 15px;">
                <a href="../events.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus" style="margin-right: 5px;"></i> Book New Event
                </a>
            </div>
        </div>

        <!-- Event Bookings -->
        <?php if (count($bookings) > 0): ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($bookings as $booking):
            $color = $statusColors[$booking['status']] ?? ['#e2e3e5', '#383d41'];
            ?>
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="display: flex;">
                    <!-- Left: Date Display -->
                    <div style="width: 100px; background-color: var(--primary-color); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: 700;"><?php echo date('d', strtotime($booking['event_date'])); ?></div>
                        <div style="font-size: 14px; text-transform: uppercase;"><?php echo date('M', strtotime($booking['event_date'])); ?></div>
                        <div style="font-size: 12px; opacity: 0.8;"><?php echo date('Y', strtotime($booking['event_date'])); ?></div>
                    </div>

                    <!-- Right: Details -->
                    <div style="flex: 1; padding: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                            <div>
                                <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($booking['space_name']); ?></h3>
                                <p style="margin: 0; color: #666;"><i class="fas fa-building"></i> Capacity: <?php echo number_format($booking['capacity']); ?> guests</p>
                            </div>
                            <span style="padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                <?php echo $booking['status']; ?>
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px;">
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-clock"></i> Time</div>
                                <div style="font-weight: 500;">
                                    <?php if ($booking['start_time']): ?>
                                        <?php echo date('g:i A', strtotime($booking['start_time'])); ?>
                                        <?php if ($booking['end_time']): ?>
                                            - <?php echo date('g:i A', strtotime($booking['end_time'])); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">To be confirmed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-users"></i> Guests</div>
                                <div style="font-weight: 500;"><?php echo $booking['guests_count'] ? number_format($booking['guests_count']) : 'Not specified'; ?></div>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-utensils"></i> Catering</div>
                                <div style="font-weight: 500;"><?php echo $booking['catering_required'] ? 'Yes' : 'No'; ?></div>
                            </div>
                        </div>

                        <?php if ($booking['event_type']): ?>
                        <div style="margin-bottom: 15px;">
                            <span style="padding: 4px 12px; border-radius: 15px; font-size: 12px; background-color: #e3f2fd; color: #1976d2;">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($booking['event_type']); ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if ($booking['special_requests']): ?>
                        <div style="background-color: var(--gray-light); padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;"><i class="fas fa-comment"></i> Special Requests</div>
                            <div style="font-size: 14px;"><?php echo htmlspecialchars($booking['special_requests']); ?></div>
                        </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid var(--gray-light);">
                            <div>
                                <div style="font-size: 12px; color: #666;">
                                    <i class="fas fa-calendar"></i> Booked on: <?php echo formatDate($booking['created_at'], 'M d, Y'); ?>
                                </div>
                                <?php if ($booking['quoted_price']): ?>
                                <div style="font-size: 14px; color: var(--primary-color); font-weight: 600; margin-top: 5px;">
                                    Quoted Price: <?php echo formatPrice($booking['quoted_price']); ?>
                                    <?php if ($booking['payment_status'] === 'paid'): ?>
                                        <span style="font-size: 12px; color: #28a745; margin-left: 10px;">
                                            <i class="fas fa-check-circle"></i> Paid
                                        </span>
                                    <?php elseif ($booking['payment_status'] === 'partial'): ?>
                                        <span style="font-size: 12px; color: #007bff; margin-left: 10px;">
                                            <i class="fas fa-check-circle"></i> Partial (<?php echo formatPrice($booking['amount_paid']); ?> paid)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                                    <i class="fas fa-info-circle"></i> Price quote pending
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if ($booking['quoted_price'] && in_array($booking['payment_status'], ['pending', 'partial', 'failed']) && in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                <button type="button" class="btn btn-primary" style="padding: 10px 20px;" onclick="openEventPaymentModal(<?php echo $booking['event_booking_id']; ?>, '<?php echo htmlspecialchars($booking['space_name']); ?>', <?php echo $booking['quoted_price']; ?>, <?php echo $booking['amount_paid'] ?? 0; ?>)">
                                    <i class="fas fa-credit-card" style="margin-right: 5px;"></i> Pay Now
                                </button>
                                <?php endif; ?>
                                <?php if (in_array($booking['status'], ['pending']) || ($booking['status'] === 'confirmed' && $booking['payment_status'] !== 'paid')): ?>
                                <form method="POST" action="" style="display: inline;" id="cancelEventForm<?php echo $booking['event_booking_id']; ?>">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['event_booking_id']; ?>">
                                    <button type="button" onclick="openDeleteModal('cancelEventForm<?php echo $booking['event_booking_id']; ?>', 'Cancel Event Booking', 'Are you sure you want to cancel this event booking?', null, 'cancel_booking')" class="btn btn-danger" style="padding: 10px 20px;">Cancel Booking</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($booking['status'] === 'completed' && !isItemRated('event', $booking['event_booking_id'], $userId)): ?>
                                <button type="button" class="btn btn-sm" style="background-color: #ffc107; color: #000; padding: 10px 20px;" onclick="openRateNowModal('event', <?php echo $booking['event_booking_id']; ?>, '<?php echo htmlspecialchars($booking['space_name']); ?>')">
                                    <i class="fas fa-star" style="margin-right: 5px;"></i>Rate Now
                                </button>
                                <?php endif; ?>
                                <?php if ($booking['status'] === 'completed' && isItemRated('event', $booking['event_booking_id'], $userId)): ?>
                                <span class="btn btn-sm" style="background-color: #28a745; color: white; cursor: default; padding: 10px 20px;">
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
        <div style="text-align: center; padding: 80px 20px; background-color: white; border-radius: 10px;">
            <i class="fas fa-calendar-times" style="font-size: 64px; color: var(--gray-light); margin-bottom: 20px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">No Event Bookings Yet</h3>
            <p style="color: #999; margin-bottom: 30px;">You haven't booked any event spaces yet. Start planning your next event!</p>
            <a href="../events.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">Browse Event Spaces</a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php require_once '../includes/rating-prompt.php'; ?>
    
    <?php require_once '../includes/user-footer.php'; ?>

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
    if (event.target.id === 'eventPaymentModal') {
        closeEventPaymentModal();
    }
}

// Event Payment Modal Functions
let currentEventBookingId = null;
let currentEventTotal = 0;
let currentEventPaid = 0;

function openEventPaymentModal(bookingId, spaceName, totalAmount, amountPaid) {
    currentEventBookingId = bookingId;
    currentEventTotal = parseFloat(totalAmount);
    currentEventPaid = parseFloat(amountPaid);
    const remaining = currentEventTotal - currentEventPaid;
    
    let modal = document.getElementById('eventPaymentModal');
    if (!modal) {
        modal = createEventPaymentModal();
    }
    
    document.getElementById('eventSpaceName').textContent = spaceName;
    document.getElementById('eventTotalAmount').textContent = '₱' + currentEventTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('eventPaidAmount').textContent = '₱' + currentEventPaid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('eventRemainingAmount').textContent = '₱' + remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Reset form
    document.getElementById('eventPaymentForm').reset();
    resetPaymentOptions();
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function createEventPaymentModal() {
    const modal = document.createElement('div');
    modal.id = 'eventPaymentModal';
    modal.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.6);z-index:10000;justify-content:center;align-items:center;';
    
    modal.innerHTML = `
        <div style="background:white;border-radius:16px;width:90%;max-width:520px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden;max-height:90vh;overflow-y:auto;">
            <div style="background:linear-gradient(135deg,#367D8A,#285F6B);color:white;padding:20px 25px;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:18px;font-weight:600;"><i class="fas fa-credit-card" style="margin-right:8px;"></i> Pay for Event Booking</h3>
                <button type="button" onclick="closeEventPaymentModal()" style="background:none;border:none;color:white;font-size:28px;cursor:pointer;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;">&times;</button>
            </div>
            <div style="padding:25px;">
                <div style="background-color:#f8f9fa;border-radius:10px;padding:15px;margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:#666;font-size:14px;">Event Space:</span>
                        <span style="font-weight:600;font-size:14px;" id="eventSpaceName"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:#666;font-size:14px;">Total Amount:</span>
                        <span style="font-weight:600;font-size:14px;" id="eventTotalAmount"></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                        <span style="color:#666;font-size:14px;">Already Paid:</span>
                        <span style="font-weight:600;font-size:14px;color:#28a745;" id="eventPaidAmount"></span>
                    </div>
                    <div style="border-top:1px solid #ddd;margin-top:10px;padding-top:10px;display:flex;justify-content:space-between;">
                        <span style="font-weight:600;font-size:15px;">Remaining:</span>
                        <span style="font-weight:700;font-size:18px;color:#dc3545;" id="eventRemainingAmount"></span>
                    </div>
                </div>
                
                <form id="eventPaymentForm" onsubmit="submitEventPayment(event)">
                    <div style="margin-bottom:20px;">
                        <label style="display:block;font-weight:600;margin-bottom:12px;color:#333;">Select Payment Method <span style="color:#dc3545;">*</span></label>
                        
                        <div style="border:2px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:10px;cursor:pointer;transition:all 0.2s;" class="event-payment-option" onclick="selectEventPaymentMethod('gcash')" data-method="gcash">
                            <div style="display:flex;align-items:center;">
                                <input type="radio" name="payment_method" value="gcash" style="margin-right:12px;" required>
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:#333;">GCash</div>
                                    <div style="font-size:12px;color:#666;">Pay using your GCash wallet</div>
                                </div>
                                <i class="fas fa-mobile-alt" style="font-size:28px;color:#007bff;"></i>
                            </div>
                            <div class="gcash-payment-details" style="display:none;margin-top:15px;padding-top:15px;border-top:1px solid #e0e0e0;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">GCash Mobile Number <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="gcash_number" placeholder="09XXXXXXXXX" pattern="09\\d{9}" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Account Name <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="gcash_name" placeholder="Full Name" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                            </div>
                        </div>
                        
                        <div style="border:2px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:10px;cursor:pointer;transition:all 0.2s;" class="event-payment-option" onclick="selectEventPaymentMethod('paypal')" data-method="paypal">
                            <div style="display:flex;align-items:center;">
                                <input type="radio" name="payment_method" value="paypal" style="margin-right:12px;">
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:#333;">PayPal</div>
                                    <div style="font-size:12px;color:#666;">Pay securely via PayPal</div>
                                </div>
                                <i class="fab fa-paypal" style="font-size:28px;color:#003087;"></i>
                            </div>
                            <div class="paypal-payment-details" style="display:none;margin-top:15px;padding-top:15px;border-top:1px solid #e0e0e0;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">PayPal Email <span style="color:#dc3545;">*</span></label>
                                <input type="email" name="paypal_email" placeholder="your@email.com" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Password <span style="color:#dc3545;">*</span></label>
                                <input type="password" name="paypal_password" placeholder="PayPal password" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                            </div>
                        </div>
                        
                        <div style="border:2px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:10px;cursor:pointer;transition:all 0.2s;" class="event-payment-option" onclick="selectEventPaymentMethod('credit_card')" data-method="credit_card">
                            <div style="display:flex;align-items:center;">
                                <input type="radio" name="payment_method" value="credit_card" style="margin-right:12px;">
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:#333;">Credit Card</div>
                                    <div style="font-size:12px;color:#666;">Visa, Mastercard, Amex</div>
                                </div>
                                <i class="fas fa-credit-card" style="font-size:28px;color:#333;"></i>
                            </div>
                            <div class="credit-card-payment-details" style="display:none;margin-top:15px;padding-top:15px;border-top:1px solid #e0e0e0;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Card Number <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
                                    <div>
                                        <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Expiry Date <span style="color:#dc3545;">*</span></label>
                                        <input type="text" name="expiry_date" placeholder="MM/YY" maxlength="5" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                                    </div>
                                    <div>
                                        <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">CVV <span style="color:#dc3545;">*</span></label>
                                        <input type="text" name="cvv" placeholder="123" maxlength="3" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                                    </div>
                                </div>
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Cardholder Name <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="card_holder" placeholder="Name on card" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                            </div>
                        </div>
                        
                        <div style="border:2px solid #e0e0e0;border-radius:8px;padding:15px;margin-bottom:10px;cursor:pointer;transition:all 0.2s;" class="event-payment-option" onclick="selectEventPaymentMethod('pay_at_hotel')" data-method="pay_at_hotel">
                            <div style="display:flex;align-items:center;">
                                <input type="radio" name="payment_method" value="pay_at_hotel" style="margin-right:12px;">
                                <div style="flex:1;">
                                    <div style="font-weight:600;color:#333;">Pay at Hotel</div>
                                    <div style="font-size:12px;color:#666;">Pay upon arrival</div>
                                </div>
                                <i class="fas fa-hotel" style="font-size:28px;color:#28a745;"></i>
                            </div>
                            <div class="pay-at-hotel-details" style="display:none;margin-top:15px;padding-top:15px;border-top:1px solid #e0e0e0;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Full Name <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="full_name" placeholder="Your full name" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Mobile Number <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="mobile_number" placeholder="09XXXXXXXXX" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Email <span style="color:#dc3545;">*</span></label>
                                <input type="email" name="email" placeholder="your@email.com" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Expected Arrival Time</label>
                                <input type="time" name="arrival_time" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;margin-bottom:10px;">
                                <label style="display:block;font-size:13px;margin-bottom:5px;font-weight:500;">Special Notes</label>
                                <textarea name="special_notes" rows="2" placeholder="Any special requests..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;font-size:14px;"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:12px;justify-content:space-between;">
                        <button type="button" onclick="closeEventPaymentModal()" style="flex:1;padding:14px 24px;background:#f5f5f5;border:2px solid #ddd;border-radius:10px;font-size:14px;font-weight:500;color:#666;cursor:pointer;">Cancel</button>
                        <button type="submit" id="eventPaymentSubmitBtn" style="flex:2;padding:14px 24px;background:linear-gradient(135deg,#367D8A,#285F6B);border:none;border-radius:10px;font-size:14px;font-weight:600;color:white;cursor:pointer;box-shadow:0 4px 15px rgba(54,125,138,0.3);">
                            <i class="fas fa-lock" style="margin-right:5px;"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <style>
            .event-payment-option:hover { border-color: var(--primary-color) !important; }
            .event-payment-option.selected { border-color: var(--primary-color) !important; background-color: #f8f9ff; }
        </style>
    `;
    
    document.body.appendChild(modal);
    return modal;
}

function selectEventPaymentMethod(method) {
    // Remove selected class from all options
    document.querySelectorAll('.event-payment-option').forEach(option => {
        option.classList.remove('selected');
        option.querySelector('.gcash-payment-details, .paypal-payment-details, .credit-card-payment-details, .pay-at-hotel-details').style.display = 'none';
    });
    
    // Add selected class to clicked option
    const selectedOption = document.querySelector(`.event-payment-option[data-method="${method}"]`);
    if (selectedOption) {
        selectedOption.classList.add('selected');
        selectedOption.querySelector('input[type="radio"]').checked = true;
        
        // Show payment details
        const detailsClass = method + '-payment-details';
        const details = selectedOption.querySelector('.' + detailsClass + ', .' + method.replace('_', '-') + '-details');
        if (details) {
            details.style.display = 'block';
        }
    }
}

function resetPaymentOptions() {
    document.querySelectorAll('.event-payment-option').forEach(option => {
        option.classList.remove('selected');
        option.querySelector('input[type="radio"]').checked = false;
    });
    document.querySelectorAll('.gcash-payment-details, .paypal-payment-details, .credit-card-payment-details, .pay-at-hotel-details').forEach(details => {
        details.style.display = 'none';
    });
}

function closeEventPaymentModal() {
    const modal = document.getElementById('eventPaymentModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

function submitEventPayment(event) {
    event.preventDefault();
    
    const form = document.getElementById('eventPaymentForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('eventPaymentSubmitBtn');
    
    const paymentMethod = formData.get('payment_method');
    if (!paymentMethod) {
        alert('Please select a payment method');
        return;
    }
    
    // Prepare payment data based on method
    let paymentData = {};
    switch (paymentMethod) {
        case 'gcash':
            paymentData = {
                mobile_number: formData.get('gcash_number'),
                account_name: formData.get('gcash_name')
            };
            if (!paymentData.mobile_number || !paymentData.account_name) {
                alert('Please fill in all GCash details');
                return;
            }
            break;
        case 'paypal':
            paymentData = {
                paypal_email: formData.get('paypal_email'),
                paypal_password: formData.get('paypal_password')
            };
            if (!paymentData.paypal_email || !paymentData.paypal_password) {
                alert('Please fill in all PayPal details');
                return;
            }
            break;
        case 'credit_card':
            paymentData = {
                card_number: formData.get('card_number'),
                card_holder: formData.get('card_holder'),
                expiry_date: formData.get('expiry_date'),
                cvv: formData.get('cvv')
            };
            if (!paymentData.card_number || !paymentData.card_holder || !paymentData.expiry_date || !paymentData.cvv) {
                alert('Please fill in all credit card details');
                return;
            }
            break;
        case 'pay_at_hotel':
            paymentData = {
                full_name: formData.get('full_name'),
                mobile_number: formData.get('mobile_number'),
                email: formData.get('email'),
                arrival_time: formData.get('arrival_time'),
                special_notes: formData.get('special_notes')
            };
            if (!paymentData.full_name || !paymentData.mobile_number || !paymentData.email) {
                alert('Please fill in all required details');
                return;
            }
            break;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:5px;"></i> Processing...';
    
    const data = {
        payment_method: paymentMethod,
        event_booking_id: currentEventBookingId,
        payment_data: paymentData
    };
    
    fetch('<?php echo SITE_URL; ?>/api/event-payment-process.php', {
        method: 'POST',
        body: JSON.stringify(data),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            submitBtn.innerHTML = '<i class="fas fa-check" style="margin-right:5px;"></i> Payment Successful!';
            submitBtn.style.background = '#28a745';
            setTimeout(() => {
                closeEventPaymentModal();
                location.reload();
            }, 1500);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-lock" style="margin-right:5px;"></i> Confirm Payment';
            alert(data.message || 'Payment failed. Please try again.');
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock" style="margin-right:5px;"></i> Confirm Payment';
        alert('An error occurred during payment. Please try again.');
    });
}
</script>

</body>
</html>
