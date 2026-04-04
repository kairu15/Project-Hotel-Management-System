<?php
$pageTitle = 'Manage Event Virtual Tour Hotspots - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Get tour ID from URL
$tourId = $_GET['tour_id'] ?? 0;
if (!$tourId) {
    redirect('admin-event-virtual-tours.php');
}

// Get tour details
$tourStmt = $db->prepare("SELECT evt.*, es.space_name FROM event_virtual_tours evt LEFT JOIN event_spaces es ON evt.space_id = es.space_id WHERE evt.tour_id = ?");
$tourStmt->execute([$tourId]);
$tour = $tourStmt->fetch();

if (!$tour) {
    showAlert('Tour not found', 'danger');
    redirect('admin-event-virtual-tours.php');
}

// Handle add/edit hotspot
if (isset($_POST['save_hotspot'])) {
    $hotspotId = $_POST['hotspot_id'] ?? null;
    $hotspotType = $_POST['hotspot_type'] ?? 'info';
    $pitch = $_POST['pitch'] ?? 0;
    $yaw = $_POST['yaw'] ?? 0;
    $text = $_POST['text'] ?? '';
    $targetTourId = $_POST['target_tour_id'] ?? null;
    $targetUrl = $_POST['target_url'] ?? '';

    if ($pitch !== '' && $yaw !== '') {
        if ($hotspotId) {
            // Update existing hotspot
            $stmt = $db->prepare("UPDATE event_virtual_tour_hotspots SET hotspot_type = ?, pitch = ?, yaw = ?, text = ?, target_tour_id = ?, target_url = ? WHERE hotspot_id = ?");
            $stmt->execute([$hotspotType, $pitch, $yaw, $text, $targetTourId, $targetUrl, $hotspotId]);
            $_SESSION['success'] = 'Hotspot updated successfully';
        } else {
            // Add new hotspot
            $stmt = $db->prepare("INSERT INTO event_virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, target_tour_id, target_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tourId, $hotspotType, $pitch, $yaw, $text, $targetTourId, $targetUrl]);
            $_SESSION['success'] = 'Hotspot added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-event-virtual-tour-hotspots.php?tour_id=' . $tourId);
}

// Handle delete hotspot
if (isset($_POST['delete_hotspot'])) {
    $hotspotId = $_POST['hotspot_id'] ?? 0;
    if ($hotspotId) {
        $stmt = $db->prepare("DELETE FROM event_virtual_tour_hotspots WHERE hotspot_id = ?");
        if ($stmt->execute([$hotspotId])) {
            $_SESSION['success'] = 'Hotspot deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete hotspot';
        }
    }
    redirect('admin-event-virtual-tour-hotspots.php?tour_id=' . $tourId);
}

// Get all hotspots for this tour
$hotspots = $db->prepare("SELECT * FROM event_virtual_tour_hotspots WHERE tour_id = ? ORDER BY hotspot_id");
$hotspots->execute([$tourId]);
$hotspots = $hotspots->fetchAll();

// Get other tours for scene navigation dropdown
$otherTours = $db->prepare("SELECT tour_id, title FROM event_virtual_tours WHERE tour_id != ? AND is_active = 1");
$otherTours->execute([$tourId]);
$otherTours = $otherTours->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="admin-event-virtual-tours.php" class="btn btn-outline" style="margin-right: 15px;">Back to Tours</a>
                <span style="font-size: 20px; font-weight: 600;"><?php echo htmlspecialchars($tour['title']); ?></span>
                <span style="color: #666; margin-left: 10px;">(<?php echo htmlspecialchars($tour['space_name']); ?>)</span>
            </div>
            <button type="button" onclick="openHotspotModal()" class="btn btn-primary">Add Hotspot</button>
        </div>

        <!-- Hotspots Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Hotspots (<?php echo count($hotspots); ?>)</h3>
                <a href="event-tour-preview.php?tour_id=<?php echo $tourId; ?>" target="_blank" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;"><i class="fas fa-eye"></i> Preview Tour</a>
            </div>

            <?php if (count($hotspots) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Type</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Position (Pitch, Yaw)</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Text / Action</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotspots as $hotspot): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $hotspot['hotspot_type'] === 'info' ? '#d1ecf1' : ($hotspot['hotspot_type'] === 'scene' ? '#d4edda' : '#fff3cd'); ?>; color: <?php echo $hotspot['hotspot_type'] === 'info' ? '#0c5460' : ($hotspot['hotspot_type'] === 'scene' ? '#155724' : '#856404'); ?>">
                                    <?php echo ucfirst($hotspot['hotspot_type']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                Pitch: <?php echo $hotspot['pitch']; ?>°, Yaw: <?php echo $hotspot['yaw']; ?>°
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($hotspot['text']): ?>
                                <div style="font-size: 14px;"><?php echo htmlspecialchars(substr($hotspot['text'], 0, 50)) . (strlen($hotspot['text']) > 50 ? '...' : ''); ?></div>
                                <?php endif; ?>
                                <?php if ($hotspot['target_tour_id']): ?>
                                <div style="font-size: 12px; color: #666;"><i class="fas fa-link"></i> Links to tour ID: <?php echo $hotspot['target_tour_id']; ?></div>
                                <?php endif; ?>
                                <?php if ($hotspot['target_url']): ?>
                                <div style="font-size: 12px; color: #666;"><i class="fas fa-external-link-alt"></i> <?php echo htmlspecialchars(substr($hotspot['target_url'], 0, 40)) . (strlen($hotspot['target_url']) > 40 ? '...' : ''); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editHotspot(<?php echo htmlspecialchars(json_encode($hotspot)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" id="deleteHotspotForm<?php echo $hotspot['hotspot_id']; ?>">
                                        <input type="hidden" name="hotspot_id" value="<?php echo $hotspot['hotspot_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('deleteHotspotForm<?php echo $hotspot['hotspot_id']; ?>', 'Delete Hotspot', 'Are you sure you want to delete this hotspot?', null, 'delete_hotspot')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-map-marker-alt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No hotspots yet</h3>
                <p style="color: #999;">Add interactive hotspots to your virtual tour</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Instructions -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px 30px; margin-top: 30px;">
            <h4 style="font-size: 18px; margin-bottom: 15px;"><i class="fas fa-info-circle" style="color: var(--info-color); margin-right: 8px;"></i>How to Position Hotspots</h4>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; font-size: 14px; color: #666;">
                <div>
                    <strong>Pitch</strong> (vertical angle):<br>
                    - 0° = Straight ahead (horizon level)<br>
                    - -90° = Straight down (floor)<br>
                    - 90° = Straight up (ceiling)<br>
                    Range: -90 to 90 degrees
                </div>
                <div>
                    <strong>Yaw</strong> (horizontal angle):<br>
                    - 0° = Starting view direction<br>
                    - -90° = Left<br>
                    - 90° = Right<br>
                    - ±180° = Behind<br>
                    Range: -180 to 180 degrees
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Hotspot Modal -->
<div id="hotspotModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add Hotspot</h3>
            <button onclick="closeHotspotModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="hotspot_id" id="hotspot_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Hotspot Type</label>
                <select name="hotspot_type" id="hotspot_type" onchange="updateHotspotForm()" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="info">Info (Tooltip)</option>
                    <option value="scene">Scene (Navigate to another tour)</option>
                    <option value="link">Link (External URL)</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Pitch (vertical) *</label>
                    <input type="number" name="pitch" id="pitch" step="0.1" min="-90" max="90" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Yaw (horizontal) *</label>
                    <input type="number" name="yaw" id="yaw" step="0.1" min="-180" max="180" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div id="infoFields" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Tooltip Text</label>
                <input type="text" name="text" id="text" placeholder="Enter tooltip text..." style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div id="sceneFields" style="margin-bottom: 20px; display: none;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Target Tour</label>
                <select name="target_tour_id" id="target_tour_id" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select target tour</option>
                    <?php foreach ($otherTours as $otherTour): ?>
                    <option value="<?php echo $otherTour['tour_id']; ?>"><?php echo htmlspecialchars($otherTour['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="linkFields" style="margin-bottom: 25px; display: none;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Target URL</label>
                <input type="url" name="target_url" id="target_url" placeholder="https://example.com" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeHotspotModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_hotspot" class="btn btn-primary">Save Hotspot</button>
            </div>
        </form>
    </div>
</div>

<script>
function openHotspotModal() {
    document.getElementById('hotspotModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add Hotspot';
    document.getElementById('hotspot_id').value = '';
    document.getElementById('hotspot_type').value = 'info';
    document.getElementById('pitch').value = '0';
    document.getElementById('yaw').value = '0';
    document.getElementById('text').value = '';
    document.getElementById('target_tour_id').value = '';
    document.getElementById('target_url').value = '';
    updateHotspotForm();
}

function editHotspot(hotspot) {
    document.getElementById('hotspotModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Hotspot';
    document.getElementById('hotspot_id').value = hotspot.hotspot_id;
    document.getElementById('hotspot_type').value = hotspot.hotspot_type;
    document.getElementById('pitch').value = hotspot.pitch;
    document.getElementById('yaw').value = hotspot.yaw;
    document.getElementById('text').value = hotspot.text || '';
    document.getElementById('target_tour_id').value = hotspot.target_tour_id || '';
    document.getElementById('target_url').value = hotspot.target_url || '';
    updateHotspotForm();
}

function updateHotspotForm() {
    const type = document.getElementById('hotspot_type').value;
    document.getElementById('infoFields').style.display = type === 'info' ? 'block' : 'none';
    document.getElementById('sceneFields').style.display = type === 'scene' ? 'block' : 'none';
    document.getElementById('linkFields').style.display = type === 'link' ? 'block' : 'none';
}

function closeHotspotModal() {
    document.getElementById('hotspotModal').style.display = 'none';
}

document.getElementById('hotspotModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeHotspotModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
