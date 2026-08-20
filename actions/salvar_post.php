<?php
session_start();
include '../includes/conexao.php';

if (!isset($_POST['conteudo']) || empty(trim($_POST['conteudo']))) {
    http_response_code(400);
    echo "Error: Empty content";
    exit;
}

$texto_bruto = $_POST['conteudo'];
$conteudo = $conexao->real_escape_string($texto_bruto);

// Get vibe from POST, default to 'neutral'
$vibe = isset($_POST['vibe']) ? $conexao->real_escape_string($_POST['vibe']) : 'neutral';

// Get username from session
$username = $_SESSION['username'] ?? 'user';
$autor = $_SESSION['nome'] ?? $username;

// Generate timestamp
$timestamp = date('Y-m-d H:i:s');

// Insert with vibe column
$sql = "INSERT INTO posts (autor, username, conteudo, vibe, data_envio) 
        VALUES ('$autor', '$username', '$conteudo', '$vibe', '$timestamp')";

if ($conexao->query($sql) === TRUE) {
    $last_id = $conexao->insert_id;
    
    // Trigger AI reply in background (optional)
    $post_data = [
        'post_id' => $last_id,
        'parent_id' => isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0,
        'vibe' => $vibe
    ];
    
    $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . '/Columbia-os/actions/ai_reply_trigger.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    curl_exec($ch);
    curl_close($ch);
    
    echo "Success";
} else {
    http_response_code(500);
    echo "Error: " . $conexao->error;
}
?>