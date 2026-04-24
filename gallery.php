<?php
require_once 'includes/header.php';
$pageTitle = __('Photo Gallery');

// Get gallery images from database
$db = getDB();

// Categories for filtering
$categories = ['all', 'rooms', 'dining', 'amenities', 'hotel', 'attractions', 'events'];
$activeCategory = $_GET['category'] ?? 'all';

// Build query based on category filter
$sql = "SELECT * FROM gallery WHERE 1=1";
$params = [];

if ($activeCategory !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $activeCategory;
}

$sql .= " ORDER BY sort_order ASC, uploaded_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$images = $stmt->fetchAll();

// Get image counts per category for display
$categoryCounts = [];
foreach ($categories as $cat) {
    if ($cat === 'all') {
        $countStmt = $db->query("SELECT COUNT(*) FROM gallery");
    } else {
        $countStmt = $db->prepare("SELECT COUNT(*) FROM gallery WHERE category = ?");
        $countStmt->execute([$cat]);
    }
    $categoryCounts[$cat] = $countStmt->fetchColumn();
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Photo Gallery</h1>
        <p>Explore Bayawan Bai Hotel through our gallery</p>
    </div>
</div>

<!-- Gallery Section -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <!-- Filter Tabs -->
        <div style="text-align: center; margin-bottom: 40px;">
            <div style="display: inline-flex; gap: 8px; background-color: white; padding: 6px; border-radius: 30px; flex-wrap: wrap; justify-content: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <a href="?category=all" style="padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s; <?php echo $activeCategory === 'all' ? 'background-color: var(--primary-color); color: white;' : 'color: #666; hover: background-color: var(--gray-light);' ?>">All (<?php echo $categoryCounts['all']; ?>)</a>
                <a href="?category=rooms" style="padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s; <?php echo $activeCategory === 'rooms' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Rooms (<?php echo $categoryCounts['rooms']; ?>)</a>
                <a href="?category=amenities" style="padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s; <?php echo $activeCategory === 'amenities' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Amenities (<?php echo $categoryCounts['amenities']; ?>)</a>
                <a href="?category=dining" style="padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s; <?php echo $activeCategory === 'dining' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Dining (<?php echo $categoryCounts['dining']; ?>)</a>
                <a href="?category=events" style="padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s; <?php echo $activeCategory === 'events' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Events (<?php echo $categoryCounts['events']; ?>)</a>
                <a href="?category=attractions" style="padding: 10px 20px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; transition: all 0.3s; <?php echo $activeCategory === 'attractions' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;' ?>">Attractions (<?php echo $categoryCounts['attractions']; ?>)</a>
            </div>
        </div>
        
        <?php if (empty($images)): ?>
        <!-- No Images Message -->
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-images" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">No images found</h3>
            <p style="color: #999;">Images uploaded by the admin will appear here.</p>
        </div>
        <?php else: ?>
        <!-- Gallery Grid - Dynamic -->
        <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
            <?php foreach ($images as $index => $image):
                // Build full image path
                $imgPath = $image['image_path'] ?? '';
                if ($imgPath && strpos($imgPath, 'http') !== 0 && strpos($imgPath, 'assets/') !== 0) {
                    $imgPath = 'assets/' . $imgPath;
                }
            ?>
            <div class="gallery-item" data-index="<?php echo $index; ?>" data-src="<?php echo htmlspecialchars($imgPath); ?>" data-caption="<?php echo htmlspecialchars($image['title'] ?: 'Gallery Image'); ?>" data-category="<?php echo htmlspecialchars($image['category']); ?>" style="position: relative; border-radius: 8px; overflow: hidden; cursor: pointer; height: 260px;" onclick="openLightbox(<?php echo $index; ?>)">
                <?php if ($imgPath): ?>
                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($image['title'] ?: 'Gallery Image'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <?php else: ?>
                <div style="width: 100%; height: 100%; background-color: var(--gray-light); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-image" style="font-size: 40px; color: #ccc;"></i>
                </div>
                <?php endif; ?>
                <div style="position: absolute; top: 12px; right: 12px; background-color: var(--primary-color); color: white; padding: 4px 12px; border-radius: 15px; font-size: 11px; font-weight: 500; z-index: 2; text-transform: capitalize;">
                    <?php echo htmlspecialchars($image['category']); ?>
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 60%, rgba(0,0,0,0.8)); display: flex; flex-direction: column; justify-content: flex-end; padding: 16px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: 16px; margin-bottom: 4px; font-weight: 500;"><?php echo htmlspecialchars($image['title'] ?: 'Gallery Image'); ?></h4>
                    <?php if ($image['description']): ?>
                    <p style="color: rgba(255,255,255,0.8); font-size: 12px; margin: 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?php echo htmlspecialchars($image['description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox -->
<div id="lightbox" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.95); z-index: 9999; justify-content: center; align-items: center;">
    <button onclick="closeLightbox()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 28px; cursor: pointer; z-index: 10; padding: 10px;">
        <i class="fas fa-times"></i>
    </button>
    <button onclick="prevImage()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; color: white; font-size: 24px; cursor: pointer; z-index: 10; padding: 15px;">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button onclick="nextImage()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; color: white; font-size: 24px; cursor: pointer; z-index: 10; padding: 15px;">
        <i class="fas fa-chevron-right"></i>
    </button>
    <img id="lightboxImage" src="" style="max-width: 85%; max-height: 85%; object-fit: contain;">
    <p id="lightboxCaption" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; font-size: 16px; text-align: center;"></p>
</div>

<script>
let currentImageIndex = 0;
let galleryImages = [];

// Build gallery images array
document.querySelectorAll('.gallery-item').forEach((item, index) => {
    galleryImages.push({
        src: item.getAttribute('data-src'),
        caption: item.getAttribute('data-caption')
    });
});

function openLightbox(index) {
    currentImageIndex = index;
    updateLightbox();
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function updateLightbox() {
    if (galleryImages[currentImageIndex]) {
        document.getElementById('lightboxImage').src = galleryImages[currentImageIndex].src;
        document.getElementById('lightboxCaption').textContent = (currentImageIndex + 1) + ' / ' + galleryImages.length + ' - ' + galleryImages[currentImageIndex].caption;
    }
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
    updateLightbox();
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
    updateLightbox();
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (document.getElementById('lightbox').style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
    }
});

// Close on click outside
document.getElementById('lightbox').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
