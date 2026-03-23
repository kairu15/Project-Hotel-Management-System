<?php
$pageTitle = 'Manage Room Categories - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit room category
if (isset($_POST['save_category'])) {
    $categoryId = $_POST['category_id'] ?? null;
    $categoryName = $_POST['category_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $basePrice = $_POST['base_price'] ?? 0;
    $maxOccupancy = $_POST['max_occupancy'] ?? 1;
    $bedType = $_POST['bed_type'] ?? '';
    $roomSizeSqm = $_POST['room_size_sqm'] ?? null;
    $amenities = $_POST['amenities'] ?? '';
    $status = $_POST['status'] ?? 'active';

    // Handle primary image upload
    $imagePrimary = '';
    if (!empty($_FILES['image_primary']['name'])) {
        $uploadDir = '../assets/images/rooms/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_primary_' . basename($_FILES['image_primary']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image_primary']['tmp_name'], $uploadFile)) {
            $imagePrimary = 'images/rooms/' . $fileName;
        }
    }

    if ($categoryName && $basePrice > 0) {
        if ($categoryId) {
            // Update existing category
            if ($imagePrimary) {
                $stmt = $db->prepare("UPDATE room_categories SET category_name = ?, description = ?, base_price = ?, max_occupancy = ?, bed_type = ?, room_size_sqm = ?, amenities = ?, image_primary = ?, status = ? WHERE category_id = ?");
                $stmt->execute([$categoryName, $description, $basePrice, $maxOccupancy, $bedType, $roomSizeSqm, $amenities, $imagePrimary, $status, $categoryId]);
            } else {
                $stmt = $db->prepare("UPDATE room_categories SET category_name = ?, description = ?, base_price = ?, max_occupancy = ?, bed_type = ?, room_size_sqm = ?, amenities = ?, status = ? WHERE category_id = ?");
                $stmt->execute([$categoryName, $description, $basePrice, $maxOccupancy, $bedType, $roomSizeSqm, $amenities, $status, $categoryId]);
            }
            $_SESSION['success'] = 'Room category updated successfully';
        } else {
            // Add new category
            $stmt = $db->prepare("INSERT INTO room_categories (category_name, description, base_price, max_occupancy, bed_type, room_size_sqm, amenities, image_primary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$categoryName, $description, $basePrice, $maxOccupancy, $bedType, $roomSizeSqm, $amenities, $imagePrimary, $status]);
            $_SESSION['success'] = 'Room category added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-room-categories.php');
}

// Handle delete category
if (isset($_POST['delete_category'])) {
    $categoryId = $_POST['category_id'] ?? 0;
    if ($categoryId) {
        // Check if category has rooms
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM rooms WHERE category_id = ?");
        $checkStmt->execute([$categoryId]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Cannot delete category that has rooms assigned';
        } else {
            $stmt = $db->prepare("DELETE FROM room_categories WHERE category_id = ?");
            if ($stmt->execute([$categoryId])) {
                $_SESSION['success'] = 'Room category deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete room category';
            }
        }
    }
    redirect('admin-room-categories.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT rc.*, (SELECT COUNT(*) FROM rooms WHERE category_id = rc.category_id) as room_count FROM room_categories rc WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND rc.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (rc.category_name LIKE ? OR rc.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY rc.base_price ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openCategoryModal()" class="btn btn-primary">Add New Category</button>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search categories..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-room-categories.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Categories Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Room Categories (<?php echo count($categories); ?>)</h3>
            </div>

            <?php if (count($categories) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Occupancy</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Rooms</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($category['image_primary']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($category['image_primary']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 60px; height: 60px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-bed" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($category['category_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($category['bed_type']); ?>, <?php echo $category['room_size_sqm']; ?> sqm</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($category['base_price']); ?>/night
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $category['max_occupancy']; ?> guests
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600;"><?php echo $category['room_count']; ?></span> rooms
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $category['status'] === 'active' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $category['status'] === 'active' ? '#155724' : '#721c24'; ?>; text-transform: capitalize;">
                                    <?php echo $category['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                        <button type="submit" name="delete_category" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <h3 style="color: #666;">No room categories found</h3>
                <p style="color: #999;">Add your first room category</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Category Modal -->
<div id="categoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Room Category</h3>
            <button onclick="closeCategoryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="category_id" id="category_id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category Name *</label>
                    <input type="text" name="category_name" id="category_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Base Price per Night *</label>
                    <input type="number" name="base_price" id="base_price" step="0.01" min="0" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Max Occupancy</label>
                    <input type="number" name="max_occupancy" id="max_occupancy" min="1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Bed Type</label>
                    <input type="text" name="bed_type" id="bed_type" placeholder="e.g., King Bed" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Room Size (sqm)</label>
                    <input type="number" name="room_size_sqm" id="room_size_sqm" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Amenities (comma-separated)</label>
                <input type="text" name="amenities" id="amenities" placeholder="WiFi, TV, Air Conditioning, Mini Bar..." style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Primary Image</label>
                <input type="file" name="image_primary" id="image_primary" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeCategoryModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_category" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCategoryModal() {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Room Category';
    document.getElementById('category_id').value = '';
    document.getElementById('category_name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('base_price').value = '';
    document.getElementById('max_occupancy').value = '';
    document.getElementById('bed_type').value = '';
    document.getElementById('room_size_sqm').value = '';
    document.getElementById('amenities').value = '';
    document.getElementById('status').value = 'active';
}

function editCategory(category) {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Room Category';
    document.getElementById('category_id').value = category.category_id;
    document.getElementById('category_name').value = category.category_name;
    document.getElementById('description').value = category.description || '';
    document.getElementById('base_price').value = category.base_price;
    document.getElementById('max_occupancy').value = category.max_occupancy || '';
    document.getElementById('bed_type').value = category.bed_type || '';
    document.getElementById('room_size_sqm').value = category.room_size_sqm || '';
    document.getElementById('amenities').value = category.amenities || '';
    document.getElementById('status').value = category.status;
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCategoryModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
