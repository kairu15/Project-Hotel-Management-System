<?php
$pageTitle = 'Forgot Password';
require_once '../includes/config.php';

$error = '';
$success = '';
$step = 'email'; // email, otp, reset

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();

    // Step 1: Send OTP
    if (isset($_POST['send_otp'])) {
        $email = sanitizeInput($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } else {
            // Check if email exists
            $stmt = $db->prepare("SELECT user_id, first_name, last_name FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate and send OTP
                $otp = generateOTP();
                $_SESSION['fp_email'] = $email;
                $_SESSION['fp_otp'] = $otp;
                $_SESSION['fp_otp_time'] = time();
                $_SESSION['fp_otp_attempts'] = 0;

                if (sendOTPEmail($email, $otp)) {
                    $success = 'A verification code has been sent to your email address.';
                    $step = 'otp';
                    logActivity('Password reset OTP sent', 'Email: ' . $email);
                } else {
                    $error = 'Failed to send verification code. Please try again.';
                }
            } else {
                // Don't reveal if email exists or not for security
                $success = 'If this email is registered, a verification code has been sent.';
                $step = 'email'; // Stay on email step but show success
            }
        }
    }

    // Step 2: Verify OTP
    if (isset($_POST['verify_otp'])) {
        $enteredOTP = sanitizeInput($_POST['otp_code'] ?? '');

        if (empty($enteredOTP)) {
            $error = 'Please enter the verification code';
            $step = 'otp';
        } elseif (!isset($_SESSION['fp_otp']) || !isset($_SESSION['fp_email'])) {
            $error = 'Session expired. Please start over.';
            session_destroy();
            $step = 'email';
        } elseif ($_SESSION['fp_otp_attempts'] >= 3) {
            $error = 'Maximum attempts reached. Please request a new code.';
            unset($_SESSION['fp_otp']);
            unset($_SESSION['fp_otp_time']);
            unset($_SESSION['fp_otp_attempts']);
            $step = 'email';
        } elseif (time() - $_SESSION['fp_otp_time'] > 300) {
            $error = 'Verification code has expired. Please request a new one.';
            unset($_SESSION['fp_otp']);
            unset($_SESSION['fp_otp_time']);
            unset($_SESSION['fp_otp_attempts']);
            $step = 'email';
        } elseif ($enteredOTP === $_SESSION['fp_otp']) {
            // OTP verified, proceed to password reset
            $step = 'reset';
            $success = 'Code verified. Please enter your new password.';
        } else {
            $_SESSION['fp_otp_attempts']++;
            $remaining = 3 - $_SESSION['fp_otp_attempts'];
            $error = 'Invalid code. ' . ($remaining > 0 ? $remaining . ' attempts remaining.' : 'Maximum attempts reached.');
            $step = 'otp';
        }
    }

    // Step 3: Reset Password
    if (isset($_POST['reset_password'])) {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!isset($_SESSION['fp_email'])) {
            $error = 'Session expired. Please start over.';
            session_destroy();
            $step = 'email';
        } elseif (empty($password) || empty($confirmPassword)) {
            $error = 'Please enter both password fields';
            $step = 'reset';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
            $step = 'reset';
        } else {
            $validation = validatePassword($password);
            if ($validation !== true) {
                $error = $validation;
                $step = 'reset';
            } else {
                // Update password
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
                if ($stmt->execute([$password, $_SESSION['fp_email']])) {
                    logActivity('Password reset successful', 'Email: ' . $_SESSION['fp_email']);

                    // Clear session data
                    unset($_SESSION['fp_email']);
                    unset($_SESSION['fp_otp']);
                    unset($_SESSION['fp_otp_time']);
                    unset($_SESSION['fp_otp_attempts']);

                    showAlert('Your password has been reset successfully. Please sign in with your new password.', 'success');
                    redirect('../auth/login.php');
                } else {
                    $error = 'Failed to reset password. Please try again.';
                    $step = 'reset';
                }
            }
        }
    }

    // Resend OTP
    if (isset($_POST['resend_otp'])) {
        if (!isset($_SESSION['fp_email'])) {
            $error = 'Session expired. Please start over.';
            $step = 'email';
        } else {
            $otp = generateOTP();
            $_SESSION['fp_otp'] = $otp;
            $_SESSION['fp_otp_time'] = time();
            $_SESSION['fp_otp_attempts'] = 0;

            if (sendOTPEmail($_SESSION['fp_email'], $otp)) {
                $success = 'A new verification code has been sent to your email.';
                $step = 'otp';
            } else {
                $error = 'Failed to resend code. Please try again.';
                $step = 'otp';
            }
        }
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
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            display: flex;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
        }

        .left-panel h1 {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            margin-bottom: 20px;
        }

        .left-panel p {
            font-size: 18px;
            line-height: 1.7;
            opacity: 0.9;
        }

        .right-panel {
            width: 500px;
            background-color: var(--light-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
        }

        .logo {
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

        .logo img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 8px;
        }

        .logo span {
            color: var(--primary-color);
        }

        .form-container h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .form-container > p {
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
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
            padding: 15px 15px 15px 45px;
            border: 2px solid var(--gray-medium);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-control.otp-input {
            padding: 15px;
            text-align: center;
            font-size: 24px;
            letter-spacing: 8px;
            font-weight: 600;
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

        .btn-secondary {
            background-color: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-secondary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
            margin-bottom: 30px;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .password-requirements {
            background-color: var(--gray-light);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin-bottom: 5px;
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .resend-link button {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
        }

        .resend-link button:hover {
            color: var(--secondary-color);
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 10px;
        }

        .step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--gray-medium);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }

        .step.active {
            background-color: var(--primary-color);
            color: white;
        }

        .step.completed {
            background-color: var(--success-color);
            color: white;
        }

        @media (max-width: 992px) {
            .left-panel {
                display: none;
            }

            .right-panel {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .right-panel {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h1>Reset Your Password</h1>
            <p>Don't worry, it happens! Enter your email address and we'll send you a verification code to reset your password securely.</p>
        </div>

        <div class="right-panel">
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Sign In
            </a>

            <div class="logo">
                <img src="../assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo">
                <span>Bayawan <span>Bai</span> Hotel</span>
            </div>

            <div class="form-container">
                <h2>Forgot Password</h2>
                <p><?php echo $step === 'email' ? 'Enter your email to receive a verification code.' : ($step === 'otp' ? 'Enter the verification code sent to your email.' : 'Create a new password for your account.'); ?></p>

                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step <?php echo $step === 'email' ? 'active' : ($step === 'otp' || $step === 'reset' ? 'completed' : ''); ?>">1</div>
                    <div class="step <?php echo $step === 'otp' ? 'active' : ($step === 'reset' ? 'completed' : ''); ?>">2</div>
                    <div class="step <?php echo $step === 'reset' ? 'active' : ''; ?>">3</div>
                </div>

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

                <?php if ($step === 'email'): ?>
                <!-- Step 1: Enter Email -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                    </div>

                    <button type="submit" name="send_otp" class="btn">
                        <i class="fas fa-paper-plane"></i> Send Verification Code
                    </button>
                </form>

                <?php elseif ($step === 'otp'): ?>
                <!-- Step 2: Enter OTP -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Verification Code</label>
                        <input type="text" name="otp_code" class="form-control otp-input" placeholder="000000" maxlength="6" required autocomplete="off">
                    </div>

                    <button type="submit" name="verify_otp" class="btn">
                        <i class="fas fa-check"></i> Verify Code
                    </button>

                    <div class="resend-link">
                        Didn't receive the code? <button type="submit" name="resend_otp">Resend Code</button>
                    </div>
                </form>

                <?php elseif ($step === 'reset'): ?>
                <!-- Step 3: Reset Password -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="input-group" style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary-color); z-index: 1;"></i>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter new password" required style="padding-left: 45px; padding-right: 50px;">
                            <button type="button" onclick="togglePassword('password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 16px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; z-index: 1;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="input-group" style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary-color); z-index: 1;"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm new password" required style="padding-left: 45px; padding-right: 50px;">
                            <button type="button" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary-color); cursor: pointer; font-size: 16px; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; z-index: 1;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li>At least 8 characters long</li>
                            <li>At least one uppercase letter</li>
                            <li>At least one lowercase letter</li>
                            <li>At least one number</li>
                        </ul>
                    </div>

                    <button type="submit" name="reset_password" class="btn" style="margin-top: 20px;">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
