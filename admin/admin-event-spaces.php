<?php
$pageTitle = 'Manage Event Spaces - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit event space
if (isset($_POST['save_space'])) {
    $spaceId = $_POST['space_id'] ?? null;
    $spaceName = $_POST['space_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $areaSqm = $_POST['area_sqm'] ?? null;
    $features = $_POST['features'] ?? '';
    $pricePerDay = $_POST['price_per_day'] ?? 0;
    $status = $_POST['status'] ?? 'available';

    // Handle image upload
    $images = '';
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir = '../assets/images/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $imagePaths = [];
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $fileName = time() . '_' . $key . '_' . basename($_FILES['images']['name'][$key]);
                $uploadFile = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $uploadFile)) {
                    $imagePaths[] = 'images/events/' . $fileName;
                }
            }
        }
        $images = implode(',', $imagePaths);
    }

    if ($spaceName && $capacity > 0) {
        if ($spaceId) {
            // Update existing space
            if ($images) {
                $stmt = $db->prepare("UPDATE event_spaces SET space_name = ?, description = ?, capacity = ?, area_sqm = ?, features = ?, price_per_day = ?, images = ?, status = ? WHERE space_id = ?");
                $stmt->execute([$spaceName, $description, $capacity, $areaSqm, $features, $pricePerDay, $images, $status, $spaceId]);
            } else {
                $stmt = $db->prepare("UPDATE event_spaces SET space_name = ?, description = ?, capacity = ?, area_sqm = ?, features = ?, price_per_day = ?, status = ? WHERE space_id = ?");
                $stmt->execute([$spaceName, $description, $capacity, $areaSqm, $features, $pricePerDay, $status, $spaceId]);
            }
            $_SESSION['success'] = 'Event space updated successfully';
        } else {
            // Add new space
            $stmt = $db->prepare("INSERT INTO event_spaces (space_name, description, capacity, area_sqm, features, price_per_day, images, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$spaceName, $description, $capacity, $areaSqm, $features, $pricePerDay, $images, $status]);
            $_SESSION['success'] = 'Event space added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-event-spaces.php');
}

// Handle delete space
if (isset($_POST['delete_space'])) {
    $spaceId = $_POST['space_id'] ?? 0;
    if ($spaceId) {
        // Check if space has bookings
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM event_bookings WHERE space_id = ? AND status != 'cancelled'");
        $checkStmt->execute([$spaceId]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Cannot delete space that has active bookings';
        } else {
            $stmt = $db->prepare("DELETE FROM event_spaces WHERE space_id = ?");
            if ($stmt->execute([$spaceId])) {
                $_SESSION['success'] = 'Event space deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete event space';
            }
        }
    }
    redirect('admin-event-spaces.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT es.*, (SELECT COUNT(*) FROM event_bookings WHERE space_id = es.space_id AND status IN ('pending', 'confirmed')) as booking_count FROM event_spaces es WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND es.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (es.space_name LIKE ? OR es.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY es.capacity DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$spaces = $stmt->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openSpaceModal()" class="btn btn-primary">Add New Event Space</button>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search event spaces..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="booked" <?php echo $statusFilter === 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-event-spaces.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Event Spaces Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Event Spaces (<?php echo count($spaces); ?>)</h3>
            </div>

            <?php if (count($spaces) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Space Name</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Capacity</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Area</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price/Day</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Bookings</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($spaces as $space): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($space['space_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($space['description'], 0, 60)) . '...'; ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo number_format($space['capacity']); ?> guests
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $space['area_sqm'] ? number_format($space['area_sqm']) . ' sqm' : 'N/A'; ?>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($space['price_per_day']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600;"><?php echo $space['booking_count']; ?></span> active
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php
                                $statusColors = [
                                    'available' => ['#d4edda', '#155724'],
                                    'booked' => ['#fff3cd', '#856404'],
                                    'maintenance' => ['#f8d7da', '#721c24']
                                ];
                                $color = $statusColors[$space['status']] ?? ['#e2e3e5', '#383d41'];
                                ?>
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $space['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editSpace(<?php echo htmlspecialchars(json_encode($space)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this event space?');">
                                        <input type="hidden" name="space_id" value="<?php echo $space['space_id']; ?>">
                                        <button type="submit" name="delete_space" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-calendar-alt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No event spaces found</h3>
                <p style="color: #999;">Add your first event space</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Event Space Modal -->
<div id="spaceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Event Space</h3>
            <button onclick="closeSpaceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="space_id" id="space_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Space Name *</label>
                <input type="text" name="space_name" id="space_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Capacity *</label>
                    <input type="number" name="capacity" id="capacity" min="1" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Area (sqm)</label>
                    <input type="number" name="area_sqm" id="area_sqm" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Price per Day (PHP)</label>
                    <input type="number" name="price_per_day" id="price_per_day" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Features (comma-separated)</label>
                <input type="text" name="features" id="features" placeholder="Stage, Sound System, Projector, WiFi..." style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Images</label>
                <input type="file" name="images[]" id="images" accept="image/*" multiple style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                <small style="color: #666;">You can select multiple images</small>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="available">Available</option>
                    <option value="booked">Booked</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeSpaceModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_space" class="btn btn-primary">Save Event Space</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSpaceModal() {
    document.getElementById('spaceModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Event Space';
    document.getElementById('space_id').value = '';
    document.getElementById('space_name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('capacity').value = '';
    document.getElementById('area_sqm').value = '';
    document.getElementById('price_per_day').value = '';
    document.getElementById('features').value = '';
    document.getElementById('status').value = 'available';
}

function editSpace(space) {
    document.getElementById('spaceModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Event Space';
    document.getElementById('space_id').value = space.space_id;
    document.getElementById('space_name').value = space.space_name;
    document.getElementById('description').value = space.description || '';
    document.getElementById('capacity').value = space.capacity;
    document.getElementById('area_sqm').value = space.area_sqm || '';
    document.getElementById('price_per_day').value = space.price_per_day || '';
    document.getElementById('features').value = space.features || '';
    document.getElementById('status').value = space.status;
}

function closeSpaceModal() {
    document.getElementById('spaceModal').style.display = 'none';
}

document.getElementById('spaceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSpaceModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
