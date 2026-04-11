<?php
/**
 * General Operations - Bayawan Bai Hotel
 * Hotel operations overview and quick access panel
 */
$pageTitle = 'General Operations';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$userId = getUserId();
$userRole = getUserRole();
$today = date('Y-m-d');

// Get today's quick stats
$quickStats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM bookings WHERE check_in = '$today' AND status = 'confirmed') as arrivals,
        (SELECT COUNT(*) FROM bookings WHERE check_out = '$today' AND status = 'checked_in') as departures,
        (SELECT COUNT(*) FROM bookings WHERE status = 'checked_in') as currently_staying,
        (SELECT COUNT(*) FROM maintenance_requests WHERE status IN ('pending', 'in_progress')) as pending_maintenance,
        (SELECT COUNT(*) FROM food_orders WHERE status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM event_bookings WHERE status = 'pending') as pending_events
")->fetch();

// Get recent activities (handle missing table gracefully)
try {
    $recentActivities = $db->query("
        SELECT * FROM activity_logs 
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll();
} catch (PDOException $e) {
    $recentActivities = [];
}

// Get operation settings
$settings = $db->query("SELECT * FROM settings WHERE setting_group = 'operations' OR setting_key LIKE '%time%' ORDER BY setting_key")
    ->fetchAll();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_setting'])) {
    $settingKey = $_POST['setting_key'] ?? '';
    $settingValue = $_POST['setting_value'] ?? '';
    
    if ($settingKey) {
        $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$settingValue, $settingKey]);
        $_SESSION['success'] = 'Setting updated successfully';
        logActivity("Updated operation setting: $settingKey");
    }
    redirect('admin-operations.php');
}

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>General Operations</h2>
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <!-- Quick Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <a href="admin-bookings.php" style="text-decoration: none;">
                <div style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-sign-in-alt" style="font-size: 28px; margin-bottom: 10px; opacity: 0.9;"></i>
                    <h3 style="margin: 0; font-size: 28px; color: white;"><?php echo $quickStats['arrivals']; ?></h3>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Today's Arrivals</p>
                </div>
            </a>
            <a href="admin-bookings.php" style="text-decoration: none;">
                <div style="background: linear-gradient(135deg, #ffc107, #ff9800); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-sign-out-alt" style="font-size: 28px; margin-bottom: 10px; opacity: 0.9;"></i>
                    <h3 style="margin: 0; font-size: 28px; color: white;"><?php echo $quickStats['departures']; ?></h3>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Today's Departures</p>
                </div>
            </a>
            <div style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <i class="fas fa-bed" style="font-size: 28px; margin-bottom: 10px; opacity: 0.9;"></i>
                <h3 style="margin: 0; font-size: 28px; color: white;"><?php echo $quickStats['currently_staying']; ?></h3>
                <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Currently Staying</p>
            </div>
            <a href="admin-food-orders.php" style="text-decoration: none;">
                <div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-utensils" style="font-size: 28px; margin-bottom: 10px; opacity: 0.9;"></i>
                    <h3 style="margin: 0; font-size: 28px; color: white;"><?php echo $quickStats['pending_orders']; ?></h3>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Pending Orders</p>
                </div>
            </a>
            <a href="admin-maintenance.php" style="text-decoration: none;">
                <div style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-tools" style="font-size: 28px; margin-bottom: 10px; opacity: 0.9;"></i>
                    <h3 style="margin: 0; font-size: 28px; color: white;"><?php echo $quickStats['pending_maintenance']; ?></h3>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Maintenance Issues</p>
                </div>
            </a>
            <a href="admin-event-bookings.php" style="text-decoration: none;">
                <div style="background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                    <i class="fas fa-calendar-alt" style="font-size: 28px; margin-bottom: 10px; opacity: 0.9;"></i>
                    <h3 style="margin: 0; font-size: 28px; color: white;"><?php echo $quickStats['pending_events']; ?></h3>
                    <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Pending Events</p>
                </div>
            </a>
        </div>

        <!-- Operations Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px;">
            <!-- Quick Actions -->
            <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="background: var(--dark-color); color: white; padding: 15px 20px;">
                    <h4 style="margin: 0; color: white;"><i class="fas fa-bolt" style="margin-right: 10px;"></i>Quick Actions</h4>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <a href="../staff/walkin-booking.php" style="background: var(--gray-light); padding: 15px; border-radius: 8px; text-decoration: none; color: var(--text-color); text-align: center; transition: all 0.3s;">
                            <i class="fas fa-walking" style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                            <span style="font-size: 13px;">Walk-in Booking</span>
                        </a>
                        <a href="../staff/staff-qr-scanner.php" style="background: var(--gray-light); padding: 15px; border-radius: 8px; text-decoration: none; color: var(--text-color); text-align: center;">
                            <i class="fas fa-qrcode" style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                            <span style="font-size: 13px;">QR Scanner</span>
                        </a>
                        <a href="admin-active.php" style="background: var(--gray-light); padding: 15px; border-radius: 8px; text-decoration: none; color: var(--text-color); text-align: center;">
                            <i class="fas fa-users" style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                            <span style="font-size: 13px;">Active Staff</span>
                        </a>
                        <a href="admin-tasks.php" style="background: var(--gray-light); padding: 15px; border-radius: 8px; text-decoration: none; color: var(--text-color); text-align: center;">
                            <i class="fas fa-tasks" style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                            <span style="font-size: 13px;">Staff Tasks</span>
                        </a>
                        <a href="../admin/admin-calendar.php" style="background: var(--gray-light); padding: 15px; border-radius: 8px; text-decoration: none; color: var(--text-color); text-align: center;">
                            <i class="fas fa-calendar" style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                            <span style="font-size: 13px;">Calendar</span>
                        </a>
                        <a href="../staff/notifications.php" style="background: var(--gray-light); padding: 15px; border-radius: 8px; text-decoration: none; color: var(--text-color); text-align: center;">
                            <i class="fas fa-bell" style="font-size: 20px; color: var(--primary-color); margin-bottom: 8px; display: block;"></i>
                            <span style="font-size: 13px;">Notifications</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Operation Settings -->
            <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="background: var(--dark-color); color: white; padding: 15px 20px;">
                    <h4 style="margin: 0; color: white;"><i class="fas fa-cog" style="margin-right: 10px;"></i>Operation Settings</h4>
                </div>
                <div style="padding: 20px;">
                    <?php foreach ($settings as $setting): ?>
                    <form method="POST" action="" style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--gray-light);">
                        <div style="flex: 1;">
                            <label style="display: block; font-size: 12px; color: #666; margin-bottom: 3px; text-transform: capitalize;">
                                <?php echo str_replace('_', ' ', $setting['setting_key']); ?>
                            </label>
                            <input type="text" name="setting_value" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" 
                                style="width: 100%; padding: 8px 12px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 13px;">
                        </div>
                        <input type="hidden" name="setting_key" value="<?php echo $setting['setting_key']; ?>">
                        <button type="submit" name="update_setting" style="background: var(--primary-color); color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; margin-top: 18px;">
                            <i class="fas fa-save"></i>
                        </button>
                    </form>
                    <?php endforeach; ?>
                    
                    <?php if (empty($settings)): ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No operation settings found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="background: var(--dark-color); color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0; color: white;"><i class="fas fa-history" style="margin-right: 10px;"></i>Recent Activity</h4>
                    <a href="admin-activity-logs.php" style="color: white; font-size: 12px; opacity: 0.8;">View All</a>
                </div>
                <div style="padding: 0; max-height: 350px; overflow-y: auto;">
                    <?php if (empty($recentActivities)): ?>
                    <p style="text-align: center; color: #999; padding: 30px;">No recent activities.</p>
                    <?php else: ?>
                    <?php foreach ($recentActivities as $activity): ?>
                    <div style="padding: 15px 20px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; align-items: start; gap: 12px;">
                            <div style="width: 32px; height: 32px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0 0 3px 0; font-size: 13px; color: #333;">
                                    <strong>Staff #<?php echo $activity['user_id'] ?? 'N/A'; ?></strong>
                                    <?php echo htmlspecialchars($activity['action'] ?? ''); ?>
                                </p>
                                <p style="margin: 0; font-size: 11px; color: #999;">
                                    <?php 
                                    if (!empty($activity['created_at'])) {
                                        echo date('M d, Y H:i', strtotime($activity['created_at']));
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status -->
            <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                <div style="background: var(--dark-color); color: white; padding: 15px 20px;">
                    <h4 style="margin: 0; color: white;"><i class="fas fa-server" style="margin-right: 10px;"></i>System Status</h4>
                </div>
                <div style="padding: 20px;">
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 13px; color: #666;">Database Connection</span>
                            <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 11px;">Connected</span>
                        </div>
                        <div style="height: 6px; background: var(--gray-light); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 13px; color: #666;">Payment Gateway</span>
                            <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 11px;">Active</span>
                        </div>
                        <div style="height: 6px; background: var(--gray-light); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 13px; color: #666;">Email Notifications</span>
                            <span style="background: #17a2b8; color: white; padding: 3px 10px; border-radius: 15px; font-size: 11px;">Enabled</span>
                        </div>
                        <div style="height: 6px; background: var(--gray-light); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: 90%; background: linear-gradient(90deg, #17a2b8, #138496); border-radius: 3px;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <span style="font-size: 13px; color: #666;">QR Scanner Service</span>
                            <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 11px;">Active</span>
                        </div>
                        <div style="height: 6px; background: var(--gray-light); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; width: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 3px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
