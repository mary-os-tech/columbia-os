<?php
date_default_timezone_set('America/Sao_Paulo');
// Or use America/New_York for Columbia University

$servidor = "127.0.0.1";
$usuario = "root";
$senha = "";
$banco = "columbia_os";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Erro na ponte de conexão: " . $conexao->connect_error);
}
// --- TWITTER-STYLE TIME FORMATTING ---
function format_twitter_time($timestamp) {
    $now = new DateTime();
    $time = new DateTime($timestamp);
    $diff = $now->diff($time);
    
    // If it's today - show minutes/hours
    if ($now->format('Y-m-d') == $time->format('Y-m-d')) {
        if ($diff->h == 0 && $diff->i == 0) {
            return 'now';
        } elseif ($diff->h == 0) {
            return $diff->i . 'm';
        } else {
            return $diff->h . 'h';
        }
    }
    
    // If it's yesterday
    $yesterday = clone $now;
    $yesterday->modify('-1 day');
    if ($yesterday->format('Y-m-d') == $time->format('Y-m-d')) {
        return 'Yesterday at ' . $time->format('g:i A');
    }
    
    // If it's older - show date
    // Twitter format: "Aug 12, 2026" or "12/08/2026"
    return $time->format('M j, Y'); // Aug 12, 2026
    // Alternative: return $time->format('d/m/Y'); // 12/08/2026
}
?>