<?php
// actions/ai_reply_trigger.php
// O "Gerente de Interações". Decide quem responde ao seu tweet.

session_start();
include_once __DIR__ . '/../includes/conexao.php';
include_once __DIR__ . '/../includes/api_config.php';
// Recebe os dados do tweet que acabou de ser postado
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
$vibe = isset($_POST['vibe']) ? $conexao->real_escape_string($_POST['vibe']) : 'neutral';

if ($post_id === 0) {
    exit(); // Se não tiver ID, sai.
}

// 1. Buscar o último post para saber o conteúdo e o autor
$sql_post = "SELECT autor, conteudo, username FROM posts WHERE id = $post_id LIMIT 1";
$res_post = $conexao->query($sql_post);
if ($res_post->num_rows === 0) exit();
$post = $res_post->fetch_assoc();

// Se o autor for um NPC, não gera resposta (evita loop infinito)
if (strtolower($post['username']) !== 'user' && strtolower($post['username']) !== 'mary') {
    exit();
}

// 2. Definir os NPCs disponíveis e seus pesos (probabilidade de responder)
$npcs = [
    [
        'username' => 'lottiematthews',
        'nome' => 'Lottie Matthews',
        'weight' => 50, // 50% de chance
        'system_prompt' => "You are Lottie Matthews, a 20yo Psych Major, Mary's girlfriend. Respond casually to her tweet in lowercase. Keep it short and cute."
    ],
    [
        'username' => 'overheardCU',
        'nome' => 'Overheard at Columbia',
        'weight' => 30, // 30% de chance
        'system_prompt' => "You are 'Overheard at Columbia', a sarcastic gossip account. You roast everything. Make a snarky, funny reply to this tweet."
    ],
    [
        'username' => 'mygod',
        'nome' => 'Katie',
        'weight' => 20, // 20% de chance
        'system_prompt' => "You are a random Columbia student. Reply to this tweet like a normal, slightly sarcastic college student."
    ]
];

// 3. Escolher um NPC baseado nos pesos
$total_weight = array_sum(array_column($npcs, 'weight'));
$random = mt_rand(1, $total_weight);
$selected_npc = null;
$current_weight = 0;

foreach ($npcs as $npc) {
    $current_weight += $npc['weight'];
    if ($random <= $current_weight) {
        $selected_npc = $npc;
        break;
    }
}

if (!$selected_npc) exit();

$npc_username = $selected_npc['username'];
$npc_nome = $selected_npc['nome'];

// 4. Montar o prompt para a IA
$system_prompt = $selected_npc['system_prompt'] . "\n\nReply to this tweet: '" . $post['conteudo'] . "'.";

$messages_payload = [
    ["role" => "system", "content" => $system_prompt],
    ["role" => "user", "content" => $post['conteudo']]
];

// 5. Chamar a IA (OPENROUTER)
$post_data = json_encode([
    "model" => OPENROUTER_MODEL,
    "messages" => $messages_payload,
    "max_tokens" => 60,
    "temperature" => 0.8
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
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response || $http_code !== 200) {
    error_log("OPENROUTER API Error in ai_reply_trigger: HTTP $http_code");
    exit();
}

// 6. Postar a resposta no banco
$data_envio = date('Y-m-d H:i:s');
$visibility = 'public';
$vibe_reply = 'neutral'; // Vibe padrão da resposta

$stmt = $conexao->prepare("INSERT INTO posts (autor, username, conteudo, data_envio, vibe, visibility, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssi", $npc_nome, $npc_username, $reply_text, $data_envio, $vibe_reply, $visibility, $post_id);
if ($stmt->execute()) {
    echo "Reply posted by " . $npc_username;
} else {
    echo "Error: " . $conexao->error;
}

$stmt->close();
?>