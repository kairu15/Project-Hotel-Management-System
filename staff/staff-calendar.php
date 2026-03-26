<?php
/**
 * Staff Calendar Page - Bayawan Bai Hotel
 * Displays assigned tasks: Food Orders, Events, Maintenance, Check-ins/outs
 */
$pageTitle = 'My Calendar';
require_once __DIR__ . '/../includes/config.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

require_once __DIR__ . '/../includes/staff-header.php';

$db = getDB();
$userId = getUserId();
$userRole = getUserRole();

// Get today's stats for staff
$today = date('Y-m-d');
$stats = [
    'today_checkins' => $db->query("SELECT COUNT(*) FROM bookings WHERE check_in = '$today' AND status IN ('confirmed', 'checked_in')")->fetchColumn(),
    'today_checkouts' => $db->query("SELECT COUNT(*) FROM bookings WHERE check_out = '$today' AND status IN ('confirmed', 'checked_in')")->fetchColumn(),
    'pending_food_orders' => $db->query("SELECT COUNT(*) FROM food_orders WHERE status IN ('pending', 'preparing', 'ready')")->fetchColumn(),
    'pending_maintenance' => $db->query("SELECT COUNT(*) FROM maintenance_requests WHERE status IN ('pending', 'in_progress')")->fetchColumn()
];
?>

<style>
    /* Calendar Container */
    .calendar-wrapper {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        padding: 20px;
        margin-top: 20px;
    }

    /* FullCalendar Customizations */
    .fc {
        font-family: 'Lato', sans-serif;
    }

    .fc-toolbar-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        color: var(--dark-color);
    }

    .fc-button {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
        color: white !important;
        font-weight: 500;
        padding: 8px 16px !important;
        border-radius: 6px !important;
        transition: all 0.3s ease;
    }

    .fc-button:hover {
        background-color: var(--secondary-color) !important;
        border-color: var(--secondary-color) !important;
    }

    .fc-button-active {
        background-color: var(--dark-color) !important;
        border-color: var(--dark-color) !important;
    }

    .fc-event {
        border-radius: 6px;
        font-size: 0.85rem;
        padding: 2px 6px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        border: none !important;
    }

    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000 !important;
    }

    .fc-daygrid-event {
        white-space: normal;
        line-height: 1.4;
    }

    .fc-day-today {
        background-color: rgba(54, 125, 138, 0.08) !important;
    }

    .fc-daygrid-day-number {
        font-weight: 600;
        color: var(--dark-color);
    }

    .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        background-color: var(--primary-color);
        color: white;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-content h4 {
        font-size: 0.85rem;
        color: #666;
        margin: 0 0 5px 0;
        font-family: 'Lato', sans-serif;
    }

    .stat-content p {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
        color: var(--dark-color);
    }

    /* Legend */
    .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }

    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 2000;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        max-width: 500px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        padding: 20px 25px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--dark-color);
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
        transition: color 0.2s;
    }

    .modal-close:hover {
        color: var(--danger-color);
    }

    .modal-body {
        padding: 25px;
    }

    .modal-footer {
        padding: 15px 25px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .event-details {
        line-height: 1.8;
    }

    .event-details-row {
        display: flex;
        margin-bottom: 12px;
        align-items: flex-start;
    }

    .event-details-label {
        font-weight: 600;
        color: #666;
        min-width: 120px;
    }

    .event-details-value {
        color: var(--text-color);
        flex: 1;
    }

    .event-category-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Tooltip */
    .calendar-tooltip {
        position: absolute;
        background: var(--dark-color);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.85rem;
        z-index: 3000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s;
        max-width: 300px;
        line-height: 1.4;
    }

    .calendar-tooltip.visible {
        opacity: 1;
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .quick-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

    .quick-action-btn.primary {
        background-color: var(--primary-color);
        color: white;
    }

    .quick-action-btn.primary:hover {
        background-color: var(--secondary-color);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .calendar-wrapper {
            padding: 10px;
        }

        .fc-toolbar {
            flex-direction: column;
            gap: 10px;
        }

        .fc-toolbar-title {
            font-size: 1.2rem;
        }

        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }

        .calendar-legend {
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Quick Actions -->
<div class="quick-actions">
    <a href="staff-foods-orders.php" class="quick-action-btn primary">
        <i class="fas fa-utensils"></i> View Food Orders
    </a>
    <a href="staff-maintenance.php" class="quick-action-btn primary" style="background: #f97316;">
        <i class="fas fa-tools"></i> View Maintenance
    </a>
    <a href="staff-event-bookings.php" class="quick-action-btn primary" style="background: #a855f7;">
        <i class="fas fa-calendar-alt"></i> View Events
    </a>
</div>

<!-- Stats Row -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon" style="background: #dbeafe; color: #2563eb;">
            <i class="fas fa-sign-in-alt"></i>
        </div>
        <div class="stat-content">
            <h4>Today's Check-ins</h4>
            <p><?php echo $stats['today_checkins']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #dbeafe; color: #3b82f6;">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <div class="stat-content">
            <h4>Today's Check-outs</h4>
            <p><?php echo $stats['today_checkouts']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">
            <i class="fas fa-utensils"></i>
        </div>
        <div class="stat-content">
            <h4>Pending Food Orders</h4>
            <p><?php echo $stats['pending_food_orders']; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #ffedd5; color: #ea580c;">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <h4>Pending Maintenance</h4>
            <p><?php echo $stats['pending_maintenance']; ?></p>
        </div>
    </div>
</div>

<!-- Color Legend -->
<div class="calendar-legend">
    <div class="legend-item">
        <div class="legend-color" style="background: #3b82f6;"></div>
        <span>Check-ins/Check-outs</span>
    </div>
    <div class="legend-item">
        <div class="legend-color" style="background: #22c55e;"></div>
        <span>Food Orders (Assigned)</span>
    </div>
    <div class="legend-item">
        <div class="legend-color" style="background: #a855f7;"></div>
        <span>Events (Assigned)</span>
    </div>
    <div class="legend-item">
        <div class="legend-color" style="background: #f97316;"></div>
        <span>Maintenance Tasks</span>
    </div>
</div>

<!-- Calendar Container -->
<div class="calendar-wrapper">
    <div id="calendar"></div>
</div>

<!-- Event Details Modal -->
<div class="modal-overlay" id="eventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Task Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="event-details" id="modalBody">
                <!-- Content will be dynamically inserted -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
            <button class="btn btn-primary" id="modalActionBtn" style="display: none;">View Full Details</button>
        </div>
    </div>
</div>

<!-- Tooltip -->
<div class="calendar-tooltip" id="calendarTooltip"></div>

<!-- FullCalendar CSS & JS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const tooltip = document.getElementById('calendarTooltip');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        views: {
            dayGridMonth: {
                titleFormat: { year: 'numeric', month: 'long' }
            },
            timeGridWeek: {
                titleFormat: { year: 'numeric', month: 'short', day: 'numeric' }
            },
            timeGridDay: {
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' }
            },
            listWeek: {
                titleFormat: { year: 'numeric', month: 'long' }
            }
        },
        height: 'auto',
        contentHeight: 'auto',
        events: {
            url: '../api/calendar-events.php',
            method: 'GET',
            failure: function() {
                alert('There was an error while fetching calendar events!');
            }
        },
        loading: function(isLoading) {
            // Show/hide loading indicator if needed
        },
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventMouseEnter: function(info) {
            showTooltip(info.event, info.el);
        },
        eventMouseLeave: function(info) {
            hideTooltip();
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        },
        dayMaxEvents: true,
        navLinks: true,
        editable: false,
        selectable: true,
        selectMirror: true,
        nowIndicator: true
    });

    calendar.render();

    // Tooltip functions
    function showTooltip(event, element) {
        const description = event.extendedProps.description || event.title;
        tooltip.innerHTML = description.replace(/\n/g, '<br>');
        tooltip.classList.add('visible');

        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();

        let top = rect.top - tooltipRect.height - 10;
        let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

        if (top < 10) top = rect.bottom + 10;
        if (left < 10) left = 10;
        if (left + tooltipRect.width > window.innerWidth - 10) {
            left = window.innerWidth - tooltipRect.width - 10;
        }

        tooltip.style.top = top + 'px';
        tooltip.style.left = left + 'px';
    }

    function hideTooltip() {
        tooltip.classList.remove('visible');
    }

    // Modal functions
    window.showEventDetails = function(event) {
        const modal = document.getElementById('eventModal');
        const titleEl = document.getElementById('modalTitle');
        const bodyEl = document.getElementById('modalBody');
        const actionBtn = document.getElementById('modalActionBtn');

        const props = event.extendedProps;
        const type = props.type;
        let categoryColor = event.backgroundColor;
        let categoryName = '';

        switch(type) {
            case 'checkin':
                categoryName = 'Check-in';
                categoryColor = '#3b82f6';
                break;
            case 'checkout':
                categoryName = 'Check-out';
                categoryColor = '#60a5fa';
                break;
            case 'food_order':
                categoryName = 'Food Order';
                categoryColor = '#22c55e';
                break;
            case 'event':
                categoryName = 'Event';
                categoryColor = '#a855f7';
                break;
            case 'maintenance':
                categoryName = 'Maintenance Task';
                categoryColor = '#f97316';
                break;
            default:
                categoryName = 'Task';
                categoryColor = '#6b7280';
        }

        titleEl.textContent = event.title;

        let detailsHTML = `
            <div class="event-details-row">
                <span class="event-details-label">Type:</span>
                <span class="event-details-value">
                    <span class="event-category-badge" style="background: ${categoryColor}20; color: ${categoryColor};">
                        ${categoryName}
                    </span>
                </span>
            </div>
        `;

        if (props.description) {
            const lines = props.description.split('\n');
            lines.forEach(line => {
                if (line.includes(':')) {
                    const [label, ...valueParts] = line.split(':');
                    const value = valueParts.join(':').trim();
                    detailsHTML += `
                        <div class="event-details-row">
                            <span class="event-details-label">${label.trim()}:</span>
                            <span class="event-details-value">${value}</span>
                        </div>
                    `;
                }
            });
        }

        bodyEl.innerHTML = detailsHTML;

        // Set up action button based on event type
        let actionUrl = '';
        if (type === 'checkin' || type === 'checkout') {
            actionUrl = `checkin.php?date=${event.start.toISOString().split('T')[0]}`;
        } else if (type === 'food_order') {
            actionUrl = `staff-foods-orders.php`;
        } else if (type === 'event') {
            actionUrl = `staff-event-bookings.php`;
        } else if (type === 'maintenance') {
            actionUrl = `staff-maintenance.php`;
        }

        if (actionUrl) {
            actionBtn.style.display = 'inline-block';
            actionBtn.onclick = function() {
                window.location.href = actionUrl;
            };
        } else {
            actionBtn.style.display = 'none';
        }

        modal.classList.add('active');
    };

    window.closeModal = function() {
        document.getElementById('eventModal').classList.remove('active');
    };

    // Close modal when clicking outside
    document.getElementById('eventModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/staff-footer.php'; ?>
