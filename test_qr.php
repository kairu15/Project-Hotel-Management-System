<?php
/**
 * QR Code Test Script
 * Run this to check if QR code generation is working
 */

require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

echo "<h1>QR Code Test</h1>";

// Check GD extension
echo "<h2>System Check</h2>";
echo "GD Extension: " . (extension_loaded('gd') ? "✓ Loaded" : "✗ NOT LOADED - Please enable in php.ini") . "<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test QR Code generation
echo "<h2>QR Code Generation Test</h2>";

try {
    $writer = new PngWriter();
    
    $qrCode = QrCode::create('TEST-12345')
        ->setEncoding(new Encoding('UTF-8'))
        ->setErrorCorrectionLevel(ErrorCorrectionLevel::Medium)
        ->setSize(150)
        ->setMargin(10)
        ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
        ->setForegroundColor(new Color(54, 125, 138))
        ->setBackgroundColor(new Color(255, 255, 255));
    
    $result = $writer->write($qrCode);
    
    $base64Image = 'data:image/png;base64,' . base64_encode($result->getString());
    
    echo "<p style='color: green;'>✓ QR Code generated successfully!</p>";
    echo "<p>Base64 length: " . strlen($base64Image) . " characters</p>";
    echo "<img src=\"$base64Image\" alt=\"Test QR Code\" style=\"border: 1px solid #ccc;\">";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Class Existence Check</h2>";
echo "PngWriter exists: " . (class_exists('Endroid\QrCode\Writer\PngWriter') ? "✓ Yes" : "✗ No") . "<br>";
echo "QrCode exists: " . (class_exists('Endroid\QrCode\QrCode') ? "✓ Yes" : "✗ No") . "<br>";
echo "Encoding exists: " . (class_exists('Endroid\QrCode\Encoding\Encoding') ? "✓ Yes" : "✗ No") . "<br>";
echo "ErrorCorrectionLevel exists: " . (enum_exists('Endroid\QrCode\ErrorCorrectionLevel') ? "✓ Yes" : "✗ No") . "<br>";
echo "Color exists: " . (class_exists('Endroid\QrCode\Color\Color') ? "✓ Yes" : "✗ No") . "<br>";
echo "RoundBlockSizeMode exists: " . (enum_exists('Endroid\QrCode\RoundBlockSizeMode') ? "✓ Yes" : "✗ No") . "<br>";
