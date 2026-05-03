# Gemini Chatbot - Quick Start

## ⚡ 3-Step Setup (5 minutes)

### 1️⃣ Get Free API Key
- Go to: https://aistudio.google.com/app/apikey
- Click "Get API Key"
- Copy your key

### 2️⃣ Add Key to Config
Edit `includes/config.php` (line ~24):
```php
define('GEMINI_API_KEY', 'YOUR_API_KEY_HERE');
// Replace with your actual key
```

### 3️⃣ Test It
- Log in to hotel account
- Open chatbot (bottom-right)
- Send a message → Get instant AI response ✅

---

## 💰 Cost
**FREE** - No costs on free tier

## ⏱️ Speed
~1-3 seconds per response

## 🎯 Works For
- Room inquiries
- Amenities questions
- Dining info
- Booking help
- Guest personal data (orders, bookings, rewards)
- General hotel questions

## 📁 Files Modified
- `includes/config.php` - Added API key
- `api/chatbot.php` - Integrated Gemini API
- `includes/chatbot-component.php` - No changes (already compatible)

## ⚙️ How It Works
```
User Question → checkContextualQueries() → callGeminiAPI() → AI Response
```

First checks user's personal data (bookings, orders), then uses Gemini AI for general questions.

## 🔧 Key Settings (in api/chatbot.php > callGeminiAPI)
```php
'temperature' => 0.7,         // Balanced responses
'maxOutputTokens' => 1024,    // Max 1KB per response
timeout => 30                 // Max wait 30 seconds
```

## 📞 Support
Personal data queries (bookings, rewards) always work offline.
General questions need working internet connection for Gemini API.

## 🚀 Done!
Your hotel now has a free, intelligent ChatGPT-like assistant! 🎉
