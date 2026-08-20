<?php
// =============================================
// TOGGLE CONTINUOUS SHARE MODE
// =============================================

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'toggle';

if ($action === 'toggle') {
    $_SESSION['continuous_share'] = !isset($_SESSION['continuous_share']) ? true : !$_SESSION['continuous_share'];
    
    // Start/stop the background process
    if ($_SESSION['continuous_share']) {
        $_SESSION['continuous_share_start'] = time();
        // Trigger immediate first check
        $ch = curl_init('../actions/process_continuous_share.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
        curl_exec($ch);
        curl_close($ch);
    }
    
    echo json_encode([
        'success' => true,
        'enabled' => $_SESSION['continuous_share'],
        'message' => $_SESSION['continuous_share'] ? '🎧 Continuous share ENABLED - Lottie will react to every new song!' : 'Continuous share DISABLED'
    ]);
} elseif ($action === 'status') {
    echo json_encode([
        'enabled' => $_SESSION['continuous_share'] ?? false,
        'started_at' => $_SESSION['continuous_share_start'] ?? null
    ]);
}
?>