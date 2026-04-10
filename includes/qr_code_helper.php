<?php
/**
 * Bayawan Bai Hotel - QR Code Helper Functions
 * Generates QR codes for reference numbers using endroid/qr-code library
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

// Check if GD extension is loaded
if (!extension_loaded('gd')) {
    error_log('QR Code Error: GD extension is not loaded. Please enable it in php.ini');
}

/**
 * Generate a QR code for a reference number
 * 
 * @param string $referenceNumber The reference number to encode
 * @param string $type The type of reference (booking, order, inquiry, etc.)
 * @param int $size The size of the QR code in pixels (default: 150)
 * @return string Base64 encoded PNG image data URL
 */
function generateReferenceQRCode($referenceNumber, $type = 'reference', $size = 150) {
    try {
        // Check GD extension
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is not loaded. Please enable it in php.ini');
        }
        
        $writer = new PngWriter();
        
        // Create QR code data with hotel info and reference
        $qrData = json_encode([
            'type' => $type,
            'reference' => $referenceNumber,
            'hotel' => 'Bayawan Bai Hotel',
            'email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'bayawanbaiminihotel@gmail.com',
            'website' => 'https://bayawanbaihotel.com'
        ], JSON_UNESCAPED_SLASHES);
        
        // Create QR code
        $qrCode = QrCode::create($qrData)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->setSize($size)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(54, 125, 138)) // Hotel primary color
            ->setBackgroundColor(new Color(255, 255, 255));
        
        $result = $writer->write($qrCode);
        
        // Return base64 encoded image
        return 'data:image/png;base64,' . base64_encode($result->getString());
    } catch (Exception $e) {
        error_log('QR Code generation failed: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return '';
    } catch (Error $e) {
        error_log('QR Code generation error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return '';
    }
}

/**
 * Generate a simple QR code for email display
 * 
 * @param string $referenceNumber The reference number to encode
 * @param int $size The size of the QR code (default: 120)
 * @param int|null $bookingId Optional booking ID to generate reference if booking_ref is empty
 * @return string Base64 encoded PNG image data URL
 */
function generateSimpleQRCode($referenceNumber, $size = 120, $bookingId = null) {
    try {
        // Check GD extension
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is not loaded. Please enable it in php.ini');
        }
        
        // If reference is empty or N/A, generate from booking_id
        if (empty($referenceNumber) || $referenceNumber === 'N/A') {
            if ($bookingId) {
                $referenceNumber = 'BBH-' . str_pad($bookingId, 6, '0', STR_PAD_LEFT);
            } else {
                return '';
            }
        }
        
        $writer = new PngWriter();
        
        // Create QR code with just the reference number
        $qrCode = QrCode::create($referenceNumber)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->setSize($size)
            ->setMargin(8)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(54, 125, 138))
            ->setBackgroundColor(new Color(255, 255, 255));
        
        $result = $writer->write($qrCode);
        
        return 'data:image/png;base64,' . base64_encode($result->getString());
    } catch (Exception $e) {
        error_log('Simple QR Code generation failed: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return '';
    } catch (Error $e) {
        error_log('Simple QR Code generation error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return '';
    }
}

/**
 * Get HTML for displaying a QR code with reference number
 * 
 * @param string $referenceNumber The reference number
 * @param string $label Label text above the reference (default: 'Reference')
 * @param int $qrSize QR code size (default: 120)
 * @return string HTML code
 */
function getReferenceQRCodeHTML($referenceNumber, $label = 'Reference', $qrSize = 120) {
    $qrCodeUrl = generateSimpleQRCode($referenceNumber, $qrSize);
    
    if (empty($qrCodeUrl)) {
        return '';
    }
    
    return '
    <div style="text-align: center; margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 10px; border: 2px solid #e9ecef;">
        <div style="font-size: 12px; color: #6c757d; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">' . htmlspecialchars($label) . '</div>
        <div style="font-size: 22px; font-weight: 700; color: #367D8A; letter-spacing: 2px; font-family: \"Courier New\", monospace; margin-bottom: 15px;">' . htmlspecialchars($referenceNumber) . '</div>
        <div style="display: inline-block; padding: 10px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <img src="' . $qrCodeUrl . '" alt="QR Code" style="width: ' . $qrSize . 'px; height: ' . $qrSize . 'px; display: block;">
        </div>
        <div style="font-size: 11px; color: #6c757d; margin-top: 10px;">Scan to verify booking</div>
    </div>';
}

/**
 * Get compact HTML for email templates
 * 
 * @param string $referenceNumber The reference number
 * @param string $type Type of reference (booking, order, inquiry)
 * @return string HTML code for email
 */
function getEmailQRCodeHTML($referenceNumber, $type = 'booking') {
    $qrCodeUrl = generateSimpleQRCode($referenceNumber, 100);
    
    if (empty($qrCodeUrl)) {
        return '';
    }
    
    $labelText = match($type) {
        'booking' => 'Booking Reference',
        'order' => 'Order Reference',
        'inquiry' => 'Inquiry Reference',
        default => 'Reference Number'
    };
    
    return '
    <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #367D8A; border-radius: 15px; padding: 25px; margin: 25px 0; text-align: center;">
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">' . $labelText . '</div>
        <div style="font-size: 26px; font-weight: 700; color: #367D8A; letter-spacing: 3px; font-family: \"Courier New\", monospace; margin-bottom: 20px;">' . htmlspecialchars($referenceNumber) . '</div>
        <div style="display: inline-block; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <img src="' . $qrCodeUrl . '" alt="QR Code" width="100" height="100" style="display: block;">
        </div>
        <div style="font-size: 12px; color: #6c757d; margin-top: 15px;">Scan this QR code to quickly access your ' . $type . ' details</div>
    </div>';
}

/**
 * Generate QR code binary data for email embedding
 * 
 * @param string $referenceNumber The reference number to encode
 * @param int $size The size of the QR code (default: 100)
 * @return string|null Binary PNG data or null on failure
 */
function generateQRCodeBinary($referenceNumber, $size = 100) {
    try {
        // Check GD extension
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is not loaded. Please enable it in php.ini');
        }
        
        $writer = new PngWriter();
        
        $qrCode = QrCode::create($referenceNumber)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->setSize($size)
            ->setMargin(8)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(54, 125, 138))
            ->setBackgroundColor(new Color(255, 255, 255));
        
        $result = $writer->write($qrCode);
        
        return $result->getString();
    } catch (Exception $e) {
        error_log('QR Code binary generation failed: ' . $e->getMessage());
        return null;
    } catch (Error $e) {
        error_log('QR Code binary generation error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get compact HTML for email templates with CID reference
 * 
 * @param string $referenceNumber The reference number
 * @param string $type Type of reference (booking, order, inquiry)
 * @param string $cid The Content-ID for the embedded image
 * @return string HTML code for email
 */
function getEmailQRCodeHTMLWithCID($referenceNumber, $type = 'booking', $cid = 'qrcode') {
    if (empty($referenceNumber)) {
        return '';
    }
    
    $labelText = match($type) {
        'booking' => 'Booking Reference',
        'order' => 'Order Reference',
        'inquiry' => 'Inquiry Reference',
        default => 'Reference Number'
    };
    
    return '
    <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px solid #367D8A; border-radius: 15px; padding: 25px; margin: 25px 0; text-align: center;">
        <div style="font-size: 13px; color: #6c757d; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">' . $labelText . '</div>
        <div style="font-size: 26px; font-weight: 700; color: #367D8A; letter-spacing: 3px; font-family: "Courier New", monospace; margin-bottom: 20px;">' . htmlspecialchars($referenceNumber) . '</div>
        <div style="display: inline-block; padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <img src="cid:' . $cid . '" alt="QR Code" width="100" height="100" style="display: block;">
        </div>
        <div style="font-size: 12px; color: #6c757d; margin-top: 15px;">Scan this QR code to quickly access your ' . $type . ' details</div>
    </div>';
}
