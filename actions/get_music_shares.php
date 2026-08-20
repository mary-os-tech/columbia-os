<?php
// =============================================
// GET MUSIC SHARE HISTORY
// =============================================
// Returns Mary's music sharing history with Lottie's reactions

session_start();
header('Content-Type: application/json');

include_once '../includes/conexao.php';

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$username = $_SESSION['username'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

$sql = "SELECT * FROM music_shares 
        WHERE username = ? 
        ORDER BY shared_at DESC 
        LIMIT ?";

$stmt = $conexao->prepare($sql);
$stmt->bind_param("si", $username, $limit);
$stmt->execute();
$result = $stmt->get_result();

$shares = [];
while ($row = $result->fetch_assoc()) {
    $shares[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($shares),
    'shares' => $shares
]);
?>