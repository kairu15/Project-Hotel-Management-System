<?php
/**
 * Bayawan Bai Hotel - Newsletter Subscription Handler
 * Subscribe to receive special offers and updates
 */

require_once __DIR__ . '/includes/config.php';

// Initialize response variables
$message = '';
$messageType = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validate email
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        try {
            $db = getDB();
            
            // Create table if not exists (for first-time setup) - MUST do this FIRST
            $createTableSQL = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) UNIQUE NOT NULL,
                first_name VARCHAR(100),
                last_name VARCHAR(100),
                status ENUM('active', 'unsubscribed') DEFAULT 'active',
                subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                unsubscribed_at TIMESTAMP NULL,
                ip_address VARCHAR(45),
                user_agent TEXT
            )";
            $db->exec($createTableSQL);
            
            // Check if email already exists
            $checkStmt = $db->prepare("SELECT subscriber_id FROM newsletter_subscribers WHERE email = ?");
            $checkStmt->execute([$email]);
            
            if ($checkStmt->rowCount() > 0) {
                $message = 'This email is already subscribed to our newsletter.';
                $messageType = 'info';
            } else {
                // Insert new subscriber
                $stmt = $db->prepare("INSERT INTO newsletter_subscribers (email, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmt->execute([
                    $email,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                
                $message = 'Thank you for subscribing! You will now receive special offers and updates from Bayawan Bai Hotel.';
                $messageType = 'success';
                
                // Send welcome email
                sendWelcomeEmail($email);
            }
        } catch (PDOException $e) {
            $message = 'An error occurred. Please try again later.';
            $messageType = 'error';
            error_log("Newsletter subscription error: " . $e->getMessage());
        }
    }
}

/**
 * Send welcome email to new subscriber
 */
function sendWelcomeEmail($email) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Welcome to Bayawan Bai Hotel Newsletter!';
        $mail->Body = getWelcomeEmailTemplate($email);
        $mail->AltBody = 'Thank you for subscribing to the Bayawan Bai Hotel newsletter. You will now receive updates on special offers, promotions, and hotel news.';
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Welcome email failed: " . $e->getMessage());
    }
}

/**
 * Get welcome email HTML template
 */
function getWelcomeEmailTemplate($email) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to Bayawan Bai Hotel Newsletter</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1a3a4a 0%, #2c5364 100%); padding: 30px; text-align: center; color: white; }
            .content { background: #f9f9f9; padding: 30px; }
            .footer { background: #333; color: #fff; padding: 20px; text-align: center; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: #d4a574; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .benefits { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .benefits ul { list-style: none; padding: 0; }
            .benefits li { padding: 10px 0; border-bottom: 1px solid #eee; }
            .benefits li:before { content: "✓"; color: #d4a574; font-weight: bold; margin-right: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Bayawan Bai Hotel</h1>
                <p>Welcome to Our Newsletter!</p>
            </div>
            <div class="content">
                <h2>Thank You for Subscribing!</h2>
                <p>Dear Valued Guest,</p>
                <p>We are thrilled to welcome you to the Bayawan Bai Hotel newsletter community. You are now subscribed to receive:</p>
                
                <div class="benefits">
                    <ul>
                        <li>Exclusive special offers and discounts</li>
                        <li>Early access to seasonal promotions</li>
                        <li>New amenities and services announcements</li>
                        <li>Local events and attractions updates</li>
                        <li>Travel tips for Bayawan City</li>
                    </ul>
                </div>
                
                <p style="text-align: center;">
                    <a href="' . SITE_URL . '/rooms.php" class="button">Explore Our Rooms</a>
                </p>
                
                <p>If you have any questions or need assistance, please don\'t hesitate to contact us at <a href="mailto:' . ADMIN_EMAIL . '">' . ADMIN_EMAIL . '</a> or call us at +63 35 123 4567.</p>
                
                <p>We look forward to welcoming you soon!</p>
                <p>Best regards,<br><strong>The Bayawan Bai Hotel Team</strong></p>
            </div>
            <div class="footer">
                <p>Bayawan Bai Hotel<br>
                Bayawan City, Negros Oriental, Philippines 6211<br>
                <a href="' . SITE_URL . '" style="color: #d4a574;">www.bayawanbaihotel.com</a></p>
                <p style="margin-top: 20px; font-size: 11px;">
                    You received this email because you subscribed to our newsletter.<br>
                    <a href="' . SITE_URL . '/unsubscribe.php?email=' . urlencode($email) . '" style="color: #d4a574;">Unsubscribe</a>
                </p>
            </div>
        </div>
    </body>
    </html>';
}

// Handle AJAX requests differently
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // AJAX request - return JSON
    header('Content-Type: application/json');
    echo json_encode(['message' => $message, 'type' => $messageType]);
    exit;
}

// Regular form submission - show full page with message
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscription - Bayawan Bai Hotel</title>
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
        
        .subscription-card {
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
        
        .benefits {
            text-align: left;
            margin: 30px 0;
        }
        
        .benefits h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }
        
        .benefits ul {
            list-style: none;
        }
        
        .benefits li {
            padding: 8px 0;
            color: #555;
        }
        
        .benefits li i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        @media (max-width: 480px) {
            .subscription-card {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="subscription-card">
        <img src="assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo">
        <h1>Newsletter Subscription</h1>
        <p class="subtitle">Stay updated with special offers and hotel news</p>
        
        <?php if ($message): ?>
            <div class="icon <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            </div>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <?php if ($messageType === 'success'): ?>
                <div class="benefits">
                    <h3>What to Expect:</h3>
                    <ul>
                        <li><i class="fas fa-tag"></i> Exclusive discounts and special offers</li>
                        <li><i class="fas fa-calendar-star"></i> Seasonal promotions and packages</li>
                        <li><i class="fas fa-sparkles"></i> New amenities and services</li>
                        <li><i class="fas fa-map-marker-alt"></i> Local events and attractions</li>
                        <li><i class="fas fa-lightbulb"></i> Travel tips and recommendations</li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="message info">
                <i class="fas fa-paper-plane"></i> Please enter your email address to subscribe to our newsletter.
            </div>
            
            <form action="subscribe.php" method="POST" style="margin-top: 20px;">
                <input type="email" name="email" placeholder="Your email address" required 
                       style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; margin-bottom: 15px;">
                <button type="submit" class="btn" style="width: 100%; border: none; cursor: pointer; font-size: 16px;">
                    Subscribe Now <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            
            <p style="margin-top: 20px; font-size: 12px; color: #666;">
                By subscribing, you agree to receive promotional emails. You can unsubscribe at any time.
            </p>
        <?php endif; ?>
        
        <a href="index.php" class="btn">
            <i class="fas fa-home"></i> Return to Homepage
        </a>
    </div>
</body>
</html>
