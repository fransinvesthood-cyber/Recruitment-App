<?php
session_start();
include('config.php');

// NOTE: This page assumes the admin is logged in.
// If your project has admin auth, enforce it here.

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Contact Messages</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #c9a9eaff;
            --dark: #18191a;
            --light-gray: #e9ecef;
            --gray: #6c757d;
            --white: #ffffff;
            --danger: #dc3545;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);

            /* dark sidebar */
            --sidebar-dark-bg: #242526;
            --sidebar-dark-hover: rgba(255,255,255,0.08);
            --sidebar-dark-active: rgba(102,126,234,0.25);
        }
        body { background-color: #f5f7fb; color: #333; display: flex; min-height: 100vh; overflow-x: hidden; }
        body.dark-mode { background-color: var(--dark); color: #e4e6eb; }

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

        /* Sidebar in dark mode */
        body.dark-mode .sidebar {
            background: var(--sidebar-dark-bg) !important;
            color: var(--white);
        }
        body.dark-mode .logo { color: var(--white); }
        body.dark-mode .side-menu li a { color: var(--white); }
        body.dark-mode .side-menu li.active a { background: var(--sidebar-dark-active); }
        body.dark-mode .side-menu li a:hover { background: var(--sidebar-dark-hover); }

        .sidebar.collapsed { width: 80px; }
        .logo { display: flex; align-items: center; gap: 12px; padding: 24px 20px; text-decoration: none; color: var(--white); font-size: 22px; font-weight: 700; }
        .logo i { font-size: 32px; }
        .logo-name span { white-space: nowrap; transition: var(--transition); }
        .sidebar.collapsed .logo-name span { display: none; }
        .side-menu { list-style: none; padding: 0 15px; flex: 1; overflow-y: auto; }
        .side-menu li { margin: 8px 0; }
        .side-menu li a { display: flex; align-items: center; gap: 14px; padding: 14px 16px; color: var(--white); text-decoration: none; border-radius: 8px; transition: var(--transition); font-size: 16px; }
        .side-menu li.active a { background: rgba(255,255,255,0.15); }
        .side-menu li a:hover { background: rgba(255,255,255,0.1); }

        .content { flex: 1; margin-left: 280px; transition: var(--transition); }
        .sidebar.collapsed ~ .content { margin-left: 80px; }

        nav { display: flex; justify-content: space-between; align-items: center; padding: 16px 30px; background: var(--white); box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 99; }
        body.dark-mode nav { background: #242526; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }

        main { padding: 24px; }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
            box-shadow: var(--box-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        .welcome-content { flex: 1; min-width: 250px; }
        .welcome-content h1 { font-size: 28px; margin-bottom: 8px; }
        .welcome-content p { opacity: 0.9; font-size: 18px; }

        @media (max-width: 768px) {
            .welcome-section { flex-direction: column; align-items: flex-start; }
        }

        @media (max-width: 480px) {
            .welcome-content h1 { font-size: 24px; }
            .welcome-content p { font-size: 16px; }
        }

        .card { background: var(--white); padding: 30px; border-radius: var(--border-radius); box-shadow: var(--box-shadow); }
        body.dark-mode .card { background: #242526; }

        .filter-section { display: flex; gap: 12px; flex-wrap: wrap; margin: 18px 0; }

        /* Modern filter/search UI */
        .filter-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            width: 100%;
        }

        .control-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            flex: 1;
        }

        .control {
            position: relative;
        }

        .control.select-control select {
            min-width: 220px;
            padding: 12px 44px 12px 14px;
            border-radius: 12px;
            border: 1px solid var(--light-gray);
            background: #fff;
            font-weight: 700;
            color: #263238;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.08s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .control.select-control:after {
            content: '';
            position: absolute;
            right: 16px;
            top: 50%;
            width: 10px;
            height: 10px;
            border-right: 2px solid rgba(102,126,234,0.9);
            border-bottom: 2px solid rgba(102,126,234,0.9);
            transform: translateY(-70%) rotate(45deg);
            pointer-events: none;
        }

        .control.input-control input {
            width: 100%;
            min-width: 240px;
            padding: 12px 14px 12px 44px;
            border-radius: 12px;
            border: 1px solid var(--light-gray);
            background: #fff;
            font-weight: 700;
            color: #263238;
            outline: none;
            transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.08s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .control.input-control .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 22px;
            height: 22px;
            display: grid;
            place-items: center;
            color: rgba(102,126,234,0.95);
            pointer-events: none;
            font-size: 18px;
        }

        .control.input-control .clear-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: 1px solid rgba(102,126,234,0.28);
            background: rgba(102,126,234,0.08);
            color: rgba(102,126,234,0.95);
            width: 32px;
            height: 32px;
            border-radius: 10px;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease, transform 0.08s ease;
        }

        .control.input-control .clear-btn:hover { background: rgba(102,126,234,0.16); }
        .control.input-control .clear-btn:active { transform: translateY(-50%) scale(0.98); }

        .control.input-control input:focus,
        .control.select-control select:focus {
            border-color: rgba(102,126,234,0.55);
            box-shadow: 0 0 0 4px rgba(102,126,234,0.18);
        }

        .control.input-control input::placeholder { color: rgba(108,117,125,0.8); font-weight: 600; }

        body.dark-mode .control.select-control select {
            background: #2f3133;
            border-color: #3f4244;
            color: #e4e6eb;
            box-shadow: 0 2px 14px rgba(0,0,0,0.25);
        }

        body.dark-mode .control.select-control:after {
            border-right-color: rgba(167,183,255,0.95);
            border-bottom-color: rgba(167,183,255,0.95);
        }

        body.dark-mode .control.input-control input {
            background: #2f3133;
            border-color: #3f4244;
            color: #e4e6eb;
            box-shadow: 0 2px 14px rgba(0,0,0,0.25);
        }

        body.dark-mode .control.input-control input::placeholder { color: rgba(228,230,235,0.65); }

        body.dark-mode .control.input-control .input-icon { color: rgba(167,183,255,0.95); }

        body.dark-mode .control.input-control .clear-btn {
            border-color: rgba(167,183,255,0.22);
            background: rgba(167,183,255,0.10);
            color: rgba(167,183,255,0.95);
        }
        body.dark-mode .control.input-control .clear-btn:hover { background: rgba(167,183,255,0.16); }

        /* Make icon+buttons feel clickable */
        .control.input-control .clear-btn { user-select: none; }

        table { width: 100%; border-collapse: collapse; margin-top: 14px; }

        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--light-gray); vertical-align: top; }
        body.dark-mode th, body.dark-mode td { border-color: #3a3b3c; }
        th { background: #f8f9fa; font-weight: 700; color: var(--primary); }
        body.dark-mode th { background: #2d2e2f; color: #a7b7ff; }
        tr:hover td { background: rgba(102,126,234,0.05); }

        .msg { max-width: 520px; word-wrap: break-word; }
        .muted { color: var(--gray); font-weight: 600; font-size: 13px; }

        .btn { padding: 10px 16px; background: var(--primary); color: #fff; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; transition: var(--transition); }
        .btn:hover { background: var(--primary-dark); transform: translateY(-1px); }

        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: #c82333; }

        .btn-view { background: rgba(102,126,234,0.15); color: var(--primary); border: 1px solid rgba(102,126,234,0.35); }
        .btn-view:hover { background: rgba(102,126,234,0.25); }

        /* modal */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }
        .modal-backdrop.show { display: flex; }
        .modal {
            width: min(720px, calc(100vw - 24px));
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        body.dark-mode .modal { background: #242526; }
        .modal-header {
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            background: linear-gradient(135deg, rgba(102,126,234,0.15), rgba(201,169,234,0.10));
            border-bottom: 1px solid rgba(102,126,234,0.25);
        }
        body.dark-mode .modal-header { border-bottom: 1px solid rgba(255,255,255,0.08); }
        .modal-title {
            font-size: 18px;
            font-weight: 900;
        }
        .modal-close {
            border: none;
            background: transparent;
            font-size: 22px;
            cursor: pointer;
            color: var(--gray);
        }
        .modal-body { padding: 18px 20px; }
        .kv { display: grid; grid-template-columns: 120px 1fr; gap: 10px 12px; }
        .kv .k { color: var(--gray); font-weight: 700; }
        .kv .v { word-break: break-word; }
        .modal-message {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 10px;
            background: rgba(102,126,234,0.08);
            border: 1px solid rgba(102,126,234,0.22);
            white-space: pre-wrap;
            line-height: 1.5;
        }
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(0,0,0,0.08);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        body.dark-mode .modal-footer { border-top: 1px solid rgba(255,255,255,0.08); }

        .btn-secondary { background: #e9ecef; color: #212529; border: 1px solid #d8dfe6; }
        .btn-secondary:hover { background: #dde2e8; }


        .small-actions { display: flex; gap: 10px; flex-wrap: wrap; }

        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar.collapsed { width: 80px; }
            .logo-name span, .side-menu li a span { display: none; }
            .side-menu li a { justify-content: center; padding: 16px; }
            .content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .content { margin-left: 0; }
            nav { padding: 12px 16px; }
            main { padding: 12px; }
        }
    </style>
</head>
<body>
    <script>
        (function(){
            const currentTheme = localStorage.getItem('theme');
            if (currentTheme === 'dark') document.body.classList.add('dark-mode');
        })();
    </script>

    <div class="sidebar" id="sidebar">
        <a href="#" class="logo">
            <i class='bx bx-user-circle'></i>
            <div class="logo-name"><span>Admin</span></div>
        </a>
        <ul class="side-menu">
            <li><a href="admin_dashboard.php"><i class='bx bxs-dashboard'></i><span>Dashboard</span></a></li>
            <li><a href="admin_contact_messages.php" class="active"><i class='bx bx-message-dots'></i><span>Contact Messages</span></a></li>
            <li><a href="manage_jobs.php"><i class='bx bx-spreadsheet'></i><span>Manage Jobs</span></a></li>
            <li><a href="manage_applications.php"><i class='bx bx-file'></i><span>Applications</span></a></li>
            <li><a href="manage_candidates.php"><i class='bx bx-user'></i><span>Candidates</span></a></li>
            <li><a href="calendar.php"><i class='bx bx-calendar'></i><span>Calendar</span></a></li>
            <li><a href="admin_chat.php"><i class='bx bx-chat'></i><span>Chats</span></a></li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="logout.php" class="logout" onclick="return confirm('Are you sure you want to log out?');">
                    <i class='bx bx-log-out-circle'></i><span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- message preview modal -->
    <div class="modal-backdrop" id="messageModalBackdrop" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="messageModalTitle">
            <div class="modal-header">
                <div>
                    <div class="modal-title" id="messageModalTitle">Message Preview</div>
                    <div class="muted" style="margin-top:6px;" id="messageModalMeta"></div>
                </div>
                <button class="modal-close" type="button" id="messageModalCloseBtn" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="kv" aria-label="Message details">
                    <div class="k">From</div>
                    <div class="v" id="messageModalFrom"></div>

                    <div class="k">Email</div>
                    <div class="v" id="messageModalEmail"></div>

                    <div class="k">Subject</div>
                    <div class="v" id="messageModalSubject"></div>

                    <div class="k">Received</div>
                    <div class="v" id="messageModalReceived"></div>
                </div>

                <div class="modal-message" id="messageModalMessage"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" id="messageModalOkBtn">Close</button>
                    <button class="btn btn-view" type="button" id="messageModalReplyBtn" data-id="" onclick="openReplyModal(this)">Reply</button>


            </div>
        </div>
    </div>

    <!-- reply modal -->
    <div class="modal-backdrop" id="replyModalBackdrop" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="replyModalTitle">
            <div class="modal-header">
                <div>
                    <div class="modal-title" id="replyModalTitle">Reply to Message</div>
                    <div class="muted" style="margin-top:6px;" id="replyMeta"></div>
                </div>
                <button class="modal-close" type="button" id="replyModalCloseBtn" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="kv" aria-label="Reply details" style="margin-bottom: 10px;">
                    <div class="k">To</div>
                    <div class="v">
                        <input id="replyTo" type="email" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--light-gray);" />
                    </div>

                    <div class="k">Subject</div>
                    <div class="v">
                        <input id="replySubject" type="text" style="width:100%; padding:10px 12px; border-radius:10px; border:1px solid var(--light-gray);" />
                    </div>
                </div>

                <label class="muted" style="display:block; font-size:13px; font-weight:800; margin-bottom:8px; text-transform:uppercase;">Message</label>
                <textarea id="replyMessage" style="width:100%; min-height: 160px; padding:12px 14px; border-radius:12px; border:1px solid var(--light-gray); background:#fff; resize: vertical;"></textarea>

                <div class="muted" style="margin-top:14px; font-weight:800; text-transform:uppercase; font-size:13px;">Preview</div>
                <div class="modal-message" id="replyPreview" style="margin-top:10px;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" id="replyModalOkBtn">Cancel</button>
                <button class="btn" type="button" id="replySendBtn" onclick="sendReply()">Send Email</button>
            </div>
        </div>
    </div>




    <div class="content">

        <nav>
            <div></div>
            <div class="small-actions">
                <span class="muted" id="loadingLabel">Loading...</span>
            </div>
        </nav>

        <main>
            <div class="welcome-section">
                <div class="welcome-content">
                    <h1>Contact Us Messages</h1>
                    <p>Review and manage messages submitted from the Contact Us page.</p>
                </div>
                <div class="muted" style="min-width: 220px; font-size:14px;">Tip: Use the filter dropdown to quickly narrow results.</div>
            </div>

            <div class="summary-kpis" style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px;">
                <div class="card" style="padding:18px; margin:0; border:1px solid rgba(102,126,234,0.18);">
                    <div class="muted" style="display:flex; gap:8px; align-items:center; font-weight:900;">Total Messages</div>
                    <div style="font-size:28px; font-weight:1000; color: var(--primary); margin-top:8px;" id="kpiTotal">0</div>
                </div>
                <div class="card" style="padding:18px; margin:0; border:1px solid rgba(220,53,69,0.18);">
                    <div class="muted" style="display:flex; gap:8px; align-items:center; font-weight:900;">Unread</div>
                    <div style="font-size:28px; font-weight:1000; color: var(--danger); margin-top:8px;" id="kpiUnread">0</div>
                </div>
                <div class="card" style="padding:18px; margin:0; border:1px solid rgba(102,126,234,0.18);">
                    <div class="muted" style="display:flex; gap:8px; align-items:center; font-weight:900;">Replied</div>
                    <div style="font-size:28px; font-weight:1000; color: var(--primary); margin-top:8px;" id="kpiReplied">0</div>
                </div>
                <div class="card" style="padding:18px; margin:0; border:1px solid rgba(201,169,234,0.35);">
                    <div class="muted" style="display:flex; gap:8px; align-items:center; font-weight:900;">Today</div>
                    <div style="font-size:28px; font-weight:1000; color: var(--secondary); margin-top:8px;" id="kpiToday">0</div>
                </div>
            </div>

            <div class="card">
                <div class="filter-section" style="align-items:center; justify-content: space-between; gap:16px;">
                    <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; flex:1;">
                        <div class="control select-control">
                            <select id="messageFilter">
                                <option value="all" selected>All messages</option>
                                <option value="today">Today</option>
                                <option value="this_week">This week</option>
                                <option value="unread">Unread</option>
                                <option value="replied">Replied</option>
                            </select>
                        </div>

                        <div class="control input-control" style="flex: 1; min-width: 240px;">
                            <span class="input-icon"><i class='bx bx-search'></i></span>
                            <input id="searchInput" type="text" placeholder="Search by name, email, subject or message..." />
                            <button class="clear-btn" type="button" id="searchClearBtn" aria-label="Clear search">×</button>
                        </div>

                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Sender</th>
                            <th style="width: 20%;">Subject</th>
                            <th style="width: 40%;">Message</th>
                            <th style="width: 20%;">Received</th>
                        </tr>
                    </thead>
                    <tbody id="tbody">
                        <tr><td colspan="4" class="muted">No data yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        let data = [];

        function escapeHtml(str){
            return (str ?? '').toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '<')
                .replace(/>/g, '>')
                .replace(/"/g, '"')
                .replace(/'/g, '&#039;');
        }

        function renderTable(items){
            const tbody = document.getElementById('tbody');
            if (!items || items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" class="muted" style="text-align:center; padding:20px;">No contact messages found.</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map(m => {
                const created = m.created_at ? new Date(m.created_at).toLocaleString() : '';
                return `
                    <tr>
                        <td>
                            <div style="font-weight:800;">${escapeHtml(m.fullname || '')}</div>
                            <div class="muted">${escapeHtml(m.email || '')}</div>
                        </td>
                        <td style="word-wrap:break-word;">
                            ${escapeHtml(m.subject || '')}
                        </td>
                        <td class="msg">
                            <div style="max-height: 48px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                                ${escapeHtml(m.message || '')}
                            </div>
                        </td>
                        <td class="muted">
                            ${created}
                            <div style="margin-top:8px;">
                                <button type="button" class="btn btn-view" data-id="${escapeHtml(String(m.contact_message_id ?? ''))}" onclick="openMessageModal(this)">View</button>
                            </div>
                        </td>

                    </tr>
                `;
            }).join('');
        }

        function openMessageModal(btn){
            const id = btn?.getAttribute('data-id') || '';
            const row = (data || []).find(x => String(x.contact_message_id ?? '') === String(id));

            const backdrop = document.getElementById('messageModalBackdrop');
            const closeBtn = document.getElementById('messageModalCloseBtn');
            const okBtn = document.getElementById('messageModalOkBtn');

            const setText = (elId, value) => {
                const el = document.getElementById(elId);
                if (el) el.textContent = value ?? '';
            };

            if (!row) {
                setText('messageModalFrom', '');
                setText('messageModalEmail', '');
                setText('messageModalSubject', '');
                setText('messageModalReceived', '');
                const msgEl = document.getElementById('messageModalMessage');
                if (msgEl) msgEl.textContent = 'Message not found.';
            } else {
                setText('messageModalFrom', row.fullname || '');
                setText('messageModalEmail', row.email || '');
                setText('messageModalSubject', row.subject || '');

                const created = row.created_at ? new Date(row.created_at).toLocaleString() : '';
                setText('messageModalReceived', created);

                const msgEl = document.getElementById('messageModalMessage');
                if (msgEl) msgEl.textContent = row.message || '';

                const meta = document.getElementById('messageModalMeta');
                if (meta) meta.textContent = id ? `ID: ${id}` : '';
            }

            if (backdrop) backdrop.classList.add('show');

            if (closeBtn) closeBtn.onclick = closeMessageModal;
            if (okBtn) okBtn.onclick = closeMessageModal;

            if (backdrop) {
                backdrop.onclick = function(e){
                    if (e.target === backdrop) closeMessageModal();
                };
            }
        }

        function closeMessageModal(){
            const backdrop = document.getElementById('messageModalBackdrop');
            if (backdrop) backdrop.classList.remove('show');
        }

        // hard close handler for both modals
        function closeModalByBackdrop(backdropId){
            const el = document.getElementById(backdropId);
            if (el) el.classList.remove('show');
        }


        function openReplyModal(btn){
            const replyBtn = btn || document.getElementById('messageModalReplyBtn');
            const id = (replyBtn?.getAttribute('data-id') ?? '');

            const row = (data || []).find(x => String(x.contact_message_id ?? '') === String(id));

            const backdrop = document.getElementById('replyModalBackdrop');
            const closeBtn = document.getElementById('replyModalCloseBtn');
            const okBtn = document.getElementById('replyModalOkBtn');

            const toEl = document.getElementById('replyTo');
            const subjectEl = document.getElementById('replySubject');
            const messageEl = document.getElementById('replyMessage');
            const previewEl = document.getElementById('replyPreview');
            const metaEl = document.getElementById('replyMeta');
            const sendBtn = document.getElementById('replySendBtn');

            if (sendBtn) sendBtn.setAttribute('data-contact-message-id', id);


            if (!row) {
                if (previewEl) previewEl.textContent = 'Message not found.';
                if (backdrop) backdrop.classList.add('show');
                return;
            }

            if (toEl) toEl.value = row.email || '';
            if (subjectEl) subjectEl.value = row.subject ? `Re: ${row.subject}` : 'Re:';
            const quoted = (row.message || '').split(/\r?\n/).map(l => `> ${l}`).join('\n');
            const defaultBody = `Hello ${row.fullname ? row.fullname.split(' ')[0] : ''},\n\nThank you for reaching out. We have received your message and will get back to you shortly.\n\n${quoted}`;
            if (messageEl) messageEl.value = defaultBody;
            if (previewEl) previewEl.textContent = defaultBody;
            if (metaEl) metaEl.textContent = `Replying to: ${row.fullname || ''} (${row.email || ''})`;

            if (backdrop) backdrop.classList.add('show');

            // Close handlers (reliable even if modal is opened multiple times)
            if (closeBtn) closeBtn.onclick = closeReplyModal;
            if (okBtn) okBtn.onclick = closeReplyModal;

            if (backdrop) {
                backdrop.onclick = function(e){
                    if (e.target === backdrop) closeReplyModal();
                };
            }


        }

        function closeReplyModal(){
            closeModalByBackdrop('replyModalBackdrop');
        }


        async function sendReply(){
            const sendBtn = document.getElementById('replySendBtn');
            let contact_message_id = (sendBtn?.getAttribute('data-contact-message-id') ?? '').toString();



            // Fallback: derive id from currently displayed preview if button attribute isn't set.
            if (!contact_message_id) {
                const replyBtn = document.getElementById('messageModalReplyBtn');
                contact_message_id = (replyBtn?.getAttribute('data-id') ?? '');
            }


            const toEl = document.getElementById('replyTo');
            const subjectEl = document.getElementById('replySubject');
            const messageEl = document.getElementById('replyMessage');

            const to_email = (toEl?.value ?? '').trim();
            const subject = (subjectEl?.value ?? '').trim();
            const body = (messageEl?.value ?? '').trim();

            if (!contact_message_id) {
                alert('Missing message id. Please try again.');
                return;
            }
            if (!to_email || !subject || !body) {
                alert('To, subject and message are required.');
                return;
            }

            const originalText = sendBtn?.textContent;
            if (sendBtn) {
                sendBtn.textContent = 'Sending...';
                sendBtn.disabled = true;
            }

            try {
                const formData = new FormData();
                formData.append('contact_message_id', contact_message_id);
                formData.append('to_email', to_email);
                formData.append('subject', subject);
                formData.append('body', body);

                const res = await fetch('admin_send_contact_reply.php', {
                    method: 'POST',
                    body: formData
                });

                const json = await res.json();
                if (!json || !json.success) {
                    alert(json?.error || 'Failed to send email.');
                    return;
                }

                alert('Reply email sent successfully.');
                closeReplyModal();
            } catch (e) {
                console.error(e);
                alert('An error occurred while sending email.');
            } finally {
                if (sendBtn) {
                    sendBtn.disabled = false;
                    if (originalText != null) sendBtn.textContent = originalText;
                }
            }
        }


        document.addEventListener('DOMContentLoaded', function(){

            // Load summary KPIs (total/unread/replied/today)
            (async () => {
                try {
                    const res = await fetch('get_contact_messages_kpis.php');
                    const json = await res.json();
                    if (!json || !json.success) return;
                    const set = (id, val) => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = Number(val ?? 0);
                    };
                    set('kpiTotal', json.total);
                    set('kpiUnread', json.unread);
                    set('kpiReplied', json.replied);
                    set('kpiToday', json.today);
                } catch (e) {
                    console.error(e);
                }
            })();



            const loadingLabel = document.getElementById('loadingLabel');

            const searchInput = document.getElementById('searchInput');
            const messageFilter = document.getElementById('messageFilter');
            const searchClearBtn = document.getElementById('searchClearBtn');

            let currentFilter = 'all';


            function applyClientSearch(items){
                const q = (searchInput.value || '').toLowerCase().trim();
                if (!q) return renderTable(items);

                const filtered = items.filter(m => {
                    const hay = [m.fullname, m.email, m.subject, m.message].join(' ').toLowerCase();
                    return hay.includes(q);
                });
                renderTable(filtered);
            }

            async function loadMessages(filterValue){
                currentFilter = filterValue;

                // Backend supports: all | today | this_week | replied
                // Unread is not implemented in this inbox schema.
                const supported = ['all', 'today', 'this_week', 'replied'];
                const backendFilter = supported.includes(filterValue) ? filterValue : 'all';


                try {
                    const res = await fetch(`admin_view_contact_messages.php?filter=${encodeURIComponent(backendFilter)}`);
                    const json = await res.json();
                    data = Array.isArray(json) ? json : [];
                    renderTable(data);
                    if (loadingLabel) loadingLabel.textContent = `${data.length} message(s)`;

                    // Re-apply search on top of the loaded dataset.
                    applyClientSearch(data);

                    // Optional user hint for unsupported filters.
                    if (filterValue !== backendFilter) {
                        // eslint-disable-next-line no-console
                        console.warn(`Filter '${filterValue}' is not supported by the current contact_messages schema. Showing 'all' instead.`);
                    }
                } catch (err) {
                    if (loadingLabel) loadingLabel.textContent = 'Failed to load messages';
                    console.error(err);
                }
            }

            // Initial load
            if (messageFilter) {
                loadMessages(messageFilter.value || 'all');
                messageFilter.addEventListener('change', function(){
                    loadMessages(this.value || 'all');
                });
            } else {
                // Fallback: keep old behavior
                loadMessages('all');
            }

            // Search overlays currently loaded dataset
            const updateClearVisibility = () => {
                if (!searchClearBtn) return;
                const show = (searchInput?.value || '').trim().length > 0;
                searchClearBtn.style.display = show ? 'inline-flex' : 'none';
            };

            if (searchClearBtn) {
                // initial state
                updateClearVisibility();

                searchClearBtn.addEventListener('click', function(){
                    if (!searchInput) return;
                    searchInput.value = '';
                    updateClearVisibility();
                    applyClientSearch(data);
                    searchInput.focus();
                });
            }

            searchInput.addEventListener('input', function(){
                updateClearVisibility();
                applyClientSearch(data);
            });


            // Modal close helpers (in case user hits ESC)
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape') closeMessageModal();
            });

        });
    </script>
</body>
</html>

