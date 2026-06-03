<!DOCTYPE html>
<html>
<head>
  <title>Interview Prep Tool - Enhanced</title>
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
    .header p {
      font-size: 16px;
      opacity: 0.9;
      position: relative;
      z-index: 2;
    }
    .content {
      padding: 30px;
      overflow-y: auto;
    }
    #startBtn, #retakeBtn, #viewResultsBtn, #dashboardBtn {
      display: block;
      margin: 0 auto 30px auto;
      padding: 15px 30px;
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
    }
    #retakeBtn {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    #viewResultsBtn {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
      display: none;
    }
    #dashboardBtn {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
      display: none;
    }
    #startBtn::before, #retakeBtn::before, #viewResultsBtn::before, #dashboardBtn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    #startBtn:hover::before, #retakeBtn:hover::before, #viewResultsBtn:hover::before, #dashboardBtn:hover::before {
      left: 100%;
    }
    #startBtn:hover, #retakeBtn:hover, #viewResultsBtn:hover, #dashboardBtn:hover { 
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    #retakeBtn:hover {
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    #viewResultsBtn:hover {
      box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    }
    #dashboardBtn:hover {
      box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }
    #startBtn:disabled, #retakeBtn:disabled, #viewResultsBtn:disabled, #dashboardBtn:disabled { 
      background: #95a5a6; 
      cursor: not-allowed; 
      transform: none;
      box-shadow: none;
    }
    #loading {
      text-align: center;
      font-style: italic;
      color: #667eea;
      margin-bottom: 20px;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .loading-spinner {
      width: 20px;
      height: 20px;
      border: 2px solid #f3f3f3;
      border-top: 2px solid #667eea;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .question-box {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      padding: 25px;
      margin-bottom: 25px;
      transition: all 0.3s ease;
      border: 1px solid #e5e7eb;
      position: relative;
      overflow: hidden;
    }
    .question-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .question-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.12);
      border-color: #667eea;
    }
    .question-type {
      font-style: italic;
      color: #667eea;
      margin-bottom: 12px;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 12px;
      letter-spacing: 1px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .question-text {
      font-size: 16px;
      color: #2c3e50;
      margin-bottom: 20px;
      font-weight: 600;
      line-height: 1.5;
    }
    label {
      display: block;
      margin-bottom: 12px;
      cursor: pointer;
      padding: 12px 16px;
      border-radius: 8px;
      transition: all 0.3s ease;
      background-color: #f8fafc;
      border: 2px solid transparent;
    }
    label:hover {
      background-color: #e3f2fd;
      border-color: #667eea;
    }
    input[type="radio"] {
      margin-right: 12px;
      accent-color: #667eea;
      transform: scale(1.2);
    }
    input[type="radio"]:checked + span {
      color: #667eea;
      font-weight: 600;
    }
    #questions button {
      display: block;
      margin: 30px auto 0 auto;
      padding: 15px 30px;
      font-size: 18px;
      font-weight: 600;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: #fff;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }
    #questions button:hover { 
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    #questions button:disabled { 
      background: #95a5a6; 
      cursor: not-allowed; 
      transform: none;
      box-shadow: none;
    }
    #feedback, #dashboard {
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      margin-top: 30px;
      line-height: 1.6;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border: 1px solid #e5e7eb;
      display: none;
    }
    #feedback h3, #dashboard h3 {
      color: #2c3e50;
      margin-bottom: 25px;
      text-align: center;
      font-size: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }
    .score-summary {
      text-align: center;
      margin-bottom: 30px;
      padding: 25px;
      background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
      border-radius: 12px;
      border: 2px solid #667eea;
      position: relative;
      overflow: hidden;
    }
    .score-summary::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
      animation: scoreShimmer 3s infinite;
    }
    @keyframes scoreShimmer {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .score-summary p {
      font-size: 20px;
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 15px;
      position: relative;
      z-index: 2;
    }
    .performance-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 14px;
      margin: 10px 5px;
      position: relative;
      z-index: 2;
    }
    .badge-excellent {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      animation: pulse 2s infinite;
    }
    .badge-good {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }
    .badge-average {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }
    .badge-needs-improvement {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    .progress-bar-container {
      background-color: #e5e7eb;
      border-radius: 25px;
      overflow: hidden;
      height: 25px;
      width: 100%;
      margin: 15px 0;
      box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
      position: relative;
      z-index: 2;
    }
    .progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #667eea, #764ba2);
      width: 0%;
      transition: width 1.5s ease-in-out;
      border-radius: 25px;
      position: relative;
      overflow: hidden;
    }
    .progress-bar::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      animation: shimmer 2s infinite;
    }
    @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }
    .category-breakdown {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin: 30px 0;
    }
    .category-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      border: 1px solid #e5e7eb;
      transition: all 0.3s ease;
    }
    .category-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .category-card h4 {
      color: #667eea;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 16px;
    }
    .category-score {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 10px;
    }
    .category-progress {
      background-color: #f1f5f9;
      border-radius: 10px;
      height: 8px;
      overflow: hidden;
    }
    .category-progress-bar {
      height: 100%;
      border-radius: 10px;
      transition: width 1s ease-in-out;
    }
    .feedback-item {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 20px;
      background-color: #f8fafc;
      transition: all 0.3s ease;
    }
    .feedback-item.correct {
      border-left: 6px solid #10b981;
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    }
    .feedback-item.incorrect {
      border-left: 6px solid #ef4444;
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    }
    .feedback-item p {
      margin-bottom: 10px;
    }
    .feedback-question {
      font-weight: 600;
      color: #2c3e50;
      font-size: 16px;
      margin-bottom: 15px;
    }
    .answer {
      font-weight: 600;
      padding: 8px 12px;
      border-radius: 6px;
      display: inline-block;
      margin: 5px 0;
    }
    .answer.correct { 
      color: #059669;
      background-color: rgba(16, 185, 129, 0.1);
    }
    .answer.incorrect { 
      color: #dc2626;
      background-color: rgba(239, 68, 68, 0.1);
    }
    .insights-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      border-radius: 12px;
      margin: 30px 0;
      position: relative;
      overflow: hidden;
    }
    .insights-section::before {
      content: '✨';
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 30px;
      animation: sparkle 2s ease-in-out infinite;
    }
    @keyframes sparkle {
      0%, 100% { opacity: 0.5; transform: scale(1); }
      50% { opacity: 1; transform: scale(1.2); }
    }
    .insights-section h3 {
      margin-bottom: 20px;
      font-size: 24px;
    }
    .insight-item {
      background: rgba(255, 255, 255, 0.1);
      padding: 15px;
      border-radius: 8px;
      margin: 10px 0;
      backdrop-filter: blur(10px);
    }
    .recommendations-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin: 20px 0;
    }
    .recommendation-card {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      border-left: 4px solid #667eea;
    }
    .recommendation-card h4 {
      color: #667eea;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
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
    /* Dashboard Styles */
    .dashboard-section {
      margin-bottom: 40px;
      padding: 20px;
      border-radius: 12px;
      background: #f8fafc;
      border: 1px solid #e5e7eb;
    }
    .dashboard-section h4 {
      color: #667eea;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #667eea;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      text-align: center;
      border: 1px solid #e5e7eb;
      transition: all 0.3s ease;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: #667eea;
      margin: 15px 0;
    }
    .stat-label {
      font-size: 14px;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .chart-container {
      height: 300px;
      margin: 30px 0;
      position: relative;
    }
    .progress-chart {
      display: flex;
      align-items: flex-end;
      height: 250px;
      gap: 15px;
      padding: 20px 10px;
    }
    .chart-bar {
      flex: 1;
      background: linear-gradient(to top, #667eea, #764ba2);
      border-radius: 8px 8px 0 0;
      position: relative;
      transition: all 0.5s ease;
      min-width: 30px;
    }
    .chart-bar:hover {
      transform: translateY(-10px);
    }
    .chart-bar-label {
      position: absolute;
      bottom: -25px;
      left: 0;
      right: 0;
      text-align: center;
      font-size: 12px;
      color: #64748b;
    }
    .chart-bar-value {
      position: absolute;
      top: -25px;
      left: 0;
      right: 0;
      text-align: center;
      font-weight: 700;
      color: #667eea;
    }
    .assessment-history {
      margin-top: 30px;
    }
    .history-item {
      display: flex;
      align-items: center;
      padding: 15px;
      border-radius: 12px;
      background: white;
      margin-bottom: 15px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      border: 1px solid #e5e7eb;
      transition: all 0.3s ease;
    }
    .history-item:hover {
      transform: translateX(5px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .history-date {
      min-width: 120px;
      font-weight: 600;
      color: #667eea;
    }
    .history-score {
      font-size: 24px;
      font-weight: 700;
      margin: 0 20px;
      color: #2c3e50;
    }
    .history-badge {
      margin-left: auto;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 600;
    }
    .strengths-weaknesses {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin: 30px 0;
    }
    .strength-card, .weakness-card {
      padding: 20px;
      border-radius: 12px;
      background: white;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .strength-card {
      border-top: 4px solid #10b981;
    }
    .weakness-card {
      border-top: 4px solid #ef4444;
    }
    .strength-card h5, .weakness-card h5 {
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .strength-card h5 {
      color: #10b981;
    }
    .weakness-card h5 {
      color: #ef4444;
    }
    .skill-list {
      list-style: none;
    }
    .skill-item {
      padding: 10px 0;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      justify-content: space-between;
    }
    .skill-name {
      font-weight: 600;
    }
    .skill-percentage {
      font-weight: 700;
      color: #667eea;
    }
    /* Animation for question appearance */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .question-box, .feedback-item, .category-card, .stat-card, .history-item {
      animation: fadeInUp 0.5s ease-out;
    }
    /* Confetti Animation */
    .confetti {
      position: fixed;
      top: -10px;
      left: 0;
      width: 100%;
      height: 100vh;
      pointer-events: none;
      overflow: hidden;
      z-index: 1000;
    }
    .confetti-piece {
      position: absolute;
      width: 10px;
      height: 10px;
      background: #667eea;
      animation: confetti-fall 3s ease-out infinite;
    }
    @keyframes confetti-fall {
      0% {
        transform: translateY(-100vh) rotateZ(0deg);
        opacity: 1;
      }
      100% {
        transform: translateY(100vh) rotateZ(720deg);
        opacity: 0;
      }
    }
    /* Category Selection Modal */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: #fefefe;
      margin: 10% auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      width: 90%;
      max-width: 800px;
      max-height: 80vh;
      overflow-y: auto;
    }
    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
    }
    .close:hover {
      color: #000;
    }
    .category-selection-header {
      text-align: center;
      margin-bottom: 30px;
      color: #2c3e50;
    }
    .category-group {
      margin-bottom: 40px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
    }
    .category-group h3 {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #667eea;
      color: #667eea;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .category-option {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 8px;
      background-color: #f8fafc;
      transition: all 0.3s ease;
    }
    .category-option:hover {
      background-color: #e3f2fd;
      transform: translateX(5px);
    }
    .category-option input[type="checkbox"] {
      accent-color: #667eea;
      transform: scale(1.3);
      margin-right: 15px;
    }
    .category-option label {
      margin-bottom: 0;
      cursor: pointer;
      padding: 0;
      background: none;
      border: none;
    }
    .category-description {
      font-size: 14px;
      color: #64748b;
      margin-top: 5px;
      font-style: italic;
    }
    .modal-buttons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
    }
    .modal-button {
      padding: 12px 30px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .select-categories-btn {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .select-categories-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    .cancel-btn {
      background: #e5e7eb;
      color: #4b5563;
    }
    .cancel-btn:hover {
      background: #d1d5db;
    }
    /* Timer Styles */
    #timer {
      display: none;
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 20px;
      font-weight: 600;
      color: #ffffff;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 12px 18px;
      border-radius: 12px;
      text-align: center;
      width: fit-content;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      letter-spacing: 0.5px;
      z-index: 1000;
    }
    
    .time-warning {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
      animation: pulseWarning 1s infinite;
    }
    
    .time-expired {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    }
    
    @keyframes pulseWarning {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    
    /* Time limit selection in modal */
    .time-limit-section {
      margin: 30px 0;
      padding: 20px;
      background: #f8fafc;
      border-radius: 12px;
      border: 2px solid #e5e7eb;
    }
    
    .time-limit-section h4 {
      margin-bottom: 20px;
      color: #667eea;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .time-limit-options {
      display: flex;
      gap: 15px;
      flex-wrap: wrap;
      justify-content: center;
    }
    
    .time-limit-option {
      padding: 10px 20px;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      background: white;
      font-weight: 600;
    }
    
    .time-limit-option:hover {
      border-color: #667eea;
      transform: translateY(-2px);
    }
    
    .time-limit-option.selected {
      border-color: #667eea;
      background: #e3f2fd;
      color: #667eea;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .container {
        margin: 10px;
        min-height: calc(100vh - 20px);
      }
      .header {
        padding: 20px;
      }
      .header h2 {
        font-size: 24px;
      }
      .content {
        padding: 20px;
      }
      .question-box {
        padding: 20px;
      }
      .category-breakdown, .stats-grid, .strengths-weaknesses, .recommendations-grid {
        grid-template-columns: 1fr;
      }
      .history-item {
        flex-direction: column;
        text-align: center;
      }
      .history-date {
        min-width: auto;
        margin-bottom: 10px;
      }
      .modal-content {
        margin: 5% auto;
        padding: 20px;
      }
      .time-limit-options {
        flex-direction: column;
        align-items: center;
      }
      .time-limit-option {
        width: 100%;
        max-width: 300px;
        text-align: center;
      }
    }
    #startBtn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* --- DARK MODE TOGGLE STYLES --- */
    .theme-switch-wrapper {
        position: absolute;
        top: 30px;     /* Aligned with header padding */
        right: 30px;   /* Aligned with header padding */
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
    

    /* --- DARK MODE THEME STYLES (Apply to body) --- */
    body.dark-mode {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: #e2e8f0; 
    }

    body.dark-mode .container {
      background-color: #1f2937; 
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.6);
    }

    body.dark-mode .header {
      background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.4);
    }

    body.dark-mode p, body.dark-mode label, body.dark-mode h1, body.dark-mode h2, body.dark-mode h3 {
        color: #e2e8f0;
    }

    body.dark-mode input[type="text"], body.dark-mode select, body.dark-mode textarea {
        background-color: #374151;
        border-color: #4b5563;
        color: #e2e8f0;
    }

    /* Modal Specific Dark Mode Fixes */
    body.dark-mode .modal-content {
        background-color: #1f2937 !important; 
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.8);
        border: 1px solid #334155;
    }

    body.dark-mode .modal-content p {
        color: #94a3b8 !important; 
    }

    body.dark-mode .category-group h3 {
        color: #93c5fd !important; 
    }

    body.dark-mode .category-option {
        background-color: #2d3a4b; 
        border: 1px solid #3e4c5e;
    }

    body.dark-mode .category-option label strong {
        color: #e2e8f0 !important;
    }

    body.dark-mode .time-limit-option {
        background-color: #334155;
        color: #e2e8f0;
        border: 1px solid #4a5568;
    }

    body.dark-mode .time-limit-option.selected {
        background-color: #6366f1;
        color: white;
    }

    /* General Dark Mode Contrast Fixes */
    body.dark-mode .container *,
    body.dark-mode p, 
    body.dark-mode label, 
    body.dark-mode h1, 
    body.dark-mode h2, 
    body.dark-mode h3 {
        color: #e2e8f0 !important; 
    }

    /* Force dark background on all internal wrappers to avoid white bars */
    body.dark-mode .container div,
    body.dark-mode .container section,
    body.dark-mode .container article,
    body.dark-mode .container li,
    body.dark-mode .container .assessment-wrapper, 
    body.dark-mode .container .question-card,      
    body.dark-mode .container form,
    body.dark-mode .container table,
    body.dark-mode .container ul,
    body.dark-mode .container ol {
        background-color: inherit; 
    }

    body.dark-mode .question-box,
    body.dark-mode .dashboard-section,
    body.dark-mode .category-card,
    body.dark-mode .stat-card,
    body.dark-mode .history-item {
      background-color: #1f2937 !important;
      border-color: #334155 !important;
    }

    body.dark-mode .feedback-item {
      background-color: #2d3a4b !important;
      border: 1px solid #3e4c5e !important;
    }
    
    body.dark-mode .feedback-item.correct {
      background: #2d3a4b !important;
      border-left: 6px solid #10b981 !important;
    }
    
    body.dark-mode .feedback-item.incorrect {
      background: #2d3a4b !important;
      border-left: 6px solid #ef4444 !important;
    }

    body.dark-mode .answer.correct {
      color: #84cc16 !important; 
      background-color: rgba(16, 185, 129, 0.2) !important; 
    }
    
    body.dark-mode .answer.incorrect { 
      color: #f87171 !important; 
      background-color: rgba(239, 68, 68, 0.2) !important; 
    }
    
    body.dark-mode .feedback-question {
      color: #e2e8f0 !important;
    }

    body.dark-mode label {
        background-color: #2d3a4b !important;
        border-color: #3e4c5e !important;
    }
    body.dark-mode label:hover {
        background-color: #374151 !important;
    }

    body.dark-mode .recommendation-card {
      background: #1f2937 !important;
      border: 1px solid #334155 !important;
      border-left: 4px solid #667eea !important;
    }
    
    body.dark-mode .recommendation-card h4 {
      color: #93c5fd !important;
    }
    
    body.dark-mode .recommendation-card p {
      color: #e2e8f0 !important;
    }

    /* Fixed White Bar Issues */
    body.dark-mode #dashboard,
    body.dark-mode #feedback {
      background: #1f2937 !important;
      border: 1px solid #334155 !important;
    }
    
    body.dark-mode .score-summary {
      background: #1f2937 !important; 
      border: 2px solid #334155 !important;
    }
    
    body.dark-mode .stat-value {
        color: #6366f1 !important;
    }

  </style>
</head>
<body>
  <div class="container">
      <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>
    <div class="header">
      <h2>
        <i class='bx bx-book'></i>
        Interview Assessment
      </h2>
      <p>Assess your reasoning, personality, and behavior to gain a complete picture of your potential.</p><br>
      <p>Aptitude Questions → Challenge your analytical thinking with numerical, verbal, logical, and diagram-based reasoning.</p>
      <p>Personality Questions → Discover your strengths, preferences, and working style through leading personality models.</p>
      <p>Behavioral & Situational Questions → Explore real-world scenarios to reveal your decision-making, values, and work habits.</p>
      <p>Job-Specific Technical Questions → Test your practical skills and knowledge relevant to the position you've applied for.</p>
    </div>
    <div class="content">
      <div id="timer">
        ⏱ Time Remaining: <span id="timeRemaining">15:00</span>
      </div>
      <button id="startBtn">
        <i class='bx bx-play'></i>
        Start Assessment
      </button>
      <button id="viewResultsBtn">
        <i class='bx bx-show'></i>
        View Previous Results
      </button>
      <button id="dashboardBtn">
        <i class='bx bx-dashboard'></i>
        View Summary Dashboard
      </button>
      <div id="loading"></div>
      <div id="questions"></div>
      <div id="feedback"></div>
      <div id="dashboard"></div>
    </div>
  </div>
  <div id="categoryModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2 class="category-selection-header">Select Question Categories</h2>
      <p style="text-align: center; margin-bottom: 30px; color: #64748b;">
        Choose the categories you want to include in your assessment. You can select as many as you'd like.
      </p>
      <div class="category-group">
        <h3><i class='bx bx-brain'></i> Aptidute Assessment</h3>
        <div class="category-option">
          <input type="checkbox" id="logicalReasoning" checked>
          <div>
            <label for="logicalReasoning"><strong>Logical Reasoning</strong></label>
            <p class="category-description">Test your ability to identify patterns, sequences, and deductive reasoning.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="numericalReasoning" checked>
          <div>
            <label for="numericalReasoning"><strong>Numerical Reasoning</strong></label>
            <p class="category-description">Assess your ability to work with numbers, percentages, ratios, and data interpretation.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="verbalReasoning" checked>
          <div>
            <label for="verbalReasoning"><strong>Verbal Reasoning</strong></label>
            <p class="category-description">Evaluate your comprehension, vocabulary, and ability to understand written information.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="diagrammaticReasoning" checked>
          <div>
            <label for="diagrammaticReasoning"><strong>Diagrammatic Reasoning</strong></label>
            <p class="category-description">Test your ability to interpret flowcharts, diagrams, and process logic.</p>
          </div>
        </div>
      </div>
      <div class="category-group">
        <h3><i class='bx bx-user-circle'></i> Other Assessment Types</h3>
        <div class="category-option">
          <input type="checkbox" id="mbti" checked>
          <div>
            <label for="mbti"><strong>MBTI (16 Personalities)</strong></label>
            <p class="category-description">Discover your personality type and how it influences your work preferences.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="discProfiling" checked>
          <div>
            <label for="discProfiling"><strong>DISC Profiling</strong></label>
            <p class="category-description">Assess your behavioral style in workplace situations.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="oceanBigFive" checked>
          <div>
            <label for="oceanBigFive"><strong>OCEAN/Big Five</strong></label>
            <p class="category-description">Evaluate your personality across five key dimensions.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="motivationTest" checked>
          <div>
            <label for="motivationTest"><strong>Motivation Test</strong></label>
            <p class="category-description">Understand what drives you - intrinsic or extrinsic motivators.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="situationalJudgement" checked>
          <div>
            <label for="situationalJudgement"><strong>Situational Judgement Tests</strong></label>
            <p class="category-description">Assess how you would respond to workplace scenarios and challenges.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="culturalFit" checked>
          <div>
            <label for="culturalFit"><strong>Cultural Fit Tests</strong></label>
            <p class="category-description">Evaluate how well your values align with organizational culture.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="timeManagement" checked>
          <div>
            <label for="timeManagement"><strong>Time Management & Priority Ranking</strong></label>
            <p class="category-description">Test your ability to prioritize tasks and manage time effectively.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="workEthics" checked>
          <div>
            <label for="workEthics"><strong>Work Ethics Self-Assessment</strong></label>
            <p class="category-description">Reflect on your professional values and ethical decision-making.</p>
          </div>
        </div>
        <div class="category-option">
          <input type="checkbox" id="jobTechnical" checked>
          <div>
            <label for="jobTechnical"><strong>Job-Specific Technical Assessment</strong></label>
            <p class="category-description">Evaluate your technical knowledge and skills relevant to the applied position.</p>
          </div>
        </div>
      </div>
      
      <div class="time-limit-section">
        <h4><i class='bx bx-time-five'></i> Select Time Limit</h4>
        <div class="time-limit-options">
          <div class="time-limit-option" data-minutes="1">1 Minutes</div>
          <div class="time-limit-option selected" data-minutes="10">10 Minutes</div>
          <div class="time-limit-option" data-minutes="20">20 Minutes</div>
          <div class="time-limit-option" data-minutes="30">30 Minutes</div>
          <div class="time-limit-option" data-minutes="45">45 Minutes</div>
          <div class="time-limit-option" data-minutes="60">60 Minutes</div>
        </div>
        <p style="margin-top: 15px; text-align: center; color: #64748b; font-size: 14px;">
          <i class='bx bx-info-circle'></i> The assessment will be automatically submitted when time expires.
        </p>
      </div>
      
      <div class="modal-buttons">
        <button class="modal-button cancel-btn" id="cancelSelection">Cancel</button>
        <button class="modal-button select-categories-btn" id="confirmSelection">Start Assessment with Selected Categories</button>
      </div>
    </div>
  </div>
  <script>
    document.getElementById("exitPage").addEventListener("click", function() {
        window.history.back();
    });

    let assessmentData = null;
    let userAnswers = null;
    let hasCompletedAssessment = false;
    let assessmentHistory = [];
    let timerInterval = null;
    let secondsRemaining = 0;
    let selectedTimeLimit = 15; // Default to 15 minutes
    let startTime = null;
    let timeTaken = "00:00";
    
    // Define all question categories
    const allCategories = {
      'logicalReasoning': 'Logical Reasoning',
      'numericalReasoning': 'Numerical Reasoning',
      'verbalReasoning': 'Verbal Reasoning',
      'diagrammaticReasoning': 'Diagrammatic Reasoning',
      'mbti': 'MBTI',
      'discProfiling': 'DISC Profiling',
      'oceanBigFive': 'OCEAN/Big Five',
      'motivationTest': 'Motivation Test',
      'situationalJudgement': 'Situational Judgement Tests',
      'culturalFit': 'Cultural Fit Tests',
      'timeManagement': 'Time Management & Priority Ranking Scenarios',
      'workEthics': 'Work Ethics Self-Assessment',
      'jobTechnical': 'Job-Specific Technical Assessment'
    };

    function getTypeIcon(type) {
      const icons = {
        'Numerical Reasoning': 'bx-calculator',
        'Verbal Reasoning': 'bx-book-reader',
        'Logical Reasoning': 'bx-brain',
        'Diagrammatic Reasoning': 'bx-sitemap',
        'MBTI': 'bx-user-circle',
        'DISC Profiling': 'bx-pyramid',
        'OCEAN/Big Five': 'bx-globe',
        'Motivation Test': 'bx-bulb',
        'Situational Judgement Tests': 'bx-question-mark',
        'Cultural Fit Tests': 'bx-group',
        'Time Management & Priority Ranking Scenarios': 'bx-time-five',
        'Work Ethics Self-Assessment': 'bx-check-shield',
        'Job-Specific Technical Assessment': 'bx-code-block'
      };
      return icons[type] || 'bx-question-mark';
    }

    const startBtn = document.getElementById("startBtn");
    const viewResultsBtn = document.getElementById("viewResultsBtn");
    const dashboardBtn = document.getElementById("dashboardBtn");
    const loadingDiv = document.getElementById("loading");
    const feedbackDiv = document.getElementById("feedback");
    const dashboardDiv = document.getElementById("dashboard");
    const categoryModal = document.getElementById("categoryModal");
    const closeBtn = document.getElementsByClassName("close")[0];
    const cancelSelectionBtn = document.getElementById("cancelSelection");
    const confirmSelectionBtn = document.getElementById("confirmSelection");
    const timerElement = document.getElementById("timer");
    const timeRemainingElement = document.getElementById("timeRemaining");

    startBtn.addEventListener("click", showCategorySelection);
    viewResultsBtn.addEventListener("click", showPreviousResults);
    dashboardBtn.addEventListener("click", showDashboard);
    closeBtn.addEventListener("click", closeModal);
    cancelSelectionBtn.addEventListener("click", closeModal);
    confirmSelectionBtn.addEventListener("click", startAssessmentWithSelection);
    
    // Handle time limit selection
    document.querySelectorAll('.time-limit-option').forEach(option => {
      option.addEventListener('click', function() {
        document.querySelectorAll('.time-limit-option').forEach(opt => {
          opt.classList.remove('selected');
        });
        this.classList.add('selected');
        selectedTimeLimit = parseInt(this.getAttribute('data-minutes'));
      });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target == categoryModal) {
        closeModal();
      }
    });

    // Check if there are previous results on page load
    window.addEventListener('DOMContentLoaded', function() {
      loadAssessmentData();
      checkForPreviousResults();
      if (assessmentHistory.length > 0) {
        dashboardBtn.style.display = 'block';
      }
    });

    function loadAssessmentData() {
      // Load assessment history from localStorage
      const savedHistory = localStorage.getItem('interviewAssessmentHistory');
      if (savedHistory) {
        assessmentHistory = JSON.parse(savedHistory);
      }
    }

    function saveToAssessmentHistory(answers, score, percentage, timeTaken) {
      const assessmentRecord = {
        id: Date.now(),
        date: new Date().toLocaleDateString('en-US', { 
          year: 'numeric', 
          month: 'short', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        }),
        score: score,
        total: answers.length,
        percentage: percentage,
        timeTaken: timeTaken,
        categoryBreakdown: getCategoryBreakdown(answers),
        answers: answers
      };
      assessmentHistory.unshift(assessmentRecord); // Add to beginning of array
      // Save to localStorage
      localStorage.setItem('interviewAssessmentHistory', JSON.stringify(assessmentHistory));
    }

    function checkForPreviousResults() {
      // Check if we have assessment data stored in localStorage
      const savedAssessment = localStorage.getItem('interviewAssessmentData');
      const savedAnswers = localStorage.getItem('interviewAssessmentAnswers');
      const savedCompletion = localStorage.getItem('hasCompletedAssessment');
      
      if (savedAssessment && savedAnswers && savedCompletion === 'true') {
        assessmentData = JSON.parse(savedAssessment);
        userAnswers = JSON.parse(savedAnswers);
        hasCompletedAssessment = true;
        viewResultsBtn.style.display = 'block';
        
        if (assessmentHistory.length > 0) {
          dashboardBtn.style.display = 'block';
        }
      }
    }

    function saveAssessmentResults(answers, timeTaken) {
      // Save to localStorage for future viewing
      localStorage.setItem('interviewAssessmentData', JSON.stringify(assessmentData));
      localStorage.setItem('interviewAssessmentAnswers', JSON.stringify(answers));
      localStorage.setItem('hasCompletedAssessment', 'true');
      hasCompletedAssessment = true;
      viewResultsBtn.style.display = 'block';
      
      // Calculate score for history record
      const correctCount = answers.filter(a => a.user_answer === a.correct).length;
      const percentage = Math.round((correctCount / answers.length) * 100);
      
      // Save to assessment history
      saveToAssessmentHistory(answers, correctCount, percentage, timeTaken);
      
      // Show dashboard button if we have history
      if (assessmentHistory.length > 0) {
        dashboardBtn.style.display = 'block';
      }
    }

    function showPreviousResults() {
      if (hasCompletedAssessment && assessmentData && userAnswers) {
        // Hide questions and dashboard if visible
        document.getElementById("questions").innerHTML = "";
        dashboardDiv.style.display = 'none';
        // Show feedback
        feedbackDiv.style.display = 'block';
        // Scroll to feedback
        feedbackDiv.scrollIntoView({ behavior: 'smooth' });
        // Generate feedback from saved data
        generateEnhancedFeedback(userAnswers);
      } else {
        alert("No previous assessment results found. Please complete an assessment first.");
      }
    }
    
    function showDashboard() {
      if (assessmentHistory.length > 0) {
        // Hide questions and feedback if visible
        document.getElementById("questions").innerHTML = "";
        feedbackDiv.style.display = 'none';
        // Show dashboard
        dashboardDiv.style.display = 'block';
        // Generate dashboard
        generateDashboard();
        // Scroll to dashboard
        dashboardDiv.scrollIntoView({ behavior: 'smooth' });
      } else {
        alert("No assessment history found. Please complete an assessment first.");
      }
    }

    function showCategorySelection() {
      categoryModal.style.display = 'block';
    }

    function closeModal() {
      categoryModal.style.display = 'none';
    }

    function startAssessmentWithSelection() {
      // Get selected categories
      const selectedCategories = [];
      Object.keys(allCategories).forEach(key => {
        const checkbox = document.getElementById(key);
        if (checkbox.checked) {
          selectedCategories.push(allCategories[key]);
        }
      });
      
      // Validate at least one category is selected
      if (selectedCategories.length === 0) {
        alert("Please select at least one category to proceed with the assessment.");
        return;
      }
      
      // Close modal
      closeModal();
      
      // Start assessment with selected categories
      startTest(selectedCategories);
    }

    async function startTest(selectedCategories = null) {
      startBtn.disabled = true;
      document.getElementById("questions").innerHTML = "";
      feedbackDiv.innerHTML = "";
      feedbackDiv.style.display = 'none';
      dashboardDiv.style.display = 'none';
      
      loadingDiv.innerHTML = `
        <div class="loading-spinner"></div>
        Generating personalized questions, please wait...
      `;

      // Filter mock questions based on selected categories
      setTimeout(() => {
        const allMockQuestions = [
          {
            question: "If a product costs $120 after a 20% discount, what was the original price?",
            type: "Numerical Reasoning",
            options: ["$144", "$150", "$160", "$140"],
            scoring: {"0": 0, "1": 1, "2": 0, "3": 0}
          },
          {
            question: "Complete the sequence: 2, 6, 18, 54, ?",
            type: "Numerical Reasoning", 
            options: ["108", "162", "216", "144"],
            scoring: {"0": 0, "1": 1, "2": 0, "3": 0}
          },
          {
            question: "Choose the word that best completes the analogy: Book is to Library as Car is to ?",
            type: "Verbal Reasoning",
            options: ["Road", "Garage", "Engine", "Driver"],
            scoring: {"0": 0, "1": 1, "2": 0, "3": 0}
          },
          {
            question: "Which word is spelled incorrectly? Accomodate, Necessary, Achievement, Privilege",
            type: "Verbal Reasoning",
            options: ["Accomodate", "Necessary", "Achievement", "Privilege"],
            scoring: {"0": 1, "1": 0, "2": 0, "3": 0}
          },
          {
            question: "All birds can fly. Penguins are birds. Therefore, penguins can fly. This reasoning is:",
            type: "Logical Reasoning",
            options: ["Valid and sound", "Valid but unsound", "Invalid", "Cannot be determined"],
            scoring: {"0": 0, "1": 1, "2": 0, "3": 0}
          },
          {
            question: "If A > B and B > C, then:",
            type: "Logical Reasoning",
            options: ["A = C", "A < C", "A > C", "Cannot be determined"],
            scoring: {"0": 0, "1": 0, "2": 1, "3": 0}
          },
          {
            question: "When working in a team, I prefer to:",
            type: "MBTI",
            options: ["Lead discussions and brainstorm openly", "Listen first, then contribute thoughtfully", "Focus on practical implementation", "Ensure everyone's ideas are heard"],
            scoring: {"0": 1, "1": 1, "2": 1, "3": 1}
          },
          {
            question: "In a conflict situation at work, I typically:",
            type: "Situational Judgement Tests",
            options: ["Address it immediately and directly", "Try to mediate between parties", "Avoid confrontation when possible", "Escalate to management"],
            scoring: {"0": 1, "1": 1, "2": 0, "3": 0}
          },
          {
            question: "When faced with a tight deadline, I usually:",
            type: "Time Management & Priority Ranking Scenarios",
            options: ["Prioritize tasks by importance and urgency", "Work longer hours to complete everything", "Delegate tasks to team members", "Request an extension"],
            scoring: {"0": 1, "1": 0, "2": 1, "3": 0}
          },
          {
            question: "I feel most motivated at work when:",
            type: "Motivation Test",
            options: ["I receive recognition for my achievements", "I'm working on challenging problems", "I have a good work-life balance", "I'm earning a high salary"],
            scoring: {"0": 1, "1": 1, "2": 1, "3": 0}
          },
          {
            question: "In a group project, I tend to:",
            type: "DISC Profiling",
            options: ["Take charge and delegate tasks", "Encourage team members and build consensus", "Follow instructions carefully and meet deadlines", "Analyze the plan and suggest improvements"],
            scoring: {"0": 1, "1": 1, "2": 1, "3": 1}
          },
          {
            question: "When learning something new, I prefer to:",
            type: "OCEAN/Big Five",
            options: ["Experiment with different approaches", "Follow a structured step-by-step guide", "Discuss it with others to gain insights", "Research extensively before trying"],
            scoring: {"0": 1, "1": 1, "2": 1, "3": 1}
          },
          {
            question: "Company culture is important to me because:",
            type: "Cultural Fit Tests",
            options: ["It affects my daily work experience", "It determines career growth opportunities", "It influences team collaboration", "It reflects the company's values and ethics"],
            scoring: {"0": 1, "1": 1, "2": 1, "3": 1}
          },
          {
            question: "If I witness unethical behavior at work, I would:",
            type: "Work Ethics Self-Assessment",
            options: ["Report it to management immediately", "Discuss it with the person involved first", "Document the incident before taking action", "Consult HR or ethics guidelines"],
            scoring: {"0": 1, "1": 1, "2": 1, "3": 1}
          },
          {
            question: "Which diagram best represents the process flow for handling customer complaints?",
            type: "Diagrammatic Reasoning",
            options: ["Option A: Linear process with feedback loop", "Option B: Parallel processing with multiple endpoints", "Option C: Hierarchical decision tree", "Option D: Circular process with no clear end"],
            scoring: {"0": 1, "1": 0, "2": 0, "3": 0}
          },
          // 🔹 Added Technical Questions
          {
            question: "Which of the following is a valid SQL statement to select all records from a table named 'employees'?",
            type: "Job-Specific Technical Assessment",
            options: ["SELECT * FROM employees;", "GET ALL employees;", "SHOW employees;", "FETCH employees;"],
            scoring: {"0": 1, "1": 0, "2": 0, "3": 0}
          },
          {
            question: "In object-oriented programming, which principle is being applied when a subclass provides a specific implementation of a method already defined in its superclass?",
            type: "Job-Specific Technical Assessment",
            options: ["Encapsulation", "Polymorphism", "Abstraction", "Inheritance"],
            scoring: {"0": 0, "1": 1, "2": 0, "3": 0}
          }
        ];
        
        // If specific categories are selected, filter questions
        let filteredQuestions = allMockQuestions;
        if (selectedCategories && selectedCategories.length > 0) {
          filteredQuestions = allMockQuestions.filter(q => selectedCategories.includes(q.type));
        }
        
        // If no questions match the selected categories, use all questions
        if (filteredQuestions.length === 0) {
          filteredQuestions = allMockQuestions;
        }
        
        assessmentData = filteredQuestions;
        renderQuestions(filteredQuestions);
        loadingDiv.innerHTML = "";
        startBtn.disabled = false;
      }, 2000);
    }

    function renderQuestions(questions) {
      // Initialize timer
      secondsRemaining = selectedTimeLimit * 60; // Convert minutes to seconds
      startTime = new Date();
      startTimer();
      
      const container = document.getElementById("questions");
      container.innerHTML = "";

      questions.forEach((q, idx) => {
        setTimeout(() => {
          const div = document.createElement("div");
          div.className = "question-box";
          div.innerHTML = `
            <p class="question-type">
              <i class='bx ${getTypeIcon(q.type)}'></i>
              ${q.type}
            </p>
            <p class="question-text"><b>Q${idx+1}:</b> ${q.question}</p>
            ${q.options.map((opt, i) => `
              <label>
                <input type="radio" name="q${idx}" value="${i}">
                <span>${opt}</span>
              </label>
            `).join("")}
          `;
          container.appendChild(div);
        }, idx * 100);
      });

      setTimeout(() => {
        const submitBtn = document.createElement("button");
        submitBtn.innerHTML = `
          <i class='bx bx-check'></i>
          Submit Assessment
        `;
        submitBtn.onclick = () => submitAnswers(questions, submitBtn);
        container.appendChild(submitBtn);
      }, questions.length * 100);
    }

    function startTimer() {
      timerElement.style.display = "block";
      updateTimerDisplay();
      
      timerInterval = setInterval(() => {
        secondsRemaining--;
        
        // Update display
        updateTimerDisplay();
        
        // Check if time is running out (last 60 seconds)
        if (secondsRemaining <= 60 && secondsRemaining > 0) {
          timerElement.classList.add('time-warning');
        }
        
        // Check if time has expired
        if (secondsRemaining <= 0) {
          stopTimer();
          autoSubmitAssessment();
        }
      }, 1000);
    }

    function stopTimer() {
      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
      
      // Calculate time taken
      const endTime = new Date();
      const timeDiff = Math.floor((endTime - startTime) / 1000);
      const minutes = String(Math.floor(timeDiff / 60)).padStart(2, '0');
      const seconds = String(timeDiff % 60).padStart(2, '0');
      timeTaken = `${minutes}:${seconds}`;
    }

    function updateTimerDisplay() {
      const minutes = String(Math.floor(secondsRemaining / 60)).padStart(2, '0');
      const seconds = String(secondsRemaining % 60).padStart(2, '0');
      timeRemainingElement.textContent = `${minutes}:${seconds}`;
    }

    function autoSubmitAssessment() {
      // Show alert that time has expired
      alert("Time's up! Your assessment will be automatically submitted.");
      
      // Get all questions
      const questions = assessmentData;
      
      // Disable submit button if it exists
      const submitBtn = document.querySelector('#questions button');
      if (submitBtn) {
        submitBtn.disabled = true;
      }
      
      // Submit answers
      submitAnswers(questions, submitBtn, true);
    }

    function createConfetti() {
      const confettiContainer = document.createElement('div');
      confettiContainer.className = 'confetti';
      document.body.appendChild(confettiContainer);
      const colors = ['#667eea', '#764ba2', '#10b981', '#f59e0b', '#ef4444'];
      for (let i = 0; i < 50; i++) {
        const confettiPiece = document.createElement('div');
        confettiPiece.className = 'confetti-piece';
        confettiPiece.style.left = Math.random() * 100 + 'vw';
        confettiPiece.style.background = colors[Math.floor(Math.random() * colors.length)];
        confettiPiece.style.animationDelay = Math.random() * 3 + 's';
        confettiPiece.style.animationDuration = (Math.random() * 3 + 2) + 's';
        confettiContainer.appendChild(confettiPiece);
      }
      setTimeout(() => {
        document.body.removeChild(confettiContainer);
      }, 5000);
    }

    async function submitAnswers(questions, btn, isAutoSubmit = false) {
      if (btn) {
        btn.disabled = true;
      }
      
      // Stop timer and calculate time taken
      if (!isAutoSubmit) {
        stopTimer();
      }
      
      loadingDiv.innerHTML = `
        <div class="loading-spinner"></div>
        ${isAutoSubmit ? "Time expired! " : ""}Analyzing your responses and generating insights...
      `;

      const answers = questions.map((q, idx) => {
        const chosen = document.querySelector(`input[name="q${idx}"]:checked`);
        const correctKey = Object.keys(q.scoring).reduce((a,b) => q.scoring[a] > q.scoring[b] ? a : b);
        return {
          question: q.question,
          type: q.type,
          user_answer: chosen ? q.options[chosen.value] : null,
          correct: q.options[correctKey],
          user_answer_index: chosen ? chosen.value : null
        };
      });

      setTimeout(() => {
        // Save results before generating feedback
        const correctCount = answers.filter(a => a.user_answer === a.correct).length;
        const percentage = Math.round((correctCount / answers.length) * 100);
        
        // Save assessment with time taken
        saveAssessmentResults(answers, timeTaken);
        
        // Generate feedback
        generateEnhancedFeedback(answers, timeTaken);
        
        loadingDiv.innerHTML = "";
        if (btn) {
          btn.disabled = false;
        }
      }, 3000);
    }

    function generateEnhancedFeedback(answers, assessmentTimeTaken = "00:00") {
      const correctCount = answers.filter(a => a.user_answer === a.correct).length;
      const percentage = Math.round((correctCount / answers.length) * 100);
      
      // Trigger confetti for good scores
      if (percentage >= 70) {
        createConfetti();
      }

      const feedbackDiv = document.getElementById("feedback");
      feedbackDiv.innerHTML = `
        <h3>
          <i class='bx bx-trophy'></i>
          Your Assessment Results
        </h3>
        <div class="score-summary">
          <p><strong>Overall Score:</strong> ${correctCount} / ${answers.length} (${percentage}%)</p>
          ${getPerformanceBadge(percentage)}
          <div class="progress-bar-container">
            <div class="progress-bar" id="progressBar"></div>
          </div>
          <p><strong>Time Taken:</strong> ${assessmentTimeTaken}</p>
          <p style="font-size: 16px; margin-top: 15px; opacity: 0.8;">${getMotivationalMessage(percentage)}</p>
        </div>
      `;

      // Category breakdown
      const categoryBreakdown = getCategoryBreakdown(answers);
      feedbackDiv.innerHTML += `
        <div class="category-breakdown">
          ${Object.entries(categoryBreakdown).map(([category, data]) => `
            <div class="category-card">
              <h4>
                <i class='bx ${getTypeIcon(category)}'></i>
                ${category}
              </h4>
              <div class="category-score" style="color: ${getScoreColor(data.percentage)}">
                ${data.correct}/${data.total} (${data.percentage}%)
              </div>
              <div class="category-progress">
                <div class="category-progress-bar" style="background: ${getScoreColor(data.percentage)}; width: 0%;" data-width="${data.percentage}%"></div>
              </div>
            </div>
          `).join('')}
        </div>
      `;

      // AI Insights
      feedbackDiv.innerHTML += `
        <div class="insights-section">
          <h3><i class='bx bx-brain'></i> AI-Powered Insights</h3>
          ${generatePersonalizedInsights(answers, percentage)}
        </div>
      `;

      // Recommendations
      feedbackDiv.innerHTML += `
        <div class="recommendations-grid">
          ${generateRecommendations(categoryBreakdown, percentage)}
        </div>
      `;

      // Detailed question feedback
      feedbackDiv.innerHTML += `<h3 style="margin-top: 40px; margin-bottom: 20px;"><i class='bx bx-list-check'></i> Detailed Question Analysis</h3>`;

      // Add retake button
      const retakeBtn = document.createElement("button");
      retakeBtn.id = "retakeBtn";
      retakeBtn.innerHTML = `<i class='bx bx-refresh'></i> Retake Assessment`;
      retakeBtn.onclick = () => {
        document.getElementById("questions").innerHTML = "";
        feedbackDiv.innerHTML = "";
        feedbackDiv.style.display = 'none';
        showCategorySelection(); // Show category selection when retaking
      };
      feedbackDiv.appendChild(retakeBtn);

      // Animate progress bars
      setTimeout(() => {
        document.getElementById("progressBar").style.width = percentage + "%";
        
        // Animate category progress bars
        document.querySelectorAll('.category-progress-bar').forEach(bar => {
          const width = bar.getAttribute('data-width');
          bar.style.width = width;
        });
      }, 500);

      // Build detailed feedback for each question
      answers.forEach((a, idx) => {
        setTimeout(() => {
          const div = document.createElement("div");
          const isCorrect = a.user_answer === a.correct;

          div.className = "feedback-item " + (isCorrect ? "correct" : "incorrect");
          div.innerHTML = `
            <p class="feedback-question">
              <i class='bx ${getTypeIcon(a.type)}'></i>
              <b>Q${idx+1} (${a.type}):</b> ${a.question}
            </p>
            <p>
              <span class="answer ${isCorrect ? "correct" : "incorrect"}">
                <i class='bx ${isCorrect ? "bx-check" : "bx-x"}</i>
                Your Answer: ${a.user_answer ? a.user_answer : "Not answered"}
              </span>
            </p>
            <p>
              <span class="answer correct">
                <i class='bx bx-check'></i>
                Correct Answer: ${a.correct}
              </span>
            </p>
            ${getQuestionInsight(a, isCorrect)}
          `;
          feedbackDiv.appendChild(div);
        }, idx * 150);
      });

      // Make sure feedback is visible
      feedbackDiv.style.display = 'block';

      // Scroll to feedback
      setTimeout(() => {
        feedbackDiv.scrollIntoView({ behavior: 'smooth' });
      }, 1000);
    }
    
    function generateDashboard() {
      // Calculate overall statistics
      const totalAssessments = assessmentHistory.length;
      const averageScore = assessmentHistory.reduce((sum, assessment) => sum + assessment.percentage, 0) / totalAssessments;
      const highestScore = Math.max(...assessmentHistory.map(a => a.percentage));
      const mostRecentScore = assessmentHistory[0].percentage;
      
      // Get best and worst categories across all assessments
      const allCategoryData = {};
      
      assessmentHistory.forEach(assessment => {
        Object.entries(assessment.categoryBreakdown).forEach(([category, data]) => {
          if (!allCategoryData[category]) {
            allCategoryData[category] = { total: 0, count: 0, scores: [] };
          }
          allCategoryData[category].total += data.percentage;
          allCategoryData[category].count += 1;
          allCategoryData[category].scores.push(data.percentage);
        });
      });
      
      // Calculate average scores for each category
      Object.keys(allCategoryData).forEach(category => {
        allCategoryData[category].average = Math.round(allCategoryData[category].total / allCategoryData[category].count);
      });
      
      // Sort categories by average score
      const sortedCategories = Object.entries(allCategoryData).sort((a, b) => b[1].average - a[1].average);
      const bestCategories = sortedCategories.slice(0, 3);
      const worstCategories = sortedCategories.slice(-3).reverse();
      
      // Generate progress data for chart (last 5 assessments)
      const recentAssessments = assessmentHistory.slice(0, 5).reverse();
      
      dashboardDiv.innerHTML = `
        <h3>
          <i class='bx bx-dashboard'></i>
          Your Assessment Dashboard
        </h3>
        
        <div class="dashboard-section">
          <h4><i class='bx bx-stats'></i> Overall Statistics</h4>
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-label">Total Assessments</div>
              <div class="stat-value">${totalAssessments}</div>
              <div>Completed assessments</div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Average Score</div>
              <div class="stat-value">${Math.round(averageScore)}%</div>
              <div>Across all assessments</div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Highest Score</div>
              <div class="stat-value">${highestScore}%</div>
              <div>Your best performance</div>
            </div>
            <div class="stat-card">
              <div class="stat-label">Latest Score</div>
              <div class="stat-value">${mostRecentScore}%</div>
              <div>Most recent assessment</div>
            </div>
          </div>
        </div>
        
        <div class="dashboard-section">
          <h4><i class='bx bx-line-chart'></i> Progress Chart</h4>
          <div class="chart-container">
            <div class="progress-chart" id="progressChart">
              ${recentAssessments.map((assessment, index) => `
                <div class="chart-bar" style="height: ${(assessment.percentage / 100) * 250}px;" title="${assessment.percentage}% on ${assessment.date}">
                  <div class="chart-bar-value">${assessment.percentage}%</div>
                  <div class="chart-bar-label">Test ${recentAssessments.length - index}</div>
                </div>
              `).join('')}
            </div>
          </div>
          <p style="text-align: center; color: #64748b; font-style: italic;">
            Your score trend over the last ${recentAssessments.length} assessments
          </p>
        </div>
        
        <div class="dashboard-section">
          <h4><i class='bx bx-medal'></i> Strengths & Weaknesses</h4>
          <div class="strengths-weaknesses">
            <div class="strength-card">
              <h5><i class='bx bx-trophy'></i> Top Strengths</h5>
              <ul class="skill-list">
                ${bestCategories.map(([category, data]) => `
                  <li class="skill-item">
                    <span class="skill-name">${category}</span>
                    <span class="skill-percentage">${data.average}%</span>
                  </li>
                `).join('')}
              </ul>
            </div>
            <div class="weakness-card">
              <h5><i class='bx bx-target-lock'></i> Areas to Improve</h5>
              <ul class="skill-list">
                ${worstCategories.map(([category, data]) => `
                  <li class="skill-item">
                    <span class="skill-name">${category}</span>
                    <span class="skill-percentage">${data.average}%</span>
                  </li>
                `).join('')}
              </ul>
            </div>
          </div>
        </div>
        
        <div class="dashboard-section">
          <h4><i class='bx bx-history'></i> Assessment History</h4>
          <div class="assessment-history">
            ${assessmentHistory.slice(0, 5).map(assessment => `
              <div class="history-item">
                <div class="history-date">${assessment.date}</div>
                <div class="history-score">${assessment.score}/${assessment.total}</div>
                <div>(${assessment.percentage}%)</div>
                <div class="history-time">Time: ${assessment.timeTaken}</div>
                <div class="history-badge ${getBadgeClass(assessment.percentage)}">
                  ${getPerformanceLabel(assessment.percentage)}
                </div>
                <button class="view-details-btn" onclick="viewAssessmentDetails(${assessment.id})" style="margin-left: 20px; padding: 8px 15px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                  View Details
                </button>
              </div>
            `).join('')}
          </div>
          
          ${assessmentHistory.length > 5 ? `
            <div style="text-align: center; margin-top: 20px;">
              <button onclick="showAllHistory()" style="padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                Show All History (${assessmentHistory.length} assessments)
              </button>
            </div>
          ` : ''}
        </div>
        
        <div class="dashboard-section">
          <h4><i class='bx bx-rocket'></i> Recommendations & Next Steps</h4>
          <div class="recommendations-grid">
            ${generateDashboardRecommendations(averageScore, bestCategories, worstCategories)}
          </div>
        </div>
      `;
      
      // Add styles for time in history items
      const style = document.createElement('style');
      style.textContent = `
        .history-time {
          font-size: 14px;
          color: #64748b;
          margin: 0 20px;
          font-weight: 500;
        }
      `;
      document.head.appendChild(style);
      
      // Add event listener for View Details buttons
      window.viewAssessmentDetails = function(assessmentId) {
        const assessment = assessmentHistory.find(a => a.id === assessmentId);
        if (assessment) {
          // Load this assessment's data
          userAnswers = assessment.answers;
          feedbackDiv.style.display = 'block';
          dashboardDiv.style.display = 'none';
          generateEnhancedFeedback(assessment.answers, assessment.timeTaken);
          feedbackDiv.scrollIntoView({ behavior: 'smooth' });
        }
      };
      
      window.showAllHistory = function() {
        // This would show all history items, for now we'll just alert
        alert(`You have ${assessmentHistory.length} total assessments. The dashboard shows the 5 most recent.`);
      };
    }
    
    function getBadgeClass(percentage) {
      if (percentage >= 90) return 'badge-excellent';
      if (percentage >= 75) return 'badge-good';
      if (percentage >= 60) return 'badge-average';
      return 'badge-needs-improvement';
    }
    
    function getPerformanceLabel(percentage) {
      if (percentage >= 90) return 'Excellent';
      if (percentage >= 75) return 'Good';
      if (percentage >= 60) return 'Average';
      return 'Needs Improvement';
    }
    
    function generateDashboardRecommendations(averageScore, bestCategories, worstCategories) {
      const recommendations = [];
      
      // Overall progress recommendation
      if (averageScore >= 80) {
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-trophy'></i> Excellent Progress!</h4>
            <p>You're performing exceptionally well. Consider tackling more advanced assessments or focusing on interview simulation.</p>
          </div>
        `);
      } else if (averageScore >= 70) {
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-chart'></i> Steady Improvement</h4>
            <p>Focus on your weaker areas to boost your overall score. Consistent practice will yield great results.</p>
          </div>
        `);
      } else {
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-target'></i> Focused Practice Needed</h4>
            <p>Don't get discouraged! Focus on fundamentals and practice regularly. Improvement takes time and effort.</p>
          </div>
        `);
      }
      
      // Best category recommendation
      if (bestCategories.length > 0) {
        const bestCategory = bestCategories[0][0];
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-star'></i> Leverage Your Strength</h4>
            <p>Your strongest area is ${bestCategory}. Consider how to highlight this strength in interviews and job applications.</p>
          </div>
        `);
      }
      
      // Worst category recommendation
      if (worstCategories.length > 0) {
        const worstCategory = worstCategories[0][0];
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-book-bookmark'></i> Focus Area</h4>
            <p>Prioritize improving in ${worstCategory}. Dedicate extra study time to this area for maximum score improvement.</p>
          </div>
        `);
      }
      
      // General recommendation
      recommendations.push(`
        <div class="recommendation-card">
          <h4><i class='bx bx-calendar'></i> Practice Schedule</h4>
          <p>Set a goal to complete 1-2 assessments per week. Consistency is key to tracking and improving your progress.</p>
        </div>
      `);
      
      return recommendations.join('');
    }

    function getPerformanceBadge(percentage) {
      if (percentage >= 90) return '<div class="performance-badge badge-excellent"><i class="bx bx-trophy"></i> Excellent Performance!</div>';
      if (percentage >= 75) return '<div class="performance-badge badge-good"><i class="bx bx-medal"></i> Good Performance</div>';
      if (percentage >= 60) return '<div class="performance-badge badge-average"><i class="bx bx-target-lock"></i> Average Performance</div>';
      return '<div class="performance-badge badge-needs-improvement"><i class="bx bx-trending-up"></i> Room for Improvement</div>';
    }

    function getMotivationalMessage(percentage) {
      const messages = {
        90: "Outstanding! You demonstrate exceptional problem-solving skills and readiness for challenging roles.",
        75: "Great job! Your performance shows strong analytical thinking and good preparation.",
        60: "Good effort! With some focused practice, you can significantly improve your performance.",
        0: "Don't worry! Every expert was once a beginner. Use this as a learning opportunity to identify areas for growth."
      };
      
      for (const threshold of [90, 75, 60, 0]) {
        if (percentage >= threshold) return messages[threshold];
      }
    }

    function getCategoryBreakdown(answers) {
      const breakdown = {};
      
      answers.forEach(answer => {
        if (!breakdown[answer.type]) {
          breakdown[answer.type] = { correct: 0, total: 0 };
        }
        breakdown[answer.type].total++;
        if (answer.user_answer === answer.correct) {
          breakdown[answer.type].correct++;
        }
      });

      // Calculate percentages
      Object.keys(breakdown).forEach(category => {
        const data = breakdown[category];
        data.percentage = Math.round((data.correct / data.total) * 100);
      });

      return breakdown;
    }

    function getScoreColor(percentage) {
      if (percentage >= 80) return '#10b981';
      if (percentage >= 60) return '#3b82f6';
      if (percentage >= 40) return '#f59e0b';
      return '#ef4444';
    }

    function generatePersonalizedInsights(answers, overallPercentage) {
      const insights = [];
      const categoryBreakdown = getCategoryBreakdown(answers);
      
      // Find strongest and weakest categories
      const categories = Object.entries(categoryBreakdown);
      const strongest = categories.reduce((a, b) => a[1].percentage > b[1].percentage ? a : b);
      const weakest = categories.reduce((a, b) => a[1].percentage < b[1].percentage ? a : b);

      insights.push(`
        <div class="insight-item">
          <strong><i class='bx bx-trending-up'></i> Your Strongest Area:</strong> 
          ${strongest[0]} (${strongest[1].percentage}%) - This suggests excellent skills in this domain.
        </div>
      `);

      if (weakest[1].percentage < 50) {
        insights.push(`
          <div class="insight-item">
            <strong><i class='bx bx-target-lock'></i> Growth Opportunity:</strong> 
            ${weakest[0]} (${weakest[1].percentage}%) - Consider focused practice in this area.
          </div>
        `);
      }

      if (overallPercentage >= 75) {
        insights.push(`
          <div class="insight-item">
            <strong><i class='bx bx-star'></i> Interview Readiness:</strong> 
            Your strong performance indicates you're well-prepared for technical interviews.
          </div>
        `);
      }

      // Pattern analysis
      const logicalAnswers = answers.filter(a => a.type.includes('Reasoning'));
      const logicalCorrect = logicalAnswers.filter(a => a.user_answer === a.correct).length;
      const logicalPercentage = logicalAnswers.length > 0 ? (logicalCorrect / logicalAnswers.length) * 100 : 0;

      if (logicalPercentage >= 80) {
        insights.push(`
          <div class="insight-item">
            <strong><i class='bx bx-brain'></i> Analytical Strength:</strong> 
            Your excellent reasoning performance suggests strong analytical and problem-solving abilities.
          </div>
        `);
      }

      return insights.join('');
    }

    function generateRecommendations(categoryBreakdown, overallPercentage) {
      const recommendations = [];

      // Study recommendations based on performance
      Object.entries(categoryBreakdown).forEach(([category, data]) => {
        if (data.percentage < 60) {
          recommendations.push(`
            <div class="recommendation-card">
              <h4><i class='bx bx-book-bookmark'></i> ${category} Improvement</h4>
              <p>${getStudyRecommendation(category)}</p>
            </div>
          `);
        }
      });

      // General recommendations
      if (overallPercentage >= 80) {
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-rocket'></i> Next Steps</h4>
            <p>Consider applying for senior-level positions and prepare for advanced technical interviews with system design questions.</p>
          </div>
        `);
      } else if (overallPercentage >= 60) {
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-time'></i> Practice Routine</h4>
            <p>Dedicate 30-45 minutes daily to practice problems in your weaker areas. Focus on understanding concepts rather than memorizing.</p>
          </div>
        `);
      } else {
        recommendations.push(`
          <div class="recommendation-card">
            <h4><i class='bx bx-book-content'></i> Foundation Building</h4>
            <p>Start with fundamental concepts and gradually build complexity. Consider taking structured courses or working with a mentor.</p>
          </div>
        `);
      }

      // Always include a motivation card
      recommendations.push(`
        <div class="recommendation-card">
          <h4><i class='bx bx-heart'></i> Remember</h4>
          <p>Every expert was once a beginner. Consistent practice and a growth mindset are your keys to success!</p>
        </div>
      `);

      return recommendations.slice(0, 4).join(''); // Limit to 4 recommendations
    }

    function getStudyRecommendation(category) {
      const recommendations = {
        'Numerical Reasoning': 'Practice with Khan Academy math sections, focus on percentages, ratios, and data interpretation.',
        'Verbal Reasoning': 'Read diverse materials daily, practice vocabulary building, and work on reading comprehension exercises.',
        'Logical Reasoning': 'Study formal logic, practice pattern recognition, and work through logic puzzle books.',
        'Diagrammatic Reasoning': 'Practice with flowchart interpretation and process mapping exercises.',
        'MBTI': 'Take practice personality assessments and reflect on your work preferences and communication style.',
        'DISC Profiling': 'Study behavioral styles and practice identifying them in workplace scenarios.',
        'OCEAN/Big Five': 'Learn about personality psychology and practice self-reflection exercises.',
        'Situational Judgement Tests': 'Practice with real workplace scenarios and study best practices for professional situations.',
        'Cultural Fit Tests': 'Research company values and practice aligning your responses with organizational culture.',
        'Time Management & Priority Ranking Scenarios': 'Practice with project management tools and learn prioritization frameworks like Eisenhower Matrix.',
        'Work Ethics Self-Assessment': 'Reflect on your values and practice articulating them in professional contexts.'
      };
      return recommendations[category] || 'Focus on regular practice and seek additional resources for this topic.';
    }

    function getQuestionInsight(answer, isCorrect) {
      if (isCorrect) {
        return `<p style="color: #059669; font-style: italic; margin-top: 10px;"><i class='bx bx-lightbulb'></i> Great job! This shows strong understanding of ${answer.type.toLowerCase()}.</p>`;
      } else {
        const insights = {
          'Numerical Reasoning': 'Review fundamental math concepts and practice step-by-step problem solving.',
          'Verbal Reasoning': 'Focus on context clues and precise word meanings.',
          'Logical Reasoning': 'Practice identifying logical relationships and patterns.',
          'MBTI': 'Consider your natural tendencies and authentic preferences.',
          'Situational Judgement Tests': 'Think about best practices and professional standards.'
        };
        const insight = insights[answer.type] || 'Review the underlying concepts for this question type.';
        return `<p style="color: #dc2626; font-style: italic; margin-top: 10px;"><i class='bx bx-lightbulb'></i> Tip: ${insight}</p>`;
      }
    }
    
    // --- DARK MODE LOGIC (SYNCED) ---
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('dark-mode-toggle');
        const body = document.body;

        function applyTheme(isEnabled) {
            if (isEnabled) {
                body.classList.add('dark-mode');
                toggle.checked = true;
            } else {
                body.classList.remove('dark-mode');
                toggle.checked = false;
            }
        }

        // 1. Check LocalStorage on Load
        const savedSetting = localStorage.getItem('darkMode');
        if (savedSetting === 'enabled') {
            applyTheme(true);
        } else {
            applyTheme(false);
        }

        // 2. Listen for Toggle Changes
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