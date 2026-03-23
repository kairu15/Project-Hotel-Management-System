<?php
$pageTitle = 'Manage Inventory Categories - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit category
if (isset($_POST['save_category'])) {
    $catId = $_POST['inv_cat_id'] ?? null;
    $categoryName = $_POST['category_name'] ?? '';

    if ($categoryName) {
        if ($catId) {
            // Update existing category
            $stmt = $db->prepare("UPDATE inventory_categories SET category_name = ? WHERE inv_cat_id = ?");
            $stmt->execute([$categoryName, $catId]);
            $_SESSION['success'] = 'Category updated successfully';
        } else {
            // Add new category
            $stmt = $db->prepare("INSERT INTO inventory_categories (category_name) VALUES (?)");
            $stmt->execute([$categoryName]);
            $_SESSION['success'] = 'Category added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please enter a category name';
    }
    redirect('admin-inventory-categories.php');
}

// Handle delete category
if (isset($_POST['delete_category'])) {
    $catId = $_POST['inv_cat_id'] ?? 0;
    if ($catId) {
        // Check if category has items
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM inventory_items WHERE inv_cat_id = ?");
        $checkStmt->execute([$catId]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Cannot delete category that has inventory items';
        } else {
            $stmt = $db->prepare("DELETE FROM inventory_categories WHERE inv_cat_id = ?");
            if ($stmt->execute([$catId])) {
                $_SESSION['success'] = 'Category deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete category';
            }
        }
    }
    redirect('admin-inventory-categories.php');
}

// Get all categories with item counts
$categories = $db->query("
    SELECT ic.*, COUNT(ii.item_id) as item_count
    FROM inventory_categories ic
    LEFT JOIN inventory_items ii ON ic.inv_cat_id = ii.inv_cat_id
    GROUP BY ic.inv_cat_id
    ORDER BY ic.category_name
")->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <a href="admin-inventory-items.php" class="btn btn-outline">Manage Items</a>
            <button type="button" onclick="openCategoryModal()" class="btn btn-primary">Add New Category</button>
        </div>

        <!-- Stats Card -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo count($categories); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Categories</p>
                    </div>
                    <i class="fas fa-folder" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo array_sum(array_column($categories, 'item_count')); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Items</p>
                    </div>
                    <i class="fas fa-boxes" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Inventory Categories (<?php echo count($categories); ?>)</h3>
            </div>

            <?php if (count($categories) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category Name</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Items Count</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($category['category_name']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600;"><?php echo $category['item_count']; ?></span> items
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                        <input type="hidden" name="inv_cat_id" value="<?php echo $category['inv_cat_id']; ?>">
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
                <i class="fas fa-folder" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No categories found</h3>
                <p style="color: #999;">Add your first inventory category</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Category Modal -->
<div id="categoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Category</h3>
            <button onclick="closeCategoryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="inv_cat_id" id="inv_cat_id">

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category Name *</label>
                <input type="text" name="category_name" id="category_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
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
    document.getElementById('modalTitle').textContent = 'Add New Category';
    document.getElementById('inv_cat_id').value = '';
    document.getElementById('category_name').value = '';
}

function editCategory(category) {
    document.getElementById('categoryModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('inv_cat_id').value = category.inv_cat_id;
    document.getElementById('category_name').value = category.category_name;
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
