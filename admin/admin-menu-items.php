<?php
$pageTitle = 'Manage Menu Items - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit item
if (isset($_POST['save_item'])) {
    $itemId = $_POST['item_id'] ?? null;
    $itemName = $_POST['item_name'] ?? '';
    $catId = $_POST['cat_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $isSpecial = isset($_POST['is_special']) ? 1 : 0;
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $dietaryInfo = $_POST['dietary_info'] ?? '';

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/menu/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/menu/' . $fileName;
        }
    }

    if ($itemName && $catId) {
        if ($itemId) {
            // Update existing item
            if ($image) {
                $stmt = $db->prepare("UPDATE menu_items SET item_name = ?, cat_id = ?, description = ?, price = ?, is_special = ?, is_available = ?, dietary_info = ?, image = ? WHERE item_id = ?");
                $stmt->execute([$itemName, $catId, $description, $price, $isSpecial, $isAvailable, $dietaryInfo, $image, $itemId]);
            } else {
                $stmt = $db->prepare("UPDATE menu_items SET item_name = ?, cat_id = ?, description = ?, price = ?, is_special = ?, is_available = ?, dietary_info = ? WHERE item_id = ?");
                $stmt->execute([$itemName, $catId, $description, $price, $isSpecial, $isAvailable, $dietaryInfo, $itemId]);
            }
            $_SESSION['success'] = 'Menu item updated successfully';
        } else {
            // Add new item
            $stmt = $db->prepare("INSERT INTO menu_items (item_name, cat_id, description, price, is_special, is_available, dietary_info, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$itemName, $catId, $description, $price, $isSpecial, $isAvailable, $dietaryInfo, $image]);
            $_SESSION['success'] = 'Menu item added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-menu-items.php');
}

// Handle delete item
if (isset($_POST['delete_item'])) {
    $itemId = $_POST['item_id'] ?? 0;
    if ($itemId) {
        $stmt = $db->prepare("DELETE FROM menu_items WHERE item_id = ?");
        if ($stmt->execute([$itemId])) {
            $_SESSION['success'] = 'Menu item deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete menu item';
        }
    }
    redirect('admin-menu-items.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$specialFilter = $_GET['special'] ?? '';
$availableFilter = $_GET['available'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT mi.*, mc.category_name
    FROM menu_items mi
    JOIN menu_categories mc ON mi.cat_id = mc.cat_id
    WHERE 1=1
";
$params = [];

if ($categoryFilter) {
    $sql .= " AND mi.cat_id = ?";
    $params[] = $categoryFilter;
}

if ($specialFilter !== '') {
    $sql .= " AND mi.is_special = ?";
    $params[] = $specialFilter;
}

if ($availableFilter !== '') {
    $sql .= " AND mi.is_available = ?";
    $params[] = $availableFilter;
}

if ($search) {
    $sql .= " AND (mi.item_name LIKE ? OR mi.description LIKE ? OR mi.dietary_info LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY mc.sort_order, mi.item_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT cat_id, category_name FROM menu_categories ORDER BY sort_order, category_name")->fetchAll();

// Calculate stats
$totalItems = count($items);
$specialItems = count(array_filter($items, function($item) { return $item['is_special']; }));
$availableItems = count(array_filter($items, function($item) { return $item['is_available']; }));

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <a href="admin-menu-categories.php" class="btn btn-outline">Manage Categories</a>
            <button type="button" onclick="openItemModal()" class="btn btn-primary">Add New Item</button>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $totalItems; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Items</p>
                    </div>
                    <i class="fas fa-utensils" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $specialItems; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Special Items</p>
                    </div>
                    <i class="fas fa-star" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $availableItems; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Available</p>
                    </div>
                    <i class="fas fa-check-circle" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search menu items..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['cat_id']; ?>" <?php echo $categoryFilter == $cat['cat_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Special</label>
                    <select name="special" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="1" <?php echo $specialFilter === '1' ? 'selected' : ''; ?>>Special</option>
                        <option value="0" <?php echo $specialFilter === '0' ? 'selected' : ''; ?>>Regular</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Available</label>
                    <select name="available" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="1" <?php echo $availableFilter === '1' ? 'selected' : ''; ?>>Available</option>
                        <option value="0" <?php echo $availableFilter === '0' ? 'selected' : ''; ?>>Not Available</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-menu-items.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Items Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Menu Items (<?php echo count($items); ?>)</h3>
            </div>

            <?php if (count($items) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Item</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Special</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Available</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($item['image']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 50px; height: 50px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-utensils" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($item['description'], 0, 40)) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2;">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($item['price']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($item['is_special']): ?>
                                <span style="color: var(--warning-color);"><i class="fas fa-star"></i> Special</span>
                                <?php else: ?>
                                <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $item['is_available'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $item['is_available'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $item['is_available'] ? 'Available' : 'Not Available'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" name="delete_item" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-utensils" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No menu items found</h3>
                <p style="color: #999;">Add your first menu item</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Item Modal -->
<div id="itemModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Menu Item</h3>
            <button onclick="closeItemModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="item_id" id="item_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Item Name *</label>
                <input type="text" name="item_name" id="item_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category *</label>
                <select name="cat_id" id="cat_id" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['cat_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="2" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Price (PHP) *</label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Dietary Info</label>
                    <input type="text" name="dietary_info" id="dietary_info" placeholder="e.g., Vegan, Gluten-free" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Image <span id="imageHint" style="font-weight: normal; color: #666;">(leave blank to keep current)</span></label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_special" id="is_special" value="1" style="width: 18px; height: 18px;">
                        <span style="font-size: 14px;"><i class="fas fa-star" style="color: var(--warning-color);"></i> Special Item</span>
                    </label>
                </div>
                <div>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_available" id="is_available" value="1" checked style="width: 18px; height: 18px;">
                        <span style="font-size: 14px;">Available</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeItemModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_item" class="btn btn-primary">Save Item</button>
            </div>
        </form>
    </div>
</div>

<script>
function openItemModal() {
    document.getElementById('itemModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Menu Item';
    document.getElementById('item_id').value = '';
    document.getElementById('item_name').value = '';
    document.getElementById('cat_id').value = '';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '';
    document.getElementById('dietary_info').value = '';
    document.getElementById('is_special').checked = false;
    document.getElementById('is_available').checked = true;
    document.getElementById('image').required = false;
    document.getElementById('imageHint').style.display = 'none';
}

function editItem(item) {
    document.getElementById('itemModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Menu Item';
    document.getElementById('item_id').value = item.item_id;
    document.getElementById('item_name').value = item.item_name;
    document.getElementById('cat_id').value = item.cat_id;
    document.getElementById('description').value = item.description || '';
    document.getElementById('price').value = item.price;
    document.getElementById('dietary_info').value = item.dietary_info || '';
    document.getElementById('is_special').checked = item.is_special == 1;
    document.getElementById('is_available').checked = item.is_available == 1;
    document.getElementById('image').required = false;
    document.getElementById('imageHint').style.display = 'inline';
}

function closeItemModal() {
    document.getElementById('itemModal').style.display = 'none';
}

document.getElementById('itemModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeItemModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
