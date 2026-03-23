<?php
$pageTitle = 'Manage Rooms - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle room status update
if (isset($_POST['update_room_status'])) {
    $roomId = $_POST['room_id'] ?? 0;
    $newStatus = $_POST['room_status'] ?? '';
    $validStatuses = ['available', 'occupied', 'maintenance', 'cleaning'];
    
    if ($roomId && in_array($newStatus, $validStatuses)) {
        $stmt = $db->prepare("UPDATE rooms SET status = ? WHERE room_id = ?");
        if ($stmt->execute([$newStatus, $roomId])) {
            $_SESSION['success'] = 'Room status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update room status';
        }
    }
    redirect('admin-rooms.php');
}

// Handle housekeeping status update
if (isset($_POST['update_housekeeping_status'])) {
    $roomId = $_POST['room_id'] ?? 0;
    $hkStatus = $_POST['housekeeping_status'] ?? '';
    $validHkStatuses = ['clean', 'dirty', 'inspected'];
    
    if ($roomId && in_array($hkStatus, $validHkStatuses)) {
        $stmt = $db->prepare("UPDATE rooms SET housekeeping_status = ? WHERE room_id = ?");
        if ($stmt->execute([$hkStatus, $roomId])) {
            $_SESSION['success'] = 'Housekeeping status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update housekeeping status';
        }
    }
    redirect('admin-rooms.php');
}

// Handle add/edit room
if (isset($_POST['save_room'])) {
    $roomId = $_POST['room_id'] ?? null;
    $categoryId = $_POST['category_id'] ?? '';
    $roomNumber = $_POST['room_number'] ?? '';
    $floor = $_POST['floor'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $housekeepingStatus = $_POST['housekeeping_status'] ?? 'clean';
    
    if ($categoryId && $roomNumber && $floor) {
        if ($roomId) {
            // Update existing room
            $stmt = $db->prepare("UPDATE rooms SET category_id = ?, room_number = ?, floor = ?, status = ?, housekeeping_status = ? WHERE room_id = ?");
            if ($stmt->execute([$categoryId, $roomNumber, $floor, $status, $housekeepingStatus, $roomId])) {
                $_SESSION['success'] = 'Room updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update room';
            }
        } else {
            // Add new room
            $stmt = $db->prepare("INSERT INTO rooms (category_id, room_number, floor, status, housekeeping_status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$categoryId, $roomNumber, $floor, $status, $housekeepingStatus])) {
                $_SESSION['success'] = 'Room added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add room. Room number may already exist.';
            }
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-rooms.php');
}

// Handle delete room
if (isset($_POST['delete_room'])) {
    $roomId = $_POST['room_id'] ?? 0;
    if ($roomId) {
        // Check if room has active bookings
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ('confirmed', 'checked_in')");
        $checkStmt->execute([$roomId]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Cannot delete room with active bookings';
        } else {
            $stmt = $db->prepare("DELETE FROM rooms WHERE room_id = ?");
            if ($stmt->execute([$roomId])) {
                $_SESSION['success'] = 'Room deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete room';
            }
        }
    }
    redirect('admin-rooms.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$floorFilter = $_GET['floor'] ?? '';

// Get all room categories
$categories = $db->query("SELECT * FROM room_categories ORDER BY category_name")->fetchAll();

// Get all floors
$floors = $db->query("SELECT DISTINCT floor FROM rooms ORDER BY floor")->fetchAll(PDO::FETCH_COLUMN);

// Build rooms query
$sql = "
    SELECT r.*, rc.category_name, rc.base_price
    FROM rooms r 
    JOIN room_categories rc ON r.category_id = rc.category_id 
    WHERE 1=1
";
$params = [];

if ($categoryFilter) {
    $sql .= " AND r.category_id = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($floorFilter) {
    $sql .= " AND r.floor = ?";
    $params[] = $floorFilter;
}

$sql .= " ORDER BY r.floor, r.room_number";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Get room counts by status
$statusCounts = $db->query("
    SELECT status, COUNT(*) as count 
    FROM rooms 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<!-- Rooms Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openRoomModal()" class="btn btn-primary">Add New Room</button>
        </div>
        <!-- Status Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #28a745;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $statusCounts['available'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Available</p>
                    </div>
                    <i class="fas fa-check-circle" style="font-size: 40px; color: #28a745;"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #dc3545;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $statusCounts['occupied'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Occupied</p>
                    </div>
                    <i class="fas fa-bed" style="font-size: 40px; color: #dc3545;"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #ffc107;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $statusCounts['maintenance'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Maintenance</p>
                    </div>
                    <i class="fas fa-tools" style="font-size: 40px; color: #ffc107;"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #17a2b8;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $statusCounts['cleaning'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Cleaning</p>
                    </div>
                    <i class="fas fa-broom" style="font-size: 40px; color: #17a2b8;"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $categoryFilter == $cat['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Statuses</option>
                        <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="occupied" <?php echo $statusFilter === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        <option value="cleaning" <?php echo $statusFilter === 'cleaning' ? 'selected' : ''; ?>>Cleaning</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Floor</label>
                    <select name="floor" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Floors</option>
                        <?php foreach ($floors as $floor): ?>
                        <option value="<?php echo $floor; ?>" <?php echo $floorFilter == $floor ? 'selected' : ''; ?>>Floor <?php echo $floor; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-rooms.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Rooms Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">All Rooms (<?php echo count($rooms); ?>)</h3>
            </div>
            
            <?php if (count($rooms) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Number</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Floor</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Base Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Housekeeping</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): 
                            $statusColors = [
                                'available' => ['#d4edda', '#155724'],
                                'occupied' => ['#f8d7da', '#721c24'],
                                'maintenance' => ['#fff3cd', '#856404'],
                                'cleaning' => ['#cce5ff', '#004085']
                            ];
                            $color = $statusColors[$room['status']] ?? ['#fff3cd', '#856404'];
                            
                            $hkColors = [
                                'clean' => ['#d4edda', '#155724', 'fa-check-circle'],
                                'dirty' => ['#f8d7da', '#721c24', 'fa-exclamation-triangle'],
                                'inspected' => ['#cce5ff', '#004085', 'fa-clipboard-check']
                            ];
                            $hk = $hkColors[$room['housekeeping_status']] ?? ['#fff3cd', '#856404', 'fa-question'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px; font-weight: 600;"><?php echo htmlspecialchars($room['room_number']); ?></td>
                            <td style="padding: 15px 20px;"><?php echo htmlspecialchars($room['category_name']); ?></td>
                            <td style="padding: 15px 20px;">Floor <?php echo $room['floor']; ?></td>
                            <td style="padding: 15px 20px;">₱<?php echo number_format($room['base_price']); ?></td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                    <select name="housekeeping_status" onchange="this.form.submit()" style="padding: 5px 10px; font-size: 12px; border: 1px solid var(--gray-light); border-radius: 4px; background-color: <?php echo $hk[0]; ?>; color: <?php echo $hk[1]; ?>">
                                        <option value="clean" <?php echo $room['housekeeping_status'] === 'clean' ? 'selected' : ''; ?>>Clean</option>
                                        <option value="dirty" <?php echo $room['housekeeping_status'] === 'dirty' ? 'selected' : ''; ?>>Dirty</option>
                                        <option value="inspected" <?php echo $room['housekeeping_status'] === 'inspected' ? 'selected' : ''; ?>>Inspected</option>
                                    </select>
                                    <input type="hidden" name="update_housekeeping_status" value="1">
                                </form>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <button type="button" onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <select name="room_status" onchange="this.form.submit()" style="padding: 5px 10px; font-size: 12px; border: 1px solid var(--gray-light); border-radius: 4px;">
                                            <option value="">Quick Update</option>
                                            <option value="available" <?php echo $room['status'] === 'available' ? 'disabled' : ''; ?>>Available</option>
                                            <option value="occupied" <?php echo $room['status'] === 'occupied' ? 'disabled' : ''; ?>>Occupied</option>
                                            <option value="maintenance" <?php echo $room['status'] === 'maintenance' ? 'disabled' : ''; ?>>Maintenance</option>
                                            <option value="cleaning" <?php echo $room['status'] === 'cleaning' ? 'disabled' : ''; ?>>Cleaning</option>
                                        </select>
                                        <input type="hidden" name="update_room_status" value="1">
                                    </form>
                                    
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this room?');">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button type="submit" name="delete_room" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-bed" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No rooms found</h3>
                <p style="color: #999;">Try adjusting your filters or add a new room</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Room Modal -->
<div id="roomModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Room</h3>
            <button onclick="closeRoomModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="room_id" id="room_id">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Room Category *</label>
                <select name="category_id" id="category_id" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?> (₱<?php echo number_format($cat['base_price']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Room Number *</label>
                <input type="text" name="room_number" id="room_number" required placeholder="e.g., 101, A-01" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Floor *</label>
                <input type="number" name="floor" id="floor" required min="1" placeholder="e.g., 1, 2, 3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="available">Available</option>
                    <option value="occupied">Occupied</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="cleaning">Cleaning</option>
                </select>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Housekeeping Status</label>
                <select name="housekeeping_status" id="housekeeping_status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="clean">Clean</option>
                    <option value="dirty">Dirty</option>
                    <option value="inspected">Inspected</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeRoomModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_room" class="btn btn-primary">Save Room</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRoomModal() {
    document.getElementById('roomModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Room';
    document.getElementById('room_id').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('room_number').value = '';
    document.getElementById('floor').value = '';
    document.getElementById('status').value = 'available';
}

function editRoom(room) {
    document.getElementById('roomModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Room';
    document.getElementById('room_id').value = room.room_id;
    document.getElementById('category_id').value = room.category_id;
    document.getElementById('room_number').value = room.room_number;
    document.getElementById('floor').value = room.floor;
    document.getElementById('status').value = room.status;
    document.getElementById('housekeeping_status').value = room.housekeeping_status || 'clean';
}

function closeRoomModal() {
    document.getElementById('roomModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('roomModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRoomModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
