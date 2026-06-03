<?php
session_start();
include('config.php');

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Consultant') {
    header('Location: index.php');
    exit();
}

if (isset($_GET['document_id'])) {
    $document_id = intval($_GET['document_id']);
    $user_id = $_SESSION['user_id'];

    // Check that the document belongs to the logged-in user
    $check = $conn->prepare("SELECT * FROM consultant_documents WHERE document_id = ? AND user_id = ?");
    $check->bind_param("ii", $document_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM consultant_documents WHERE document_id = ?");
        $stmt->bind_param("i", $document_id);
        if ($stmt->execute()) {
            echo "<script>alert('Document deleted successfully.'); window.location.href='consultant_profile.php';</script>";
        } else {
            echo "<script>alert('Failed to delete document.'); window.location.href='consultant_profile.php';</script>";
        }
    } else {
        echo "<script>alert('Unauthorized or document not found.'); window.location.href='consultant_profile.php';</script>";
    }
} else {
    echo "<script>alert('No document ID provided.'); window.location.href='consultant_profile.php';</script>";
}
?>