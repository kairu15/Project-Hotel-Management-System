<?php
$pageTitle = 'My Reviews';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle form submissions
$message = '';
$error = '';

// Submit new review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    $category = $_POST['category'] ?? 'overall';
    
    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } elseif (empty($reviewText) || strlen($reviewText) < 100) {
        $error = 'Please write a review with at least 100 characters.';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO reviews (user_id, rating, review_text, category, is_approved, is_featured, created_at)
                VALUES (?, ?, ?, ?, FALSE, FALSE, NOW())
            ");
            $stmt->execute([$userId, $rating, $reviewText, $category]);
            
            // Get admin users for notification
            $adminStmt = $db->query("SELECT user_id FROM users WHERE role IN ('admin', 'manager')");
            $admins = $adminStmt->fetchAll();
            
            // Get user name for notification
            $userStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
            $userStmt->execute([$userId]);
            $userData = $userStmt->fetch();
            $userName = $userData['first_name'] . ' ' . $userData['last_name'];
            
            // Create notification for admins
            foreach ($admins as $admin) {
                $notifStmt = $db->prepare("
                    INSERT INTO notifications (user_id, type, title, message, action_url, status, created_at)
                    VALUES (?, 'system', 'New Review Submitted', ?, 'admin/admin-reviews.php', 'unread', NOW())
                ");
                $notifStmt->execute([$admin['user_id'], "$userName has submitted a new review for approval."]);
            }
            
            $message = 'Your review has been submitted successfully! It will be displayed after admin approval.';
        } catch (Exception $e) {
            $error = 'Failed to submit review: ' . $e->getMessage();
            error_log('Review submission error: ' . $e->getMessage());
        }
    }
}

// Edit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_review') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    $category = $_POST['category'] ?? 'overall';
    
    // Verify review belongs to current user
    $verifyStmt = $db->prepare("SELECT * FROM reviews WHERE review_id = ? AND user_id = ?");
    $verifyStmt->execute([$reviewId, $userId]);
    $review = $verifyStmt->fetch();
    
    if (!$review) {
        $error = 'Review not found or unauthorized.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } elseif (empty($reviewText) || strlen($reviewText) < 100) {
        $error = 'Please write a review with at least 100 characters.';
    } else {
        try {
            // Reset approval status when editing
            $stmt = $db->prepare("
                UPDATE reviews 
                SET rating = ?, review_text = ?, category = ?, is_approved = FALSE, updated_at = NOW()
                WHERE review_id = ? AND user_id = ?
            ");
            $stmt->execute([$rating, $reviewText, $category, $reviewId, $userId]);
            
            $message = 'Your review has been updated successfully! It will be re-reviewed by admin.';
        } catch (Exception $e) {
            $error = 'Failed to update review. Please try again.';
        }
    }
}

// Delete review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    
    // Verify review belongs to current user
    $verifyStmt = $db->prepare("SELECT * FROM reviews WHERE review_id = ? AND user_id = ?");
    $verifyStmt->execute([$reviewId, $userId]);
    $review = $verifyStmt->fetch();
    
    if (!$review) {
        $error = 'Review not found or unauthorized.';
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM reviews WHERE review_id = ? AND user_id = ?");
            $stmt->execute([$reviewId, $userId]);
            $message = 'Your review has been deleted successfully.';
        } catch (Exception $e) {
            $error = 'Failed to delete review. Please try again.';
        }
    }
}

// Get user's reviews
$reviewsStmt = $db->prepare("
    SELECT r.*, 
           CASE WHEN r.is_approved THEN 'Approved' ELSE 'Pending Approval' END as status_text
    FROM reviews r
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$reviewsStmt->execute([$userId]);
$userReviews = $reviewsStmt->fetchAll();

// Get review statistics
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        SUM(CASE WHEN is_approved THEN 1 ELSE 0 END) as approved_reviews,
        SUM(CASE WHEN is_featured THEN 1 ELSE 0 END) as featured_reviews,
        AVG(rating) as avg_rating
    FROM reviews 
    WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

$categories = [
    'overall' => 'Overall Experience',
    'room' => 'Room Quality',
    'dining' => 'Dining & Food',
    'service' => 'Customer Service',
    'amenities' => 'Amenities & Facilities'
];
?>

<style>
.review-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}
.review-rating {
    color: #ffc107;
    font-size: 18px;
}
.review-category {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}
.review-category.overall { background: #e3f2fd; color: #1976d2; }
.review-category.room { background: #f3e5f5; color: #7b1fa2; }
.review-category.dining { background: #e8f5e9; color: #388e3c; }
.review-category.service { background: #fff3e0; color: #f57c00; }
.review-category.amenities { background: #fce4ec; color: #c2185b; }
.review-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.review-status.approved {
    background: #d4edda;
    color: #155724;
}
.review-status.pending {
    background: #fff3cd;
    color: #856404;
}
.review-text {
    font-size: 15px;
    line-height: 1.7;
    color: #555;
    margin-bottom: 15px;
    font-style: italic;
}
.review-date {
    font-size: 13px;
    color: #999;
}
.review-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}
.review-form {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
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
.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}
.star-rating-input input {
    display: none;
}
.star-rating-input label {
    cursor: pointer;
    font-size: 32px;
    color: #ddd;
    transition: color 0.2s;
}
.star-rating-input label:hover,
.star-rating-input label:hover ~ label,
.star-rating-input input:checked ~ label {
    color: #ffc107;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-card {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
}
.stat-card h3 {
    font-size: 36px;
    margin: 0 0 5px 0;
}
.stat-card p {
    margin: 0;
    font-size: 14px;
    opacity: 0.9;
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.empty-state i {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 20px;
}
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo __('My Reviews & Testimonials'); ?></h1>
        <p><?php echo __('Share your experience and manage your reviews'); ?></p>
    </div>
</div>

<div style="padding: 40px 0; background-color: var(--gray-light); min-height: 600px;">
    <div class="container">
        <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i><?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $stats['total_reviews'] ?? 0; ?></h3>
                <p><?php echo __('Total Reviews'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['approved_reviews'] ?? 0; ?></h3>
                <p><?php echo __('Approved Reviews'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats['featured_reviews'] ?? 0; ?></h3>
                <p><?php echo __('Featured on Homepage'); ?></p>
            </div>
            <div class="stat-card">
                <h3><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></h3>
                <p><?php echo __('Average Rating'); ?></p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Review Form -->
            <div>
                <div class="review-form">
                    <h3 style="margin-bottom: 25px; font-size: 22px;"><i class="fas fa-pen-fancy" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Write a Review'); ?></h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="submit_review">
                        
                        <div class="form-group">
                            <label><?php echo __('Your Rating'); ?> <span style="color: #dc3545;">*</span></label>
                            <div class="star-rating-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="category"><?php echo __('Category'); ?> <span style="color: #dc3545;">*</span></label>
                            <select name="category" id="category" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                                <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="review_text"><?php echo __('Your Review'); ?> <span style="color: #dc3545;">*</span></label>
                            <textarea name="review_text" id="review_text" rows="5" required minlength="100" maxlength="1000" 
                                placeholder="Share your experience with Bayawan Bai Hotel... What did you like? What could we improve?"
                                style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                            <div style="text-align: right; font-size: 12px; color: #999; margin-top: 5px;">Minimum 100 characters</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 16px;">
                            <i class="fas fa-paper-plane" style="margin-right: 10px;"></i><?php echo __('Submit Review'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- My Reviews List -->
            <div>
                <h3 style="margin-bottom: 20px; font-size: 22px;"><i class="fas fa-list-alt" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('My Reviews'); ?></h3>
                
                <?php if (count($userReviews) > 0): ?>
                    <?php foreach ($userReviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div>
                                <span class="review-category <?php echo $review['category']; ?>">
                                    <?php echo $categories[$review['category']] ?? 'Review'; ?>
                                </span>
                                <span class="review-status <?php echo $review['is_approved'] ? 'approved' : 'pending'; ?>" style="margin-left: 10px;">
                                    <?php echo $review['is_approved'] ? 'Approved' : 'Pending'; ?>
                                    <?php if ($review['is_featured']): ?>
                                    <i class="fas fa-star" style="color: #ffc107; margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <p class="review-text">"<?php echo nl2br(htmlspecialchars($review['review_text'])); ?>"</p>
                        
                        <div class="review-date">
                            <i class="far fa-clock" style="margin-right: 5px;"></i>
                            <?php echo date('F j, Y g:i A', strtotime($review['created_at'])); ?>
                            <?php if (isset($review['updated_at']) && $review['updated_at'] && $review['updated_at'] != $review['created_at']): ?>
                            <span style="color: #999;">(Edited)</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="review-actions">
                            <button type="button" onclick="openEditModal(<?php echo $review['review_id']; ?>, '<?php echo $review['category']; ?>', <?php echo $review['rating']; ?>, '<?php echo htmlspecialchars(addslashes($review['review_text'])); ?>')" 
                                class="btn btn-sm btn-outline" style="padding: 8px 16px;">
                                <i class="fas fa-edit" style="margin-right: 5px;"></i><?php echo __('Edit'); ?>
                            </button>
                            <button type="button" onclick="openDeleteModal(<?php echo $review['review_id']; ?>)" class="btn btn-sm btn-danger" style="padding: 8px 16px;">
                                <i class="fas fa-trash" style="margin-right: 5px;"></i><?php echo __('Delete'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="far fa-comment-dots"></i>
                        <h3><?php echo __('No Reviews Yet'); ?></h3>
                        <p><?php echo __('You haven\'t submitted any reviews. Share your experience with us!'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 5% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 550px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); animation: modalSlideIn 0.3s;">
        <div style="padding: 20px 25px; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 20px; color: #333;"><i class="fas fa-edit" style="color: var(--primary-color); margin-right: 10px;"></i><?php echo __('Edit Review'); ?></h3>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 24px; color: #999; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" id="editForm">
            <input type="hidden" name="action" value="edit_review">
            <input type="hidden" name="review_id" id="editReviewId">
            
            <div style="padding: 25px;">
                <div class="form-group">
                    <label><?php echo __('Your Rating'); ?></label>
                    <div class="star-rating-input" id="editStarRating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="editStar<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>">
                        <label for="editStar<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="editCategory"><?php echo __('Category'); ?></label>
                    <select name="category" id="editCategory" required style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                        <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editReviewText"><?php echo __('Your Review'); ?></label>
                    <textarea name="review_text" id="editReviewText" rows="5" required minlength="100" maxlength="1000" 
                        style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                </div>
            </div>
            
            <div style="padding: 20px 25px; border-top: 1px solid #e0e0e0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-outline"><?php echo __('Cancel'); ?></button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save" style="margin-right: 5px;"></i><?php echo __('Update Review'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div style="background-color: white; margin: 15% auto; padding: 0; border-radius: 12px; width: 90%; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); animation: modalSlideIn 0.3s;">
        <div style="padding: 25px; text-align: center;">
            <div style="width: 60px; height: 60px; background-color: #fee; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: #dc3545;"></i>
            </div>
            <h3 style="margin: 0 0 10px 0; font-size: 20px; color: #333;"><?php echo __('Delete Review'); ?></h3>
            <p style="margin: 0 0 25px 0; color: #666; font-size: 15px;"><?php echo __('Are you sure you want to delete this review? This action cannot be undone.'); ?></p>
            <form method="POST" action="" id="deleteForm">
                <input type="hidden" name="action" value="delete_review">
                <input type="hidden" name="review_id" id="deleteReviewId">
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-outline" style="padding: 10px 20px;"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-danger" style="padding: 10px 20px;">
                        <i class="fas fa-trash" style="margin-right: 5px;"></i><?php echo __('Delete'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(reviewId, category, rating, reviewText) {
    document.getElementById('editReviewId').value = reviewId;
    document.getElementById('editCategory').value = category;
    document.getElementById('editReviewText').value = reviewText;
    
    // Set star rating
    document.getElementById('editStar' + rating).checked = true;
    
    document.getElementById('editModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function openDeleteModal(reviewId) {
    document.getElementById('deleteReviewId').value = reviewId;
    document.getElementById('deleteModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>
