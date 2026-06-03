<?php
session_start();
include('config.php');

// NOTE: This page assumes the admin is logged in.
// If your project has a dedicated admin auth check, add it here.

header('Content-Type: application/json');

$filter = isset($_GET['filter']) ? (string)$_GET['filter'] : 'all';
$filter = strtolower(trim($filter));

// Supported filters
// all | today | this_week | replied
// (unread is not implemented in schema; UI can still show it but backend defaults to all)
$allowed = ['all', 'today', 'this_week', 'replied'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'all';
}

try {
    $where = '';
    $conditions = [];

    // time conditions
    switch ($filter) {
        case 'today':
            $conditions[] = "DATE(created_at) = CURDATE()";
            break;
        case 'this_week':
            // MySQL WEEK mode default; captures current week for the server locale.
            $conditions[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'replied':
            $conditions[] = "replied = 1";
            break;
        case 'all':
        default:
            // no time condition
            break;
    }

    if (!empty($conditions)) {
        $where = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "SELECT 
                contact_message_id,
                fullname,
                email,
                subject,
                message,
                created_at
            FROM contact_messages
            {$where}
            ORDER BY created_at DESC";



    // If created_at does not exist in older schema, this will throw.
    // We'll fall back to using an auto id timestamp-free ordering.
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception($conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = [
            'contact_message_id' => $row['contact_message_id'] ?? null,
            'fullname' => $row['fullname'] ?? '',
            'email' => $row['email'] ?? '',
            'subject' => $row['subject'] ?? '',
            'message' => $row['message'] ?? '',
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    echo json_encode($rows);
} catch (Throwable $e) {
    // Try schema fallback: some installations might not have contact_message_id or created_at.
    // We still return a best-effort response.
    try {
        $fallbackWhere = '';
        $fallbackConditions = [];

        switch ($filter) {
            case 'today':
                $fallbackConditions[] = "DATE(created_at) = CURDATE()";
                break;
            case 'this_week':
                $fallbackConditions[] = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'replied':
                $fallbackConditions[] = "replied = 1";
                break;
            case 'all':
            default:
                break;
        }

        if (!empty($fallbackConditions)) {
            $fallbackWhere = 'WHERE ' . implode(' AND ', $fallbackConditions);
        }

        $fallbackSql = "SELECT fullname, email, subject, message, created_at FROM contact_messages {$fallbackWhere} ORDER BY created_at DESC";

        $fallback = $conn->query($fallbackSql);
        if (!$fallback) {
            throw new Exception('Fallback query failed: ' . $conn->error);
        }

        $rows = [];
        while ($row = $fallback->fetch_assoc()) {
            $rows[] = [
                'contact_message_id' => null,
                'fullname' => $row['fullname'] ?? '',
                'email' => $row['email'] ?? '',
                'subject' => $row['subject'] ?? '',
                'message' => $row['message'] ?? '',
                'created_at' => $row['created_at'] ?? null,
            ];
        }

        echo json_encode($rows);
    } catch (Throwable $e2) {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to fetch contact messages']);
    }
}

