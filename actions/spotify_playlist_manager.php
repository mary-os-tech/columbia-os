<?php
// =============================================
// SPOTIFY PLAYLIST MANAGER
// =============================================
// Handles: create, add, remove, get playlist, and stats

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$username = $_SESSION['username'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Check Spotify connection
if (!isset($_SESSION[SPOTIFY_TOKEN_SESSION])) {
    http_response_code(401);
    echo json_encode(['error' => 'Spotify not connected']);
    exit;
}

// Check token expiry
if (isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION]) {
    http_response_code(401);
    echo json_encode(['error' => 'Token expired']);
    exit;
}

$access_token = $_SESSION[SPOTIFY_TOKEN_SESSION];

// =============================================
// HELPER FUNCTIONS
// =============================================

function get_playlist_id($conexao, $username) {
    $sql = "SELECT setting_value FROM settings 
            WHERE username = ? AND setting_key = 'spotify_playlist_id'";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    return null;
}

function save_playlist_id($conexao, $username, $playlist_id) {
    $sql = "INSERT INTO settings (username, setting_key, setting_value) 
            VALUES (?, 'spotify_playlist_id', ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("sss", $username, $playlist_id, $playlist_id);
    $stmt->execute();
    $stmt->close();
}

function get_playlist_stats($conexao, $username) {
    $stats = ['mary' => 0, 'lottie' => 0];
    
    $sql = "SELECT added_by, COUNT(*) as count 
            FROM playlist_activity 
            WHERE username = ? 
            GROUP BY added_by";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['added_by']] = (int)$row['count'];
    }
    
    return $stats;
}

function send_playlist_notification($conexao, $username, $track_name, $artist_name) {
    $messages = [
        "🎵 i just added '{$track_name}' to our playlist! it's literally perfect 💕",
        "🎶 okay babe, i had to add '{$track_name}' to our mix. it's giving main character energy 😭",
        "🎵 '{$track_name}' by {$artist_name} is going straight to our playlist. you're welcome ❤️",
        "🎶 i'm building our playlist and '{$track_name}' just HAD to be on it 🥺",
        "🎵 okay i'm obsessed with '{$track_name}'... added to our playlist immediately 💕"
    ];
    
    $message = $messages[array_rand($messages)];
    
    $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES ('lottiematthews', ?, ?, 0)");
    $stmt->bind_param("ss", $username, $message);
    $stmt->execute();
    $stmt->close();
    
    return $message;
}

// =============================================
// ACTION: CREATE PLAYLIST
// =============================================
if ($action === 'create') {
    $existing_id = get_playlist_id($conexao, $username);
    if ($existing_id) {
        echo json_encode([
            'success' => true,
            'playlist_id' => $existing_id,
            'message' => 'Playlist already exists'
        ]);
        exit;
    }
    
    $url = SPOTIFY_API_BASE . '/me/playlists';
    $body = json_encode([
        'name' => "Mary & Lottie's Dorm Mix",
        'description' => 'Our shared playlist - curated by Lottie and Mary 🎵❤️',
        'public' => false
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 201 || $http_code === 200) {
        $data = json_decode($response, true);
        $playlist_id = $data['id'];
        save_playlist_id($conexao, $username, $playlist_id);
        
        echo json_encode([
            'success' => true,
            'playlist_id' => $playlist_id,
            'playlist_url' => $data['external_urls']['spotify'] ?? null,
            'message' => 'Playlist created! 🎵'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create playlist: HTTP ' . $http_code,
            'response' => json_decode($response, true)
        ]);
    }
    exit;
}

// =============================================
// ACTION: ADD TRACK
// =============================================
if ($action === 'add') {
    $track_uri = $_POST['track_uri'] ?? $_GET['track_uri'] ?? null;
    $added_by = $_POST['added_by'] ?? 'mary';
    $track_name = $_POST['track_name'] ?? $_GET['track_name'] ?? null;
    $artist_name = $_POST['artist_name'] ?? $_GET['artist_name'] ?? null;
    
    if (!$track_uri) {
        echo json_encode(['error' => 'Missing track_uri']);
        exit;
    }

    // Primeiro, tenta pegar o ID da playlist salva no banco
    $playlist_id = get_playlist_id($conexao, $username);
    
    // Se não existir uma playlist salva, cria uma nova
    if (!$playlist_id) {
        $url = SPOTIFY_API_BASE . '/me/playlists';
        $body = json_encode([
            'name' => "Mary & Lottie's Dorm Mix",
            'description' => 'Our shared playlist - curated by Lottie and Mary 🎵❤️',
            'public' => false
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 201 || $http_code === 200) {
            $data = json_decode($response, true);
            $playlist_id = $data['id'];
            save_playlist_id($conexao, $username, $playlist_id);
        } else {
            echo json_encode(['error' => 'Could not create playlist']);
            exit;
        }
    }
    
    // Agora que temos a playlist, adiciona a música
    $url = SPOTIFY_API_BASE . '/playlists/' . $playlist_id . '/tracks';
    $body = json_encode(['uris' => [$track_uri]]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 201 || $http_code === 200) {
        $sql = "INSERT INTO playlist_activity (username, track_name, artist_name, track_uri, added_by) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sssss", $username, $track_name, $artist_name, $track_uri, $added_by);
        $stmt->execute();
        $stmt->close();
        
        if ($added_by === 'lottie') {
            send_playlist_notification($conexao, $username, $track_name, $artist_name);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Track added to playlist! 🎵',
            'added_by' => $added_by
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add track: HTTP ' . $http_code
        ]);
    }
    exit;
}

// =============================================
// ACTION: GET PLAYLIST
// =============================================
if ($action === 'get') {
    $playlist_id = get_playlist_id($conexao, $username);
    
    // Se o banco não tem o ID, vamos procurar no Spotify antes de desistir
    if (!$playlist_id) {
        $ch_search = curl_init(SPOTIFY_API_BASE . '/me/playlists');
        curl_setopt($ch_search, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_search, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        $res_search = curl_exec($ch_search);
        curl_close($ch_search);
        
        $playlists = json_decode($res_search, true);
        if (isset($playlists['items'])) {
            foreach ($playlists['items'] as $pl) {
                if ($pl['name'] === "Mary & Lottie's Dorm Mix") {
                    $playlist_id = $pl['id'];
                    // Salva o ID que achamos no banco de dados!
                    save_playlist_id($conexao, $username, $playlist_id);
                    break;
                }
            }
        }
    }
    
    // Se ainda não achou nada, então avisa que não tem
    if (!$playlist_id) {
        echo json_encode([
            'success' => false,
            'playlist_exists' => false,
            'message' => 'Playlist not created yet'
        ]);
        exit;
    }
    
    $url = SPOTIFY_API_BASE . '/playlists/' . $playlist_id . '/tracks?limit=50';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        $stats = get_playlist_stats($conexao, $username);
        
        echo json_encode([
            'success' => true,
            'playlist_id' => $playlist_id,
            'tracks' => $data['items'],
            'total' => $data['total'],
            'stats' => $stats
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get playlist: HTTP ' . $http_code
        ]);
    }
    exit;
}
// Adicione antes do DEFAULT RESPONSE:
if ($action === 'remove') {
    $track_uri = $_POST['track_uri'] ?? null;
    
    if (!$track_uri) {
        echo json_encode(['error' => 'Missing track_uri']);
        exit;
    }
    
    $playlist_id = get_playlist_id($conexao, $username);
    
    if (!$playlist_id) {
        echo json_encode(['error' => 'Playlist not found']);
        exit;
    }
    
    $url = SPOTIFY_API_BASE . '/playlists/' . $playlist_id . '/tracks';
    $body = json_encode(['tracks' => [['uri' => $track_uri]]]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        echo json_encode([
            'success' => true,
            'message' => 'Track removed from playlist'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to remove track'
        ]);
    }
    exit;
}

// =============================================
// DEFAULT RESPONSE
// =============================================
echo json_encode([
    'error' => 'Invalid action. Use: create, add, get, stats, remove'
]);
?>