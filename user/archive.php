<?php
/**
 * Archive - Bayawan Bai Hotel
 * Stores reservations that are no longer active but kept for records
 */

$pageTitle = 'Archive';
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
        
        $verifyStmt = $db->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? AND user_id = ? AND is_archived = 1");
        $verifyStmt->execute([$id, $userId]);
        $item = $verifyStmt->fetch();
        
        if ($item) {
            if ($action === 'restore') {
                $updateStmt = $db->prepare("UPDATE {$table} SET is_archived = 0, updated_at = NOW() WHERE {$idColumn} = ?");
                $updateStmt->execute([$id]);
                showAlert('Item restored to inbox.', 'success');
            } elseif ($action === 'delete') {
                $updateStmt = $db->prepare("UPDATE {$table} SET is_deleted = 1, is_archived = 0, updated_at = NOW() WHERE {$idColumn} = ?");
                $updateStmt->execute([$id]);
                showAlert('Item moved to trash.', 'success');
            }
        }
        redirect('archive.php');
    }
}

// Get archived reservations
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
    AND b.is_archived = 1
    AND b.is_deleted = 0
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
    AND eb.is_archived = 1
    AND eb.is_deleted = 0
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
        background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    }
    
    .inbox-header h2 {
        margin: 0;
        font-size: 24px;
        color: #495057;
    }
    
    .inbox-header h2 i {
        margin-right: 10px;
        color: #6c757d;
    }
    
    .archive-actions {
        padding: 15px 30px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        gap: 10px;
    }
    
    .archive-actions a {
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
    
    .archive-actions a:hover {
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
        opacity: 0.85;
    }
    
    .inbox-item:hover {
        background-color: #f8f9fa;
        opacity: 1;
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
        background: linear-gradient(135deg, #e9ecef, #dee2e6);
        color: #6c757d;
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
        color: #6c757d;
        text-align: right;
        min-width: 120px;
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
    
    .action-btn.delete:hover {
        background: #f8d7da;
        color: #dc3545;
        border-color: #dc3545;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 30px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #dee2e6;
    }
    
    .archived-date {
        font-size: 12px;
        color: #6c757d;
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
    
    .modal-icon.trash {
        background: #f8d7da;
        color: #dc3545;
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
        background: #dc3545;
        color: white;
    }
    
    .modal-btn-danger:hover {
        background: #c82333;
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
        <h2><i class="fas fa-archive"></i> Archive</h2>
    </div>
    
    <div class="archive-actions">
        <a href="inbox.php"><i class="fas fa-inbox"></i> Back to Inbox</a>
        <a href="trash.php"><i class="fas fa-trash-alt"></i> Trash</a>
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
                    <span class="archived-date">
                        <i class="fas fa-archive"></i> Archived <?php echo formatDate($reservation['updated_at'], 'M d, Y'); ?>
                    </span>
                </div>
            </div>
            
            <div class="item-amount">
                <?php echo formatPrice($reservation['total_amount']); ?>
            </div>
            
            <div class="item-actions">
                <?php if ($reservation['type'] === 'room'): ?>
                    <a href="booking-details.php?id=<?php echo $reservation['id']; ?>" class="action-btn" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                <?php else: ?>
                    <a href="event-booking-details.php?id=<?php echo $reservation['id']; ?>" class="action-btn" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                <?php endif; ?>
                
                <button type="button" class="action-btn restore" title="Restore to Inbox"
                    onclick="openRestoreModal(<?php echo $reservation['id']; ?>, '<?php echo $reservation['type']; ?>', '<?php echo htmlspecialchars(addslashes($reservation['item_name'])); ?>', '<?php echo formatDate($reservation['check_in']); ?>')">
                    <i class="fas fa-undo"></i>
                </button>
                
                <button type="button" class="action-btn delete" title="Move to Trash"
                    onclick="openTrashModal(<?php echo $reservation['id']; ?>, '<?php echo $reservation['type']; ?>', '<?php echo htmlspecialchars(addslashes($reservation['item_name'])); ?>', '<?php echo formatDate($reservation['check_in']); ?>')">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-archive"></i>
        <h3>Archive is empty</h3>
        <p>Archived reservations will appear here.</p>
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

<!-- Trash Modal -->
<div class="modal-overlay" id="trashModal">
    <div class="modal-window">
        <div class="modal-header">
            <div class="modal-icon trash">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3>Move to trash?</h3>
            <p>This will remove it from archive. Items in trash will be automatically deleted after 30 days.</p>
        </div>
        <div class="modal-body">
            <div class="reservation-preview">
                <div class="item-title" id="trashItemTitle"></div>
                <div class="item-meta" id="trashItemMeta"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('trashModal')">Cancel</button>
            <button type="button" class="modal-btn modal-btn-danger" onclick="confirmTrash()">Move to Trash</button>
        </div>
    </div>
</div>

<!-- Hidden Forms for Submission -->
<form method="POST" id="restoreForm" style="display: none;">
    <input type="hidden" name="action" value="restore">
    <input type="hidden" name="id" id="restoreFormId">
    <input type="hidden" name="type" id="restoreFormType">
</form>

<form method="POST" id="trashForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="trashFormId">
    <input type="hidden" name="type" id="trashFormType">
</form>

<script>
function openRestoreModal(id, type, title, date) {
    document.getElementById('restoreFormId').value = id;
    document.getElementById('restoreFormType').value = type;
    document.getElementById('restoreItemTitle').textContent = title;
    document.getElementById('restoreItemMeta').innerHTML = '<i class="fas fa-calendar"></i> ' + date;
    document.getElementById('restoreModal').classList.add('active');
}

function openTrashModal(id, type, title, date) {
    document.getElementById('trashFormId').value = id;
    document.getElementById('trashFormType').value = type;
    document.getElementById('trashItemTitle').textContent = title;
    document.getElementById('trashItemMeta').innerHTML = '<i class="fas fa-calendar"></i> ' + date;
    document.getElementById('trashModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function confirmRestore() {
    document.getElementById('restoreForm').submit();
}

function confirmTrash() {
    document.getElementById('trashForm').submit();
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
