<?php
/**
 * Admin Header Include File - Bayawan Bai Hotel
 * Sidebar layout for admin dashboard
 */
require_once __DIR__ . '/../includes/config.php';

// Check if user is admin or has staff permission for this page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Map staff page filenames to their permission keys
$pageToPermissionMap = [
    'staff-inventory' => 'inventory',
    'staff-maintenance' => 'maintenance',
    'staff-booking-charges' => 'booking_charges'
];

// Get the permission key for the current page (or use current page name if not mapped)
$permissionKey = isset($pageToPermissionMap[$currentPage]) ? $pageToPermissionMap[$currentPage] : $currentPage;

// Admin always has access, staff needs specific permission
if (!isAdmin()) {
    // Check if user is staff (manager or receptionist)
    $userRole = getUserRole();
    if (!in_array($userRole, ['manager', 'receptionist'])) {
        showAlert('Access denied. Admin privileges required.', 'danger');
        redirect('../index.php');
    }
    
    // Staff users need explicit permission for each admin page
    $userId = getUserId();
    if (!hasStaffPermission($userId, $permissionKey)) {
        error_log("Permission denied for UserID: $userId on Page: $permissionKey");
        showAlert('Access denied. You do not have permission to access this page.', 'danger');
        redirect('../staff/staff-dashboard.php');
    }
    
    error_log("Permission granted for UserID: $userId on Page: $permissionKey");
}

// Get alert if any
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/bayawanhotellogo.png">
    <link rel="shortcut icon" type="image/png" href="../assets/bayawanhotellogo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Lato:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #367D8A;
            --secondary-color: #285F6B;
            --dark-color: #133336;
            --light-color: #FFFFFF;
            --text-color: #010001;
            --gray-light: #F5F5F5;
            --gray-medium: #E0E0E0;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --sidebar-width: 260px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            background-color: var(--gray-light);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        /* Layout */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-color);
            color: var(--light-color);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
            color: var(--light-color);
        }
        
        .sidebar-header .logo-image {
            width: 45px;
            height: 45px;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .sidebar-header .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
        }
        
        .sidebar-header .logo-text span {
            color: var(--primary-color);
        }
        
        .sidebar-header .admin-label {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 8px;
        }
        
        /* User Info in Sidebar */
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-user .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 18px;
        }
        
        .sidebar-user .user-info h4 {
            color: var(--light-color);
            font-size: 14px;
            margin: 0 0 3px 0;
        }
        
        .sidebar-user .user-info p {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 0;
        }
        
        /* Navigation */
        .sidebar-nav {
            flex: 1;
            padding: 15px 0;
        }
        
        .sidebar-nav ul {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin: 2px 0;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-nav a:hover {
            background-color: rgba(255,255,255,0.05);
            color: var(--light-color);
        }
        
        .sidebar-nav a.active {
            background-color: var(--primary-color);
            color: var(--light-color);
            border-left-color: var(--light-color);
        }
        
        .sidebar-nav a i {
            width: 24px;
            text-align: center;
            font-size: 16px;
        }
        
        .sidebar-nav .nav-section {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 20px 20px 10px;
            margin-top: 10px;
        }
        
        /* Sidebar Footer */
        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .sidebar-footer a:hover {
            color: var(--light-color);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Top Header */
        .top-header {
            background-color: var(--light-color);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .top-header h1 {
            font-size: 24px;
            margin: 0;
        }
        
        .top-header .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .top-header .header-actions a {
            color: var(--text-color);
            font-size: 18px;
            text-decoration: none;
            position: relative;
        }
        
        .top-header .header-actions .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        /* Notification Bell Styles */
        .notification-bell {
            position: relative;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background-color 0.3s;
        }
        
        .notification-bell:hover {
            background-color: var(--gray-light);
        }
        
        .notification-bell i {
            font-size: 20px;
            color: var(--text-color);
        }
        
        .notification-bell .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            padding: 2px 5px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            font-weight: 600;
        }
        
        /* Floating Notification Panel */
        .notification-panel {
            position: fixed;
            top: 70px;
            right: 30px;
            width: 380px;
            max-height: 500px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 1001;
            display: none;
            flex-direction: column;
            overflow: hidden;
        }
        
        .notification-panel.active {
            display: flex;
        }
        
        .notification-panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--primary-color);
            color: white;
        }
        
        .notification-panel-header h3 {
            margin: 0;
            font-size: 16px;
            color: white;
            font-family: 'Lato', sans-serif;
        }
        
        .notification-panel-header .actions {
            display: flex;
            gap: 10px;
        }
        
        .notification-panel-header .actions a {
            color: white;
            font-size: 12px;
            text-decoration: none;
            padding: 4px 10px;
            border-radius: 4px;
            background: rgba(255,255,255,0.2);
            transition: background 0.3s;
        }
        
        .notification-panel-header .actions a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .notification-list {
            flex: 1;
            overflow-y: auto;
            max-height: 380px;
        }
        
        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            gap: 12px;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }
        
        .notification-item:hover {
            background-color: var(--gray-light);
        }
        
        .notification-item.unread {
            background-color: #f0f9ff;
        }
        
        .notification-item.unread:hover {
            background-color: #e0f2fe;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .notification-icon i {
            font-size: 16px;
            color: white;
        }
        
        .notification-content {
            flex: 1;
            min-width: 0;
        }
        
        .notification-content h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-family: 'Lato', sans-serif;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .notification-content p {
            margin: 0;
            font-size: 13px;
            color: #666;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }
        
        .notification-time {
            font-size: 11px;
            color: #999;
        }
        
        .notification-status {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }
        
        .notification-status.read {
            background-color: transparent;
        }
        
        .notification-empty {
            padding: 40px 20px;
            text-align: center;
            color: #999;
        }
        
        .notification-empty i {
            font-size: 48px;
            margin-bottom: 15px;
            color: var(--gray-medium);
        }
        
        .notification-empty p {
            margin: 0;
            font-size: 14px;
        }
        
        .notification-loading {
            padding: 20px;
            text-align: center;
            color: #999;
        }
        
        .notification-panel-footer {
            padding: 12px 20px;
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--gray-light);
        }
        
        .notification-panel-footer a {
            font-size: 12px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .notification-panel-footer a:hover {
            text-decoration: underline;
        }
        
        .mark-all-read {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 12px;
            cursor: pointer;
            font-weight: 500;
            padding: 0;
        }
        
        .mark-all-read:hover {
            text-decoration: underline;
        }
        
        /* Notification Overlay */
        .notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 1000;
            display: none;
        }
        
        .notification-overlay.active {
            display: block;
        }
        
        @media (max-width: 480px) {
            .notification-panel {
                width: calc(100% - 40px);
                right: 20px;
                left: 20px;
            }
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--light-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-outline {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline:hover {
            background-color: var(--primary-color);
            color: var(--light-color);
        }
        
        .btn-secondary {
            background-color: var(--gray-medium);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background-color: #d0d0d0;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: var(--light-color);
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-sm {
            padding: 8px 20px;
            font-size: 13px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin: 20px 30px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }
        
        /* Page Content */
        .page-content {
            flex: 1;
            padding: 30px;
        }
        
        /* Mobile Toggle */
        .mobile-toggle {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--dark-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .overlay.active {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .top-header {
                padding: 12px 20px;
            }
            
            .top-header h1 {
                font-size: 18px;
            }
            
            .page-content {
                padding: 20px;
            }
            
            .alert {
                margin: 15px 20px 0;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Overlay for mobile -->
        <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="admin-dashboard.php" class="logo">
                    <img src="../assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo-image">
                    <span class="logo-text">Bayawan <span>Bai</span></span>
                </a>
                <div class="admin-label">Administration</div>
            </div>
            
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                    <p><?php echo ucfirst(getUserRole()); ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="admin-dashboard.php" class="<?php echo $currentPage === 'admin-dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                    <li><a href="admin-bookings.php" class="<?php echo $currentPage === 'admin-bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a></li>
                    <li><a href="admin-payments.php" class="<?php echo $currentPage === 'admin-payments' ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-wave"></i> Payments
                    </a></li>
                    
                    <li class="nav-section">Rooms & Amenities</li>
                    <li><a href="admin-rooms.php" class="<?php echo $currentPage === 'admin-rooms' ? 'active' : ''; ?>">
                        <i class="fas fa-bed"></i> Rooms
                    </a></li>
                    <li><a href="admin-room-categories.php" class="<?php echo $currentPage === 'admin-room-categories' ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Room Categories
                    </a></li>
                    <li><a href="admin-amenities.php" class="<?php echo $currentPage === 'admin-amenities' ? 'active' : ''; ?>">
                        <i class="fas fa-spa"></i> Amenities
                    </a></li>
                    <li><a href="admin-maintenance.php" class="<?php echo $currentPage === 'admin-maintenance' ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i> Maintenance
                    </a></li>
                    
                    <li class="nav-section">Events & Dining</li>
                    <li><a href="admin-event-spaces.php" class="<?php echo $currentPage === 'admin-event-spaces' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i> Event Spaces
                    </a></li>
                    <li><a href="admin-event-bookings.php" class="<?php echo $currentPage === 'admin-event-bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Event Bookings
                    </a></li>
                    <li><a href="admin-menu-categories.php" class="<?php echo $currentPage === 'admin-menu-categories' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> Menu Categories
                    </a></li>
                    <li><a href="admin-menu-items.php" class="<?php echo $currentPage === 'admin-menu-items' ? 'active' : ''; ?>">
                        <i class="fas fa-hamburger"></i> Menu Items
                    </a></li>
                    <li><a href="admin-foods-inventory.php" class="<?php echo $currentPage === 'admin-foods-inventory' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Foods Inventory
                    </a></li>
                    
                    <li class="nav-section">Content & Marketing</li>
                    <li><a href="admin-faqs.php" class="<?php echo $currentPage === 'admin-faqs' ? 'active' : ''; ?>">
                        <i class="fas fa-question-circle"></i> FAQs
                    </a></li>
                    <li><a href="admin-gallery.php" class="<?php echo $currentPage === 'admin-gallery' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Gallery
                    </a></li>
                    <li><a href="admin-homepage-slider.php" class="<?php echo $currentPage === 'admin-homepage-slider' ? 'active' : ''; ?>">
                        <i class="fas fa-sliders-h"></i> Homepage Slider
                    </a></li>
                    <li><a href="admin-promotions.php" class="<?php echo $currentPage === 'admin-promotions' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Promotions
                    </a></li>
                    <li><a href="admin-reviews.php" class="<?php echo $currentPage === 'admin-reviews' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                    </a></li>
                    
                    <li class="nav-section">Operations</li>
                    <li><a href="admin-analytics.php" class="<?php echo $currentPage === 'admin-analytics' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a></li>
                    <li><a href="admin-users.php" class="<?php echo $currentPage === 'admin-users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users
                    </a></li>
                    <li><a href="admin-staff-permissions.php" class="<?php echo $currentPage === 'admin-staff-permissions' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Staff Permissions
                    </a></li>
                    <li><a href="admin-inventory-categories.php" class="<?php echo $currentPage === 'admin-inventory-categories' ? 'active' : ''; ?>">
                        <i class="fas fa-folder"></i> Inventory Categories
                    </a></li>
                    <li><a href="admin-inventory-items.php" class="<?php echo $currentPage === 'admin-inventory-items' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Inventory Items
                    </a></li>
                    <li><a href="admin-staff-schedules.php" class="<?php echo $currentPage === 'admin-staff-schedules' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Staff Schedules
                    </a></li>
                    <li><a href="admin-reports.php" class="<?php echo $currentPage === 'admin-reports' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a></li>
                    <li><a href="admin-ratings.php" class="<?php echo $currentPage === 'admin-ratings' ? 'active' : ''; ?>">
                        <i class="fas fa-star-half-alt"></i> Ratings
                    </a></li>
                    
                    <li class="nav-section">System</li>
                    <li><a href="admin-settings.php" class="<?php echo $currentPage === 'admin-settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a></li>
                    <li><a href="admin-user-sessions.php" class="<?php echo $currentPage === 'admin-user-sessions' ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i> User Sessions
                    </a></li>
                    <li><a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Website
                    </a></li>
                    <li><a href="../auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="mobile-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </div>
                    <h1><?php echo isset($pageTitle) ? $pageTitle : 'Admin Dashboard'; ?></h1>
                </div>
                <div class="header-actions">
                    <div class="notification-bell" onclick="toggleNotifications()" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge" id="notification-badge" style="display: none;">0</span>
                    </div>
                    <a href="admin-dashboard.php" title="Dashboard"><i class="fas fa-home"></i></a>
                    <a href="../auth/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </header>
            
            <!-- Alert Messages -->
            <?php if ($alert): ?>
            <div class="alert alert-<?php echo $alert['type']; ?>">
                <i class="fas fa-<?php echo $alert['type'] == 'success' ? 'check-circle' : ($alert['type'] == 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo $alert['message']; ?>
            </div>
            <?php endif; ?>
            
            <!-- Notification Overlay -->
            <div class="notification-overlay" id="notification-overlay" onclick="closeNotifications()"></div>
            
            <!-- Floating Notification Panel -->
            <div class="notification-panel" id="notification-panel">
                <div class="notification-panel-header">
                    <h3><i class="fas fa-bell" style="margin-right: 8px;"></i>Notifications</h3>
                    <div class="actions">
                        <a href="admin-notifications.php">View All</a>
                        <a href="notification-settings.php">Settings</a>
                    </div>
                </div>
                <div class="notification-list" id="notification-list">
                    <div class="notification-loading">
                        <i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Loading...
                    </div>
                </div>
                <div class="notification-panel-footer">
                    <button class="mark-all-read" onclick="markAllAsRead()">Mark all as read</button>
                    <span id="notification-count">0 unread</span>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="page-content">
                
            <!-- Notification System JavaScript -->
            <script>
            // Notification System
            let notificationRefreshInterval;
            
            // Load notifications on page load
            document.addEventListener('DOMContentLoaded', function() {
                fetchNotifications();
                // Auto-refresh every 30 seconds
                notificationRefreshInterval = setInterval(fetchNotifications, 30000);
            });
            
            function toggleNotifications() {
                const panel = document.getElementById('notification-panel');
                const overlay = document.getElementById('notification-overlay');
                
                if (panel.classList.contains('active')) {
                    closeNotifications();
                } else {
                    panel.classList.add('active');
                    overlay.classList.add('active');
                    fetchNotifications();
                }
            }
            
            function closeNotifications() {
                const panel = document.getElementById('notification-panel');
                const overlay = document.getElementById('notification-overlay');
                panel.classList.remove('active');
                overlay.classList.remove('active');
            }
            
            function fetchNotifications() {
                fetch('../api/ajax-notifications.php?action=get_notifications&limit=10')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateNotificationUI(data.notifications, data.unread_count);
                        }
                    })
                    .catch(error => console.error('Error fetching notifications:', error));
            }
            
            function updateNotificationUI(notifications, unreadCount) {
                const list = document.getElementById('notification-list');
                const badge = document.getElementById('notification-badge');
                const countLabel = document.getElementById('notification-count');
                
                // Update badge
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badge.style.display = 'block';
                    countLabel.textContent = unreadCount + ' unread';
                } else {
                    badge.style.display = 'none';
                    countLabel.textContent = 'No unread notifications';
                }
                
                // Update list
                if (notifications.length === 0) {
                    list.innerHTML = `
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>No notifications yet</p>
                        </div>
                    `;
                } else {
                    list.innerHTML = notifications.map(n => `
                        <div class="notification-item ${n.status === 'unread' ? 'unread' : ''}" 
                             onclick="handleNotificationClick(${n.notification_id}, '${n.action_url || ''}')"
                             data-id="${n.notification_id}">
                            <div class="notification-icon" style="background-color: ${n.color};">
                                <i class="fas fa-${n.icon}"></i>
                            </div>
                            <div class="notification-content">
                                <h4>${escapeHtml(n.title)}</h4>
                                <p>${escapeHtml(n.message)}</p>
                                <div class="notification-meta">
                                    <span class="notification-time">${n.time_ago}</span>
                                    <span class="notification-status ${n.status === 'read' ? 'read' : ''}"></span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            }
            
            function handleNotificationClick(notificationId, actionUrl) {
                // Mark as read
                fetch('../api/ajax-notifications.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=mark_read&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh notifications
                        fetchNotifications();
                        // Navigate if action URL exists
                        if (actionUrl) {
                            window.location.href = actionUrl;
                        }
                    }
                })
                .catch(error => console.error('Error marking notification as read:', error));
            }
            
            function markAllAsRead() {
                fetch('../api/ajax-notifications.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=mark_all_read'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        fetchNotifications();
                    }
                })
                .catch(error => console.error('Error marking all as read:', error));
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            </script>
