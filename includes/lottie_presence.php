<?php
// =============================================
// LOTTIE'S REAL-TIME PRESENCE
// =============================================
// This tracks Lottie's activity status

function update_lottie_presence($conexao, $action, $track_name = null, $artist_name = null) {
    $status_messages = [
        'listening' => "🎧 Listening to music with Mary",
        'reacting' => "💕 Reacting to Mary's song choice",
        'waiting' => "🔄 Waiting for Mary to share something",
        'online' => "💚 Online",
        'studying' => "📚 Studying while listening",
        'daydreaming' => "💭 Daydreaming about Mary",
        'typing' => "✍️ Typing a message..."
    ];
    
    $status = $status_messages[$action] ?? $status_messages['online'];
    
    $sql = "UPDATE lottie_spotify_state 
            SET status_message = ?, 
                current_track = ?,
                current_artist = ?,
                last_activity = NOW()
            WHERE id = 1";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("sss", $status, $track_name, $artist_name);
    $stmt->execute();
    $stmt->close();
    
    return $status;
}

function get_lottie_presence($conexao) {
    $sql = "SELECT * FROM lottie_spotify_state WHERE id = 1";
    $result = $conexao->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

function generate_lottie_activity_message($state) {
    if (!$state) return "💚 Online";
    
    if ($state['is_listening']) {
        return "🎧 Listening: " . $state['current_track'] . " by " . $state['current_artist'];
    }
    
    if ($state['headphones_on']) {
        return "🎧 Wearing headphones (don't disturb)";
    }
    
    return $state['status_message'] ?? "💚 Online";
}
?>