<?php
$pageTitle = 'Manage Additional Services - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit service
if (isset($_POST['save_service'])) {
    $serviceId = $_POST['service_id'] ?? null;
    $serviceName = $_POST['service_name'] ?? '';
    $category = $_POST['category'] ?? '';
    $subcategory = $_POST['subcategory'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? 0;
    $durationMinutes = $_POST['duration_minutes'] ?? null;
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $requiresBooking = isset($_POST['requires_booking']) ? 1 : 0;
    $sortOrder = $_POST['sort_order'] ?? 0;

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/services/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/services/' . $fileName;
        }
    }

    if ($serviceName && $category) {
        if ($serviceId) {
            // Update existing service
            if ($image) {
                $stmt = $db->prepare("UPDATE additional_services SET service_name = ?, category = ?, subcategory = ?, description = ?, price = ?, duration_minutes = ?, image = ?, is_available = ?, requires_booking = ?, sort_order = ? WHERE service_id = ?");
                $stmt->execute([$serviceName, $category, $subcategory, $description, $price, $durationMinutes, $image, $isAvailable, $requiresBooking, $sortOrder, $serviceId]);
            } else {
                $stmt = $db->prepare("UPDATE additional_services SET service_name = ?, category = ?, subcategory = ?, description = ?, price = ?, duration_minutes = ?, is_available = ?, requires_booking = ?, sort_order = ? WHERE service_id = ?");
                $stmt->execute([$serviceName, $category, $subcategory, $description, $price, $durationMinutes, $isAvailable, $requiresBooking, $sortOrder, $serviceId]);
            }
            $_SESSION['success'] = 'Service "' . $serviceName . '" updated successfully';
        } else {
            // Add new service
            $stmt = $db->prepare("INSERT INTO additional_services (service_name, category, subcategory, description, price, duration_minutes, image, is_available, requires_booking, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$serviceName, $category, $subcategory, $description, $price, $durationMinutes, $image, $isAvailable, $requiresBooking, $sortOrder]);
            $_SESSION['success'] = 'Service "' . $serviceName . '" added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-additional-services.php');
}

// Handle delete service
if (isset($_POST['delete_service'])) {
    $serviceId = $_POST['service_id'] ?? 0;
    if ($serviceId) {
        // Check if service has any requests
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM guest_service_requests WHERE service_id = ?");
        $checkStmt->execute([$serviceId]);
        $requestCount = $checkStmt->fetchColumn();
        
        if ($requestCount > 0) {
            $_SESSION['error'] = 'Cannot delete service. It has ' . $requestCount . ' associated request(s).';
        } else {
            // Get service name before deletion
            $nameStmt = $db->prepare("SELECT service_name FROM additional_services WHERE service_id = ?");
            $nameStmt->execute([$serviceId]);
            $serviceName = $nameStmt->fetchColumn() ?? 'Service';
            
            $stmt = $db->prepare("DELETE FROM additional_services WHERE service_id = ?");
            if ($stmt->execute([$serviceId])) {
                $_SESSION['success'] = 'Service "' . $serviceName . '" deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete service';
            }
        }
    }
    redirect('admin-additional-services.php');
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM additional_services WHERE 1=1";
$params = [];

if ($categoryFilter) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

if ($search) {
    $sql .= " AND (service_name LIKE ? OR description LIKE ? OR subcategory LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY category, sort_order, service_name";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Get category counts
$categoryCounts = $db->query("SELECT category, COUNT(*) as count FROM additional_services GROUP BY category")->fetchAll(PDO::FETCH_KEY_PAIR);

// Categories for dropdown
$categories = ['laundry' => 'Laundry & Dry Cleaning', 'spa' => 'Spa & Wellness', 'wellness' => 'Wellness Activities', 'other' => 'Other Services'];

// Category icons
$categoryIcons = [
    'laundry' => 'fa-tshirt',
    'spa' => 'fa-spa',
    'wellness' => 'fa-om',
    'other' => 'fa-concierge-bell'
];

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openServiceModal()" class="btn btn-primary">Add New Service</button>
        </div>

        <!-- Category Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <?php foreach ($categories as $cat => $catLabel): 
                $count = $categoryCounts[$cat] ?? 0;
                $icon = $categoryIcons[$cat] ?? 'fa-star';
            ?>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo $count; ?></h3>
                        <p style="color: #666; margin: 5px 0 0; text-transform: capitalize;"><?php echo $catLabel; ?></p>
                    </div>
                    <i class="fas <?php echo $icon; ?>" style="font-size: 30px; color: var(--primary-color);"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search services..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat => $catLabel): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo $catLabel; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-additional-services.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Services Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Additional Services (<?php echo count($services); ?>)</h3>
            </div>

            <?php if (count($services) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Service</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Category</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Duration</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Booking Required</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($service['image']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($service['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 50px; height: 50px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-<?php echo $categoryIcons[$service['category']] ?? 'fa-star'; ?>" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($service['service_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;">
                                            <?php if ($service['subcategory']): ?>
                                            <?php echo htmlspecialchars($service['subcategory']); ?>
                                            <?php else: ?>
                                            <?php echo htmlspecialchars(substr($service['description'], 0, 50)) . '...'; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2; text-transform: capitalize;">
                                    <?php echo $categories[$service['category']] ?? ucfirst($service['category']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($service['price']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $service['duration_minutes'] ? $service['duration_minutes'] . ' min' : '-'; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($service['requires_booking']): ?>
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #d4edda; color: #155724;">
                                    <i class="fas fa-check"></i> Yes
                                </span>
                                <?php else: ?>
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #f8d7da; color: #721c24;">
                                    <i class="fas fa-times"></i> No
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $service['is_available'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $service['is_available'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $service['is_available'] ? 'Available' : 'Not Available'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" id="deleteServiceForm<?php echo $service['service_id']; ?>">
                                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('deleteServiceForm<?php echo $service['service_id']; ?>', 'Delete Service', 'Are you sure you want to delete &quot;<?php echo htmlspecialchars($service['service_name']); ?>&quot;?', null, 'delete_service')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-concierge-bell" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No services found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Service Modal -->
<div id="serviceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Service</h3>
            <button onclick="closeServiceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="service_id" id="service_id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Service Name *</label>
                    <input type="text" name="service_name" id="service_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Category *</label>
                    <select name="category" id="category" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <?php foreach ($categories as $cat => $catLabel): ?>
                        <option value="<?php echo $cat; ?>"><?php echo $catLabel; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Subcategory</label>
                <input type="text" name="subcategory" id="subcategory" placeholder="e.g., Spa & Wellness, Laundry Services" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Price (PHP) *</label>
                    <input type="number" name="price" id="price" step="0.01" min="0" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" id="duration_minutes" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div style="display: flex; align-items: center; gap: 15px; padding-top: 30px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="requires_booking" id="requires_booking" value="1" style="width: 18px; height: 18px;">
                        <span style="font-size: 14px;">Requires Advance Booking</span>
                    </label>
                </div>
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
                <button type="button" onclick="closeServiceModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_service" class="btn btn-primary">Save Service</button>
            </div>
        </form>
    </div>
</div>

<script>
function openServiceModal() {
    document.getElementById('serviceModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Service';
    document.getElementById('service_id').value = '';
    document.getElementById('service_name').value = '';
    document.getElementById('category').value = 'laundry';
    document.getElementById('subcategory').value = '';
    document.getElementById('description').value = '';
    document.getElementById('price').value = '';
    document.getElementById('duration_minutes').value = '';
    document.getElementById('sort_order').value = '0';
    document.getElementById('requires_booking').checked = false;
    document.getElementById('is_available').checked = true;
}

function editService(service) {
    document.getElementById('serviceModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Service';
    document.getElementById('service_id').value = service.service_id;
    document.getElementById('service_name').value = service.service_name;
    document.getElementById('category').value = service.category;
    document.getElementById('subcategory').value = service.subcategory || '';
    document.getElementById('description').value = service.description || '';
    document.getElementById('price').value = service.price || '';
    document.getElementById('duration_minutes').value = service.duration_minutes || '';
    document.getElementById('sort_order').value = service.sort_order || 0;
    document.getElementById('requires_booking').checked = service.requires_booking == 1;
    document.getElementById('is_available').checked = service.is_available == 1;
}

function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
}

document.getElementById('serviceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeServiceModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
