<?php
/**
 * Staff Contact Messages Dashboard
 * Shows messages assigned to the logged-in staff member
 */
$pageTitle = 'My Assigned Messages';
require_once '../includes/config.php';
require_once '../includes/notifications.php';

// Allow access to all logged-in staff (admin, manager, receptionist)
$userId = getUserId();
$userRole = getUserRole();

if (!in_array($userRole, ['admin', 'manager', 'receptionist'])) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle status update
if (isset($_POST['update_status'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    if ($messageId && in_array($status, ['in_progress', 'replied', 'resolved'])) {
        // Verify the message is assigned to this staff member
        $checkStmt = $db->prepare("SELECT message_id FROM contact_messages WHERE message_id = ? AND assigned_to = ?");
        $checkStmt->execute([$messageId, $userId]);
        if ($checkStmt->fetch()) {
            $stmt = $db->prepare("UPDATE contact_messages SET status = ? WHERE message_id = ?");
            $stmt->execute([$status, $messageId]);
            $_SESSION['success'] = 'Status updated to ' . ucfirst(str_replace('_', ' ', $status));
        } else {
            $_SESSION['error'] = 'You can only update messages assigned to you';
        }
    }
    redirect('staff-contact-messages.php');
}

// Handle reply
if (isset($_POST['send_reply'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $replyMessage = sanitizeInput($_POST['reply_message'] ?? '');
    if ($messageId && $replyMessage) {
        // Verify the message is assigned to this staff member
        $checkStmt = $db->prepare("SELECT email, name, subject FROM contact_messages WHERE message_id = ? AND assigned_to = ?");
        $checkStmt->execute([$messageId, $userId]);
        $message = $checkStmt->fetch();
        
        if ($message) {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'replied', replied_at = NOW(), replied_by = ?, reply_message = ? WHERE message_id = ?");
            $stmt->execute([$userId, $replyMessage, $messageId]);
            
            // Find user by email and send notification
            $userStmt = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
            $userStmt->execute([$message['email'] ?? '']);
            $user = $userStmt->fetch();
            
            if ($user) {
                createNotification(
                    $user['user_id'],
                    'system',
                    'Reply to Your Message: ' . ($message['subject'] ?? 'Inquiry'),
                    'A staff member has replied to your message. Click to view the response.',
                    [
                        'related_id' => $messageId,
                        'related_type' => 'contact_message',
                        'priority' => 'medium',
                        'action_url' => '/bayawanhotel/user/my-messages.php?view=' . $messageId
                    ]
                );
            }
            
            $_SESSION['success'] = 'Reply sent to ' . htmlspecialchars($message['name'] ?? 'sender');
        } else {
            $_SESSION['error'] = 'Message not found or not assigned to you';
        }
    }
    redirect('staff-contact-messages.php');
}

// Handle add notes
if (isset($_POST['add_notes'])) {
    $messageId = $_POST['message_id'] ?? 0;
    $notes = sanitizeInput($_POST['staff_notes'] ?? '');
    if ($messageId) {
        // Verify the message is assigned to this staff member
        $checkStmt = $db->prepare("SELECT message_id FROM contact_messages WHERE message_id = ? AND assigned_to = ?");
        $checkStmt->execute([$messageId, $userId]);
        if ($checkStmt->fetch()) {
            // Append to existing admin_notes
            $getStmt = $db->prepare("SELECT admin_notes FROM contact_messages WHERE message_id = ?");
            $getStmt->execute([$messageId]);
            $existing = $getStmt->fetchColumn();
            
            $timestamp = date('Y-m-d H:i:s');
            $staffName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
            $newNote = "[$timestamp] $staffName:\n$notes\n\n";
            $updatedNotes = $existing . $newNote;
            
            $stmt = $db->prepare("UPDATE contact_messages SET admin_notes = ? WHERE message_id = ?");
            $stmt->execute([$updatedNotes, $messageId]);
            $_SESSION['success'] = 'Notes added successfully';
        } else {
            $_SESSION['error'] = 'You can only add notes to messages assigned to you';
        }
    }
    redirect('staff-contact-messages.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$viewId = $_GET['view'] ?? 0;

// Build query - only show messages assigned to this staff member
$sql = "
    SELECT cm.*, 
           u.first_name as user_first, u.last_name as user_last
    FROM contact_messages cm
    LEFT JOIN users u ON cm.user_id = u.user_id
    WHERE cm.assigned_to = ? AND cm.status != 'archived'
";
$params = [$userId];

if ($statusFilter) {
    $sql .= " AND cm.status = ?";
    $params[] = $statusFilter;
}

if ($priorityFilter) {
    $sql .= " AND cm.priority = ?";
    $params[] = $priorityFilter;
}

$sql .= " ORDER BY 
    CASE cm.priority 
        WHEN 'urgent' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END,
    cm.created_at DESC
";

$messagesStmt = $db->prepare($sql);
$messagesStmt->execute($params);
$messages = $messagesStmt->fetchAll();

// Get statistics
$statsStmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' OR status = 'read' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as `high_priority`
    FROM contact_messages 
    WHERE assigned_to = ? AND status != 'archived'
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();

// Get specific message to view
$viewMessage = null;
if ($viewId) {
    $viewStmt = $db->prepare("
        SELECT cm.*, 
               u.first_name as user_first, u.last_name as user_last,
               assignee.first_name as assigned_first, assignee.last_name as assigned_last
        FROM contact_messages cm
        LEFT JOIN users u ON cm.user_id = u.user_id
        LEFT JOIN users assignee ON cm.assigned_to = assignee.user_id
        WHERE cm.message_id = ? AND cm.assigned_to = ?
    ");
    $viewStmt->execute([$viewId, $userId]);
    $viewMessage = $viewStmt->fetch();
    
    // Mark related notifications as read
    if ($viewMessage) {
        $markReadStmt = $db->prepare("UPDATE notifications SET status = 'read', read_at = NOW() WHERE user_id = ? AND related_id = ? AND related_type = 'contact_message'");
        $markReadStmt->execute([$userId, $viewId]);
    }
}

require_once '../includes/staff-header.php';
?>

<style>
/* ===== CSS Variables ===== */
:root {
    --primary: #367D8A;
    --primary-dark: #285F6B;
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
    --info: #17a2b8;
    --gray-100: #f8f9fa;
    --gray-200: #e9ecef;
    --gray-300: #dee2e6;
    --gray-600: #6c757d;
    --gray-800: #343a40;
    --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
    --shadow: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-lg: 0 4px 12px rgba(0,0,0,0.12);
    --radius: 12px;
    --radius-sm: 8px;
}

/* ===== Page Header ===== */
.content-header {
    margin-bottom: 28px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--gray-200);
}

.content-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.content-header h1 i {
    color: var(--primary);
}

.content-header p {
    color: var(--gray-600);
    font-size: 15px;
    margin: 0;
}

/* ===== Stats Grid ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 20px;
    margin-bottom: 28px;
}

.stat-card {
    background: white;
    padding: 24px 16px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    text-align: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid var(--gray-200);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.stat-card.urgent { border-left: 5px solid var(--danger); }
.stat-card.high { border-left: 5px solid #fd7e14; }
.stat-card.progress { border-left: 5px solid var(--warning); }
.stat-card.resolved { border-left: 5px solid var(--success); }
.stat-card:last-child { border-left: 5px solid var(--primary); }

.stat-number {
    font-size: 32px;
    font-weight: 800;
    color: var(--gray-800);
    margin-bottom: 6px;
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
}

/* ===== Filters Section ===== */
.filter-section {
    background: white;
    padding: 20px 24px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 28px;
    border: 1px solid var(--gray-200);
}

.filter-form .form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
}

.filter-form select,
.filter-form button,
.filter-form a.btn {
    height: 44px;
    padding: 0 18px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 500;
}

.filter-form select {
    min-width: 160px;
    border: 1.5px solid var(--gray-300);
    background: white;
    color: var(--gray-800);
    cursor: pointer;
}

.filter-form select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(54, 125, 138, 0.1);
}

.filter-form button {
    background: var(--primary);
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-form button:hover {
    background: var(--primary-dark);
}

.filter-form a.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-800);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.filter-form a.btn-secondary:hover {
    background: var(--gray-300);
}

/* ===== Table Styles ===== */
.table-container {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.data-table thead {
    background: var(--gray-100);
    border-bottom: 2px solid var(--gray-200);
}

.data-table th {
    padding: 16px 20px;
    text-align: left;
    font-weight: 700;
    color: var(--gray-800);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-200);
    vertical-align: middle;
}

.data-table tbody tr:hover {
    background: var(--gray-100);
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

/* ===== Badges ===== */
.priority-badge, .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.priority-urgent { background: #ffebee; color: #c62828; }
.priority-high { background: #fff3e0; color: #ef6c00; }
.priority-medium { background: #fff8e1; color: #f9a825; }
.priority-low { background: #e8f5e9; color: #2e7d32; }

.status-new { background: #e3f2fd; color: #1565c0; }
.status-read { background: #f5f5f5; color: #616161; }
.status-in_progress { background: #fff3e0; color: #ef6c00; }
.status-replied { background: #e8f5e9; color: #2e7d32; }
.status-resolved { background: #e0f2f1; color: #00796b; }

/* ===== Message Detail Panel ===== */
.message-detail-panel {
    background: white;
    border-radius: var(--radius);
    padding: 32px;
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-200);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--gray-200);
}

.message-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0 0 12px 0;
}

.message-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    color: var(--gray-600);
    font-size: 14px;
}

.message-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.message-meta i {
    color: var(--primary);
    width: 16px;
}

.message-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* ===== Tabs ===== */
.content-tabs {
    margin-top: 24px;
}

.tab-buttons {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid var(--gray-200);
    margin-bottom: 24px;
}

.tab-btn {
    padding: 14px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--gray-600);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab-btn:hover {
    color: var(--primary);
    background: var(--gray-100);
}

.tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--gray-100);
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== Message Cards ===== */
.message-card {
    background: var(--gray-100);
    border-radius: var(--radius-sm);
    padding: 24px;
    margin-bottom: 20px;
}

.message-card h4 {
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.message-card p {
    color: var(--gray-800);
    line-height: 1.7;
    margin: 0;
    font-size: 15px;
}

.message-card.reply-card {
    background: #e8f5e9;
    border-left: 4px solid var(--success);
}

.message-card.reply-card h4 {
    color: #2e7d32;
}

.reply-meta {
    font-size: 13px;
    color: var(--gray-600);
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(0,0,0,0.08);
}

/* ===== Forms ===== */
.form-section {
    background: white;
    border-radius: var(--radius-sm);
    padding: 24px;
    border: 1px solid var(--gray-200);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-size: 15px;
    color: var(--gray-800);
    transition: all 0.2s ease;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(54, 125, 138, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

/* ===== Buttons ===== */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-success {
    background: var(--success);
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-secondary {
    background: var(--gray-200);
    color: var(--gray-800);
}

.btn-secondary:hover {
    background: var(--gray-300);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* ===== Notes Section ===== */
.notes-section {
    background: var(--gray-100);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin-bottom: 20px;
}

.notes-section h4 {
    font-size: 15px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0 0 14px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.notes-content {
    white-space: pre-wrap;
    font-family: 'SF Mono', Monaco, monospace;
    font-size: 13px;
    color: var(--gray-800);
    max-height: 240px;
    overflow-y: auto;
    background: white;
    padding: 16px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--gray-300);
    line-height: 1.6;
}

/* ===== Alert Messages ===== */
.alert {
    padding: 16px 20px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}

.alert-info {
    background: #e3f2fd;
    color: #1565c0;
    border: 1px solid #bbdefb;
}

/* ===== Status Update Section ===== */
.status-section {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid var(--gray-200);
}

.status-section h4 {
    font-size: 16px;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0 0 16px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.status-form select {
    min-width: 160px;
    padding: 12px 16px;
    border: 1.5px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-size: 14px;
    background: white;
}

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-600);
}

.empty-state i {
    font-size: 56px;
    color: var(--gray-300);
    margin-bottom: 20px;
}

.empty-state p {
    font-size: 16px;
    margin: 0;
}

/* ===== Back Button ===== */
.back-link {
    margin-top: 24px;
}

/* ===== Responsive Design ===== */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .stat-card {
        padding: 18px 12px;
    }
    
    .stat-number {
        font-size: 24px;
    }
    
    .message-header {
        flex-direction: column;
        gap: 16px;
    }
    
    .message-badges {
        width: 100%;
        justify-content: flex-start;
    }
    
    .tab-buttons {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-btn {
        padding: 12px 16px;
        font-size: 13px;
        white-space: nowrap;
    }
    
    .filter-form .form-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-form select,
    .filter-form button,
    .filter-form a.btn {
        width: 100%;
    }
    
    .data-table {
        font-size: 13px;
    }
    
    .data-table th,
    .data-table td {
        padding: 12px 14px;
    }
    
    .message-detail-panel {
        padding: 20px;
    }
    
    .message-meta {
        flex-direction: column;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .content-header h1 {
        font-size: 22px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .message-card {
        padding: 16px;
    }
}
</style>

<div class="content-header">
    <h1><i class="fas fa-envelope"></i> My Assigned Messages</h1>
    <p>Manage contact messages assigned to you</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card urgent">
        <div class="stat-number"><?php echo $stats['urgent'] ?? 0; ?></div>
        <div class="stat-label">Urgent</div>
    </div>
    <div class="stat-card high">
        <div class="stat-number"><?php echo $stats['high_priority'] ?? 0; ?></div>
        <div class="stat-label">High Priority</div>
    </div>
    <div class="stat-card progress">
        <div class="stat-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card resolved">
        <div class="stat-number"><?php echo $stats['resolved'] ?? 0; ?></div>
        <div class="stat-label">Resolved</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total'] ?? 0; ?></div>
        <div class="stat-label">Total Assigned</div>
    </div>
</div>

<!-- Filters -->
<div class="filter-section">
    <form method="get" class="filter-form">
        <div class="form-row">
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="replied" <?php echo $statusFilter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            </select>
            
            <select name="priority" class="form-control">
                <option value="">All Priorities</option>
                <option value="urgent" <?php echo $priorityFilter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                <option value="high" <?php echo $priorityFilter === 'high' ? 'selected' : ''; ?>>High</option>
                <option value="medium" <?php echo $priorityFilter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                <option value="low" <?php echo $priorityFilter === 'low' ? 'selected' : ''; ?>>Low</option>
            </select>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="staff-contact-messages.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<?php if ($viewMessage): ?>
<!-- Message Detail View -->
<div class="message-detail-panel">
    <div class="message-header">
        <div>
            <h2><?php echo htmlspecialchars(ucfirst($viewMessage['subject'])); ?></h2>
            <div class="message-meta">
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($viewMessage['name']); ?></span>
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($viewMessage['email']); ?></span>
                <span><i class="fas fa-calendar"></i> <?php echo formatDate($viewMessage['created_at'], 'M d, Y g:i A'); ?></span>
            </div>
        </div>
        <div class="message-badges">
            <span class="priority-badge priority-<?php echo $viewMessage['priority']; ?>">
                <?php echo ucfirst($viewMessage['priority']); ?>
            </span>
            <span class="status-badge status-<?php echo $viewMessage['status']; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $viewMessage['status'])); ?>
            </span>
        </div>
    </div>

    <div class="content-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="switchTab('message')">Message</button>
            <button class="tab-btn" onclick="switchTab('reply')">Reply</button>
            <button class="tab-btn" onclick="switchTab('notes')">Internal Notes</button>
        </div>
        
        <div id="tab-message" class="tab-content active">
            <div class="message-card">
                <h4><i class="fas fa-comment"></i> Original Message</h4>
                <p><?php echo nl2br(htmlspecialchars($viewMessage['message'])); ?></p>
            </div>
            
            <?php if ($viewMessage['reply_message']): ?>
            <div class="message-card reply-card">
                <h4><i class="fas fa-reply"></i> Your Reply</h4>
                <p><?php echo nl2br(htmlspecialchars($viewMessage['reply_message'])); ?></p>
                <div class="reply-meta">
                    Sent on <?php echo formatDate($viewMessage['replied_at'], 'M d, Y g:i A'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div id="tab-reply" class="tab-content">
            <?php if (!$viewMessage['reply_message']): ?>
            <form method="post" class="form-section">
                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                <div class="form-group">
                    <label>Your Reply</label>
                    <textarea name="reply_message" class="form-control" rows="8" placeholder="Type your response to the user..." required></textarea>
                </div>
                <button type="submit" name="send_reply" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </form>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You have already replied to this message. The user has been notified.
            </div>
            <?php endif; ?>
        </div>
        
        <div id="tab-notes" class="tab-content">
            <?php if ($viewMessage['admin_notes']): ?>
            <div class="notes-section">
                <h4><i class="fas fa-sticky-note"></i> Existing Notes</h4>
                <div class="notes-content"><?php echo nl2br(htmlspecialchars($viewMessage['admin_notes'])); ?></div>
            </div>
            <?php endif; ?>
            
            <form method="post" class="form-section">
                <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
                <div class="form-group">
                    <label>Add Internal Note</label>
                    <textarea name="staff_notes" class="form-control" rows="4" placeholder="Add your notes about this message (only visible to staff)..." required></textarea>
                </div>
                <button type="submit" name="add_notes" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Note
                </button>
            </form>
        </div>
    </div>

    <!-- Status Update -->
    <div class="status-section">
        <h4><i class="fas fa-tasks"></i> Update Status</h4>
        <form method="post" class="status-form">
            <input type="hidden" name="message_id" value="<?php echo $viewMessage['message_id']; ?>">
            <select name="status" class="form-control">
                <option value="in_progress" <?php echo $viewMessage['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="replied" <?php echo $viewMessage['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                <option value="resolved" <?php echo $viewMessage['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
            </select>
            <button type="submit" name="update_status" class="btn btn-primary">
                <i class="fas fa-check"></i> Update Status
            </button>
        </form>
    </div>

    <div class="back-link">
        <a href="staff-contact-messages.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    document.querySelector(`.tab-btn[onclick="switchTab('${tabName}')"]`).classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}
</script>

<?php else: ?>
<!-- Messages List -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Priority</th>
                <th>From</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Received</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
                <td>
                    <span class="priority-badge priority-<?php echo $msg['priority']; ?>">
                        <?php echo ucfirst($msg['priority']); ?>
                    </span>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                    <small style="color: #666;"><?php echo htmlspecialchars($msg['email']); ?></small>
                </td>
                <td><?php echo htmlspecialchars(ucfirst($msg['subject'])); ?></td>
                <td>
                    <span class="status-badge status-<?php echo $msg['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $msg['status'])); ?>
                    </span>
                </td>
                <td><?php echo formatDate($msg['created_at'], 'M d, Y'); ?></td>
                <td>
                    <a href="staff-contact-messages.php?view=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($messages)): ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                    <p>No messages assigned to you yet.</p>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php require_once '../includes/admin-footer.php'; ?>
