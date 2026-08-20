<?php
// =============================================
// SPOTIFY CONTROL ENDPOINT (FULL VERSION)
// =============================================
set_time_limit(15);
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

$access_token = $_SESSION[SPOTIFY_TOKEN_SESSION];
$username = $_SESSION['username'];

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data) {
    $data = [
        'command' => $_POST['command'] ?? null,
        'track_uri' => $_POST['track_uri'] ?? null,
        'volume' => $_POST['volume'] ?? null,
        'name' => $_POST['name'] ?? null,
        'description' => $_POST['description'] ?? null,
        'playlist_name' => $_POST['playlist_name'] ?? null
    ];
}

$command = $data['command'] ?? null;
$track_uri = $data['track_uri'] ?? null;
$volume = isset($data['volume']) ? (int)$data['volume'] : null;

if (!$command) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing command parameter']);
    exit;
}

// Helper to get default saved playlist ID from DB
function get_saved_playlist_id($conexao, $user) {
    $sql = "SELECT setting_value FROM settings WHERE username = ? AND setting_key = 'spotify_playlist_id'";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc()['setting_value'];
    }
    return null;
}

$spotify_base = SPOTIFY_API_BASE;

switch ($command) {
    // =========================================
    // PLAY
    // =========================================
    case 'play':
        $url = $spotify_base . '/me/player/play';
        $body = $track_uri ? json_encode(['uris' => [$track_uri]]) : '{}';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? 'Playback resumed' : 'Failed to play';
        break;
    
    // =========================================
    // PAUSE
    // =========================================
    case 'pause':
        $ch = curl_init($spotify_base . '/me/player/pause');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? 'Playback paused' : 'Failed to pause';
        break;
    
    // =========================================
    // SKIP / NEXT
    // =========================================
    case 'skip':
    case 'next':
        $ch = curl_init($spotify_base . '/me/player/next');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? 'Skipped to next track' : 'Failed to skip';
        break;

    // =========================================
    // ADD TO QUEUE
    // =========================================
    case 'add_to_queue':
        if (!$track_uri) {
            echo json_encode(['error' => 'Missing track_uri']);
            exit;
        }
        $url = $spotify_base . '/me/player/queue?uri=' . urlencode($track_uri);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? 'Track added to queue' : 'Failed to add to queue';
        break;

    // =========================================
    // VOLUME UP (+10%)
    // =========================================
    case 'volume_up':
        $ch = curl_init($spotify_base . '/me/player');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $res = curl_exec($ch);
        curl_close($ch);
        
        $player_state = json_decode($res, true);
        $current_volume = $player_state['device']['volume_percent'] ?? 50;
        $new_volume = min(100, $current_volume + 10);
        
        $ch = curl_init($spotify_base . '/me/player/volume?volume_percent=' . $new_volume);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? "Volume increased to {$new_volume}%" : 'Failed to change volume';
        break;

    // =========================================
    // VOLUME DOWN (-10%)
    // =========================================
    case 'volume_down':
        $ch = curl_init($spotify_base . '/me/player');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $res = curl_exec($ch);
        curl_close($ch);
        
        $player_state = json_decode($res, true);
        $current_volume = $player_state['device']['volume_percent'] ?? 50;
        $new_volume = max(0, $current_volume - 10);
        
        $ch = curl_init($spotify_base . '/me/player/volume?volume_percent=' . $new_volume);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? "Volume decreased to {$new_volume}%" : 'Failed to change volume';
        break;

    // =========================================
    // SET EXACT VOLUME
    // =========================================
    case 'set_volume':
        if ($volume === null) {
            echo json_encode(['error' => 'Missing volume parameter']);
            exit;
        }
        $ch = curl_init($spotify_base . '/me/player/volume?volume_percent=' . $volume);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 204 || $http_code === 200);
        $message = $success ? "Volume set to {$volume}%" : 'Failed to set volume';
        break;

    // =========================================
    // CREATE PLAYLIST
    // =========================================
    case 'create_playlist':
        $name = $data['name'] ?? 'Lottie & Mary Mix';
        $desc = $data['description'] ?? 'Curated by Lottie Matthews';
        
        // 1. Get Spotify User ID
        $ch = curl_init($spotify_base . '/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
        $me_res = curl_exec($ch);
        $me_data = json_decode($me_res, true);
        curl_close($ch);
        
        if (!isset($me_data['id'])) {
            echo json_encode(['error' => 'Could not fetch Spotify User ID']);
            exit;
        }
        $spotify_user_id = $me_data['id'];
        
        // 2. Create the Playlist
        $url = $spotify_base . '/users/' . $spotify_user_id . '/playlists';
        $body = json_encode(['name' => $name, 'description' => $desc, 'public' => false]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 201 || $http_code === 200);
        $message = $success ? "Created playlist: $name" : 'Failed to create playlist';
        break;
    
    // =========================================
    // ADD TO PLAYLIST
    // =========================================
    case 'add_to_playlist':
        if (!$track_uri) {
            echo json_encode(['error' => 'Missing track_uri']);
            exit;
        }
        
        $playlist_id = null;
        $target_name = $data['playlist_name'] ?? null;
        
        // If AI specified a playlist name, search for it
        if ($target_name) {
            $ch = curl_init($spotify_base . '/me/playlists?limit=50');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $pl_data = json_decode($res, true);
            if (isset($pl_data['items'])) {
                foreach ($pl_data['items'] as $pl) {
                    if (strtolower($pl['name']) === strtolower($target_name)) {
                        $playlist_id = $pl['id'];
                        break;
                    }
                }
            }
        }
        
        // Fallback to default playlist if no name provided or not found
        if (!$playlist_id) {
            $playlist_id = get_saved_playlist_id($conexao, $username);
        }
        
        if (!$playlist_id) {
            echo json_encode(['error' => 'Could not find playlist']);
            exit;
        }
        
        $url = $spotify_base . '/playlists/' . $playlist_id . '/tracks';
        $body = json_encode(['uris' => [$track_uri]]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 201 || $http_code === 200);
        $message = $success ? 'Track added to playlist' : 'Failed to add to playlist';
        break;

    // =========================================
    // REMOVE FROM PLAYLIST
    // =========================================
    case 'remove_from_playlist':
        if (!$track_uri) {
            echo json_encode(['error' => 'Missing track_uri']);
            exit;
        }
        
        $playlist_id = null;
        $target_name = $data['playlist_name'] ?? null;
        
        if ($target_name) {
            $ch = curl_init($spotify_base . '/me/playlists?limit=50');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
            $res = curl_exec($ch);
            curl_close($ch);
            
            $pl_data = json_decode($res, true);
            if (isset($pl_data['items'])) {
                foreach ($pl_data['items'] as $pl) {
                    if (strtolower($pl['name']) === strtolower($target_name)) {
                        $playlist_id = $pl['id'];
                        break;
                    }
                }
            }
        }
        
        if (!$playlist_id) {
            $playlist_id = get_saved_playlist_id($conexao, $username);
        }
        
        if (!$playlist_id) {
            echo json_encode(['error' => 'Could not find playlist']);
            exit;
        }
        
        $url = SPOTIFY_API_BASE . '/playlists/' . $playlist_id . '/tracks';
        $body = json_encode(['tracks' => [['uri' => $track_uri]]]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token, 'Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $success = ($http_code === 200);
        $message = $success ? 'Track removed from playlist' : 'Failed to remove track';
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown command: ' . $command]);
        exit;
}

echo json_encode([
    'success' => $success,
    'command' => $command,
    'message' => $message,
    'track_uri' => $track_uri,
    'http_code' => $http_code ?? null
]);
?>