<?php
/**
 * Columbia OS - AI-Driven Music Reaction Engine
 * Feeds environmental data (volume, time) to the AI so she can react autonomously.
 */

set_time_limit(60);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Active session required.']);
    exit();
}

// Cooldown: 3 minutes between autonomous checks
if (isset($_SESSION['last_vibe_check_time']) && (time() - $_SESSION['last_vibe_check_time']) < 180) {
    echo json_encode(['status' => 'cooldown_active']);
    exit();
}

require_once '../includes/conexao.php';
require_once '../includes/spotify_config.php';

$activeUser = $_SESSION['username'];
$ai_prompt_trigger = null;

try {
    // 1. Get current track data
    $ch = curl_init('http://127.0.0.1:8080/Columbia-os/actions/spotify_current_track.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $response = curl_exec($ch);
    curl_close($ch);
    
    $track_data = json_decode($response, true);
    if (!$track_data || empty($track_data['is_playing'])) {
        echo json_encode(['status' => 'not_playing']);
        exit();
    }
    
    // 2. Get Current Volume
    $ch_vol = curl_init(SPOTIFY_API_BASE . '/me/player');
    curl_setopt($ch_vol, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_vol, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $_SESSION[SPOTIFY_TOKEN_SESSION]]);
    $res_vol = curl_exec($ch_vol);
    curl_close($ch_vol);
    
    $player_state = json_decode($res_vol, true);
    $volume = $player_state['device']['volume_percent'] ?? 50;
    
    // ==========================================
    // ENVIRONMENTAL TRIGGERS
    // ==========================================
    
    if ($volume > 85) {
        $ai_prompt_trigger = "🎧 *System Alert: Mary's Spotify volume is currently at {$volume}%. It is dangerously loud. React to this and lower her volume or pause her music.*";
    } elseif (date('H') >= 2 && date('H') < 5) {
        $ai_prompt_trigger = "🎧 *System Alert: It is currently " . date('g:i A') . " and Mary is still awake listening to music. Tell her to go to sleep and pause her music.*";
    }
    
    if (!$ai_prompt_trigger) {
        echo json_encode(['status' => 'environment_normal']);
        exit();
    }
    
    // 3. Send the trigger to the AI Engine
    // We save it as a DM from Mary so the AI reads it, but we mark it as read immediately
    $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES (?, 'lottiematthews', ?, 1)");
    $stmt->bind_param("ss", $activeUser, $ai_prompt_trigger);
    $stmt->execute();
    $stmt->close();
    
    // Wake up the AI
    $session_id = session_id();
$session_name = session_name();
    $ch_ai = curl_init('http://127.0.0.1:8080/Columbia-os/actions/ai_engine.php');
    curl_setopt($ch_ai, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_ai, CURLOPT_POST, true);
    curl_setopt($ch_ai, CURLOPT_POSTFIELDS, http_build_query(['npc' => 'lottiematthews']));
    curl_setopt($ch_ai, CURLOPT_COOKIE, session_name() . '=' . session_id());

    session_write_close();
    
    $ai_reply = curl_exec($ch_ai);
     curl_close($ch_ai);
    
    // 4. Update cooldown
    session_start();
    $_SESSION['last_vibe_check_time'] = time();
    
    echo json_encode([
        'success' => true,
        'rule_triggered' => true,
        'ai_response' => $ai_reply
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
?>