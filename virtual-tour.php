<?php
$pageTitle = '360° Virtual Tour';
require_once 'includes/config.php';

$db = getDB();

// Get category filter
$categoryId = $_GET['category'] ?? 0;
$tourId = $_GET['tour'] ?? 0;

// Get all room categories with virtual tours
$stmt = $db->query("SELECT rc.category_id, rc.category_name, rc.base_price, rc.image_primary, 
    (SELECT COUNT(*) FROM room_virtual_tours WHERE category_id = rc.category_id AND is_active = 1) as tour_count
    FROM room_categories rc 
    WHERE rc.status = 'active' 
    HAVING tour_count > 0
    ORDER BY rc.base_price ASC");
$categories = $stmt->fetchAll();

// Get specific tour or first available
if ($tourId) {
    $tourStmt = $db->prepare("SELECT vt.*, rc.category_name, rc.description as room_description FROM room_virtual_tours vt 
        LEFT JOIN room_categories rc ON vt.category_id = rc.category_id 
        WHERE vt.tour_id = ? AND vt.is_active = 1");
    $tourStmt->execute([$tourId]);
    $currentTour = $tourStmt->fetch();
} elseif ($categoryId) {
    $tourStmt = $db->prepare("SELECT vt.*, rc.category_name, rc.description as room_description FROM room_virtual_tours vt 
        LEFT JOIN room_categories rc ON vt.category_id = rc.category_id 
        WHERE vt.category_id = ? AND vt.is_active = 1 ORDER BY vt.display_order ASC LIMIT 1");
    $tourStmt->execute([$categoryId]);
    $currentTour = $tourStmt->fetch();
} else {
    $tourStmt = $db->query("SELECT vt.*, rc.category_name, rc.description as room_description FROM room_virtual_tours vt 
        LEFT JOIN room_categories rc ON vt.category_id = rc.category_id 
        WHERE vt.is_active = 1 ORDER BY vt.display_order ASC LIMIT 1");
    $currentTour = $tourStmt->fetch();
}

// Get hotspots for current tour
$hotspots = [];
if ($currentTour) {
    $hotspotStmt = $db->prepare("SELECT * FROM virtual_tour_hotspots WHERE tour_id = ?");
    $hotspotStmt->execute([$currentTour['tour_id']]);
    $hotspots = $hotspotStmt->fetchAll();
}

// Get all tours for current category
$categoryTours = [];
if ($currentTour) {
    $catTourStmt = $db->prepare("SELECT * FROM room_virtual_tours WHERE category_id = ? AND is_active = 1 ORDER BY display_order");
    $catTourStmt->execute([$currentTour['category_id']]);
    $categoryTours = $catTourStmt->fetchAll();
}

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<style>
.pnlm-container {
    border-radius: 10px;
}
.custom-hotspot {
    width: 30px;
    height: 30px;
    background-color: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.2s;
}
.custom-hotspot:hover {
    transform: scale(1.2);
}
.custom-hotspot::after {
    content: '\f129';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    color: var(--primary-color);
    font-size: 14px;
}
.scene-hotspot::after {
    content: '\f0b2';
}
.link-hotspot::after {
    content: '\f35d';
}
div.custom-tooltip span {
    visibility: hidden;
    position: absolute;
    border-radius: 3px;
    background-color: #fff;
    color: #000;
    text-align: center;
    max-width: 200px;
    padding: 5px 10px;
    margin-left: -220px;
    cursor: default;
    font-size: 14px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.custom-hotspot:hover div.custom-tooltip span {
    visibility: visible;
}
</style>

<!-- Page Header -->
<div style="height: 300px; position: relative; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/images/virtual-tour-header.jpg') center/cover no-repeat;">
    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.8)); padding: 40px 0;">
        <div class="container">
            <h1 style="color: white; font-size: 42px; margin-bottom: 10px;">360° Virtual Tour</h1>
            <p style="color: rgba(255,255,255,0.9); font-size: 18px;">Explore our rooms from every angle</p>
        </div>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li>Virtual Tour</li>
        </ul>
    </div>
</div>

<!-- Virtual Tour Section -->
<section style="padding: 40px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 280px 1fr; gap: 30px;">
            
            <!-- Sidebar - Room Selection -->
            <div>
                <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-bottom: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 20px;">Select Room</h3>
                    
                    <?php foreach ($categories as $category): ?>
                    <a href="virtual-tour.php?category=<?php echo $category['category_id']; ?>" 
                       style="display: block; padding: 15px; margin-bottom: 10px; border-radius: 8px; text-decoration: none; transition: all 0.3s;
                       <?php echo ($currentTour && $currentTour['category_id'] == $category['category_id']) ? 'background-color: var(--primary-color); color: white;' : 'background-color: var(--gray-light); color: var(--text-color);'; ?>">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if ($category['image_primary']): ?>
                            <img src="assets/<?php echo htmlspecialchars($category['image_primary']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                            <div style="width: 50px; height: 50px; background-color: #ddd; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-bed"></i>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($category['category_name']); ?></div>
                                <div style="font-size: 12px; opacity: 0.8;"><?php echo formatPrice($category['base_price']); ?>/night</div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($currentTour): ?>
                <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h3 style="font-size: 18px; margin-bottom: 15px;">Room Details</h3>
                    <h4 style="font-size: 16px; margin-bottom: 10px;"><?php echo htmlspecialchars($currentTour['category_name']); ?></h4>
                    <p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 15px;">
                        <?php echo htmlspecialchars($currentTour['room_description'] ?? ''); ?>
                    </p>
                    <a href="room-details.php?id=<?php echo $currentTour['category_id']; ?>" class="btn btn-outline" style="width: 100%; display: block; text-align: center;">
                        View Room Info
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Main Content - Panorama Viewer -->
            <div>
                <?php if ($currentTour): ?>
                <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <div style="margin-bottom: 15px;">
                        <h2 style="font-size: 24px; margin-bottom: 5px;"><?php echo htmlspecialchars($currentTour['title']); ?></h2>
                        <p style="color: #666; font-size: 14px;"><?php echo htmlspecialchars($currentTour['description'] ?? ''); ?></p>
                    </div>

                    <!-- Pannellum Viewer -->
                    <div id="panorama" style="width: 100%; height: 500px;"></div>

                    <!-- Controls Info -->
                    <div style="margin-top: 15px; padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-hand-paper" style="color: var(--primary-color);"></i>
                                <span style="font-size: 13px; color: #666;">Click and drag to look around</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-search-plus" style="color: var(--primary-color);"></i>
                                <span style="font-size: 13px; color: #666;">Scroll to zoom in/out</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-expand" style="color: var(--primary-color);"></i>
                                <span style="font-size: 13px; color: #666;">Click fullscreen button for best experience</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Multiple Tours for Same Category -->
                <?php if (count($categoryTours) > 1): ?>
                <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-top: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 15px;">More Views</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                        <?php foreach ($categoryTours as $tour): ?>
                        <a href="virtual-tour.php?tour=<?php echo $tour['tour_id']; ?>" 
                           style="text-decoration: none; border-radius: 8px; overflow: hidden; border: 2px solid <?php echo $tour['tour_id'] == $currentTour['tour_id'] ? 'var(--primary-color)' : 'transparent'; ?>;">
                            <?php if ($tour['thumbnail_image']): ?>
                            <img src="assets/<?php echo htmlspecialchars($tour['thumbnail_image']); ?>" style="width: 100%; height: 100px; object-fit: cover;">
                            <?php else: ?>
                            <div style="width: 100%; height: 100px; background-color: var(--gray-light); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-vr-cardboard" style="color: #999; font-size: 24px;"></i>
                            </div>
                            <?php endif; ?>
                            <div style="padding: 10px; background-color: var(--gray-light);">
                                <div style="font-size: 12px; font-weight: 500; color: var(--text-color);"><?php echo htmlspecialchars($tour['title']); ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div style="background-color: white; padding: 60px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); text-align: center;">
                    <i class="fas fa-vr-cardboard" style="font-size: 64px; color: var(--gray-light); margin-bottom: 20px;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">No Virtual Tour Available</h3>
                    <p style="color: #999;">Please select a room category to view its virtual tour.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php if ($currentTour): ?>
<script>
pannellum.viewer('panorama', {
    type: 'equirectangular',
    panorama: 'assets/<?php echo htmlspecialchars($currentTour['panorama_image']); ?>',
    autoLoad: true,
    autoRotate: -2,
    compass: true,
    showFullscreenCtrl: true,
    showZoomCtrl: true,
    mouseZoom: true,
    draggable: true,
    disableKeyboardCtrl: false,
    
    hotSpotDebug: false,
    hotSpots: [
        <?php foreach ($hotspots as $index => $hotspot): ?>
        {
            pitch: <?php echo $hotspot['pitch']; ?>,
            yaw: <?php echo $hotspot['yaw']; ?>,
            type: '<?php echo $hotspot['hotspot_type']; ?>',
            text: '<?php echo addslashes($hotspot['text'] ?? ''); ?>',
            cssClass: 'custom-hotspot <?php echo $hotspot['hotspot_type']; ?>-hotspot',
            clickHandlerFunc: <?php echo $hotspot['hotspot_type'] === 'link' ? 'function() { window.open("' . htmlspecialchars($hotspot['target_url'] ?? '#') . '", "_blank"); }' : 'null'; ?>
        }<?php echo $index < count($hotspots) - 1 ? ',' : ''; ?>
        <?php endforeach; ?>
    ]
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
