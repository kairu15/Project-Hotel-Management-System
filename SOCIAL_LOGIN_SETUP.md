# Google & Facebook Login Integration - Complete Setup Guide

## Overview
This guide walks you through integrating Google and Facebook login into your Bayawan Hotel login system.

**Time needed:** ~30-45 minutes total  
**Cost:** FREE (both Google and Facebook free tier)

---

## PART 1: GOOGLE OAUTH SETUP (15 minutes)

### Step 1.1: Go to Google Cloud Console

1. Open: **https://console.cloud.google.com/**
2. You may need to sign in with your Google account
3. Click the blue **"SELECT A PROJECT"** button at the top

### Step 1.2: Create New Project

1. In the dropdown, click **"NEW PROJECT"**
2. Enter project name: `Bayawan Hotel`
3. Click **"CREATE"**
4. Wait 1-2 minutes for project to be created
5. Select the new project from the dropdown

### Step 1.3: Enable Google+ API

1. In left sidebar, click **"APIs & Services"** → **"Library"**
2. Search for: `Google+ API`
3. Click on "Google+ API"
4. Click blue **"ENABLE"** button
5. Wait for it to enable

### Step 1.4: Configure OAuth Consent Screen (MUST DO FIRST!)

1. In left sidebar, click **"APIs & Services"** → **"OAuth consent screen"**
2. You'll see: **"Google Auth Platform not configured yet"**
3. Click the blue **"Get started"** button
4. You'll enter the **Project configuration** wizard with 4 steps:

**Step 1: App Information**
   - **App name**: `Bayawan Hotel`
   - **User support email**: `bayawanbaiminihotel@gmail.com`
   - Click **"NEXT"** or move to step 2

**Step 2: Audience**
   - This shows who can use your app
   - Keep defaults (External access)
   - Click **"NEXT"** or move to step 3

**Step 3: Contact Information**
   - **Developer contact information**: Your email
   - Click **"NEXT"** or move to step 4

**Step 4: Finish**
   - Review your information
   - Click **"FINISH"** or **"SAVE"**

✅ **OAuth consent screen is now configured!**

### Step 1.5: Create OAuth Client ID

1. Now in left sidebar, click **"APIs & Services"** → **"Credentials"**
2. Click **"+ CREATE CREDENTIALS"** button
3. Select: **"OAuth client ID"**
4. Choose: **"Web application"**

### Step 1.6: Fill OAuth Application Details

1. Name it: `Bayawan Hotel Web App`
2. Under "Authorized JavaScript origins" add:
   ```
   http://localhost
   http://localhost:80
   http://localhost/bayawanhotel
   ```
3. Under "Authorized redirect URIs" add:
   ```
   http://localhost/bayawanhotel/auth/google-callback.php
   ```
4. Click **"CREATE"**

### Step 1.7: Copy Your Google Credentials

A popup will show your credentials:
- **Client ID** (big number)
- **Client Secret** (code)

This is your ONLY chance to copy the Client Secret! Copy both now and save them safely.

📋 **Copy and save these somewhere safe:**
```
GOOGLE_CLIENT_ID: [1032689699648-839tkitlfhjr0jt1tdd78fabpujqi42o.apps.googleusercontent.com]
GOOGLE_CLIENT_SECRET: [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]
```

---

## PART 2: FACEBOOK APP SETUP (15 minutes)

### Step 2.1: Go to Facebook Developers

1. Open: **https://developers.facebook.com/**
2. Click **"Get Started"**
3. Sign in with your Facebook account (create one if needed)

### Step 2.2: Create New App

1. Click **"My Apps"** (top right)
2. Click **"Create App"**
3. Choose app type: **"Consumer"**
4. Click **"Next"**

### Step 2.3: Fill App Details

After clicking **"Create App"**, you'll be shown the **App details** configuration screen:

1. **App name** (already filled): `Bayawan Hotel`
2. **App contact email** (already filled): `bayawanbaiminihotel@gmail.com`
3. **Use cases**: Select appropriate categories for your app (optional, you can skip)
4. **Business**: Leave as is
5. **Requirements**: Review and accept
6. **Overview**: Review your app info
7. Click **"NEXT"** or **"COMPLETE"** to continue (button may vary)

✅ **Your Facebook app is created!**

### Step 2.4: Add Facebook Login Product

After your app is created, you'll be on the **app dashboard**. Look for **Facebook Login** in one of these ways:

**Option 1: Products section**
1. In the left sidebar, look for **"Products"**
2. Scroll down to find **"Facebook Login"**
3. Click on it

**Option 2: Settings**
1. Go to **"Settings"** → **"Basic"**
2. Copy your **App ID** and **App Secret** (do this first)
3. Continue to Step 2.5

**If you don't see Facebook Login:**
1. Go to **Settings** → **Basic**
2. You should see your **App ID** and **App Secret** here
3. Continue to Step 2.5 directly (Facebook Login is automatically available)

### Step 2.5: Configure Facebook Login Settings

1. Go to **Settings** → **"Basic"**
   - You'll see your **App ID** (big number) - Copy this
   - You'll see your **App Secret** (code) - Copy this carefully

📋 **Save these immediately:**
```
FACEBOOK_APP_ID: [1503665758050914]
FACEBOOK_APP_SECRET: [e49d1a39f4ed2e5fcffbaa3287dd7f81]
```

2. On the same **Settings → Basic** page, scroll down to **"App Domains"**:
   - Add: `localhost`
   - Click **"Save Changes"**

3. Find **"Facebook Login"** settings:
   - Go to **Settings** → **"Basic"** (or left sidebar if available)
   - Look for **"Valid OAuth Redirect URIs"** section
   - Add this URL:
   ```
   http://localhost/bayawanhotel/auth/facebook-callback.php
   ```
   - Click **"Save Changes"**

✅ **Your Facebook app is now configured!**

---

## PART 3: UPDATE BAYAWAN HOTEL CONFIG (5 minutes)

### Step 3.1: Add Social Credentials to Config

Edit file: `includes/config.php`

Add these lines after the Gemini API configuration (around line 32):

```php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', 'http://localhost/bayawanhotel/auth/google-callback.php');

// Facebook OAuth Configuration
define('FACEBOOK_APP_ID', 'YOUR_FACEBOOK_APP_ID_HERE');
define('FACEBOOK_APP_SECRET', 'YOUR_FACEBOOK_APP_SECRET_HERE');
define('FACEBOOK_REDIRECT_URI', 'http://localhost/bayawanhotel/auth/facebook-callback.php');
```

### Step 3.2: Replace with Your Credentials

Replace the placeholders with actual values from Step 1.7 and 2.5:

**Example:**
```php
define('GOOGLE_CLIENT_ID', '123456789-abcdefghijk.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-abc123def456');
define('FACEBOOK_APP_ID', '1234567890');
define('FACEBOOK_APP_SECRET', 'abc123def456ghi789');
```

---

## PART 4: CREATE CALLBACK HANDLER FILES (10 minutes)

### Step 4.1: Create Google Callback Handler

Create new file: `auth/google-callback.php`

```php
<?php
/**
 * Google OAuth Callback Handler
 */

require_once __DIR__ . '/../includes/config.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    die('Authorization code not received from Google');
}

$code = $_GET['code'];
$db = getDB();

try {
    // Exchange code for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        die('Failed to get access token from Google');
    }
    
    $tokenData = json_decode($response, true);
    $accessToken = $tokenData['access_token'];
    
    // Get user info from Google
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($response, true);
    
    if (!isset($userInfo['email'])) {
        die('Failed to get user information from Google');
    }
    
    // Handle user login/registration
    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? 'Google User';
    $googleId = $userInfo['id'];
    $profilePic = $userInfo['picture'] ?? null;
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, google_id, profile_picture, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $name,
            $email,
            hash('sha256', 'social_login'), // Dummy password
            $googleId,
            $profilePic
        ]);
        
        $userId = $db->lastInsertId();
    } else {
        $userId = $user['user_id'];
        
        // Update google_id if not set
        if (empty($user['google_id'])) {
            $stmt = $db->prepare("UPDATE users SET google_id = ? WHERE user_id = ?");
            $stmt->execute([$googleId, $userId]);
        }
    }
    
    // Set session and redirect
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = empty($user) ? 'user' : $user['role'];
    
    header('Location: ../../index.php');
    exit;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
```

### Step 4.2: Create Facebook Callback Handler

Create new file: `auth/facebook-callback.php`

```php
<?php
/**
 * Facebook OAuth Callback Handler
 */

require_once __DIR__ . '/../includes/config.php';

// Check for authorization code
if (!isset($_GET['code'])) {
    die('Authorization code not received from Facebook');
}

$code = $_GET['code'];
$db = getDB();

try {
    // Exchange code for access token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/v18.0/oauth/access_token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'redirect_uri' => FACEBOOK_REDIRECT_URI,
        'code' => $code
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $tokenData = json_decode($response, true);
    
    if (!isset($tokenData['access_token'])) {
        die('Failed to get access token from Facebook');
    }
    
    $accessToken = $tokenData['access_token'];
    
    // Get user info from Facebook
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/me?fields=id,name,email,picture&access_token=' . urlencode($accessToken));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $userInfo = json_decode($response, true);
    
    if (!isset($userInfo['email'])) {
        die('Failed to get user information from Facebook');
    }
    
    // Handle user login/registration
    $email = $userInfo['email'];
    $name = $userInfo['name'] ?? 'Facebook User';
    $facebookId = $userInfo['id'];
    $profilePic = $userInfo['picture']['data']['url'] ?? null;
    
    // Check if user exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create new user
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, facebook_id, profile_picture, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $name,
            $email,
            hash('sha256', 'social_login'), // Dummy password
            $facebookId,
            $profilePic
        ]);
        
        $userId = $db->lastInsertId();
    } else {
        $userId = $user['user_id'];
        
        // Update facebook_id if not set
        if (empty($user['facebook_id'])) {
            $stmt = $db->prepare("UPDATE users SET facebook_id = ? WHERE user_id = ?");
            $stmt->execute([$facebookId, $userId]);
        }
    }
    
    // Set session and redirect
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role'] = empty($user) ? 'user' : $user['role'];
    
    header('Location: ../../index.php');
    exit;
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
```

---

## PART 5: UPDATE LOGIN PAGE (5 minutes)

### Step 5.1: Find Current Login Buttons

Open: `auth/login.php`

Look for the social login section (with Google and Facebook buttons).

### Step 5.2: Replace Google Button

Find this section:
```html
<button class="social-btn" onclick="alert('Google login coming soon!')">
    <i class="fab fa-google"></i> Google
</button>
```

Replace with:
```html
<a href="https://accounts.google.com/o/oauth2/v2/auth?client_id=<?php echo GOOGLE_CLIENT_ID; ?>&redirect_uri=<?php echo urlencode(GOOGLE_REDIRECT_URI); ?>&response_type=code&scope=openid%20email%20profile" class="social-btn" title="Login with Google">
    <i class="fab fa-google"></i> Google
</a>
```

### Step 5.3: Replace Facebook Button

Find this section:
```html
<button class="social-btn" onclick="alert('Facebook login coming soon!')">
    <i class="fab fa-facebook"></i> Facebook
</button>
```

Replace with:
```html
<a href="https://www.facebook.com/v18.0/dialog/oauth?client_id=<?php echo FACEBOOK_APP_ID; ?>&redirect_uri=<?php echo urlencode(FACEBOOK_REDIRECT_URI); ?>&scope=email,public_profile&response_type=code" class="social-btn" title="Login with Facebook">
    <i class="fab fa-facebook"></i> Facebook
</a>
```

---

## PART 6: UPDATE DATABASE SCHEMA (2 minutes)

### Step 6.1: Add Social Login Columns

The users table needs two new columns. Run this in phpMyAdmin:

```sql
ALTER TABLE users 
ADD COLUMN google_id VARCHAR(255) NULL UNIQUE,
ADD COLUMN facebook_id VARCHAR(255) NULL UNIQUE;
```

---

## PART 7: TEST YOUR SETUP (5 minutes)

### Step 7.1: Test Google Login

1. Go to login page: `http://localhost/bayawanhotel/auth/login.php`
2. Click **"Google"** button
3. Sign in with your Google account
4. You should be redirected back and logged in
5. Check your name is displayed

### Step 7.2: Test Facebook Login

1. Go to login page again
2. Click **"Facebook"** button
3. Sign in with your Facebook account (or create test account)
4. You should be redirected back and logged in

### Step 7.3: Verify Database

1. Open phpMyAdmin
2. Check `users` table
3. New user columns should have `google_id` or `facebook_id` filled

---

## TROUBLESHOOTING

### "Authorization code not received"
- **Cause**: Redirect URI mismatch
- **Fix**: Check URLs in Google/Facebook settings match exactly

### "Failed to get access token"
- **Cause**: Invalid Client ID or Secret
- **Fix**: Copy credentials again carefully (no extra spaces!)

### "User not being created"
- **Cause**: Database columns missing
- **Fix**: Run Part 6 SQL script

### "Buttons not working"
- **Cause**: Config constants not defined
- **Fix**: Check Part 3 - all credentials added to config.php

### "Redirects to login page again"
- **Cause**: Session not being set
- **Fix**: Check database columns exist (Part 6)

---

## SECURITY NOTES

⚠️ **Important:**
1. Never share your Client Secret or App Secret
2. Keep config.php safe (don't upload to public repo)
3. Use HTTPS in production (not just localhost)
4. Test with Facebook test account first (more private)

---

## NEXT STEPS (Optional)

1. **Add profile picture display** - Show user's Google/Facebook pic
2. **Link multiple accounts** - Allow user to connect Google AND Facebook
3. **Add logout** - Already works, users can log out normally
4. **Add email verification** - For social logins, trust provider's verification

---

## Files Modified/Created

| File | Action | Purpose |
|------|--------|---------|
| `includes/config.php` | Modified | Added social credentials |
| `auth/google-callback.php` | Created | Handle Google login |
| `auth/facebook-callback.php` | Created | Handle Facebook login |
| `auth/login.php` | Modified | Added social buttons |
| Database | Modified | Added google_id, facebook_id |

---

## Summary

✅ **Now users can login via:**
- Traditional email/password
- Google account
- Facebook account

✅ **All three methods create same user record**

✅ **Automatic user creation on first social login**

---

**Questions?** Check the troubleshooting section or verify each step carefully!

**Ready?** Follow these steps in order. Should take ~45 minutes total.
