<?php
/**
 * Trash - Bayawan Bai Hotel
 * Contains deleted reservations that can be restored or permanently removed
 */

$pageTitle = 'Trash';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle restore and permanent delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'room';
    
    if ($id > 0) {
        $table = ($type === 'event') ? 'event_bookings' : 'bookings';
        $idColumn = ($type === 'event') ? 'event_booking_id' : 'booking_id';
        
        $verifyStmt = $db->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? AND user_id = ? AND is_deleted = 1");
        $verifyStmt->execute([$id, $userId]);
        $item = $verifyStmt->fetch();
        
        if ($item) {
            if ($action === 'restore') {
                $updateStmt = $db->prepare("UPDATE {$table} SET is_deleted = 0, is_archived = 0, updated_at = NOW() WHERE {$idColumn} = ?");
                $updateStmt->execute([$id]);
                showAlert('Item restored to inbox.', 'success');
            } elseif ($action === 'permanent_delete') {
                // For safety, we don't actually delete from DB, just mark as permanently deleted
                // In a real system, you might want to move to a separate audit table
                $updateStmt = $db->prepare("UPDATE {$table} SET status = 'deleted_permanently', updated_at = NOW() WHERE {$idColumn} = ?");
                $updateStmt->execute([$id]);
                showAlert('Item permanently deleted.', 'success');
            }
        }
        redirect('trash.php');
    }
}

// Get trashed reservations
$allReservations = [];

$roomStmt = $db->prepare("
    SELECT 
        b.booking_id as id,
        'room' as type,
        b.status,
        b.check_in,
        b.check_out,
        b.total_amount,
        b.created_at,
        b.updated_at,
        rc.category_name as item_name,
        r.room_number,
        b.adults,
        b.children,
        b.nights,
        b.special_requests
    FROM bookings b
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.user_id = ?
    AND b.is_deleted = 1
    AND (b.status != 'deleted_permanently' OR b.status IS NULL)
    ORDER BY b.updated_at DESC
");
$roomStmt->execute([$userId]);
$roomBookings = $roomStmt->fetchAll();

$eventStmt = $db->prepare("
    SELECT 
        eb.event_booking_id as id,
        'event' as type,
        eb.status,
        eb.event_date as check_in,
        eb.event_date as check_out,
        eb.created_at,
        eb.updated_at,
        eb.event_type as item_name,
        eb.event_type,
        eb.guests_count,
        eb.start_time,
        eb.end_time,
        eb.special_requests,
        eb.quoted_price as total_amount
    FROM event_bookings eb
    WHERE eb.user_id = ?
    AND eb.is_deleted = 1
    AND (eb.status != 'deleted_permanently' OR eb.status IS NULL)
    ORDER BY eb.updated_at DESC
");
$eventStmt->execute([$userId]);
$eventBookings = $eventStmt->fetchAll();

$allReservations = array_merge($roomBookings, $eventBookings);

function getTypeIcon($type) {
    return $type === 'room' 
        ? '<i class="fas fa-bed" title="Room Booking"></i>' 
        : '<i class="fas fa-calendar-week" title="Event Booking"></i>';
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="status-badge pending"><i class="fas fa-clock"></i> Pending</span>',
        'confirmed' => '<span class="status-badge confirmed"><i class="fas fa-check-circle"></i> Confirmed</span>',
        'checked_in' => '<span class="status-badge checkin"><i class="fas fa-door-open"></i> Checked In</span>',
        'checked_out' => '<span class="status-badge checkout"><i class="fas fa-door-closed"></i> Completed</span>',
        'cancelled' => '<span class="status-badge cancelled"><i class="fas fa-times-circle"></i> Cancelled</span>',
        'completed' => '<span class="status-badge checkout"><i class="fas fa-check-double"></i> Completed</span>'
    ];
    return $badges[$status] ?? '<span class="status-badge">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}
?>

<style>
    .inbox-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .inbox-header {
        padding: 25px 30px;
        border-bottom: 1px solid #e9ecef;
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    }
    
    .inbox-header h2 {
        margin: 0;
        font-size: 24px;
        color: #721c24;
    }
    
    .inbox-header h2 i {
        margin-right: 10px;
    }
    
    .trash-notice {
        padding: 15px 30px;
        background: #fff3cd;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #856404;
        font-size: 14px;
    }
    
    .trash-actions {
        padding: 15px 30px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        gap: 10px;
    }
    
    .trash-actions a {
        padding: 8px 16px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        background: white;
        color: #495057;
        font-size: 13px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
    }
    
    .trash-actions a:hover {
        background: #e9ecef;
    }
    
    .inbox-list {
        padding: 0;
    }
    
    .inbox-item {
        display: flex;
        align-items: center;
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        transition: background-color 0.2s;
        gap: 20px;
        opacity: 0.75;
    }
    
    .inbox-item:hover {
        background-color: #f8f9fa;
        opacity: 0.9;
    }
    
    .item-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .item-icon.room {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        color: #721c24;
    }
    
    .item-icon.event {
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        color: #7b1fa2;
    }
    
    .item-content {
        flex: 1;
        min-width: 0;
    }
    
    .item-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }
    
    .item-title {
        font-weight: 600;
        font-size: 16px;
        color: var(--dark-color);
        margin: 0;
        text-decoration: line-through;
    }
    
    .status-badge {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .status-badge.confirmed {
        background: #d4edda;
        color: #155724;
    }
    
    .status-badge.checkin {
        background: #cce5ff;
        color: #004085;
    }
    
    .status-badge.checkout {
        background: #e2e3e5;
        color: #383d41;
    }
    
    .status-badge.cancelled {
        background: #f8d7da;
        color: #721c24;
    }
    
    .item-meta {
        display: flex;
        align-items: center;
        gap: 15px;
        font-size: 13px;
        color: #6c757d;
        flex-wrap: wrap;
    }
    
    .item-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .item-amount {
        font-size: 18px;
        font-weight: 700;
        color: #dc3545;
        text-align: right;
        min-width: 120px;
        text-decoration: line-through;
    }
    
    .item-actions {
        display: flex;
        gap: 8px;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        background: white;
        color: #6c757d;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 14px;
    }
    
    .action-btn:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .action-btn.restore:hover {
        background: #d4edda;
        color: #155724;
        border-color: #28a745;
    }
    
    .action-btn.perm-delete:hover {
        background: #721c24;
        color: white;
        border-color: #721c24;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 30px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #f8d7da;
    }
    
    .deleted-date {
        font-size: 12px;
        color: #dc3545;
        font-style: italic;
    }
    
    /* Floating Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }
    
    .modal-window {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        width: 90%;
        max-width: 420px;
        transform: scale(0.9) translateY(20px);
        transition: transform 0.3s ease;
        overflow: hidden;
    }
    
    .modal-overlay.active .modal-window {
        transform: scale(1) translateY(0);
    }
    
    .modal-header {
        padding: 24px 24px 16px;
        text-align: center;
    }
    
    .modal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 28px;
    }
    
    .modal-icon.restore {
        background: #d4edda;
        color: #155724;
    }
    
    .modal-icon.perm-delete {
        background: #721c24;
        color: white;
    }
    
    .modal-header h3 {
        margin: 0 0 8px;
        font-size: 20px;
        color: var(--dark-color);
    }
    
    .modal-header p {
        margin: 0;
        color: #6c757d;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .modal-body {
        padding: 0 24px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .reservation-preview {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 16px;
    }
    
    .reservation-preview .item-title {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 8px;
    }
    
    .reservation-preview .item-meta {
        font-size: 13px;
        color: #6c757d;
    }
    
    .modal-footer {
        padding: 16px 24px 24px;
        display: flex;
        gap: 12px;
        justify-content: center;
    }
    
    .modal-btn {
        padding: 12px 24px;
        border-radius: 10px;
        border: none;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 100px;
    }
    
    .modal-btn-secondary {
        background: #e9ecef;
        color: #495057;
    }
    
    .modal-btn-secondary:hover {
        background: #dee2e6;
    }
    
    .modal-btn-success {
        background: #28a745;
        color: white;
    }
    
    .modal-btn-success:hover {
        background: #218838;
    }
    
    .modal-btn-danger {
        background: #721c24;
        color: white;
    }
    
    .modal-btn-danger:hover {
        background: #5a1a1e;
    }
    
    @media (max-width: 768px) {
        .inbox-item {
            flex-wrap: wrap;
            padding: 15px 20px;
        }
        
        .item-amount {
            width: 100%;
            text-align: left;
            margin-top: 10px;
        }
        
        .item-actions {
            width: 100%;
            justify-content: flex-end;
            margin-top: 10px;
        }
    }
</style>

<div class="inbox-container">
    <div class="inbox-header">
        <h2><i class="fas fa-trash-alt"></i> Trash</h2>
    </div>
    
    <div class="trash-notice">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Items in trash will be automatically deleted after 30 days. You can restore them before then.</span>
    </div>
    
    <div class="trash-actions">
        <a href="inbox.php"><i class="fas fa-inbox"></i> Back to Inbox</a>
        <a href="archive.php"><i class="fas fa-archive"></i> Archive</a>
    </div>
    
    <?php if (count($allReservations) > 0): ?>
    <div class="inbox-list">
        <?php foreach ($allReservations as $reservation): ?>
        <div class="inbox-item">
            <div class="item-icon <?php echo $reservation['type']; ?>">
                <?php echo getTypeIcon($reservation['type']); ?>
            </div>
            
            <div class="item-content">
                <div class="item-title-row">
                    <h4 class="item-title"><?php echo htmlspecialchars($reservation['item_name']); ?></h4>
                    <?php echo getStatusBadge($reservation['status']); ?>
                </div>
                <div class="item-meta">
                    <span><i class="fas fa-calendar"></i> 
                        <?php echo formatDate($reservation['check_in']); ?> - 
                        <?php echo formatDate($reservation['check_out']); ?>
                    </span>
                    <span class="deleted-date">
                        <i class="fas fa-trash"></i> Deleted <?php echo formatDate($reservation['updated_at'], 'M d, Y'); ?>
                    </span>
                </div>
            </div>
            
            <div class="item-amount">
                <?php echo formatPrice($reservation['total_amount']); ?>
            </div>
            
            <div class="item-actions">
                <button type="button" class="action-btn restore" title="Restore to Inbox"
                    onclick="openRestoreModal(<?php echo $reservation['id']; ?>, '<?php echo $reservation['type']; ?>', '<?php echo htmlspecialchars(addslashes($reservation['item_name'])); ?>', '<?php echo formatDate($reservation['check_in']); ?>')">
                    <i class="fas fa-undo"></i>
                </button>
                
                <button type="button" class="action-btn perm-delete" title="Delete Permanently"
                    onclick="openPermDeleteModal(<?php echo $reservation['id']; ?>, '<?php echo $reservation['type']; ?>', '<?php echo htmlspecialchars(addslashes($reservation['item_name'])); ?>', '<?php echo formatDate($reservation['check_in']); ?>')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-trash-alt"></i>
        <h3>Trash is empty</h3>
        <p>No deleted reservations found.</p>
        <a href="inbox.php" class="btn btn-primary">
            <i class="fas fa-inbox"></i> Back to Inbox
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Restore Modal -->
<div class="modal-overlay" id="restoreModal">
    <div class="modal-window">
        <div class="modal-header">
            <div class="modal-icon restore">
                <i class="fas fa-undo"></i>
            </div>
            <h3>Restore this reservation to inbox?</h3>
            <p>This will move the reservation back to your active inbox.</p>
        </div>
        <div class="modal-body">
            <div class="reservation-preview">
                <div class="item-title" id="restoreItemTitle"></div>
                <div class="item-meta" id="restoreItemMeta"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('restoreModal')">Cancel</button>
            <button type="button" class="modal-btn modal-btn-success" onclick="confirmRestore()">Restore</button>
        </div>
    </div>
</div>

<!-- Permanent Delete Modal -->
<div class="modal-overlay" id="permDeleteModal">
    <div class="modal-window">
        <div class="modal-header">
            <div class="modal-icon perm-delete">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Permanently delete this reservation?</h3>
            <p>This action cannot be undone. The reservation will be permanently removed from your account.</p>
        </div>
        <div class="modal-body">
            <div class="reservation-preview">
                <div class="item-title" id="permDeleteItemTitle"></div>
                <div class="item-meta" id="permDeleteItemMeta"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('permDeleteModal')">Cancel</button>
            <button type="button" class="modal-btn modal-btn-danger" onclick="confirmPermDelete()">Delete Permanently</button>
        </div>
    </div>
</div>

<!-- Hidden Forms for Submission -->
<form method="POST" id="restoreForm" style="display: none;">
    <input type="hidden" name="action" value="restore">
    <input type="hidden" name="id" id="restoreFormId">
    <input type="hidden" name="type" id="restoreFormType">
</form>

<form method="POST" id="permDeleteForm" style="display: none;">
    <input type="hidden" name="action" value="permanent_delete">
    <input type="hidden" name="id" id="permDeleteFormId">
    <input type="hidden" name="type" id="permDeleteFormType">
</form>

<script>
function openRestoreModal(id, type, title, date) {
    document.getElementById('restoreFormId').value = id;
    document.getElementById('restoreFormType').value = type;
    document.getElementById('restoreItemTitle').textContent = title;
    document.getElementById('restoreItemMeta').innerHTML = '<i class="fas fa-calendar"></i> ' + date;
    document.getElementById('restoreModal').classList.add('active');
}

function openPermDeleteModal(id, type, title, date) {
    document.getElementById('permDeleteFormId').value = id;
    document.getElementById('permDeleteFormType').value = type;
    document.getElementById('permDeleteItemTitle').textContent = title;
    document.getElementById('permDeleteItemMeta').innerHTML = '<i class="fas fa-calendar"></i> ' + date;
    document.getElementById('permDeleteModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function confirmRestore() {
    document.getElementById('restoreForm').submit();
}

function confirmPermDelete() {
    document.getElementById('permDeleteForm').submit();
}

// Close modal when clicking overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});
</script>

<?php require_once '../includes/user-footer.php'; ?>
