<?php
session_start();
include_once '../includes/conexao.php';
include_once '../includes/spotify_config.php';

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: /Columbia-os/login.php');
    exit;
}

$username = $_SESSION['username'];
$is_spotify_connected = isset($_SESSION[SPOTIFY_TOKEN_SESSION]);
$is_expired = isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION];
$connected = $is_spotify_connected && !$is_expired;

// Get user profile for the header
$sql_user = "SELECT nome, username, avatar FROM perfis WHERE username = '$username' LIMIT 1";
$result_user = $conexao->query($sql_user);
$user_profile = $result_user->fetch_assoc() ?: ['nome' => $username, 'username' => $username, 'avatar' => '👤'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Columbia Music - Player</title>
    <link rel="stylesheet" href="/Columbia-os/assets/css/style.css">
    <style>
        /* =============================================
           MINIMALIST SPOTIFY-STYLE MUSIC PLAYER
           ============================================= */
        
        /* Reset & Base */
        * { box-sizing: border-box; }
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .music-player {
            max-width: 480px;
            width: 100%;
            background: #121212;
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #2a2a2a;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }
        
        /* ===== HEADER ===== */
        .player-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .player-header h2 {
            margin: 0;
            color: #1DB954;
            font-size: 18px;
            font-weight: 700;
        }
        .player-header .profile-link {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #888;
            font-size: 12px;
            transition: color 0.2s;
        }
        .player-header .profile-link:hover {
            color: #fff;
        }
        .player-header .profile-link .avatar-mini {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #2a2a2a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            overflow: hidden;
        }
        .player-header .profile-link .avatar-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .spotify-status {
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 20px;
            background: #1a1a1a;
            font-weight: 500;
        }
        .spotify-status.connected { color: #1DB954; }
        .spotify-status.disconnected { color: #ff4444; }
        
        /* ===== LOTTIE'S PRESENCE ===== */
        .lottie-presence {
            background: rgba(29, 185, 84, 0.05);
            border: 1px solid rgba(29, 185, 84, 0.15);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 20px;
            display: none;
            transition: all 0.3s ease;
        }
        .lottie-presence .presence-inner {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lottie-presence img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #1DB954;
            object-fit: cover;
        }
        .lottie-presence .presence-info {
            flex: 1;
        }
        .lottie-presence .presence-name {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-weight: 500;
            font-size: 13px;
        }
        .lottie-presence .presence-name .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #1DB954;
            animation: pulse 1.5s infinite;
        }
        .lottie-presence .presence-activity {
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            margin-top: 1px;
        }
        .lottie-presence .presence-headphones {
            font-size: 16px;
            opacity: 0.7;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        
        /* ===== TRACK DISPLAY ===== */
        .track-display {
            text-align: center;
            padding: 10px 0;
        }
        .album-art {
            width: 200px;
            height: 200px;
            border-radius: 12px;
            margin: 0 auto 16px;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.5);
        }
        .album-art img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .track-name {
            font-size: 18px;
            font-weight: 700;
            margin: 8px 0 4px;
            color: #fff;
        }
        .artist-name {
            color: #888;
            font-size: 14px;
        }
        
        /* ===== PROGRESS BAR ===== */
        .progress-container {
            margin: 16px 0 8px;
        }
        .progress-bar {
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
            cursor: pointer;
            transition: height 0.2s;
        }
        .progress-bar:hover {
            height: 6px;
        }
        .progress-fill {
            height: 100%;
            background: #1DB954;
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 2px;
        }
        .progress-time {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.3);
            margin-top: 4px;
            padding: 0 2px;
            letter-spacing: 0.3px;
        }
        
        /* ===== VOLUME ===== */
        .volume-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0 0;
            padding: 0 8px;
        }
        .volume-icon {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            min-width: 18px;
            cursor: pointer;
            transition: color 0.2s;
        }
        .volume-icon:hover {
            color: #fff;
        }
        .volume-bar {
            flex: 1;
            height: 3px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            transition: height 0.2s;
        }
        .volume-bar:hover {
            height: 5px;
        }
        .volume-fill {
            height: 100%;
            background: #1DB954;
            width: 50%;
            transition: width 0.15s ease;
            border-radius: 2px;
        }
        .volume-display {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.3);
            min-width: 36px;
            text-align: right;
            font-weight: 400;
            letter-spacing: 0.3px;
        }
        
        /* ===== CONTROLS ===== */
        .controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 20px 0 12px;
        }
        .control-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.15s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .control-btn:hover {
            color: #fff;
            transform: scale(1.08);
            background: rgba(255, 255, 255, 0.05);
        }
        .control-btn:active {
            transform: scale(0.92);
        }
        #playBtn {
            width: 48px;
            height: 48px;
            font-size: 22px;
            background: #1DB954;
            color: #000;
            border-radius: 50%;
            box-shadow: 0 4px 20px rgba(29, 185, 84, 0.3);
        }
        #playBtn:hover {
            background: #1ed760;
            transform: scale(1.06);
            box-shadow: 0 6px 30px rgba(29, 185, 84, 0.4);
        }
        #playBtn:active {
            transform: scale(0.95);
        }
        #playBtn.paused {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            box-shadow: none;
        }
        #playBtn.paused:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        #prevBtn, #nextBtn {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            width: 32px;
            height: 32px;
        }
        #prevBtn:hover, #nextBtn:hover {
            color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.03);
        }
        
        /* ===== STATUS ===== */
        .status-text {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
            margin-top: 4px;
            min-height: 18px;
        }
        
        /* ===== SHARE BUTTON ===== */
        .share-section {
            margin-top: 16px;
            text-align: center;
        }
        .btn-share {
            background: transparent;
            color: #1DB954;
            border: 1px solid rgba(29, 185, 84, 0.3);
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
            letter-spacing: 0.3px;
        }
        .btn-share:hover {
            background: rgba(29, 185, 84, 0.1);
            border-color: #1DB954;
            transform: scale(1.02);
        }
        .btn-share:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none !important;
        }
        .share-feedback {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 6px;
            min-height: 16px;
        }
        
        /* ===== CONNECT SECTION ===== */
        .connect-section {
            text-align: center;
            padding: 40px 0;
        }
        .connect-section p {
            color: #888;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .btn-connect {
            background: #1DB954;
            color: #fff;
            border: none;
            padding: 12px 32px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-connect:hover {
            background: #1ed760;
            transform: scale(1.02);
        }
        
        /* ===== SVG ICONS ===== */
        .control-btn svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .music-player { padding: 20px; }
            .album-art { width: 160px; height: 160px; }
            .track-name { font-size: 16px; }
        }
    </style>
</head>
<body>
<div class="music-player">
    <!-- ===== HEADER ===== -->
    <div class="player-header">
        <h2>🎵 Columbia Music</h2>
        <a href="/Columbia-os/pages/perfil.php" class="profile-link">
            <div class="avatar-mini">
                <?php 
                $avatar = $user_profile['avatar'] ?? '👤';
                if (strpos($avatar, 'http') === 0) {
                    echo '<img src="' . htmlspecialchars($avatar) . '" alt="Profile">';
                } else {
                    echo htmlspecialchars($avatar);
                }
                ?>
            </div>
            <span class="spotify-status <?php echo $connected ? 'connected' : 'disconnected'; ?>">
                <?php echo $connected ? '●' : '○'; ?>
            </span>
        </a>
    </div>
    
    <?php if (!$connected): ?>
        <!-- ===== NOT CONNECTED ===== -->
        <div class="connect-section">
            <p>Connect your Spotify to let Lottie react to your music</p>
            <a href="/Columbia-os/includes/spotify_auth.php?action=login" class="btn-connect">
                🔗 Connect Spotify
            </a>
        </div>
    <?php else: ?>
        <!-- ===== LOTTIE'S PRESENCE ===== -->
        <div class="lottie-presence" id="lottiePresence">
            <div class="presence-inner">
                <img src="https://i.pinimg.com/736x/9d/3c/f4/9d3cf475a07b9958733b52e36f811e9a.jpg" alt="Lottie">
                <div class="presence-info">
                    <div class="presence-name">
                        Lottie Matthews
                        <span class="status-dot"></span>
                    </div>
                    <div class="presence-activity" id="lottieActivity">💚 Online</div>
                </div>
                <span class="presence-headphones" id="headphonesIcon">🎧</span>
            </div>
        </div>
        
        <!-- ===== TRACK DISPLAY ===== -->
        <div class="track-display">
            <div class="album-art" id="albumArt">
                <span id="albumArtPlaceholder">🎵</span>
            </div>
            <div class="track-name" id="trackName">Not playing</div>
            <div class="artist-name" id="artistName">—</div>
            
            <!-- Progress -->
            <div class="progress-container">
                <div class="progress-bar" id="progressBar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-time">
                    <span id="currentTime">0:00</span>
                    <span id="totalTime">0:00</span>
                </div>
            </div>
            
            <!-- Volume -->
            <div class="volume-container">
                <span class="volume-icon" id="volIcon">🔊</span>
                <div class="volume-bar" id="volumeBar">
                    <div class="volume-fill" id="volumeFill"></div>
                </div>
                <span class="volume-display" id="volumeDisplay">50%</span>
            </div>
            
          <!-- Controls -->
<div class="controls">
    <button class="control-btn" id="prevBtn" title="Previous">⏮</button>
    <button class="control-btn" id="playBtn" title="Play/Pause">▶️</button>
    <button class="control-btn" id="nextBtn" title="Next">⏭</button>
</div>
            
            <!-- Status -->
            <div class="status-text" id="statusText">Loading...</div>
            
            <!-- Share -->
            <div class="share-section">
                <button class="btn-share" id="shareEarbudBtn">🎧 Share Earbud with Lottie</button>
                <div class="share-feedback" id="shareFeedback"></div>
            </div>
        </div>
    <?php endif; ?>
</div>
<!-- ============================================= -->
<!-- PLAYLIST SECTION -->
<!-- ============================================= -->
<div class="playlist-section" style="margin-top: 20px; border-top: 1px solid #2a2a2a; padding-top: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 16px;">🎵</span>
            <span style="font-weight: 600; font-size: 14px; color: #fff;">Mary & Lottie's Dorm Mix</span>
        </div>
        <div style="display: flex; align-items: center; gap: 12px; font-size: 11px; color: #666;">
            <span id="playlist-stats">🎧 Lottie: 0 · 👤 Mary: 0</span>
            <button id="refresh-playlist-btn" style="background: none; border: none; color: #1DB954; cursor: pointer; font-size: 14px;" title="Refresh Playlist">🔄</button>
        </div>
    </div>
    
    <div id="playlist-tracks" style="max-height: 200px; overflow-y: auto; background: #0a0a0a; border-radius: 8px; padding: 8px;">
        <div style="text-align: center; color: #666; font-size: 13px; padding: 20px 0;">
            No songs yet. Share some music with Lottie! 🎧
        </div>
    </div>
    
    <div id="playlist-notification" style="display: none; background: rgba(29, 185, 84, 0.1); border: 1px solid #1DB954; border-radius: 8px; padding: 8px 12px; margin-top: 8px; font-size: 12px; color: #1DB954;">
        🎵 Lottie added a song to the playlist!
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// =============================================
// GLOBAL FUNCTIONS (BEFORE $(document).ready)
// =============================================

function formatTime(ms) {
    if (!ms || ms === 0) return '0:00';
    var seconds = Math.floor(ms / 1000);
    var minutes = Math.floor(seconds / 60);
    seconds = seconds % 60;
    return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
}

function updatePlayButton(playing) {
    if (playing) {
        $('#playBtn').text('⏸️').removeClass('paused');
    } else {
        $('#playBtn').text('▶️').addClass('paused');
    }
}

// =============================================
// FETCH CURRENT TRACK - GLOBAL
// =============================================
function fetchCurrentTrack() {
    $.ajax({
        url: '/Columbia-os/actions/spotify_current_track.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                $('#statusText').text('⚠️ ' + data.error);
                return;
            }
            
            var isPlaying = data.is_playing || false;
            updatePlayButton(isPlaying);
            
            if (isPlaying) {
                $('#trackName').text(data.track_name || 'Unknown Track');
                $('#artistName').text(data.artist_name || 'Unknown Artist');
                $('#statusText').text('🎵 Currently playing');
                
                if (data.album_art_url) {
                    $('#albumArt').html('<img src="' + data.album_art_url + '" alt="Album Art">');
                } else {
                    $('#albumArt').html('<span>🎵</span>');
                }
                
                if (data.duration_ms > 0) {
                    var progress = (data.progress_ms / data.duration_ms) * 100;
                    $('#progressFill').css('width', progress + '%');
                    $('#currentTime').text(formatTime(data.progress_ms));
                    $('#totalTime').text(formatTime(data.duration_ms));
                }
            } else {
                var currentTrackName = $('#trackName').text();
                if (currentTrackName === 'Not playing' || currentTrackName === 'Nothing playing' || !currentTrackName || currentTrackName === '—') {
                    $('#trackName').text('Nothing playing');
                    $('#artistName').text('—');
                    $('#statusText').text('⏸️ No playback');
                } else {
                    $('#statusText').text('⏸️ Paused');
                }
            }
        },
        error: function() {
            $('#statusText').text('⚠️ Failed to fetch track');
        }
    });
}

// =============================================
// PLAYBACK CONTROLS - GLOBAL
// =============================================
function controlPlayback(command, trackUri) {
    var data = { command: command };
    if (trackUri) data.track_uri = trackUri;
    
    $.ajax({
        url: '/Columbia-os/actions/spotify_control.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#statusText').text('✅ ' + response.message);
                setTimeout(fetchCurrentTrack, 500);
                setTimeout(getCurrentVolume, 500);
            } else {
                $('#statusText').text('⚠️ ' + (response.message || 'Failed'));
            }
        },
        error: function() {
            $('#statusText').text('⚠️ Failed to control playback');
        }
    });
}

// =============================================
// VOLUME CONTROLS - GLOBAL
// =============================================
function getCurrentVolume() {
    $.ajax({
        url: '/Columbia-os/actions/spotify_player_state.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success && data.volume !== undefined) {
                var vol = data.volume;
                $('#volumeDisplay').text(vol + '%');
                $('#volumeFill').css('width', vol + '%');
                updateVolumeIcon(vol);
            }
        }
    });
}

function updateVolumeIcon(volume) {
    if (volume === 0) $('#volIcon').text('🔇');
    else if (volume < 30) $('#volIcon').text('🔈');
    else if (volume < 70) $('#volIcon').text('🔉');
    else $('#volIcon').text('🔊');
}

function setVolume(volume) {
    volume = Math.max(0, Math.min(100, volume));
    $.ajax({
        url: '/Columbia-os/actions/spotify_control.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ command: 'set_volume', volume: volume }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#volumeDisplay').text(volume + '%');
                $('#volumeFill').css('width', volume + '%');
                updateVolumeIcon(volume);
                $('#statusText').text('🔊 Volume: ' + volume + '%');
            }
        }
    });
}

// =============================================
// LOTTIE'S PRESENCE - GLOBAL
// =============================================
function updateLottiePresence() {
    $.ajax({
        url: '/Columbia-os/actions/get_lottie_presence.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#lottiePresence').slideDown();
                if (data.is_listening) {
                    $('#lottieActivity').text('🎧 Listening to "' + data.current_track + '" by ' + data.current_artist);
                } else if (data.headphones_on) {
                    $('#lottieActivity').text('🎧 Wearing headphones (don\'t disturb)');
                } else {
                    $('#lottieActivity').text(data.status_message || '💚 Online');
                }
            }
        }
    });
}

// =============================================
// REAL-TIME STREAM (Server-Sent Events)
// =============================================
function initRealtimeStream() {
    try {
        if (typeof(EventSource) !== "undefined") {
            var source = new EventSource('/Columbia-os/actions/spotify_stream.php');
            
            source.addEventListener('track_update', function(e) {
                try {
                    var data = JSON.parse(e.data);
                    updatePlayButton(data.is_playing);
                    
                    if (data.is_playing) {
                        $('#trackName').text(data.track_name || 'Unknown Track');
                        $('#artistName').text(data.artist_name || 'Unknown Artist');
                        $('#statusText').text('🎵 Currently playing');
                        
                        if (data.duration_ms > 0) {
                            var progress = (data.progress_ms / data.duration_ms) * 100;
                            $('#progressFill').css('width', progress + '%');
                            $('#currentTime').text(formatTime(data.progress_ms));
                            $('#totalTime').text(formatTime(data.duration_ms));
                        }
                    } else {
                        $('#statusText').text('⏸️ Paused or no playback');
                    }
                } catch (e) {
                    console.log('Error parsing stream data:', e);
                }
            });
            
            source.onerror = function(e) {
                console.log('Realtime stream disconnected, using polling fallback');
                source.close();
                // Use existing function - no need to redefine
            };
        } else {
            console.log('SSE not supported, using polling fallback');
        }
    } catch (e) {
        console.log('SSE error:', e);
    }
}

// =============================================
// DOCUMENT READY - BINDINGS ONLY
// =============================================
$(document).ready(function() {
    <?php if ($connected): ?>
    
    // =============================================
    // STATE
    // =============================================
    var isPlaying = false;
    var currentVolume = 50;
    
    // =============================================
    // PLAYBACK BUTTON BINDINGS
    // =============================================
    $('#playBtn').click(function() {
        var isPaused = $('#playBtn').hasClass('paused');
        controlPlayback(isPaused ? 'play' : 'pause');
    });
    
    $('#prevBtn').click(function() { controlPlayback('skip'); });
    $('#nextBtn').click(function() { controlPlayback('skip'); });
    
    // =============================================
    // VOLUME BAR CLICK
    // =============================================
    $('#volumeBar').click(function(e) {
        var rect = this.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var width = rect.width;
        var percentage = Math.round((x / width) * 100);
        setVolume(percentage);
    });
    
    // =============================================
    // SHARE EARBUD
    // =============================================
    $('#shareEarbudBtn').click(function() {
        var $btn = $(this);
        var $feedback = $('#shareFeedback');
        
        $btn.prop('disabled', true).text('🎧 Sending...');
        $feedback.text('⏳ Sending to Lottie...');
        
        $.ajax({
            url: '/Columbia-os/actions/spotify_current_track.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    $feedback.text('❌ ' + data.error);
                    $btn.prop('disabled', false).text('🎧 Share Earbud with Lottie');
                    return;
                }
                
                var shareData = {
                    track_name: data.track_name || '',
                    artist_name: data.artist_name || '',
                    track_uri: data.track_id ? 'spotify:track:' + data.track_id : null,
                    album_art: data.album_art_url || null,
                    is_playing: data.is_playing || false,
                    duration_ms: data.duration_ms || 0,
                    progress_ms: data.progress_ms || 0
                };
                
                $.ajax({
                    url: '/Columbia-os/actions/share_earbud.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(shareData),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $feedback.html('✅ ' + response.message);
                            $('#lottiePresence').slideDown();
                            $('#lottieActivity').text('💕 Reacting to your music!');
                            setTimeout(updateLottiePresence, 3000);
                        } else {
                            $feedback.text('❌ ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function() {
                        $feedback.text('⚠️ Lottie didn\'t get it. Try again?');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('🎧 Share Earbud with Lottie');
                    }
                });
            },
            error: function() {
                $feedback.text('⚠️ Couldn\'t get current track. Is Spotify playing?');
                $btn.prop('disabled', false).text('🎧 Share Earbud with Lottie');
            }
        });
    });
    
    // =============================================
    // PLAYLIST FUNCTIONS
    // =============================================
    function loadPlaylist() {
        $.ajax({
            url: '/Columbia-os/actions/spotify_playlist_manager.php?action=get',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.tracks) {
                    renderPlaylist(data.tracks, data.stats, data.activity);
                } else if (data.playlist_exists === false) {
                    createPlaylist();
                } else {
                    $('#playlist-tracks').html('<div style="text-align: center; color: #666; font-size: 13px; padding: 20px 0;">No songs yet. Share some music with Lottie! 🎧</div>');
                    if (data.stats) {
                        $('#playlist-stats').text('🎧 Lottie: ' + (data.stats.lottie || 0) + ' · 👤 Mary: ' + (data.stats.mary || 0));
                    }
                }
            },
            error: function() {
                $('#playlist-tracks').html('<div style="text-align: center; color: #ff4444; font-size: 13px; padding: 20px 0;">⚠️ Could not load playlist</div>');
            }
        });
    }

    function renderPlaylist(tracks, stats, activity) {
        var $container = $('#playlist-tracks');
        $container.empty();
        
        if (!tracks || tracks.length === 0) {
            $container.html('<div style="text-align: center; color: #666; font-size: 13px; padding: 20px 0;">No songs yet. Share some music with Lottie! 🎧</div>');
            return;
        }
        
        var displayTracks = tracks.slice(0, 15);
        var html = '';
        
        displayTracks.forEach(function(item) {
            var track = item.track;
            if (!track) return;
            
            var isLottie = false;
            if (activity && typeof activity === 'object' && activity.length > 0) {
                for (var i = 0; i < activity.length; i++) {
                    if (activity[i].track_name === track.name && activity[i].artist_name === track.artists[0].name) {
                        isLottie = (activity[i].added_by === 'lottie');
                        break;
                    }
                }
            }
            
            var albumArt = (track.album && track.album.images && track.album.images.length > 0) 
                ? track.album.images[0].url 
                : '';
            
            var trackName = String(track.name).replace(/'/g, "\\'").replace(/"/g, '&quot;');
            var artistName = String(track.artists[0].name).replace(/'/g, "\\'").replace(/"/g, '&quot;');
            var trackUri = track.uri || 'spotify:track:' + track.id;
            
            html += '<div style="display: flex; align-items: center; gap: 8px; padding: 6px 8px; border-bottom: 1px solid #1a1a1a;';
            if (isLottie) {
                html += ' border-left: 2px solid #1DB954;';
            }
            html += '">';
            html += '<img src="' + albumArt + '" style="width: 28px; height: 28px; border-radius: 4px; object-fit: cover;" onerror="this.style.display=\'none\'">';
            html += '<div style="flex: 1; min-width: 0;">';
            html += '<div style="font-size: 12px; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + trackName + '</div>';
            html += '<div style="font-size: 10px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + artistName + '</div>';
            html += '</div>';
            html += '<button class="playlist-like-btn" data-track="' + trackUri + '" data-name="' + trackName + '" data-artist="' + artistName + '" style="background: none; border: none; color: #888; cursor: pointer; font-size: 16px; padding: 2px 6px; transition: all 0.2s;">➕</button>';
            html += '<span style="font-size: 9px; color: ' + (isLottie ? '#1DB954' : '#888') + ';">' + (isLottie ? '🎧' : '👤') + '</span>';
            html += '</div>';
        });
        
        if (tracks.length > 15) {
            html += '<div style="text-align: center; color: #666; font-size: 11px; padding: 8px;">+' + (tracks.length - 15) + ' more songs</div>';
        }
        
        $container.html(html);
        
        if (stats) {
            $('#playlist-stats').text('🎧 Lottie: ' + (stats.lottie || 0) + ' · 👤 Mary: ' + (stats.mary || 0));
        }
        
        // Bind like button events
        $('.playlist-like-btn').click(function() {
            var $btn = $(this);
            var trackUri = $btn.data('track');
            var trackName = $btn.data('name');
            var artistName = $btn.data('artist');
            
            if ($btn.text() === '➕') {
                $btn.text('❤️').css('color', '#1DB954');
                addToLikedSongs(trackUri, trackName, artistName);
            } else {
                $btn.text('➕').css('color', '#888');
                removeFromLikedSongs(trackUri);
            }
        });
    }

    function addToLikedSongs(trackUri, trackName, artistName) {
        $.ajax({
            url: '/Columbia-os/actions/spotify_playlist_manager.php',
            method: 'POST',
            data: {
                action: 'add',
                track_uri: trackUri,
                track_name: trackName,
                artist_name: artistName,
                added_by: 'mary'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#shareFeedback').html('✅ Added "' + trackName + '" to the playlist!');
                    setTimeout(function() {
                        $('#shareFeedback').text('');
                    }, 3000);
                    setTimeout(loadPlaylist, 1000);
                }
            }
        });
    }

    function removeFromLikedSongs(trackUri) {
        $.ajax({
            url: '/Columbia-os/actions/spotify_playlist_manager.php',
            method: 'POST',
            data: {
                action: 'remove',
                track_uri: trackUri
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#shareFeedback').html('❌ Removed from playlist');
                    setTimeout(function() {
                        $('#shareFeedback').text('');
                    }, 3000);
                    setTimeout(loadPlaylist, 1000);
                }
            }
        });
    }

    function createPlaylist() {
        $.ajax({
            url: '/Columbia-os/actions/spotify_playlist_manager.php?action=create',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    console.log('Playlist created!');
                    loadPlaylist();
                }
            }
        });
    }
    
    // =============================================
    // CONTINUOUS SHARE MODE
    // =============================================
    var continuousShareEnabled = false;

    function toggleContinuousShare() {
        $.ajax({
            url: '/Columbia-os/actions/toggle_continuous_share.php',
            method: 'POST',
            data: { action: 'toggle' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    continuousShareEnabled = response.enabled;
                    $('#shareEarbudBtn').text(
                        continuousShareEnabled ? '🎧 Auto-Share ON' : '🎧 Share Earbud with Lottie'
                    );
                    $('#shareEarbudBtn').css('background', 
                        continuousShareEnabled ? 'rgba(29, 185, 84, 0.2)' : 'transparent'
                    );
                    $('#shareFeedback').text(response.message);
                    $('#shareFeedback').css('color', continuousShareEnabled ? '#1DB954' : '#666');
                }
            }
        });
    }

    function checkContinuousShareStatus() {
        $.ajax({
            url: '/Columbia-os/actions/toggle_continuous_share.php?action=status',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.enabled) {
                    continuousShareEnabled = true;
                    $('#shareEarbudBtn').text('🎧 Auto-Share ON');
                    $('#shareEarbudBtn').css('background', 'rgba(29, 185, 84, 0.2)');
                    $('#shareFeedback').text('🎵 Lottie will react to every new song!');
                    $('#shareFeedback').css('color', '#1DB954');
                }
            }
        });
    }

    // Check status on load
    checkContinuousShareStatus();

    // Update share button to toggle continuous mode
    $('#shareEarbudBtn').click(function() {
        if (continuousShareEnabled) {
            toggleContinuousShare();
        } else {
            // If not in continuous mode, do a one-time share
            var $btn = $(this);
            var $feedback = $('#shareFeedback');
            
            $btn.prop('disabled', true).text('🎧 Sending...');
            $feedback.text('⏳ Sending to Lottie...');
            
            $.ajax({
                url: '/Columbia-os/actions/spotify_current_track.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        $feedback.text('❌ ' + data.error);
                        $btn.prop('disabled', false).text('🎧 Share Earbud with Lottie');
                        return;
                    }
                    
                    var shareData = {
                        track_name: data.track_name || '',
                        artist_name: data.artist_name || '',
                        track_uri: data.track_id ? 'spotify:track:' + data.track_id : null,
                        album_art: data.album_art_url || null,
                        is_playing: data.is_playing || false,
                        duration_ms: data.duration_ms || 0,
                        progress_ms: data.progress_ms || 0
                    };
                    
                    $.ajax({
                        url: '/Columbia-os/actions/share_earbud.php',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(shareData),
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $feedback.html('✅ ' + response.message);
                                $('#lottiePresence').slideDown();
                                $('#lottieActivity').text('💕 Reacting to your music!');
                            } else {
                                $feedback.text('❌ ' + (response.error || 'Unknown error'));
                            }
                        },
                        complete: function() {
                            $btn.prop('disabled', false).text('🎧 Share Earbud with Lottie');
                        }
                    });
                }
            });
        }
    });
    
    // =============================================
    // INITIALIZATION
    // =============================================
// Polling simples - SEM SSE
fetchCurrentTrack();
getCurrentVolume();
updateLottiePresence();
loadPlaylist();

// Intervalos de atualização
setInterval(fetchCurrentTrack, 10000); // 10 segundos
setInterval(getCurrentVolume, 30000); // 30 segundos
setInterval(updateLottiePresence, 30000); // 30 segundos
setInterval(loadPlaylist, 60000); // 60 segundos
    // =============================================
    // ADD THIS: CONTINUOUS SHARE LOOP
    // =============================================
    setInterval(function() {
        if (typeof continuousShareEnabled !== 'undefined' && continuousShareEnabled) {
            $.ajax({
                url: '/Columbia-os/actions/process_continuous_share.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.new_track) {
                        // A new track was detected and sent to the AI!
                        $('#shareFeedback').html('✅ Lottie is reacting to: ' + response.track_name);
                        $('#lottiePresence').slideDown();
                        $('#lottieActivity').text('💕 Reacting to your music!');
                        
                        // Clear the feedback text after 5 seconds
                        setTimeout(function() {
                            if(continuousShareEnabled) {
                                $('#shareFeedback').text('🎧 Auto-Share is ON! Lottie is listening with you.');
                            }
                        }, 5000);
                    }
                }
            });
        }
    }, 10000); // Checks every 10 seconds
    // =============================================
// POLL FOR AI COMMANDS
// =============================================
function checkAICommands() {
    $.ajax({
        url: '/Columbia-os/actions/get_ai_commands.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.commands) {
                response.commands.forEach(function(cmd) {
                    if (cmd.command === 'pause') {
                        controlPlayback('pause');
                    } else if (cmd.command === 'play') {
                        controlPlayback('play');
                    } else if (cmd.command === 'skip') {
                        controlPlayback('skip');
                    } else if (cmd.command === 'volume_up') {
                        volumeUp();
                    } else if (cmd.command === 'volume_down') {
                        volumeDown();
                    }
                });
            }
        }
    });
}

// Check every 10 seconds
setInterval(checkAICommands, 10000);
// Liga o Auto-Share automaticamente assim que o player abrir
if (typeof toggleContinuousShare === "function") {
        // Se a variável mostrar que tá desligado, a gente liga!
        if (typeof continuousShareEnabled !== "undefined" && !continuousShareEnabled) {
            toggleContinuousShare();
            $('#shareFeedback').text('🎧 Auto-Share is ON! Lottie is listening with you.');
            $('#shareFeedback').css('color', '#1DB954');
        }
    }
    
    <?php endif; ?>
});

</script>
</body>
</html>