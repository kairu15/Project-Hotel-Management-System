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
            $stmt = $db->prepare("UPDATE event_bookings SET status = 'cancelled' WHERE event_booking_id = ?");
            $stmt->execute([$bookingId]);
            $_SESSION['success'] = 'Event booking cancelled successfully';
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
                                </div>
                                <?php else: ?>
                                <div style="font-size: 13px; color: #666; margin-top: 5px;">
                                    <i class="fas fa-info-circle"></i> Price quote pending
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel this event booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['event_booking_id']; ?>">
                                    <button type="submit" name="cancel_booking" class="btn btn-danger" style="padding: 10px 20px;">Cancel Booking</button>
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
}
</script>

</body>
</html>
