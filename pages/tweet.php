<?php
session_start();
include(__DIR__ . '/../includes/config.php');
include_once(__DIR__ . '/../includes/conexao.php');

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$post_id = intval($_GET['id']);

$sql_post = "SELECT * FROM posts WHERE id = $post_id LIMIT 1";
$result_post = $conexao->query($sql_post);

if ($result_post->num_rows == 0) { 
    header("Location: ../index.php"); 
    exit(); 
}
$post = $result_post->fetch_assoc();

$autor_username = $post['username'];
$sql_autor = "SELECT avatar FROM perfis WHERE username = '$autor_username' LIMIT 1";
$result_autor = $conexao->query($sql_autor);
$avatar_autor = ($result_autor->num_rows > 0) ? $result_autor->fetch_assoc()['avatar'] : '👤';
$avatar_html = (strpos($avatar_autor, 'http') === 0) ? '<img src="'.htmlspecialchars($avatar_autor).'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">' : htmlspecialchars($avatar_autor);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post by @<?php echo htmlspecialchars($post['username']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dynamic_themes.css?v=<?php echo time(); ?>"> 
    <style>
        .tweet-action-bar { display: flex; justify-content: space-around; padding: 10px 0; margin-top: 10px; border-top: 1px solid #2f3336; border-bottom: 1px solid #2f3336; }
        .action-btn { display: flex; align-items: center; gap: 8px; color: #71767b; cursor: pointer; transition: 0.2s; font-size: 0.95rem; }
        .action-btn svg { width: 22.5px; height: 22.5px; fill: currentColor; }
        .action-btn.active.like { color: #f91880; }
        .action-btn.active.repost { color: #00ba7c; }
        .action-btn.active.bookmark { color: #1d9bf0; }
        .tweet { display: flex; gap: 12px; padding: 12px 15px; border-bottom: 1px solid #2f3336; }
        .tweet .avatar { width: 48px; height: 48px; flex-shrink: 0; }
        .tweet .avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .tweet-content { flex: 1; min-width: 0; }
        .tweet-header { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; margin-bottom: 4px; }
        .tweet-header strong { color: #e7e9ea; font-size: 0.95rem; }
        .tweet-header span { color: #71767b; font-size: 0.9rem; }
        .tweet-content p { color: #e7e9ea; font-size: 1.1rem; margin: 8px 0 12px 0; word-wrap: break-word; white-space: pre-wrap; }
        .tweet-actions { display: flex; justify-content: space-between; max-width: 425px; margin-top: 8px; }
        .action-btn { display: flex; align-items: center; color: #71767b; cursor: pointer; transition: 0.2s; font-size: 0.85rem; gap: 4px; }
        .action-btn svg { width: 18.75px; height: 18.75px; fill: currentColor; padding: 8px; border-radius: 50%; box-sizing: content-box; }
        .action-btn:hover { color: #1d9bf0; }
        .action-btn:hover svg { background-color: rgba(29, 155, 240, 0.1); }
        .action-btn.like:hover { color: #f91880; }
        .action-btn.like:hover svg { background-color: rgba(249, 24, 128, 0.1); }
        .action-btn.like.active { color: #f91880; }
        .action-btn.like.active svg { fill: #f91880; }
        .action-btn.repost:hover { color: #00ba7c; }
        .action-btn.repost:hover svg { background-color: rgba(0, 186, 124, 0.1); }
        .action-btn.repost.active { color: #00ba7c; }
        .action-btn.repost.active svg { fill: #00ba7c; }
        .action-btn.bookmark.active { color: #1d9bf0; }
        .action-btn.bookmark.active svg { fill: #1d9bf0; }
        .reply-input-area { padding: 15px; border-bottom: 1px solid #2f3336; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .reply-input-area input { flex: 1; background: transparent; border: none; color: #e7e9ea; font-size: 1.1rem; outline: none; min-width: 100px; }
        #btn-post-reply { background-color: #1d9bf0; color: white; border: none; padding: 8px 16px; border-radius: 9999px; font-weight: bold; cursor: pointer; flex-shrink: 0; }
        #btn-post-reply:hover { background-color: #1a8cd8; }
    </style>
</head>
<?php
// Conexão e Lógica de Clima Global
include_once __DIR__ . '/../includes/weather.php';

$current_weather = getDynamicWeather();

$weather_class = '';
if (isset($current_weather['condition'])) {
    $cond = strtolower($current_weather['condition']);
    if (in_array($cond, ['rain', 'drizzle', 'thunderstorm'])) {
        $weather_class = 'weather-rain';
    } elseif ($cond === 'snow') {
        $weather_class = 'weather-snow';
    } elseif (in_array($cond, ['mist', 'fog', 'haze'])) {
        $weather_class = 'weather-fog';
    }
}
?>
<body style="background-color: #000;" class="<?php echo $weather_class; ?>">

<div class="app-wrapper">
    
    <aside class="sidebar">
        <?php 
        if (file_exists(__DIR__ . '/../includes/spotify_check.php')) {
            include __DIR__ . '/../includes/spotify_check.php';
        }
        if (file_exists(__DIR__ . '/../includes/sidebar.php')) {
            include __DIR__ . '/../includes/sidebar.php';
        }
        ?>
    </aside>
    
    <main class="container" style="width: 600px; max-width: 600px; flex-shrink: 0; border-left: 1px solid #2f3336; border-right: 1px solid #2f3336;">
        
        <div style="display: flex; align-items: center; padding: 10px 15px; border-bottom: 1px solid #2f3336; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 10;">
            <a href="../index.php" style="display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 50%; color: #e7e9ea; transition: 0.2s;" onmouseover="this.style.backgroundColor='rgba(239, 243, 244, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                <svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: currentColor;"><path d="M7.414 13l5.293 5.293-1.414 1.414L3.586 12 11.293 4.293l1.414 1.414L7.414 11H21v2H7.414z"></path></svg>
            </a>
            <h1 style="font-size: 1.2rem; margin: 0 0 0 15px;">Post</h1>
        </div>

        <!-- TWEET PRINCIPAL -->
        <div class="tweet" data-post-id="<?php echo $post['id']; ?>">
           
            <a href="perfil.php?user=<?php echo urlencode($post['username']); ?>" class="avatar">
                <?php echo $avatar_html; ?>
            </a>
            <div class="tweet-content">
                <div class="tweet-header">
                    <a href="perfil.php?user=<?php echo urlencode($post['username']); ?>" style="text-decoration: none; color: inherit;">
                        <strong><?php echo htmlspecialchars($post['autor']); ?></strong>
                    </a>
                    <span>@<?php echo htmlspecialchars($post['username']); ?></span>
                    <span>· <?php echo format_twitter_time($post['data_envio'] ?? 'now'); ?></span>
                </div>

                <?php
                $is_deleted = isset($post['is_deleted']) && $post['is_deleted'] == 1;

                $meu_user = $conexao->real_escape_string($_SESSION['username']);
                $sql_ints = "SELECT tipo FROM interacoes WHERE username = '$meu_user' AND post_id = $post_id";
                $res_ints = $conexao->query($sql_ints);
                $interacoes_deste_post = [];
                if ($res_ints && $res_ints->num_rows > 0) {
                    while($int = $res_ints->fetch_assoc()) {
                        $interacoes_deste_post[] = $int['tipo'];
                    }
                }
                $is_liked = in_array('like', $interacoes_deste_post);
                $is_reposted = in_array('repost', $interacoes_deste_post);
                $is_bookmarked = in_array('bookmark', $interacoes_deste_post);

                $path_like = $is_liked ? "M20.884 13.19c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3C7.121 18.31 4.471 15.67 3.119 13.19 1.928 11.01 1.618 8.69 2.222 6.6 2.827 4.5 4.3 2.91 6.275 2.6c1.651-.26 3.368.3 4.798 1.75l.927.94.927-.94c1.43-1.45 3.146-2.01 4.796-1.75 1.975.31 3.448 1.9 4.053 3.99.604 2.09.294 4.41-.892 6.6z" : "M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91zm4.187 7.69c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3c-4.379-2.55-7.029-5.19-8.382-7.67-1.36-2.5-1.41-4.86-.514-6.67.887-1.79 2.647-2.91 4.601-3.01 1.651-.09 3.368.56 4.798 2.01 1.429-1.45 3.146-2.1 4.796-2.01 1.954.1 3.714 1.22 4.601 3.01.896 1.81.846 4.17-.514 6.67z";
                $path_bookmark = $is_bookmarked ? "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5z" : "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z";

                if ($is_deleted) {
                    echo "<p style='color:#71767b;font-style:italic;'>This Tweet has been deleted.</p>";
                } else {
                    echo "<p>" . htmlspecialchars(stripslashes($post['conteudo'])) . "</p>";
                    
                    // Exibe a vibe se existir
                    if (!empty($post['vibe']) && $post['vibe'] != 'neutral') {
                        $vibe_icons = [
                            'flirty' => '✨',
                            'sad' => '🌧️',
                            'toxic' => '🖤',
                            'excited' => '🤩',
                            'pittsburgh-pride' => '🏈',
                            'frustrated' => '😤',
                            'anxious' => '😰',
                            'romantic' => '💖',
                            'tired' => '🥱'
                        ];
                        $icon = $vibe_icons[$post['vibe']] ?? '☁️';
                        echo "<span style='color:#71767b;font-size:0.85rem;display:block;margin-bottom:12px;'>" . $icon . " " . ucfirst($post['vibe']) . "</span>";
                    }

                    echo "<div class='tweet-actions'>";
                    echo "  <div class='action-btn reply'><svg viewBox='0 0 24 24'><path d='M1.751 10c0-4.42 3.584-8 8.005-8h4.366c4.49 0 8.129 3.64 8.129 8.13 0 2.96-1.607 5.68-4.196 7.11l-8.054 4.46v-3.69h-.067c-4.49.1-8.183-3.51-8.183-8.01zm8.005-6c-3.317 0-6.005 2.69-6.005 6 0 3.37 2.77 6.08 6.138 6.01l.351-.01h1.761v2.3l5.087-2.81c1.951-1.08 3.163-3.13 3.163-5.36 0-3.39-2.744-6.13-6.129-6.13H9.756z'></path></svg></div>";
                    echo "  <div class='action-btn repost " . ($is_reposted ? 'active' : '') . "'><svg viewBox='0 0 24 24'><path d='M4.5 3.88l4.432 4.14-1.364 1.46L5.5 7.55V16c0 1.1.896 2 2 2H13v2H7.5c-2.209 0-4-1.79-4-4V7.55L1.432 9.48.068 8.02 4.5 3.88zM16.5 6H11V4h5.5c2.209 0 4 1.79 4 4v8.45l2.068-1.93 1.364 1.46-4.432 4.14-4.432-4.14 1.364-1.46 2.068 1.93V8c0-1.1-.896-2-2-2z'></path></svg></div>";
                    echo "  <div class='action-btn like " . ($is_liked ? 'active' : '') . "'><svg viewBox='0 0 24 24'><path d='{$path_like}'></path></svg></div>";
                    echo "  <div class='action-btn views'><svg viewBox='0 0 24 24'><path d='M8.75 21V3h2v18h-2zM18 21V8.5h2V21h-2zM4 21l.004-10h2L6 21H4zm9.248 0v-7h2v7h-2z'></path></svg></div>";
                    echo "  <div class='action-btn bookmark " . ($is_bookmarked ? 'active' : '') . "'><svg viewBox='0 0 24 24'><path d='{$path_bookmark}'></path></svg></div>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- ÁREA DE REPLY -->
        <div class="reply-input-area">
            <div class="avatar" style="width: 40px; height: 40px; flex-shrink: 0; border-radius: 50%; overflow: hidden; background: #333;">
                <?php
                $meu_user = $_SESSION['username'];
                $sql_meu_avatar = "SELECT avatar FROM perfis WHERE username = '$meu_user' LIMIT 1";
                $res_meu_avatar = $conexao->query($sql_meu_avatar);
                $meu_avatar = ($res_meu_avatar->num_rows > 0) ? $res_meu_avatar->fetch_assoc()['avatar'] : '👤';
                $meu_avatar_html = (strpos($meu_avatar, 'http') === 0) ? '<img src="'.htmlspecialchars($meu_avatar).'" style="width:100%;height:100%;object-fit:cover;">' : htmlspecialchars($meu_avatar);
                echo $meu_avatar_html;
                ?>
            </div>
            <input type="text" id="reply-input-text" placeholder="Post your reply" style="flex:1; background: transparent; border: none; color: #e7e9ea; font-size: 1.1rem; outline: none;">
            <select id="reply-vibe-selector" style="background: #16181c; color: #e7e9ea; border: 1px solid #2f3336; border-radius: 9999px; padding: 4px 12px; font-size: 0.85rem; outline: none; cursor: pointer;">
                <option value="neutral">☁️ Neutral</option>
                <option value="excited">🤩 Excited</option>
                <option value="romantic">💖 Romantic</option>
                <option value="frustrated">😤 Frustrated</option>
                <option value="toxic">🖤 Toxic</option>
                <option value="pittsburgh-pride">🏈 Steelers Pride</option>
            </select>
            <button id="btn-post-reply" style="background-color: #1d9bf0; color: white; border: none; padding: 8px 16px; border-radius: 9999px; font-weight: bold; cursor: pointer; flex-shrink: 0;">Reply</button>
        </div>

        <!-- REPLIES -->
        <div id="replies-area" style="padding-bottom: 60px;">
            <?php
            // CORREÇÃO: Verifica se a coluna parent_id existe
            $sql_check = "SHOW COLUMNS FROM posts LIKE 'parent_id'";
            $res_check = $conexao->query($sql_check);
            $has_parent_id = $res_check->num_rows > 0;

            if ($has_parent_id) {
                $sql_replies = "SELECT * FROM posts WHERE parent_id = $post_id ORDER BY id DESC";
                $result_replies = $conexao->query($sql_replies);

                if ($result_replies->num_rows > 0) {
                    while($reply = $result_replies->fetch_assoc()) {
                        $reply_id = $reply['id'];
                        $reply_username = $reply['username'];
                        
                        $sql_rep_autor = "SELECT avatar FROM perfis WHERE username = '$reply_username' LIMIT 1";
                        $res_rep_autor = $conexao->query($sql_rep_autor);
                        $rep_avatar = ($res_rep_autor->num_rows > 0) ? $res_rep_autor->fetch_assoc()['avatar'] : '👤';
                        $rep_avatar_html = (strpos($rep_avatar, 'http') === 0) ? '<img src="'.htmlspecialchars($rep_avatar).'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">' : htmlspecialchars($rep_avatar);

                        echo '<div class="tweet" data-post-id="' . $reply_id . '">';
                        echo '    <div class="avatar">' . $rep_avatar_html . '</div>';
                        echo '    <div class="tweet-content">';
                        echo '        <div class="tweet-header">';
                        echo '            <strong>' . htmlspecialchars($reply['autor']) . '</strong>';
                        echo '            <span>@' . htmlspecialchars($reply['username']) . '</span>';
                        echo '            <span>· ' . format_twitter_time($reply['data_envio'] ?? 'now') . '</span>';
                        echo '        </div>';
                        echo '        <p>' . htmlspecialchars(stripslashes($reply['conteudo'])) . '</p>';
                        echo '    </div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p style="padding: 40px 20px; text-align: center; color: #71767b; font-size: 0.95rem;">No replies yet. Be the first to reply!</p>';
                }
            } else {
                echo '<p style="padding: 40px 20px; text-align: center; color: #71767b; font-size: 0.95rem;">Reply system not available yet.</p>';
            }
            ?>
        </div>

    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var replyInput = document.getElementById('reply-input-text');
    var replyBtn = document.getElementById('btn-post-reply');
    var vibeSelector = document.getElementById('reply-vibe-selector');

    if (replyBtn && replyInput) {
        replyBtn.addEventListener('click', function() {
            var conteudo = replyInput.value.trim();
            var vibe = vibeSelector ? vibeSelector.value : 'neutral';
            var postId = <?php echo $post_id; ?>;

            if (conteudo !== '') {
                fetch('../actions/salvar_reply.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'conteudo=' + encodeURIComponent(conteudo) + '&parent_id=' + postId + '&vibe=' + encodeURIComponent(vibe)
                })
                .then(response => response.text())
                .then(data => {
                    if(data.trim() === 'Success') {
                        location.reload();
                    } else {
                        alert('Erro ao postar: ' + data);
                    }
                })
                .catch(error => {
                    alert('Erro de conexão: ' + error);
                });
            }
        });

        // Enter para enviar
        replyInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                replyBtn.click();
            }
        });
    }
});
</script>
</body>
</html>