# GEMINI SETUP - COMPLETE Step-by-Step Guide

## ✅ What's Already Done
- ✅ Chatbot API integrated with Gemini 
- ✅ Config file ready for API key
- ✅ Test verification script created

## 📋 Your Next Steps (3 Easy Steps)

---

## STEP 1: Get Your Free API Key (2 minutes)

### 1a. Open Google AI Studio
- Go to: **https://aistudio.google.com/app/apikey**
- (Link is also in config.php line 30 as a comment)

### 1b. Create API Key
- You'll see a blue button: **"Create API Key"** or **"Get API Key"**
- Click it
- Select: **"Create API Key in new Google Cloud project"**
- A dialog will appear with your new API key

### 1c. Copy Your Key
- Look for a long string starting with `AIzaSy...`
- Click the **copy icon** next to it
- **Keep it safe!** (Don't share publicly)

**Example of what it looks like:**
```
AIzaSyD1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q
```

---

## STEP 2: Add Key to Config File (1 minute)

### 2a. Open config.php
- File path: `includes/config.php`
- Line 30 is where you need to edit

### 2b. Find This Line (Line 30)
```php
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
```

### 2c. Replace with Your Key
**BEFORE:**
```php
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
```

**AFTER (example):**
```php
define('GEMINI_API_KEY', 'AIzaSyD1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o6p7q');
```

### 2d. Save the File
- Press `Ctrl+S` to save

---

## STEP 3: Verify & Test (1 minute)

### 3a. Test Your Setup
Open this URL in your browser:
```
http://localhost/bayawanhotel/test-gemini.php
```

### 3b. Expected Results
You should see:
```
✅ PASS: API key is configured
✅ PASS: Database connection successful
✅ PASS: Table 'chat_sessions' exists
✅ PASS: Table 'chat_messages' exists
✅ PASS: Gemini API responded successfully!

RESPONSE:
[AI-generated response to test message]

✅ VERIFICATION COMPLETE
```

### 3c. Troubleshooting Test Results

**If you see ❌ FAIL: API key not configured**
- Make sure you edited config.php correctly
- Check there are no extra spaces
- Save the file
- Refresh the page

**If you see ❌ API Error**
- Your API key may be invalid
- Get a new one from Google AI Studio
- Double-check you copied it correctly
- Make sure it starts with `AIzaSy`

**If you see ⚠️ Table not found**
- Run `database/database.sql` in phpMyAdmin
- This sets up the chat tables

**If response is slow (>10 seconds)**
- This is normal first time (caches on subsequent calls)
- Network or API queue issue
- Try again in a moment

---

## STEP 4: Use the Chatbot! (0 minutes - just test!)

### 4a. Log In
- Go to: http://localhost/bayawanhotel/
- Log in with your hotel account (or register)

### 4b. Open Chatbot
- Look for a **chat icon** or **comments icon** (bottom right)
- Click it to open the chatbot window

### 4c. Send a Test Message
Try any of these:
- `"What rooms do you have?"`
- `"Tell me about the amenities"`
- `"How do I make a booking?"`
- `"What's your restaurant hours?"`
- Or ask **anything** about the hotel!

### 4d. You Should Get AI Response In 1-3 Seconds ✅

---

## 📊 Quick Reference

| Item | Details |
|------|---------|
| **API Key Source** | https://aistudio.google.com/app/apikey |
| **Config File** | includes/config.php (line 30) |
| **Test URL** | http://localhost/bayawanhotel/test-gemini.php |
| **Chatbot Location** | Bottom-right corner when logged in |
| **Cost** | FREE - no charges |
| **Response Time** | 1-3 seconds typical |
| **API Model** | Google Gemini 1.5 Flash |

---

## ❓ FAQ

**Q: Do I need to pay?**
A: No! Google Gemini free tier is 100% free for this use case.

**Q: How long does each response take?**
A: Usually 1-3 seconds. First request might be 2-3 seconds, then faster.

**Q: Can the chatbot know about my bookings?**
A: Yes! It checks your personal data (bookings, orders, points) before calling AI.

**Q: What if the API goes down?**
A: Chatbot will show "I'm currently unavailable" message and suggest contacting staff.

**Q: Can other people see my API key?**
A: No! It's stored server-side only in config.php. Never exposed to browser.

**Q: Can I customize responses?**
A: Yes! Edit the system prompt in `api/chatbot.php` function `callGeminiAPI()`

---

## 🎯 Success Checklist

- [ ] Got API key from Google AI Studio
- [ ] Added API key to includes/config.php line 30
- [ ] Saved config.php file
- [ ] Ran test at http://localhost/bayawanhotel/test-gemini.php
- [ ] All tests showing ✅ PASS
- [ ] Logged into hotel account
- [ ] Opened chatbot widget
- [ ] Sent test message
- [ ] Got AI response back ✅

---

## 📞 Need Help?

Check these files:
- **Setup Details:** `GEMINI_CHATBOT_SETUP.md`
- **Quick Reference:** `GEMINI_QUICK_START.md`
- **Test Script:** `test-gemini.php`
- **API Code:** `api/chatbot.php` (function `callGeminiAPI`)
- **Config:** `includes/config.php` (line 30)

---

**You're all set! Enjoy your AI-powered hotel chatbot! 🤖💬**
