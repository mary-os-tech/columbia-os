<?php
session_start();
include(__DIR__ . '/../includes/config.php');
include '../includes/conexao.php';

$nome = $conexao->real_escape_string($_POST['nome']);
$username = $conexao->real_escape_string($_POST['username']);
$avatar = $conexao->real_escape_string($_POST['avatar']);
$header = $conexao->real_escape_string($_POST['header']);
$presence = $conexao->real_escape_string($_POST['presence']); 
$bio = $conexao->real_escape_string($_POST['bio']);

$sql = "UPDATE perfis SET nome='$nome', username='$username', avatar='$avatar', header_image='$header', status_presenca='$presence', bio='$bio' WHERE username='user'";

if ($conexao->query($sql) === TRUE) {
    echo "Atualizado com sucesso!";
} else {
    http_response_code(500);
    echo "Erro: " . $conexao->error;
}
?>