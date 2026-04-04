<?php
$pageTitle = 'Manage Inventory - Staff';
require_once '../includes/config.php';

// Check permission for inventory page
checkStaffPermission('inventory');

$db = getDB();

// Handle update quantity
if (isset($_POST['update_quantity'])) {
    $itemId = $_POST['item_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;

    if ($itemId) {
        // Get item name for message
        $nameStmt = $db->prepare("SELECT item_name FROM inventory_items WHERE item_id = ?");
        $nameStmt->execute([$itemId]);
        $itemName = $nameStmt->fetchColumn() ?? 'Inventory item';
        
        $stmt = $db->prepare("UPDATE inventory_items SET quantity = ? WHERE item_id = ?");
        $stmt->execute([$quantity, $itemId]);
        $_SESSION['success'] = $itemName . ' quantity updated to ' . $quantity;
    }
    redirect('staff-inventory.php');
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
    $sql .= " AND (ii.item_name LIKE ? OR ii.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY ic.category_name, ii.item_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT inv_cat_id, category_name FROM inventory_categories ORDER BY category_name")->fetchAll();

require_once '../includes/staff-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="staff-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search inventory items..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
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
                    <a href="staff-inventory.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Inventory Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($items as $item):
            $isLowStock = $item['quantity'] <= $item['reorder_level'];
            ?>
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px; <?php echo $isLowStock ? 'border: 2px solid #dc3545;' : ''; ?>">
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                    <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2;">
                        <?php echo htmlspecialchars($item['category_name']); ?>
                    </span>
                    <?php if ($isLowStock): ?>
                    <span style="color: #dc3545; font-size: 12px; font-weight: 600;"><i class="fas fa-exclamation-circle"></i> Low Stock</span>
                    <?php endif; ?>
                </div>

                <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                <p style="font-size: 13px; color: #666; margin-bottom: 15px;"><?php echo htmlspecialchars($item['description'] ?: 'No description'); ?></p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                    <div style="background-color: var(--gray-light); padding: 10px; border-radius: 5px; text-align: center;">
                        <div style="font-size: 11px; color: #666;">Current Stock</div>
                        <div style="font-size: 20px; font-weight: 600; color: <?php echo $isLowStock ? '#dc3545' : 'var(--primary-color)'; ?>"><?php echo number_format($item['quantity']); ?></div>
                    </div>
                    <div style="background-color: var(--gray-light); padding: 10px; border-radius: 5px; text-align: center;">
                        <div style="font-size: 11px; color: #666;">Reorder Level</div>
                        <div style="font-size: 20px; font-weight: 600;"><?php echo number_format($item['reorder_level']); ?></div>
                    </div>
                </div>

                <form method="POST" action="" style="display: flex; gap: 10px;">
                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" style="flex: 1; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                    <button type="submit" name="update_quantity" class="btn btn-primary" style="padding: 10px 20px;">Update</button>
                </form>

                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    <i class="fas fa-info-circle"></i> Unit: <?php echo htmlspecialchars($item['unit'] ?: 'piece'); ?> | Supplier: <?php echo htmlspecialchars($item['supplier'] ?: 'N/A'); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($items) === 0): ?>
        <div style="text-align: center; padding: 60px;">
            <i class="fas fa-boxes" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
            <h3 style="color: #666;">No inventory items found</h3>
            <p style="color: #999;">Try adjusting your search filters</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../includes/staff-footer.php'; ?>
