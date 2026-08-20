<?php
// =============================================
// TOGGLE LOTTIE'S HEADPHONES
// =============================================

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get current state
$sql = "SELECT headphones_on FROM lottie_spotify_state WHERE id = 1";
$result = $conexao->query($sql);

if ($result && $result->num_rows > 0) {
    $state = $result->fetch_assoc();
    $current = (bool)$state['headphones_on'];
    $new_state = $current ? 0 : 1;
    
    $sql_update = "UPDATE lottie_spotify_state 
                   SET headphones_on = ?,
                       status_message = ?,
                       last_activity = NOW()
                   WHERE id = 1";
    
    $message = $new_state ? '🎧 Wearing headphones (don\'t disturb)' : '💚 Online - Listening with you';
    $stmt = $conexao->prepare($sql_update);
    $stmt->bind_param("is", $new_state, $message);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'headphones_on' => (bool)$new_state,
        'message' => $message
    ]);
} else {
    echo json_encode(['error' => 'Lottie state not found']);
}
?>