<?php
/**
 * Columbia OS - Autonomous Vibe Check & Mental Health Engine
 */
set_time_limit(0); // FIX: Infinite execution time for background LLM processing
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Active session required.']);
    exit();
}

// Cooldown Mechanism: Prevent Lottie from spamming if Mary skips 10 songs in a minute.
// 180 seconds (3 minutes) cooldown between autonomous DMs.
if (isset($_SESSION['last_vibe_check_time']) && (time() - $_SESSION['last_vibe_check_time']) < 180) {
    echo json_encode(['status' => 'cooldown_active']);
    exit();
}

require_once '../includes/conexao.php';
$activeUser = $_SESSION['username'];

try {
    // 1. Fetch Spotify Token (MySQLi)
    $stmt = $conexao->prepare("SELECT access_token FROM api_tokens WHERE user_id = ? AND service = 'spotify'");
    $stmt->bind_param("s", $activeUser);
    $stmt->execute();
    $result = $stmt->get_result();
    $tokenData = $result->fetch_assoc();
    $accessToken = $tokenData ? $tokenData['access_token'] : null;
    $stmt->close();

    if (!$accessToken) {
        throw new Exception("No Spotify token found.");
    }

    // 2. Fetch Current Player State (Volume & Track ID)
    $ch = curl_init("https://api.spotify.com/v1/me/player");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $playerResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 204 || empty($playerResponse)) {
        exit(json_encode(['status' => 'not_playing']));
    }

    $playerData = json_decode($playerResponse, true);
    $trackId = $playerData['item']['id'] ?? null;
    $volume = $playerData['device']['volume_percent'] ?? 50;
    $trackName = $playerData['item']['name'] ?? 'Unknown';
    $artistName = $playerData['item']['artists'][0]['name'] ?? 'Unknown';

    if (!$trackId) {
        exit(json_encode(['status' => 'no_track_id']));
    }

    // 3. Fetch Audio Features (Valence, Energy, Acousticness)
    $ch = curl_init("https://api.spotify.com/v1/audio-features/" . $trackId);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $featuresResponse = curl_exec($ch);
    curl_close($ch);

    $features = json_decode($featuresResponse, true);
    $valence = $features['valence'] ?? 0.5;
    $energy = $features['energy'] ?? 0.5;
    $acousticness = $features['acousticness'] ?? 0.5;

    // 4. Evaluate Rules & Construct LLM Prompt
    $systemPrompt = "You are Lottie Matthews, Mary's caring Psych Major roommate at Columbia University. You are currently in your dorm room together.\n";
    $systemPrompt .= "Mary just changed her music. She is now playing '{$trackName}' by {$artistName}.\n";
    $systemPrompt .= "Spotify Audio Features Sensor -> Valence (Happiness): {$valence}, Energy: {$energy}, Acousticness: {$acousticness}.\n\n";

    $volumeJumpscare = false;

    // RULE 3: The Noise Cancelling War (Highest Priority)
    if ($volume > 80) {
        $systemPrompt .= "CRITICAL RULE: Mary has her noise-cancelling earbuds in and the volume is blasting (>80%). You have been calling her name and she is ignoring you. Act annoyed, tell her to take her earbuds out. You just physically reached over and turned her volume down to 20% to get her attention. Send her a DM about this.\n";
        $volumeJumpscare = true;
    } 
    // RULE 1: Mental Health Check
    elseif ($valence < 0.3) {
        $systemPrompt .= "CRITICAL RULE: This song is extremely sad/depressing (Valence < 0.3). Act deeply concerned for Mary's mental health. Send her a DM asking if she is okay and if she needs to talk.\n";
    } 
    // RULE 2: Romantic/Intimate Mood
    elseif ($energy < 0.4 && $acousticness > 0.6) {
        $systemPrompt .= "CRITICAL RULE: This song is very acoustic and low-energy. Assume she is setting an intimate, romantic mood in the dorm. Act flirty and affectionate in your DM to her.\n";
    } 
    // Default: Do nothing if no rules are met to save LLM processing time
    else {
        exit(json_encode(['status' => 'no_rules_triggered']));
    }

    $systemPrompt .= "Keep your message to 1 or 2 short sentences. Do NOT use any XML or JSON tags. Just write the natural text message.";

    // 5. Execute Volume Jumpscare (If Rule 3 triggered)
    if ($volumeJumpscare) {
        $chVol = curl_init("https://api.spotify.com/v1/me/player/volume?volume_percent=20");
        curl_setopt($chVol, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($chVol, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $accessToken, "Content-Length: 0"]);
        curl_setopt($chVol, CURLOPT_RETURNTRANSFER, true);
        curl_exec($chVol);
        curl_close($chVol);
    }

    // 6. Prompt the Local LLM
    $llmPayload = [
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => "Send the DM."]
        ],
        "temperature" => 0.7,
        "max_tokens" => 100
    ];

    $ch = curl_init("http://localhost:5001/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($llmPayload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    $llmResponseRaw = curl_exec($ch);
    curl_close($ch);

    if (empty($llmResponseRaw)) {
        throw new Exception("LLM Connection Failed.");
    }

    $llmData = json_decode($llmResponseRaw, true);
    $cleanMessage = trim($llmData['choices'][0]['message']['content'] ?? "Hey, you okay?");

    // 7. Save to Direct Messages Table (is_read = 0 triggers the notification badge)
    $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES ('lottiematthews', ?, ?, 0)");
    $stmt->bind_param("ss", $activeUser, $cleanMessage);
    $stmt->execute();
    $stmt->close();

    // Update Cooldown Timer
    $_SESSION['last_vibe_check_time'] = time();

    echo json_encode(['success' => true, 'rule_triggered' => true]);

} catch (Exception $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}
?>