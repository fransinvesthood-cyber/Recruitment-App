<?php
// chat_handler.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

include('config.php');

$user_id = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'send_message':
        sendMessage($conn, $user_id);
        break;
        
    case 'get_messages':
        getMessages($conn, $user_id);
        break;
        
    case 'mark_read':
        markMessagesAsRead($conn, $user_id);
        break;
        
    case 'send_voice_note':
        sendVoiceNote($conn, $user_id);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function sendMessage($conn, $sender_id) {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message) || $receiver_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid message or receiver']);
        return;
    }
    
    // Validate that receiver exists
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receiver = $result->fetch_assoc();
    $stmt->close();
    
    if (!$receiver) {
        echo json_encode(['success' => false, 'error' => 'Receiver not found']);
        return;
    }
    
    // Get sender role
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sender = $result->fetch_assoc();
    $stmt->close();
    
    // Validate communication between Admin and Consultant only
    if (!(($sender['role'] === 'Consultant' && $receiver['role'] === 'Admin') || 
          ($sender['role'] === 'Admin' && $receiver['role'] === 'Consultant'))) {
        echo json_encode(['success' => false, 'error' => 'Invalid communication channel']);
        return;
    }
    
    // Insert message with proper message_type
    $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, message_type) VALUES (?, ?, ?, 'text')");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    
    if ($stmt->execute()) {
        $message_id = $conn->insert_id;
        $stmt->close();
        
        // Update or create conversation
        $consultant_id = ($sender['role'] === 'Consultant') ? $sender_id : $receiver_id;
        $admin_id = ($sender['role'] === 'Admin') ? $sender_id : $receiver_id;
        
        $stmt = $conn->prepare("
            INSERT INTO chat_conversations (consultant_id, admin_id, last_message_id, last_activity) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            last_message_id = ?, last_activity = NOW()
        ");
        $stmt->bind_param("iiii", $consultant_id, $admin_id, $message_id, $message_id);
        $stmt->execute();
        $stmt->close();

        // Create notification for admin if sender is a consultant
        if ($sender['role'] === 'Consultant' && $receiver['role'] === 'Admin') {
            // Get consultant's fullname for the notification message
            $stmt = $conn->prepare("SELECT fullname FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $sender_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $consultant = $result->fetch_assoc();
            $stmt->close();

            $notification_message = "New message from consultant " . $consultant['fullname'];
            
            $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, reference_id, is_read, created_at) VALUES (?, ?, 'chat', ?, 0, NOW())");
            $notification_stmt->bind_param("isi", $admin_id, $notification_message, $sender_id);
            $notification_stmt->execute();
            $notification_stmt->close();
        }
        
        echo json_encode(['success' => true, 'message_id' => $message_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message: ' . $conn->error]);
    }
}

function getMessages($conn, $user_id) {
    $other_user_id = intval($_GET['other_user_id'] ?? 0);
    $last_message_id = intval($_GET['last_message_id'] ?? 0);
    
    if ($other_user_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT cm.*, u.fullname, u.role 
        FROM chat_messages cm 
        JOIN users u ON cm.sender_id = u.user_id 
        WHERE ((cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?)) 
        AND cm.message_id > ?
        ORDER BY cm.timestamp ASC 
        LIMIT 100
    ");
    $stmt->bind_param("iiiii", $user_id, $other_user_id, $other_user_id, $user_id, $last_message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
    
    echo json_encode(['success' => true, 'messages' => $messages]);
}

function markMessagesAsRead($conn, $user_id) {
    $other_user_id = intval($_POST['other_user_id'] ?? 0);
    
    if ($other_user_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        return;
    }
    
    $stmt = $conn->prepare("
        UPDATE chat_messages 
        SET is_read = TRUE 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = FALSE
    ");
    $stmt->bind_param("ii", $other_user_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
}

function sendVoiceNote($conn, $sender_id) {
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    
    if ($receiver_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid receiver']);
        return;
    }
    
    // Check if voice note file was uploaded
    if (!isset($_FILES['voice_note']) || $_FILES['voice_note']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'No voice note uploaded']);
        return;
    }
    
    // Validate that receiver exists
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receiver = $result->fetch_assoc();
    $stmt->close();
    
    if (!$receiver) {
        echo json_encode(['success' => false, 'error' => 'Receiver not found']);
        return;
    }
    
    // Get sender role
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sender = $result->fetch_assoc();
    $stmt->close();
    
    // Validate communication
    if (!(($sender['role'] === 'Consultant' && $receiver['role'] === 'Admin') || 
          ($sender['role'] === 'Admin' && $receiver['role'] === 'Consultant'))) {
        echo json_encode(['success' => false, 'error' => 'Invalid communication channel']);
        return;
    }
    
    // Create voice_notes directory if it doesn't exist
    $upload_dir = 'voice_notes/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = 'webm';
    $filename = 'voice_' . time() . '_' . uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['voice_note']['tmp_name'], $filepath)) {
        // Get audio duration (approximate)
        $duration = '0:' . str_pad(rand(5, 59), 2, '0', STR_PAD_LEFT);
        
        // Insert voice message
        $stmt = $conn->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message_type, voice_note_path, voice_note_duration) VALUES (?, ?, 'voice', ?, ?)");
        $stmt->bind_param("iiss", $sender_id, $receiver_id, $filepath, $duration);
        
        if ($stmt->execute()) {
            $message_id = $conn->insert_id;
            $stmt->close();
            
            // Update conversation
            $consultant_id = ($sender['role'] === 'Consultant') ? $sender_id : $receiver_id;
            $admin_id = ($sender['role'] === 'Admin') ? $sender_id : $receiver_id;
            
            $stmt = $conn->prepare("
                INSERT INTO chat_conversations (consultant_id, admin_id, last_message_id, last_activity) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                last_message_id = ?, last_activity = NOW()
            ");
            $stmt->bind_param("iiii", $consultant_id, $admin_id, $message_id, $message_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'message_id' => $message_id]);
        } else {
            unlink($filepath); // Delete uploaded file on failure
            echo json_encode(['success' => false, 'error' => 'Failed to save voice note']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload voice note']);
    }
}

$conn->close();
?>