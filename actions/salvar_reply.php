<?php
session_start();
include '../includes/conexao.php';

$conteudo = $_POST['conteudo'];
$parent_id = $_POST['parent_id'];
$vibe = isset($_POST['vibe']) ? $_POST['vibe'] : 'neutral'; // Caso o seletor seja adicionado depois
$username = $_SESSION['username'];
$autor = $username;

$sql = "INSERT INTO posts (autor, username, conteudo, parent_id, vibe, data_envio, data_criacao) 
        VALUES ('$autor', '$username', '$conteudo', '$parent_id', '$vibe', NOW(), NOW())";

if ($conexao->query($sql) === TRUE) {
    echo "Success";
} else {
    http_response_code(500);
    echo "Error: " . $conexao->error;
}
?>