<?php
session_start();
include '../includes/conexao.php';

$sender = $_POST['npc'] ?? '';
$receiver = $_SESSION['username'] ?? 'user';

// --- HUMAN FILTER (Whitelist) ---
// Prevent player accounts from auto-replying to each other
$player_accounts = ['mary', 'mary_alt', 'user'];
if (in_array(strtolower($sender), $player_accounts)) {
    exit(); // Abort AI response silently
}

// --- AI MODULE (Simulator) ---
$simulated_responses = [
    "OMG yes!! 😭",
    "I'm literally screaming rn lol",
    "Wait, let me send u a pic later",
    "For sure. Btw did u see what happened today?",
    "idk tbh, but I'm down for coffee ☕",
    "You're so right bestie ✨",
    "Stop, I can't even deal with this right now 💀"
];

$content = $simulated_responses[array_rand($simulated_responses)];
$escaped_content = $conexao->real_escape_string($content);

// Save NPC response to the database using Prepared Statements
$stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $sender, $receiver, $content);

if ($stmt->execute()) {
    echo htmlspecialchars($content);
} else {
    echo "Error replying.";
}
$stmt->close();
?>