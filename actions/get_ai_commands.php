<?php
// =============================================
// GET AI COMMANDS
// =============================================

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get unprocessed commands
$sql = "SELECT id, command, data FROM ai_commands WHERE processed = FALSE ORDER BY created_at LIMIT 10";
$result = $conexao->query($sql);

$commands = [];
while ($row = $result->fetch_assoc()) {
    $commands[] = $row;
}

// Mark them as processed
if (count($commands) > 0) {
    $ids = implode(',', array_column($commands, 'id'));
    $conexao->query("UPDATE ai_commands SET processed = TRUE WHERE id IN ($ids)");
}

echo json_encode([
    'success' => true,
    'commands' => $commands
]);
?>