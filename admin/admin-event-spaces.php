<?php
$pageTitle = 'Manage Event Spaces - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Image validation and upload helper
function validateImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.'];
    }
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }
    return ['valid' => true];
}

function uploadImage($file, $uploadDir) {
    $validation = validateImage($file);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    $fileName = time() . '_' . uniqid() . '_' . basename($file['name']);
    $uploadFile = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
        return ['success' => true, 'path' => $fileName];
    }
    return ['success' => false, 'error' => 'Failed to upload file.'];
}

// Handle add/edit event space
if (isset($_POST['save_space'])) {
    $spaceId = $_POST['space_id'] ?? null;
    $spaceName = $_POST['space_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $areaSqm = $_POST['area_sqm'] ?? null;
    $features = $_POST['features'] ?? '';
    $pricePerDay = $_POST['price_per_day'] ?? 0;
    $status = $_POST['status'] ?? 'available';

    // Handle primary image upload
    $primaryImage = '';
    if (!empty($_FILES['image_primary']['name'])) {
        if ($spaceId) {
            $uploadDir = '../assets/images/events/' . $spaceId . '/';
        } else {
            $uploadDir = '../assets/images/events/temp/';
        }
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = [
            'name' => $_FILES['image_primary']['name'],
            'type' => $_FILES['image_primary']['type'],
            'tmp_name' => $_FILES['image_primary']['tmp_name'],
            'size' => $_FILES['image_primary']['size']
        ];
        $result = uploadImage($file, $uploadDir);
        if ($result['success']) {
            $primaryImage = 'images/events/' . ($spaceId ? $spaceId . '/' : 'temp/') . $result['path'];
        }
    }

    // Handle gallery images upload
    $galleryImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        if ($spaceId) {
            $uploadDir = '../assets/images/events/' . $spaceId . '/';
        } else {
            $uploadDir = '../assets/images/events/temp/';
        }
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName)) {
                $file = [
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'tmp_name' => $tmpName,
                    'size' => $_FILES['images']['size'][$key]
                ];
                $result = uploadImage($file, $uploadDir);
                if ($result['success']) {
                    $galleryImages[] = 'images/events/' . ($spaceId ? $spaceId . '/' : 'temp/') . $result['path'];
                }
            }
        }
    }

    if ($spaceName && $capacity > 0) {
        if ($spaceId) {
            // Update existing space
            $stmt = $db->prepare("SELECT images, image_primary FROM event_spaces WHERE space_id = ?");
            $stmt->execute([$spaceId]);
            $existingData = $stmt->fetch();
            $existingImages = $existingData['images'] ?? '';
            $existingPrimary = $existingData['image_primary'] ?? '';
            
            // Use new primary image if uploaded, otherwise keep existing
            $finalPrimaryImage = $primaryImage ?: $existingPrimary;
            
            $allImages = [];
            if ($existingImages) {
                $allImages = explode(',', $existingImages);
            }
            $allImages = array_merge($allImages, $galleryImages);
            $allImages = array_filter(array_unique($allImages));
            
            $stmt = $db->prepare("UPDATE event_spaces SET space_name = ?, description = ?, capacity = ?, area_sqm = ?, features = ?, price_per_day = ?, image_primary = ?, images = ?, status = ? WHERE space_id = ?");
            $stmt->execute([$spaceName, $description, $capacity, $areaSqm, $features, $pricePerDay, $finalPrimaryImage, implode(',', $allImages), $status, $spaceId]);
            $_SESSION['success'] = 'Event space "' . $spaceName . '" updated successfully';
        } else {
            // Add new space
            $stmt = $db->prepare("INSERT INTO event_spaces (space_name, description, capacity, area_sqm, features, price_per_day, image_primary, images, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$spaceName, $description, $capacity, $areaSqm, $features, $pricePerDay, $primaryImage, implode(',', $galleryImages), $status]);
            $newSpaceId = $db->lastInsertId();
            
            // Move temp images to proper folder
            if (!empty($primaryImage) || !empty($galleryImages)) {
                $tempDir = '../assets/images/events/temp/';
                $newDir = '../assets/images/events/' . $newSpaceId . '/';
                if (!is_dir($newDir)) {
                    mkdir($newDir, 0755, true);
                }
                
                // Move primary image
                if (!empty($primaryImage)) {
                    $tempPath = '../assets/' . $primaryImage;
                    $fileName = basename($primaryImage);
                    $newPath = $newDir . $fileName;
                    
                    if (file_exists($tempPath) && rename($tempPath, $newPath)) {
                        $primaryImage = 'images/events/' . $newSpaceId . '/' . $fileName;
                    }
                }
                
                // Move gallery images
                $newGalleryImages = [];
                foreach ($galleryImages as $imgPath) {
                    $tempPath = '../assets/' . $imgPath;
                    $fileName = basename($imgPath);
                    $newPath = $newDir . $fileName;
                    
                    if (file_exists($tempPath) && rename($tempPath, $newPath)) {
                        $newGalleryImages[] = 'images/events/' . $newSpaceId . '/' . $fileName;
                    }
                }
                
                // Update database with new paths
                if (!empty($primaryImage) || !empty($newGalleryImages)) {
                    $stmt = $db->prepare("UPDATE event_spaces SET image_primary = ?, images = ? WHERE space_id = ?");
                    $stmt->execute([$primaryImage, implode(',', $newGalleryImages), $newSpaceId]);
                }
            }
            $_SESSION['success'] = 'Event space "' . $spaceName . '" added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-event-spaces.php');
}

// Handle delete space
if (isset($_POST['delete_space'])) {
    $spaceId = $_POST['space_id'] ?? 0;
    if ($spaceId) {
        // Get space name before deletion
        $nameStmt = $db->prepare("SELECT space_name FROM event_spaces WHERE space_id = ?");
        $nameStmt->execute([$spaceId]);
        $spaceName = $nameStmt->fetchColumn() ?? 'Event space';
        
        // Check if space has bookings
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM event_bookings WHERE space_id = ? AND status != 'cancelled'");
        $checkStmt->execute([$spaceId]);
        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'Cannot delete "' . $spaceName . '" - has active bookings';
        } else {
            // Get and delete images
            $stmt = $db->prepare("SELECT images FROM event_spaces WHERE space_id = ?");
            $stmt->execute([$spaceId]);
            $space = $stmt->fetch();
            
            if ($space && $space['images']) {
                $images = explode(',', $space['images']);
                foreach ($images as $img) {
                    if (file_exists('../assets/' . trim($img))) {
                        unlink('../assets/' . trim($img));
                    }
                }
                
                // Remove space folder
                $spaceDir = '../assets/images/events/' . $spaceId . '/';
                if (is_dir($spaceDir)) {
                    rmdir($spaceDir);
                }
            }
            
            $stmt = $db->prepare("DELETE FROM event_spaces WHERE space_id = ?");
            if ($stmt->execute([$spaceId])) {
                $_SESSION['success'] = 'Event space "' . $spaceName . '" deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete event space';
            }
        }
    }
    redirect('admin-event-spaces.php');
}

// Handle delete individual gallery image
if (isset($_POST['delete_event_image'])) {
    $spaceId = $_POST['space_id'] ?? 0;
    $imagePath = $_POST['image_path'] ?? '';
    
    if ($spaceId && $imagePath) {
        // Remove from database
        $stmt = $db->prepare("SELECT images FROM event_spaces WHERE space_id = ?");
        $stmt->execute([$spaceId]);
        $images = $stmt->fetchColumn();
        
        if ($images) {
            $imagesArray = explode(',', $images);
            $imagesArray = array_filter($imagesArray, function($img) use ($imagePath) {
                return trim($img) !== trim($imagePath);
            });
            
            $stmt = $db->prepare("UPDATE event_spaces SET images = ? WHERE space_id = ?");
            $stmt->execute([implode(',', $imagesArray), $spaceId]);
            
            // Delete file
            if (file_exists('../assets/' . $imagePath)) {
                unlink('../assets/' . $imagePath);
            }
            
            $_SESSION['success'] = 'Image deleted successfully';
        }
    }
    redirect('admin-event-spaces.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT es.*, (SELECT COUNT(*) FROM event_bookings WHERE space_id = es.space_id AND status IN ('pending', 'confirmed')) as booking_count FROM event_spaces es WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND es.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (es.space_name LIKE ? OR es.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY es.capacity DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$spaces = $stmt->fetchAll();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openSpaceModal()" class="btn btn-primary">Add New Event Space</button>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search event spaces..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="booked" <?php echo $statusFilter === 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="maintenance" <?php echo $statusFilter === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-event-spaces.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Event Spaces Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Event Spaces (<?php echo count($spaces); ?>)</h3>
            </div>

            <?php if (count($spaces) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Space Name</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Capacity</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Area</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Price/Day</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Bookings</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Images</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($spaces as $space): 
                            $imageCount = $space['images'] ? count(explode(',', $space['images'])) : 0;
                            // Get primary image for thumbnail
                            $thumbImage = '';
                            if (!empty($space['image_primary'])) {
                                $thumbImage = $space['image_primary'];
                                if (strpos($thumbImage, 'http') !== 0 && strpos($thumbImage, 'assets/') !== 0) {
                                    $thumbImage = 'assets/' . $thumbImage;
                                }
                            }
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($thumbImage): ?>
                                    <img src="../<?php echo htmlspecialchars($thumbImage); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                    <div style="width: 60px; height: 60px; background-color: var(--gray-light); border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-calendar-alt" style="color: #999;"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($space['space_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($space['description'], 0, 60)) . '...'; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo number_format($space['capacity']); ?> guests
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $space['area_sqm'] ? number_format($space['area_sqm']) . ' sqm' : 'N/A'; ?>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($space['price_per_day']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600;"><?php echo $space['booking_count']; ?></span> active
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="background-color: var(--primary-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">
                                    <i class="fas fa-images" style="margin-right: 5px;"></i><?php echo $imageCount; ?> photos
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php
                                $statusColors = [
                                    'available' => ['#d4edda', '#155724'],
                                    'booked' => ['#fff3cd', '#856404'],
                                    'maintenance' => ['#f8d7da', '#721c24']
                                ];
                                $color = $statusColors[$space['status']] ?? ['#e2e3e5', '#383d41'];
                                ?>
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $space['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="button" onclick="editSpace(<?php echo htmlspecialchars(json_encode($space)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <button type="button" onclick="openEventImageManager(<?php echo $space['space_id']; ?>, '<?php echo htmlspecialchars($space['space_name']); ?>')" class="btn btn-sm btn-secondary" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-images"></i> Photos</button>
                                    <form method="POST" action="" style="display: inline;" id="deleteEventSpaceForm<?php echo $space['space_id']; ?>">
                                        <input type="hidden" name="space_id" value="<?php echo $space['space_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('deleteEventSpaceForm<?php echo $space['space_id']; ?>', 'Delete Event Space', 'Are you sure you want to delete event space &quot;<?php echo htmlspecialchars($space['space_name']); ?>&quot;? This will also delete all associated images.', null, 'delete_space')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-calendar-alt" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No event spaces found</h3>
                <p style="color: #999;">Add your first event space</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Event Space Modal -->
<div id="spaceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 700px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Event Space</h3>
            <button onclick="closeSpaceModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="space_id" id="space_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Space Name *</label>
                <input type="text" name="space_name" id="space_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Capacity *</label>
                    <input type="number" name="capacity" id="capacity" min="1" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Area (sqm)</label>
                    <input type="number" name="area_sqm" id="area_sqm" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Price per Day (PHP)</label>
                    <input type="number" name="price_per_day" id="price_per_day" step="0.01" min="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Features (comma-separated)</label>
                <input type="text" name="features" id="features" placeholder="Stage, Sound System, Projector, WiFi..." style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Primary Image</label>
                <input type="file" name="image_primary" id="image_primary" accept="image/jpeg,image/png,image/gif,image/webp" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                <small style="color: #666;">This will be the main image shown on the events page. JPG, PNG, GIF, WEBP only. Max 5MB.</small>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Gallery Images (Multiple)</label>
                <input type="file" name="images[]" id="images" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                <small style="color: #666;">JPG, PNG, GIF, WEBP only. Max 5MB each. Upload multiple images at once.</small>
                <div id="imagePreview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 15px;"></div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="available">Available</option>
                    <option value="booked">Booked</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeSpaceModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_space" class="btn btn-primary">Save Event Space</button>
            </div>
        </form>
    </div>
</div>

<!-- Event Image Manager Modal -->
<div id="eventImageManagerModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1001; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 id="eventImageManagerTitle" style="font-size: 20px; margin: 0;">Manage Images</h3>
                <p style="color: #666; margin: 5px 0 0 0; font-size: 13px;">View, add, or delete images for this event space</p>
            </div>
            <button onclick="closeEventImageManager()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 30px;">
            <!-- Add New Images -->
            <div style="background-color: var(--gray-light); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;"><i class="fas fa-upload"></i> Add New Images</label>
                <form method="POST" action="" enctype="multipart/form-data" id="addEventImagesForm">
                    <input type="hidden" name="space_id" id="eventImageManagerSpaceId">
                    <input type="hidden" name="space_name" id="imgMgrSpaceName">
                    <input type="hidden" name="description" id="imgMgrEventDesc">
                    <input type="hidden" name="capacity" id="imgMgrEventCapacity">
                    <input type="hidden" name="area_sqm" id="imgMgrEventArea">
                    <input type="hidden" name="features" id="imgMgrEventFeatures">
                    <input type="hidden" name="price_per_day" id="imgMgrEventPrice">
                    <input type="hidden" name="status" id="imgMgrEventStatus">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <button type="submit" name="save_space" class="btn btn-primary" style="padding: 10px 20px;">Upload</button>
                    </div>
                    <small style="color: #666; display: block; margin-top: 8px;">JPG, PNG, GIF, WEBP only. Max 5MB each.</small>
                </form>
            </div>
            
            <!-- Existing Images -->
            <div id="existingEventImagesContainer">
                <h4 style="font-size: 16px; margin-bottom: 15px;">Existing Images</h4>
                <div id="existingEventImagesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                    <!-- Images will be loaded here dynamically -->
                </div>
                <p id="noEventImagesMessage" style="text-align: center; color: #999; padding: 40px; display: none;">
                    <i class="fas fa-images" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    No images uploaded yet
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function openSpaceModal() {
    document.getElementById('spaceModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Event Space';
    document.getElementById('space_id').value = '';
    document.getElementById('space_name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('capacity').value = '';
    document.getElementById('area_sqm').value = '';
    document.getElementById('price_per_day').value = '';
    document.getElementById('features').value = '';
    document.getElementById('status').value = 'available';
    document.getElementById('imagePreview').innerHTML = '';
}

function editSpace(space) {
    document.getElementById('spaceModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Event Space';
    document.getElementById('space_id').value = space.space_id;
    document.getElementById('space_name').value = space.space_name;
    document.getElementById('description').value = space.description || '';
    document.getElementById('capacity').value = space.capacity;
    document.getElementById('area_sqm').value = space.area_sqm || '';
    document.getElementById('price_per_day').value = space.price_per_day || '';
    document.getElementById('features').value = space.features || '';
    document.getElementById('status').value = space.status;
    document.getElementById('imagePreview').innerHTML = '';
}

function closeSpaceModal() {
    document.getElementById('spaceModal').style.display = 'none';
}

const spacesData = <?php echo json_encode($spaces); ?>;

function openEventImageManager(spaceId, spaceName) {
    document.getElementById('eventImageManagerModal').style.display = 'flex';
    document.getElementById('eventImageManagerTitle').textContent = 'Manage Images: ' + spaceName;
    document.getElementById('eventImageManagerSpaceId').value = spaceId;
    
    // Set hidden form values for the add images form
    const space = spacesData.find(s => s.space_id == spaceId);
    if (space) {
        document.getElementById('imgMgrSpaceName').value = space.space_name || '';
        document.getElementById('imgMgrEventDesc').value = space.description || '';
        document.getElementById('imgMgrEventCapacity').value = space.capacity || '';
        document.getElementById('imgMgrEventArea').value = space.area_sqm || '';
        document.getElementById('imgMgrEventFeatures').value = space.features || '';
        document.getElementById('imgMgrEventPrice').value = space.price_per_day || '';
        document.getElementById('imgMgrEventStatus').value = space.status || 'available';
    }
    
    // Load existing images
    const grid = document.getElementById('existingEventImagesGrid');
    const noImagesMsg = document.getElementById('noEventImagesMessage');
    
    grid.innerHTML = '';
    
    if (space && space.images) {
        const images = space.images.split(',').filter(img => img.trim());
        if (images.length > 0) {
            noImagesMsg.style.display = 'none';
            images.forEach((img, index) => {
                const imgPath = img.trim();
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);';
                div.innerHTML = `
                    <img src="../assets/${imgPath}" style="width: 100%; height: 150px; object-fit: cover;">
                    <form method="POST" action="" id="deleteEventImgForm${spaceId}_${index}" style="position: absolute; top: 5px; right: 5px;">
                        <input type="hidden" name="space_id" value="${spaceId}">
                        <input type="hidden" name="image_path" value="${imgPath}">
                        <button type="button" onclick="openDeleteModal('deleteEventImgForm${spaceId}_${index}', 'Delete Image', 'Are you sure you want to delete this image?', null, 'delete_event_image')" style="background: #dc3545; color: white; border: none; border-radius: 4px; padding: 6px 10px; cursor: pointer; font-size: 12px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                `;
                grid.appendChild(div);
            });
        } else {
            noImagesMsg.style.display = 'block';
        }
    } else {
        noImagesMsg.style.display = 'block';
    }
}

function closeEventImageManager() {
    document.getElementById('eventImageManagerModal').style.display = 'none';
}

// Image preview for file input
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    Array.from(e.target.files).forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);';
                div.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100px; object-fit: cover;">`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
});

// Close modals on outside click
document.getElementById('spaceModal').addEventListener('click', function(e) {
    if (e.target === this) closeSpaceModal();
});
document.getElementById('eventImageManagerModal').addEventListener('click', function(e) {
    if (e.target === this) closeEventImageManager();
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
