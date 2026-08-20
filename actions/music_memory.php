<?php
// =============================================
// MUSIC MEMORY - Tracks Mary's listening history
// =============================================

function track_played_song($conexao, $username, $track_name, $artist_name, $track_uri, $was_shared = false) {
    $sql = "INSERT INTO music_memory (username, track_name, artist_name, track_uri, was_shared, played_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("ssssi", $username, $track_name, $artist_name, $track_uri, $was_shared);
    $stmt->execute();
    $stmt->close();
    
    // Only check for AI reaction if Mary shared it with Lottie
    if ($was_shared) {
        trigger_lottie_reaction($conexao, $username, $track_name, $artist_name, $track_uri);
    }
}

function get_recent_songs($conexao, $username, $limit = 10) {
    $sql = "SELECT * FROM music_memory 
            WHERE username = ? 
            ORDER BY played_at DESC 
            LIMIT ?";
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("si", $username, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $songs = [];
    while ($row = $result->fetch_assoc()) {
        $songs[] = $row;
    }
    $stmt->close();
    return $songs;
}

function trigger_lottie_reaction($conexao, $username, $track_name, $artist_name, $track_uri) {
    // Instead of reacting instantly, schedule a delayed reaction
    $delay = rand(30, 180); // 30 seconds to 3 minutes
    
    $sql = "INSERT INTO lottie_actions (action_type, action_data, scheduled_at, trigger_track_id) 
            SELECT 'dm', 
                   JSON_OBJECT('dm_text', CONCAT(
                       '🎧 hey babe... i just heard \'', ?, '\' by ', ?, 
                       ' and i can\'t stop thinking about it ', 
                       get_random_reaction()
                   )),
                   DATE_ADD(NOW(), INTERVAL ? SECOND),
                   ?";
    
    // We'll use a subquery for the random reaction
    $reactions = [
        '🥺 you have the best taste ever',
        '😭 it\'s literally so good',
        '💕 this is going to be stuck in my head all day',
        '🎶 you always know exactly what i need to hear',
        '🥹 i\'m literally crying this is beautiful'
    ];
    $random_reaction = $reactions[array_rand($reactions)];
    
    $sql = "INSERT INTO lottie_actions (action_type, action_data, scheduled_at, trigger_track_id) 
            VALUES ('dm', ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?)";
    
    $dm_data = json_encode([
        'dm_text' => "🎧 hey babe... i just heard '{$track_name}' by {$artist_name} and i can't stop thinking about it {$random_reaction}"
    ]);
    
    $stmt = $conexao->prepare($sql);
    $stmt->bind_param("sii", $dm_data, $delay, $track_uri);
    $stmt->execute();
    $stmt->close();
    
    // Also maybe add to playlist (50% chance)
    if (rand(0, 100) > 50) {
        $delay = rand(60, 300); // 1-5 minutes later
        
        $sql = "INSERT INTO lottie_actions (action_type, action_data, scheduled_at, trigger_track_id) 
                VALUES ('add_to_playlist', ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?)";
        $playlist_data = json_encode([
            'track_uri' => $track_uri,
            'message' => "🎵 i added '{$track_name}' to our playlist! it's literally perfect 💕"
        ]);
        
        $stmt = $conexao->prepare($sql);
        $stmt->bind_param("sii", $playlist_data, $delay, $track_uri);
        $stmt->execute();
        $stmt->close();
    }
}
?>