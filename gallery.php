<?php
$pageTitle = 'Photo Gallery';
require_once 'includes/header.php';

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
            <div style="display: inline-flex; gap: 10px; background-color: white; padding: 8px; border-radius: 30px; flex-wrap: wrap; justify-content: center;">
                <a href="?category=all" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'all' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">All Photos (<?php echo $categoryCounts['all']; ?>)</a>
                <a href="?category=rooms" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'rooms' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Rooms (<?php echo $categoryCounts['rooms']; ?>)</a>
                <a href="?category=amenities" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'amenities' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Amenities (<?php echo $categoryCounts['amenities']; ?>)</a>
                <a href="?category=dining" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'dining' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Dining (<?php echo $categoryCounts['dining']; ?>)</a>
                <a href="?category=events" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'events' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Events (<?php echo $categoryCounts['events']; ?>)</a>
                <a href="?category=attractions" style="padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.3s; <?php echo $activeCategory === 'attractions' ? 'background-color: var(--primary-color); color: white;' : 'color: #666;'; ?>">Attractions (<?php echo $categoryCounts['attractions']; ?>)</a>
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
        <div class="gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($images as $index => $image): 
                // Build full image path
                $imgPath = $image['image_path'] ?? '';
                if ($imgPath && strpos($imgPath, 'http') !== 0 && strpos($imgPath, 'assets/') !== 0) {
                    $imgPath = 'assets/' . $imgPath;
                }
                // Determine if this should be a featured (larger) item
                $isFeatured = $image['is_featured'] || ($index === 0 && $activeCategory === 'all');
                $gridClass = $isFeatured ? 'grid-column: span 2; grid-row: span 2;' : '';
                $height = $isFeatured ? '500px' : '240px';
            ?>
            <div class="gallery-item" data-category="<?php echo htmlspecialchars($image['category']); ?>" style="<?php echo $gridClass; ?> position: relative; border-radius: 10px; overflow: hidden; cursor: pointer; height: <?php echo $height; ?>;" onclick="openLightbox('<?php echo htmlspecialchars($imgPath); ?>', '<?php echo htmlspecialchars($image['title'] ?: 'Gallery Image'); ?>')">
                <?php if ($imgPath): ?>
                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($image['title'] ?: 'Gallery Image'); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <?php else: ?>
                <div style="width: 100%; height: 100%; background-color: var(--gray-light); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-image" style="font-size: 48px; color: #ccc;"></i>
                </div>
                <?php endif; ?>
                <?php if ($image['is_featured']): ?>
                <div style="position: absolute; top: 15px; left: 15px; background-color: var(--warning-color); color: var(--dark-color); padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; z-index: 2;">
                    <i class="fas fa-star"></i> Featured
                </div>
                <?php endif; ?>
                <div style="position: absolute; top: 15px; right: 15px; background-color: var(--primary-color); color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; z-index: 2; text-transform: capitalize;">
                    <?php echo htmlspecialchars($image['category']); ?>
                </div>
                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; opacity: 0; transition: opacity 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                    <h4 style="color: white; font-size: <?php echo $isFeatured ? '20px' : '16px'; ?>; margin-bottom: 5px;"><?php echo htmlspecialchars($image['title'] ?: 'Gallery Image'); ?></h4>
                    <?php if ($image['description']): ?>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px; margin: 0; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;"><?php echo htmlspecialchars($image['description']); ?></p>
                    <?php endif; ?>
                    <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin-top: 10px;"><i class="fas fa-search-plus"></i> Click to view</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox -->
<div id="lightbox" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.95); z-index: 9999; justify-content: center; align-items: center;">
    <button onclick="closeLightbox()" style="position: absolute; top: 20px; right: 20px; background: none; border: none; color: white; font-size: 30px; cursor: pointer;">
        <i class="fas fa-times"></i>
    </button>
    <img id="lightboxImage" src="" style="max-width: 90%; max-height: 90%; object-fit: contain;">
    <p id="lightboxCaption" style="position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); color: white; font-size: 18px;"></p>
</div>

<script>
function openLightbox(src, caption) {
    document.getElementById('lightboxImage').src = src;
    document.getElementById('lightboxCaption').textContent = caption;
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
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
