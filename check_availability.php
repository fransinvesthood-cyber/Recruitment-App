<?php
session_start();
include('config.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['type']) || !isset($input['value'])) {
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    $type = $input['type'];
    $value = trim($input['value']);
    
    if (empty($value)) {
        echo json_encode(['available' => true]);
        exit;
    }
    
    $response = ['available' => true, 'message' => ''];
    
    if ($type === 'username') {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if ($count > 0) {
            $response['available'] = false;
            $response['message'] = 'Username is already taken';
        } else {
            $response['message'] = 'Username is available';
        }
        
    } elseif ($type === 'email') {
        // Basic email validation
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $response['available'] = false;
            $response['message'] = 'Invalid email format';
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->bind_param("s", $value);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            
            if ($count > 0) {
                $response['available'] = false;
                $response['message'] = 'Email is already registered';
            } else {
                $response['message'] = 'Email is available';
            }
        }
    }
    
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Method not allowed']);
}

$conn->close();
?>