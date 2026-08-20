<?php
// =============================================
// LOTTIE'S MUSICAL PERSONALITY
// =============================================

function lottie_evaluate_song($track_name, $artist_name) {
    $loves = ['the neighbourhood', 'phoebe bridgers', 'mitski', 'bon iver', 'beach house'];
    $hates = ['country', 'nickelback', 'florida georgia line', 'luke bryan'];
    
    $track_lower = strtolower($track_name);
    $artist_lower = strtolower($artist_name);
    
    foreach ($loves as $artist) {
        if (strpos($artist_lower, $artist) !== false) {
            return ['verdict' => 'love', 'reason' => "omg i LOVE {$artist_name}!!"];
        }
    }
    
    foreach ($hates as $artist) {
        if (strpos($artist_lower, $artist) !== false) {
            return ['verdict' => 'hate', 'reason' => "babe... {$artist_name}? really? 💀"];
        }
    }
    
    return ['verdict' => 'neutral', 'reason' => "hmm i don't know this one... 🤔"];
}

function schedule_lottie_action($type, $data, $delay) {
    // Placeholder - will be implemented later
    return true;
}
?>