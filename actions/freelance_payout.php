<?php
session_start();
include '../includes/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

$username = $conexao->real_escape_string($_SESSION['username']);

// Busca o ID do usuário na SUA tabela correta (perfis)
$sql_user = "SELECT id FROM perfis WHERE username = '$username' LIMIT 1";
$res_user = $conexao->query($sql_user);

if ($res_user && $res_user->num_rows > 0) {
    $row = $res_user->fetch_assoc();
    $user_id = $row['id'];

    // Atualiza o dinheiro na tabela correta de stats
    $sql_update = "UPDATE player_stats SET money = money + 50 WHERE user_id = $user_id";
    if ($conexao->query($sql_update) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => '$50 added to your account!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update stats.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found in perfis table.']);
}
?>