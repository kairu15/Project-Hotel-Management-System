<?php
/**
 * Rating Prompt Component - Bayawan Bai Hotel
 * Floating modal for rating services
 * 
 * This component should be included in user pages that need rating prompts.
 * It automatically shows when a user has eligible items to rate.
 */

// Get user ID if logged in
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Check if user has pending ratings
$pendingRatings = [];
if ($userId) {
    $db = getDB();
    
    // Find items that need to be rated (completed but not rated)
    $stmt = $db->prepare("
        SELECT 
            'room' as service_type,
            b.booking_id as item_id,
            rc.category_name as item_name,
            b.checked_out_at as completed_date,
            b.total_amount as amount
        FROM bookings b
        JOIN room_categories rc ON b.category_id = rc.category_id
        WHERE b.user_id = ? 
        AND b.status = 'checked_out'
        AND NOT EXISTS (
            SELECT 1 FROM ratings r 
            WHERE r.booking_id = b.booking_id 
            AND r.user_id = b.user_id
        )
        AND NOT EXISTS (
            SELECT 1 FROM rating_eligibility re 
            WHERE re.booking_id = b.booking_id 
            AND re.user_id = b.user_id 
            AND re.status IN ('skipped', 'shown')
        )
        
        UNION ALL
        
        SELECT 
            'event' as service_type,
            eb.event_booking_id as item_id,
            es.space_name as item_name,
            eb.event_date as completed_date,
            eb.quoted_price as amount
        FROM event_bookings eb
        JOIN event_spaces es ON eb.space_id = es.space_id
        WHERE eb.user_id = ? 
        AND eb.status = 'completed'
        AND NOT EXISTS (
            SELECT 1 FROM ratings r 
            WHERE r.event_booking_id = eb.event_booking_id 
            AND r.user_id = eb.user_id
        )
        AND NOT EXISTS (
            SELECT 1 FROM rating_eligibility re 
            WHERE re.event_booking_id = eb.event_booking_id 
            AND re.user_id = eb.user_id 
            AND re.status IN ('skipped', 'shown')
        )
        
        UNION ALL
        
        SELECT 
            'food' as service_type,
            fo.order_id as item_id,
            mi.item_name as item_name,
            fo.delivered_at as completed_date,
            fo.total_price as amount
        FROM food_orders fo
        JOIN menu_items mi ON fo.food_id = mi.item_id
        WHERE fo.user_id = ? 
        AND fo.status = 'delivered'
        AND NOT EXISTS (
            SELECT 1 FROM ratings r 
            WHERE r.food_order_id = fo.order_id 
            AND r.user_id = fo.user_id
        )
        AND NOT EXISTS (
            SELECT 1 FROM rating_eligibility re 
            WHERE re.food_order_id = fo.order_id 
            AND re.user_id = fo.user_id 
            AND re.status IN ('skipped', 'shown')
        )
        
        ORDER BY completed_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $pendingRatings = $stmt->fetchAll();
    
    // Mark as shown if there are pending ratings
    if (!empty($pendingRatings)) {
        $rating = $pendingRatings[0];
        $eligibilityStmt = $db->prepare("
            INSERT INTO rating_eligibility 
            (user_id, service_type, booking_id, event_booking_id, food_order_id, status, shown_at)
            VALUES (?, ?, ?, ?, ?, 'shown', NOW())
            ON DUPLICATE KEY UPDATE status = 'shown', shown_at = NOW()
        ");
        
        $bookingId = $rating['service_type'] === 'room' ? $rating['item_id'] : null;
        $eventId = $rating['service_type'] === 'event' ? $rating['item_id'] : null;
        $foodId = $rating['service_type'] === 'food' ? $rating['item_id'] : null;
        
        $eligibilityStmt->execute([$userId, $rating['service_type'], $bookingId, $eventId, $foodId]);
    }
}

$hasPendingRating = !empty($pendingRatings);
$currentRating = $hasPendingRating ? $pendingRatings[0] : null;

// Only render if there's a pending rating
if ($hasPendingRating):
    $serviceLabels = [
        'room' => 'Room Booking',
        'event' => 'Event Booking', 
        'food' => 'Food Order'
    ];
    
    $serviceIcons = [
        'room' => 'fa-bed',
        'event' => 'fa-calendar-alt',
        'food' => 'fa-utensils'
    ];
?>

<!-- Rating Prompt Modal -->
<div id="ratingModal" class="rating-modal-overlay">
    <div class="rating-modal">
        <div class="rating-modal-header">
            <h3><i class="fas fa-star"></i> Rate Your Experience</h3>
            <button type="button" class="rating-close-btn" onclick="closeRatingModal()">&times;</button>
        </div>
        
        <div class="rating-modal-body">
            <div class="rating-service-info">
                <div class="rating-service-icon">
                    <i class="fas <?php echo $serviceIcons[$currentRating['service_type']]; ?>"></i>
                </div>
                <div class="rating-service-details">
                    <h4><?php echo htmlspecialchars($currentRating['item_name']); ?></h4>
                    <p><?php echo $serviceLabels[$currentRating['service_type']]; ?></p>
                    <?php if ($currentRating['completed_date']): ?>
                    <small>Completed: <?php echo date('M d, Y', strtotime($currentRating['completed_date'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <p class="rating-prompt-text">How would you rate your experience with our <?php echo strtolower($serviceLabels[$currentRating['service_type']]); ?> service?</p>
            
            <form id="ratingForm" onsubmit="submitRating(event)">
                <input type="hidden" name="service_type" value="<?php echo $currentRating['service_type']; ?>">
                <input type="hidden" name="item_id" value="<?php echo $currentRating['item_id']; ?>">
                
                <div class="star-rating-container">
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">
                            <i class="fas fa-star"></i>
                        </label>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-label" id="ratingLabel">Select a rating</div>
                </div>
                
                <div class="rating-comment-section">
                    <label for="ratingComment">Add a comment (optional)</label>
                    <textarea 
                        id="ratingComment" 
                        name="comment" 
                        rows="3" 
                        placeholder="Tell us about your experience..."
                        maxlength="500"
                    ></textarea>
                    <div class="char-count"><span id="charCount">0</span>/500</div>
                </div>
                
                <div class="rating-actions">
                    <button type="button" class="btn-skip" onclick="skipRating()">
                        Skip for now
                    </button>
                    <button type="submit" class="btn-submit-rating" id="submitRatingBtn" disabled>
                        Submit Rating
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.rating-modal-overlay {
    display: flex;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 10000;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.rating-modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 480px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.4s ease;
    overflow: hidden;
}

@keyframes slideUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.rating-modal-header {
    background: linear-gradient(135deg, #367D8A, #285F6B);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rating-modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.rating-modal-header h3 i {
    margin-right: 8px;
    color: #ffc107;
}

.rating-close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.rating-close-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.rating-modal-body {
    padding: 25px;
}

.rating-service-info {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.rating-service-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.rating-service-icon i {
    font-size: 28px;
    color: #367D8A;
}

.rating-service-details h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    color: #333;
}

.rating-service-details p {
    margin: 0 0 3px 0;
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.rating-service-details small {
    font-size: 12px;
    color: #999;
}

.rating-prompt-text {
    text-align: center;
    color: #555;
    margin-bottom: 25px;
    font-size: 15px;
    line-height: 1.5;
}

.star-rating-container {
    text-align: center;
    margin-bottom: 25px;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    font-size: 36px;
    color: #ddd;
    transition: all 0.2s;
    padding: 5px;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #ffc107;
    transform: scale(1.1);
}

.star-rating label:hover {
    text-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
}

.rating-label {
    font-size: 14px;
    color: #666;
    min-height: 20px;
    transition: color 0.2s;
}

.rating-label.rated {
    color: #367D8A;
    font-weight: 600;
}

.rating-comment-section {
    margin-bottom: 25px;
}

.rating-comment-section label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
}

.rating-comment-section textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.2s;
}

.rating-comment-section textarea:focus {
    outline: none;
    border-color: #367D8A;
}

.char-count {
    text-align: right;
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.rating-actions {
    display: flex;
    gap: 12px;
    justify-content: space-between;
}

.btn-skip {
    flex: 1;
    padding: 14px 24px;
    background: #f5f5f5;
    border: 2px solid #ddd;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-skip:hover {
    background: #e9ecef;
    border-color: #ccc;
}

.btn-submit-rating {
    flex: 2;
    padding: 14px 24px;
    background: linear-gradient(135deg, #367D8A, #285F6B);
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(54, 125, 138, 0.3);
}

.btn-submit-rating:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(54, 125, 138, 0.4);
}

.btn-submit-rating:disabled {
    background: #ccc;
    cursor: not-allowed;
    box-shadow: none;
}

/* Responsive */
@media (max-width: 480px) {
    .rating-modal {
        width: 95%;
        margin: 20px;
    }
    
    .rating-modal-body {
        padding: 20px;
    }
    
    .star-rating label {
        font-size: 30px;
    }
    
    .rating-actions {
        flex-direction: column-reverse;
    }
    
    .btn-skip,
    .btn-submit-rating {
        width: 100%;
    }
}
</style>

<script>
// Rating labels for each star value
const ratingLabels = {
    1: 'Poor - 1 star',
    2: 'Fair - 2 stars',
    3: 'Good - 3 stars',
    4: 'Very Good - 4 stars',
    5: 'Excellent - 5 stars'
};

// Update rating label and enable submit button
const ratingInputs = document.querySelectorAll('.star-rating input');
const ratingLabel = document.getElementById('ratingLabel');
const submitBtn = document.getElementById('submitRatingBtn');

ratingInputs.forEach(input => {
    input.addEventListener('change', function() {
        const value = this.value;
        ratingLabel.textContent = ratingLabels[value];
        ratingLabel.classList.add('rated');
        submitBtn.disabled = false;
    });
});

// Character counter
const commentTextarea = document.getElementById('ratingComment');
const charCount = document.getElementById('charCount');

commentTextarea.addEventListener('input', function() {
    charCount.textContent = this.value.length;
});

// Close modal
function closeRatingModal() {
    const modal = document.getElementById('ratingModal');
    modal.style.opacity = '0';
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Skip rating
function skipRating() {
    const form = document.getElementById('ratingForm');
    const formData = new FormData(form);
    
    fetch('<?php echo SITE_URL; ?>/api/submit-rating.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'skip',
            service_type: formData.get('service_type'),
            item_id: formData.get('item_id')
        }),
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRatingModal();
        }
    })
    .catch(error => console.error('Error:', error));
    
    closeRatingModal();
}

// Submit rating
function submitRating(event) {
    event.preventDefault();
    
    const form = document.getElementById('ratingForm');
    const formData = new FormData(form);
    const submitBtn = document.getElementById('submitRatingBtn');
    
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
            // Show success message
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Thank You!';
            submitBtn.style.background = '#28a745';
            
            setTimeout(() => {
                closeRatingModal();
                // Optionally refresh to show any updates
                // location.reload();
            }, 1500);
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Submit Rating';
            alert(data.message || 'Failed to submit rating. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Submit Rating';
        alert('An error occurred. Please try again.');
    });
}
</script>

<?php endif; // End if hasPendingRating ?>
