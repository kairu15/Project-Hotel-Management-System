# Bayawan Bai Hotel - System Architecture Design

## 1. High-Level Architecture Overview

```mermaid
flowchart TB
    subgraph CLIENT["📱 Client Layer"]
        WEB["Web Browser\n(HTML/CSS/JS)"]
        MOBILE["Mobile App\n(PWA/Responsive)"]
        THIRD["Third-party\n(OAuth Providers)"]
    end

    subgraph WEBSERVER["🌐 Web Server Layer"]
        APACHE["Apache HTTP Server\n(XAMPP Stack)"]
    end

    subgraph APPLICATION["⚙️ Application Layer (PHP)"]
        subgraph PUBLIC["Public Pages"]
            INDEX["index.php\nHomepage"]
            ROOMS["rooms.php\nRoom Listings"]
            DINING["dining.php\nRestaurant"]
            EVENTS["events.php\nEvent Spaces"]
            ABOUT["about.php\nAbout/Contact"]
        end

        subgraph AUTH["Authentication Module"]
            LOGIN["login.php\nLocal Auth"]
            REGISTER["register.php\nUser Registration"]
            GOOGLE["google-callback.php\nGoogle OAuth"]
            FACEBOOK["facebook-callback.php\nFacebook OAuth"]
        end

        subgraph USERPORTAL["User Portal"]
            UDASH["user/dashboard.php\nUser Dashboard"]
            UBOOKINGS["user/my-bookings.php\nMy Bookings"]
            UORDERS["user/my-food-orders.php\nFood Orders"]
            UEVENTS["user/my-event-bookings.php\nEvent Bookings"]
        end

        subgraph STAFFPORTAL["Staff Portal"]
            SDASH["staff/staff-dashboard.php\nStaff Dashboard"]
            SCANNER["staff/staff-qr-scanner.php\nQR Scanner"]
            CHECKIN["staff/checkin.php\nCheck-in"]
            CHECKOUT["staff/checkout.php\nCheck-out"]
            WALKIN["staff/walkin-booking.php\nWalk-in Booking"]
        end

        subgraph ADMINPORTAL["Admin Portal"]
            ADASH["admin/admin-dashboard.php\nAdmin Dashboard"]
            ABOOKINGS["admin/admin-bookings.php\nBooking Management"]
            AUSERS["admin/admin-users.php\nUser Management"]
            AREPORTS["admin/admin-reports.php\nReports & Analytics"]
            ASETTINGS["admin/admin-settings.php\nSystem Settings"]
        end

        subgraph API["API Endpoints"]
            CHATBOT["api/chatbot.php\nAI Chatbot"]
            PAYMENT["api/event-payment-process.php\nPayment Processing"]
            NOTIF["api/ajax-notifications.php\nNotifications"]
            CALENDAR["api/calendar-events.php\nCalendar Events"]
            RATING["api/submit-rating.php\nRating Submission"]
        end
    end

    subgraph INCLUDES["🧩 Shared Components"]
        CONFIG["includes/config.php\nConfiguration"]
        HEADER["includes/header.php\nPage Headers"]
        FOOTER["includes/footer.php\nPage Footers"]
        TRANSLATION["includes/TranslationEngine.php\nI18n Support"]
        CHATCOMP["includes/chatbot-component.php\nChatbot UI"]
        NOTIFCOMP["includes/notifications.php\nNotification System"]
        QRHELPER["includes/qr_code_helper.php\nQR Code Generator"]
    end

    subgraph EXTERNAL["🔌 External Services"]
        GEMINI["Google Gemini API\nAI Chatbot Engine"]
        LANGBLY["Langbly API\nTranslation Service"]
        SMTP["Gmail SMTP\nEmail Service"]
        GCASH["GCash API\nPayment Gateway"]
        PAYPAL["PayPal API\nPayment Gateway"]
        GOOGLEAUTH["Google OAuth\nSocial Login"]
        FB["Facebook OAuth\nSocial Login"]
    end

    subgraph DATA["🗄️ Data Layer"]
        MYSQL[("MySQL Database\nbayawan_hotel")]
        CACHE[("File Cache\n/cache/*.json")]
        LOGS[("Log Files\n/logs/*.log")]
        ASSETS[("Static Assets\n/assets/images")]
    end

    WEB --> APACHE
    MOBILE --> APACHE
    THIRD --> AUTH
    APACHE --> PUBLIC
    APACHE --> AUTH
    APACHE --> USERPORTAL
    APACHE --> STAFFPORTAL
    APACHE --> ADMINPORTAL
    APACHE --> API

    PUBLIC --> INCLUDES
    AUTH --> INCLUDES
    USERPORTAL --> INCLUDES
    STAFFPORTAL --> INCLUDES
    ADMINPORTAL --> INCLUDES
    API --> INCLUDES

    CHATBOT --> GEMINI
    TRANSLATION --> LANGBLY
    CONFIG --> SMTP
    PAYMENT --> GCASH
    PAYMENT --> PAYPAL
    GOOGLE --> GOOGLEAUTH
    FACEBOOK --> FB

    INCLUDES --> DATA
    API --> DATA
    USERPORTAL --> DATA
    STAFFPORTAL --> DATA
    ADMINPORTAL --> DATA
```

---

## 2. Technology Stack

| Layer | Technology | Purpose |
|-------|------------|---------|
| **Web Server** | Apache HTTP Server (XAMPP) | HTTP request handling |
| **Server-side** | PHP 8.x | Application logic |
| **Database** | MySQL 8.x | Data persistence (35 tables) |
| **Frontend** | HTML5, CSS3, JavaScript | UI rendering |
| **CSS Framework** | Custom + FontAwesome | Styling & icons |
| **JS Libraries** | Vanilla JS, Fetch API | Client-side interactivity |
| **Email** | PHPMailer 7.x | SMTP email sending |
| **QR Codes** | Endroid QR Code 5.x | QR generation for check-in |
| **AI/ML** | Google Gemini API | Chatbot intelligence |
| **Translation** | Langbly API | Multi-language support |
| **Payments** | GCash, PayPal APIs | Payment processing |
| **OAuth** | Google, Facebook APIs | Social authentication |

---

## 3. Application Architecture (MVC Pattern)

```mermaid
flowchart LR
    subgraph VIEW["👁️ View Layer"]
        TEMPLATES["Page Templates\n*.php files"]
        HEADER["Header Component\nincludes/header.php"]
        FOOTER["Footer Component\nincludes/footer.php"]
    end

    subgraph CONTROLLER["🎮 Controller Layer"]
        PAGE_CONTROLLERS["Page Controllers\nBusiness Logic"]
        API_CONTROLLERS["API Controllers\n/api/*.php"]
        AUTH_CONTROLLERS["Auth Controllers\n/auth/*.php"]
    end

    subgraph MODEL["📦 Model Layer (Implicit)"]
        DB_FUNCTIONS["Database Functions\nincludes/config.php"]
        HELPERS["Helper Functions\nUtility Methods"]
        CONFIG_DEFS["Configuration\nConstants & Settings"]
    end

    subgraph DATABASE["🗄️ Database Layer"]
        TABLES[("35 Database Tables\nMySQL InnoDB")]
        RELATIONSHIPS["Foreign Key\nRelationships"]
    end

    TEMPLATES --> PAGE_CONTROLLERS
    HEADER --> PAGE_CONTROLLERS
    FOOTER --> PAGE_CONTROLLERS

    PAGE_CONTROLLERS --> DB_FUNCTIONS
    API_CONTROLLERS --> DB_FUNCTIONS
    AUTH_CONTROLLERS --> DB_FUNCTIONS

    DB_FUNCTIONS --> HELPERS
    DB_FUNCTIONS --> CONFIG_DEFS

    DB_FUNCTIONS --> TABLES
    HELPERS --> TABLES
    TABLES --> RELATIONSHIPS
```

---

## 4. Authentication & Authorization Flow

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant WebServer as Apache/PHP
    participant AuthController as /auth/*.php
    participant SessionMgr as Session Manager
    participant DB as MySQL
    participant OAuth as Google/Facebook

    Note over User,OAuth: Local Authentication
    User->>Browser: Enter credentials
    Browser->>WebServer: POST /auth/login.php
    WebServer->>AuthController: Process login
    AuthController->>DB: Validate credentials
    DB-->>AuthController: User data
    AuthController->>SessionMgr: Start session
    SessionMgr-->>AuthController: Session ID
    AuthController-->>Browser: Set session cookie
    Browser-->>User: Redirect to dashboard

    Note over User,OAuth: Social Authentication
    User->>Browser: Click "Sign in with Google"
    Browser->>OAuth: Redirect to OAuth provider
    OAuth-->>Browser: Authorization code
    Browser->>WebServer: POST /auth/google-callback.php
    WebServer->>AuthController: Process OAuth
    AuthController->>OAuth: Get user info
    OAuth-->>AuthController: Profile data
    AuthController->>DB: Find/create user
    AuthController->>SessionMgr: Start session
    AuthController-->>Browser: Set session cookie

    Note over User,OAuth: Role-Based Access Control
    User->>Browser: Access /admin/
    Browser->>WebServer: Request page
    WebServer->>AuthController: Check permissions
    AuthController->>SessionMgr: Verify role
    SessionMgr-->>AuthController: isAdmin = true
    AuthController->>DB: Check staff_permissions
    DB-->>AuthController: Permission granted
    AuthController-->>Browser: Serve admin page
```

---

## 5. Database Architecture

```mermaid
flowchart TB
    subgraph CORE["Core Domain"]
        U[("users\nAuthentication")]
        RC[("room_categories\nRoom Types")]
        R[("rooms\nInventory")]
        B[("bookings\nReservations")]
        P[("payments\nTransactions")]
    end

    subgraph SERVICES["Service Domain"]
        MC[("menu_categories")]
        MI[("menu_items")]
        FO[("food_orders")]
        ES[("event_spaces")]
        EB[("event_bookings")]
        AM[("amenities")]
    end

    subgraph CONTENT["Content Domain"]
        G[("gallery")]
        HS[("homepage_slider")]
        PR[("promotions")]
        FAQ[("faqs")]
    end

    subgraph OPERATIONS["Operations Domain"]
        IC[("inventory_categories")]
        II[("inventory_items")]
        MR[("maintenance_requests")]
        SS[("staff_schedules")]
    end

    subgraph FEEDBACK["Feedback Domain"]
        REV[("reviews")]
        RAT[("ratings")]
        RE[("rating_eligibility")]
    end

    subgraph SYSTEM["System Domain"]
        S[("settings")]
        NS[("notification_settings")]
        N[("notifications")]
        AL[("activity_logs")]
        BL[("booking_logs")]
        US[("user_sessions")]
    end

    subgraph AI["AI Domain"]
        CS[("chat_sessions")]
        CM[("chat_messages")]
        CK[("chatbot_knowledge")]
        CC[("chatbot_context")]
    end

    subgraph TOUR["Virtual Tour Domain"]
        RVT[("room_virtual_tours")]
        VTH[("virtual_tour_hotspots")]
        EVT[("event_virtual_tours")]
        ETH[("event_virtual_tour_hotspots")]
    end

    U --> B
    U --> P
    U --> FO
    U --> EB
    RC --> R
    RC --> B
    R --> B
    B --> P

    MC --> MI
    MI --> FO
    ES --> EB

    IC --> II
    R --> MR
    U --> SS
    U --> MR

    U --> REV
    B --> REV
    B --> RAT
    EB --> RAT
    FO --> RAT

    U --> CS
    CS --> CM
    U --> CC
    U --> N
    U --> NS

    RC --> RVT
    RVT --> VTH
    ES --> EVT
    EVT --> ETH
```

---

## 6. Chatbot Architecture (AI Integration)

```mermaid
flowchart TB
    subgraph USER_INPUT["User Input"]
        CHAT_UI["Chat Interface\nchatbot-component.php"]
    end

    subgraph API_LAYER["API Layer"]
        CHAT_ENDPOINT["/api/chatbot.php\nREST Endpoint"]
    end

    subgraph PROCESSING["Message Processing"]
        INTENT["Intent Detection\nKeyword Matching"]
        CONTEXT["Context Manager\nchatbot_context table"]
        DB_KB["Knowledge Base\nchatbot_knowledge table"]
    end

    subgraph AI_ENGINE["AI Engine"]
        GEMINI_API["Google Gemini API\nFallback for complex queries"]
    end

    subgraph RESPONSE["Response Generation"]
        KB_RESPONSE["Knowledge Base\nResponse"]
        AI_RESPONSE["AI-Generated\nResponse"]
        MSG_STORE["Store Message\nchat_messages table"]
    end

    CHAT_UI -->|AJAX POST| CHAT_ENDPOINT
    CHAT_ENDPOINT --> INTENT
    INTENT --> DB_KB
    INTENT --> CONTEXT
    DB_KB -->|Match found| KB_RESPONSE
    DB_KB -->|No match| GEMINI_API
    GEMINI_API --> AI_RESPONSE
    KB_RESPONSE --> MSG_STORE
    AI_RESPONSE --> MSG_STORE
    MSG_STORE -->|JSON Response| CHAT_UI
```

---

## 7. Payment Processing Architecture

```mermaid
flowchart TB
    subgraph INIT["Payment Initiation"]
        CHECKOUT["Checkout Page\nbooking.php"]
        EVENT_PAY["Event Payment\nevent-payment-process.php"]
        FOOD_PAY["Food Order Payment\nfood-order-payment-process.php"]
    end

    subgraph GATEWAY["Payment Gateway"]
        GCASH["GCash API\nMobile Wallet"]
        PAYPAL["PayPal API\nCard/Wallet"]
        CASH["Cash Payment\nWalk-in/Front Desk"]
    end

    subgraph PROCESSOR["Payment Processor"]
        VALIDATE["Validate Payment"]
        RECORD["Record Transaction\npayments table"]
        UPDATE["Update Booking Status"]
    end

    subgraph CONFIRMATION["Confirmation"]
        RECEIPT["Generate Receipt"]
        EMAIL["Send Email\nPHPMailer"]
        QR["Generate QR Code\nEndroid QR"]
    end

    CHECKOUT -->|Booking| PROCESSOR
    EVENT_PAY -->|Event Booking| PROCESSOR
    FOOD_PAY -->|Food Order| PROCESSOR

    PROCESSOR -->|GCash| GCASH
    PROCESSOR -->|PayPal| PAYPAL
    PROCESSOR -->|Cash| CASH

    GCASH --> VALIDATE
    PAYPAL --> VALIDATE
    CASH --> VALIDATE

    VALIDATE --> RECORD
    RECORD --> UPDATE
    UPDATE --> RECEIPT
    UPDATE --> EMAIL
    UPDATE --> QR
```

---

## 8. Notification System Architecture

```mermaid
flowchart TB
    subgraph TRIGGERS["Notification Triggers"]
        NEW_BOOKING["New Booking"]
        CHECKIN_REM["Check-in Reminder"]
        PAYMENT_CONF["Payment Confirmation"]
        FOOD_READY["Food Order Ready"]
        PROMO["Promotional"]
    end

    subgraph ENGINE["Notification Engine\nincludes/notifications.php"]
        GENERATE["Generate Notification"]
        QUEUE["Add to Queue\nnotifications table"]
    end

    subgraph CHANNELS["Delivery Channels"]
        IN_APP["In-App\nBell Icon / Badge"]
        EMAIL["Email\nPHPMailer + SMTP"]
        POPUP["Browser Popup\nToast Notifications"]
    end

    subgraph USER_PREF["User Preferences\nnotification_settings table"]
        EMAIL_ON["Email Enabled"]
        POPUP_ON["Popup Enabled"]
    end

    NEW_BOOKING --> GENERATE
    CHECKIN_REM --> GENERATE
    PAYMENT_CONF --> GENERATE
    FOOD_READY --> GENERATE
    PROMO --> GENERATE

    GENERATE --> QUEUE
    QUEUE --> USER_PREF

    USER_PREF -->|email_enabled=1| EMAIL
    USER_PREF -->|popup_enabled=1| POPUP
    QUEUE --> IN_APP
```

---

## 9. Multi-language (i18n) Architecture

```mermaid
flowchart LR
    subgraph DETECT["Language Detection"]
        SESSION["Session Storage\n$_SESSION['user_language']"]
        URL_PARAM["URL Parameter\n?lang=tl"]
        DEFAULT["Default: en"]
    end

    subgraph ENGINE["Translation Engine\nincludes/TranslationEngine.php"]
        CACHE["File Cache\n/cache/*.json"]
        LANGBLY["Langbly API\nTranslation Service"]
        FALLBACK["Fallback: English"]
    end

    subgraph UI["UI Layer"]
        PHP_TEMPLATES["PHP Templates\n<?= t('key') ?>"]
        JS_TEMPLATES["JavaScript\ngetTranslation()"]
    end

    SESSION --> ENGINE
    URL_PARAM --> SESSION
    DEFAULT --> ENGINE

    ENGINE -->|Cache hit| CACHE
    ENGINE -->|Cache miss| LANGBLY
    LANGBLY --> CACHE
    ENGINE -->|API fail| FALLBACK

    CACHE --> UI
    LANGBLY --> UI
    FALLBACK --> UI
```

---

## 10. QR Code Integration Architecture

```mermaid
flowchart TB
    subgraph GENERATION["QR Code Generation"]
        BOOKING_QR["Booking QR\nBooking Reference + URL"]
        EVENT_QR["Event QR\nEvent Booking ID"]
        FOOD_QR["Food Order QR\nOrder ID + Status"]
    end

    subgraph SCANNING["QR Code Scanning"]
        STAFF_SCANNER["Staff Scanner\nstaff/staff-qr-scanner.php"]
        CAMERA["Device Camera\nJavaScript getUserMedia"]
        DECODER["QR Decoder\nJavaScript Library"]
    end

    subgraph ACTION["Action Processing"]
        CHECK_IN["Process Check-in\nUpdate booking status"]
        CHECK_OUT["Process Check-out\nCalculate charges"]
        VERIFY_FOOD["Verify Food Order\nMark as delivered"]
    end

    BOOKING_QR -->|Print/Email| USER["Guest"]
    EVENT_QR -->|Print/Email| USER
    FOOD_QR -->|Display| USER

    USER -->|Show QR| STAFF_SCANNER
    STAFF_SCANNER --> CAMERA
    CAMERA --> DECODER
    DECODER -->|Extract ID| ACTION

    ACTION --> CHECK_IN
    ACTION --> CHECK_OUT
    ACTION --> VERIFY_FOOD

    CHECK_IN -->|Update| DB[("Database")]
    CHECK_OUT -->|Update| DB
    VERIFY_FOOD -->|Update| DB
```

---

## 11. Module Dependencies

```mermaid
flowchart TB
    subgraph CORE_MOD["Core Module"]
        CONFIG_MOD["config.php\nDatabase, Constants, Helpers"]
    end

    subgraph DEPENDENT_MOD["Dependent Modules"]
        AUTH_MOD["Authentication\n+ User Management"]
        BOOKING_MOD["Booking System\n+ Payments"]
        FOOD_MOD["Food Ordering\n+ Inventory"]
        EVENT_MOD["Event Booking\n+ Virtual Tours"]
        CHAT_MOD["AI Chatbot\n+ Gemini API"]
        NOTIF_MOD["Notifications\n+ Email"]
        I18N_MOD["Translation\n+ Langbly API"]
    end

    subgraph UI_MOD["UI Components"]
        HEADER_MOD["Header\nNavigation, Notifications"]
        FOOTER_MOD["Footer\nScripts, Analytics"]
        RATING_MOD["Rating System\n+ Reviews"]
    end

    CONFIG_MOD --> AUTH_MOD
    CONFIG_MOD --> BOOKING_MOD
    CONFIG_MOD --> FOOD_MOD
    CONFIG_MOD --> EVENT_MOD
    CONFIG_MOD --> CHAT_MOD
    CONFIG_MOD --> NOTIF_MOD
    CONFIG_MOD --> I18N_MOD

    AUTH_MOD --> HEADER_MOD
    NOTIF_MOD --> HEADER_MOD
    I18N_MOD --> HEADER_MOD

    BOOKING_MOD --> RATING_MOD
    FOOD_MOD --> RATING_MOD
    EVENT_MOD --> RATING_MOD

    AUTH_MOD --> FOOTER_MOD
    CHAT_MOD --> FOOTER_MOD
```

---

## 12. Security Architecture

```mermaid
flowchart TB
    subgraph INPUT["Input Security"]
        SANITIZE["sanitizeInput()\nhtmlspecialchars, strip_tags"]
        PREPARE["Prepared Statements\nPDO Parameter Binding"]
        VALIDATE["Input Validation\nRegex, Type Checking"]
    end

    subgraph SESSION["Session Security"]
        REGEN["Session Regeneration\nAfter login"]
        TIMEOUT["Session Timeout\nAuto-logout"]
        SECURE["Secure Cookies\nHttpOnly, Secure"]
    end

    subgraph ACCESS["Access Control"]
        RBAC["Role-Based Access\nadmin/manager/receptionist/guest"]
        PERM["Page Permissions\nstaff_permissions table"]
        CHECK["Permission Check\ncheckStaffPermission()"]
    end

    subgraph OUTPUT["Output Security"]
        ESCAPE["HTML Escaping\nhtmlspecialchars"]
        CSRF["CSRF Tokens\nForm Protection"]
    end

    REQUEST["HTTP Request"] --> SANITIZE
    SANITIZE --> PREPARE
    PREPARE --> VALIDATE
    VALIDATE --> PROCESS["Process Request"]

    PROCESS --> SESSION
    SESSION --> RBAC
    RBAC --> PERM
    PERM --> CHECK
    CHECK --> RESPONSE["Generate Response"]

    RESPONSE --> ESCAPE
    ESCAPE --> CSRF
    CSRF --> OUTPUT["HTTP Response"]
```

---

## 13. Deployment Architecture

```mermaid
flowchart TB
    subgraph CLIENT["Client Devices"]
        DESKTOP["Desktop Browser\nChrome, Firefox, Edge"]
        MOBILE_DEV["Mobile Browser\nSafari, Chrome Mobile"]
        TABLET["Tablet\niPad, Android"]
    end

    subgraph SERVER["XAMPP Server Stack"]
        APACHE_S["Apache HTTP Server\nPort 80/443"]
        PHP_S["PHP 8.x\nmod_php/FPM"]
        MYSQL_S["MySQL 8.x\nDatabase Server"]
    end

    subgraph FILES["Application Files"]
        PHP_FILES["PHP Application\n/htdocs/bayawanhotel"]
        VENDOR["Composer Dependencies\n/vendor"]
        ASSETS_F["Static Assets\n/assets"]
    end

    subgraph EXTERNAL_S["External Services"]
        GMAIL["Gmail SMTP\nEmail"]
        GCASH_S["GCash API\nPayments"]
        PAYPAL_S["PayPal API\nPayments"]
        GEMINI_S["Google Gemini\nAI Chat"]
        LANG_S["Langbly API\nTranslation"]
    end

    DESKTOP -->|HTTPS| APACHE_S
    MOBILE_DEV -->|HTTPS| APACHE_S
    TABLET -->|HTTPS| APACHE_S

    APACHE_S --> PHP_S
    PHP_S --> MYSQL_S
    PHP_S --> PHP_FILES
    PHP_S --> VENDOR
    APACHE_S --> ASSETS_F

    PHP_S -->|SMTP| GMAIL
    PHP_S -->|REST| GCASH_S
    PHP_S -->|REST| PAYPAL_S
    PHP_S -->|REST| GEMINI_S
    PHP_S -->|REST| LANG_S
```

---

## 14. Directory Structure

```
bayawanhotel/
├── 📄 Root Pages (Public)
│   ├── index.php              # Homepage
│   ├── rooms.php              # Room listings
│   ├── room-details.php       # Individual room
│   ├── booking.php            # Booking process
│   ├── dining.php             # Restaurant menu
│   ├── order-now.php          # Food ordering
│   ├── events.php             # Event spaces
│   ├── about.php              # About/Contact
│   ├── gallery.php            # Photo gallery
│   ├── virtual-tour.php       # 360° room tours
│   └── ...
│
├── 🔐 auth/                   # Authentication
│   ├── login.php
│   ├── register.php
│   ├── google-callback.php
│   └── facebook-callback.php
│
├── 👤 user/                   # User Portal
│   ├── dashboard.php
│   ├── my-bookings.php
│   ├── my-food-orders.php
│   ├── my-event-bookings.php
│   ├── notifications.php
│   └── ...
│
├── 👔 staff/                  # Staff Portal
│   ├── staff-dashboard.php
│   ├── checkin.php
│   ├── checkout.php
│   ├── staff-qr-scanner.php
│   ├── walkin-booking.php
│   └── ...
│
├── ⚙️ admin/                  # Admin Portal (44 files)
│   ├── admin-dashboard.php
│   ├── admin-bookings.php
│   ├── admin-users.php
│   ├── admin-reports.php
│   ├── admin-analytics.php
│   └── ...
│
├── 🔌 api/                    # API Endpoints
│   ├── chatbot.php            # AI chat
│   ├── event-payment-process.php
│   ├── ajax-notifications.php
│   ├── calendar-events.php
│   └── submit-rating.php
│
├── 🧩 includes/               # Shared Components
│   ├── config.php             # Core configuration
│   ├── header.php             # Page header
│   ├── footer.php             # Page footer
│   ├── TranslationEngine.php  # i18n support
│   ├── chatbot-component.php  # Chatbot UI
│   ├── notifications.php      # Notification system
│   └── qr_code_helper.php     # QR generation
│
├── 🗄️ database/               # Database
│   └── database.sql           # Schema & seed data
│
├── 💾 cache/                  # Translation cache
│   └── *.json
│
├── 📦 vendor/                 # Composer deps
│   ├── phpmailer/
│   └── endroid/
│
├── 🖼️ assets/                 # Static assets
│   ├── images/
│   ├── css/
│   └── js/
│
└── 📋 logs/                   # Application logs
    └── *.log
```

---

## 15. Summary Statistics

| Metric | Count |
|--------|-------|
| **Total Files** | 150+ PHP files |
| **Database Tables** | 35 tables |
| **Public Pages** | 15 pages |
| **User Portal Pages** | 21 pages |
| **Staff Portal Pages** | 19 pages |
| **Admin Portal Pages** | 44 pages |
| **API Endpoints** | 6 endpoints |
| **External APIs** | 6 services |
| **User Roles** | 4 roles (guest, receptionist, manager, admin) |
| **Composer Packages** | 2 packages |

---

## 16. Key Features

### Core Hotel Management
- ✅ Room booking with availability calendar
- ✅ Online payment (GCash, PayPal, Credit Card, Cash)
- ✅ Check-in/Check-out with QR codes
- ✅ Room service food ordering
- ✅ Event space booking
- ✅ Housekeeping & maintenance tracking

### Guest Features
- ✅ User registration & social login (Google/Facebook)
- ✅ Multi-language support (English + Translation API)
- ✅ AI-powered chatbot (Google Gemini)
- ✅ 360° virtual tours
- ✅ Rating & review system
- ✅ Real-time notifications

### Staff Features
- ✅ QR code scanner for quick check-in/out
- ✅ Walk-in booking capability
- ✅ Food order management
- ✅ Maintenance request tracking
- ✅ Inventory management

### Admin Features
- ✅ Comprehensive analytics dashboard
- ✅ Staff permission management
- ✅ Dynamic content management (slider, gallery, FAQs)
- ✅ Promotional code system
- ✅ Detailed reports & logs

---

*Generated for Bayawan Bai Hotel Management System*
