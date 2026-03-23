<?php
$pageTitle = 'Welcome';
require_once 'includes/config.php';
require_once 'includes/header.php';

// Get featured rooms
$db = getDB();
$roomsStmt = $db->query("SELECT * FROM room_categories WHERE status = 'active' ORDER BY base_price LIMIT 4");
$featuredRooms = $roomsStmt->fetchAll();

// Get active promotions
$promoStmt = $db->query("SELECT * FROM promotions WHERE is_active = 1 AND (end_date >= CURDATE() OR end_date IS NULL) ORDER BY created_at DESC LIMIT 3");
$promotions = $promoStmt->fetchAll();
?>

<!-- Hero Slider Section -->
<section style="position: relative; height: 100vh; min-height: 600px; overflow: hidden;">
    <div id="heroSlider" style="position: relative; height: 100%;">
        <!-- Slide 1 -->
        <div class="hero-slide active" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat; opacity: 1; transition: opacity 1s;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white; max-width: 800px; padding: 0 20px;">
                <p style="font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 20px; opacity: 0.9;">Welcome to Paradise</p>
                <h1 style="font-size: 64px; color: white; margin-bottom: 25px; line-height: 1.2;">Bayawan Bai Hotel</h1>
                <p style="font-size: 20px; margin-bottom: 40px; opacity: 0.9;">Experience luxury and comfort in the heart of Bayawan City, Negros Oriental</p>
                <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                    <a href="booking.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">Book Your Stay</a>
                    <a href="rooms.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 16px; border-color: white; color: white;">Explore Rooms</a>
                </div>
            </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="hero-slide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1582719508461-905c673771fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat; opacity: 0; transition: opacity 1s;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white; max-width: 800px; padding: 0 20px;">
                <p style="font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 20px; opacity: 0.9;">Unforgettable Experience</p>
                <h1 style="font-size: 64px; color: white; margin-bottom: 25px; line-height: 1.2;">Escape to Paradise</h1>
                <p style="font-size: 20px; margin-bottom: 40px; opacity: 0.9;">Discover pristine beaches, stunning ocean views, and world-class hospitality</p>
                <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                    <a href="booking.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">Book Now</a>
                    <a href="amenities.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 16px; border-color: white; color: white;">Our Amenities</a>
                </div>
            </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="hero-slide" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat; opacity: 0; transition: opacity 1s;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white; max-width: 800px; padding: 0 20px;">
                <p style="font-size: 18px; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 20px; opacity: 0.9;">Culinary Excellence</p>
                <h1 style="font-size: 64px; color: white; margin-bottom: 25px; line-height: 1.2;">Savor the Flavors</h1>
                <p style="font-size: 20px; margin-bottom: 40px; opacity: 0.9;">Experience authentic Negros Oriental cuisine at our world-class restaurants</p>
                <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
                    <a href="dining.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">View Dining</a>
                    <a href="booking.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 16px; border-color: white; color: white;">Reserve a Table</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Slider Controls -->
    <button onclick="changeSlide(-1)" style="position: absolute; left: 30px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: white; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 20px; transition: all 0.3s; z-index: 10;">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button onclick="changeSlide(1)" style="position: absolute; right: 30px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.2); border: none; color: white; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; font-size: 20px; transition: all 0.3s; z-index: 10;">
        <i class="fas fa-chevron-right"></i>
    </button>
    
    <!-- Slider Dots -->
    <div style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; z-index: 10;">
        <span class="slide-dot active" onclick="goToSlide(0)" style="width: 12px; height: 12px; background: rgba(255,255,255,0.5); border-radius: 50%; cursor: pointer; transition: all 0.3s;"></span>
        <span class="slide-dot" onclick="goToSlide(1)" style="width: 12px; height: 12px; background: rgba(255,255,255,0.5); border-radius: 50%; cursor: pointer; transition: all 0.3s;"></span>
        <span class="slide-dot" onclick="goToSlide(2)" style="width: 12px; height: 12px; background: rgba(255,255,255,0.5); border-radius: 50%; cursor: pointer; transition: all 0.3s;"></span>
    </div>
</section>

<style>
/* Hero Section Responsive */
@media (max-width: 768px) {
    section[style*="height: 100vh"] {
        height: 80vh;
        min-height: 500px;
    }
    
    .hero-slide h1 {
        font-size: 42px !important;
        margin-bottom: 20px !important;
    }
    
    .hero-slide p {
        font-size: 16px !important;
        margin-bottom: 30px !important;
    }
    
    .hero-slide p:first-child {
        font-size: 14px !important;
        margin-bottom: 15px !important;
    }
    
    .hero-slide .btn {
        padding: 12px 25px !important;
        font-size: 14px !important;
    }
    
    button[onclick*="changeSlide"] {
        width: 40px !important;
        height: 40px !important;
        font-size: 16px !important;
        left: 15px !important;
    }
    
    button[onclick*="changeSlide"]:last-of-type {
        left: auto !important;
        right: 15px !important;
    }
}

@media (max-width: 576px) {
    section[style*="height: 100vh"] {
        height: 70vh;
        min-height: 450px;
    }
    
    .hero-slide h1 {
        font-size: 32px !important;
        margin-bottom: 15px !important;
    }
    
    .hero-slide p {
        font-size: 14px !important;
        margin-bottom: 25px !important;
    }
    
    .hero-slide p:first-child {
        font-size: 12px !important;
        margin-bottom: 10px !important;
        letter-spacing: 1px !important;
    }
    
    .hero-slide .btn {
        padding: 10px 20px !important;
        font-size: 13px !important;
    }
    
    .hero-slide div[style*="display: flex"] {
        gap: 15px !important;
    }
    
    button[onclick*="changeSlide"] {
        width: 35px !important;
        height: 35px !important;
        font-size: 14px !important;
    }
    
    div[style*="position: absolute; bottom: 30px"] {
        bottom: 20px !important;
        gap: 8px !important;
    }
    
    .slide-dot {
        width: 10px !important;
        height: 10px !important;
    }
}
</style>

<!-- Quick Booking Form -->
<section style="background-color: var(--primary-color); padding: 40px 0;">
    <div class="container">
        <form action="availability.php" method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr) auto; gap: 20px; align-items: end;">
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Check-in Date</label>
                <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px;">
            </div>
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Check-out Date</label>
                <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px;">
            </div>
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Guests</label>
                <select name="guests" style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px; background-color: white;">
                    <option value="1">1 Guest</option>
                    <option value="2" selected>2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4 Guests</option>
                    <option value="5">5+ Guests</option>
                </select>
            </div>
            <div>
                <label style="display: block; color: white; margin-bottom: 8px; font-size: 14px;">Room Type</label>
                <select name="room_type" style="width: 100%; padding: 15px; border: none; border-radius: 5px; font-size: 15px; background-color: white;">
                    <option value="">All Rooms</option>
                    <?php foreach ($featuredRooms as $room): ?>
                    <option value="<?php echo $room['category_id']; ?>"><?php echo htmlspecialchars($room['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-dark" style="padding: 15px 30px; white-space: nowrap;">
                    <i class="fas fa-search"></i> Check Availability
                </button>
            </div>
        </form>
    </div>
</section>

<style>
/* Quick Booking Form Responsive */
@media (max-width: 992px) {
    section[style*="background-color: var(--primary-color)"] form {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    section[style*="background-color: var(--primary-color)"] form > div:last-child {
        grid-column: span 2;
    }
}

@media (max-width: 768px) {
    section[style*="background-color: var(--primary-color)"] {
        padding: 30px 0;
    }
    
    section[style*="background-color: var(--primary-color)"] form {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    section[style*="background-color: var(--primary-color)"] form > div:last-child {
        grid-column: span 1;
    }
    
    section[style*="background-color: var(--primary-color)"] input,
    section[style*="background-color: var(--primary-color)"] select {
        padding: 12px;
        font-size: 14px;
    }
    
    section[style*="background-color: var(--primary-color)"] label {
        font-size: 13px;
        margin-bottom: 6px;
    }
}

@media (max-width: 576px) {
    section[style*="background-color: var(--primary-color)"] {
        padding: 20px 0;
    }
    
    section[style*="background-color: var(--primary-color)"] input,
    section[style*="background-color: var(--primary-color)"] select {
        padding: 10px;
        font-size: 13px;
    }
    
    section[style*="background-color: var(--primary-color)"] button {
        padding: 12px 20px;
        font-size: 13px;
    }
}

/* Welcome Section Responsive */
@media (max-width: 992px) {
    section[style*="background-color: var(--light-color)"] > .container > div {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    section[style*="background-color: var(--light-color)"] > .container > div > div:last-child {
        order: -1;
    }
    
    section[style*="background-color: var(--light-color)"] > .container > div > div:last-child > div {
        bottom: -20px;
        left: -20px;
        padding: 20px;
    }
}

@media (max-width: 768px) {
    section[style*="background-color: var(--light-color)"] {
        padding: 60px 0;
    }
    
    section[style*="background-color: var(--light-color)"] h2 {
        font-size: 32px;
        margin-bottom: 20px;
    }
    
    section[style*="background-color: var(--light-color)"] p {
        font-size: 15px;
    }
    
    section[style*="background-color: var(--light-color)"] div[style*="display: flex; gap: 30px"] {
        gap: 20px;
    }
    
    section[style*="background-color: var(--light-color)"] div[style*="text-align: center"] h3 {
        font-size: 28px;
    }
}

@media (max-width: 576px) {
    section[style*="background-color: var(--light-color)"] {
        padding: 40px 0;
    }
    
    section[style*="background-color: var(--light-color)"] h2 {
        font-size: 28px;
        margin-bottom: 15px;
    }
    
    section[style*="background-color: var(--light-color)"] p {
        font-size: 14px;
    }
    
    section[style*="background-color: var(--light-color)"] div[style*="display: flex; gap: 30px"] {
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }
    
    section[style*="background-color: var(--light-color)"] > .container > div > div:last-child > div {
        position: static;
        margin-top: 20px;
        text-align: center;
    }
    
    section[style*="background-color: var(--light-color)"] > .container > div > div:last-child > div i {
        font-size: 30px;
    }
}

/* Featured Rooms Responsive */
@media (max-width: 1200px) {
    section[style*="background-color: var(--gray-light)"] > .container > div:last-child {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 992px) {
    section[style*="background-color: var(--gray-light)"] > .container > div:last-child {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    section[style*="background-color: var(--gray-light)"] {
        padding: 60px 0;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:first-child h2 {
        font-size: 32px;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:first-child p {
        font-size: 15px;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:last-child {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 576px) {
    section[style*="background-color: var(--gray-light)"] {
        padding: 40px 0;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:first-child h2 {
        font-size: 28px;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:first-child p {
        font-size: 14px;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:last-child > div {
        margin-bottom: 15px;
    }
    
    section[style*="background-color: var(--gray-light)"] > .container > div:last-child > div > div {
        height: 180px;
    }
}
</style>

<!-- Welcome Section -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;">
            <div>
                <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">About Our Hotel</p>
                <h2 style="font-size: 42px; margin-bottom: 25px;">Experience the Beauty of Bayawan City</h2>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 20px;">
                    Nestled in the heart of Bayawan City, Negros Oriental, Bayawan Bai Hotel offers a perfect blend of modern luxury and natural beauty. Our hotel is inspired by the rich cultural heritage and stunning coastal landscapes of this beautiful region.
                </p>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 30px;">
                    Whether you're here for business or leisure, our dedicated team ensures an unforgettable stay with personalized service, world-class amenities, and easy access to local attractions like Danjugan Island and Mt. Talinis.
                </p>
                <div style="display: flex; gap: 30px; margin-bottom: 30px;">
                    <div style="text-align: center;">
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">50+</h3>
                        <p style="font-size: 14px; color: #666;">Luxury Rooms</p>
                    </div>
                    <div style="text-align: center;">
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">15+</h3>
                        <p style="font-size: 14px; color: #666;">Years Experience</p>
                    </div>
                    <div style="text-align: center;">
                        <h3 style="font-size: 36px; color: var(--primary-color); margin-bottom: 5px;">10k+</h3>
                        <p style="font-size: 14px; color: #666;">Happy Guests</p>
                    </div>
                </div>
                <a href="about.php" class="btn btn-outline">Learn More About Us</a>
            </div>
            <div style="position: relative;">
                <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Hotel View" style="width: 100%; border-radius: 10px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <div style="position: absolute; bottom: -30px; left: -30px; background-color: var(--primary-color); color: white; padding: 30px; border-radius: 10px;">
                    <i class="fas fa-award" style="font-size: 40px; margin-bottom: 10px;"></i>
                    <p style="font-size: 14px;">Award Winning<br>Hospitality 2024</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Rooms Section -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Accommodations</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Our Rooms & Suites</h2>
            <p style="font-size: 16px; color: #666; max-width: 600px; margin: 0 auto;">Choose from our selection of beautifully designed rooms and suites, each offering comfort and elegance.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
            <?php foreach ($featuredRooms as $index => $room): ?>
            <div style="background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.08); transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-10px)'; this.style.boxShadow='0 15px 40px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.08)';">
                <div style="position: relative; overflow: hidden; height: 220px;">
                    <img src="https://images.unsplash.com/photo-<?php echo ['1631049307260-da0c0f11336a','1566666208517-13f42e1e3c2c','1590490360182-c33d57733427','1582719478250-c89cae141e86'][$index] ?>?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="<?php echo htmlspecialchars($room['category_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)';" onmouseout="this.style.transform='scale(1)';">
                    <div style="position: absolute; top: 15px; right: 15px; background-color: var(--primary-color); color: white; padding: 8px 15px; border-radius: 5px; font-size: 14px; font-weight: 600;">
                        <?php echo formatPrice($room['base_price']); ?>/night
                    </div>
                </div>
                <div style="padding: 25px;">
                    <h3 style="font-size: 22px; margin-bottom: 10px;"><?php echo htmlspecialchars($room['category_name']); ?></h3>
                    <div style="display: flex; gap: 15px; margin-bottom: 15px; font-size: 13px; color: #666;">
                        <span><i class="fas fa-user" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo $room['max_occupancy']; ?> Guests</span>
                        <span><i class="fas fa-vector-square" style="color: var(--primary-color); margin-right: 5px;"></i> <?php echo $room['room_size_sqm']; ?> m²</span>
                    </div>
                    <p style="font-size: 14px; color: #666; line-height: 1.6; margin-bottom: 20px;"><?php echo substr(htmlspecialchars($room['description']), 0, 100) . '...'; ?></p>
                    <div style="display: flex; gap: 10px;">
                        <a href="room-details.php?id=<?php echo $room['category_id']; ?>" class="btn btn-outline btn-sm" style="flex: 1; text-align: center;">Details</a>
                        <a href="booking.php?room=<?php echo $room['category_id']; ?>" class="btn btn-primary btn-sm" style="flex: 1; text-align: center;">Book Now</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="rooms.php" class="btn btn-outline">View All Rooms</a>
        </div>
    </div>
</section>

<!-- Amenities Section -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Facilities</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Hotel Amenities</h2>
            <p style="font-size: 16px; color: #666; max-width: 600px; margin: 0 auto;">Enjoy our world-class facilities designed for your comfort and relaxation.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 30px;">
            <div style="text-align: center; padding: 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.8)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-swimming-pool" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px; transition: color 0.3s;"></i>
                <h4 style="font-size: 18px; margin-bottom: 10px; transition: color 0.3s;">Infinity Pool</h4>
                <p style="font-size: 14px; color: #666; transition: color 0.3s;">Stunning pool with bay views</p>
            </div>
            <div style="text-align: center; padding: 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.8)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-spa" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px; transition: color 0.3s;"></i>
                <h4 style="font-size: 18px; margin-bottom: 10px; transition: color 0.3s;">Spa & Wellness</h4>
                <p style="font-size: 14px; color: #666; transition: color 0.3s;">Rejuvenating treatments</p>
            </div>
            <div style="text-align: center; padding: 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.8)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-dumbbell" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px; transition: color 0.3s;"></i>
                <h4 style="font-size: 18px; margin-bottom: 10px; transition: color 0.3s;">Fitness Center</h4>
                <p style="font-size: 14px; color: #666; transition: color 0.3s;">24/7 modern equipment</p>
            </div>
            <div style="text-align: center; padding: 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.8)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-utensils" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px; transition: color 0.3s;"></i>
                <h4 style="font-size: 18px; margin-bottom: 10px; transition: color 0.3s;">Fine Dining</h4>
                <p style="font-size: 14px; color: #666; transition: color 0.3s;">Exquisite cuisine</p>
            </div>
            <div style="text-align: center; padding: 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.8)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-wifi" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px; transition: color 0.3s;"></i>
                <h4 style="font-size: 18px; margin-bottom: 10px; transition: color 0.3s;">Free WiFi</h4>
                <p style="font-size: 14px; color: #666; transition: color 0.3s;">High-speed internet</p>
            </div>
            <div style="text-align: center; padding: 30px; background-color: var(--gray-light); border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.backgroundColor='var(--primary-color)'; this.querySelector('i').style.color='white'; this.querySelector('h4').style.color='white'; this.querySelector('p').style.color='rgba(255,255,255,0.8)';" onmouseout="this.style.backgroundColor='var(--gray-light)'; this.querySelector('i').style.color='var(--primary-color)'; this.querySelector('h4').style.color='var(--dark-color)'; this.querySelector('p').style.color='#666';">
                <i class="fas fa-concierge-bell" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px; transition: color 0.3s;"></i>
                <h4 style="font-size: 18px; margin-bottom: 10px; transition: color 0.3s;">24/7 Service</h4>
                <p style="font-size: 14px; color: #666; transition: color 0.3s;">Always at your service</p>
            </div>
        </div>
    </div>
</section>

<!-- Promotions Section -->
<?php if (count($promotions) > 0): ?>
<section style="padding: 80px 0; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: rgba(255,255,255,0.8); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Special Offers</p>
            <h2 style="font-size: 42px; margin-bottom: 20px; color: white;">Current Promotions</h2>
            <p style="font-size: 16px; color: rgba(255,255,255,0.8); max-width: 600px; margin: 0 auto;">Take advantage of our exclusive deals and save on your next stay.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <?php foreach ($promotions as $promo): ?>
            <div style="background-color: white; border-radius: 10px; padding: 40px; text-align: center; position: relative; overflow: hidden;">
                <?php if ($promo['discount_percent']): ?>
                <div style="position: absolute; top: 20px; right: -35px; background-color: var(--danger-color); color: white; padding: 8px 40px; transform: rotate(45deg); font-weight: 600; font-size: 14px;">
                    -<?php echo $promo['discount_percent']; ?>%
                </div>
                <?php endif; ?>
                <i class="fas fa-tag" style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="font-size: 24px; margin-bottom: 15px;"><?php echo htmlspecialchars($promo['title']); ?></h3>
                <p style="font-size: 15px; color: #666; line-height: 1.6; margin-bottom: 20px;"><?php echo htmlspecialchars($promo['description']); ?></p>
                <?php if ($promo['promo_code']): ?>
                <div style="background-color: var(--gray-light); padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p style="font-size: 13px; color: #666; margin-bottom: 5px;">Use Promo Code:</p>
                    <code style="font-size: 20px; color: var(--primary-color); font-weight: 600;"><?php echo htmlspecialchars($promo['promo_code']); ?></code>
                </div>
                <?php endif; ?>
                <a href="booking.php?promo=<?php echo $promo['promo_code']; ?>" class="btn btn-primary">Book Now</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials Section -->
<section style="padding: 80px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Testimonials</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">What Our Guests Say</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
            <div style="background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div style="color: var(--warning-color); margin-bottom: 20px;">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 25px; font-style: italic;">
                    "An absolutely stunning hotel with incredible views of Bayawan Bay. The staff went above and beyond to make our anniversary special. The spa treatments were divine!"
                </p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 60px; height: 60px; background-color: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: 600;">MR</div>
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 3px;">Maria Rodriguez</h4>
                        <p style="font-size: 14px; color: #999;">Manila, Philippines</p>
                    </div>
                </div>
            </div>
            
            <div style="background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div style="color: var(--warning-color); margin-bottom: 20px;">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 25px; font-style: italic;">
                    "Perfect location for exploring Negros Oriental. We visited Danjugan Island and Mt. Talinis - both easily accessible from the hotel. The restaurant serves amazing local cuisine!"
                </p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 60px; height: 60px; background-color: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: 600;">JC</div>
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 3px;">John Chen</h4>
                        <p style="font-size: 14px; color: #999;">Singapore</p>
                    </div>
                </div>
            </div>
            
            <div style="background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div style="color: var(--warning-color); margin-bottom: 20px;">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <p style="font-size: 16px; color: #666; line-height: 1.8; margin-bottom: 25px; font-style: italic;">
                    "We hosted our company retreat here and everything was perfect. The conference facilities are excellent and the team-building activities arranged by the hotel were fantastic."
                </p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 60px; height: 60px; background-color: var(--dark-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: 600;">SL</div>
                    <div>
                        <h4 style="font-size: 18px; margin-bottom: 3px;">Sarah Lim</h4>
                        <p style="font-size: 14px; color: #999;">Cebu City, Philippines</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Gallery Preview Section -->
<section style="padding: 80px 0; background-color: var(--light-color);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <p style="color: var(--primary-color); font-weight: 600; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 15px;">Gallery</p>
            <h2 style="font-size: 42px; margin-bottom: 20px;">Explore Our Hotel</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
            <div style="grid-column: span 2; grid-row: span 2; position: relative; overflow: hidden; border-radius: 10px; height: 400px;">
                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,0.7)); padding: 30px; color: white;">
                    <h4 style="color: white; font-size: 20px;">Hotel Exterior</h4>
                </div>
            </div>
            <div style="position: relative; overflow: hidden; border-radius: 10px; height: 192px;">
                <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            </div>
            <div style="position: relative; overflow: hidden; border-radius: 10px; height: 192px;">
                <img src="https://images.unsplash.com/photo-1582719508461-905c673771fd?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            </div>
            <div style="position: relative; overflow: hidden; border-radius: 10px; height: 192px;">
                <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            </div>
            <div style="position: relative; overflow: hidden; border-radius: 10px; height: 192px;">
                <img src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="gallery.php" class="btn btn-outline">View Full Gallery</a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="padding: 100px 0; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover fixed no-repeat;">
    <div class="container" style="text-align: center;">
        <h2 style="font-size: 48px; color: white; margin-bottom: 20px;">Ready to Experience Bayawan?</h2>
        <p style="font-size: 20px; color: rgba(255,255,255,0.9); margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">Book your stay today and discover the beauty of Negros Oriental with world-class hospitality.</p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="booking.php" class="btn btn-primary" style="padding: 18px 50px; font-size: 18px;">Book Your Stay Now</a>
            <a href="contact.php" class="btn btn-outline" style="padding: 18px 50px; font-size: 18px; border-color: white; color: white;">Contact Us</a>
        </div>
    </div>
</section>

<!-- Hero Slider Script -->
<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slide-dot');
    const totalSlides = slides.length;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.style.opacity = i === index ? '1' : '0';
        });
        dots.forEach((dot, i) => {
            dot.style.background = i === index ? 'white' : 'rgba(255,255,255,0.5)';
            dot.style.transform = i === index ? 'scale(1.3)' : 'scale(1)';
        });
    }
    
    function changeSlide(direction) {
        currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
        showSlide(currentSlide);
    }
    
    function goToSlide(index) {
        currentSlide = index;
        showSlide(currentSlide);
    }
    
    // Auto-advance slides
    setInterval(() => {
        changeSlide(1);
    }, 6000);
    
    // Initialize first slide
    showSlide(0);
</script>

<?php require_once 'includes/footer.php'; ?>
