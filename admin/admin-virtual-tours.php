<?php
$pageTitle = 'Manage Virtual Tours - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit virtual tour
if (isset($_POST['save_tour'])) {
    $tourId = $_POST['tour_id'] ?? null;
    $categoryId = $_POST['category_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $isActive = $_POST['is_active'] ?? 1;
    $displayOrder = $_POST['display_order'] ?? 0;

    // Handle panorama image upload
    $panoramaImage = '';
    if (!empty($_FILES['panorama_image']['name'])) {
        $uploadDir = '../assets/uploads/virtual_tours/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_panorama_' . basename($_FILES['panorama_image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['panorama_image']['tmp_name'], $uploadFile)) {
            $panoramaImage = 'uploads/virtual_tours/' . $fileName;
        }
    }

    // Handle thumbnail image upload
    $thumbnailImage = '';
    if (!empty($_FILES['thumbnail_image']['name'])) {
        $uploadDir = '../assets/uploads/virtual_tours/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_thumb_' . basename($_FILES['thumbnail_image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $uploadFile)) {
            $thumbnailImage = 'uploads/virtual_tours/' . $fileName;
        }
    }

    if ($categoryId && $title) {
        if ($tourId) {
            // Update existing tour
            $sql = "UPDATE room_virtual_tours SET category_id = ?, title = ?, description = ?, is_active = ?, display_order = ?";
            $params = [$categoryId, $title, $description, $isActive, $displayOrder];
            
            if ($panoramaImage) {
                $sql .= ", panorama_image = ?";
                $params[] = $panoramaImage;
            }
            if ($thumbnailImage) {
                $sql .= ", thumbnail_image = ?";
                $params[] = $thumbnailImage;
            }
            $sql .= " WHERE tour_id = ?";
            $params[] = $tourId;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['success'] = 'Virtual tour updated successfully';
        } else {
            // Add new tour
            if ($panoramaImage) {
                $stmt = $db->prepare("INSERT INTO room_virtual_tours (category_id, panorama_image, thumbnail_image, title, description, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$categoryId, $panoramaImage, $thumbnailImage, $title, $description, $isActive, $displayOrder]);
                $_SESSION['success'] = 'Virtual tour added successfully';
            } else {
                $_SESSION['error'] = 'Panorama image is required';
            }
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-virtual-tours.php');
}

// Handle delete tour
if (isset($_POST['delete_tour'])) {
    $tourId = $_POST['tour_id'] ?? 0;
    if ($tourId) {
        $stmt = $db->prepare("DELETE FROM room_virtual_tours WHERE tour_id = ?");
        if ($stmt->execute([$tourId])) {
            $_SESSION['success'] = 'Virtual tour deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete virtual tour';
        }
    }
    redirect('admin-virtual-tours.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT vt.*, rc.category_name 
        FROM room_virtual_tours vt 
        LEFT JOIN room_categories rc ON vt.category_id = rc.category_id 
        WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND vt.category_id = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND vt.is_active = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (vt.title LIKE ? OR rc.category_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY vt.display_order ASC, vt.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tours = $stmt->fetchAll();

// Get room categories for dropdown
$categoryStmt = $db->query("SELECT category_id, category_name FROM room_categories WHERE status = 'active' ORDER BY category_name");
$categories = $categoryStmt->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openTourModal()" class="btn btn-primary">Add Virtual Tour</button>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search virtual tours..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Room Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo $categoryFilter == $category['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-virtual-tours.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Tours Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Virtual Tours (<?php echo count($tours); ?>)</h3>
            </div>

            <?php if (count($tours) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Tour</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Room Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Order</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tours as $tour): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($tour['thumbnail_image']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($tour['thumbnail_image']); ?>" alt="" style="width: 80px; height: 60px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 80px; height: 60px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-vr-cardboard" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($tour['title']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($tour['description'], 0, 50)); ?>...</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo htmlspecialchars($tour['category_name']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $tour['display_order']; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $tour['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $tour['is_active'] ? '#155724' : '#721c24'; ?>">
                                    <?php echo $tour['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <a href="admin-virtual-tour-hotspots.php?tour_id=<?php echo $tour['tour_id']; ?>" class="btn btn-sm btn-secondary" style="padding: 5px 12px; font-size: 12px;">Hotspots</a>
                                    <button type="button" onclick="editTour(<?php echo htmlspecialchars(json_encode($tour)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this virtual tour?');">
                                        <input type="hidden" name="tour_id" value="<?php echo $tour['tour_id']; ?>">
                                        <button type="submit" name="delete_tour" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-vr-cardboard" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No virtual tours found</h3>
                <p style="color: #999;">Add your first virtual tour</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Tour Modal -->
<div id="tourModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add Virtual Tour</h3>
            <button onclick="closeTourModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="tour_id" id="tour_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Room Category *</label>
                <select name="category_id" id="category_id" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Room Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['category_id']; ?>">
                        <?php echo htmlspecialchars($category['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Title *</label>
                <input type="text" name="title" id="title" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">360° Panorama Image (Equirectangular) *</label>
                <input type="file" name="panorama_image" id="panorama_image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                <small style="color: #666; display: block; margin-top: 5px;">Recommended: 4096x2048px or higher, 2:1 aspect ratio</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Thumbnail Image</label>
                <input type="file" name="thumbnail_image" id="thumbnail_image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Display Order</label>
                    <input type="number" name="display_order" id="display_order" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                    <select name="is_active" id="is_active" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeTourModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_tour" class="btn btn-primary">Save Tour</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTourModal() {
    document.getElementById('tourModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add Virtual Tour';
    document.getElementById('tour_id').value = '';
    document.getElementById('category_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('panorama_image').value = '';
    document.getElementById('thumbnail_image').value = '';
    document.getElementById('display_order').value = '0';
    document.getElementById('is_active').value = '1';
}

function editTour(tour) {
    document.getElementById('tourModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Virtual Tour';
    document.getElementById('tour_id').value = tour.tour_id;
    document.getElementById('category_id').value = tour.category_id;
    document.getElementById('title').value = tour.title;
    document.getElementById('description').value = tour.description || '';
    document.getElementById('panorama_image').value = '';
    document.getElementById('thumbnail_image').value = '';
    document.getElementById('display_order').value = tour.display_order || 0;
    document.getElementById('is_active').value = tour.is_active;
}

function closeTourModal() {
    document.getElementById('tourModal').style.display = 'none';
}

document.getElementById('tourModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTourModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
