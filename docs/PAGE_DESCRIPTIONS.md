# Bayawan Hotel Management System - Page Descriptions

This document provides a description of all pages in the Bayawan Hotel Management System, organized by user role and functionality.

---

## Main/Public Pages (Guest/Visitor Access)

| Page | Description |
|------|-------------|
| **index.php** | This figure shows the hotel's homepage displaying a hero slider with promotional images, featured room categories, active promotions, hotel statistics (total rooms, years of experience, happy guests), and featured customer testimonials. |
| **about.php** | This figure shows the About Us page displaying the hotel's history, mission and vision, core values, team information, and hotel statistics. |
| **amenities.php** | This figure shows the Amenities page displaying a gallery of hotel facilities including pools, spa, fitness center, restaurant, and other guest amenities with descriptions and images. |
| **rooms.php** | This figure shows the Rooms page displaying all available room categories with pricing, amenities, capacity information, and booking options for guests. |
| **room-details.php** | This figure shows the Room Details page displaying detailed information about a specific room category including images, amenities, pricing, availability calendar, and booking form. |
| **booking.php** | This figure shows the Booking page displaying the room reservation form where guests can select dates, room category, number of guests, and complete their reservation. |
| **booking-confirmation.php** | This figure shows the Booking Confirmation page displaying the reservation summary, payment options, and confirmation details after a successful booking. |
| **availability.php** | This figure shows the Room Availability page displaying real-time room availability calendar showing which rooms are available for selected dates. |
| **dining.php** | This figure shows the Dining page displaying restaurant information, menu categories, food items, dining hours, and online food ordering options. |
| **foods-details.php** | This figure shows the Food Details page displaying detailed information about a specific menu item including images, ingredients, price, and ordering options. |
| **order-now.php** | This figure shows the Order Now page displaying the food ordering interface where guests can select food items, customize orders, and complete food purchases. |
| **payment-process.php** | This figure shows the Payment Process page displaying the payment gateway interface for processing room booking payments with multiple payment options. |
| **food-order-payment-process.php** | This figure shows the Food Order Payment Process page displaying the payment gateway interface specifically for food order transactions. |
| **events.php** | This figure shows the Events page displaying available event spaces for conferences, weddings, and meetings with capacity information, amenities, and booking options. |
| **event-space-details.php** | This figure shows the Event Space Details page displaying detailed information about a specific event venue including images, capacity, pricing, and availability calendar. |
| **event-virtual-tour.php** | This figure shows the Event Virtual Tour page displaying an interactive 360-degree virtual tour of the event spaces allowing guests to explore venues online. |
| **virtual-tour.php** | This figure shows the Virtual Tour page displaying an interactive 360-degree virtual tour of the hotel rooms and facilities allowing guests to explore before booking. |
| **gallery.php** | This figure shows the Gallery page displaying a photo gallery of the hotel rooms, amenities, events, dining areas, and facilities in a grid or carousel layout. |
| **contact.php** | This figure shows the Contact page displaying contact information, location map, contact form for inquiries, and hotel operating hours. |
| **location.php** | This figure shows the Location page displaying the hotel's address, interactive map, directions, nearby attractions, and transportation information. |
| **faq.php** | This figure shows the FAQ page displaying frequently asked questions organized by categories with collapsible answers for guest self-service support. |
| **terms.php** | This figure shows the Terms and Conditions page displaying legal terms, booking policies, cancellation rules, and guest agreement conditions. |
| **privacy.php** | This figure shows the Privacy Policy page displaying the hotel's data privacy practices, GDPR compliance information, and guest data protection policies. |
| **subscribe.php** | This figure shows the Newsletter Subscribe page displaying the email subscription form for guests to receive promotional offers and hotel news. |
| **unsubscribe.php** | This figure shows the Unsubscribe page displaying the email unsubscription form for guests to opt out of marketing communications. |
| **download-app.php** | This figure shows the Download App page displaying information about the hotel's mobile application with download links for iOS and Android platforms. |
| **my-bookings.php** | This figure shows the My Bookings page displaying a summary of the guest's current and past room reservations with status indicators and quick actions. |
| **test_qr.php** | This figure shows the QR Code Test page displaying a testing interface for QR code generation and scanning functionality used in the hotel system. |

---

## Authentication Pages (Login/Register)

| Page | Description |
|------|-------------|
| **auth/login.php** | This figure shows the Login page displaying the user authentication form with email/username and password fields, social login options (Google/Facebook), and password recovery link. |
| **auth/register.php** | This figure shows the Registration page displaying the new user signup form with personal information fields, email verification, and account creation options. |
| **auth/logout.php** | This figure shows the Logout page (process page) displaying logout confirmation and session termination functionality redirecting users to the homepage. |
| **auth/google-callback.php** | This figure shows the Google OAuth Callback page displaying the authentication handler for Google social login integration and account linking. |
| **auth/facebook-callback.php** | This figure shows the Facebook OAuth Callback page displaying the authentication handler for Facebook social login integration and account linking. |

---

## User Dashboard Pages (Guest/Customer Portal)

| Page | Description |
|------|-------------|
| **user/dashboard.php** | This figure shows the User Dashboard page displaying the guest's personal dashboard with booking statistics, upcoming stays, recent event bookings, food orders, and account summary cards. |
| **user/my-bookings.php** | This figure shows the My Bookings page displaying a comprehensive list of all the guest's room reservations with filtering, sorting, status tracking, and management options including archive and cancellation features. |
| **user/booking-details.php** | This figure shows the Booking Details page displaying detailed information about a specific room reservation including dates, room info, payment status, and service history. |
| **user/pending.php** | This figure shows the Pending Bookings page displaying all pending room reservations awaiting confirmation with payment reminders and cancellation options. |
| **user/confirmed.php** | This figure shows the Confirmed Bookings page displaying all confirmed room reservations with check-in instructions, QR codes, and modification options. |
| **user/checkin.php** | This figure shows the Check-in page displaying the online check-in interface for guests to complete pre-arrival registration and upload documents. |
| **user/checkout.php** | This figure shows the Check-out page displaying the online check-out interface for guests to review charges, provide feedback, and complete departure. |
| **user/completed.php** | This figure shows the Completed Bookings page displaying all completed/checked-out room stays with booking history, invoices, and review options. |
| **user/cancelled.php** | This figure shows the Cancelled Bookings page displaying all cancelled reservations with cancellation reasons, refund status, and rebooking options. |
| **user/archive.php** | This figure shows the Archived Bookings page displaying archived reservations that are hidden from the main bookings list but retained for records. |
| **user/trash.php** | This figure shows the Trash page displaying soft-deleted bookings that can be restored or permanently deleted by the user. |
| **user/my-event-bookings.php** | This figure shows the My Event Bookings page displaying all event space reservations with event details, catering options, and event management tools. |
| **user/my-food-orders.php** | This figure shows the My Food Orders page displaying all food and beverage orders with order status tracking, delivery information, and reorder options. |
| **user/my-service-requests.php** | This figure shows the My Service Requests page displaying all housekeeping and maintenance requests submitted by the guest with status updates. |
| **user/request-service.php** | This figure shows the Request Service page displaying the service request form for guests to submit housekeeping, maintenance, or special service requests. |
| **user/maintenance-request.php** | This figure shows the Maintenance Request page displaying the maintenance request form for guests to report room issues or request repairs. |
| **user/my-reviews.php** | This figure shows the My Reviews page displaying all reviews and ratings submitted by the guest for rooms, services, and dining with edit options. |
| **user/inbox.php** | This figure shows the Inbox page displaying the guest's message inbox with communications from hotel staff, booking confirmations, and promotional messages. |
| **user/my-messages.php** | This figure shows the My Messages page displaying the messaging interface for direct communication between guests and hotel staff. |
| **user/notifications.php** | This figure shows the Notifications page displaying all system notifications including booking updates, check-in reminders, and promotional alerts. |
| **user/notification-settings.php** | This figure shows the Notification Settings page displaying preferences for email, SMS, and push notifications with subscription management options. |
| **user/profile.php** | This figure shows the Profile page displaying the guest's personal information, contact details, preferences, and account settings with edit functionality. |
| **user/change-password.php** | This figure shows the Change Password page displaying the password modification form with current password verification and new password creation. |
| **user/user-calendar.php** | This figure shows the User Calendar page displaying a calendar view of the guest's bookings, events, and reservations with upcoming stay reminders. |

---

## Staff Pages (Staff/Management Portal)

| Page | Description |
|------|-------------|
| **staff/staff-dashboard.php** | This figure shows the Staff Dashboard page displaying the staff control panel with today's check-ins, check-outs, pending bookings, occupied rooms, and arrival/departure summaries. |
| **staff/staff-bookings.php** | This figure shows the Staff Bookings page displaying all room reservations with search, filter, and management tools for staff to process bookings. |
| **staff/staff-booking-charges.php** | This figure shows the Booking Charges page displaying additional charges management for room bookings including late fees, minibar, and service charges. |
| **staff/confirm-booking.php** | This figure shows the Confirm Booking page displaying the booking confirmation interface for staff to verify and approve pending reservations. |
| **staff/checkin.php** | This figure shows the Staff Check-in page displaying the guest check-in interface for staff to process arrivals, verify IDs, and assign rooms. |
| **staff/checkout.php** | This figure shows the Staff Check-out page displaying the guest check-out interface for staff to process departures, settle bills, and inspect rooms. |
| **staff/walkin-booking.php** | This figure shows the Walk-in Booking page displaying the interface for staff to create reservations for guests who arrive without prior booking. |
| **staff/staff-event-bookings.php** | This figure shows the Staff Event Bookings page displaying all event space reservations with event details, catering requirements, and setup instructions. |
| **staff/staff-foods-orders.php** | This figure shows the Staff Food Orders page displaying all food and beverage orders with kitchen status tracking, delivery management, and order fulfillment. |
| **staff/staff-maintenance.php** | This figure shows the Staff Maintenance page displaying maintenance task management with room status updates, repair schedules, and maintenance staff assignments. |
| **staff/staff-inventory.php** | This figure shows the Staff Inventory page displaying inventory management for room supplies, amenities, and consumables with stock levels and reorder alerts. |
| **staff/staff-service-requests.php** | This figure shows the Staff Service Requests page displaying all guest service requests with assignment tools, status tracking, and completion workflows. |
| **staff/staff-qr-scanner.php** | This figure shows the Staff QR Scanner page displaying the QR code scanning interface for room booking verification and check-in/check-out processing. |
| **staff/staff-qr-scanner-event.php** | This figure shows the Event QR Scanner page displaying the QR code scanning interface for event booking verification and attendee management. |
| **staff/staff-qr-scanner-food.php** | This figure shows the Food QR Scanner page displaying the QR code scanning interface for food order verification and delivery confirmation. |
| **staff/staff-calendar.php** | This figure shows the Staff Calendar page displaying a calendar view of all bookings, events, and staff schedules with availability and occupancy visualization. |
| **staff/staff-contact-messages.php** | This figure shows the Staff Contact Messages page displaying guest inquiries and contact form submissions with response tools and message management. |
| **staff/staff-profile.php** | This figure shows the Staff Profile page displaying staff member's personal information, work schedule, permissions, and account settings. |
| **staff/notifications.php** | This figure shows the Staff Notifications page displaying system alerts, booking updates, task assignments, and operational notifications for staff. |
| **staff/notification-settings.php** | This figure shows the Staff Notification Settings page displaying preferences for receiving alerts about bookings, tasks, and system events. |

---

## Admin Pages (Administrator Portal)

| Page | Description |
|------|-------------|
| **admin/admin-dashboard.php** | This figure shows the Admin Dashboard page displaying the administrator control panel with user statistics, booking counts, revenue metrics, room occupancy rates, food order statistics, and month-over-month comparison charts. |
| **admin/admin-bookings.php** | This figure shows the Admin Bookings page displaying all hotel reservations with advanced search, filtering by status/date, bulk actions, and detailed booking management tools. |
| **admin/admin-booking-details.php** | This figure shows the Admin Booking Details page displaying comprehensive information about a specific reservation including guest info, payment details, room assignment, and booking timeline. |
| **admin/admin-active.php** | This figure shows the Active Bookings page displaying currently active reservations including checked-in guests and confirmed upcoming stays with guest contact info. |
| **admin/admin-users.php** | This figure shows the Admin Users page displaying all registered users with role management, account status controls, search functionality, and user activity tracking. |
| **admin/admin-calendar.php** | This figure shows the Admin Calendar page displaying a comprehensive calendar view of all room bookings, event reservations, and hotel operations with drag-and-drop scheduling. |
| **admin/admin-reports.php** | This figure shows the Admin Reports page displaying financial reports, occupancy statistics, revenue analytics, guest demographics, and downloadable report generation. |
| **admin/admin-analytics.php** | This figure shows the Admin Analytics page displaying advanced business intelligence dashboards with charts, graphs, and data visualization for hotel performance metrics. |
| **admin/admin-room-categories.php** | This figure shows the Room Categories page displaying management interface for room types including pricing, amenities, capacity settings, and category status controls. |
| **admin/admin-rooms.php** | This figure shows the Rooms page displaying individual room management with room numbers, floor assignment, status updates, maintenance tracking, and room details. |
| **admin/admin-event-spaces.php** | This figure shows the Event Spaces page displaying management interface for event venues including capacity, pricing, amenities, availability, and booking configuration. |
| **admin/admin-event-bookings.php** | This figure shows the Admin Event Bookings page displaying all event reservations with event details, catering orders, setup requirements, and event approval workflow. |
| **admin/admin-virtual-tours.php** | This figure shows the Virtual Tours Management page displaying the interface for creating and managing 360-degree virtual tours for rooms and facilities. |
| **admin/admin-virtual-tour-hotspots.php** | This figure shows the Virtual Tour Hotspots page displaying the interactive hotspot management for virtual tours with navigation points and information overlays. |
| **admin/admin-event-virtual-tours.php** | This figure shows the Event Virtual Tours Management page displaying the interface for creating and managing virtual tours specifically for event spaces. |
| **admin/admin-event-virtual-tour-hotspots.php** | This figure shows the Event Virtual Tour Hotspots page displaying hotspot configuration for event space virtual tours with navigation and information points. |
| **admin/event-tour-preview.php** | This figure shows the Event Tour Preview page displaying a preview interface for administrators to test and review event virtual tours before publishing. |
| **admin/admin-amenities.php** | This figure shows the Amenities Management page displaying the interface for adding, editing, and managing hotel amenities and facilities with images and descriptions. |
| **admin/admin-additional-services.php** | This figure shows the Additional Services page displaying management interface for extra guest services like spa treatments, transportation, and special packages. |
| **admin/admin-foods-inventory.php** | This figure shows the Foods Inventory page displaying restaurant inventory management with stock levels, ingredient tracking, supplier information, and reorder management. |
| **admin/admin-menu-categories.php** | This figure shows the Menu Categories page displaying management interface for food menu categories with organization, display order, and category status controls. |
| **admin/admin-menu-items.php** | This figure shows the Menu Items page displaying the interface for adding, editing, and managing food and beverage items with pricing, ingredients, and availability. |
| **admin/admin-inventory-categories.php** | This figure shows the Inventory Categories page displaying category management for hotel inventory items with classification and organization tools. |
| **admin/admin-inventory-items.php** | This figure shows the Inventory Items page displaying comprehensive inventory management with stock tracking, usage logs, and automated reorder notifications. |
| **admin/admin-gallery.php** | This figure shows the Gallery Management page displaying the interface for uploading, organizing, and managing hotel photos in the public gallery with categorization. |
| **admin/admin-homepage-slider.php** | This figure shows the Homepage Slider Management page displaying the interface for managing hero banner images, captions, links, and slide ordering on the homepage. |
| **admin/admin-faqs.php** | This figure shows the FAQ Management page displaying the interface for creating, editing, and organizing frequently asked questions with categorization and ordering. |
| **admin/admin-promotions.php** | This figure shows the Promotions Management page displaying the interface for creating and managing promotional offers, discount codes, and special deals. |
| **admin/admin-ratings.php** | This figure shows the Ratings Management page displaying guest rating data, review analytics, rating trends, and response management tools. |
| **admin/admin-reviews.php** | This figure shows the Reviews Management page displaying guest reviews with approval workflow, response tools, feature flagging, and moderation controls. |
| **admin/admin-payments.php** | This figure shows the Payments Management page displaying all payment transactions with status tracking, refund processing, payment gateway logs, and financial records. |
| **admin/admin-contact-messages.php** | This figure shows the Admin Contact Messages page displaying all guest inquiries with assignment tools, response tracking, and message resolution workflows. |
| **admin/admin-team.php** | This figure shows the Team Management page displaying hotel staff directory with member profiles, roles, contact information, and team organization. |
| **admin/admin-staff-permissions.php** | This figure shows the Staff Permissions page displaying role-based access control configuration with permission assignment for different staff levels. |
| **admin/admin-staff-schedules.php** | This figure shows the Staff Schedules page displaying work schedule management with shift assignments, time-off requests, and staff availability calendars. |
| **admin/admin-tasks.php** | This figure shows the Tasks Management page displaying task assignment and tracking for staff with priority levels, deadlines, and completion status. |
| **admin/admin-maintenance.php** | This figure shows the Admin Maintenance page displaying maintenance task scheduling, room maintenance history, and preventive maintenance planning tools. |
| **admin/admin-operations.php** | This figure shows the Operations Management page displaying day-to-day hotel operations monitoring with occupancy tracking and operational status indicators. |
| **admin/admin-activity-logs.php** | This figure shows the Activity Logs page displaying comprehensive system audit logs with user actions, timestamp tracking, and log filtering/search capabilities. |
| **admin/admin-user-sessions.php** | This figure shows the User Sessions page displaying active user session monitoring with login history, device tracking, and session management tools. |
| **admin/admin-notification-logs.php** | This figure shows the Notification Logs page displaying all sent notifications with delivery status, read receipts, and notification history tracking. |
| **admin/admin-notifications.php** | This figure shows the Admin Notifications page displaying the notification center for administrators with system alerts and broadcast message tools. |
| **admin/notification-settings.php** | This figure shows the Admin Notification Settings page displaying system-wide notification configuration with email templates and alert preferences. |
| **admin/admin-profile.php** | This figure shows the Admin Profile page displaying the administrator's personal information, account settings, and security configuration options. |
| **admin/admin-settings.php** | This figure shows the Admin Settings page displaying system configuration options for hotel information, booking rules, payment settings, and global preferences. |

---

## Summary

| Role | Page Count |
|------|------------|
| Main/Public Pages | 28 |
| Authentication | 5 |
| User Dashboard | 24 |
| Staff Portal | 20 |
| Admin Portal | 48 |
| **Total** | **125** |
