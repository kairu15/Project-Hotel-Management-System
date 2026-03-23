<?php
$pageTitle = 'Manage Reviews - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle approve/reject review
if (isset($_POST['update_review'])) {
    $reviewId = $_POST['review_id'] ?? 0;
    $isApproved = isset($_POST['is_approved']) ? 1 : 0;

    if ($reviewId) {
        $stmt = $db->prepare("UPDATE reviews SET is_approved = ? WHERE review_id = ?");
        $stmt->execute([$isApproved, $reviewId]);
        $_SESSION['success'] = 'Review updated successfully';
    }
    redirect('admin-reviews.php');
}

// Handle delete review
if (isset($_POST['delete_review'])) {
    $reviewId = $_POST['review_id'] ?? 0;
    if ($reviewId) {
        $stmt = $db->prepare("DELETE FROM reviews WHERE review_id = ?");
        if ($stmt->execute([$reviewId])) {
            $_SESSION['success'] = 'Review deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete review';
        }
    }
    redirect('admin-reviews.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$ratingFilter = $_GET['rating'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT r.*, u.first_name, u.last_name, b.booking_id
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    LEFT JOIN bookings b ON r.booking_id = b.booking_id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND r.is_approved = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter) {
    $sql .= " AND r.category = ?";
    $params[] = $categoryFilter;
}

if ($ratingFilter) {
    $sql .= " AND r.rating = ?";
    $params[] = $ratingFilter;
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR r.review_text LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get stats
$totalReviews = count($reviews);
$approvedCount = count(array_filter($reviews, function($r) { return $r['is_approved']; }));
$pendingCount = $totalReviews - $approvedCount;
$avgRating = $totalReviews > 0 ? round(array_sum(array_column($reviews, 'rating')) / $totalReviews, 1) : 0;

// Get rating distribution
$ratingDistribution = $db->query("
    SELECT rating, COUNT(*) as count FROM reviews GROUP BY rating ORDER BY rating DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$categories = ['room', 'dining', 'service', 'amenities', 'overall'];

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $totalReviews; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">Total Reviews</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $avgRating; ?> <i class="fas fa-star" style="color: var(--warning-color); font-size: 20px;"></i></h3>
                    <p style="color: #666; margin: 5px 0 0;">Average Rating</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--info-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $approvedCount; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">Approved</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $pendingCount; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">Pending</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $ratingDistribution[5] ?? 0; ?> <i class="fas fa-star" style="color: var(--warning-color); font-size: 16px;"></i></h3>
                    <p style="color: #666; margin: 5px 0 0;">5-Star Reviews</p>
                </div>
            </div>
        </div>

        <!-- Rating Distribution -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <h4 style="margin-bottom: 15px;">Rating Distribution</h4>
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
                <?php for ($i = 5; $i >= 1; $i--):
                $count = $ratingDistribution[$i] ?? 0;
                $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                ?>
                <div style="text-align: center;">
                    <div style="font-weight: 600; margin-bottom: 5px;"><?php echo $i; ?> <i class="fas fa-star" style="color: var(--warning-color);"></i></div>
                    <div style="height: 8px; background-color: var(--gray-light); border-radius: 4px; overflow: hidden; margin-bottom: 5px;">
                        <div style="height: 100%; width: <?php echo $percentage; ?>%; background-color: var(--warning-color);"></div>
                    </div>
                    <div style="font-size: 12px; color: #666;"><?php echo $count; ?> (<?php echo $percentage; ?>%)</div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search reviews..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Approved</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Rating</label>
                    <select name="rating" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="5" <?php echo $ratingFilter === '5' ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $ratingFilter === '4' ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $ratingFilter === '3' ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $ratingFilter === '2' ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $ratingFilter === '1' ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-reviews.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Reviews Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Reviews (<?php echo count($reviews); ?>)</h3>
            </div>

            <?php if (count($reviews) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Customer</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Rating</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Review</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Date</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></div>
                                <?php if ($review['booking_id']): ?>
                                <div style="font-size: 12px; color: #666;">Booking #<?php echo $review['booking_id']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="color: var(--warning-color);">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div style="font-size: 12px; color: #666;"><?php echo $review['rating']; ?>/5</div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2; text-transform: capitalize;">
                                    <?php echo $review['category']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="max-width: 250px; font-size: 13px;"><?php echo htmlspecialchars($review['review_text'] ?: 'No comment'); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo formatDate($review['created_at'], 'M d, Y'); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $review['is_approved'] ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $review['is_approved'] ? '#155724' : '#856404'; ?>;">
                                    <?php echo $review['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                        <input type="checkbox" name="is_approved" value="1" <?php echo $review['is_approved'] ? 'checked' : ''; ?> style="display: none;">
                                        <button type="submit" name="update_review" class="btn btn-sm <?php echo $review['is_approved'] ? 'btn-secondary' : 'btn-success'; ?>" style="padding: 5px 12px; font-size: 12px;">
                                            <?php echo $review['is_approved'] ? 'Unapprove' : 'Approve'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                        <button type="submit" name="delete_review" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-star" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No reviews found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
