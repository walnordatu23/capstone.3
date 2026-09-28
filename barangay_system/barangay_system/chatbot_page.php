<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Barangay 76-A Chatbot</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      height: 100%; width: 100%;
      background: transparent;
      font-family: 'DM Serif Display', 'Georgia', serif;
    }

    @keyframes typingBounce {
      0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
      40%            { transform: translateY(-7px); opacity: 1; }
    }
    @keyframes msgIn {
      from { opacity: 0; transform: translateY(16px) scale(0.95); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes shimmer {
      0%   { background-position: -200% center; }
      100% { background-position:  200% center; }
    }
    @keyframes pulse-ring {
      0%   { transform: scale(1);   opacity: 0.6; }
      100% { transform: scale(1.6); opacity: 0;   }
    }
    @keyframes floatOrb {
      0%, 100% { transform: translateY(0px)   scale(1);    }
      50%      { transform: translateY(-20px) scale(1.05); }
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    textarea:focus   { outline: none; }
    textarea::placeholder { color: rgba(30,58,138,0.3); }

    ::-webkit-scrollbar       { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: rgba(29,78,216,0.2); border-radius: 4px; }

    .suggest-chip {
      font-family: 'DM Sans', sans-serif;
      font-size: 12.5px; font-weight: 400;
      padding: 7px 14px; border-radius: 30px;
      border: 1px solid rgba(29,78,216,0.18);
      background: rgba(29,78,216,0.06);
      color: rgba(30,58,138,0.85);
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.34,1.56,0.64,1);
      display: flex; align-items: center; gap: 6px;
    }
    .suggest-chip:hover {
      background: rgba(29,78,216,0.12);
      border-color: rgba(29,78,216,0.45);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(29,78,216,0.15);
    }
    .suggest-chip:disabled { opacity: 0.4; cursor: default; transform: none; }

    .send-btn {
      width: 42px; height: 42px; border-radius: 13px; border: none;
      font-size: 18px; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
    }
    .send-btn:hover:not(:disabled) {
      transform: scale(1.06);
      box-shadow: 0 0 30px rgba(29,78,216,0.5) !important;
    }
    .send-btn:disabled { opacity: 0.35; cursor: default; }

    /* ── Chat wrapper ── */
    #chat-wrap {
      display: flex; flex-direction: column;
      height: 100vh; width: 100%;
      background: #f0f4ff;
      position: relative; overflow: hidden;
    }

    /* ── Orb ── */
    .orb {
      position: absolute; border-radius: 50%;
      pointer-events: none; z-index: 0;
    }

    /* ── Scanline ── */
    #scanline {
      position: absolute; inset: 0; z-index: 1; pointer-events: none;
      background: repeating-linear-gradient(
        0deg,
        transparent, transparent 2px,
        rgba(0,0,0,0.03) 2px, rgba(0,0,0,0.03) 4px
      );
    }

    /* ── Header ── */
    #chat-header {
      position: relative; z-index: 10;
      padding: 20px 24px 18px;
      border-bottom: 1px solid rgba(29,78,216,0.15);
      background: rgba(255,255,255,0.75);
      backdrop-filter: blur(20px);
      display: flex; align-items: center; gap: 16px;
      flex-shrink: 0;
    }
    #logo-wrap { position: relative; flex-shrink: 0; }
    #logo-icon {
      width: 52px; height: 52px; border-radius: 14px;
      background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
      display: flex; align-items: center; justify-content: center;
      font-size: 26px;
      box-shadow: 0 0 0 1px rgba(29,78,216,0.4), 0 8px 24px rgba(29,78,216,0.35);
    }
    #online-dot {
      position: absolute; bottom: 1px; right: 1px;
      width: 12px; height: 12px; border-radius: 50%;
      background: #4ade80;
    }
    #online-ring {
      position: absolute; inset: -2px; border-radius: 50%;
      border: 2px solid #4ade80;
      animation: pulse-ring 1.8s ease-out infinite;
    }
    #header-title {
      font-family: 'DM Serif Display', serif;
      font-size: 18px; font-weight: 400;
      color: #0f172a; letter-spacing: 0.3px; line-height: 1.2;
    }
    #header-sub {
      font-family: 'DM Sans', sans-serif;
      font-size: 11.5px; font-weight: 400;
      color: rgba(29,78,216,0.7);
      margin-top: 3px; letter-spacing: 1.2px;
      text-transform: uppercase;
    }
    #ai-badge {
      font-family: 'DM Sans', sans-serif;
      font-size: 10px; font-weight: 600;
      padding: 4px 10px; border-radius: 20px;
      background: rgba(29,78,216,0.08);
      border: 1px solid rgba(29,78,216,0.2);
      color: rgba(29,78,216,0.85);
      letter-spacing: 1px; text-transform: uppercase;
      margin-left: auto; white-space: nowrap;
    }

    /* ── Chat area ── */
    #chat-area {
      flex: 1; overflow-y: auto;
      padding: 24px 20px;
      display: flex; flex-direction: column;
      gap: 18px; position: relative; z-index: 5;
    }

    /* ── Messages ── */
    .msg-row {
      display: flex; align-items: flex-end; gap: 10px;
      animation: msgIn 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
    }
    .msg-row.user  { flex-direction: row-reverse; }
    .msg-row.bot   { flex-direction: row; }

    .avatar {
      width: 38px; height: 38px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; flex-shrink: 0;
    }
    .avatar.bot {
      background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
      box-shadow: 0 0 14px rgba(29,78,216,0.45);
    }
    .avatar.user {
      background: rgba(30,58,138,0.15);
      border: 1.5px solid rgba(29,78,216,0.35);
      font-size: 17px;
    }

    .bubble {
      max-width: 72%; padding: 12px 16px;
      font-size: 14px; line-height: 1.75;
    }
    .bubble.bot {
      border-radius: 20px 20px 20px 4px;
      background: rgba(255,255,255,0.92);
      color: #0f172a;
      border: 1px solid rgba(30,58,138,0.12);
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 12px rgba(30,58,138,0.1);
    }
    .bubble.user {
      border-radius: 20px 20px 4px 20px;
      background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%);
      color: #ffffff; font-weight: 500;
      box-shadow: 0 4px 20px rgba(29,78,216,0.35);
    }

    /* Typing dots */
    .typing-dots { display: flex; gap: 5px; align-items: center; padding: 4px 0; }
    .typing-dots span {
      width: 8px; height: 8px; border-radius: 50%;
      background: rgba(30,80,180,0.7);
      display: inline-block;
      animation: typingBounce 1.2s ease-in-out infinite;
    }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

    /* Suggestions */
    #suggestions { animation: msgIn 0.4s ease both; margin-top: 4px; }
    #suggestions-label {
      font-family: 'DM Sans', sans-serif;
      font-size: 10.5px; letter-spacing: 1.5px;
      text-transform: uppercase; font-weight: 600;
      color: rgba(29,78,216,0.45);
      margin-bottom: 12px; padding-left: 2px;
    }
    #suggestions-chips { display: flex; flex-wrap: wrap; gap: 8px; }

    /* ── Input area ── */
    #input-area {
      position: relative; z-index: 10;
      padding: 14px 20px 22px;
      border-top: 1px solid rgba(29,78,216,0.12);
      background: rgba(255,255,255,0.8);
      backdrop-filter: blur(24px);
      flex-shrink: 0;
    }
    #shimmer-line {
      position: absolute; top: 0; left: 10%; right: 10%; height: 1px;
      background: linear-gradient(90deg, transparent, rgba(29,78,216,0.4), transparent);
      background-size: 200% 100%;
      animation: shimmer 3s linear infinite;
    }
    #input-row {
      display: flex; gap: 12px; align-items: flex-end;
      background: rgba(255,255,255,0.9);
      border: 1px solid rgba(29,78,216,0.2);
      border-radius: 20px;
      padding: 10px 10px 10px 18px;
      transition: border-color 0.2s;
    }
    #msg-input {
      flex: 1; background: transparent;
      border: none; resize: none;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px; font-weight: 400;
      color: #0f172a; line-height: 1.65;
      max-height: 120px; overflow-y: auto;
      padding: 0;
    }
    #footer-label {
      font-family: 'DM Sans', sans-serif;
      text-align: center; font-size: 10.5px;
      color: rgba(29,78,216,0.3);
      margin-top: 10px; letter-spacing: 0.5px;
    }

    /* Spinner inside send button */
    .spinner {
      width: 18px; height: 18px; border-radius: 50%;
      border: 2px solid rgba(29,78,216,0.2);
      border-top-color: rgba(29,78,216,0.9);
      animation: spin 0.8s linear infinite;
    }
  </style>
</head>
<body>

<div id="chat-wrap">

  <div class="orb" style="top:-120px;right:-80px;width:360px;height:360px;
    background:radial-gradient(circle,rgba(29,78,216,0.1) 0%,transparent 70%);
    animation:floatOrb 8s ease-in-out infinite;"></div>
  <div class="orb" style="bottom:-80px;left:-60px;width:280px;height:280px;
    background:radial-gradient(circle,rgba(30,58,138,0.08) 0%,transparent 70%);
    animation:floatOrb 10s ease-in-out infinite reverse;"></div>

  <div id="scanline"></div>

  <div id="chat-header">
    <div id="logo-wrap">
      <div id="logo-icon">🏛️</div>
      <div id="online-dot"><div id="online-ring"></div></div>
    </div>
    <div style="flex:1;">
      <div id="header-title">Barangay 76-A System</div>
      <div id="header-sub">FAQ · Capstone Assistant</div>
    </div>
    <div id="ai-badge">AI Powered</div>
  </div>

  <div id="chat-area">
    <div id="suggestions">
      <div id="suggestions-label">Try asking</div>
      <div id="suggestions-chips"></div>
    </div>
    <div id="bottom-anchor"></div>
  </div>

  <div id="input-area">
    <div id="shimmer-line"></div>
    <div id="input-row">
      <textarea id="msg-input" rows="1" placeholder="Ask about the capstone…"></textarea>
      <button class="send-btn" id="send-btn"
        style="background:rgba(29,78,216,0.08);color:rgba(29,78,216,0.3);box-shadow:none;">↑</button>
    </div>
    <div id="footer-label">Chavez &amp; Datu · BSIT Capstone · St. John Paul II College of Davao</div>
  </div>

</div>

<script>
// ── CONFIG AND SUGGESTIONS ──────────────────────────────────────────────
const SUGGESTIONS = [
  { icon: "📋", text: "What is this project about?" },
  { icon: "👥", text: "Who are the developers?" },
  { icon: "⚙️", text: "What tech stack is used?" },
  { icon: "🔍", text: "What problem does it solve?" },
  { icon: "🧑‍💼", text: "Who are the system users?" },
  { icon: "✨", text: "What are the key features?" },
  { icon: "🔄", text: "How is it different from FixMyStreet?" },
  { icon: "⚠️", text: "What are the limitations?" },
];

// ── STATE ────────────────────────────────────────────────────────────────
let messages = []; // Track conversion history structure locally
let loading = false;

// ── DOM REFS ─────────────────────────────────────────────────────────────
const chatArea   = document.getElementById('chat-area');
const bottomAnchor = document.getElementById('bottom-anchor');
const suggestions  = document.getElementById('suggestions');
const sugChips     = document.getElementById('suggestions-chips');
const msgInput     = document.getElementById('msg-input');
const sendBtn      = document.getElementById('send-btn');

// ── HELPERS ───────────────────────────────────────────────────────────────
function scrollToBottom() {
  bottomAnchor.scrollIntoView({ behavior: 'smooth' });
}

function renderContent(text) {
  // Simple markdown conversion for **bolding** and HTML entity escapes
  return text
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>');
}

function addMessage(role, content, typing = false) {
  const isUser = role === 'user';
  const row = document.createElement('div');
  row.className = `msg-row ${isUser ? 'user' : 'bot'}`;
  row.dataset.id = Date.now() + Math.random();

  const avatar = document.createElement('div');
  avatar.className = `avatar ${isUser ? 'user' : 'bot'}`;
  avatar.textContent = isUser ? '👤' : '🏛️';

  const bubble = document.createElement('div');
  bubble.className = `bubble ${isUser ? 'user' : 'bot'}`;

  if (typing) {
    bubble.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';
    row.dataset.typing = 'true';
  } else {
    bubble.innerHTML = renderContent(content);
  }

  if (isUser) {
    row.appendChild(bubble);
    row.appendChild(avatar);
  } else {
    row.appendChild(avatar);
    row.appendChild(bubble);
  }

  chatArea.insertBefore(row, suggestions);
  scrollToBottom();
  return row;
}

function removeTyping() {
  const typing = chatArea.querySelector('[data-typing="true"]');
  if (typing) typing.remove();
}

function updateSendBtn() {
  const hasText = msgInput.value.trim().length > 0;
  if (loading) {
    sendBtn.innerHTML = '<div class="spinner"></div>';
    sendBtn.disabled = true;
    sendBtn.style.background = 'rgba(29,78,216,0.08)';
    sendBtn.style.color = 'rgba(29,78,216,0.3)';
    sendBtn.style.boxShadow = 'none';
  } else if (hasText) {
    sendBtn.innerHTML = '↑';
    sendBtn.disabled = false;
    sendBtn.style.background = 'linear-gradient(135deg, #1e3a8a, #1d4ed8)';
    sendBtn.style.color = '#ffffff';
    sendBtn.style.boxShadow = '0 0 20px rgba(29,78,216,0.35)';
  } else {
    sendBtn.innerHTML = '↑';
    sendBtn.disabled = true;
    sendBtn.style.background = 'rgba(29,78,216,0.08)';
    sendBtn.style.color = 'rgba(29,78,216,0.3)';
    sendBtn.style.boxShadow = 'none';
  }
}

// ── SEND MESSAGE TO LOCAL SECURE PHP BACKEND ──────────────────────────────
async function sendMessage(text) {
  const userText = (text || msgInput.value).trim();
  if (!userText || loading) return;

  msgInput.value = '';
  msgInput.style.height = 'auto';
  suggestions.style.display = 'none';
  loading = true;
  msgInput.disabled = true;
  msgInput.placeholder = "Barangay AI is typing...";  
  updateSendBtn();

  // Add to UI
  addMessage('user', userText);
  addMessage('assistant', '', true);

  messages.push({ role: 'user', content: userText });

  // 🔴 1. PASTE YOUR ACTIVE NGROK URL HERE
  const NGROK_FLASK_URL = "https://catwalk-gratitude-composer.ngrok-free.dev";

  try {
    // 🔄 2. ROUTE BACK TO GOOGLE COLAB 
    const res = await fetch(`${NGROK_FLASK_URL}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: userText
      }),
    });

    const data = await res.json();
    const reply = data.reply || "Sorry, I couldn't get a response.";

    removeTyping();
    addMessage('assistant', reply);
    messages.push({ role: 'assistant', content: reply }); 

  } catch (err) {
    console.error("Colab Bridge Error: ", err);
    removeTyping();
    addMessage('assistant', '⚠️ Connection error. Make sure Google Colab is running.');
  }

  loading = false;
  updateSendBtn();
  msgInput.disabled = false;
  msgInput.placeholder = "Type your message here...";
}
sendBtn.addEventListener('click', () => sendMessage());

// ── INITIALIZATION ────────────────────────────────────────────────────────
// Warm welcome message instantiation
addMessage('assistant', "Maayong Adlaw! 👋\n\nI'm your AI guide for the **Barangay 76-A Web-Based System** capstone by Chavez & Datu. Ask me anything about the project!");

// Dynamic build of suggestions interaction triggers
SUGGESTIONS.forEach(s => {
  const btn = document.createElement('button');
  btn.className = 'suggest-chip';
  btn.innerHTML = `<span style="font-size:13px">${s.icon}</span> ${s.text}`;
  btn.addEventListener('click', () => sendMessage(s.text));
  sugChips.appendChild(btn);
});

updateSendBtn();
scrollToBottom();
</script>
</body>
</html>