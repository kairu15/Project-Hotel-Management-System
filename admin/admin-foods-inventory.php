<?php
$pageTitle = 'Foods Inventory';
require_once '../includes/config.php';

// Check if user is admin or has permission
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit food item - MUST be before header include
if (isset($_POST['save_food'])) {
    $foodId = $_POST['food_id'] ?? null;
    $foodName = $_POST['food_name'] ?? '';
    $categoryId = $_POST['category_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $costPrice = $_POST['cost_price'] ?? 0;
    $isSpecial = isset($_POST['is_special']) ? 1 : 0;
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $dietaryInfo = $_POST['dietary_info'] ?? '';
    $prepTime = $_POST['prep_time_minutes'] ?? 20;
    $stockQuantity = $_POST['stock_quantity'] ?? 0;

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/foods/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/foods/' . $fileName;
        }
    }

    if ($foodName && $categoryId) {
        if ($foodId) {
            // Update existing food item
            if ($image) {
                $stmt = $db->prepare("UPDATE foods SET food_name = ?, category_id = ?, description = ?, price = ?, cost_price = ?, is_special = ?, is_available = ?, dietary_info = ?, prep_time_minutes = ?, stock_quantity = ?, image = ? WHERE food_id = ?");
                $stmt->execute([$foodName, $categoryId, $description, $price, $costPrice, $isSpecial, $isAvailable, $dietaryInfo, $prepTime, $stockQuantity, $image, $foodId]);
            } else {
                $stmt = $db->prepare("UPDATE foods SET food_name = ?, category_id = ?, description = ?, price = ?, cost_price = ?, is_special = ?, is_available = ?, dietary_info = ?, prep_time_minutes = ?, stock_quantity = ? WHERE food_id = ?");
                $stmt->execute([$foodName, $categoryId, $description, $price, $costPrice, $isSpecial, $isAvailable, $dietaryInfo, $prepTime, $stockQuantity, $foodId]);
            }
            showAlert('Food item updated successfully', 'success');
        } else {
            // Add new food item
            $stmt = $db->prepare("INSERT INTO foods (food_name, category_id, description, price, cost_price, is_special, is_available, dietary_info, prep_time_minutes, stock_quantity, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$foodName, $categoryId, $description, $price, $costPrice, $isSpecial, $isAvailable, $dietaryInfo, $prepTime, $stockQuantity, $image]);
            showAlert('Food item added successfully', 'success');
        }
    } else {
        showAlert('Please fill in all required fields', 'danger');
    }
    redirect('admin-foods-inventory.php');
}

// Handle delete food item - MUST be before header include
if (isset($_POST['delete_food'])) {
    $foodId = $_POST['food_id'] ?? 0;
    if ($foodId) {
        $stmt = $db->prepare("DELETE FROM foods WHERE food_id = ?");
        if ($stmt->execute([$foodId])) {
            showAlert('Food item deleted successfully', 'success');
        } else {
            showAlert('Failed to delete food item', 'danger');
        }
    }
    redirect('admin-foods-inventory.php');
}

// Now include header after all POST handling
require_once '../includes/admin-header.php';

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$specialFilter = $_GET['special'] ?? '';
$availableFilter = $_GET['available'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT f.*, mc.category_name
    FROM foods f
    JOIN menu_categories mc ON f.category_id = mc.cat_id
    WHERE 1=1
";
$params = [];

if ($categoryFilter) {
    $sql .= " AND f.category_id = ?";
    $params[] = $categoryFilter;
}

if ($specialFilter !== '') {
    $sql .= " AND f.is_special = ?";
    $params[] = $specialFilter;
}

if ($availableFilter !== '') {
    $sql .= " AND f.is_available = ?";
    $params[] = $availableFilter;
}

if ($search) {
    $sql .= " AND (f.food_name LIKE ? OR f.description LIKE ? OR f.dietary_info LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY mc.sort_order, f.food_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$foods = $stmt->fetchAll();

// Get categories for filter and form
$categories = $db->query("SELECT cat_id, category_name FROM menu_categories ORDER BY sort_order, category_name")->fetchAll();

// Calculate stats
$totalItems = count($foods);
$specialItems = count(array_filter($foods, function($item) { return $item['is_special']; }));
$availableItems = count(array_filter($foods, function($item) { return $item['is_available']; }));
$lowStockItems = count(array_filter($foods, function($item) { return $item['stock_quantity'] <= 10 && $item['stock_quantity'] > 0; }));
$outOfStockItems = count(array_filter($foods, function($item) { return $item['stock_quantity'] == 0; }));

// Dietary options
$dietaryOptions = ['Vegan', 'Vegetarian', 'Gluten-Free', 'Dairy-Free', 'Nut-Free', 'Spicy', 'Halal', 'Kosher'];
?>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $totalItems; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Total Items</p>
            </div>
            <i class="fas fa-utensils" style="font-size: 28px; color: var(--primary-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $specialItems; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Special Items</p>
            </div>
            <i class="fas fa-star" style="font-size: 28px; color: var(--warning-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $availableItems; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Available</p>
            </div>
            <i class="fas fa-check-circle" style="font-size: 28px; color: var(--success-color);"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #ffc107;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $lowStockItems; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Low Stock</p>
            </div>
            <i class="fas fa-exclamation-triangle" style="font-size: 28px; color: #ffc107;"></i>
        </div>
    </div>
    <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="font-size: 28px; margin: 0;"><?php echo $outOfStockItems; ?></h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 13px;">Out of Stock</p>
            </div>
            <i class="fas fa-times-circle" style="font-size: 28px; color: var(--danger-color);"></i>
        </div>
    </div>
</div>

<!-- Filters -->
<div style="background-color: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search food items..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Category</label>
            <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['cat_id']; ?>" <?php echo $categoryFilter == $cat['cat_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Special</label>
            <select name="special" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All</option>
                <option value="1" <?php echo $specialFilter === '1' ? 'selected' : ''; ?>>Special</option>
                <option value="0" <?php echo $specialFilter === '0' ? 'selected' : ''; ?>>Regular</option>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">Availability</label>
            <select name="available" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                <option value="">All</option>
                <option value="1" <?php echo $availableFilter === '1' ? 'selected' : ''; ?>>Available</option>
                <option value="0" <?php echo $availableFilter === '0' ? 'selected' : ''; ?>>Not Available</option>
            </select>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px;"><i class="fas fa-filter"></i> Filter</button>
            <a href="admin-foods-inventory.php" class="btn btn-outline" style="padding: 10px 20px;"><i class="fas fa-times"></i></a>
        </div>
    </form>
</div>

<!-- Food Items Table -->
<div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
    <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-size: 18px;"><i class="fas fa-boxes" style="color: var(--primary-color); margin-right: 10px;"></i>Food Inventory</h3>
        <button type="button" onclick="openFoodModal()" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Food Item</button>
    </div>
    
    <?php if (count($foods) > 0): ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: var(--gray-light);">
                    <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 600; color: #666;">Image</th>
                    <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 600; color: #666;">Food Name</th>
                    <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 600; color: #666;">Category</th>
                    <th style="padding: 15px; text-align: right; font-size: 13px; font-weight: 600; color: #666;">Price</th>
                    <th style="padding: 15px; text-align: right; font-size: 13px; font-weight: 600; color: #666;">Cost</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Stock</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Special</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Available</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Dietary Info</th>
                    <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 600; color: #666;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($foods as $food): 
                    $stockStatus = $food['stock_quantity'] == 0 ? ['Out of Stock', '#dc3545'] : 
                                   ($food['stock_quantity'] <= 10 ? ['Low Stock', '#ffc107'] : 
                                   ['In Stock', '#28a745']);
                ?>
                <tr style="border-bottom: 1px solid var(--gray-light);">
                    <td style="padding: 15px;">
                        <?php if ($food['image']): ?>
                        <img src="../assets/<?php echo htmlspecialchars($food['image']); ?>" alt="<?php echo htmlspecialchars($food['food_name']); ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white;">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px;">
                        <p style="margin: 0; font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($food['food_name']); ?></p>
                        <p style="margin: 5px 0 0; font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($food['description'], 0, 50)) . (strlen($food['description']) > 50 ? '...' : ''); ?></p>
                    </td>
                    <td style="padding: 15px;">
                        <span style="padding: 5px 12px; border-radius: 15px; font-size: 12px; background-color: var(--gray-light);"><?php echo htmlspecialchars($food['category_name']); ?></span>
                    </td>
                    <td style="padding: 15px; text-align: right;">
                        <span style="font-weight: 600; color: var(--primary-color);"><?php echo formatPrice($food['price']); ?></span>
                    </td>
                    <td style="padding: 15px; text-align: right;">
                        <span style="font-size: 13px; color: #666;"><?php echo $food['cost_price'] ? formatPrice($food['cost_price']) : '-'; ?></span>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <span style="font-weight: 600; color: <?php echo $stockStatus[1]; ?>;"><?php echo $food['stock_quantity']; ?></span>
                        <p style="margin: 3px 0 0; font-size: 11px; color: <?php echo $stockStatus[1]; ?>;"><?php echo $stockStatus[0]; ?></p>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <?php if ($food['is_special']): ?>
                        <span style="color: var(--warning-color);"><i class="fas fa-star"></i></span>
                        <?php else: ?>
                        <span style="color: #ccc;"><i class="far fa-star"></i></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <?php if ($food['is_available']): ?>
                        <span style="padding: 5px 12px; border-radius: 15px; font-size: 12px; background-color: #d4edda; color: #155724;">Yes</span>
                        <?php else: ?>
                        <span style="padding: 5px 12px; border-radius: 15px; font-size: 12px; background-color: #f8d7da; color: #721c24;">No</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <?php if ($food['dietary_info']): ?>
                        <span style="padding: 5px 12px; border-radius: 15px; font-size: 11px; background-color: #e3f2fd; color: #1976d2;">
                            <i class="fas fa-leaf" style="margin-right: 3px;"></i><?php echo htmlspecialchars($food['dietary_info']); ?>
                        </span>
                        <?php else: ?>
                        <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px; text-align: center;">
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <button type="button" onclick='openFoodModal(<?php echo json_encode($food); ?>)' class="btn btn-outline btn-sm" style="padding: 6px 12px;"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="" style="display: inline;" id="deleteFoodForm<?php echo $food['food_id']; ?>">
                                <input type="hidden" name="food_id" value="<?php echo $food['food_id']; ?>">
                                <button type="button" onclick="openDeleteModal('deleteFoodForm<?php echo $food['food_id']; ?>', 'Delete Food Item', 'Are you sure you want to delete &quot;<?php echo htmlspecialchars($food['food_name']); ?>&quot;?', null, 'delete_food')" class="btn btn-danger btn-sm" style="padding: 6px 12px;"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div style="padding: 50px; text-align: center;">
        <i class="fas fa-box-open" style="font-size: 50px; color: var(--gray-medium); margin-bottom: 20px;"></i>
        <h3 style="font-size: 20px; margin-bottom: 10px;">No Food Items Found</h3>
        <p style="color: #666;">No food items match your current filters or the inventory is empty.</p>
    </div>
    <?php endif; ?>
</div>

<!-- Food Item Modal -->
<div id="foodModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 5px 30px rgba(0,0,0,0.3);">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 20px;" id="modalTitle">Add New Food Item</h3>
            <button type="button" onclick="closeFoodModal()" style="background: none; border: none; font-size: 24px; color: #666; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="food_id" id="foodId" value="">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Food Name <span style="color: #dc3545;">*</span></label>
                    <input type="text" name="food_name" id="foodName" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Category <span style="color: #dc3545;">*</span></label>
                    <select name="category_id" id="categoryId" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['cat_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px; resize: vertical;"></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Price <span style="color: #dc3545;">*</span></label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Cost Price</label>
                    <input type="number" name="cost_price" id="costPrice" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Prep Time (min)</label>
                    <input type="number" name="prep_time_minutes" id="prepTime" min="0" value="20" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Stock Quantity</label>
                    <input type="number" name="stock_quantity" id="stockQuantity" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Dietary Info</label>
                    <select name="dietary_info" id="dietaryInfo" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <option value="">None</option>
                        <?php foreach ($dietaryOptions as $option): ?>
                        <option value="<?php echo $option; ?>"><?php echo $option; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <input type="checkbox" name="is_special" id="isSpecial" value="1" style="width: 18px; height: 18px;">
                        <span style="font-weight: 600;"><i class="fas fa-star" style="color: var(--warning-color); margin-right: 5px;"></i>Mark as Special</span>
                    </label>
                </div>
                <div>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <input type="checkbox" name="is_available" id="isAvailable" value="1" checked style="width: 18px; height: 18px;">
                        <span style="font-weight: 600;"><i class="fas fa-check-circle" style="color: var(--success-color); margin-right: 5px;"></i>Available</span>
                    </label>
                </div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 13px; color: #666; margin-bottom: 8px; font-weight: 600;">Food Image</label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                <small style="color: #666; font-size: 12px;">Leave empty to keep current image when editing</small>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeFoodModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" name="save_food" class="btn btn-primary"><i class="fas fa-save"></i> Save Food Item</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFoodModal(food = null) {
    const modal = document.getElementById('foodModal');
    const title = document.getElementById('modalTitle');
    
    if (food) {
        title.textContent = 'Edit Food Item';
        document.getElementById('foodId').value = food.food_id;
        document.getElementById('foodName').value = food.food_name;
        document.getElementById('categoryId').value = food.category_id;
        document.getElementById('description').value = food.description || '';
        document.getElementById('price').value = food.price;
        document.getElementById('costPrice').value = food.cost_price || '';
        document.getElementById('prepTime').value = food.prep_time_minutes || 20;
        document.getElementById('stockQuantity').value = food.stock_quantity || 0;
        document.getElementById('dietaryInfo').value = food.dietary_info || '';
        document.getElementById('isSpecial').checked = food.is_special == 1;
        document.getElementById('isAvailable').checked = food.is_available == 1;
    } else {
        title.textContent = 'Add New Food Item';
        document.getElementById('foodId').value = '';
        document.getElementById('foodName').value = '';
        document.getElementById('categoryId').value = '';
        document.getElementById('description').value = '';
        document.getElementById('price').value = '';
        document.getElementById('costPrice').value = '';
        document.getElementById('prepTime').value = 20;
        document.getElementById('stockQuantity').value = 0;
        document.getElementById('dietaryInfo').value = '';
        document.getElementById('isSpecial').checked = false;
        document.getElementById('isAvailable').checked = true;
        document.getElementById('image').value = '';
    }
    
    modal.style.display = 'flex';
}

function closeFoodModal() {
    document.getElementById('foodModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('foodModal');
    if (event.target == modal) {
        closeFoodModal();
    }
}
</script>

<?php require_once '../includes/admin-footer.php'; ?>
