<?php
// actions/update_stats.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized or missing action.']);
    exit();
}

require_once '../includes/conexao.php';

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];

if ($action === 'penalty') {
    // Add 15 Stress, cap at 100
    $stmt = $conexao->prepare("UPDATE player_stats SET stress = LEAST(stress + 15, 100) WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
} elseif ($action === 'success') {
    // Add 20 Energy (cap at 100) and 5 Focus Points
    $stmt = $conexao->prepare("UPDATE player_stats SET energy = LEAST(energy + 20, 100), focus_points = focus_points + 5 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    exit();
}

// Fetch the updated stats to return to the frontend
$stmt_fetch = $conexao->prepare("SELECT energy, stress, money, focus_points FROM player_stats WHERE user_id = ?");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
$stats = $result->fetch_assoc();
$stmt_fetch->close();

echo json_encode([
    'status' => 'success',
    'energy' => $stats['energy'],
    'stress' => $stats['stress'],
    'focus_points' => $stats['focus_points'],
    'money' => $stats['money']
]);
?>