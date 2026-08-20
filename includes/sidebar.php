<?php
// Garante que a conexão com o banco exista para puxarmos a foto e o nome
include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/weather.php'; // Fetch Manhattan Weather
include_once __DIR__ . '/spotify_config.php';

$cache_key = 'sidebar_music_' . $_SESSION['username'];
$cache_time = 30; // seconds

// Check if we have cached data
$cached_data = null;
if (isset($_SESSION[$cache_key]) && isset($_SESSION[$cache_key . '_time'])) {
    if (time() - $_SESSION[$cache_key . '_time'] < $cache_time) {
        $cached_data = $_SESSION[$cache_key];
    }
}

if ($cached_data) {
    $current_track = $cached_data;
} else {
    // Query database and store in session
    // ... your existing query ...
    $_SESSION[$cache_key] = $current_track= null;
    $_SESSION[$cache_key . '_time'] = time();
}

$sidebar_user = $_SESSION['username'] ?? 'user';
// CORREÇÃO: Trocado 'autor' por 'nome'
$sql_sidebar = "SELECT nome, username, avatar FROM perfis WHERE username = '$sidebar_user' LIMIT 1";
$res_sidebar = $conexao->query($sql_sidebar);
// CORREÇÃO: Trocado 'autor' por 'nome' no array de fallback
$perfil_sidebar = $res_sidebar ? $res_sidebar->fetch_assoc() : ['nome' => 'User', 'username' => 'user', 'avatar' => '👤'];

$avatar_sidebar = $perfil_sidebar['avatar'];
$avatar_html_sidebar = (strpos($avatar_sidebar, 'http') === 0) ? "<img src='" . htmlspecialchars($avatar_sidebar) . "' style='width: 100%; height: 100%; object-fit: cover;'>" : htmlspecialchars($avatar_sidebar);

// NOVO: Busca as contas vinculadas usando o ID principal (root) para permitir ida e volta
$current_id = $_SESSION['user_id'] ?? 0;
$root_id = $_SESSION['main_user_id'] ?? $current_id;

// Busca todas as contas do "cluster" (a principal + as alts), exceto a que está logada agora
$sql_alts = "SELECT id, username, nome, avatar FROM perfis 
             WHERE id IN (
                SELECT conta_alt_id FROM contas_vinculadas WHERE conta_principal_id = $root_id
                UNION 
                SELECT $root_id
             ) AND id != $current_id";
$res_alts = $conexao->query($sql_alts);

// Fetch unread DMs count for the new 'dms' table architecture
$unread_dms_count = 0;
if (!empty($sidebar_user)) {
    // Optimized query handling both 0 and NULL states
    $sql_unread_dms = "SELECT COUNT(id) as unread FROM dms WHERE receiver = '$sidebar_user' AND (is_read = 0 OR is_read IS NULL)";
    $res_unread_dms = $conexao->query($sql_unread_dms);
    if ($res_unread_dms && $row_unread = $res_unread_dms->fetch_assoc()) {
        $unread_dms_count = (int)$row_unread['unread'];
    }
}
?>

<nav class="sidebar" style="position: -webkit-sticky !important; position: sticky !important; top: 0 !important; height: 100vh !important; align-self: flex-start !important;">
    <div class="sidebar-logo">
        <svg viewBox="0 0 24 24" style="width: 30px; height: 30px; fill: #e7e9ea;"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
    </div>

    <a href="<?php echo BASE_URL; ?>/index.php" class="sidebar-item active">
        <svg viewBox="0 0 24 24"><path d="M12 1.696L.622 8.807l1.06 1.696L3 9.679V19.5C3 20.881 4.119 22 5.5 22h13c1.381 0 2.5-1.119 2.5-2.5V9.679l1.318 1.082 1.06-1.696zm7.5 17.804c0 .276-.224.5-.5.5h-13c-.276 0-.5-.224-.5-.5V8.194l7-5.75 7 5.75z"/></svg>
        <span>Home</span>
    </a>

    <a href="#" class="sidebar-item">
        <svg viewBox="0 0 24 24" aria-hidden="true"><g><path d="M10.25 3.75c-3.59 0-6.5 2.91-6.5 6.5s2.91 6.5 6.5 6.5c1.53 0 2.93-.53 4.04-1.42l5.34 5.34 1.42-1.42-5.34-5.34c.9-1.11 1.42-2.51 1.42-4.04 0-3.59-2.91-6.5-6.5-6.5zm-4.5 6.5c0-2.48 2.02-4.5 4.5-4.5s4.5 2.02 4.5 4.5-2.02 4.5-4.5 4.5-4.5-2.02-4.5-4.5z"></path></g></svg>
        <span>Explore</span>
    </a>

    <a href="#" class="sidebar-item">
        <div style="position: relative; display: inline-flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24" aria-hidden="true"><g><path d="M11.996 2.005c-5.108 0-8.125 3.137-8.125 7.39v4.426l-2.058 4.062h20.374l-2.057-4.062V9.395c0-4.253-3.016-7.39-8.134-7.39zm-6.134 7.39c0-3.228 2.345-4.89 5.5-4.89 3.15 0 5.495 1.662 5.495 4.89v5.122l1.61 3.18H4.89l1.61-3.18V9.395zm4.945 11.46c.728 1.167 2.058 1.895 3.555 1.895s2.827-.728 3.555-1.895H8.945z"></path></g></svg>
            
            <?php 
            // Placeholder: Aqui você pode colocar sua query real de notificações não lidas no futuro
            $unread_notifs = 1; 
            if ($unread_notifs > 0): 
            ?>
            <span style="position: absolute; top: -4px; right: -2px; background-color: #f4212e; color: #fff; font-size: 11px; font-weight: bold; padding: 0 4px; border-radius: 9999px; min-width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; border: 1px solid #000;">
                <?php echo "{$unread_notifs}"; ?>
            </span>
            <?php endif; ?>
        </div>
        <span>Notifications</span>
    </a>
    <?php $is_in_pages = (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false); ?>
    <a href="<?php echo $is_in_pages ? 'messages.php' : 'pages/messages.php'; ?>" class="sidebar-item">
        <div style="position: relative; display: inline-flex; align-items: center; justify-content: center;">
            <svg viewBox="0 0 24 24"><path d="M1.998 5.5c0-1.381 1.119-2.5 2.5-2.5h15c1.381 0 2.5 1.119 2.5 2.5v13c0 1.381-1.119 2.5-2.5 2.5h-15c-1.381 0-2.5-1.119-2.5-2.5zm2.5-.5c-.276 0-.5.224-.5.5v.069l8 5.333 8-5.333V5.5c0-.276-.224-.5-.5-.5zm16 2.331l-7.5 5a1 1 0 0 1-1.108 0l-7.5-5V18.5c0 .276.224.5.5.5h15c.276 0 .5-.224.5-.5z"/></svg>
            
            <?php if ($unread_dms_count > 0): ?>
            <span style="position: absolute; top: -4px; right: -6px; background-color: #1d9bf0; color: #fff; font-size: 11px; font-weight: bold; padding: 0 4px; border-radius: 9999px; min-width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; border: 1px solid #000;">
                <?php echo $unread_dms_count; ?>
            </span>
            <?php endif; ?>
        </div>
        <span>Messages</span>
    </a>
    <a href="<?php echo $is_in_pages ? 'bookmarks.php' : 'pages/bookmarks.php'; ?>" class="sidebar-item">
        <svg viewBox="0 0 24 24" aria-hidden="true"><g><path d="M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z"></path></g></svg>
        <span>Bookmarks</span>
    </a>
 
    <a href="<?php echo BASE_URL; ?>/pages/perfil.php" class="sidebar-item">
        <svg viewBox="0 0 24 24" aria-hidden="true"><g><path d="M5.651 19h12.698c-.337-1.8-1.023-3.21-1.945-4.19C15.318 13.65 13.838 13 12 13s-3.317.65-4.404 1.81c-.922.98-1.608 2.39-1.945 4.19zm.486-5.56C7.627 11.85 9.648 11 12 11s4.373.85 5.863 2.44c1.477 1.58 2.366 3.8 2.632 6.46l.11 1.1H3.395l.11-1.1c.266-2.66 1.155-4.88 2.632-6.46zM12 4c-1.105 0-2 .9-2 2s.895 2 2 2 2-.9 2-2-.895-2-2-2zM8 6c0-2.21 1.791-4 4-4s4 1.79 4 4-1.791 4-4 4-4-1.79-4-4z"></path></g></svg>
        <span>Profile</span>
    </a>

  <!-- Weather Widget -->
  <div class="sidebar-item" style="cursor: default; pointer-events: none; margin-bottom: 10px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <span id="weather-icon" style="font-size: 1.5rem; width: 24px; text-align: center;"><?php echo $current_weather['icon']; ?></span>
            <div style="display: flex; flex-direction: column; line-height: 1.2;">
                <span style="font-size: 1rem; color: #e7e9ea; font-weight: bold;"><span id="weather-temp"><?php echo $current_weather['temp']; ?></span>°C</span>
                <span id="weather-city" style="font-size: 0.8rem; color: #71767b;"><?php echo htmlspecialchars($current_weather['city'] ?? 'Manhattan'); ?></span>
            </div>
        </div>
    </div>
<!-- ============================================= -->
<!-- MUSIC PLAYER WIDGET (Enhanced with controls)  -->
<!-- ============================================= -->
<?php
$current_track = null;
$is_spotify_connected = isset($_SESSION[SPOTIFY_TOKEN_SESSION]);
$is_expired = isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION];
$connected = $is_spotify_connected && !$is_expired;
$is_playing = false;

if ($connected) {
    $sql_track = "SELECT track_name, artist_name, is_playing FROM spotify_cache 
    WHERE user_id = ? 
    ORDER BY fetched_at DESC LIMIT 1";
    $stmt = $conexao->prepare($sql_track);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $current_track = $result->fetch_assoc();
        $is_playing = (bool)$current_track['is_playing'];
    }
    $stmt->close();
}
?>

<div class="sidebar-widget music-widget" style="background: #1a1a1a; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; border: 1px solid #2a2a2a;">
    <!-- Header -->
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <span style="font-size: 16px;">🎵</span>
        <span style="font-weight: 600; font-size: 13px; color: #fff; flex: 1;">Columbia Music</span>
        <?php if ($connected): ?>
            <span style="font-size: 9px; color: #1DB954;">● Live</span>
        <?php else: ?>
            <span style="font-size: 9px; color: #888;">○ Offline</span>
        <?php endif; ?>
        <a href="/Columbia-os/pages/music_player.php" target="_blank" style="color: #1DB954; text-decoration: none; font-size: 11px;">⤴</a>
    </div>
    
    <?php if ($connected && $current_track): ?>
        <!-- Track Info -->
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
            <div style="width: 32px; height: 32px; background: #2a2a2a; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;">
                <?php echo $current_track['is_playing'] ? '🎧' : '⏸️'; ?>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 12px; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($current_track['track_name'] ?? 'Unknown Track'); ?>
                </div>
                <div style="font-size: 10px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($current_track['artist_name'] ?? 'Unknown Artist'); ?>
                </div>
            </div>
            <span style="font-size: 10px; color: <?php echo $current_track['is_playing'] ? '#1DB954' : '#666'; ?>;">
                <?php echo $current_track['is_playing'] ? '▶' : '⏸'; ?>
            </span>
        </div>
        
        <!-- Playback Controls -->
        <div style="display: flex; align-items: center; gap: 6px; justify-content: center; padding: 4px 0; border-top: 1px solid #2a2a2a; border-bottom: 1px solid #2a2a2a; margin-bottom: 4px;">
            <button class="sidebar-control-btn" id="sidebar-prev" style="background: none; border: none; color: #888; cursor: pointer; font-size: 14px; padding: 2px 6px;">⏮</button>
            <button class="sidebar-control-btn" id="sidebar-play" style="background: #1DB954; border: none; color: #000; cursor: pointer; font-size: 16px; padding: 2px 12px; border-radius: 16px; font-weight: bold;">
                <?php echo $current_track['is_playing'] ? '⏸' : '▶'; ?>
            </button>
            <button class="sidebar-control-btn" id="sidebar-next" style="background: none; border: none; color: #888; cursor: pointer; font-size: 14px; padding: 2px 6px;">⏭</button>
            <button class="sidebar-control-btn" id="sidebar-volume-down" style="background: none; border: none; color: #888; cursor: pointer; font-size: 12px; padding: 2px 6px;">🔉</button>
            <span id="sidebar-volume-display" style="font-size: 9px; color: #666; min-width: 28px;">50%</span>
            <button class="sidebar-control-btn" id="sidebar-volume-up" style="background: none; border: none; color: #888; cursor: pointer; font-size: 12px; padding: 2px 6px;">🔊</button>
        </div>
        
        <!-- Open Player Link -->
        <div style="text-align: center; font-size: 9px; color: #666; margin-top: 2px;">
            <a href="/Columbia-os/pages/music_player.php" target="_blank" style="color: #1DB954; text-decoration: none;">Open full player</a>
        </div>
        
    <?php elseif ($connected): ?>
        <div style="text-align: center; padding: 6px 0; color: #666; font-size: 12px;">
            Nothing playing
            <br>
            <a href="/Columbia-os/pages/music_player.php" target="_blank" style="color: #1DB954; text-decoration: none; font-size: 11px;">Open player</a>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 6px 0;">
            <a href="/Columbia-os/includes/spotify_auth.php?action=login" style="color: #1DB954; text-decoration: none; font-size: 12px; font-weight: 500;">
                🔗 Connect Spotify
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Sidebar Control JavaScript -->
<script>
$(document).ready(function() {
    <?php if ($connected): ?>
    
    // Sidebar Playback Controls
    function sidebarControlPlayback(command, trackUri) {
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
                    // Update UI
                    if (command === 'play') {
                        $('#sidebar-play').text('⏸');
                    } else if (command === 'pause') {
                        $('#sidebar-play').text('▶');
                    }
                    // Refresh track info after a moment
                    setTimeout(function() {
                        location.reload(); // Quick refresh to update sidebar
                    }, 1000);
                }
            }
        });
    }
    
    $('#sidebar-play').click(function() {
        var isPlaying = $(this).text().trim() === '⏸';
        sidebarControlPlayback(isPlaying ? 'pause' : 'play');
    });
    
    $('#sidebar-prev').click(function() {
        sidebarControlPlayback('skip');
    });
    
    $('#sidebar-next').click(function() {
        sidebarControlPlayback('skip');
    });
    
    // Sidebar Volume Controls
    function sidebarGetVolume() {
        $.ajax({
            url: '/Columbia-os/actions/spotify_player_state.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.success && data.volume !== undefined) {
                    $('#sidebar-volume-display').text(data.volume + '%');
                }
            }
        });
    }
    
    function sidebarSetVolume(direction) {
        var command = direction === 'up' ? 'volume_up' : 'volume_down';
        $.ajax({
            url: '/Columbia-os/actions/spotify_control.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ command: command }),
            dataType: 'json',
            success: function() {
                setTimeout(sidebarGetVolume, 500);
            }
        });
    }
    
    $('#sidebar-volume-up').click(function() {
        sidebarSetVolume('up');
    });
    
    $('#sidebar-volume-down').click(function() {
        sidebarSetVolume('down');
    });
    
    // Get initial volume
    sidebarGetVolume();
    
    <?php endif; ?>
});
</script>
<script>
$(document).ready(function() {
    // Refresh sidebar music widget every 10 seconds
    setInterval(function() {
        $.ajax({
            url: '/Columbia-os/actions/spotify_current_track.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.is_playing) {
                    $('.music-widget .track-name').text(data.track_name);
                    $('.music-widget .artist-name').text(data.artist_name);
                    $('.music-widget .play-status').text('▶ Playing');
                }
            }
        });
    }, 10000);
});
</script>
<!-- ============================================= -->
<!-- LOTTIE'S PROFILE CARD -->
<!-- ============================================= -->
<?php include_once __DIR__ . '/lottie_profile_card.php'; ?>
<?php render_lottie_profile_card($conexao); ?>
    <!-- Battery Widget -->
    <div class="sidebar-item" style="cursor: default; pointer-events: none; margin-bottom: 10px;">
        <svg id="battery-svg" viewBox="0 0 24 24" style="width: 26.25px; height: 26.25px; fill: currentColor; transition: color 0.3s;">
            <!-- Battery Outline -->
            <path d="M20 10v4h2v-4h-2zm-2-4H4c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 10H4V8h14v8z"/>
            <!-- Battery Fill (Dynamic Width) -->
            <rect id="battery-fill" x="5" y="9" width="12" height="6" rx="0.5" fill="currentColor" style="transition: width 0.5s ease;"/>
        </svg>
        <span id="battery-indicator-text" style="font-size: 1.25rem; transition: color 0.3s;">100%</span>
    </div>
 
    <button onclick="abrirModalPost()" class="btn-sidebar-post" style="width: 90%; background-color: #1d9bf0; color: #fff; border: none; border-radius: 9999px; padding: 15px 32px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: 0.2s;">
    Post
</button>

    <div class="sidebar-profile" onclick="toggleSidebarMenu(event)">
        <div class="sidebar-profile-avatar">
            <?php echo $avatar_html_sidebar; ?>
        </div>
        <div class="sidebar-profile-info">
            <!-- CORREÇÃO: Trocado 'autor' por 'nome' -->
            <strong><?php echo htmlspecialchars($perfil_sidebar['nome']); ?></strong>
            <span>@<?php echo htmlspecialchars($perfil_sidebar['username']); ?></span>
        </div>
        <div class="sidebar-profile-dots">
            <svg viewBox="0 0 24 24"><path d="M3 12c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm9 2c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm7 0c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"></path></svg>
        </div>
        
        <div id="sidebar-dropdown" class="sidebar-dropdown-menu">
            
            <?php
            // NOVO: Loop para renderizar as contas vinculadas
            if ($res_alts && $res_alts->num_rows > 0) {
                while ($alt = $res_alts->fetch_assoc()) {
                    $alt_avatar = $alt['avatar'];
                    $alt_avatar_html = (strpos($alt_avatar, 'http') === 0) ? "<img src='" . htmlspecialchars($alt_avatar) . "' style='width: 100%; height: 100%; object-fit: cover;'>" : htmlspecialchars($alt_avatar);
                    ?>
                    <a href="<?php echo BASE_URL; ?>/actions/switch_account.php?id=<?php echo $alt['id']; ?>" class="dropdown-item account-switch" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px;">
                        <div style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; overflow: hidden; background-color: #333;">
                            <?php echo $alt_avatar_html; ?>
                        </div>
                        <div style="display: flex; flex-direction: column; line-height: 1.2;">
                            <!-- CORREÇÃO: Trocado 'autor' por 'nome' -->
                            <strong style="font-size: 0.95rem; color: #e7e9ea;"><?php echo htmlspecialchars($alt['nome']); ?></strong>
                            <span style="font-size: 0.85rem; color: #71767b;">@<?php echo htmlspecialchars($alt['username']); ?></span>
                        </div>
                    </a>
                    <?php
                }
                // Linha divisória antes do botão de logout
                echo '<div style="height: 1px; background-color: #2f3336; margin: 4px 0;"></div>';
            }
            ?>

<a href="<?php echo BASE_URL; ?>/pages/conectar_alt.php" class="dropdown-item" style="padding: 12px 16px; font-weight: bold;">
                Add an existing account
            </a>

            <a href="<?php echo BASE_URL; ?>/actions/logout.php" class="dropdown-item" style="padding: 12px 16px;">
                Log out @<?php echo htmlspecialchars($perfil_sidebar['username']); ?>
            </a>
        </div>
    </div>
</nav>

<!-- SCRIPT DO MENU DE TROCA DE CONTA E LOGOUT -->
<script>
    // Função para abrir e fechar o menu de perfil
    function toggleSidebarMenu(event) {
        event.stopPropagation();
        var menu = document.getElementById('sidebar-dropdown');
        if (menu.style.display === 'block') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
    }

    // Fecha o menu se clicar fora dele
    document.addEventListener('click', function(e) {
        var profileBtn = document.querySelector('.sidebar-profile');
        var menu = document.getElementById('sidebar-dropdown');
        if (menu && menu.style.display === 'block' && !profileBtn.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = 'none';
        }
    });
</script>

<!-- Global Battery & Screen Time Tracker -->
<script src="/Columbia-os/assets/js/battery.js"></script>