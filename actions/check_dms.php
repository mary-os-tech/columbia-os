<?php
session_start();
include '../includes/conexao.php';
$meu_username = $_SESSION['username'];

// Conta quantas mensagens NÃO LIDAS você tem
$sql = "SELECT COUNT(id) as total FROM dms WHERE receiver = '$meu_username' AND (is_read = 0 OR is_read IS NULL)";
$res = $conexao->query($sql);
$row = $res->fetch_assoc();

// Se tiver mais de 0, retorna 1. Se não, retorna 0.
echo ($row['total'] > 0) ? '1' : '0';
?>