<?php
session_start();
require_once '../includes/conexao.php';

header('Content-Type: application/json');

$username = $_SESSION['username'] ?? '';

if (empty($username)) {
    echo json_encode(["success" => false, "message" => "No active session."]);
    exit;
}

try {
    // Fetch User ID
    $stmtUser = $conexao->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->bind_param("s", $username);
    $stmtUser->execute();
    $user_id = $stmtUser->get_result()->fetch_assoc()['id'] ?? null;

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "User not found."]);
        exit;
    }

    $current_date = date('Y-m-d');

    // Check if a screen time record exists for today
    $stmtCheck = $conexao->prepare("SELECT id FROM screen_time WHERE user_id = ? AND date = ?");
    $stmtCheck->bind_param("is", $user_id, $current_date);
    $stmtCheck->execute();
    $record = $stmtCheck->get_result()->fetch_assoc();

    if ($record) {
        // Update existing record (+1 minute)
        $stmtUpdate = $conexao->prepare("UPDATE screen_time SET minutes_spent = minutes_spent + 1 WHERE id = ?");
        $stmtUpdate->bind_param("i", $record['id']);
        $stmtUpdate->execute();
    } else {
        // Insert new record for today
        $stmtInsert = $conexao->prepare("INSERT INTO screen_time (user_id, date, minutes_spent) VALUES (?, ?, 1)");
        $stmtInsert->bind_param("is", $user_id, $current_date);
        $stmtInsert->execute();
    }

    echo json_encode(["success" => true, "message" => "Screen time logged."]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>