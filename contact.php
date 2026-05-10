<?php
require_once 'includes/header.php';
require_once 'includes/notifications.php';
$pageTitle = __('Contact Us');

$success = '';
$error = '';

// Process contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = __('Please fill in all required fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = __('Please enter a valid email address');
    } else {
        // Save message to database
        $db = getDB();
        $userId = isLoggedIn() ? getUserId() : null;
        $stmt = $db->prepare("INSERT INTO contact_messages (user_id, name, email, phone, subject, message, status, priority, created_at) VALUES (?, ?, ?, ?, ?, ?, 'new', 'medium', NOW())");

        if ($stmt->execute([$userId, $name, $email, $phone, $subject, $message])) {
            $messageId = $db->lastInsertId();
            $success = __('Thank you for your message! We will get back to you within 24 hours.');
            logActivity('Contact form submitted', 'From: ' . $email . ' (Message ID: ' . $messageId . ')');

            // Notify all admin and manager users about new contact message
            createRoleNotifications(
                ['admin', 'manager'],
                'system',
                'New Contact Message',
                'New message from ' . $name . ' regarding ' . $subject,
                [
                    'priority' => 'medium',
                    'action_url' => '/bayawanhotel/admin/admin-contact-messages.php?view=' . $messageId
                ]
            );
        } else {
            $error = __('Failed to send message. Please try again later.');
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><?php echo __('Contact Us'); ?></h1>
        <p><?php echo __('We\'d love to hear from you'); ?></p>
    </div>
</div>

<!-- Contact Section -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 60px;">
            <!-- Contact Info -->
            <div>
                <h2 style="font-size: 32px; margin-bottom: 30px;"><?php echo __('Get in Touch'); ?></h2>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 40px;">
                    <?php echo __('Have questions about your reservation, or need assistance with planning your stay? Our team is here to help you 24/7.'); ?>
                </p>
                
                <div style="margin-bottom: 40px;">
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 50px; height: 50px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 18px; margin-bottom: 5px;">Address</h4>
                            <p style="color: #666; line-height: 1.6;">Bayawan City, Negros Oriental<br>Philippines 6211</p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 50px; height: 50px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 18px; margin-bottom: 5px;">Phone</h4>
                            <p style="color: #666; line-height: 1.6;">
                                Reservations: +63 35 123 4567<br>
                                Front Desk: +63 35 123 4568
                            </p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                        <div style="width: 50px; height: 50px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 18px; margin-bottom: 5px;">Email</h4>
                            <p style="color: #666; line-height: 1.6;">
                                <a href="mailto:bayawanbaiminihotel@gmail.com" style="color: var(--primary-color);">bayawanbaiminihotel@gmail.com</a><br>
                                <a href="mailto:reservations@bayawanbaihotel.com" style="color: var(--primary-color);">reservations@bayawanbaihotel.com</a>
                            </p>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px;">
                        <div style="width: 50px; height: 50px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 18px; margin-bottom: 5px;">Hours</h4>
                            <p style="color: #666; line-height: 1.6;">
                                Front Desk: 24/7<br>
                                Reservations: 8:00 AM - 8:00 PM
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 20px;">Follow Us</h4>
                    <div style="display: flex; gap: 15px;">
                        <a href="#" style="width: 45px; height: 45px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--secondary-color)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.backgroundColor='var(--primary-color)'; this.style.transform='translateY(0)'"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" style="width: 45px; height: 45px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--secondary-color)'; this.style.transform='translateY(-3px)'" onmouseout="this.style.backgroundColor='var(--primary-color)'; this.style.transform='translateY(0)'"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div style="background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <h3 style="font-size: 24px; margin-bottom: 25px;">Send us a Message</h3>
                
                <?php if ($error): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Your Name *</label>
                            <input type="text" name="name" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email Address *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Phone Number</label>
                        <input type="tel" name="phone" style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Subject *</label>
                        <select name="subject" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                            <option value="">Select a subject</option>
                            <option value="reservation">Reservation Inquiry</option>
                            <option value="general">General Inquiry</option>
                            <option value="feedback">Feedback</option>
                            <option value="complaint">Complaint</option>
                            <option value="business">Business/Corporate</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Message *</label>
                        <textarea name="message" rows="6" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section style="padding: 0; height: 450px; position: relative;">
    <!-- Google Maps Embed -->
    <iframe
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d251260.25526696823!2d122.63643545!3d9.63333555!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33ab233fee0c1bc7%3A0x39c1f17ed0d8c6d6!2sBayawan%20City%2C%20Negros%20Oriental!5e0!3m2!1sen!2sph!4v1700000000000!5m2!1sen!2sph"
        width="100%"
        height="100%"
        style="border:0; filter: sepia(20%) saturate(80%);"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>

    <!-- Overlay Content -->
    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(54,125,138,0.3), rgba(54,125,138,0.5)); display: flex; align-items: center; justify-content: center; pointer-events: none;">
        <div style="text-align: center; color: white; pointer-events: auto;">
            <i class="fas fa-map-marker-alt" style="font-size: 70px; margin-bottom: 15px; color: var(--primary-color); text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>
            <h3 style="font-size: 36px; margin-bottom: 10px; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">Find Us in Bayawan City</h3>
            <p style="font-size: 20px; margin-bottom: 25px; color: rgba(255,255,255,0.95); text-shadow: 1px 1px 3px rgba(0,0,0,0.4);">Negros Oriental, Philippines</p>
            <a href="https://maps.google.com/?q=Bayawan+City+Negros+Oriental" target="_blank" class="btn btn-primary" style="background-color: var(--primary-color); border: none; padding: 15px 35px; font-size: 16px;">
                <i class="fas fa-location-arrow"></i> Get Directions
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
