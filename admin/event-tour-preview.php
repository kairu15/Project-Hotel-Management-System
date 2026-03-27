<?php
$pageTitle = 'Preview Event Virtual Tour';
require_once '../includes/config.php';

// Check if user is logged in (staff or admin)
if (!isStaff() && !isAdmin()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../auth/login.php');
}

$db = getDB();

// Get tour ID from URL
$tourId = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;
if (!$tourId) {
    showAlert('No tour specified', 'warning');
    redirect('admin-event-virtual-tours.php');
}

// Get tour details
$tourStmt = $db->prepare("
    SELECT evt.*, es.space_name, es.capacity, es.area_sqm 
    FROM event_virtual_tours evt 
    LEFT JOIN event_spaces es ON evt.space_id = es.space_id 
    WHERE evt.tour_id = ?
");
$tourStmt->execute([$tourId]);
$tour = $tourStmt->fetch();

if (!$tour) {
    showAlert('Tour not found', 'danger');
    redirect('admin-event-virtual-tours.php');
}

// Get hotspots for this tour
$hotspotStmt = $db->prepare("SELECT * FROM event_virtual_tour_hotspots WHERE tour_id = ?");
$hotspotStmt->execute([$tourId]);
$hotspots = $hotspotStmt->fetchAll();

// Build hotspot config for Pannellum
$hotspotConfig = [];
foreach ($hotspots as $hotspot) {
    $hotspotData = [
        'pitch' => floatval($hotspot['pitch']),
        'yaw' => floatval($hotspot['yaw']),
        'type' => $hotspot['hotspot_type'],
        'text' => $hotspot['text'] ?? ''
    ];
    
    if ($hotspot['hotspot_type'] === 'scene' && $hotspot['target_tour_id']) {
        $hotspotData['sceneId'] = 'tour_' . $hotspot['target_tour_id'];
    }
    
    if ($hotspot['hotspot_type'] === 'link' && $hotspot['target_url']) {
        $hotspotData['URL'] = $hotspot['target_url'];
    }
    
    $hotspotConfig[] = $hotspotData;
}

require_once '../includes/admin-header.php';
?>

<style>
    .preview-container {
        display: flex;
        height: calc(100vh - 120px);
        min-height: 600px;
    }
    
    .preview-sidebar {
        width: 300px;
        background: white;
        border-right: 1px solid var(--gray-medium);
        padding: 20px;
        overflow-y: auto;
    }
    
    .preview-viewer {
        flex: 1;
        position: relative;
    }
    
    #panorama {
        width: 100%;
        height: 100%;
    }
    
    .tour-info {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--gray-light);
    }
    
    .tour-info h3 {
        margin: 0 0 10px 0;
        font-size: 20px;
    }
    
    .hotspot-list {
        margin-top: 20px;
    }
    
    .hotspot-item {
        background: var(--gray-light);
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 10px;
        font-size: 13px;
    }
    
    .hotspot-item strong {
        color: var(--primary-color);
    }
    
    .preview-badge {
        position: absolute;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(220, 53, 69, 0.9);
        color: white;
        padding: 10px 30px;
        border-radius: 5px;
        font-weight: 600;
        z-index: 1000;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<section style="padding: 20px; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0;">Preview: <?php echo htmlspecialchars($tour['title']); ?></h2>
            <p style="margin: 5px 0 0 0; color: #666;">
                <?php echo htmlspecialchars($tour['space_name']); ?>
                <?php if ($tour['capacity']): ?> • <?php echo $tour['capacity']; ?> guests<?php endif; ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="admin-event-virtual-tour-hotspots.php?tour_id=<?php echo $tourId; ?>" class="btn btn-outline">
                <i class="fas fa-map-marker-alt"></i> Manage Hotspots
            </a>
            <a href="admin-event-virtual-tours.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Tours
            </a>
        </div>
    </div>

    <div class="preview-container">
        <div class="preview-sidebar">
            <div class="tour-info">
                <h3><?php echo htmlspecialchars($tour['title']); ?></h3>
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <?php echo htmlspecialchars($tour['description'] ?? 'No description available'); ?>
                </p>
            </div>
            
            <div>
                <h4 style="font-size: 16px; margin-bottom: 15px;"><i class="fas fa-info-circle" style="color: var(--info-color); margin-right: 8px;"></i>Tour Info</h4>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    <strong>Status:</strong> 
                    <span style="padding: 3px 10px; border-radius: 12px; font-size: 12px; background-color: <?php echo $tour['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $tour['is_active'] ? '#155724' : '#721c24'; ?>">
                        <?php echo $tour['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </p>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    <strong>Event Space:</strong> <?php echo htmlspecialchars($tour['space_name']); ?>
                </p>
                <?php if ($tour['capacity']): ?>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    <strong>Capacity:</strong> <?php echo $tour['capacity']; ?> guests
                </p>
                <?php endif; ?>
                <?php if ($tour['area_sqm']): ?>
                <p style="font-size: 14px; margin-bottom: 10px;">
                    <strong>Area:</strong> <?php echo $tour['area_sqm']; ?> m²
                </p>
                <?php endif; ?>
            </div>
            
            <?php if (count($hotspots) > 0): ?>
            <div class="hotspot-list">
                <h4 style="font-size: 16px; margin-bottom: 15px;"><i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 8px;"></i>Hotspots (<?php echo count($hotspots); ?>)</h4>
                <?php foreach ($hotspots as $hotspot): ?>
                <div class="hotspot-item">
                    <strong><?php echo ucfirst($hotspot['hotspot_type']); ?></strong><br>
                    Pitch: <?php echo $hotspot['pitch']; ?>°, Yaw: <?php echo $hotspot['yaw']; ?><br>
                    <?php if ($hotspot['text']): ?>
                    <small>"<?php echo htmlspecialchars(substr($hotspot['text'], 0, 30)) . (strlen($hotspot['text']) > 30 ? '...' : ''); ?>"</small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="preview-viewer">
            <div class="preview-badge">Preview Mode</div>
            <div id="panorama"></div>
        </div>
    </div>
</section>

<!-- Pannellum Library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

<script>
    let viewer;
    
    document.addEventListener('DOMContentLoaded', function() {
        const hotspots = <?php echo json_encode($hotspotConfig); ?>;
        
        const config = {
            type: 'equirectangular',
            panorama: '../<?php echo addslashes($tour['panorama_image']); ?>',
            autoLoad: true,
            showControls: true,
            showFullscreenCtrl: true,
            showZoomCtrl: true,
            autoRotate: 0,
            compass: true,
            northOffset: 0,
            title: '<?php echo addslashes($tour['title']); ?>',
            hotSpots: hotspots
        };
        
        viewer = pannellum.viewer('panorama', config);
    });
</script>

<?php require_once '../includes/admin-footer.php'; ?>
