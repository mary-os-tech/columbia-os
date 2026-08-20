<?php
// actions/gerar_usuario_teste.php
include '../includes/conexao.php';

$username = 'mary';
$senha = '123456';
$nome = 'Mary';

// Verifica se a usuária já existe
$sql_check = "SELECT id FROM perfis WHERE username = '$username'";
$res_check = $conexao->query($sql_check);

if ($res_check && $res_check->num_rows > 0) {
    echo "User '{$username}' already exists in the database.

    You can now log in!";
} else {
    // Insert new user with plain text password
    $sql_insert = "INSERT INTO perfis (nome, username, senha) VALUES ('$nome', '$username', '$senha')";
    
    if ($conexao->query($sql_insert)) {
        echo "User '{$username}' with password '{$senha}' created successfully! 

        Welcome back to Columbia, Mary.";
    } else {
        echo "Error creating user: " . $conexao->error;
    }
}
?>