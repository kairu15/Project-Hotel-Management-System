<?php
$pageTitle = 'Admin Dashboard';
require_once '../includes/config.php';

// Check if user is admin
if (!isAdmin()) {
    showAlert('Access denied. Admin privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/admin-header.php';

$db = getDB();

// Get statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn(),
    'total_bookings' => $db->query("SELECT COUNT(*) FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'total_revenue' => $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'checked_out' AND MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn(),
    'occupied_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn(),
    'available_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn(),
    'maintenance_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'maintenance'")->fetchColumn(),
];

// Get recent bookings
$recentBookings = $db->query("
    SELECT b.*, u.first_name, u.last_name, rc.category_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN room_categories rc ON b.category_id = rc.category_id 
    ORDER BY b.created_at DESC 
    LIMIT 10
")->fetchAll();

// Get monthly revenue data for chart
$revenueData = $db->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as revenue 
    FROM bookings 
    WHERE status IN ('confirmed', 'checked_out') 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month 
    ORDER BY month
")->fetchAll();

// Get room occupancy by category
$occupancyByCategory = $db->query("
    SELECT rc.category_name, COUNT(r.room_id) as total,
    SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) as occupied,
    SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) as available
    FROM room_categories rc
    LEFT JOIN rooms r ON rc.category_id = r.category_id
    GROUP BY rc.category_id
")->fetchAll();
?>

<!-- Admin Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; gap: 15px; margin-bottom: 30px;">
            <a href="admin-users.php" class="btn btn-outline">Manage Users</a>
            <a href="admin-rooms.php" class="btn btn-primary">Manage Rooms</a>
        </div>
        <!-- Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-users" style="font-size: 30px; color: var(--primary-color);"></i>
                    <span style="font-size: 12px; color: #28a745;"><i class="fas fa-arrow-up"></i> +12%</span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo number_format($stats['total_users']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Total Users</p>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-calendar-check" style="font-size: 30px; color: var(--info-color);"></i>
                    <span style="font-size: 12px; color: #28a745;"><i class="fas fa-arrow-up"></i> +8%</span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo number_format($stats['total_bookings']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Bookings (30 days)</p>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-peso-sign" style="font-size: 30px; color: var(--success-color);"></i>
                    <span style="font-size: 12px; color: #28a745;"><i class="fas fa-arrow-up"></i> +15%</span>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;">₱<?php echo number_format($stats['total_revenue']); ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Revenue (This Month)</p>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-bed" style="font-size: 30px; color: var(--warning-color);"></i>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo $stats['occupied_rooms']; ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Occupied Rooms</p>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-door-open" style="font-size: 30px; color: var(--success-color);"></i>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo $stats['available_rooms']; ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Available Rooms</p>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                    <i class="fas fa-tools" style="font-size: 30px; color: var(--danger-color);"></i>
                </div>
                <h3 style="font-size: 28px; margin-bottom: 5px;"><?php echo $stats['maintenance_rooms']; ?></h3>
                <p style="font-size: 14px; color: #666; margin: 0;">Maintenance</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Recent Bookings -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 20px; margin: 0;">Recent Bookings</h3>
                    <a href="admin-bookings.php" style="color: var(--primary-color); font-size: 14px;">View All</a>
                </div>
                <div style="padding: 0;">
                    <?php foreach ($recentBookings as $booking): ?>
                    <div style="padding: 20px 30px; border-bottom: 1px solid var(--gray-light); display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; align-items: center; gap: 20px;">
                        <div>
                            <h4 style="font-size: 15px; margin-bottom: 3px;"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h4>
                            <p style="font-size: 13px; color: #666;"><?php echo htmlspecialchars($booking['category_name']); ?></p>
                        </div>
                        <div>
                            <p style="font-size: 13px; color: #666; margin: 0;">Check-in</p>
                            <p style="font-size: 14px; font-weight: 500; margin: 0;"><?php echo formatDate($booking['check_in'], 'M d, Y'); ?></p>
                        </div>
                        <div>
                            <p style="font-size: 13px; color: #666; margin: 0;">Amount</p>
                            <p style="font-size: 14px; font-weight: 500; color: var(--primary-color); margin: 0;"><?php echo formatPrice($booking['total_amount']); ?></p>
                        </div>
                        <div>
                            <?php
                            $statusColors = [
                                'pending' => ['#fff3cd', '#856404'],
                                'confirmed' => ['#d4edda', '#155724'],
                                'checked_in' => ['#cce5ff', '#004085'],
                                'checked_out' => ['#e2e3e5', '#383d41'],
                                'cancelled' => ['#f8d7da', '#721c24']
                            ];
                            $color = $statusColors[$booking['status']] ?? ['#fff3cd', '#856404'];
                            ?>
                            <span style="padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: <?php echo $color[0]; ?>; color: <?php echo $color[1]; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                        <div>
                            <a href="admin-booking-details.php?id=<?php echo $booking['booking_id']; ?>" style="color: var(--primary-color);"><i class="fas fa-eye"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Room Occupancy -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 25px 30px; border-bottom: 1px solid var(--gray-light);">
                    <h3 style="font-size: 20px; margin: 0;">Room Occupancy</h3>
                </div>
                <div style="padding: 25px 30px;">
                    <?php foreach ($occupancyByCategory as $cat): 
                        $total = $cat['total'] ?: 1;
                        $occupiedPct = ($cat['occupied'] / $total) * 100;
                    ?>
                    <div style="margin-bottom: 25px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                            <span style="font-size: 14px; color: #666;"><?php echo $cat['occupied']; ?>/<?php echo $cat['total']; ?> occupied</span>
                        </div>
                        <div style="height: 8px; background-color: var(--gray-light); border-radius: 4px; overflow: hidden;">
                            <div style="width: <?php echo $occupiedPct; ?>%; height: 100%; background-color: var(--primary-color); transition: width 0.3s;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 30px;">
            <a href="admin-bookings.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-calendar-alt" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Bookings</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">View and manage reservations</p>
            </a>
            
            <a href="admin-rooms.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-bed" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Rooms</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Add, edit room details</p>
            </a>
            
            <a href="admin-users.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-users-cog" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Manage Users</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Staff and guest accounts</p>
            </a>
            
            <a href="admin-reports.php" style="background-color: white; padding: 25px; border-radius: 10px; text-decoration: none; color: var(--dark-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.05)';">
                <i class="fas fa-chart-bar" style="font-size: 30px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h4 style="font-size: 18px; margin-bottom: 5px;">Reports</h4>
                <p style="font-size: 14px; color: #666; margin: 0;">Financial & occupancy reports</p>
            </a>
        </div>
    </div>
</section>

<?php require_once '../includes/admin-footer.php'; ?>
