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
