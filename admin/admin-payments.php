<?php
$pageTitle = 'Manage Payments - Admin';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

$db = getDB();

// Handle update status
if (isset($_POST['update_status'])) {
    $paymentId = $_POST['payment_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    if ($paymentId && $status) {
        // Get payment reference for message
        $payStmt = $db->prepare("SELECT p.booking_id, b.booking_reference FROM payments p JOIN bookings b ON p.booking_id = b.booking_id WHERE p.payment_id = ?");
        $payStmt->execute([$paymentId]);
        $payment = $payStmt->fetch();
        $bookingRef = $payment['booking_reference'] ?? 'Booking';
        
        $stmt = $db->prepare("UPDATE payments SET status = ? WHERE payment_id = ?");
        $stmt->execute([$status, $paymentId]);
        $_SESSION['success'] = 'Payment for ' . $bookingRef . ' updated to ' . ucfirst($status);
    }
    redirect('admin-payments.php');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$methodFilter = $_GET['method'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query
$sql = "
    SELECT p.*, b.booking_id, b.check_in, b.check_out, u.first_name, u.last_name, u.email
    FROM payments p
    JOIN bookings b ON p.booking_id = b.booking_id
    JOIN users u ON p.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($statusFilter) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
}

if ($methodFilter) {
    $sql .= " AND p.payment_method = ?";
    $params[] = $methodFilter;
}

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR p.transaction_id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($dateFrom) {
    $sql .= " AND DATE(p.payment_date) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $sql .= " AND DATE(p.payment_date) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY p.payment_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get stats
$totalPayments = count($payments);
$totalAmount = array_sum(array_column($payments, 'amount'));
$completedAmount = array_sum(array_map(function($p) {
    return $p['status'] === 'completed' ? $p['amount'] : 0;
}, $payments));

$statusCounts = $db->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$paymentMethods = ['gcash', 'paypal', 'credit_card', 'cash', 'bank_transfer'];

require_once '../includes/admin-header.php';
?>

<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 20px;">
            <a href="admin-dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            <a href="admin-bookings.php" class="btn btn-outline">Manage Bookings</a>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--primary-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo $totalPayments; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Payments</p>
                    </div>
                    <i class="fas fa-money-bill-wave" style="font-size: 32px; color: var(--primary-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo formatPrice($totalAmount); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Total Amount</p>
                    </div>
                    <i class="fas fa-coins" style="font-size: 32px; color: var(--success-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--info-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 24px; margin: 0;"><?php echo formatPrice($completedAmount); ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Completed</p>
                    </div>
                    <i class="fas fa-check-circle" style="font-size: 32px; color: var(--info-color);"></i>
                </div>
            </div>
            <div style="background-color: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 28px; margin: 0;"><?php echo $statusCounts['pending'] ?? 0; ?></h3>
                        <p style="color: #666; margin: 5px 0 0;">Pending</p>
                    </div>
                    <i class="fas fa-clock" style="font-size: 32px; color: var(--warning-color);"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div style="background-color: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, transaction ID..." style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">Method</label>
                    <select name="method" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                        <option value="">All Methods</option>
                        <?php foreach ($paymentMethods as $method): ?>
                        <option value="<?php echo $method; ?>" <?php echo $methodFilter === $method ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $method)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">From Date</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; color: #666; margin-bottom: 5px;">To Date</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" style="width: 100%; padding: 10px; border: 1px solid var(--gray-light); border-radius: 5px;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Filter</button>
                    <a href="admin-payments.php" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Payments Table -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 20px; margin: 0;">Payments (<?php echo count($payments); ?>)</h3>
            </div>

            <?php if (count($payments) > 0): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Payment ID</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Customer</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Booking</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Amount</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Method</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Transaction ID</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Date</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Status</th>
                            <th style="padding: 15px 20px; text-align: left; font-size: 13px; color: #666; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment):
                        $statusColors = [
                            'pending' => ['#fff3cd', '#856404'],
                            'completed' => ['#d4edda', '#155724'],
                            'failed' => ['#f8d7da', '#721c24'],
                            'refunded' => ['#cce5ff', '#004085']
                        ];
                        $color = $statusColors[$payment['status']] ?? ['#e2e3e5', '#383d41'];
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 20px;">
                                <span style="font-weight: 600;">#<?php echo $payment['payment_id']; ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                                <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($payment['email']); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <a href="admin-booking-details.php?id=<?php echo $payment['booking_id']; ?>" style="color: var(--primary-color);">
                                    #<?php echo $payment['booking_id']; ?>
                                </a>
                            </td>
                            <td style="padding: 15px 20px; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($payment['amount']); ?>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="text-transform: capitalize;"><?php echo str_replace('_', ' ', $payment['payment_method']); ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($payment['transaction_id'] ?: 'N/A'); ?></span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <?php echo formatDate($payment['payment_date'], 'M d, Y'); ?>
                                <div style="font-size: 11px; color: #666;"><?php echo date('g:i A', strtotime($payment['payment_date'])); ?></div>
                            </td>
                            <td style="padding: 15px 20px;">
                                <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>; text-transform: capitalize;">
                                    <?php echo $payment['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 15px 20px;">
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" onclick="updatePayment(<?php echo htmlspecialchars(json_encode($payment)); ?>)" class="btn btn-sm btn-primary" style="padding: 5px 12px; font-size: 12px;">Update</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="padding: 60px; text-align: center;">
                <i class="fas fa-money-bill-wave" style="font-size: 48px; color: var(--gray-light); margin-bottom: 20px;"></i>
                <h3 style="color: #666;">No payments found</h3>
                <p style="color: #999;">Try adjusting your search filters</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Payment Modal -->
<div id="paymentModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 10px; width: 90%; max-width: 400px;">
        <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 20px; margin: 0;">Update Payment Status</h3>
            <button onclick="closePaymentModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" style="padding: 30px;">
            <input type="hidden" name="payment_id" id="payment_id">

            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Status</label>
                <select name="status" id="payment_status" style="width: 100%; padding: 12px; border: 1px solid var(--gray-light); border-radius: 5px; font-size: 14px;">
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>

            <div style="display: flex; gap: 15px; justify-content: flex-end;">
                <button type="button" onclick="closePaymentModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function updatePayment(payment) {
    document.getElementById('paymentModal').style.display = 'flex';
    document.getElementById('payment_id').value = payment.payment_id;
    document.getElementById('payment_status').value = payment.status;
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});
</script>

<?php require_once '../includes/admin-footer.php'; ?>
