<?php
include('config.php');
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interview_id = isset($_POST['interview_id']) ? intval($_POST['interview_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if (!$interview_id || !in_array($action, ['accept', 'decline'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    // Verify the interview belongs to the user
    $sql = "SELECT * FROM interviews WHERE interview_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $interview_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Interview not found']);
        exit;
    }

    $interview = $result->fetch_assoc();

    // Check if already responded
    if (strtolower($interview['availability_status']) === 'accepted' || strtolower($interview['availability_status']) === 'declined') {
        echo json_encode(['status' => 'error', 'message' => 'Already responded']);
        exit;
    }

    // Update availability status
    $status = ($action === 'accept') ? 'Accepted' : 'Declined';
    $sql = "UPDATE interviews SET availability_status = ? WHERE interview_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $interview_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Response updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update response']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>
