<?php
// This script can be called by bot_engine.php or a cron job
include '../includes/conexao.php';

// Find the latest message in every conversation
$sql = "SELECT id, sender, receiver, timestamp, is_read FROM dms 
        WHERE is_read = 1 
        AND id IN (SELECT MAX(id) FROM dms GROUP BY sender, receiver)";
$res = $conexao->query($sql);

if ($res && $res->num_rows > 0) {
    while ($msg = $res->fetch_assoc()) {
        $msg_time = strtotime($msg['timestamp']);
        $now = time();
        $hours_passed = ($now - $msg_time) / 3600;

        // If Lottie sent the last message, Mary read it, and > 2 hours have passed without Mary replying
        if ($msg['sender'] === 'lottiematthews' && $hours_passed > 2) {
            // Flag this in the notifications table for the AI to reference later
            $stmt = $conexao->prepare("INSERT IGNORE INTO notifications (target_username, trigger_username, type, post_id) VALUES (?, ?, 'ignored_dm', ?)");
            $target = 'lottiematthews';
            $trigger = $msg['receiver']; // Mary
            $stmt->bind_param("ssi", $target, $trigger, $msg['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}
echo "Ignored DMs checked.";
?>