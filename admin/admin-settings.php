<?php
$pageTitle = 'Manage Settings - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle save settings
if (isset($_POST['save_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        // Check if setting exists
        $checkStmt = $db->prepare("SELECT setting_id FROM settings WHERE setting_key = ?");
        $checkStmt->execute([$key]);
        if ($checkStmt->fetch()) {
            // Update
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        } else {
            // Insert
            $group = explode('_', $key)[0];
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_group) VALUES (?, ?, ?)");
            $stmt->execute([$key, $value, $group]);
        }
    }
    $_SESSION['success'] = 'Settings saved successfully';
    redirect('admin-settings.php');
}

// Get all settings grouped by group
$settings = $db->query("SELECT * FROM settings ORDER BY setting_group, setting_key")->fetchAll();
$groupedSettings = [];
foreach ($settings as $setting) {
    $group = $setting['setting_group'] ?: 'general';
    $groupedSettings[$group][] = $setting;
}

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <form method="POST" action="">
            <?php foreach ($groupedSettings as $group => $groupSettings): ?>
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
                <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); background-color: var(--primary-color); color: white;">
                    <h3 style="font-size: 18px; margin: 0; text-transform: capitalize;"><i class="fas fa-cog"></i> <?php echo htmlspecialchars($group); ?> Settings</h3>
                </div>
                <div style="padding: 30px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <?php foreach ($groupSettings as $setting): ?>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; text-transform: capitalize;">
                                <?php echo str_replace('_', ' ', preg_replace('/^' . $group . '_/', '', $setting['setting_key'])); ?>
                            </label>
                            <?php if (strpos($setting['setting_value'], '\n') !== false || strlen($setting['setting_value']) > 50): ?>
                            <textarea name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" rows="3" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                            <?php else: ?>
                            <input type="text" name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Add New Setting -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
                <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-plus-circle"></i> Add New Setting</h3>
                </div>
                <div style="padding: 30px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Setting Key</label>
                            <input type="text" name="new_setting_key" placeholder="e.g., hotel_name" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Setting Value</label>
                            <input type="text" name="new_setting_value" placeholder="Setting value" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Group</label>
                            <input type="text" name="new_setting_group" placeholder="e.g., general" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 30px;">
                <button type="submit" name="save_settings" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
