<?php
/**
 * User Messages/Inbox Page - Bayawan Bai Hotel
 * Allows users to view their contact messages and admin replies
 */
$pageTitle = 'My Messages';
require_once '../includes/user-header.php';
require_once '../includes/notifications.php';

$db = getDB();
$userId = getUserId();
$userEmail = $_SESSION['email'] ?? '';

// Mark notification as read if viewing a specific message
$viewId = $_GET['view'] ?? 0;
if ($viewId) {
    // Mark related notifications as read
    $markReadStmt = $db->prepare("UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND related_id = ? AND related_type = 'contact_message'");
    $markReadStmt->execute([$userId, $viewId]);
}

// Get all contact messages from this user (matched by user_id OR email)
$messagesStmt = $db->prepare("
    SELECT cm.*, 
           u.first_name as replied_first, u.last_name as replied_last
    FROM contact_messages cm
    LEFT JOIN users u ON cm.replied_by = u.user_id
    WHERE cm.user_id = ? OR cm.email = ?
    ORDER BY cm.created_at DESC
");
$messagesStmt->execute([$userId, $userEmail]);
$messages = $messagesStmt->fetchAll();

// Get specific message to view
$viewMessage = null;
if ($viewId) {
    $viewStmt = $db->prepare("
        SELECT cm.*, 
               u.first_name as replied_first, u.last_name as replied_last
        FROM contact_messages cm
        LEFT JOIN users u ON cm.replied_by = u.user_id
        WHERE cm.message_id = ? AND (cm.user_id = ? OR cm.email = ?)
    ");
    $viewStmt->execute([$viewId, $userId, $userEmail]);
    $viewMessage = $viewStmt->fetch();
}

// Get unread notification count for messages (only for messages that exist for this user)
$unreadStmt = $db->prepare("
    SELECT COUNT(*) FROM notifications n
    JOIN contact_messages cm ON n.related_id = cm.message_id
    WHERE n.user_id = ? AND n.related_type = 'contact_message' AND n.status = 'unread'
    AND (cm.user_id = ? OR cm.email = ?)
");
$unreadStmt->execute([$userId, $userId, $userEmail]);
$unreadCount = $unreadStmt->fetchColumn() ?: 0;
?>

<style>
.messages-container {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    min-height: 600px;
}

.messages-sidebar {
    background: #f8f9fa;
    border-right: 1px solid var(--gray-light);
    overflow-y: auto;
    max-height: 600px;
}

.messages-sidebar-header {
    padding: 20px;
    border-bottom: 1px solid var(--gray-light);
    background: white;
}

.messages-sidebar-header h3 {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.message-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.message-item {
    padding: 15px 20px;
    border-bottom: 1px solid var(--gray-light);
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: block;
    color: inherit;
}

.message-item:hover {
    background: #e9ecef;
}

.message-item.active {
    background: #e3f2fd;
    border-left: 3px solid var(--primary-color);
}

.message-item.unread {
    background: #fff3cd;
}

.message-item.unread::before {
    content: '';
    width: 8px;
    height: 8px;
    background: var(--primary-color);
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}

.message-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.message-preview-subject {
    font-weight: 600;
    font-size: 14px;
    color: var(--dark-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.message-preview-date {
    font-size: 12px;
    color: #999;
}

.message-preview-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.message-preview-status.new {
    background: #fff3cd;
    color: #856404;
}

.message-preview-status.read {
    background: #d1ecf1;
    color: #0c5460;
}

.message-preview-status.replied {
    background: #d4edda;
    color: #155724;
}

.message-content-area {
    padding: 30px;
    overflow-y: auto;
    max-height: 600px;
}

.message-content-header {
    border-bottom: 2px solid var(--gray-light);
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.message-content-header h2 {
    margin: 0 0 15px 0;
    font-size: 22px;
}

.message-meta {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #666;
}

.message-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.message-body {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    white-space: pre-wrap;
    line-height: 1.6;
}

.admin-reply-section {
    background: #e8f5e9;
    border-left: 4px solid var(--success-color);
    padding: 25px;
    border-radius: 8px;
    margin-top: 30px;
}

.admin-reply-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    color: #155724;
}

.admin-reply-header i {
    font-size: 20px;
}

.admin-reply-header h4 {
    margin: 0;
    font-size: 16px;
}

.admin-reply-meta {
    font-size: 13px;
    color: #666;
    margin-bottom: 15px;
}

.admin-reply-body {
    background: white;
    padding: 20px;
    border-radius: 8px;
    white-space: pre-wrap;
    line-height: 1.6;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    color: var(--gray-medium);
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: var(--dark-color);
}

.badge-count {
    background: var(--danger-color);
    color: white;
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: auto;
}

@media (max-width: 768px) {
    .messages-container {
        grid-template-columns: 1fr;
    }
    
    .messages-sidebar {
        max-height: 300px;
        border-right: none;
        border-bottom: 1px solid var(--gray-light);
    }
}
</style>

<section class="user-section">
    <div class="container">
        <div class="section-header">
            <h1><i class="fas fa-envelope"></i> My Messages</h1>
            <p>View your conversations with our team</p>
        </div>

        <div class="messages-container">
            <!-- Sidebar: Message List -->
            <div class="messages-sidebar">
                <div class="messages-sidebar-header">
                    <h3>
                        <i class="fas fa-inbox"></i> Inbox
                        <?php if ($unreadCount > 0): ?>
                        <span class="badge-count"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </h3>
                </div>
                
                <?php if (count($messages) > 0): ?>
                <ul class="message-list">
                    <?php foreach ($messages as $msg): 
                        $isActive = $viewMessage && $viewMessage['message_id'] == $msg['message_id'];
                        $hasUnreadReply = $msg['status'] === 'replied' && $msg['replied_at'] > ($msg['updated_at'] ?? $msg['created_at']);
                    ?>
                    <li>
                        <a href="my-messages.php?view=<?php echo $msg['message_id']; ?>" 
                           class="message-item <?php echo $isActive ? 'active' : ''; ?> <?php echo $hasUnreadReply ? 'unread' : ''; ?>">
                            <div class="message-preview-header">
                                <span class="message-preview-subject"><?php echo htmlspecialchars(ucfirst($msg['subject'])); ?></span>
                                <span class="message-preview-date"><?php echo formatDate($msg['created_at'], 'M d'); ?></span>
                            </div>
                            <span class="message-preview-status <?php echo $msg['status']; ?>">
                                <?php echo ucfirst($msg['status']); ?>
                            </span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div style="padding: 30px; text-align: center; color: #999;">
                    <p>No messages yet</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Content Area: Message Detail -->
            <div class="message-content-area">
                <?php if ($viewMessage): ?>
                    <div class="message-content-header">
                        <h2><?php echo htmlspecialchars(ucfirst($viewMessage['subject'])); ?></h2>
                        <div class="message-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo formatDate($viewMessage['created_at'], 'F d, Y g:i A'); ?></span>
                            <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($viewMessage['email']); ?></span>
                            <span><i class="fas fa-tag"></i> <?php echo ucfirst($viewMessage['status']); ?></span>
                        </div>
                    </div>

                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($viewMessage['message'])); ?>
                    </div>

                    <?php if ($viewMessage['reply_message']): ?>
                    <div class="admin-reply-section">
                        <div class="admin-reply-header">
                            <i class="fas fa-reply"></i>
                            <h4>Reply from Bayawan Bai Hotel</h4>
                        </div>
                        <div class="admin-reply-meta">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars(($viewMessage['replied_first'] ?? 'Admin') . ' ' . ($viewMessage['replied_last'] ?? '')); ?>
                            <span style="margin-left: 15px;">
                                <i class="fas fa-clock"></i> 
                                <?php echo formatDate($viewMessage['replied_at'], 'F d, Y g:i A'); ?>
                            </span>
                        </div>
                        <div class="admin-reply-body">
                            <?php echo nl2br(htmlspecialchars($viewMessage['reply_message'])); ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #999;">
                        <i class="fas fa-clock" style="font-size: 32px; margin-bottom: 15px;"></i>
                        <p>Waiting for a reply from our team...</p>
                        <p style="font-size: 13px; margin-top: 10px;">We typically respond within 24 hours</p>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-envelope-open"></i>
                        <h3>Select a message to view</h3>
                        <p>Click on any message from the list to view details and replies</p>
                        
                        <?php if (count($messages) === 0): ?>
                        <div style="margin-top: 30px;">
                            <a href="../contact.php" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send a Message
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Auto-refresh for new replies (every 60 seconds)
setInterval(function() {
    // Only refresh if viewing a specific message
    const urlParams = new URLSearchParams(window.location.search);
    const viewId = urlParams.get('view');
    if (viewId) {
        // Check for new replies via AJAX
        fetch(`../api/ajax-notifications.php?action=check_message_reply&message_id=${viewId}`)
            .then(response => response.json())
            .then(data => {
                if (data.has_new_reply) {
                    // Reload page to show new reply
                    window.location.reload();
                }
            })
            .catch(error => console.error('Error checking for replies:', error));
    }
}, 60000);
</script>

