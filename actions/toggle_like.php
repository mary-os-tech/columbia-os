<?php
session_start();
include '../includes/conexao.php';
include '../includes/notifications.php';

$post_id = $_POST['post_id'] ?? 0;
$username = $_SESSION['username'] ?? 'user';

// Get post author
$sql = "SELECT autor FROM posts WHERE id = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if ($post && $post['autor'] != $username) {
    // Create notification for post author
    $message = "@{$username} liked your tweet";
    $link = "pages/tweet.php?post_id={$post_id}";
    create_notification($post['autor'], 'like', $message, $link);
}

$acao = $_POST['acao'] ?? 'add';
// Secure Prepared Statement to fetch post details for the anxiety mechanic
$stmt = $conexao->prepare("SELECT username, data_envio FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) exit('Post not found');

$post_author = $post['username'];
$post_date = strtotime($post['data_envio']);
$now = time();
$days_old = ($now - $post_date) / (60 * 60 * 24);

if ($acao === 'add') {
    // Insert into the new post_likes table
    $stmt = $conexao->prepare("INSERT IGNORE INTO post_likes (post_id, username) VALUES (?, ?)");
    $stmt->bind_param("is", $post_id, $username);
    $stmt->execute();
    $stmt->close();
    
    // Maintain legacy interacoes table for your existing feed UI state
    $stmt2 = $conexao->prepare("INSERT IGNORE INTO interacoes (post_id, username, tipo) VALUES (?, ?, 'like')");
    $stmt2->bind_param("is", $post_id, $username);
    $stmt2->execute();
    $stmt2->close();

} elseif ($acao === 'remove') {
    // Remove from post_likes
    $stmt = $conexao->prepare("DELETE FROM post_likes WHERE post_id = ? AND username = ?");
    $stmt->bind_param("is", $post_id, $username);
    $stmt->execute();
    $stmt->close();
    
    // Remove from legacy interacoes table
    $stmt2 = $conexao->prepare("DELETE FROM interacoes WHERE post_id = ? AND username = ? AND tipo = 'like'");
    $stmt2->bind_param("is", $post_id, $username);
    $stmt2->execute();
    $stmt2->close();

    // 😰 ANXIETY MECHANIC: The "Accidental Like" (Ghost Notification)
    // If Mary unlikes a post older than 30 days, 50% chance to trigger a ghost notification
    if ($days_old > 30 && $post_author !== $username) {
        if (rand(1, 100) <= 50) {
            $type = 'ghost_like';
            $stmt3 = $conexao->prepare("INSERT INTO notifications (target_username, trigger_username, type, post_id) VALUES (?, ?, ?, ?)");
            $stmt3->bind_param("sssi", $post_author, $username, $type, $post_id);
            $stmt3->execute();
            $stmt3->close();
        }
    }
}

echo "Success";
?>