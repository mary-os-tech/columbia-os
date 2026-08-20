<?php
session_start();
include_once '../includes/conexao.php';

// Se não houver sessão ou ID alvo, chuta pro index
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$target_id = (int)$_GET['id'];
$current_id = $_SESSION['user_id'];
// Define quem é a conta "mãe" para não perdermos a referência ao trocar para uma alt
$root_id = $_SESSION['main_user_id'] ?? $current_id;

// Verifica se o ID alvo é a conta principal OU uma das alts vinculadas a ela
$sql_check = "SELECT id, username FROM perfis 
              WHERE id = $target_id AND (
                  id = $root_id OR 
                  id IN (SELECT conta_alt_id FROM contas_vinculadas WHERE conta_principal_id = $root_id)
              ) LIMIT 1";
              
$res = $conexao->query($sql_check);

if ($res && $res->num_rows > 0) {
    $perfil = $res->fetch_assoc();
    
    // Sobrescreve a sessão com os dados da nova conta
    $_SESSION['user_id'] = $perfil['id'];
    $_SESSION['username'] = $perfil['username'];
    // Salva a conta raiz para permitir a volta
    $_SESSION['main_user_id'] = $root_id;
    
    session_write_close();
}

// Redireciona de volta para o feed
header("Location: ../index.php");
exit();