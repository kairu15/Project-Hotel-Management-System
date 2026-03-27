<?php
$pageTitle = 'Events & Meetings';
require_once 'includes/header.php';

// Process event inquiry form
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    
    $eventType = sanitizeInput($_POST['event_type'] ?? '');
    $eventDate = sanitizeInput($_POST['event_date'] ?? '');
    $startTime = sanitizeInput($_POST['start_time'] ?? '');
    $endTime = sanitizeInput($_POST['end_time'] ?? '');
    $guests = (int)($_POST['guests'] ?? 0);
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $cateringRequired = isset($_POST['catering_required']) ? 1 : 0;
    $spaceId = (int)($_POST['space_id'] ?? 0);
    
    if (empty($eventType) || empty($eventDate) || empty($name) || empty($email) || empty($guests)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Check if user is logged in
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            $stmt = $db->prepare("
                INSERT INTO event_bookings 
                (user_id, space_id, event_type, event_date, start_time, end_time, guests_count, 
                catering_required, special_requests, inquiry_name, inquiry_email, inquiry_phone, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $userId,
                $spaceId,
                $eventType,
                $eventDate,
                $startTime ?: null,
                $endTime ?: null,
                $guests,
                $cateringRequired,
                $message,
                $name,
                $email,
                $phone
            ]);
            
            $success = 'Thank you for your inquiry! Our events team will contact you within 24 hours with a customized quotation.';
        } catch (PDOException $e) {
            $error = 'An error occurred while saving your inquiry. Please try again.';
            error_log('Event booking error: ' . $e->getMessage());
        }
    }
}

// Get event spaces from database
$db = getDB();
$spaces = $db->query("SELECT * FROM event_spaces WHERE status = 'available' ORDER BY capacity")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Events & Meetings</h1>
        <p>Host your perfect event with us</p>
    </div>
</div>

<!-- Hero Section -->
<section style="padding: 0; position: relative; height: 500px; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1519167758481-83f550bb49b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white; max-width: 800px; padding: 0 20px;">
        <p style="font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 20px;">Unforgettable Events</p>
        <h2 style="font-size: 48px; color: white; margin-bottom: 25px;">Celebrate Life's Special Moments</h2>
        <p style="font-size: 18px; line-height: 1.8; opacity: 0.9;">From intimate gatherings to grand celebrations, we provide the perfect setting for your events</p>
    </div>
</section>

<!-- Event Types -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">We Host</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Events for Every Occasion</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-heart" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px; transition: color 0.3s;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; transition: color 0.3s;">Weddings</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.6; transition: color 0.3s;">Create your dream wedding in our romantic venues with stunning bay views.</p>
            </div>
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-briefcase" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px; transition: color 0.3s;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; transition: color 0.3s;">Corporate</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.6; transition: color 0.3s;">Professional meeting spaces equipped with modern AV technology.</p>
            </div>
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-birthday-cake" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px; transition: color 0.3s;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; transition: color 0.3s;">Celebrations</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.6; transition: color 0.3s;">Birthdays, anniversaries, and milestone celebrations to remember.</p>
            </div>
            <div style="text-align: center; padding: 40px 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.9)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-users" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px; transition: color 0.3s;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px; transition: color 0.3s;">Social Events</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.6; transition: color 0.3s;">Galas, fundraisers, and community gatherings with impact.</p>
            </div>
        </div>
    </div>
</section>

<!-- Event Spaces -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Venues</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Our Event Spaces</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px;">
            <?php foreach ($spaces as $index => $space): 
                // Get primary image or use default
                $primaryImage = $space['image_primary'] ?? '';
                if ($primaryImage) {
                    // Add assets/ prefix if not already there
                    if (strpos($primaryImage, 'http') !== 0 && strpos($primaryImage, 'assets/') !== 0) {
                        $primaryImage = 'assets/' . $primaryImage;
                    }
                } else {
                    // Default placeholder images
                    $defaultImages = ['1519167758481-83f550bb49b3','1540575462033-afcf0b7f5a67','1531058020387-3be67869e66f','1464369063991-193918d63341'];
                    $primaryImage = 'https://images.unsplash.com/photo-' . $defaultImages[$index % 4] . '?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                }
            ?>
            <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <div style="position: relative; height: 250px; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($primaryImage); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                    <div style="position: absolute; top: 20px; right: 20px; background-color: var(--primary-color); color: white; padding: 10px 20px; border-radius: 5px; font-weight: 600;">
                        Up to <?php echo $space['capacity']; ?> guests
                    </div>
                </div>
                <div style="padding: 30px;">
                    <h3 style="font-size: 24px; margin-bottom: 10px;"><?php echo htmlspecialchars($space['space_name']); ?></h3>
                    <p style="font-size: 15px; color: #666; line-height: 1.7; margin-bottom: 20px;"><?php echo htmlspecialchars($space['description'] ?? 'Perfect venue for your special event'); ?></p>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <span style="font-size: 14px; color: #666;"><i class="fas fa-users" style="color: var(--primary-color); margin-right: 8px;"></i><?php echo $space['capacity']; ?> Capacity</span>
                        <span style="font-size: 14px; color: #666;"><i class="fas fa-vector-square" style="color: var(--primary-color); margin-right: 8px;"></i><?php echo $space['area_sqm']; ?> m²</span>
                        <?php if ($space['price_per_day']): ?>
                        <span style="font-size: 14px; color: #666;"><i class="fas fa-tag" style="color: var(--primary-color); margin-right: 8px;"></i>From <?php echo formatPrice($space['price_per_day']); ?>/day</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($space['features']): ?>
                    <div style="margin-bottom: 20px;">
                        <p style="font-size: 13px; color: #666; margin-bottom: 8px;">Features:</p>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php foreach (explode(', ', $space['features']) as $feature): ?>
                            <span style="background-color: var(--gray-light); padding: 5px 12px; border-radius: 20px; font-size: 12px; color: #666;"><?php echo htmlspecialchars(trim($feature)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <a href="event-space-details.php?id=<?php echo $space['space_id']; ?>" class="btn btn-primary" style="display: block; text-align: center;">View Details & Book</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Services -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Services</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Event Services</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-utensils" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px;">Catering & Dining</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7;">Customized menus crafted by our executive chef, from cocktail receptions to banquets.</p>
            </div>
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-lightbulb" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px;">Event Planning</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7;">Dedicated event coordinators to help plan every detail of your special occasion.</p>
            </div>
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-palette" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px;">Decor & Setup</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7;">Beautiful floral arrangements, linens, and custom decorations to match your theme.</p>
            </div>
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-music" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px;">Audio & Visual</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7;">State-of-the-art sound systems, projectors, and lighting for your presentations.</p>
            </div>
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-bed" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px;">Room Blocks</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7;">Special rates for event attendees with convenient accommodation packages.</p>
            </div>
            <div style="padding: 40px; text-align: center;">
                <i class="fas fa-shuttle-van" style="font-size: 50px; color: var(--primary-color); margin-bottom: 25px;"></i>
                <h4 style="font-size: 22px; margin-bottom: 15px;">Transportation</h4>
                <p style="font-size: 15px; color: #666; line-height: 1.7;">Airport transfers and shuttle services for your guests' convenience.</p>
            </div>
        </div>
    </div>
</section>

<!-- Inquiry Form -->
<section id="inquiry" style="padding: 80px 0; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 40px;">
                <h2 style="color: white; font-size: 36px; margin-bottom: 15px;">Request a Quotation</h2>
                <p style="color: rgba(255,255,255,0.9); font-size: 18px;">Tell us about your event, and we will create a customized package for you.</p>
            </div>
            
            <div style="background-color: white; padding: 50px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
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
                
                <form method="POST" action="#inquiry">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Event Type *</label>
                            <select name="event_type" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                                <option value="">Select event type</option>
                                <option value="wedding">Wedding</option>
                                <option value="corporate">Corporate Meeting</option>
                                <option value="conference">Conference</option>
                                <option value="birthday">Birthday Celebration</option>
                                <option value="anniversary">Anniversary</option>
                                <option value="social">Social Gathering</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Preferred Date *</label>
                            <input type="date" name="event_date" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Start Time</label>
                            <input type="time" name="start_time" style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">End Time</label>
                            <input type="time" name="end_time" style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Number of Guests *</label>
                        <input type="number" name="guests" min="1" required style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Space Selection</label>
                        <select name="space_id" style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                            <option value="">Select preferred space (optional)</option>
                            <?php foreach ($spaces as $space): ?>
                            <option value="<?php echo $space['space_id']; ?>"><?php echo htmlspecialchars($space['space_name']); ?> (Up to <?php echo $space['capacity']; ?> guests)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
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
                        <label style="display: flex; align-items: center; cursor: pointer; font-size: 14px;">
                            <input type="checkbox" name="catering_required" value="1" style="width: 18px; height: 18px; margin-right: 10px;">
                            <span>Catering Required</span>
                        </label>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Additional Details / Special Requests</label>
                        <textarea name="message" rows="4" placeholder="Tell us more about your event requirements..." style="width: 100%; padding: 14px; border: 2px solid var(--gray-medium); border-radius: 8px; font-size: 15px; resize: vertical;"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px;">
                        <i class="fas fa-paper-plane"></i> Submit Inquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function prefillSpace(spaceId, spaceName) {
    document.querySelector('select[name="space_id"]').value = spaceId;
    var textarea = document.querySelector('textarea[name="message"]');
    var currentValue = textarea.value;
    if (currentValue) {
        textarea.value = currentValue + '\n\nI am interested in booking the ' + spaceName + ' for my event.';
    } else {
        textarea.value = 'I am interested in booking the ' + spaceName + ' for my event.';
    }
    document.querySelector('#inquiry').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require_once 'includes/footer.php'; ?>
