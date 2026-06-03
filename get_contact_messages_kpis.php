<?php
session_start();
include('config.php');

header('Content-Type: application/json');

try {
    // Debug helper: if KPIs show 0 unexpectedly, set this to true temporarily.
    $debug = false;

    // contact_messages schema (per create_contact_messages_table.sql):
    // - replied TINYINT(1) NOT NULL DEFAULT 0
    // - created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP

    $total = 0;
    $replied = 0;
    $today = 0;

    // Unread logic (per user confirmation): not replied yet
    // If you later add a real is_read column, this can be swapped.
    $unread = 0;

    $sqlTotal = "SELECT COUNT(*) AS cnt FROM contact_messages";
    $resTotal = $conn->query($sqlTotal);
    if ($resTotal && ($row = $resTotal->fetch_assoc())) {
        $total = (int)($row['cnt'] ?? 0);
    }

    // replied column may not exist in older schemas
    $hasRepliedCol = false;
    try {
        $colCheck = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'replied'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasRepliedCol = true;
        }
    } catch (Throwable $e) {
        $hasRepliedCol = false;
    }

    $sqlReplied = "SELECT COUNT(*) AS cnt FROM contact_messages";
    if ($hasRepliedCol) {
        $sqlReplied = "SELECT COUNT(*) AS cnt FROM contact_messages WHERE replied = 1";
    }

    $resReplied = $conn->query($sqlReplied);
    if ($resReplied && ($row = $resReplied->fetch_assoc())) {
        $replied = (int)($row['cnt'] ?? 0);
    }


    $sqlToday = "SELECT COUNT(*) AS cnt FROM contact_messages WHERE DATE(created_at) = CURDATE()";
    $resToday = $conn->query($sqlToday);
    if ($resToday && ($row = $resToday->fetch_assoc())) {
        $today = (int)($row['cnt'] ?? 0);
    }

    $unread = max(0, $total - $replied);

    $response = [
        'success' => true,
        'total' => $total,
        'unread' => $unread,
        'replied' => $replied,
        'today' => $today,
    ];

    if (!empty($debug)) {
        $response['debug'] = [
            'total_sql' => $sqlTotal,
            'replied_sql' => $sqlReplied,
            'today_sql' => $sqlToday,
            'server_time' => date('c'),
            'db_name' => $conn->select_db ? null : null,
        ];
    }

    echo json_encode($response);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch contact message KPIs',
        'exception' => $e->getMessage(),
    ]);
}


