<?php
$pageTitle = 'Staff Permissions - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle global settings update
if (isset($_POST['update_global_settings'])) {
    $allowAllInventory = isset($_POST['allow_all_inventory']) ? 'true' : 'false';
    $allowAllMaintenance = isset($_POST['allow_all_maintenance']) ? 'true' : 'false';
    $allowAllBookingCharges = isset($_POST['allow_all_booking_charges']) ? 'true' : 'false';
    $allowAllContactMessages = isset($_POST['allow_all_contact_messages']) ? 'true' : 'false';
    
    setStaffPermissionSetting('allow_all_staff_inventory', $allowAllInventory);
    setStaffPermissionSetting('allow_all_staff_maintenance', $allowAllMaintenance);
    setStaffPermissionSetting('allow_all_staff_booking_charges', $allowAllBookingCharges);
    setStaffPermissionSetting('allow_all_staff_contact_messages', $allowAllContactMessages);
    
    $_SESSION['success'] = 'Global permission settings updated successfully';
    redirect('admin-staff-permissions.php');
}

// Handle individual permission update
if (isset($_POST['update_staff_permissions'])) {
    $userId = $_POST['user_id'] ?? 0;
    $permissions = $_POST['permissions'] ?? [];
    
    if ($userId) {
        // Get staff name for message
        $staffStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $staffStmt->execute([$userId]);
        $staffData = $staffStmt->fetch();
        $staffName = $staffData ? $staffData['first_name'] . ' ' . $staffData['last_name'] : 'Staff';
        
        $pages = getPermissionPages();
        foreach ($pages as $pageKey => $pageInfo) {
            $canAccess = in_array($pageKey, $permissions) ? 1 : 0;
            setStaffPermission($userId, $pageKey, $canAccess);
        }
        $_SESSION['success'] = $staffName . '\'s permissions updated successfully';
    }
    redirect('admin-staff-permissions.php');
}

// Get all staff members
$staffMembers = $db->query("
    SELECT user_id, first_name, last_name, email, role, status 
    FROM users 
    WHERE role IN ('receptionist', 'manager') 
    AND status = 'active'
    ORDER BY first_name, last_name
")->fetchAll();

// Get global settings
$globalSettings = [
    'inventory' => getStaffPermissionSetting('allow_all_staff_inventory') === 'true',
    'maintenance' => getStaffPermissionSetting('allow_all_staff_maintenance') === 'true',
    'booking_charges' => getStaffPermissionSetting('allow_all_staff_booking_charges') === 'true',
    'contact_messages' => getStaffPermissionSetting('allow_all_staff_contact_messages') === 'true'
];

// Get permission pages
$permissionPages = getPermissionPages();

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h2 style="margin-bottom: 5px;"><i class="fas fa-user-shield" style="color: var(--primary-color); margin-right: 10px;"></i>Staff Permissions</h2>
                <p style="color: #666; margin: 0;">Manage access permissions for staff members</p>
            </div>
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <!-- Global Settings Card -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); background-color: #f8f9fa;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-globe" style="color: var(--primary-color); margin-right: 10px;"></i>Global Permission Settings</h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 14px;">Enable "Allow all staff" to grant access to all staff members for specific pages</p>
            </div>
            <div style="padding: 30px;">
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                        <?php foreach ($permissionPages as $pageKey => $pageInfo): ?>
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid <?php echo $globalSettings[$pageKey] ? '#28a745' : '#dee2e6'; ?>;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                                <div style="width: 45px; height: 45px; background-color: var(--primary-color); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas <?php echo $pageInfo['icon']; ?>" style="color: white; font-size: 18px;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($pageInfo['name']); ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($pageInfo['file']); ?></div>
                                </div>
                            </div>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; background-color: white; border-radius: 5px;">
                                <input type="checkbox" name="allow_all_<?php echo $pageKey; ?>" <?php echo $globalSettings[$pageKey] ? 'checked' : ''; ?> style="width: 20px; height: 20px; cursor: pointer;">
                                <span style="font-weight: 500;">Allow all staff</span>
                            </label>
                            <?php if ($globalSettings[$pageKey]): ?>
                            <div style="margin-top: 10px; padding: 8px 12px; background-color: #d4edda; border-radius: 5px; font-size: 12px; color: #155724;">
                                <i class="fas fa-check-circle"></i> All staff have access
                            </div>
                            <?php else: ?>
                            <div style="margin-top: 10px; padding: 8px 12px; background-color: #fff3cd; border-radius: 5px; font-size: 12px; color: #856404;">
                                <i class="fas fa-lock"></i> Individual permissions required
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; justify-content: flex-end;">
                        <button type="submit" name="update_global_settings" class="btn btn-primary" style="padding: 12px 30px;">
                            <i class="fas fa-save" style="margin-right: 8px;"></i>Save Global Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Staff Members Permissions -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); background-color: #f8f9fa;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-users" style="color: var(--primary-color); margin-right: 10px;"></i>Individual Staff Permissions</h3>
                <p style="color: #666; margin: 5px 0 0; font-size: 14px;">Assign specific permissions to individual staff members (only applies when "Allow all staff" is disabled)</p>
            </div>
            
            <?php if (count($staffMembers) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Staff Member</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Role</th>
                            <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;"><i class="fas fa-boxes"></i> Inventory</th>
                            <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;"><i class="fas fa-tools"></i> Maintenance</th>
                            <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;"><i class="fas fa-file-invoice-dollar"></i> Charges</th>
                            <th style="padding: 15px 20px; text-align: center; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staffMembers as $staff): 
                            $staffPermissions = getAllStaffPermissions($staff['user_id']);
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; flex-shrink: 0; aspect-ratio: 1;">
                                        <?php echo strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($staff['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2; text-transform: capitalize;">
                                    <?php echo $staff['role']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <?php if ($globalSettings['inventory']): ?>
                                    <span style="color: #28a745;"><i class="fas fa-check-circle"></i> All Staff</span>
                                <?php else: ?>
                                    <span style="color: <?php echo isset($staffPermissions['inventory']) && $staffPermissions['inventory'] ? '#28a745' : '#dc3545'; ?>;">
                                        <i class="fas <?php echo isset($staffPermissions['inventory']) && $staffPermissions['inventory'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo isset($staffPermissions['inventory']) && $staffPermissions['inventory'] ? 'Yes' : 'No'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <?php if ($globalSettings['maintenance']): ?>
                                    <span style="color: #28a745;"><i class="fas fa-check-circle"></i> All Staff</span>
                                <?php else: ?>
                                    <span style="color: <?php echo isset($staffPermissions['maintenance']) && $staffPermissions['maintenance'] ? '#28a745' : '#dc3545'; ?>;">
                                        <i class="fas <?php echo isset($staffPermissions['maintenance']) && $staffPermissions['maintenance'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo isset($staffPermissions['maintenance']) && $staffPermissions['maintenance'] ? 'Yes' : 'No'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <?php if ($globalSettings['booking_charges']): ?>
                                    <span style="color: #28a745;"><i class="fas fa-check-circle"></i> All Staff</span>
                                <?php else: ?>
                                    <span style="color: <?php echo isset($staffPermissions['booking_charges']) && $staffPermissions['booking_charges'] ? '#28a745' : '#dc3545'; ?>;">
                                        <i class="fas <?php echo isset($staffPermissions['booking_charges']) && $staffPermissions['booking_charges'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo isset($staffPermissions['booking_charges']) && $staffPermissions['booking_charges'] ? 'Yes' : 'No'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px; text-align: center;">
                                <button type="button" onclick="openPermissionModal(<?php echo htmlspecialchars(json_encode($staff)); ?>, <?php echo htmlspecialchars(json_encode($staffPermissions)); ?>)" class="btn btn-sm btn-primary" style="padding: 8px 16px;">
                                    <i class="fas fa-edit"></i> Edit Permissions
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-users" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No Staff Members Found</h3>
                <p style="color: #999;">There are no active staff members (receptionists or managers) in the system.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Permission Modal -->
<div id="permissionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 500px; max-height: 80vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa;">
            <h3 style="font-size: 20px; margin: 0;"><i class="fas fa-user-cog" style="color: var(--primary-color); margin-right: 10px;"></i>Edit Staff Permissions</h3>
            <button onclick="closePermissionModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div style="margin-bottom: 25px;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
                    <div style="width: 50px; height: 50px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 18px;" id="modalAvatar">
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 16px;" id="modalStaffName"></div>
                        <div style="font-size: 13px; color: #666;" id="modalStaffRole"></div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 15px; color: #333;">Page Access Permissions</label>
                
                <?php foreach ($permissionPages as $pageKey => $pageInfo): ?>
                <div style="margin-bottom: 15px; padding: 15px; border: 1px solid var(--gray-light); border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="permissions[]" value="<?php echo $pageKey; ?>" id="perm_<?php echo $pageKey; ?>" style="width: 20px; height: 20px; cursor: pointer;">
                        <div style="flex: 1;">
                            <div style="font-weight: 600; font-size: 14px;"><i class="fas <?php echo $pageInfo['icon']; ?>" style="color: var(--primary-color); margin-right: 8px;"></i><?php echo htmlspecialchars($pageInfo['name']); ?></div>
                            <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($pageInfo['file']); ?></div>
                        </div>
                    </label>
                    <?php if ($globalSettings[$pageKey]): ?>
                    <div style="margin-top: 10px; padding: 8px 12px; background-color: #d4edda; border-radius: 5px; font-size: 12px; color: #155724;">
                        <i class="fas fa-info-circle"></i> "Allow all staff" is enabled for this page
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closePermissionModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_staff_permissions" class="btn btn-primary">
                    <i class="fas fa-save" style="margin-right: 8px;"></i>Save Permissions
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPermissionModal(staff, permissions) {
    document.getElementById('permissionModal').style.display = 'flex';
    document.getElementById('modalUserId').value = staff.user_id;
    document.getElementById('modalStaffName').textContent = staff.first_name + ' ' + staff.last_name;
    document.getElementById('modalStaffRole').textContent = staff.role.charAt(0).toUpperCase() + staff.role.slice(1);
    document.getElementById('modalAvatar').textContent = (staff.first_name.charAt(0) + staff.last_name.charAt(0)).toUpperCase();
    
    // Reset all checkboxes
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
        cb.checked = false;
    });
    
    // Check boxes based on existing permissions
    if (permissions) {
        Object.keys(permissions).forEach(page => {
            if (permissions[page]) {
                const cb = document.getElementById('perm_' + page);
                if (cb) cb.checked = true;
            }
        });
    }
}

function closePermissionModal() {
    document.getElementById('permissionModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('permissionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePermissionModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
