<?php
// =============================================
// LOTTIE'S PROFILE CARD
// =============================================
// Displays Lottie's avatar, status, and music activity

function render_lottie_profile_card($conexao) {
    // Get Lottie's profile
    $sql_lottie = "SELECT * FROM perfis WHERE username = 'lottiematthews' LIMIT 1";
    $result = $conexao->query($sql_lottie);
    $lottie = $result->fetch_assoc();
    
    if (!$lottie) {
        return '<div style="color:#666;font-size:13px;">Lottie not found</div>';
    }
    
    // Get Lottie's state from lottie_spotify_state
    $sql_state = "SELECT * FROM lottie_spotify_state WHERE id = 1";
    $result_state = $conexao->query($sql_state);
    $state = $result_state->fetch_assoc();
    
    if (!$state) {
        // Initialize if not exists
        $conexao->query("INSERT INTO lottie_spotify_state (id, is_listening, songs_shared, status_message) VALUES (1, 0, 0, '💚 Online')");
        $state = ['is_listening' => 0, 'current_track' => null, 'current_artist' => null, 'status_message' => '💚 Online', 'headphones_on' => 0, 'songs_shared' => 0];
    }
    
    $avatar = $lottie['avatar'] ?? 'https://i.pinimg.com/736x/9d/3c/f4/9d3cf475a07b9958733b52e36f811e9a.jpg';
    $avatar_html = (strpos($avatar, 'http') === 0) 
        ? "<img src='" . htmlspecialchars($avatar) . "' style='width:100%;height:100%;object-fit:cover;'>" 
        : htmlspecialchars($avatar);
    
    $is_listening = (bool)($state['is_listening'] ?? 0);
    $headphones_on = (bool)($state['headphones_on'] ?? 0);
    $songs_shared = (int)($state['songs_shared'] ?? 0);
    $status_message = $state['status_message'] ?? '💚 Online';
    $current_track = $state['current_track'] ?? null;
    $current_artist = $state['current_artist'] ?? null;
    
    $status_dot_color = $is_listening ? '#1DB954' : '#888';
    $status_text = $headphones_on ? '🎧 Do Not Disturb' : ($is_listening ? '🎧 Listening' : '💚 Online');
    
    // Build activity text
    if ($headphones_on) {
        $activity_text = "Wearing headphones (don't disturb)";
    } elseif ($is_listening && $current_track) {
        $activity_text = "Listening to \"" . htmlspecialchars($current_track) . "\"";
        $activity_sub = "by " . htmlspecialchars($current_artist);
    } elseif ($status_message) {
        $activity_text = $status_message;
        $activity_sub = '';
    } else {
        $activity_text = '💚 Online';
        $activity_sub = '';
    }
    ?>
    
    <div class="lottie-profile-card" style="background: #1a1a1a; border-radius: 16px; padding: 16px; border: 1px solid #2a2a2a; margin-bottom: 16px; transition: all 0.3s ease;">
        <!-- Header -->
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; border: 2px solid <?php echo $status_dot_color; ?>; background: #2a2a2a; flex-shrink: 0;">
                <?php echo $avatar_html; ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 700; font-size: 14px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($lottie['nome'] ?? 'Lottie Matthews'); ?>
                </div>
                <div style="font-size: 11px; color: #888;">@<?php echo htmlspecialchars($lottie['username']); ?></div>
                <div style="display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                    <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: <?php echo $status_dot_color; ?>; <?php echo $is_listening ? 'animation: pulse 1.5s infinite;' : ''; ?>"></span>
                    <span style="font-size: 10px; color: <?php echo $is_listening ? '#1DB954' : '#888'; ?>;"><?php echo $status_text; ?></span>
                </div>
            </div>
        </div>
        
      <!-- Activity -->
<div style="background: #0a0a0a; border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; min-height: 36px; <?php echo $headphones_on ? 'border-left: 3px solid #ff4444;' : 'border-left: 3px solid #1DB954;'; ?>">
    <?php if ($headphones_on): ?>
        <div style="font-size: 12px; color: #ff6666;">🎧 DO NOT DISTURB</div>
        <div style="font-size: 10px; color: #888; margin-top: 2px;">Lottie has headphones on. She needs space right now.</div>
    <?php else: ?>
        <div style="font-size: 12px; color: #ddd;"><?php echo $activity_text; ?></div>
        <?php if ($activity_sub): ?>
            <div style="font-size: 10px; color: #666; margin-top: 1px;"><?php echo $activity_sub; ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
        
        <!-- Stats -->
        <div style="display: flex; gap: 12px; padding: 6px 0; border-top: 1px solid #2a2a2a; border-bottom: 1px solid #2a2a2a; margin-bottom: 10px;">
            <div style="text-align: center; flex: 1;">
                <div style="font-weight: 700; font-size: 16px; color: #1DB954;"><?php echo $songs_shared; ?></div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Shared</div>
            </div>
            <div style="text-align: center; flex: 1;">
                <div style="font-weight: 700; font-size: 16px; color: #fff;">❤️</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Together</div>
            </div>
            <div style="text-align: center; flex: 1;">
                <div style="font-weight: 700; font-size: 16px; color: #ffd700;">🎵</div>
                <div style="font-size: 9px; color: #666; text-transform: uppercase; letter-spacing: 0.5px;">Playlist</div>
            </div>
        </div>
        
        <!-- Actions -->
        <div style="display: flex; gap: 6px;">
            <a href="/Columbia-os/pages/music_player.php" style="flex: 1; text-align: center; background: #1DB954; color: #000; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 11px; transition: background 0.2s;" onmouseover="this.style.background='#1ed760'" onmouseout="this.style.background='#1DB954'">
                🎧 Listen
            </a>
            <a href="/Columbia-os/pages/dm.php?user=lottiematthews" style="flex: 1; text-align: center; background: #2a2a2a; color: #fff; padding: 6px 10px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 11px; transition: background 0.2s;" onmouseover="this.style.background='#333'" onmouseout="this.style.background='#2a2a2a'">
                💬 DM
            </a>
        </div>
    </div>
    
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .lottie-profile-card {
            transition: all 0.3s ease;
        }
        .lottie-profile-card:hover {
            border-color: #1DB954;
        }
    </style>
    <?php
}
?>