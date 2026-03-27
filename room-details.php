<?php
$pageTitle = 'Room Details';
require_once 'includes/config.php';

// Get room category ID from URL
$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$categoryId) {
    redirect('rooms.php');
}

// Get room details
$db = getDB();
$stmt = $db->prepare("SELECT * FROM room_categories WHERE category_id = ? AND status = 'active'");
$stmt->execute([$categoryId]);
$room = $stmt->fetch();

if (!$room) {
    showAlert('Room not found', 'danger');
    redirect('rooms.php');
}

require_once 'includes/header.php';

// Get available rooms count
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$availableRooms = checkAvailability($today, $tomorrow, $categoryId);

// Get similar rooms
$similarStmt = $db->prepare("SELECT * FROM room_categories WHERE category_id != ? AND status = 'active' ORDER BY base_price LIMIT 3");
$similarStmt->execute([$categoryId]);
$similarRooms = $similarStmt->fetchAll();

$amenities = explode(', ', $room['amenities']);

// Get ratings statistics for this room category
$ratingsStmt = $db->prepare("
    SELECT 
        r.rating_value,
        COUNT(*) as count
    FROM ratings r
    INNER JOIN bookings b ON r.booking_id = b.booking_id
    WHERE b.category_id = ? AND r.service_type = 'room'
    GROUP BY r.rating_value
");
$ratingsStmt->execute([$categoryId]);
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
    INNER JOIN bookings b ON r.booking_id = b.booking_id
    INNER JOIN users u ON r.user_id = u.user_id
    WHERE b.category_id = ? AND r.service_type = 'room'
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recentReviewsStmt->execute([$categoryId]);
$recentReviews = $recentReviewsStmt->fetchAll();

// Get room images from database
$roomImages = [];
if (!empty($room['images_gallery'])) {
    $roomImages = array_filter(explode(',', $room['images_gallery']));
}
// Ensure we have at least 7 images (use primary image and fill with defaults if needed)
$primaryImage = $room['image_primary'] ?? '';
if ($primaryImage && !in_array($primaryImage, $roomImages)) {
    array_unshift($roomImages, $primaryImage);
}
// Convert stored paths to full asset paths
$roomImages = array_map(function($img) {
    $img = trim($img);
    // If it's already a full URL or starts with assets/, keep it
    if (strpos($img, 'http') === 0 || strpos($img, 'assets/') === 0) {
        return $img;
    }
    // Otherwise prepend assets/
    return 'assets/' . $img;
}, $roomImages);
// Fill with default images if less than 7
$defaultImages = [
    'https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1566666208517-13f42e1e3c2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1582719478250-c89cae141e86?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1590490360182-c33d57733427?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
    'https://images.unsplash.com/photo-1582719508461-905c673771fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
];
while (count($roomImages) < 7) {
    $roomImages[] = $defaultImages[count($roomImages) % count($defaultImages)];
}
$roomImages = array_slice($roomImages, 0, 7);
?>

<!-- Page Header -->
<div style="height: 500px; position: relative; background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1631049307260-da0c0f11336a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;">
    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 60px 0;">
        <div class="container">
            <h1 style="color: white; font-size: 48px; margin-bottom: 10px;"><?php echo htmlspecialchars($room['category_name']); ?></h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 18px;">Experience luxury and comfort</p>
        </div>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li><a href="rooms.php">Rooms</a></li>
            <li>/</li>
            <li><?php echo htmlspecialchars($room['category_name']); ?></li>
        </ul>
    </div>
</div>

<!-- Room Details Section -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <!-- Main Content -->
            <div>
                <!-- Image Gallery with Lightbox -->
                <div style="background-color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <!-- Main Large Image -->
                        <div style="border-radius: 10px; overflow: hidden; height: 400px; cursor: pointer;" onclick="openLightbox(0)">
                            <img id="mainImage" src="<?php echo isset($roomImages[0]) ? htmlspecialchars(trim($roomImages[0])) : ''; ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                        <!-- Side Thumbnails (3 images) -->
                        <div style="display: grid; grid-template-rows: repeat(3, 1fr); gap: 10px;">
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                            <?php if (isset($roomImages[$i])): ?>
                            <div style="border-radius: 10px; overflow: hidden; cursor: pointer; position: relative;" onclick="openLightbox(<?php echo $i; ?>)" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                <img src="<?php echo htmlspecialchars(trim($roomImages[$i])); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php if ($i === 3 && count($roomImages) > 4): ?>
                                <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                    +<?php echo count($roomImages) - 4; ?> More
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <!-- Bottom Thumbnail Row (4 images) -->
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                        <?php for ($i = 4; $i < min(8, count($roomImages)); $i++): ?>
                        <div style="border-radius: 8px; overflow: hidden; height: 100px; cursor: pointer;" onclick="openLightbox(<?php echo $i; ?>)">
                            <img src="<?php echo htmlspecialchars(trim($roomImages[$i])); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                        <?php endfor; ?>
                    </div>
                    <p style="text-align: center; color: #666; font-size: 13px; margin-top: 10px;">
                        <i class="fas fa-images" style="color: var(--primary-color); margin-right: 5px;"></i>
                        Click any image to view in fullscreen gallery
                    </p>
                </div>
                
                <!-- Description -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 20px;">About This Room</h2>
                    <p style="font-size: 16px; line-height: 1.8; color: #666;">
                        <?php echo nl2br(htmlspecialchars($room['description'])); ?>
                    </p>
                    <p style="font-size: 16px; line-height: 1.8; color: #666; margin-top: 15px;">
                        Our <?php echo htmlspecialchars($room['category_name']); ?> offers the perfect blend of comfort and elegance. 
                        With <?php echo $room['room_size_sqm']; ?> square meters of space, it comfortably accommodates up to 
                        <?php echo $room['max_occupancy']; ?> guests. The room features a luxurious 
                        <?php echo htmlspecialchars($room['bed_type']); ?> and stunning views of Bayawan City.
                    </p>
                </div>
                
                <!-- Amenities -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 25px;">Room Amenities</h2>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <?php foreach ($amenities as $amenity): ?>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 12px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            <span style="font-size: 14px;"><?php echo htmlspecialchars(trim($amenity)); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Room Policy -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 20px;">Room Policies</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-clock" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Check-in/Check-out</h4>
                            <p style="font-size: 14px; color: #666;">Check-in: 2:00 PM<br>Check-out: 12:00 PM</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-ban" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Cancellation</h4>
                            <p style="font-size: 14px; color: #666;">Free cancellation up to 48 hours before check-in</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-paw" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Pets</h4>
                            <p style="font-size: 14px; color: #666;">No pets allowed (service animals excepted)</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 5px;">
                            <i class="fas fa-smoking-ban" style="color: var(--primary-color); font-size: 24px; margin-bottom: 10px;"></i>
                            <h4 style="font-size: 16px; margin-bottom: 5px;">Smoking</h4>
                            <p style="font-size: 14px; color: #666;">Non-smoking room</p>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h2 style="font-size: 28px; margin-bottom: 25px;">Guest Reviews</h2>
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
                                    <div style="width: <?php echo $starData['percentage']; ?>%; height: 100%; background-color: <?php echo $barColor; ?>;"></div>
                                </div>
                                <span style="font-size: 14px; color: #666;"><?php echo $starData['percentage']; ?>%</span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Reviews -->
                    <?php if (!empty($recentReviews)): ?>
                    <div style="margin-top: 30px;">
                        <h3 style="font-size: 20px; margin-bottom: 20px;">Recent Reviews</h3>
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
                            <?php if ($review['comment']): ?>
                            <p style="color: #666; line-height: 1.6; margin-left: 55px;"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Booking Card -->
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); position: sticky; top: 100px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--gray-light);">
                        <div>
                            <span style="font-size: 32px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($room['base_price']); ?></span>
                            <span style="font-size: 14px; color: #666;">/night</span>
                        </div>
                        <div style="background-color: var(--success-color); color: white; padding: 8px 15px; border-radius: 20px; font-size: 13px;">
                            <?php echo count($availableRooms); ?> rooms available
                        </div>
                    </div>
                    
                    <!-- Quick Availability Check -->
                    <form action="availability.php" method="GET" style="margin-bottom: 25px;">
                        <input type="hidden" name="room_type" value="<?php echo $categoryId; ?>">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Check-in</label>
                            <input type="date" name="check_in" min="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        </div>
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Check-out</label>
                            <input type="date" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-medium); border-radius: 5px;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 10px;">
                            Check Availability
                        </button>
                    </form>
                    
                    <a href="booking.php?room=<?php echo $categoryId; ?>" class="btn btn-dark" style="width: 100%; display: block; text-align: center;">
                        Book Now
                    </a>
                    
                    <a href="virtual-tour.php?category=<?php echo $categoryId; ?>" class="btn btn-outline" style="width: 100%; display: block; text-align: center; margin-top: 10px;">
                        <i class="fas fa-vr-cardboard"></i> 360° Virtual Tour
                    </a>
                    
                    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid var(--gray-light);">
                        <p style="font-size: 14px; font-weight: 600; margin-bottom: 15px;">Room Features</p>
                        <ul style="list-style: none; font-size: 14px; color: #666;">
                            <li style="margin-bottom: 10px;"><i class="fas fa-user-friends" style="color: var(--primary-color); width: 25px;"></i> Max <?php echo $room['max_occupancy']; ?> guests</li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-bed" style="color: var(--primary-color); width: 25px;"></i> <?php echo htmlspecialchars($room['bed_type']); ?></li>
                            <li style="margin-bottom: 10px;"><i class="fas fa-expand" style="color: var(--primary-color); width: 25px;"></i> <?php echo $room['room_size_sqm']; ?> m²</li>
                            <li><i class="fas fa-wifi" style="color: var(--primary-color); width: 25px;"></i> Free WiFi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar Rooms -->
        <div style="margin-top: 60px;">
            <h2 style="font-size: 32px; margin-bottom: 30px; text-align: center;">Similar Rooms</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                <?php foreach ($similarRooms as $index => $similar): ?>
                <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <div style="position: relative; height: 200px;">
                        <img src="https://images.unsplash.com/photo-<?php echo ['1590490360182-c33d57733427','1582719478250-c89cae141e86','1566666208517-13f42e1e3c2c'][$index % 3] ?>?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; top: 15px; right: 15px; background-color: var(--primary-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                            <?php echo formatPrice($similar['base_price']); ?>/night
                        </div>
                    </div>
                    <div style="padding: 25px;">
                        <h3 style="font-size: 20px; margin-bottom: 10px;"><?php echo htmlspecialchars($similar['category_name']); ?></h3>
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; font-size: 13px; color: #666;">
                            <span><i class="fas fa-user" style="color: var(--primary-color);"></i> <?php echo $similar['max_occupancy']; ?></span>
                            <span><i class="fas fa-vector-square" style="color: var(--primary-color);"></i> <?php echo $similar['room_size_sqm']; ?> m²</span>
                        </div>
                        <a href="room-details.php?id=<?php echo $similar['category_id']; ?>" class="btn btn-outline" style="width: 100%; display: block; text-align: center;">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Room images array for lightbox
const roomImages = <?php echo json_encode(array_map(function($img) { return htmlspecialchars(trim($img)); }, $roomImages)); ?>;
let currentImageIndex = 0;

function openLightbox(index) {
    currentImageIndex = index;
    const modal = document.getElementById('imageLightbox');
    const modalImg = document.getElementById('lightboxImage');
    const caption = document.getElementById('lightboxCaption');
    
    modal.style.display = 'flex';
    modalImg.src = roomImages[index];
    caption.innerHTML = '<?php echo htmlspecialchars($room['category_name']); ?> - Image ' + (index + 1) + ' of ' + roomImages.length;
    document.body.style.overflow = 'hidden';
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
    modalImg.src = roomImages[index];
    caption.innerHTML = '<?php echo htmlspecialchars($room['category_name']); ?> - Image ' + (index + 1) + ' of ' + roomImages.length;
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % roomImages.length;
    changeImage(currentImageIndex);
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + roomImages.length) % roomImages.length;
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
        <img id="lightboxImage" src="" alt="Room Image">
        <div id="lightboxCaption" class="lightbox-caption"></div>
    </div>
    <div class="lightbox-thumbnails">
        <?php foreach ($roomImages as $index => $img): ?>
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
