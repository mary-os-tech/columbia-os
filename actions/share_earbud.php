<?php
// =============================================
// SHARE EARBUD - Connected to REAL AI Engine
// =============================================
set_time_limit(30);
session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';
include_once '../includes/lottie_presence.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$username = $_SESSION['username'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = [
        'track_name' => $_POST['track_name'] ?? null,
        'artist_name' => $_POST['artist_name'] ?? null,
        'track_uri' => $_POST['track_uri'] ?? null,
        'is_playing' => $_POST['is_playing'] ?? false
    ];
}

$track_name = $input['track_name'] ?? 'Unknown Track';
$artist_name = $input['artist_name'] ?? 'Unknown Artist';
$track_uri = $input['track_uri'] ?? null;
$is_playing = $input['is_playing'] ?? false;

if (!$is_playing || empty($track_name)) {
    echo json_encode([
        'success' => true,
        'message' => "🎧 babe, you're sharing your music but nothing's playing! 😅 put something on!",
        'vibe' => 'playful'
    ]);
    exit;
}

// 1. SAVE THE SHARE AS A DM FROM MARY (Clean format)
$stmt = $conexao->prepare("INSERT INTO music_events (username, event_type, track_name, artist_name, track_uri) VALUES (?, 'listening', ?, ?, ?)");
$stmt->bind_param("ssss", $username, $track_name, $artist_name, $track_uri);
$stmt->execute();
$stmt->close();

// 2. WAKE UP THE REAL AI ENGINE
$ch = curl_init('http://127.0.0.1:8080/Columbia-os/actions/ai_engine.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 25);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'npc' => 'lottiematthews',
    'music_event' => true,
    'track_name' => $track_name,
    'artist_name' => $artist_name,
    'track_uri' => $track_uri
]));
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$ai_reply = curl_exec($ch);
curl_close($ch);

if (empty(trim($ai_reply))) {
    $ai_reply = "🎧 listening with you 💕";
}

// 3. UPDATE PRESENCE & LOGS
update_lottie_presence($conexao, 'listening', $track_name, $artist_name);

$stmt = $conexao->prepare("INSERT INTO music_shares (username, track_name, artist_name, track_uri, is_playing, lottie_vibe) VALUES (?, ?, ?, ?, ?, 'dynamic')");
$stmt->bind_param("ssssi", $username, $track_name, $artist_name, $track_uri, $is_playing);
$stmt->execute();
$stmt->close();

// =============================================
// RETURN TO UI
// =============================================
echo json_encode([
    'success' => true,
    'message' => $ai_reply,
    'vibe' => 'dynamic',
    'track_name' => $track_name,
    'artist_name' => $artist_name,
    'track_id' => $track_uri
]);
?>