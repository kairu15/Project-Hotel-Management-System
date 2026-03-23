<?php
$pageTitle = 'Manage Gallery - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit gallery item
if (isset($_POST['save_gallery'])) {
    $imageId = $_POST['image_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'hotel';
    $sortOrder = $_POST['sort_order'] ?? 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle image upload
    $imagePath = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/gallery/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $imagePath = 'images/gallery/' . $fileName;
        }
    }

    if ($title && ($imagePath || $imageId)) {
        if ($imageId) {
            // Update existing item
            if ($imagePath) {
                $stmt = $db->prepare("UPDATE gallery SET title = ?, description = ?, category = ?, image_path = ?, sort_order = ?, is_featured = ? WHERE image_id = ?");
                $stmt->execute([$title, $description, $category, $imagePath, $sortOrder, $isFeatured, $imageId]);
            } else {
                $stmt = $db->prepare("UPDATE gallery SET title = ?, description = ?, category = ?, sort_order = ?, is_featured = ? WHERE image_id = ?");
                $stmt->execute([$title, $description, $category, $sortOrder, $isFeatured, $imageId]);
            }
            $_SESSION['success'] = 'Gallery item updated successfully';
        } else {
            // Add new item
            if ($imagePath) {
                $stmt = $db->prepare("INSERT INTO gallery (title, description, category, image_path, sort_order, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $category, $imagePath, $sortOrder, $isFeatured]);
                $_SESSION['success'] = 'Gallery item added successfully';
            } else {
                $_SESSION['error'] = 'Please upload an image';
            }
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-gallery.php');
}

// Handle delete gallery item
if (isset($_POST['delete_gallery'])) {
    $imageId = $_POST['image_id'] ?? 0;
    if ($imageId) {
        $stmt = $db->prepare("DELETE FROM gallery WHERE image_id = ?");
        if ($stmt->execute([$imageId])) {
            $_SESSION['success'] = 'Gallery item deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete gallery item';
        }
    }
    redirect('admin-gallery.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$featuredFilter = $_GET['featured'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM gallery WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($featuredFilter !== '') {
    $sql .= " AND is_featured = ?";
    $params[] = $featuredFilter;
}

if ($search) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY sort_order ASC, uploaded_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$galleryItems = $stmt->fetchAll();

// Categories for filter
$categories = ['rooms', 'dining', 'amenities', 'events', 'attractions', 'hotel'];

// Category counts
$categoryCounts = $db->query("SELECT category, COUNT(*) as count FROM gallery GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openGalleryModal()" class="btn btn-primary">Add New Image</button>
        </div>

        <!-- Category Cards -->
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php foreach ($categories as $cat): ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo $categoryCounts[$cat] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0; text-transform: capitalize;"><?php echo $cat; ?></p>
                    </div>
                    <i class="fas fa-image" style="font-size: 28px; color: var(--primary-color);"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search gallery..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
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
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Featured</label>
                    <select name="featured" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="1" <?php echo $featuredFilter === '1' ? 'selected' : ''; ?>>Featured</option>
                        <option value="0" <?php echo $featuredFilter === '0' ? 'selected' : ''; ?>>Not Featured</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-gallery.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Gallery Grid -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Gallery Images (<?php echo count($galleryItems); ?>)</h3>
            </div>

            <?php if (count($galleryItems) > 0): ?>
            <div style="padding: 30px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                    <?php foreach ($galleryItems as $item): ?>
                    <div style="background-color: var(--gray-light); border-radius: 10px; overflow: hidden; position: relative;">
                        <?php if ($item['is_featured']): ?>
                        <div style="position: absolute; top: 10px; left: 10px; background-color: var(--warning-color); color: #856404; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: 600; z-index: 1;">
                            <i class="fas fa-star"></i> Featured
                        </div>
                        <?php endif; ?>
                        <div style="position: absolute; top: 10px; right: 10px; background-color: var(--primary-color); color: white; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: 600; z-index: 1; text-transform: capitalize;">
                            <?php echo $item['category']; ?>
                        </div>
                        <img src="../assets/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 15px;">
                            <h4 style="font-size: 16px; margin-bottom: 5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($item['title'] ?: 'Untitled'); ?></h4>
                            <p style="font-size: 12px; color: #666; margin-bottom: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 12px; color: #999;">Order: <?php echo $item['sort_order']; ?></span>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" onclick="editGallery(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                        <input type="hidden" name="image_id" value="<?php echo $item['image_id']; ?>">
                                        <button type="submit" name="delete_gallery" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-images" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No gallery images found</h3>
                <p style="color: #999;">Add your first gallery image</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Gallery Modal -->
<div id="galleryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Gallery Image</h3>
            <button onclick="closeGalleryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="image_id" id="image_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Title</label>
                <input type="text" name="title" id="title" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category *</label>
                    <select name="category" id="category" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Image * <span id="imageHint" style="font-weight: normal; color: #666;">(leave blank to keep current)</span></label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_featured" id="is_featured" value="1" style="width: 18px; height: 18px;">
                    <span style="font-size: 14px;">Featured Image</span>
                </label>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeGalleryModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_gallery" class="btn btn-primary">Save Image</button>
            </div>
        </form>
    </div>
</div>

<script>
function openGalleryModal() {
    document.getElementById('galleryModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Gallery Image';
    document.getElementById('image_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('category').value = 'hotel';
    document.getElementById('sort_order').value = '0';
    document.getElementById('is_featured').checked = false;
    document.getElementById('image').required = true;
    document.getElementById('imageHint').style.display = 'none';
}

function editGallery(item) {
    document.getElementById('galleryModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Gallery Image';
    document.getElementById('image_id').value = item.image_id;
    document.getElementById('title').value = item.title || '';
    document.getElementById('description').value = item.description || '';
    document.getElementById('category').value = item.category;
    document.getElementById('sort_order').value = item.sort_order || 0;
    document.getElementById('is_featured').checked = item.is_featured == 1;
    document.getElementById('image').required = false;
    document.getElementById('imageHint').style.display = 'inline';
}

function closeGalleryModal() {
    document.getElementById('galleryModal').style.display = 'none';
}

document.getElementById('galleryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeGalleryModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
