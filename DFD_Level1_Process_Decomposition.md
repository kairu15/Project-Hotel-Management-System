# Level 1 DFD - Process Decomposition
## Bayawan Bai Hotel Management System

```mermaid
flowchart TB
    subgraph EXTERNAL["External Entities"]
        GUEST["👤 GUEST"]
        STAFF["👔 STAFF\n(Reception/Manager)"]
        ADMIN["⚙️ ADMIN"]
    end

    subgraph P1["1.0 USER MANAGEMENT & AUTHENTICATION"]
        P1_1["1.1 Register User"]
        P1_2["1.2 Login User"]
        P1_3["1.3 Profile Management"]
        P1_4["1.4 Password Reset"]
        P1_5["1.5 Social OAuth\n(Facebook/Google)"]
    end

    subgraph D1["Data Store: D1 USERS TABLE"]
        DB_USERS[("users\nuser_id, email, role, status")]
    end

    subgraph P2["2.0 ROOM BOOKING MANAGEMENT"]
        P2_1["2.1 Check Availability"]
        P2_2["2.2 Create Booking"]
        P2_3["2.3 Modify Booking"]
        P2_4["2.4 Cancel Booking"]
        P2_5["2.5 Check In"]
        P2_6["2.6 Check Out"]
        P2_7["2.7 Walk-in Booking"]
    end

    subgraph D2["Data Stores"]
        DB_ROOMS[("D2 ROOMS")]
        DB_BOOKINGS[("D3 BOOKINGS")]
        DB_CALENDAR[("D4 CALENDAR/SCHEDULE")]
    end

    subgraph P3["3.0 ADMIN & CONFIGURATION"]
        P3_1["3.1 Room Categories"]
        P3_2["3.2 User Management"]
        P3_3["3.3 System Settings"]
        P3_4["3.4 Reports & Analytics"]
        P3_5["3.5 Promos & Pricing"]
        P3_6["3.6 Staff Schedules"]
        P3_7["3.7 Permission Management"]
    end

    subgraph D5["Data Store: D5 SETTINGS"]
        DB_SETTINGS[("settings\nconfiguration data")]
    end

    subgraph P4["4.0 FOOD SERVICES"]
        P4_1["4.1 Browse Menu"]
        P4_2["4.2 Place Order"]
        P4_3["4.3 Track Order"]
    end

    subgraph P5["5.0 PAYMENT PROCESSING"]
        P5_1["5.1 Process Payment"]
        P5_2["5.2 Verify Transaction"]
        P5_3["5.3 Issue Receipt"]
    end

    subgraph P6["6.0 EVENT MANAGEMENT"]
        P6_1["6.1 Check Availability"]
        P6_2["6.2 Book Event"]
        P6_3["6.3 Manage Space"]
    end

    subgraph D6["Data Stores"]
        DB_MENU[("D6 MENU ITEMS")]
        DB_FOOD_ORDERS[("D7 FOOD ORDERS")]
        DB_PAYMENTS[("D7 PAYMENTS")]
        DB_EVENT_SPACES[("D8 EVENT SPACES")]
        DB_EVENT_BOOKINGS[("D9 EVENT BOOKINGS")]
    end

    subgraph P7["7.0 INVENTORY MANAGEMENT"]
        P7_1["7.1 Track Stock"]
        P7_2["7.2 Reorder Items"]
        P7_3["7.3 Manage Suppliers"]
    end

    subgraph P8["8.0 CONTENT MANAGEMENT"]
        P8_1["8.1 Manage Slider"]
        P8_2["8.2 Gallery Images"]
        P8_3["8.3 Virtual Tours"]
    end

    subgraph P9["9.0 CHATBOT SERVICE"]
        P9_1["9.1 Answer Queries"]
        P9_2["9.2 Suggest Bookings"]
        P9_3["9.3 Provide Info"]
    end

    subgraph D10["Data Stores"]
        DB_INVENTORY[("D10 INVENTORY ITEMS")]
        DB_GALLERY[("D11 GALLERY")]
        DB_VTOURS[("D12 VIRTUAL TOURS")]
        AI_SERVICE[("EXTERNAL AI\nGemini Service")]
    end

    subgraph P10["10.0 NOTIFICATION SYSTEM"]
        P10_1["10.1 Email Alerts"]
        P10_2["10.2 SMS Alerts"]
        P10_3["10.3 Push Notifications"]
        P10_4["10.4 In-App Notifications"]
    end

    subgraph D13["Data Store: D13 NOTIFICATION LOGS"]
        DB_NOTIF_LOGS[("notification_logs")]
    end

    %% External Entity connections
    GUEST -->|"Registration/Login\nBooking Requests"| P1
    GUEST -->|"Room Inquiries\nReviews/Feedback"| P2
    GUEST --> P4
    GUEST --> P6
    
    STAFF -->|"Walk-in Booking"| P2_7
    STAFF --> P7
    
    ADMIN --> P3

    %% Process 1 connections
    P1 --> DB_USERS
    P1 --> P2
    
    %% Process 2 connections
    P2_1 --> DB_ROOMS
    P2_2 --> DB_BOOKINGS
    P2 --> DB_CALENDAR
    P2 -->|"Booking Details\nAmount Due"| P5
    P5 -->|"Payment Confirmation"| P2
    P2 --> P10
    
    %% Process 3 connections
    P3 --> DB_SETTINGS
    P3 --> DB_ROOMS
    P3 --> DB_USERS
    P3 --> DB_BOOKINGS
    
    %% Process 4 connections
    P4 --> DB_MENU
    P4_2 --> DB_FOOD_ORDERS
    P4 -->|"Order Total"| P5
    
    %% Process 5 connections
    P5 --> DB_PAYMENTS
    
    %% Process 6 connections
    P6 --> DB_EVENT_SPACES
    P6_2 --> DB_EVENT_BOOKINGS
    P6 -->|"Event Cost"| P5
    
    %% Process 7 connections
    P7 --> DB_INVENTORY
    P7 -->|"Stock Reports"| P3
    
    %% Process 8 connections
    P8 --> DB_GALLERY
    P8 --> DB_VTOURS
    
    %% Process 9 connections
    P9 -->|"Booking Intent"| P2
    P9 --> AI_SERVICE
    
    %% Process 10 connections
    P10 --> DB_NOTIF_LOGS
    P10 -->|"Delivery Status"| P1
```

---

## ASCII Art Alternative (Legacy)

<details>
<summary>View Original ASCII Version</summary>

```
[Original ASCII diagram preserved here...]
```
</details>

---

## Process Descriptions

### **1.0 USER MANAGEMENT & AUTHENTICATION**
Handles all user-related operations including registration, login, profile management, and password resets.
- **Inputs:** Registration data, login credentials, OAuth tokens
- **Outputs:** User sessions, authentication tokens, profile data
- **Data Stores:** D1 Users Table

### **2.0 ROOM BOOKING MANAGEMENT**
Core booking system handling room availability checks, reservations, modifications, and check-in/check-out.
- **2.1 Check Availability** - Queries room availability for date ranges
- **2.2 Create Booking** - Processes new reservations
- **2.3 Modify Booking** - Handles changes to existing bookings
- **2.4 Cancel Booking** - Processes cancellations and refunds
- **2.5 Check In** - Guest arrival processing
- **2.6 Check Out** - Departure processing and billing
- **2.7 Walk-in Booking** - Direct desk bookings by staff
- **Data Stores:** D2 Rooms, D3 Bookings, D4 Calendar/Schedule

### **3.0 ADMIN & CONFIGURATION**
Administrative functions for system management.
- **3.1 Room Categories Management** - Define room types, pricing, amenities
- **3.2 User Management** - CRUD operations on users
- **3.3 System Settings** - Global configuration
- **3.4 Reports & Analytics** - Generate business reports
- **3.5 Promotions & Pricing** - Special offers and dynamic pricing
- **3.6 Staff Schedules** - Work shift management
- **3.7 Permission Management** - Role-based access control
- **Data Stores:** D5 Settings, D1 Users, D2 Rooms

### **4.0 FOOD SERVICES**
Restaurant and room service ordering system.
- **4.1 Browse Menu** - Display food items and categories
- **4.2 Place Order** - Submit food orders
- **4.3 Track Order** - Order status tracking
- **Data Stores:** D6 Menu Items, D7 Food Orders

### **5.0 PAYMENT PROCESSING**
Handles all financial transactions.
- **5.1 Process Payment** - Charge processing via GCash, PayPal, Credit Card
- **5.2 Verify Transaction** - Payment confirmation and fraud checks
- **5.3 Issue Receipt** - Generate payment confirmations
- **External:** Payment Gateways (GCash, PayPal, Stripe)
- **Data Store:** D7 Payments

### **6.0 EVENT MANAGEMENT**
Event space booking and management.
- **6.1 Check Availability** - Venue availability queries
- **6.2 Book Event** - Event reservation processing
- **6.3 Manage Space** - Venue configuration
- **Data Stores:** D8 Event Spaces, D9 Event Bookings

### **7.0 INVENTORY MANAGEMENT**
Hotel supplies and stock management.
- **7.1 Track Stock** - Inventory level monitoring
- **7.2 Reorder Items** - Purchase order generation
- **7.3 Manage Suppliers** - Vendor relationships
- **Data Store:** D10 Inventory Items

### **8.0 CONTENT MANAGEMENT**
Website content and media management.
- **8.1 Manage Slider** - Homepage banner content
- **8.2 Gallery Images** - Photo management
- **8.3 Virtual Tours** - 360° tour management
- **Data Stores:** D11 Gallery, D12 Virtual Tours

### **9.0 CHATBOT SERVICE**
AI-powered customer service automation.
- **9.1 Answer Queries** - FAQ and information responses
- **9.2 Suggest Bookings** - Room recommendations
- **9.3 Provide Info** - General hotel information
- **External:** Gemini AI Service

### **10.0 NOTIFICATION SYSTEM**
Multi-channel communication system.
- **10.1 Email Alerts** - Booking confirmations, promotions
- **10.2 SMS Alerts** - Payment confirmations, reminders
- **10.3 Push Notifications** - Mobile app alerts
- **10.4 In-App Notifications** - Dashboard alerts
- **Data Store:** D13 Notification Logs

---

## Data Store Definitions

| ID | Name | Description | Key Entities |
|----|------|-------------|--------------|
| **D1** | Users Table | Guest, staff, and admin accounts | user_id, email, role, status |
| **D2** | Rooms | Room inventory and status | room_id, category_id, status |
| **D3** | Bookings | Reservation records | booking_id, user_id, dates, status |
| **D4** | Calendar/Schedule | Availability and scheduling data | dates, room assignments |
| **D5** | Settings Table | System configuration | setting_key, setting_value |
| **D6** | Menu Items | Food and beverage catalog | item_id, name, price, category |
| **D7** | Food Orders | Order transactions | order_id, user_id, items, status |
| **D7** | Payments | Financial transactions | payment_id, booking_id, amount, method |
| **D8** | Event Spaces | Venue inventory | space_id, name, capacity, price |
| **D9** | Event Bookings | Event reservations | event_booking_id, space_id, date |
| **D10** | Inventory Items | Supply stock | item_id, quantity, reorder_level |
| **D11** | Gallery | Media content | image_id, path, category |
| **D12** | Virtual Tours | 360° tour data | tour_id, hotspots, images |
| **D13** | Notification Logs | Communication history | log_id, user_id, type, status |

---

## Data Flow Matrix

| From Process | To Process | Data Flow Description |
|--------------|------------|----------------------|
| 1.0 (User Mgmt) | 2.0 (Booking) | Validated user_id |
| 2.0 (Booking) | 5.0 (Payment) | Booking details, amount due |
| 5.0 (Payment) | 2.0 (Booking) | Payment confirmation, status |
| 2.0 (Booking) | 10.0 (Notification) | Trigger events (confirm, reminder) |
| 4.0 (Food) | 5.0 (Payment) | Order total, payment request |
| 6.0 (Event) | 5.0 (Payment) | Event cost, payment request |
| 3.0 (Admin) | 2.0 (Booking) | Configuration updates |
| 9.0 (Chatbot) | 2.0 (Booking) | Booking intent, user queries |
| 7.0 (Inventory) | 3.0 (Admin) | Stock reports, alerts |
| 10.0 (Notification) | 1.0 (User Mgmt) | Delivery status |

---

## Key Processes to Level 2 DFD

For more detailed analysis, the following processes can be decomposed further:
- **2.0 Room Booking Management** → Individual booking lifecycle states
- **5.0 Payment Processing** → Gateway-specific flows
- **3.0 Admin & Configuration** → Each admin function as separate process
