<?php
$pageTitle = 'Manage Team - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle add/edit team member
if (isset($_POST['save_member'])) {
    $memberId = $_POST['member_id'] ?? null;
    $fullName = $_POST['full_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $description = $_POST['description'] ?? '';
    $displayOrder = $_POST['display_order'] ?? 0;
    $status = $_POST['status'] ?? 'active';

    // Handle image upload
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../assets/images/team/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $uploadFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $image = 'images/team/' . $fileName;
        }
    }

    if ($fullName && $position) {
        if ($memberId) {
            // Update existing member
            if ($image) {
                $stmt = $db->prepare("UPDATE team_members SET full_name = ?, position = ?, description = ?, image = ?, display_order = ?, status = ? WHERE member_id = ?");
                $stmt->execute([$fullName, $position, $description, $image, $displayOrder, $status, $memberId]);
            } else {
                $stmt = $db->prepare("UPDATE team_members SET full_name = ?, position = ?, description = ?, display_order = ?, status = ? WHERE member_id = ?");
                $stmt->execute([$fullName, $position, $description, $displayOrder, $status, $memberId]);
            }
            $_SESSION['success'] = 'Team member "' . $fullName . '" updated successfully';
        } else {
            // Add new member
            $stmt = $db->prepare("INSERT INTO team_members (full_name, position, description, image, display_order, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fullName, $position, $description, $image, $displayOrder, $status]);
            $_SESSION['success'] = 'Team member "' . $fullName . '" added successfully';
        }
    } else {
        $_SESSION['error'] = 'Please fill in all required fields';
    }
    redirect('admin-team.php');
}

// Handle delete member
if (isset($_POST['delete_member'])) {
    $memberId = $_POST['member_id'] ?? 0;
    if ($memberId) {
        // Get member name before deletion
        $nameStmt = $db->prepare("SELECT full_name FROM team_members WHERE member_id = ?");
        $nameStmt->execute([$memberId]);
        $memberName = $nameStmt->fetchColumn() ?? 'Member';
        
        $stmt = $db->prepare("DELETE FROM team_members WHERE member_id = ?");
        if ($stmt->execute([$memberId])) {
            $_SESSION['success'] = 'Team member "' . $memberName . '" deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete team member';
        }
    }
    redirect('admin-team.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM team_members WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (full_name LIKE ? OR position LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY display_order ASC, full_name ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$teamMembers = $stmt->fetchAll();

// Get status counts
$statusCounts = $db->query("SELECT status, COUNT(*) as count FROM team_members GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <button type="button" onclick="openMemberModal()" class="btn btn-primary">Add Team Member</button>
        </div>

        <!-- Status Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo $statusCounts['active'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Active Members</p>
                    </div>
                    <i class="fas fa-user-check" style="font-size: 30px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo $statusCounts['inactive'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Inactive Members</p>
                    </div>
                    <i class="fas fa-user-slash" style="font-size: 30px; color: var(--warning-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo count($teamMembers); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Members</p>
                    </div>
                    <i class="fas fa-users" style="font-size: 30px; color: var(--success-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search team members..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-team.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Team Members Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Team Members (<?php echo count($teamMembers); ?>)</h3>
            </div>

            <?php if (count($teamMembers) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Member</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Position</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Order</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMembers as $member): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <?php if ($member['image']): ?>
                                    <img src="../assets/<?php echo htmlspecialchars($member['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                    <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div style="font-weight: 500;"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars(substr($member['description'], 0, 50)) . (strlen($member['description']) > 50 ? '...' : ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #e3f2fd; color: #1976d2;">
                                    <?php echo htmlspecialchars($member['position']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo $member['display_order']; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $member['status'] === 'active' ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $member['status'] === 'active' ? '#155724' : '#721c24'; ?>; text-transform: capitalize;">
                                    <?php echo $member['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="editMember(<?php echo htmlspecialchars(json_encode($member)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Edit</button>
                                    <form method="POST" action="" style="display: inline;" id="deleteMemberForm<?php echo $member['member_id']; ?>">
                                        <input type="hidden" name="member_id" value="<?php echo $member['member_id']; ?>">
                                        <button type="button" onclick="openDeleteModal('deleteMemberForm<?php echo $member['member_id']; ?>', 'Delete Team Member', 'Are you sure you want to delete &quot;<?php echo htmlspecialchars($member['full_name']); ?>&quot;?', null, 'delete_member')" class="btn btn-sm btn-danger" style="padding: 5px 12px; font-size: 12px;"><i class="fas fa-trash"></i></button>
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
                <i class="fas fa-users" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No team members found</h3>
                <p style="color: #999;">Add your first team member to display on the About page</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Member Modal -->
<div id="memberModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="font-size: 20px; margin: 0;">Add Team Member</h3>
            <button onclick="closeMemberModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" style="padding: 30px;">
            <input type="hidden" name="member_id" id="member_id">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Full Name *</label>
                <input type="text" name="full_name" id="full_name" required style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Position *</label>
                <input type="text" name="position" id="position" required placeholder="e.g., General Manager, Executive Chef" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="description" rows="3" placeholder="Brief bio or description of the team member" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Display Order</label>
                    <input type="number" name="display_order" id="display_order" min="0" value="0" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <small style="color: #666; font-size: 12px;">Lower numbers appear first</small>
                </div>
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                    <select name="status" id="status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Photo</label>
                <input type="file" name="image" id="image" accept="image/*" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                <small style="color: #666; font-size: 12px;">Recommended: Square image (400x400px or larger)</small>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closeMemberModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="save_member" class="btn btn-primary">Save Member</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMemberModal() {
    document.getElementById('memberModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Add Team Member';
    document.getElementById('member_id').value = '';
    document.getElementById('full_name').value = '';
    document.getElementById('position').value = '';
    document.getElementById('description').value = '';
    document.getElementById('display_order').value = '0';
    document.getElementById('status').value = 'active';
    document.getElementById('image').value = '';
}

function editMember(member) {
    document.getElementById('memberModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Team Member';
    document.getElementById('member_id').value = member.member_id;
    document.getElementById('full_name').value = member.full_name;
    document.getElementById('position').value = member.position;
    document.getElementById('description').value = member.description || '';
    document.getElementById('display_order').value = member.display_order || '0';
    document.getElementById('status').value = member.status;
    document.getElementById('image').value = '';
}

function closeMemberModal() {
    document.getElementById('memberModal').style.display = 'none';
}

document.getElementById('memberModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMemberModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
