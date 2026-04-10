-- =====================================================

-- Bayawan Bai Hotel Management System - Database Schema

-- =====================================================

 

-- Create Database

CREATE DATABASE IF NOT EXISTS bayawan_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE bayawan_hotel;

 

-- =====================================================

-- CORE TABLES

-- =====================================================

 

-- Users Table (Guests, Staff, Admins)

CREATE TABLE users (

    user_id INT AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(255) UNIQUE NOT NULL,

    password VARCHAR(255) NOT NULL,

    first_name VARCHAR(100) NOT NULL,

    last_name VARCHAR(100) NOT NULL,

    phone VARCHAR(20),

    address TEXT,

    city VARCHAR(100),

    country VARCHAR(100),

    role ENUM('guest', 'receptionist', 'manager', 'admin') DEFAULT 'guest',

    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',

    email_verified BOOLEAN DEFAULT FALSE,

    loyalty_points INT DEFAULT 0,

    member_since DATE DEFAULT CURRENT_DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    last_login TIMESTAMP NULL

);

 

-- Room Categories Table

CREATE TABLE room_categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL,

    description TEXT,

    base_price DECIMAL(10,2) NOT NULL,

    max_occupancy INT NOT NULL,

    bed_type VARCHAR(50),

    room_size_sqm INT,

    amenities TEXT,

    image_primary VARCHAR(255),

    images_gallery TEXT,

    status ENUM('active', 'inactive') DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

 

-- Rooms Table

CREATE TABLE rooms (

    room_id INT AUTO_INCREMENT PRIMARY KEY,

    room_number VARCHAR(20) UNIQUE NOT NULL,

    category_id INT NOT NULL,

    floor INT,

    status ENUM('available', 'occupied', 'maintenance', 'cleaning', 'reserved') DEFAULT 'available',

    housekeeping_status ENUM('clean', 'dirty', 'inspected') DEFAULT 'clean',

    special_features TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES room_categories(category_id)

);

 

-- Bookings Table

CREATE TABLE bookings (

    booking_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    room_id INT,

    category_id INT NOT NULL,

    check_in DATE NOT NULL,

    check_out DATE NOT NULL,

    adults INT DEFAULT 1,

    children INT DEFAULT 0,

    nights INT NOT NULL,

    room_rate DECIMAL(10,2) NOT NULL,

    total_amount DECIMAL(10,2) NOT NULL,

    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',

    payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',

    payment_method ENUM('gcash', 'paypal', 'credit_card', 'cash', 'bank_transfer') DEFAULT 'cash',

    special_requests TEXT,

    booking_source ENUM('website', 'walk_in', 'phone', 'ota') DEFAULT 'website',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    checked_in_at TIMESTAMP NULL,

    checked_out_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    FOREIGN KEY (room_id) REFERENCES rooms(room_id),

    FOREIGN KEY (category_id) REFERENCES room_categories(category_id)

);



-- Payments Table

CREATE TABLE payments (

    payment_id INT AUTO_INCREMENT PRIMARY KEY,

    booking_id INT NOT NULL,

    user_id INT NOT NULL,

    amount DECIMAL(10,2) NOT NULL,

    payment_method ENUM('gcash', 'paypal', 'credit_card', 'cash', 'bank_transfer') NOT NULL,

    transaction_id VARCHAR(255),

    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',

    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    notes TEXT,

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),

    FOREIGN KEY (user_id) REFERENCES users(user_id)

);



-- Booking Additional Charges Table

CREATE TABLE booking_charges (

    charge_id INT AUTO_INCREMENT PRIMARY KEY,

    booking_id INT NOT NULL,

    description VARCHAR(255) NOT NULL,

    amount DECIMAL(10,2) NOT NULL,

    charge_type ENUM('minibar', 'room_service', 'laundry', 'damage', 'late_checkout', 'other') DEFAULT 'other',

    status ENUM('active', 'waived', 'paid') DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    created_by INT,

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),

    FOREIGN KEY (created_by) REFERENCES users(user_id)

);



-- =====================================================

-- HOTEL SERVICES TABLES

-- =====================================================



-- Dining/Restaurant Menu

CREATE TABLE menu_categories (

    cat_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL,

    description TEXT,

    sort_order INT DEFAULT 0

);

 

CREATE TABLE menu_items (

    item_id INT AUTO_INCREMENT PRIMARY KEY,

    cat_id INT NOT NULL,

    item_name VARCHAR(200) NOT NULL,

    description TEXT,

    price DECIMAL(10,2) NOT NULL,

    image VARCHAR(255),

    is_special BOOLEAN DEFAULT FALSE,

    is_available BOOLEAN DEFAULT TRUE,

    dietary_info TEXT,

    FOREIGN KEY (cat_id) REFERENCES menu_categories(cat_id)

);

 

-- Amenities/Spa Services

CREATE TABLE amenities (

    amenity_id INT AUTO_INCREMENT PRIMARY KEY,

    amenity_name VARCHAR(100) NOT NULL,

    category ENUM('spa', 'gym', 'pool', 'wellness', 'other') NOT NULL,

    description TEXT,

    price DECIMAL(10,2),

    duration_minutes INT,

    image VARCHAR(255),

    is_available BOOLEAN DEFAULT TRUE,

    operating_hours VARCHAR(100)

);

 

-- Events/Meetings

CREATE TABLE event_spaces (

    space_id INT AUTO_INCREMENT PRIMARY KEY,

    space_name VARCHAR(100) NOT NULL,

    description TEXT,

    capacity INT NOT NULL,

    area_sqm INT,

    features TEXT,

    price_per_day DECIMAL(10,2),

    images TEXT,

    status ENUM('available', 'booked', 'maintenance') DEFAULT 'available'

);

 

-- Event Bookings

CREATE TABLE event_bookings (

    event_booking_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    space_id INT NOT NULL,

    event_type VARCHAR(100),

    event_date DATE NOT NULL,

    start_time TIME,

    end_time TIME,

    guests_count INT,

    catering_required BOOLEAN DEFAULT FALSE,

    special_requests TEXT,

    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',

    quoted_price DECIMAL(10,2),

    -- Fields for non-registered user inquiries

    inquiry_name VARCHAR(200),

    inquiry_email VARCHAR(255),

    inquiry_phone VARCHAR(20),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    FOREIGN KEY (space_id) REFERENCES event_spaces(space_id)

);



-- Events Table (similar structure to rooms table)

CREATE TABLE events (

    event_id INT AUTO_INCREMENT PRIMARY KEY,

    event_name VARCHAR(200) NOT NULL,

    category_id INT,

    floor INT,

    status ENUM('available', 'reserved', 'occupied') DEFAULT 'available',

    maintenance_status ENUM('clean', 'under_maintenance') DEFAULT 'clean',

    special_features TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES room_categories(category_id)

);



-- Gallery

CREATE TABLE gallery (

    image_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(200),

    description TEXT,

    image_path VARCHAR(255) NOT NULL,

    category ENUM('rooms', 'dining', 'amenities', 'events', 'attractions', 'hotel') DEFAULT 'hotel',

    is_featured BOOLEAN DEFAULT FALSE,

    sort_order INT DEFAULT 0,

    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

 

-- Reviews/Ratings

CREATE TABLE reviews (

    review_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    booking_id INT,

    rating INT CHECK (rating >= 1 AND rating <= 5),

    review_text TEXT,

    category ENUM('room', 'dining', 'service', 'amenities', 'overall') DEFAULT 'overall',

    is_approved BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id)

);

 

-- =====================================================

-- CONTENT MANAGEMENT TABLES

-- =====================================================

 

-- Homepage Slider

CREATE TABLE homepage_slider (

    slide_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(200),

    subtitle TEXT,

    image VARCHAR(255) NOT NULL,

    button_text VARCHAR(50),

    button_link VARCHAR(255),

    sort_order INT DEFAULT 0,

    is_active BOOLEAN DEFAULT TRUE

);

 

-- Special Offers/Promotions

CREATE TABLE promotions (

    promo_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(200) NOT NULL,

    description TEXT,

    image VARCHAR(255),

    discount_percent INT,

    discount_amount DECIMAL(10,2),

    promo_code VARCHAR(50),

    start_date DATE,

    end_date DATE,

    min_nights INT DEFAULT 1,

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);

 

-- FAQs

CREATE TABLE faqs (

    faq_id INT AUTO_INCREMENT PRIMARY KEY,

    question TEXT NOT NULL,

    answer TEXT NOT NULL,

    category VARCHAR(100),

    sort_order INT DEFAULT 0,

    is_active BOOLEAN DEFAULT TRUE

);

 

-- =====================================================

-- STAFF & OPERATIONS TABLES

-- =====================================================

 

-- Staff Shifts/Schedule

CREATE TABLE staff_schedules (

    schedule_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    work_date DATE NOT NULL,

    shift_start TIME,

    shift_end TIME,

    role VARCHAR(50),

    status ENUM('scheduled', 'completed', 'absent', 'leave') DEFAULT 'scheduled',

    notes TEXT,

    FOREIGN KEY (user_id) REFERENCES users(user_id)

);

 

-- Maintenance Requests

CREATE TABLE maintenance_requests (

    request_id INT AUTO_INCREMENT PRIMARY KEY,

    room_id INT,

    reported_by INT,

    issue_type ENUM('plumbing', 'electrical', 'hvac', 'furniture', 'appliance', 'other') NOT NULL,

    description TEXT NOT NULL,

    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',

    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    resolved_at TIMESTAMP NULL,

    FOREIGN KEY (room_id) REFERENCES rooms(room_id),

    FOREIGN KEY (reported_by) REFERENCES users(user_id)

);

 

-- Inventory

CREATE TABLE inventory_categories (

    inv_cat_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL

);

 

CREATE TABLE inventory_items (

    item_id INT AUTO_INCREMENT PRIMARY KEY,

    inv_cat_id INT NOT NULL,

    item_name VARCHAR(200) NOT NULL,

    description TEXT,

    unit VARCHAR(50),

    quantity INT DEFAULT 0,

    reorder_level INT DEFAULT 10,

    unit_cost DECIMAL(10,2),

    supplier VARCHAR(200),

    FOREIGN KEY (inv_cat_id) REFERENCES inventory_categories(inv_cat_id)

);

 

-- =====================================================

-- SYSTEM TABLES

-- =====================================================

 

-- Hotel Settings

CREATE TABLE settings (

    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(100) UNIQUE NOT NULL,

    setting_value TEXT,

    setting_group VARCHAR(50)

);

 

-- Email/SMS Logs

CREATE TABLE notification_logs (

    log_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT,

    type ENUM('email', 'sms') NOT NULL,

    subject VARCHAR(255),

    content TEXT,

    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',

    sent_at TIMESTAMP NULL,

    error_message TEXT

);

 

-- User Sessions

CREATE TABLE user_sessions (

    session_id VARCHAR(255) PRIMARY KEY,

    user_id INT NOT NULL,

    ip_address VARCHAR(45),

    user_agent TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    expires_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id)

);



-- Booking Logs (for tracking booking status changes and activity)

CREATE TABLE booking_logs (

    log_id INT AUTO_INCREMENT PRIMARY KEY,

    booking_id INT NOT NULL,

    action VARCHAR(50) NOT NULL,

    details TEXT,

    created_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),

    FOREIGN KEY (created_by) REFERENCES users(user_id)

);



-- Staff Permissions Table

CREATE TABLE staff_permissions (

    permission_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    page_name VARCHAR(100) NOT NULL,

    can_access BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    UNIQUE KEY unique_user_page (user_id, page_name)

);



-- Staff Permission Settings Table (for global settings)

CREATE TABLE staff_permission_settings (

    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    setting_name VARCHAR(100) UNIQUE NOT NULL,

    setting_value TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

);



-- =====================================================

-- INSERT SAMPLE DATA

-- =====================================================

 

-- Sample Admin User (password: admin123)

INSERT INTO users (email, password, first_name, last_name, phone, role, status) VALUES

('admin@bayawanbaihotel.com', 'admin123', 'Admin', 'User', '+63 912 345 6789', 'admin', 'active');

 

-- Sample Receptionist (password: staff123)

INSERT INTO users (email, password, first_name, last_name, phone, role, status) VALUES

('reception@bayawanbaihotel.com', 'staff123', 'Maria', 'Santos', '+63 923 456 7890', 'receptionist', 'active'),

('manager@bayawanbaihotel.com', 'manager123', 'Juan', 'Dela Cruz', '+63 934 567 8901', 'manager', 'active');

 

-- Room Categories

INSERT INTO room_categories (category_name, description, base_price, max_occupancy, bed_type, room_size_sqm, amenities) VALUES

('Standard Room', 'Comfortable room with essential amenities perfect for budget-conscious travelers. Features city views and modern furnishings.', 2500.00, 2, 'Queen Bed', 25, 'WiFi, TV, Air Conditioning, Mini Refrigerator, Private Bathroom'),

('Deluxe Room', 'Spacious room with premium amenities and bay views. Includes work desk and sitting area.', 3500.00, 3, 'King Bed', 32, 'WiFi, Smart TV, Air Conditioning, Mini Bar, Coffee Maker, Safe, Bay View'),

('Suite', 'Luxurious suite with separate living area, bedroom with Jacuzzi, and panoramic ocean views.', 7500.00, 4, 'King Bed + Sofa Bed', 55, 'WiFi, Smart TV, Air Conditioning, Mini Bar, Coffee Machine, Safe, Jacuzzi, Ocean View, Living Room, Dining Area'),

('Family Room', 'Spacious room designed for families with two queen beds and kid-friendly amenities.', 4500.00, 4, '2 Queen Beds', 40, 'WiFi, TV, Air Conditioning, Mini Refrigerator, Kids Amenities, Connecting Room Option');

 

-- Sample Rooms

INSERT INTO rooms (room_number, category_id, floor, status, housekeeping_status) VALUES

('101', 1, 1, 'available', 'clean'),

('102', 1, 1, 'available', 'clean'),

('103', 1, 1, 'available', 'clean'),

('104', 1, 1, 'maintenance', 'dirty'),

('201', 2, 2, 'available', 'clean'),

('202', 2, 2, 'available', 'clean'),

('203', 2, 2, 'occupied', 'dirty'),

('204', 2, 2, 'available', 'clean'),

('301', 3, 3, 'available', 'clean'),

('302', 3, 3, 'reserved', 'clean'),

('303', 4, 3, 'available', 'clean'),

('304', 4, 3, 'available', 'clean');

 

-- Menu Categories

INSERT INTO menu_categories (category_name, description, sort_order) VALUES

('Breakfast', 'Start your day with our delicious breakfast options', 1),

('Main Course', 'Exquisite dishes prepared by our master chefs', 2),

('Desserts', 'Sweet indulgences to complete your meal', 3),

('Beverages', 'Refreshing drinks and cocktails', 4);

 

-- Menu Items

INSERT INTO menu_items (cat_id, item_name, description, price, is_special) VALUES

(1, 'Filipino Breakfast', 'Garlic rice, tocino/longganisa, fried egg, atchara, and brewed coffee', 450.00, TRUE),

(1, 'Continental Breakfast', 'Fresh fruits, pastries, yogurt, and choice of juice or coffee', 380.00, FALSE),

(1, 'American Breakfast', 'Eggs any style, bacon/sausage, hash browns, toast, and coffee', 520.00, FALSE),

(2, 'Grilled Blue Marlin', 'Fresh catch from Bayawan Bay with garlic butter sauce, served with rice and vegetables', 680.00, TRUE),

(2, 'Chicken Inasal', 'Authentic Negros-style grilled chicken with annatto oil and calamansi', 450.00, TRUE),

(2, 'Beef Steak Tagalog', 'Tender beef slices in soy-calamansi marinade with caramelized onions', 580.00, FALSE),

(3, 'Halo-Halo Special', 'Traditional Filipino dessert with ube, leche flan, and assorted sweet beans', 280.00, TRUE),

(3, 'Mango Float', 'Layers of graham crackers, cream, and fresh mangoes', 250.00, TRUE),

(4, 'Bayawan Bay Breeze', 'Refreshing tropical cocktail with rum, pineapple, and coconut', 320.00, TRUE),

(4, 'Fresh Buko Juice', 'Young coconut water served in the shell', 180.00, FALSE);

 

-- Amenities/Spa Services

INSERT INTO amenities (amenity_name, category, description, price, duration_minutes, operating_hours) VALUES

('Swedish Massage', 'spa', 'Relaxing full-body massage to relieve stress and tension', 1500.00, 60, '9:00 AM - 9:00 PM'),

('Hot Stone Therapy', 'spa', 'Therapeutic massage using heated stones for deep relaxation', 2000.00, 90, '9:00 AM - 9:00 PM'),

('Facial Treatment', 'spa', 'Rejuvenating facial with natural ingredients', 1200.00, 45, '10:00 AM - 8:00 PM'),

('Infinity Pool Access', 'pool', 'Access to our stunning infinity pool with bay views', 0.00, NULL, '6:00 AM - 10:00 PM'),

('Fitness Center', 'gym', 'State-of-the-art gym equipment and personal training', 0.00, NULL, '24 Hours'),

('Yoga Session', 'wellness', 'Guided yoga session by the pool or beach', 500.00, 60, '6:00 AM - 7:00 AM Daily');

 

-- Event Spaces

INSERT INTO event_spaces (space_name, description, capacity, area_sqm, features, price_per_day) VALUES

('Grand Ballroom', 'Elegant ballroom perfect for weddings, conferences, and galas', 300, 500, 'Stage, Sound System, Projector, Dance Floor, Bridal Suite', 50000.00),

('Conference Room A', 'Professional meeting space with modern AV equipment', 50, 80, 'Projector, Whiteboard, Video Conferencing, Coffee Station', 8000.00),

('Conference Room B', 'Intimate meeting room for small groups', 20, 40, 'TV Screen, Whiteboard, Coffee Station', 4000.00),

('Garden Pavilion', 'Outdoor venue with stunning bay views for romantic events', 150, 300, 'Tent Options, Garden Setting, Sound System, Catering Area', 35000.00),

('Rooftop Terrace', 'Exclusive rooftop space with panoramic views', 80, 150, 'City & Bay Views, Bar Area, Lounge Seating', 25000.00);



-- Events Table Sample Data (similar to rooms structure)

INSERT INTO events (event_name, category_id, floor, status, maintenance_status, special_features) VALUES

('Conference Room A', NULL, 1, 'available', 'clean', 'Projector, Whiteboard, Video Conferencing'),

('Conference Room B', NULL, 1, 'available', 'clean', 'TV Screen, Whiteboard'),

('Function Hall 1', NULL, 2, 'available', 'clean', 'Stage, Sound System, Dance Floor'),

('Function Hall 2', NULL, 2, 'available', 'clean', 'Stage, Sound System, Projector'),

('Garden Pavilion', NULL, 0, 'available', 'clean', 'Outdoor Setup, Tent Options, Garden Setting'),

('Rooftop Terrace', NULL, 5, 'available', 'clean', 'City & Bay Views, Bar Area, Lounge Seating');



-- Gallery Images

INSERT INTO gallery (title, description, image_path, category, is_featured, sort_order) VALUES

('Hotel Exterior', 'Stunning view of Bayawan Bai Hotel facade', 'images/gallery/hotel-exterior.jpg', 'hotel', TRUE, 1),

('Grand Lobby', 'Welcoming lobby with modern Filipino design', 'images/gallery/lobby.jpg', 'hotel', FALSE, 2),

('Standard Room', 'Comfortable standard room with city view', 'images/gallery/standard-room.jpg', 'rooms', TRUE, 1),

('Deluxe Room', 'Spacious deluxe room with bay view', 'images/gallery/deluxe-room.jpg', 'rooms', TRUE, 2),

('Suite Living Area', 'Elegant living space in our suites', 'images/gallery/suite-living.jpg', 'rooms', FALSE, 3),

('Suite Bedroom', 'Luxurious bedroom with ocean view', 'images/gallery/suite-bedroom.jpg', 'rooms', FALSE, 4),

('Infinity Pool', 'Relax by our stunning infinity pool', 'images/gallery/pool.jpg', 'amenities', TRUE, 1),

('Spa Treatment Room', 'Tranquil spa environment for relaxation', 'images/gallery/spa.jpg', 'amenities', FALSE, 2),

('Restaurant', 'Fine dining at our in-house restaurant', 'images/gallery/restaurant.jpg', 'dining', TRUE, 1),

('Breakfast Buffet', 'Delicious morning spread', 'images/gallery/breakfast.jpg', 'dining', FALSE, 2),

('Danjugan Island', 'Explore the beautiful Danjugan Island nearby', 'images/gallery/danjugan.jpg', 'attractions', TRUE, 1),

('Bayawan Bay Beach', 'Pristine beach just minutes away', 'images/gallery/bayawan-bay.jpg', 'attractions', TRUE, 2),

('Mt. Talinis', 'Majestic mountain views from the region', 'images/gallery/mt-talinis.jpg', 'attractions', FALSE, 3);

 

-- Homepage Slider

INSERT INTO homepage_slider (title, subtitle, image, button_text, button_link, sort_order, is_active) VALUES

('Welcome to Bayawan Bai Hotel', 'Experience the perfect blend of luxury and nature in Bayawan City', 'images/slider/slide1.jpg', 'Book Now', 'booking.php', 1, TRUE),

('Escape to Paradise', 'Discover pristine beaches and stunning ocean views', 'images/slider/slide2.jpg', 'Explore Rooms', 'rooms.php', 2, TRUE),

('Culinary Excellence', 'Savor the flavors of Negros Oriental', 'images/slider/slide3.jpg', 'View Dining', 'dining.php', 3, TRUE),

('Unforgettable Events', 'Host your special moments in our elegant venues', 'images/slider/slide4.jpg', 'Plan Your Event', 'events.php', 4, TRUE);

 

-- Promotions

INSERT INTO promotions (title, description, discount_percent, promo_code, start_date, end_date, min_nights, is_active) VALUES

('Summer Special', 'Book 3 nights and get 20% off your stay! Perfect for your summer getaway in Bayawan.', 20, 'SUMMER20', '2024-03-01', '2024-05-31', 3, TRUE),

('Early Bird Discount', 'Plan ahead! Book 30 days in advance and save 15% on your reservation.', 15, 'EARLY15', '2024-01-01', '2024-12-31', 1, TRUE),

('Weekend Escape', 'Special weekend rates for a relaxing break. Includes complimentary breakfast!', 25, 'WEEKEND25', '2024-01-01', '2024-12-31', 2, TRUE),

('Loyalty Member Special', 'Members enjoy an extra 10% off on top of any promotion!', 10, 'LOYAL10', '2024-01-01', '2024-12-31', 1, TRUE);

 

-- FAQs

INSERT INTO faqs (question, answer, category, sort_order) VALUES

('What are the check-in and check-out times?', 'Check-in time is 2:00 PM and check-out time is 12:00 PM (noon). Early check-in and late check-out are subject to availability and may incur additional charges.', 'reservations', 1),

('Is breakfast included in the room rate?', 'Breakfast inclusion depends on your booking package. Our Bed & Breakfast rates include breakfast for all registered guests. Please check your reservation confirmation for details.', 'dining', 2),

('Do you offer airport transfers?', 'Yes, we offer airport transfer services from Dumaguete Airport (Sibulan) to our hotel. Please contact our reservations team at least 24 hours in advance to arrange this service.', 'services', 3),

('Is there WiFi available?', 'Complimentary high-speed WiFi is available throughout the hotel premises for all guests.', 'services', 4),

('What payment methods do you accept?', 'We accept GCash, PayPal, major credit cards (Visa, Mastercard, Amex), cash, and bank transfers.', 'payments', 5),

('Can I modify or cancel my reservation?', 'Yes, reservations can be modified or cancelled according to our policy. Cancellations made 48 hours prior to check-in are fully refundable. Please refer to your booking confirmation for specific terms.', 'reservations', 6),

('Are pets allowed?', 'We regret that pets are not allowed in the hotel, with the exception of service animals.', 'policies', 7),

('Do you have parking facilities?', 'Yes, we offer complimentary parking for our hotel guests.', 'services', 8),

('What attractions are near the hotel?', 'Bayawan Bai Hotel is close to Danjugan Island Marine Reserve, Bayawan Bay Beach, and Mt. Talinis. Our concierge can help arrange tours and transportation.', 'location', 9),

('Is there a gym and spa?', 'Yes, we have a 24-hour fitness center and a full-service spa offering various treatments and massages.', 'amenities', 10);

 

-- Settings

INSERT INTO settings (setting_key, setting_value, setting_group) VALUES

('hotel_name', 'Bayawan Bai Hotel', 'general'),

('hotel_address', 'Bayawan City, Negros Oriental, Philippines', 'general'),

('hotel_phone', '+63 35 123 4567', 'general'),

('hotel_email', 'bayawanbaiminihotel@gmail.com', 'general'),

('check_in_time', '14:00', 'operations'),

('check_out_time', '12:00', 'operations'),

('currency', 'PHP', 'general'),

('facebook_url', 'https://facebook.com/bayawanbaihotel', 'social'),

('instagram_url', 'https://instagram.com/bayawanbaihotel', 'social'),

('twitter_url', 'https://twitter.com/bayawanbaihotel', 'social'),

('smtp_host', 'smtp.gmail.com', 'email'),

('smtp_port', '587', 'email'),

('smtp_username', 'bookings@bayawanbaihotel.com', 'email'),

('gcash_enabled', '1', 'payments'),

('paypal_enabled', '1', 'payments'),

('credit_card_enabled', '1', 'payments');

 

-- Inventory Categories

INSERT INTO inventory_categories (category_name) VALUES

('Linens & Towels'),

('Toiletries'),

('Cleaning Supplies'),

('Minibar Items'),

('Office Supplies'),

('Kitchen Supplies');

 

-- Inventory Items

INSERT INTO inventory_items (inv_cat_id, item_name, description, unit, quantity, reorder_level, unit_cost, supplier) VALUES

(1, 'Bath Towels', 'Premium white bath towels', 'piece', 200, 50, 450.00, 'Manila Textiles'),

(1, 'Bed Sheets', 'Queen size white sheets', 'piece', 150, 30, 850.00, 'Manila Textiles'),

(2, 'Shampoo', 'Hotel size shampoo bottles 30ml', 'bottle', 500, 100, 25.00, 'Amenities Supplier PH'),

(2, 'Soap', 'Hotel size soap bars 25g', 'piece', 600, 150, 15.00, 'Amenities Supplier PH'),

(3, 'All-Purpose Cleaner', 'Multi-surface cleaning solution', 'liter', 50, 10, 180.00, 'CleanPro Supplies'),

(4, 'Bottled Water', '500ml mineral water', 'bottle', 300, 50, 20.00, 'Nestle Philippines'),

(4, 'Snacks Assorted', 'Mixed snack items for minibar', 'pack', 100, 20, 45.00, 'Local Distributor');

 

-- Food Orders Table (for room service and restaurant orders)

CREATE TABLE food_orders (

    order_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    booking_id INT NULL,

    food_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    unit_price DECIMAL(10,2) NOT NULL,

    total_price DECIMAL(10,2) NOT NULL,

    status ENUM('pending', 'preparing', 'ready', 'delivered', 'cancelled') DEFAULT 'pending',

    order_type ENUM('room_service', 'dine_in', 'takeaway') DEFAULT 'room_service',

    payment_method ENUM('gcash', 'paypal', 'credit_card', 'pay_at_hotel', 'cash') DEFAULT 'pay_at_hotel',

    payment_status ENUM('pending', 'paid', 'partial') DEFAULT 'pending',

    room_number VARCHAR(20),

    special_instructions TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    prepared_at TIMESTAMP NULL,

    delivered_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id),

    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),

    FOREIGN KEY (food_id) REFERENCES menu_items(item_id)

);



-- Foods Table (Extended menu items for inventory management)

CREATE TABLE foods (

    food_id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,

    food_name VARCHAR(200) NOT NULL,

    description TEXT,

    price DECIMAL(10,2) NOT NULL,

    image VARCHAR(255),

    is_special BOOLEAN DEFAULT FALSE,

    is_available BOOLEAN DEFAULT TRUE,

    dietary_info VARCHAR(255),

    prep_time_minutes INT DEFAULT 20,

    stock_quantity INT DEFAULT 0,

    cost_price DECIMAL(10,2),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES menu_categories(cat_id)

);



-- Sample Data for Foods Table

INSERT INTO foods (category_id, food_name, description, price, cost_price, is_special, is_available, dietary_info, prep_time_minutes, stock_quantity) VALUES

(1, 'Filipino Breakfast Platter', 'Garlic rice, choice of tocino or longganisa, fried egg, atchara, and brewed coffee', 450.00, 180.00, TRUE, TRUE, NULL, 20, 50),

(1, 'Continental Breakfast', 'Fresh seasonal fruits, assorted pastries, yogurt, and choice of juice or coffee', 380.00, 150.00, FALSE, TRUE, 'Vegetarian', 15, 40),

(1, 'American Breakfast', 'Eggs any style, bacon or sausage, hash browns, toast, and coffee', 520.00, 220.00, FALSE, TRUE, NULL, 25, 35),

(2, 'Grilled Blue Marlin', 'Fresh catch from Bayawan Bay with garlic butter sauce, served with rice and vegetables', 680.00, 280.00, TRUE, TRUE, NULL, 30, 25),

(2, 'Chicken Inasal', 'Authentic Negros-style grilled chicken with annatto oil and calamansi', 450.00, 180.00, TRUE, TRUE, 'Gluten-Free', 25, 30),

(2, 'Beef Steak Tagalog', 'Tender beef slices in soy-calamansi marinade with caramelized onions', 580.00, 240.00, FALSE, TRUE, NULL, 30, 20),

(2, 'Vegetable Curry', 'Assorted vegetables in coconut curry sauce with steamed rice', 380.00, 140.00, FALSE, TRUE, 'Vegan, Gluten-Free', 25, 15),

(3, 'Halo-Halo Special', 'Traditional Filipino dessert with ube, leche flan, sweet beans, and shaved ice', 280.00, 100.00, TRUE, TRUE, 'Vegetarian', 10, 45),

(3, 'Mango Float', 'Layers of graham crackers, cream, and fresh sweet mangoes', 250.00, 90.00, TRUE, TRUE, 'Vegetarian', 15, 30),

(3, 'Leche Flan', 'Creamy caramel custard dessert', 180.00, 70.00, FALSE, TRUE, 'Vegetarian, Gluten-Free', 10, 40),

(4, 'Bayawan Bay Breeze', 'Refreshing tropical cocktail with rum, pineapple, and coconut cream', 320.00, 80.00, TRUE, TRUE, NULL, 5, 100),

(4, 'Fresh Buko Juice', 'Young coconut water served fresh in the shell', 180.00, 50.00, FALSE, TRUE, 'Vegan, Gluten-Free', 5, 60),

(4, 'Kapeng Barako', 'Strong Batangas brewed coffee', 120.00, 40.00, FALSE, TRUE, 'Vegan', 10, 80),

(2, 'Seafood Paella', 'Spanish rice dish with shrimp, mussels, squid, and fish', 750.00, 320.00, TRUE, TRUE, NULL, 45, 20),

(2, 'Pork Sinigang', 'Tamarind soup with pork and vegetables', 420.00, 160.00, FALSE, TRUE, 'Gluten-Free', 35, 25),

(2, 'Grilled Salmon', 'Norwegian salmon fillet with lemon butter sauce', 850.00, 380.00, TRUE, FALSE, NULL, 25, 0),

(3, 'Chocolate Lava Cake', 'Warm chocolate cake with molten center', 320.00, 120.00, FALSE, TRUE, 'Vegetarian', 20, 18);



SELECT 'Food orders and foods tables created successfully!' AS message;

-- =====================================================

-- NOTIFICATION SYSTEM TABLES

-- =====================================================

-- Notifications Table (for user notifications)

CREATE TABLE notifications (

    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    type ENUM('booking', 'food_order', 'payment', 'system', 'schedule', 'maintenance', 'event', 'promotion') NOT NULL,

    title VARCHAR(255) NOT NULL,

    message TEXT NOT NULL,

    related_id INT NULL,

    related_type VARCHAR(50) NULL,

    status ENUM('unread', 'read') DEFAULT 'unread',

    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',

    action_url VARCHAR(500) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    read_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,

    INDEX idx_user_status (user_id, status),

    INDEX idx_created_at (created_at),

    INDEX idx_type (type)

);

 

-- Notification Settings Table (per user preferences)

CREATE TABLE notification_settings (

    setting_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    notification_type ENUM('booking', 'food_order', 'payment', 'system', 'schedule', 'maintenance', 'event', 'promotion', 'all') NOT NULL,

    email_enabled BOOLEAN DEFAULT TRUE,

    popup_enabled BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,

    UNIQUE KEY unique_user_type (user_id, notification_type)

);

 

-- Insert default notification settings for existing users

INSERT INTO notification_settings (user_id, notification_type, email_enabled, popup_enabled)

SELECT user_id, 'all', TRUE, TRUE FROM users;

 

SELECT 'Notification system tables created successfully!' AS message;


-- =====================================================

-- CHATBOT TABLES

-- =====================================================

-- Chatbot tables for Bayawan Bai Hotel

-- Table for storing chat sessions
CREATE TABLE IF NOT EXISTS chat_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('active', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_message_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_last_message (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing chat messages
CREATE TABLE IF NOT EXISTS chat_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NULL,
    message_type ENUM('user', 'bot', 'staff') NOT NULL,
    message TEXT NOT NULL,
    intent VARCHAR(50) NULL,
    metadata JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_message_type (message_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for storing chatbot knowledge base / FAQs
CREATE TABLE IF NOT EXISTS chatbot_knowledge (
    knowledge_id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    question_pattern VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    keywords TEXT NULL,
    priority INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default knowledge base entries
INSERT INTO chatbot_knowledge (category, question_pattern, answer, keywords, priority) VALUES
-- Greetings
('greeting', 'hello|hi|hey|greetings', 'Hello! Welcome to Bayawan Bai Hotel. I\'m your virtual assistant. How can I help you today?', 'hello,hi,hey,greetings,welcome', 10),

-- Bookings
('booking', 'book.*room|reserve.*room|make.*booking|how.*book', 'You can book a room by visiting our Rooms page and selecting your preferred dates. Would you like me to guide you to the booking page?', 'book,room,reservation,booking,reserve', 9),
('booking', 'cancel.*booking|how.*cancel', 'To cancel a booking, please go to My Bookings in your dashboard. You can cancel confirmed bookings there. Note that cancellation policies may apply.', 'cancel,booking,refund', 9),
('booking', 'modify.*booking|change.*booking|edit.*booking', 'To modify your booking, please contact our front desk at +63 35 123 4567 or email us at bayawanbaiminihotel@gmail.com with your booking reference.', 'modify,change,edit,booking', 8),
('booking', 'booking.*status|check.*booking', 'You can check your booking status in the My Bookings section of your dashboard. You\'ll see all your current and past reservations there.', 'status,booking,check,reservation', 9),

-- Room Information
('rooms', 'room.*type|types.*room|what.*rooms', 'We offer several room types: Deluxe Rooms, Superior Rooms, Family Suites, and Presidential Suites. Each comes with different amenities and pricing. Would you like details on a specific room type?', 'room,type,deluxe,superior,family,suite', 8),
('rooms', 'room.*price|how.*much|price.*room', 'Our room rates vary by type and season. Deluxe rooms start at ₱2,500/night, Superior at ₱3,500/night, Family Suites at ₱5,000/night, and Presidential Suites at ₱8,000/night. Check our Rooms page for current rates.', 'price,cost,rate,expensive,cheap', 8),
('rooms', 'room.*amenity|what.*include|facilities.*room', 'All our rooms include: Free WiFi, Air Conditioning, Flat-screen TV, Mini-bar, Coffee/Tea maker, Room service, Daily housekeeping, and Premium toiletries. Suites additionally include living areas and kitchenettes.', 'amenity,wifi,ac,tv,mini-bar, facilities', 7),

-- Dining
('dining', 'restaurant|dining|food|eat', 'Our hotel features the Bayawan Bistro restaurant serving local and international cuisine. We also offer 24/7 room service. Would you like to see our menu or make a reservation?', 'restaurant,dining,food,eat,menu,bistro', 8),
('dining', 'room.*service|order.*food|food.*order', 'Yes! We offer 24/7 room service. You can order food directly from your room through our website or by calling the front desk. Check out the Order Now section for our full menu!', 'room service,order,food,delivery', 9),
('dining', 'breakfast|breakfast.*include', 'Yes, we offer complimentary breakfast for all guests. It\'s served from 6:00 AM to 10:00 AM at our Bayawan Bistro restaurant. We serve both Filipino and continental breakfast options.', 'breakfast,morning,food,complimentary', 8),

-- Events
('events', 'event.*space|venue|conference|meeting.*room', 'We have several event spaces available: Grand Ballroom (up to 300 guests), Conference Rooms (20-50 guests), and Outdoor Garden venues. All spaces come with audio-visual equipment and catering options.', 'event,venue,conference,meeting,ballroom,wedding', 8),
('events', 'book.*event|reserve.*venue', 'To book an event space, please visit our Events page or contact our events team at events@bayawanbaihotel.com. We recommend booking at least 2 weeks in advance for large events.', 'book event,reserve venue,party,wedding', 7),

-- Amenities
('amenities', 'pool|swimming', 'Yes, we have a beautiful outdoor swimming pool open from 6:00 AM to 10:00 PM. It\'s complimentary for all hotel guests. We also have a poolside bar for refreshments.', 'pool,swim,swimming', 8),
('amenities', 'gym|fitness', 'Yes, our fitness center is available 24/7 for hotel guests. It features cardio machines, weight equipment, and yoga mats. Located on the 2nd floor.', 'gym,fitness,exercise,workout', 7),
('amenities', 'spa|massage', 'We offer spa services including massages, facials, and body treatments. Operating hours are 9:00 AM to 9:00 PM. Reservations are recommended. Call extension 5555 from your room.', 'spa,massage,relax,treatment', 7),
('amenities', 'wifi|internet', 'Yes! We offer complimentary high-speed WiFi throughout the hotel. The network name is "BayawanBai-Guest" - no password required.', 'wifi,internet,connection,online', 9),
('amenities', 'parking|car', 'Yes, we offer complimentary parking for hotel guests. We have both outdoor and covered parking areas. Valet service is available upon request.', 'parking,car,vehicle', 7),

-- Location & Contact
('location', 'where.*located|address|location', 'Bayawan Bai Hotel is located in Bayawan City, Negros Oriental, Philippines. Our address is: Bayawan City, Negros Oriental, Philippines 6211', 'location,address,where,find', 8),
('location', 'airport.*shuttle|transport|pick.*up', 'We offer airport shuttle services from Dumaguete Airport (Sibulan) for an additional fee. Please contact us at least 24 hours in advance to arrange pickup.', 'airport,shuttle,transport,pickup', 7),
('location', 'nearby|attraction|places.*visit', 'Bayawan City has several attractions nearby: Niludhan Falls, Bayawan Boulevard, and local markets. Our front desk can provide tourist information and arrange tours.', 'nearby,attraction,tour,visit,places', 6),

-- Policies
('policies', 'check.*in|checkin|arrival', 'Our standard check-in time is 2:00 PM. Early check-in may be available upon request, subject to room availability. Please contact us in advance if you need early check-in.', 'check-in,arrival,checkin,time', 9),
('policies', 'check.*out|checkout|departure', 'Our standard check-out time is 12:00 PM (noon). Late check-out may be available upon request, subject to availability and may incur additional charges.', 'check-out,departure,checkout,leave', 9),
('policies', 'pet.*policy|bring.*pet|dog|cat', 'We are a pet-friendly hotel! Small pets are allowed in designated rooms for an additional cleaning fee of ₱500 per stay. Please inform us when booking if you\'re bringing a pet.', 'pet,dog,cat,animal', 7),
('policies', 'payment|pay|credit.*card', 'We accept cash (Philippine Peso), credit cards (Visa, Mastercard), and GCash. A valid credit card is required to guarantee reservations.', 'payment,pay,credit card,gcash', 8),

-- Support
('support', 'help|support|assistance', 'I\'m here to help! I can assist with bookings, room information, dining options, amenities, and general hotel inquiries. What do you need help with?', 'help,support,assist', 10),
('support', 'contact|phone|email|reach.*you', 'You can reach us at:\n📞 Phone: +63 35 123 4567\n📧 Email: bayawanbaiminihotel@gmail.com\n🌐 Website: www.bayawanbaihotel.com\n\nFront desk is available 24/7!', 'contact,phone,email,reach,call', 9),
('support', 'complaint|problem|issue|unhappy', 'I\'m sorry to hear you\'re experiencing an issue. For immediate assistance with complaints or urgent problems, please contact our front desk directly at +63 35 123 4567 or speak to a manager on duty.', 'complaint,problem,issue,unhappy,bad', 10),
('support', 'speak.*human|talk.*person|real.*person|staff', 'I can connect you with a staff member. Please hold while I transfer you to our front desk, or you can call us directly at +63 35 123 4567 for immediate assistance.', 'human,person,staff,agent,representative', 10),

-- Goodbye
('goodbye', 'bye|goodbye|see.*you|thank.*you|thanks', 'Thank you for chatting with me! If you need any further assistance, feel free to ask. Have a wonderful stay at Bayawan Bai Hotel!', 'bye,goodbye,thanks,thank you', 10);

-- Table for storing user preferences/context
CREATE TABLE IF NOT EXISTS chatbot_context (
    context_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    context_key VARCHAR(50) NOT NULL,
    context_value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_context (user_id, context_key),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'Chatbot tables created successfully!' AS message;

-- =====================================================
-- ADDITIONAL SCHEMA FILES COMBINED BELOW
-- =====================================================

-- =====================================================
-- UPDATE: Add image_primary column to event_spaces table
-- =====================================================
ALTER TABLE event_spaces ADD COLUMN image_primary VARCHAR(255) AFTER price_per_day;

-- =====================================================
-- RATINGS TABLE SCHEMA
-- =====================================================

-- Ratings Table for Bayawan Bai Hotel
-- Stores user ratings for Room Bookings, Events, and Food Orders

CREATE TABLE ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type ENUM('room', 'event', 'food') NOT NULL,
    booking_id INT NULL,
    event_booking_id INT NULL,
    food_order_id INT NULL,
    rating_value TINYINT NOT NULL CHECK (rating_value BETWEEN 1 AND 5),
    comment TEXT NULL,
    is_rated TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Ensure only one rating per service item per user
    UNIQUE KEY unique_room_rating (user_id, booking_id),
    UNIQUE KEY unique_event_rating (user_id, event_booking_id),
    UNIQUE KEY unique_food_rating (user_id, food_order_id),
    
    -- Foreign key constraints
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL,
    FOREIGN KEY (event_booking_id) REFERENCES event_bookings(event_booking_id) ON DELETE SET NULL,
    FOREIGN KEY (food_order_id) REFERENCES food_orders(order_id) ON DELETE SET NULL
);

-- Index for efficient queries
CREATE INDEX idx_service_type ON ratings(service_type);
CREATE INDEX idx_rating_value ON ratings(rating_value);
CREATE INDEX idx_created_at ON ratings(created_at);

-- Table to track which items need to be rated (for pending rating prompts)
-- This helps determine when to show the rating popup
CREATE TABLE rating_eligibility (
    eligibility_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type ENUM('room', 'event', 'food') NOT NULL,
    booking_id INT NULL,
    event_booking_id INT NULL,
    food_order_id INT NULL,
    status VARCHAR(20) NOT NULL, -- 'pending', 'shown', 'completed', 'skipped'
    eligible_at TIMESTAMP NULL, -- When the item became eligible for rating
    shown_at TIMESTAMP NULL, -- When the rating prompt was shown
    completed_at TIMESTAMP NULL, -- When the rating was submitted
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_eligibility (user_id, booking_id, event_booking_id, food_order_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_eligibility_status ON rating_eligibility(status);
CREATE INDEX idx_eligible_at ON rating_eligibility(eligible_at);

-- =====================================================
-- VIRTUAL TOUR TABLES FOR BAYAWAN BAI HOTEL
-- =====================================================

-- Table to store 360-degree panorama images for each room category
CREATE TABLE IF NOT EXISTS room_virtual_tours (
    tour_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    panorama_image VARCHAR(255) NOT NULL COMMENT 'Path to 360-degree equirectangular image',
    thumbnail_image VARCHAR(255) COMMENT 'Optional thumbnail preview',
    title VARCHAR(100) NOT NULL DEFAULT 'Virtual Tour',
    description TEXT,
    hotspot_config JSON COMMENT 'Hotspot configuration for interactive elements',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES room_categories(category_id) ON DELETE CASCADE,
    INDEX idx_category_active (category_id, is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='360-degree virtual tours for room categories';

-- Table to store hotspots within virtual tours (interactive points)
CREATE TABLE IF NOT EXISTS virtual_tour_hotspots (
    hotspot_id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    hotspot_type ENUM('info', 'scene', 'link') DEFAULT 'info',
    pitch DECIMAL(8,4) NOT NULL COMMENT 'Vertical angle in degrees (-90 to 90)',
    yaw DECIMAL(8,4) NOT NULL COMMENT 'Horizontal angle in degrees (-180 to 180)',
    text VARCHAR(255) COMMENT 'Tooltip text',
    target_tour_id INT NULL COMMENT 'For scene type - target tour ID to navigate to',
    target_url VARCHAR(255) NULL COMMENT 'For link type - external URL',
    css_class VARCHAR(50) DEFAULT 'custom-hotspot',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES room_virtual_tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (target_tour_id) REFERENCES room_virtual_tours(tour_id) ON DELETE SET NULL,
    INDEX idx_tour_position (tour_id, pitch, yaw)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for each room category
-- Note: These use placeholder image paths. Replace with actual 360 images

INSERT INTO room_virtual_tours (category_id, panorama_image, thumbnail_image, title, description, is_active, display_order) VALUES
(1, 'uploads/virtual_tours/standard_room_360.jpg', 'uploads/virtual_tours/standard_room_thumb.jpg', 'Standard Room - 360° View', 'Experience our comfortable Standard Room with a full 360-degree panoramic view. Perfect for budget-conscious travelers.', 1, 1),
(2, 'uploads/virtual_tours/deluxe_room_360.jpg', 'uploads/virtual_tours/deluxe_room_thumb.jpg', 'Deluxe Room - 360° View', 'Explore our spacious Deluxe Room with premium amenities and stunning bay views from every angle.', 1, 1),
(3, 'uploads/virtual_tours/suite_room_360.jpg', 'uploads/virtual_tours/suite_room_thumb.jpg', 'Suite - 360° View', 'Take a virtual tour of our luxurious Suite featuring a separate living area, bedroom, and panoramic ocean views.', 1, 1),
(4, 'uploads/virtual_tours/family_room_360.jpg', 'uploads/virtual_tours/family_room_thumb.jpg', 'Family Room - 360° View', 'Discover our Family Room designed for comfort with ample space for the whole family.', 1, 1);

-- Sample hotspots for the Deluxe Room tour (tour_id 2)
INSERT INTO virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, css_class) VALUES
(2, 'info', -5.0, 45.0, 'King-size bed with premium linens', 'info-hotspot'),
(2, 'info', -10.0, -30.0, 'Work desk with bay view', 'info-hotspot'),
(2, 'info', 0.0, 90.0, 'Private balcony access', 'info-hotspot');

-- Sample hotspots for the Suite tour (tour_id 3)
INSERT INTO virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, css_class) VALUES
(3, 'info', -5.0, 0.0, 'Luxurious King Bed', 'info-hotspot'),
(3, 'info', 0.0, -90.0, 'Living room area', 'info-hotspot'),
(3, 'info', 10.0, 45.0, 'Jacuzzi tub', 'info-hotspot');

-- =====================================================
-- EVENT VIRTUAL TOUR TABLES FOR BAYAWAN BAI HOTEL
-- =====================================================

-- Table to store 360-degree panorama images for each event space
CREATE TABLE IF NOT EXISTS event_virtual_tours (
    tour_id INT AUTO_INCREMENT PRIMARY KEY,
    space_id INT NOT NULL,
    panorama_image VARCHAR(255) NOT NULL COMMENT 'Path to 360-degree equirectangular image',
    thumbnail_image VARCHAR(255) COMMENT 'Optional thumbnail preview',
    title VARCHAR(100) NOT NULL DEFAULT 'Virtual Tour',
    description TEXT,
    hotspot_config JSON COMMENT 'Hotspot configuration for interactive elements',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (space_id) REFERENCES event_spaces(space_id) ON DELETE CASCADE,
    INDEX idx_space_active (space_id, is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='360-degree virtual tours for event spaces';

-- Table to store hotspots within event virtual tours (interactive points)
CREATE TABLE IF NOT EXISTS event_virtual_tour_hotspots (
    hotspot_id INT AUTO_INCREMENT PRIMARY KEY,
    tour_id INT NOT NULL,
    hotspot_type ENUM('info', 'scene', 'link') DEFAULT 'info',
    pitch DECIMAL(8,4) NOT NULL COMMENT 'Vertical angle in degrees (-90 to 90)',
    yaw DECIMAL(8,4) NOT NULL COMMENT 'Horizontal angle in degrees (-180 to 180)',
    text VARCHAR(255) COMMENT 'Tooltip text',
    target_tour_id INT NULL COMMENT 'For scene type - target tour ID to navigate to',
    target_url VARCHAR(255) NULL COMMENT 'For link type - external URL',
    css_class VARCHAR(50) DEFAULT 'custom-hotspot',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tour_id) REFERENCES event_virtual_tours(tour_id) ON DELETE CASCADE,
    FOREIGN KEY (target_tour_id) REFERENCES event_virtual_tours(tour_id) ON DELETE SET NULL,
    INDEX idx_tour_position (tour_id, pitch, yaw)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for each event space
-- Note: These use placeholder image paths. Replace with actual 360 images

INSERT INTO event_virtual_tours (space_id, panorama_image, thumbnail_image, title, description, is_active, display_order) VALUES
(1, 'assets/uploads/event_virtual_tours/grand_ballroom_360.jpg', 'assets/uploads/event_virtual_tours/grand_ballroom_thumb.jpg', 'Grand Ballroom - 360° View', 'Experience our elegant Grand Ballroom, perfect for weddings, conferences, and galas. Full 360-degree panoramic view.', 1, 1),
(2, 'assets/uploads/event_virtual_tours/conference_a_360.jpg', 'assets/uploads/event_virtual_tours/conference_a_thumb.jpg', 'Conference Room A - 360° View', 'Professional meeting space with modern AV equipment. Explore the room in full 360° view.', 1, 1),
(3, 'assets/uploads/event_virtual_tours/conference_b_360.jpg', 'assets/uploads/event_virtual_tours/conference_b_thumb.jpg', 'Conference Room B - 360° View', 'Intimate meeting room for small groups with professional setup.', 1, 1),
(4, 'assets/uploads/event_virtual_tours/garden_pavilion_360.jpg', 'assets/uploads/event_virtual_tours/garden_pavilion_thumb.jpg', 'Garden Pavilion - 360° View', 'Outdoor venue with stunning bay views for romantic events and celebrations.', 1, 1),
(5, 'assets/uploads/event_virtual_tours/rooftop_terrace_360.jpg', 'assets/uploads/event_virtual_tours/rooftop_terrace_thumb.jpg', 'Rooftop Terrace - 360° View', 'Exclusive rooftop space with panoramic city and bay views.', 1, 1);

-- Sample hotspots for Grand Ballroom tour (tour_id 1)
INSERT INTO event_virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, css_class) VALUES
(1, 'info', -5.0, 0.0, 'Main stage area with professional lighting', 'info-hotspot'),
(1, 'info', -10.0, 45.0, 'Dance floor and entertainment area', 'info-hotspot'),
(1, 'info', 0.0, -90.0, 'Bridal suite entrance', 'info-hotspot'),
(1, 'info', 5.0, 120.0, 'Catering preparation area', 'info-hotspot');

-- Sample hotspots for Garden Pavilion tour (tour_id 4)
INSERT INTO event_virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, css_class) VALUES
(4, 'info', -5.0, 0.0, 'Main ceremony area with garden backdrop', 'info-hotspot'),
(4, 'info', 0.0, 90.0, 'Bay view dining setup', 'info-hotspot'),
(4, 'info', -10.0, -45.0, 'Catering and buffet area', 'info-hotspot');

-- Sample hotspots for Rooftop Terrace tour (tour_id 5)
INSERT INTO event_virtual_tour_hotspots (tour_id, hotspot_type, pitch, yaw, text, css_class) VALUES
(5, 'info', -5.0, 0.0, 'Lounge seating area', 'info-hotspot'),
(5, 'info', 10.0, 90.0, 'Panoramic city view', 'info-hotspot'),
(5, 'info', -10.0, -90.0, 'Bar and cocktail area', 'info-hotspot');

SELECT 'All additional tables and data combined successfully!' AS message;

-- =====================================================
-- ADDITIONAL MIGRATION FILES COMBINED BELOW
-- =====================================================

-- =====================================================
-- Add profile_picture column to users table
-- =====================================================
ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL AFTER country;

-- =====================================================
-- Add booking_ref column to bookings table
-- =====================================================
ALTER TABLE bookings 
ADD COLUMN booking_ref VARCHAR(50) UNIQUE NULL AFTER booking_id;

ALTER TABLE bookings 
ADD INDEX idx_booking_ref (booking_ref);

ALTER TABLE bookings 
MODIFY COLUMN booking_ref VARCHAR(50) UNIQUE NULL COMMENT 'Unique booking reference number (BBHYYYYMMDDXXXXXX)';

-- =====================================================
-- Add event_ref and order_ref columns for QR code scanning
-- =====================================================
ALTER TABLE event_bookings 
ADD COLUMN event_ref VARCHAR(50) UNIQUE NULL AFTER event_booking_id;

ALTER TABLE event_bookings 
ADD INDEX idx_event_ref (event_ref);

ALTER TABLE food_orders 
ADD COLUMN order_ref VARCHAR(50) UNIQUE NULL AFTER order_id;

ALTER TABLE food_orders 
ADD INDEX idx_order_ref (order_ref);

-- =====================================================
-- Add Payment Columns to Event Bookings Table
-- =====================================================
ALTER TABLE event_bookings 
ADD COLUMN payment_status ENUM('pending', 'paid', 'partial', 'failed', 'refunded') DEFAULT 'pending' AFTER status;

ALTER TABLE event_bookings 
ADD COLUMN payment_method VARCHAR(50) DEFAULT NULL AFTER payment_status;

ALTER TABLE event_bookings 
ADD COLUMN amount_paid DECIMAL(10,2) DEFAULT 0.00 AFTER payment_method;

ALTER TABLE event_bookings 
ADD COLUMN transaction_id VARCHAR(100) DEFAULT NULL AFTER amount_paid;

ALTER TABLE event_bookings 
ADD COLUMN paid_at TIMESTAMP NULL AFTER transaction_id;

ALTER TABLE event_bookings 
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER paid_at;

ALTER TABLE payments 
ADD COLUMN event_booking_id INT NULL AFTER booking_id,
ADD FOREIGN KEY (event_booking_id) REFERENCES event_bookings(event_booking_id) ON DELETE SET NULL;

ALTER TABLE payments 
MODIFY COLUMN booking_id INT NULL;

SELECT 'All database migration files combined successfully!' AS message;