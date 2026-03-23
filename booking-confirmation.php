<?php
$pageTitle = 'Booking Confirmed';
require_once 'includes/config.php';

// Check if booking confirmation exists in session
if (!isset($_SESSION['booking_confirmation'])) {
    redirect('index.php');
}

require_once 'includes/header.php';

$booking = $_SESSION['booking_confirmation'];

// Determine status colors and labels
$paymentStatus = $booking['payment_status'] ?? 'pending';
$statusColor = match($paymentStatus) {
    'paid' => '#28a745',
    'partial' => '#ffc107',
    'failed' => '#dc3545',
    default => '#6c757d'
};
$statusBg = match($paymentStatus) {
    'paid' => '#d4edda',
    'partial' => '#fff3cd',
    'failed' => '#f8d7da',
    default => '#e2e3e5'
};
$statusTextColor = match($paymentStatus) {
    'paid' => '#155724',
    'partial' => '#856404',
    'failed' => '#721c24',
    default => '#383d41'
};
$statusLabel = match($paymentStatus) {
    'paid' => 'PAID',
    'partial' => 'PARTIAL PAYMENT',
    'failed' => 'PAYMENT FAILED',
    default => 'PENDING PAYMENT'
};

// Clear the session after displaying
// unset($_SESSION['booking_confirmation']);
?>

<!-- Page Header -->
<div style="background: linear-gradient(135deg, var(--success-color), #1e7e34); padding: 60px 0; text-align: center; color: white;">
    <div class="container">
        <i class="fas fa-check-circle" style="font-size: 80px; margin-bottom: 20px;"></i>
        <h1 style="color: white; font-size: 42px; margin-bottom: 10px;">Booking Confirmed!</h1>
        <p style="font-size: 18px; opacity: 0.9;">Thank you for choosing Bayawan Bai Hotel</p>
    </div>
</div>

<!-- Confirmation Details -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <!-- Success Message -->
            <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 10px; padding: 25px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <i class="fas fa-info-circle" style="font-size: 24px; color: #155724;"></i>
                    <div>
                        <h4 style="color: #155724; margin-bottom: 5px;">What happens next?</h4>
                        <p style="color: #155724; font-size: 14px; margin: 0;">A confirmation email has been sent to your email address. Our team will contact you shortly to confirm your reservation details.</p>
                    </div>
                </div>
            </div>
            
            <!-- Booking Reference Card -->
            <div style="background-color: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); margin-bottom: 30px; overflow: hidden;">
                <div style="background-color: var(--primary-color); color: white; padding: 20px 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <p style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">Booking Reference</p>
                            <h2 style="color: white; font-size: 28px; margin: 0;"><?php echo htmlspecialchars($booking['booking_ref']); ?></h2>
                        </div>
                        <div style="text-align: right;">
                            <span style="background-color: <?php echo $statusBg; ?>; color: <?php echo $statusTextColor; ?>; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600;">
                                <i class="fas fa-<?php echo $paymentStatus === 'paid' || $paymentStatus === 'partial' ? 'check' : 'clock'; ?>"></i> <?php echo $statusLabel; ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="padding: 30px;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px;">
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 8px;">
                            <p style="font-size: 13px; color: #666; margin-bottom: 5px;"><i class="fas fa-calendar-check" style="color: var(--primary-color); margin-right: 8px;"></i>Check-in</p>
                            <p style="font-size: 20px; font-weight: 600; color: var(--dark-color);"><?php echo formatDate($booking['check_in'], 'F d, Y'); ?></p>
                            <p style="font-size: 14px; color: #666;">From 2:00 PM</p>
                        </div>
                        <div style="padding: 20px; background-color: var(--gray-light); border-radius: 8px;">
                            <p style="font-size: 13px; color: #666; margin-bottom: 5px;"><i class="fas fa-calendar-times" style="color: var(--primary-color); margin-right: 8px;"></i>Check-out</p>
                            <p style="font-size: 20px; font-weight: 600; color: var(--dark-color);"><?php echo formatDate($booking['check_out'], 'F d, Y'); ?></p>
                            <p style="font-size: 14px; color: #666;">Until 12:00 PM</p>
                        </div>
                    </div>
                    
                    <div style="border-top: 1px solid var(--gray-light); padding-top: 25px;">
                        <h3 style="font-size: 20px; margin-bottom: 20px;">Reservation Details</h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
                            <div>
                                <p style="font-size: 13px; color: #666; margin-bottom: 5px;">Room Type</p>
                                <p style="font-size: 16px; font-weight: 500;"><?php echo htmlspecialchars($booking['room_name']); ?></p>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #666; margin-bottom: 5px;">Number of Nights</p>
                                <p style="font-size: 16px; font-weight: 500;"><?php echo $booking['nights']; ?> night<?php echo $booking['nights'] > 1 ? 's' : ''; ?></p>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #666; margin-bottom: 5px;">Guests</p>
                                <p style="font-size: 16px; font-weight: 500;"><?php echo $booking['guests']; ?> guest<?php echo $booking['guests'] > 1 ? 's' : ''; ?></p>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #666; margin-bottom: 5px;">Payment Method</p>
                                <p style="font-size: 16px; font-weight: 500;">
                                    <?php 
                                    $paymentMethods = [
                                        'gcash' => 'GCash',
                                        'paypal' => 'PayPal',
                                        'credit_card' => 'Credit Card',
                                        'cash' => 'Pay at Hotel'
                                    ];
                                    echo $paymentMethods[$booking['payment_method']] ?? ucfirst($booking['payment_method']);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div style="border-top: 2px solid var(--gray-light); padding-top: 25px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="font-size: 16px; margin-bottom: 5px;">Total Amount</p>
                                <p style="font-size: 14px; color: #666;">Including all taxes and fees</p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 32px; font-weight: 700; color: var(--primary-color);"><?php echo formatPrice($booking['total']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($booking['amount_paid']) && $booking['amount_paid'] > 0): ?>
                    <div style="border-top: 1px solid var(--gray-light); padding-top: 20px; margin-top: 20px;">
                        <h4 style="font-size: 16px; margin-bottom: 15px; color: var(--dark-color);"><i class="fas fa-receipt" style="color: var(--primary-color); margin-right: 8px;"></i>Payment Details</h4>
                        
                        <?php if (isset($booking['transaction_id'])): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px;">
                            <span style="color: #666;">Transaction ID</span>
                            <span style="font-weight: 600; font-family: monospace;"><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px;">
                            <span style="color: #666;">Amount Paid</span>
                            <span style="font-weight: 600; color: #28a745;"><?php echo formatPrice($booking['amount_paid']); ?></span>
                        </div>
                        
                        <?php if (isset($booking['remaining_amount']) && $booking['remaining_amount'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; padding: 10px; background: #fff3cd; border-radius: 5px;">
                            <span style="color: #856404; font-weight: 600;">Remaining Balance</span>
                            <span style="font-weight: 700; color: #dc3545;"><?php echo formatPrice($booking['remaining_amount']); ?></span>
                        </div>
                        <p style="font-size: 13px; color: #856404; margin-top: 10px;"><i class="fas fa-info-circle"></i> Please settle the remaining balance at check-in.</p>
                        <?php else: ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; padding: 10px; background: #d4edda; border-radius: 5px;">
                            <span style="color: #155724; font-weight: 600;">Payment Status</span>
                            <span style="font-weight: 700; color: #28a745;">FULLY PAID</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                <a href="javascript:window.print()" class="btn btn-outline" style="text-align: center;">
                    <i class="fas fa-print"></i> Print Confirmation
                </a>
                <a href="contact.php" class="btn btn-outline" style="text-align: center;">
                    <i class="fas fa-envelope"></i> Email Support
                </a>
                <a href="/bayawanhotel/user/my-bookings.php" class="btn btn-primary" style="text-align: center;">
                    <i class="fas fa-list"></i> My Bookings
                </a>
            </div>
            
            <!-- Important Information -->
            <div style="background-color: white; border-radius: 10px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                <h3 style="font-size: 20px; margin-bottom: 20px;"><i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 10px;"></i>Important Information</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <h4 style="font-size: 16px; margin-bottom: 10px;"><i class="fas fa-id-card" style="color: var(--primary-color); margin-right: 8px;"></i>Check-in Requirements</h4>
                        <p style="font-size: 14px; color: #666; line-height: 1.6;">Please present a valid ID and this booking confirmation upon check-in. All guests must be registered.</p>
                    </div>
                    <div style="padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <h4 style="font-size: 16px; margin-bottom: 10px;"><i class="fas fa-ban" style="color: var(--primary-color); margin-right: 8px;"></i>Cancellation Policy</h4>
                        <p style="font-size: 14px; color: #666; line-height: 1.6;">Free cancellation up to 48 hours before check-in. Late cancellations may be subject to charges.</p>
                    </div>
                    <div style="padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <h4 style="font-size: 16px; margin-bottom: 10px;"><i class="fas fa-utensils" style="color: var(--primary-color); margin-right: 8px;"></i>Breakfast</h4>
                        <p style="font-size: 14px; color: #666; line-height: 1.6;">Breakfast is included with your stay. Served daily from 6:00 AM to 10:00 AM at Bai Restaurant.</p>
                    </div>
                    <div style="padding: 15px; background-color: var(--gray-light); border-radius: 8px;">
                        <h4 style="font-size: 16px; margin-bottom: 10px;"><i class="fas fa-phone" style="color: var(--primary-color); margin-right: 8px;"></i>Need Help?</h4>
                        <p style="font-size: 14px; color: #666; line-height: 1.6;">Contact our reservations team at <strong>+63 35 123 4567</strong> or email <strong>reservations@bayawanbaihotel.com</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="padding: 60px 0; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));">
    <div class="container" style="text-align: center;">
        <h2 style="color: white; font-size: 32px; margin-bottom: 15px;">Looking Forward to Your Stay!</h2>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; margin-bottom: 30px;">Explore more of what Bayawan Bai Hotel has to offer</p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="amenities.php" class="btn" style="background-color: white; color: var(--primary-color);">Explore Amenities</a>
            <a href="dining.php" class="btn btn-outline" style="border-color: white; color: white;">View Dining</a>
            <a href="location.php" style="color: white; padding: 12px 30px; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-map-marker-alt"></i> View Location
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
