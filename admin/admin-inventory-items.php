<?php
$pageTitle = 'Manage Inventory Items - Admin';
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
    $invCatId = $_POST['inv_cat_id'] ?? '';
    $description = $_POST['description'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $reorderLevel = $_POST['reorder_level'] ?? 10;
    $unitCost = $_POST['unit_cost'] ?? 0;
    $supplier = $_POST['supplier'] ?? '';

    if ($itemName && $invCatId) {
        if ($itemId) {
            // Update existing item
            $stmt = $db->prepare("UPDATE inventory_items SET item_name = ?, inv_cat_id = ?, description = ?, unit = ?, quantity = ?, reorder_level = ?, unit_cost = ?, supplier = ? WHERE item_id = ?");
            $stmt->execute([$itemName, $invCatId, $description, $unit, $quantity, $reorderLevel, $unitCost, $supplier, $itemId]);
            $_SESSION['success'] = 'Inventory item updated successfully';
        } else {
            // Add new item
            $stmt = $db->prepare("INSERT INTO inventory_items (item_name, inv_cat_id, description, unit, quantity, reorder_level, unit_cost, supplier) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$itemName, $invCatId, $description, $unit, $quantity, $reorderLevel, $unitCost, $supplier]);
            $_SESSION['success'] = 'Inventory item added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-inventory-items.php');
}

// Handle delete item
if (isset($_POST['delete_item'])) {
    $itemId = $_POST['item_id'] ?? 0;
    if ($itemId) {
        $stmt = $db->prepare("DELETE FROM inventory_items WHERE item_id = ?");
        if ($stmt->execute([$itemId])) {
            $_SESSION['success'] = 'Inventory item deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete inventory item';
        }
    }
    redirect('admin-inventory-items.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT ii.*, ic.category_name
    FROM inventory_items ii
    JOIN inventory_categories ic ON ii.inv_cat_id = ic.inv_cat_id
    WHERE 1=1
";
$params = [];

if ($categoryFilter) {
    $sql .= " AND ii.inv_cat_id = ?";
    $params[] = $categoryFilter;
}

if ($search) {
    $sql .= " AND (ii.item_name LIKE ? OR ii.description LIKE ? OR ii.supplier LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY ic.category_name, ii.item_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT inv_cat_id, category_name FROM inventory_categories ORDER BY category_name")->fetchAll();

// Calculate stats
$totalItems = count($items);
$lowStock = array_filter($items, function($item) {
    return $item['quantity'] <= $item['reorder_level'];
});
$totalValue = array_sum(array_map(function($item) {
    return $item['quantity'] * $item['unit_cost'];
}, $items));

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <a href="admin-inventory-categories.php" class="btn btn-outline">Manage Categories</a>
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
                    <i class="fas fa-boxes" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo count($lowStock); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Low Stock</p>
                    </div>
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo formatPrice($totalValue); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Inventory Value</p>
                    </div>
                    <i class="fas fa-dollar-sign" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search items..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['inv_cat_id']; ?>" <?php echo $categoryFilter == $cat['inv_cat_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-inventory-items.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Items Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Inventory Items (<?php echo count($items); ?>)</h3>
            </div>

            <?php if (count($items) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Item</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Quantity</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Reorder Level</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Unit Cost</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Supplier</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item):
                        $isLowStock = $item['quantity'] <= $item['reorder_level'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light); <?php echo $isLowStock ? 'background-color: #fff3cd;' : ''; ?>">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2;">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600; <?php echo $isLowStock ? 'color: #dc3545;' : ''; ?>">
                                    <?php echo number_format($item['quantity']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                </span>
                                <?php if ($isLowStock): ?>
                                <div style="font-size: 11px; color: #dc3545;"><i class="fas fa-exclamation-circle"></i> Low stock</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo number_format($item['reorder_level']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo formatPrice($item['unit_cost']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo htmlspecialchars($item['supplier'] ?: 'N/A'); ?>
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
                <i class="fas fa-boxes" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No inventory items found</h3>
                <p style="color: #999;">Add your first inventory item</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Item Modal -->
<div id="itemModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Inventory Item</h3>
            <button onclick="closeItemModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="item_id" id="item_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Item Name *</label>
                <input type="text" name="item_name" id="item_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category *</label>
                <select name="inv_cat_id" id="inv_cat_id" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['inv_cat_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="2" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Unit</label>
                    <input type="text" name="unit" id="unit" placeholder="e.g., piece, bottle, liter" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Quantity</label>
                    <input type="number" name="quantity" id="quantity" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Reorder Level</label>
                    <input type="number" name="reorder_level" id="reorder_level" min="0" value="10" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Unit Cost (PHP)</label>
                    <input type="number" name="unit_cost" id="unit_cost" step="0.01" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Supplier</label>
                <input type="text" name="supplier" id="supplier" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
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
    document.getElementById('modalTitle').textContent = 'Add New Inventory Item';
    document.getElementById('item_id').value = '';
    document.getElementById('item_name').value = '';
    document.getElementById('inv_cat_id').value = '';
    document.getElementById('description').value = '';
    document.getElementById('unit').value = '';
    document.getElementById('quantity').value = '0';
    document.getElementById('reorder_level').value = '10';
    document.getElementById('unit_cost').value = '0';
    document.getElementById('supplier').value = '';
}

function editItem(item) {
    document.getElementById('itemModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Inventory Item';
    document.getElementById('item_id').value = item.item_id;
    document.getElementById('item_name').value = item.item_name;
    document.getElementById('inv_cat_id').value = item.inv_cat_id;
    document.getElementById('description').value = item.description || '';
    document.getElementById('unit').value = item.unit || '';
    document.getElementById('quantity').value = item.quantity || 0;
    document.getElementById('reorder_level').value = item.reorder_level || 10;
    document.getElementById('unit_cost').value = item.unit_cost || 0;
    document.getElementById('supplier').value = item.supplier || '';
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
