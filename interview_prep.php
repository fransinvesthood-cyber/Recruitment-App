<!DOCTYPE html>
<html>
<head>
  <title>Interview Prep Tool</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    * { 
      box-sizing: border-box; 
      margin: 0; 
      padding: 0; 
    }
    
    body { 
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      color: #333; 
      min-height: 100vh;
      padding: 20px;
    }

    .container {
      max-width: 1200px;
      margin: auto;
      background-color: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      min-height: calc(100vh - 40px);
      position: relative;
    }

    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
      opacity: 0.3;
    }

    .header h2 {
      font-size: 32px;
      margin-bottom: 10px;
      position: relative;
      z-index: 2;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 15px;
    }

    .btn-exit {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f3f4f6;
            color: #4f46e5;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        .btn-exit:hover {
            background: #e0e7ff;
            color: #3730a3;
            transform: scale(1.05);
        }

    .header p {
      font-size: 16px;
      opacity: 0.9;
      position: relative;
      z-index: 2;
      margin-bottom: 10px;
    }

    .content {
      padding: 40px;
      overflow-y: auto;
    }

    .button-container {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .prep-button {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 18px 35px;
      font-size: 18px;
      font-weight: 600;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #fff;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      position: relative;
      overflow: hidden;
      text-decoration: none;
      min-width: 200px;
      justify-content: center;
    }

    .prep-button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .prep-button:hover::before {
      left: 100%;
    }

    .prep-button:hover { 
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
      text-decoration: none;
      color: white;
    }

    .prep-button.mock-interview {
      background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    }

    .prep-button.mock-interview:hover {
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .prep-button:disabled { 
      background: #95a5a6; 
      cursor: not-allowed; 
      transform: none;
      box-shadow: none;
    }

    /* Feature Cards */
    .features-section {
      margin-top: 40px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 25px;
    }

    .feature-card {
      background: white;
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      border: 1px solid #e5e7eb;
      transition: all 0.3s ease;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .feature-icon {
      font-size: 48px;
      margin-bottom: 15px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .feature-title {
      font-size: 20px;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 12px;
    }

    .feature-description {
      color: #6b7280;
      line-height: 1.6;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
    }

    .modal-content {
      background-color: #fefefe;
      margin: 2% auto;
      padding: 0;
      border-radius: 15px;
      width: 90%;
      max-width: 900px;
      height: 90%;
      display: flex;
      flex-direction: column;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }

    .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .modal-header h3 {
      margin: 0;
      font-size: 26px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .close-modal {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      font-size: 24px;
      cursor: pointer;
      padding: 8px;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .close-modal:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: scale(1.1);
    }

    /* Chat Styles */
    .chat-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .chat-messages {
      flex: 1;
      padding: 25px;
      overflow-y: auto;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .message {
      margin: 20px 0;
      display: flex;
      align-items: flex-start;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .message.ai {
      justify-content: flex-start;
    }

    .message.user {
      justify-content: flex-end;
    }

    .message-bubble {
      max-width: 75%;
      padding: 18px 24px;
      border-radius: 20px;
      position: relative;
      word-wrap: break-word;
      font-size: 15px;
      line-height: 1.5;
    }

    .message.ai .message-bubble {
      background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
      color: #1e293b;
      margin-right: 60px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .message.user .message-bubble {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      margin-left: 60px;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .message-avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      margin: 0 12px;
      font-size: 14px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .message.ai .message-avatar {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      color: white;
    }

    .message.user .message-avatar {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .chat-input-container {
      padding: 25px;
      background: white;
      border-top: 1px solid #e5e7eb;
    }

    .voice-status {
      font-size: 15px;
      color: #6b7280;
      margin-bottom: 18px;
      text-align: center;
      min-height: 22px;
      font-weight: 500;
    }

    .voice-btn {
      background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
      color: white;
      border: none;
      padding: 18px 35px;
      border-radius: 25px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin: 0 auto;
      display: block;
      position: relative;
      overflow: hidden;
      min-width: 180px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }

    .voice-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
    }

    .voice-btn.recording {
      background: linear-gradient(135deg, #ff3838 0%, #c44536 100%);
      animation: pulse 1.5s infinite;
      box-shadow: 0 0 25px rgba(255, 56, 56, 0.6);
    }

    .voice-btn.processing {
      background: linear-gradient(135deg, #ffa726 0%, #ff9800 100%);
    }

    .voice-instructions {
      text-align: center;
      color: #9ca3af;
      font-size: 14px;
      margin-top: 15px;
      font-style: italic;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    /* Loading animation */
    .typing-indicator {
      display: none;
      margin: 20px 0;
    }

    .typing-dots {
      display: inline-block;
      position: relative;
      width: 50px;
      height: 12px;
    }

    .typing-dots div {
      position: absolute;
      top: 0;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: #667eea;
      animation: typing 1.4s infinite ease-in-out both;
    }

    .typing-dots div:nth-child(1) { left: 0; animation-delay: -0.32s; }
    .typing-dots div:nth-child(2) { left: 18px; animation-delay: -0.16s; }
    .typing-dots div:nth-child(3) { left: 36px; animation-delay: 0; }

    @keyframes typing {
      0%, 80%, 100% {
        transform: scale(0);
      }
      40% {
        transform: scale(1);
      }
    }

    /* Industry Selection */
    .industry-selection {
      padding: 30px;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      border-bottom: 1px solid #e5e7eb;
    }

    .industry-selection h4 {
      text-align: center;
      margin-bottom: 25px;
      color: #2c3e50;
      font-size: 20px;
    }

    .industry-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
      margin-bottom: 25px;
    }

    .industry-btn {
      background: white;
      border: 2px solid #e2e8f0;
      padding: 12px 24px;
      border-radius: 25px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
      font-weight: 500;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .industry-btn:hover, .industry-btn.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-color: #667eea;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .start-interview-btn {
      background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 25px;
      cursor: pointer;
      font-size: 18px;
      font-weight: bold;
      margin: 0 auto;
      display: block;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
    }

    .start-interview-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
    }

    .start-interview-btn:disabled {
      background: #9ca3af;
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .content {
        padding: 20px;
      }
      
      .button-container {
        flex-direction: column;
        align-items: center;
      }
      
      .prep-button {
        width: 100%;
        max-width: 300px;
      }
      
      .features-section {
        grid-template-columns: 1fr;
      }
      
      .modal-content {
        width: 95%;
        height: 95%;
        margin: 2.5% auto;
      }
      
      .industry-buttons {
        justify-content: center;
      }
    }

    /* Welcome Message */
    .welcome-section {
      text-align: center;
      margin-bottom: 30px;
      padding: 25px;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
      border-radius: 15px;
      border: 1px solid rgba(102, 126, 234, 0.1);
    }

    .welcome-title {
      font-size: 24px;
      color: #2c3e50;
      margin-bottom: 15px;
      font-weight: 600;
    }

    .welcome-text {
      color: #6b7280;
      font-size: 16px;
      line-height: 1.6;
      max-width: 600px;
      margin: 0 auto;
    }

    /* --- UNIFIED DARK MODE TOGGLE STYLES --- */
    .theme-switch-wrapper {
        position: absolute;
        top: 22px;       /* Aligned with Exit button */
        right: 70px;     /* Positioned left of Exit button */
        z-index: 100;
        display: flex;
        align-items: center;
    }

    .theme-switch {
        display: inline-block;
        height: 30px;
        position: relative;
        width: 64px;
    }

    .theme-switch input {
        display: none;
    }

    .slider {
        background-color: #cbd5e1; /* Light gray base */
        bottom: 0;
        cursor: pointer;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        transition: .4s;
        border-radius: 34px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 6px;
    }

    .slider:before {
        background-color: #fff;
        bottom: 3px;
        content: "";
        height: 24px;
        left: 3px;
        position: absolute;
        transition: .4s;
        width: 24px;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        z-index: 2;
    }

    /* Icons inside the toggle */
    .slider .bx {
        font-size: 16px;
        z-index: 1;
        transition: 0.4s;
    }

    .slider .bx-sun {
        color: #f59e0b; /* Orange/Yellow Sun */
    }

    .slider .bx-moon {
        color: #fff;
        opacity: 0.5;
    }

    /* Checked State (Dark Mode Active) */
    input:checked + .slider {
        background: linear-gradient(135deg, #667eea, #764ba2); /* Purple Gradient */
    }

    input:checked + .slider:before {
        transform: translateX(34px); /* Moves circle right */
    }

    input:checked + .slider .bx-moon {
        opacity: 1;
    }

    input:checked + .slider .bx-sun {
        opacity: 0.5;
        color: #fff;
    }

    /* Mobile Responsiveness for Toggle */
    @media (max-width: 768px) {
        .theme-switch-wrapper {
            right: 70px;
        }
    }

    /* Dark Mode Styles */
    body.dark-mode {
      background: #121212;
      color: #f0f0f0;
    }

    body.dark-mode .container {
      background-color: #1e1e1e;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
    }

    body.dark-mode .header {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      color: #fff;
    }

    body.dark-mode .welcome-section {
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.05) 100%);
      border: 1px solid rgba(255, 255, 255, 0.1);
      color: #f0f0f0;  /* Ensure welcome text is visible */
    }

    body.dark-mode .welcome-title {
      color: #ffffff;  /* Make title clearly visible */
    }

    body.dark-mode .welcome-text {
      color: #e0e0e0;  /* Slightly softer for paragraph text */
    }

    body.dark-mode .feature-card {
      background: #2a2a2a;
      border-color: #444;
      color: #f0f0f0;
    }

    body.dark-mode .feature-title {
      color: #ffffff;
    }

    body.dark-mode .feature-description {
      color: #d0d0d0;
    }
    
  </style>
</head>
<body>
<div class="container">
    <button class="btn-exit" id="exitPage">
      <i class='bx bx-x'></i>
    </button>
    
    <div class="header">
      <h2>
        <i class='bx bx-brain'></i>
        Interview Preparation Tool
      </h2>
      <p>Prepare with proven strategies to communicate confidently and leave a lasting impression.</p>
      <p>Learn how to research companies, understand job roles, and align your answers with employer expectations.</p>
      <p>Practice in a realistic environment to build confidence and sharpen your interview performance.</p>
    </div>
    
    <div class="content">
      <div class="welcome-section">
        <h3 class="welcome-title">Master Your Next Interview</h3>
        <p class="welcome-text">
          Transform your interview anxiety into confidence with our comprehensive preparation tools. 
          Access expert tips, practice with AI-powered mock interviews, and get ready to impress employers.
        </p>
      </div>

      <div class="button-container">
        <button class="prep-button" id="interviewTipsBtn">
          <i class='bx bx-lightbulb'></i>
          Interview Tips
        </button>
        
        <!-- <button class="prep-button mock-interview" id="mockInterviewBtn">
          <i class='bx bx-microphone'></i>
          Mock Interview
        </button> -->
      </div>

      <div class="features-section">
        <div class="feature-card">
          <div class="feature-icon">
            <i class='bx bx-book-open'></i>
          </div>
          <h4 class="feature-title">Expert Interview Tips</h4>
          <p class="feature-description">
            Access proven strategies and techniques from HR professionals and career experts to ace your interviews.
          </p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">
            <i class='bx bx-bot'></i>
          </div>
          <h4 class="feature-title">AI Mock Interviews</h4>
          <p class="feature-description">
            Practice with our advanced AI interviewer that adapts to your industry and provides real-time feedback.
          </p>
        </div>
        
        <div class="feature-card">
          <div class="feature-icon">
            <i class='bx bx-chart-line'></i>
          </div>
          <h4 class="feature-title">Performance Analytics</h4>
          <p class="feature-description">
            Get detailed insights into your performance and track your improvement over multiple practice sessions.
          </p>
        </div>
      </div>
      
      <div id="loading"></div>
      <div id="questions"></div>
      <div id="feedback"></div>
    </div>
  </div>

  <div id="mockInterviewModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>
          <i class='bx bx-microphone'></i>
          AI Mock Interview
        </h3>
        <button class="close-modal" id="closeModal">
          <i class='bx bx-x'></i>
        </button>
      </div>
      
      <div class="industry-selection" id="industrySelection">
        <h4>Select Your Industry:</h4>
        <div class="industry-buttons">
          <button class="industry-btn" data-industry="general">General</button>
          <button class="industry-btn" data-industry="it">IT & Technology</button>
          <button class="industry-btn" data-industry="teaching">Education/Teaching</button>
          <button class="industry-btn" data-industry="healthcare">Healthcare</button>
          <button class="industry-btn" data-industry="finance">Finance</button>
          <button class="industry-btn" data-industry="marketing">Marketing</button>
          <button class="industry-btn" data-industry="admin">Administration</button>
          <button class="industry-btn" data-industry="sales">Sales</button>
        </div>
        <button class="start-interview-btn" id="startInterviewBtn" disabled>Start Interview</button>
      </div>

      <div class="chat-container" id="chatContainer" style="display: none;">
        <div class="chat-messages" id="chatMessages"></div>
        
        <div class="typing-indicator" id="typingIndicator">
          <div class="message ai">
            <div class="message-avatar">AI</div>
            <div class="message-bubble">
              <div class="typing-dots">
                <div></div>
                <div></div>
                <div></div>
              </div>
            </div>
          </div>
        </div>

        <div class="chat-input-container">
          <div class="voice-status" id="voiceStatus">Click the microphone to record your answer</div>
          <button class="voice-btn" id="voiceBtn" title="Click to start voice recording">
            <i class='bx bx-microphone'></i>
            Record Answer
          </button>
          <div class="voice-instructions">
            Voice-only interview - speak your answers naturally
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Exit page functionality
    document.getElementById("exitPage").addEventListener("click", function() {
      window.history.back();
    });

    // Interview Tips button
    document.getElementById("interviewTipsBtn").onclick = function () {
      window.location.href = "interview_tips.php";
    };
    
    // Modal functionality
    const modal = document.getElementById('mockInterviewModal');
    const mockInterviewBtn = document.getElementById('mockInterviewBtn');
    const closeModal = document.getElementById('closeModal');
    const industrySelection = document.getElementById('industrySelection');
    const chatContainer = document.getElementById('chatContainer');
    const startInterviewBtn = document.getElementById('startInterviewBtn');
    const chatMessages = document.getElementById('chatMessages');
    const voiceBtn = document.getElementById('voiceBtn');
    const voiceStatus = document.getElementById('voiceStatus');
    const typingIndicator = document.getElementById('typingIndicator');

    let selectedIndustry = '';
    let conversationHistory = [];
    let questionCount = 0;
    const MAX_QUESTIONS = 5;

    // Speech Recognition Setup
    let recognition = null;
    let isRecording = false;

    // Initialize Speech Recognition
    if ('webkitSpeechRecognition' in window) {
      recognition = new webkitSpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.lang = 'en-US';
      
      recognition.onstart = function() {
        isRecording = true;
        voiceBtn.classList.add('recording');
        voiceBtn.innerHTML = '<i class="bx bx-stop"></i> Stop Recording';
        voiceStatus.textContent = '🎤 Listening... Speak clearly!';
        voiceBtn.title = 'Click to stop recording';
      };
      
      recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        voiceStatus.textContent = `✅ Recorded: "${transcript}"`;
        // Automatically send the message after a brief delay
        setTimeout(() => {
          sendMessage(transcript);
        }, 1000);
      };
      
      recognition.onerror = function(event) {
        voiceStatus.textContent = `❌ Error: ${event.error}. Please try again.`;
        resetVoiceButton();
      };
      
      recognition.onend = function() {
        resetVoiceButton();
      };
    } else if ('SpeechRecognition' in window) {
      // Standard Speech Recognition
      recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.lang = 'en-US';
      
      recognition.onstart = function() {
        isRecording = true;
        voiceBtn.classList.add('recording');
        voiceBtn.innerHTML = '<i class="bx bx-stop"></i> Stop Recording';
        voiceStatus.textContent = 'Listening... Speak now!';
        voiceBtn.title = 'Click to stop recording';
      };
      
      recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        voiceStatus.textContent = `Heard: "${transcript}"`;
        setTimeout(() => {
          sendMessage(transcript);
        }, 1000);
      };
      
      recognition.onerror = function(event) {
        voiceStatus.textContent = `Error: ${event.error}. Please try again.`;
        resetVoiceButton();
      };
      
      recognition.onend = function() {
        resetVoiceButton();
      };
    }

    function resetVoiceButton() {
      isRecording = false;
      voiceBtn.classList.remove('recording', 'processing');
      voiceBtn.innerHTML = '<i class="bx bx-microphone"></i> Record Answer';
      voiceBtn.title = 'Click to start voice recording';
      if (!voiceStatus.textContent.includes('Recorded:') && !voiceStatus.textContent.includes('Error:')) {
        voiceStatus.textContent = 'Click the microphone to record your answer';
      }
    }

    // Gemini API configuration
    const GEMINI_API_KEY = 'AIzaSyCCp-Tpn38uEhR4Kc1qckJ--uNKo-SkAio';
    const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent';

    // Open modal
    mockInterviewBtn.addEventListener('click', () => {
      modal.style.display = 'block';
      resetInterview();
    });

    // Close modal
    closeModal.addEventListener('click', () => {
      modal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
      if (event.target == modal) {
        modal.style.display = 'none';
      }
    });

    // Industry selection
    document.querySelectorAll('.industry-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.industry-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        selectedIndustry = btn.dataset.industry;
        startInterviewBtn.disabled = false;
      });
    });

    // Start interview
    startInterviewBtn.addEventListener('click', () => {
      industrySelection.style.display = 'none';
      chatContainer.style.display = 'flex';
      startInterviewSession();
    });

    // Voice recording
    voiceBtn.addEventListener('click', toggleVoiceRecording);

    function toggleVoiceRecording() {
      if (!recognition) {
        voiceStatus.textContent = '❌ Speech recognition not supported in this browser';
        return;
      }

      if (isRecording) {
        recognition.stop();
      } else {
        // Request microphone permission and start recording
        navigator.mediaDevices.getUserMedia({ audio: true })
          .then(() => {
            voiceStatus.textContent = 'Initializing voice recognition...';
            recognition.start();
          })
          .catch((error) => {
            console.error('Microphone access denied:', error);
            voiceStatus.textContent = '❌ Microphone access denied. Please enable microphone permissions.';
          });
      }
    }

    function resetInterview() {
      selectedIndustry = '';
      conversationHistory = [];
      questionCount = 0;
      chatMessages.innerHTML = '';
      voiceStatus.textContent = 'Click the microphone to record your answer';
      resetVoiceButton();
      industrySelection.style.display = 'block';
      chatContainer.style.display = 'none';
      startInterviewBtn.disabled = true;
      document.querySelectorAll('.industry-btn').forEach(b => b.classList.remove('active'));
    }

    function addMessage(content, isUser = false) {
      const messageDiv = document.createElement('div');
      messageDiv.className = `message ${isUser ? 'user' : 'ai'}`;
      
      messageDiv.innerHTML = `
        <div class="message-avatar">${isUser ? 'YOU' : 'AI'}</div>
        <div class="message-bubble">${content}</div>
      `;
      
      chatMessages.appendChild(messageDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTyping(show = true) {
      typingIndicator.style.display = show ? 'block' : 'none';
      if (show) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }
    }

    async function startInterviewSession() {
      const industryContext = {
        'general': 'general business positions',
        'it': 'IT and Technology roles including software development, system administration, and technical support',
        'teaching': 'Education and Teaching positions',
        'healthcare': 'Healthcare and Medical positions',
        'finance': 'Finance and Banking roles',
        'marketing': 'Marketing and Digital Marketing positions',
        'admin': 'Administrative and Office Management roles',
        'sales': 'Sales and Customer Relations positions'
      };

      const prompt = `You are an experienced professional interviewer conducting a mock interview for ${industryContext[selectedIndustry]}. 

      Your role:
      - Ask realistic, industry-relevant interview questions
      - Provide constructive feedback after each answer
      - Be professional but encouraging
      - Ask follow-up questions based on responses
      - Keep the conversation flowing naturally

      Start with a warm greeting and ask the first question. Make it feel like a real interview.`;

      await sendToGemini(prompt, true);
    }

    async function sendMessage(message) {
      if (!message || !message.trim()) return;

      // Add user message
      addMessage(message, true);
      voiceStatus.textContent = ''; // Clear voice status

      // Disable input while processing
      voiceBtn.disabled = true;
      showTyping(true);

      // Send to Gemini
      await sendToGemini(message);

      // Re-enable input
      voiceBtn.disabled = false;
      showTyping(false);
      
      // Reset voice status
      if (!voiceStatus.textContent.includes('Error:')) {
        voiceStatus.textContent = 'Click the microphone to record your next answer';
      }
    }

    async function sendToGemini(userMessage, isInitial = false) {
      try {
        if (!isInitial) {
          conversationHistory.push({ role: 'user', content: userMessage });
          questionCount++;
        }

        const systemPrompt = isInitial ? userMessage : 
          `Continue the mock interview. The candidate just answered: "${userMessage}". 
          
          ${questionCount < MAX_QUESTIONS ? 
            `Provide brief feedback on their answer (2-3 sentences), then ask the next relevant interview question. Keep the interview flowing naturally.` : 
            `This was question ${questionCount}. Provide feedback on their answer, then conclude the interview with overall feedback and encouragement. Thank them for their time.`
          }`;

        const requestBody = {
          contents: [{
            parts: [{
              text: systemPrompt
            }]
          }]
        };

        const response = await fetch(`${GEMINI_API_URL}?key=${GEMINI_API_KEY}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(requestBody)
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        const aiResponse = data.candidates[0].content.parts[0].text;

        // Add AI response with typing delay
        setTimeout(() => {
          addMessage(aiResponse);
          conversationHistory.push({ role: 'assistant', content: aiResponse });
          
          // If interview is complete, show restart option
          if (questionCount >= MAX_QUESTIONS) {
            setTimeout(() => {
              const restartDiv = document.createElement('div');
              restartDiv.innerHTML = `
                <div style="text-align: center; margin: 20px 0;">
                  <button class="start-interview-btn" onclick="resetInterview(); industrySelection.style.display = 'block'; chatContainer.style.display = 'none';">
                    Start New Interview
                  </button>
                </div>
              `;
              chatMessages.appendChild(restartDiv);
              chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 2000);
          }
        }, 1000);

      } catch (error) {
        console.error('Error calling Gemini API:', error);
        addMessage('Sorry, I encountered an error. Please try again or start a new interview.', false);
        
        // Show restart button on error
        setTimeout(() => {
          const restartDiv = document.createElement('div');
          restartDiv.innerHTML = `
            <div style="text-align: center; margin: 20px 0;">
              <button class="start-interview-btn" onclick="resetInterview(); industrySelection.style.display = 'block'; chatContainer.style.display = 'none';">
                Try Again
              </button>
            </div>
          `;
          chatMessages.appendChild(restartDiv);
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 1000);
      }
    }

    // Add smooth scrolling and focus management
    document.addEventListener('DOMContentLoaded', function() {
      // Smooth scroll behavior for chat messages
      if (chatMessages) {
        chatMessages.style.scrollBehavior = 'smooth';
      }
      
      // Add keyboard shortcuts
      document.addEventListener('keydown', function(e) {
        // ESC to close modal
        if (e.key === 'Escape' && modal.style.display === 'block') {
          modal.style.display = 'none';
        }
        
        // Space to toggle recording (when modal is open and not in industry selection)
        if (e.code === 'Space' && modal.style.display === 'block' && chatContainer.style.display === 'flex') {
          e.preventDefault();
          toggleVoiceRecording();
        }
      });
      
      // Add visual feedback for microphone access
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        // Check if microphone is available
        navigator.mediaDevices.enumerateDevices()
          .then(devices => {
            const hasAudio = devices.some(device => device.kind === 'audioinput');
            if (!hasAudio) {
              console.warn('No microphone detected');
            }
          })
          .catch(err => {
            console.warn('Could not enumerate devices:', err);
          });
      }
    });

    // Add window resize handler for responsive modal
    window.addEventListener('resize', function() {
      if (modal.style.display === 'block') {
        // Ensure modal stays centered and fits viewport
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
          const viewportHeight = window.innerHeight;
          const maxHeight = Math.min(viewportHeight * 0.9, 800);
          modalContent.style.maxHeight = maxHeight + 'px';
        }
      }
    });

    // --- DARK MODE LOGIC (SYNCED) ---
    const toggle = document.getElementById('dark-mode-toggle'); // UPDATED ID

    // Load saved preference
    if (localStorage.getItem('darkMode') === 'enabled') {
      document.body.classList.add('dark-mode');
      toggle.checked = true;
    }

    // Toggle dark mode
    toggle.addEventListener('change', () => {
      if (toggle.checked) {
        document.body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
      } else {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
      }
    });

  </script>
</body>
</html>