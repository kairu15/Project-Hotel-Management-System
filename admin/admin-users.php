<?php
$pageTitle = 'Manage Users - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit user
if (isset($_POST['save_user'])) {
    $userId = $_POST['user_id'] ?? null;
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $role = $_POST['role'] ?? 'guest';
    $password = $_POST['password'] ?? '';
    
    if ($firstName && $lastName && $email) {
        // Check if email exists (for new users or if email changed)
        $checkStmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $checkStmt->execute([$email, $userId ?: 0]);
        if ($checkStmt->fetch()) {
            $_SESSION['error'] = 'Email already exists';
        } else {
            if ($userId) {
                // Update existing user
                if ($password) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, role = ?, password = ? WHERE user_id = ?");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $address, $city, $country, $role, $hashedPassword, $userId]);
                } else {
                    $stmt = $db->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, city = ?, country = ?, role = ? WHERE user_id = ?");
                    $stmt->execute([$firstName, $lastName, $email, $phone, $address, $city, $country, $role, $userId]);
                }
                $_SESSION['success'] = 'User ' . $firstName . ' ' . $lastName . ' updated successfully';
            } else {
                // Add new user
                if (!$password) {
                    $_SESSION['error'] = 'Password is required for new users';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, phone, address, city, country, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone, $address, $city, $country, $role])) {
                        $_SESSION['success'] = 'User ' . $firstName . ' ' . $lastName . ' added successfully';
                    } else {
                        $_SESSION['error'] = 'Failed to add user';
                    }
                }
            }
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-users.php');
}

// Handle delete user
if (isset($_POST['delete_user'])) {
    $userId = $_POST['user_id'] ?? 0;
    if ($userId) {
        // Get user name before deletion
        $nameStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $nameStmt->execute([$userId]);
        $userData = $nameStmt->fetch();
        $userName = $userData ? $userData['first_name'] . ' ' . $userData['last_name'] : 'User';
        
        // Prevent deleting own account
        if ($userId == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Cannot delete your own account';
        } else {
            // Check if user has bookings
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            if ($checkStmt->fetchColumn() > 0) {
                $_SESSION['error'] = 'Cannot delete user with booking history';
            } else {
                // Delete related records from chatbot_context first
                $deleteContextStmt = $db->prepare("DELETE FROM chatbot_context WHERE user_id = ?");
                $deleteContextStmt->execute([$userId]);
                
                // Delete user sessions
                $deleteSessionsStmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $deleteSessionsStmt->execute([$userId]);
                
                $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
                if ($stmt->execute([$userId])) {
                    $_SESSION['success'] = 'User ' . $userName . ' deleted successfully';
                } else {
                    $_SESSION['error'] = 'Failed to delete user';
                }
            }
        }
    }
    redirect('admin-users.php');
}

// Get filter parameters
$roleFilter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "
    SELECT u.*, 
           (SELECT COUNT(*) FROM bookings WHERE user_id = u.user_id) as booking_count,
           (SELECT MAX(created_at) FROM bookings WHERE user_id = u.user_id) as last_booking,
           (SELECT COUNT(*) FROM user_sessions WHERE user_id = u.user_id AND expires_at > NOW()) as session_count
    FROM users u 
    WHERE 1=1
";
$params = [];

if ($roleFilter) {
    $sql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get role counts
$roleCounts = $db->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<!-- Users Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openUserModal()" class="btn btn-primary">Add New User</button>
        </div>
        <!-- Role Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #6c757d;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $roleCounts['guest'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Guests</p>
                    </div>
                    <i class="fas fa-users" style="font-size: 40px; color: #6c757d;"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $roleCounts['staff'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Staff</p>
                    </div>
                    <i class="fas fa-user-tie" style="font-size: 40px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #dc3545;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $roleCounts['admin'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Admins</p>
                    </div>
                    <i class="fas fa-user-shield" style="font-size: 40px; color: #dc3545;"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Role</label>
                    <select name="role" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Roles</option>
                        <option value="guest" <?php echo $roleFilter === 'guest' ? 'selected' : ''; ?>>Guest</option>
                        <option value="staff" <?php echo $roleFilter === 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-users.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">All Users (<?php echo count($users); ?>)</h3>
            </div>
            
            <?php if (count($users) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">User</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Contact</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Role</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Bookings</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Member Since</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $roleColors = [
                                'guest' => ['#d4edda', '#155724'],
                                'staff' => ['#cce5ff', '#004085'],
                                'admin' => ['#f8d7da', '#721c24']
                            ];
                            $color = $roleColors[$user['role']] ?? ['#fff3cd', '#856404'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; min-width: 40px; min-height: 40px; border-radius: 50%; background-color: var(--primary-color); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; flex-shrink: 0; aspect-ratio: 1;">
                                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;">ID: <?php echo $user['user_id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div><?php echo htmlspecialchars($user['email']); ?></div>
                                <?php if ($user['phone']): ?>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($user['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($user['session_count'] > 0 || $user['active_status']): ?>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="width: 10px; height: 10px; background-color: #28a745; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #28a745;"></span>
                                        <span style="font-size: 12px; font-weight: 600; color: #28a745;">Online</span>
                                    </div>
                                    <?php if ($user['last_login']): ?>
                                        <div style="font-size: 11px; color: #666; margin-top: 3px;">Last seen: <?php echo formatDate($user['last_login'], 'M d, Y H:i'); ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="width: 10px; height: 10px; background-color: #6c757d; border-radius: 50%; display: inline-block;"></span>
                                        <span style="font-size: 12px; font-weight: 600; color: #6c757d;">Offline</span>
                                    </div>
                                    <?php if ($user['last_login']): ?>
                                        <div style="font-size: 11px; color: #666; margin-top: 3px;">Last seen: <?php echo formatDate($user['last_login'], 'M d, Y H:i'); ?></div>
                                    <?php else: ?>
                                        <div style="font-size: 11px; color: #999; margin-top: 3px;">Never logged in</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600;"><?php echo $user['booking_count']; ?></span> bookings
                                <?php if ($user['last_booking']): ?>
                                <div style="font-size: 12px; color: #666;">Last: <?php echo formatDate($user['last_booking'], 'M d, Y'); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;"><?php echo formatDate($user['created_at'], 'M d, Y'); ?></td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="" style="display: inline;" id="deleteUserForm<?php echo $user['user_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('deleteUserForm<?php echo $user['user_id']; ?>', 'Delete User', 'Are you sure you want to delete <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>?', null, 'delete_user')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-users" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No users found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- User Modal -->
<div id="userModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add New User</h3>
            <button onclick="closeUserModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="user_id" id="user_id">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">First Name *</label>
                    <input type="text" name="first_name" id="first_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Last Name *</label>
                    <input type="text" name="last_name" id="last_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Email *</label>
                    <input type="email" name="email" id="email" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Phone</label>
                    <input type="text" name="phone" id="phone" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Address</label>
                <input type="text" name="address" id="address" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">City</label>
                    <input type="text" name="city" id="city" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Country</label>
                    <input type="text" name="country" id="country" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Role</label>
                    <select name="role" id="role" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <option value="guest">Guest</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Password <span id="passwordHint" style="font-weight: normal; color: #666;">(leave blank to keep current)</span></label>
                    <input type="password" name="password" id="password" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeUserModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_user" class="btn btn-primary">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal() {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('user_id').value = '';
    document.getElementById('first_name').value = '';
    document.getElementById('last_name').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('address').value = '';
    document.getElementById('city').value = '';
    document.getElementById('country').value = '';
    document.getElementById('role').value = 'guest';
    document.getElementById('password').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordHint').style.display = 'none';
}

function editUser(user) {
    document.getElementById('userModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('user_id').value = user.user_id;
    document.getElementById('first_name').value = user.first_name;
    document.getElementById('last_name').value = user.last_name;
    document.getElementById('email').value = user.email;
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('address').value = user.address || '';
    document.getElementById('city').value = user.city || '';
    document.getElementById('country').value = user.country || '';
    document.getElementById('role').value = user.role;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('passwordHint').style.display = 'inline';
}

function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
