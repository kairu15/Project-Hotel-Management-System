<?php
$pageTitle = 'Staff Dashboard';
require_once '../includes/config.php';

// Check if user is staff (admin, manager, or receptionist)
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

require_once '../includes/staff-header.php';

$db = getDB();
$userRole = getUserRole();

// Get today's stats
$today = date('Y-m-d');
$stats = [
    'today_checkins' => $db->query("SELECT COUNT(*) FROM bookings WHERE check_in = '$today' AND status IN ('confirmed', 'checked_in')")->fetchColumn(),
    'today_checkouts' => $db->query("SELECT COUNT(*) FROM bookings WHERE check_out = '$today' AND status IN ('confirmed', 'checked_in')")->fetchColumn(),
    'pending_bookings' => $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    'occupied_rooms' => $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn(),
];

// Get today's arrivals (first 3 for summary)
$arrivals = $db->query("
    SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.check_in = '$today'
    AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.check_in
    LIMIT 3
")->fetchAll();

// Get today's departures (first 3 for summary)
$departures = $db->query("
    SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.check_out = '$today'
    AND b.status IN ('confirmed', 'checked_in')
    ORDER BY b.check_out
    LIMIT 3
")->fetchAll();

// Get pending bookings (first 3 for summary)
$pendingBookings = $db->query("
    SELECT b.*, u.first_name, u.last_name, rc.category_name
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
    LIMIT 3
")->fetchAll();

// Get current checked-in guests (first 3 for quick checkout summary)
$currentGuests = $db->query("
    SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
    JOIN room_categories rc ON b.category_id = rc.category_id
    LEFT JOIN rooms r ON b.room_id = r.room_id
    WHERE b.status = 'checked_in'
    ORDER BY b.check_out ASC
    LIMIT 3
")->fetchAll();

// Get room status
$roomStatus = $db->query("
    SELECT r.*, rc.category_name
    FROM rooms r
    JOIN room_categories rc ON r.category_id = rc.category_id
    ORDER BY r.floor, r.room_number
")->fetchAll();

// Get event space status
$eventSpaceStatus = $db->query("
    SELECT * FROM events ORDER BY floor, event_name
")->fetchAll();

// Get pending event bookings count
$pendingEventBookings = $db->query("SELECT COUNT(*) FROM event_bookings WHERE status = 'pending'")->fetchColumn();
?>
<!-- Staff Content -->
<section style="padding: 30px 0; background-color: var(--gray-light); min-height: calc(100vh - 250px);">
    <div class="container">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
            <a href="walkin-booking.php" class="btn" style="background-color: white; color: var(--primary-color);"><i class="fas fa-plus"></i> Walk-in Booking</a>
        </div>
        <!-- Quick Stats -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--info-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Today's Arrivals</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['today_checkins']; ?></h3>
                    </div>
                    <i class="fas fa-sign-in-alt" style="font-size: 40px; color: var(--info-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--warning-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Today's Departures</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['today_checkouts']; ?></h3>
                    </div>
                    <i class="fas fa-sign-out-alt" style="font-size: 40px; color: var(--warning-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--danger-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Pending Bookings</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['pending_bookings']; ?></h3>
                    </div>
                    <i class="fas fa-clock" style="font-size: 40px; color: var(--danger-color); opacity: 0.3;"></i>
                </div>
            </div>
            
            <div style="background-color: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid var(--success-color);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <p style="font-size: 14px; color: #666; margin: 0 0 5px 0;">Occupied Rooms</p>
                        <h3 style="font-size: 32px; margin: 0;"><?php echo $stats['occupied_rooms']; ?></h3>
                    </div>
                    <i class="fas fa-bed" style="font-size: 40px; color: var(--success-color); opacity: 0.3;"></i>
                </div>
            </div>
        </div>
        
        <!-- Summary Panels Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Today's Arrivals Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-sign-in-alt" style="color: var(--info-color); margin-right: 10px;"></i>Today's Arrivals</h3>
                    <span style="background-color: var(--info-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $stats['today_checkins']; ?> guest<?php echo $stats['today_checkins'] != 1 ? 's' : ''; ?></span>
                </div>
                <div style="padding: 0;">
                    <?php if (count($arrivals) > 0): ?>
                        <?php foreach ($arrivals as $arrival): ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($arrival['first_name'] . ' ' . $arrival['last_name']); ?></h4>
                                    <p style="font-size: 13px; color: #666; margin: 0;">
                                        <?php echo htmlspecialchars($arrival['category_name']); ?>
                                        <?php echo $arrival['room_number'] ? ' – Room ' . $arrival['room_number'] : ''; ?>
                                    </p>
                                    <?php if ($arrival['status'] === 'checked_in'): ?>
                                    <span style="display: inline-block; margin-top: 5px; background-color: #d4edda; color: #155724; padding: 3px 10px; border-radius: 15px; font-size: 11px;">Checked In</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <p style="color: #666; margin: 0;">No arrivals scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="checkin.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Arrivals</a>
                </div>
            </div>
            
            <!-- Today's Departures Summary -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-sign-out-alt" style="color: var(--warning-color); margin-right: 10px;"></i>Today's Departures</h3>
                    <span style="background-color: var(--warning-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $stats['today_checkouts']; ?> guest<?php echo $stats['today_checkouts'] != 1 ? 's' : ''; ?></span>
                </div>
                <div style="padding: 0;">
                    <?php if (count($departures) > 0): ?>
                        <?php foreach ($departures as $departure): ?>
                        <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($departure['first_name'] . ' ' . $departure['last_name']); ?></h4>
                                    <p style="font-size: 13px; color: #666; margin: 0;">Room <?php echo htmlspecialchars($departure['room_number']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 40px; text-align: center;">
                            <p style="color: #666; margin: 0;">No departures scheduled for today</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                    <a href="checkout.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Departures</a>
                </div>
            </div>
        </div>
        
        <!-- Current Guests Summary -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-users" style="color: var(--info-color); margin-right: 10px;"></i>Current Guests</h3>
                <span style="background-color: var(--info-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'")->fetchColumn(); ?> total guests</span>
            </div>
            <div style="padding: 0;">
                <?php if (count($currentGuests) > 0): ?>
                    <?php foreach ($currentGuests as $guest): ?>
                    <div style="padding: 15px 25px; border-bottom: 1px solid var(--gray-light);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="font-size: 15px; margin: 0 0 3px 0;"><?php echo htmlspecialchars($guest['first_name'] . ' ' . $guest['last_name']); ?></h4>
                                        <p style="font-size: 13px; color: #666; margin: 0;">
                                            Room <?php echo htmlspecialchars($guest['room_number'] ?: 'Not Assigned'); ?> | 
                                            <?php echo htmlspecialchars($guest['category_name']); ?>
                                        </p>
                                        <p style="font-size: 12px; color: #999; margin: 3px 0 0 0;">
                                            <i class="fas fa-calendar-times"></i> Checkout: <?php echo formatDate($guest['check_out']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center;">
                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
                        <p style="color: #666; margin: 0;">No guests currently checked in</p>
                    </div>
                <?php endif; ?>
            </div>
            <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                <a href="checkout.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> Quick Checkout – View All Guests</a>
            </div>
        </div>
        
        <!-- Pending Bookings Summary -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-clock" style="color: var(--danger-color); margin-right: 10px;"></i>Pending Bookings (Requires Confirmation)</h3>
                <span style="background-color: var(--danger-color); color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;"><?php echo $stats['pending_bookings']; ?> pending</span>
            </div>
            <div style="padding: 0;">
                <?php if (count($pendingBookings) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: var(--gray-light);">
                            <th style="padding: 15px 25px; text-align: left; font-size: 14px;">Guest</th>
                            <th style="padding: 15px 25px; text-align: left; font-size: 14px;">Room Type</th>
                            <th style="padding: 15px 25px; text-align: left; font-size: 14px;">Dates</th>
                            <th style="padding: 15px 25px; text-align: right; font-size: 14px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingBookings as $booking): ?>
                        <tr style="border-bottom: 1px solid var(--gray-light);">
                            <td style="padding: 15px 25px;">
                                <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong>
                            </td>
                            <td style="padding: 15px 25px;"><?php echo htmlspecialchars($booking['category_name']); ?></td>
                            <td style="padding: 15px 25px;">
                                <?php echo formatDate($booking['check_in']); ?> - <?php echo formatDate($booking['check_out']); ?>
                            </td>
                            <td style="padding: 15px 25px; text-align: right; font-weight: 600; color: var(--primary-color);">
                                <?php echo formatPrice($booking['total_amount']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="padding: 40px; text-align: center;">
                    <p style="color: #666; margin: 0;">No pending bookings to confirm</p>
                </div>
                <?php endif; ?>
            </div>
            <div style="padding: 15px 25px; border-top: 1px solid var(--gray-light); text-align: center;">
                <a href="confirm-booking.php" class="btn btn-primary" style="padding: 8px 20px; font-size: 13px;"><i class="fas fa-list"></i> View All Pending Bookings</a>
            </div>
        </div>
        
        <!-- Room Status Grid -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light);">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-th-large" style="color: var(--primary-color); margin-right: 10px;"></i>Room Status Overview</h3>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 15px;">
                    <?php foreach ($roomStatus as $room): 
                        $statusColors = [
                            'available' => ['#d4edda', '#155724', '#28a745'],
                            'occupied' => ['#f8d7da', '#721c24', '#dc3545'],
                            'maintenance' => ['#fff3cd', '#856404', '#ffc107'],
                            'cleaning' => ['#cce5ff', '#004085', '#17a2b8'],
                            'reserved' => ['#e2e3e5', '#383d41', '#6c757d']
                        ];
                        $color = $statusColors[$room['status']] ?? $statusColors['available'];
                    ?>
                    <div style="background-color: <?php echo $color[0]; ?>; border: 2px solid <?php echo $color[2]; ?>; border-radius: 8px; padding: 15px; text-align: center;">
                        <h4 style="font-size: 18px; margin: 0 0 5px 0; color: <?php echo $color[1]; ?>"><?php echo htmlspecialchars($room['room_number']); ?></h4>
                        <p style="font-size: 12px; color: <?php echo $color[1]; ?>; margin: 0 0 10px 0;"><?php echo htmlspecialchars($room['category_name']); ?></p>
                        <span style="font-size: 11px; padding: 3px 10px; border-radius: 20px; background-color: <?php echo $color[2]; ?>; color: white; text-transform: uppercase;">
                            <?php echo $room['status']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Event Space Status Overview -->
        <div style="background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="padding: 20px 25px; border-bottom: 1px solid var(--gray-light); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; margin: 0;"><i class="fas fa-calendar-alt" style="color: var(--primary-color); margin-right: 10px;"></i>Event Space Status Overview</h3>
                <a href="staff-event-bookings.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                    <i class="fas fa-list"></i> View Event Bookings (<?php echo $pendingEventBookings; ?> pending)
                </a>
            </div>
            <div style="padding: 25px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <?php foreach ($eventSpaceStatus as $space): 
                        $eventStatusColors = [
                            'available' => ['#d4edda', '#155724', '#28a745'],
                            'reserved' => ['#fff3cd', '#856404', '#ffc107'],
                            'occupied' => ['#f8d7da', '#721c24', '#dc3545']
                        ];
                        $color = $eventStatusColors[$space['status']] ?? $eventStatusColors['available'];
                    ?>
                    <div style="background-color: <?php echo $color[0]; ?>; border: 2px solid <?php echo $color[2]; ?>; border-radius: 8px; padding: 15px; text-align: center;">
                        <h4 style="font-size: 16px; margin: 0 0 5px 0; color: <?php echo $color[1]; ?>"><?php echo htmlspecialchars($space['event_name']); ?></h4>
                        <p style="font-size: 12px; color: <?php echo $color[1]; ?>; margin: 0 0 10px 0;">
                            Floor: <?php echo $space['floor'] ?: 'Ground'; ?> | 
                            <?php echo $space['maintenance_status'] === 'clean' ? 'Clean' : 'Under Maintenance'; ?>
                        </p>
                        <span style="font-size: 11px; padding: 3px 10px; border-radius: 20px; background-color: <?php echo $color[2]; ?>; color: white; text-transform: uppercase;">
                            <?php echo $space['status']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/staff-footer.php'; ?>
