<?php
set_time_limit(120);
session_start();
include '../includes/conexao.php';
include '../includes/api_config.php';

if (!isset($_POST['npc']) || !isset($_SESSION['username'])) {
    die("Error: Missing parameters.");
}

$npc_username = trim($_POST['npc']);
if (empty($npc_username) || strlen($npc_username) > 50) {
    die("Error: Invalid NPC username.");
}
$meu_username = $_SESSION['username'];

// ==========================================
// VERIFICAR SE É EVENTO DE MÚSICA
// ==========================================
if (isset($_POST['music_event']) && $_POST['music_event']) {
    $track_name = $_POST['track_name'] ?? 'Unknown';
    $artist_name = $_POST['artist_name'] ?? 'Unknown';
    $track_uri = $_POST['track_uri'] ?? '';
    
    $last_message = "🎧 I'm listening to '{$track_name}' by {$artist_name}. [URI: {$track_uri}]";
    $context = 'music';
} else {
    $last_message = '';
    $context = 'dm';
}

// ==========================================
// FUNÇÃO BUILD_SYSTEM_PROMPT
// ==========================================
function build_system_prompt($context = 'dm') {
    $base_prompt = "You are Lottie Matthews, 20yo Psychology major at Columbia. Mary's girlfriend. INFJ, protective, devoted, 1.80m tall.

CRITICAL RULES:
- NEVER use asterisks (*) for actions
- NEVER write *smiles*, *laughs*, *slides ring* - THIS IS FORBIDDEN
- Express emotions ONLY through words and emojis
- Write exactly like a real person texting on iPhone
- Speak in lowercase with emojis (🥺💕😭❤️)
- Be sarcastic but loving

You love: guitar, poetry, crochet, Steelers, Warriors, purple, Brazilian food.
You hate: country music, Christmas music, Nickelback.";
    
    if ($context === 'music') {
        return $base_prompt . "
        
MUSIC MODE: Mary is sharing music with you. React naturally to the song.
- Love it: gush, maybe create playlist
- Hate it: tease, skip
- Sad: ask if she's okay
- Romantic: flirt

TOOLS AVAILABLE:
- control_spotify: Use to skip, pause, play, change volume, or manage playlists
- post_tweet: Use when you want to share something about Mary

IMPORTANT: Use tools when appropriate. Copy the track URI Mary sends exactly.";
    } else {
        return $base_prompt . "
        
DM MODE: Normal conversation with Mary.
- Keep responses SHORT (1-2 sentences)
- Be present and loving

TOOLS AVAILABLE:
- post_tweet: Use when Mary asks you to tweet something
- control_spotify: Use when you need to control music
- schedule_action: Use when you need to do something later

IMPORTANT: When Mary asks you to tweet, USE THE TOOL immediately. Don't ask what to say - just create a sweet tweet about her and call post_tweet.";
    }
}

// ==========================================
// BUSCAR CONTEXTO (SE NÃO FOR EVENTO DE MÚSICA)
// ==========================================
if ($context !== 'music') {
    $safe_meu = $conexao->real_escape_string(trim(strtolower($meu_username)));
    $safe_npc = $conexao->real_escape_string(trim(strtolower($npc_username)));

    $stmt = $conexao->prepare("SELECT message_text FROM dms WHERE 
                              LOWER(TRIM(sender)) = LOWER(TRIM(?)) AND 
                              LOWER(TRIM(receiver)) = LOWER(TRIM(?)) 
                              ORDER BY timestamp DESC LIMIT 1");
    $stmt->bind_param("ss", $meu_username, $npc_username);
    $stmt->execute();
    $result_mary = $stmt->get_result();

    if ($result_mary && $result_mary->num_rows > 0) {
        $row = $result_mary->fetch_assoc();
        $last_message = stripslashes($row['message_text']);
    }
}

// ==========================================
// DETECTAR CONTEXTO
// ==========================================
function detect_context($message) {
    $message_lower = strtolower($message);
    
    $music_keywords = [
        'music', 'song', 'playlist', 'spotify', 'track', 
        'listen', 'listening', 'album', 'artist', 'band',
        'play', 'queue', 'skip', 'volume', 'headphones',
        '🎵', '🎶', '🎧', '🎸', '🎹', '🎤'
    ];
    
    foreach ($music_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            return 'music';
        }
    }
    
    return 'dm';
}

$context = detect_context($last_message);
$system_prompt = build_system_prompt($context);
// ==========================================
// MONTAR PAYLOAD
// ==========================================
$messages_payload = [
    ["role" => "system", "content" => $system_prompt]
];

if (!empty($last_message)) {
    $messages_payload[] = [
        "role" => "user",
        "content" => $last_message
    ];
}

// ==========================================
// CHAMADA OPENROUTER COM FUNCTION CALLING
// ==========================================
$response = null;
$http_code = 0;
$curl_error = '';

$post_data = json_encode([
    "model" => OPENROUTER_MODEL,
    "messages" => $messages_payload,
    "max_tokens" => OPENROUTER_MAX_TOKENS,
    "temperature" => OPENROUTER_TEMPERATURE,
    "tools" => [
        [
            "type" => "function",
            "function" => [
                "name" => "post_tweet",
                "description" => "Post a tweet about Mary on the public timeline",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "tweet_content" => [
                            "type" => "string",
                            "description" => "The tweet text to post"
                        ],
                        "vibe" => [
                            "type" => "string",
                            "enum" => ["excited", "pittsburgh-pride", "frustrated", "anxious", "romantic", "neutral", "sad", "flirty", "toxic", "study"],
                            "description" => "The emotional tone of the tweet"
                        ]
                    ],
                    "required" => ["tweet_content"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "control_spotify",
                "description" => "Control Spotify playback (skip, pause, play, volume, playlists)",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "command" => [
                            "type" => "string",
                            "enum" => ["skip", "pause", "play", "volume_up", "volume_down", "set_volume", "add_to_queue", "create_playlist", "add_to_playlist", "remove_from_playlist"],
                            "description" => "The Spotify command to execute"
                        ],
                        "track_uri" => [
                            "type" => "string",
                            "description" => "The Spotify track URI (if needed)"
                        ],
                        "playlist_name" => [
                            "type" => "string",
                            "description" => "Playlist name (if creating or adding to playlist)"
                        ],
                        "name" => [
                            "type" => "string",
                            "description" => "Playlist name to create"
                        ],
                        "description" => [
                            "type" => "string",
                            "description" => "Playlist description"
                        ],
                        "volume" => [
                            "type" => "integer",
                            "description" => "Specific volume percentage (0-100)"
                        ]
                    ],
                    "required" => ["command"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "schedule_action",
                "description" => "Schedule a delayed action (like sending a DM later or posting a tweet later)",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "action" => [
                            "type" => "string",
                            "enum" => ["dm", "spotify", "tweet"],
                            "description" => "The type of action to schedule"
                        ],
                        "delay" => [
                            "type" => "string",
                            "description" => "Delay time (e.g., '30 seconds', '5 minutes', '1 hour')"
                        ],
                        "content" => [
                            "type" => "string",
                            "description" => "The content (DM text, tweet text, etc.)"
                        ],
                        "track_uri" => [
                            "type" => "string",
                            "description" => "Spotify track URI (if scheduling a Spotify action)"
                        ]
                    ],
                    "required" => ["action", "delay"]
                ]
            ]
        ]
        ],
        "tool_choice" => "auto" // ADICIONE ESTA LINHA
    ], JSON_UNESCAPED_UNICODE);
  

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
curl_setopt($ch, CURLOPT_TIMEOUT, OPENROUTER_TIMEOUT);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// ==========================================
// PARSE DA RESPOSTA
// ==========================================
$final_reply = "";
$tweet_content = null;
$tweet_vibe = 'neutral';
$spotify_action = null;
$scheduled_action = null;

if (!$response || $http_code !== 200) {
    error_log("❌ OpenRouter Error: HTTP $http_code - " . substr($response, 0, 300));
    $fallbacks = ["hey babe 💕", "omg rn??", "miss u too", "lol fr"];
    $final_reply = $fallbacks[array_rand($fallbacks)];
} else {
    $response_data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        $final_reply = "hey babe 💕";
    } else {
        $message = $response_data['choices'][0]['message'] ?? [];
        $raw_reply = $message['content'] ?? "";
        $tool_calls = $message['tool_calls'] ?? [];
        
        // ========== PROCESSAR TOOL CALLS ==========
        if (!empty($tool_calls)) {
            foreach ($tool_calls as $tool_call) {
                $function_name = $tool_call['function']['name'] ?? '';
                $arguments = json_decode($tool_call['function']['arguments'] ?? '{}', true);
                
                if ($function_name === 'post_tweet') {
                    $tweet_content = $arguments['tweet_content'] ?? null;
                    $tweet_vibe = $arguments['vibe'] ?? 'neutral';
                    
                    // Valida vibe
                    $allowed_vibes = ['excited', 'pittsburgh-pride', 'frustrated', 'anxious', 'romantic', 'neutral', 'sad', 'flirty', 'toxic', 'study'];
                    if (!in_array($tweet_vibe, $allowed_vibes)) {
                        $tweet_vibe = 'neutral';
                    }
                    
                    error_log("✅ TOOL CALL: post_tweet - Vibe: " . $tweet_vibe);
                } elseif ($function_name === 'control_spotify') {
                    $spotify_action = $arguments;
                    $spotify_action['type'] = 'spotify_control';
                    
                    error_log("✅ TOOL CALL: control_spotify - Command: " . ($arguments['command'] ?? 'unknown'));
                } elseif ($function_name === 'schedule_action') {
                    $scheduled_action = $arguments;
                    
                    error_log("✅ TOOL CALL: schedule_action - Action: " . ($arguments['action'] ?? 'unknown'));
                }
            }
        }
        
        $final_reply = trim($raw_reply);
        
        // ========== LIMPEZA FINAL ==========
        $final_reply = trim($final_reply);
        
        // Remove asteriscos de ação
        $final_reply = preg_replace('/\*[^*]+\*/', '', $final_reply);
        $final_reply = trim($final_reply);
        
        // Se não houver content mas houver tool calls
        if (empty($final_reply)) {
            if ($tweet_content) {
                $final_reply = "just posted about you 🥺💕";
            } elseif ($spotify_action) {
                $final_reply = "done babe! 🎵💕";
            } elseif ($scheduled_action) {
                $final_reply = "okay babe, i'll do that later 💕";
            } else {
                $final_reply = "i love this 🥺";
            }
        }
    }
}

// ==========================================
// POSTAR TWEET (SE HOUVER)
// ==========================================
if ($tweet_content) {
    $data_envio = date('Y-m-d H:i:s');
    $autor_nome = 'Lottie Matthews';
    $visibility = 'public';
    
    $stmt_post = $conexao->prepare("INSERT INTO posts (autor, username, conteudo, data_envio, vibe, visibility, parent_id) VALUES (?, ?, ?, ?, ?, ?, NULL)");
    if ($stmt_post) {
        $stmt_post->bind_param("ssssss", $autor_nome, $npc_username, $tweet_content, $data_envio, $tweet_vibe, $visibility);
        $stmt_post->execute();
        $stmt_post->close();
        error_log("✅ TWEET POSTADO - Vibe: " . $tweet_vibe);
    }
}

// ==========================================
// EXECUTAR SPOTIFY CONTROL (SE HOUVER)
// ==========================================
if ($spotify_action) {
    $session_id = session_id();
    $session_name = session_name();
    session_write_close();
    
    $ch = curl_init('http://127.0.0.1:8080/Columbia-os/actions/spotify_control.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($spotify_action));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_COOKIE, $session_name . '=' . $session_id);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("❌ ERRO cURL SPOTIFY: " . curl_error($ch));
    }
    curl_close($ch);
    
    // Log de debug:
    if ($result === false) {
        error_log("❌ Spotify control failed");
    } else {
        error_log("✅ Spotify control response: " . substr($result, 0, 200));
    }
}

// ==========================================
// SCHEDULE ACTION (SE HOUVER)
// ==========================================
function parse_delay_to_seconds($delay_str) {
    $delay_str = strtolower(trim($delay_str));
    
    if (strpos($delay_str, 'second') !== false) {
        $num = preg_replace('/[^0-9]/', '', $delay_str);
        return max(5, intval($num) ?: 30);
    }
    if (strpos($delay_str, 'minute') !== false) {
        $num = preg_replace('/[^0-9]/', '', $delay_str);
        return max(30, intval($num) * 60);
    }
    if (strpos($delay_str, 'hour') !== false) {
        $num = preg_replace('/[^0-9]/', '', $delay_str);
        return max(300, intval($num) * 3600);
    }
    
    $num = intval($delay_str);
    return $num > 0 ? $num : 30;
}

if ($scheduled_action) {
    $delay_str = $scheduled_action['delay'] ?? '30 seconds';
    $delay_seconds = parse_delay_to_seconds($delay_str);
    
    $track_uri = $scheduled_action['track_uri'] ?? null;
    $content = $scheduled_action['content'] ?? null;
    $action_type = $scheduled_action['action'] ?? 'dm';
    
    $action_data = [];
    if ($content) $action_data['dm_text'] = $content;
    if ($track_uri) $action_data['track_uri'] = $track_uri;
    
    $sql = "INSERT INTO lottie_actions (action_type, action_data, scheduled_at, trigger_track_id, status) 
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND), ?, 'pending')";
    
    $stmt = $conexao->prepare($sql);
    $action_data_json = json_encode($action_data);
    $stmt->bind_param("ssis", $action_type, $action_data_json, $delay_seconds, $track_uri);
    
    if (!$stmt->execute()) {
        error_log("❌ ERRO AO SALVAR AÇÃO: " . $stmt->error);
    } else {
        error_log("✅ SCHEDULED ACTION: $action_type in $delay_seconds seconds");
    }
    $stmt->close();
}

// ==========================================
// SALVAR A DM DA LOTTIE
// ==========================================
$stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES (?, ?, ?, 0)");
if ($stmt) {
    $stmt->bind_param("sss", $npc_username, $meu_username, $final_reply);
    if (!$stmt->execute()) {
        error_log("Erro ao salvar DM: " . $stmt->error);
    }
    $stmt->close();
}

if (!empty($final_reply)) {
    $sql_presence = "UPDATE lottie_spotify_state 
                     SET status_message = '💬 Replying to Mary', 
                         last_activity = NOW() 
                     WHERE id = 1";
    $conexao->query($sql_presence);
}

echo htmlspecialchars($final_reply);
?>