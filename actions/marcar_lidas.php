<?php
session_start();
include '../includes/conexao.php';

if (!isset($_POST['npc']) || !isset($_SESSION['username'])) {
    exit();
}

$meu_username = $_SESSION['username'];
$npc_username = $_POST['npc'];

$sql = "UPDATE dms SET is_read = 1 WHERE receiver = '$meu_username' AND sender = '$npc_username' AND (is_read = 0 OR is_read IS NULL)";
$conexao->query($sql);

echo "ok";
?>