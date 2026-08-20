<?php
// =============================================
// SPOTIFY OAUTH2 AUTHENTICATION HANDLER
// =============================================
// This file handles:
// 1. Redirect to Spotify login
// 2. Callback with authorization code
// 3. Exchange code for access/refresh tokens
// 4. Store tokens in session

session_start();
include_once 'spotify_config.php';
include_once 'conexao.php';

// Get the action from URL parameter
$action = $_GET['action'] ?? '';

// =============================================
// ACTION: LOGIN - Redirect to Spotify
// =============================================
if ($action === 'login') {
    // Build the authorization URL
    $params = http_build_query([
        'client_id' => SPOTIFY_CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri' => SPOTIFY_REDIRECT_URI,
        'scope' => SPOTIFY_SCOPES,
        'show_dialog' => 'true'
    ]);
    
    $auth_url = SPOTIFY_AUTH_URL . '?' . $params;
    
    // Redirect user to Spotify login
    header('Location: ' . $auth_url);
    exit;
}

// =============================================
// ACTION: CALLBACK - Handle Spotify's response
// =============================================
if ($action === 'callback' || isset($_GET['code'])) {
    $code = $_GET['code'] ?? '';
    $error = $_GET['error'] ?? '';
    
    // Check for errors from Spotify
    if ($error) {
        $_SESSION['spotify_error'] = "Spotify auth error: " . htmlspecialchars($error);
        header('Location: /Columbia-os/pages/music_player.php?error=auth_failed');
        exit;
    }
    
    if (empty($code)) {
        $_SESSION['spotify_error'] = "No authorization code received from Spotify.";
        header('Location: /Columbia-os/pages/music_player.php?error=no_code');
        exit;
    }
    
    // Exchange authorization code for access token
    $post_data = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => SPOTIFY_REDIRECT_URI,
        'client_id' => SPOTIFY_CLIENT_ID,
        'client_secret' => SPOTIFY_CLIENT_SECRET
    ]);
    
    $ch = curl_init(SPOTIFY_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        $_SESSION['spotify_error'] = "Failed to get token: HTTP $http_code";
        header('Location: /Columbia-os/pages/music_player.php?error=token_failed');
        exit;
    }
    
    $token_data = json_decode($response, true);
    
    if (!isset($token_data['access_token'])) {
        $_SESSION['spotify_error'] = "No access token in response.";
        header('Location: /Columbia-os/pages/music_player.php?error=no_token');
        exit;
    }
    
    // Store tokens in session
    $_SESSION[SPOTIFY_TOKEN_SESSION] = $token_data['access_token'];
    $_SESSION[SPOTIFY_REFRESH_SESSION] = $token_data['refresh_token'] ?? null;
    $_SESSION[SPOTIFY_EXPIRY_SESSION] = time() + ($token_data['expires_in'] ?? 3600);
    $_SESSION['spotify_connected'] = true;
    
    // Log success
    error_log("✅ Spotify OAuth2 connected successfully for user: " . ($_SESSION['username'] ?? 'unknown'));
    
    // Redirect back to music player
    header('Location: /Columbia-os/pages/music_player.php?connected=1');
    exit;
}

// =============================================
// ACTION: REFRESH - Refresh expired token
// =============================================
if ($action === 'refresh') {
    $refresh_token = $_SESSION[SPOTIFY_REFRESH_SESSION] ?? null;
    
    if (!$refresh_token) {
        http_response_code(401);
        echo json_encode(['error' => 'No refresh token available']);
        exit;
    }
    
    $post_data = http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
        'client_id' => SPOTIFY_CLIENT_ID,
        'client_secret' => SPOTIFY_CLIENT_SECRET
    ]);
    
    $ch = curl_init(SPOTIFY_TOKEN_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        http_response_code(401);
        echo json_encode(['error' => 'Failed to refresh token']);
        exit;
    }
    
    $token_data = json_decode($response, true);
    
    $_SESSION[SPOTIFY_TOKEN_SESSION] = $token_data['access_token'];
    $_SESSION[SPOTIFY_EXPIRY_SESSION] = time() + ($token_data['expires_in'] ?? 3600);
    
    echo json_encode(['success' => true]);
    exit;
}

// =============================================
// ACTION: LOGOUT - Clear Spotify session
// =============================================
if ($action === 'logout') {
    unset($_SESSION[SPOTIFY_TOKEN_SESSION]);
    unset($_SESSION[SPOTIFY_REFRESH_SESSION]);
    unset($_SESSION[SPOTIFY_EXPIRY_SESSION]);
    $_SESSION['spotify_connected'] = false;
    
    header('Location: /Columbia-os/pages/music_player.php');
    exit;
}

// =============================================
// ACTION: STATUS - Check if user is connected
// =============================================
if ($action === 'status') {
    $is_connected = isset($_SESSION[SPOTIFY_TOKEN_SESSION]);
    $is_expired = isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION];
    
    header('Content-Type: application/json');
    echo json_encode([
        'connected' => $is_connected && !$is_expired,
        'expired' => $is_expired,
        'username' => $_SESSION['username'] ?? null
    ]);
    exit;
}

// =============================================
// DEFAULT: Show connection status page
// =============================================
?>
<!DOCTYPE html>
<html>
<head>
    <title>Spotify Connection - Columbia OS</title>
    <meta charset="UTF-8">
    <style>
        body {
            background: #0a0a0a;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #1a1a1a;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            max-width: 400px;
            border: 1px solid #2a2a2a;
        }
        .spotify-logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1DB954;
        }
        p {
            color: #888;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            background: #1DB954;
            color: #fff;
            padding: 12px 32px;
            border-radius: 24px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #1ed760;
        }
        .btn-secondary {
            background: #333;
        }
        .btn-secondary:hover {
            background: #444;
        }
        .status-connected {
            color: #1DB954;
            font-weight: 600;
        }
        .status-disconnected {
            color: #ff4444;
            font-weight: 600;
        }
        .error {
            color: #ff4444;
            background: #2a0a0a;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="spotify-logo">🎵</div>
    <h1>Columbia Music</h1>
    <p>Connect your Spotify account to sync music with Lottie</p>
    
    <?php
    // Check if already connected
    $is_connected = isset($_SESSION[SPOTIFY_TOKEN_SESSION]);
    $is_expired = isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION];
    $error = $_SESSION['spotify_error'] ?? null;
    unset($_SESSION['spotify_error']);
    ?>
    
    <?php if ($error): ?>
        <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($is_connected && !$is_expired): ?>
        <p class="status-connected">✅ Connected to Spotify</p>
        <p style="font-size: 12px; color: #666;">Connected as: <?php echo htmlspecialchars($_SESSION['username'] ?? 'unknown'); ?></p>
        <a href="/Columbia-os/pages/music_player.php" class="btn">🎧 Go to Music Player</a>
        <br><br>
        <a href="?action=logout" class="btn btn-secondary">Disconnect</a>
    <?php else: ?>
        <p class="status-disconnected">⛔ Not connected</p>
        <?php if ($is_expired): ?>
            <p style="font-size: 12px; color: #ff8844;">⚠️ Token expired - please reconnect</p>
        <?php endif; ?>
        <a href="?action=login" class="btn">🔗 Connect Spotify</a>
    <?php endif; ?>
</div>
</body>
</html>