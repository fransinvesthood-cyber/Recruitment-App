<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <title>Prepare for Interview</title>
  <style>
    /* Base theme and layout */
    :root { 
      font-family: system-ui, sans-serif; 
      --primary-blue: #667eea;
      --primary-purple: #764ba2;
      --success-green: #10b981;
      --card-bg: #fff;
      --text-primary: #2c3e50;
      --text-secondary: #6b7280;
      --border-color: #e5e7eb;
      --background: #f8fafc;
    }
    
    body { 
      margin: 0; 
      background: var(--background); 
      color: var(--text-primary);
      line-height: 1.6;
      transition: background 0.3s ease, color 0.3s ease;
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
      transition: background-color 0.3s ease;
    }
    
    .content {
      padding: 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    /* Header styles */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
      padding: 25px;
      background: var(--card-bg);
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-bottom: 2px solid #f0f0f0;
      position: relative;
    }

    .page-title {
      font-size: 28px;
      color: var(--text-primary);
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 600;
    }

    .page-subtitle {
      color: var(--text-secondary);
      font-size: 16px;
      margin: 8px 0 0 44px;
    }

    /* Form container */
    .form-container {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border: 1px solid var(--border-color);
      margin-bottom: 25px;
      transition: background 0.3s ease, box-shadow 0.3s ease;
    }

    /* Input grid */
    .input-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 25px;
    }

    .input-group {
      display: flex;
      flex-direction: column;
    }

    label {
      font-size: 14px;
      color: var(--text-primary);
      font-weight: 500;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    label i {
      color: var(--primary-blue);
      font-size: 25px;
    }

    input, select {
      width: 100%;
      padding: 12px 16px;
      border-radius: 8px;
      border: 1px solid var(--border-color);
      background: var(--card-bg);
      color: var(--text-primary);
      font-size: 16px;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }

    input:focus, select:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    /* Buttons */
    .actions {
      display: flex;
      gap: 15px;
      align-items: center;
      flex-wrap: wrap;
      margin-top: 20px;
      padding-top: 15px;
      border-top: 1px solid var(--border-color);
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

    button, .btn {
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 500;
      font-size: 16px;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: none;
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
      color: white;
    }

    button:hover, .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
      color: white;
    }

    button.secondary {
      background: var(--card-bg);
      color: var(--text-primary);
      border: 2px solid var(--border-color);
    }

    button.secondary:hover {
      border-color: var(--primary-blue);
      background: #f8fafc;
      color: var(--text-primary);
    }

    button:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .status {
      font-family: monospace;
      font-size: 14px;
      color: var(--text-secondary);
      margin-left: auto;
    }

    /* Results */
    .results-container {
      margin-top: 25px;
      display: none;
    }

    .results-grid {
      display: grid;
      gap: 20px;
    }

    .result-panel {
      background: var(--card-bg);
      border-radius: 12px;
      padding: 25px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      border: 1px solid var(--border-color);
      transition: all 0.3s ease;
    }

    .result-panel h3 {
      margin: 0 0 20px 0;
      font-size: 20px;
      color: var(--text-primary);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
    }

    .result-panel h3 i {
      color: var(--primary-blue);
      font-size: 22px;
    }

    .result-panel ul {
      margin: 0;
      padding: 0;
      list-style: none;
    }

    .result-panel li {
      margin-bottom: 12px;
      padding: 12px 16px;
      background: #f8fafc;
      border-radius: 8px;
      border-left: 4px solid var(--primary-blue);
      color: var(--text-secondary);
      line-height: 1.5;
      transition: all 0.3s ease;
    }

    /* Tabs */
    .feature-tabs {
      display: flex;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .tab-button {
      padding: 10px 20px;
      background: var(--card-bg);
      border: 2px solid var(--border-color);
      border-radius: 25px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
      color: var(--text-secondary);
    }

    .tab-button.active {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
      color: white;
      border-color: var(--primary-blue);
    }

    /* --- Theme Switcher (Standardized) --- */
    .theme-switch-wrapper {
      position: absolute;
      top: 25px;
      right: 25px;
      z-index: 100;
      display: flex;
      align-items: center;
      margin-right: 50px;
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
      background-color: #cbd5e1;
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

    .slider .bx {
      font-size: 16px;
      z-index: 1;
      transition: 0.4s;
    }

    .slider .bx-sun {
      color: #f59e0b;
    }

    .slider .bx-moon {
      color: #fff;
      opacity: 0.5;
    }

    input:checked + .slider {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
    }

    input:checked + .slider:before {
      transform: translateX(34px);
    }

    input:checked + .slider .bx-moon {
      opacity: 1;
    }

    input:checked + .slider .bx-sun {
      opacity: 0.5;
      color: #fff;
    }

    /* --- Dark Mode Styles --- */
    body.dark-mode {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: #e2e8f0;
    }

    body.dark-mode .container {
      background-color: #1f2937;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
    }

    body.dark-mode .page-header {
      background-color: #1f2937 !important;
      border-bottom-color: #374151;
    }

    body.dark-mode .page-title,
    body.dark-mode .page-subtitle,
    body.dark-mode label {
      color: #e2e8f0 !important;
    }

    body.dark-mode .form-container,
    body.dark-mode .result-panel {
      background-color: #1f2937 !important;
      border-color: #374151 !important;
      color: #e2e8f0 !important;
    }

    body.dark-mode input,
    body.dark-mode select {
      background-color: #374151 !important;
      border-color: #4b5563 !important;
      color: #e2e8f0 !important;
    }

    body.dark-mode .result-panel li {
      background-color: #2d3748 !important;
      color: #e2e8f0 !important;
      border-left-color: #667eea;
    }

    body.dark-mode .tab-button {
      background-color: #374151;
      color: #e2e8f0;
      border-color: #4b5563;
    }

    body.dark-mode .tab-button.active {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
      color: white;
      border-color: var(--primary-blue);
    }

    body.dark-mode button.secondary {
      background-color: #374151;
      color: #e2e8f0;
      border-color: #4b5563;
    }
    
    body.dark-mode button.secondary:hover {
      background-color: #4b5563;
    }

    /* Modal */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      z-index: 2000;
    }

    .modal {
      width: 100%;
      max-width: 560px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.25);
      overflow: hidden;
      border: 1px solid var(--border-color);
    }

    body.dark-mode .modal {
      background: #1f2937;
      color: #e2e8f0;
      border-color: #374151;
    }

    .modal-header {
      padding: 16px 18px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }

    body.dark-mode .modal-header {
      border-bottom-color: #374151;
    }

    .modal-header i {
      color: var(--primary-blue);
      font-size: 22px;
      margin-top: 1px;
    }

    .modal-title {
      font-size: 18px;
      font-weight: 700;
      line-height: 1.3;
      margin: 0;
    }

    .modal-body {
      padding: 14px 18px 18px 18px;
      color: var(--text-secondary);
      white-space: pre-line;
    }

    body.dark-mode .modal-body {
      color: #e2e8f0;
    }

    .modal-actions {
      padding: 0 18px 18px 18px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .modal-actions button {
      background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 100%);
      color: #fff;
      padding: 10px 16px;
      border-radius: 10px;
    }

    @media (max-width: 768px) {
      .theme-switch { top: 20px; right: 20px; }
      .page-header { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
  <div class="container">
    <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>

    <div class="content">
      <div class="page-header">
        <div>
          <h1 class="page-title">
            <i class='bx bx-brain'></i>
            Interview Preparation Tips
          </h1>
          <p class="page-subtitle">Generate industry-specific tips, questions, and do's & don'ts</p>
        </div>
      </div>

      <div class="form-container">
        <div class="input-grid">
          <div class="input-group">
            <label for="position">
              <i class='bx bx-briefcase'></i>
              Position
            </label>
            <input id="position" placeholder="e.g. Software Developer" />
          </div>
          
          <div class="input-group">
            <label for="department">
              <i class='bx bx-category'></i>
              Department
            </label>
            <input id="department" placeholder="e.g. Information Technology" />
          </div>
          
          <div class="input-group">
            <label for="company_name">
              <i class='bx bx-buildings'></i>
              Company
            </label>
            <input id="company_name" placeholder="e.g. TechSolutions Ltd" />
          </div>
          
          <div class="input-group">
            <label for="experience">
              <i class='bx bx-trending-up'></i>
              Experience Level
            </label>
            <select id="experience">
              <option value="">Not specified</option>
              <option>Intern</option>
              <option>Junior</option>
              <option>Mid</option>
              <option>Senior</option>
              <option>Lead</option>
            </select>
          </div>
        </div>
        
        <div class="actions">
          <button id="prepareBtn">
            <i class='bx bx-circle'></i>
            Generate Tips
          </button>
          <button id="copyBtn" class="secondary">
            <i class='bx bx-copy'></i>
            Copy All Results
          </button>
          <button id="pdfBtn" class="secondary">
            <i class='bx bx-download'></i>
            Download PDF
          </button>
          <span id="status" class="status"></span>
        </div>
      </div>

      <div class="form-container" id="newFeaturesSection" style="display: none;">
        <div class="feature-tabs">
          <button class="tab-button active" onclick="switchTab('stress')">
            <i class='bx bx-heart'></i> Stress Level
          </button>
          <button class="tab-button" onclick="switchTab('mentor')">
            <i class='bx bx-group'></i> Find Mentor
          </button>
          <button class="tab-button" onclick="switchTab('salary')">
            <i class='bx bx-money'></i> Salary Tips
          </button>
          <button class="tab-button" onclick="switchTab('research')">
            <i class='bx bx-search'></i> Company Research
          </button>
        </div>

        <div id="stressTab" class="tab-content">
          <div class="stress-indicator">
            <h3><i class='bx bx-heart'></i> Interview Stress Assessment</h3>
            <div class="stress-level">
              <span>Low</span>
              <div class="stress-meter">
                <div class="stress-fill" id="stressFill" style="width: 60%;"></div>
              </div>
              <span>High</span>
            </div>
            <p id="stressText">Moderate stress level - Some nervousness is normal!</p>
            <div id="stressTips">
              <h4>Relaxation Techniques:</h4>
              <ul id="relaxationTips"></ul>
            </div>
          </div>
        </div>

        <div id="mentorTab" class="tab-content" style="display: none;">
          <h3><i class='bx bx-group'></i> Connect with Industry Mentors</h3>
          <div id="mentorList"></div>
        </div>

        <div id="salaryTab" class="tab-content" style="display: none;">
          <h3><i class='bx bx-money'></i> Salary Negotiation Strategy</h3>
          <div id="salaryContent"></div>
        </div>

        <div id="researchTab" class="tab-content" style="display: none;">
          <h3><i class='bx bx-search'></i> Company Intelligence</h3>
          <div id="companyResearch"></div>
        </div>
      </div>

      <div id="result" class="results-container">
        <div id="debugPanel" class="result-panel debug-panel" style="display:none;">
          <h3>
            <i class='bx bx-bug'></i>
            Debug Information
          </h3>
          <div id="debugContent" class="debug-content"></div>
        </div>
        
        <div class="results-grid">
          <div class="result-panel">
            <h3>
              <i class='bx bx-bulb'></i>
              Customized Preparation Tips
            </h3>
            <ul id="custom_tips"></ul>
          </div>
          
          <div class="result-panel">
            <h3>
              <i class='bx bx-help-circle'></i>
              Common Interview Questions
            </h3>
            <ul id="common_questions"></ul>
          </div>
          
          <div class="result-panel">
            <h3>
              <i class='bx bx-check-circle'></i>
              Do's
            </h3>
            <ul id="dos"></ul>
          </div>
          
          <div class="result-panel">
            <h3>
              <i class='bx bx-x-circle'></i>
              Don'ts
            </h3>
            <ul id="donts"></ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Friendly error modal -->
  <div class="modal-overlay" id="friendlyErrorOverlay" role="dialog" aria-modal="true" aria-labelledby="friendlyErrorTitle">
    <div class="modal">
      <div class="modal-header">
        <i class='bx bx-error-circle'></i>
        <div>
          <h3 class="modal-title" id="friendlyErrorTitle">Interview Tips Unavailable</h3>
        </div>
      </div>
      <div class="modal-body" id="friendlyErrorMessage">Please try again in a few minutes.</div>
      <div class="modal-actions">
        <button type="button" id="friendlyErrorOkBtn">Got it</button>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("exitPage").addEventListener("click", function() {
        window.history.back();
    });

    // Helper functions
    function byId(id){ return document.getElementById(id); }
    function setStatus(msg){ byId('status').textContent = msg || ''; }
    function showDebug(message){
      byId('debugContent').textContent = message;
      byId('debugPanel').style.display = 'block';
    }
    function bulletList(el, arr){
      el.innerHTML = (arr||[]).map(x => `<li>${escapeHtml(x)}</li>`).join('');
    }
    function escapeHtml(str){
      str = (str === null || str === undefined) ? '' : String(str);
      return str.replace(/[&<>"']/g, s => ({
          '&':'&amp;',
          '<':'<',
          '>':'>',
          '"':'"',
          "'":'&#39;'
      }[s]));
    }

    // Modal helpers
    function showFriendlyError(title, message){
      const overlay = byId('friendlyErrorOverlay');
      byId('friendlyErrorTitle').textContent = title || 'Interview Tips Unavailable';
      byId('friendlyErrorMessage').textContent = message || 'Please try again in a few minutes.';
      overlay.style.display = 'flex';
    }
    function hideFriendlyError(){
      byId('friendlyErrorOverlay').style.display = 'none';
    }

    byId('friendlyErrorOkBtn').addEventListener('click', hideFriendlyError);
    byId('friendlyErrorOverlay').addEventListener('click', (e) => {
      if(e.target && e.target.id === 'friendlyErrorOverlay') hideFriendlyError();
    });

    document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape') hideFriendlyError();
    });

    // API endpoint
    const API_URL = new URL("prepare_interview.php", window.location.href).toString();

    // Prepare button handler
    async function prepare(){
      const payload = {
        position: byId('position').value.trim(),
        department: byId('department').value.trim(),
        company_name: byId('company_name').value.trim(),
        experience: byId('experience').value.trim(),
      };

      if(!payload.position || !payload.department){
        alert("Please fill Position and Department.");
        return;
      }

      setStatus("Generating…");
      byId('prepareBtn').disabled = true;

      try {
        const res = await fetch(API_URL, {
          method:'POST',
          headers:{ 'Content-Type':'application/json' },
          body: JSON.stringify(payload)
        });

        const text = await res.text();
        let json = null;

        try {
          json = text ? JSON.parse(text) : null;
        } catch (parseErr) {
          // Keep it friendly.
          showDebug("Server returned an invalid response. (Technical details logged server-side)");
          throw new Error('Invalid response');
        }

        if(!json || !json.ok){
          // Show only the friendly message (never raw provider errors).
          const title = (json && json.title) ? String(json.title) : "Interview Tips Temporarily Unavailable";
          const message = (json && json.error) ? String(json.error) : "Please try again in a few minutes.";
          // Optional generic debug indicator without exposing internals.
          showDebug('The AI service was unable to generate tips at this time.');
          throw new Error(JSON.stringify({ title, message }));
        }

        const data = json.data;
        byId('result').style.display = 'grid';
        bulletList(byId('custom_tips'), data.custom_tips);
        bulletList(byId('common_questions'), data.common_questions);
        bulletList(byId('dos'), data.dos);
        bulletList(byId('donts'), data.donts);
        setStatus("Done");

      } catch(e){
        console.error(e);

        // If we threw a JSON string with {title, message}, parse it.
        let friendly = null;
        try {
          friendly = JSON.parse(e && e.message ? String(e.message) : '');
        } catch(_){ /* ignore */ }

        const title = friendly && friendly.title ? friendly.title : "Interview Tips Unavailable";
        const message = friendly && friendly.message
          ? friendly.message
          : "We’re currently unable to generate interview tips because the AI service is temporarily unavailable or has reached its usage limit.\n\nPlease try again in a few minutes. If the problem persists, contact the system administrator.";

        showFriendlyError(title, message);
        setStatus("Error");

      } finally {
        byId('prepareBtn').disabled = false;
      }
    }

    // Copy all button
    async function copyAll(){
      const blocks = Array.from(document.querySelectorAll('#result .panel'))
        .map(p => p.querySelector('h3').textContent + "\n" +
          Array.from(p.querySelectorAll('li')).map(li => "- " + li.textContent).join("\n"))
        .join("\n\n");
      await navigator.clipboard.writeText(blocks);
      setStatus("Copied");
      setTimeout(()=>setStatus(""),1500);
    }

    // PDF generation
    async function toPdf(){
      setStatus("Building PDF…");
      if(!window.jspdf){
        await new Promise((resolve, reject)=>{
          const s=document.createElement('script');
          s.src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js";
          s.onload=resolve; s.onerror=reject;
          document.head.appendChild(s);
        });
      }
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ unit:'pt', format:'a4' });
      let y=40, left=40, width=515;

      doc.setFont('helvetica','bold'); doc.setFontSize(16);
      doc.text("Interview Preparation Tips", left, y); y+=20;
      doc.setFont('helvetica','normal'); doc.setFontSize(10);

      const meta = [
        "Position: " + byId('position').value,
        "Department: " + byId('department').value,
        "Company: " + byId('company_name').value,
        "Experience: " + byId('experience').value,
        "Generated: " + new Date().toLocaleString()
      ].join(" | ");
      doc.text(meta, left, y, { maxWidth: width }); y+=20;

      doc.setFontSize(12);
      const sections = Array.from(document.querySelectorAll('#result .panel'));
      for(const sec of sections){
        doc.setFont('helvetica','bold'); 
        doc.text(sec.querySelector('h3').textContent, left, y); y+=14;
        doc.setFont('helvetica','normal');
        const lines = Array.from(sec.querySelectorAll('li')).map(li => "• " + li.textContent);
        for(const line of lines){
          const wrapped = doc.splitTextToSize(line, width);
          for(const l of wrapped){
            if(y>780){ doc.addPage(); y=40; }
            doc.text(l, left, y); y+=14;
          }
        }
        y+=10;
      }
      doc.save("interview-prep.pdf");
      setStatus("PDF ready");
      setTimeout(()=>setStatus(""),1500);
    }

    // Event listeners
    byId('prepareBtn').addEventListener('click', prepare);
    byId('copyBtn').addEventListener('click', copyAll);
    byId('pdfBtn').addEventListener('click', toPdf);

    function switchTab(tabName) {
      document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
      document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
      byId(tabName + 'Tab').style.display = 'block';
      document.querySelector(`.tab-button[onclick="switchTab('${tabName}')"]`).classList.add('active');
    }

    // --- Dark Mode Logic (Synced) ---
    document.addEventListener('DOMContentLoaded', () => {
      const toggle = byId('dark-mode-toggle');
      const body = document.body;

      // Some pages may not include the toggle; guard it.
      if(!toggle) return;

      function applyTheme(isEnabled) {
        if (isEnabled) {
          body.classList.add('dark-mode');
          toggle.checked = true;
        } else {
          body.classList.remove('dark-mode');
          toggle.checked = false;
        }
      }

      const savedSetting = localStorage.getItem('darkMode');
      if (savedSetting === 'enabled') {
        applyTheme(true);
      } else {
        applyTheme(false);
      }

      toggle.addEventListener('change', () => {
        if (toggle.checked) {
          localStorage.setItem('darkMode', 'enabled');
          applyTheme(true);
        } else {
          localStorage.setItem('darkMode', 'disabled');
          applyTheme(false);
        }
      });
    });
  </script>
</body>
</html>

