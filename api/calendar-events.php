<?php
/**
 * Calendar Events API - Returns calendar data for FullCalendar.js
 * Supports Admin, Staff, and User roles with different event visibility
 */
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get parameters
$role = getUserRole();
$userId = getUserId();
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

try {
    $db = getDB();
    $events = [];

    switch ($role) {
        case 'admin':
            $events = getAdminCalendarEvents($db, $start, $end);
            break;
        case 'manager':
        case 'receptionist':
            $events = getStaffCalendarEvents($db, $userId, $start, $end);
            break;
        case 'guest':
            $events = getUserCalendarEvents($db, $userId, $start, $end);
            break;
        default:
            $events = [];
    }

    echo json_encode($events);

} catch (Exception $e) {
    error_log('Calendar API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to fetch calendar events']);
}

/**
 * Get all calendar events for Admin (all types)
 */
function getAdminCalendarEvents($db, $start, $end) {
    $events = [];

    // 1. Room Bookings (Blue)
    $bookings = $db->query("
        SELECT b.*, u.first_name, u.last_name, u.phone, rc.category_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE (b.check_in BETWEEN '$start' AND '$end' 
           OR b.check_out BETWEEN '$start' AND '$end'
           OR (b.check_in <= '$start' AND b.check_out >= '$end'))
        AND b.status IN ('pending', 'confirmed', 'checked_in', 'checked_out')
        ORDER BY b.check_in
    ")->fetchAll();

    foreach ($bookings as $booking) {
        $title = "Room " . ($booking['room_number'] ?? 'TBA') . " - " . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']);
        $description = sprintf(
            "Booking #%d\nGuest: %s %s\nRoom: %s (%s)\nCheck-in: %s\nCheck-out: %s\nStatus: %s\nPhone: %s",
            $booking['booking_id'],
            $booking['first_name'],
            $booking['last_name'],
            $booking['room_number'] ?? 'TBA',
            $booking['category_name'],
            date('M d, Y', strtotime($booking['check_in'])),
            date('M d, Y', strtotime($booking['check_out'])),
            ucfirst($booking['status']),
            $booking['phone']
        );

        // Check-in event
        $events[] = [
            'id' => 'booking_checkin_' . $booking['booking_id'],
            'title' => $title . ' - Check-in',
            'start' => $booking['check_in'],
            'end' => $booking['check_in'],
            'allDay' => true,
            'color' => '#3b82f6', // Blue
            'textColor' => '#ffffff',
            'category' => 'booking',
            'description' => $description,
            'extendedProps' => [
                'type' => 'booking_checkin',
                'booking_id' => $booking['booking_id'],
                'guest' => $booking['first_name'] . ' ' . $booking['last_name'],
                'room' => $booking['room_number'],
                'status' => $booking['status']
            ]
        ];

        // Check-out event
        $events[] = [
            'id' => 'booking_checkout_' . $booking['booking_id'],
            'title' => $title . ' - Check-out',
            'start' => $booking['check_out'],
            'end' => $booking['check_out'],
            'allDay' => true,
            'color' => '#60a5fa', // Light Blue
            'textColor' => '#ffffff',
            'category' => 'booking',
            'description' => $description,
            'extendedProps' => [
                'type' => 'booking_checkout',
                'booking_id' => $booking['booking_id'],
                'guest' => $booking['first_name'] . ' ' . $booking['last_name'],
                'room' => $booking['room_number'],
                'status' => $booking['status']
            ]
        ];
    }

    // 2. Food Orders (Green)
    $foodOrders = $db->query("
        SELECT fo.*, mi.item_name, u.first_name, u.last_name, r.room_number
        FROM food_orders fo
        JOIN menu_items mi ON fo.food_id = mi.item_id
        JOIN users u ON fo.user_id = u.user_id
        LEFT JOIN bookings b ON fo.booking_id = b.booking_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE DATE(fo.created_at) BETWEEN '$start' AND '$end'
        AND fo.status IN ('pending', 'preparing', 'ready', 'delivered')
        ORDER BY fo.created_at
    ")->fetchAll();

    foreach ($foodOrders as $order) {
        $deliveryTime = $order['delivered_at'] ?? $order['created_at'];
        $events[] = [
            'id' => 'food_order_' . $order['order_id'],
            'title' => 'Order #' . $order['order_id'] . ' - ' . htmlspecialchars($order['item_name']) . ' (Room ' . ($order['room_number'] ?? 'N/A') . ')',
            'start' => $deliveryTime,
            'allDay' => false,
            'color' => '#22c55e', // Green
            'textColor' => '#ffffff',
            'category' => 'food_order',
            'description' => sprintf(
                "Order #%d\nItem: %s\nGuest: %s %s\nRoom: %s\nQuantity: %d\nStatus: %s\nTotal: ₱%.2f",
                $order['order_id'],
                $order['item_name'],
                $order['first_name'],
                $order['last_name'],
                $order['room_number'] ?? 'N/A',
                $order['quantity'],
                ucfirst($order['status']),
                $order['total_price']
            ),
            'extendedProps' => [
                'type' => 'food_order',
                'order_id' => $order['order_id'],
                'status' => $order['status'],
                'room' => $order['room_number']
            ]
        ];
    }

    // 3. Event Bookings (Purple)
    $eventBookings = $db->query("
        SELECT eb.*, es.space_name, u.first_name, u.last_name, u.phone
        FROM event_bookings eb
        JOIN event_spaces es ON eb.space_id = es.space_id
        LEFT JOIN users u ON eb.user_id = u.user_id
        WHERE eb.event_date BETWEEN '$start' AND '$end'
        AND eb.status IN ('pending', 'confirmed', 'completed')
        ORDER BY eb.event_date, eb.start_time
    ")->fetchAll();

    foreach ($eventBookings as $event) {
        $startDateTime = $event['event_date'];
        if ($event['start_time']) {
            $startDateTime .= 'T' . $event['start_time'];
        }

        $endDateTime = $event['event_date'];
        if ($event['end_time']) {
            $endDateTime .= 'T' . $event['end_time'];
        }

        $contactName = $event['first_name'] ? $event['first_name'] . ' ' . $event['last_name'] : $event['inquiry_name'];
        $contactPhone = $event['phone'] ?? $event['inquiry_phone'];

        $events[] = [
            'id' => 'event_' . $event['event_booking_id'],
            'title' => $event['event_type'] . ' - ' . $event['space_name'],
            'start' => $startDateTime,
            'end' => $endDateTime,
            'allDay' => empty($event['start_time']),
            'color' => '#a855f7', // Purple
            'textColor' => '#ffffff',
            'category' => 'event',
            'description' => sprintf(
                "Event Booking #%d\nType: %s\nVenue: %s\nContact: %s\nGuests: %d\nDate: %s\nTime: %s - %s\nStatus: %s\nPhone: %s",
                $event['event_booking_id'],
                $event['event_type'],
                $event['space_name'],
                $contactName,
                $event['guests_count'],
                date('M d, Y', strtotime($event['event_date'])),
                $event['start_time'] ? date('h:i A', strtotime($event['start_time'])) : 'TBA',
                $event['end_time'] ? date('h:i A', strtotime($event['end_time'])) : 'TBA',
                ucfirst($event['status']),
                $contactPhone ?? 'N/A'
            ),
            'extendedProps' => [
                'type' => 'event',
                'event_booking_id' => $event['event_booking_id'],
                'status' => $event['status'],
                'venue' => $event['space_name']
            ]
        ];
    }

    // 4. Maintenance Requests (Orange)
    $maintenance = $db->query("
        SELECT mr.*, r.room_number, u.first_name, u.last_name
        FROM maintenance_requests mr
        LEFT JOIN rooms r ON mr.room_id = r.room_id
        LEFT JOIN users u ON mr.reported_by = u.user_id
        WHERE DATE(mr.created_at) BETWEEN '$start' AND '$end'
        OR (mr.resolved_at IS NOT NULL AND DATE(mr.resolved_at) BETWEEN '$start' AND '$end')
        ORDER BY mr.created_at
    ")->fetchAll();

    foreach ($maintenance as $req) {
        $events[] = [
            'id' => 'maintenance_' . $req['request_id'],
            'title' => ($req['room_number'] ? 'Room ' . $req['room_number'] : 'General') . ' - ' . ucfirst($req['issue_type']),
            'start' => $req['created_at'],
            'allDay' => true,
            'color' => '#f97316', // Orange
            'textColor' => '#ffffff',
            'category' => 'maintenance',
            'description' => sprintf(
                "Maintenance Request #%d\nType: %s\nRoom: %s\nPriority: %s\nStatus: %s\nDescription: %s\nReported: %s\nReported By: %s",
                $req['request_id'],
                ucfirst($req['issue_type']),
                $req['room_number'] ?? 'N/A',
                ucfirst($req['priority']),
                ucfirst($req['status']),
                $req['description'],
                date('M d, Y h:i A', strtotime($req['created_at'])),
                $req['first_name'] ? $req['first_name'] . ' ' . $req['last_name'] : 'System'
            ),
            'extendedProps' => [
                'type' => 'maintenance',
                'request_id' => $req['request_id'],
                'status' => $req['status'],
                'priority' => $req['priority']
            ]
        ];
    }

    // 5. Staff Schedules (Gray)
    $schedules = $db->query("
        SELECT ss.*, u.first_name, u.last_name, u.role as user_role
        FROM staff_schedules ss
        JOIN users u ON ss.user_id = u.user_id
        WHERE ss.work_date BETWEEN '$start' AND '$end'
        ORDER BY ss.work_date, ss.shift_start
    ")->fetchAll();

    foreach ($schedules as $schedule) {
        $startTime = $schedule['work_date'];
        if ($schedule['shift_start']) {
            $startTime .= 'T' . $schedule['shift_start'];
        }

        $endTime = $schedule['work_date'];
        if ($schedule['shift_end']) {
            $endTime .= 'T' . $schedule['shift_end'];
        }

        $events[] = [
            'id' => 'schedule_' . $schedule['schedule_id'],
            'title' => $schedule['first_name'] . ' ' . $schedule['last_name'] . ' - ' . ($schedule['role'] ?? $schedule['user_role']),
            'start' => $startTime,
            'end' => $endTime,
            'allDay' => empty($schedule['shift_start']),
            'color' => '#6b7280', // Gray
            'textColor' => '#ffffff',
            'category' => 'schedule',
            'description' => sprintf(
                "Staff Schedule #%d\nStaff: %s %s\nRole: %s\nDate: %s\nShift: %s - %s\nStatus: %s\nNotes: %s",
                $schedule['schedule_id'],
                $schedule['first_name'],
                $schedule['last_name'],
                $schedule['role'] ?? $schedule['user_role'],
                date('M d, Y', strtotime($schedule['work_date'])),
                $schedule['shift_start'] ? date('h:i A', strtotime($schedule['shift_start'])) : 'N/A',
                $schedule['shift_end'] ? date('h:i A', strtotime($schedule['shift_end'])) : 'N/A',
                ucfirst($schedule['status']),
                $schedule['notes'] ?? 'None'
            ),
            'extendedProps' => [
                'type' => 'schedule',
                'schedule_id' => $schedule['schedule_id'],
                'status' => $schedule['status'],
                'staff' => $schedule['first_name'] . ' ' . $schedule['last_name']
            ]
        ];
    }

    return $events;
}

/**
 * Get calendar events for Staff (assigned tasks only)
 */
function getStaffCalendarEvents($db, $userId, $start, $end) {
    $events = [];

    // 1. Food Orders assigned to this staff member (Green)
    // For now, show all food orders as staff may need to handle any order
    $foodOrders = $db->prepare("
        SELECT fo.*, mi.item_name, u.first_name, u.last_name, r.room_number
        FROM food_orders fo
        JOIN menu_items mi ON fo.food_id = mi.item_id
        JOIN users u ON fo.user_id = u.user_id
        LEFT JOIN bookings b ON fo.booking_id = b.booking_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE DATE(fo.created_at) BETWEEN :start AND :end
        AND fo.status IN ('pending', 'preparing', 'ready')
        ORDER BY fo.created_at
    ");
    $foodOrders->execute([':start' => $start, ':end' => $end]);

    foreach ($foodOrders->fetchAll() as $order) {
        $events[] = [
            'id' => 'food_order_' . $order['order_id'],
            'title' => 'Serve Order #' . $order['order_id'] . ' - ' . $order['item_name'] . ' (Room ' . ($order['room_number'] ?? 'N/A') . ')',
            'start' => $order['created_at'],
            'allDay' => false,
            'color' => '#22c55e', // Green
            'textColor' => '#ffffff',
            'category' => 'food_order',
            'description' => sprintf(
                "Food Order #%d\nItem: %s\nQuantity: %d\nRoom: %s\nGuest: %s %s\nStatus: %s\nInstructions: %s",
                $order['order_id'],
                $order['item_name'],
                $order['quantity'],
                $order['room_number'] ?? 'N/A',
                $order['first_name'],
                $order['last_name'],
                ucfirst($order['status']),
                $order['special_instructions'] ?? 'None'
            ),
            'extendedProps' => [
                'type' => 'food_order',
                'order_id' => $order['order_id'],
                'status' => $order['status']
            ]
        ];
    }

    // 2. Event Bookings (Purple)
    $eventBookings = $db->prepare("
        SELECT eb.*, es.space_name, u.first_name, u.last_name
        FROM event_bookings eb
        JOIN event_spaces es ON eb.space_id = es.space_id
        LEFT JOIN users u ON eb.user_id = u.user_id
        WHERE eb.event_date BETWEEN :start AND :end
        AND eb.status IN ('pending', 'confirmed')
        ORDER BY eb.event_date, eb.start_time
    ");
    $eventBookings->execute([':start' => $start, ':end' => $end]);

    foreach ($eventBookings->fetchAll() as $event) {
        $startDateTime = $event['event_date'];
        if ($event['start_time']) {
            $startDateTime .= 'T' . $event['start_time'];
        }

        $events[] = [
            'id' => 'event_' . $event['event_booking_id'],
            'title' => 'Event: ' . $event['event_type'] . ' - ' . $event['space_name'],
            'start' => $startDateTime,
            'allDay' => empty($event['start_time']),
            'color' => '#a855f7', // Purple
            'textColor' => '#ffffff',
            'category' => 'event',
            'description' => sprintf(
                "Event Booking #%d\nType: %s\nVenue: %s\nDate: %s\nTime: %s - %s\nGuests: %d\nCatering: %s",
                $event['event_booking_id'],
                $event['event_type'],
                $event['space_name'],
                date('M d, Y', strtotime($event['event_date'])),
                $event['start_time'] ? date('h:i A', strtotime($event['start_time'])) : 'TBA',
                $event['end_time'] ? date('h:i A', strtotime($event['end_time'])) : 'TBA',
                $event['guests_count'],
                $event['catering_required'] ? 'Yes' : 'No'
            ),
            'extendedProps' => [
                'type' => 'event',
                'event_booking_id' => $event['event_booking_id'],
                'status' => $event['status']
            ]
        ];
    }

    // 3. Maintenance Tasks (Orange)
    $maintenance = $db->prepare("
        SELECT mr.*, r.room_number
        FROM maintenance_requests mr
        LEFT JOIN rooms r ON mr.room_id = r.room_id
        WHERE DATE(mr.created_at) BETWEEN :start AND :end
        AND mr.status IN ('pending', 'in_progress')
        ORDER BY FIELD(mr.priority, 'urgent', 'high', 'medium', 'low'), mr.created_at
    ");
    $maintenance->execute([':start' => $start, ':end' => $end]);

    foreach ($maintenance->fetchAll() as $req) {
        $events[] = [
            'id' => 'maintenance_' . $req['request_id'],
            'title' => 'Maintenance: ' . ($req['room_number'] ? 'Room ' . $req['room_number'] : 'General') . ' - ' . ucfirst($req['issue_type']),
            'start' => $req['created_at'],
            'allDay' => true,
            'color' => '#f97316', // Orange
            'textColor' => '#ffffff',
            'category' => 'maintenance',
            'description' => sprintf(
                "Maintenance Task #%d\nType: %s\nRoom: %s\nPriority: %s\nDescription: %s",
                $req['request_id'],
                ucfirst($req['issue_type']),
                $req['room_number'] ?? 'N/A',
                ucfirst($req['priority']),
                $req['description']
            ),
            'extendedProps' => [
                'type' => 'maintenance',
                'request_id' => $req['request_id'],
                'priority' => $req['priority'],
                'status' => $req['status']
            ]
        ];
    }

    // 4. Today's Check-ins and Check-outs (Blue)
    $bookings = $db->prepare("
        SELECT b.*, u.first_name, u.last_name, rc.category_name, r.room_number
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE (b.check_in BETWEEN :start AND :end OR b.check_out BETWEEN :start AND :end)
        AND b.status IN ('confirmed', 'checked_in')
        ORDER BY b.check_in
    ");
    $bookings->execute([':start' => $start, ':end' => $end]);

    foreach ($bookings->fetchAll() as $booking) {
        // Check-in
        $events[] = [
            'id' => 'booking_checkin_' . $booking['booking_id'],
            'title' => 'Check-in: Room ' . ($booking['room_number'] ?? 'TBA') . ' - ' . $booking['first_name'] . ' ' . $booking['last_name'],
            'start' => $booking['check_in'],
            'allDay' => true,
            'color' => '#3b82f6', // Blue
            'textColor' => '#ffffff',
            'category' => 'booking',
            'description' => sprintf(
                "Check-in: Room %s\nGuest: %s %s\nCategory: %s\nNights: %d",
                $booking['room_number'] ?? 'TBA',
                $booking['first_name'],
                $booking['last_name'],
                $booking['category_name'],
                $booking['nights']
            ),
            'extendedProps' => [
                'type' => 'checkin',
                'booking_id' => $booking['booking_id']
            ]
        ];

        // Check-out
        $events[] = [
            'id' => 'booking_checkout_' . $booking['booking_id'],
            'title' => 'Check-out: Room ' . ($booking['room_number'] ?? 'TBA') . ' - ' . $booking['first_name'] . ' ' . $booking['last_name'],
            'start' => $booking['check_out'],
            'allDay' => true,
            'color' => '#60a5fa', // Light Blue
            'textColor' => '#ffffff',
            'category' => 'booking',
            'description' => sprintf(
                "Check-out: Room %s\nGuest: %s %s\nCategory: %s",
                $booking['room_number'] ?? 'TBA',
                $booking['first_name'],
                $booking['last_name'],
                $booking['category_name']
            ),
            'extendedProps' => [
                'type' => 'checkout',
                'booking_id' => $booking['booking_id']
            ]
        ];
    }

    return $events;
}

/**
 * Get calendar events for User (their own bookings, orders, and events)
 */
function getUserCalendarEvents($db, $userId, $start, $end) {
    $events = [];

    // 1. User's Room Bookings (Blue)
    $bookings = $db->prepare("
        SELECT b.*, rc.category_name, r.room_number
        FROM bookings b
        JOIN room_categories rc ON b.category_id = rc.category_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE b.user_id = :user_id
        AND (b.check_in BETWEEN :start AND :end 
           OR b.check_out BETWEEN :start AND :end
           OR (b.check_in <= :start AND b.check_out >= :end))
        AND b.status IN ('pending', 'confirmed', 'checked_in', 'checked_out')
        ORDER BY b.check_in
    ");
    $bookings->execute([':user_id' => $userId, ':start' => $start, ':end' => $end]);

    foreach ($bookings->fetchAll() as $booking) {
        // Check-in
        $events[] = [
            'id' => 'booking_checkin_' . $booking['booking_id'],
            'title' => 'Check-in: ' . $booking['category_name'] . ' (Room ' . ($booking['room_number'] ?? 'TBA') . ')',
            'start' => $booking['check_in'],
            'end' => $booking['check_in'],
            'allDay' => true,
            'color' => '#3b82f6', // Blue
            'textColor' => '#ffffff',
            'category' => 'booking',
            'description' => sprintf(
                "Your Booking #%d\nRoom Type: %s\nRoom: %s\nCheck-in: %s\nCheck-out: %s\nNights: %d\nTotal: ₱%.2f\nStatus: %s",
                $booking['booking_id'],
                $booking['category_name'],
                $booking['room_number'] ?? 'To be assigned',
                date('M d, Y', strtotime($booking['check_in'])),
                date('M d, Y', strtotime($booking['check_out'])),
                $booking['nights'],
                $booking['total_amount'],
                ucfirst($booking['status'])
            ),
            'extendedProps' => [
                'type' => 'booking_checkin',
                'booking_id' => $booking['booking_id'],
                'status' => $booking['status'],
                'room' => $booking['room_number']
            ]
        ];

        // Check-out
        $events[] = [
            'id' => 'booking_checkout_' . $booking['booking_id'],
            'title' => 'Check-out: ' . $booking['category_name'] . ' (Room ' . ($booking['room_number'] ?? 'TBA') . ')',
            'start' => $booking['check_out'],
            'end' => $booking['check_out'],
            'allDay' => true,
            'color' => '#60a5fa', // Light Blue
            'textColor' => '#ffffff',
            'category' => 'booking',
            'description' => sprintf(
                "Your Booking #%d\nRoom Type: %s\nRoom: %s\nCheck-out Date: %s",
                $booking['booking_id'],
                $booking['category_name'],
                $booking['room_number'] ?? 'N/A',
                date('M d, Y', strtotime($booking['check_out']))
            ),
            'extendedProps' => [
                'type' => 'booking_checkout',
                'booking_id' => $booking['booking_id'],
                'status' => $booking['status']
            ]
        ];

        // Full stay duration
        $events[] = [
            'id' => 'booking_stay_' . $booking['booking_id'],
            'title' => 'Your Stay: ' . $booking['category_name'],
            'start' => $booking['check_in'],
            'end' => date('Y-m-d', strtotime($booking['check_out'] . ' +1 day')),
            'allDay' => true,
            'color' => '#93c5fd', // Very Light Blue
            'textColor' => '#1e40af',
            'display' => 'background',
            'category' => 'booking',
            'description' => sprintf(
                "Your Stay at %s\nRoom: %s\nDuration: %d nights\nCheck-in: %s\nCheck-out: %s",
                $booking['category_name'],
                $booking['room_number'] ?? 'To be assigned',
                $booking['nights'],
                date('M d, Y', strtotime($booking['check_in'])),
                date('M d, Y', strtotime($booking['check_out']))
            ),
            'extendedProps' => [
                'type' => 'booking_stay',
                'booking_id' => $booking['booking_id']
            ]
        ];
    }

    // 2. User's Food Orders (Green)
    $foodOrders = $db->prepare("
        SELECT fo.*, mi.item_name, r.room_number
        FROM food_orders fo
        JOIN menu_items mi ON fo.food_id = mi.item_id
        LEFT JOIN bookings b ON fo.booking_id = b.booking_id
        LEFT JOIN rooms r ON b.room_id = r.room_id
        WHERE fo.user_id = :user_id
        AND DATE(fo.created_at) BETWEEN :start AND :end
        ORDER BY fo.created_at
    ");
    $foodOrders->execute([':user_id' => $userId, ':start' => $start, ':end' => $end]);

    foreach ($foodOrders->fetchAll() as $order) {
        $deliveryTime = $order['delivered_at'] ?? $order['created_at'];
        $events[] = [
            'id' => 'food_order_' . $order['order_id'],
            'title' => 'Food Order: ' . $order['item_name'],
            'start' => $deliveryTime,
            'allDay' => false,
            'color' => '#22c55e', // Green
            'textColor' => '#ffffff',
            'category' => 'food_order',
            'description' => sprintf(
                "Your Food Order #%d\nItem: %s\nQuantity: %d\nRoom: %s\nStatus: %s\nTotal: ₱%.2f\nSpecial Instructions: %s",
                $order['order_id'],
                $order['item_name'],
                $order['quantity'],
                $order['room_number'] ?? 'N/A',
                ucfirst($order['status']),
                $order['total_price'],
                $order['special_instructions'] ?? 'None'
            ),
            'extendedProps' => [
                'type' => 'food_order',
                'order_id' => $order['order_id'],
                'status' => $order['status']
            ]
        ];
    }

    // 3. User's Event Bookings (Purple)
    $eventBookings = $db->prepare("
        SELECT eb.*, es.space_name
        FROM event_bookings eb
        JOIN event_spaces es ON eb.space_id = es.space_id
        WHERE eb.user_id = :user_id
        AND eb.event_date BETWEEN :start AND :end
        AND eb.status IN ('pending', 'confirmed', 'completed')
        ORDER BY eb.event_date
    ");
    $eventBookings->execute([':user_id' => $userId, ':start' => $start, ':end' => $end]);

    foreach ($eventBookings->fetchAll() as $event) {
        $startDateTime = $event['event_date'];
        if ($event['start_time']) {
            $startDateTime .= 'T' . $event['start_time'];
        }

        $endDateTime = $event['event_date'];
        if ($event['end_time']) {
            $endDateTime .= 'T' . $event['end_time'];
        }

        $events[] = [
            'id' => 'event_' . $event['event_booking_id'],
            'title' => 'Your Event: ' . $event['event_type'],
            'start' => $startDateTime,
            'end' => $endDateTime,
            'allDay' => empty($event['start_time']),
            'color' => '#a855f7', // Purple
            'textColor' => '#ffffff',
            'category' => 'event',
            'description' => sprintf(
                "Your Event Booking #%d\nType: %s\nVenue: %s\nDate: %s\nTime: %s - %s\nGuests: %d\nCatering: %s\nStatus: %s",
                $event['event_booking_id'],
                $event['event_type'],
                $event['space_name'],
                date('M d, Y', strtotime($event['event_date'])),
                $event['start_time'] ? date('h:i A', strtotime($event['start_time'])) : 'TBA',
                $event['end_time'] ? date('h:i A', strtotime($event['end_time'])) : 'TBA',
                $event['guests_count'],
                $event['catering_required'] ? 'Yes' : 'No',
                ucfirst($event['status'])
            ),
            'extendedProps' => [
                'type' => 'event',
                'event_booking_id' => $event['event_booking_id'],
                'status' => $event['status'],
                'venue' => $event['space_name']
            ]
        ];
    }

    return $events;
}
