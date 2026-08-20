<?php
// =============================================
// SPOTIFY REAL-TIME STREAM (Server-Sent Events)
// =============================================

set_time_limit(60); // Máximo 60 segundos
session_start();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

if (!isset($_SESSION['username'])) {
    echo "event: error\ndata: Not logged in\n\n";
    exit;
}

$username = $_SESSION['username'];
$access_token = $_SESSION[SPOTIFY_TOKEN_SESSION] ?? null;

// Verifica se tem token
if (!$access_token) {
    echo "event: error\ndata: Spotify not connected\n\n";
    exit;
}

session_write_close();

$last_track_id = null;
$last_check = 0;
$max_iterations = 30; // 30 * 3 segundos = 90 segundos máximo
$counter = 0;

while ($counter < $max_iterations) {
    // Só verifica a cada 3 segundos
    if (time() - $last_check < 3) {
        sleep(1);
        continue;
    }
    
    $last_check = time();
    $counter++;
    
    // Busca do cache
    $sql = "SELECT track_id, track_name, artist_name, is_playing, progress_ms, duration_ms 
            FROM spotify_cache 
            WHERE user_id = ? 
            ORDER BY fetched_at DESC LIMIT 1";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $track = $result->fetch_assoc();
        $current_id = $track['track_id'] ?? null;
        
        // Só envia se a música mudou OU a cada 10 checks
        if ($current_id !== $last_track_id || $counter % 10 === 0) {
            $last_track_id = $current_id;
            
            $payload = [
                'track_name' => $track['track_name'],
                'artist_name' => $track['artist_name'],
                'is_playing' => (bool)$track['is_playing'],
                'progress_ms' => $track['progress_ms'],
                'duration_ms' => $track['duration_ms']
            ];
            
            echo "event: track_update\n";
            echo "data: " . json_encode($payload) . "\n\n";
            
            // Flush para enviar imediatamente
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
        }
    }
    
    $stmt->close();
    
    // Verifica se o cliente desconectou
    if (connection_aborted()) {
        error_log("SSE: Client disconnected");
        break;
    }
}

// Envia evento de fim
echo "event: stream_end\n";
echo "data: Stream finished\n\n";
if (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// Fecha a conexão com o banco
if (isset($conexao)) {
    $conexao->close();
}

exit;
?>