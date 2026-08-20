<?php
/**
 * Columbia OS - Fetch Collaborative Playlist
 * Returns the latest tracks from Mary & Lottie's Dorm Mix
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Active session required.']);
    exit;
}

require_once __DIR__ . '/../includes/conexao.php';

try {
    $stmt = $conexao->prepare("SELECT track_name, artist_name, album_art, added_by, added_at FROM collaborative_playlist ORDER BY added_at DESC LIMIT 20");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tracks = [];
    while ($row = $result->fetch_assoc()) {
        $tracks[] = $row;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'tracks' => $tracks]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>