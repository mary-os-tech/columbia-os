<?php
session_start();
include(__DIR__ . '/../includes/config.php');

// Prevent BFCache (Back-Forward Cache)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/conexao.php';

$meu_user = $conexao->real_escape_string($_SESSION['username']);

// Fetch numeric user_id for the bookmarks query
$sql_user = "SELECT id, avatar FROM perfis WHERE username = '$meu_user' LIMIT 1";
$res_user = $conexao->query($sql_user);
$perfil_atual = $res_user ? $res_user->fetch_assoc() : ['id' => 0, 'avatar' => '👤'];
$user_id = (int)$perfil_atual['id'];

// Fetch ALL your interactions at once to prevent N+1 Queries
$sql_ints = "SELECT post_id, tipo FROM interacoes WHERE username = '$meu_user'";
$res_ints = $conexao->query($sql_ints);
$minhas_interacoes = [];
if ($res_ints && $res_ints->num_rows > 0) {
    while($int = $res_ints->fetch_assoc()) {
        $minhas_interacoes[$int['post_id']][] = $int['tipo'];
    }
}

// Helper function to render tweets (Identical to index.php)
function renderizar_tweet($row, $perfil_atual, $minhas_interacoes) {
    $avatar_db = $row['avatar'] ?? '👤';
    $avatar_html = (strpos($avatar_db, 'http') === 0) ? "<img src='" . htmlspecialchars($avatar_db) . "' style='width: 100%; height: 100%; object-fit: cover; border-radius: 50%;'>" : htmlspecialchars($avatar_db);

    $link_perfil = "perfil.php?user=" . urlencode($row["username"]);
    $link_tweet = "tweet.php?id=" . $row["id"];
    $texto_limpo = htmlspecialchars(stripslashes($row["conteudo"]));
    $vibe_class = isset($row['vibe']) ? 'vibe-' . htmlspecialchars($row['vibe']) : 'vibe-neutral';

    $likes_f = ($row['id'] * 47) % 950 + 12;
    $reposts_f = ($row['id'] * 13) % 150 + 1;
    $views_f = ($row['id'] * 97) % 90 + 5;
    $data_formatada = date('M d', strtotime($row['data_criacao'] ?? 'now'));

    $interacoes_deste_post = $minhas_interacoes[$row['id']] ?? [];
    $is_liked = in_array('like', $interacoes_deste_post);
    $is_reposted = in_array('repost', $interacoes_deste_post);
    $is_bookmarked = in_array('bookmark', $interacoes_deste_post);

    $path_like = $is_liked ? "M20.884 13.19c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3C7.121 18.31 4.471 15.67 3.119 13.19 1.928 11.01 1.618 8.69 2.222 6.6 2.827 4.5 4.3 2.91 6.275 2.6c1.651-.26 3.368.3 4.798 1.75l.927.94.927-.94c1.43-1.45 3.146-2.01 4.796-1.75 1.975.31 3.448 1.9 4.053 3.99.604 2.09.294 4.41-.892 6.6z" : "M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91zm4.187 7.69c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3c-4.379-2.55-7.029-5.19-8.382-7.67-1.36-2.5-1.41-4.86-.514-6.67.887-1.79 2.647-2.91 4.601-3.01 1.651-.09 3.368.56 4.798 2.01 1.429-1.45 3.146-2.1 4.796-2.01 1.954.1 3.714 1.22 4.601 3.01.896 1.81.846 4.17-.514 6.67z";
    $path_bookmark = $is_bookmarked ? "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5z" : "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z";

    $context_label = !empty($row['context_label']) ? htmlspecialchars($row['context_label']) : '';
    $is_deleted = isset($row['is_deleted']) && $row['is_deleted'] == 1;

    echo "
    <div class='tweet {$vibe_class}' data-post-id='{$row['id']}'>
        <a href='{$link_perfil}' class='avatar'>{$avatar_html}</a>
        <div class='tweet-content'>
            <div class='tweet-header' style='display: flex; justify-content: space-between; align-items: flex-start;'>
                <div style='display: flex; flex-direction: column;'>
                    <a href='{$link_perfil}' style='text-decoration: none; color: inherit;'>
                        <strong>" . htmlspecialchars($row["autor"]) . "</strong> <span style='color: #71767b;'>@" . htmlspecialchars($row["username"]) . " · {$data_formatada}</span>
                    </a>
                    " . ($context_label ? "<span style='color: #71767b; font-size: 0.8rem; margin-top: 2px;'>{$context_label}</span>" : "") . "
                </div>
                
                <div class='tweet-menu-container' style='position: relative;'>
                    <div class='btn-tweet-menu' style='cursor: pointer; color: #71767b; padding: 5px; border-radius: 50%; display:flex; align-items:center; justify-content:center;'>
                        <svg viewBox='0 0 24 24' style='width: 18px; height: 18px; fill: currentColor;'><path d='M3 12c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm9 2c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm7 0c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z'></path></svg>
                    </div>
                    <div class='tweet-dropdown' style='display: none; position: absolute; right: 0; top: 25px; background-color: #000; border: 1px solid #2f3336; border-radius: 12px; box-shadow: 0 0 15px rgba(255,255,255,0.1); z-index: 100; width: 250px; font-weight: bold;'>
                        <div class='dropdown-item' style='padding: 12px 16px; color: #e7e9ea; cursor: pointer;'><span style='margin-right: 12px;'>👤</span> Follow @" . htmlspecialchars($row["username"]) . "</div>
                        <div class='dropdown-item' style='padding: 12px 16px; color: #e7e9ea; cursor: pointer;'><span style='margin-right: 12px;'>🔇</span> Mute @" . htmlspecialchars($row["username"]) . "</div>
                        <div class='dropdown-item' style='padding: 12px 16px; color: #e7e9ea; cursor: pointer;'><span style='margin-right: 12px;'>🚫</span> Block @" . htmlspecialchars($row["username"]) . "</div>
                        <div class='dropdown-item' style='padding: 12px 16px; color: #f4212e; cursor: pointer;'><span style='margin-right: 12px;'>🚩</span> Report post</div>
                    </div>
                </div>
            </div>";

            if ($is_deleted) {
                echo "
                <div style='background-color: rgba(255,255,255,0.03); border: 1px solid #2f3336; border-radius: 12px; padding: 12px; margin-top: 8px; margin-bottom: 8px; color: #71767b; font-style: italic;'>
                    This Tweet has been deleted.
                </div>";
            } else {
                echo "
                <a href='{$link_tweet}' style='text-decoration: none; color: inherit; display: block; margin-bottom: 5px;'>
                    <p>{$texto_limpo}</p>
                </a>
                <div class='tweet-actions'>
                    <div class='action-btn reply'><svg viewBox='0 0 24 24'><path d='M1.751 10c0-4.42 3.584-8 8.005-8h4.366c4.49 0 8.129 3.64 8.129 8.13 0 2.96-1.607 5.68-4.196 7.11l-8.054 4.46v-3.69h-.067c-4.49.1-8.183-3.51-8.183-8.01zm8.005-6c-3.317 0-6.005 2.69-6.005 6 0 3.37 2.77 6.08 6.138 6.01l.351-.01h1.761v2.3l5.087-2.81c1.951-1.08 3.163-3.13 3.163-5.36 0-3.39-2.744-6.13-6.129-6.13H9.756z'></path></svg></div>
                    <div class='action-btn repost " . ($is_reposted ? 'active' : '') . "'><svg viewBox='0 0 24 24'><path d='M4.5 3.88l4.432 4.14-1.364 1.46L5.5 7.55V16c0 1.1.896 2 2 2H13v2H7.5c-2.209 0-4-1.79-4-4V7.55L1.432 9.48.068 8.02 4.5 3.88zM16.5 6H11V4h5.5c2.209 0 4 1.79 4 4v8.45l2.068-1.93 1.364 1.46-4.432 4.14-4.432-4.14 1.364-1.46 2.068 1.93V8c0-1.1-.896-2-2-2z'></path></svg> <span class='action-num'>{$reposts_f}</span></div>
                    <div class='action-btn like " . ($is_liked ? 'active' : '') . "'><svg viewBox='0 0 24 24'><path d='{$path_like}'></path></svg> <span class='action-num'>{$likes_f}</span></div>
                    <div class='action-btn views'><svg viewBox='0 0 24 24'><path d='M8.75 21V3h2v18h-2zM18 21V8.5h2V21h-2zM4 21l.004-10h2L6 21H4zm9.248 0v-7h2v7h-2z'></path></svg> <span class='action-num'>{$views_f}K</span></div>
                    <div style='display: flex; gap: 0;'>
                        <div class='action-btn bookmark " . ($is_bookmarked ? 'active' : '') . "'><svg viewBox='0 0 24 24'><path d='{$path_bookmark}'></path></svg></div>
                    </div>
                </div>";
            }

            echo "
        </div>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarks</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dynamic_themes.css?v=<?php echo time(); ?>">
</head>
<?php
// Conexão e Lógica de Clima Global
include_once '../includes/conexao.php';
include_once '../includes/weather.php';

// Garante que a variável do clima é gerada chamando a função diretamente!
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
        <?php include '../includes/spotify_check.php';
        include '../includes/sidebar.php'; ?>

        <div class="container" style="border-left: none; border-right: 1px solid #2f3336; flex-grow: 1; max-width: 600px;">
            
        <header style="padding: 10px 15px; border-bottom: 1px solid #2f3336; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 10;">
                <h1 style="font-size: 1.2rem; margin: 0; color: #e7e9ea;">Bookmarks 🔖</h1>
                <span style="color: #71767b; font-size: 0.85rem;">@<?php echo htmlspecialchars($meu_user); ?></span>
            </header>

            <?php
            // Fetch user's folders for the tabs
            $sql_folders = "SELECT id, folder_name FROM bookmark_folders WHERE user_id = $user_id ORDER BY created_at ASC";
            $res_folders = $conexao->query($sql_folders);
            ?>

            <!-- Horizontal Folder Tabs -->
            <div class="perfil-abas" style="margin-top: 0; border-bottom: 1px solid #2f3336; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); position: sticky; top: 53px; z-index: 9; overflow-x: auto; white-space: nowrap; display: flex;">
                <a href="bookmarks.php" class="aba-item <?php echo !isset($_GET['folder_id']) ? 'ativa' : ''; ?>" style="text-decoration: none; color: inherit; display: inline-block;">All Bookmarks</a>
                
                <?php if ($res_folders && $res_folders->num_rows > 0): ?>
                    <?php while($f = $res_folders->fetch_assoc()): ?>
                        <a href="bookmarks.php?folder_id=<?php echo $f['id']; ?>" class="aba-item <?php echo (isset($_GET['folder_id']) && $_GET['folder_id'] == $f['id']) ? 'ativa' : ''; ?>" style="text-decoration: none; color: inherit; display: inline-block;">
                            <?php echo htmlspecialchars($f['folder_name']); ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <main id="timeline">
                <div class="feed-section">
                    <?php
                    // Dynamic SQL Filter based on selected folder
                    $folder_filter = "";
                    if (isset($_GET['folder_id'])) {
                        $f_id = (int)$_GET['folder_id'];
                        $folder_filter = " AND bookmarks.folder_id = $f_id ";
                    }

                    // INNER JOIN to fetch only posts bookmarked by the active user (filtered by folder if applicable)
                    $sql_bookmarks = "SELECT posts.*, perfis.avatar 
                                      FROM posts 
                                      INNER JOIN bookmarks ON posts.id = bookmarks.post_id 
                                      LEFT JOIN perfis ON posts.username = perfis.username 
                                      WHERE bookmarks.user_id = $user_id $folder_filter
                                      ORDER BY bookmarks.created_at DESC";
                    
                    $res_bookmarks = $conexao->query($sql_bookmarks);
                    
                    if ($res_bookmarks && $res_bookmarks->num_rows > 0) {
                        while($row = $res_bookmarks->fetch_assoc()) { 
                            renderizar_tweet($row, $perfil_atual, $minhas_interacoes); 
                        }
                    } else {
                        echo '<div style="padding: 40px 20px; text-align: center;">
                                <h2 style="color: #e7e9ea; font-size: 1.8rem; margin-bottom: 10px;">Save Tweets for later</h2>
                                <p style="color: #71767b; font-size: 0.95rem;">Don’t let the good ones fly away! Bookmark Tweets to easily find them again in the future.</p>
                              </div>';
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>
<!-- TOAST NOTIFICATION CONTAINER -->
<div id="bookmark-toast" style="display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background-color: #1d9bf0; color: #fff; padding: 12px 20px; border-radius: 4px; font-size: 0.95rem; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 9999; align-items: center; gap: 15px;">
            <span>Added to your Bookmarks</span>
            <a href="#" id="btn-add-folder" style="color: #fff; text-decoration: underline; font-weight: normal; font-size: 0.9rem;">Add to Folder</a>
        </div>

        <style>
            /* Toast Slide-up Animation */
            @keyframes slideUpFade {
                0% { bottom: 0; opacity: 0; }
                10% { bottom: 30px; opacity: 1; }
                90% { bottom: 30px; opacity: 1; }
                100% { bottom: 0; opacity: 0; }
            }
            .toast-animate {
                display: flex !important;
                animation: slideUpFade 4s ease-in-out forwards;
            }
        </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>
</html>