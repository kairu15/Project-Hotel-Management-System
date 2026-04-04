<?php
$pageTitle = 'Manage Amenities - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit amenity
if (isset($_POST['save_amenity'])) {
    $amenityId = $_POST['amenity_id'] ?? null;
    $amenityName = $_POST['amenity_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $durationMinutes = $_POST['duration_minutes'] ?? null;
    $operatingHours = $_POST['operating_hours'] ?? '';
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/amenities/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/amenities/' . $fileName;
        }
    }

    if ($amenityName && $category) {
        if ($amenityId) {
            // Update existing amenity
            if ($image) {
                $stmt = $db->prepare("UPDATE amenities SET amenity_name = ?, category = ?, description = ?, price = ?, duration_minutes = ?, operating_hours = ?, image = ?, is_available = ? WHERE amenity_id = ?");
                $stmt->execute([$amenityName, $category, $description, $price, $durationMinutes, $operatingHours, $image, $isAvailable, $amenityId]);
            } else {
                $stmt = $db->prepare("UPDATE amenities SET amenity_name = ?, category = ?, description = ?, price = ?, duration_minutes = ?, operating_hours = ?, is_available = ? WHERE amenity_id = ?");
                $stmt->execute([$amenityName, $category, $description, $price, $durationMinutes, $operatingHours, $isAvailable, $amenityId]);
            }
            $_SESSION['success'] = 'Amenity "' . $amenityName . '" updated successfully';
        } else {
            // Add new amenity
            $stmt = $db->prepare("INSERT INTO amenities (amenity_name, category, description, price, duration_minutes, operating_hours, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$amenityName, $category, $description, $price, $durationMinutes, $operatingHours, $image, $isAvailable]);
            $_SESSION['success'] = 'Amenity "' . $amenityName . '" added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-amenities.php');
}

// Handle delete amenity
if (isset($_POST['delete_amenity'])) {
    $amenityId = $_POST['amenity_id'] ?? 0;
    if ($amenityId) {
        // Get amenity name before deletion
        $nameStmt = $db->prepare("SELECT amenity_name FROM amenities WHERE amenity_id = ?");
        $nameStmt->execute([$amenityId]);
        $amenityName = $nameStmt->fetchColumn() ?? 'Amenity';
        
        $stmt = $db->prepare("DELETE FROM amenities WHERE amenity_id = ?");
        if ($stmt->execute([$amenityId])) {
            $_SESSION['success'] = 'Amenity "' . $amenityName . '" deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete amenity';
        }
    }
    redirect('admin-amenities.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM amenities WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($search) {
    $sql .= " AND (amenity_name LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY category, amenity_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$amenities = $stmt->fetchAll();

// Get category counts
$categoryCounts = $db->query("SELECT category, COUNT(*) as count FROM amenities GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

// Categories for dropdown
$categories = ['spa', 'gym', 'pool', 'wellness', 'other'];

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openAmenityModal()" class="btn btn-primary">Add New Amenity</button>
        </div>

        <!-- Category Cards -->
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php foreach ($categories as $cat): ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo $categoryCounts[$cat] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0; text-transform: capitalize;"><?php echo $cat; ?></p>
                    </div>
                    <i class="fas fa-spa" style="font-size: 30px; color: var(--primary-color);"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search amenities..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-amenities.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Amenities Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">All Amenities (<?php echo count($amenities); ?>)</h3>
            </div>

            <?php if (count($amenities) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Amenity</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Duration</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($amenities as $amenity): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($amenity['image']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($amenity['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 50px; height: 50px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($amenity['amenity_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($amenity['description'], 0, 50)) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2; text-transform: capitalize;">
                                    <?php echo $amenity['category']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $amenity['price'] > 0 ? formatPrice($amenity['price']) : 'Free'; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $amenity['duration_minutes'] ? $amenity['duration_minutes'] . ' min' : 'N/A'; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $amenity['is_available'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $amenity['is_available'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $amenity['is_available'] ? 'Available' : 'Not Available'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editAmenity(<?php echo htmlspecialchars(json_encode($amenity)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" id="deleteAmenityForm<?php echo $amenity['amenity_id']; ?>">
                                        <input type="hidden" name="amenity_id" value="<?php echo $amenity['amenity_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('deleteAmenityForm<?php echo $amenity['amenity_id']; ?>', 'Delete Amenity', 'Are you sure you want to delete &quot;<?php echo htmlspecialchars($amenity['amenity_name']); ?>&quot;?', null, 'delete_amenity')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-spa" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No amenities found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Amenity Modal -->
<div id="amenityModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Amenity</h3>
            <button onclick="closeAmenityModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="amenity_id" id="amenity_id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Amenity Name *</label>
                    <input type="text" name="amenity_name" id="amenity_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category *</label>
                    <select name="category" id="category" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Price (PHP)</label>
                    <input type="number" name="price" id="price" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Operating Hours</label>
                <input type="text" name="operating_hours" id="operating_hours" placeholder="e.g., 9:00 AM - 9:00 PM" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Image</label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_available" id="is_available" value="1" checked style="width: 18px; height: 18px;">
                    <span style="font-size: 14px;">Available</span>
                </label>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeAmenityModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_amenity" class="btn btn-primary">Save Amenity</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAmenityModal() {
    document.getElementById('amenityModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Amenity';
    document.getElementById('amenity_id').value = '';
    document.getElementById('amenity_name').value = '';
    document.getElementById('category').value = 'spa';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '';
    document.getElementById('duration_minutes').value = '';
    document.getElementById('operating_hours').value = '';
    document.getElementById('is_available').checked = true;
}

function editAmenity(amenity) {
    document.getElementById('amenityModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Amenity';
    document.getElementById('amenity_id').value = amenity.amenity_id;
    document.getElementById('amenity_name').value = amenity.amenity_name;
    document.getElementById('category').value = amenity.category;
    document.getElementById('description').value = amenity.description || '';
    document.getElementById('price').value = amenity.price || '';
    document.getElementById('duration_minutes').value = amenity.duration_minutes || '';
    document.getElementById('operating_hours').value = amenity.operating_hours || '';
    document.getElementById('is_available').checked = amenity.is_available == 1;
}

function closeAmenityModal() {
    document.getElementById('amenityModal').style.display = 'none';
}

document.getElementById('amenityModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAmenityModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
