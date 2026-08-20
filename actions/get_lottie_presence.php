<?php
// =============================================
// GET LOTTIE'S PRESENCE STATUS
// =============================================

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';

$sql = "SELECT * FROM lottie_spotify_state WHERE id = 1";
$result = $conexao->query($sql);

if ($result && $result->num_rows > 0) {
    $state = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'is_listening' => (bool)$state['is_listening'],
        'current_track' => $state['current_track'],
        'current_artist' => $state['current_artist'],
        'status_message' => $state['status_message'] ?? '💚 Online',
        'headphones_on' => (bool)$state['headphones_on']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No state found'
    ]);
}
?>