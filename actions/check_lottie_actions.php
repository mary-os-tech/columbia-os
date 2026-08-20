<?php
// =============================================
// LOTTIE'S ACTION SCHEDULER
// =============================================
// This should be called every 30 seconds by a cron job or AJAX

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';

// Get pending actions that are ready
$sql = "SELECT * FROM lottie_actions 
        WHERE status = 'pending' 
        AND scheduled_at <= NOW() 
        ORDER BY scheduled_at ASC 
        LIMIT 5";

$result = $conexao->query($sql);

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => true, 'actions_processed' => 0]);
    exit;
}

$processed = 0;

while ($action = $result->fetch_assoc()) {
    $action_data = json_decode($action['action_data'], true);
    
    switch ($action['action_type']) {
        case 'dm':
            // Send DM from Lottie to Mary
            $username = 'mary'; // Or get from session
            $dm_text = $action_data['dm_text'] ?? '🎧 hey babe, i was just thinking about you ❤️';
            
            $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES ('lottiematthews', ?, ?, 0)");
            $stmt->bind_param("ss", $username, $dm_text);
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'add_to_playlist':
            // Add to Spotify playlist (if connected)
            $track_uri = $action_data['track_uri'] ?? null;
            // We'll implement this later
            break;
            
        case 'play_track':
            // Play a specific track
            $track_uri = $action_data['track_uri'] ?? null;
            // We'll implement this later
            break;
            
        case 'volume_change':
            // Change volume
            $direction = $action_data['direction'] ?? 'up';
            // We'll implement this later
            break;
    }
    
    // Mark as executed
    $sql_update = "UPDATE lottie_actions SET status = 'executed', executed_at = NOW() WHERE id = ?";
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("i", $action['id']);
    $stmt->execute();
    $stmt->close();
    
    $processed++;
}

echo json_encode([
    'success' => true,
    'actions_processed' => $processed,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>