<?php
$pageTitle = 'Food Details';
require_once 'includes/config.php';

// Get food item ID
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$itemId) {
    showAlert('Invalid food item', 'danger');
    redirect('dining.php');
}

// Get food item details
$db = getDB();
$stmt = $db->prepare("
    SELECT mi.*, mc.category_name 
    FROM menu_items mi 
    JOIN menu_categories mc ON mi.cat_id = mc.cat_id 
    WHERE mi.item_id = ? AND mi.is_available = 1
");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    showAlert('Food item not found', 'danger');
    redirect('dining.php');
}

// Get food image
$itemImage = $item['image'] ?? '';
if ($itemImage) {
    // Add assets/ prefix if not already there
    if (strpos($itemImage, 'http') !== 0 && strpos($itemImage, 'assets/') !== 0) {
        $itemImage = 'assets/' . $itemImage;
    }
} else {
    // Default placeholder images
    $defaultImages = ['1504674900247-0877df9cc836','1540189549336-e6e99c3679fe','1565299624946-b28f40a0ae38','1567620905732-2d1ec7ab7445'];
    $itemImage = 'https://images.unsplash.com/photo-' . $defaultImages[$item['item_id'] % 4] . '?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
}

// Get related items from same category
$relatedStmt = $db->prepare("
    SELECT mi.*, mc.category_name 
    FROM menu_items mi 
    JOIN menu_categories mc ON mi.cat_id = mc.cat_id 
    WHERE mi.cat_id = ? AND mi.item_id != ? AND mi.is_available = 1 
    ORDER BY RAND() LIMIT 3
");
$relatedStmt->execute([$item['cat_id'], $itemId]);
$relatedItems = $relatedStmt->fetchAll();

// Get ratings statistics for this food item
$ratingsStmt = $db->prepare("
    SELECT 
        r.rating_value,
        COUNT(*) as count
    FROM ratings r
    INNER JOIN food_orders fo ON r.food_order_id = fo.order_id
    WHERE fo.food_id = ? AND r.service_type = 'food'
    GROUP BY r.rating_value
");
$ratingsStmt->execute([$itemId]);
$ratingDistribution = $ratingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Calculate total reviews and average rating
$totalReviews = array_sum($ratingDistribution);
$overallRating = 0;
if ($totalReviews > 0) {
    $sumRatings = 0;
    foreach ($ratingDistribution as $rating => $count) {
        $sumRatings += $rating * $count;
    }
    $overallRating = round($sumRatings / $totalReviews, 1);
}

// Calculate percentages for each star rating
$starPercentages = [];
for ($i = 5; $i >= 1; $i--) {
    $count = $ratingDistribution[$i] ?? 0;
    $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
    $starPercentages[$i] = ['count' => $count, 'percentage' => $percentage];
}

// Get recent reviews with user info
$recentReviewsStmt = $db->prepare("
    SELECT 
        r.rating_value,
        r.comment,
        r.created_at,
        u.first_name,
        u.last_name
    FROM ratings r
    INNER JOIN food_orders fo ON r.food_order_id = fo.order_id
    INNER JOIN users u ON r.user_id = u.user_id
    WHERE fo.food_id = ? AND r.service_type = 'food'
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recentReviewsStmt->execute([$itemId]);
$recentReviews = $recentReviewsStmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($item['item_name']); ?></h1>
        <p><?php echo htmlspecialchars($item['category_name']); ?></p>
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
            <li><?php echo htmlspecialchars($item['item_name']); ?></li>
        </ul>
    </div>
</div>

<!-- Food Details Section -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
            <!-- Image -->
            <div style="position: relative; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                <img src="<?php echo htmlspecialchars($itemImage); ?>" 
                     style="width: 100%; height: 500px; object-fit: cover;" 
                     alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                <?php if ($item['is_special']): ?>
                <div style="position: absolute; top: 20px; left: 20px; background-color: var(--warning-color); color: var(--dark-color); padding: 10px 20px; border-radius: 30px; font-weight: 600;">
                    <i class="fas fa-star"></i> Chef's Special
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Details -->
            <div>
                <div style="margin-bottom: 20px;">
                    <span style="background-color: var(--primary-color); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                        <?php echo htmlspecialchars($item['category_name']); ?>
                    </span>
                    <?php if ($item['dietary_info']): ?>
                    <span style="background-color: var(--success-color); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; margin-left: 10px;">
                        <i class="fas fa-leaf"></i> <?php echo htmlspecialchars($item['dietary_info']); ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <h2 style="font-size: 36px; margin-bottom: 20px;"><?php echo htmlspecialchars($item['item_name']); ?></h2>
                
                <div style="font-size: 32px; font-weight: 700; color: var(--primary-color); margin-bottom: 30px;">
                    <?php echo formatPrice($item['price']); ?>
                </div>
                
                <p style="font-size: 18px; line-height: 1.8; color: #666; margin-bottom: 40px;">
                    <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                </p>
                
                <!-- Features -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 40px;">
                    <div style="display: flex; align-items: center; gap: 15px; padding: 20px; background-color: var(--gray-light); border-radius: 10px;">
                        <i class="fas fa-clock" style="font-size: 24px; color: var(--primary-color);"></i>
                        <div>
                            <div style="font-weight: 600;">Preparation Time</div>
                            <div style="color: #666; font-size: 14px;">20-30 minutes</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px; padding: 20px; background-color: var(--gray-light); border-radius: 10px;">
                        <i class="fas fa-utensils" style="font-size: 24px; color: var(--primary-color);"></i>
                        <div>
                            <div style="font-weight: 600;">Serving Type</div>
                            <div style="color: #666; font-size: 14px;">Dine-in / Room Service</div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Buttons -->
                <div style="display: flex; gap: 20px; margin-bottom: 40px;">
                    <?php if (isLoggedIn()): ?>
                        <a href="order-now.php?item_id=<?php echo $item['item_id']; ?>" class="btn btn-primary" style="flex: 1; padding: 18px 40px; font-size: 18px; font-weight: 600;">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="return requireLoginForBooking('food_order', 'order-now.php?item_id=<?php echo $item['item_id']; ?>');" class="btn btn-primary" style="flex: 1; padding: 18px 40px; font-size: 18px; font-weight: 600;">
                            <i class="fas fa-shopping-cart"></i> Order Now
                        </a>
                    <?php endif; ?>
                    <a href="dining.php" class="btn btn-outline" style="padding: 18px 40px; font-size: 18px;">
                        <i class="fas fa-arrow-left"></i> Back to Menu
                    </a>
                </div>
                
                <!-- Reviews Section -->
                <div style="background-color: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h3 style="font-size: 24px; margin-bottom: 25px;"><i class="fas fa-star" style="color: var(--warning-color); margin-right: 10px;"></i>Guest Reviews</h3>
                    <div style="display: flex; align-items: center; gap: 30px; margin-bottom: 30px; padding: 25px; background-color: var(--gray-light); border-radius: 10px;">
                        <div style="text-align: center;">
                            <div style="font-size: 48px; font-weight: 700; color: var(--primary-color);"><?php echo $overallRating > 0 ? number_format($overallRating, 1) : '0.0'; ?></div>
                            <div style="color: var(--warning-color); font-size: 18px;">
                                <?php
                                $fullStars = floor($overallRating);
                                $halfStar = ($overallRating - $fullStars) >= 0.5;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $fullStars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $fullStars + 1 && $halfStar) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div style="font-size: 14px; color: #666; margin-top: 5px;">Based on <?php echo $totalReviews; ?> review<?php echo $totalReviews !== 1 ? 's' : ''; ?></div>
                        </div>
                        <div style="flex: 1;">
                            <?php for ($i = 5; $i >= 1; $i--): 
                                $starData = $starPercentages[$i];
                                $barColor = $i >= 4 ? 'var(--success-color)' : ($i == 3 ? 'var(--warning-color)' : 'var(--danger-color)');
                            ?>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <span style="font-size: 14px; width: 60px;"><?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?></span>
                                <div style="flex: 1; height: 8px; background-color: #ddd; border-radius: 4px; overflow: hidden;">
                                    <div style="width: <?php echo $starData['percentage']; ?>%; height: 100%; background-color: <?php echo $barColor; ?>"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;"><?php echo $starData['percentage']; ?>%</span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Reviews -->
                    <?php if (!empty($recentReviews)): ?>
                    <div style="margin-top: 30px;">
                        <h4 style="font-size: 18px; margin-bottom: 20px;">Recent Reviews</h4>
                        <?php foreach ($recentReviews as $review): ?>
                        <div style="padding: 20px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                <div style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; flex-shrink: 0; aspect-ratio: 1;">
                                    <?php echo strtoupper(substr($review['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($review['first_name'] . ' ' . substr($review['last_name'], 0, 1) . '.'); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div style="margin-left: auto; color: var(--warning-color);">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo $i <= $review['rating_value'] ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if ($review['comment']): ?>
                            <p style="color: #666; line-height: 1.6; margin-left: 55px;"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Items Section -->
<?php if (!empty($relatedItems)): ?>
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">You May Also Like</p>
            <h2 style="font-size: 36px;">Related Dishes</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <?php foreach ($relatedItems as $related): 
                // Get related item image
                $relatedImage = $related['image'] ?? '';
                if ($relatedImage) {
                    if (strpos($relatedImage, 'http') !== 0 && strpos($relatedImage, 'assets/') !== 0) {
                        $relatedImage = 'assets/' . $relatedImage;
                    }
                } else {
                    $defaultImages = ['1504674900247-0877df9cc836','1540189549336-e6e99c3679fe','1565299624946-b28f40a0ae38','1567620905732-2d1ec7ab7445'];
                    $relatedImage = 'https://images.unsplash.com/photo-' . $defaultImages[$related['item_id'] % 4] . '?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
                }
            ?>
            <div style="background-color: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="height: 200px; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($relatedImage); ?>" 
                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" 
                         onmouseover="this.style.transform='scale(1.1)'" 
                         onmouseout="this.style.transform='scale(1)'">
                </div>
                <div style="padding: 25px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="font-size: 18px;"><?php echo htmlspecialchars($related['item_name']); ?></h4>
                        <span style="font-size: 18px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($related['price']); ?></span>
                    </div>
                    <p style="font-size: 14px; color: #666; margin-bottom: 20px; line-height: 1.6;"><?php echo substr(htmlspecialchars($related['description']), 0, 80) . '...'; ?></p>
                    <a href="foods-details.php?id=<?php echo $related['item_id']; ?>" class="btn btn-outline" style="width: 100%; text-align: center;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
