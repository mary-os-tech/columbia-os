<?php
session_start();
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'mary';
}

set_time_limit(30);
include '../includes/conexao.php';
include '../includes/api_config.php';

$active_player = $_SESSION['username'] ?? 'mary';
session_write_close();

echo "<h3>Columbia OS - Autonomous Routine (COM VIBES)</h3>";

// Buscar timeline
$sql_timeline = "SELECT id, autor, username, conteudo FROM posts WHERE (is_locked = 0 OR is_locked IS NULL) AND parent_id IS NULL ORDER BY id DESC LIMIT 5";
$result = $conexao->query($sql_timeline);

$timeline_context = "Timeline:\n";
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $timeline_context .= "@{$row['username']}: {$row['conteudo']}\n";
    }
} else {
    $timeline_context .= "Empty.\n";
}

echo "<pre>" . htmlspecialchars($timeline_context) . "</pre><hr>";

// ========== OPENROUTER HELPER ==========
function call_ai_fast($system_prompt, $user_prompt) {
    $post_data = json_encode([
        "model" => OPENROUTER_MODEL,
        "messages" => [
            ["role" => "system", "content" => $system_prompt],
            ["role" => "user", "content" => $user_prompt]
        ],
        "max_tokens" => 120,
        "temperature" => 0.7
    ]);

    $ch = curl_init(OPENROUTER_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENROUTER_API_KEY,
        'HTTP-Referer: ' . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : ''),
        'X-Title: Columbia OS'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$response || $http_code !== 200) {
        error_log("OPENROUTER API Error in routine_trigger: HTTP $http_code");
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

// ========== EXECUTOR COM VIBES ==========
function execute_npc_action($response_text, $npc_username, $npc_name, $conexao) {
    $action_pattern = '/<ACTION>(.*?)<\/ACTION>/is';
    if (preg_match($action_pattern, $response_text, $matches)) {
        $action_data = json_decode(trim($matches[1]), true);
        
        if ($action_data && isset($action_data['type'])) {
            if ($action_data['type'] === 'post_status' && isset($action_data['content'])) {
                $content = $action_data['content'];
                $vibe = $action_data['vibe'] ?? 'neutral';
                
                if ($npc_username === 'overheardCU') {
                    $vibe = 'toxic';
                }
                
                $allowed_vibes = ['excited', 'pittsburgh-pride', 'frustrated', 'anxious', 'romantic', 'neutral', 'sad', 'flirty', 'toxic', 'study'];
                if (!in_array($vibe, $allowed_vibes)) {
                    $vibe = 'neutral';
                }
                
                $date = date('Y-m-d H:i:s');
                
                $stmt = $conexao->prepare("INSERT INTO posts (autor, username, conteudo, data_envio, vibe, visibility, parent_id) VALUES (?, ?, ?, ?, ?, 'public', NULL)");
                $stmt->bind_param("sssss", $npc_name, $npc_username, $content, $date, $vibe);
                $stmt->execute();
                $stmt->close();
                
                $emoji = [
                    'excited' => '🟡',
                    'pittsburgh-pride' => '🟡',
                    'frustrated' => '🟠',
                    'anxious' => '🟣',
                    'romantic' => '💗',
                    'toxic' => '🔴',
                    'neutral' => '⚪'
                ][$vibe] ?? '⚪';
                
                return "📝 Posted ({$emoji} {$vibe}): $content";
            } elseif ($action_data['type'] === 'like_post' && isset($action_data['post_id'])) {
                $post_id = (int)$action_data['post_id'];
                $conexao->query("INSERT IGNORE INTO interacoes (post_id, username, tipo) VALUES ($post_id, '$npc_username', 'like')");
                return "❤️ Liked Post $post_id";
            } elseif ($action_data['type'] === 'send_dm' && isset($action_data['message'])) {
                $receiver = $action_data['receiver'] ?? 'mary';
                $message = $action_data['message'];
                $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("sss", $npc_username, $receiver, $message);
                $stmt->execute();
                $stmt->close();
                return "💬 DM sent to @$receiver: $message";
            }
        }
    }
    return "😴 Did nothing";
}

// ==========================================
// LOTTIE (COM VIBES)
// ==========================================
$lottie_prompt = "You are Lottie Matthews, Mary's girlfriend. Based on the timeline, decide ONE action:

For POST_STATUS, you MUST choose a vibe from these EXACT options:
- excited (happy/energetic) 🟡
- pittsburgh-pride (Steelers/Pittsburgh love) 🟡
- frustrated (annoyed/venting) 🟠
- anxious (worried) 🟣
- romantic (lovey-dovey about Mary) 💗
- neutral (normal) ⚪

Examples:
<ACTION>{\"type\":\"post_status\",\"content\":\"just got an A! so happy rn 🎉\",\"vibe\":\"excited\"}</ACTION>
<ACTION>{\"type\":\"post_status\",\"content\":\"missing my girl so much rn 🥺\",\"vibe\":\"romantic\"}</ACTION>

Options:
<ACTION>{\"type\":\"post_status\",\"content\":\"your tweet\",\"vibe\":\"excited\"}</ACTION>
<ACTION>{\"type\":\"like_post\",\"post_id\":123}</ACTION>
<ACTION>{\"type\":\"send_dm\",\"receiver\":\"mary\",\"message\":\"your DM\"}</ACTION>
<ACTION>{\"type\":\"do_nothing\"}</ACTION>
Only output the <ACTION> tag, nothing else.";

echo "<strong>🤖 Lottie...</strong><br>";
$lottie_response = call_ai_fast($lottie_prompt, $timeline_context);

if ($lottie_response) {
    echo "📤 Response: " . htmlspecialchars($lottie_response) . "<br>";
    $result = execute_npc_action($lottie_response, 'lottiematthews', 'Lottie Matthews', $conexao);
    echo "<span style='color:green;'>✅ $result</span><br><br>";
} else {
    echo "<span style='color:red;'>❌ Lottie offline</span><br><br>";
}

// ==========================================
// OVERHEADCU (FORÇA VIBE TOXIC)
// ==========================================
$gossip_prompt = "You are @overheardCU, a gossipy college account. Based on the timeline, post ONE rumor or do nothing:
<ACTION>{\"type\":\"post_status\",\"content\":\"Spotted: ...\",\"vibe\":\"toxic\"}</ACTION>
<ACTION>{\"type\":\"do_nothing\"}</ACTION>
Only output the <ACTION> tag.";

echo "<strong>🤖 OverheardCU...</strong><br>";
$gossip_response = call_ai_fast($gossip_prompt, $timeline_context);

if ($gossip_response) {
    echo "📤 Response: " . htmlspecialchars($gossip_response) . "<br>";
    $result = execute_npc_action($gossip_response, 'overheardCU', 'Overheard at Columbia', $conexao);
    echo "<span style='color:green;'>✅ $result</span><br><br>";
} else {
    echo "<span style='color:red;'>❌ OverheardCU offline</span><br><br>";
}

echo "<hr><strong>✅ Routine Complete!</strong>";

// Process scheduled actions
include_once 'process_scheduled_actions.php';
?>