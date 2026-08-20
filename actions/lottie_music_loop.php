<?php
/**
 * Columbia OS - Lottie's Music Loop
 * Simulates Lottie listening to music based on her emotional state
 * This should be run as a cron job every 5 minutes
 */
set_time_limit(0);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/conexao.php';

try {
    // 1. Get Lottie's current emotional state
    $stmt = $conexao->prepare("SELECT emotional_state, reason FROM lottie_state ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $state = $result->fetch_assoc();
    $stmt->close();

    $mood = $state['emotional_state'] ?? 'neutral';
    $reason = $state['reason'] ?? '';

    // 2. Generate music recommendations based on mood
    $playlist = generateMoodPlaylist($mood);

    // 3. Pick a random track from the mood playlist
    if (!empty($playlist)) {
        $selected = $playlist[array_rand($playlist)];
        
        // 4. Update Lottie's "Currently Listening" state
        $stmt = $conexao->prepare("INSERT INTO lottie_spotify_state (track_name, artist_name, mood, listened_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $selected['track'], $selected['artist'], $mood);
        $stmt->execute();
        $stmt->close();

        // 5. Check if this should trigger a reaction from Mary
        // If Lottie is listening to sad music, she might send a "worried" DM to Mary
        if ($mood === 'sad' || $mood === 'angry') {
            sendEmotionalDM($mood, $selected['track'], $selected['artist']);
        }

        echo json_encode([
            'success' => true,
            'mood' => $mood,
            'track' => $selected['track'],
            'artist' => $selected['artist'],
            'message' => "Lottie is listening to '{$selected['track']}' by {$selected['artist']} ({$mood} mood)"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No tracks found for mood: ' . $mood]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// ==========================================
// MOOD PLAYLIST GENERATOR
// ==========================================
function generateMoodPlaylist($mood) {
    $playlists = [
        'happy' => [
            ['track' => 'Good as Hell', 'artist' => 'Lizzo'],
            ['track' => 'Levitating', 'artist' => 'Dua Lipa'],
            ['track' => 'Uptown Funk', 'artist' => 'Mark Ronson ft. Bruno Mars'],
            ['track' => 'Happy', 'artist' => 'Pharrell Williams'],
            ['track' => 'Dance Monkey', 'artist' => 'Tones and I'],
            ['track' => 'Shake It Off', 'artist' => 'Taylor Swift'],
            ['track' => 'Can\'t Stop the Feeling', 'artist' => 'Justin Timberlake']
        ],
        'sad' => [
            ['track' => 'Someone Like You', 'artist' => 'Adele'],
            ['track' => 'Fix You', 'artist' => 'Coldplay'],
            ['track' => 'The Night We Met', 'artist' => 'Lord Huron'],
            ['track' => 'All I Want', 'artist' => 'Kodaline'],
            ['track' => 'Skinny Love', 'artist' => 'Bon Iver'],
            ['track' => 'Holocene', 'artist' => 'Bon Iver'],
            ['track' => 'I Found', 'artist' => 'Amber Run']
        ],
        'angry' => [
            ['track' => 'Killing in the Name', 'artist' => 'Rage Against the Machine'],
            ['track' => 'Break Stuff', 'artist' => 'Limp Bizkit'],
            ['track' => 'Bodies', 'artist' => 'Drowning Pool'],
            ['track' => 'The Pretender', 'artist' => 'Foo Fighters'],
            ['track' => 'Bulls on Parade', 'artist' => 'Rage Against the Machine'],
            ['track' => 'Given Up', 'artist' => 'Linkin Park']
        ],
        'romantic' => [
            ['track' => 'Lover', 'artist' => 'Taylor Swift'],
            ['track' => 'Perfect', 'artist' => 'Ed Sheeran'],
            ['track' => 'All of Me', 'artist' => 'John Legend'],
            ['track' => 'Stay With Me', 'artist' => 'Sam Smith'],
            ['track' => 'Thinking Out Loud', 'artist' => 'Ed Sheeran'],
            ['track' => 'Just the Way You Are', 'artist' => 'Bruno Mars']
        ],
        'neutral' => [
            ['track' => 'Blinding Lights', 'artist' => 'The Weeknd'],
            ['track' => 'Heat Waves', 'artist' => 'Glass Animals'],
            ['track' => 'Circles', 'artist' => 'Post Malone'],
            ['track' => 'Watermelon Sugar', 'artist' => 'Harry Styles'],
            ['track' => 'As It Was', 'artist' => 'Harry Styles']
        ]
    ];

    return $playlists[$mood] ?? $playlists['neutral'];
}

// ==========================================
// SEND EMOTIONAL DM
// ==========================================
function sendEmotionalDM($mood, $track, $artist) {
    global $conexao;
    
    $messages = [
        'sad' => "I'm listening to \"{$track}\" by {$artist} and it's making me think about life... Are you okay? Let's talk. 🥺",
        'angry' => "Ugh, I'm so annoyed. Listening to \"{$track}\" by {$artist} and just... some people. 😤",
        'romantic' => "\"{$track}\" by {$artist} is making me think about you... Just so you know. 💕"
    ];

    $message = $messages[$mood] ?? "I'm listening to \"{$track}\" by {$artist}. Come listen with me? 🎵";

    // Send DM from Lottie to Mary
    $stmt = $conexao->prepare("INSERT INTO dms (sender, receiver, message_text, is_read) VALUES ('lottiematthews', 'mary', ?, 0)");
    $stmt->bind_param("s", $message);
    $stmt->execute();
    $stmt->close();
}
?>