<?php
// Optional: Add session/authentication checks here
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Client Feedback</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

  <style>
    /* ===========================
       GLOBAL RESET & VARIABLES (from consultant_dashboard.php)
    ============================ */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    :root {
      --primary: #667eea;
      --primary-dark: #5a67d8;
      --secondary: #c9a9eaff;
      --dark: #18191a;
      --darker: #121314;
      --light: #f8f9fa;
      --gray: #6c757d;
      --light-gray: #e9ecef;
      --success: #28a745;
      --danger: #dc3545;
      --warning: #ffc107;
      --info: #17a2b8;
      --white: #ffffff;
      --black: #000000;
      --border-radius: 12px;
      --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
    }
    body {
      background-color: #f5f7fb;
      color: #333;
      display: flex;
      min-height: 100vh;
      overflow-x: hidden;
    }
    body.dark-mode {
      background-color: var(--dark);
      color: #e4e6eb;
    }

    /* ===========================
       SIDEBAR (admin version, same style as consultant)
    ============================ */
    .sidebar {
      width: 280px;
      background: linear-gradient(180deg, var(--primary), var(--secondary));
      color: var(--white);
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 100;
      transition: var(--transition);
      display: flex;
      flex-direction: column;
    }
    .sidebar.collapsed {
      width: 80px;
    }
    .logo {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 24px 20px;
      text-decoration: none;
      color: var(--white);
      font-size: 22px;
      font-weight: 700;
    }
    .logo i {
      font-size: 32px;
    }
    .logo-name span {
      white-space: nowrap;
      transition: var(--transition);
    }
    .sidebar.collapsed .logo-name span {
      display: none;
    }
    .side-menu {
      list-style: none;
      padding: 0 15px;
      flex: 1;
      overflow-y: auto;
    }
    .side-menu li {
      margin: 8px 0;
    }
    .side-menu li a {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 16px;
      color: var(--white);
      text-decoration: none;
      border-radius: 8px;
      transition: var(--transition);
      font-size: 16px;
    }
    .side-menu li.active a {
      background: rgba(255, 255, 255, 0.15);
    }
    .side-menu li a:hover {
      background: rgba(255, 255, 255, 0.1);
    }
    .side-menu li a i {
      font-size: 22px;
      min-width: 24px;
      text-align: center;
    }
    .logout {
      margin-top: auto;
      padding: 16px !important;
      background: rgba(0, 0, 0, 0.2);
    }

    /* ===========================
       MAIN CONTENT & NAVBAR
    ============================ */
    .content {
      flex: 1;
      margin-left: 280px;
      transition: var(--transition);
    }
    .sidebar.collapsed ~ .content {
      margin-left: 80px;
    }

    nav {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 30px;
      background: var(--white);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 99;
    }
    body.dark-mode nav {
      background: #242526;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }
    nav .bx-menu {
      font-size: 28px;
      cursor: pointer;
      color: var(--gray);
    }

    .theme-toggle {
      width: 50px;
      height: 24px;
      background: var(--light-gray);
      border-radius: 50px;
      position: relative;
      cursor: pointer;
      display: flex;
      align-items: center;
      padding: 2px;
    }
    body.dark-mode .theme-toggle {
      background: #3a3b3c;
    }
    .theme-toggle::before {
      content: '';
      width: 20px;
      height: 20px;
      background: var(--white);
      border-radius: 50%;
      transition: var(--transition);
    }
    #theme-toggle:checked + .theme-toggle::before {
      transform: translateX(26px);
      background: var(--primary);
    }

    /* ===========================
       MOBILE NAV LINKS BAR
    ============================ */
    .mobile-nav-links {
      display: none;
      background: var(--white);
      padding: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      overflow-x: auto;
      white-space: nowrap;
    }
    body.dark-mode .mobile-nav-links {
      background: #242526;
    }
    .mobile-nav-links a {
      display: inline-block;
      padding: 8px 16px;
      margin: 0 4px;
      background: var(--light-gray);
      border-radius: 8px;
      text-decoration: none;
      color: var(--gray);
      font-size: 14px;
      transition: var(--transition);
    }
    body.dark-mode .mobile-nav-links a {
      background: #3a3b3c;
      color: #adb5bd;
    }
    .mobile-nav-links a:hover,
    .mobile-nav-links a.active {
      background: var(--primary);
      color: white;
    }

    /* ===========================
       MAIN
    ============================ */
    main {
      padding: 24px;
    }

    .header {
      margin-bottom: 24px;
    }
    .header h1 {
      font-size: 28px;
      color: var(--primary);
      margin-bottom: 8px;
    }
    .breadcrumb {
      list-style: none;
      display: flex;
      gap: 8px;
      font-size: 14px;
      color: var(--gray);
    }
    .breadcrumb a {
      color: var(--primary);
      text-decoration: none;
    }
    .breadcrumb a.active {
      color: var(--gray);
      font-weight: 500;
    }

    /* ===========================
       CARD & TABLE
    ============================ */
    .card {
      background: var(--white);
      padding: 30px;
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
    }
    body.dark-mode .card {
      background: #242526;
    }

    .card h2 {
      font-size: 20px;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    body.dark-mode .card h2 {
      color: #e4e6eb;
    }

    .filter-section {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 24px;
    }
    .filter-section input,
    .filter-section select {
      flex: 1;
      min-width: 250px;
      padding: 14px;
      border: 1px solid var(--light-gray);
      border-radius: 8px;
      font-size: 15px;
      background: white;
      transition: var(--transition);
    }
    body.dark-mode .filter-section input,
    body.dark-mode .filter-section select {
      background: #3a3b3c;
      color: #e4e6eb;
      border-color: #4a4b4d;
    }

    .filter-section input:focus,
    .filter-section select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 16px;
    }
    th, td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid var(--light-gray);
    }
    body.dark-mode th,
    body.dark-mode td {
      border-color: #3a3b3c;
    }
    th {
      background: #f8f9fa;
      font-weight: 600;
    }
    body.dark-mode th {
      background: #2d2e2f;
    }
    tr:last-child td {
      border-bottom: none;
    }
    tr:hover {
      background: #f8f9fa;
    }
    body.dark-mode tr:hover {
      background: #2d2e2f;
    }

    .star {
      color: #f59e0b;
      font-size: 1.2rem;
    }

    /* ===========================
       BUTTON
    ============================ */
    .btn {
      padding: 12px 24px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: var(--border-radius);
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 15px;
      text-decoration: none;
    }
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    /* ===========================
       RESPONSIVE
    ============================ */
    @media (max-width: 992px) {
      .sidebar {
        width: 80px;
      }
      .logo-name span,
      .side-menu li a span {
        display: none;
      }
      .side-menu li a {
        justify-content: center;
        padding: 16px;
      }
      .content {
        margin-left: 80px;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }
      .sidebar.active {
        transform: translateX(0);
      }
      .content {
        margin-left: 0;
      }
      nav {
        padding: 12px 16px;
      }
      .mobile-nav-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 4px;
        padding: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      .mobile-nav-links a {
        padding: 6px 8px;
        font-size: 12px;
        white-space: nowrap;
        flex-shrink: 0;
      }
      main {
        padding: 12px;
      }
      .header h1 {
        font-size: 24px;
      }
      .breadcrumb {
        font-size: 12px;
      }
      .card {
        padding: 16px;
      }
      .card h2 {
        font-size: 18px;
      }
      .filter-section {
        flex-direction: column;
        gap: 10px;
      }
      .filter-section input,
      .filter-section select {
        font-size: 14px;
        padding: 10px;
        min-width: 200px;
      }
      table {
        font-size: 14px;
      }
      th, td {
        padding: 10px 8px;
      }
      .btn {
        font-size: 14px;
        padding: 10px 16px;
      }
    }

    @media (max-width: 480px) {
      nav {
        padding: 8px 12px;
      }
      .mobile-nav-links {
        gap: 2px;
        padding: 6px;
      }
      .mobile-nav-links a {
        font-size: 11px;
        padding: 4px 6px;
      }
      main {
        padding: 8px;
      }
      .header h1 {
        font-size: 20px;
      }
      .card {
        padding: 12px;
      }
      .card h2 {
        font-size: 16px;
      }
      .filter-section input,
      .filter-section select {
        font-size: 13px;
        padding: 8px;
        min-width: 150px;
      }
      table {
        font-size: 12px;
        overflow-x: auto;
        display: block;
        white-space: nowrap;
      }
      th, td {
        padding: 8px 4px;
      }
      .btn {
        font-size: 13px;
        padding: 8px 12px;
      }
    }

    /* Mobile Menu Overlay */
    .mobile-menu-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 999;
    }
    .sidebar.active ~ .mobile-menu-overlay {
      display: block;
    }
  </style>
</head>
<body>
  <script>
    (function() {
      const currentTheme = localStorage.getItem('theme');
      if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode'); 
      }
    })();
  </script>

  <!-- Mobile Menu Overlay -->
  <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Admin</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Manage Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Manage Applications</span></a></li>
            <li><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Manage Candidates</span></a></li>
            <li><a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interview Schedule</span></a></li>
            <li><a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a></li>
            <li><a href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout.php" class="logout" onclick="return confirmLogout();">
                    <i class='bx bx-log-out-circle'></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

  <!-- Main Content -->
  <div class="content">
    <!-- Navbar -->
    <nav>
      <div></div>
      <input type="checkbox" id="theme-toggle" hidden>
      <label for="theme-toggle" class="theme-toggle"></label>
    </nav>

    <!-- Mobile Nav Links -->
    <div class="mobile-nav-links">
      <a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
      <a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i> Manage Jobs</a>
      <a href="manage_applications.php"><i class='bx bx-file'></i> Applications</a>
      <a href="manage_candidates.php"><i class='bx bx-user'></i> Candidates</a>
      <a href="schedule_interview.php"><i class='bx bx-group'></i><span>Interviews</span></a>
      <a href="admin_invoices.php"><i class='bx bx-receipt'></i> Invoices</a>
      <a class="active" href="admin_client_feedback.php"><i class='bx bx-message-dots'></i> Feedback</a>
      <a href="calendar.php"><i class='bx bx-calendar'></i> Calendar</a>
      <a href="admin_chat.php"><i class='bx bx-chat'></i> Chats</a>
    </div>

    <main>
      <div class="header">
        <h1>Client Feedback Records</h1>
        <ul class="breadcrumb">
          <li><a href="admin_dashboard.php">Dashboard</a></li>
          <li><a href="#" class="active">Client Feedback</a></li>
        </ul>
      </div>

      <div class="card">
        <h2><i class='bx bx-table'></i> All Client Feedback</h2>

        <div class="filter-section">
          <input type="text" id="searchInput" placeholder="Search by client name...">
          <select id="ratingFilter">
            <option value="">Filter by Communication Rating</option>
            <option value="5">Excellent</option>
            <option value="4">Very Good</option>
            <option value="3">Good</option>
            <option value="2">Fair</option>
            <option value="1">Poor</option>
          </select>
        </div>

        <table id="feedbackTable">
          <thead>
            <tr>
              <th>Client Name</th>
              <th>Communication</th>
              <th>Professionalism</th>
              <th>Collaboration</th>
              <th>Comments</th>
            </tr>
          </thead>
          <tbody id="feedbackBody">
            <!-- Rows injected by JS -->
          </tbody>
        </table>

        <button onclick="exportTableToPDF()" class="btn" style="margin-top:24px;">
          <i class='bx bx-file-pdf'></i> Export to PDF
        </button>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      fetch('admin_view_feedback.php')
        .then(response => response.json())
        .then(data => populateTable(data));

      document.getElementById("searchInput").addEventListener("input", filterTable);
      document.getElementById("ratingFilter").addEventListener("change", filterTable);
    });

    let feedbackData = [];

    function populateTable(data) {
      feedbackData = data;
      const tbody = document.getElementById("feedbackBody");
      tbody.innerHTML = "";
      if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:20px;">No feedback records found.</td></tr>`;
        return;
      }
      data.forEach(fb => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td>${fb.client_name}</td>
          <td>${renderStars(fb.communication)}</td>
          <td>${renderStars(fb.professionalism)}</td>
          <td>${renderStars(fb.collaboration)}</td>
          <td style="max-width:300px; word-wrap:break-word;">${fb.comments || '-'}</td>
        `;
        tbody.appendChild(row);
      });
    }

    function renderStars(rating) {
      rating = parseInt(rating) || 0;
      let stars = "";
      for (let i = 1; i <= 5; i++) {
        stars += `<span class="star">${i <= rating ? '★' : '☆'}</span>`;
      }
      return stars;
    }

    function filterTable() {
      const search = document.getElementById("searchInput").value.toLowerCase();
      const rating = document.getElementById("ratingFilter").value;
      const filtered = feedbackData.filter(fb => {
        const matchName = fb.client_name.toLowerCase().includes(search);
        const matchRating = rating ? parseInt(fb.communication) === parseInt(rating) : true;
        return matchName && matchRating;
      });
      populateTable(filtered);
    }

    function exportTableToPDF() {
      if (!feedbackData || feedbackData.length === 0) {
        alert("No data to export.");
        return;
      }
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      const tableColumn = ["Client Name", "Comm.", "Prof.", "Collab.", "Comments"];
      const tableRows = feedbackData.map(fb => [
        fb.client_name,
        fb.communication,
        fb.professionalism,
        fb.collaboration,
        (fb.comments || '').substring(0, 50) + (fb.comments && fb.comments.length > 50 ? '...' : '')
      ]);

      doc.setFontSize(18);
      doc.text("Client Feedback Report", 14, 20);
      doc.setFontSize(12);
      doc.text(`Exported: ${new Date().toLocaleString()}`, 14, 28);

      doc.autoTable({
        startY: 36,
        head: [tableColumn],
        body: tableRows,
        theme: 'striped',
        styles: { fontSize: 9, cellPadding: 3 },
        headStyles: { fillColor: [102, 126, 234] }, // --primary
        columnStyles: { 4: { cellWidth: 60 } }
      });

      doc.save("client_feedback_report.pdf");
    }

    // === Theme & Mobile Menu (identical to consultant dashboard) ===
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
      const currentTheme = localStorage.getItem('theme');
      if (currentTheme) {
        themeToggle.checked = (currentTheme === 'dark');
      }
      themeToggle.addEventListener('change', function() {
        if (this.checked) {
          document.body.classList.add('dark-mode');
          localStorage.setItem('theme', 'dark');
        } else {
          document.body.classList.remove('dark-mode');
          localStorage.setItem('theme', 'light');
        }
      });
    }

    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
      document.getElementById('sidebar').classList.toggle('active');
      document.getElementById('mobileMenuOverlay').style.display = 
        document.getElementById('sidebar').classList.contains('active') ? 'block' : 'none';
    });
    document.getElementById('mobileMenuOverlay').addEventListener('click', function() {
      document.getElementById('sidebar').classList.remove('active');
      this.style.display = 'none';
    });

    function handleTabletView() {
      const sidebar = document.getElementById('sidebar');
      if (window.innerWidth <= 992 && window.innerWidth > 768) {
        sidebar.classList.add('collapsed');
      } else {
        sidebar.classList.remove('collapsed');
      }
    }
    window.addEventListener('resize', handleTabletView);
    handleTabletView();
  </script>
</body>
</html>