<?php
$pageTitle = 'Manage Promotions - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit promotion
if (isset($_POST['save_promotion'])) {
    $promoId = $_POST['promo_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $discountPercent = $_POST['discount_percent'] ?? null;
    $discountAmount = $_POST['discount_amount'] ?? null;
    $promoCode = $_POST['promo_code'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $minNights = $_POST['min_nights'] ?? 1;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/promotions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/promotions/' . $fileName;
        }
    }

    if ($title && $startDate && $endDate) {
        if ($promoId) {
            // Update existing promotion
            if ($image) {
                $stmt = $db->prepare("UPDATE promotions SET title = ?, description = ?, discount_percent = ?, discount_amount = ?, promo_code = ?, start_date = ?, end_date = ?, min_nights = ?, image = ?, is_active = ? WHERE promo_id = ?");
                $stmt->execute([$title, $description, $discountPercent, $discountAmount, $promoCode, $startDate, $endDate, $minNights, $image, $isActive, $promoId]);
            } else {
                $stmt = $db->prepare("UPDATE promotions SET title = ?, description = ?, discount_percent = ?, discount_amount = ?, promo_code = ?, start_date = ?, end_date = ?, min_nights = ?, is_active = ? WHERE promo_id = ?");
                $stmt->execute([$title, $description, $discountPercent, $discountAmount, $promoCode, $startDate, $endDate, $minNights, $isActive, $promoId]);
            }
            $_SESSION['success'] = 'Promotion updated successfully';
        } else {
            // Add new promotion
            $stmt = $db->prepare("INSERT INTO promotions (title, description, discount_percent, discount_amount, promo_code, start_date, end_date, min_nights, image, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $discountPercent, $discountAmount, $promoCode, $startDate, $endDate, $minNights, $image, $isActive]);
            $_SESSION['success'] = 'Promotion added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-promotions.php');
}

// Handle delete promotion
if (isset($_POST['delete_promotion'])) {
    $promoId = $_POST['promo_id'] ?? 0;
    if ($promoId) {
        $stmt = $db->prepare("DELETE FROM promotions WHERE promo_id = ?");
        if ($stmt->execute([$promoId])) {
            $_SESSION['success'] = 'Promotion deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete promotion';
        }
    }
    redirect('admin-promotions.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM promotions WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR promo_code LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$promotions = $stmt->fetchAll();

// Get counts
$activeCount = $db->query("SELECT COUNT(*) FROM promotions WHERE is_active = 1 AND end_date >= CURDATE()")->fetchColumn();
$expiredCount = $db->query("SELECT COUNT(*) FROM promotions WHERE end_date < CURDATE()")->fetchColumn();
$totalCount = count($promotions);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openPromotionModal()" class="btn btn-primary">Add New Promotion</button>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $activeCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Active Promotions</p>
                    </div>
                    <i class="fas fa-percent" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $expiredCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Expired</p>
                    </div>
                    <i class="fas fa-calendar-times" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $totalCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Promotions</p>
                    </div>
                    <i class="fas fa-tags" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search promotions..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All</option>
                        <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-promotions.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Promotions Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Promotions (<?php echo count($promotions); ?>)</h3>
            </div>

            <?php if (count($promotions) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Promotion</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Discount</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Promo Code</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Valid Period</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Min Nights</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promotions as $promo):
                        $isExpired = strtotime($promo['end_date']) < time();
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($promo['image']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($promo['image']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 60px; height: 60px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-percent" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($promo['title']); ?></div>
                                        <div style="font-size: 12px; color: #666; max-width: 200px;"><?php echo htmlspecialchars(substr($promo['description'], 0, 50)) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($promo['discount_percent']): ?>
                                <span style="font-weight: 600; color: var(--success-color);"><?php echo $promo['discount_percent']; ?>% OFF</span>
                                <?php elseif ($promo['discount_amount']): ?>
                                <span style="font-weight: 600; color: var(--success-color);">-<?php echo formatPrice($promo['discount_amount']); ?></span>
                                <?php else: ?>
                                <span style="color: #999;">No discount</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($promo['promo_code']): ?>
                                <span style="font-family: monospace; background-color: var(--gray-light); padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($promo['promo_code']); ?></span>
                                <?php else: ?>
                                <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-size: 13px;"><?php echo formatDate($promo['start_date'], 'M d, Y'); ?></div>
                                <div style="font-size: 13px;">to <?php echo formatDate($promo['end_date'], 'M d, Y'); ?></div>
                                <?php if ($isExpired): ?>
                                <div style="font-size: 11px; color: var(--danger-color);"><i class="fas fa-times-circle"></i> Expired</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $promo['min_nights']; ?> nights
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo ($promo['is_active'] && !$isExpired) ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo ($promo['is_active'] && !$isExpired) ? '#155724' : '#721c24'; ?>;">
                                    <?php echo ($promo['is_active'] && !$isExpired) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editPromotion(<?php echo htmlspecialchars(json_encode($promo)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this promotion?');">
                                        <input type="hidden" name="promo_id" value="<?php echo $promo['promo_id']; ?>">
                                        <button type="submit" name="delete_promotion" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-tags" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No promotions found</h3>
                <p style="color: #999;">Add your first promotion</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Promotion Modal -->
<div id="promotionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Promotion</h3>
            <button onclick="closePromotionModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="promo_id" id="promo_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Title *</label>
                <input type="text" name="title" id="title" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Discount Percent (%)</label>
                    <input type="number" name="discount_percent" id="discount_percent" min="0" max="100" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Discount Amount (PHP)</label>
                    <input type="number" name="discount_amount" id="discount_amount" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Promo Code</label>
                <input type="text" name="promo_code" id="promo_code" placeholder="e.g., SUMMER20" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Start Date *</label>
                    <input type="date" name="start_date" id="start_date" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">End Date *</label>
                    <input type="date" name="end_date" id="end_date" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Minimum Nights</label>
                <input type="number" name="min_nights" id="min_nights" min="1" value="1" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Image <span id="imageHint" style="font-weight: normal; color: #666;">(leave blank to keep current)</span></label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked style="width: 18px; height: 18px;">
                    <span style="font-size: 14px;">Active</span>
                </label>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closePromotionModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_promotion" class="btn btn-primary">Save Promotion</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPromotionModal() {
    document.getElementById('promotionModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Promotion';
    document.getElementById('promo_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('description').value = '';
    document.getElementById('discount_percent').value = '';
    document.getElementById('discount_amount').value = '';
    document.getElementById('promo_code').value = '';
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('min_nights').value = '1';
    document.getElementById('is_active').checked = true;
    document.getElementById('image').required = false;
    document.getElementById('imageHint').style.display = 'none';
}

function editPromotion(promo) {
    document.getElementById('promotionModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Promotion';
    document.getElementById('promo_id').value = promo.promo_id;
    document.getElementById('title').value = promo.title;
    document.getElementById('description').value = promo.description || '';
    document.getElementById('discount_percent').value = promo.discount_percent || '';
    document.getElementById('discount_amount').value = promo.discount_amount || '';
    document.getElementById('promo_code').value = promo.promo_code || '';
    document.getElementById('start_date').value = promo.start_date;
    document.getElementById('end_date').value = promo.end_date;
    document.getElementById('min_nights').value = promo.min_nights || 1;
    document.getElementById('is_active').checked = promo.is_active == 1;
    document.getElementById('image').required = false;
    document.getElementById('imageHint').style.display = 'inline';
}

function closePromotionModal() {
    document.getElementById('promotionModal').style.display = 'none';
}

document.getElementById('promotionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePromotionModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
