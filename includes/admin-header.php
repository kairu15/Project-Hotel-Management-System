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

// Get success/error messages from session
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Sidebar Search */
        .sidebar-search {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background-color: rgba(0,0,0,0.1);
        }
        
        .sidebar-search-input-wrapper {
            position: relative;
        }
        
        .sidebar-search input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background-color: rgba(255,255,255,0.1);
            color: var(--light-color);
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .sidebar-search input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .sidebar-search input:focus {
            border-color: var(--primary-color);
            background-color: rgba(255,255,255,0.15);
            box-shadow: 0 0 0 3px rgba(54,125,138,0.2);
        }
        
        .sidebar-search .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 16px;
        }
        
        .sidebar-search .clear-search {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            cursor: pointer;
            display: none;
            width: 22px;
            height: 22px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-search .clear-search:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .sidebar-search .clear-search.visible {
            display: flex;
        }
        
        .sidebar-search .search-icon.hidden {
            display: none;
        }
        
        /* Search Results Panel */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-height: 350px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 5px;
            display: none;
        }
        
        .search-results.active {
            display: block;
        }
        
        .search-result-item {
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--dark-color);
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        
        .search-result-item i {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .search-result-info {
            flex: 1;
        }
        
        .search-result-info h4 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .search-result-info p {
            margin: 3px 0 0 0;
            font-size: 12px;
            color: #666;
        }
        
        .search-no-results {
            padding: 30px 20px;
            text-align: center;
            color: #999;
        }
        
        .search-no-results i {
            font-size: 36px;
            margin-bottom: 10px;
            color: var(--gray-medium);
        }
        
        .search-highlight {
            background-color: #fff3cd;
            color: #856404;
            padding: 0 2px;
            border-radius: 2px;
            font-weight: 600;
        }
        
        /* Mobile search */
        @media (max-width: 992px) {
            .search-results {
                position: fixed;
                top: auto;
                left: 10px;
                right: 10px;
                max-height: 50vh;
            }
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
                top: 60px;
                max-height: calc(100vh - 80px);
            }
            
            .notification-panel-header {
                padding: 12px 15px;
            }
            
            .notification-panel-header h3 {
                font-size: 14px;
            }
            
            .notification-panel-header .actions a {
                font-size: 11px;
                padding: 3px 8px;
            }
            
            .notification-item {
                padding: 12px 15px;
                gap: 10px;
            }
            
            .notification-icon {
                width: 36px;
                height: 36px;
            }
            
            .notification-icon i {
                font-size: 14px;
            }
            
            .notification-content h4 {
                font-size: 13px;
                margin-bottom: 3px;
            }
            
            .notification-content p {
                font-size: 12px;
                -webkit-line-clamp: 3;
            }
            
            .notification-time {
                font-size: 10px;
            }
            
            .notification-panel-footer {
                padding: 10px 15px;
            }
        }
        
        @media (max-width: 360px) {
            .notification-panel {
                width: calc(100% - 20px);
                right: 10px;
                left: 10px;
            }
            
            .notification-panel-header .actions {
                gap: 5px;
            }
            
            .notification-panel-header .actions a {
                font-size: 10px;
                padding: 2px 6px;
            }
            
            .notification-content p {
                -webkit-line-clamp: 2;
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
        
        /* Floating Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.4s ease-out, fadeOut 0.4s ease-in 4.6s forwards;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            border-left: 4px solid;
            position: relative;
            overflow: hidden;
            min-width: 300px;
            backdrop-filter: blur(10px);
        }
        
        .alert::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255,255,255,0.6);
            animation: progressBar 5s linear forwards;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateX(20px);
            }
        }
        
        @keyframes progressBar {
            from { width: 100%; }
            to { width: 0%; }
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(212,237,218,0.95) 0%, rgba(195,230,203,0.95) 100%);
            color: #155724;
            border-color: #28a745;
        }
        
        .alert-success i {
            color: #28a745;
            font-size: 22px;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, rgba(248,215,218,0.95) 0%, rgba(245,198,203,0.95) 100%);
            color: #721c24;
            border-color: #dc3545;
        }
        
        .alert-danger i {
            color: #dc3545;
            font-size: 22px;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(255,243,205,0.95) 0%, rgba(255,234,167,0.95) 100%);
            color: #856404;
            border-color: #ffc107;
        }
        
        .alert-warning i {
            color: #ffc107;
            font-size: 22px;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(209,236,241,0.95) 0%, rgba(190,229,235,0.95) 100%);
            color: #0c5460;
            border-color: #17a2b8;
        }
        
        .alert-info i {
            color: #17a2b8;
            font-size: 22px;
        }
        
        /* Persistent alert (no auto-dismiss) */
        .alert-persistent {
            animation: slideInRight 0.4s ease-out;
        }
        
        .alert-persistent::before {
            display: none;
        }
        
        /* Mobile responsive toast */
        @media (max-width: 768px) {
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            
            .alert {
                min-width: auto;
                animation: slideInUp 0.4s ease-out, fadeOut 0.4s ease-in 4.6s forwards;
            }
            
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
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
                padding: 12px 15px;
                font-size: 14px;
                flex-wrap: wrap;
                gap: 8px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                hyphens: auto;
            }
            
            .alert i {
                font-size: 16px;
                flex-shrink: 0;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 13px;
            }
            
            .notification-bell .badge {
                font-size: 9px;
                padding: 1px 4px;
                min-width: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .alert {
                margin: 10px 15px 0;
                padding: 10px 12px;
                font-size: 13px;
                border-radius: 4px;
            }
            
            .alert i {
                font-size: 14px;
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
                <?php
                $db = getDB();
                $userId = getUserId();
                $userStmt = $db->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
                $userStmt->execute([$userId]);
                $userData = $userStmt->fetch();
                $profilePic = $userData['profile_picture'] ?? null;

                // Fetch badge counts for menu items
                $badgeCounts = [];

                // Bookings & Payments
                $badgeCounts['bookings'] = $db->query("SELECT COUNT(*) FROM bookings WHERE status IN ('pending', 'confirmed')")->fetchColumn() ?: 0;
                $badgeCounts['payments_pending'] = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn() ?: 0;

                // Rooms & Amenities
                $badgeCounts['rooms'] = $db->query("SELECT COUNT(*) FROM rooms")->fetchColumn() ?: 0;
                $badgeCounts['room_categories'] = $db->query("SELECT COUNT(*) FROM room_categories WHERE status = 'active'")->fetchColumn() ?: 0;
                $badgeCounts['amenities'] = $db->query("SELECT COUNT(*) FROM amenities WHERE is_available = 1")->fetchColumn() ?: 0;
                $badgeCounts['additional_services'] = $db->query("SELECT COUNT(*) FROM additional_services WHERE is_available = 1")->fetchColumn() ?: 0;
                $badgeCounts['virtual_tours'] = $db->query("SELECT COUNT(*) FROM room_virtual_tours WHERE is_active = 1")->fetchColumn() ?: 0;

                // Maintenance
                $badgeCounts['maintenance_open'] = $db->query("SELECT COUNT(*) FROM maintenance_requests WHERE status IN ('pending', 'in_progress')")->fetchColumn() ?: 0;

                // Events & Dining
                $badgeCounts['event_spaces'] = $db->query("SELECT COUNT(*) FROM event_spaces WHERE status = 'available'")->fetchColumn() ?: 0;
                $badgeCounts['event_bookings'] = $db->query("SELECT COUNT(*) FROM event_bookings WHERE event_date >= CURDATE() AND status IN ('pending', 'confirmed')")->fetchColumn() ?: 0;
                $badgeCounts['event_virtual_tours'] = $db->query("SELECT COUNT(*) FROM event_virtual_tours WHERE is_active = 1")->fetchColumn() ?: 0;
                $badgeCounts['menu_categories'] = $db->query("SELECT COUNT(*) FROM menu_categories")->fetchColumn() ?: 0;
                $badgeCounts['menu_items'] = $db->query("SELECT COUNT(*) FROM menu_items WHERE is_available = 1")->fetchColumn() ?: 0;

                // Inventory Management
                $badgeCounts['inventory_categories'] = $db->query("SELECT COUNT(*) FROM inventory_categories")->fetchColumn() ?: 0;
                $badgeCounts['inventory_items'] = $db->query("SELECT COUNT(*) FROM inventory_items")->fetchColumn() ?: 0;
                $badgeCounts['food_inventory_low'] = $db->query("SELECT COUNT(*) FROM inventory_items ii JOIN inventory_categories ic ON ii.inv_cat_id = ic.inv_cat_id WHERE ic.category_name LIKE '%food%' AND ii.quantity <= ii.reorder_level")->fetchColumn() ?: 0;

                // Content & Marketing
                $badgeCounts['homepage_slider'] = $db->query("SELECT COUNT(*) FROM homepage_slider WHERE is_active = 1")->fetchColumn() ?: 0;
                $badgeCounts['promotions'] = $db->query("SELECT COUNT(*) FROM promotions WHERE is_active = 1 AND start_date <= CURDATE() AND (end_date IS NULL OR end_date >= CURDATE())")->fetchColumn() ?: 0;
                $badgeCounts['gallery'] = $db->query("SELECT COUNT(*) FROM gallery")->fetchColumn() ?: 0;
                $badgeCounts['faqs'] = $db->query("SELECT COUNT(*) FROM faqs WHERE is_active = 1")->fetchColumn() ?: 0;
                $badgeCounts['reviews_new'] = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 0")->fetchColumn() ?: 0;

                // Contact Messages
                $badgeCounts['contact_messages_new'] = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn() ?: 0;
                $badgeCounts['contact_messages_total'] = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status != 'archived'")->fetchColumn() ?: 0;

                // Operations
                $badgeCounts['staff_schedules'] = $db->query("SELECT COUNT(*) FROM staff_schedules WHERE work_date = CURDATE() AND status = 'scheduled'")->fetchColumn() ?: 0;
                $badgeCounts['staff_permissions'] = $db->query("SELECT COUNT(DISTINCT user_id) FROM staff_permissions WHERE can_access = 1")->fetchColumn() ?: 0;
                $badgeCounts['active_staff'] = $db->query("SELECT COUNT(*) FROM users WHERE role IN ('admin', 'manager', 'receptionist') AND last_login >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")->fetchColumn() ?: 0;
                $badgeCounts['staff_tasks'] = $db->query("SELECT COUNT(*) FROM staff_schedules WHERE work_date = CURDATE() AND status = 'scheduled'")->fetchColumn() ?: 0;

                // Analytics & Reports
                $badgeCounts['ratings'] = $db->query("SELECT COUNT(*) FROM reviews WHERE is_approved = 1")->fetchColumn() ?: 0;

                // User Management
                $badgeCounts['users'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn() ?: 0;
                $badgeCounts['user_sessions'] = $db->query("SELECT COUNT(*) FROM user_sessions WHERE expires_at > NOW()")->fetchColumn() ?: 0;
                ?>

                <style>
                    .menu-badge {
                        margin-left: auto;
                        background: var(--primary-color);
                        color: white;
                        font-size: 11px;
                        font-weight: 600;
                        padding: 2px 8px;
                        border-radius: 10px;
                        min-width: 20px;
                        text-align: center;
                    }
                    .menu-badge.warning {
                        background: var(--warning-color);
                        color: var(--dark-color);
                    }
                    .menu-badge.danger {
                        background: var(--danger-color);
                    }
                    .menu-badge.success {
                        background: var(--success-color);
                    }
                    .sidebar-nav a {
                        justify-content: flex-start;
                    }
                    .sidebar-nav a i {
                        margin-right: 12px;
                    }
                </style>
                <?php if (!empty($profilePic) && file_exists('../' . $profilePic)): ?>
                    <img src="../<?php echo htmlspecialchars($profilePic); ?>" alt="Profile" class="avatar" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color);">
                <?php else: ?>
                    <div class="avatar"><?php echo strtoupper(substr($_SESSION['first_name'], 0, 1)); ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                    <p><?php echo ucfirst(getUserRole()); ?></p>
                </div>
            </div>
            
            <!-- Sidebar Search -->
            <div class="sidebar-search">
                <div class="sidebar-search-input-wrapper">
                    <input type="text" id="sidebarSearchInput" placeholder="Search menu... (e.g., Bookings, Rooms, Users)" autocomplete="off">
                    <i class="fas fa-search search-icon" id="searchIcon"></i>
                    <span class="clear-search" id="clearSearch" onclick="clearSidebarSearch()" title="Clear search"><i class="fas fa-times"></i></span>
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="nav-section">Dashboard</li>
                    <li><a href="admin-dashboard.php" class="<?php echo $currentPage === 'admin-dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                    </a></li>
                    <li><a href="admin-calendar.php" class="<?php echo $currentPage === 'admin-calendar' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Calendar
                    </a></li>

                    <li class="nav-section">Bookings & Payments</li>
                    <li><a href="admin-bookings.php" class="<?php echo $currentPage === 'admin-bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Bookings
                        <?php if ($badgeCounts['bookings'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['bookings']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-payments.php" class="<?php echo $currentPage === 'admin-payments' ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-wave"></i> Payments
                        <?php if ($badgeCounts['payments_pending'] > 0): ?><span class="menu-badge warning"><?php echo $badgeCounts['payments_pending']; ?> Pending</span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">Rooms & Amenities</li>
                    <li><a href="admin-rooms.php" class="<?php echo $currentPage === 'admin-rooms' ? 'active' : ''; ?>">
                        <i class="fas fa-bed"></i> Rooms
                        <?php if ($badgeCounts['rooms'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['rooms']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-room-categories.php" class="<?php echo $currentPage === 'admin-room-categories' ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group"></i> Room Categories
                        <?php if ($badgeCounts['room_categories'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['room_categories']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-amenities.php" class="<?php echo $currentPage === 'admin-amenities' ? 'active' : ''; ?>">
                        <i class="fas fa-spa"></i> Amenities
                        <?php if ($badgeCounts['amenities'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['amenities']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-additional-services.php" class="<?php echo $currentPage === 'admin-additional-services' ? 'active' : ''; ?>">
                        <i class="fas fa-concierge-bell"></i> Additional Services
                        <?php if ($badgeCounts['additional_services'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['additional_services']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-virtual-tours.php" class="<?php echo $currentPage === 'admin-virtual-tours' ? 'active' : ''; ?>">
                        <i class="fas fa-vr-cardboard"></i> Virtual Tours
                        <?php if ($badgeCounts['virtual_tours'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['virtual_tours']; ?></span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">Maintenance</li>
                    <li><a href="admin-maintenance.php" class="<?php echo $currentPage === 'admin-maintenance' ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i> Maintenance Requests
                        <?php if ($badgeCounts['maintenance_open'] > 0): ?><span class="menu-badge danger"><?php echo $badgeCounts['maintenance_open']; ?> Open</span><?php endif; ?>
                    </a></li>

                    <li class="nav-section" style="cursor: pointer;" onclick="window.location.href='admin-operations.php'">Operations</li>
                    <li><a href="admin-operations.php" class="<?php echo $currentPage === 'admin-operations' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> Operations Dashboard
                    </a></li>
                    <li><a href="admin-active.php" class="<?php echo $currentPage === 'admin-active' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Active Staff
                        <?php if ($badgeCounts['active_staff'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['active_staff']; ?> Online</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-tasks.php" class="<?php echo $currentPage === 'admin-tasks' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i> Staff Tasks
                        <?php if ($badgeCounts['staff_tasks'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['staff_tasks']; ?> Today</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-staff-schedules.php" class="<?php echo $currentPage === 'admin-staff-schedules' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Staff Schedules
                        <?php if ($badgeCounts['staff_schedules'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['staff_schedules']; ?> Today</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-staff-permissions.php" class="<?php echo $currentPage === 'admin-staff-permissions' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i> Staff Permissions
                        <?php if ($badgeCounts['staff_permissions'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['staff_permissions']; ?> Assigned</span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">Events & Dining</li>
                    <li><a href="admin-event-spaces.php" class="<?php echo $currentPage === 'admin-event-spaces' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i> Event Spaces
                        <?php if ($badgeCounts['event_spaces'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['event_spaces']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-event-bookings.php" class="<?php echo $currentPage === 'admin-event-bookings' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-alt"></i> Event Bookings
                        <?php if ($badgeCounts['event_bookings'] > 0): ?><span class="menu-badge success"><?php echo $badgeCounts['event_bookings']; ?> Upcoming</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-event-virtual-tours.php" class="<?php echo $currentPage === 'admin-event-virtual-tours' ? 'active' : ''; ?>">
                        <i class="fas fa-vr-cardboard"></i> Event Virtual Tours
                        <?php if ($badgeCounts['event_virtual_tours'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['event_virtual_tours']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-menu-categories.php" class="<?php echo $currentPage === 'admin-menu-categories' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> Menu Categories
                        <?php if ($badgeCounts['menu_categories'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['menu_categories']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-menu-items.php" class="<?php echo $currentPage === 'admin-menu-items' ? 'active' : ''; ?>">
                        <i class="fas fa-hamburger"></i> Menu Items
                        <?php if ($badgeCounts['menu_items'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['menu_items']; ?></span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">Inventory Management</li>
                    <li><a href="admin-inventory-categories.php" class="<?php echo $currentPage === 'admin-inventory-categories' ? 'active' : ''; ?>">
                        <i class="fas fa-folder"></i> Inventory Categories
                        <?php if ($badgeCounts['inventory_categories'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['inventory_categories']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-inventory-items.php" class="<?php echo $currentPage === 'admin-inventory-items' ? 'active' : ''; ?>">
                        <i class="fas fa-boxes"></i> Inventory Items
                        <?php if ($badgeCounts['inventory_items'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['inventory_items']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-foods-inventory.php" class="<?php echo $currentPage === 'admin-foods-inventory' ? 'active' : ''; ?>">
                        <i class="fas fa-utensils"></i> Food Inventory
                        <?php if ($badgeCounts['food_inventory_low'] > 0): ?><span class="menu-badge danger"><?php echo $badgeCounts['food_inventory_low']; ?> Low Stock</span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">Content & Marketing</li>
                    <li><a href="admin-homepage-slider.php" class="<?php echo $currentPage === 'admin-homepage-slider' ? 'active' : ''; ?>">
                        <i class="fas fa-sliders-h"></i> Homepage Slider
                        <?php if ($badgeCounts['homepage_slider'] > 0): ?><span class="menu-badge success"><?php echo $badgeCounts['homepage_slider']; ?> Active</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-promotions.php" class="<?php echo $currentPage === 'admin-promotions' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i> Promotions
                        <?php if ($badgeCounts['promotions'] > 0): ?><span class="menu-badge success"><?php echo $badgeCounts['promotions']; ?> Current</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-gallery.php" class="<?php echo $currentPage === 'admin-gallery' ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Gallery
                        <?php if ($badgeCounts['gallery'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['gallery']; ?> Images</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-team.php" class="<?php echo $currentPage === 'admin-team' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Team Management
                    </a></li>
                    <li><a href="admin-faqs.php" class="<?php echo $currentPage === 'admin-faqs' ? 'active' : ''; ?>">
                        <i class="fas fa-question-circle"></i> FAQs
                        <?php if ($badgeCounts['faqs'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['faqs']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-reviews.php" class="<?php echo $currentPage === 'admin-reviews' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                        <?php if ($badgeCounts['reviews_new'] > 0): ?><span class="menu-badge success"><?php echo $badgeCounts['reviews_new']; ?> New</span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-contact-messages.php" class="<?php echo $currentPage === 'admin-contact-messages' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> Contact Messages
                        <?php if ($badgeCounts['contact_messages_new'] > 0): ?><span class="menu-badge warning"><?php echo $badgeCounts['contact_messages_new']; ?> New</span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">Analytics & Reports</li>
                    <li><a href="admin-analytics.php" class="<?php echo $currentPage === 'admin-analytics' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a></li>
                    <li><a href="admin-reports.php" class="<?php echo $currentPage === 'admin-reports' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a></li>
                    <li><a href="admin-ratings.php" class="<?php echo $currentPage === 'admin-ratings' ? 'active' : ''; ?>">
                        <i class="fas fa-star-half-alt"></i> Ratings
                        <?php if ($badgeCounts['ratings'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['ratings']; ?></span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">User Management</li>
                    <li><a href="admin-users.php" class="<?php echo $currentPage === 'admin-users' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Users
                        <?php if ($badgeCounts['users'] > 0): ?><span class="menu-badge"><?php echo $badgeCounts['users']; ?></span><?php endif; ?>
                    </a></li>
                    <li><a href="admin-user-sessions.php" class="<?php echo $currentPage === 'admin-user-sessions' ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog"></i> User Sessions
                        <?php if ($badgeCounts['user_sessions'] > 0): ?><span class="menu-badge success"><?php echo $badgeCounts['user_sessions']; ?> Active</span><?php endif; ?>
                    </a></li>

                    <li class="nav-section">System</li>
                    <li><a href="admin-profile.php" class="<?php echo $currentPage === 'admin-profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a></li>
                    <li><a href="admin-settings.php" class="<?php echo $currentPage === 'admin-settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Settings
                    </a></li>
                    <li><a href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Website
                    </a></li>
                    <li><a href="javascript:void(0);" onclick="openLogoutModal();">
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
                    <a href="admin-settings.php" title="Settings"><i class="fas fa-cog"></i></a>
                    <a href="admin-profile.php" title="My Profile"><i class="fas fa-user-circle"></i></a>
                    <a href="admin-dashboard.php" title="Dashboard"><i class="fas fa-home"></i></a>
                    <a href="javascript:void(0);" title="Logout" onclick="openLogoutModal();"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </header>
            
            <!-- Toast Notification Container -->
            <div class="toast-container">
                <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <i class="fas fa-<?php echo $alert['type'] == 'success' ? 'check-circle' : ($alert['type'] == 'danger' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                    <span><?php echo $alert['message']; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $successMessage; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-persistent">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $errorMessage; ?></span>
                </div>
                <?php endif; ?>
            </div>
            
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
            
            // CSS Confirmation Modal for Delete/Cancel actions
            let currentDeleteForm = null;
            let currentDeleteUrl = null;
            let currentDeleteButtonName = null;
            
            function openDeleteModal(formId, title, message, redirectUrl = null, buttonName = null) {
                // Close any existing modal first
                closeDeleteModal();
                
                currentDeleteForm = formId;
                currentDeleteUrl = redirectUrl;
                currentDeleteButtonName = buttonName;
                
                // Create modal HTML
                const modal = document.createElement('div');
                modal.id = 'deleteConfirmModal';
                modal.style.cssText = 'display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);z-index:10000;justify-content:center;align-items:center;';
                modal.innerHTML = `
                    <div style="background:white;border-radius:16px;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideUp 0.3s ease;overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#dc3545,#c82333);color:white;padding:20px 25px;display:flex;justify-content:space-between;align-items:center;">
                            <h3 style="margin:0;font-size:18px;font-weight:600;"><i class="fas fa-exclamation-triangle" style="margin-right:10px;"></i>${title}</h3>
                            <button type="button" onclick="closeDeleteModal()" style="background:none;border:none;color:white;font-size:24px;cursor:pointer;">&times;</button>
                        </div>
                        <div style="padding:25px;">
                            <p style="margin:0 0 25px 0;color:#555;font-size:15px;line-height:1.5;">${message}</p>
                            <div style="display:flex;gap:12px;justify-content:flex-end;">
                                <button type="button" onclick="closeDeleteModal()" style="padding:12px 24px;background:#f5f5f5;border:2px solid #ddd;border-radius:10px;font-size:14px;font-weight:500;color:#666;cursor:pointer;">Cancel</button>
                                <button type="button" onclick="confirmDeleteAction()" style="padding:12px 24px;background:linear-gradient(135deg,#dc3545,#c82333);border:none;border-radius:10px;font-size:14px;font-weight:600;color:white;cursor:pointer;box-shadow:0 4px 15px rgba(220,53,69,0.3);">Confirm</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                document.body.style.overflow = 'hidden';
                
                // Add animation style
                const style = document.createElement('style');
                style.id = 'deleteModalStyle';
                style.textContent = '@keyframes slideUp { from { opacity:0; transform:translateY(30px); } to { opacity:1; transform:translateY(0); } }';
                document.head.appendChild(style);
            }
            
            function closeDeleteModal() {
                const modal = document.getElementById('deleteConfirmModal');
                if (modal) {
                    modal.remove();
                    document.body.style.overflow = 'auto';
                }
                const style = document.getElementById('deleteModalStyle');
                if (style) style.remove();
                currentDeleteForm = null;
                currentDeleteUrl = null;
                currentDeleteButtonName = null;
            }
            
            function confirmDeleteAction() {
                if (currentDeleteForm) {
                    const form = document.getElementById(currentDeleteForm);
                    if (form) {
                        // Add hidden input for button name if specified
                        if (currentDeleteButtonName) {
                            let hiddenInput = form.querySelector('input[name="' + currentDeleteButtonName + '"]');
                            if (!hiddenInput) {
                                hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = currentDeleteButtonName;
                                hiddenInput.value = '1';
                                form.appendChild(hiddenInput);
                            }
                        }
                        form.submit();
                    }
                } else if (currentDeleteUrl) {
                    window.location.href = currentDeleteUrl;
                }
                closeDeleteModal();
            }
            
            // Close modal when clicking outside
            document.addEventListener('click', function(e) {
                const modal = document.getElementById('deleteConfirmModal');
                if (modal && e.target === modal) {
                    closeDeleteModal();
                }
            });
            
            // ============================================
            // SIDEBAR SEARCH FUNCTIONALITY
            // ============================================
            
            // Menu items data for search
            const menuItems = [
                { name: 'Dashboard Overview', keywords: 'dashboard overview main home', url: 'admin-dashboard.php', icon: 'tachometer-alt', section: 'Dashboard' },
                { name: 'Calendar', keywords: 'calendar schedule events dates', url: 'admin-calendar.php', icon: 'calendar-alt', section: 'Dashboard' },
                { name: 'Bookings', keywords: 'bookings reservations rooms', url: 'admin-bookings.php', icon: 'calendar-check', section: 'Bookings & Payments' },
                { name: 'Payments', keywords: 'payments billing invoice transactions', url: 'admin-payments.php', icon: 'money-bill-wave', section: 'Bookings & Payments' },
                { name: 'Rooms', keywords: 'rooms accommodation', url: 'admin-rooms.php', icon: 'bed', section: 'Rooms & Amenities' },
                { name: 'Room Categories', keywords: 'room categories types', url: 'admin-room-categories.php', icon: 'layer-group', section: 'Rooms & Amenities' },
                { name: 'Amenities', keywords: 'amenities facilities features', url: 'admin-amenities.php', icon: 'spa', section: 'Rooms & Amenities' },
                { name: 'Additional Services', keywords: 'services laundry spa wellness dry cleaning', url: 'admin-additional-services.php', icon: 'concierge-bell', section: 'Rooms & Amenities' },
                { name: 'Virtual Tours', keywords: 'virtual tours vr rooms', url: 'admin-virtual-tours.php', icon: 'vr-cardboard', section: 'Rooms & Amenities' },
                { name: 'Maintenance Requests', keywords: 'maintenance repair fix requests', url: 'admin-maintenance.php', icon: 'tools', section: 'Maintenance' },
                { name: 'Event Spaces', keywords: 'event spaces venues halls', url: 'admin-event-spaces.php', icon: 'building', section: 'Events & Dining' },
                { name: 'Event Bookings', keywords: 'event bookings inquiries', url: 'admin-event-bookings.php', icon: 'calendar-alt', section: 'Events & Dining' },
                { name: 'Event Virtual Tours', keywords: 'event virtual tours vr', url: 'admin-event-virtual-tours.php', icon: 'vr-cardboard', section: 'Events & Dining' },
                { name: 'Menu Categories', keywords: 'menu categories food dining', url: 'admin-menu-categories.php', icon: 'utensils', section: 'Events & Dining' },
                { name: 'Menu Items', keywords: 'menu items food dishes meals', url: 'admin-menu-items.php', icon: 'hamburger', section: 'Events & Dining' },
                { name: 'Inventory Categories', keywords: 'inventory categories stock', url: 'admin-inventory-categories.php', icon: 'folder', section: 'Inventory Management' },
                { name: 'Inventory Items', keywords: 'inventory items stock products', url: 'admin-inventory-items.php', icon: 'boxes', section: 'Inventory Management' },
                { name: 'Food Inventory', keywords: 'food inventory stock kitchen', url: 'admin-foods-inventory.php', icon: 'utensils', section: 'Inventory Management' },
                { name: 'Homepage Slider', keywords: 'homepage slider banner hero', url: 'admin-homepage-slider.php', icon: 'sliders-h', section: 'Content & Marketing' },
                { name: 'Promotions', keywords: 'promotions deals offers discounts', url: 'admin-promotions.php', icon: 'tags', section: 'Content & Marketing' },
                { name: 'Gallery', keywords: 'gallery images photos media', url: 'admin-gallery.php', icon: 'images', section: 'Content & Marketing' },
                { name: 'Team Management', keywords: 'team members leadership staff about page', url: 'admin-team.php', icon: 'users', section: 'Content & Marketing' },
                { name: 'FAQs', keywords: 'faqs questions answers help', url: 'admin-faqs.php', icon: 'question-circle', section: 'Content & Marketing' },
                { name: 'Reviews', keywords: 'reviews ratings feedback testimonials', url: 'admin-reviews.php', icon: 'star', section: 'Content & Marketing' },
                { name: 'Staff Schedules', keywords: 'staff schedules roster shifts', url: 'admin-staff-schedules.php', icon: 'calendar-alt', section: 'Operations' },
                { name: 'Staff Permissions', keywords: 'staff permissions roles access', url: 'admin-staff-permissions.php', icon: 'user-shield', section: 'Operations' },
                { name: 'Analytics', keywords: 'analytics statistics charts data', url: 'admin-analytics.php', icon: 'chart-line', section: 'Analytics & Reports' },
                { name: 'Reports', keywords: 'reports summary data export', url: 'admin-reports.php', icon: 'chart-bar', section: 'Analytics & Reports' },
                { name: 'Ratings', keywords: 'ratings stars reviews scores', url: 'admin-ratings.php', icon: 'star-half-alt', section: 'Analytics & Reports' },
                { name: 'Users', keywords: 'users guests customers accounts', url: 'admin-users.php', icon: 'users', section: 'User Management' },
                { name: 'User Sessions', keywords: 'user sessions login activity', url: 'admin-user-sessions.php', icon: 'users-cog', section: 'User Management' },
                { name: 'My Profile', keywords: 'profile account user settings', url: 'admin-profile.php', icon: 'user-circle', section: 'System' },
                { name: 'Settings', keywords: 'settings configuration options', url: 'admin-settings.php', icon: 'cog', section: 'System' },
                { name: 'View Website', keywords: 'website view site public', url: '../index.php', icon: 'external-link-alt', section: 'System' },
                { name: 'Logout', keywords: 'logout signout exit', url: '../auth/logout.php', icon: 'sign-out-alt', section: 'System' }
            ];
            
            let searchInput, searchResults, searchIcon, clearSearchBtn;
            
            // Initialize search on DOM load
            document.addEventListener('DOMContentLoaded', function() {
                searchInput = document.getElementById('sidebarSearchInput');
                searchResults = document.getElementById('searchResults');
                searchIcon = document.getElementById('searchIcon');
                clearSearchBtn = document.getElementById('clearSearch');
                
                if (searchInput) {
                    // Live search on input
                    searchInput.addEventListener('input', function() {
                        performSearch(this.value);
                    });
                    
                    // Handle keyboard navigation
                    searchInput.addEventListener('keydown', function(e) {
                        handleSearchKeydown(e);
                    });
                    
                    // Close search when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!e.target.closest('.sidebar-search')) {
                            hideSearchResults();
                        }
                    });
                }
            });
            
            function performSearch(query) {
                query = query.trim().toLowerCase();
                
                // Toggle clear button visibility
                if (query.length > 0) {
                    clearSearchBtn.classList.add('visible');
                    searchIcon.classList.add('hidden');
                } else {
                    clearSearchBtn.classList.remove('visible');
                    searchIcon.classList.remove('hidden');
                    hideSearchResults();
                    return;
                }
                
                // Search in menu items
                const results = menuItems.filter(item => {
                    return item.name.toLowerCase().includes(query) || 
                           item.keywords.toLowerCase().includes(query);
                });
                
                displaySearchResults(results, query);
            }
            
            function displaySearchResults(results, query) {
                if (results.length === 0) {
                    searchResults.innerHTML = `
                        <div class="search-no-results">
                            <i class="fas fa-search"></i>
                            <p>No results found for "${escapeHtml(query)}"</p>
                        </div>
                    `;
                } else {
                    searchResults.innerHTML = results.map(item => {
                        const highlightedName = highlightText(item.name, query);
                        return `
                            <a href="${item.url}" class="search-result-item" data-result-index="${results.indexOf(item)}">
                                <i class="fas fa-${item.icon}"></i>
                                <div class="search-result-info">
                                    <h4>${highlightedName}</h4>
                                    <p>${escapeHtml(item.section)}</p>
                                </div>
                                <i class="fas fa-chevron-right" style="color: #ccc; font-size: 12px;"></i>
                            </a>
                        `;
                    }).join('');
                }
                
                searchResults.classList.add('active');
            }
            
            function highlightText(text, query) {
                if (!query) return escapeHtml(text);
                const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
                return escapeHtml(text).replace(regex, '<span class="search-highlight">$1</span>');
            }
            
            function escapeRegex(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
            
            function hideSearchResults() {
                if (searchResults) {
                    searchResults.classList.remove('active');
                }
            }
            
            function clearSidebarSearch() {
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                    clearSearchBtn.classList.remove('visible');
                    searchIcon.classList.remove('hidden');
                    hideSearchResults();
                }
            }
            
            function handleSearchKeydown(e) {
                const items = searchResults.querySelectorAll('.search-result-item');
                let currentIndex = -1;
                
                // Find current focused item
                items.forEach((item, index) => {
                    if (item.classList.contains('focused')) {
                        currentIndex = index;
                    }
                });
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentIndex < items.length - 1) {
                        if (currentIndex >= 0) items[currentIndex].classList.remove('focused');
                        items[currentIndex + 1].classList.add('focused');
                        items[currentIndex + 1].style.backgroundColor = '#f0f9ff';
                        if (currentIndex >= 0) items[currentIndex].style.backgroundColor = '';
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentIndex > 0) {
                        items[currentIndex].classList.remove('focused');
                        items[currentIndex].style.backgroundColor = '';
                        items[currentIndex - 1].classList.add('focused');
                        items[currentIndex - 1].style.backgroundColor = '#f0f9ff';
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex >= 0) {
                        window.location.href = items[currentIndex].href;
                    } else if (items.length > 0) {
                        window.location.href = items[0].href;
                    }
                } else if (e.key === 'Escape') {
                    hideSearchResults();
                    searchInput.blur();
                }
            }
            </script>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center;">
    <div style="background-color: white; border-radius: 15px; width: 90%; max-width: 400px; padding: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); text-align: center; animation: modalSlideIn 0.3s ease;">
        <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #ff6b6b, #ee5a5a); border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-sign-out-alt" style="font-size: 30px; color: white;"></i>
        </div>
        <h3 style="font-size: 22px; color: #333; margin-bottom: 10px; font-weight: 600;">Logout Confirmation</h3>
        <p style="color: #666; font-size: 15px; margin-bottom: 25px; line-height: 1.5;">Are you sure you want to logout?</p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button onclick="closeLogoutModal()" style="padding: 12px 30px; border: 2px solid #ddd; background-color: white; color: #666; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s;">No</button>
            <a href="../auth/logout.php" style="padding: 12px 30px; border: none; background: linear-gradient(135deg, #ff6b6b, #ee5a5a); color: white; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s;">Yes</a>
        </div>
    </div>
</div>
<style>
@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-50px) scale(0.9); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
#logoutModal button:hover, #logoutModal a:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
#logoutModal button:hover {
    border-color: #bbb;
}
</style>
<script>
function openLogoutModal() {
    document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}
// Close modal when clicking outside
document.getElementById('logoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogoutModal();
    }
});
// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLogoutModal();
    }
});
</script>
