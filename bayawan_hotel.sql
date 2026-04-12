-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 12, 2026 at 02:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bayawan_hotel`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `amenities`
--

CREATE TABLE `amenities` (
  `amenity_id` int(11) NOT NULL,
  `amenity_name` varchar(100) NOT NULL,
  `category` enum('spa','gym','pool','wellness','other') NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `operating_hours` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `amenities`
--

INSERT INTO `amenities` (`amenity_id`, `amenity_name`, `category`, `description`, `price`, `duration_minutes`, `image`, `is_available`, `operating_hours`) VALUES
(1, 'Swedish Massage', 'spa', 'Relaxing full-body massage to relieve stress and tension', 1500.00, 60, NULL, 1, '9:00 AM - 9:00 PM'),
(2, 'Hot Stone Therapy', 'spa', 'Therapeutic massage using heated stones for deep relaxation', 2000.00, 90, NULL, 1, '9:00 AM - 9:00 PM'),
(3, 'Facial Treatment', 'spa', 'Rejuvenating facial with natural ingredients', 1200.00, 40, 'images/amenities/1774690506_exercise1.png', 1, '10:00 AM - 8:00 PM'),
(4, 'Infinity Pool Access', 'pool', 'Access to our stunning infinity pool with bay views', 0.00, NULL, NULL, 1, '6:00 AM - 10:00 PM'),
(5, 'Fitness Center', 'gym', 'State-of-the-art gym equipment and personal training', 0.00, NULL, NULL, 1, '24 Hours'),
(6, 'Yoga Session', 'wellness', 'Guided yoga session by the pool or beach', 500.00, 60, NULL, 1, '6:00 AM - 7:00 AM Daily');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `booking_ref` varchar(50) DEFAULT NULL COMMENT 'Unique booking reference number (BBHYYYYMMDDXXXXXX)',
  `user_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `adults` int(11) DEFAULT 1,
  `children` int(11) DEFAULT 0,
  `nights` int(11) NOT NULL,
  `room_rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled','no_show') DEFAULT 'pending',
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `payment_method` enum('gcash','paypal','credit_card','cash','bank_transfer') DEFAULT 'cash',
  `special_requests` text DEFAULT NULL,
  `booking_source` enum('website','walk_in','phone','ota') DEFAULT 'website',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `checked_out_at` timestamp NULL DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `booking_ref`, `user_id`, `room_id`, `category_id`, `check_in`, `check_out`, `adults`, `children`, `nights`, `room_rate`, `total_amount`, `status`, `payment_status`, `payment_method`, `special_requests`, `booking_source`, `created_at`, `updated_at`, `checked_in_at`, `checked_out_at`, `is_archived`, `is_deleted`) VALUES
(54, 'BBH20260331AAF91C', 28, 1, 1, '2026-03-31', '2026-04-03', 2, 0, 1, 2500.00, 2500.00, 'checked_out', 'paid', 'gcash', '', 'website', '2026-03-31 14:42:18', '2026-04-09 14:21:44', '2026-03-31 14:44:14', '2026-04-04 01:50:40', 0, 1),
(55, 'BBH202604046205AB', 28, 2, 1, '2026-04-04', '2026-04-05', 2, 0, 1, 2500.00, 2500.00, 'checked_out', 'paid', 'gcash', '', 'website', '2026-04-04 03:22:30', '2026-04-09 14:10:31', '2026-04-04 03:22:58', '2026-04-04 03:36:44', 0, 1),
(56, 'BBH202604047D099D', 28, 3, 1, '2026-04-06', '2026-04-07', 2, 0, 1, 2500.00, 2500.00, 'cancelled', 'refunded', 'gcash', '', 'website', '2026-04-04 03:50:47', '2026-04-09 14:10:29', NULL, NULL, 0, 1),
(57, 'BBH20260404BC20C1', 28, 9, 3, '2026-04-04', '2026-04-05', 2, 0, 1, 7500.00, 7500.00, 'cancelled', 'refunded', 'gcash', '', 'website', '2026-04-04 03:51:39', '2026-04-09 14:10:27', NULL, NULL, 0, 1),
(58, 'BBH20260409C11651', 28, 9, 3, '2026-04-09', '2026-04-11', 2, 0, 2, 7500.00, 15000.00, 'cancelled', 'refunded', 'gcash', '', 'website', '2026-04-09 14:05:16', '2026-04-09 14:10:23', NULL, NULL, 0, 1),
(59, 'BBH2026040918AD5C', 28, 9, 3, '2026-04-09', '2026-04-11', 2, 0, 2, 7500.00, 15000.00, 'checked_out', 'paid', 'gcash', '', 'website', '2026-04-09 14:13:21', '2026-04-09 16:30:10', '2026-04-09 14:19:18', '2026-04-09 16:30:10', 0, 0),
(60, 'BBH202604101D87B3', 28, 11, 4, '2026-04-10', '2026-04-11', 2, 0, 1, 4500.00, 4500.00, 'pending', 'paid', 'gcash', '', 'website', '2026-04-10 13:15:13', '2026-04-10 13:15:13', NULL, NULL, 0, 0),
(61, 'BBH202604114A1F9A', 28, 12, 4, '2026-04-11', '2026-04-12', 2, 0, 1, 4500.00, 4500.00, 'pending', 'paid', 'gcash', '', 'website', '2026-04-11 09:07:48', '2026-04-11 09:07:48', NULL, NULL, 0, 0),
(62, 'BBH20260411C1F03F', 28, 5, 2, '2026-04-11', '2026-04-12', 2, 0, 1, 3500.00, 3500.00, 'pending', 'paid', 'gcash', '', 'website', '2026-04-11 09:24:12', '2026-04-11 09:24:12', NULL, NULL, 0, 0),
(63, 'BBH202604114368AD', 28, 6, 2, '2026-04-11', '2026-04-13', 2, 0, 2, 3500.00, 7000.00, 'checked_in', 'paid', 'gcash', '', 'website', '2026-04-11 09:37:08', '2026-04-11 11:07:17', '2026-04-11 11:07:17', NULL, 0, 0),
(64, 'BBH202604114B22C9', 28, 3, 1, '2026-04-11', '2026-04-13', 2, 0, 2, 2500.00, 5000.00, 'checked_out', 'paid', 'gcash', '', 'website', '2026-04-11 09:43:00', '2026-04-11 13:28:02', '2026-04-11 09:54:14', '2026-04-11 10:49:56', 0, 0),
(65, 'BBH20260411FE8E9E', 29, 10, 3, '2026-04-11', '2026-04-24', 1, 0, 13, 7500.00, 97500.00, 'pending', 'partial', '', 'Dapat gaming room dapat. Dili lag wifi dapat bugnaw kaayo', 'website', '2026-04-11 13:45:51', '2026-04-11 13:45:51', NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `booking_charges`
--

CREATE TABLE `booking_charges` (
  `charge_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `charge_type` enum('minibar','room_service','laundry','damage','late_checkout','other') DEFAULT 'other',
  `status` enum('active','waived','paid') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `booking_logs`
--

CREATE TABLE `booking_logs` (
  `log_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_logs`
--

INSERT INTO `booking_logs` (`log_id`, `booking_id`, `action`, `details`, `created_by`, `created_at`) VALUES
(4, 54, 'rescheduled', 'Rescheduled from 2026-03-31 - 2026-04-02 to 2026-04-02 - 2026-04-03', 28, '2026-03-31 14:42:49'),
(5, 57, 'cancelled', 'Booking cancelled with refund of ₱7,500.00 via gcash', 28, '2026-04-04 03:52:33'),
(6, 56, 'rescheduled', 'Rescheduled from 2026-04-04 - 2026-04-05 to 2026-04-06 - 2026-04-07', 28, '2026-04-04 03:52:47'),
(7, 56, 'cancelled', 'Booking cancelled with refund of ₱2,500.00 via gcash', 28, '2026-04-04 03:52:56'),
(8, 58, 'cancelled', 'Cancelled by user from pending list', 28, '2026-04-09 14:09:12');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_context`
--

CREATE TABLE `chatbot_context` (
  `context_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `context_key` varchar(50) NOT NULL,
  `context_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_knowledge`
--

CREATE TABLE `chatbot_knowledge` (
  `knowledge_id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL,
  `question_pattern` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `keywords` text DEFAULT NULL,
  `priority` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chatbot_knowledge`
--

INSERT INTO `chatbot_knowledge` (`knowledge_id`, `category`, `question_pattern`, `answer`, `keywords`, `priority`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'greeting', 'hello|hi|hey|greetings', 'Hello! Welcome to Bayawan Bai Hotel. I\'m your virtual assistant. How can I help you today?', 'hello,hi,hey,greetings,welcome', 10, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(2, 'booking', 'book.*room|reserve.*room|make.*booking|how.*book', 'You can book a room by visiting our Rooms page and selecting your preferred dates. Would you like me to guide you to the booking page?', 'book,room,reservation,booking,reserve', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(3, 'booking', 'cancel.*booking|how.*cancel', 'To cancel a booking, please go to My Bookings in your dashboard. You can cancel confirmed bookings there. Note that cancellation policies may apply.', 'cancel,booking,refund', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(4, 'booking', 'modify.*booking|change.*booking|edit.*booking', 'To modify your booking, please contact our front desk at +63 35 123 4567 or email us at info@bayawanbaihotel.com with your booking reference.', 'modify,change,edit,booking', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(5, 'booking', 'booking.*status|check.*booking', 'You can check your booking status in the My Bookings section of your dashboard. You\'ll see all your current and past reservations there.', 'status,booking,check,reservation', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(6, 'rooms', 'room.*type|types.*room|what.*rooms', 'We offer several room types: Deluxe Rooms, Superior Rooms, Family Suites, and Presidential Suites. Each comes with different amenities and pricing. Would you like details on a specific room type?', 'room,type,deluxe,superior,family,suite', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(7, 'rooms', 'room.*price|how.*much|price.*room', 'Our room rates vary by type and season. Deluxe rooms start at ₱2,500/night, Superior at ₱3,500/night, Family Suites at ₱5,000/night, and Presidential Suites at ₱8,000/night. Check our Rooms page for current rates.', 'price,cost,rate,expensive,cheap', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(8, 'rooms', 'room.*amenity|what.*include|facilities.*room', 'All our rooms include: Free WiFi, Air Conditioning, Flat-screen TV, Mini-bar, Coffee/Tea maker, Room service, Daily housekeeping, and Premium toiletries. Suites additionally include living areas and kitchenettes.', 'amenity,wifi,ac,tv,mini-bar, facilities', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(9, 'dining', 'restaurant|dining|food|eat', 'Our hotel features the Bayawan Bistro restaurant serving local and international cuisine. We also offer 24/7 room service. Would you like to see our menu or make a reservation?', 'restaurant,dining,food,eat,menu,bistro', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(10, 'dining', 'room.*service|order.*food|food.*order', 'Yes! We offer 24/7 room service. You can order food directly from your room through our website or by calling the front desk. Check out the Order Now section for our full menu!', 'room service,order,food,delivery', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(11, 'dining', 'breakfast|breakfast.*include', 'Yes, we offer complimentary breakfast for all guests. It\'s served from 6:00 AM to 10:00 AM at our Bayawan Bistro restaurant. We serve both Filipino and continental breakfast options.', 'breakfast,morning,food,complimentary', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(12, 'events', 'event.*space|venue|conference|meeting.*room', 'We have several event spaces available: Grand Ballroom (up to 300 guests), Conference Rooms (20-50 guests), and Outdoor Garden venues. All spaces come with audio-visual equipment and catering options.', 'event,venue,conference,meeting,ballroom,wedding', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(13, 'events', 'book.*event|reserve.*venue', 'To book an event space, please visit our Events page or contact our events team at events@bayawanbaihotel.com. We recommend booking at least 2 weeks in advance for large events.', 'book event,reserve venue,party,wedding', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(14, 'amenities', 'pool|swimming', 'Yes, we have a beautiful outdoor swimming pool open from 6:00 AM to 10:00 PM. It\'s complimentary for all hotel guests. We also have a poolside bar for refreshments.', 'pool,swim,swimming', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(15, 'amenities', 'gym|fitness', 'Yes, our fitness center is available 24/7 for hotel guests. It features cardio machines, weight equipment, and yoga mats. Located on the 2nd floor.', 'gym,fitness,exercise,workout', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(16, 'amenities', 'spa|massage', 'We offer spa services including massages, facials, and body treatments. Operating hours are 9:00 AM to 9:00 PM. Reservations are recommended. Call extension 5555 from your room.', 'spa,massage,relax,treatment', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(17, 'amenities', 'wifi|internet', 'Yes! We offer complimentary high-speed WiFi throughout the hotel. The network name is \"BayawanBai-Guest\" - no password required.', 'wifi,internet,connection,online', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(18, 'amenities', 'parking|car', 'Yes, we offer complimentary parking for hotel guests. We have both outdoor and covered parking areas. Valet service is available upon request.', 'parking,car,vehicle', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(19, 'location', 'where.*located|address|location', 'Bayawan Bai Hotel is located in Bayawan City, Negros Oriental, Philippines. Our address is: Bayawan City, Negros Oriental, Philippines 6211', 'location,address,where,find', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(20, 'location', 'airport.*shuttle|transport|pick.*up', 'We offer airport shuttle services from Dumaguete Airport (Sibulan) for an additional fee. Please contact us at least 24 hours in advance to arrange pickup.', 'airport,shuttle,transport,pickup', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(21, 'location', 'nearby|attraction|places.*visit', 'Bayawan City has several attractions nearby: Niludhan Falls, Bayawan Boulevard, and local markets. Our front desk can provide tourist information and arrange tours.', 'nearby,attraction,tour,visit,places', 6, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(22, 'policies', 'check.*in|checkin|arrival', 'Our standard check-in time is 2:00 PM. Early check-in may be available upon request, subject to room availability. Please contact us in advance if you need early check-in.', 'check-in,arrival,checkin,time', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(23, 'policies', 'check.*out|checkout|departure', 'Our standard check-out time is 12:00 PM (noon). Late check-out may be available upon request, subject to availability and may incur additional charges.', 'check-out,departure,checkout,leave', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(24, 'policies', 'pet.*policy|bring.*pet|dog|cat', 'We are a pet-friendly hotel! Small pets are allowed in designated rooms for an additional cleaning fee of ₱500 per stay. Please inform us when booking if you\'re bringing a pet.', 'pet,dog,cat,animal', 7, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(25, 'policies', 'payment|pay|credit.*card', 'We accept cash (Philippine Peso), credit cards (Visa, Mastercard), and GCash. A valid credit card is required to guarantee reservations.', 'payment,pay,credit card,gcash', 8, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(26, 'support', 'help|support|assistance', 'I\'m here to help! I can assist with bookings, room information, dining options, amenities, and general hotel inquiries. What do you need help with?', 'help,support,assist', 10, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(27, 'support', 'contact|phone|email|reach.*you', 'You can reach us at:\n📞 Phone: +63 35 123 4567\n📧 Email: info@bayawanbaihotel.com\n🌐 Website: www.bayawanbaihotel.com\n\nFront desk is available 24/7!', 'contact,phone,email,reach,call', 9, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(28, 'support', 'complaint|problem|issue|unhappy', 'I\'m sorry to hear you\'re experiencing an issue. For immediate assistance with complaints or urgent problems, please contact our front desk directly at +63 35 123 4567 or speak to a manager on duty.', 'complaint,problem,issue,unhappy,bad', 10, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(29, 'support', 'speak.*human|talk.*person|real.*person|staff', 'I can connect you with a staff member. Please hold while I transfer you to our front desk, or you can call us directly at +63 35 123 4567 for immediate assistance.', 'human,person,staff,agent,representative', 10, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02'),
(30, 'goodbye', 'bye|goodbye|see.*you|thank.*you|thanks', 'Thank you for chatting with me! If you need any further assistance, feel free to ask. Have a wonderful stay at Bayawan Bai Hotel!', 'bye,goodbye,thanks,thank you', 10, 1, '2026-03-25 11:16:02', '2026-03-25 11:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message_type` enum('user','bot','staff') NOT NULL,
  `message` text NOT NULL,
  `intent` varchar(50) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`message_id`, `session_id`, `user_id`, `message_type`, `message`, `intent`, `metadata`, `is_read`, `created_at`) VALUES
(63, 5, 28, 'user', 'hi', NULL, NULL, 0, '2026-03-30 01:23:27'),
(64, 5, NULL, 'bot', 'Hello! Welcome to Bayawan Bai Hotel. I\'m your virtual hotel assistant, and I\'m happy to help you today.\n\nHow may I assist you? Are you looking for information about our rooms, amenities, dining, or anything else?', 'gemini_ai', NULL, 1, '2026-03-30 01:23:30'),
(65, 5, 28, 'user', 'what is bayawan bai hotel', NULL, NULL, 0, '2026-03-30 01:23:40'),
(66, 5, NULL, 'bot', 'Welcome to Bayawan Bai Hotel!\n\nWe are a modern and welcoming hotel dedicated to providing a comfortable and memorable stay for our guests in Bayawan. We offer a range of well-appointed accommodations, excellent dining options, versatile event spaces, and various amenities designed to ensure a pleasant experience whether you\'re traveling for business or leisure.\n\nHow may I assist you today?', 'gemini_ai', NULL, 1, '2026-03-30 01:23:44'),
(67, 5, 28, 'user', 'what are available rooms', NULL, NULL, 0, '2026-03-30 01:24:05'),
(68, 5, NULL, 'bot', 'Hello! I\'d be happy to help you with our available rooms at Bayawan Bai Hotel.\n\nWe offer several comfortable options designed to suit your needs:\n\n*   **Standard Rooms:** Perfect for solo travelers or couples, featuring essential amenities.\n*   **Deluxe Rooms:** More spacious with enhanced features for added comfort.\n*   **Family Suites:** Ideal for groups or families, offering multiple beds and living space.\n\nRates vary depending on the season and specific dates. To get the most accurate availability and pricing for your intended stay, please let us know your desired check-in and check-out dates, along with the number of guests.\n\nYou can also visit our official website or contact our front desk directly for real-time availability and to make a reservation. We look forward to assisting you!', 'gemini_ai', NULL, 1, '2026-03-30 01:24:08'),
(69, 5, 28, 'user', 'what is the official website', NULL, NULL, 0, '2026-03-30 01:24:39'),
(70, 5, NULL, 'bot', 'Hello! Welcome to Bayawan Bai Hotel.\n\nOur official website is currently under development to bring you an even better online experience.\n\nIn the meantime, you can find more information and make reservations by contacting us directly at:\n*   **Phone:** [Insert Hotel Phone Number Here, e.g., (035) 430-1234]\n*   **Email:** [Insert Hotel Email Address Here, e.g., info@bayawanbaihotel.com]\n*   You can also find us on popular booking platforms like Booking.com or Agoda.\n\nIs there anything specific I can help you with today?', 'gemini_ai', NULL, 1, '2026-03-30 01:24:42'),
(71, 5, 28, 'user', 'kay thanks', NULL, NULL, 0, '2026-03-30 01:25:01'),
(72, 5, NULL, 'bot', 'You\'re most welcome! I\'m here to assist you with anything you need regarding Bayawan Bai Hotel. Please feel free to ask about our rooms, amenities, dining, events, or anything else that comes to mind. How may I help you today?', 'gemini_ai', NULL, 1, '2026-03-30 01:25:05'),
(73, 5, 28, 'user', 'Hi', NULL, NULL, 0, '2026-03-30 03:37:33'),
(74, 5, NULL, 'bot', 'Hello! Welcome to Bayawan Bai Hotel. I\'m your virtual hotel assistant.\n\nHow may I help you today? I can provide information on our rooms, amenities, dining, events, local attractions, and more. Feel free to ask!', 'gemini_ai', NULL, 1, '2026-03-30 03:37:35'),
(75, 5, 28, 'user', 'hi', NULL, NULL, 0, '2026-04-01 12:50:34'),
(76, 5, NULL, 'bot', 'Hello! Welcome to Bayawan Bai Hotel. I\'m your virtual hotel assistant, and I\'m happy to help you today.\n\nHow can I assist you? Are you looking for information about our rooms, amenities, dining, or perhaps local attractions? Feel free to ask!', 'gemini_ai', NULL, 1, '2026-04-01 12:50:37');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_message_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`session_id`, `user_id`, `session_token`, `status`, `created_at`, `updated_at`, `last_message_at`) VALUES
(2, 24, '2cbfe58e39538a8d52c6222cd911dfd56173571fa0c95036b5f7e345fbd7509f', 'active', '2026-03-26 05:22:47', '2026-03-26 05:22:47', NULL),
(3, 1, '56d93ceba9bfce1b0343d8b68d631a095dd46af00584839d61cce151b802dd0b', 'active', '2026-03-27 05:28:25', '2026-03-27 05:28:25', NULL),
(5, 28, '51463cbeeec412d385cb5808a69adf9e729802d1cb8e33afe728e7545638e754', 'active', '2026-03-30 01:04:48', '2026-04-01 12:50:37', '2026-04-01 12:50:37'),
(6, 2, '733a1f9ade774c2ac77f588c5497610bead58d88c9ecc811755c0bb8046f70b5', 'active', '2026-03-31 14:27:31', '2026-03-31 14:27:31', NULL),
(7, 29, 'e30bf86659941f80f12e46de43279abd8bd967375fa14e0175ccda50452abed3', 'active', '2026-04-11 13:42:48', '2026-04-11 13:42:48', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `floor` int(11) DEFAULT NULL,
  `status` enum('available','reserved','occupied') DEFAULT 'available',
  `maintenance_status` enum('clean','under_maintenance') DEFAULT 'clean',
  `special_features` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `category_id`, `floor`, `status`, `maintenance_status`, `special_features`, `created_at`) VALUES
(1, 'Conference Room A', NULL, 1, 'available', 'clean', 'Projector, Whiteboard, Video Conferencing', '2026-03-21 11:53:51'),
(2, 'Conference Room B', NULL, 1, 'available', 'clean', 'TV Screen, Whiteboard', '2026-03-21 11:53:51'),
(3, 'Function Hall 1', NULL, 2, 'available', 'clean', 'Stage, Sound System, Dance Floor', '2026-03-21 11:53:51'),
(4, 'Function Hall 2', NULL, 2, 'available', 'clean', 'Stage, Sound System, Projector', '2026-03-21 11:53:51'),
(5, 'Garden Pavilion', NULL, 0, 'available', 'clean', 'Outdoor Setup, Tent Options, Garden Setting', '2026-03-21 11:53:51'),
(6, 'Rooftop Terrace', NULL, 5, 'available', 'clean', 'City & Bay Views, Bar Area, Lounge Seating', '2026-03-21 11:53:51');

-- --------------------------------------------------------

--
-- Table structure for table `event_bookings`
--

CREATE TABLE `event_bookings` (
  `event_booking_id` int(11) NOT NULL,
  `event_ref` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `space_id` int(11) NOT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `guests_count` int(11) DEFAULT NULL,
  `catering_required` tinyint(1) DEFAULT 0,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','partial','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `transaction_id` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quoted_price` decimal(10,2) DEFAULT NULL,
  `inquiry_name` varchar(200) DEFAULT NULL,
  `inquiry_email` varchar(255) DEFAULT NULL,
  `inquiry_phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_bookings`
--

INSERT INTO `event_bookings` (`event_booking_id`, `event_ref`, `user_id`, `space_id`, `event_type`, `event_date`, `start_time`, `end_time`, `guests_count`, `catering_required`, `special_requests`, `status`, `payment_status`, `payment_method`, `amount_paid`, `transaction_id`, `paid_at`, `updated_at`, `quoted_price`, `inquiry_name`, `inquiry_email`, `inquiry_phone`, `created_at`, `is_archived`, `is_deleted`) VALUES
(10, 'INQ-000010', 28, 2, 'birthday', '2026-03-30', '13:45:00', '17:50:00', 50, 1, '', 'completed', 'paid', 'gcash', 40000.00, 'GCASH-4BB29D', '2026-03-30 05:22:28', '2026-04-09 14:10:34', 40000.00, 'Kylle Acibron', 'kylleacibron@gmail.com', '09455470173', '2026-03-30 04:44:20', 0, 1),
(11, 'INQ-000011', 28, 2, 'conference', '2026-04-10', '00:02:00', '22:06:00', 120, 1, '', 'completed', 'pending', NULL, 0.00, NULL, NULL, '2026-04-11 10:09:17', 0.00, 'Kylle Acibron', 'kylleacibron@gmail.com', '09455470173', '2026-04-09 14:03:26', 0, 1),
(12, 'INQ-000012', 28, 3, 'wedding', '2026-04-10', '10:14:00', '22:14:00', 120, 1, '', 'completed', 'paid', 'gcash', 20000.00, 'GCASH-2B8868', '2026-04-09 15:34:42', '2026-04-09 15:56:31', 20000.00, 'Kylle Acibron', 'kylleacibron@gmail.com', '09455470173', '2026-04-09 14:14:23', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `event_spaces`
--

CREATE TABLE `event_spaces` (
  `space_id` int(11) NOT NULL,
  `space_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `area_sqm` int(11) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `price_per_day` decimal(10,2) DEFAULT NULL,
  `image_primary` varchar(255) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_spaces`
--

INSERT INTO `event_spaces` (`space_id`, `space_name`, `description`, `capacity`, `area_sqm`, `features`, `price_per_day`, `image_primary`, `images`, `status`) VALUES
(1, 'Grand Ballroom', 'Elegant ballroom perfect for weddings, conferences, and galas', 300, 500, 'Stage, Sound System, Projector, Dance Floor, Bridal Suite', 50000.00, 'images/events/1/1774623588_69c69b64829a4_Screenshot 2026-03-15 212742.png', 'images/events/1/1774619555_69c68ba300722_Screenshot 2026-03-15 205234.png,images/events/1/1774619555_69c68ba300a3d_Screenshot 2026-03-15 205404.png,images/events/1/1774619555_69c68ba3010b3_Screenshot 2026-03-15 210237.png,images/events/1/1774619555_69c68ba3015f8_Screenshot 2026-03-15 210640.png,images/events/1/1774619555_69c68ba3018c4_Screenshot 2026-03-15 211038.png', 'available'),
(2, 'Conference Room A', 'Professional meeting space with modern AV equipment', 50, 80, 'Projector, Whiteboard, Video Conferencing, Coffee Station', 8000.00, NULL, NULL, 'available'),
(3, 'Conference Room B', 'Intimate meeting room for small groups', 20, 40, 'TV Screen, Whiteboard, Coffee Station', 4000.00, NULL, NULL, 'available'),
(4, 'Garden Pavilion', 'Outdoor venue with stunning bay views for romantic events', 150, 300, 'Tent Options, Garden Setting, Sound System, Catering Area', 35000.00, NULL, NULL, 'available'),
(5, 'Rooftop Terrace', 'Exclusive rooftop space with panoramic views', 80, 150, 'City & Bay Views, Bar Area, Lounge Seating', 25000.00, NULL, NULL, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `event_virtual_tours`
--

CREATE TABLE `event_virtual_tours` (
  `tour_id` int(11) NOT NULL,
  `space_id` int(11) NOT NULL,
  `panorama_image` varchar(255) NOT NULL COMMENT 'Path to 360-degree equirectangular image',
  `thumbnail_image` varchar(255) DEFAULT NULL COMMENT 'Optional thumbnail preview',
  `title` varchar(100) NOT NULL DEFAULT 'Virtual Tour',
  `description` text DEFAULT NULL,
  `hotspot_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Hotspot configuration for interactive elements' CHECK (json_valid(`hotspot_config`)),
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='360-degree virtual tours for event spaces';

--
-- Dumping data for table `event_virtual_tours`
--

INSERT INTO `event_virtual_tours` (`tour_id`, `space_id`, `panorama_image`, `thumbnail_image`, `title`, `description`, `hotspot_config`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'assets/uploads/event_virtual_tours/panorama_1774615319_Screenshot of the running website.png', 'assets/uploads/event_virtual_tours/grand_ballroom_thumb.jpg', 'Grand Ballroom - 360° View', 'Experience our elegant Grand Ballroom, perfect for weddings, conferences, and galas. Full 360-degree panoramic view.', NULL, 1, 1, '2026-03-27 12:39:59', '2026-03-27 12:41:59'),
(2, 2, 'assets/uploads/event_virtual_tours/conference_a_360.jpg', 'assets/uploads/event_virtual_tours/conference_a_thumb.jpg', 'Conference Room A - 360° View', 'Professional meeting space with modern AV equipment. Explore the room in full 360° view.', NULL, 1, 1, '2026-03-27 12:39:59', '2026-03-27 12:39:59'),
(3, 3, 'assets/uploads/event_virtual_tours/conference_b_360.jpg', 'assets/uploads/event_virtual_tours/conference_b_thumb.jpg', 'Conference Room B - 360° View', 'Intimate meeting room for small groups with professional setup.', NULL, 1, 1, '2026-03-27 12:39:59', '2026-03-27 12:39:59'),
(4, 4, 'assets/uploads/event_virtual_tours/garden_pavilion_360.jpg', 'assets/uploads/event_virtual_tours/garden_pavilion_thumb.jpg', 'Garden Pavilion - 360° View', 'Outdoor venue with stunning bay views for romantic events and celebrations.', NULL, 1, 1, '2026-03-27 12:39:59', '2026-03-27 12:39:59'),
(5, 5, 'assets/uploads/event_virtual_tours/rooftop_terrace_360.jpg', 'assets/uploads/event_virtual_tours/rooftop_terrace_thumb.jpg', 'Rooftop Terrace - 360° View', 'Exclusive rooftop space with panoramic city and bay views.', NULL, 1, 1, '2026-03-27 12:39:59', '2026-03-27 12:39:59');

-- --------------------------------------------------------

--
-- Table structure for table `event_virtual_tour_hotspots`
--

CREATE TABLE `event_virtual_tour_hotspots` (
  `hotspot_id` int(11) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `hotspot_type` enum('info','scene','link') DEFAULT 'info',
  `pitch` decimal(8,4) NOT NULL COMMENT 'Vertical angle in degrees (-90 to 90)',
  `yaw` decimal(8,4) NOT NULL COMMENT 'Horizontal angle in degrees (-180 to 180)',
  `text` varchar(255) DEFAULT NULL COMMENT 'Tooltip text',
  `target_tour_id` int(11) DEFAULT NULL COMMENT 'For scene type - target tour ID to navigate to',
  `target_url` varchar(255) DEFAULT NULL COMMENT 'For link type - external URL',
  `css_class` varchar(50) DEFAULT 'custom-hotspot',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `event_virtual_tour_hotspots`
--

INSERT INTO `event_virtual_tour_hotspots` (`hotspot_id`, `tour_id`, `hotspot_type`, `pitch`, `yaw`, `text`, `target_tour_id`, `target_url`, `css_class`, `created_at`) VALUES
(1, 1, 'info', -5.0000, 0.0000, 'Main stage area with professional lighting', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(2, 1, 'info', -10.0000, 45.0000, 'Dance floor and entertainment area', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(3, 1, 'info', 0.0000, -90.0000, 'Bridal suite entrance', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(4, 1, 'info', 5.0000, 120.0000, 'Catering preparation area', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(5, 4, 'info', -5.0000, 0.0000, 'Main ceremony area with garden backdrop', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(6, 4, 'info', 0.0000, 90.0000, 'Bay view dining setup', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(7, 4, 'info', -10.0000, -45.0000, 'Catering and buffet area', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(8, 5, 'info', -5.0000, 0.0000, 'Lounge seating area', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(9, 5, 'info', 10.0000, 90.0000, 'Panoramic city view', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59'),
(10, 5, 'info', -10.0000, -90.0000, 'Bar and cocktail area', NULL, NULL, 'info-hotspot', '2026-03-27 12:39:59');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `faq_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`faq_id`, `question`, `answer`, `category`, `sort_order`, `is_active`) VALUES
(1, 'What are the check-in and check-out times?', 'Check-in time is 2:00 PM and check-out time is 12:00 PM (noon). Early check-in and late check-out are subject to availability and may incur additional charges.', 'reservations', 1, 1),
(2, 'Is breakfast included in the room rate?', 'Breakfast inclusion depends on your booking package. Our Bed & Breakfast rates include breakfast for all registered guests. Please check your reservation confirmation for details.', 'dining', 2, 1),
(3, 'Do you offer airport transfers?', 'Yes, we offer airport transfer services from Dumaguete Airport (Sibulan) to our hotel. Please contact our reservations team at least 24 hours in advance to arrange this service.', 'services', 3, 1),
(4, 'Is there WiFi available?', 'Complimentary high-speed WiFi is available throughout the hotel premises for all guests.', 'services', 4, 1),
(5, 'What payment methods do you accept?', 'We accept GCash, PayPal, major credit cards (Visa, Mastercard, Amex), cash, and bank transfers.', 'payments', 5, 1),
(6, 'Can I modify or cancel my reservation?', 'Yes, reservations can be modified or cancelled according to our policy. Cancellations made 48 hours prior to check-in are fully refundable. Please refer to your booking confirmation for specific terms.', 'reservations', 6, 1),
(7, 'Are pets allowed?', 'We regret that pets are not allowed in the hotel, with the exception of service animals.', 'policies', 7, 1),
(8, 'Do you have parking facilities?', 'Yes, we offer complimentary parking for our hotel guests.', 'services', 8, 1),
(9, 'What attractions are near the hotel?', 'Bayawan Bai Hotel is close to Danjugan Island Marine Reserve, Bayawan Bay Beach, and Mt. Talinis. Our concierge can help arrange tours and transportation.', 'location', 9, 1),
(10, 'Is there a gym and spa?', 'Yes, we have a 24-hour fitness center and a full-service spa offering various treatments and massages.', 'amenities', 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `foods`
--

CREATE TABLE `foods` (
  `food_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `food_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_special` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `dietary_info` varchar(255) DEFAULT NULL,
  `prep_time_minutes` int(11) DEFAULT 20,
  `stock_quantity` int(11) DEFAULT 0,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `foods`
--

INSERT INTO `foods` (`food_id`, `category_id`, `food_name`, `description`, `price`, `image`, `is_special`, `is_available`, `dietary_info`, `prep_time_minutes`, `stock_quantity`, `cost_price`, `created_at`, `updated_at`) VALUES
(1, 1, 'Filipino Breakfast Platter', 'Garlic rice, choice of tocino or longganisa, fried egg, atchara, and brewed coffee', 450.00, NULL, 1, 1, NULL, 20, 48, 180.00, '2026-03-21 15:08:59', '2026-03-30 01:21:01'),
(2, 1, 'Continental Breakfast', 'Fresh seasonal fruits, assorted pastries, yogurt, and choice of juice or coffee', 380.00, NULL, 0, 1, 'Vegetarian', 15, 40, 150.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(3, 1, 'American Breakfast', 'Eggs any style, bacon or sausage, hash browns, toast, and coffee', 520.00, 'images/foods/1774623644_Screenshot 2026-03-15 205234.png', 0, 1, '', 25, 30, 220.00, '2026-03-21 15:08:59', '2026-04-11 10:36:28'),
(4, 2, 'Grilled Blue Marlin', 'Fresh catch from Bayawan Bay with garlic butter sauce, served with rice and vegetables', 680.00, NULL, 1, 1, NULL, 30, 25, 280.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(5, 2, 'Chicken Inasal', 'Authentic Negros-style grilled chicken with annatto oil and calamansi', 450.00, NULL, 1, 1, 'Gluten-Free', 25, 30, 180.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(6, 2, 'Beef Steak Tagalog', 'Tender beef slices in soy-calamansi marinade with caramelized onions', 580.00, NULL, 0, 1, NULL, 30, 20, 240.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(7, 2, 'Vegetable Curry', 'Assorted vegetables in coconut curry sauce with steamed rice', 380.00, NULL, 0, 1, 'Vegan, Gluten-Free', 25, 15, 140.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(8, 3, 'Halo-Halo Special', 'Traditional Filipino dessert with ube, leche flan, sweet beans, and shaved ice', 280.00, NULL, 1, 1, 'Vegetarian', 10, 44, 100.00, '2026-03-21 15:08:59', '2026-03-26 10:09:54'),
(9, 3, 'Mango Float', 'Layers of graham crackers, cream, and fresh sweet mangoes', 250.00, NULL, 1, 1, 'Vegetarian', 15, 30, 90.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(10, 3, 'Leche Flan', 'Creamy caramel custard dessert', 180.00, NULL, 0, 1, 'Vegetarian, Gluten-Free', 10, 40, 70.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(11, 4, 'Bayawan Bay Breeze', 'Refreshing tropical cocktail with rum, pineapple, and coconut cream', 320.00, NULL, 1, 1, NULL, 5, 100, 80.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(12, 4, 'Fresh Buko Juice', 'Young coconut water served fresh in the shell', 180.00, NULL, 0, 1, 'Vegan, Gluten-Free', 5, 60, 50.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(13, 4, 'Kapeng Barako', 'Strong Batangas brewed coffee', 120.00, NULL, 0, 1, 'Vegan', 10, 80, 40.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(14, 2, 'Seafood Paella', 'Spanish rice dish with shrimp, mussels, squid, and fish', 750.00, NULL, 1, 1, NULL, 45, 20, 320.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(15, 2, 'Pork Sinigang', 'Tamarind soup with pork and vegetables', 420.00, NULL, 0, 1, 'Gluten-Free', 35, 25, 160.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59'),
(16, 2, 'Grilled Salmon', 'Norwegian salmon fillet with lemon butter sauce', 850.00, NULL, 1, 1, '', 25, 20, 380.00, '2026-03-21 15:08:59', '2026-03-21 16:00:43'),
(17, 3, 'Chocolate Lava Cake', 'Warm chocolate cake with molten center', 320.00, NULL, 0, 1, 'Vegetarian', 20, 18, 120.00, '2026-03-21 15:08:59', '2026-03-21 15:08:59');

-- --------------------------------------------------------

--
-- Table structure for table `food_orders`
--

CREATE TABLE `food_orders` (
  `order_id` int(11) NOT NULL,
  `order_ref` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `food_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','preparing','ready','delivered','cancelled') DEFAULT 'pending',
  `order_type` enum('room_service','dine_in','takeaway') DEFAULT 'room_service',
  `payment_method` enum('gcash','paypal','credit_card','pay_at_hotel','cash') DEFAULT 'pay_at_hotel',
  `payment_status` enum('pending','paid','partial') DEFAULT 'pending',
  `room_number` varchar(20) DEFAULT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `prepared_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `food_orders`
--

INSERT INTO `food_orders` (`order_id`, `order_ref`, `user_id`, `booking_id`, `food_id`, `quantity`, `unit_price`, `total_price`, `status`, `order_type`, `payment_method`, `payment_status`, `room_number`, `special_instructions`, `created_at`, `updated_at`, `prepared_at`, `delivered_at`) VALUES
(23, 'FOD20260330FCAD53', 28, NULL, 3, 1, 400.00, 400.00, 'delivered', 'dine_in', 'gcash', 'paid', NULL, '', '2026-03-30 05:08:17', '2026-03-30 05:11:57', '2026-03-30 05:11:51', '2026-03-30 05:11:57'),
(24, 'FOD20260409E453F5', 28, NULL, 3, 1, 520.00, 520.00, 'cancelled', 'dine_in', 'gcash', 'paid', NULL, '', '2026-04-09 14:15:44', '2026-04-09 14:16:56', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `image_id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `category` enum('rooms','dining','amenities','events','attractions','hotel') DEFAULT 'hotel',
  `is_featured` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`image_id`, `title`, `description`, `image_path`, `category`, `is_featured`, `sort_order`, `uploaded_at`) VALUES
(1, 'Hotel Exterior', 'Stunning view of Bayawan Bai Hotel facade', 'images/gallery/1774623926_Screenshot 2026-03-15 213930.png', 'hotel', 0, 1, '2026-03-17 08:51:18'),
(2, 'Grand Lobby', 'Welcoming lobby with modern Filipino design', 'images/gallery/1774625239_Screenshot 2026-03-16 164757.png', 'hotel', 0, 2, '2026-03-17 08:51:18'),
(3, 'Standard Room', 'Comfortable standard room with city view', 'images/gallery/standard-room.jpg', 'rooms', 1, 1, '2026-03-17 08:51:18'),
(4, 'Deluxe Room', 'Spacious deluxe room with bay view', 'images/gallery/deluxe-room.jpg', 'rooms', 1, 2, '2026-03-17 08:51:18'),
(5, 'Suite Living Area', 'Elegant living space in our suites', 'images/gallery/suite-living.jpg', 'rooms', 0, 3, '2026-03-17 08:51:18'),
(6, 'Suite Bedroom', 'Luxurious bedroom with ocean view', 'images/gallery/suite-bedroom.jpg', 'rooms', 0, 4, '2026-03-17 08:51:18'),
(7, 'Infinity Pool', 'Relax by our stunning infinity pool', 'images/gallery/pool.jpg', 'amenities', 1, 1, '2026-03-17 08:51:18'),
(8, 'Spa Treatment Room', 'Tranquil spa environment for relaxation', 'images/gallery/spa.jpg', 'amenities', 0, 2, '2026-03-17 08:51:18'),
(9, 'Restaurant', 'Fine dining at our in-house restaurant', 'images/gallery/restaurant.jpg', 'dining', 1, 1, '2026-03-17 08:51:18'),
(10, 'Breakfast Buffet', 'Delicious morning spread', 'images/gallery/breakfast.jpg', 'dining', 0, 2, '2026-03-17 08:51:18'),
(11, 'Danjugan Island', 'Explore the beautiful Danjugan Island nearby', 'images/gallery/danjugan.jpg', 'attractions', 1, 1, '2026-03-17 08:51:18'),
(12, 'Bayawan Bay Beach', 'Pristine beach just minutes away', 'images/gallery/bayawan-bay.jpg', 'attractions', 1, 2, '2026-03-17 08:51:18'),
(13, 'Mt. Talinis', 'Majestic mountain views from the region', 'images/gallery/mt-talinis.jpg', 'attractions', 0, 3, '2026-03-17 08:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_slider`
--

CREATE TABLE `homepage_slider` (
  `slide_id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `button_text` varchar(50) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `homepage_slider`
--

INSERT INTO `homepage_slider` (`slide_id`, `title`, `subtitle`, `image`, `button_text`, `button_link`, `sort_order`, `is_active`) VALUES
(1, 'Welcome to Bayawan Bai Hotel', 'Experience the perfect blend of luxury and nature in Bayawan City', 'images/slider/slide1.jpg', 'Book Now', 'booking.php', 1, 1),
(2, 'Escape to Paradise', 'Discover pristine beaches and stunning ocean views', 'images/slider/slide2.jpg', 'Explore Rooms', 'rooms.php', 2, 1),
(3, 'Culinary Excellence', 'Savor the flavors of Negros Oriental', 'images/slider/slide3.jpg', 'View Dining', 'dining.php', 3, 1),
(4, 'Unforgettable Events', 'Host your special moments in our elegant venues', 'images/slider/slide4.jpg', 'Plan Your Event', 'events.php', 4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `inv_cat_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`inv_cat_id`, `category_name`) VALUES
(1, 'Linens & Towels'),
(2, 'Toiletries'),
(3, 'Cleaning Supplies'),
(4, 'Minibar Items'),
(5, 'Office Supplies'),
(6, 'Kitchen Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `inv_cat_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `inv_cat_id`, `item_name`, `description`, `unit`, `quantity`, `reorder_level`, `unit_cost`, `supplier`) VALUES
(1, 1, 'Bath Towels', 'Premium white bath towels', 'piece', 200, 50, 450.00, 'Manila Textiles'),
(2, 1, 'Bed Sheets', 'Queen size white sheets', 'piece', 150, 30, 850.00, 'Manila Textiles'),
(3, 2, 'Shampoo', 'Hotel size shampoo bottles 30ml', 'bottle', 500, 100, 25.00, 'Amenities Supplier PH'),
(4, 2, 'Soap', 'Hotel size soap bars 25g', 'piece', 600, 150, 15.00, 'Amenities Supplier PH'),
(5, 3, 'All-Purpose Cleaner', 'Multi-surface cleaning solution', 'liter', 91, 15, 180.00, 'CleanPro Supplies'),
(6, 4, 'Bottled Water', '500ml mineral water', 'bottle', 300, 50, 20.00, 'Nestle Philippines'),
(7, 4, 'Snacks Assorted', 'Mixed snack items for minibar', 'pack', 100, 20, 45.00, 'Local Distributor');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `request_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `issue_type` enum('plumbing','electrical','hvac','furniture','appliance','other') NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_categories`
--

CREATE TABLE `menu_categories` (
  `cat_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_categories`
--

INSERT INTO `menu_categories` (`cat_id`, `category_name`, `description`, `sort_order`) VALUES
(1, 'Breakfast', 'Start your day with our delicious breakfast options', 1),
(2, 'Main Course', 'Exquisite dishes prepared by our master chefs', 2),
(3, 'Desserts', 'Sweet indulgences to complete your meal', 3),
(4, 'Beverages', 'Refreshing drinks and cocktails', 4);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `item_id` int(11) NOT NULL,
  `cat_id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_special` tinyint(1) DEFAULT 0,
  `is_available` tinyint(1) DEFAULT 1,
  `dietary_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `cat_id`, `item_name`, `description`, `price`, `image`, `is_special`, `is_available`, `dietary_info`) VALUES
(1, 1, 'Filipino Breakfast', 'Garlic rice, tocino/longganisa, fried egg, atchara, and brewed coffee', 450.00, NULL, 1, 1, NULL),
(2, 1, 'Continental Breakfast', 'Fresh fruits, pastries, yogurt, and choice of juice or coffee', 380.00, NULL, 0, 1, NULL),
(3, 1, 'American Breakfast', 'Eggs any style, bacon/sausage, hash browns, toast, and coffee', 520.00, 'images/menu/1774620340_Screenshot 2026-03-15 210237.png', 1, 1, ''),
(4, 2, 'Grilled Blue Marlin', 'Fresh catch from Bayawan Bay with garlic butter sauce, served with rice and vegetables', 680.00, NULL, 1, 1, NULL),
(5, 2, 'Chicken Inasal', 'Authentic Negros-style grilled chicken with annatto oil and calamansi', 450.00, NULL, 1, 1, NULL),
(6, 2, 'Beef Steak Tagalog', 'Tender beef slices in soy-calamansi marinade with caramelized onions', 580.00, NULL, 0, 1, NULL),
(7, 3, 'Halo-Halo Special', 'Traditional Filipino dessert with ube, leche flan, and assorted sweet beans', 280.00, NULL, 1, 1, NULL),
(8, 3, 'Mango Float', 'Layers of graham crackers, cream, and fresh mangoes', 250.00, NULL, 1, 1, NULL),
(9, 4, 'Bayawan Bay Breeze', 'Refreshing tropical cocktail with rum, pineapple, and coconut', 320.00, NULL, 1, 1, NULL),
(10, 4, 'Fresh Buko Juice', 'Young coconut water served in the shell', 180.00, NULL, 0, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `subscriber_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `status` enum('active','unsubscribed') DEFAULT 'active',
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`subscriber_id`, `email`, `first_name`, `last_name`, `status`, `subscribed_at`, `unsubscribed_at`, `ip_address`, `user_agent`) VALUES
(1, 'kylleacibron@gmail.com', NULL, NULL, 'active', '2026-03-28 09:21:42', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('booking','food_order','payment','system','schedule','maintenance','event','promotion') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `action_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `title`, `message`, `related_id`, `related_type`, `status`, `priority`, `action_url`, `created_at`, `read_at`) VALUES
(329, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 51, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:35:07', '2026-04-09 15:29:58'),
(330, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 01, 2026', 51, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:35:07', '2026-04-11 09:18:10'),
(331, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 01, 2026', 51, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:35:07', NULL),
(332, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 52, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:37:58', '2026-04-09 15:29:58'),
(333, 28, 'payment', 'Payment Pending', 'Your payment of ₱5,000.00 is being processed.', 52, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:37:58', '2026-04-09 15:29:58'),
(334, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 52, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-03-31 14:37:58', '2026-04-11 09:18:10'),
(336, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 52, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-03-31 14:37:58', NULL),
(337, 1, 'payment', 'Payment Pending', 'Payment of ₱0.00 from Kylle Acibron is pending processing.', 52, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-03-31 14:37:58', '2026-04-11 09:18:10'),
(338, 3, 'payment', 'Payment Pending', 'Payment of ₱0.00 from Kylle Acibron is pending processing.', 52, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-03-31 14:37:58', NULL),
(339, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Mar 31, 2026', 52, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:37:58', '2026-04-11 09:18:10'),
(340, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Mar 31, 2026', 52, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:37:58', NULL),
(341, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 53, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:38:48', '2026-04-09 15:29:58'),
(342, 28, 'payment', 'Payment Successful', 'Your payment of ₱5,000.00 has been received. Thank you!', 53, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:38:48', '2026-04-09 15:29:58'),
(343, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 53, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-03-31 14:38:48', '2026-04-11 09:18:10'),
(345, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 53, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-03-31 14:38:48', NULL),
(346, 1, 'payment', 'Payment Received', 'Payment of ₱5,000.00 via gcash received from Kylle Acibron.', 53, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-03-31 14:38:48', '2026-04-11 09:18:10'),
(347, 3, 'payment', 'Payment Received', 'Payment of ₱5,000.00 via gcash received from Kylle Acibron.', 53, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-03-31 14:38:48', NULL),
(348, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Mar 31, 2026', 53, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:38:48', '2026-04-11 09:18:10'),
(349, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Mar 31, 2026', 53, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:38:48', NULL),
(350, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 52, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:39:04', '2026-04-09 15:29:58'),
(351, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Mar 31, 2026', 52, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:39:04', '2026-04-11 09:18:10'),
(352, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Mar 31, 2026', 52, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:39:04', NULL),
(353, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 54, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:42:23', '2026-04-09 15:29:58'),
(354, 28, 'payment', 'Payment Successful', 'Your payment of ₱5,000.00 has been received. Thank you!', 54, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:42:23', '2026-04-09 15:29:58'),
(355, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 54, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-03-31 14:42:23', '2026-04-11 09:18:10'),
(357, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 54, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-03-31 14:42:23', NULL),
(358, 1, 'payment', 'Payment Received', 'Payment of ₱5,000.00 via gcash received from Kylle Acibron.', 54, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-03-31 14:42:23', '2026-04-11 09:18:10'),
(359, 3, 'payment', 'Payment Received', 'Payment of ₱5,000.00 via gcash received from Kylle Acibron.', 54, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-03-31 14:42:23', NULL),
(360, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Mar 31, 2026', 54, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:42:23', '2026-04-11 09:18:10'),
(361, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Mar 31, 2026', 54, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:42:23', NULL),
(362, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 54, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:43:03', '2026-04-09 15:29:58'),
(363, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 02, 2026', 54, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:43:03', '2026-04-11 09:18:10'),
(364, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 02, 2026', 54, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-03-31 14:43:03', NULL),
(365, 28, 'booking', 'Checked In Successfully', 'You have successfully checked in. Enjoy your stay!', 54, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-03-31 14:44:14', '2026-04-09 15:29:58'),
(366, 28, 'booking', 'Checked Out - Thank You!', 'Thank you for staying with us. We hope to see you again soon!', 54, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 01:50:40', '2026-04-09 15:29:58'),
(367, 28, 'event', 'Event Updated: birthday', 'There have been some changes to \'birthday\' on Mar 30, 2026. Location: Conference Room A Please review the updated details.', 10, 'event', 'read', 'medium', '/bayawanhotel/user/my-event-bookings.php', '2026-04-04 01:57:47', '2026-04-09 15:29:58'),
(368, 1, 'event', 'Event Modified', 'Event \'birthday\' on Mar 30, 2026 has been modified by Kylle Acibron.', 10, 'event', 'read', 'medium', '/bayawanhotel/admin/admin-event-bookings.php', '2026-04-04 01:57:47', '2026-04-11 09:18:10'),
(369, 3, 'event', 'Event Modified', 'Event \'birthday\' on Mar 30, 2026 has been modified by Kylle Acibron.', 10, 'event', 'unread', 'medium', '/bayawanhotel/admin/admin-event-bookings.php', '2026-04-04 01:57:47', NULL),
(370, 1, 'event', 'Event Schedule Updated', 'Event \'birthday\' on Mar 30, 2026 has been updated. Please review the changes.', 10, 'event', 'read', 'medium', '/bayawanhotel/staff/staff-event-bookings.php', '2026-04-04 01:57:47', '2026-04-11 09:18:10'),
(372, 3, 'event', 'Event Schedule Updated', 'Event \'birthday\' on Mar 30, 2026 has been updated. Please review the changes.', 10, 'event', 'unread', 'medium', '/bayawanhotel/staff/staff-event-bookings.php', '2026-04-04 01:57:47', NULL),
(373, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 55, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:22:36', '2026-04-09 15:29:58'),
(374, 28, 'payment', 'Payment Successful', 'Your payment of ₱2,500.00 has been received. Thank you!', 55, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:22:36', '2026-04-09 15:29:58'),
(375, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 55, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:22:36', '2026-04-11 09:18:10'),
(377, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 55, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:22:36', NULL),
(378, 1, 'payment', 'Payment Received', 'Payment of ₱2,500.00 via gcash received from Kylle Acibron.', 55, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-04 03:22:36', '2026-04-11 09:18:10'),
(379, 3, 'payment', 'Payment Received', 'Payment of ₱2,500.00 via gcash received from Kylle Acibron.', 55, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-04 03:22:36', NULL),
(380, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 04, 2026', 55, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:22:36', '2026-04-11 09:18:10'),
(381, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 04, 2026', 55, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:22:36', NULL),
(382, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 55, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:22:50', '2026-04-09 15:29:58'),
(383, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 04, 2026', 55, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:22:50', '2026-04-11 09:18:10'),
(384, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 04, 2026', 55, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:22:50', NULL),
(385, 28, 'booking', 'Checked In Successfully', 'You have successfully checked in. Enjoy your stay!', 55, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:22:58', '2026-04-09 15:29:58'),
(386, 1, 'maintenance', 'Maintenance Request Resolved', 'plumbing maintenance request in Room 304 has been resolved.', 3, 'maintenance_request', 'read', 'low', '/bayawanhotel/admin/admin-maintenance.php', '2026-04-04 03:35:47', '2026-04-11 09:18:10'),
(387, 3, 'maintenance', 'Maintenance Request Resolved', 'plumbing maintenance request in Room 304 has been resolved.', 3, 'maintenance_request', 'unread', 'low', '/bayawanhotel/admin/admin-maintenance.php', '2026-04-04 03:35:47', NULL),
(388, 2, 'maintenance', 'Maintenance Task Completed', 'The plumbing maintenance in Room 304 has been marked as completed. Thank you for your work.', 3, 'maintenance_request', 'read', 'low', '/bayawanhotel/staff/staff-maintenance.php', '2026-04-04 03:35:47', '2026-04-09 16:31:52'),
(389, 28, 'booking', 'Checked Out - Thank You!', 'Thank you for staying with us. We hope to see you again soon!', 55, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:36:44', '2026-04-09 15:29:58'),
(390, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 56, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:50:53', '2026-04-09 15:29:58'),
(391, 28, 'payment', 'Payment Pending', 'Your payment of ₱2,500.00 is being processed.', 56, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:50:53', '2026-04-09 15:29:58'),
(392, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 56, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:50:53', '2026-04-11 09:18:10'),
(393, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 56, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:50:53', '2026-04-09 16:31:52'),
(394, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 56, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:50:53', NULL),
(395, 1, 'payment', 'Payment Pending', 'Payment of ₱0.00 from Kylle Acibron is pending processing.', 56, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-04 03:50:53', '2026-04-11 09:18:10'),
(396, 3, 'payment', 'Payment Pending', 'Payment of ₱0.00 from Kylle Acibron is pending processing.', 56, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-04 03:50:53', NULL),
(397, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 04, 2026', 56, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:50:53', '2026-04-11 09:18:10'),
(398, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 04, 2026', 56, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:50:53', NULL),
(399, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 57, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:51:45', '2026-04-09 15:29:58'),
(400, 28, 'payment', 'Payment Successful', 'Your payment of ₱7,500.00 has been received. Thank you!', 57, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-04 03:51:45', '2026-04-09 15:29:58'),
(401, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 57, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:51:45', '2026-04-11 09:18:10'),
(402, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 57, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:51:45', '2026-04-09 16:31:52'),
(403, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 57, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-04 03:51:45', NULL),
(404, 1, 'payment', 'Payment Received', 'Payment of ₱7,500.00 via gcash received from Kylle Acibron.', 57, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-04 03:51:45', '2026-04-11 09:18:10'),
(405, 3, 'payment', 'Payment Received', 'Payment of ₱7,500.00 via gcash received from Kylle Acibron.', 57, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-04 03:51:45', NULL),
(406, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 04, 2026', 57, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:51:45', '2026-04-11 09:18:10'),
(407, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 04, 2026', 57, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-04 03:51:45', NULL),
(408, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 58, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 14:05:21', '2026-04-09 15:29:58'),
(409, 28, 'payment', 'Payment Successful', 'Your payment of ₱15,000.00 has been received. Thank you!', 58, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 14:05:21', '2026-04-09 15:29:58'),
(410, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 58, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-09 14:05:21', '2026-04-11 09:18:10'),
(411, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 58, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-09 14:05:21', '2026-04-09 16:31:52'),
(412, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 58, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-09 14:05:21', NULL),
(413, 1, 'payment', 'Payment Received', 'Payment of ₱15,000.00 via gcash received from Kylle Acibron.', 58, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-09 14:05:21', '2026-04-11 09:18:10'),
(414, 3, 'payment', 'Payment Received', 'Payment of ₱15,000.00 via gcash received from Kylle Acibron.', 58, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-09 14:05:21', NULL),
(415, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 09, 2026', 58, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-09 14:05:21', '2026-04-11 09:18:10'),
(416, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 09, 2026', 58, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-09 14:05:21', NULL),
(417, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 59, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 14:13:26', '2026-04-09 15:29:58'),
(418, 28, 'payment', 'Payment Successful', 'Your payment of ₱15,000.00 has been received. Thank you!', 59, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 14:13:27', '2026-04-09 15:29:58'),
(419, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 59, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-09 14:13:27', '2026-04-11 09:18:10'),
(420, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 59, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-09 14:13:27', '2026-04-09 16:31:52'),
(421, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 59, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-09 14:13:27', NULL),
(422, 1, 'payment', 'Payment Received', 'Payment of ₱15,000.00 via gcash received from Kylle Acibron.', 59, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-09 14:13:27', '2026-04-11 09:18:10'),
(423, 3, 'payment', 'Payment Received', 'Payment of ₱15,000.00 via gcash received from Kylle Acibron.', 59, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-09 14:13:27', NULL),
(424, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 09, 2026', 59, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-09 14:13:27', '2026-04-11 09:18:10'),
(425, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 09, 2026', 59, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-09 14:13:27', NULL),
(426, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 59, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 14:18:55', '2026-04-09 15:29:58'),
(427, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 09, 2026', 59, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-09 14:18:55', '2026-04-11 09:18:10'),
(428, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 09, 2026', 59, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-09 14:18:55', NULL),
(429, 28, 'booking', 'Checked In Successfully', 'You have successfully checked in. Enjoy your stay!', 59, 'booking', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 14:19:18', '2026-04-09 15:29:58'),
(430, 28, 'payment', 'Payment Successful', 'Your payment of ₱20,000.00 has been received. Thank you!', 58, 'payment', 'read', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 15:34:42', '2026-04-09 15:57:33'),
(431, 1, 'payment', 'Payment Received', 'Payment of ₱20,000.00 via gcash received from Kylle Acibron.', 58, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-09 15:34:42', '2026-04-11 09:18:10'),
(432, 3, 'payment', 'Payment Received', 'Payment of ₱20,000.00 via gcash received from Kylle Acibron.', 58, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-09 15:34:42', NULL),
(433, 28, 'booking', 'Checked Out - Thank You!', 'Thank you for staying with us. We hope to see you again soon!', 59, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-09 16:30:10', NULL),
(434, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 60, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-10 13:15:20', NULL),
(435, 28, 'payment', 'Payment Successful', 'Your payment of ₱4,500.00 has been received. Thank you!', 60, 'payment', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-10 13:15:20', NULL),
(436, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 60, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-10 13:15:20', '2026-04-11 09:18:10'),
(437, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 60, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-10 13:15:20', '2026-04-11 09:07:24'),
(438, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 60, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-10 13:15:20', NULL),
(439, 1, 'payment', 'Payment Received', 'Payment of ₱4,500.00 via gcash received from Kylle Acibron.', 60, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-10 13:15:20', '2026-04-11 09:18:10'),
(440, 3, 'payment', 'Payment Received', 'Payment of ₱4,500.00 via gcash received from Kylle Acibron.', 60, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-10 13:15:20', NULL),
(441, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 10, 2026', 60, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-10 13:15:20', '2026-04-11 09:18:10'),
(442, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 10, 2026', 60, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-10 13:15:20', NULL),
(443, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 61, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:07:54', NULL),
(444, 28, 'payment', 'Payment Successful', 'Your payment of ₱4,500.00 has been received. Thank you!', 61, 'payment', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:07:54', NULL),
(445, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 61, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:07:55', '2026-04-11 09:18:10'),
(446, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 61, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:07:55', '2026-04-11 09:23:00'),
(447, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 61, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:07:55', NULL),
(448, 1, 'payment', 'Payment Received', 'Payment of ₱4,500.00 via gcash received from Kylle Acibron.', 61, 'payment', 'read', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:07:55', '2026-04-11 09:18:10'),
(449, 3, 'payment', 'Payment Received', 'Payment of ₱4,500.00 via gcash received from Kylle Acibron.', 61, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:07:55', NULL),
(450, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 61, 'booking', 'read', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:07:55', '2026-04-11 09:18:10'),
(451, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 61, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:07:55', NULL),
(452, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 62, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:24:16', NULL),
(453, 28, 'payment', 'Payment Successful', 'Your payment of ₱3,500.00 has been received. Thank you!', 62, 'payment', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:24:16', NULL),
(454, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for N/A', 62, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:24:16', NULL),
(455, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for N/A', 62, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:24:16', '2026-04-11 09:24:35'),
(456, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for N/A', 62, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:24:17', NULL),
(457, 1, 'payment', 'Payment Received', 'Payment of ₱3,500.00 via gcash received from Kylle Acibron.', 62, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:24:17', NULL),
(458, 3, 'payment', 'Payment Received', 'Payment of ₱3,500.00 via gcash received from Kylle Acibron.', 62, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:24:17', NULL),
(459, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 62, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:24:17', NULL),
(460, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 62, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:24:17', NULL),
(461, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 63, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:37:13', NULL),
(462, 28, 'payment', 'Payment Successful', 'Your payment of ₱7,000.00 has been received. Thank you!', 63, 'payment', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:37:13', NULL),
(463, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 63, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:37:13', NULL),
(464, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 63, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:37:13', '2026-04-11 09:40:50'),
(465, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron for Jan 01, 1970', 63, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:37:13', NULL),
(466, 1, 'payment', 'Payment Received', 'Payment of ₱7,000.00 via gcash received from Kylle Acibron.', 63, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:37:13', NULL),
(467, 3, 'payment', 'Payment Received', 'Payment of ₱7,000.00 via gcash received from Kylle Acibron.', 63, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:37:13', NULL),
(468, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 63, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:37:13', NULL),
(469, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 63, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:37:13', NULL),
(470, 28, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 64, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:43:06', NULL),
(471, 28, 'payment', 'Payment Successful', 'Your payment of ₱5,000.00 has been received. Thank you!', 64, 'payment', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:43:06', NULL),
(472, 1, 'booking', 'New Booking Received', 'New booking from Kylle Acibron', 64, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:43:06', '2026-04-11 09:45:10'),
(473, 2, 'booking', 'New Booking Received', 'New booking from Kylle Acibron', 64, 'booking', 'read', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:43:06', '2026-04-11 10:00:05'),
(474, 3, 'booking', 'New Booking Received', 'New booking from Kylle Acibron', 64, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 09:43:06', NULL),
(475, 1, 'payment', 'Payment Received', 'Payment of ₱5,000.00 via gcash received from Kylle Acibron.', 64, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:43:06', NULL),
(476, 3, 'payment', 'Payment Received', 'Payment of ₱5,000.00 via gcash received from Kylle Acibron.', 64, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 09:43:06', NULL),
(477, 1, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 64, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:43:06', NULL),
(478, 3, 'booking', 'New Booking Created', 'New booking from Kylle Acibron. Check-in: Apr 11, 2026', 64, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:43:06', NULL),
(479, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 64, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:54:06', NULL),
(480, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 11, 2026', 64, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:54:06', NULL),
(481, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 11, 2026', 64, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 09:54:06', NULL),
(482, 28, 'booking', 'Checked In Successfully', 'You have successfully checked in. Enjoy your stay!', 64, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 09:54:14', NULL),
(483, 28, 'event', 'Event Updated: conference', 'There have been some changes to \'conference\' on Apr 10, 2026. Location: Conference Room A Please review the updated details.', 11, 'event', 'unread', 'medium', '/bayawanhotel/user/my-event-bookings.php', '2026-04-11 10:09:17', NULL),
(484, 1, 'event', 'Event Modified', 'Event \'conference\' on Apr 10, 2026 has been modified by Kylle Acibron.', 11, 'event', 'unread', 'medium', '/bayawanhotel/admin/admin-event-bookings.php', '2026-04-11 10:09:17', NULL),
(485, 3, 'event', 'Event Modified', 'Event \'conference\' on Apr 10, 2026 has been modified by Kylle Acibron.', 11, 'event', 'unread', 'medium', '/bayawanhotel/admin/admin-event-bookings.php', '2026-04-11 10:09:17', NULL),
(486, 1, 'event', 'Event Schedule Updated', 'Event \'conference\' on Apr 10, 2026 has been updated. Please review the changes.', 11, 'event', 'unread', 'medium', '/bayawanhotel/staff/staff-event-bookings.php', '2026-04-11 10:09:17', NULL),
(487, 2, 'event', 'Event Schedule Updated', 'Event \'conference\' on Apr 10, 2026 has been updated. Please review the changes.', 11, 'event', 'unread', 'medium', '/bayawanhotel/staff/staff-event-bookings.php', '2026-04-11 10:09:17', NULL),
(488, 3, 'event', 'Event Schedule Updated', 'Event \'conference\' on Apr 10, 2026 has been updated. Please review the changes.', 11, 'event', 'unread', 'medium', '/bayawanhotel/staff/staff-event-bookings.php', '2026-04-11 10:09:17', NULL),
(489, 28, 'booking', 'Checked Out - Thank You!', 'Thank you for staying with us. We hope to see you again soon!', 64, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 10:49:56', NULL),
(490, 28, 'booking', 'Booking Confirmed', 'Your booking has been confirmed. We look forward to welcoming you!', 63, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 11:05:54', NULL),
(491, 1, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 11, 2026', 63, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 11:05:54', NULL),
(492, 3, 'booking', 'Booking Confirmed', 'Booking for Kylle Acibron has been confirmed. Check-in: Apr 11, 2026', 63, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 11:05:54', NULL),
(493, 28, 'booking', 'Checked In Successfully', 'You have successfully checked in. Enjoy your stay!', 63, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 11:07:17', NULL),
(494, 29, 'booking', 'Booking Received', 'Your booking request has been received and is pending confirmation.', 65, 'booking', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 13:46:11', NULL),
(495, 29, 'payment', 'Payment Successful', 'Your payment of ₱1,000.00 has been received. Thank you!', 65, 'payment', 'unread', 'medium', '/bayawanhotel/user/my-bookings.php', '2026-04-11 13:46:11', NULL),
(496, 1, 'booking', 'New Booking Received', 'New booking from John Lloyd Chu', 65, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 13:46:11', NULL),
(497, 2, 'booking', 'New Booking Received', 'New booking from John Lloyd Chu', 65, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 13:46:11', NULL),
(498, 3, 'booking', 'New Booking Received', 'New booking from John Lloyd Chu', 65, 'booking', 'unread', 'medium', '/bayawanhotel/staff/confirm-booking.php', '2026-04-11 13:46:11', NULL),
(499, 1, 'payment', 'Payment Received', 'Payment of ₱1,000.00 via pay_at_hotel received from John Lloyd Chu.', 65, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 13:46:11', NULL),
(500, 3, 'payment', 'Payment Received', 'Payment of ₱1,000.00 via pay_at_hotel received from John Lloyd Chu.', 65, 'payment', 'unread', 'medium', '/bayawanhotel/admin/admin-payments.php', '2026-04-11 13:46:12', NULL),
(501, 1, 'booking', 'New Booking Created', 'New booking from John Lloyd Chu. Check-in: Apr 11, 2026', 65, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 13:46:12', NULL),
(502, 3, 'booking', 'New Booking Created', 'New booking from John Lloyd Chu. Check-in: Apr 11, 2026', 65, 'booking', 'unread', 'medium', '/bayawanhotel/admin/admin-bookings.php', '2026-04-11 13:46:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('email','sms') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_type` enum('booking','food_order','payment','system','schedule','maintenance','event','promotion','all') NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `popup_enabled` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`setting_id`, `user_id`, `notification_type`, `email_enabled`, `popup_enabled`, `created_at`, `updated_at`) VALUES
(1, 1, 'all', 1, 1, '2026-03-25 12:06:36', '2026-03-25 12:06:36'),
(2, 24, 'all', 1, 1, '2026-03-25 12:06:36', '2026-03-25 12:06:36'),
(3, 23, 'all', 1, 1, '2026-03-25 12:06:36', '2026-03-25 12:06:36'),
(5, 3, 'all', 1, 1, '2026-03-25 12:06:36', '2026-03-25 12:06:36'),
(6, 2, 'all', 1, 1, '2026-03-25 12:06:36', '2026-03-25 12:06:36'),
(7, 28, 'booking', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(8, 28, 'food_order', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(9, 28, 'payment', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(10, 28, 'system', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(11, 28, 'schedule', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(12, 28, 'maintenance', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(13, 28, 'event', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(14, 28, 'promotion', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58'),
(15, 28, 'all', 1, 1, '2026-04-04 03:26:58', '2026-04-04 03:26:58');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `event_booking_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','paypal','credit_card','cash','bank_transfer') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `event_booking_id`, `user_id`, `amount`, `payment_method`, `transaction_id`, `status`, `payment_date`, `notes`) VALUES
(50, 54, NULL, 28, 5000.00, 'gcash', 'GCASH-AAF905', 'completed', '2026-03-31 14:42:18', 'GCash payment from Kylle Acibron (09455470173)'),
(51, 55, NULL, 28, 2500.00, 'gcash', 'GCASH-62058A', 'completed', '2026-04-04 03:22:30', 'GCash payment from Kylle Acibron (09455470173)'),
(52, 56, NULL, 28, 0.00, 'gcash', NULL, 'failed', '2026-04-04 03:50:47', 'GCash payment failed from Kylle Acibron ()'),
(53, 57, NULL, 28, 7500.00, 'gcash', 'GCASH-BC20A0', 'completed', '2026-04-04 03:51:39', 'GCash payment from Kylle Acibron (09455470173)'),
(54, 57, NULL, 28, 7500.00, 'gcash', NULL, 'refunded', '2026-04-04 03:52:33', 'Refund processed via gcash for cancelled booking'),
(55, 56, NULL, 28, 2500.00, 'gcash', NULL, 'refunded', '2026-04-04 03:52:56', 'Refund processed via gcash for cancelled booking'),
(56, 58, NULL, 28, 15000.00, 'gcash', 'GCASH-C1162F', 'completed', '2026-04-09 14:05:16', 'GCash payment from Kylle Acibron (09455470173)'),
(57, 59, NULL, 28, 15000.00, 'gcash', 'GCASH-18AD40', 'completed', '2026-04-09 14:13:21', 'GCash payment from Kylle Acibron (09455470173)'),
(58, NULL, 12, 28, 20000.00, 'gcash', 'GCASH-2B8868', 'completed', '2026-04-09 15:34:42', 'GCash payment from Kylle Acibron (09455470173)'),
(59, 60, NULL, 28, 4500.00, 'gcash', 'GCASH-1D8794', 'completed', '2026-04-10 13:15:13', 'GCash payment from Kylle Acibron (09455470173)'),
(60, 61, NULL, 28, 4500.00, 'gcash', 'GCASH-4A1E75', 'completed', '2026-04-11 09:07:48', 'GCash payment from Kylle Acibron (09455470173)'),
(61, 62, NULL, 28, 3500.00, 'gcash', 'GCASH-C1F02A', 'completed', '2026-04-11 09:24:12', 'GCash payment from Kylle Acibron (09455470173)'),
(62, 63, NULL, 28, 7000.00, 'gcash', 'GCASH-436645', 'completed', '2026-04-11 09:37:08', 'GCash payment from Kylle Acibron (09455470173)'),
(63, 64, NULL, 28, 5000.00, 'gcash', 'GCASH-4B22B3', 'completed', '2026-04-11 09:43:00', 'GCash payment from Kylle Acibron (09455470173)'),
(64, 65, NULL, 29, 1000.00, '', 'HOTEL-FE8E7E', 'completed', '2026-04-11 13:45:51', 'Pay at Hotel - John Lloyd Chu (09661351116) | Arrival: 12:00 | Notes: Dapat naa pagkaon');

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promo_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `discount_percent` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `min_nights` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promo_id`, `title`, `description`, `image`, `discount_percent`, `discount_amount`, `promo_code`, `start_date`, `end_date`, `min_nights`, `is_active`, `created_at`) VALUES
(1, 'Summer Special', 'Book 3 nights and get 20% off your stay! Perfect for your summer getaway in Bayawan.', NULL, 20, 0.00, 'SUMMER20', '2024-03-01', '2024-05-31', 3, 1, '2026-03-17 08:51:18'),
(2, 'Early Bird Discount', 'Plan ahead! Book 30 days in advance and save 15% on your reservation.', NULL, 15, NULL, 'EARLY15', '2024-01-01', '2024-12-31', 1, 1, '2026-03-17 08:51:18'),
(3, 'Weekend Escape', 'Special weekend rates for a relaxing break. Includes complimentary breakfast!', NULL, 25, NULL, 'WEEKEND25', '2024-01-01', '2024-12-31', 2, 1, '2026-03-17 08:51:18'),
(4, 'Loyalty Member Special', 'Members enjoy an extra 10% off on top of any promotion!', NULL, 10, NULL, 'LOYAL10', '2024-01-01', '2024-12-31', 1, 1, '2026-03-17 08:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_type` enum('room','event','food') NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `event_booking_id` int(11) DEFAULT NULL,
  `food_order_id` int(11) DEFAULT NULL,
  `rating_value` tinyint(4) NOT NULL CHECK (`rating_value` between 1 and 5),
  `comment` text DEFAULT NULL,
  `is_rated` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`rating_id`, `user_id`, `service_type`, `booking_id`, `event_booking_id`, `food_order_id`, `rating_value`, `comment`, `is_rated`, `created_at`, `updated_at`) VALUES
(11, 28, 'food', NULL, NULL, 23, 5, '', 1, '2026-04-09 13:51:15', '2026-04-09 13:51:15'),
(12, 28, 'event', NULL, 10, NULL, 5, '', 1, '2026-04-09 14:01:03', '2026-04-09 14:01:03'),
(13, 28, 'event', NULL, 12, NULL, 5, '', 1, '2026-04-09 15:13:59', '2026-04-09 15:13:59'),
(14, 28, 'room', 59, NULL, NULL, 5, '', 1, '2026-04-09 16:29:23', '2026-04-09 16:29:23'),
(15, 28, 'room', 64, NULL, NULL, 5, '', 1, '2026-04-11 10:50:04', '2026-04-11 10:50:04'),
(16, 28, 'event', NULL, 11, NULL, 5, '', 1, '2026-04-11 10:50:10', '2026-04-11 10:50:10'),
(17, 28, 'room', 55, NULL, NULL, 5, '', 1, '2026-04-11 10:50:19', '2026-04-11 10:50:19'),
(18, 28, 'room', 54, NULL, NULL, 5, '', 1, '2026-04-11 10:50:24', '2026-04-11 10:50:24');

-- --------------------------------------------------------

--
-- Table structure for table `rating_eligibility`
--

CREATE TABLE `rating_eligibility` (
  `eligibility_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `service_type` enum('room','event','food') NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `event_booking_id` int(11) DEFAULT NULL,
  `food_order_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `eligible_at` timestamp NULL DEFAULT NULL,
  `shown_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rating_eligibility`
--

INSERT INTO `rating_eligibility` (`eligibility_id`, `user_id`, `service_type`, `booking_id`, `event_booking_id`, `food_order_id`, `status`, `eligible_at`, `shown_at`, `completed_at`, `created_at`) VALUES
(9, 28, 'food', NULL, NULL, 23, 'completed', NULL, '2026-03-30 05:12:38', '2026-04-09 13:51:15', '2026-03-30 05:12:38'),
(10, 28, 'event', NULL, 10, NULL, 'completed', NULL, '2026-03-30 05:26:51', '2026-04-09 14:01:03', '2026-03-30 05:26:51'),
(11, 28, 'room', 54, NULL, NULL, 'completed', NULL, '2026-04-04 03:21:46', '2026-04-11 10:50:24', '2026-04-04 03:21:46'),
(12, 28, 'room', 55, NULL, NULL, 'completed', NULL, '2026-04-04 03:49:26', '2026-04-11 10:50:19', '2026-04-04 03:49:26'),
(13, 28, 'event', NULL, 10, NULL, 'completed', NULL, '2026-04-04 03:49:34', '2026-04-09 14:01:03', '2026-04-04 03:49:34'),
(14, 28, 'room', 55, NULL, NULL, 'completed', NULL, '2026-04-04 03:51:08', '2026-04-11 10:50:19', '2026-04-04 03:51:08'),
(15, 28, 'room', 54, NULL, NULL, 'completed', NULL, '2026-04-04 03:51:51', '2026-04-11 10:50:24', '2026-04-04 03:51:51'),
(16, 28, 'event', NULL, 10, NULL, 'completed', NULL, '2026-04-04 03:52:07', '2026-04-09 14:01:03', '2026-04-04 03:52:07'),
(17, 28, 'event', NULL, 12, NULL, 'completed', NULL, '2026-04-09 15:13:57', '2026-04-09 15:13:59', '2026-04-09 15:13:57'),
(18, 28, 'room', 59, NULL, NULL, 'completed', NULL, '2026-04-09 16:29:20', '2026-04-09 16:29:23', '2026-04-09 16:29:20'),
(19, 28, 'room', 64, NULL, NULL, 'completed', NULL, '2026-04-11 10:50:02', '2026-04-11 10:50:04', '2026-04-11 10:50:02'),
(20, 28, 'event', NULL, 11, NULL, 'completed', NULL, '2026-04-11 10:50:08', '2026-04-11 10:50:10', '2026-04-11 10:50:08');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `category` enum('room','dining','service','amenities','overall') DEFAULT 'overall',
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `category_id` int(11) NOT NULL,
  `floor` int(11) DEFAULT NULL,
  `status` enum('available','occupied','maintenance','cleaning','reserved') DEFAULT 'available',
  `housekeeping_status` enum('clean','dirty','inspected') DEFAULT 'clean',
  `special_features` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_number`, `category_id`, `floor`, `status`, `housekeeping_status`, `special_features`, `created_at`) VALUES
(1, '101', 1, 1, 'cleaning', 'clean', NULL, '2026-03-17 08:51:18'),
(2, '102', 1, 1, 'cleaning', 'clean', NULL, '2026-03-17 08:51:18'),
(3, '103', 1, 1, 'cleaning', 'clean', NULL, '2026-03-17 08:51:18'),
(4, '104', 1, 1, 'available', 'clean', NULL, '2026-03-17 08:51:18'),
(5, '201', 2, 2, 'reserved', 'clean', NULL, '2026-03-17 08:51:18'),
(6, '202', 2, 2, 'occupied', 'clean', NULL, '2026-03-17 08:51:18'),
(7, '203', 2, 2, 'available', 'clean', NULL, '2026-03-17 08:51:18'),
(8, '204', 2, 2, 'available', 'clean', NULL, '2026-03-17 08:51:18'),
(9, '301', 3, 3, 'cleaning', 'clean', NULL, '2026-03-17 08:51:18'),
(10, '302', 3, 3, 'available', 'clean', NULL, '2026-03-17 08:51:18'),
(11, '303', 4, 3, 'reserved', 'clean', NULL, '2026-03-17 08:51:18'),
(12, '304', 4, 3, 'reserved', 'clean', NULL, '2026-03-17 08:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `room_categories`
--

CREATE TABLE `room_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `max_occupancy` int(11) NOT NULL,
  `bed_type` varchar(50) DEFAULT NULL,
  `room_size_sqm` int(11) DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image_primary` varchar(255) DEFAULT NULL,
  `images_gallery` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_categories`
--

INSERT INTO `room_categories` (`category_id`, `category_name`, `description`, `base_price`, `max_occupancy`, `bed_type`, `room_size_sqm`, `amenities`, `image_primary`, `images_gallery`, `status`, `created_at`) VALUES
(1, 'Standard Room', 'Comfortable room with essential amenities perfect for budget-conscious travelers. Features city views and modern furnishings.', 2500.00, 2, 'Queen Bed', 25, 'WiFi, TV, Air Conditioning, Mini Refrigerator, Private Bathroom', 'images/rooms/1774620165_69c68e05399d1_Screenshot 2026-03-16 183348.png', 'images/rooms/1/1774619256_69c68a784a0e0_Screenshot 2026-03-15 205234.png,images/rooms/1/1774619256_69c68a784a82b_Screenshot 2026-03-15 205404.png,images/rooms/1/1774619256_69c68a784abc5_Screenshot 2026-03-15 210237.png,images/rooms/1/1774619256_69c68a784b0e2_Screenshot 2026-03-15 210640.png,images/rooms/1/1774619256_69c68a784b3cc_Screenshot 2026-03-15 211038.png,images/rooms/1/1774619386_69c68afa1dae4_Screenshot 2026-03-15 205234.png,images/rooms/1/1774619386_69c68afa1dcf8_Screenshot 2026-03-15 205404.png', 'active', '2026-03-17 08:51:18'),
(2, 'Deluxe Room', 'Spacious room with premium amenities and bay views. Includes work desk and sitting area.', 3500.00, 3, 'King Bed', 32, 'WiFi, Smart TV, Air Conditioning, Mini Bar, Coffee Maker, Safe, Bay View', NULL, NULL, 'active', '2026-03-17 08:51:18'),
(3, 'Suite', 'Luxurious suite with separate living area, bedroom with Jacuzzi, and panoramic ocean views.', 7500.00, 4, 'King Bed + Sofa Bed', 55, 'WiFi, Smart TV, Air Conditioning, Mini Bar, Coffee Machine, Safe, Jacuzzi, Ocean View, Living Room, Dining Area', NULL, NULL, 'active', '2026-03-17 08:51:18'),
(4, 'Family Room', 'Spacious room designed for families with two queen beds and kid-friendly amenities.', 4500.00, 4, '2 Queen Beds', 40, 'WiFi, TV, Air Conditioning, Mini Refrigerator, Kids Amenities, Connecting Room Option', NULL, NULL, 'active', '2026-03-17 08:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `room_virtual_tours`
--

CREATE TABLE `room_virtual_tours` (
  `tour_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `panorama_image` varchar(255) NOT NULL COMMENT 'Path to 360-degree equirectangular image',
  `thumbnail_image` varchar(255) DEFAULT NULL COMMENT 'Optional thumbnail preview',
  `title` varchar(100) NOT NULL DEFAULT 'Virtual Tour',
  `description` text DEFAULT NULL,
  `hotspot_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Hotspot configuration for interactive elements' CHECK (json_valid(`hotspot_config`)),
  `is_active` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='360-degree virtual tours for room categories';

--
-- Dumping data for table `room_virtual_tours`
--

INSERT INTO `room_virtual_tours` (`tour_id`, `category_id`, `panorama_image`, `thumbnail_image`, `title`, `description`, `hotspot_config`, `is_active`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'uploads/virtual_tours/1774611431_panorama_diagram.jpg', 'uploads/virtual_tours/1774611431_thumb_diagram.jpg', 'Standard Room - 360° View', 'Experience our comfortable Standard Room with a full 360-degree panoramic view. Perfect for budget-conscious travelers.', NULL, 1, 1, '2026-03-27 11:34:30', '2026-03-27 11:37:11'),
(2, 2, 'uploads/virtual_tours/deluxe_room_360.jpg', 'uploads/virtual_tours/deluxe_room_thumb.jpg', 'Deluxe Room - 360° View', 'Explore our spacious Deluxe Room with premium amenities and stunning bay views from every angle.', NULL, 1, 1, '2026-03-27 11:34:30', '2026-03-27 11:34:30'),
(3, 3, 'uploads/virtual_tours/suite_room_360.jpg', 'uploads/virtual_tours/suite_room_thumb.jpg', 'Suite - 360° View', 'Take a virtual tour of our luxurious Suite featuring a separate living area, bedroom, and panoramic ocean views.', NULL, 1, 1, '2026-03-27 11:34:30', '2026-03-27 11:34:30'),
(4, 4, 'uploads/virtual_tours/family_room_360.jpg', 'uploads/virtual_tours/family_room_thumb.jpg', 'Family Room - 360° View', 'Discover our Family Room designed for comfort with ample space for the whole family.', NULL, 1, 1, '2026-03-27 11:34:30', '2026-03-27 11:34:30');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `setting_group`) VALUES
(1, 'hotel_name', 'Bayawan Bai Hotel', 'general'),
(2, 'hotel_address', 'Bayawan City, Negros Oriental, Philippines', 'general'),
(3, 'hotel_phone', '+63 35 123 4567', 'general'),
(4, 'hotel_email', 'info@bayawanbaihotel.com', 'general'),
(5, 'check_in_time', '14:00', 'operations'),
(6, 'check_out_time', '12:00', 'operations'),
(7, 'currency', 'PHP', 'general'),
(8, 'facebook_url', 'https://facebook.com/bayawanbaihotel', 'social'),
(9, 'instagram_url', 'https://instagram.com/bayawanbaihotel', 'social'),
(10, 'twitter_url', 'https://twitter.com/bayawanbaihotel', 'social'),
(11, 'smtp_host', 'smtp.gmail.com', 'email'),
(12, 'smtp_port', '587', 'email'),
(13, 'smtp_username', 'bookings@bayawanbaihotel.com', 'email'),
(14, 'gcash_enabled', '1', 'payments'),
(15, 'paypal_enabled', '1', 'payments'),
(16, 'credit_card_enabled', '1', 'payments');

-- --------------------------------------------------------

--
-- Table structure for table `staff_permissions`
--

CREATE TABLE `staff_permissions` (
  `permission_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `page_name` varchar(100) NOT NULL,
  `can_access` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_permissions`
--

INSERT INTO `staff_permissions` (`permission_id`, `user_id`, `page_name`, `can_access`, `created_at`, `updated_at`) VALUES
(10, 2, 'inventory', 0, '2026-03-19 16:35:41', '2026-03-19 17:13:26'),
(11, 2, 'maintenance', 0, '2026-03-19 16:35:41', '2026-03-19 17:13:26'),
(12, 2, 'booking_charges', 0, '2026-03-19 16:35:41', '2026-03-19 17:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `staff_permission_settings`
--

CREATE TABLE `staff_permission_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_permission_settings`
--

INSERT INTO `staff_permission_settings` (`setting_id`, `setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
(13, 'allow_all_staff_inventory', 'true', '2026-03-19 16:35:11', '2026-03-28 00:06:25'),
(14, 'allow_all_staff_maintenance', 'true', '2026-03-19 16:35:11', '2026-03-28 00:06:25'),
(15, 'allow_all_staff_booking_charges', 'true', '2026-03-19 16:35:11', '2026-03-28 00:06:25');

-- --------------------------------------------------------

--
-- Table structure for table `staff_schedules`
--

CREATE TABLE `staff_schedules` (
  `schedule_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `shift_start` time DEFAULT NULL,
  `shift_end` time DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `status` enum('scheduled','completed','absent','leave') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('guest','receptionist','manager','admin') DEFAULT 'guest',
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `loyalty_points` int(11) DEFAULT 0,
  `member_since` date DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `active_status` tinyint(1) DEFAULT 0,
  `google_id` varchar(255) DEFAULT NULL,
  `facebook_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `first_name`, `last_name`, `phone`, `address`, `city`, `country`, `profile_picture`, `role`, `status`, `email_verified`, `loyalty_points`, `member_since`, `created_at`, `updated_at`, `last_login`, `active_status`, `google_id`, `facebook_id`) VALUES
(1, 'bayawanbaiminihotel@gmail.com', 'admin123', 'Admin', 'User', '+63 912 345 6789', '', '', '', 'assets/uploads/profile_pictures/admin_1_1774849069.jpg', 'admin', 'active', 0, 0, '2026-03-17', '2026-03-17 08:51:18', '2026-04-11 13:21:06', '2026-04-11 13:21:06', 1, '109361663575422923194', NULL),
(2, '09265040593k@gmail.com', 'staff123', 'Kylle', 'Acibron', '+63 923 456 7890', '', '', '', 'assets/uploads/profile_pictures/staff_2_1774849035.jpg', 'receptionist', 'active', 0, 0, '2026-03-17', '2026-03-17 08:51:18', '2026-04-11 13:21:32', '2026-04-11 13:21:32', 1, '100630696929272279982', NULL),
(3, 'manager@bayawanbaihotel.com', 'manager123', 'Juan', 'Dela Cruz', '+63 934 567 8901', NULL, NULL, NULL, NULL, 'manager', 'active', 0, 0, '2026-03-17', '2026-03-17 08:51:18', '2026-03-17 09:02:56', '2026-03-17 09:02:56', 0, NULL, NULL),
(23, 'gemdacibron@gmail.com', 'Gemnicole_09', 'Gem Nicole', 'Acibron', '', NULL, NULL, NULL, NULL, 'guest', 'active', 0, 0, '2026-03-18', '2026-03-18 13:46:06', '2026-03-18 13:46:30', '2026-03-18 13:46:30', 0, NULL, NULL),
(24, 'angelitofeliciano143@gmail.com', 'Toto122704', 'Angelito', 'Feliciano', '09368300598', NULL, NULL, NULL, NULL, 'guest', 'active', 0, 0, '2026-03-22', '2026-03-22 13:23:34', '2026-03-26 05:22:46', '2026-03-26 05:22:46', 0, NULL, NULL),
(28, 'kylleacibron@gmail.com', 'Kylle1234-', 'Kylle', 'Acibron', '09455470173', 'Claro M. Recto Street, Purok 4 Barangay Tinago, Bayawan City', 'Visayas, Negros Oriental, Bayawan City, Tinago (Pob.)', 'Philippines', 'assets/uploads/profile_pictures/user_28_1774851413.jpeg', 'guest', 'active', 0, 60, '2026-03-30', '2026-03-30 01:04:23', '2026-04-11 14:12:51', '2026-04-11 14:12:51', 1, '109268813112420568853', NULL),
(29, 'ljchu147@gmail.com', 'Bayawan213456', 'John Lloyd', 'Chu', '09661351116', NULL, NULL, NULL, NULL, 'guest', 'active', 0, 0, '2026-04-11', '2026-04-11 13:42:21', '2026-04-11 13:48:12', '2026-04-11 13:48:12', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `virtual_tour_hotspots`
--

CREATE TABLE `virtual_tour_hotspots` (
  `hotspot_id` int(11) NOT NULL,
  `tour_id` int(11) NOT NULL,
  `hotspot_type` enum('info','scene','link') DEFAULT 'info',
  `pitch` decimal(8,4) NOT NULL COMMENT 'Vertical angle in degrees (-90 to 90)',
  `yaw` decimal(8,4) NOT NULL COMMENT 'Horizontal angle in degrees (-180 to 180)',
  `text` varchar(255) DEFAULT NULL COMMENT 'Tooltip text',
  `target_tour_id` int(11) DEFAULT NULL COMMENT 'For scene type - target tour ID to navigate to',
  `target_url` varchar(255) DEFAULT NULL COMMENT 'For link type - external URL',
  `css_class` varchar(50) DEFAULT 'custom-hotspot',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `virtual_tour_hotspots`
--

INSERT INTO `virtual_tour_hotspots` (`hotspot_id`, `tour_id`, `hotspot_type`, `pitch`, `yaw`, `text`, `target_tour_id`, `target_url`, `css_class`, `created_at`) VALUES
(1, 2, 'info', -5.0000, 45.0000, 'King-size bed with premium linens', NULL, NULL, 'info-hotspot', '2026-03-27 11:34:30'),
(2, 2, 'info', -10.0000, -30.0000, 'Work desk with bay view', NULL, NULL, 'info-hotspot', '2026-03-27 11:34:30'),
(3, 2, 'info', 0.0000, 90.0000, 'Private balcony access', NULL, NULL, 'info-hotspot', '2026-03-27 11:34:30'),
(4, 3, 'info', -5.0000, 0.0000, 'Luxurious King Bed', NULL, NULL, 'info-hotspot', '2026-03-27 11:34:30'),
(5, 3, 'info', 0.0000, -90.0000, 'Living room area', NULL, NULL, 'info-hotspot', '2026-03-27 11:34:30'),
(6, 3, 'info', 10.0000, 45.0000, 'Jacuzzi tub', NULL, NULL, 'info-hotspot', '2026-03-27 11:34:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`amenity_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD UNIQUE KEY `booking_ref_2` (`booking_ref`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_booking_ref` (`booking_ref`);

--
-- Indexes for table `booking_charges`
--
ALTER TABLE `booking_charges`
  ADD PRIMARY KEY (`charge_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `chatbot_context`
--
ALTER TABLE `chatbot_context`
  ADD PRIMARY KEY (`context_id`),
  ADD UNIQUE KEY `unique_user_context` (`user_id`,`context_key`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `chatbot_knowledge`
--
ALTER TABLE `chatbot_knowledge`
  ADD PRIMARY KEY (`knowledge_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_message_type` (`message_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_last_message` (`last_message_at`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD PRIMARY KEY (`event_booking_id`),
  ADD UNIQUE KEY `event_ref` (`event_ref`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `space_id` (`space_id`),
  ADD KEY `idx_event_ref` (`event_ref`);

--
-- Indexes for table `event_spaces`
--
ALTER TABLE `event_spaces`
  ADD PRIMARY KEY (`space_id`);

--
-- Indexes for table `event_virtual_tours`
--
ALTER TABLE `event_virtual_tours`
  ADD PRIMARY KEY (`tour_id`),
  ADD KEY `idx_space_active` (`space_id`,`is_active`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `event_virtual_tour_hotspots`
--
ALTER TABLE `event_virtual_tour_hotspots`
  ADD PRIMARY KEY (`hotspot_id`),
  ADD KEY `target_tour_id` (`target_tour_id`),
  ADD KEY `idx_tour_position` (`tour_id`,`pitch`,`yaw`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`faq_id`);

--
-- Indexes for table `foods`
--
ALTER TABLE `foods`
  ADD PRIMARY KEY (`food_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `food_orders`
--
ALTER TABLE `food_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_ref` (`order_ref`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `food_id` (`food_id`),
  ADD KEY `idx_order_ref` (`order_ref`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes for table `homepage_slider`
--
ALTER TABLE `homepage_slider`
  ADD PRIMARY KEY (`slide_id`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`inv_cat_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `inv_cat_id` (`inv_cat_id`);

--
-- Indexes for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `reported_by` (`reported_by`);

--
-- Indexes for table `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`cat_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `cat_id` (`cat_id`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`subscriber_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `unique_user_type` (`user_id`,`notification_type`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `event_booking_id` (`event_booking_id`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promo_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_room_rating` (`user_id`,`booking_id`),
  ADD UNIQUE KEY `unique_event_rating` (`user_id`,`event_booking_id`),
  ADD UNIQUE KEY `unique_food_rating` (`user_id`,`food_order_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `event_booking_id` (`event_booking_id`),
  ADD KEY `food_order_id` (`food_order_id`),
  ADD KEY `idx_service_type` (`service_type`),
  ADD KEY `idx_rating_value` (`rating_value`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `rating_eligibility`
--
ALTER TABLE `rating_eligibility`
  ADD PRIMARY KEY (`eligibility_id`),
  ADD UNIQUE KEY `unique_eligibility` (`user_id`,`booking_id`,`event_booking_id`,`food_order_id`),
  ADD KEY `idx_eligibility_status` (`status`),
  ADD KEY `idx_eligible_at` (`eligible_at`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `room_categories`
--
ALTER TABLE `room_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `room_virtual_tours`
--
ALTER TABLE `room_virtual_tours`
  ADD PRIMARY KEY (`tour_id`),
  ADD KEY `idx_category_active` (`category_id`,`is_active`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `unique_user_page` (`user_id`,`page_name`);

--
-- Indexes for table `staff_permission_settings`
--
ALTER TABLE `staff_permission_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Indexes for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD UNIQUE KEY `facebook_id` (`facebook_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `virtual_tour_hotspots`
--
ALTER TABLE `virtual_tour_hotspots`
  ADD PRIMARY KEY (`hotspot_id`),
  ADD KEY `target_tour_id` (`target_tour_id`),
  ADD KEY `idx_tour_position` (`tour_id`,`pitch`,`yaw`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `amenities`
--
ALTER TABLE `amenities`
  MODIFY `amenity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `booking_charges`
--
ALTER TABLE `booking_charges`
  MODIFY `charge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `booking_logs`
--
ALTER TABLE `booking_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chatbot_context`
--
ALTER TABLE `chatbot_context`
  MODIFY `context_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chatbot_knowledge`
--
ALTER TABLE `chatbot_knowledge`
  MODIFY `knowledge_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_bookings`
--
ALTER TABLE `event_bookings`
  MODIFY `event_booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `event_spaces`
--
ALTER TABLE `event_spaces`
  MODIFY `space_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_virtual_tours`
--
ALTER TABLE `event_virtual_tours`
  MODIFY `tour_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `event_virtual_tour_hotspots`
--
ALTER TABLE `event_virtual_tour_hotspots`
  MODIFY `hotspot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `foods`
--
ALTER TABLE `foods`
  MODIFY `food_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `food_orders`
--
ALTER TABLE `food_orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `homepage_slider`
--
ALTER TABLE `homepage_slider`
  MODIFY `slide_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `inv_cat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `cat_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `subscriber_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=503;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `rating_eligibility`
--
ALTER TABLE `rating_eligibility`
  MODIFY `eligibility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `room_categories`
--
ALTER TABLE `room_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `room_virtual_tours`
--
ALTER TABLE `room_virtual_tours`
  MODIFY `tour_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `staff_permission_settings`
--
ALTER TABLE `staff_permission_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `virtual_tour_hotspots`
--
ALTER TABLE `virtual_tour_hotspots`
  MODIFY `hotspot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `room_categories` (`category_id`);

--
-- Constraints for table `booking_charges`
--
ALTER TABLE `booking_charges`
  ADD CONSTRAINT `booking_charges_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `booking_charges_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `booking_logs`
--
ALTER TABLE `booking_logs`
  ADD CONSTRAINT `booking_logs_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `booking_logs_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `chatbot_context`
--
ALTER TABLE `chatbot_context`
  ADD CONSTRAINT `chatbot_context_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `room_categories` (`category_id`);

--
-- Constraints for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD CONSTRAINT `event_bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `event_bookings_ibfk_2` FOREIGN KEY (`space_id`) REFERENCES `event_spaces` (`space_id`);

--
-- Constraints for table `event_virtual_tours`
--
ALTER TABLE `event_virtual_tours`
  ADD CONSTRAINT `event_virtual_tours_ibfk_1` FOREIGN KEY (`space_id`) REFERENCES `event_spaces` (`space_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_virtual_tour_hotspots`
--
ALTER TABLE `event_virtual_tour_hotspots`
  ADD CONSTRAINT `event_virtual_tour_hotspots_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `event_virtual_tours` (`tour_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_virtual_tour_hotspots_ibfk_2` FOREIGN KEY (`target_tour_id`) REFERENCES `event_virtual_tours` (`tour_id`) ON DELETE SET NULL;

--
-- Constraints for table `foods`
--
ALTER TABLE `foods`
  ADD CONSTRAINT `foods_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`cat_id`);

--
-- Constraints for table `food_orders`
--
ALTER TABLE `food_orders`
  ADD CONSTRAINT `food_orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `food_orders_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `food_orders_ibfk_3` FOREIGN KEY (`food_id`) REFERENCES `menu_items` (`item_id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`inv_cat_id`) REFERENCES `inventory_categories` (`inv_cat_id`);

--
-- Constraints for table `maintenance_requests`
--
ALTER TABLE `maintenance_requests`
  ADD CONSTRAINT `maintenance_requests_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  ADD CONSTRAINT `maintenance_requests_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`cat_id`) REFERENCES `menu_categories` (`cat_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `notification_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`event_booking_id`) REFERENCES `event_bookings` (`event_booking_id`) ON DELETE SET NULL;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`event_booking_id`) REFERENCES `event_bookings` (`event_booking_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ratings_ibfk_4` FOREIGN KEY (`food_order_id`) REFERENCES `food_orders` (`order_id`) ON DELETE SET NULL;

--
-- Constraints for table `rating_eligibility`
--
ALTER TABLE `rating_eligibility`
  ADD CONSTRAINT `rating_eligibility_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `room_categories` (`category_id`);

--
-- Constraints for table `room_virtual_tours`
--
ALTER TABLE `room_virtual_tours`
  ADD CONSTRAINT `room_virtual_tours_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `room_categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD CONSTRAINT `staff_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `staff_schedules`
--
ALTER TABLE `staff_schedules`
  ADD CONSTRAINT `staff_schedules_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `virtual_tour_hotspots`
--
ALTER TABLE `virtual_tour_hotspots`
  ADD CONSTRAINT `virtual_tour_hotspots_ibfk_1` FOREIGN KEY (`tour_id`) REFERENCES `room_virtual_tours` (`tour_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `virtual_tour_hotspots_ibfk_2` FOREIGN KEY (`target_tour_id`) REFERENCES `room_virtual_tours` (`tour_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
