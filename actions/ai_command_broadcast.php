<?php
// =============================================
// AI COMMAND BROADCAST
// =============================================
// This receives commands from the AI and broadcasts them

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$command = $_POST['command'] ?? $_GET['command'] ?? null;
$data = $_POST['data'] ?? $_GET['data'] ?? null;

if (!$command) {
    echo json_encode(['error' => 'No command']);
    exit;
}

// Log the command
$log = "AI Command: $command - " . date('Y-m-d H:i:s');
error_log($log);

// Store in a table for the frontend to poll
$sql = "CREATE TABLE IF NOT EXISTS ai_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    command VARCHAR(50),
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conexao->query($sql);

// Store the command
$sql = "INSERT INTO ai_commands (command, data) VALUES (?, ?)";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("ss", $command, $data);
$stmt->execute();
$stmt->close();

echo json_encode([
    'success' => true,
    'command' => $command,
    'message' => 'Command broadcasted'
]);
?>