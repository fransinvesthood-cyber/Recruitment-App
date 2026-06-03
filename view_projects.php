<?php
// view_projects.php

session_start();
// Make sure the user is logged in before accessing
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "recruitment_db");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch projects
$sql = "SELECT id, uploaded_by, file_path, file_name, uploaded_at 
        FROM submitted_projects 
        ORDER BY uploaded_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>View Submitted Projects</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 50px auto;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8rem;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            font-size: 0.95rem;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .btn {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #218838;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="client_dashboard.php" class="btn">
            <i class='bx bx-arrow-back'></i> Back to Dashboard
        </a>

        <h1>📂 Submitted Projects</h1>
        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Client</th>
                    <th>Project</th>
                    <th>File</th>
                    <th>Uploaded At</th>
                    <th>Action</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['uploaded_by']) ?></td>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= htmlspecialchars($row['file_name']) ?></td>
                        <td><?= htmlspecialchars($row['uploaded_at']) ?></td>
                        <td>
                            <a href="download_projects.php?id=<?= $row['id'] ?>" class="btn">
                                Download
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p class="no-data">No projects submitted yet.</p>
        <?php endif; ?>
    </div>
</body>
</html>
