<?php
// Columbia OS - Spotify Tables Auto-Installer
// Run this once to create all necessary Spotify tables

session_start();
include_once __DIR__ . '/../includes/conexao.php';

// Security: Only allow in development mode
$dev_mode = true; // Set to false in production

if (!$dev_mode) {
    die('This script is disabled in production mode.');
}

// Check if user is admin (optional)
// if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
//     die('Only admin can run this script.');
// }

echo "<!DOCTYPE html>
<html>
<head>
    <title>Spotify Tables Installer</title>
    <style>
        body { font-family: system-ui; background: #15202b; color: #e7e9ea; padding: 20px; max-width: 800px; margin: 0 auto; }
        .container { background: #1e2732; padding: 30px; border-radius: 12px; }
        .success { color: #1DB954; }
        .error { color: #f4212e; }
        .info { color: #1d9bf0; }
        pre { background: #15202b; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🎵 Spotify Tables Installer</h1>";

// ========== CREATE TABLES ==========
$tables_created = 0;
$errors = [];

// 1. spotify_tokens
$sql_tokens = "CREATE TABLE IF NOT EXISTS spotify_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conexao->query($sql_tokens)) {
    echo "<p class='success'>✅ spotify_tokens table created successfully</p>";
    $tables_created++;
} else {
    echo "<p class='error'>❌ Error creating spotify_tokens: " . $conexao->error . "</p>";
    $errors[] = $conexao->error;
}

// 2. spotify_cache
$sql_cache = "CREATE TABLE IF NOT EXISTS spotify_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255),
    track_data TEXT,
    track_id VARCHAR(255),
    track_name VARCHAR(255),
    artist_name VARCHAR(255),
    album_name VARCHAR(255),
    album_art_url VARCHAR(500),
    energy FLOAT DEFAULT 0.5,
    acousticness FLOAT DEFAULT 0.5,
    danceability FLOAT DEFAULT 0.5,
    valence FLOAT DEFAULT 0.5,
    tempo FLOAT DEFAULT 120,
    played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username_track (username, track_id),
    INDEX idx_played_at (played_at),
    UNIQUE KEY unique_username_track (username, track_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conexao->query($sql_cache)) {
    echo "<p class='success'>✅ spotify_cache table created successfully</p>";
    $tables_created++;
} else {
    echo "<p class='error'>❌ Error creating spotify_cache: " . $conexao->error . "</p>";
    $errors[] = $conexao->error;
}

// 3. lottie_spotify_state
$sql_lottie = "CREATE TABLE IF NOT EXISTS lottie_spotify_state (
    id INT AUTO_INCREMENT PRIMARY KEY,
    is_listening BOOLEAN DEFAULT FALSE,
    current_track_id VARCHAR(255),
    current_track_name VARCHAR(255),
    current_artist VARCHAR(255),
    mood_playlist_id VARCHAR(255),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    emotional_state VARCHAR(50) DEFAULT 'neutral',
    INDEX idx_listening_state (is_listening)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conexao->query($sql_lottie)) {
    echo "<p class='success'>✅ lottie_spotify_state table created successfully</p>";
    $tables_created++;
} else {
    echo "<p class='error'>❌ Error creating lottie_spotify_state: " . $conexao->error . "</p>";
    $errors[] = $conexao->error;
}

// Insert default Lottie state
$sql_insert = "INSERT INTO lottie_spotify_state (is_listening, emotional_state) 
               VALUES (FALSE, 'neutral') 
               ON DUPLICATE KEY UPDATE emotional_state = 'neutral'";
if ($conexao->query($sql_insert)) {
    echo "<p class='success'>✅ Lottie default state inserted</p>";
} else {
    echo "<p class='info'>ℹ️ Lottie state already exists or insert skipped</p>";
}

// ========== SUMMARY ==========
echo "<div style='margin-top: 20px; padding: 20px; background: #15202b; border-radius: 8px;'>";
echo "<h3>Installation Summary</h3>";
echo "<p><strong>Tables created:</strong> $tables_created / 3</p>";

if (empty($errors)) {
    echo "<p class='success'>✅ All tables created successfully! Spotify integration is ready.</p>";
    echo "<p><a href='/Columbia-os/pages/spotify_test.php' style='color: #1DB954;'>→ Test Spotify Connection</a></p>";
} else {
    echo "<p class='error'>⚠️ Some errors occurred:</p>";
    echo "<pre>";
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . "\n";
    }
    echo "</pre>";
}

echo "<p><a href='/Columbia-os/index.php' style='color: #1d9bf0;'>← Back to Columbia OS</a></p>";
echo "</div>";

echo "</div></body></html>";
?>