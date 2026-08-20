<?php
session_start();
include '../includes/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $conexao->real_escape_string($_POST['nome']);
    $user = $conexao->real_escape_string($_POST['username']);
    $pass = $conexao->real_escape_string($_POST['password']); // Mantendo texto puro conforme login.php

    // Remove o @ se o usuário tiver digitado
    $user = ltrim($user, '@');

    $check_sql = "SELECT id FROM perfis WHERE username = '{$user}' LIMIT 1";
    $check_res = $conexao->query($check_sql);

    if ($check_res && $check_res->num_rows > 0) {
        $_SESSION['login_erro'] = "This username is already in use on the Columbia network.";
        header("Location: ../login.php");
        exit();
    }

    $insert_sql = "INSERT INTO perfis (nome, username, senha) VALUES ('{$nome}', '{$user}', '{$pass}')";
    
    if ($conexao->query($insert_sql)) {
        $_SESSION['login_erro'] = "Account created successfully! Log in to access Status.";
    } else {
        $_SESSION['login_erro'] = "Columbia OS system error. Please try again.";
    }
    header("Location: ../login.php");
    exit();
}