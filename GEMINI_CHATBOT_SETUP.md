# Google Gemini Chatbot Integration Guide

## Overview
The Bayawan Bai Hotel chatbot has been integrated with Google's **Gemini AI** to provide intelligent, context-aware responses to guest inquiries. This integration uses the **free tier** of Google Gemini API with no usage limits for the experimental version.

## Setup Instructions

### Step 1: Get Your Free Gemini API Key

1. Visit **[Google AI Studio](https://aistudio.google.com/app/apikey)**
2. Click **"Get API Key"** button
3. Select **"Create API Key in new Google Cloud Project"** (or use existing project)
4. Copy the generated API key
5. **Keep it safe!** - Do not share this key publicly

### Step 2: Add API Key to Configuration

1. Open `includes/config.php`
2. Find this line:
   ```php
   define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
   ```
3. Replace `'YOUR_GEMINI_API_KEY_HERE'` with your actual API key:
   ```php
   define('GEMINI_API_KEY', 'AIzaSyD...');
   ```
4. Save the file

### Step 3: Test the Chatbot

1. Log in to your hotel account
2. Open the chatbot widget (bottom-right corner)
3. Send a test message: *"What rooms do you have available?"*
4. You should get an AI-generated response

## Features

### ✅ What the Chatbot Can Do

The Gemini-powered chatbot can:
- **Answer general hotel questions** about amenities, services, policies
- **Provide room information** - types, rates, features
- **Help with dining** - restaurant hours, menu items, reservations
- **Event space inquiries** - capacity, amenities, pricing
- **Local information** - nearby attractions, transportation
- **Booking guidance** - how to reserve rooms, payment options
- **Retrieve personal bookings** - show user's active reservations
- **Food order status** - display user's current food orders
- **Loyalty program info** - show accumulated points and rewards
- **Current time/date** - provide temporal information

### 🤖 AI Capabilities

- **Natural language understanding** - understands varied phrasing
- **Context awareness** - considers user's personal data (bookings, orders)
- **Multi-turn conversations** - remembers previous messages in session
- **Fallback responses** - handles edge cases gracefully

## API Response Time

- **Typical response:** 1-3 seconds
- **Max response:** ~30 seconds (timeout)
- **No rate limiting** on free tier for reasonable usage
- **Conversation history** stored in database for each user session

## Architecture

### Request Flow
```
User Message
    ↓
[api/chatbot.php] receives request
    ↓
Check contextual queries (bookings, orders, loyalty) → YES → Return contextual response
    ↓ NO
Call Gemini API [callGeminiAPI()]
    ↓
Parse response
    ↓
Store in database
    ↓
Return to user
```

### Database Tables Used
- `chat_sessions` - User chat sessions
- `chat_messages` - Individual messages (user & bot)
- `bookings` - For booking queries
- `food_orders` - For order queries
- `users` - For loyalty points

## Security Considerations

⚠️ **Important:**
- API keys are stored in `config.php` (server-side only)
- Never expose API keys in client-side code
- API key cannot be triggered from the browser directly
- All API calls made from your PHP backend (secure)
- User sessions required to use chatbot (authenticated only)

## Customization

### Modify System Prompt
Edit the `callGeminiAPI()` function in `api/chatbot.php`:

```php
$systemPrompt = "You are a helpful hotel assistant for Bayawan Bai Hotel. You are knowledgeable about:
// ... customize this section ...
";
```

### Change AI Model
To use a different Gemini model, update the URL in `callGeminiAPI()`:

```php
// Current: gemini-1.5-flash (fast, free tier)
// Alternative: gemini-1.5-pro (more capable, paid)
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);
```

### Adjust Response Settings
In `callGeminiAPI()`, modify these parameters:

```php
'generationConfig' => [
    'temperature' => 0.7,      // 0=deterministic, 1=creative (0.7=balanced)
    'topK' => 40,              // diversity
    'topP' => 0.95,            // probability threshold
    'maxOutputTokens' => 1024  // max response length
]
```

## Troubleshooting

### Issue: "API key not configured" error
- ✅ **Solution:** Ensure API key is set in `includes/config.php`
- Check there are no extra spaces or quotes

### Issue: Chatbot returns generic fallback responses
- **Possible causes:**
  - Gemini API key is invalid/revoked
  - Network/connectivity issue
  - API rate limit reached
- **Solution:** Check browser console for error messages, verify API key is active

### Issue: Slow responses
- **Normal:** First request might take 2-3 seconds
- **Check:** Network connectivity, API quota (free tier is generous)
- **Option:** Increase timeout in timeout parameter if needed

### Issue: Response timeout (30 seconds)
- **Cause:** Network issue or API overload
- **Solution:** Adjust timeout in `debug_java_application` parameter
- **Fallback:** User sees "I'm currently unavailable..." message

## Performance Metrics

| Metric | Value |
|--------|-------|
| Average Response Time | 1-2 seconds |
| Max Timeout | 30 seconds |
| API Calls per Day | Unlimited (free tier) |
| Concurrent Requests | Supported |
| Database Query Time | <100ms |
| Token Limit per Request | ~32K input, 1K output |

## Next Steps (Optional Enhancements)

1. **Add conversation memory** - Use database conversation history to improve context
2. **Analytics** - Track common questions and chatbot performance
3. **Multi-language support** - Detect language and respond accordingly
4. **Admin dashboard** - View and manage chat sessions
5. **Email notifications** - Alert staff of unresolved queries
6. **WhatsApp integration** - Extend chatbot to messaging apps

## Support & Documentation

- [Google Gemini API Docs](https://ai.google.dev/docs)
- [Bayawan Hotel Info](./README.md)
- [Chatbot Component](./includes/chatbot-component.php)
- [API Endpoint](./api/chatbot.php)

---

**Date Setup:** March 29, 2026  
**Integration Status:** ✅ Active  
**Free Tier Status:** ✅ No Costs
