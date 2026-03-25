<?php
/**
 * Staff Notification Settings Page - Bayawan Bai Hotel
 * Manage notification preferences for staff
 */
$pageTitle = 'Notification Settings';
require_once '../includes/config.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/staff-header.php';
require_once '../includes/notifications.php';

$userId = getUserId();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $type => $settings) {
        $emailEnabled = isset($settings['email']) ? true : false;
        $popupEnabled = isset($settings['popup']) ? true : false;
        updateNotificationSetting($userId, $type, $emailEnabled, $popupEnabled);
    }
    
    $_SESSION['alert'] = ['message' => 'Notification settings saved successfully!', 'type' => 'success'];
    redirect('notification-settings.php');
}

// Get current settings
$settings = getNotificationSettings($userId);
$settingsMap = [];
foreach ($settings as $setting) {
    $settingsMap[$setting['notification_type']] = $setting;
}

$notificationTypes = [
    'booking' => [
        'label' => 'New Bookings',
        'description' => 'Notifications when guests make new room bookings',
        'icon' => 'calendar-check',
        'color' => '#28a745'
    ],
    'food_order' => [
        'label' => 'Food Orders',
        'description' => 'New food orders from guests and order status updates',
        'icon' => 'utensils',
        'color' => '#fd7e14'
    ],
    'payment' => [
        'label' => 'Payments',
        'description' => 'Payment confirmations and transaction updates',
        'icon' => 'credit-card',
        'color' => '#17a2b8'
    ],
    'schedule' => [
        'label' => 'Schedule Changes',
        'description' => 'Updates to your work schedule and shift assignments',
        'icon' => 'clock',
        'color' => '#ffc107'
    ],
    'maintenance' => [
        'label' => 'Maintenance Requests',
        'description' => 'New maintenance requests and issue reports',
        'icon' => 'tools',
        'color' => '#6f42c1'
    ],
    'system' => [
        'label' => 'System Notifications',
        'description' => 'Important system alerts and operational updates',
        'icon' => 'cog',
        'color' => '#6c757d'
    ]
];

$alert = getAlert();
?>

<style>
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .settings-header {
        margin-bottom: 30px;
    }
    
    .settings-header h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
    }
    
    .settings-header p {
        margin: 0;
        color: #666;
    }
    
    .settings-form {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .setting-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 25px;
        border-bottom: 1px solid var(--gray-light);
    }
    
    .setting-item:last-child {
        border-bottom: none;
    }
    
    .setting-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .setting-icon i {
        font-size: 20px;
        color: white;
    }
    
    .setting-details {
        flex: 1;
    }
    
    .setting-details h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 600;
    }
    
    .setting-details p {
        margin: 0;
        font-size: 13px;
        color: #666;
        line-height: 1.5;
    }
    
    .setting-toggles {
        display: flex;
        gap: 20px;
        align-items: center;
    }
    
    .toggle-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .toggle-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    .toggle-group label {
        font-size: 14px;
        cursor: pointer;
        user-select: none;
    }
    
    .global-setting {
        background: var(--gray-light);
        padding: 20px 25px;
        border-bottom: 1px solid var(--gray-medium);
    }
    
    .global-setting h4 {
        margin: 0 0 15px 0;
        font-size: 16px;
    }
    
    .global-toggles {
        display: flex;
        gap: 30px;
    }
    
    .form-footer {
        padding: 20px 25px;
        background: var(--gray-light);
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }
    
    .btn-primary {
        padding: 12px 30px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background 0.3s;
    }
    
    .btn-primary:hover {
        background: var(--secondary-color);
    }
    
    .btn-secondary {
        padding: 12px 30px;
        background: white;
        color: var(--text-color);
        border: 1px solid var(--gray-medium);
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .btn-secondary:hover {
        background: var(--gray-light);
    }
    
    @media (max-width: 768px) {
        .setting-item {
            flex-wrap: wrap;
        }
        
        .setting-toggles {
            width: 100%;
            justify-content: flex-start;
            margin-top: 15px;
        }
        
        .global-toggles {
            flex-direction: column;
            gap: 15px;
        }
        
        .form-footer {
            flex-direction: column;
        }
        
        .btn-primary,
        .btn-secondary {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="settings-container">
    <div class="settings-header">
        <h2><i class="fas fa-cog" style="margin-right: 10px; color: var(--primary-color);"></i>Notification Settings</h2>
        <p>Customize how and when you receive notifications</p>
    </div>
    
    <?php if ($alert): ?>
    <div class="alert alert-<?php echo $alert['type']; ?>" style="margin-bottom: 20px;">
        <i class="fas fa-<?php echo $alert['type'] == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo $alert['message']; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="settings-form">
        <!-- Global Settings -->
        <div class="global-setting">
            <h4><i class="fas fa-sliders-h" style="margin-right: 8px;"></i>Default Preferences (Apply to All)</h4>
            <div class="global-toggles">
                <div class="toggle-group">
                    <input type="checkbox" id="global-email" checked disabled>
                    <label for="global-email">Email Notifications</label>
                </div>
                <div class="toggle-group">
                    <input type="checkbox" id="global-popup" checked disabled>
                    <label for="global-popup">In-App Notifications</label>
                </div>
            </div>
        </div>
        
        <!-- Individual Type Settings -->
        <?php foreach ($notificationTypes as $type => $info): 
            $setting = $settingsMap[$type] ?? null;
            $emailEnabled = $setting ? $setting['email_enabled'] : true;
            $popupEnabled = $setting ? $setting['popup_enabled'] : true;
        ?>
        <div class="setting-item">
            <div class="setting-icon" style="background-color: <?php echo $info['color']; ?>;">
                <i class="fas fa-<?php echo $info['icon']; ?>"></i>
            </div>
            <div class="setting-details">
                <h4><?php echo $info['label']; ?></h4>
                <p><?php echo $info['description']; ?></p>
            </div>
            <div class="setting-toggles">
                <div class="toggle-group">
                    <input type="checkbox" 
                           id="<?php echo $type; ?>-email" 
                           name="settings[<?php echo $type; ?>][email]" 
                           <?php echo $emailEnabled ? 'checked' : ''; ?>>
                    <label for="<?php echo $type; ?>-email">Email</label>
                </div>
                <div class="toggle-group">
                    <input type="checkbox" 
                           id="<?php echo $type; ?>-popup" 
                           name="settings[<?php echo $type; ?>][popup]" 
                           <?php echo $popupEnabled ? 'checked' : ''; ?>>
                    <label for="<?php echo $type; ?>-popup">In-App</label>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="form-footer">
            <a href="staff-dashboard.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save" style="margin-right: 8px;"></i>Save Settings
            </button>
        </div>
    </form>
</div>

</body>
</html>
