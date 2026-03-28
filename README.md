# Bayawan Bai Hotel Management System

A comprehensive, full-featured Hotel Management System for Bayawan Bai Hotel built with PHP, MySQL, and inline CSS/JavaScript.

[![PHP Version](https://img.shields.io/badge/PHP-7.4+-777BB4.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)

## 📋 Table of Contents

- [🏨 Project Overview](#-project-overview)
- [🌟 Features](#-features)
- [🛠️ Technology Stack](#️-technology-stack)
- [📁 Complete Project Structure](#-complete-project-structure)
- [🗄️ Database Architecture](#️-database-architecture)
- [🎨 Color Scheme](#-color-scheme)
- [🚀 Quick Start](#-quick-start)
- [📊 Component Documentation](#-component-documentation)
- [🌐 API Endpoints](#-api-endpoints)
- [🔧 Configuration](#-configuration)
- [🧪 Testing](#-testing)
- [📈 Performance Considerations](#-performance-considerations)
- [🔄 Backup & Maintenance](#-backup--maintenance)
- [🐛 Troubleshooting](#-troubleshooting)
- [📚 Documentation](#-documentation)
- [🤝 Contributing](#-contributing)
- [📞 Support](#-support)
- [📄 License](#-license)
- [🏆 Credits](#-credits)
- [📝 Recent Updates](#-recent-updates)

## 🏨 Project Overview

Bayawan Bai Hotel Management System is a dynamic, database-driven web application designed for both customer-facing services and internal operational tools. The system is inspired by tourism and hospitality in Bayawan City, Negros Oriental, Philippines, providing a complete solution for hotel management operations.

This system encompasses:
- **Guest-facing booking and reservation system**
- **Staff management and operations tools**
- **Administrative control panel**
- **Real-time inventory and availability management**
- **Payment processing integration**
- **Comprehensive reporting and analytics**

## 🌟 Features

### 🏨 Guest / Front-End Pages
- **Home** (`index.php`) - Welcome page with hero slider, featured rooms, promotions
- **Rooms** (`rooms.php`) - Room categories with filtering and comparison
- **Room Details** (`room-details.php`) - Detailed room information with image gallery
- **Booking** (`booking.php`) - Dynamic reservation form with real-time price calculation
- **Availability** (`availability.php`) - Real-time room availability checker
- **Dining** (`dining.php`) - Restaurant menu with categories and special offers
- **Events** (`events.php`) - Event spaces and inquiry form
- **Amenities** (`amenities.php`) - Spa, pool, gym services and pricing
- **Gallery** (`gallery.php`) - Photo gallery with lightbox
- **About** (`about.php`) - Hotel history, mission, and team
- **Contact** (`contact.php`) - Contact form and information
- **Location** (`location.php`) - Directions and nearby attractions
- **FAQ** (`faq.php`) - Frequently asked questions with categories

### 👤 User Account Pages
- **Login** (`auth/login.php`) - Secure authentication system
- **Register** (`auth/register.php`) - User registration with validation
- **Logout** (`auth/logout.php`) - Session termination
- **Dashboard** (`user/dashboard.php`) - User profile overview and bookings
- **My Bookings** (`user/my-bookings.php`) - Booking history and management
- **My Event Bookings** (`user/my-event-bookings.php`) - Event reservation management
- **My Food Orders** (`user/my-food-orders.php`) - Room service order history
- **Booking Details** (`user/booking-details.php`) - Detailed booking information
- **Profile** (`user/profile.php`) - Personal information management
- **Change Password** (`user/change-password.php`) - Password management

### 🏢 Staff / Receptionist Pages
- **Staff Dashboard** (`staff/staff-dashboard.php`) - Daily operations overview
- **Check-in** (`staff/checkin.php`) - Guest check-in process
- **Check-out** (`staff/checkout.php`) - Guest check-out process
- **Confirm Booking** (`staff/confirm-booking.php`) - Booking confirmation
- **Walk-in Booking** (`staff/walkin-booking.php`) - Direct booking creation
- **Staff Bookings** (`staff/staff-bookings.php`) - Booking management
- **Staff Event Bookings** (`staff/staff-event-bookings.php`) - Event booking management
- **Staff Food Orders** (`staff/staff-foods-orders.php`) - Food order management
- **Staff Inventory** (`staff/staff-inventory.php`) - Inventory management
- **Staff Maintenance** (`staff/staff-maintenance.php`) - Maintenance requests
- **Booking Charges** (`staff/staff-booking-charges.php`) - Additional charges management

### ⚙️ Admin Pages
- **Admin Dashboard** (`admin/admin-dashboard.php`) - Statistics and analytics
- **User Management** (`admin/admin-users.php`) - User account management
- **Room Management** (`admin/admin-rooms.php`) - Room inventory management
- **Room Categories** (`admin/admin-room-categories.php`) - Room type management
- **Booking Management** (`admin/admin-bookings.php`) - Booking oversight
- **Booking Details** (`admin/admin-booking-details.php`) - Detailed booking views
- **Payment Management** (`admin/admin-payments.php`) - Payment processing
- **Analytics** (`admin/admin-analytics.php`) - Business analytics
- **Reports** (`admin/admin-reports.php`) - Comprehensive reporting
- **Gallery Management** (`admin/admin-gallery.php`) - Photo gallery admin
- **Homepage Slider** (`admin/admin-homepage-slider.php`) - Hero slide management
- **Promotions** (`admin/admin-promotions.php`) - Promotional campaigns
- **Reviews** (`admin/admin-reviews.php`) - Customer review management
- **FAQs** (`admin/admin-faqs.php`) - FAQ management
- **Settings** (`admin/admin-settings.php`) - System configuration
- **Maintenance** (`admin/admin-maintenance.php`) - System maintenance
- **Staff Permissions** (`admin/admin-staff-permissions.php`) - Role management
- **Staff Schedules** (`admin/admin-staff-schedules.php`) - Staff scheduling
- **User Sessions** (`admin/admin-user-sessions.php`) - Session monitoring
- **Notification Logs** (`admin/admin-notification-logs.php`) - Communication logs
- **Amenities** (`admin/admin-amenities.php`) - Hotel amenities management
- **Event Spaces** (`admin/admin-event-spaces.php`) - Event venue management
- **Event Bookings** (`admin/admin-event-bookings.php`) - Event booking oversight
- **Menu Categories** (`admin/admin-menu-categories.php`) - Dining menu categories
- **Menu Items** (`admin/admin-menu-items.php`) - Restaurant menu management
- **Food Inventory** (`admin/admin-foods-inventory.php`) - Food stock management
- **Inventory Categories** (`admin/admin-inventory-categories.php`) - Inventory classification
- **Inventory Items** (`admin/admin-inventory-items.php`) - General inventory management

## 🛠️ Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Backend | PHP | 7.4+ |
| Frontend | HTML5 + Inline CSS | - |
| Interactivity | JavaScript (Vanilla) | ES6+ |
| Database | MySQL / MariaDB | 5.7+ / 10.2+ |
| Icons | Font Awesome | 6.4.0 |
| Fonts | Google Fonts | Playfair Display, Lato |
| Email | PHPMailer | 7.0+ |
| Package Manager | Composer | 2.0+ |

## 📁 Complete Project Structure

```
bayawanhotel/
├── 📄 Core Application Files
│   ├── index.php                     # Home page with hero slider
│   ├── rooms.php                     # Room listings and filtering
│   ├── room-details.php              # Individual room details
│   ├── booking.php                   # Booking reservation form
│   ├── booking-confirmation.php      # Booking confirmation page
│   ├── availability.php              # Room availability checker
│   ├── dining.php                    # Restaurant and dining info
│   ├── events.php                    # Events and venues
│   ├── amenities.php                 # Hotel amenities and services
│   ├── gallery.php                   # Photo gallery
│   ├── about.php                     # About the hotel
│   ├── contact.php                   # Contact information and form
│   ├── location.php                  # Location and directions
│   ├── faq.php                       # Frequently asked questions
│   ├── privacy.php                   # Privacy policy
│   ├── terms.php                     # Terms and conditions
│   ├── order-now.php                 # Food ordering system
│   ├── foods-details.php             # Food item details
│   ├── payment-process.php           # Payment processing
│   ├── food-order-payment-process.php # Food payment processing
│   ├── my-bookings.php               # User bookings (legacy)
│   └── database.sql                  # Database schema and data
│
├── 📁 Authentication Module
│   ├── auth/
│   │   ├── login.php                 # User login
│   │   ├── register.php              # User registration
│   │   └── logout.php                # Session logout
│
├── 📁 User Dashboard Module
│   ├── user/
│   │   ├── dashboard.php             # User main dashboard
│   │   ├── profile.php               # User profile management
│   │   ├── change-password.php       # Password change
│   │   ├── my-bookings.php           # Booking history
│   │   ├── booking-details.php       # Individual booking details
│   │   ├── my-event-bookings.php     # Event booking history
│   │   └── my-food-orders.php        # Food order history
│
├── 📁 Staff Operations Module
│   ├── staff/
│   │   ├── staff-dashboard.php       # Staff operations overview
│   │   ├── checkin.php               # Guest check-in process
│   │   ├── checkout.php              # Guest check-out process
│   │   ├── confirm-booking.php       # Booking confirmation
│   │   ├── walkin-booking.php        # Direct booking creation
│   │   ├── staff-bookings.php        # Booking management
│   │   ├── staff-event-bookings.php  # Event booking management
│   │   ├── staff-foods-orders.php    # Food order management
│   │   ├── staff-inventory.php       # Inventory management
│   │   ├── staff-maintenance.php     # Maintenance requests
│   │   └── staff-booking-charges.php # Additional charges
│
├── 📁 Administration Module
│   ├── admin/
│   │   ├── admin-dashboard.php        # Admin main dashboard
│   │   ├── admin-users.php           # User management
│   │   ├── admin-rooms.php           # Room management
│   │   ├── admin-room-categories.php  # Room categories
│   │   ├── admin-bookings.php        # Booking management
│   │   ├── admin-booking-details.php # Booking details
│   │   ├── admin-payments.php        # Payment management
│   │   ├── admin-analytics.php       # Analytics dashboard
│   │   ├── admin-reports.php         # Comprehensive reports
│   │   ├── admin-gallery.php         # Photo gallery
│   │   ├── admin-homepage-slider.php # Homepage slides
│   │   ├── admin-promotions.php      # Promotions management
│   │   ├── admin-reviews.php         # Customer reviews
│   │   ├── admin-faqs.php            # FAQ management
│   │   ├── admin-settings.php        # System settings
│   │   ├── admin-maintenance.php     # System maintenance
│   │   ├── admin-staff-permissions.php # Staff permissions
│   │   ├── admin-staff-schedules.php # Staff scheduling
│   │   ├── admin-user-sessions.php   # Session monitoring
│   │   ├── admin-notification-logs.php # Notification logs
│   │   ├── admin-amenities.php       # Amenities management
│   │   ├── admin-event-spaces.php    # Event venues
│   │   ├── admin-event-bookings.php  # Event bookings
│   │   ├── admin-menu-categories.php  # Menu categories
│   │   ├── admin-menu-items.php      # Menu items
│   │   ├── admin-foods-inventory.php # Food inventory
│   │   ├── admin-inventory-categories.php # Inventory categories
│   │   └── admin-inventory-items.php # General inventory
│
├── 📁 Common Components
│   ├── includes/
│   │   ├── config.php                # Configuration and functions
│   │   ├── header.php                # Common header
│   │   ├── footer.php                # Common footer
│   │   ├── admin-header.php          # Admin-specific header
│   │   ├── admin-footer.php          # Admin-specific footer
│   │   ├── staff-header.php          # Staff-specific header
│   │   ├── staff-footer.php          # Staff-specific footer
│   │   ├── user-header.php           # User-specific header
│   │   ├── user-footer.php           # User-specific footer
│   │   └── logs/                     # System logs
│
├── 📁 Dependencies
│   ├── vendor/                       # Composer dependencies
│   ├── composer.json                 # PHP dependencies
│   ├── composer.lock                 # Dependency lock file
│   └── assets/                       # Static assets (CSS, JS, Images)
│
└── 📁 Documentation
    └── README.md                      # This documentation file
```

## 🗄️ Database Architecture

### Core Database Tables

#### 👥 User Management
- **`users`** - Central user accounts table with role-based access
  - Fields: user_id, email, password, first_name, last_name, role, status, loyalty_points
  - Roles: guest, receptionist, manager, admin
  - Security: Password hashing with bcrypt, email verification tracking

#### 🏨 Room Management
- **`room_categories`** - Room type definitions and pricing
  - Fields: category_id, category_name, description, base_price, max_occupancy, amenities
  - Features: Dynamic pricing, capacity management, amenity lists

- **`rooms`** - Individual room instances
  - Fields: room_id, room_number, category_id, status, floor, maintenance_notes
  - Status: available, occupied, maintenance, cleaning, out_of_order

#### 📅 Booking System
- **`bookings`** - Reservation records
  - Fields: booking_id, user_id, room_id, check_in, check_out, total_amount, status
  - Integration: Links to users, rooms, payments, and guest information

- **`booking_details`** - Extended booking information
  - Fields: booking_detail_id, booking_id, guests_count, special_requests, check_in_time

#### 💳 Payment Processing
- **`payments`** - Financial transactions
  - Fields: payment_id, booking_id, amount, payment_method, transaction_id, status
  - Methods: cash, gcash, paypal, credit_card, bank_transfer

#### 🍽️ Dining Services
- **`menu_categories`** - Restaurant menu organization
- **`menu_items`** - Individual food and beverage items
- **`food_orders`** - Room service and restaurant orders

#### 🎉 Event Management
- **`event_spaces`** - Event venue definitions
- **`event_bookings`** - Event reservations and management

#### 📊 Analytics & Reporting
- **`reviews`** - Customer feedback and ratings
- **`promotions`** - Marketing campaigns and discounts
- **`notification_logs`** - Communication tracking

### Database Relationships
```
users (1) ←→ (many) bookings
room_categories (1) ←→ (many) rooms
rooms (1) ←→ (many) bookings
bookings (1) ←→ (many) payments
users (1) ←→ (many) reviews
promotions (1) ←→ (many) bookings
```

### Database Requirements
- **MySQL Version**: 5.7+ or MariaDB 10.2+
- **Storage Engine**: InnoDB for transaction support
- **Character Set**: utf8mb4 for full Unicode support
- **Collation**: utf8mb4_unicode_ci for accurate sorting
- **Indexing**: Optimized indexes on frequently queried columns
- **Constraints**: Foreign key relationships for data integrity

## 🎨 Color Scheme

```css
:root {
    --primary-color: #367D8A;
    --secondary-color: #285F6B;
    --dark-color: #133336;
    --light-color: #FFFFFF;
    --text-color: #010001;
}
```

## 🚀 Quick Start

### Prerequisites
- XAMPP/WAMP/MAMP or similar web server stack
- PHP 7.4 or higher with extensions: PDO, MySQLi, GD, cURL
- MySQL 5.7+ or MariaDB 10.2+
- Web browser (Chrome 80+, Firefox 75+, Safari 13+, Edge 80+)
- Composer (for dependency management)

### 5-Minute Setup

1. **Download & Extract**
   ```bash
   # Extract to your web server directory
   # e.g., C:\xampp\htdocs\bayawanhotel\
   ```

2. **Install Dependencies**
   ```bash
   cd bayawanhotel
   composer install
   ```

3. **Database Setup**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Import the `database.sql` file
   - Verify database creation: `bayawan_hotel`

4. **Configure (Optional)**
   - Edit `includes/config.php` if needed
   - Default database settings work with XAMPP

5. **Launch Application**
   - Start Apache and MySQL services
   - Navigate to: `http://localhost/bayawanhotel/`

6. **Login**
   - **Admin**: `admin@bayawanbaihotel.com` / `admin123`
   - **Staff**: `reception@bayawanbaihotel.com` / `staff123`
   - **Manager**: `manager@bayawanbaihotel.com` / `staff123`

That's it! 🎉 Your hotel management system is ready to use.

### First Steps After Installation
- [ ] Explore the admin dashboard
- [ ] Add your hotel's actual rooms and rates
- [ ] Configure email settings for notifications
- [ ] Update hotel information in About page
- [ ] Test the booking process
- [ ] Create staff accounts and permissions
- [ ] Set up payment methods
- [ ] Customize homepage slider and gallery

## 📊 Component Documentation

### 🏠 Core Application Components

#### `index.php` - Homepage
**Purpose**: Main landing page with dynamic content
**Features**:
- Hero slider with 3 promotional slides
- Featured rooms display (4 rooms from database)
- Active promotions showcase
- Responsive design with mobile optimization
**Dependencies**: `includes/config.php`, `includes/header.php`

#### `booking.php` - Booking Engine
**Purpose**: Room reservation system with real-time calculations
**Features**:
- Dynamic room availability checking
- Real-time price calculation
- Multi-step booking process
- Guest information collection
- Payment method selection
**Security**: CSRF protection, input validation

#### `rooms.php` - Room Catalog
**Purpose**: Browse and filter available rooms
**Features**:
- Category-based filtering
- Price range filtering
- Occupancy filtering
- Sorting options
- Image galleries
**Performance**: Lazy loading for images

### 🔐 Authentication Module

#### `auth/login.php` - User Authentication
**Purpose**: Secure user login system
**Features**:
- Email/password authentication
- Session management
- Remember me functionality
- Login attempt tracking
- Password reset capability
**Security**: Rate limiting, bcrypt password hashing

#### `auth/register.php` - User Registration
**Purpose**: New user account creation
**Features**:
- Email validation
- Password strength requirements
- Profile information collection
- Email verification (optional)
- Anti-bot protection
**Validation**: Server-side and client-side validation

### 👥 User Dashboard Module

#### `user/dashboard.php` - User Portal
**Purpose**: Personalized user experience
**Features**:
- Booking overview
- Profile summary
- Loyalty points display
- Recent activity
- Quick actions
**Personalization**: User-specific content

#### `user/my-bookings.php` - Booking Management
**Purpose**: User booking history and management
**Features**:
- Booking status tracking
- Cancellation options
- Modification requests
- Payment history
- Download receipts

### 🏢 Staff Operations Module

#### `staff/staff-dashboard.php` - Operations Center
**Purpose**: Daily staff operations hub
**Features**:
- Today's check-ins/check-outs
- Pending bookings
- Room status overview
- Maintenance requests
- Staff notifications
**Real-time**: Live status updates

#### `staff/checkin.php` - Check-in Process
**Purpose**: Guest registration and room assignment
**Features**:
- Booking verification
- Guest registration
- ID scanning (placeholder)
- Key assignment
- Payment collection
**Integration**: Booking system, payment processing

#### `staff/checkout.php` - Check-out Process
**Purpose**: Guest departure and final billing
**Features**:
- Room inspection checklist
- Final bill calculation
- Additional charges
- Payment processing
- Feedback collection
**Workflow**: Multi-step checkout process

### ⚙️ Administration Module

#### `admin/admin-dashboard.php` - Admin Control Panel
**Purpose**: System overview and analytics
**Features**:
- Key performance indicators
- Revenue charts
- Occupancy statistics
- Recent activities
- System alerts
**Analytics**: Real-time data visualization

#### `admin/admin-reports.php` - Comprehensive Reporting
**Purpose**: Business intelligence and reporting
**Features**:
- Financial reports
- Occupancy reports
- Guest demographics
- Revenue analysis
- Export capabilities (CSV, PDF)
**Data Range**: Custom date filtering

#### `admin/admin-users.php` - User Management
**Purpose**: User account administration
**Features**:
- User CRUD operations
- Role assignment
- Status management
- Bulk operations
- Activity logs
**Security**: Role-based access control

### 📧 Common Components

#### `includes/config.php` - System Configuration
**Purpose**: Central configuration and utilities
**Features**:
- Database connection management
- Application constants
- Helper functions
- Security utilities
- Email configuration
**Architecture**: Singleton database class

#### `includes/header.php` - Common Header
**Purpose**: Consistent page header across application
**Features**:
- Navigation menu
- User authentication status
- Search functionality
- Cart indicator
- Responsive design
**Personalization**: Role-based menu items

## 🌐 API Endpoints

### Booking & Availability APIs
- `POST booking-process.php` - Process booking requests
  - Parameters: room_id, check_in, check_out, guest_info
  - Returns: booking_id, confirmation_number, payment_url
  
- `GET availability-check.php` - Check room availability
  - Parameters: check_in, check_out, room_category
  - Returns: available_rooms, pricing, availability_status

- `POST payment-process.php` - Handle payment processing
  - Parameters: booking_id, payment_method, amount
  - Returns: payment_status, transaction_id, receipt_url

### User Management APIs
- `POST auth/login-process.php` - User authentication
  - Parameters: email, password, remember_me
  - Returns: user_data, session_token, redirect_url

- `POST auth/register-process.php` - User registration
  - Parameters: user_info, preferences
  - Returns: user_id, verification_status, welcome_email

- `POST user/profile-update.php` - Profile management
  - Parameters: profile_data, preferences
  - Returns: update_status, updated_profile

### Admin Function APIs
- `GET admin-stats.php` - Dashboard statistics
  - Parameters: date_range, metrics
  - Returns: kpi_data, charts, trends

- `POST admin/user-management.php` - User CRUD operations
  - Parameters: action, user_data, permissions
  - Returns: operation_status, affected_users

- `POST admin/room-management.php` - Room management
  - Parameters: action, room_data, availability
  - Returns: operation_status, room_inventory

### AJAX Endpoints
- `GET ajax/search-rooms.php` - Live room search
- `POST ajax/update-booking.php` - Booking modifications
- `GET ajax/load-notifications.php` - Real-time notifications
- `POST ajax/process-payment.php` - Payment processing

## 🔧 Configuration

### Environment Variables
The system uses constants defined in `includes/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bayawan_hotel');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'Bayawan Bai Hotel');
define('SITE_URL', 'http://localhost/bayawanhotel');
define('ADMIN_EMAIL', 'bayawanbaiminihotel@gmail.com');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'bayawanbaiminihotel@gmail.com');
define('SMTP_PASSWORD', 'app_password_here');

// Application Settings
define('CURRENCY', 'PHP');
define('CURRENCY_SYMBOL', '₱');
define('DATE_FORMAT', 'Y-m-d');
define('TIME_FORMAT', 'H:i:s');

// Security Settings
define('SESSION_LIFETIME', 7200); // 2 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes
```

### Email Configuration
The system uses PHPMailer for email communications:
- **SMTP Server**: smtp.gmail.com
- **Port**: 587 (TLS)
- **Authentication**: Required
- **From Address**: bayawanbaiminihotel@gmail.com
- **Templates**: Located in `includes/email-templates/`

### File Upload Configuration
```php
// Upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', 'uploads/');
define('GALLERY_PATH', 'uploads/gallery/');
```

### Payment Gateway Configuration
```php
// GCash Configuration
define('GCASH_API_URL', 'https://api.gcash.com');
define('GCASH_PUBLIC_KEY', 'your_public_key');
define('GCASH_SECRET_KEY', 'your_secret_key');

// PayPal Configuration
define('PAYPAL_API_URL', 'https://api.paypal.com');
define('PAYPAL_CLIENT_ID', 'your_client_id');
define('PAYPAL_CLIENT_SECRET', 'your_client_secret');
```

## 🧪 Testing

### Manual Testing Checklist

#### 🔐 Authentication Testing
- [ ] User registration flow
- [ ] Email validation process
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Password reset functionality
- [ ] Session management
- [ ] Logout functionality

#### 🏨 Booking System Testing
- [ ] Room availability checking
- [ ] Booking creation process
- [ ] Price calculation accuracy
- [ ] Payment processing
- [ ] Booking confirmation emails
- [ ] Booking modification
- [ ] Booking cancellation

#### 👥 User Dashboard Testing
- [ ] Profile updates
- [ ] Booking history display
- [ ] Password change
- [ ] Loyalty points calculation
- [ ] Notification system

#### 🏢 Staff Operations Testing
- [ ] Check-in process
- [ ] Check-out process
- [ ] Room status updates
- [ ] Walk-in bookings
- [ ] Maintenance requests
- [ ] Staff scheduling

#### ⚙️ Admin Functions Testing
- [ ] User management
- [ ] Room management
- [ ] Report generation
- [ ] Analytics accuracy
- [ ] System settings
- [ ] Permission management

### Automated Testing
```bash
# Run PHP syntax checks
find . -name "*.php" -exec php -l {} \;

# Check for security vulnerabilities
composer audit

# Database integrity check
mysql -u root -p -e "CHECK TABLE bayawan_hotel.*;"
```

### Test Data
The `database.sql` file includes comprehensive sample data:
- **Users**: 3 staff accounts with different roles
- **Rooms**: 12 rooms across 4 categories
- **Amenities**: Complete hotel services list
- **Gallery**: 20+ sample images
- **Menu Items**: Full restaurant menu
- **Events**: Sample event spaces and bookings
- **Reviews**: Customer feedback examples

## 📈 Performance Considerations

### Optimization Features
- **Database Indexing**: Optimized indexes on frequently queried columns
- **Prepared Statements**: SQL injection prevention and performance
- **Image Optimization**: Compressed images with lazy loading
- **Caching**: Session-based caching for frequently accessed data
- **Minimal Dependencies**: Reduced external HTTP requests
- **Inline CSS**: Eliminates additional CSS file requests

### Recommended Server Requirements

#### Minimum Requirements
- **CPU**: 2 cores @ 2.0GHz
- **RAM**: 2GB
- **Storage**: 10GB SSD
- **Network**: 10 Mbps
- **PHP**: 7.4+
- **MySQL**: 5.7+

#### Recommended Requirements
- **CPU**: 4 cores @ 2.5GHz
- **RAM**: 4GB+
- **Storage**: 50GB SSD
- **Network**: 100 Mbps
- **PHP**: 8.0+
- **MySQL**: 8.0+

### Performance Monitoring
```php
// Enable performance monitoring in config.php
define('PERFORMANCE_MONITORING', true);
define('SLOW_QUERY_THRESHOLD', 1000); // milliseconds
```

### Caching Strategy
- **APCu**: For session data and frequently accessed configuration
- **Browser Caching**: Static assets (CSS, JS, images)
- **Database Query Caching**: Repeated complex queries
- **Output Caching**: For static content pages

## 🔄 Backup & Maintenance

### Database Backup Procedures

#### Automated Daily Backup
```bash
#!/bin/bash
# daily_backup.sh
DATE=$(date +%Y%m%d)
mysqldump -u root -p bayawan_hotel | gzip > backups/bayawan_hotel_$DATE.sql.gz
find backups/ -name "*.sql.gz" -mtime +30 -delete
```

#### Weekly Full Backup
```bash
#!/bin/bash
# weekly_backup.sh
DATE=$(date +%Y%m%d)
mysqldump -u root -p --all-databases | gzip > backups/full_backup_$DATE.sql.gz
```

### File Backup Strategy
**Critical Files to Backup**:
- All PHP application files
- `database.sql` schema file
- `composer.json` and `composer.lock`
- Upload directories (`uploads/`, `assets/`)
- Configuration files (`includes/config.php`)
- Log files (`includes/logs/`)

#### Backup Script
```bash
#!/bin/bash
# file_backup.sh
DATE=$(date +%Y%m%d)
tar -czf backups/application_$DATE.tar.gz \
    --exclude='vendor/' \
    --exclude='*.log' \
    --exclude='temp/' \
    .
```

### Maintenance Tasks

#### Daily Maintenance
- [ ] Check error logs for issues
- [ ] Monitor disk space usage
- [ ] Review failed login attempts
- [ ] Check system performance metrics

#### Weekly Maintenance
- [ ] Database optimization
- [ ] Clear temporary files
- [ ] Review user sessions
- [ ] Update security patches

#### Monthly Maintenance
- [ ] Full system backup verification
- [ ] Security audit
- [ ] Performance analysis
- [ ] Software updates

### Log Management
```php
// Log rotation in config.php
define('LOG_RETENTION_DAYS', 30);
define('MAX_LOG_SIZE', 10485760); // 10MB
```

## 🐛 Troubleshooting

### Common Issues and Solutions

#### 🔌 Database Connection Issues
**Error**: `Connection Error: SQLSTATE[HY000] [1045] Access denied`
**Causes**:
- Incorrect database credentials
- Database server not running
- Network connectivity issues
- Insufficient permissions

**Solutions**:
```bash
# Check MySQL service status
sudo systemctl status mysql

# Test database connection
mysql -u root -p -h localhost bayawan_hotel

# Reset MySQL password
sudo mysql_secure_installation
```

#### 🚫 Blank Pages / White Screen
**Error**: PHP fatal error causing blank page
**Causes**:
- Syntax errors in PHP code
- Memory limit exceeded
- File permission issues
- Missing dependencies

**Solutions**:
```php
// Enable error reporting in config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

// Check error logs
tail -f /var/log/apache2/error.log
```

#### 📧 Email Not Sending
**Error**: PHPMailer authentication failed
**Causes**:
- Incorrect SMTP credentials
- Firewall blocking SMTP
- Gmail app password issues
- SSL/TLS configuration

**Solutions**:
```php
// Test SMTP connection
$mail = new PHPMailer();
$mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPAuth = true;
```

#### 🔄 Session Issues
**Error**: User not staying logged in
**Causes**:
- Session storage permissions
- Cookie configuration issues
- Session timeout settings
- Server time synchronization

**Solutions**:
```php
// Check session configuration
phpinfo(); // Look for session settings

// Verify session storage path
session_save_path('/tmp');

// Set secure session parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
```

#### 📦 File Upload Issues
**Error**: Files not uploading successfully
**Causes**:
- Insufficient permissions
- File size exceeds limits
- Incorrect MIME types
- Disk space full

**Solutions**:
```bash
# Check upload directory permissions
chmod 755 uploads/
chown www-data:www-data uploads/

# Check PHP upload settings
php -i | grep upload
```

### Debug Mode
Enable comprehensive debugging by setting in `includes/config.php`:
```php
define('DEBUG_MODE', true);
define('LOG_LEVEL', 'DEBUG');
```

### Performance Issues
**Symptoms**: Slow page loads, timeouts
**Solutions**:
- Enable database query logging
- Check server resource usage
- Optimize database queries
- Implement caching
- Use CDN for static assets

## � Documentation

### Code Documentation Standards
- **PHPDoc**: All functions and classes documented
- **Inline Comments**: Complex logic explained
- **Database Comments**: All tables and columns documented
- **API Documentation**: Endpoints documented with examples

### User Manual Structure
```
docs/
├── user-guide/
│   ├── getting-started.md
│   ├── booking-guide.md
│   ├── payment-options.md
│   └── account-management.md
├── staff-manual/
│   ├── daily-operations.md
│   ├── check-in-checkout.md
│   ├── booking-management.md
│   └── troubleshooting.md
├── admin-guide/
│   ├── system-configuration.md
│   ├── user-management.md
│   ├── reporting.md
│   └── maintenance.md
└── technical/
    ├── api-documentation.md
    ├── database-schema.md
    ├── security-guidelines.md
    └── deployment-guide.md
```

### Training Materials
- **Video Tutorials**: Screen recordings of common tasks
- **Interactive Guides**: Step-by-step walkthroughs
- **FAQ Database**: Common questions and answers
- **Best Practices**: Recommended workflows

## 🤝 Contributing

### Development Guidelines

#### Code Standards
1. **PHP Standards**: Follow PSR-12 coding standards
2. **Security**: Use prepared statements for all database queries
3. **Validation**: Sanitize all user inputs
4. **Error Handling**: Implement proper error handling and logging
5. **Documentation**: Document all new features and functions

#### Git Workflow
```bash
# Create feature branch
git checkout -b feature/new-feature-name

# Make changes and commit
git add .
git commit -m "feat: Add new feature description"

# Push and create pull request
git push origin feature/new-feature-name
```

#### Code Review Process
- **Peer Review**: All code must be reviewed before merge
- **Testing**: Include tests for new functionality
- **Documentation**: Update relevant documentation
- **Security**: Security review for sensitive changes

### Feature Development
1. **Planning**: Create feature specification
2. **Design**: Plan database and UI changes
3. **Development**: Implement following coding standards
4. **Testing**: Comprehensive testing including edge cases
5. **Documentation**: Update user and technical documentation
6. **Deployment**: Follow deployment checklist

### Bug Reporting
Use the following template for bug reports:
```
**Bug Description**: Clear description of the issue
**Steps to Reproduce**: Detailed steps to reproduce the bug
**Expected Behavior**: What should happen
**Actual Behavior**: What actually happens
**Environment**: PHP version, MySQL version, Browser
**Screenshots**: If applicable
**Additional Info**: Any other relevant information
```

## 📞 Support

For technical support or questions, please contact:

### 📧 Email Support
- **General Inquiries**: admin@bayawanbaihotel.com
- **Technical Support**: support@bayawanbaihotel.com
- **Billing Questions**: billing@bayawanbaihotel.com

### 📞 Phone Support
- **Main Line**: +63 35 123 4567
- **Technical Support**: +63 35 123 4568
- **Emergency Hotline**: +63 35 123 4569 (24/7)

### 💬 Live Chat
Available through the admin panel during business hours:
- **Monday - Friday**: 9:00 AM - 6:00 PM PHT
- **Saturday**: 9:00 AM - 3:00 PM PHT
- **Sunday**: Closed

### 📋 Support Ticket System
Submit support tickets through the admin panel for:
- Bug reports
- Feature requests
- Technical issues
- Account problems

**Response Times**:
- **Critical Issues**: Within 2 hours
- **High Priority**: Within 8 hours
- **Normal Priority**: Within 24 hours
- **Low Priority**: Within 48 hours

## 👥 Default Users

| Role | Email | Password | Permissions |
|------|-------|----------|--------------|
| Admin | admin@bayawanbaihotel.com | admin123 | Full system access |
| Manager | manager@bayawanbaihotel.com | staff123 | Staff management, reports |
| Receptionist | reception@bayawanbaihotel.com | staff123 | Daily operations, bookings |

### 🔐 First Login Security
- Change default passwords immediately after first login
- Enable two-factor authentication (if available)
- Review and update user permissions
- Configure security settings

## 📄 License

This project is proprietary software developed for Bayawan Bai Hotel.

### 📜 License Terms
- **Copyright**: © 2026 Bayawan Bai Hotel. All rights reserved.
- **Usage**: Restricted to Bayawan Bai Hotel operations
- **Distribution**: Prohibited without explicit permission
- **Modification**: Allowed for internal use only
- **Commercial Use**: Restricted to licensed hotel operations

### ⚖️ Legal Information
- **Developer**: Bayawan Bai Hotel IT Department
- **Support**: In-house development team
- **Maintenance**: Regular updates and security patches
- **Warranty**: 90-day warranty for critical bugs

## 🏆 Credits

### 🎨 Design & Inspiration
- **Design Inspiration**: Marco Polo Plaza Cebu
- **UI/UX Design**: Bayawan Bai Hotel Design Team
- **Brand Identity**: Bayawan Bai Hotel Marketing Department

### 🛠️ Technology Credits
- **Icons**: Font Awesome 6.4.0 - https://fontawesome.com
- **Fonts**: Google Fonts (Playfair Display, Lato) - https://fonts.google.com
- **Images**: Unsplash (placeholder images) - https://unsplash.com
- **PHPMailer**: PHPMailer Library - https://github.com/PHPMailer/PHPMailer

### 👥 Development Team
- **Lead Developer**: [Lead Developer Name]
- **Backend Development**: [Developer Names]
- **Frontend Development**: [Developer Names]
- **Database Design**: [DBA Name]
- **Quality Assurance**: [QA Team Names]
- **Project Management**: [Project Manager Name]

### 🙏 Special Thanks
- Bayawan City Tourism Office
- Negros Oriental Tourism Board
- Local hospitality industry partners
- Beta testing participants
- Hotel staff for feedback and requirements

---

## 📈 System Statistics

### 📊 Current Version Information
- **Version**: 1.0.0
- **Release Date**: March 2026
- **Last Updated**: March 2026
- **PHP Version Required**: 7.4+
- **MySQL Version Required**: 5.7+

### 🏨 Hotel Configuration
- **Total Rooms**: Configurable (default: 50+ rooms)
- **Room Categories**: 4+ categories
- **Staff Capacity**: 100+ users
- **Concurrent Users**: 500+ supported
- **Daily Bookings**: 1000+ processed

### 📱 Mobile Compatibility
- **Responsive Design**: Fully responsive
- **Mobile Browsers**: iOS Safari, Chrome Mobile
- **Tablet Support**: iPad, Android tablets
- **PWA Features**: Progressive Web App ready

---

## 🔮 Future Roadmap

### 🚀 Upcoming Features (Version 1.1)
- [ ] **Mobile App**: Native iOS and Android applications
- [ ] **Channel Manager**: Integration with Booking.com, Agoda, Expedia
- [ ] **AI Chatbot**: 24/7 customer service automation
- [ ] **Revenue Management**: Dynamic pricing algorithms
- [ ] **Housekeeping Module**: Staff scheduling and room status tracking

### 🌟 Advanced Features (Version 1.2)
- [ ] **Loyalty Program**: Points, tiers, and rewards system
- [ ] **Multi-property Support**: Manage multiple hotel locations
- [ ] **Advanced Analytics**: Predictive analytics and business intelligence
- [ ] **API Integration**: Third-party service integrations
- [ ] **Blockchain**: Secure payment processing

### 🏢 Enterprise Features (Version 2.0)
- [ ] **Cloud Deployment**: AWS/Azure cloud hosting
- [ ] **Microservices Architecture**: Scalable service-oriented design
- [ ] **Real-time Synchronization**: Multi-device real-time updates
- [ ] **Advanced Security**: Biometric authentication, fraud detection
- [ ] **Global Support**: Multi-language, multi-currency support

---

## 📞 Emergency Contacts

### 🚨 Critical System Issues
For system-wide outages or critical security issues:
- **Emergency Hotline**: +63 35 123 4569 (24/7)
- **On-call Engineer**: Available for emergency support
- **Response Time**: Within 30 minutes for critical issues

### 🔧 Technical Support escalation
1. **Level 1**: Admin panel documentation and FAQs
2. **Level 2**: Email support (response within 24 hours)
3. **Level 3**: Phone support (business hours)
4. **Level 4**: Emergency on-call support (critical issues only)

---

## 📝 Recent Updates

This section documents all recently added features, modified files, and system improvements.

**Last Updated**: March 28, 2026

---

### 🌟 New Features

#### 1. **360° Virtual Tour System**
**New Files:**
- `virtual-tour.php` - Guest-facing virtual tour viewer with panoramic room exploration
- `event-virtual-tour.php` - 360° virtual tours for event spaces
- `admin/admin-virtual-tours.php` - Admin panel for managing virtual tours

**Description**: Immersive 360-degree panoramic viewing system allowing guests to explore rooms and event spaces before booking. Uses Pannellum library for interactive viewing with hotspot navigation.

**Key Features:**
- Full 360° rotation (left, right, up, down)
- Interactive hotspots with information tooltips
- Room-to-room scene navigation
- Mobile responsive design
- Admin CRUD operations for tour management

---

#### 2. **Calendar & Scheduling System**
**New Files:**
- `admin/admin-calendar.php` - Admin calendar dashboard for bookings and events
- `staff/staff-calendar.php` - Staff calendar view for daily operations
- `user/user-calendar.php` - User calendar showing personal bookings
- `api/calendar-events.php` - API endpoint for calendar event data

**Description**: Comprehensive calendar system for visualizing bookings, events, staff schedules, and maintenance tasks across different user roles.

---

#### 3. **Guest Rating & Review System**
**New Files:**
- `admin/admin-ratings.php` - Admin interface for managing guest ratings and reviews
- `api/submit-rating.php` - API endpoint for submitting ratings
- `includes/rating-prompt.php` - Component for prompting guests to rate their stay

**Description**: Post-stay rating system allowing guests to provide feedback on their experience. Includes admin moderation tools and automated rating prompts.

---

#### 4. **Event Space Details Page**
**New Files:**
- `event-space-details.php` - Detailed event space information with amenities and pricing

**Description**: Enhanced event space showcase with detailed capacity information, amenities, pricing tiers, and virtual tour integration.

---

### 🔄 Updated Files

#### Admin Module Updates
| File | Changes Made |
|------|-------------|
| `admin/admin-analytics.php` | Enhanced reporting with new metrics and visualization improvements |
| `admin/admin-event-bookings.php` | Updated booking management interface with status tracking |
| `admin/admin-maintenance.php` | Added maintenance scheduling and staff assignment features |
| `admin/admin-promotions.php` | Improved promotional campaign management tools |
| `admin/admin-reports.php` | Added new report types and export functionality |
| `admin/admin-staff-schedules.php` | Enhanced scheduling interface with calendar integration |
| `admin/admin-header.php` | Updated navigation to include new calendar and virtual tour sections |

#### Staff Module Updates
| File | Changes Made |
|------|-------------|
| `staff/confirm-booking.php` | Improved booking confirmation workflow with email notifications |
| `staff/staff-foods-orders.php` | Enhanced food order management with status tracking |
| `staff/staff-maintenance.php` | Updated maintenance request handling and completion tracking |
| `includes/staff-header.php` | Added calendar navigation for staff schedule viewing |

#### User Module Updates
| File | Changes Made |
|------|-------------|
| `user/my-bookings.php` | Added booking status indicators and quick action buttons |
| `user/my-event-bookings.php` | Enhanced event booking display with cancellation options |
| `user/my-food-orders.php` | Improved food order history with reorder functionality |
| `includes/user-header.php` | Added calendar link for personal booking overview |

#### Core System Updates
| File | Changes Made |
|------|-------------|
| `includes/config.php` | Added new configuration constants for virtual tours and ratings |
| `includes/notifications.php` | Enhanced notification system with new alert types |
| `includes/logs/activity.log` | Activity logging updates for new features |
| `rooms.php` | Integrated virtual tour preview thumbnails |
| `room-details.php` | Added 360° virtual tour viewer integration |
| `events.php` | Updated event space listings with virtual tour links |
| `event-space-details.php` | Added detailed capacity and pricing information |
| `dining.php` | Enhanced menu display with improved categorization |
| `foods-details.php` | Updated food item presentation with nutritional info |
| `gallery.php` | Improved image gallery with categorization |
| `order-now.php` | Enhanced food ordering flow with real-time availability |
| `payment-process.php` | Updated payment handling with new gateway integrations |
| `food-order-payment-process.php` | Improved food order payment processing |

---

### 🐛 Bug Fixes

| Issue | Resolution |
|-------|-----------|
| Calendar date parsing | Fixed timezone handling for booking dates |
| Virtual tour image loading | Resolved path issues for uploaded panorama images |
| Rating submission validation | Added proper input sanitization and validation |
| Staff schedule conflicts | Fixed overlapping shift detection logic |
| Notification delivery | Corrected email notification triggers for bookings |

---

### 🗄️ Database Changes

#### New Tables Added

**`room_virtual_tours`** - Stores 360° virtual tour data
- `tour_id` (Primary Key)
- `category_id` (Foreign Key to room_categories)
- `panorama_image` - Path to equirectangular image
- `thumbnail_image` - Preview thumbnail path
- `title`, `description` - Tour metadata
- `is_active`, `display_order` - Display controls

**`virtual_tour_hotspots`** - Interactive hotspot definitions
- `hotspot_id` (Primary Key)
- `tour_id` (Foreign Key)
- `hotspot_type` - Type (info, scene, link)
- `pitch`, `yaw` - Spatial coordinates
- `text`, `target_tour_id`, `target_url` - Interaction data

**`event_virtual_tours`** - Event space virtual tours
- Similar structure to room_virtual_tours
- Links to event_spaces table

**`guest_ratings`** - Guest review and rating storage
- `rating_id` (Primary Key)
- `booking_id`, `user_id` (Foreign Keys)
- `rating` - Numerical rating (1-5)
- `review_text` - Written feedback
- `status` - Moderation status
- `created_at`, `updated_at` - Timestamps

#### Modified Tables

**`database.sql`** - Updated schema including:
- New virtual tour tables
- Guest ratings table
- Additional indexes for performance
- Updated foreign key constraints

---

### 🖼️ New Assets Added

**Image Uploads:**
- Room category images (`assets/images/rooms/`)
- Event space photos (`assets/images/events/`)
- Food and menu images (`assets/images/foods/`, `assets/images/menu/`)
- Gallery images (`assets/images/gallery/`)
- Amenity photos (`assets/images/amenities/`)

**Virtual Tour Assets:**
- 360° panorama images (`assets/uploads/virtual_tours/`)
- Event space panoramas (`assets/uploads/event_virtual_tours/`)
- Thumbnail previews for all tours

---

### 📋 Setup Instructions for New Features

#### Virtual Tour Setup

1. **Database**: Ensure `room_virtual_tours` and `virtual_tour_hotspots` tables exist
2. **Upload Directory**: Create `assets/uploads/virtual_tours/` with write permissions
3. **Dependencies**: Pannellum library loads via CDN (no local installation needed)
4. **Admin Access**: Navigate to Admin > Virtual Tours to add tours

**Image Requirements:**
- Format: Equirectangular projection (2:1 aspect ratio)
- Resolution: Minimum 4096x2048px recommended
- File Size: Under 5MB for optimal loading
- Format: JPG or PNG

#### Calendar System Setup

1. **Database**: Ensure calendar-related tables are present in schema
2. **Permissions**: Staff and admin accounts need calendar access permissions
3. **API Access**: Verify `api/calendar-events.php` is accessible for AJAX loading

#### Rating System Setup

1. **Database**: Run schema update to create `guest_ratings` table
2. **Configuration**: Set rating display options in `includes/config.php`
3. **Email Templates**: Customize rating request emails in notification settings

---

### 🔧 Configuration Updates

Add these new constants to `includes/config.php`:

```php
// Virtual Tour Configuration
define('VIRTUAL_TOUR_ENABLED', true);
define('VIRTUAL_TOUR_UPLOAD_PATH', 'assets/uploads/virtual_tours/');
define('VIRTUAL_TOUR_MAX_SIZE', 5242880); // 5MB

// Rating System Configuration
define('RATINGS_ENABLED', true);
define('MIN_RATING_PROMPT_DAYS', 1); // Days after checkout to prompt
define('MAX_RATING_PROMPT_DAYS', 7); // Maximum days to show prompt
```

---

### 🚀 Migration Notes

For existing installations, follow these steps to enable new features:

1. **Update Database Schema**:
   ```bash
   mysql -u root -p bayawan_hotel < database/database.sql
   ```

2. **Create Upload Directories**:
   ```bash
   mkdir -p assets/uploads/virtual_tours
   mkdir -p assets/uploads/event_virtual_tours
   chmod 755 assets/uploads/virtual_tours
   chmod 755 assets/uploads/event_virtual_tours
   ```

3. **Clear Browser Cache**: Users should clear cache to load updated CSS/JS

4. **Review Permissions**: Ensure staff roles have appropriate access to new features

---

**Bayawan Bai Hotel Management System**  
*Complete Hotel Management Solution*  
© 2026 Bayawan Bai Hotel. All rights reserved.

**📍 Location**: Bayawan City, Negros Oriental, Philippines  
**🌐 Website**: www.bayawanbaihotel.com  
**📧 Email**: info@bayawanbaihotel.com

---

*Last Updated: March 23, 2026*  
*Documentation Version: 1.0.0*

This project is proprietary software developed for Bayawan Bai Hotel.

## 🏆 Credits

- Design inspired by Marco Polo Plaza Cebu
- Icons by Font Awesome
- Fonts by Google Fonts
- Images from Unsplash (placeholder images)

---

**Bayawan Bai Hotel Management System**  
© 2026 Bayawan Bai Hotel. All rights reserved.
