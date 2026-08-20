<?php
session_start();
include '../includes/conexao.php';
include '../includes/notifications.php'; // ADD THIS - includes the time formatter

if (!isset($_SESSION['username']) || !isset($_POST['npc']) || !isset($_POST['last_id'])) {
    exit(json_encode([]));
}

$meu_username = trim(strtolower($_SESSION['username']));
$npc_username = trim(strtolower($_POST['npc']));
$last_id = (int)$_POST['last_id'];

$safe_meu = $conexao->real_escape_string($meu_username);
$safe_npc = $conexao->real_escape_string($npc_username);

// Fetch messages newer than the last ID currently rendered on the screen
$sql = "SELECT * FROM dms WHERE id > $last_id AND 
       ((LOWER(TRIM(sender)) = '$safe_meu' AND LOWER(TRIM(receiver)) = '$safe_npc') OR 
        (LOWER(TRIM(sender)) = '$safe_npc' AND LOWER(TRIM(receiver)) = '$safe_meu')) 
       ORDER BY id ASC";
       
$res = $conexao->query($sql);
$new_msgs = [];

while($msg = $res->fetch_assoc()) {
    // Guarantee message_text is a string
    $msg['message_text'] = (string)($msg['message_text'] ?? '');
    
    // FORMAT THE TIMESTAMP FOR DISPLAY (Twitter-style)
    $msg['formatted_time'] = format_twitter_time($msg['timestamp'] ?? date('Y-m-d H:i:s'));
    
    // CORREÇÃO: Marca como lida automaticamente se for do próprio usuário
    if (strtolower(trim($msg['sender'])) === strtolower(trim($_SESSION['username']))) {
        // Não precisa fazer nada aqui, mas evita que o JavaScript reenvie
    }
    
    $new_msgs[] = $msg;
}

header('Content-Type: application/json');
echo json_encode($new_msgs);
?>