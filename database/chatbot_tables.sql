-- Chatbot tables for Bayawan Bai Hotel
-- Add these tables to your database

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
('booking', 'modify.*booking|change.*booking|edit.*booking', 'To modify your booking, please contact our front desk at +63 35 123 4567 or email us at info@bayawanbaihotel.com with your booking reference.', 'modify,change,edit,booking', 8),
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
('support', 'contact|phone|email|reach.*you', 'You can reach us at:\n📞 Phone: +63 35 123 4567\n📧 Email: info@bayawanbaihotel.com\n🌐 Website: www.bayawanbaihotel.com\n\nFront desk is available 24/7!', 'contact,phone,email,reach,call', 9),
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
