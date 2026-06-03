<?php
// client_dashboard.php
session_start();

include('session_config.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Client') {
    header("Location: index.php");
    exit();
}

include('config.php');

$username = $_SESSION['username'];
$fullname = 'Guest';
$email = 'No email available';
$user_id = null;

// Fetch client info
$stmt = $conn->prepare("SELECT user_id, fullname, email FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($db_user_id, $db_fullname, $db_email);
if ($stmt->fetch()) {
    $fullname = $db_fullname;
    $email = $db_email;
    $user_id = $db_user_id;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <title>Client Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:0; }
        /* Sidebar (Consultant-style) */
        .sidebar {
            width: 250px; background:#1f2937; position:fixed; top:0; left:0; height:100%; color:white;
        }
        .sidebar .logo {
            display:flex; align-items:center; justify-content:center; padding:20px; font-size:1.2rem;
        }
        .sidebar .logo i { margin-right:10px; font-size:1.5rem; }
        .sidebar ul { list-style:none; padding:0; margin:0; }
        .sidebar ul li { border-bottom:1px solid rgba(255,255,255,0.1); }
        .sidebar ul li a {
            display:flex; align-items:center; color:white; padding:15px 20px; text-decoration:none;
            transition:0.3s;
        }
        .sidebar ul li a i { margin-right:12px; font-size:1.2rem; }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        }

        /* Content area */
        .content { margin-left:250px; padding:30px; }

        /* Welcome section */
        .welcome-section {
            background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
            color:white; padding:25px; border-radius:12px; margin-bottom:20px;
        }
        .welcome-section h1 { margin:0 0 10px 0; font-size:2rem; }
        .welcome-section p { margin:0; opacity:0.9; }

        /* Dashboard cards (Quick Actions) */
        .dashboard-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:25px; margin-top:20px; }
        .dashboard-card {
            background:white; border-radius:12px; padding:25px; box-shadow:0 4px 6px rgba(0,0,0,0.1);
            transition:all 0.3s ease; text-decoration:none; color:inherit; display:block; border:1px solid #e5e7eb;
        }
        .dashboard-card:hover { transform:translateY(-5px); box-shadow:0 8px 25px rgba(0,0,0,0.15); }
        .dashboard-card h3 { margin-bottom:10px; font-size:1.2rem; font-weight:600; color:#1f2937; }
        .dashboard-card p { margin:0; font-size:0.9rem; color:#6b7280; }
        .dashboard-card .card-icon { font-size:2.5rem; color:#3b82f6; margin-bottom:15px; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo"><i class='bx bx-user-circle'></i><span>Client</span></div>
        <ul class="side-menu">
            <li class="active"><a href="client_dashboard.php"><i class='bx bxs-dashboard'></i>Dashboard</a></li>
            <li><a href="view_projects.php"><i class='bx bx-folder-open'></i>View Projects</a></li>
            <li><a href="consultant_feedback.php"><i class='bx bx-message-dots'></i>Give Feedback</a></li>
            <li><a href="client_profile.php"><i class='bx bx-user'></i>Manage Profile</a></li>
        </ul>
        <ul class="side-menu">
            <li><a href="logout.php" class="logout" onclick="return confirmLogout();"><i class='bx bx-log-out-circle'></i>Logout</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="welcome-section">
            <h1>Welcome, <?= htmlspecialchars($fullname); ?>!</h1>
            <p>Here’s your client dashboard</p>
        </div>

        <!-- Quick Action Cards -->
        <div class="dashboard-grid">
            <a href="view_projects.php" class="dashboard-card">
                <i class='bx bx-folder-open card-icon'></i>
                <h3>View Projects</h3>
                <p>Track submitted projects and review consultant feedback.</p>
            </a>
            <a href="client_profile.php" class="dashboard-card">
                <i class='bx bx-user card-icon'></i>
                <h3>Profile Management</h3>
                <p>Update your personal information and account settings.</p>
            </a>
        </div>
    </div>

    <script src="session_manager.js"></script>
    <script>
        function confirmLogout() { return confirm("Are you sure you want to log out?"); }
    </script>
</body>
</html>
