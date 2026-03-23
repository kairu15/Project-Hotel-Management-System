<?php
$pageTitle = 'Create Account';
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../user/dashboard.php');
}

$error = '';
$success = '';
$showOTP = false;
$otpSent = false;

// Initialize registration data in session if not exists
if (!isset($_SESSION['registration_data'])) {
    $_SESSION['registration_data'] = [];
}

// Process registration form (first step)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName = sanitizeInput($_POST['last_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } else {
        $db = getDB();
        
        // Check if email already exists
        $checkStmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'Email address is already registered';
        } else {
            // Store registration data in session
            $_SESSION['registration_data'] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'password' => $password
            ];
            
            // Generate and send OTP
            $otp = generateOTP();
            $_SESSION['otp_code'] = $otp;
            $_SESSION['otp_time'] = time();
            $_SESSION['otp_attempts'] = 0;
            
            if (sendOTPEmail($email, $otp)) {
                $showOTP = true;
                $otpSent = true;
                $success = 'OTP code has been sent to your email address';
            } else {
                $error = 'Failed to send OTP code. Please try again.';
                // Clear registration data on failure
                unset($_SESSION['registration_data']);
            }
        }
    }
}

// Process OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    $enteredOTP = sanitizeInput($_POST['otp_code'] ?? '');
    
    // Check OTP attempts
    if ($_SESSION['otp_attempts'] >= 3) {
        $error = 'Maximum OTP attempts reached. Please start over.';
        unset($_SESSION['registration_data']);
        unset($_SESSION['otp_code']);
        unset($_SESSION['otp_time']);
        $showOTP = false;
    } elseif (empty($enteredOTP)) {
        $error = 'Please enter the OTP code';
    } else {
        // Check if OTP is expired (5 minutes)
        if (time() - $_SESSION['otp_time'] > 300) {
            $error = 'OTP code has expired. Please request a new one.';
            unset($_SESSION['otp_code']);
            unset($_SESSION['registration_data']);
            $showOTP = false;
        } elseif ($enteredOTP === $_SESSION['otp_code']) {
            // OTP is correct, create user account
            $data = $_SESSION['registration_data'];
            $db = getDB();
            
            $insertStmt = $db->prepare("INSERT INTO users (email, password, first_name, last_name, phone, role, status) VALUES (?, ?, ?, ?, ?, 'guest', 'active')");
            
            if ($insertStmt->execute([$data['email'], $data['password'], $data['first_name'], $data['last_name'], $data['phone']])) {
                $success = 'Account created successfully!';
                logActivity('New user registration', 'Email: ' . $data['email']);
                
                // Clear session data
                unset($_SESSION['registration_data']);
                unset($_SESSION['otp_code']);
                unset($_SESSION['otp_time']);
                unset($_SESSION['otp_attempts']);
                $showOTP = false;
                
                // Set redirect flag
                $_SESSION['redirect_to_login'] = true;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        } else {
            $_SESSION['otp_attempts']++;
            $remaining = 3 - $_SESSION['otp_attempts'];
            $error = 'Invalid OTP code. ' . ($remaining > 0 ? $remaining . ' attempts remaining.' : 'Maximum attempts reached.');
        }
    }
}

// Process resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
    if (!empty($_SESSION['registration_data'])) {
        $email = $_SESSION['registration_data']['email'];
        
        // Generate new OTP
        $otp = generateOTP();
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_time'] = time();
        $_SESSION['otp_attempts'] = 0;
        
        if (sendOTPEmail($email, $otp)) {
            $success = 'New OTP code has been sent to your email address';
            $showOTP = true;
        } else {
            $error = 'Failed to resend OTP code. Please try again.';
        }
    } else {
        $error = 'Session expired. Please start over.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo SITE_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/bayawanhotellogo.png">
    <link rel="shortcut icon" type="image/png" href="../assets/bayawanhotellogo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #367D8A;
            --secondary-color: #285F6B;
            --dark-color: #133336;
            --light-color: #FFFFFF;
            --text-color: #010001;
            --gray-light: #F5F5F5;
            --gray-medium: #E0E0E0;
            --success-color: #28a745;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .register-container {
            background-color: var(--light-color);
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 550px;
            padding: 50px;
        }
        
        .login-logo {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .logo-image {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .login-logo span {
            color: var(--primary-color);
        }
        
        .register-form h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--dark-color);
            text-align: center;
        }
        
        .register-form > p {
            color: #666;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: #dc3545;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid var(--gray-medium);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background-color: var(--gray-medium);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }
        
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .terms input {
            margin-top: 3px;
        }
        
        .terms a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 15px;
            color: #666;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .benefits {
            background-color: var(--gray-light);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .benefits h4 {
            font-size: 16px;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .benefits ul {
            list-style: none;
        }
        
        .benefits li {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .benefits li i {
            color: var(--success-color);
        }
        
        .toggle-password {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 16px;
            padding: 5px;
        }
        
        .toggle-password:hover {
            color: var(--secondary-color);
        }
        
        .form-control[type="password"],
        .form-control[type="text"] {
        }
        
        @media (max-width: 576px) {
            .register-container {
                padding: 30px 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="/bayawanhotel/index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        
        <div class="login-logo">
            <img src="../assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo-image">
            <span>Bayawan <span>Bai</span> Hotel</span>
        </div>
        
        <div class="register-form">
            <h2>Create Your Account</h2>
            <p>Join us for exclusive benefits and faster booking</p>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['redirect_to_login']) && $_SESSION['redirect_to_login']): ?>
            <!-- Loading Modal -->
            <div id="loadingModal" class="loading-modal">
                <div class="loading-content">
                    <div class="loading-spinner"></div>
                    <h3>Registration Successful!</h3>
                    <p>Redirecting to login page...</p>
                    <div class="countdown-timer">
                        <span id="countdown">3</span>
                    </div>
                </div>
            </div>
            
            <script>
                // Countdown timer
                let countdown = 3;
                const countdownElement = document.getElementById('countdown');
                
                const countdownInterval = setInterval(function() {
                    countdown--;
                    countdownElement.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(countdownInterval);
                        window.location.href = 'login.php';
                    }
                }, 1000);
                
                // Show modal immediately
                document.getElementById('loadingModal').style.display = 'flex';
            </script>
            
            <style>
                .loading-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    display: none;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                    backdrop-filter: blur(5px);
                }
                
                .loading-content {
                    background: white;
                    padding: 40px 50px;
                    border-radius: 20px;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    min-width: 350px;
                    animation: slideIn 0.5s ease-out;
                }
                
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-30px) scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                
                .loading-spinner {
                    width: 60px;
                    height: 60px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid var(--primary-color);
                    border-radius: 50%;
                    margin: 0 auto 20px;
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .loading-content h3 {
                    color: var(--primary-color);
                    margin-bottom: 10px;
                    font-size: 24px;
                    font-weight: 600;
                }
                
                .loading-content p {
                    color: #666;
                    margin-bottom: 20px;
                    font-size: 16px;
                }
                
                .countdown-timer {
                    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                    color: white;
                    width: 60px;
                    height: 60px;
                    border-radius: 50%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    margin: 0 auto;
                    font-size: 24px;
                    font-weight: bold;
                    box-shadow: 0 5px 15px rgba(54,125,138,0.3);
                    animation: pulse 1s ease-in-out infinite;
                }
                
                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }
            </style>
            
            <?php 
                // Clear the redirect flag
                unset($_SESSION['redirect_to_login']);
            endif; 
            ?>
            
                        
            <?php if ($showOTP): ?>
            <!-- OTP Verification Form -->
            <div class="otp-verification">
                <h3 style="text-align: center;"><i class="fas fa-shield-alt" style="color: var(--primary-color);"></i> Verify Your Email</h3>
                <p style="text-align: center;">We've sent a 6-digit OTP code to your email address:</p>
                <p style="text-align: center;"><span class="email-highlight"><?php echo htmlspecialchars($_SESSION['registration_data']['email'] ?? ''); ?></span></p>
                <p style="text-align: center;">Please enter it below to complete your registration.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="verify">
                    
                    <div class="form-group">
                        <label>Enter OTP Code <span class="required">*</span></label>
                        <div class="otp-input-group">
                            <input type="text" name="otp_code" class="form-control otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                        </div>
                        <p class="otp-hint">Enter the 6-digit code sent to your email</p>
                    </div>
                    
                    <div class="otp-actions">
                        <button type="submit" class="btn">
                            <i class="fas fa-check"></i> Verify Code
                        </button>
                    </div>
                </form>
                
                <form method="POST" action="" style="margin-top: 15px;">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('A new OTP code will be sent to your email. Continue?')" style="width: 100%;">
                        <i class="fas fa-redo"></i> Resend Code
                    </button>
                </form>
            </div>
            
            <style>
                .otp-verification {
                    background: #f8f9fa;
                    padding: 30px;
                    border-radius: 10px;
                    margin: 20px 0;
                    text-align: center;
                }
                
                .otp-input-group {
                    max-width: 200px;
                    margin: 0 auto;
                    text-align: center;
                }
                
                .otp-input {
                    font-size: 24px;
                    font-weight: bold;
                    text-align: center;
                    letter-spacing: 8px;
                    padding: 15px;
                    border: 2px solid var(--primary-color);
                    border-radius: 8px;
                    width: 100%;
                    box-sizing: border-box;
                }
                
                .email-highlight {
                    background-color: #e3f2fd;
                    color: var(--primary-color);
                    font-weight: 600;
                    padding: 4px 8px;
                    border-radius: 4px;
                    border: 1px solid var(--primary-color);
                    display: inline-block;
                }
                
                .otp-hint {
                    color: #666;
                    font-size: 14px;
                    margin-top: 10px;
                }
                
                .otp-actions {
                    display: flex;
                    gap: 15px;
                    justify-content: center;
                    margin-top: 25px;
                }
                
                .otp-actions .btn {
                    min-width: 150px;
                    transition: all 0.3s ease;
                }
                
                @media (max-width: 576px) {
                    .otp-actions {
                        flex-direction: column;
                    }
                    
                    .otp-actions .btn {
                        width: 100%;
                    }
                }
            </style>
            
            <script>
                // Auto-focus OTP input
                document.addEventListener('DOMContentLoaded', function() {
                    const otpInput = document.querySelector('.otp-input');
                    if (otpInput) {
                        otpInput.focus();
                        
                        // Only allow numbers
                        otpInput.addEventListener('input', function(e) {
                            this.value = this.value.replace(/[^0-9]/g, '');
                            
                            // Highlight verify button when 6 digits entered
                            if (this.value.length === 6) {
                                const verifyBtn = document.querySelector('button[type="submit"]:not([name="action"])');
                                if (verifyBtn) {
                                    verifyBtn.style.backgroundColor = '#28a745';
                                    verifyBtn.style.transform = 'scale(1.05)';
                                }
                            } else {
                                const verifyBtn = document.querySelector('button[type="submit"]:not([name="action"])');
                                if (verifyBtn) {
                                    verifyBtn.style.backgroundColor = '';
                                    verifyBtn.style.transform = '';
                                }
                            }
                        });
                    }
                });
            </script>
            <?php else: ?>
            <!-- Registration Form -->
            <form method="POST" action="">
                <input type="hidden" name="action" value="register">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="first_name" class="form-control" placeholder="First name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="last_name" class="form-control" placeholder="Last name" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address <span class="required">*</span></label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" class="form-control" placeholder="Phone number">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="Create password" required id="password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <p class="password-hint">Min 8 characters with letters and numbers</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm password" required id="confirm_password">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <label class="terms">
                    <input type="checkbox" name="agree_terms" required>
                    <span>I agree to the <a href="/bayawanhotel/terms.php" target="_blank">Terms of Service</a> and <a href="/bayawanhotel/privacy.php" target="_blank">Privacy Policy</a></span>
                </label>
                
                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <?php endif; ?>
            
            <p class="login-link">
                Already have an account? <a href="/bayawanhotel/auth/login.php">Sign in</a>
            </p>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;
            
            if (password.length >= 8) strength += 25;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            if (password.match(/[0-9]/)) strength += 25;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.backgroundColor = '#dc3545';
            } else if (strength <= 50) {
                strengthBar.style.backgroundColor = '#ffc107';
            } else if (strength <= 75) {
                strengthBar.style.backgroundColor = '#17a2b8';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
            }
        });
        
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
