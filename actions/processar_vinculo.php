<?php
session_start();
include '../includes/conexao.php';

// Garante que o usuário principal está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $main_id = $_SESSION['user_id'];
    $alt_user = $conexao->real_escape_string($_POST['username']);
    $alt_pass = $_POST['password'];

    // Remove o @ se digitado
    $alt_user = ltrim($alt_user, '@');

    // Busca a conta que está tentando vincular
    $sql = "SELECT * FROM perfis WHERE username = '{$alt_user}' LIMIT 1";
    $res = $conexao->query($sql);

    if ($res && $res->num_rows > 0) {
        $alt_perfil = $res->fetch_assoc();
        
        // Verifica a senha
        if ($alt_pass === $alt_perfil['senha']) {
            $alt_id = $alt_perfil['id'];

            // Prevent linking the currently active account
            if ($main_id == $alt_id) {
                die("<script>alert('You cannot link the account you are currently using!'); window.location.href='../pages/conectar_alt.php';</script>");
            }

            // Check if link already exists
            $check_link = "SELECT id FROM contas_vinculadas WHERE conta_principal_id = {$main_id} AND conta_secundaria_id = {$alt_id}";
            $res_link = $conexao->query($check_link);

            if ($res_link && $res_link->num_rows > 0) {
                die("<script>alert('This account is already linked to your profile!'); window.location.href='../index.php';</script>");
            }

            // Insert link
            $insert_link = "INSERT INTO contas_vinculadas (conta_principal_id, conta_alt_id) VALUES ({$main_id}, {$alt_id})";
            if ($conexao->query($insert_link)) {
                // Success - Redirect to feed
                header("Location: ../index.php");
                exit();
            } else {
                die("<script>alert('Columbia OS database error.'); window.location.href='../pages/conectar_alt.php';</script>");
            }
        } else {
            die("<script>alert('Incorrect password for the secondary account.'); window.location.href='../pages/conectar_alt.php';</script>");
        }
    } else {
        die("<script>alert('Account not found in the system.'); window.location.href='../pages/conectar_alt.php';</script>");
    }
}