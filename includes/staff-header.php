<?php
/**
 * Staff Header Include File - Bayawan Bai Hotel
 * Sidebar layout for staff dashboard
 */
require_once __DIR__ . '/../includes/config.php';

// Check if user is staff
if (!isStaff()) {
    showAlert('Access denied. Staff privileges required.', 'danger');
    redirect('../index.php');
}

// Get alert if any
$alert = getAlert();

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get user role
$userRole = getUserRole();

// Get user ID for permission checks
$userId = getUserId();

// Check permissions for restricted pages
$canAccessInventory = $userRole === 'admin' || hasStaffPermission($userId, 'inventory');
$canAccessMaintenance = $userRole === 'admin' || hasStaffPermission($userId, 'maintenance');
$canAccessBookingCharges = $userRole === 'admin' || hasStaffPermission($userId, 'booking_charges');
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
        .staff-wrapper {
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
        
        .sidebar-header .staff-label {
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
            text-transform: capitalize;
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
        
        /* Date Display */
        .date-display {
            color: #666;
            font-size: 14px;
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
            
            .date-display {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="staff-wrapper">
        <!-- Overlay for mobile -->
        <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="staff-dashboard.php" class="logo">
                    <img src="../assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo-image">
                    <span class="logo-text">Bayawan <span>Bai</span></span>
                </a>
                <div class="staff-label">Staff Portal</div>
            </div>
            
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                    <p><?php echo ucfirst($userRole); ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="staff-dashboard.php" class="<?php echo $currentPage === 'staff-dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a></li>
                    <li><a href="checkin.php" class="<?php echo $currentPage === 'checkin' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt"></i> Check-In
                    </a></li>
                    <li><a href="checkout.php" class="<?php echo $currentPage === 'checkout' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-out-alt"></i> Check-Out
                    </a></li>
                    <li><a href="confirm-booking.php" class="<?php echo $currentPage === 'confirm-booking' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i> Confirm Bookings
                    </a></li>
                    <li><a href="staff-event-bookings.php" class="<?php echo $currentPage === 'staff-event-bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Event Bookings
                    </a></li>
                    <?php if ($canAccessBookingCharges): ?>
                    <li><a href="staff-booking-charges.php" class="<?php echo $currentPage === 'staff-booking-charges' ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice-dollar"></i> Booking Charges
                    </a></li>
                    <?php endif; ?>
                    <li><a href="staff-foods-orders.php" class="<?php echo $currentPage === 'staff-foods-orders' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> Food Orders
                    </a></li>
                    
                    <li class="nav-section">Operations</li>
                    <?php if ($canAccessInventory): ?>
                    <li><a href="staff-inventory.php" class="<?php echo $currentPage === 'staff-inventory' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Inventory Management
                    </a></li>
                    <?php endif; ?>
                    <?php if ($canAccessMaintenance): ?>
                    <li><a href="staff-maintenance.php" class="<?php echo $currentPage === 'staff-maintenance' ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i> Maintenance Requests
                    </a></li>
                    <?php endif; ?>
                    
                    <li class="nav-section">System</li>
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
                    <h1><?php echo isset($pageTitle) ? $pageTitle : 'Staff Dashboard'; ?></h1>
                </div>
                <div class="header-actions">
                    <span class="date-display"><i class="far fa-calendar-alt"></i> <?php echo date('l, F d, Y'); ?></span>
                    <a href="staff-dashboard.php" title="Dashboard"><i class="fas fa-home"></i></a>
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
            
            <!-- Page Content -->
            <div class="page-content">
