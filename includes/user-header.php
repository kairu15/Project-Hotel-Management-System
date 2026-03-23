<?php
/**
 * User Dashboard Header Include File - Bayawan Bai Hotel
 * Sidebar layout for user dashboard with welcome message
 */
require_once __DIR__ . '/config.php';

// Get alert if any
$alert = getAlert();

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get user info
$db = getDB();
$userId = getUserId();
$userStmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Generate initials
$initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
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
            --sidebar-width: 280px;
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
        .user-wrapper {
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
        
        /* Logo Section */
        .sidebar-header {
            padding: 25px 20px;
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
        
        /* Welcome Message */
        .welcome-section {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: linear-gradient(135deg, rgba(54,125,138,0.3), rgba(40,95,107,0.3));
        }
        
        .welcome-section h3 {
            color: var(--light-color);
            font-size: 18px;
            margin: 0;
            font-family: 'Lato', sans-serif;
            font-weight: 600;
        }
        
        .welcome-section p {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            margin: 5px 0 0 0;
        }
        
        /* User Profile Card in Sidebar */
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .sidebar-user .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: var(--light-color);
        }
        
        .sidebar-user .user-info h4 {
            color: var(--light-color);
            font-size: 15px;
            margin: 0 0 3px 0;
            font-family: 'Lato', sans-serif;
            font-weight: 600;
        }
        
        .sidebar-user .user-info p {
            color: rgba(255,255,255,0.6);
            font-size: 12px;
            margin: 0;
        }
        
        .sidebar-user .user-info .member-since {
            color: var(--primary-color);
            font-size: 11px;
            margin-top: 2px;
        }
        
        /* Loyalty Points Badge */
        .loyalty-badge {
            margin-top: 15px;
            padding: 12px 15px;
            background-color: rgba(54,125,138,0.2);
            border-radius: 8px;
            border: 1px solid rgba(54,125,138,0.3);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .loyalty-badge span:first-child {
            font-size: 12px;
            color: rgba(255,255,255,0.7);
        }
        
        .loyalty-badge span:last-child {
            font-size: 16px;
            font-weight: 700;
            color: var(--primary-color);
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
        
        .sidebar-nav a.logout {
            color: #ff6b6b;
        }
        
        .sidebar-nav a.logout:hover {
            background-color: rgba(255,107,107,0.1);
            color: #ff6b6b;
        }
        
        /* Sidebar Footer - Quick Actions */
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-footer .quick-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }
        
        .sidebar-footer .quick-link:hover {
            color: var(--light-color);
        }
        
        .sidebar-footer .quick-link i {
            color: var(--primary-color);
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
        
        .top-header .welcome-message {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }
        
        .top-header .welcome-message strong {
            color: var(--primary-color);
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
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .top-header .header-actions a:hover {
            background-color: var(--gray-light);
            color: var(--primary-color);
        }
        
        .top-header .header-actions .badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--danger-color);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        /* Home Button */
        .home-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: var(--gray-light);
            color: var(--text-color);
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .home-btn:hover {
            background-color: var(--primary-color);
            color: var(--light-color);
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
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .mobile-toggle:hover {
            background-color: var(--gray-light);
        }
        
        /* Card Styles */
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            padding: 25px 30px;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            font-size: 20px;
            margin: 0;
        }
        
        .card-body {
            padding: 30px;
        }
        
        /* Stat Cards */
        .stat-card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .stat-card .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .stat-card .stat-label {
            font-size: 12px;
            color: #666;
            padding: 5px 10px;
            border-radius: 20px;
            background-color: var(--gray-light);
        }
        
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .stat-card .stat-desc {
            font-size: 14px;
            color: #666;
        }
        
        /* Quick Action Cards */
        .quick-action-card {
            padding: 30px;
            border-radius: 10px;
            text-decoration: none;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
        }
        
        .quick-action-card:hover {
            transform: translateY(-5px);
        }
        
        .quick-action-card.primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .quick-action-card.white {
            background-color: white;
            color: var(--dark-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .quick-action-card.white i {
            color: var(--primary-color);
        }
        
        .quick-action-card h4 {
            font-size: 18px;
            margin-bottom: 5px;
            color: inherit;
        }
        
        .quick-action-card p {
            font-size: 14px;
            opacity: 0.9;
            color: inherit;
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
                display: flex;
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
            
            .top-header .welcome-message {
                display: none;
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
            
            .card-header {
                padding: 20px;
            }
            
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="user-wrapper">
        <!-- Overlay for mobile -->
        <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <!-- Logo -->
            <div class="sidebar-header">
                <a href="../index.php" class="logo">
                    <img src="../assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo-image">
                    <span class="logo-text">Bayawan <span>Bai</span></span>
                </a>
            </div>
            
            <!-- Welcome Message -->
            <div class="welcome-section">
                <h3><i class="fas fa-hand-sparkles" style="color: var(--primary-color); margin-right: 8px;"></i>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h3>
                <p>Member since <?php echo date('M Y', strtotime($user['member_since'] ?? 'now')); ?></p>
            </div>
            
            <!-- User Profile -->
            <div class="sidebar-user">
                <div class="avatar"><?php echo $initials; ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="member-since"><i class="fas fa-crown" style="margin-right: 4px;"></i><?php echo number_format($user['loyalty_points'] ?? 0); ?> Points</p>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-section">Main Menu</li>
                    <li>
                        <a href="../index.php" class="<?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li>
                        <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                        </a>
                    </li>
                    <li class="nav-section">My Reservations</li>
                    <li>
                        <a href="my-bookings.php" class="<?php echo $currentPage === 'my-bookings' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> My Room Bookings
                        </a>
                    </li>
                    <li>
                        <a href="my-event-bookings.php" class="<?php echo $currentPage === 'my-event-bookings' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> My Event Bookings
                        </a>
                    </li>
                    <li>
                        <a href="my-food-orders.php" class="<?php echo $currentPage === 'my-food-orders' ? 'active' : ''; ?>">
                            <i class="fas fa-utensils"></i> My Food Orders
                        </a>
                    </li>
                    
                    <li class="nav-section">Account</li>
                    <li>
                        <a href="profile.php" class="<?php echo $currentPage === 'profile' ? 'active' : ''; ?>">
                            <i class="fas fa-user-cog"></i> Profile Settings
                        </a>
                    </li>
                    <li>
                        <a href="change-password.php" class="<?php echo $currentPage === 'change-password' ? 'active' : ''; ?>">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                    </li>
                    <li>
                        <a href="../auth/logout.php" class="logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
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
                    <h1><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                </div>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <span class="welcome-message">
                        Welcome back, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?>!</strong>
                    </span>
                    <div class="header-actions">
                        <a href="../index.php" title="Home"><i class="fas fa-home"></i></a>
                        <a href="../auth/logout.php" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
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
