<?php
$pageTitle = 'Manage Homepage Slider - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit slide
if (isset($_POST['save_slide'])) {
    $slideId = $_POST['slide_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $subtitle = $_POST['subtitle'] ?? '';
    $buttonText = $_POST['button_text'] ?? '';
    $buttonLink = $_POST['button_link'] ?? '';
    $sortOrder = $_POST['sort_order'] ?? 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/slider/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/slider/' . $fileName;
        }
    }

    if ($title && ($image || $slideId)) {
        if ($slideId) {
            // Update existing slide
            if ($image) {
                $stmt = $db->prepare("UPDATE homepage_slider SET title = ?, subtitle = ?, button_text = ?, button_link = ?, image = ?, sort_order = ?, is_active = ? WHERE slide_id = ?");
                $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $image, $sortOrder, $isActive, $slideId]);
            } else {
                $stmt = $db->prepare("UPDATE homepage_slider SET title = ?, subtitle = ?, button_text = ?, button_link = ?, sort_order = ?, is_active = ? WHERE slide_id = ?");
                $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $sortOrder, $isActive, $slideId]);
            }
            $_SESSION['success'] = 'Slide updated successfully';
        } else {
            // Add new slide
            if ($image) {
                $stmt = $db->prepare("INSERT INTO homepage_slider (title, subtitle, button_text, button_link, image, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $image, $sortOrder, $isActive]);
                $_SESSION['success'] = 'Slide added successfully';
            } else {
                $_SESSION['error'] = 'Please upload an image';
            }
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-homepage-slider.php');
}

// Handle delete slide
if (isset($_POST['delete_slide'])) {
    $slideId = $_POST['slide_id'] ?? 0;
    if ($slideId) {
        $stmt = $db->prepare("DELETE FROM homepage_slider WHERE slide_id = ?");
        if ($stmt->execute([$slideId])) {
            $_SESSION['success'] = 'Slide deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete slide';
        }
    }
    redirect('admin-homepage-slider.php');
}

// Get all slides
$stmt = $db->query("SELECT * FROM homepage_slider ORDER BY sort_order ASC, slide_id DESC");
$slides = $stmt->fetchAll();

// Get counts
$activeCount = $db->query("SELECT COUNT(*) FROM homepage_slider WHERE is_active = 1")->fetchColumn();
$inactiveCount = $db->query("SELECT COUNT(*) FROM homepage_slider WHERE is_active = 0")->fetchColumn();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openSlideModal()" class="btn btn-primary">Add New Slide</button>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $activeCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Active Slides</p>
                    </div>
                    <i class="fas fa-eye" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $inactiveCount; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Inactive Slides</p>
                    </div>
                    <i class="fas fa-eye-slash" style="font-size: 32px; color: var(--danger-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo count($slides); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Slides</p>
                    </div>
                    <i class="fas fa-images" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
        </div>

        <!-- Slides Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Homepage Slides (<?php echo count($slides); ?>)</h3>
            </div>

            <?php if (count($slides) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Order</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Preview</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Content</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Button</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slides as $slide): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600; color: var(--primary-color);"><?php echo $slide['sort_order']; ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <img src="../assets/<?php echo htmlspecialchars($slide['image']); ?>" alt="" style="width: 120px; height: 70px; object-fit: cover; border-radius: 5px;">
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500; max-width: 250px;"><?php echo htmlspecialchars($slide['title'] ?: 'Untitled'); ?></div>
                                <div style="font-size: 12px; color: #666; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($slide['subtitle'] ?: 'No subtitle'); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($slide['button_text']): ?>
                                <div style="font-size: 12px;"><strong><?php echo htmlspecialchars($slide['button_text']); ?></strong></div>
                                <div style="font-size: 11px; color: #666;"><?php echo htmlspecialchars($slide['button_link']); ?></div>
                                <?php else: ?>
                                <span style="color: #999; font-size: 12px;">No button</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $slide['is_active'] ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $slide['is_active'] ? '#155724' : '#721c24'; ?>;">
                                    <?php echo $slide['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editSlide(<?php echo htmlspecialchars(json_encode($slide)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this slide?');">
                                        <input type="hidden" name="slide_id" value="<?php echo $slide['slide_id']; ?>">
                                        <button type="submit" name="delete_slide" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-images" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No slides found</h3>
                <p style="color: #999;">Add your first homepage slide</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Slide Modal -->
<div id="slideModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New Slide</h3>
            <button onclick="closeSlideModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="slide_id" id="slide_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Title *</label>
                <input type="text" name="title" id="title" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Subtitle</label>
                <textarea name="subtitle" id="subtitle" rows="2" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Button Text</label>
                    <input type="text" name="button_text" id="button_text" placeholder="e.g., Book Now" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Button Link</label>
                    <input type="text" name="button_link" id="button_link" placeholder="e.g., booking.php" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Slide Image * <span id="imageHint" style="font-weight: normal; color: #666;">(leave blank to keep current)</span></label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Sort Order</label>
                    <input type="number" name="sort_order" id="sort_order" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div style="display: flex; align-items: center; padding-top: 28px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked style="width: 18px; height: 18px;">
                        <span style="font-size: 14px;">Active</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeSlideModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_slide" class="btn btn-primary">Save Slide</button>
            </div>
        </form>
    </div>
</div>

<script>
function openSlideModal() {
    document.getElementById('slideModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New Slide';
    document.getElementById('slide_id').value = '';
    document.getElementById('title').value = '';
    document.getElementById('subtitle').value = '';
    document.getElementById('button_text').value = '';
    document.getElementById('button_link').value = '';
    document.getElementById('sort_order').value = '0';
    document.getElementById('is_active').checked = true;
    document.getElementById('image').required = true;
    document.getElementById('imageHint').style.display = 'none';
}

function editSlide(slide) {
    document.getElementById('slideModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Slide';
    document.getElementById('slide_id').value = slide.slide_id;
    document.getElementById('title').value = slide.title || '';
    document.getElementById('subtitle').value = slide.subtitle || '';
    document.getElementById('button_text').value = slide.button_text || '';
    document.getElementById('button_link').value = slide.button_link || '';
    document.getElementById('sort_order').value = slide.sort_order || 0;
    document.getElementById('is_active').checked = slide.is_active == 1;
    document.getElementById('image').required = false;
    document.getElementById('imageHint').style.display = 'inline';
}

function closeSlideModal() {
    document.getElementById('slideModal').style.display = 'none';
}

document.getElementById('slideModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSlideModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
