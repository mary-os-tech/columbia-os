<?php
// =============================================
// CONTINUOUS SHARE EARBUD - Background Processor
// =============================================

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$username = $_SESSION['username'];

// Get the last shared track using track_uri (track_id doesn't exist in this table)
$sql_last = "SELECT track_uri, track_name, artist_name FROM music_shares 
             WHERE username = ? 
             ORDER BY shared_at DESC LIMIT 1";
$stmt = $conexao->prepare($sql_last);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$last_share = $result->fetch_assoc();
$stmt->close();

// Get current playing track
if (!isset($_SESSION[SPOTIFY_TOKEN_SESSION])) {
    echo json_encode(['error' => 'Spotify not connected']);
    exit;
}

$access_token = $_SESSION[SPOTIFY_TOKEN_SESSION];
$ch = curl_init(SPOTIFY_API_BASE . '/me/player/currently-playing');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['error' => 'No track playing']);
    exit;
}

$data = json_decode($response, true);
$current_track = $data['item'] ?? null;

if (!$current_track) {
    echo json_encode(['error' => 'No track data']);
    exit;
}

$track_id = $current_track['id'] ?? null;
$track_name = $current_track['name'] ?? 'Unknown';
$artist_name = $current_track['artists'][0]['name'] ?? 'Unknown';
$track_uri = 'spotify:track:' . $track_id;

// Check if this is a new track (compare URIs)
if ($last_share && $last_share['track_uri'] === $track_uri) {
    echo json_encode(['new_track' => false, 'message' => 'Same track']);
    exit;
}

// NEW TRACK! Trigger Share Earbud automatically
$share_data = [
    'track_name' => $track_name,
    'artist_name' => $artist_name,
    'track_uri' => $track_uri,
    'album_art' => $current_track['album']['images'][0]['url'] ?? null,
    'is_playing' => $data['is_playing'] ?? false,
    'duration_ms' => $current_track['duration_ms'] ?? 0,
    'progress_ms' => $data['progress_ms'] ?? 0
];
$session_id = session_id();
$session_name = session_name();
session_write_close();

// Call share_earbud.php
$ch = curl_init('http://127.0.0.1:8080/Columbia-os/actions/share_earbud.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($share_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, $session_name . '=' . $session_id);
$response = curl_exec($ch);
curl_close($ch);

echo json_encode([
    'new_track' => true,
    'track_name' => $track_name,
    'artist_name' => $artist_name,
    'response' => json_decode($response, true)
]);
?>