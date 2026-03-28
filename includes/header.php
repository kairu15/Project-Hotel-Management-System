<?php
/**
 * Header Include File - Bayawan Bai Hotel
 * Include this at the top of every page
 */
require_once __DIR__ . '/config.php';

// Get alert if any
$alert = getAlert();

// Get user info for profile picture
$userProfilePicture = null;
if (isLoggedIn()) {
    $db = getDB();
    $userId = getUserId();
    $userStmt = $db->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch();
    $userProfilePicture = $userData['profile_picture'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/bayawanhotellogo.png">
    <link rel="shortcut icon" type="image/png" href="assets/bayawanhotellogo.png">
    
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
            background-color: var(--light-color);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        /* Top Bar */
        .top-bar {
            background-color: var(--dark-color);
            color: var(--light-color);
            padding: 8px 0;
            font-size: 14px;
        }
        
        .top-bar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar a {
            color: var(--light-color);
            text-decoration: none;
            margin-left: 20px;
            transition: color 0.3s;
        }
        
        .top-bar a:hover {
            color: var(--primary-color);
        }
        
        .top-bar i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        /* Main Navigation */
        .main-nav {
            background-color: var(--light-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .main-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .logo-image {
            height: 50px;
            margin-right: 15px;
            width: 50px;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .logo-text span {
            color: var(--primary-color);
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
            position: relative;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: var(--primary-color);
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--primary-color);
            transition: width 0.3s;
        }
        
        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }
        
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
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
        
        .btn-dark {
            background-color: var(--dark-color);
            color: var(--light-color);
        }
        
        .btn-dark:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-sm {
            padding: 8px 20px;
            font-size: 13px;
        }
        
        /* Mobile Menu Toggle */
        .mobile-toggle {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--dark-color);
        }
        
        /* User Dropdown */
        .user-menu {
            position: relative;
        }
        
        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .user-menu-btn:hover {
            background-color: var(--gray-light);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--light-color);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            border-radius: 5px;
            min-width: 200px;
            display: none;
            overflow: hidden;
        }
        
        .user-dropdown.active {
            display: block;
        }
        
        .user-dropdown a {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .user-dropdown a:hover {
            background-color: var(--gray-light);
            color: var(--primary-color);
        }
        
        .user-dropdown i {
            margin-right: 10px;
            width: 20px;
        }
        
        .user-dropdown hr {
            border: none;
            border-top: 1px solid var(--gray-medium);
            margin: 5px 0;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--light-color);
            padding: 60px 0;
            text-align: center;
        }
        
        .page-header h1 {
            color: var(--light-color);
            font-size: 42px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background-color: var(--gray-light);
            padding: 15px 0;
            margin-bottom: 0;
        }
        
        .breadcrumb ul {
            display: flex;
            list-style: none;
            gap: 10px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb li:last-child {
            color: var(--text-color);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background-color: var(--light-color);
                flex-direction: column;
                padding: 20px;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                gap: 15px;
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .top-bar .container {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .top-bar .container > div:first-child {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .top-bar .container > div:last-child {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 32px;
            }
            
            .page-header p {
                font-size: 16px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .main-nav .container {
                padding: 10px 0;
            }
            
            .logo-text {
                font-size: 18px;
            }
            
            .logo-image {
                height: 40px;
                width: 40px;
            }
            
            .user-menu-btn span {
                display: none;
            }
            
            .top-bar {
                font-size: 12px;
                padding: 6px 0;
            }
            
            .top-bar i {
                margin-right: 3px;
            }
            
            .top-bar a {
                margin-left: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 0 15px;
            }
            
            .page-header {
                padding: 40px 0;
            }
            
            .page-header h1 {
                font-size: 28px;
            }
            
            .page-header p {
                font-size: 14px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 12px;
            }
            
            .logo-text {
                font-size: 16px;
            }
            
            .logo-image {
                height: 35px;
                width: 35px;
                margin-right: 10px;
            }
            
            .main-nav .container {
                padding: 8px 0;
            }
            
            .nav-links {
                padding: 15px;
                gap: 10px;
            }
            
            .nav-links a {
                font-size: 14px;
                padding: 10px 0;
                border-bottom: 1px solid var(--gray-medium);
                width: 100%;
            }
            
            .nav-links li:last-child a {
                border-bottom: none;
            }
            
            .user-dropdown {
                min-width: 180px;
                right: -10px;
            }
            
            .top-bar .container > div:first-child,
            .top-bar .container > div:last-child {
                gap: 10px;
                font-size: 11px;
            }
            
            .top-bar a {
                margin-left: 8px;
            }
            
            .breadcrumb ul {
                flex-wrap: wrap;
                gap: 5px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div>
                <i class="fas fa-phone"></i> +63 35 123 4567
                <span style="margin: 0 15px;">|</span>
                <i class="fas fa-envelope"></i> info@bayawanbaihotel.com
            </div>
            <div>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <?php if (isLoggedIn()): ?>
                    <span style="margin: 0 15px;">|</span>
                    <span><i class="fas fa-user"></i> Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container">
            <a href="/bayawanhotel/index.php" class="logo">
                <img src="assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo-image">
                <div class="logo-text">Bayawan <span>Bai</span> Hotel</div>
            </a>
            
            <ul class="nav-links" id="navLinks">
                <li><a href="/bayawanhotel/index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                <li><a href="/bayawanhotel/rooms.php" <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'class="active"' : ''; ?>>Rooms</a></li>
                <li><a href="/bayawanhotel/dining.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dining.php' ? 'class="active"' : ''; ?>>Dining</a></li>
                <li><a href="/bayawanhotel/amenities.php" <?php echo basename($_SERVER['PHP_SELF']) == 'amenities.php' ? 'class="active"' : ''; ?>>Amenities</a></li>
                <li><a href="/bayawanhotel/events.php" <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'class="active"' : ''; ?>>Events</a></li>
                <li><a href="/bayawanhotel/gallery.php" <?php echo basename($_SERVER['PHP_SELF']) == 'gallery.php' ? 'class="active"' : ''; ?>>Gallery</a></li>
                <li><a href="/bayawanhotel/about.php" <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'class="active"' : ''; ?>>About</a></li>
                <li><a href="/bayawanhotel/contact.php" <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
            </ul>
            
            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="user-menu">
                        <div class="user-menu-btn" onclick="toggleUserMenu()">
                            <?php if (!empty($userProfilePicture) && file_exists($userProfilePicture)): ?>
                                <img src="<?php echo htmlspecialchars($userProfilePicture); ?>" alt="Profile Picture" 
                                     style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                            <?php else: ?>
                                <div class="user-avatar"><?php echo substr($_SESSION['first_name'], 0, 1); ?></div>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="user-dropdown" id="userDropdown">
                            <a href="/bayawanhotel/user/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a href="/bayawanhotel/user/profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <a href="/bayawanhotel/user/my-bookings.php"><i class="fas fa-calendar-check"></i> My Bookings</a>
                            <?php if (isStaff()): ?>
                                <hr>
                                <a href="/bayawanhotel/staff/staff-dashboard.php"><i class="fas fa-briefcase"></i> Staff Panel</a>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                                <a href="/bayawanhotel/admin/admin-dashboard.php"><i class="fas fa-cog"></i> Admin Panel</a>
                            <?php endif; ?>
                            <hr>
                            <a href="/bayawanhotel/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/bayawanhotel/auth/login.php" class="btn btn-outline btn-sm">Sign In</a>
                    <a href="/bayawanhotel/booking.php" class="btn btn-primary btn-sm">Book Now</a>
                <?php endif; ?>
                <div class="mobile-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Alert Messages -->
    <?php if ($alert): ?>
    <div class="container" style="margin-top: 20px;">
        <div class="alert alert-<?php echo $alert['type']; ?>">
            <i class="fas fa-<?php echo $alert['type'] == 'success' ? 'check-circle' : ($alert['type'] == 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $alert['message']; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function toggleMobileMenu() {
            document.getElementById('navLinks').classList.toggle('active');
        }
        
        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('active');
        }
        
        // Close user dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            if (userMenu && !userMenu.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
