<?php
/**
 * Bayawan Bai Hotel - Newsletter Unsubscription Handler
 * Unsubscribe from special offers and updates
 */

require_once __DIR__ . '/includes/config.php';

// Initialize response variables
$message = '';
$messageType = '';

// Get email from URL parameter
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($email)) {
    $message = 'No email address provided.';
    $messageType = 'error';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Invalid email address provided.';
    $messageType = 'error';
} else {
    try {
        $db = getDB();
        
        // Check if table exists first
        $tableCheck = $db->query("SHOW TABLES LIKE 'newsletter_subscribers'");
        
        if ($tableCheck->rowCount() === 0) {
            $message = 'No subscription found for this email address.';
            $messageType = 'info';
        } else {
            // Check if subscriber exists and is active
            $checkStmt = $db->prepare("SELECT subscriber_id, status FROM newsletter_subscribers WHERE email = ?");
            $checkStmt->execute([$email]);
            $subscriber = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$subscriber) {
                $message = 'No subscription found for this email address.';
                $messageType = 'info';
            } elseif ($subscriber['status'] === 'unsubscribed') {
                $message = 'You have already unsubscribed from our newsletter.';
                $messageType = 'info';
            } else {
                // Update subscriber status to unsubscribed
                $stmt = $db->prepare("UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = NOW() WHERE subscriber_id = ?");
                $stmt->execute([$subscriber['subscriber_id']]);
                
                $message = 'You have been successfully unsubscribed from our newsletter. We\'re sorry to see you go!';
                $messageType = 'success';
            }
        }
    } catch (PDOException $e) {
        $message = 'An error occurred. Please try again later.';
        $messageType = 'error';
        error_log("Newsletter unsubscription error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe - Bayawan Bai Hotel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #d4a574;
            --dark-color: #1a3a4a;
            --light-color: #f5f5f5;
            --accent-color: #2c5364;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, var(--dark-color) 0%, var(--accent-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .unsubscribe-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }
        
        h1 {
            font-family: 'Playfair Display', serif;
            color: var(--dark-color);
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 15px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .icon.success { color: #28a745; }
        .icon.error { color: #dc3545; }
        .icon.info { color: #17a2b8; }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #c49360;
            transform: translateY(-2px);
        }
        
        .resubscribe {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        
        .resubscribe h3 {
            color: var(--dark-color);
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        
        .resubscribe p {
            color: #666;
            margin-bottom: 15px;
        }
        
        @media (max-width: 480px) {
            .unsubscribe-card {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="unsubscribe-card">
        <img src="assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo">
        <h1>Newsletter Preferences</h1>
        <p class="subtitle">Manage your email subscription</p>
        
        <div class="icon <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <?php if ($messageType === 'success'): ?>
                <p style="color: #666; font-size: 14px;">
                    Email: <strong><?php echo htmlspecialchars($email); ?></strong>
                </p>
                
                <div class="resubscribe">
                    <h3>Changed Your Mind?</h3>
                    <p>If you unsubscribed by mistake, you can subscribe again anytime.</p>
                    <a href="subscribe.php" class="btn">
                        <i class="fas fa-envelope"></i> Resubscribe
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="index.php" class="btn">
            <i class="fas fa-home"></i> Return to Homepage
        </a>
    </div>
</body>
</html>
