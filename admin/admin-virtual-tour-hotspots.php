<?php
$pageTitle = 'Manage Hotspots - Admin';
require_once '../includes/config.php';

if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$tourId = $_GET['tour_id'] ?? 0;

$tourStmt = $db->prepare("SELECT vt.*, rc.category_name FROM room_virtual_tours vt LEFT JOIN room_categories rc ON vt.category_id = rc.category_id WHERE vt.tour_id = ?");
$tourStmt->execute([$tourId]);
$tour = $tourStmt->fetch();

if (!$tour) {
    $_SESSION['error'] = 'Virtual tour not found';
    redirect('admin-virtual-tours.php');
}

if (isset($_POST['save_hotspot'])) {
    $hotspotId = $_POST['hotspot_id'] ?? null;
    $hotspotType = $_POST['hotspot_type'] ?? 'info';
    $pitch = $_POST['pitch'] ?? 0;
    $yaw = $_POST['yaw'] ?? 0;
    $text = $_POST['text'] ?? '';
    $targetTourId = $_POST['target_tour_id'] ?? null;
    $targetUrl = $_POST['target_url'] ?? '';
    $cssClass = $_POST['css_class'] ?? 'custom-hotspot';

    if ($hotspotId) {
        $stmt = $db->prepare("UPDATE virtual_tour_hotspots SET hotspot_type = ?, pitch = ?, yaw = ?, text = ?, target_tour_id = ?, target_url = ?, css_class = ? WHERE hotspot_id = ?");
        $stmt->execute([$hotspotType, $pitch, $yaw, $text, $targetTourId, $targetUrl, $cssClass, $hotspotId]);
        $_SESSION['success'] = 'Hotspot updated successfully';
    } else {
        $stmt = $db->prepare("INSERT INTO virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, target_tour_id, target_url, css_class) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tourId, $hotspotType, $pitch, $yaw, $text, $targetTourId, $targetUrl, $cssClass]);
        $_SESSION['success'] = 'Hotspot added successfully';
    }
    redirect('admin-virtual-tour-hotspots.php?tour_id=' . $tourId);
}

if (isset($_POST['delete_hotspot'])) {
    $hotspotId = $_POST['hotspot_id'] ?? 0;
    if ($hotspotId) {
        $stmt = $db->prepare("DELETE FROM virtual_tour_hotspots WHERE hotspot_id = ?");
        $stmt->execute([$hotspotId]);
        $_SESSION['success'] = 'Hotspot deleted successfully';
    }
    redirect('admin-virtual-tour-hotspots.php?tour_id=' . $tourId);
}

$hotspotStmt = $db->prepare("SELECT * FROM virtual_tour_hotspots WHERE tour_id = ? ORDER BY created_at DESC");
$hotspotStmt->execute([$tourId]);
$hotspots = $hotspotStmt->fetchAll();

$allToursStmt = $db->query("SELECT tour_id, title FROM room_virtual_tours WHERE is_active = 1");
$allTours = $allToursStmt->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-virtual-tours.php" class="btn btn-outline">Back to Virtual Tours</a>
            <button type="button" onclick="openHotspotModal()" class="btn btn-primary">Add Hotspot</button>
        </div>

        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <h2 style="margin: 0 0 10px 0;"><?php echo htmlspecialchars($tour['title']); ?></h2>
            <p style="color: #666; margin: 0;">Room: <?php echo htmlspecialchars($tour['category_name']); ?></p>
        </div>

        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
                <h3 style="font-size: 20px; margin: 0;">Hotspots (<?php echo count($hotspots); ?>)</h3>
            </div>

            <?php if (count($hotspots) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Type</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Position</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Text</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hotspots as $hotspot): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;"><?php echo ucfirst($hotspot['hotspot_type']); ?></td>
                            <td style="padding: 15px 20px;">P: <?php echo $hotspot['pitch']; ?> Y: <?php echo $hotspot['yaw']; ?></td>
                            <td style="padding: 15px 20px;"><?php echo htmlspecialchars($hotspot['text']); ?></td>
                            <td style="padding: 15px 20px;">
                                <button type="button" onclick="editHotspot(<?php echo htmlspecialchars(json_encode($hotspot)); ?>)" class="btn btn-sm btn-primary">Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="hotspot_id" value="<?php echo $hotspot['hotspot_id']; ?>">
                                    <button type="submit" name="delete_hotspot" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <h3 style="color: #666;">No hotspots yet</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="hotspotModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add Hotspot</h3>
        </div>
        <form method="POST" style="padding: 30px;">
            <input type="hidden" name="hotspot_id" id="hotspot_id">
            <div style="margin-bottom: 15px;">
                <label>Type</label>
                <select name="hotspot_type" id="hotspot_type" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                    <option value="info">Info</option>
                    <option value="scene">Scene</option>
                    <option value="link">Link</option>
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label>Pitch (-90 to 90)</label>
                    <input type="number" name="pitch" id="pitch" step="0.1" min="-90" max="90" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label>Yaw (-180 to 180)</label>
                    <input type="number" name="yaw" id="yaw" step="0.1" min="-180" max="180" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Text</label>
                <input type="text" name="text" id="text" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
            </div>
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeHotspotModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_hotspot" class="btn btn-primary">Save</button>
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
}

function editHotspot(hotspot) {
    document.getElementById('hotspotModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Hotspot';
    document.getElementById('hotspot_id').value = hotspot.hotspot_id;
    document.getElementById('hotspot_type').value = hotspot.hotspot_type;
    document.getElementById('pitch').value = hotspot.pitch;
    document.getElementById('yaw').value = hotspot.yaw;
    document.getElementById('text').value = hotspot.text || '';
}

function closeHotspotModal() {
    document.getElementById('hotspotModal').style.display = 'none';
}
</script>

<?php require_once '../includes/admin-footer.php'; ?>
