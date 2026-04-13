<?php
$pageTitle = 'Contact Messages - Admin';
require_once '../includes/config.php';
require_once '../includes/notifications.php';

// Check if user is admin or manager
if (!isAdmin() && !isManager()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();
$userId = $_SESSION['user_id'] ?? 0;

// Handle actions
if (isset($_POST['mark_read'])) {
    $messageId = $_POST['message_id'] ?? 0;
    if ($messageId) {
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'read' WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $_SESSION['success'] = 'Message marked as read';
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['mark_replied'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $replyMessage = sanitizeInput($_POST['reply_message'] ?? '');
    if ($messageId && $replyMessage) {
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', replied_at = NOW(), replied_by = ?, reply_message = ? WHERE message_id = ?");
        $stmt->execute([$userId, $replyMessage, $messageId]);
        
        // Get sender info for notification
        $stmt = $db->prepare("SELECT email, name, subject FROM contact_messages WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $sender = $stmt->fetch();
        
        // Find user by email and send notification
        $userStmt = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $userStmt->execute([$sender['email'] ?? '']);
        $user = $userStmt->fetch();
        
        if ($user) {
            // Create notification for the user
            createNotification(
                $user['user_id'],
                'system',
                'Reply to Your Message: ' . ($sender['subject'] ?? 'Inquiry'),
                'An admin has replied to your message. Click to view the response.',
                [
                    'related_id' => $messageId,
                    'related_type' => 'contact_message',
                    'priority' => 'medium',
                    'action_url' => '/bayawanhotel/user/my-messages.php?view=' . $messageId
                ]
            );
        }
        
        $_SESSION['success'] = 'Reply sent to ' . htmlspecialchars($sender['name'] ?? 'sender');
        logActivity('Contact message replied', 'Message ID: ' . $messageId . ', Replied to: ' . ($sender['email'] ?? 'unknown'));
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['update_priority'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $priority = $_POST['priority'] ?? 'medium';
    if ($messageId && in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
        $stmt = $db->prepare("UPDATE contact_messages SET priority = ? WHERE message_id = ?");
        $stmt->execute([$priority, $messageId]);
        $_SESSION['success'] = 'Priority updated to ' . ucfirst($priority);
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['update_status'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $status = $_POST['status'] ?? 'new';
    if ($messageId && in_array($status, ['new', 'read', 'in_progress', 'replied', 'resolved', 'archived'])) {
        $stmt = $db->prepare("UPDATE contact_messages SET status = ? WHERE message_id = ?");
        $stmt->execute([$status, $messageId]);
        $_SESSION['success'] = 'Status updated to ' . ucfirst(str_replace('_', ' ', $status));
        logActivity('Contact message status updated', 'Message ID: ' . $messageId . ', Status: ' . $status);
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['assign_to'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $assignedTo = $_POST['assigned_to'] ?? 0;
    if ($messageId) {
        $stmt = $db->prepare("UPDATE contact_messages SET assigned_to = ? WHERE message_id = ?");
        $stmt->execute([$assignedTo ?: null, $messageId]);
        
        // Also update status to in_progress when assigned
        if ($assignedTo) {
            $statusStmt = $db->prepare("UPDATE contact_messages SET status = 'in_progress' WHERE message_id = ? AND status IN ('new', 'read')");
            $statusStmt->execute([$messageId]);
            
            $userStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
            $userStmt->execute([$assignedTo]);
            $assignee = $userStmt->fetch();
            $_SESSION['success'] = 'Message assigned to ' . htmlspecialchars($assignee['first_name'] . ' ' . $assignee['last_name']);
            
            // Notify assigned staff
            createNotification(
                $assignedTo,
                'system',
                'New Message Assigned',
                'A contact message has been assigned to you for handling.',
                [
                    'related_id' => $messageId,
                    'related_type' => 'contact_message',
                    'priority' => 'medium',
                    'action_url' => '/bayawanhotel/staff/staff-contact-messages.php?view=' . $messageId
                ]
            );
        } else {
            $_SESSION['success'] = 'Message unassigned';
        }
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['add_notes'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $notes = sanitizeInput($_POST['admin_notes'] ?? '');
    if ($messageId) {
        $stmt = $db->prepare("UPDATE contact_messages SET admin_notes = ? WHERE message_id = ?");
        $stmt->execute([$notes, $messageId]);
        $_SESSION['success'] = 'Notes saved';
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['archive_message'])) {
    $messageId = $_POST['message_id'] ?? 0;
    if ($messageId) {
        $stmt = $db->prepare("UPDATE contact_messages SET status = 'archived' WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $_SESSION['success'] = 'Message archived';
    }
    redirect('admin-contact-messages.php');
}

if (isset($_POST['delete_message'])) {
    $messageId = $_POST['message_id'] ?? 0;
    if ($messageId) {
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE message_id = ?");
        $stmt->execute([$messageId]);
        $_SESSION['success'] = 'Message deleted permanently';
        logActivity('Contact message deleted', 'Message ID: ' . $messageId);
    }
    redirect('admin-contact-messages.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$subjectFilter = $_GET['subject'] ?? '';
$search = $_GET['search'] ?? '';
$viewId = $_GET['view'] ?? 0;

// Build query
$sql = "
    SELECT cm.*, 
           assignee.first_name as assigned_first, assignee.last_name as assigned_last,
           replier.first_name as replied_first, replier.last_name as replied_last
    FROM contact_messages cm
    LEFT JOIN users assignee ON cm.assigned_to = assignee.user_id
    LEFT JOIN users replier ON cm.replied_by = replier.user_id
    WHERE 1=1
";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND cm.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $sql .= " AND cm.priority = ?";
    $params[] = $priorityFilter;
}

if ($subjectFilter) {
    $sql .= " AND cm.subject = ?";
    $params[] = $subjectFilter;
}

if ($search) {
    $sql .= " AND (cm.name LIKE ? OR cm.email LIKE ? OR cm.message LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY cm.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Get message to view if view parameter is set
$viewMessage = null;
if ($viewId) {
    // Mark as read when viewed
    $db->prepare("UPDATE contact_messages SET status = CASE WHEN status = 'new' THEN 'read' ELSE status END WHERE message_id = ?")->execute([$viewId]);
    
    $viewStmt = $db->prepare("
        SELECT cm.*, 
               assignee.first_name as assigned_first, assignee.last_name as assigned_last,
               replier.first_name as replied_first, replier.last_name as replied_last
        FROM contact_messages cm
        LEFT JOIN users assignee ON cm.assigned_to = assignee.user_id
        LEFT JOIN users replier ON cm.replied_by = replier.user_id
        WHERE cm.message_id = ?
    ");
    $viewStmt->execute([$viewId]);
    $viewMessage = $viewStmt->fetch();
}

// Get stats
$totalMessages = count($messages);
$newCount = count(array_filter($messages, fn($m) => $m['status'] === 'new'));
$unreadCount = $newCount;
$repliedCount = count(array_filter($messages, fn($m) => $m['status'] === 'replied'));
$archivedCount = count(array_filter($messages, fn($m) => $m['status'] === 'archived'));
$highPriorityCount = count(array_filter($messages, fn($m) => $m['priority'] === 'high' && $m['status'] !== 'archived'));

// Get all staff for assignment dropdown
$staffStmt = $db->query("SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'manager', 'receptionist') AND status = 'active' ORDER BY first_name");
$staff = $staffStmt->fetchAll();

// Get subject options
$subjectStmt = $db->query("SELECT DISTINCT subject FROM contact_messages ORDER BY subject");
$subjects = $subjectStmt->fetchAll(PDO::FETCH_COLUMN);

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $totalMessages; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">Total Messages</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color); position: relative;">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $newCount; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">New/Unseen</p>
                </div>
                <?php if ($newCount > 0): ?>
                <span style="position: absolute; top: -5px; right: -5px; background-color: var(--danger-color); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"><?php echo $newCount; ?></span>
                <?php endif; ?>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $repliedCount; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">Replied</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $highPriorityCount; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">High Priority</p>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #6c757d;">
                <div style="text-align: center;">
                    <h3 style="font-size: 32px; margin: 0;"><?php echo $archivedCount; ?></h3>
                    <p style="color: #666; margin: 5px 0 0;">Archived</p>
                </div>
            </div>
        </div>

        <?php if ($viewMessage): ?>
        <!-- Message Detail View -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center; background-color: #f8f9fa;">
                <div>
                    <h3 style="font-size: 20px; margin: 0;">Message Details #<?php echo $viewMessage['message_id']; ?></h3>
                    <p style="margin: 5px 0 0; color: #666; font-size: 14px;">Received <?php echo formatDate($viewMessage['created_at'], 'F d, Y g:i A'); ?></p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="admin-contact-messages.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to List</a>
                </div>
            </div>

            <div style="padding: 30px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <!-- Message Content -->
                    <div>
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label style="font-size: 12px; color: #666; text-transform: uppercase;">From</label>
                                    <p style="margin: 5px 0; font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($viewMessage['name']); ?></p>
                                    <p style="margin: 0; font-size: 14px;"><a href="mailto:<?php echo htmlspecialchars($viewMessage['email']); ?>" style="color: var(--primary-color);"><?php echo htmlspecialchars($viewMessage['email']); ?></a></p>
                                </div>
                                <div>
                                    <label style="font-size: 12px; color: #666; text-transform: uppercase;">Phone</label>
                                    <p style="margin: 5px 0; font-size: 16px;"><?php echo $viewMessage['phone'] ? htmlspecialchars($viewMessage['phone']) : '<em style="color: #999;">Not provided</em>'; ?></p>
                                </div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label style="font-size: 12px; color: #666; text-transform: uppercase;">Subject</label>
                                <p style="margin: 5px 0; font-weight: 600;"><?php echo ucfirst(htmlspecialchars($viewMessage['subject'])); ?></p>
                            </div>
                            <div>
                                <label style="font-size: 12px; color: #666; text-transform: uppercase;">Message</label>
                                <div style="background-color: white; padding: 15px; border-radius: 8px; margin-top: 5px; border: 1px solid var(--gray-light); white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($viewMessage['message'])); ?></div>
                            </div>
                        </div>

                        <?php if ($viewMessage['reply_message']): ?>
                        <div style="background-color: #d4edda; padding: 20px; border-radius: 10px; border-left: 4px solid var(--success-color);">
                            <label style="font-size: 12px; color: #155724; text-transform: uppercase;"><i class="fas fa-reply"></i> Your Reply (<?php echo formatDate($viewMessage['replied_at'], 'M d, Y g:i A'); ?>)</label>
                            <div style="margin-top: 10px; white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($viewMessage['reply_message'])); ?></div>
                            <p style="margin: 10px 0 0; font-size: 12px; color: #666;">Replied by <?php echo htmlspecialchars($viewMessage['replied_first'] . ' ' . $viewMessage['replied_last']); ?></p>
                        </div>
                        <?php else: ?>
                        <!-- Reply Form -->
                        <form method="POST" action="">
                            <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                            <div style="margin-bottom: 15px;">
                                <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;"><i class="fas fa-reply"></i> Send Reply</label>
                                <textarea name="reply_message" rows="6" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px; resize: vertical;" placeholder="Type your reply here..."></textarea>
                            </div>
                            <button type="submit" name="mark_replied" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Reply
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar Actions -->
                    <div>
                        <!-- Status Badge -->
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 12px; color: #666; text-transform: uppercase;">Current Status</label>
                            <?php
                            $statusColors = [
                                'new' => ['#fff3cd', '#856404'],
                                'read' => ['#d1ecf1', '#0c5460'],
                                'in_progress' => ['#ffe5d4', '#9c4b00'],
                                'replied' => ['#d4edda', '#155724'],
                                'resolved' => ['#c3e6cb', '#155724'],
                                'archived' => ['#e2e3e5', '#383d41']
                            ];
                            $colors = $statusColors[$viewMessage['status']] ?? ['#f5f5f5', '#666'];
                            ?>
                            <span style="display: inline-block; padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; background-color: <?php echo $colors[0]; ?>; color: <?php echo $colors[1]; ?>; margin-top: 5px; text-transform: uppercase;">
                                <?php echo ucfirst($viewMessage['status']); ?>
                            </span>
                        </div>

                        <!-- Priority -->
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <label style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 10px; display: block;">Priority</label>
                            <form method="POST" action="" style="display: flex; gap: 10px;">
                                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                                <select name="priority" style="flex: 1; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                                    <option value="low" <?php echo $viewMessage['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $viewMessage['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $viewMessage['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo $viewMessage['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                                <button type="submit" name="update_priority" class="btn btn-primary" style="padding: 10px 15px;">Update</button>
                            </form>
                        </div>

                        <!-- Assignment -->
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <label style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 10px; display: block;">Assigned To</label>
                            <form method="POST" action="" style="display: flex; gap: 10px;">
                                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                                <select name="assigned_to" style="flex: 1; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach ($staff as $member): ?>
                                    <option value="<?php echo $member['user_id']; ?>" <?php echo $viewMessage['assigned_to'] == $member['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_to" class="btn btn-primary" style="padding: 10px 15px;">Assign</button>
                            </form>
                            <?php if ($viewMessage['assigned_first']): ?>
                            <p style="margin: 10px 0 0; font-size: 13px; color: #666;">
                                <i class="fas fa-user-check" style="color: var(--primary-color);"></i> 
                                Currently assigned to: <strong><?php echo htmlspecialchars($viewMessage['assigned_first'] . ' ' . $viewMessage['assigned_last']); ?></strong>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Admin Notes -->
                        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <label style="font-size: 12px; color: #666; text-transform: uppercase; margin-bottom: 10px; display: block;">Admin Notes</label>
                            <form method="POST" action="">
                                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                                <textarea name="admin_notes" rows="4" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px; margin-bottom: 10px; resize: vertical;" placeholder="Add internal notes..."><?php echo htmlspecialchars($viewMessage['admin_notes'] ?? ''); ?></textarea>
                                <button type="submit" name="add_notes" class="btn btn-outline" style="width: 100%;">Save Notes</button>
                            </form>
                        </div>

                        <!-- Quick Actions -->
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <?php if ($viewMessage['status'] === 'new'): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                                <button type="submit" name="mark_read" class="btn btn-outline" style="width: 100%;">
                                    <i class="fas fa-envelope-open"></i> Mark as Read
                                </button>
                            </form>
                            <?php endif; ?>

                            <?php if ($viewMessage['status'] !== 'archived'): ?>
                            <form method="POST" action="" style="display: inline;" id="archiveForm<?php echo $viewMessage['message_id']; ?>">
                                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                                <button type="submit" name="archive_message" onclick="return confirm('Archive this message? It will be moved to archived status.')" class="btn btn-outline" style="width: 100%;">
                                    <i class="fas fa-archive"></i> Archive Message
                                </button>
                            </form>
                            <?php endif; ?>

                            <form method="POST" action="" style="display: inline;" id="deleteForm<?php echo $viewMessage['message_id']; ?>">
                                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                                <button type="submit" name="delete_message" onclick="return confirm('WARNING: This will permanently delete this message. This action cannot be undone. Are you sure?')" class="btn btn-danger" style="width: 100%;">
                                    <i class="fas fa-trash"></i> Delete Permanently
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or message..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="new" <?php echo $statusFilter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $statusFilter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="replied" <?php echo $statusFilter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Priority</label>
                    <select name="priority" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Priorities</option>
                        <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Subject</label>
                    <select name="subject" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subj): ?>
                        <option value="<?php echo htmlspecialchars($subj); ?>" <?php echo $subjectFilter === $subj ? 'selected' : ''; ?>><?php echo ucfirst(htmlspecialchars($subj)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div></div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-contact-messages.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Messages Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Contact Messages (<?php echo count($messages); ?>)</h3>
                <div style="display: flex; gap: 10px;">
                    <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; background-color: var(--warning-color); color: white;">
                        <i class="fas fa-bell"></i> <?php echo $newCount; ?> New
                    </span>
                </div>
            </div>

            <?php if (count($messages) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Sender</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Subject</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Message Preview</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Priority</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Assigned To</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Date</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): 
                            $statusColors = [
                                'new' => ['#fff3cd', '#856404', 'New'],
                                'read' => ['#d1ecf1', '#0c5460', 'Read'],
                                'replied' => ['#d4edda', '#155724', 'Replied'],
                                'archived' => ['#e2e3e5', '#383d41', 'Archived']
                            ];
                            $colors = $statusColors[$msg['status']] ?? ['#f5f5f5', '#666', $msg['status']];
                            $priorityColors = ['low' => '#6c757d', 'medium' => '#ffc107', 'high' => '#dc3545'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light); <?php echo $msg['status'] === 'new' ? 'background-color: #fffbf0;' : ''; ?>">
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; background-color: <?php echo $colors[0]; ?>; color: <?php echo $colors[1]; ?>; text-transform: uppercase;">
                                    <?php echo $colors[2]; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($msg['name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($msg['email']); ?></div>
                                <?php if ($msg['phone']): ?>
                                <div style="font-size: 11px; color: #999;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($msg['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="text-transform: capitalize;"><?php echo ucfirst(str_replace('_', ' ', $msg['subject'])); ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="max-width: 250px; font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 80)) . (strlen($msg['message']) > 80 ? '...' : ''); ?>
                                </div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; background-color: <?php echo $priorityColors[$msg['priority']]; ?>; color: white; text-transform: uppercase;">
                                    <?php echo $msg['priority']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php if ($msg['assigned_first']): ?>
                                    <span style="font-size: 13px;"><i class="fas fa-user" style="color: var(--primary-color);"></i> <?php echo htmlspecialchars($msg['assigned_first'] . ' ' . $msg['assigned_last']); ?></span>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #999;"><em>Unassigned</em></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px 20px; font-size: 13px;">
                                <?php echo formatDate($msg['created_at'], 'M d, Y'); ?><br>
                                <span style="color: #999; font-size: 11px;"><?php echo formatDate($msg['created_at'], 'g:i A'); ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="admin-contact-messages.php?view=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($msg['status'] === 'new'): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['message_id']; ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline" style="padding: 5px 12px; font-size: 12px;">
                                            <i class="fas fa-envelope-open"></i>
                                        </button>
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
                <i class="fas fa-envelope" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No messages found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
