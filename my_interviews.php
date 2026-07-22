<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: Unauthorized Access.");
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Fetch user's full name
$sql = "SELECT fullname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullname);
$stmt->fetch();
$stmt->close();

// Fetch user's scheduled interviews with full details
$sql = "SELECT
            interviews.interview_id,
            interviews.interview_date,
            interviews.company_address,
            interviews.interviewer,
            interviews.interview_status,
            interviews.availability_status,
            interviews.reschedule_reason,
            interviews.cancellation_reason,
            interviews.interview_type,
            interviews.meeting_link,
            interviews.duration_minutes,
            job_postings.position,
            companies.company_name
        FROM interviews
        INNER JOIN job_postings ON interviews.job_id = job_postings.job_id
        INNER JOIN companies ON job_postings.company_id = companies.company_id
        WHERE interviews.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Interviews</title>
    <script>
        (function () {
            try {
                const isEnabled = localStorage.getItem('darkMode') === 'enabled';
                if (isEnabled) document.documentElement.classList.add('dark-mode');
            } catch (e) {}
        })();
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            /* Light Theme (Default) */
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --text-color: #2d3748;
            --text-muted: #718096;
            --bg-gradient-start: #e0e7ff;
            --bg-gradient-end: #f0f2f5;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Dark Theme Variables - Activated via Class */
        body.dark-mode {
            --primary-color: #7f9cf5;
            --text-color: #e2e8f0;
            --text-muted: #a0aec0;
            --bg-gradient-start: #1a202c;
            --bg-gradient-end: #2d3748;
            --card-bg: #2d3748;
            --border-color: #4a5568;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-color);
            transition: var(--transition);
        }

        /* Specific Overrides for Body Dark Mode */
        body.dark-mode .container {
            border: 1px solid var(--border-color);
        }
        
        body.dark-mode .header h2 {
            /* Make text clearer in dark mode */
            background: linear-gradient(135deg, #a3bffa, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 1200px;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: slideIn 0.5s ease-out;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }

        .header h2 {
            margin: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 28px;
            font-weight: 700;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            text-align: center;
        }

        /* Dark Mode Welcome Section */
        body.dark-mode .welcome-section {
            background: linear-gradient(135deg, #1e3a5f, #2d3748);
            border: 1px solid #4a5568;
        }
        .welcome-section h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .welcome-section p {
            opacity: 0.9;
            font-size: 18px;
        }

        /* Exit Button */
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

        body.dark-mode .btn-exit {
            background: linear-gradient(135deg, #c53030, #9b2c2c);
            box-shadow: none;
        }

        /* --- Toggle Switch Styling --- */
        .theme-switch-wrapper {
            display: flex;
            align-items: center;
            position: absolute;
            top: 28px;
            right: 80px; /* To the left of the exit button */
            z-index: 10;
        }

        .theme-switch {
            display: inline-block;
            height: 34px;
            position: relative;
            width: 60px;
        }

        .theme-switch input {
            display: none;
        }

        .slider {
            background-color: #ccc;
            bottom: 0;
            cursor: pointer;
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
            transition: .4s;
        }

        .slider:before {
            background-color: #fff;
            bottom: 4px;
            content: "";
            height: 26px;
            left: 4px;
            position: absolute;
            transition: .4s;
            width: 26px;
        }

        .slider.round {
            border-radius: 34px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        /* Checked State (Dark Mode) */
        input:checked + .slider {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        /* Icons inside the toggle */
        .slider-icon {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            padding: 0 8px;
            font-size: 14px;
            color: white;
        }

        /* Interview Container Styles */
        .interviews-container {
            margin-top: 20px;
        }

        .interview-container {
            max-width: 1200px;
            margin: 0 auto 30px auto;
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        body.dark-mode .interview-container {
            background: var(--card-bg);
        }

        .interview-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        body.dark-mode .interview-header {
            border-bottom-color: var(--border-color);
        }

        .interview-header h1 {
            font-size: 28px;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        body.dark-mode .interview-header h1 {
            color: #a7b7ff;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .status-scheduled { background-color: #d4edda; color: #155724; }
        .status-rescheduled { background-color: #fff3cd; color: #856404; }
        .status-completed { background-color: #d1ecf1; color: #0c5460; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }

        /* Availability badges */
        .status-pending { background-color: #e2e3e5; color: #41464b; }
        .status-available { background-color: #d4edda; color: #155724; }
        .status-not-available { background-color: #f8d7da; color: #721c24; }


        .interview-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        body.dark-mode .detail-section {
            background: #3a3b3c;
        }

        .detail-section h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        body.dark-mode .detail-section h3 {
            color: #a7b7ff;
        }

        .detail-item {
            margin-bottom: 12px;
        }

        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        body.dark-mode .detail-label {
            color: #adb5bd;
        }

        .detail-value {
            font-size: 16px;
            color: var(--text-color);
            margin-top: 4px;
        }

        body.dark-mode .detail-value {
            color: var(--text-color);
        }

        .interview-datetime {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 30px;
        }

        .interview-datetime h2 {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .interview-datetime p {
            opacity: 0.9;
            font-size: 16px;
        }

        .no-interviews {
            text-align: center;
            padding: 40px;
            color: var(--text-color);
            font-size: 18px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn-accept,
        .btn-decline {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }

        .btn-accept {
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
        }

        .btn-accept:hover {
            background: linear-gradient(135deg, #45a049, #4caf50);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .btn-decline {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
        }

        .btn-decline:hover {
            background: linear-gradient(135deg, #d32f2f, #f44336);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.3);
        }

        .btn-accept:disabled,
        .btn-decline:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* New styles for interview type and meeting link */
        .type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .type-online { background-color: #e3f2fd; color: #1976d2; }
        .type-inperson { background-color: #f3e5f5; color: #7b1fa2; }

        .meeting-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }
        .meeting-link-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        body.dark-mode .meeting-link-btn {
            background: linear-gradient(135deg, #7f9cf5, #a78bfa);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .interview-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .theme-switch-wrapper {
                right: 70px;
            }
            body {
                align-items: flex-start;
                margin-top: 30px;
            }
            .interview-details {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn-accept,
            .btn-decline {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-exit" id="exitPage"><i class='bx bx-x'></i></button>

        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Interviews</h1>
            <p>Track and manage your scheduled interviews</p>
        </div>

        <div class="interviews-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="interview-container">
                        <div class="interview-header">
                            <h1><?php echo htmlspecialchars($row['position']); ?> at <?php echo htmlspecialchars($row['company_name']); ?></h1>
                            <div class="status-badge status-<?php echo strtolower($row['interview_status']); ?>">
                                <?php echo ucfirst($row['interview_status']); ?>
                            </div>
                        </div>

                        <div class="interview-details">
                            <!-- Job Information -->
                            <div class="detail-section">
                                <h3><i class='bx bx-briefcase'></i> Job Information</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Position</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['position']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Company</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['company_name']); ?></div>
                                </div>
                            </div>

                            <!-- Interview Details -->
                            <div class="detail-section">
                                <h3><i class='bx bx-info-circle'></i> Interview Details</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Interviewer(s)</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($row['interviewer']); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Location</div>
                                    <div class="detail-value"><?php if (($row['interview_type'] ?? '') === 'Online'): ?><span class="type-badge type-online">Online</span><?php else: echo htmlspecialchars($row['company_address']); ?><?php endif; ?></div>
                                </div>
                            </div>

                            <!-- Interview Date & Time -->
                            <div class="detail-section">
                                <h3><i class='bx bx-calendar'></i> Interview Date & Time</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Scheduled Date</div>
                                    <div class="detail-value"><?php echo date('l, F j, Y', strtotime($row['interview_date'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Time</div>
                                    <div class="detail-value"><?php echo date('g:i A', strtotime($row['interview_date'])); ?></div>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div class="detail-section">
                                <h3><i class='bx bx-time'></i> Status Information</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value"><span class="status-badge status-<?php echo strtolower($row['interview_status']); ?>"><?php echo ucfirst($row['interview_status']); ?></span></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Availability</div>
                                    <div class="detail-value">
                                        <?php
                                            $availability = trim((string)($row['availability_status'] ?? 'Pending'));
                                            $availabilityClass = strtolower(str_replace(' ', '-', $availability));
                                        ?>
                                        <span class="status-badge status-<?php echo htmlspecialchars($availabilityClass); ?>">
                                            <?php echo htmlspecialchars(ucfirst($availability)); ?>
                                        </span>
                                    </div>
                                </div>

                            </div>

                            <!-- Interview Format -->
                            <div class="detail-section">
                                <h3><i class='bx bx-video'></i> Interview Format</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Type</div>
                                    <div class="detail-value">
                                        <span class="type-badge type-<?php echo strtolower(str_replace(' ', '', $row['interview_type'] ?? 'inperson')); ?>">
                                            <?php echo htmlspecialchars($row['interview_type'] ?? 'In-person'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Duration</div>
                                    <div class="detail-value"><?php echo ($row['duration_minutes'] ?? 30); ?> minutes</div>
                                </div>
                            </div>

                            <?php if (($row['interview_type'] ?? '') === 'Online' && !empty($row['meeting_link'])): ?>
                            <!-- Online Meeting Link -->
                            <div class="detail-section">
                                <h3><i class='bx bx-link-external'></i> Online Meeting</h3>
                                <div class="detail-item">
                                    <div class="detail-label">Join Link</div>
                                    <div class="detail-value">
                                        <a href="<?php echo htmlspecialchars($row['meeting_link']); ?>" target="_blank" class="meeting-link-btn">
                                            <i class='bx bx-link'></i> Join Meeting
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Actions -->
<div class="detail-section">
                                    <h3><i class='bx bx-cog'></i> Actions</h3>
                                    <div class="action-buttons">
                                        <form action="confirm_availability.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="interview_id" value="<?php echo $row['interview_id']; ?>">
                                            <input type="hidden" name="availability_status" value="Available">
                                            <button type="submit" class="btn-accept">
                                                <i class='bx bx-check'></i> Accept
                                            </button>
                                        </form>
                                        <form action="confirm_availability.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="interview_id" value="<?php echo $row['interview_id']; ?>">
                                            <input type="hidden" name="availability_status" value="Not Available">
                                            <button type="submit" class="btn-decline">
                                                <i class='bx bx-x'></i> Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>

                            <!-- Additional Information -->
                            <?php if (!empty($row['reschedule_reason']) || !empty($row['cancellation_reason'])): ?>
                                <div class="detail-section">
                                    <h3><i class='bx bx-info-circle'></i> Additional Information</h3>
                                    <?php if (!empty($row['reschedule_reason'])): ?>
                                        <div class="detail-item">
                                            <div class="detail-label">Reschedule Reason</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($row['reschedule_reason']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['cancellation_reason'])): ?>
                                        <div class="detail-item">
                                            <div class="detail-label">Cancellation Reason</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($row['cancellation_reason']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-interviews">
                    <i class='bx bx-calendar-x' style="font-size: 48px; color: #cbd5e0; margin-bottom: 15px;"></i>
                    <h3>No Scheduled Interviews</h3>
                    <p>You don't have any interviews scheduled at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // --- Dark Mode Logic (Synced with applicant.php) ---
        (function () {
            const body = document.body;

            const applyTheme = () => {
                const isEnabled = localStorage.getItem('darkMode') === 'enabled';
                if (isEnabled) body.classList.add('dark-mode');
                else body.classList.remove('dark-mode');
            };

            applyTheme();

            // Keep in sync across tabs/pages
            window.addEventListener('storage', (e) => {
                if (e.key !== 'darkMode') return;
                applyTheme();
            });
        })();

        // --- Exit Logic ---
        document.getElementById("exitPage").addEventListener("click", function() {
            window.location.href = 'applicant.php';
        });

        // Accept/Decline actions are handled by POST forms (confirm_availability.php)

        // --- SweetAlert2 Success Popup on Status Update ---
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');

            if (status === 'accepted' || status === 'declined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Status updated successfully!',
                    text: 'Your availability status has been updated successfully.',
                    confirmButtonColor: '#2980b9',
                    confirmButtonText: 'OK'
                });
                // Clean URL
                const cleanUrl = window.location.pathname + window.location.hash;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        })();
    </script>

</body>
</html>