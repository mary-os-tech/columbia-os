<?php
session_start();
include '../includes/conexao.php';

$texto_puro = str_replace(array("\r", "\n"), '', $_POST['conteudo']);
$seu_texto = $conexao->real_escape_string($texto_puro);

$sql_perfil = "SELECT * FROM perfis WHERE username = 'user' LIMIT 1";
$resultado_perfil = $conexao->query($sql_perfil);

if ($resultado_perfil->num_rows > 0) {
    $perfil = $resultado_perfil->fetch_assoc();
    $autor = $perfil['nome'];
    $username = $perfil['username'];
    $avatar_db = $perfil['avatar'];
    
    if (strpos($avatar_db, 'http') === 0) {
        $avatar_html = '<img src="' . htmlspecialchars($avatar_db) . '" alt="Foto">';
    } else {
        $avatar_html = htmlspecialchars($avatar_db);
    }
} else {
    $autor = 'Desconhecido';
    $username = 'anonimo';
    $avatar_html = '👤';
}

$resposta = "OMG, I totally agree about '" . $seu_texto . "'! We need to get coffee and talk.";
$resposta_limpa = $conexao->real_escape_string($resposta);

$sql = "INSERT INTO posts (autor, username, conteudo) VALUES ('$autor', '$username', '$resposta_limpa')";

if ($conexao->query($sql) === TRUE) {
    echo '
    <div class="tweet">
        <div class="avatar">' . $avatar_html . '</div>
        <div class="tweet-content">
            <div class="tweet-header">
                <strong>' . htmlspecialchars($autor) . '</strong> <span>@' . htmlspecialchars($username) . '</span>
            </div>
            <p>' . stripslashes($resposta) . '</p>
        </div>
    </div>';
}
?>