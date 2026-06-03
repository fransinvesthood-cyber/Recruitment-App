<?php
include('config.php');
session_start();

// Test direct access
header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

echo json_encode([
    'logged_in' => isset($_SESSION['user_id']),
    'session_user_id' => $_SESSION['user_id'] ?? null,
    'search_query' => $search,
    'connection_status' => $conn->connect_error ?? 'connected'
]);

$conn->close();
exit;
