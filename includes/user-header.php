<?php
/**
 * User Dashboard Header Include File - Bayawan Bai Hotel
 * Sidebar layout for user dashboard with welcome message
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/TranslationEngine.php';

// Get alert if any
$alert = getAlert();

// Get success/error messages from session
$successMessage = $_SESSION['success'] ?? null;
$errorMessage = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get user info
$db = getDB();
$userId = getUserId();
$userStmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get reservation counts for inbox badges (for room bookings)
$reservationCounts = [
    'all' => 0,
    'confirmed' => 0,
    'checked_in' => 0,
    'checked_out' => 0,
    'cancelled' => 0,
    'completed' => 0,
    'pending' => 0,
    'archived' => 0,
    'trashed' => 0
];

if ($userId) {
    // Room bookings counts
    $countStmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM bookings 
        WHERE user_id = ? 
        AND is_deleted = 0 AND is_archived = 0
        GROUP BY status
    ");
    $countStmt->execute([$userId]);
    $bookingCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Event bookings counts
    $eventCountStmt = $db->prepare("
        SELECT status, COUNT(*) as count 
        FROM event_bookings 
        WHERE user_id = ? 
        AND is_deleted = 0 AND is_archived = 0
        GROUP BY status
    ");
    $eventCountStmt->execute([$userId]);
    $eventCounts = $eventCountStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Merge counts from both tables
    foreach (['confirmed', 'checked_in', 'checked_out', 'cancelled', 'completed', 'pending'] as $status) {
        $reservationCounts[$status] = ($bookingCounts[$status] ?? 0) + ($eventCounts[$status] ?? 0);
    }
    
    // Total active reservations (not archived or deleted)
    $reservationCounts['all'] = array_sum($reservationCounts);
    
    // Archived count
    $archivedStmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND is_archived = 1 AND is_deleted = 0) +
            (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND is_archived = 1 AND is_deleted = 0) as total
    ");
    $archivedStmt->execute([$userId, $userId]);
    $reservationCounts['archived'] = $archivedStmt->fetchColumn() ?: 0;
    
    // Trashed count
    $trashedStmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM bookings WHERE user_id = ? AND is_deleted = 1) +
            (SELECT COUNT(*) FROM event_bookings WHERE user_id = ? AND is_deleted = 1) as total
    ");
    $trashedStmt->execute([$userId, $userId]);
    $reservationCounts['trashed'] = $trashedStmt->fetchColumn() ?: 0;
    
    // Get unread contact message notifications count (only for messages that exist for this user)
    $userEmail = $_SESSION['email'] ?? '';
    $unreadStmt = $db->prepare("
        SELECT COUNT(*) FROM notifications n
        JOIN contact_messages cm ON n.related_id = cm.message_id
        WHERE n.user_id = ? AND n.related_type = 'contact_message' AND n.status = 'unread'
        AND (cm.user_id = ? OR cm.email = ?)
    ");
    $unreadStmt->execute([$userId, $userId, $userEmail]);
    $unreadCount = $unreadStmt->fetchColumn() ?: 0;
}

// Helper function to get badge HTML
function getInboxBadge($count) {
    if ($count == 0) return '';
    $display = $count > 99 ? '99+' : $count;
    return '<span class="inbox-badge">' . $display . '</span>';
}

// Generate initials
$firstName = $_SESSION['first_name'] ?? '';
$lastName = $_SESSION['last_name'] ?? '';
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
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
        
        /* Inbox-Style Menu Badge System */
        .inbox-badge {
            margin-left: auto;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
        }
        
        .inbox-badge.success {
            background: linear-gradient(135deg, #28a745, #218838);
        }
        
        .inbox-badge.warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #333;
        }
        
        .inbox-badge.info {
            background: linear-gradient(135deg, #17a2b8, #138496);
        }
        
        .inbox-badge.secondary {
            background: linear-gradient(135deg, #6c757d, #545b62);
        }
        
        .sidebar-nav a .inbox-badge {
            opacity: 0.95;
        }
        
        /* Inbox menu item colors */
        .sidebar-nav a.inbox-all:hover,
        .sidebar-nav a.inbox-all.active {
            border-left-color: #fff;
        }
        
        .sidebar-nav a.inbox-confirmed:hover,
        .sidebar-nav a.inbox-confirmed.active {
            border-left-color: #28a745;
        }
        
        .sidebar-nav a.inbox-checkin:hover,
        .sidebar-nav a.inbox-checkin.active {
            border-left-color: #17a2b8;
        }
        
        .sidebar-nav a.inbox-checkout:hover,
        .sidebar-nav a.inbox-checkout.active {
            border-left-color: #6c757d;
        }
        
        .sidebar-nav a.inbox-cancelled:hover,
        .sidebar-nav a.inbox-cancelled.active {
            border-left-color: #dc3545;
        }

        .sidebar-nav a.inbox-completed:hover,
        .sidebar-nav a.inbox-completed.active {
            border-left-color: #28a745;
        }

        .sidebar-nav a.inbox-pending:hover,
        .sidebar-nav a.inbox-pending.active {
            border-left-color: #ffc107;
        }
        
        .sidebar-nav a.manage-archive:hover,
        .sidebar-nav a.manage-archive.active {
            border-left-color: #6c757d;
        }
        
        .sidebar-nav a.manage-trash:hover,
        .sidebar-nav a.manage-trash.active {
            border-left-color: #dc3545;
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
            
            .card-header {
                padding: 20px;
            }
            
            .card-body {
                padding: 20px;
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
                <h3><i class="fas fa-hand-sparkles" style="color: var(--primary-color); margin-right: 8px;"></i>Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Guest'); ?>!</h3>
                <p>Member since <?php echo date('M Y', strtotime($user['member_since'] ?? 'now')); ?></p>
            </div>
            
            <!-- Sidebar Search -->
            <div class="sidebar-search">
                <div class="sidebar-search-input-wrapper">
                    <input type="text" id="sidebarSearchInput" placeholder="Search menu... (e.g., Bookings, Food, Profile)" autocomplete="off">
                    <i class="fas fa-search search-icon" id="searchIcon"></i>
                    <span class="clear-search" id="clearSearch" onclick="clearSidebarSearch()" title="Clear search"><i class="fas fa-times"></i></span>
                    <div class="search-results" id="searchResults"></div>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="sidebar-user">
                <?php if (!empty($user['profile_picture']) && file_exists('../' . $user['profile_picture'])): ?>
                    <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" 
                         style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color);">
                <?php else: ?>
                    <div class="avatar"><?php echo $initials; ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></h4>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="member-since"><i class="fas fa-crown" style="margin-right: 4px;"></i><?php echo number_format($user['loyalty_points'] ?? 0); ?> Points</p>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="sidebar-nav">
                <ul>
                    <!-- Home Section -->
                    <li class="nav-section">Home</li>
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
                    <li>
                        <a href="user-calendar.php" class="<?php echo $currentPage === 'user-calendar' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i> My Calendar
                        </a>
                    </li>
                    
                    <!-- Reservations -->
                    <li class="nav-section">Reservations <i class="fas fa-inbox" style="margin-left: 5px;"></i></li>
                    <li>
                        <a href="inbox.php" class="inbox-all <?php echo $currentPage === 'inbox' ? 'active' : ''; ?>">
                            <i class="fas fa-inbox"></i> Inbox
                            <?php echo getInboxBadge($reservationCounts['all']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="confirmed.php" class="inbox-confirmed <?php echo $currentPage === 'confirmed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Confirmed Bookings
                            <?php echo getInboxBadge($reservationCounts['confirmed']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="checkin.php" class="inbox-checkin <?php echo $currentPage === 'checkin' ? 'active' : ''; ?>">
                            <i class="fas fa-door-open"></i> Check-In
                            <?php echo getInboxBadge($reservationCounts['checked_in']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="checkout.php" class="inbox-checkout <?php echo $currentPage === 'checkout' ? 'active' : ''; ?>">
                            <i class="fas fa-door-closed"></i> Check-Out
                            <?php echo getInboxBadge($reservationCounts['checked_out']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="cancelled.php" class="inbox-cancelled <?php echo $currentPage === 'cancelled' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle"></i> Cancelled
                            <?php echo getInboxBadge($reservationCounts['cancelled']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="completed.php" class="inbox-completed <?php echo $currentPage === 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-double"></i> Completed
                            <?php echo getInboxBadge($reservationCounts['completed']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="pending.php" class="inbox-pending <?php echo $currentPage === 'pending' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> Pending Requests
                            <?php echo getInboxBadge($reservationCounts['pending']); ?>
                        </a>
                    </li>
                    
                    <!-- Bookings -->
                    <li class="nav-section">Bookings</li>
                    <li>
                        <a href="my-bookings.php" class="<?php echo in_array($currentPage, ['my-bookings', 'booking-details']) ? 'active' : ''; ?>">
                            <i class="fas fa-bed"></i> My Room Bookings
                        </a>
                    </li>
                    <li>
                        <a href="my-event-bookings.php" class="<?php echo $currentPage === 'my-event-bookings' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-week"></i> My Event Bookings
                        </a>
                    </li>
                    
                    <!-- Orders -->
                    <li class="nav-section">Orders</li>
                    <li>
                        <a href="my-food-orders.php" class="<?php echo $currentPage === 'my-food-orders' ? 'active' : ''; ?>">
                            <i class="fas fa-utensils"></i> My Food Orders
                        </a>
                    </li>
                    <li>
                        <a href="my-service-requests.php" class="<?php echo in_array($currentPage, ['my-service-requests', 'request-service']) ? 'active' : ''; ?>">
                            <i class="fas fa-concierge-bell"></i> My Service Requests
                        </a>
                    </li>
                    <li>
                        <a href="maintenance-request.php" class="<?php echo $currentPage === 'maintenance-request' ? 'active' : ''; ?>">
                            <i class="fas fa-tools"></i> Maintenance Request
                        </a>
                    </li>

                    <!-- Manage -->
                    <li class="nav-section">Manage</li>
                    <li>
                        <a href="archive.php" class="manage-archive <?php echo $currentPage === 'archive' ? 'active' : ''; ?>">
                            <i class="fas fa-archive"></i> Archive
                            <?php echo getInboxBadge($reservationCounts['archived']); ?>
                        </a>
                    </li>
                    <li>
                        <a href="trash.php" class="manage-trash <?php echo $currentPage === 'trash' ? 'active' : ''; ?>">
                            <i class="fas fa-trash-alt"></i> Trash
                            <?php echo getInboxBadge($reservationCounts['trashed']); ?>
                        </a>
                    </li>
                    
                    <!-- Feedback -->
                    <li class="nav-section">Feedback</li>
                    <li>
                        <a href="my-reviews.php" class="<?php echo $currentPage === 'my-reviews' ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> My Reviews
                        </a>
                    </li>
                    <li>
                        <a href="my-messages.php" class="<?php echo $currentPage === 'my-messages' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope"></i> My Messages
                            <?php if ($unreadCount > 0): ?>
                            <span class="inbox-badge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Account -->
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
                        <a href="javascript:void(0);" class="logout" onclick="openLogoutModal();">
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
                        Welcome back, <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Guest'); ?>!</strong>
                    </span>
                    <div class="header-actions">
                        <div class="notification-bell" onclick="toggleNotifications()" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="badge" id="notification-badge" style="display: none;">0</span>
                        </div>
                        <a href="../index.php" title="Home"><i class="fas fa-home"></i></a>
                        <a href="javascript:void(0);" title="Logout" onclick="openLogoutModal();"><i class="fas fa-sign-out-alt"></i></a>
                    </div>
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
                        <a href="notifications.php">View All</a>
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
                { name: 'Home', keywords: 'home main index', url: '../index.php', icon: 'home', section: 'Home' },
                { name: 'Dashboard Overview', keywords: 'dashboard overview main', url: 'dashboard.php', icon: 'tachometer-alt', section: 'Home' },
                { name: 'My Calendar', keywords: 'calendar schedule events dates', url: 'user-calendar.php', icon: 'calendar-alt', section: 'Home' },
                { name: 'Inbox', keywords: 'inbox messages all', url: 'inbox.php', icon: 'inbox', section: 'Reservations' },
                { name: 'Confirmed Bookings', keywords: 'confirmed bookings reservations', url: 'confirmed.php', icon: 'check-circle', section: 'Reservations' },
                { name: 'Check-In', keywords: 'checkin check-in arrival', url: 'checkin.php', icon: 'door-open', section: 'Reservations' },
                { name: 'Check-Out', keywords: 'checkout check-out departure', url: 'checkout.php', icon: 'door-closed', section: 'Reservations' },
                { name: 'Cancelled', keywords: 'cancelled canceled', url: 'cancelled.php', icon: 'times-circle', section: 'Reservations' },
                { name: 'Completed', keywords: 'completed finished done', url: 'completed.php', icon: 'check-double', section: 'Reservations' },
                { name: 'Pending Requests', keywords: 'pending waiting requests', url: 'pending.php', icon: 'clock', section: 'Reservations' },
                { name: 'My Room Bookings', keywords: 'room bookings rooms hotels stays', url: 'my-bookings.php', icon: 'bed', section: 'Bookings' },
                { name: 'My Event Bookings', keywords: 'event bookings events spaces', url: 'my-event-bookings.php', icon: 'calendar-week', section: 'Bookings' },
                { name: 'My Food Orders', keywords: 'food orders meals dining restaurant', url: 'my-food-orders.php', icon: 'utensils', section: 'Orders' },
                { name: 'My Service Requests', keywords: 'service requests laundry spa wellness dry cleaning massage', url: 'my-service-requests.php', icon: 'concierge-bell', section: 'Orders' },
                { name: 'My Reviews', keywords: 'reviews testimonials feedback ratings', url: 'my-reviews.php', icon: 'star', section: 'Feedback' },
                { name: 'Archive', keywords: 'archive archived history', url: 'archive.php', icon: 'archive', section: 'Manage' },
                { name: 'Trash', keywords: 'trash deleted recycle bin', url: 'trash.php', icon: 'trash-alt', section: 'Manage' },
                { name: 'Profile Settings', keywords: 'profile settings account user', url: 'profile.php', icon: 'user-cog', section: 'Account' },
                { name: 'Change Password', keywords: 'password security', url: 'change-password.php', icon: 'lock', section: 'Account' },
                { name: 'Notifications', keywords: 'notifications alerts', url: 'notifications.php', icon: 'bell', section: 'Account' },
                { name: 'Logout', keywords: 'logout signout exit', url: '../auth/logout.php', icon: 'sign-out-alt', section: 'Account' }
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
