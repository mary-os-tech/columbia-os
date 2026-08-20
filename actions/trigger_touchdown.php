<?php
session_start();
include_once '../includes/conexao.php';

header('Content-Type: application/json');

// Check current location state
$sql_check = "SELECT setting_value FROM settings WHERE setting_key = 'current_location'";
$res_check = $conexao->query($sql_check);
$row = $res_check->fetch_assoc();

if ($row && $row['setting_value'] !== 'NY') {
    // Update global OS location to NY
    $conexao->query("UPDATE settings SET setting_value = 'NY' WHERE setting_key = 'current_location'");
    
    // Unlock Lottie's past lore
    $conexao->query("UPDATE posts SET is_locked = 0 WHERE is_locked = 1");
    
    echo json_encode(['status' => 'success', 'message' => 'Touchdown triggered.']);
} else {
    echo json_encode(['status' => 'ignored', 'message' => 'Already in NY.']);
}
?>