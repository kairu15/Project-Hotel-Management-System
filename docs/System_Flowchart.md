# Bayawan Hotel Management System - System Flowchart

This document illustrates the complete system flowchart for the Bayawan Hotel Management System, showing the flow of data and processes across different modules and user roles.

---

## 1. Overall System Flow

```mermaid
flowchart TB
    START([Start]) --> USER_TYPE{User Type?}
    
    USER_TYPE -->|Guest/Visitor| PUBLIC[Public Pages]
    USER_TYPE -->|Registered User| AUTH[Authentication]
    USER_TYPE -->|Staff| STAFF_AUTH[Staff Login]
    USER_TYPE -->|Admin| ADMIN_AUTH[Admin Login]
    
    PUBLIC --> BROWSE[Browse Information]
    BROWSE --> ACTION{Action?}
    
    ACTION -->|View Rooms| VIEW_ROOMS[View Rooms Page]
    ACTION -->|View Events| VIEW_EVENTS[View Events Page]
    ACTION -->|View Menu| VIEW_MENU[View Dining/Menu]
    ACTION -->|Contact| CONTACT[Contact Form - Public]
    ACTION -->|Virtual Tour| VIRTUAL_TOUR[Virtual Tour]
    
    VIEW_ROOMS --> BOOKING_ACTION{Want to Book?}
    VIEW_EVENTS --> EVENT_ACTION{Want to Book Event?}
    VIEW_MENU --> ORDER_ACTION{Want to Order?}
    
    BOOKING_ACTION -->|No| END1([End])
    EVENT_ACTION -->|No| END2([End])
    ORDER_ACTION -->|No| END3([End])
    
    BOOKING_ACTION -->|Yes| LOGIN_CHECK1{Logged In?}
    EVENT_ACTION -->|Yes| EVENT_FLOW[Event Booking Flow]
    ORDER_ACTION -->|Yes| LOGIN_CHECK2{Logged In?}
    
    LOGIN_CHECK1 -->|No| PROMPT_LOGIN1[Show Login Overlay]
    LOGIN_CHECK1 -->|Yes| ROOM_FLOW[Room Booking Flow]
    
    LOGIN_CHECK2 -->|No| PROMPT_LOGIN2[Show Login Overlay]
    LOGIN_CHECK2 -->|Yes| FOOD_FLOW[Food Order Flow]
    
    PROMPT_LOGIN1 --> AUTH_REDIRECT1[Login/Register]
    PROMPT_LOGIN2 --> AUTH_REDIRECT2[Login/Register]
    
    AUTH_REDIRECT1 -->|Success| ROOM_FLOW
    AUTH_REDIRECT2 -->|Success| FOOD_FLOW
    
    AUTH -->|Success| USER_PORTAL[User Portal]
    AUTH -->|Failure| RETRY[Retry Login]
    RETRY --> AUTH
    
    STAFF_AUTH -->|Success| STAFF_PORTAL[Staff Portal]
    STAFF_AUTH -->|Failure| RETRY
    
    ADMIN_AUTH -->|Success| ADMIN_PORTAL[Admin Portal]
    ADMIN_AUTH -->|Failure| RETRY
    
    ROOM_FLOW --> BOOKING_CONFIRM[Booking Confirmation]
    EVENT_FLOW --> EVENT_CONFIRM[Event Confirmation]
    FOOD_FLOW --> FOOD_CONFIRM[Order Confirmation]
    
    BOOKING_CONFIRM --> PAYMENT[Payment Processing]
    EVENT_CONFIRM --> PAYMENT
    FOOD_CONFIRM --> PAYMENT
    
    PAYMENT -->|Success| SUCCESS[Success Page]
    PAYMENT -->|Failure| RETRY_PAYMENT[Retry Payment]
    RETRY_PAYMENT --> PAYMENT
    
    SUCCESS --> END([End])
    CONTACT --> END
    VIRTUAL_TOUR --> END
    END1 --> END
    END2 --> END
    END3 --> END
```

---

## 2. Room Booking Flowchart

```mermaid
flowchart TD
    START([Start]) --> VIEW_ROOMS[View Rooms Page]
    VIEW_ROOMS --> SELECT_ROOM[Select Room Category]
    SELECT_ROOM --> CHECK_AVAILABILITY[Check Availability]
    
    CHECK_AVAILABILITY --> AVAILABLE{Available?}
    AVAILABLE -->|Yes| SELECT_DATES[Select Check-in/Check-out Dates]
    AVAILABLE -->|No| SUGGEST_ALT[Suggest Alternatives]
    SUGGEST_ALT --> SELECT_ROOM
    
    SELECT_DATES --> CALCULATE_PRICE[Calculate Total Price]
    CALCULATE_PRICE --> ENTER_DETAILS[Enter Guest Details]
    ENTER_DETAILS --> AUTH_CHECK{Logged In?}
    
    AUTH_CHECK -->|No| LOGIN[Login/Register]
    LOGIN --> ENTER_DETAILS
    AUTH_CHECK -->|Yes| REVIEW_BOOKING[Review Booking]
    
    REVIEW_BOOKING --> CONFIRM_DETAILS{Confirm?}
    CONFIRM_DETAILS -->|Yes| PROCESS_PAYMENT[Process Payment]
    CONFIRM_DETAILS -->|No| EDIT_BOOKING[Edit Booking]
    EDIT_BOOKING --> SELECT_DATES
    
    PROCESS_PAYMENT --> PAYMENT_STATUS{Payment Status?}
    PAYMENT_STATUS -->|Success| GENERATE_QR[Generate QR Code]
    PAYMENT_STATUS -->|Pending| PENDING_STATUS[Set Pending Status]
    PAYMENT_STATUS -->|Failed| RETRY_PAYMENT[Retry Payment]
    RETRY_PAYMENT --> PROCESS_PAYMENT
    
    GENERATE_QR --> SEND_CONFIRMATION[Send Email Confirmation]
    PENDING_STATUS --> SEND_PENDING[Send Pending Notification]
    
    SEND_CONFIRMATION --> BOOKING_COMPLETE[Booking Complete]
    SEND_PENDING --> BOOKING_COMPLETE
    
    BOOKING_COMPLETE --> STAFF_NOTIFY[Notify Staff]
    STAFF_NOTIFY --> ADD_TO_CALENDAR[Add to Calendar]
    ADD_TO_CALENDAR --> END([End])
```

---

## 3. Check-in Process Flowchart

```mermaid
flowchart TD
    START([Start]) --> ARRIVAL[Guest Arrival]
    ARRIVAL --> VERIFY_BOOKING{Has Booking?}
    
    VERIFY_BOOKING -->|Yes| SEARCH_BOOKING[Search Booking]
    VERIFY_BOOKING -->|No| WALKIN_PROCESS[Walk-in Process]
    
    SEARCH_BOOKING --> SCAN_QR[Scan QR Code] 
    SEARCH_BOOKING --> MANUAL_SEARCH[Manual Search by Name/ID]
    
    SCAN_QR --> VALID_BOOKING{Valid Booking?}
    MANUAL_SEARCH --> VALID_BOOKING
    
    VALID_BOOKING -->|Yes| CHECK_STATUS{Booking Status?}
    VALID_BOOKING -->|No| ERROR_INVALID[Show Error]
    ERROR_INVALID --> SEARCH_BOOKING
    
    CHECK_STATUS -->|Confirmed| VERIFY_ID[Verify Guest ID]
    CHECK_STATUS -->|Pending| CHECK_PAYMENT{Payment Complete?}
    CHECK_STATUS -->|Checked-in| ALREADY_IN[Already Checked In]
    
    CHECK_PAYMENT -->|Yes| VERIFY_ID
    CHECK_PAYMENT -->|No| REQUEST_PAYMENT[Request Payment]
    REQUEST_PAYMENT --> VERIFY_ID
    
    VERIFY_ID --> ID_VALID{ID Valid?}
    ID_VALID -->|Yes| ASSIGN_ROOM[Assign Room Number]
    ID_VALID -->|No| REQUEST_ID[Request Valid ID]
    REQUEST_ID --> VERIFY_ID
    
    ASSIGN_ROOM --> UPDATE_STATUS[Update Booking Status to Checked-in]
    UPDATE_STATUS --> GENERATE_KEY[Generate Room Key/Access]
    GENERATE_KEY --> WELCOME_GUEST[Welcome Guest]
    WELCOME_GUEST --> END([End])
    
    WALKIN_PROCESS --> CHECK_AVAIL[Check Room Availability]
    CHECK_AVAIL --> ROOM_AVAIL{Room Available?}
    ROOM_AVAIL -->|Yes| CREATE_BOOKING[Create Walk-in Booking]
    ROOM_AVAIL -->|No| SUGGEST_WAIT[Suggest Wait/Alternative]
    CREATE_BOOKING --> PROCESS_PAYMENT
    
    ALREADY_IN --> SHOW_ROOM_INFO[Show Room Info]
    SHOW_ROOM_INFO --> END
```

---

## 4. Check-out Process Flowchart

```mermaid
flowchart TD
    START([Start]) --> CHECKOUT_REQUEST[Check-out Request]
    CHECKOUT_REQUEST --> LOCATE_BOOKING[Locate Booking]
    
    LOCATE_BOOKING --> SCAN_QR[Scan QR Code]
    LOCATE_BOOKING --> ROOM_NUMBER[Enter Room Number]
    LOCATE_BOOKING --> GUEST_NAME[Search Guest Name]
    
    SCAN_QR --> VERIFY_BOOKING{Valid Booking?}
    ROOM_NUMBER --> VERIFY_BOOKING
    GUEST_NAME --> VERIFY_BOOKING
    
    VERIFY_BOOKING -->|Yes| CHECK_STATUS{Status?}
    VERIFY_BOOKING -->|No| ERROR_INVALID[Invalid Booking]
    ERROR_INVALID --> LOCATE_BOOKING
    
    CHECK_STATUS -->|Checked-in| REVIEW_CHARGES[Review Room Charges]
    CHECK_STATUS -->|Already Out| ALREADY_OUT[Already Checked Out]
    CHECK_STATUS -->|Other| HANDLE_OTHER[Handle Special Case]
    
    REVIEW_CHARGES --> ADDITIONAL_CHARGES[Check Additional Charges]
    ADDITIONAL_CHARGES --> MINIBAR[Minibar Usage]
    ADDITIONAL_CHARGES --> DAMAGES[Room Damages]
    ADDITIONAL_CHARGES --> LATE_CHECKOUT[Late Check-out Fee]
    ADDITIONAL_CHARGES --> SERVICES[Extra Services]
    
    MINIBAR --> CALCULATE_TOTAL[Calculate Final Total]
    DAMAGES --> CALCULATE_TOTAL
    LATE_CHECKOUT --> CALCULATE_TOTAL
    SERVICES --> CALCULATE_TOTAL
    
    CALCULATE_TOTAL --> PRESENT_BILL[Present Final Bill]
    PRESENT_BILL --> PAYMENT_DUE{Payment Due?}
    
    PAYMENT_DUE -->|Yes| PROCESS_PAYMENT[Process Payment]
    PAYMENT_DUE -->|No| FINALIZE_CHECKOUT[Finalize Check-out]
    PROCESS_PAYMENT --> PAYMENT_SUCCESS{Success?}
    
    PAYMENT_SUCCESS -->|Yes| FINALIZE_CHECKOUT
    PAYMENT_SUCCESS -->|No| RETRY_PAYMENT[Retry Payment]
    RETRY_PAYMENT --> PROCESS_PAYMENT
    
    FINALIZE_CHECKOUT --> UPDATE_STATUS[Update Status to Checked-out]
    UPDATE_STATUS --> ROOM_INSPECTION[Schedule Room Inspection]
    ROOM_INSPECTION --> GENERATE_RECEIPT[Generate Receipt]
    GENERATE_RECEIPT --> THANK_GUEST[Thank Guest]
    THANK_GUEST --> END([End])
    
    ALREADY_OUT --> SHOW_RECEIPT[Show Receipt]
    SHOW_RECEIPT --> END
    HANDLE_OTHER --> END
```

---

## 5. Food Order Process Flowchart

```mermaid
flowchart TD
    START([Start]) --> VIEW_MENU[View Dining/Menu Page]
    VIEW_MENU --> SELECT_ITEMS[Select Food Items]
    
    SELECT_ITEMS --> CUSTOMIZE{Customize?}
    CUSTOMIZE -->|Yes| ADD_NOTES[Add Special Instructions]
    CUSTOMIZE -->|No| ADD_TO_CART[Add to Cart]
    ADD_NOTES --> ADD_TO_CART
    
    ADD_TO_CART --> CONTINUE{Continue?}
    CONTINUE -->|Yes| SELECT_ITEMS
    CONTINUE -->|No| REVIEW_CART[Review Cart]
    
    REVIEW_CART --> DELIVERY_TYPE{Delivery Type?}
    DELIVERY_TYPE -->|Room Service| ENTER_ROOM[Enter Room Number]
    DELIVERY_TYPE -->|Pickup| SELECT_TIME[Select Pickup Time]
    DELIVERY_TYPE -->|Dine-in| ENTER_TABLE[Enter Table Number]
    
    ENTER_ROOM --> VERIFY_GUEST{Valid Guest?}
    VERIFY_GUEST -->|Yes| CALCULATE_TOTAL[Calculate Total]
    VERIFY_GUEST -->|No| REQUEST_VALID[Request Valid Info]
    REQUEST_VALID --> ENTER_ROOM
    
    SELECT_TIME --> CALCULATE_TOTAL
    ENTER_TABLE --> CALCULATE_TOTAL
    
    CALCULATE_TOTAL --> REVIEW_ORDER[Review Order Details]
    REVIEW_ORDER --> CONFIRM{Confirm?}
    CONFIRM -->|Yes| PROCESS_PAYMENT[Process Payment]
    CONFIRM -->|No| EDIT_ORDER[Edit Order]
    EDIT_ORDER --> SELECT_ITEMS
    
    PROCESS_PAYMENT --> PAYMENT_STATUS{Status?}
    PAYMENT_STATUS -->|Success| SEND_KITCHEN[Send to Kitchen]
    PAYMENT_STATUS -->|Failed| RETRY[Retry Payment]
    RETRY --> PROCESS_PAYMENT
    
    SEND_KITCHEN --> NOTIFY_KITCHEN[Notify Kitchen Staff]
    NOTIFY_KITCHEN --> ESTIMATE_TIME[Calculate Prep Time]
    ESTIMATE_TIME --> CONFIRM_ORDER[Confirm Order]
    CONFIRM_ORDER --> SEND_RECEIPT[Send Receipt]
    SEND_RECEIPT --> TRACK_STATUS[Enable Order Tracking]
    TRACK_STATUS --> END([End])
```

---

## 6. Event Booking Flowchart

```mermaid
flowchart TD
    START([Start]) --> VIEW_EVENTS[View Event Spaces]
    VIEW_EVENTS --> SELECT_VENUE[Select Event Venue]
    SELECT_VENUE --> CHECK_AVAILABILITY[Check Date Availability]
    
    CHECK_AVAILABILITY --> AVAILABLE{Available?}
    AVAILABLE -->|Yes| ENTER_EVENT_DETAILS[Enter Event Details]
    AVAILABLE -->|No| SUGGEST_ALTERNATIVES[Suggest Alternatives]
    SUGGEST_ALTERNATIVES --> SELECT_VENUE
    
    ENTER_EVENT_DETAILS --> EVENT_TYPE[Select Event Type]
    EVENT_TYPE --> GUEST_COUNT[Enter Expected Guests]
    GUEST_COUNT --> EVENT_DATE[Select Event Date]
    EVENT_DATE --> DURATION[Select Duration]
    
    DURATION --> CATERING{Catering Required?}
    CATERING -->|Yes| SELECT_MENU[Select Catering Menu]
    CATERING -->|No| EQUIPMENT_NEEDS[Equipment Needs]
    
    SELECT_MENU --> EQUIPMENT_NEEDS
    EQUIPMENT_NEEDS --> AUDIO_VISUAL[Audio/Visual Equipment]
    EQUIPMENT_NEEDS --> DECORATION[Decoration Requirements]
    EQUIPMENT_NEEDS --> SETUP[Setup Preferences]
    
    AUDIO_VISUAL --> CALCULATE_QUOTE[Calculate Quote]
    DECORATION --> CALCULATE_QUOTE
    SETUP --> CALCULATE_QUOTE
    
    CALCULATE_QUOTE --> REVIEW_BOOKING[Review Event Booking]
    REVIEW_BOOKING --> AUTH_CHECK{Logged In?}
    AUTH_CHECK -->|No| LOGIN[Login/Register]
    LOGIN --> REVIEW_BOOKING
    AUTH_CHECK -->|Yes| CONFIRM{Confirm?}
    
    CONFIRM -->|Yes| DEPOSIT[Process Deposit Payment]
    CONFIRM -->|No| EDIT_BOOKING[Edit Details]
    EDIT_BOOKING --> ENTER_EVENT_DETAILS
    
    DEPOSIT --> PAYMENT_STATUS{Payment Status?}
    PAYMENT_STATUS -->|Success| GENERATE_CONTRACT[Generate Contract]
    PAYMENT_STATUS -->|Pending| HOLD_DATES[Hold Dates Pending Payment]
    PAYMENT_STATUS -->|Failed| RETRY_PAYMENT[Retry Payment]
    RETRY_PAYMENT --> DEPOSIT
    
    GENERATE_CONTRACT --> SEND_CONFIRMATION[Send Confirmation]
    HOLD_DATES --> SEND_REMINDER[Send Payment Reminder]
    
    SEND_CONFIRMATION --> ADMIN_REVIEW[Admin Review]
    SEND_REMINDER --> END_PENDING[Pending Status]
    END_PENDING --> END([End])
    
    ADMIN_REVIEW --> APPROVED{Approved?}
    APPROVED -->|Yes| FINAL_CONFIRM[Final Confirmation]
    APPROVED -->|No| REQUEST_CHANGES[Request Changes]
    REQUEST_CHANGES --> ENTER_EVENT_DETAILS
    
    FINAL_CONFIRM --> ADD_CALENDAR[Add to Event Calendar]
    ADD_CALENDAR --> ASSIGN_COORDINATOR[Assign Event Coordinator]
    ASSIGN_COORDINATOR --> END
```

---

## 7. User Registration & Authentication Flowchart

```mermaid
flowchart TD
    START([Start]) --> ACCESS_LOGIN[Access Login Page]
    ACCESS_LOGIN --> AUTH_METHOD{Authentication Method?}
    
    AUTH_METHOD -->|Local| ENTER_CREDENTIALS[Enter Email/Password]
    AUTH_METHOD -->|Google| GOOGLE_OAUTH[Google OAuth]
    AUTH_METHOD -->|Facebook| FACEBOOK_OAUTH[Facebook OAuth]
    AUTH_METHOD -->|Register| REGISTRATION[Registration Form]
    
    ENTER_CREDENTIALS --> VALIDATE_CREDENTIALS{Valid?}
    VALIDATE_CREDENTIALS -->|Yes| CHECK_ROLE[Check User Role]
    VALIDATE_CREDENTIALS -->|No| SHOW_ERROR[Show Error Message]
    SHOW_ERROR --> RETRY_LOGIN[Retry Login]
    RETRY_LOGIN --> ENTER_CREDENTIALS
    
    GOOGLE_OAUTH --> OAUTH_CALLBACK[OAuth Callback]
    FACEBOOK_OAUTH --> OAUTH_CALLBACK
    OAUTH_CALLBACK --> ACCOUNT_EXISTS{Account Exists?}
    
    ACCOUNT_EXISTS -->|Yes| LINK_ACCOUNT[Link OAuth to Account]
    ACCOUNT_EXISTS -->|No| CREATE_OAUTH_ACCOUNT[Create New Account]
    LINK_ACCOUNT --> CHECK_ROLE
    CREATE_OAUTH_ACCOUNT --> SEND_WELCOME[Send Welcome Email]
    SEND_WELCOME --> REDIRECT_DASHBOARD[Redirect to Dashboard]
    
    REGISTRATION --> ENTER_DETAILS[Enter Personal Details]
    ENTER_DETAILS --> VALIDATE_EMAIL{Email Valid?}
    VALIDATE_EMAIL -->|No| REQUEST_VALID_EMAIL[Request Valid Email]
    REQUEST_VALID_EMAIL --> ENTER_DETAILS
    VALIDATE_EMAIL -->|Yes| CHECK_EMAIL_UNIQUE{Email Unique?}
    
    CHECK_EMAIL_UNIQUE -->|No| EMAIL_EXISTS[Email Already Exists]
    EMAIL_EXISTS --> LOGIN_EXISTING[Login with Existing]
    CHECK_EMAIL_UNIQUE -->|Yes| ENTER_PASSWORD[Enter Password]
    
    ENTER_PASSWORD --> VALIDATE_PASSWORD{Strong Password?}
    VALIDATE_PASSWORD -->|No| PASSWORD_REQUIREMENTS[Show Requirements]
    PASSWORD_REQUIREMENTS --> ENTER_PASSWORD
    VALIDATE_PASSWORD -->|Yes| CONFIRM_PASSWORD[Confirm Password]
    
    CONFIRM_PASSWORD --> PASSWORD_MATCH{Match?}
    PASSWORD_MATCH -->|No| MISMATCH_ERROR[Show Mismatch Error]
    MISMATCH_ERROR --> CONFIRM_PASSWORD
    PASSWORD_MATCH -->|Yes| ACCEPT_TERMS[Accept Terms]
    
    ACCEPT_TERMS --> TERMS_ACCEPTED{Accepted?}
    TERMS_ACCEPTED -->|No| REQUIRED[Terms Required]
    REQUIRED --> ACCEPT_TERMS
    TERMS_ACCEPTED -->|Yes| CREATE_ACCOUNT[Create Account]
    
    CREATE_ACCOUNT --> SEND_VERIFICATION[Send Verification Email]
    SEND_VERIFICATION --> SHOW_SUCCESS[Show Success Message]
    SHOW_SUCCESS --> VERIFY_EMAIL{Email Verified?}
    VERIFY_EMAIL -->|No| RESEND_OPTION[Resend/Change Email]
    RESEND_OPTION --> VERIFY_EMAIL
    VERIFY_EMAIL -->|Yes| COMPLETE_REGISTRATION[Registration Complete]
    
    CHECK_ROLE --> ROLE_TYPE{User Role?}
    ROLE_TYPE -->|Guest| USER_DASHBOARD[User Dashboard]
    ROLE_TYPE -->|Staff| STAFF_DASHBOARD[Staff Dashboard]
    ROLE_TYPE -->|Admin| ADMIN_DASHBOARD[Admin Dashboard]
    
    COMPLETE_REGISTRATION --> REDIRECT_DASHBOARD
    REDIRECT_DASHBOARD --> END([End])
    USER_DASHBOARD --> END
    STAFF_DASHBOARD --> END
    ADMIN_DASHBOARD --> END
```

---

## 8. Payment Processing Flowchart

```mermaid
flowchart TD
    START([Start]) --> INITIATE_PAYMENT[Initiate Payment]
    INITIATE_PAYMENT --> SELECT_METHOD[Select Payment Method]
    
    SELECT_METHOD --> METHOD_TYPE{Payment Method?}
    METHOD_TYPE -->|GCash| GCASH_FLOW[GCash Payment]
    METHOD_TYPE -->|PayPal| PAYPAL_FLOW[PayPal Payment]
    METHOD_TYPE -->|Credit Card| CARD_FLOW[Credit Card Payment]
    METHOD_TYPE -->|Cash| CASH_FLOW[Cash Payment]
    
    GCASH_FLOW --> REDIRECT_GCASH[Redirect to GCash]
    PAYPAL_FLOW --> REDIRECT_PAYPAL[Redirect to PayPal]
    CARD_FLOW --> ENTER_CARD[Enter Card Details]
    CASH_FLOW --> RECORD_CASH[Record Cash Payment]
    
    REDIRECT_GCASH --> GCASH_AUTH[GCash Authentication]
    REDIRECT_PAYPAL --> PAYPAL_AUTH[PayPal Authentication]
    ENTER_CARD --> VALIDATE_CARD{Valid Card?}
    
    VALIDATE_CARD -->|No| INVALID_CARD[Invalid Card Error]
    INVALID_CARD --> ENTER_CARD
    VALIDATE_CARD -->|Yes| PROCESS_CARD[Process Card Payment]
    
    GCASH_AUTH --> GCASH_CONFIRM[Confirm GCash Payment]
    PAYPAL_AUTH --> PAYPAL_CONFIRM[Confirm PayPal Payment]
    PROCESS_CARD --> BANK_RESPONSE[Bank Response]
    RECORD_CASH --> CASH_RECEIPT[Generate Cash Receipt]
    
    GCASH_CONFIRM --> PAYMENT_RESPONSE{Response Status?}
    PAYPAL_CONFIRM --> PAYMENT_RESPONSE
    BANK_RESPONSE --> PAYMENT_RESPONSE
    CASH_RECEIPT --> PAYMENT_RESPONSE
    
    PAYMENT_RESPONSE -->|Success| UPDATE_RECORD[Update Payment Record]
    PAYMENT_RESPONSE -->|Pending| PENDING_STATUS[Set Pending Status]
    PAYMENT_RESPONSE -->|Failed| PAYMENT_FAILED[Payment Failed]
    
    UPDATE_RECORD --> UPDATE_BOOKING[Update Booking Status]
    PENDING_STATUS --> SEND_PENDING_NOTIF[Send Pending Notification]
    PAYMENT_FAILED --> RETRY_OPTION{Retry?}
    
    RETRY_OPTION -->|Yes| SELECT_METHOD
    RETRY_OPTION -->|No| CANCEL_TRANSACTION[Cancel Transaction]
    
    UPDATE_BOOKING --> GENERATE_RECEIPT[Generate Receipt]
    SEND_PENDING_NOTIF --> END_PENDING[End - Pending]
    CANCEL_TRANSACTION --> UPDATE_CANCELLED[Update as Cancelled]
    
    GENERATE_RECEIPT --> SEND_CONFIRMATION[Send Confirmation Email]
    UPDATE_CANCELLED --> SEND_CANCELLATION[Send Cancellation Notice]
    
    SEND_CONFIRMATION --> LOG_TRANSACTION[Log Transaction]
    SEND_CANCELLATION --> LOG_TRANSACTION
    END_PENDING --> LOG_TRANSACTION
    
    LOG_TRANSACTION --> END([End])
```

---

## 9. Admin User Management Flowchart

```mermaid
flowchart TD
    START([Start]) --> ACCESS_USERS[Access User Management]
    ACCESS_USERS --> LIST_USERS[List All Users]
    
    LIST_USERS --> ACTION{Select Action}
    ACTION -->|View| VIEW_USER[View User Details]
    ACTION -->|Edit| EDIT_USER[Edit User]
    ACTION -->|Delete| DELETE_USER[Delete User]
    ACTION -->|Add| ADD_USER[Add New User]
    ACTION -->|Search| SEARCH_USER[Search Users]
    
    SEARCH_USER --> FILTER_RESULTS[Apply Filters]
    FILTER_RESULTS --> DISPLAY_RESULTS[Display Results]
    DISPLAY_RESULTS --> SELECT_USER[Select User]
    SELECT_USER --> VIEW_USER
    
    VIEW_USER --> USER_ACTIONS{User Actions?}
    USER_ACTIONS -->|Edit| EDIT_USER
    USER_ACTIONS -->|Deactivate| DEACTIVATE[Deactivate Account]
    USER_ACTIONS -->|Reset Password| RESET_PASSWORD[Reset Password]
    USER_ACTIONS -->|View Bookings| VIEW_BOOKINGS[View User Bookings]
    USER_ACTIONS -->|Back| LIST_USERS
    
    EDIT_USER --> EDIT_DETAILS[Edit User Details]
    EDIT_DETAILS --> VALIDATE_CHANGES{Valid?}
    VALIDATE_CHANGES -->|No| SHOW_ERRORS[Show Validation Errors]
    SHOW_ERRORS --> EDIT_DETAILS
    VALIDATE_CHANGES -->|Yes| SAVE_CHANGES[Save Changes]
    SAVE_CHANGES --> LOG_CHANGES[Log Changes]
    LOG_CHANGES --> NOTIFY_USER[Notify User of Changes]
    NOTIFY_USER --> LIST_USERS
    
    ADD_USER --> ENTER_NEW_DETAILS[Enter New User Details]
    ENTER_NEW_DETAILS --> CHECK_UNIQUE{Email Unique?}
    CHECK_UNIQUE -->|No| EMAIL_TAKEN[Email Already Taken]
    EMAIL_TAKEN --> ENTER_NEW_DETAILS
    CHECK_UNIQUE -->|Yes| SET_ROLE[Assign Role]
    SET_ROLE --> SET_PERMISSIONS[Set Permissions]
    SET_PERMISSIONS --> CREATE_ACCOUNT[Create Account]
    CREATE_ACCOUNT --> SEND_CREDENTIALS[Send Credentials]
    SEND_CREDENTIALS --> LIST_USERS
    
    DEACTIVATE --> CONFIRM_DEACTIVATE{Confirm?}
    CONFIRM_DEACTIVATE -->|Yes| DISABLE_ACCOUNT[Disable Account]
    CONFIRM_DEACTIVATE -->|No| VIEW_USER
    DISABLE_ACCOUNT --> LOG_DEACTIVATION[Log Deactivation]
    LOG_DEACTIVATION --> LIST_USERS
    
    RESET_PASSWORD --> GENERATE_TEMP[Generate Temp Password]
    GENERATE_TEMP --> SEND_RESET[Send Reset Email]
    SEND_RESET --> FORCE_CHANGE[Require Password Change]
    FORCE_CHANGE --> LOG_RESET[Log Password Reset]
    LOG_RESET --> VIEW_USER
    
    DELETE_USER --> CONFIRM_DELETE{Confirm Delete?}
    CONFIRM_DELETE -->|Yes| CHECK_DEPENDENCIES{Has Dependencies?}
    CONFIRM_DELETE -->|No| LIST_USERS
    
    CHECK_DEPENDENCIES -->|Yes| SHOW_DEPENDENCIES[Show Dependencies]
    SHOW_DEPENDENCIES --> TRANSFER_OR_DELETE[Transfer or Delete Data]
    CHECK_DEPENDENCIES -->|No| SOFT_DELETE[Soft Delete Account]
    
    TRANSFER_OR_DELETE --> SOFT_DELETE
    SOFT_DELETE --> LOG_DELETION[Log Deletion]
    LOG_DELETION --> LIST_USERS
    
    VIEW_BOOKINGS --> DISPLAY_BOOKINGS[Display User Bookings]
    DISPLAY_BOOKINGS --> BOOKING_ACTIONS{Booking Actions?}
    BOOKING_ACTIONS -->|View Details| BOOKING_DETAILS[View Booking Details]
    BOOKING_ACTIONS -->|Cancel| CANCEL_BOOKING[Cancel Booking]
    BOOKING_ACTIONS -->|Modify| MODIFY_BOOKING[Modify Booking]
    BOOKING_ACTIONS -->|Back| VIEW_USER
    
    BOOKING_DETAILS --> DISPLAY_BOOKINGS
    CANCEL_BOOKING --> CONFIRM_CANCEL{Confirm?}
    CONFIRM_CANCEL -->|Yes| PROCESS_CANCEL[Process Cancellation]
    CONFIRM_CANCEL -->|No| DISPLAY_BOOKINGS
    PROCESS_CANCEL --> DISPLAY_BOOKINGS
    
    MODIFY_BOOKING --> EDIT_BOOKING[Edit Booking Details]
    EDIT_BOOKING --> SAVE_BOOKING[Save Changes]
    SAVE_BOOKING --> DISPLAY_BOOKINGS
```

---

## 10. Notification System Flowchart

```mermaid
flowchart TD
    START([Event Triggered]) --> DETERMINE_TYPE{Notification Type?}
    
    DETERMINE_TYPE -->|Booking| BOOKING_NOTIF[Booking Notification]
    DETERMINE_TYPE -->|Payment| PAYMENT_NOTIF[Payment Notification]
    DETERMINE_TYPE -->|System| SYSTEM_NOTIF[System Notification]
    DETERMINE_TYPE -->|Marketing| MARKETING_NOTIF[Marketing Notification]
    
    BOOKING_NOTIF --> CHECK_PREFERENCES{User Preferences?}
    PAYMENT_NOTIF --> CHECK_PREFERENCES
    SYSTEM_NOTIF --> CHECK_PREFERENCES
    MARKETING_NOTIF --> CHECK_OPTIN{User Opted In?}
    
    CHECK_OPTIN -->|No| DISCARD[Discard Notification]
    CHECK_OPTIN -->|Yes| CHECK_PREFERENCES
    
    CHECK_PREFERENCES --> EMAIL_ENABLED{Email Enabled?}
    CHECK_PREFERENCES --> SMS_ENABLED{SMS Enabled?}
    CHECK_PREFERENCES --> PUSH_ENABLED{Push Enabled?}
    CHECK_PREFERENCES --> INAPP_ENABLED{In-App Enabled?}
    
    EMAIL_ENABLED -->|Yes| PREPARE_EMAIL[Prepare Email]
    SMS_ENABLED -->|Yes| PREPARE_SMS[Prepare SMS]
    PUSH_ENABLED -->|Yes| PREPARE_PUSH[Prepare Push]
    INAPP_ENABLED -->|Yes| PREPARE_INAPP[Prepare In-App]
    
    PREPARE_EMAIL --> QUEUE_EMAIL[Queue Email]
    PREPARE_SMS --> QUEUE_SMS[Queue SMS]
    PREPARE_PUSH --> QUEUE_PUSH[Queue Push]
    PREPARE_INAPP --> QUEUE_INAPP[Queue In-App]
    
    QUEUE_EMAIL --> SEND_EMAIL[Send via SMTP]
    QUEUE_SMS --> SEND_SMS[Send via SMS Gateway]
    QUEUE_PUSH --> SEND_PUSH[Send Push Notification]
    QUEUE_INAPP --> STORE_INAPP[Store In-App Notification]
    
    SEND_EMAIL --> EMAIL_SUCCESS{Success?}
    SEND_SMS --> SMS_SUCCESS{Success?}
    SEND_PUSH --> PUSH_SUCCESS{Success?}
    STORE_INAPP --> INAPP_SUCCESS[Stored Successfully]
    
    EMAIL_SUCCESS -->|Yes| LOG_EMAIL[Log Success]
    EMAIL_SUCCESS -->|No| RETRY_EMAIL{Retry?}
    SMS_SUCCESS -->|Yes| LOG_SMS[Log Success]
    SMS_SUCCESS -->|No| RETRY_SMS{Retry?}
    PUSH_SUCCESS -->|Yes| LOG_PUSH[Log Success]
    PUSH_SUCCESS -->|No| RETRY_PUSH{Retry?}
    
    RETRY_EMAIL -->|Yes| QUEUE_EMAIL
    RETRY_EMAIL -->|No| LOG_EMAIL_FAIL[Log Failure]
    RETRY_SMS -->|Yes| QUEUE_SMS
    RETRY_SMS -->|No| LOG_SMS_FAIL[Log Failure]
    RETRY_PUSH -->|Yes| QUEUE_PUSH
    RETRY_PUSH -->|No| LOG_PUSH_FAIL[Log Failure]
    
    LOG_EMAIL --> UPDATE_STATUS[Update Notification Status]
    LOG_SMS --> UPDATE_STATUS
    LOG_PUSH --> UPDATE_STATUS
    INAPP_SUCCESS --> UPDATE_STATUS
    LOG_EMAIL_FAIL --> UPDATE_STATUS_FAIL[Update Failed Status]
    LOG_SMS_FAIL --> UPDATE_STATUS_FAIL
    LOG_PUSH_FAIL --> UPDATE_STATUS_FAIL
    
    UPDATE_STATUS --> CLEANUP[Cleanup Queue]
    UPDATE_STATUS_FAIL --> CLEANUP
    DISCARD --> CLEANUP
    
    CLEANUP --> END([End])
```

---

## 11. Chatbot Interaction Flowchart

```mermaid
flowchart TD
    START([User Message]) --> RECEIVE_MESSAGE[Receive Message]
    RECEIVE_MESSAGE --> DETECT_LANGUAGE[Detect Language]
    DETECT_LANGUAGE --> TRANSLATE_INPUT[Translate to English if needed]
    
    TRANSLATE_INPUT --> INTENT_CLASSIFICATION{Classify Intent}
    INTENT_CLASSIFICATION -->|Greeting| GREETING[Generate Greeting]
    INTENT_CLASSIFICATION -->|Booking Inquiry| BOOKING_INQUIRY[Booking Inquiry Handler]
    INTENT_CLASSIFICATION -->|Room Info| ROOM_INFO[Room Information]
    INTENT_CLASSIFICATION -->|Pricing| PRICING[Pricing Information]
    INTENT_CLASSIFICATION -->|Amenities| AMENITIES[Amenities Information]
    INTENT_CLASSIFICATION -->|Location| LOCATION[Location/Directions]
    INTENT_CLASSIFICATION -->|Dining| DINING[Dining Information]
    INTENT_CLASSIFICATION -->|Events| EVENTS[Event Information]
    INTENT_CLASSIFICATION -->|Support| SUPPORT[Support Request]
    INTENT_CLASSIFICATION -->|FAQ| FAQ[FAQ Handler]
    INTENT_CLASSIFICATION -->|Unknown| UNKNOWN[Unknown Intent Handler]
    
    GREETING --> GENERATE_RESPONSE[Generate Response]
    BOOKING_INQUIRY --> CHECK_AVAILABILITY_API[Check Availability API]
    ROOM_INFO --> FETCH_ROOM_DATA[Fetch Room Data]
    PRICING --> FETCH_PRICING_DATA[Fetch Pricing Data]
    AMENITIES --> FETCH_AMENITIES[Fetch Amenities Data]
    LOCATION --> GET_LOCATION_INFO[Get Location Info]
    DINING --> FETCH_MENU[Fetch Menu Data]
    EVENTS --> FETCH_EVENT_DATA[Fetch Event Data]
    SUPPORT --> CREATE_TICKET[Create Support Ticket]
    FAQ --> SEARCH_FAQ[Search FAQ Database]
    UNKNOWN --> CLARIFY_REQUEST[Ask for Clarification]
    
    CHECK_AVAILABILITY_API --> FORMAT_AVAILABILITY[Format Availability Response]
    FETCH_ROOM_DATA --> FORMAT_ROOM_INFO[Format Room Information]
    FETCH_PRICING_DATA --> FORMAT_PRICING[Format Pricing Response]
    FETCH_AMENITIES --> FORMAT_AMENITIES[Format Amenities List]
    GET_LOCATION_INFO --> FORMAT_LOCATION[Format Location Info]
    FETCH_MENU --> FORMAT_MENU[Format Menu Response]
    FETCH_EVENT_DATA --> FORMAT_EVENTS[Format Event Info]
    CREATE_TICKET --> CONFIRM_TICKET[Confirm Ticket Created]
    SEARCH_FAQ --> CHECK_FAQ_FOUND{FAQ Found?}
    
    CHECK_FAQ_FOUND -->|Yes| FORMAT_FAQ[Format FAQ Answer]
    CHECK_FAQ_FOUND -->|No| ESCALATE[Escalate to Human]
    
    FORMAT_AVAILABILITY --> GENERATE_RESPONSE
    FORMAT_ROOM_INFO --> GENERATE_RESPONSE
    FORMAT_PRICING --> GENERATE_RESPONSE
    FORMAT_AMENITIES --> GENERATE_RESPONSE
    FORMAT_LOCATION --> GENERATE_RESPONSE
    FORMAT_MENU --> GENERATE_RESPONSE
    FORMAT_EVENTS --> GENERATE_RESPONSE
    CONFIRM_TICKET --> GENERATE_RESPONSE
    FORMAT_FAQ --> GENERATE_RESPONSE
    ESCALATE --> GENERATE_RESPONSE
    CLARIFY_REQUEST --> GENERATE_RESPONSE
    
    GENERATE_RESPONSE --> TRANSLATE_OUTPUT{Translate Back?}
    TRANSLATE_OUTPUT -->|Yes| TRANSLATE_RESPONSE[Translate to User Language]
    TRANSLATE_OUTPUT -->|No| PREPARE_MESSAGE[Prepare Message]
    TRANSLATE_RESPONSE --> PREPARE_MESSAGE
    
    PREPARE_MESSAGE --> LOG_INTERACTION[Log Interaction]
    LOG_INTERACTION --> SEND_RESPONSE[Send Response to User]
    SEND_RESPONSE --> END([End])
```

---

## 12. Room Management Flowchart (Admin)

```mermaid
flowchart TD
    START([Start]) --> ACCESS_ROOMS[Access Room Management]
    ACCESS_ROOMS --> VIEW_CATEGORIES[View Room Categories]
    
    VIEW_CATEGORIES --> CATEGORY_ACTION{Action?}
    CATEGORY_ACTION -->|Add Category| ADD_CATEGORY[Add New Category]
    CATEGORY_ACTION -->|Edit Category| EDIT_CATEGORY[Edit Category]
    CATEGORY_ACTION -->|Delete Category| DELETE_CATEGORY[Delete Category]
    CATEGORY_ACTION -->|Manage Rooms| MANAGE_ROOMS[Manage Individual Rooms]
    
    ADD_CATEGORY --> ENTER_CAT_DETAILS[Enter Category Details]
    ENTER_CAT_DETAILS --> UPLOAD_IMAGES[Upload Room Images]
    UPLOAD_IMAGES --> SET_AMENITIES[Set Amenities]
    SET_AMENITIES --> SET_PRICING[Set Pricing]
    SET_PRICING --> SET_CAPACITY[Set Capacity]
    SET_CAPACITY --> SAVE_CATEGORY[Save Category]
    SAVE_CATEGORY --> VIEW_CATEGORIES
    
    EDIT_CATEGORY --> SELECT_CATEGORY[Select Category]
    SELECT_CATEGORY --> MODIFY_DETAILS[Modify Details]
    MODIFY_DETAILS --> UPDATE_CATEGORY[Update Category]
    UPDATE_CATEGORY --> VIEW_CATEGORIES
    
    DELETE_CATEGORY --> SELECT_DEL_CATEGORY[Select Category to Delete]
    SELECT_DEL_CATEGORY --> CHECK_BOOKINGS{Has Active Bookings?}
    CHECK_BOOKINGS -->|Yes| CANNOT_DELETE[Cannot Delete - Alert]
    CANNOT_DELETE --> VIEW_CATEGORIES
    CHECK_BOOKINGS -->|No| CONFIRM_DELETE{Confirm Delete?}
    CONFIRM_DELETE -->|Yes| REMOVE_CATEGORY[Remove Category]
    CONFIRM_DELETE -->|No| VIEW_CATEGORIES
    REMOVE_CATEGORY --> VIEW_CATEGORIES
    
    MANAGE_ROOMS --> ROOM_ACTION{Room Action?}
    ROOM_ACTION -->|Add Room| ADD_ROOM[Add New Room]
    ROOM_ACTION -->|Edit Room| EDIT_ROOM[Edit Room]
    ROOM_ACTION -->|Change Status| CHANGE_STATUS[Change Room Status]
    ROOM_ACTION -->|View History| VIEW_HISTORY[View Room History]
    
    ADD_ROOM --> ENTER_ROOM_NUM[Enter Room Number]
    ENTER_ROOM_NUM --> SELECT_FLOOR[Select Floor]
    SELECT_FLOOR --> ASSIGN_CATEGORY[Assign Category]
    ASSIGN_CATEGORY --> SET_INITIAL_STATUS[Set Initial Status]
    SET_INITIAL_STATUS --> SAVE_ROOM[Save Room]
    SAVE_ROOM --> MANAGE_ROOMS
    
    EDIT_ROOM --> SELECT_EDIT_ROOM[Select Room]
    SELECT_EDIT_ROOM --> MODIFY_ROOM[Modify Room Details]
    MODIFY_ROOM --> UPDATE_ROOM[Update Room]
    UPDATE_ROOM --> MANAGE_ROOMS
    
    CHANGE_STATUS --> SELECT_STATUS_ROOM[Select Room]
    SELECT_STATUS_ROOM --> NEW_STATUS{New Status?}
    NEW_STATUS -->|Available| SET_AVAILABLE[Set Available]
    NEW_STATUS -->|Occupied| SET_OCCUPIED[Set Occupied]
    NEW_STATUS -->|Maintenance| SET_MAINTENANCE[Set Maintenance]
    NEW_STATUS -->|Cleaning| SET_CLEANING[Set Cleaning]
    
    SET_AVAILABLE --> UPDATE_ROOM_STATUS[Update Status]
    SET_OCCUPIED --> UPDATE_ROOM_STATUS
    SET_MAINTENANCE --> UPDATE_ROOM_STATUS
    SET_CLEANING --> UPDATE_ROOM_STATUS
    
    UPDATE_ROOM_STATUS --> LOG_STATUS_CHANGE[Log Status Change]
    LOG_STATUS_CHANGE --> MANAGE_ROOMS
    
    VIEW_HISTORY --> SELECT_HISTORY_ROOM[Select Room]
    SELECT_HISTORY_ROOM --> DISPLAY_HISTORY[Display Booking History]
    DISPLAY_HISTORY --> MANAGE_ROOMS
```

---

## Legend

| Symbol | Meaning |
|--------|---------|
| `([Start])` / `([End])` | Start/End of process |
| `[Process]` | Process or action step |
| `{Decision}` | Decision point with branches |
| `-->|Label|` | Flow direction with condition |

---

## Summary

This System Flowchart document provides comprehensive visual documentation of all major processes in the Bayawan Hotel Management System, including:

- **Overall System Flow**: High-level view of user navigation
- **Room Booking Flow**: Complete reservation process
- **Check-in/Check-out**: Guest arrival and departure workflows
- **Food Ordering**: Room service and dining processes
- **Event Booking**: Event space reservation workflow
- **Authentication**: User registration and login flows
- **Payment Processing**: Multi-gateway payment handling
- **Admin Functions**: User and room management
- **Notification System**: Multi-channel notification delivery
- **Chatbot**: AI-powered guest assistance
- **Room Management**: Administrative room operations
