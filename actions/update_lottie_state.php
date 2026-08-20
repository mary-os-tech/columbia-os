<?php
// =============================================
// MANUALLY UPDATE LOTTIE'S STATE (Testing)
// =============================================

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';
include_once '../includes/lottie_presence.php';

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'mary') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$track = $_POST['track'] ?? $_GET['track'] ?? 'Test Song';
$artist = $_POST['artist'] ?? $_GET['artist'] ?? 'Test Artist';

if ($action === 'listen') {
    update_lottie_presence($conexao, 'listening', $track, $artist);
    echo json_encode(['success' => true, 'message' => "Lottie is now listening to '$track' by $artist"]);
} elseif ($action === 'react') {
    update_lottie_presence($conexao, 'reacting', $track, $artist);
    echo json_encode(['success' => true, 'message' => "Lottie is reacting to '$track'"]);
} elseif ($action === 'online') {
    update_lottie_presence($conexao, 'online');
    echo json_encode(['success' => true, 'message' => "Lottie is online"]);
} elseif ($action === 'status') {
    $state = get_lottie_presence($conexao);
    echo json_encode(['success' => true, 'state' => $state]);
} else {
    echo json_encode(['error' => 'Invalid action. Use: listen, react, online, status']);
}
?>