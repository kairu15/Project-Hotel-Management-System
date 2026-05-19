<?php
/**
 * Bayawan Bai Hotel - Email Notification System
 * Handles sending transactional email notifications for bookings, event inquiries, and food orders
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/qr_code_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function getEmailConnectionErrorMessage() {
    return 'No internet connection detected. Please connect to the internet and try again so we can send your confirmation email.';
}

function isSmtpConnectionAvailable($timeoutSeconds = 5) {
    static $connectionCache = [];

    $host = SMTP_HOST;
    $port = (int)SMTP_PORT;
    $cacheKey = $host . ':' . $port . ':' . (int)$timeoutSeconds;

    if (array_key_exists($cacheKey, $connectionCache)) {
        return $connectionCache[$cacheKey];
    }

    $errorNumber = 0;
    $errorMessage = '';
    $connection = @fsockopen($host, $port, $errorNumber, $errorMessage, $timeoutSeconds);

    if ($connection) {
        fclose($connection);
        $connectionCache[$cacheKey] = true;
        return true;
    }

    error_log("SMTP connection check failed for {$host}:{$port} - {$errorNumber} {$errorMessage}");
    $connectionCache[$cacheKey] = false;
    return false;
}

/**
 * Send Room Booking Confirmation Email
 * 
 * @param string $to Recipient email address
 * @param array $bookingData Booking details including:
 *   - booking_ref: Booking reference number
 *   - room_type: Room type name
 *   - check_in: Check-in date
 *   - check_out: Check-out date
 *   - nights: Number of nights
 *   - guests: Number of guests
 *   - total_amount: Total booking amount
 *   - payment_status: Payment status
 *   - payment_method: Payment method used
 *   - guest_name: Guest name
 * @return bool Success status
 */
function sendBookingConfirmationEmail($to, $bookingData) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Generate QR code and attach as embedded image
        $qrBinary = generateQRCodeBinary($bookingData['booking_ref'] ?? '', 100);
        if ($qrBinary) {
            $mail->addStringEmbeddedImage($qrBinary, 'qrcode', 'qrcode.png', 'base64', 'image/png');
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Room Booking Confirmation - ' . $bookingData['booking_ref'];
        $mail->Body    = getBookingConfirmationTemplate($bookingData, $qrBinary ? 'qrcode' : null);
        $mail->AltBody = getBookingConfirmationPlainText($bookingData);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Booking confirmation email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send Event Inquiry Confirmation Email
 * 
 * @param string $to Recipient email address
 * @param array $eventData Event inquiry details including:
 *   - inquiry_id: Event booking/inquiry ID
 *   - event_type: Type of event
 *   - event_date: Event date
 *   - start_time: Start time
 *   - end_time: End time
 *   - guests_count: Number of guests
 *   - space_name: Event space name
 *   - inquiry_name: Contact person name
 *   - special_requests: Special requests or message
 * @return bool Success status
 */
function sendEventInquiryConfirmationEmail($to, $eventData) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Generate QR code and attach as embedded image
        $inquiryRef = $eventData['event_ref'] ?? ('INQ-' . str_pad($eventData['inquiry_id'] ?? '0', 6, '0', STR_PAD_LEFT));
        $qrBinary = generateQRCodeBinary($inquiryRef, 100);
        if ($qrBinary) {
            $mail->addStringEmbeddedImage($qrBinary, 'qrcode', 'qrcode.png', 'base64', 'image/png');
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Event Inquiry Received - Bayawan Bai Hotel';
        $mail->Body    = getEventInquiryTemplate($eventData, $qrBinary ? 'qrcode' : null);
        $mail->AltBody = getEventInquiryPlainText($eventData);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Event inquiry confirmation email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send Food Order Confirmation Email
 * 
 * @param string $to Recipient email address
 * @param array $orderData Food order details including:
 *   - order_id: Order ID
 *   - transaction_ref: Transaction reference
 *   - item_name: Food item name
 *   - quantity: Quantity ordered
 *   - unit_price: Price per item
 *   - total_price: Total order amount
 *   - order_type: Order type (dine_in, room_service, takeaway)
 *   - payment_method: Payment method used
 *   - payment_status: Payment status
 *   - room_number: Room number (if room service)
 *   - special_instructions: Special instructions
 *   - estimated_time: Estimated preparation/delivery time
 * @return bool Success status
 */
function sendFoodOrderConfirmationEmail($to, $orderData) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Generate QR code and attach as embedded image using order_ref
        $orderRef = $orderData['order_ref'] ?? $orderData['transaction_ref'] ?? '';
        $qrBinary = generateQRCodeBinary($orderRef, 100);
        if ($qrBinary) {
            $mail->addStringEmbeddedImage($qrBinary, 'qrcode', 'qrcode.png', 'base64', 'image/png');
        }
        
        // Ensure order_ref is set for the template
        $orderData['order_ref'] = $orderRef;
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Food Order Confirmation - ' . $orderRef;
        $mail->Body    = getFoodOrderConfirmationTemplate($orderData, $qrBinary ? 'qrcode' : null);
        $mail->AltBody = getFoodOrderConfirmationPlainText($orderData);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Food order confirmation email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Get HTML email template for room booking confirmation
 */
function getBookingConfirmationTemplate($data, $qrCID = null) {
    $bookingRef = htmlspecialchars($data['booking_ref'] ?? 'N/A');
    $roomType = htmlspecialchars($data['room_type'] ?? 'N/A');
    $checkIn = htmlspecialchars($data['check_in'] ?? 'N/A');
    $checkOut = htmlspecialchars($data['check_out'] ?? 'N/A');
    $nights = htmlspecialchars($data['nights'] ?? '0');
    $guests = htmlspecialchars($data['guests'] ?? '0');
    $totalAmount = number_format($data['total_amount'] ?? 0, 2);
    $paymentStatus = ucfirst(htmlspecialchars($data['payment_status'] ?? 'pending'));
    $paymentMethod = ucfirst(str_replace('_', ' ', htmlspecialchars($data['payment_method'] ?? 'N/A')));
    $guestName = htmlspecialchars($data['guest_name'] ?? 'Valued Guest');
    
    $statusColor = ($data['payment_status'] === 'paid') ? '#28a745' : '#ffc107';
    
    // Generate QR code HTML with CID if available
    if ($qrCID) {
        $qrCodeHtml = getEmailQRCodeHTMLWithCID($data['booking_ref'] ?? '', 'booking', $qrCID);
    } else {
        $qrCodeHtml = getEmailQRCodeHTML($data['booking_ref'] ?? '', 'booking');
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Room Booking Confirmation - Bayawan Bai Hotel</title>
        <style>
            body { font-family: Georgia, "Times New Roman", serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #333; }
            .container { max-width: 650px; margin: 30px auto; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e9ecef; }
            .header { background: linear-gradient(135deg, #367D8A 0%, #285F6B 100%); padding: 40px 30px; text-align: center; position: relative; }
            .logo { font-size: 32px; font-weight: 700; color: #ffffff; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); letter-spacing: 1px; }
            .logo span { color: #ffffff; font-style: italic; }
            .tagline { color: rgba(255,255,255,0.9); font-size: 14px; font-style: italic; margin: 0; }
            .content { padding: 50px 40px; }
            .greeting { font-size: 18px; margin-bottom: 25px; color: #495057; }
            .booking-ref-box { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #367D8A; border-radius: 15px; padding: 30px; margin: 35px 0; text-align: center; }
            .ref-label { font-size: 14px; color: #6c757d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
            .ref-number { font-size: 28px; font-weight: 700; color: #367D8A; letter-spacing: 3px; font-family: "Courier New", monospace; }
            .details-section { background: white; border-radius: 10px; padding: 25px; margin: 30px 0; border: 1px solid #e9ecef; }
            .section-title { font-size: 18px; color: #367D8A; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #367D8A; padding-bottom: 10px; }
            .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #333; font-weight: 500; }
            .payment-status { display: inline-block; padding: 8px 20px; border-radius: 20px; font-weight: 600; font-size: 14px; background-color: ' . $statusColor . '; color: white; }
            .total-box { background: linear-gradient(135deg, #367D8A 0%, #285F6B 100%); color: white; padding: 25px; border-radius: 10px; margin-top: 20px; }
            .total-row { display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; }
            .info-box { background-color: #e7f3ff; border-left: 4px solid #367D8A; padding: 20px; margin: 30px 0; border-radius: 0 8px 8px 0; }
            .info-box h4 { color: #367D8A; margin-bottom: 10px; }
            .info-box p { margin: 0; color: #495057; font-size: 14px; line-height: 1.6; }
            .footer { background-color: #2c3e50; color: #ecf0f1; padding: 30px; text-align: center; border-top: 3px solid #367D8A; }
            .footer-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .footer-left { text-align: left; }
            .footer-right { text-align: right; }
            .footer h5 { color: #367D8A; margin-bottom: 10px; font-size: 16px; }
            .footer p { margin: 5px 0; font-size: 13px; opacity: 0.9; }
            .copyright { font-size: 11px; opacity: 0.7; margin-top: 20px; }
            @media (max-width: 600px) {
                .container { margin: 10px; border-radius: 10px; }
                .content { padding: 30px 20px; }
                .footer-content { flex-direction: column; text-align: center; }
                .footer-left, .footer-right { text-align: center; margin-bottom: 15px; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">Bayawan <span>Bai</span> Hotel</div>
                <p class="tagline">Experience Luxury and Comfort</p>
            </div>
            
            <div class="content">
                <h2 style="color: #367D8A; font-size: 28px; margin-bottom: 20px; text-align: center;">Booking Confirmed!</h2>
                
                <p class="greeting">Dear ' . $guestName . ',</p>
                
                <p style="font-size: 16px; line-height: 1.7; color: #495057;">
                    Thank you for choosing Bayawan Bai Hotel. Your room reservation has been successfully confirmed. We look forward to welcoming you.
                </p>
                
                ' . $qrCodeHtml . '
                
                <div class="details-section">
                    <h3 class="section-title">Booking Details</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Room Type</span>
                        <span class="detail-value">' . $roomType . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-in Date</span>
                        <span class="detail-value">' . date('F d, Y', strtotime($checkIn)) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-out Date</span>
                        <span class="detail-value">' . date('F d, Y', strtotime($checkOut)) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Number of Nights</span>
                        <span class="detail-value">' . $nights . ' night' . ($nights > 1 ? 's' : '') . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Guests</span>
                        <span class="detail-value">' . $guests . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value">' . $paymentMethod . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value"><span class="payment-status">' . $paymentStatus . '</span></span>
                    </div>
                </div>
                
                <div class="total-box">
                    <div class="total-row">
                        <span>Total Amount</span>
                        <span>₱' . $totalAmount . '</span>
                    </div>
                </div>
                
                <div class="info-box">
                    <h4>Important Information</h4>
                    <p>Please present your booking reference upon check-in. Check-in time is 2:00 PM and check-out time is 12:00 PM. For early check-in or late check-out requests, please contact our front desk.</p>
                </div>
                
                <p style="font-size: 14px; color: #6c757d; margin-top: 30px;">
                    If you have any questions or need to modify your reservation, please contact us at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #367D8A;">' . SMTP_FROM_EMAIL . '</a> or call us at +63 (32) 123-4567.
                </p>
            </div>
            
            <div class="footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <h5>Contact Information</h5>
                        <p>Bayawan City, Negros Oriental</p>
                        <p>+63 (32) 123-4567</p>
                        <p>' . SMTP_FROM_EMAIL . '</p>
                    </div>
                    <div class="footer-right">
                        <h5>Business Hours</h5>
                        <p>Monday - Friday: 8:00 AM - 8:00 PM</p>
                        <p>Saturday - Sunday: 9:00 AM - 6:00 PM</p>
                        <p>24/7 Front Desk Available</p>
                    </div>
                </div>
                <p class="copyright">
                    © ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.<br>
                    This is an automated confirmation email. Please do not reply.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Get plain text version of booking confirmation email
 */
function getBookingConfirmationPlainText($data) {
    return '
BOOKING CONFIRMATION - BAYAWAN BAI HOTEL

Dear ' . ($data['guest_name'] ?? 'Valued Guest') . ',

Thank you for choosing Bayawan Bai Hotel. Your room reservation has been successfully confirmed.

BOOKING REFERENCE: ' . ($data['booking_ref'] ?? 'N/A') . '

BOOKING DETAILS:
- Room Type: ' . ($data['room_type'] ?? 'N/A') . '
- Check-in Date: ' . date('F d, Y', strtotime($data['check_in'] ?? 'today')) . '
- Check-out Date: ' . date('F d, Y', strtotime($data['check_out'] ?? 'today')) . '
- Number of Nights: ' . ($data['nights'] ?? '0') . '
- Guests: ' . ($data['guests'] ?? '0') . '
- Payment Method: ' . ucfirst(str_replace('_', ' ', $data['payment_method'] ?? 'N/A')) . '
- Payment Status: ' . ucfirst($data['payment_status'] ?? 'pending') . '
- Total Amount: ₱' . number_format($data['total_amount'] ?? 0, 2) . '

IMPORTANT INFORMATION:
Please present your booking reference upon check-in. Check-in time is 2:00 PM and check-out time is 12:00 PM.

For inquiries or reservation modifications, contact us at:
Email: ' . SMTP_FROM_EMAIL . '
Phone: +63 (32) 123-4567

© ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.
';
}

/**
 * Get HTML email template for event inquiry confirmation
 */
function getEventInquiryTemplate($data, $qrCID = null) {
    $inquiryId = htmlspecialchars($data['inquiry_id'] ?? 'N/A');
    $eventType = ucfirst(htmlspecialchars($data['event_type'] ?? 'N/A'));
    $eventDate = htmlspecialchars($data['event_date'] ?? 'N/A');
    $startTime = htmlspecialchars($data['start_time'] ?? '');
    $endTime = htmlspecialchars($data['end_time'] ?? '');
    $guestsCount = htmlspecialchars($data['guests_count'] ?? '0');
    $spaceName = htmlspecialchars($data['space_name'] ?? 'To be determined');
    $inquiryName = htmlspecialchars($data['inquiry_name'] ?? 'Valued Guest');
    $specialRequests = nl2br(htmlspecialchars($data['special_requests'] ?? 'None'));
    
    $timeDisplay = '';
    if ($startTime && $endTime) {
        $timeDisplay = date('h:i A', strtotime($startTime)) . ' - ' . date('h:i A', strtotime($endTime));
    } elseif ($startTime) {
        $timeDisplay = 'Starting at ' . date('h:i A', strtotime($startTime));
    }
    
    $inquiryRef = 'INQ-' . str_pad($inquiryId, 6, '0', STR_PAD_LEFT);
    
    // Generate QR code for inquiry reference
    if ($qrCID) {
        $qrCodeHtml = getEmailQRCodeHTMLWithCID($inquiryRef, 'inquiry', $qrCID);
    } else {
        $qrCodeHtml = getEmailQRCodeHTML($inquiryRef, 'inquiry');
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Event Inquiry Confirmation - Bayawan Bai Hotel</title>
        <style>
            body { font-family: Georgia, "Times New Roman", serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #333; }
            .container { max-width: 650px; margin: 30px auto; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e9ecef; }
            .header { background: linear-gradient(135deg, #8B6B4A 0%, #6B4E3D 100%); padding: 40px 30px; text-align: center; }
            .logo { font-size: 32px; font-weight: 700; color: #ffffff; margin-bottom: 10px; letter-spacing: 1px; }
            .logo span { color: #ffffff; font-style: italic; }
            .tagline { color: rgba(255,255,255,0.9); font-size: 14px; font-style: italic; margin: 0; }
            .content { padding: 50px 40px; }
            .greeting { font-size: 18px; margin-bottom: 25px; color: #495057; }
            .inquiry-box { background: linear-gradient(135deg, #fff8f0 0%, #f5ebe0 100%); border: 2px solid #8B6B4A; border-radius: 15px; padding: 30px; margin: 35px 0; text-align: center; }
            .inquiry-label { font-size: 14px; color: #6c757d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
            .inquiry-number { font-size: 24px; font-weight: 700; color: #8B6B4A; font-family: "Courier New", monospace; }
            .details-section { background: white; border-radius: 10px; padding: 25px; margin: 30px 0; border: 1px solid #e9ecef; }
            .section-title { font-size: 18px; color: #8B6B4A; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #8B6B4A; padding-bottom: 10px; }
            .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #333; font-weight: 500; }
            .message-box { background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-top: 15px; }
            .message-box h4 { color: #6c757d; margin-bottom: 10px; font-size: 14px; }
            .message-box p { margin: 0; color: #495057; line-height: 1.6; }
            .response-box { background: linear-gradient(135deg, #8B6B4A 0%, #6B4E3D 100%); color: white; padding: 25px; border-radius: 10px; margin-top: 30px; text-align: center; }
            .response-box h3 { margin-bottom: 15px; font-size: 20px; }
            .response-box p { margin: 0; font-size: 14px; opacity: 0.9; }
            .footer { background-color: #2c3e50; color: #ecf0f1; padding: 30px; text-align: center; border-top: 3px solid #8B6B4A; }
            .footer-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .footer-left { text-align: left; }
            .footer-right { text-align: right; }
            .footer h5 { color: #d4a574; margin-bottom: 10px; font-size: 16px; }
            .footer p { margin: 5px 0; font-size: 13px; opacity: 0.9; }
            .copyright { font-size: 11px; opacity: 0.7; margin-top: 20px; }
            @media (max-width: 600px) {
                .container { margin: 10px; border-radius: 10px; }
                .content { padding: 30px 20px; }
                .footer-content { flex-direction: column; text-align: center; }
                .footer-left, .footer-right { text-align: center; margin-bottom: 15px; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">Bayawan <span>Bai</span> Hotel</div>
                <p class="tagline">Events & Meetings</p>
            </div>
            
            <div class="content">
                <h2 style="color: #8B6B4A; font-size: 28px; margin-bottom: 20px; text-align: center;">Event Inquiry Received</h2>
                
                <p class="greeting">Dear ' . $inquiryName . ',</p>
                
                <p style="font-size: 16px; line-height: 1.7; color: #495057;">
                    Thank you for your interest in hosting your event at Bayawan Bai Hotel. We have received your inquiry and our events team will review your requirements and contact you within 24 hours with a customized quotation.
                </p>
                
                ' . $qrCodeHtml . '
                
                <div class="details-section">
                    <h3 class="section-title">Event Details</h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Event Type</span>
                        <span class="detail-value">' . $eventType . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Event Date</span>
                        <span class="detail-value">' . date('F d, Y', strtotime($eventDate)) . '</span>
                    </div>
                    ' . ($timeDisplay ? '
                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span class="detail-value">' . $timeDisplay . '</span>
                    </div>
                    ' : '') . '
                    <div class="detail-row">
                        <span class="detail-label">Number of Guests</span>
                        <span class="detail-value">' . $guestsCount . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Preferred Space</span>
                        <span class="detail-value">' . $spaceName . '</span>
                    </div>
                </div>
                
                ' . ($data['special_requests'] ? '
                <div class="message-box">
                    <h4>Your Special Requests</h4>
                    <p>' . $specialRequests . '</p>
                </div>
                ' : '') . '
                
                <div class="response-box">
                    <h3>What Happens Next?</h3>
                    <p>Our events coordinator will contact you within 24 hours to discuss your event requirements and provide a customized quotation based on your needs.</p>
                </div>
                
                <p style="font-size: 14px; color: #6c757d; margin-top: 30px;">
                    For immediate assistance, please contact our Events Team at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #8B6B4A;">' . SMTP_FROM_EMAIL . '</a> or call us at +63 (32) 123-4567.
                </p>
            </div>
            
            <div class="footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <h5>Events Team</h5>
                        <p>Bayawan Bai Hotel</p>
                        <p>Bayawan City, Negros Oriental</p>
                    </div>
                    <div class="footer-right">
                        <h5>Contact Us</h5>
                        <p>' . SMTP_FROM_EMAIL . '</p>
                        <p>+63 (32) 123-4567</p>
                    </div>
                </div>
                <p class="copyright">
                    © ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.<br>
                    This is an automated confirmation email. Please do not reply.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Get plain text version of event inquiry email
 */
function getEventInquiryPlainText($data) {
    $timeDisplay = '';
    if (!empty($data['start_time']) && !empty($data['end_time'])) {
        $timeDisplay = date('h:i A', strtotime($data['start_time'])) . ' - ' . date('h:i A', strtotime($data['end_time']));
    } elseif (!empty($data['start_time'])) {
        $timeDisplay = 'Starting at ' . date('h:i A', strtotime($data['start_time']));
    }
    
    return '
EVENT INQUIRY RECEIVED - BAYAWAN BAI HOTEL

Dear ' . ($data['inquiry_name'] ?? 'Valued Guest') . ',

Thank you for your interest in hosting your event at Bayawan Bai Hotel. We have received your inquiry and our events team will review your requirements and contact you within 24 hours with a customized quotation.

INQUIRY REFERENCE: INQ-' . str_pad($data['inquiry_id'] ?? 'N/A', 6, '0', STR_PAD_LEFT) . '

EVENT DETAILS:
- Event Type: ' . ucfirst($data['event_type'] ?? 'N/A') . '
- Event Date: ' . date('F d, Y', strtotime($data['event_date'] ?? 'today')) . '
' . ($timeDisplay ? '- Time: ' . $timeDisplay . '\n' : '') . '- Number of Guests: ' . ($data['guests_count'] ?? '0') . '
- Preferred Space: ' . ($data['space_name'] ?? 'To be determined') . '

WHAT HAPPENS NEXT?
Our events coordinator will contact you within 24 hours to discuss your event requirements and provide a customized quotation.

For immediate assistance, contact our Events Team:
Email: ' . SMTP_FROM_EMAIL . '
Phone: +63 (32) 123-4567

© ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.
';
}

/**
 * Get HTML email template for food order confirmation
 */
function getFoodOrderConfirmationTemplate($data, $qrCID = null) {
    $orderId = htmlspecialchars($data['order_id'] ?? 'N/A');
    $orderRef = htmlspecialchars($data['order_ref'] ?? $data['transaction_ref'] ?? 'N/A');
    $itemName = htmlspecialchars($data['item_name'] ?? 'N/A');
    $quantity = htmlspecialchars($data['quantity'] ?? '1');
    $unitPrice = number_format($data['unit_price'] ?? 0, 2);
    $totalPrice = number_format($data['total_price'] ?? 0, 2);
    $orderType = ucfirst(str_replace('_', ' ', htmlspecialchars($data['order_type'] ?? 'dine_in')));
    $paymentMethod = ucfirst(str_replace('_', ' ', htmlspecialchars($data['payment_method'] ?? 'N/A')));
    $paymentStatus = ucfirst(htmlspecialchars($data['payment_status'] ?? 'pending'));
    $roomNumber = htmlspecialchars($data['room_number'] ?? '');
    $specialInstructions = nl2br(htmlspecialchars($data['special_instructions'] ?? ''));
    $estimatedTime = htmlspecialchars($data['estimated_time'] ?? '20-30 minutes');
    
    $statusColor = ($data['payment_status'] === 'paid') ? '#28a745' : '#ffc107';
    
    $orderTypeIcon = '🍽️';
    if ($data['order_type'] === 'room_service') $orderTypeIcon = '🛏️';
    if ($data['order_type'] === 'takeaway') $orderTypeIcon = '📦';
    
    // Generate QR code for order reference
    $qrRef = $data['order_ref'] ?? $data['transaction_ref'] ?? '';
    if ($qrCID) {
        $qrCodeHtml = getEmailQRCodeHTMLWithCID($qrRef, 'order', $qrCID);
    } else {
        $qrCodeHtml = getEmailQRCodeHTML($qrRef, 'order');
    }
    
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Food Order Confirmation - Bayawan Bai Hotel</title>
        <style>
            body { font-family: Georgia, "Times New Roman", serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #333; }
            .container { max-width: 650px; margin: 30px auto; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e9ecef; }
            .header { background: linear-gradient(135deg, #d4a574 0%, #b8935f 100%); padding: 40px 30px; text-align: center; }
            .logo { font-size: 32px; font-weight: 700; color: #ffffff; margin-bottom: 10px; letter-spacing: 1px; }
            .logo span { color: #ffffff; font-style: italic; }
            .tagline { color: rgba(255,255,255,0.9); font-size: 14px; font-style: italic; margin: 0; }
            .content { padding: 50px 40px; }
            .order-ref-box { background: linear-gradient(135deg, #fff8f0 0%, #f5ebe0 100%); border: 2px solid #d4a574; border-radius: 15px; padding: 30px; margin: 35px 0; text-align: center; }
            .ref-label { font-size: 14px; color: #6c757d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
            .ref-number { font-size: 24px; font-weight: 700; color: #8B6B4A; font-family: "Courier New", monospace; }
            .order-summary { background: white; border-radius: 10px; padding: 25px; margin: 30px 0; border: 1px solid #e9ecef; }
            .section-title { font-size: 18px; color: #d4a574; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #d4a574; padding-bottom: 10px; }
            .order-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 15px; }
            .item-info { flex: 1; }
            .item-name { font-weight: 600; color: #333; font-size: 16px; }
            .item-qty { color: #6c757d; font-size: 14px; }
            .item-price { font-weight: 600; color: #d4a574; font-size: 18px; }
            .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #333; font-weight: 500; }
            .payment-status { display: inline-block; padding: 8px 20px; border-radius: 20px; font-weight: 600; font-size: 14px; background-color: ' . $statusColor . '; color: white; }
            .total-box { background: linear-gradient(135deg, #d4a574 0%, #b8935f 100%); color: white; padding: 25px; border-radius: 10px; margin-top: 20px; }
            .total-row { display: flex; justify-content: space-between; font-size: 22px; font-weight: 700; }
            .time-box { background-color: #e8f5e9; border-left: 4px solid #28a745; padding: 20px; margin: 30px 0; border-radius: 0 8px 8px 0; }
            .time-box h4 { color: #28a745; margin-bottom: 10px; }
            .time-box p { margin: 0; color: #495057; font-size: 16px; font-weight: 500; }
            .special-instructions { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0; border-radius: 0 8px 8px 0; }
            .special-instructions h4 { color: #856404; margin-bottom: 10px; }
            .special-instructions p { margin: 0; color: #856404; }
            .footer { background-color: #2c3e50; color: #ecf0f1; padding: 30px; text-align: center; border-top: 3px solid #d4a574; }
            .footer-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .footer-left { text-align: left; }
            .footer-right { text-align: right; }
            .footer h5 { color: #d4a574; margin-bottom: 10px; font-size: 16px; }
            .footer p { margin: 5px 0; font-size: 13px; opacity: 0.9; }
            .copyright { font-size: 11px; opacity: 0.7; margin-top: 20px; }
            @media (max-width: 600px) {
                .container { margin: 10px; border-radius: 10px; }
                .content { padding: 30px 20px; }
                .order-item { flex-direction: column; text-align: center; }
                .footer-content { flex-direction: column; text-align: center; }
                .footer-left, .footer-right { text-align: center; margin-bottom: 15px; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">Bayawan <span>Bai</span> Hotel</div>
                <p class="tagline">Culinary Excellence</p>
            </div>
            
            <div class="content">
                <h2 style="color: #d4a574; font-size: 28px; margin-bottom: 10px; text-align: center;">Order Confirmed!</h2>
                <p style="text-align: center; color: #6c757d; margin-bottom: 30px;">Thank you for ordering with us</p>
                
                ' . $qrCodeHtml . '
                
                <div class="order-summary">
                    <h3 class="section-title">' . $orderTypeIcon . ' Order Summary</h3>
                    
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-name">' . $itemName . '</div>
                            <div class="item-qty">Quantity: ' . $quantity . ' x ₱' . $unitPrice . '</div>
                        </div>
                        <div class="item-price">₱' . $totalPrice . '</div>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Order Type</span>
                        <span class="detail-value">' . $orderType . '</span>
                    </div>
                    ' . ($roomNumber ? '
                    <div class="detail-row">
                        <span class="detail-label">Room Number</span>
                        <span class="detail-value">' . $roomNumber . '</span>
                    </div>
                    ' : '') . '
                    <div class="detail-row">
                        <span class="detail-label">Payment Method</span>
                        <span class="detail-value">' . $paymentMethod . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value"><span class="payment-status">' . $paymentStatus . '</span></span>
                    </div>
                </div>
                
                <div class="total-box">
                    <div class="total-row">
                        <span>Total Amount</span>
                        <span>₱' . $totalPrice . '</span>
                    </div>
                </div>
                
                <div class="time-box">
                    <h4>⏱️ Estimated ' . ($data['order_type'] === 'room_service' ? 'Delivery' : 'Preparation') . ' Time</h4>
                    <p>' . $estimatedTime . '</p>
                </div>
                
                ' . ($specialInstructions ? '
                <div class="special-instructions">
                    <h4>Special Instructions</h4>
                    <p>' . $specialInstructions . '</p>
                </div>
                ' : '') . '
                
                <p style="font-size: 14px; color: #6c757d; margin-top: 30px; text-align: center;">
                    If you have any questions about your order, please contact our dining services at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #d4a574;">' . SMTP_FROM_EMAIL . '</a> or call ext. 2 from your room.
                </p>
            </div>
            
            <div class="footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <h5>Dining Services</h5>
                        <p>Bayawan Bai Hotel</p>
                        <p>Open 6:00 AM - 10:00 PM Daily</p>
                    </div>
                    <div class="footer-right">
                        <h5>Contact Us</h5>
                        <p>' . SMTP_FROM_EMAIL . '</p>
                        <p>+63 (32) 123-4567 ext. 2</p>
                    </div>
                </div>
                <p class="copyright">
                    © ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.<br>
                    This is an automated confirmation email. Please do not reply.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Get plain text version of food order confirmation email
 */
function getFoodOrderConfirmationPlainText($data) {
    $orderType = ucfirst(str_replace('_', ' ', $data['order_type'] ?? 'dine_in'));

    return '
FOOD ORDER CONFIRMATION - BAYAWAN BAI HOTEL

Thank you for ordering with us!

ORDER REFERENCE: ' . ($data['order_ref'] ?? $data['transaction_ref'] ?? 'N/A') . '

ORDER SUMMARY:
- Item: ' . ($data['item_name'] ?? 'N/A') . '
- Quantity: ' . ($data['quantity'] ?? '1') . '
- Unit Price: ₱' . number_format($data['unit_price'] ?? 0, 2) . '
- Total: ₱' . number_format($data['total_price'] ?? 0, 2) . '
- Order Type: ' . $orderType . '
' . (!empty($data['room_number']) ? '- Room Number: ' . $data['room_number'] . '\n' : '') . '- Payment Method: ' . ucfirst(str_replace('_', ' ', $data['payment_method'] ?? 'N/A')) . '
- Payment Status: ' . ucfirst($data['payment_status'] ?? 'pending') . '

ESTIMATED ' . (strpos($orderType, 'Room') !== false ? 'DELIVERY' : 'PREPARATION') . ' TIME: ' . ($data['estimated_time'] ?? '20-30 minutes') . '
' . (!empty($data['special_instructions']) ? '
SPECIAL INSTRUCTIONS:
' . $data['special_instructions'] . '\n' : '') . '

For questions about your order, contact our dining services:
Email: ' . SMTP_FROM_EMAIL . '
Phone: +63 (32) 123-4567 ext. 2

© ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.
';
}

/**
 * Send Checkout Receipt Email
 *
 * @param string $to Recipient email address
 * @param array $checkoutData Checkout details including:
 *   - booking_ref: Booking reference number
 *   - guest_name: Guest full name
 *   - room_type: Room type name
 *   - room_number: Room number
 *   - check_in: Check-in date
 *   - check_out: Check-out date
 *   - nights: Number of nights
 *   - adults: Number of adults
 *   - children: Number of children
 *   - room_charges: Room charges amount
 *   - additional_charges: Array of additional charges (description, amount)
 *   - total_charges: Total charges amount
 *   - payments: Array of payments (date, method, amount)
 *   - total_paid: Total amount paid
 *   - balance_due: Balance due (if any)
 *   - checkout_date: Date and time of checkout
 *   - staff_name: Staff member who processed checkout
 * @return bool Success status
 */
function sendCheckoutReceiptEmail($to, $checkoutData) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Checkout Receipt - ' . ($checkoutData['booking_ref'] ?? 'Booking');
        $mail->Body    = getCheckoutReceiptTemplate($checkoutData);
        $mail->AltBody = getCheckoutReceiptPlainText($checkoutData);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Checkout receipt email failed: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Get HTML email template for checkout receipt
 */
function getCheckoutReceiptTemplate($data) {
    $bookingRef = htmlspecialchars($data['booking_ref'] ?? 'N/A');
    $guestName = htmlspecialchars($data['guest_name'] ?? 'Valued Guest');
    $roomType = htmlspecialchars($data['room_type'] ?? 'N/A');
    $roomNumber = htmlspecialchars($data['room_number'] ?? 'N/A');
    $checkIn = htmlspecialchars($data['check_in'] ?? 'N/A');
    $checkOut = htmlspecialchars($data['check_out'] ?? 'N/A');
    $nights = htmlspecialchars($data['nights'] ?? '0');
    $adults = htmlspecialchars($data['adults'] ?? '0');
    $children = htmlspecialchars($data['children'] ?? '0');
    $roomCharges = number_format($data['room_charges'] ?? 0, 2);
    $totalCharges = number_format($data['total_charges'] ?? 0, 2);
    $totalPaid = number_format($data['total_paid'] ?? 0, 2);
    $balanceDue = number_format($data['balance_due'] ?? 0, 2);
    $checkoutDate = htmlspecialchars($data['checkout_date'] ?? date('F d, Y h:i A'));
    $staffName = htmlspecialchars($data['staff_name'] ?? 'Staff');

    $additionalCharges = $data['additional_charges'] ?? [];
    $payments = $data['payments'] ?? [];

    // Build additional charges HTML
    $chargesHtml = '';
    foreach ($additionalCharges as $charge) {
        $chargeDesc = htmlspecialchars($charge['description'] ?? '');
        $chargeAmount = number_format($charge['amount'] ?? 0, 2);
        $chargesHtml .= '
                    <div class="detail-row">
                        <span class="detail-label">' . $chargeDesc . '</span>
                        <span class="detail-value">₱' . $chargeAmount . '</span>
                    </div>';
    }

    // Build payments HTML
    $paymentsHtml = '';
    foreach ($payments as $payment) {
        $paymentDate = htmlspecialchars($payment['date'] ?? '');
        $paymentMethod = htmlspecialchars($payment['method'] ?? '');
        $paymentAmount = number_format($payment['amount'] ?? 0, 2);
        $paymentsHtml .= '
                    <div class="detail-row">
                        <span class="detail-label">Payment (' . $paymentDate . ') - ' . $paymentMethod . '</span>
                        <span class="detail-value" style="color: #28a745;">-₱' . $paymentAmount . '</span>
                    </div>';
    }

    $balanceColor = ($data['balance_due'] ?? 0) > 0 ? '#dc3545' : '#28a745';
    $balanceText = ($data['balance_due'] ?? 0) > 0 ? 'Balance Due' : 'Fully Paid';

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Checkout Receipt - Bayawan Bai Hotel</title>
        <style>
            body { font-family: Georgia, "Times New Roman", serif; margin: 0; padding: 0; background-color: #f8f9fa; color: #333; }
            .container { max-width: 650px; margin: 30px auto; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid #e9ecef; }
            .header { background: linear-gradient(135deg, #367D8A 0%, #285F6B 100%); padding: 40px 30px; text-align: center; position: relative; }
            .logo { font-size: 32px; font-weight: 700; color: #ffffff; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); letter-spacing: 1px; }
            .logo span { color: #ffffff; font-style: italic; }
            .tagline { color: rgba(255,255,255,0.9); font-size: 14px; font-style: italic; margin: 0; }
            .receipt-title { background-color: #e9ecef; padding: 15px; text-align: center; border-bottom: 2px solid #367D8A; }
            .receipt-title h2 { margin: 0; color: #367D8A; font-size: 24px; }
            .content { padding: 40px; }
            .greeting { font-size: 18px; margin-bottom: 25px; color: #495057; }
            .booking-ref-box { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #367D8A; border-radius: 15px; padding: 25px; margin: 25px 0; text-align: center; }
            .ref-label { font-size: 14px; color: #6c757d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
            .ref-number { font-size: 24px; font-weight: 700; color: #367D8A; letter-spacing: 2px; font-family: "Courier New", monospace; }
            .details-section { background: white; border-radius: 10px; padding: 25px; margin: 25px 0; border: 1px solid #e9ecef; }
            .section-title { font-size: 18px; color: #367D8A; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #367D8A; padding-bottom: 10px; }
            .detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e9ecef; }
            .detail-row:last-child { border-bottom: none; }
            .detail-label { font-weight: 600; color: #6c757d; }
            .detail-value { color: #333; font-weight: 500; }
            .charges-section { background: white; border-radius: 10px; padding: 25px; margin: 25px 0; border: 1px solid #e9ecef; }
            .payment-section { background: white; border-radius: 10px; padding: 25px; margin: 25px 0; border: 1px solid #e9ecef; }
            .total-box { background: linear-gradient(135deg, #367D8A 0%, #285F6B 100%); color: white; padding: 25px; border-radius: 10px; margin: 25px 0; }
            .total-row { display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; }
            .balance-box { background: linear-gradient(135deg, ' . (($data['balance_due'] ?? 0) > 0 ? '#dc3545' : '#28a745') . ' 0%, ' . (($data['balance_due'] ?? 0) > 0 ? '#c82333' : '#218838') . ' 100%); color: white; padding: 20px; border-radius: 10px; margin: 25px 0; text-align: center; }
            .balance-row { font-size: 18px; font-weight: 700; }
            .checkout-info { background-color: #e7f3ff; border-left: 4px solid #367D8A; padding: 20px; margin: 25px 0; border-radius: 0 8px 8px 0; }
            .checkout-info h4 { color: #367D8A; margin-bottom: 10px; }
            .checkout-info p { margin: 5px 0; color: #495057; font-size: 14px; }
            .thank-you { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-radius: 10px; padding: 30px; margin: 25px 0; text-align: center; }
            .thank-you h3 { color: #155724; margin-bottom: 10px; font-size: 22px; }
            .thank-you p { color: #155724; margin: 0; font-size: 14px; }
            .footer { background-color: #2c3e50; color: #ecf0f1; padding: 30px; text-align: center; border-top: 3px solid #367D8A; }
            .footer-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .footer-left { text-align: left; }
            .footer-right { text-align: right; }
            .footer h5 { color: #367D8A; margin-bottom: 10px; font-size: 16px; }
            .footer p { margin: 5px 0; font-size: 13px; opacity: 0.9; }
            .copyright { font-size: 11px; opacity: 0.7; margin-top: 20px; }
            @media (max-width: 600px) {
                .container { margin: 10px; border-radius: 10px; }
                .content { padding: 25px 20px; }
                .footer-content { flex-direction: column; text-align: center; }
                .footer-left, .footer-right { text-align: center; margin-bottom: 15px; }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="logo">Bayawan <span>Bai</span> Hotel</div>
                <p class="tagline">Experience Luxury and Comfort</p>
            </div>

            <div class="receipt-title">
                <h2>OFFICIAL CHECKOUT RECEIPT</h2>
            </div>

            <div class="content">
                <p class="greeting">Dear ' . $guestName . ',</p>

                <p style="font-size: 16px; line-height: 1.7; color: #495057;">
                    Thank you for choosing Bayawan Bai Hotel. We hope you enjoyed your stay. Below is your official checkout receipt.
                </p>

                <div class="booking-ref-box">
                    <div class="ref-label">Booking Reference</div>
                    <div class="ref-number">' . $bookingRef . '</div>
                </div>

                <div class="details-section">
                    <h3 class="section-title">Stay Details</h3>

                    <div class="detail-row">
                        <span class="detail-label">Guest Name</span>
                        <span class="detail-value">' . $guestName . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Room Type</span>
                        <span class="detail-value">' . $roomType . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Room Number</span>
                        <span class="detail-value">' . $roomNumber . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-in Date</span>
                        <span class="detail-value">' . date('F d, Y', strtotime($checkIn)) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Check-out Date</span>
                        <span class="detail-value">' . date('F d, Y', strtotime($checkOut)) . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Number of Nights</span>
                        <span class="detail-value">' . $nights . ' night' . ($nights > 1 ? 's' : '') . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Guests</span>
                        <span class="detail-value">' . $adults . ' Adults, ' . $children . ' Children</span>
                    </div>
                </div>

                <div class="charges-section">
                    <h3 class="section-title">Charges Summary</h3>

                    <div class="detail-row">
                        <span class="detail-label">Room Charges (' . $nights . ' nights)</span>
                        <span class="detail-value">₱' . $roomCharges . '</span>
                    </div>' . $chargesHtml . '
                    <div class="detail-row" style="border-top: 2px solid #e9ecef; margin-top: 10px; padding-top: 15px;">
                        <span class="detail-label" style="font-size: 16px;"><strong>Total Charges</strong></span>
                        <span class="detail-value" style="font-size: 16px;"><strong>₱' . $totalCharges . '</strong></span>
                    </div>
                </div>

                <div class="payment-section">
                    <h3 class="section-title">Payment History</h3>' . $paymentsHtml . '
                    <div class="detail-row" style="border-top: 2px solid #e9ecef; margin-top: 10px; padding-top: 15px;">
                        <span class="detail-label" style="font-size: 16px;"><strong>Total Paid</strong></span>
                        <span class="detail-value" style="font-size: 16px; color: #28a745;"><strong>₱' . $totalPaid . '</strong></span>
                    </div>
                </div>

                <div class="balance-box">
                    <div class="balance-row">
                        <span>' . $balanceText . '</span>
                        <span>₱' . $balanceDue . '</span>
                    </div>
                </div>

                <div class="checkout-info">
                    <h4>Checkout Information</h4>
                    <p><strong>Checkout Date:</strong> ' . $checkoutDate . '</p>
                    <p><strong>Processed by:</strong> ' . $staffName . '</p>
                </div>

                <div class="thank-you">
                    <h3>Thank You for Your Stay!</h3>
                    <p>We appreciate your business and look forward to welcoming you back soon.</p>
                </div>

                <p style="font-size: 14px; color: #6c757d; margin-top: 30px;">
                    If you have any questions about this receipt or need a copy for expense purposes, please contact us at <a href="mailto:' . SMTP_FROM_EMAIL . '" style="color: #367D8A;">' . SMTP_FROM_EMAIL . '</a> or call us at +63 (32) 123-4567.
                </p>
            </div>

            <div class="footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <h5>Contact Information</h5>
                        <p>Bayawan City, Negros Oriental</p>
                        <p>+63 (32) 123-4567</p>
                        <p>' . SMTP_FROM_EMAIL . '</p>
                    </div>
                    <div class="footer-right">
                        <h5>Business Hours</h5>
                        <p>Monday - Friday: 8:00 AM - 8:00 PM</p>
                        <p>Saturday - Sunday: 9:00 AM - 6:00 PM</p>
                        <p>24/7 Front Desk Available</p>
                    </div>
                </div>
                <p class="copyright">
                    © ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.<br>
                    This is an automated receipt. Please do not reply.
                </p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Get plain text version of checkout receipt email
 */
function getCheckoutReceiptPlainText($data) {
    $guestName = $data['guest_name'] ?? 'Valued Guest';
    $bookingRef = $data['booking_ref'] ?? 'N/A';

    $text = '
CHECKOUT RECEIPT - BAYAWAN BAI HOTEL

Dear ' . $guestName . ',

Thank you for choosing Bayawan Bai Hotel. We hope you enjoyed your stay.

BOOKING REFERENCE: ' . $bookingRef . '

STAY DETAILS:
- Guest Name: ' . $guestName . '
- Room Type: ' . ($data['room_type'] ?? 'N/A') . '
- Room Number: ' . ($data['room_number'] ?? 'N/A') . '
- Check-in Date: ' . date('F d, Y', strtotime($data['check_in'] ?? 'today')) . '
- Check-out Date: ' . date('F d, Y', strtotime($data['check_out'] ?? 'today')) . '
- Nights: ' . ($data['nights'] ?? '0') . '
- Guests: ' . ($data['adults'] ?? '0') . ' Adults, ' . ($data['children'] ?? '0') . ' Children

CHARGES SUMMARY:
- Room Charges: ₱' . number_format($data['room_charges'] ?? 0, 2) . '
';

    // Add additional charges
    if (!empty($data['additional_charges'])) {
        foreach ($data['additional_charges'] as $charge) {
            $text .= '- ' . ($charge['description'] ?? 'Charge') . ': ₱' . number_format($charge['amount'] ?? 0, 2) . '\n';
        }
    }

    $text .= '- Total Charges: ₱' . number_format($data['total_charges'] ?? 0, 2) . '

PAYMENT HISTORY:
';

    // Add payments
    if (!empty($data['payments'])) {
        foreach ($data['payments'] as $payment) {
            $text .= '- Payment (' . ($payment['date'] ?? 'N/A') . ') - ' . ($payment['method'] ?? 'Unknown') . ': ₱' . number_format($payment['amount'] ?? 0, 2) . '\n';
        }
    }

    $balanceDue = $data['balance_due'] ?? 0;
    $text .= '- Total Paid: ₱' . number_format($data['total_paid'] ?? 0, 2) . '
';
    $text .= '- Balance: ₱' . number_format($balanceDue, 2) . ' (' . ($balanceDue > 0 ? 'Due' : 'Fully Paid') . ')

';

    $text .= 'CHECKOUT INFORMATION:
- Checkout Date: ' . ($data['checkout_date'] ?? date('F d, Y h:i A')) . '
- Processed by: ' . ($data['staff_name'] ?? 'Staff') . '

Thank you for your stay! We look forward to welcoming you back.

For questions about this receipt, contact us at:
Email: ' . SMTP_FROM_EMAIL . '
Phone: +63 (32) 123-4567

© ' . date('Y') . ' Bayawan Bai Hotel. All rights reserved.
';

    return $text;
}
