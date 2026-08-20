<?php
// =============================================
// SPOTIFY CURRENT TRACK FETCHER
// =============================================
// Returns the user's currently playing track with caching
// Usage: GET /actions/spotify_current_track.php
set_time_limit(15);
session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

// =============================================
// 1. VERIFICAÇÕES PRIMEIRO (SESSÃO E TOKEN)
// =============================================

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Check if Spotify is connected
if (!isset($_SESSION[SPOTIFY_TOKEN_SESSION])) {
    http_response_code(401);
    echo json_encode(['error' => 'Spotify not connected', 'connect_url' => '/includes/spotify_auth.php?action=login']);
    exit;
}

// =============================================
// 2. FUNÇÃO PARA REFRESH TOKEN
// =============================================
function refresh_spotify_token() {
    include_once '../includes/spotify_config.php';
    
    $refresh_token = $_SESSION[SPOTIFY_REFRESH_SESSION] ?? null;
    if (!$refresh_token) return false;
    
    $post_data = http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
        'client_id' => SPOTIFY_CLIENT_ID,
        'client_secret' => SPOTIFY_CLIENT_SECRET
    ]);
    
    $ch = curl_init(SPOTIFY_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) return false;
    
    $token_data = json_decode($response, true);
    
    if (!isset($token_data['access_token'])) return false;
    
    $_SESSION[SPOTIFY_TOKEN_SESSION] = $token_data['access_token'];
    $_SESSION[SPOTIFY_EXPIRY_SESSION] = time() + ($token_data['expires_in'] ?? 3600);
    
    return true;
}

// =============================================
// 3. VERIFICAR EXPIRAÇÃO DO TOKEN
// =============================================
if (isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION]) {
    $refresh_success = refresh_spotify_token();
    if (!$refresh_success) {
        http_response_code(401);
        echo json_encode(['error' => 'Token expired, please reconnect', 'connect_url' => '/includes/spotify_auth.php?action=login']);
        exit;
    }
}

$username = $_SESSION['username'];
$access_token = $_SESSION[SPOTIFY_TOKEN_SESSION];

// =============================================
// 4. VERIFICAR CACHE (30 segundos TTL)
// =============================================
$cache_ttl = SPOTIFY_CACHE_TTL;
$sql_check = "SELECT * FROM spotify_cache 
              WHERE user_id = ? 
              AND fetched_at > DATE_SUB(NOW(), INTERVAL $cache_ttl SECOND)
              ORDER BY fetched_at DESC LIMIT 1";

$stmt = $conexao->prepare($sql_check);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $cached = $result->fetch_assoc();
    
    // Return cached data with cache flag
    echo json_encode([
        'cached' => true,
        'is_playing' => (bool)$cached['is_playing'],
        'track_id' => $cached['track_id'],
        'track_name' => $cached['track_name'],
        'artist_name' => $cached['artist_name'],
        'album_name' => $cached['album_name'],
        'album_art_url' => $cached['album_art_url'],
        'duration_ms' => (int)$cached['duration_ms'],
        'progress_ms' => (int)$cached['progress_ms'],
        'fetched_at' => $cached['fetched_at']
    ]);
    exit;
}

// =============================================
// 5. BUSCAR DA API DO SPOTIFY
// =============================================
$ch = curl_init(SPOTIFY_API_BASE . '/me/player/currently-playing');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Handle no active playback
if ($http_code === 204) {
    // No track playing - update cache to reflect this
    $stmt = $conexao->prepare("INSERT INTO spotify_cache (user_id, is_playing, fetched_at) VALUES (?, 0, NOW())");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'cached' => false,
        'is_playing' => false,
        'track_name' => 'Nothing playing',
        'artist_name' => '—'
    ]);
    exit;
}

if ($http_code !== 200) {
    http_response_code($http_code);
    echo json_encode(['error' => 'Failed to fetch current track']);
    exit;
}

$data = json_decode($response, true);

// Parse track data
$track = $data['item'] ?? null;
$is_playing = $data['is_playing'] ?? false;

if (!$track) {
    echo json_encode([
        'cached' => false,
        'is_playing' => false
    ]);
    exit;
}

// Build response data
$track_data = [
    'cached' => false,
    'is_playing' => $is_playing,
    'track_id' => $track['id'] ?? null,
    'track_name' => $track['name'] ?? 'Unknown Track',
    'artist_name' => $track['artists'][0]['name'] ?? 'Unknown Artist',
    'album_name' => $track['album']['name'] ?? 'Unknown Album',
    'album_art_url' => $track['album']['images'][0]['url'] ?? null,
    'duration_ms' => $track['duration_ms'] ?? 0,
    'progress_ms' => $data['progress_ms'] ?? 0
];

// =============================================
// 6. SALVAR NO CACHE
// =============================================
$stmt = $conexao->prepare("INSERT INTO spotify_cache 
    (user_id, track_id, track_name, artist_name, album_name, album_art_url, duration_ms, is_playing, progress_ms, fetched_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

$stmt->bind_param(
    "ssssssiii",
    $username,
    $track_data['track_id'],
    $track_data['track_name'],
    $track_data['artist_name'],
    $track_data['album_name'],
    $track_data['album_art_url'],
    $track_data['duration_ms'],
    $track_data['is_playing'],
    $track_data['progress_ms']
);
$stmt->execute();
$stmt->close();

// Return the data
echo json_encode($track_data);
?>