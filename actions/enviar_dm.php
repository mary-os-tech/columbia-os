<?php
session_start();
include '../includes/conexao.php';
include '../includes/notifications.php';

$sender = $_SESSION['username'] ?? '';
$receiver = $_POST['receiver'] ?? '';
$message_text = $_POST['message_text'] ?? '';
$imagem_url = $_POST['imagen_url'] ?? '';

if (empty($sender) || empty($receiver)) {
    die("ERRO: Sender ou Receiver estão vazios.");
}

$timestamp = date('Y-m-d H:i:s');

// Insert DM
$sql = "INSERT INTO dms (sender, receiver, message_text, imagem_url, timestamp) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("sssss", $sender, $receiver, $message_text, $imagem_url, $timestamp);

if ($stmt->execute()) {
    // Create notification for receiver
    $message = "New DM from @{$sender}";
    $link = "pages/dm.php?user={$receiver}";
   // create_notification($receiver, 'dm', $message, $link); //
    
    echo "Success";
} else {
    echo "ERRO AO SALVAR: " . $stmt->error;
}
$stmt->close();
?>