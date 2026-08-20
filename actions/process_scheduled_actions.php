<?php
// =============================================
// PROCESS SCHEDULED ACTIONS
// =============================================
// Run this via cron or AJAX every 30 seconds

include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

session_start();
if (!isset($_SESSION['username'])) {
    exit;
}
$username = $_SESSION['username'];

// Get pending actions that are ready
$sql = "SELECT * FROM lottie_actions 
        WHERE status = 'pending' 
        AND scheduled_at <= NOW() 
        ORDER BY scheduled_at ASC 
        LIMIT 10";

$result = $conexao->query($sql);

if (!$result || $result->num_rows === 0) {
    exit;
}

while ($action = $result->fetch_assoc()) {
    $action_data = json_decode($action['action_data'], true);
    $action_type = $action['action_type'];
    
    // Se for uma Mensagem Direta (DM)
    if ($action_type === 'dm') {
        $dm_text = $action_data['dm_text'] ?? '🎧 hey babe, i was just thinking about you ❤️';
        
        $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES ('lottiematthews', ?, ?, 0)");
        $stmt->bind_param("ss", $username, $dm_text);
        $stmt->execute();
        $stmt->close();
        
        // Update Lottie's presence
        $sql_presence = "UPDATE lottie_spotify_state 
                         SET status_message = '💬 Thinking about you', 
                             last_activity = NOW() 
                         WHERE id = 1";
        $conexao->query($sql_presence);
        
    } else {
        // PARA TODO O RESTO (Spotify Control: play, pause, skip, add_to_playlist, volume, etc)
        // Nós enviamos direto para o arquivo perfeito que já criamos!
        
        $payload = [
            'command' => $action_type
        ];
        
        // Se a ação agendada tiver uma música específica, adicionamos no pacote
        if (isset($action_data['track_uri'])) {
            $payload['track_uri'] = $action_data['track_uri'];
        }
        
        $ch = curl_init('http://127.0.0.1:8080/Columbia-os/actions/spotify_control.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Mark as executed
    $sql_update = "UPDATE lottie_actions SET status = 'executed', executed_at = NOW() WHERE id = ?";
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("i", $action['id']);
    $stmt->execute();
    $stmt->close();
}
?>