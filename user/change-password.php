<?php
$pageTitle = 'Change Password';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

// Get user data for the sidebar
$db = getDB();
$userId = getUserId();
$userStmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

$error = '';
$success = '';

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long';
    } elseif ($newPassword === $currentPassword) {
        $error = 'New password must be different from current password';
    } else {
        $db = getDB();
        
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && $user['password'] === $currentPassword) {
            // Update password (plaintext)
            $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            
            if ($updateStmt->execute([$newPassword, $_SESSION['user_id']])) {
                $success = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] . '\'s password changed successfully!';
                logActivity('Password changed', 'User ID: ' . $_SESSION['user_id']);
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        } else {
            $error = 'Current password is incorrect';
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-lock" style="color: var(--primary-color); margin-right: 10px;"></i>Change Password</h3>
    </div>
    
    <div class="card-body">
        <p style="color: #666; margin-bottom: 25px;">Update your account password to keep your account secure</p>
        
        <?php if ($error): ?>
        <div class="alert alert-danger" style="margin: 0 0 25px 0;">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success" style="margin: 0 0 25px 0;">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <div style="background-color: var(--gray-light); padding: 20px; border-radius: 8px; margin-bottom: 25px;">
            <h4 style="font-size: 14px; margin-bottom: 10px; color: var(--dark-color);"><i class="fas fa-shield-alt" style="color: var(--primary-color); margin-right: 8px;"></i>Password Requirements</h4>
            <ul style="list-style: none; font-size: 13px; color: #666; margin: 0; padding: 0;">
                <li style="margin-bottom: 5px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-check" style="color: var(--success-color); font-size: 12px;"></i> At least 8 characters long</li>
                <li style="margin-bottom: 5px; display: flex; align-items: center; gap: 8px;"><i class="fas fa-check" style="color: var(--success-color); font-size: 12px;"></i> Must be different from your current password</li>
                <li style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-check" style="color: var(--success-color); font-size: 12px;"></i> New password and confirmation must match</li>
            </ul>
        </div>
        
        <form method="POST" action="" style="max-width: 500px;">
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 600; color: var(--dark-color); margin-bottom: 8px;">Current Password <span style="color: #dc3545;">*</span></label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary-color);"></i>
                    <input type="password" name="current_password" required id="current_password" placeholder="Enter your current password" style="width: 100%; padding: 14px 45px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    <button type="button" onclick="togglePassword('current_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 16px; padding: 5px;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 600; color: var(--dark-color); margin-bottom: 8px;">New Password <span style="color: #dc3545;">*</span></label>
                <div style="position: relative;">
                    <i class="fas fa-key" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary-color);"></i>
                    <input type="password" name="new_password" required minlength="8" id="new_password" placeholder="Enter new password" style="width: 100%; padding: 14px 45px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    <button type="button" onclick="togglePassword('new_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 16px; padding: 5px;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <p style="font-size: 12px; color: #666; margin-top: 5px;">Minimum 8 characters</p>
            </div>
            
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 600; color: var(--dark-color); margin-bottom: 8px;">Confirm New Password <span style="color: #dc3545;">*</span></label>
                <div style="position: relative;">
                    <i class="fas fa-key" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary-color);"></i>
                    <input type="password" name="confirm_password" required minlength="8" id="confirm_password" placeholder="Confirm new password" style="width: 100%; padding: 14px 45px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    <button type="button" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 16px; padding: 5px;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Password
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        const icon = button.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

<?php require_once '../includes/user-footer.php'; ?>
