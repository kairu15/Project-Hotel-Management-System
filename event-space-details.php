<?php
$pageTitle = 'Event Space Details';
require_once 'includes/config.php';

// Get event space ID from URL
$spaceId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$spaceId) {
    showAlert('Invalid event space', 'danger');
    redirect('events.php');
}

// Get event space details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM event_spaces WHERE space_id = ? AND status = 'available'");
$stmt->execute([$spaceId]);
$space = $stmt->fetch();

if (!$space) {
    showAlert('Event space not found', 'danger');
    redirect('events.php');
}

// Get other event spaces
$otherSpacesStmt = $db->prepare("SELECT * FROM event_spaces WHERE space_id != ? AND status = 'available' ORDER BY capacity LIMIT 3");
$otherSpacesStmt->execute([$spaceId]);
$otherSpaces = $otherSpacesStmt->fetchAll();

// Get ratings statistics for this event space
$ratingsStmt = $db->prepare("
    SELECT 
        r.rating_value,
        COUNT(*) as count
    FROM ratings r
    INNER JOIN event_bookings eb ON r.event_booking_id = eb.event_booking_id
    WHERE eb.space_id = ? AND r.service_type = 'event'
    GROUP BY r.rating_value
");
$ratingsStmt->execute([$spaceId]);
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
        u.last_name,
        eb.event_type
    FROM ratings r
    INNER JOIN event_bookings eb ON r.event_booking_id = eb.event_booking_id
    INNER JOIN users u ON r.user_id = u.user_id
    WHERE eb.space_id = ? AND r.service_type = 'event'
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recentReviewsStmt->execute([$spaceId]);
$recentReviews = $recentReviewsStmt->fetchAll();

require_once 'includes/header.php';

$features = $space['features'] ? explode(', ', $space['features']) : [];

// Get event space images from database
$eventImages = [];
if (!empty($space['images'])) {
    $eventImages = array_filter(explode(',', $space['images']));
}
// Convert stored paths to full asset paths
$eventImages = array_map(function($img) {
    $img = trim($img);
    // If it's already a full URL or starts with assets/, keep it
    if (strpos($img, 'http') === 0 || strpos($img, 'assets/') === 0) {
        return $img;
    }
    // Otherwise prepend assets/
    return 'assets/' . $img;
}, $eventImages);
// Fill with default images if less than 7
$defaultEventImages = [
    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1540575462033-afcf0b7f5a67?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1531058020387-3be67869e66f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1464369063991-193918d63341?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1510074377623-8cf13fb86c08?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1527529482837-4698179dc6ce?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1530103862676-de3c9a59aa38?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
];
while (count($eventImages) < 7) {
    $eventImages[] = $defaultEventImages[count($eventImages) % count($defaultEventImages)];
}
$eventImages = array_slice($eventImages, 0, 7);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo htmlspecialchars($space['space_name']); ?></h1>
        <p><?php echo $space['capacity']; ?> guests capacity • <?php echo $space['area_sqm']; ?> m²</p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li><a href="events.php">Events</a></li>
            <li>/</li>
            <li><?php echo htmlspecialchars($space['space_name']); ?></li>
        </ul>
    </div>
</div>

<!-- Event Space Details Section -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
            <!-- Image Gallery with Lightbox -->
            <div>
                <div style="background-color: white; padding: 20px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); margin-bottom: 20px;">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <!-- Main Large Image -->
                        <div style="border-radius: 10px; overflow: hidden; height: 350px; cursor: pointer; position: relative;" onclick="openLightbox(0)">
                            <img src="<?php echo isset($eventImages[0]) ? htmlspecialchars(trim($eventImages[0])) : ''; ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                            <div style="position: absolute; top: 15px; right: 15px; background-color: var(--primary-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                                Up to <?php echo $space['capacity']; ?> guests
                            </div>
                        </div>
                        <!-- Side Thumbnails (3 images) -->
                        <div style="display: grid; grid-template-rows: repeat(3, 1fr); gap: 10px;">
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                            <?php if (isset($eventImages[$i])): ?>
                            <div style="border-radius: 10px; overflow: hidden; cursor: pointer; position: relative;" onclick="openLightbox(<?php echo $i; ?>)" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <img src="<?php echo htmlspecialchars(trim($eventImages[$i])); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php if ($i === 3 && count($eventImages) > 4): ?>
                                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    +<?php echo count($eventImages) - 4; ?> More
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <!-- Bottom Thumbnail Row (4 images) -->
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                        <?php for ($i = 4; $i < min(8, count($eventImages)); $i++): ?>
                        <div style="border-radius: 8px; overflow: hidden; height: 100px; cursor: pointer;" onclick="openLightbox(<?php echo $i; ?>)">
                            <img src="<?php echo htmlspecialchars(trim($eventImages[$i])); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                        <?php endfor; ?>
                    </div>
                    <p style="text-align: center; color: #666; font-size: 13px; margin-top: 10px;">
                        <i class="fas fa-images" style="color: var(--primary-color); margin-right: 5px;"></i>
                        Click any image to view in fullscreen gallery
                    </p>
                </div>
            </div>
            
            <!-- Details -->
            <div>
                <div style="margin-bottom: 20px;">
                    <span style="background-color: var(--primary-color); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px;">
                        <i class="fas fa-calendar-alt"></i> Event Space
                    </span>
                    <span style="background-color: var(--success-color); color: white; padding: 8px 16px; border-radius: 20px; font-size: 14px; margin-left: 10px;">
                        <i class="fas fa-vector-square"></i> <?php echo $space['area_sqm']; ?> m²
                    </span>
                </div>
                
                <h2 style="font-size: 36px; margin-bottom: 20px;"><?php echo htmlspecialchars($space['space_name']); ?></h2>
                
                <?php if ($space['price_per_day']): ?>
                <div style="font-size: 32px; font-weight: 700; color: var(--primary-color); margin-bottom: 30px;">
                    From <?php echo formatPrice($space['price_per_day']); ?>/day
                </div>
                <?php endif; ?>
                
                <p style="font-size: 18px; line-height: 1.8; color: #666; margin-bottom: 40px;">
                    <?php echo nl2br(htmlspecialchars($space['description'] ?? 'Perfect venue for your special event with stunning views and professional amenities.')); ?>
                </p>
                
                <!-- Features -->
                <?php if (!empty($features)): ?>
                <div style="margin-bottom: 40px;">
                    <h3 style="font-size: 20px; margin-bottom: 20px;">Features & Amenities</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <?php foreach ($features as $feature): ?>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 12px; background-color: var(--gray-light); border-radius: 8px;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span style="font-size: 14px;"><?php echo htmlspecialchars(trim($feature)); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px;">
                    <div style="text-align: center; padding: 20px; background-color: var(--gray-light); border-radius: 10px;">
                        <i class="fas fa-users" style="font-size: 24px; color: var(--primary-color); margin-bottom: 10px;"></i>
                        <div style="font-weight: 600;"><?php echo $space['capacity']; ?></div>
                        <div style="color: #666; font-size: 14px;">Max Capacity</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background-color: var(--gray-light); border-radius: 10px;">
                        <i class="fas fa-vector-square" style="font-size: 24px; color: var(--primary-color); margin-bottom: 10px;"></i>
                        <div style="font-weight: 600;"><?php echo $space['area_sqm']; ?> m²</div>
                        <div style="color: #666; font-size: 14px;">Area</div>
                    </div>
                    <div style="text-align: center; padding: 20px; background-color: var(--gray-light); border-radius: 10px;">
                        <i class="fas fa-star" style="font-size: 24px; color: var(--warning-color); margin-bottom: 10px;"></i>
                        <div style="font-weight: 600;"><?php echo $overallRating > 0 ? number_format($overallRating, 1) : 'N/A'; ?></div>
                        <div style="color: #666; font-size: 14px;">Rating</div>
                    </div>
                </div>
                
                <!-- CTA Buttons -->
                <div style="display: flex; gap: 20px; margin-bottom: 40px;">
                    <a href="events.php#inquiry" class="btn btn-primary" style="flex: 1; padding: 18px 40px; font-size: 18px; font-weight: 600;">
                        <i class="fas fa-paper-plane"></i> Request Quotation
                    </a>
                    <a href="event-virtual-tour.php?space=<?php echo $spaceId; ?>" class="btn btn-outline" style="flex: 1; padding: 18px 40px; font-size: 18px; text-align: center;">
                        <i class="fas fa-vr-cardboard"></i> 360° Virtual Tour
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
                        <h4 style="font-size: 18px; margin-bottom: 20px;">Recent Event Reviews</h4>
                        <?php foreach ($recentReviews as $review): ?>
                        <div style="padding: 20px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600;">
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
                            <?php if ($review['event_type']): ?>
                            <div style="margin-left: 55px; margin-bottom: 8px;">
                                <span style="background-color: var(--gray-light); padding: 4px 10px; border-radius: 12px; font-size: 12px; color: #666;">
                                    <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($review['event_type']); ?>
                                </span>
                            </div>
                            <?php endif; ?>
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

<!-- Other Event Spaces -->
<?php if (!empty($otherSpaces)): ?>
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Explore More</p>
            <h2 style="font-size: 36px;">Other Event Spaces</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <?php foreach ($otherSpaces as $index => $otherSpace): ?>
            <div style="background-color: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-10px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="position: relative; height: 200px; overflow: hidden;">
                    <img src="https://images.unsplash.com/photo-<?php echo ['1540575462033-afcf0b7f5a67','1531058020387-3be67869e66f','1464369063991-193918d63341'][$index % 3] ?>?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" 
                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" 
                         onmouseover="this.style.transform='scale(1.1)'" 
                         onmouseout="this.style.transform='scale(1)'">
                    <div style="position: absolute; top: 15px; right: 15px; background-color: var(--primary-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                        <?php echo $otherSpace['capacity']; ?> guests
                    </div>
                </div>
                <div style="padding: 25px;">
                    <h3 style="font-size: 20px; margin-bottom: 10px;"><?php echo htmlspecialchars($otherSpace['space_name']); ?></h3>
                    <p style="font-size: 14px; color: #666; margin-bottom: 15px; line-height: 1.6;"><?php echo substr(htmlspecialchars($otherSpace['description'] ?? ''), 0, 80) . '...'; ?></p>
                    <?php if ($otherSpace['price_per_day']): ?>
                    <div style="font-size: 18px; font-weight: 700; color: var(--primary-color); margin-bottom: 15px;">
                        From <?php echo formatPrice($otherSpace['price_per_day']); ?>/day
                    </div>
                    <?php endif; ?>
                    <a href="event-space-details.php?id=<?php echo $otherSpace['space_id']; ?>" class="btn btn-outline" style="width: 100%; text-align: center;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Event space images array for lightbox
const eventImages = <?php echo json_encode(array_map(function($img) { return htmlspecialchars(trim($img)); }, $eventImages)); ?>;
let currentImageIndex = 0;

function openLightbox(index) {
    currentImageIndex = index;
    const modal = document.getElementById('imageLightbox');
    const modalImg = document.getElementById('lightboxImage');
    const caption = document.getElementById('lightboxCaption');
    
    modal.style.display = 'flex';
    modalImg.src = eventImages[index];
    caption.innerHTML = '<?php echo htmlspecialchars($space['space_name']); ?> - Image ' + (index + 1) + ' of ' + eventImages.length;
    document.body.style.overflow = 'hidden';
    
    // Update active thumbnail
    document.querySelectorAll('.lightbox-thumb').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

function closeLightbox() {
    const modal = document.getElementById('imageLightbox');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function changeImage(index) {
    currentImageIndex = index;
    const modalImg = document.getElementById('lightboxImage');
    const caption = document.getElementById('lightboxCaption');
    modalImg.src = eventImages[index];
    caption.innerHTML = '<?php echo htmlspecialchars($space['space_name']); ?> - Image ' + (index + 1) + ' of ' + eventImages.length;
    
    // Update active thumbnail
    document.querySelectorAll('.lightbox-thumb').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % eventImages.length;
    changeImage(currentImageIndex);
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + eventImages.length) % eventImages.length;
    changeImage(currentImageIndex);
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageLightbox');
    if (modal.style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
    }
});

// Close on click outside
window.onclick = function(event) {
    const modal = document.getElementById('imageLightbox');
    if (event.target === modal) {
        closeLightbox();
    }
}
</script>

<!-- Lightbox Modal -->
<div id="imageLightbox" class="lightbox-modal">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <span class="lightbox-nav lightbox-prev" onclick="prevImage()">&#10094;</span>
    <span class="lightbox-nav lightbox-next" onclick="nextImage()">&#10095;</span>
    <div class="lightbox-content">
        <img id="lightboxImage" src="" alt="Event Space Image">
        <div id="lightboxCaption" class="lightbox-caption"></div>
    </div>
    <div class="lightbox-thumbnails">
        <?php foreach ($eventImages as $index => $img): ?>
        <img src="<?php echo htmlspecialchars(trim($img)); ?>" onclick="changeImage(<?php echo $index; ?>)" class="lightbox-thumb" data-index="<?php echo $index; ?>">
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Lightbox Modal Styles */
.lightbox-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.95);
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 75%;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 8px;
}

.lightbox-caption {
    color: #fff;
    text-align: center;
    padding: 15px 0;
    font-size: 16px;
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 40px;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
    z-index: 10001;
}

.lightbox-close:hover,
.lightbox-close:focus {
    color: var(--primary-color);
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    color: #fff;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    padding: 20px;
    transform: translateY(-50%);
    transition: 0.3s;
    user-select: none;
    z-index: 10001;
}

.lightbox-nav:hover {
    color: var(--primary-color);
    background-color: rgba(255, 255, 255, 0.1);
}

.lightbox-prev {
    left: 20px;
}

.lightbox-next {
    right: 20px;
}

.lightbox-thumbnails {
    display: flex;
    gap: 10px;
    padding: 20px;
    overflow-x: auto;
    max-width: 90%;
}

.lightbox-thumb {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
    cursor: pointer;
    opacity: 0.6;
    transition: 0.3s;
    border: 2px solid transparent;
}

.lightbox-thumb:hover,
.lightbox-thumb.active {
    opacity: 1;
    border-color: var(--primary-color);
}

@media (max-width: 768px) {
    .lightbox-nav {
        font-size: 30px;
        padding: 10px;
    }
    .lightbox-prev {
        left: 10px;
    }
    .lightbox-next {
        right: 10px;
    }
    .lightbox-close {
        top: 10px;
        right: 20px;
        font-size: 30px;
    }
    .lightbox-thumb {
        width: 60px;
        height: 45px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
