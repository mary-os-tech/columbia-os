<?php
session_start();
include '../includes/conexao.php';

// SECURITY CHECK: Ensure valid session and POST payload
if (!isset($_SESSION['username']) || !isset($_POST['post_id']) || !isset($_POST['acao'])) {
    exit('Invalid request');
}

$username = $_SESSION['username'];
$post_id = (int)$_POST['post_id'];
$acao = $_POST['acao']; // 'add' or 'remove'

// 1. Fetch the numeric user_id from the perfis table
$stmt_user = $conexao->prepare("SELECT id FROM perfis WHERE username = ? LIMIT 1");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user_row = $res_user->fetch_assoc();
$stmt_user->close();

if (!$user_row) exit('User profile not found');
$user_id = (int)$user_row['id'];

if ($acao === 'add') {
    // Insert into the new bookmarks table
    $stmt = $conexao->prepare("INSERT IGNORE INTO bookmarks (post_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Maintain legacy interacoes table for your existing feed UI state
    $stmt2 = $conexao->prepare("INSERT IGNORE INTO interacoes (post_id, username, tipo) VALUES (?, ?, 'bookmark')");
    $stmt2->bind_param("is", $post_id, $username);
    $stmt2->execute();
    $stmt2->close();

} elseif ($acao === 'remove') {
    // Remove from bookmarks table
    $stmt = $conexao->prepare("DELETE FROM bookmarks WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Remove from legacy interacoes table
    $stmt2 = $conexao->prepare("DELETE FROM interacoes WHERE post_id = ? AND username = ? AND tipo = 'bookmark'");
    $stmt2->bind_param("is", $post_id, $username);
    $stmt2->execute();
    $stmt2->close();
}

echo "Success";
?>