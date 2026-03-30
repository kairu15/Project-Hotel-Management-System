<?php
$pageTitle = 'My Profile';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/admin-header.php';

$db = getDB();
$userId = getUserId();
$error = '';
$success = '';

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $country = sanitizeInput($_POST['country'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required';
    } else {
        // Handle profile picture upload
        $profilePicture = $user['profile_picture'] ?? null;
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/profile_pictures/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileInfo = pathinfo($_FILES['profile_picture']['name']);
            $extension = strtolower($fileInfo['extension']);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($extension, $allowedExtensions)) {
                // Generate unique filename
                $filename = 'admin_' . $userId . '_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
                    // Delete old profile picture if exists
                    if ($profilePicture && file_exists('../' . $profilePicture)) {
                        unlink('../' . $profilePicture);
                    }
                    $profilePicture = 'assets/uploads/profile_pictures/' . $filename;
                } else {
                    $error = 'Failed to upload profile picture.';
                }
            } else {
                $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
            }
        }
        
        if (empty($error)) {
            $updateStmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, country = ?, profile_picture = ?, updated_at = NOW() WHERE user_id = ?");
            if ($updateStmt->execute([$firstName, $lastName, $phone, $address, $city, $country, $profilePicture, $userId])) {
                // Update session
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $success = 'Profile updated successfully!';
                logActivity('Admin profile updated', 'User ID: ' . $userId);
                
                // Refresh user data
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}
?>

<div class="page-content">
    <div class="container">
        <!-- Page Header -->
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 28px; margin-bottom: 10px;"><i class="fas fa-user-circle" style="color: var(--primary-color); margin-right: 10px;"></i>My Profile</h2>
            <p style="color: #666;">Manage your personal information and profile picture</p>
        </div>

        <!-- Profile Card -->
        <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
                <h3 style="font-size: 20px; margin: 0;">Personal Information</h3>
            </div>
            
            <div style="padding: 30px;">
                <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom: 25px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 25px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Profile Picture Section -->
                    <div style="text-align: center; margin-bottom: 30px; padding: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 15px;">
                        <div style="position: relative; display: inline-block;">
                            <?php if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])): ?>
                                <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" 
                                     style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 4px solid var(--primary-color); box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            <?php else: ?>
                                <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color) 0%, #285F6B 100%); display: flex; align-items: center; justify-content: center; border: 4px solid #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                    <i class="fas fa-user" style="font-size: 60px; color: white;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <label for="profile_picture" style="display: inline-block; padding: 12px 24px; background: var(--primary-color); color: white; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s;">
                                <i class="fas fa-camera" style="margin-right: 8px;"></i>Change Photo
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewImage(this)">
                            <p style="font-size: 12px; color: #666; margin-top: 10px;">JPG, PNG, GIF, WebP (Max 5MB)</p>
                        </div>
                        
                        <!-- Image Preview Container -->
                        <div id="imagePreview" style="display: none; margin-top: 15px;">
                            <img id="preview" src="" alt="Preview" style="width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid var(--primary-color);">
                            <p style="font-size: 13px; color: var(--primary-color); margin-top: 5px;">New photo selected</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required
                                style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required
                                style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled
                            style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px; background-color: var(--gray-light);">
                        <p style="font-size: 13px; color: #666; margin-top: 5px;">Email cannot be changed. <a href="../contact.php" style="color: var(--primary-color);">Contact support</a> for assistance.</p>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Role</label>
                        <input type="text" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled
                            style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px; background-color: var(--gray-light);">
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Phone Number</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                            style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Address</label>
                        <textarea name="address" rows="3" 
                            style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px; resize: vertical;"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>"
                                style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once '../includes/admin-footer.php'; ?>
