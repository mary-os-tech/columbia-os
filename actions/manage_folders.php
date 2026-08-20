<?php
session_start();
include '../includes/conexao.php';

if (!isset($_SESSION['username']) || !isset($_POST['action'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$username = $_SESSION['username'];
$action = $_POST['action'];

// Fetch the numeric user_id from the perfis table
$stmt_user = $conexao->prepare("SELECT id FROM perfis WHERE username = ? LIMIT 1");
$stmt_user->bind_param("s", $username);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user_row = $res_user->fetch_assoc();
$stmt_user->close();

if (!$user_row) exit(json_encode(['error' => 'User not found']));
$user_id = (int)$user_row['id'];

if ($action === 'fetch') {
    // Fetch existing folders for this user
    $stmt = $conexao->prepare("SELECT id, folder_name FROM bookmark_folders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $folders = [];
    while ($row = $res->fetch_assoc()) {
        $folders[] = $row;
    }
    $stmt->close();
    echo json_encode($folders);
} 
elseif ($action === 'create') {
    // Create a new folder and immediately assign the post to it
    $folder_name = trim($_POST['folder_name'] ?? '');
    $post_id = (int)($_POST['post_id'] ?? 0);
    
    if (empty($folder_name) || $post_id === 0) exit(json_encode(['error' => 'Invalid data']));
    
    $stmt = $conexao->prepare("INSERT INTO bookmark_folders (user_id, folder_name) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $folder_name);
    $stmt->execute();
    $folder_id = $stmt->insert_id;
    $stmt->close();
    
    $stmt2 = $conexao->prepare("UPDATE bookmarks SET folder_id = ? WHERE post_id = ? AND user_id = ?");
    $stmt2->bind_param("iii", $folder_id, $post_id, $user_id);
    $stmt2->execute();
    $stmt2->close();
    
    echo json_encode(['success' => true]);
}
elseif ($action === 'assign') {
    // Assign the post to an existing folder
    $folder_id = (int)($_POST['folder_id'] ?? 0);
    $post_id = (int)($_POST['post_id'] ?? 0);
    
    if ($folder_id === 0 || $post_id === 0) exit(json_encode(['error' => 'Invalid data']));
    
    $stmt = $conexao->prepare("UPDATE bookmarks SET folder_id = ? WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("iii", $folder_id, $post_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
}
?>