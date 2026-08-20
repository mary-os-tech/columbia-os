<?php
session_start();
include '../includes/conexao.php';

if (!isset($_POST['post_id']) || !isset($_POST['tipo']) || !isset($_POST['acao'])) {
    exit('Dados inválidos');
}

$post_id = intval($_POST['post_id']);
$tipo = $conexao->real_escape_string($_POST['tipo']);
$acao = $_POST['acao']; // 'add' ou 'remove'
$username = 'user'; // O seu usuário logado

if ($acao == 'add') {
    $sql = "INSERT IGNORE INTO interacoes (post_id, username, tipo) VALUES ($post_id, '$username', '$tipo')";
} else {
    $sql = "DELETE FROM interacoes WHERE post_id = $post_id AND username = '$username' AND tipo = '$tipo'";
}

$conexao->query($sql);

// Retorna o novo total de interações daquele tipo para o post (para atualizar a UI se quisermos)
$sql_count = "SELECT COUNT(*) as total FROM interacoes WHERE post_id = $post_id AND tipo = '$tipo'";
$res = $conexao->query($sql_count);
$total = $res->fetch_assoc()['total'];

echo json_encode(['status' => 'success', 'total' => $total]);
?>