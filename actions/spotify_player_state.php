<?php
// =============================================
// GET SPOTIFY PLAYER STATE (Including Volume)
// =============================================

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

if (!isset($_SESSION[SPOTIFY_TOKEN_SESSION])) {
    http_response_code(401);
    echo json_encode(['error' => 'Spotify not connected']);
    exit;
}

// Check token expiry and refresh if needed
if (isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION]) {
    // Let the main fetch handle refresh
    http_response_code(401);
    echo json_encode(['error' => 'Token expired, please reconnect']);
    exit;
}

$access_token = $_SESSION[SPOTIFY_TOKEN_SESSION];

$ch = curl_init(SPOTIFY_API_BASE . '/me/player');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['error' => 'Could not get player state']);
    exit;
}

$player_state = json_decode($response, true);

echo json_encode([
    'success' => true,
    'is_playing' => $player_state['is_playing'] ?? false,
    'volume' => $player_state['device']['volume_percent'] ?? 0,
    'device_name' => $player_state['device']['name'] ?? 'Unknown'
]);
?>