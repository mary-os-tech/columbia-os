<?php
/**
 * Columbia OS - Get Lottie's Spotify Listening State
 * Used by music_player.php to show what Lottie is listening to
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Active session required.']);
    exit();
}

require_once '../includes/conexao.php';

$username = $_SESSION['username'];

$stmt = $conexao->prepare("SELECT is_listening, current_track_name, current_artist, mood_playlist, last_updated 
                           FROM lottie_spotify_state 
                           WHERE username = ? 
                           ORDER BY last_updated DESC LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $state = $result->fetch_assoc();
    echo json_encode([
        'is_listening' => (bool)$state['is_listening'],
        'current_track' => $state['current_track_name'],
        'current_artist' => $state['current_artist'],
        'mood_playlist' => $state['mood_playlist'],
        'last_updated' => $state['last_updated']
    ]);
} else {
    echo json_encode([
        'is_listening' => false,
        'current_track' => null,
        'current_artist' => null,
        'mood_playlist' => 'balanced',
        'last_updated' => null
    ]);
}

$stmt->close();
?>