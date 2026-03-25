<?php
/**
 * Chatbot Component for User Panel
 * Include this file at the end of user panel pages (before footer)
 */

// Only show chatbot for logged-in users
if (!isLoggedIn()) {
    return;
}
?>

<!-- Chatbot Widget -->
<div id="chatbot-widget" class="chatbot-widget">
    <!-- Chat Toggle Button -->
    <button id="chatbot-toggle" class="chatbot-toggle" onclick="toggleChatbot()" title="Chat with us!">
        <i class="fas fa-comments"></i>
        <span id="chatbot-badge" class="chatbot-badge" style="display: none;">0</span>
    </button>
    
    <!-- Chat Window -->
    <div id="chatbot-window" class="chatbot-window" style="display: none;">
        <!-- Chat Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-title">
                    <h4>Hotel Assistant</h4>
                    <span class="chatbot-status">
                        <span class="status-dot"></span>
                        Online
                    </span>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button class="chatbot-btn-icon" onclick="minimizeChatbot()" title="Minimize">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="chatbot-btn-icon" onclick="closeChatbot()" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div id="chatbot-messages" class="chatbot-messages">
            <!-- Welcome Message -->
            <div class="chat-message bot-message">
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <p>👋 Hello! I'm your virtual assistant at Bayawan Bai Hotel. How can I help you today?</p>
                    <div class="quick-replies">
                        <button onclick="sendQuickReply('I want to book a room')">Book a room</button>
                        <button onclick="sendQuickReply('Show my bookings')">My bookings</button>
                        <button onclick="sendQuickReply('Restaurant hours')">Dining</button>
                        <button onclick="sendQuickReply('Hotel amenities')">Amenities</button>
                    </div>
                    <span class="message-time">Just now</span>
                </div>
            </div>
        </div>
        
        <!-- Typing Indicator -->
        <div id="chatbot-typing" class="chatbot-typing" style="display: none;">
            <div class="typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <!-- Chat Input -->
        <div class="chatbot-input-area">
            <form id="chatbot-form" onsubmit="sendMessage(event)">
                <div class="chatbot-input-wrapper">
                    <input 
                        type="text" 
                        id="chatbot-input" 
                        class="chatbot-input" 
                        placeholder="Type your message..." 
                        autocomplete="off"
                        maxlength="500"
                    >
                    <button type="submit" class="chatbot-send-btn" title="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Chatbot Widget Styles */
.chatbot-widget {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 9999;
    font-family: 'Lato', sans-serif;
}

/* Toggle Button */
.chatbot-toggle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color, #367D8A), var(--secondary-color, #285F6B));
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(54, 125, 138, 0.4);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.chatbot-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(54, 125, 138, 0.5);
}

.chatbot-toggle:active {
    transform: scale(0.95);
}

.chatbot-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #dc3545;
    color: white;
    font-size: 12px;
    font-weight: bold;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Chat Window */
.chatbot-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Chat Header */
.chatbot-header {
    background: linear-gradient(135deg, var(--primary-color, #367D8A), var(--secondary-color, #285F6B));
    color: white;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chatbot-header-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chatbot-avatar {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.chatbot-title h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: white;
    font-family: 'Lato', sans-serif;
}

.chatbot-status {
    font-size: 12px;
    opacity: 0.9;
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #4ade80;
    border-radius: 50%;
    animation: blink 2s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.chatbot-header-actions {
    display: flex;
    gap: 8px;
}

.chatbot-btn-icon {
    width: 32px;
    height: 32px;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.chatbot-btn-icon:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Chat Messages */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.chat-message {
    display: flex;
    gap: 10px;
    max-width: 85%;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.bot-message {
    align-self: flex-start;
}

.user-message {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}

.bot-message .message-avatar {
    background: var(--primary-color, #367D8A);
    color: white;
}

.user-message .message-avatar {
    background: var(--dark-color, #133336);
    color: white;
}

.message-content {
    background: white;
    padding: 12px 16px;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    position: relative;
}

.bot-message .message-content {
    border-bottom-left-radius: 4px;
}

.user-message .message-content {
    background: var(--primary-color, #367D8A);
    color: white;
    border-bottom-right-radius: 4px;
}

.message-content p {
    margin: 0 0 8px 0;
    line-height: 1.5;
    font-size: 14px;
}

.message-content p:last-child {
    margin-bottom: 0;
}

.message-time {
    font-size: 11px;
    opacity: 0.6;
    display: block;
    margin-top: 5px;
}

/* Quick Replies */
.quick-replies {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.quick-replies button {
    background: rgba(54, 125, 138, 0.1);
    border: 1px solid var(--primary-color, #367D8A);
    color: var(--primary-color, #367D8A);
    padding: 6px 12px;
    border-radius: 16px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-replies button:hover {
    background: var(--primary-color, #367D8A);
    color: white;
}

/* Typing Indicator */
.chatbot-typing {
    padding: 0 20px 10px;
    background: #f8f9fa;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px 16px;
    background: white;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    width: fit-content;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.typing-indicator span {
    width: 8px;
    height: 8px;
    background: var(--primary-color, #367D8A);
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out both;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

/* Chat Input */
.chatbot-input-area {
    padding: 15px 20px;
    background: white;
    border-top: 1px solid #e9ecef;
}

.chatbot-input-wrapper {
    display: flex;
    gap: 10px;
    align-items: center;
}

.chatbot-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #e9ecef;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: all 0.2s;
}

.chatbot-input:focus {
    border-color: var(--primary-color, #367D8A);
    box-shadow: 0 0 0 3px rgba(54, 125, 138, 0.1);
}

.chatbot-send-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: var(--primary-color, #367D8A);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    flex-shrink: 0;
}

.chatbot-send-btn:hover {
    background: var(--secondary-color, #285F6B);
    transform: scale(1.05);
}

.chatbot-send-btn:active {
    transform: scale(0.95);
}

/* Scrollbar Styling */
.chatbot-messages::-webkit-scrollbar {
    width: 6px;
}

.chatbot-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chatbot-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.chatbot-messages::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive */
@media (max-width: 480px) {
    .chatbot-widget {
        bottom: 20px;
        right: 20px;
    }
    
    .chatbot-window {
        width: calc(100vw - 40px);
        height: 450px;
        right: 0;
        left: 0;
        margin: 0 auto;
        bottom: 80px;
        position: fixed;
    }
    
    .chatbot-toggle {
        width: 56px;
        height: 56px;
        font-size: 22px;
    }
}

/* Message content formatting */
.message-content strong {
    font-weight: 600;
}

.message-content br {
    display: block;
    margin: 5px 0;
}
</style>

<script>
// Chatbot State
let chatbotSessionToken = localStorage.getItem('chatbot_session_token') || null;
let isChatbotOpen = false;
let isTyping = false;

// Initialize Chatbot
document.addEventListener('DOMContentLoaded', function() {
    initializeChatbot();
    
    // Handle Enter Key
    const chatInput = document.getElementById('chatbot-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage(e);
            }
        });
    }
});

function initializeChatbot() {
    // Get or create session
    fetch('../api/chatbot.php?action=get_session')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatbotSessionToken = data.session_token;
                localStorage.setItem('chatbot_session_token', chatbotSessionToken);
                
                // Update badge with unread count
                if (data.unread_count > 0) {
                    updateBadge(data.unread_count);
                }
                
                // Load chat history
                loadChatHistory();
            }
        })
        .catch(error => console.error('Chatbot init error:', error));
}

// Toggle Chat Window
function toggleChatbot() {
    const window = document.getElementById('chatbot-window');
    if (isChatbotOpen) {
        closeChatbot();
    } else {
        window.style.display = 'flex';
        isChatbotOpen = true;
        scrollToBottom();
        document.getElementById('chatbot-input').focus();
        
        // Mark messages as read
        markMessagesAsRead();
    }
}

// Minimize Chat
function minimizeChatbot() {
    const window = document.getElementById('chatbot-window');
    window.style.display = 'none';
    isChatbotOpen = false;
}

// Close Chat
function closeChatbot() {
    minimizeChatbot();
}

// Send Message
function sendMessage(event) {
    event.preventDefault();
    
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();
    
    if (!message || isTyping) return;
    
    // Add user message to chat
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing indicator
    showTyping();
    
    // Send to API
    fetch('../api/chatbot.php?action=send_message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message: message,
            session_token: chatbotSessionToken
        })
    })
    .then(response => response.json())
    .then(data => {
        hideTyping();
        
        if (data.error) {
            addMessage('Sorry, I encountered an error. Please try again.', 'bot');
        } else {
            addMessage(data.bot_response, 'bot');
            
            // Update session token if new
            if (data.session_token) {
                chatbotSessionToken = data.session_token;
                localStorage.setItem('chatbot_session_token', chatbotSessionToken);
            }
        }
    })
    .catch(error => {
        hideTyping();
        addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
        console.error('Chatbot error:', error);
    });
}

// Send Quick Reply
function sendQuickReply(message) {
    const input = document.getElementById('chatbot-input');
    input.value = message;
    sendMessage({ preventDefault: () => {} });
}

// Add Message to Chat
function addMessage(text, type) {
    const messagesContainer = document.getElementById('chatbot-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${type}-message`;
    
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    
    messageDiv.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-${type === 'bot' ? 'robot' : 'user'}"></i>
        </div>
        <div class="message-content">
            <p>${formatMessage(text)}</p>
            <span class="message-time">${time}</span>
        </div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    scrollToBottom();
}

// Format message (convert newlines to <br>)
function formatMessage(text) {
    return text.replace(/\n/g, '<br>');
}

// Show Typing Indicator
function showTyping() {
    isTyping = true;
    document.getElementById('chatbot-typing').style.display = 'block';
    scrollToBottom();
}

// Hide Typing Indicator
function hideTyping() {
    isTyping = false;
    document.getElementById('chatbot-typing').style.display = 'none';
}

// Scroll to Bottom
function scrollToBottom() {
    const messagesContainer = document.getElementById('chatbot-messages');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Load Chat History
function loadChatHistory() {
    if (!chatbotSessionToken) return;
    
    fetch(`../api/chatbot.php?action=get_history&session_token=${chatbotSessionToken}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                const messagesContainer = document.getElementById('chatbot-messages');
                messagesContainer.innerHTML = ''; // Clear default message
                
                data.messages.forEach(msg => {
                    if (msg.message_type !== 'bot' && msg.message_type !== 'user') return;
                    addMessage(msg.message, msg.message_type);
                });
            }
        })
        .catch(error => console.error('Load history error:', error));
}

// Update Badge
function updateBadge(count) {
    const badge = document.getElementById('chatbot-badge');
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// Mark Messages as Read
function markMessagesAsRead() {
    fetch('../api/chatbot.php?action=mark_read', { method: 'POST' })
        .then(() => updateBadge(0))
        .catch(error => console.error('Mark read error:', error));
}
</script>
