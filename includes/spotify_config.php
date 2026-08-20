<?php
// =============================================
// SPOTIFY API CONFIGURATION
// =============================================

// Puxa as credenciais do arquivo .env (que está seguro na pasta _private)
$client_id     = getenv('SPOTIFY_CLIENT_ID');
$client_secret = getenv('SPOTIFY_CLIENT_SECRET');

// ========== DEFINE AS CONSTANTES COM OS VALORES DO .ENV ==========
define('SPOTIFY_CLIENT_ID', $client_id);
define('SPOTIFY_CLIENT_SECRET', $client_secret);

// Redirect URI - MUST match exactly what's in Spotify Dashboard
define('SPOTIFY_REDIRECT_URI', 'http://127.0.0.1:8080/Columbia-os/includes/spotify_auth.php');

// Spotify API Endpoints
define('SPOTIFY_AUTH_URL', 'https://accounts.spotify.com/authorize');
define('SPOTIFY_TOKEN_URL', 'https://accounts.spotify.com/api/token');
define('SPOTIFY_API_BASE', 'https://api.spotify.com/v1');

// Required scopes for full functionality
define('SPOTIFY_SCOPES', implode(' ', [
    'user-read-playback-state',
    'user-modify-playback-state',
    'playlist-modify-private',
    'playlist-modify-public',
    'user-read-currently-playing',
    'user-top-read',
    'playlist-read-private',
    'playlist-read-collaborative',
    'ugc-image-upload'
]));

// Session keys for storing tokens
define('SPOTIFY_TOKEN_SESSION', 'spotify_access_token');
define('SPOTIFY_REFRESH_SESSION', 'spotify_refresh_token');
define('SPOTIFY_EXPIRY_SESSION', 'spotify_token_expiry');

// Cache TTL (seconds) - how long before we re-fetch current track
define('SPOTIFY_CACHE_TTL', 5);
?>