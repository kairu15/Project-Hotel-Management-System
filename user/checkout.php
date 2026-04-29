<?php
/**
 * Check-Out - Bayawan Bai Hotel
 * Shows guests who have completed their stay
 */

$pageTitle = 'Check-Out';
require_once '../includes/config.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

require_once '../includes/user-header.php';

$db = getDB();
$userId = getUserId();

// Handle archive and delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'room';
    
    if ($id > 0) {
        $table = ($type === 'event') ? 'event_bookings' : 'bookings';
        $idColumn = ($type === 'event') ? 'event_booking_id' : 'booking_id';
        
        $verifyStmt = $db->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? AND user_id = ?");
        $verifyStmt->execute([$id, $userId]);
        $item = $verifyStmt->fetch();
        
        if ($item) {
            if ($action === 'archive') {
                $updateStmt = $db->prepare("UPDATE {$table} SET is_archived = 1, updated_at = NOW() WHERE {$idColumn} = ?");
                $updateStmt->execute([$id]);
                showAlert('Item moved to archive.', 'success');
            } elseif ($action === 'delete') {
                $updateStmt = $db->prepare("UPDATE {$table} SET is_deleted = 1, updated_at = NOW() WHERE {$idColumn} = ?");
                $updateStmt->execute([$id]);
                showAlert('Item moved to trash.', 'success');
            }
        }
        redirect('checkout.php');
    }
}

// Get checked-out reservations only
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
        b.checked_out_at,
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
    AND b.status = 'checked_out'
    AND b.is_archived = 0 
    AND b.is_deleted = 0
    ORDER BY b.checked_out_at DESC
");
$roomStmt->execute([$userId]);
$roomBookings = $roomStmt->fetchAll();

$allReservations = $roomBookings;

// Get status counts for all categories
$statusStmt = $db->prepare("
    SELECT
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND is_archived = 0 AND is_deleted = 0) as all_count,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'confirmed' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'confirmed' AND is_archived = 0 AND is_deleted = 0) as confirmed,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'checked_in' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'checked_in' AND is_archived = 0 AND is_deleted = 0) as checked_in,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'checked_out' AND is_archived = 0 AND is_deleted = 0) as checked_out,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'cancelled' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'cancelled' AND is_archived = 0 AND is_deleted = 0) as cancelled,
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'completed' AND is_archived = 0 AND is_deleted = 0) as completed,
        (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'pending' AND is_archived = 0 AND is_deleted = 0) +
        (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND status = 'pending' AND is_archived = 0 AND is_deleted = 0) as pending
");
$statusStmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId]);
$statusCounts = $statusStmt->fetch();

function getTypeIcon($type) {
    return $type === 'room' 
        ? '<i class="fas fa-bed" title="Room Booking"></i>' 
        : '<i class="fas fa-calendar-week" title="Event Booking"></i>';
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
        background: linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%);
    }
    
    .inbox-header h2 {
        margin: 0;
        font-size: 24px;
        color: #383d41;
    }
    
    .inbox-header h2 i {
        margin-right: 10px;
    }
    
    .inbox-filters {
        padding: 15px 30px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
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
    
    .filter-btn:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .filter-btn.active {
        background: #6c757d;
        color: white;
        border-color: #6c757d;
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
    }
    
    .inbox-item:hover {
        background-color: #f8f9fa;
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
        background: linear-gradient(135deg, #e2e3e5, #d6d8db);
        color: #383d41;
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
        background: #e2e3e5;
        color: #383d41;
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
    }
    
    .action-btn:hover {
        background: #f8f9fa;
        border-color: #adb5bd;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 30px;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        color: #e2e3e5;
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
    
    .modal-icon.archive {
        background: #e9ecef;
        color: #495057;
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
    
    .modal-btn-primary {
        background: var(--primary-color);
        color: white;
    }
    
    .modal-btn-primary:hover {
        background: #1e3a5f;
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
        <h2><i class="fas fa-door-closed"></i> Completed Stays</h2>
    </div>
    
    <div class="inbox-filters">
        <a href="inbox.php" class="filter-btn"><i class="fas fa-inbox"></i> All <span class="count"><?php echo $statusCounts['all_count']; ?></span></a>
        <a href="confirmed.php" class="filter-btn"><i class="fas fa-check-circle"></i> Confirmed <span class="count"><?php echo $statusCounts['confirmed']; ?></span></a>
        <a href="checkin.php" class="filter-btn"><i class="fas fa-door-open"></i> Check-In <span class="count"><?php echo $statusCounts['checked_in']; ?></span></a>
        <a href="checkout.php" class="filter-btn active"><i class="fas fa-door-closed"></i> Check-Out <span class="count"><?php echo $statusCounts['checked_out']; ?></span></a>
        <a href="cancelled.php" class="filter-btn"><i class="fas fa-times-circle"></i> Cancelled <span class="count"><?php echo $statusCounts['cancelled']; ?></span></a>
        <a href="completed.php" class="filter-btn"><i class="fas fa-check-double"></i> Completed <span class="count"><?php echo $statusCounts['completed']; ?></span></a>
        <a href="pending.php" class="filter-btn"><i class="fas fa-clock"></i> Pending <span class="count"><?php echo $statusCounts['pending']; ?></span></a>
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
                    <span class="status-badge">
                        <i class="fas fa-check-double"></i> Completed
                    </span>
                </div>
                <div class="item-meta">
                    <span><i class="fas fa-calendar-check"></i> 
                        Stayed: <?php echo formatDate($reservation['check_in']); ?> - 
                        <?php echo formatDate($reservation['check_out']); ?>
                    </span>
                    <?php if ($reservation['type'] === 'room' && !empty($reservation['room_number'])): ?>
                        <span><i class="fas fa-bed"></i> Room <?php echo htmlspecialchars($reservation['room_number']); ?></span>
                    <?php endif; ?>
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
                
                <button type="button" class="action-btn archive" title="Archive"
                    onclick="openArchiveModal(<?php echo $reservation['id']; ?>, '<?php echo $reservation['type']; ?>', '<?php echo htmlspecialchars(addslashes($reservation['item_name'])); ?>', '<?php echo formatDate($reservation['check_in']); ?>')">
                    <i class="fas fa-archive"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-door-closed"></i>
        <h3>No completed stays yet</h3>
        <p>You haven't checked out of any reservations yet.</p>
        <a href="checkin.php" class="btn btn-primary">
            <i class="fas fa-door-open"></i> View Active Check-ins
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Archive Modal -->
<div class="modal-overlay" id="archiveModal">
    <div class="modal-window">
        <div class="modal-header">
            <div class="modal-icon archive">
                <i class="fas fa-archive"></i>
            </div>
            <h3>Archive this reservation?</h3>
            <p>Archived reservations are moved to your archive folder and can be restored anytime.</p>
        </div>
        <div class="modal-body">
            <div class="reservation-preview">
                <div class="item-title" id="archiveItemTitle"></div>
                <div class="item-meta" id="archiveItemMeta"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" onclick="closeModal('archiveModal')">Cancel</button>
            <button type="button" class="modal-btn modal-btn-primary" onclick="confirmArchive()">Archive</button>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form method="POST" id="archiveForm" style="display: none;">
    <input type="hidden" name="action" value="archive">
    <input type="hidden" name="id" id="archiveFormId">
    <input type="hidden" name="type" id="archiveFormType">
</form>

<script>
function openArchiveModal(id, type, title, date) {
    document.getElementById('archiveFormId').value = id;
    document.getElementById('archiveFormType').value = type;
    document.getElementById('archiveItemTitle').textContent = title;
    document.getElementById('archiveItemMeta').innerHTML = '<i class="fas fa-calendar"></i> ' + date;
    document.getElementById('archiveModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function confirmArchive() {
    document.getElementById('archiveForm').submit();
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
