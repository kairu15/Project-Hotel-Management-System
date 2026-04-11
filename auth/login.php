<?php
$pageTitle = 'Sign In';
require_once '../includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    // Check if there's an intended redirect after login
    if (isset($_SESSION['intended_redirect']) && !empty($_SESSION['intended_redirect'])) {
        $redirectUrl = $_SESSION['intended_redirect'];
        unset($_SESSION['intended_action']);
        unset($_SESSION['intended_redirect']);
        unset($_SESSION['intended_action_timestamp']);
        redirect('../' . $redirectUrl);
    }
    redirect('../user/dashboard.php');
}

// Get redirect URL from query parameter if available
$redirectAfterLogin = $_GET['redirect'] ?? null;
if ($redirectAfterLogin) {
    $_SESSION['intended_redirect'] = $redirectAfterLogin;
}

$error = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && $user['password'] === $password) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login and set active status to 1 (online)
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW(), active_status = 1 WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            logActivity('User login', 'User ID: ' . $user['user_id']);

            // Check for intended redirect first (for booking actions)
            if (isset($_SESSION['intended_redirect']) && !empty($_SESSION['intended_redirect'])) {
                $redirectUrl = $_SESSION['intended_redirect'];
                unset($_SESSION['intended_action']);
                unset($_SESSION['intended_redirect']);
                unset($_SESSION['intended_action_timestamp']);
                redirect('../' . $redirectUrl);
            }

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('../admin/admin-dashboard.php');
            } elseif (in_array($user['role'], ['manager', 'receptionist'])) {
                redirect('../staff/staff-dashboard.php');
            } else {
                redirect('../user/dashboard.php');
            }
        } else {
            $error = 'Invalid email or password';
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
        
        .login-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
        }
        
        .login-left h1 {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .login-left p {
            font-size: 18px;
            line-height: 1.7;
            opacity: 0.9;
        }
        
        .login-right {
            width: 500px;
            background-color: var(--light-color);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
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
        
        .login-form h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .login-form > p {
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--gray-medium);
        }
        
        .divider span {
            background-color: white;
            padding: 0 15px;
            position: relative;
            color: #666;
            font-size: 14px;
        }
        
        .social-login {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .social-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--gray-medium);
            background-color: transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: inherit;
        }
        
        .social-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .signup-link {
            text-align: center;
            font-size: 15px;
            color: #666;
        }
        
        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
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
        
        @media (max-width: 992px) {
            .login-left {
                display: none;
            }
            
            .login-right {
                width: 100%;
            }
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
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid var(--gray-medium);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control[type="password"],
        .form-control[type="text"] {
            padding-right: 45px;
        }
        
        @media (max-width: 480px) {
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <h1>Welcome Back!</h1>
            <p>Sign in to access your account, manage your bookings, and enjoy exclusive member benefits at Bayawan Bai Hotel.</p>
        </div>
        
        <div class="login-right">
            <a href="/bayawanhotel/index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <div class="login-logo">
                <img src="../assets/bayawanhotellogo.png" alt="Bayawan Bai Hotel Logo" class="logo-image">
                <span>Bayawan <span>Bai</span> Hotel</span>
            </div>
            
            <div class="login-form">
                <h2>Sign In</h2>
                <p>Please enter your credentials to continue</p>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" class="form-control" placeholder="Enter your password" required id="password">
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
                
                <div class="divider">
                    <span>OR</span>
                </div>
                
                <div class="social-login">
                    <a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?php echo GOOGLE_CLIENT_ID; ?>&redirect_uri=<?php echo urlencode(GOOGLE_REDIRECT_URI); ?>&response_type=code&scope=openid%20email%20profile" class="social-btn" title="Login with Google">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="https://www.facebook.com/v18.0/dialog/oauth?client_id=<?php echo FACEBOOK_APP_ID; ?>&redirect_uri=<?php echo urlencode(FACEBOOK_REDIRECT_URI); ?>&scope=email,public_profile&response_type=code" class="social-btn" title="Login with Facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>
                
                <p class="signup-link">
                    Don't have an account? <a href="/bayawanhotel/auth/register.php">Sign up now</a>
                </p>
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
