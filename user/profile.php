<?php
$pageTitle = 'Profile Settings';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

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
        $updateStmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, country = ?, updated_at = NOW() WHERE user_id = ?");
        if ($updateStmt->execute([$firstName, $lastName, $phone, $address, $city, $country, $userId])) {
            // Update session
            $_SESSION['first_name'] = $firstName;
            $_SESSION['last_name'] = $lastName;
            $success = 'Profile updated successfully!';
            logActivity('Profile updated', 'User ID: ' . $userId);
            
            // Refresh user data
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } else {
            $error = 'Failed to update profile. Please try again.';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-circle" style="color: var(--primary-color); margin-right: 10px;"></i>Personal Information</h3>
    </div>
    
    <div class="card-body">
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
        
        <form method="POST" action="">
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
                <a href="dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/user-footer.php'; ?>
