<?php
session_start();
include __DIR__ . '/../includes/conexao.php';

if (!isset($_SESSION['username'])) {
    echo "0";
    exit();
}

$username = $_SESSION['username'];
$last_time = isset($_POST['last_time']) ? $_POST['last_time'] : date('Y-m-d H:i:s', strtotime('-1 minute'));

$sql = "SELECT COUNT(*) as count FROM posts 
        WHERE (is_locked = 0 OR is_locked IS NULL) 
        AND parent_id IS NULL 
        AND data_envio > '$last_time'";

$res = $conexao->query($sql);
if ($res) {
    $row = $res->fetch_assoc();
    echo $row['count'];
} else {
    echo "0";
}
?>