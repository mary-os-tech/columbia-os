<?php
session_start();
include 'includes/config.php';

// Prevent BFCache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Load database BEFORE anything else!
include 'includes/conexao.php'; 

// Universe locked in Columbia OS
// SECURITY CHECK: Redirect to login if no active session
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once 'includes/weather.php';

// ===== POST LOGIC (FIXED) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conteudo']) && !empty(trim($_POST['conteudo']))) {
    
    $conteudo = $conexao->real_escape_string(trim($_POST['conteudo']));
    $vibe = isset($_POST['vibe']) ? $conexao->real_escape_string($_POST['vibe']) : 'neutral';
    $meu_user = $_SESSION['username'];

    $sql_insert = "INSERT INTO posts (username, autor, conteudo, vibe, data_envio, data_criacao) 
                   VALUES ('$meu_user', '$meu_user', '$conteudo', '$vibe', NOW(), NOW())";
    
    if ($conexao->query($sql_insert)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?postado=ok");
        exit(); 
    } else {
        echo "<p style='color:red;position:fixed;top:0;z-index:99999;background:black;padding:10px;'>Error: " . $conexao->error . "</p>";
    }
}
// ===== END POST LOGIC =====

// Initialize count to prevent undefined variable warnings
$my_username = $conexao->real_escape_string($_SESSION['username']);

// Optimized query handling both 0 and NULL states
$sql_unread = "SELECT COUNT(id) AS unread FROM dms WHERE receiver = '$my_username' AND (is_read = 0 OR is_read IS NULL)";
$res_unread = $conexao->query($sql_unread);

if ($res_unread && $row = $res_unread->fetch_assoc()) {
    $count = (int)$row['unread'];
}

$unread_count = $count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status RPG</title>
        <!-- jQuery - Load FIRST -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js?v=<?php echo time(); ?>"></script>
    
    <style>
        .audience-btn {
            background-color: transparent; color: #1d9bf0; border: 1px solid #333639; border-radius: 9999px;
            padding: 2px 12px; font-size: 0.85rem; font-weight: bold; cursor: pointer; display: inline-flex;
            align-items: center; gap: 4px; margin-bottom: 8px;
        }
        .audience-btn svg { width: 14px; height: 14px; fill: currentColor; }
        
        .reply-restriction {
            color: #1d9bf0; font-size: 0.9rem; font-weight: bold; display: flex; align-items: center;
            gap: 6px; padding-bottom: 12px; border-bottom: 1px solid #2f3336; margin-bottom: 12px; cursor: pointer;
        }
        .reply-restriction svg { width: 16px; height: 16px; fill: currentColor; }
        
        .post-toolbar { display: flex; justify-content: space-between; align-items: center; }
        .toolbar-icons { display: flex; gap: 15px; }
        .toolbar-icons svg {
            width: 20px; height: 20px; fill: #1d9bf0; cursor: pointer; padding: 8px;
            border-radius: 50%; transition: 0.2s; box-sizing: content-box;
        }
        .toolbar-icons svg:hover { background-color: rgba(29, 155, 240, 0.1); }

        .tweet-actions { display: flex; justify-content: space-between; margin-top: 12px; max-width: 480px; }
        .action-btn { display: flex; align-items: center; color: #71767b; cursor: pointer; transition: 0.2s; font-size: 0.85rem; }
        .action-btn svg {
            width: 18.75px; height: 18.75px; fill: currentColor; padding: 8px; box-sizing: content-box;
            border-radius: 50%; transition: 0.2s; margin-right: -4px;
        }
        
        .action-btn.reply:hover { color: #1d9bf0; } .action-btn.reply:hover svg { background-color: rgba(29, 155, 240, 0.1); }
        .action-btn.repost:hover { color: #00ba7c; } .action-btn.repost:hover svg { background-color: rgba(0, 186, 124, 0.1); }
        .action-btn.like:hover { color: #f91880; } .action-btn.like:hover svg { background-color: rgba(249, 24, 128, 0.1); }
        .action-btn.views:hover, .action-btn.share:hover, .action-btn.bookmark:hover { color: #1d9bf0; }
        .action-btn.views:hover svg, .action-btn.share:hover svg, .action-btn.bookmark:hover svg { background-color: rgba(29, 155, 240, 0.1); }
        
        .action-btn.like.active { color: #f91880; } .action-btn.like.active svg { fill: #f91880; }
        .action-btn.repost.active { color: #00ba7c; } .action-btn.repost.active svg { fill: #00ba7c; }
        .action-btn.bookmark.active { color: #1d9bf0; } .action-btn.bookmark.active svg { fill: #1d9bf0; }
        .show-new-posts {
            display: block; width: 100%; padding: 16px 0; text-align: center;
            color: #1d9bf0; background-color: transparent; border: none;
            border-bottom: 1px solid #2f3336; font-size: 0.95rem; cursor: pointer;
            transition: background-color 0.2s; font-family: inherit;
        }
        .show-new-posts:hover { background-color: rgba(255, 255, 255, 0.03); }
    </style>
</head>
<?php
// Determine dynamic weather class
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
<script>
console.log('Testing jQuery...');
if (typeof jQuery !== 'undefined') {
    console.log('✅ jQuery loaded successfully!');
} else {
    console.log('❌ jQuery NOT loaded!');
}
</script>

<div class="app-wrapper">
    
    <?php include 'includes/sidebar.php'; ?> <!-- Coluna 1 -->

    <!-- Coluna 2: O Feed (Container) -->
    <div class="container" style="flex-grow: 0; flex-shrink: 0; max-width: 600px;">

        <header style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; border-bottom: 1px solid #2f3336; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); position: sticky; top: 0; z-index: 10;">
           <h1 style="font-size: 1.2rem; margin: 0;">Status 🗽</h1>
                
           <a href="pages/messages.php" style="color: #e7e9ea; display: flex; align-items: center; justify-content: center; position: relative;">
                    <svg viewBox="0 0 24 24" style="width: 24px; height: 24px; fill: currentColor;"><path d="M1.998 5.5c0-1.381 1.119-2.5 2.5-2.5h15c1.381 0 2.5 1.119 2.5 2.5v13c0 1.381-1.119 2.5-2.5 2.5h-15c-1.381 0-2.5-1.119-2.5-2.5v-13zm2.5-.5c-.276 0-.5.224-.5.5v2.764l8 3.638 8-3.636V5.5c0-.276-.224-.5-.5-.5h-15zm15.5 4.466l-8 3.636-8-3.638V18.5c0 .276.224.5.5.5h15c.276 0 .5-.224.5-.5V9.466z"></path></svg>
                    <?php if($unread_count > 0): ?>
                        <span style="position: absolute; top: -5px; right: -8px; background-color: #1d9bf0; color: #fff; font-size: 0.7rem; font-weight: bold; border-radius: 50%; padding: 2px 6px; border: 2px solid #000;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </header>

            <div class="stories-container">
            
                <div class="story-item">
                    <div class="story-avatar seen" style="position: relative;">
                        👤
                        <div style="position: absolute; bottom: 0; right: 0; background: #1d9bf0; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border: 2px solid #000;">
                            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: white;"><path d="M11 11V4h2v7h7v2h-7v7h-2v-7H4v-2h7z"></path></svg>
                        </div>
                    </div>
                    <span class="story-user">Add Status</span>
                </div>

                <div class="story-item">
                    <div class="story-avatar">
                        <img src="https://i.pinimg.com/736x/8d/f3/d3/8df3d3cf459294d1b8ab6ec0a3825838.jpg" alt="Lottie">
                    </div>
                    <span class="story-user">lottiemat...</span>
                </div>
                
            </div>
             <div class="perfil-abas" style="margin-top: 0; border-bottom: 1px solid #2f3336; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); position: sticky; top: 53px; z-index: 9;">
    <div class="aba-item ativa" data-feed="foryou">For You</div>
    <div class="aba-item" data-feed="following">Following</div>
</div>
          <!-- ===== POST BOX (FIXED - FULLY FUNCTIONAL) ===== -->
<section class="post-box" style="padding: 15px; border-bottom: 1px solid #2f3336; background: rgba(0,0,0,0.5);">
    
    <?php if (isset($_GET['postado']) && $_GET['postado'] == 'ok'): ?>
        <div style="background: #00ff0022; border: 1px solid #00ff00; border-radius: 8px; padding: 10px; margin-bottom: 10px; color: #00ff00;">
            ✅ Post sent successfully!
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div style="display: flex; gap: 10px; align-items: flex-start;">
            
            <!-- Avatar -->
            <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background: #333; flex-shrink: 0;">
                <?php 
                $meu_avatar = $perfil_atual['avatar'] ?? '👤';
                echo (strpos($meu_avatar, 'http') === 0) ? "<img src='" . htmlspecialchars($meu_avatar) . "' style='width:100%;height:100%;object-fit:cover;'>" : htmlspecialchars($meu_avatar);
                ?>
            </div>
            
            <!-- Content -->
            <div style="flex: 1; min-width: 0;">
                <textarea 
                    name="conteudo" 
                    id="novo-tweet" 
                    placeholder="What is happening?!" 
                    style="width: 100%; background: transparent; border: none; color: #e7e9ea; font-size: 1.1rem; resize: none; outline: none; min-height: 60px; font-family: inherit;"
                ></textarea>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; flex-wrap: wrap; gap: 8px;">
                    
                    <!-- Icons -->
                    <div style="display: flex; gap: 8px;">
                        <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;">
                            <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M3 5.5C3 4.119 4.119 3 5.5 3h13C19.881 3 21 4.119 21 5.5v13c0 1.381-1.119 2.5-2.5 2.5h-13C4.119 21 3 19.881 3 18.5v-13zM5.5 5c-.276 0-.5.224-.5.5v9.086l3-3 3 3 5-5 3 3V5.5c0-.276-.224-.5-.5-.5h-13zM19 15.414l-3-3-5 5-3-3-3 3V18.5c0 .276.224.5.5.5h13c.276 0 .5-.224.5-.5v-3.086zM9.75 7C8.784 7 8 7.784 8 8.75s.784 1.75 1.75 1.75 1.75-.784 1.75-1.75S10.716 7 9.75 7z"></path></svg>
                        </button>
                        <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;">
                            <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z"></path></svg>
                        </button>
                        <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;">
                            <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M19 4H5C3.343 4 2 5.343 2 7v10c0 1.657 1.343 3 3 3h14c1.657 0 3-1.343 3-3V7c0-1.657-1.343-3-3-3zM5 6h14c.552 0 1 .448 1 1v10c0 .552-.448 1-1 1H5c-.552 0-1-.448-1-1V7c0-.552.448-1 1-1zm6 3h2v6h-2V9zm-4 2h2v4H7v-4zm8 1h2v3h-2v-3z"></path></svg>
                        </button>
                    </div>
                    
                    <!-- Vibe Selector + Post Button -->
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select name="vibe" style="background: #16181c; color: #e7e9ea; border: 1px solid #2f3336; border-radius: 9999px; padding: 4px 12px; font-size: 0.85rem; outline: none; cursor: pointer;">
                            <option value="neutral">☁️ Neutral</option>
                            <option value="flirty">✨ Confident</option>
                            <option value="sad">🌧️ Sad</option>
                            <option value="toxic">🖤 Toxic</option>
                            <option value="excited">🤩 Excited</option>
                            <option value="pittsburgh-pride">🏈 Steelers Pride</option>
                            <option value="frustrated">😤 Frustrated</option>
                            <option value="anxious">😰 Anxious</option>
                            <option value="romantic">💖 Romantic</option>
                            <option value="tired">🥱 Tired</option>
                        </select>
                        
                        <button type="submit" id="btn-postar" style="background-color: #1d9bf0; color: white; border: none; border-radius: 9999px; padding: 8px 18px; font-weight: bold; font-size: 1rem; cursor: pointer; opacity: 0.5; pointer-events: none; transition: 0.2s;">
                            Post
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
<!-- ===== END POST BOX ===== -->
            
               <!-- NOVO: Abas do Feed -->

               <main id="timeline">
                <!-- New posts notification pill -->
                <button id="new-posts-pill" class="show-new-posts">
                    Show 35 posts
                </button>
                <?php
                // DB connection moved to the top of the file
                
                $meu_user = $conexao->real_escape_string($_SESSION['username']);
                
              // Fetch current profile correctly without swallowing timeline posts
              $sql_perfil = "SELECT avatar FROM perfis WHERE username = '$meu_user' LIMIT 1";
              $res_perfil = $conexao->query($sql_perfil);
              $perfil_atual = $res_perfil ? $res_perfil->fetch_assoc() : ['avatar' => '👤'];

              // OPTIMIZATION: Fetch ALL your interactions at once (Prevents N+1 Queries and lag)
              $sql_ints = "SELECT post_id, tipo FROM interacoes WHERE username = '$meu_user'";
              $res_ints = $conexao->query($sql_ints);
              $minhas_interacoes = [];
              if ($res_ints && $res_ints->num_rows > 0) {
                  while($int = $res_ints->fetch_assoc()) {
                      $minhas_interacoes[$int['post_id']][] = $int['tipo'];
                  }
              }

             // BUG FIX: Added (is_locked = 0 OR is_locked IS NULL) and parent_id IS NULL to properly fetch main timeline posts
             $sql_foryou = "SELECT posts.*, perfis.avatar FROM posts LEFT JOIN perfis ON posts.username = perfis.username WHERE (posts.is_locked = 0 OR posts.is_locked IS NULL) AND posts.parent_id IS NULL ORDER BY posts.data_envio DESC, posts.id DESC LIMIT 30";
              $res_foryou = $conexao->query($sql_foryou);

              // Helper function to avoid repeating the Tweet HTML twice
            function renderizar_tweet($row, $perfil_atual, $minhas_interacoes) {
                $avatar_db = $row['avatar'] ?? '👤';
                $avatar_html = (strpos($avatar_db, 'http') === 0) ? "<img src='" . htmlspecialchars($avatar_db) . "' style='width: 100%; height: 100%; object-fit: cover; border-radius: 50%;'>" : htmlspecialchars($avatar_db);

    $link_perfil = "pages/perfil.php?user=" . urlencode($row["username"]);
    $link_tweet = "pages/tweet.php?id=" . $row["id"];
    $texto_limpo = htmlspecialchars(stripslashes($row["conteudo"]));
    // Conserta espaços em branco nas vibes geradas pela IA (ex: pittsburgh pride vira pittsburgh-pride)
    $vibe_class = isset($row['vibe']) ? 'vibe-' . htmlspecialchars(str_replace(' ', '-', strtolower($row['vibe']))) : 'vibe-neutral';

    // Matemática das métricas falsas que haviam sumido
    $likes_f = ($row['id'] * 47) % 950 + 12;
    $reposts_f = ($row['id'] * 13) % 150 + 1;
    $views_f = ($row['id'] * 97) % 90 + 5;
    
    // Data (Ex: M d - igual ao X real)
    $data_formatada = date('M d', strtotime($row['data_criacao'] ?? 'now'));

    $interacoes_deste_post = $minhas_interacoes[$row['id']] ?? [];
    $is_liked = in_array('like', $interacoes_deste_post);
    $is_reposted = in_array('repost', $interacoes_deste_post);
    $is_bookmarked = in_array('bookmark', $interacoes_deste_post);

    $path_like = $is_liked ? "M20.884 13.19c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3C7.121 18.31 4.471 15.67 3.119 13.19 1.928 11.01 1.618 8.69 2.222 6.6 2.827 4.5 4.3 2.91 6.275 2.6c1.651-.26 3.368.3 4.798 1.75l.927.94.927-.94c1.43-1.45 3.146-2.01 4.796-1.75 1.975.31 3.448 1.9 4.053 3.99.604 2.09.294 4.41-.892 6.6z" : "M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91zm4.187 7.69c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3c-4.379-2.55-7.029-5.19-8.382-7.67-1.36-2.5-1.41-4.86-.514-6.67.887-1.79 2.647-2.91 4.601-3.01 1.651-.09 3.368.56 4.798 2.01 1.429-1.45 3.146-2.1 4.796-2.01 1.954.1 3.714 1.22 4.601 3.01.896 1.81.846 4.17-.514 6.67z";
    $path_bookmark = $is_bookmarked ? "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5z" : "M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z";

   // Default to 'Twitter Web App' if the column is empty for testing purposes
   $context_label = !empty($row['context_label']) ? htmlspecialchars($row['context_label']) : 'Twitter Web App';
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
                <!-- FEED: FOLLOWING (Cronológico - Apenas sua bolha) -->
                <div id="feed-foryou" class="feed-section">
    <?php
// FIX: Added NULL checks for is_locked and parent_id to ensure AI posts appear correctly
$sql_following = "SELECT posts.*, perfis.avatar FROM posts LEFT JOIN perfis ON posts.username = perfis.username WHERE (posts.is_locked = 0 OR posts.is_locked IS NULL) AND posts.parent_id IS NULL AND (posts.visibility = 'following' OR posts.visibility = 'public' OR posts.username = '$meu_user') ORDER BY posts.data_envio DESC, posts.id DESC LIMIT 30";
    $res_following = $conexao->query($sql_following);
    if ($res_following->num_rows > 0) {
        while($row = $res_following->fetch_assoc()) { 
            // BUSCA TODAS AS SUAS INTERAÇÕES DE UMA VEZ PARA NÃO PESAR O BANCO
$meu_username = $_SESSION['username']; // Agora é dinâmico baseado na sessão
$sql_ints = "SELECT post_id, tipo FROM interacoes WHERE username = '$meu_username'";
$res_ints = $conexao->query($sql_ints);
$minhas_interacoes = [];
if ($res_ints && $res_ints->num_rows > 0) {
    while($int = $res_ints->fetch_assoc()) {
        $minhas_interacoes[$int['post_id']][] = $int['tipo'];
    }
}
            renderizar_tweet($row, $perfil_atual, $minhas_interacoes); 
        }
    } else {
        echo '<p style="padding: 20px; text-align: center; color: #65676b;">No posts here yet.</p>';
    }
    ?>
</div>

<div id="feed-following" class="feed-section" style="display: none;">
    <?php
 // FIX: Added NULL checks for is_locked and parent_id to ensure AI posts appear correctly
 $sql_following = "SELECT posts.*, perfis.avatar FROM posts LEFT JOIN perfis ON posts.username = perfis.username WHERE (posts.is_locked = 0 OR posts.is_locked IS NULL) AND posts.parent_id IS NULL AND (posts.visibility = 'following' OR posts.visibility = 'public' OR posts.username = '$meu_user') ORDER BY posts.data_envio DESC, posts.id DESC LIMIT 30";
    $res_following = $conexao->query($sql_following);
    if ($res_following->num_rows > 0) {
        while($row = $res_following->fetch_assoc()) { 
            renderizar_tweet($row, $perfil_atual, $minhas_interacoes); 
        }
    } else {
        echo '<p style="padding: 20px; text-align: center; color: #65676b;">You are not following anyone with recent posts.</p>';
    }
    ?>
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
    <!-- FOLDER MODAL OVERLAY -->
    <div id="folder-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(36, 45, 52, 0.5); z-index: 10000; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
            <div id="folder-modal" style="background-color: #000; width: 90%; max-width: 400px; border-radius: 16px; border: 1px solid #2f3336; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 0 15px rgba(255,255,255,0.1);">
                
                <div style="display: flex; align-items: center; padding: 12px 16px; border-bottom: 1px solid #2f3336;">
                    <div id="close-folder-modal" style="cursor: pointer; padding: 8px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: 0.2s; margin-left: -8px; margin-right: 16px;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #e7e9ea;"><path d="M10.59 12L4.54 5.96l1.42-1.42L12 10.59l6.04-6.05 1.42 1.42L13.41 12l6.05 6.04-1.42 1.42L12 13.41l-6.04 6.05-1.42-1.42L10.59 12z"></path></svg>
                    </div>
                    <h2 style="color: #e7e9ea; font-size: 1.2rem; margin: 0;">Add to folder</h2>
                </div>
                
                <div style="padding: 16px; display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="new-folder-name" placeholder="Create new folder" style="flex-grow: 1; background-color: transparent; border: 1px solid #333639; border-radius: 4px; color: #e7e9ea; padding: 10px; font-size: 1rem; outline: none;" onfocus="this.style.border='1px solid #1d9bf0'" onblur="this.style.border='1px solid #333639'">
                        <button id="btn-create-folder" style="background-color: #eff3f4; color: #0f1419; border: none; border-radius: 9999px; padding: 0 16px; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#d7dbdc'" onmouseout="this.style.backgroundColor='#eff3f4'">Create</button>
                    </div>
                    
                    <div id="folder-list-placeholder" style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px; max-height: 200px; overflow-y: auto;">
                        <!-- Placeholder for existing folders -->
                        <p style="color: #71767b; font-size: 0.9rem; text-align: center; margin: 20px 0;">No folders yet.</p>
                    </div>
                </div>
                
            </div>
        </div>
  
<!-- ============================================= -->
<!-- NEW POST MODAL (FIXED - FULLY FUNCTIONAL)    -->
<!-- ============================================= -->
<div id="modal-novo-post" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    
    <div style="background-color: #000000; width: 100%; max-width: 600px; border-radius: 16px; padding: 16px 20px; border: 1px solid #2f3336; box-shadow: 0 0 30px rgba(0,0,0,0.9); position: relative; margin: 20px;">

        <!-- Header: Close button -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <button onclick="fecharModalPost()" style="background: transparent; border: none; cursor: pointer; color: #e7e9ea; padding: 8px; border-radius: 50%; transition: 0.2s;">
                <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M10.59 12L4.54 5.96l1.42-1.42L12 10.59l6.04-6.05 1.42 1.42L13.41 12l6.05 6.04-1.42 1.42L12 13.41l-6.04 6.05-1.42-1.42L10.59 12z"></path></svg>
            </button>
            <span style="color: #e7e9ea; font-weight: bold;">New Post</span>
            <div style="width: 20px;"></div>
        </div>

        <div class="story-item" onclick="abrirModalPost()" style="cursor: pointer;">
    <div class="story-avatar seen" style="position: relative;">
        👤
        <div style="position: absolute; bottom: 0; right: 0; background: #1d9bf0; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border: 2px solid #000;">
            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: white;"><path d="M11 11V4h2v7h7v2h-7v7h-2v-7H4v-2h7z"></path></svg>
        </div>
    </div>
    <span class="story-user">Add Status</span>
</div>

        <!-- Post Form -->
        <form id="form-novo-post" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            
            <!-- Avatar + Everyone button -->
            <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px;">
                <div class="avatar" style="width: 40px; height: 40px; margin: 0; overflow: hidden; border-radius: 50%; background: #333;">
                    <?php 
                    $meu_avatar = $perfil_atual['avatar'] ?? '👤';
                    echo (strpos($meu_avatar, 'http') === 0) ? "<img src='" . htmlspecialchars($meu_avatar) . "' style='width: 100%; height: 100%; object-fit: cover;'>" : htmlspecialchars($meu_avatar);
                    ?>
                </div>
                <button type="button" class="audience-btn" style="pointer-events: none; opacity: 0.7;">Everyone <svg viewBox="0 0 24 24" style="width:14px;height:14px;fill:currentColor;"><path d="M3.543 8.96l1.414-1.42L12 14.59l7.043-7.05 1.414 1.42L12 17.41 3.543 8.96z"></path></svg></button>
            </div>

            <!-- Text area -->
            <textarea name="conteudo" id="novo-tweet-modal" placeholder="What is happening?!" style="width: 100%; background: transparent; border: none; color: #e7e9ea; font-size: 1.3rem; resize: none; outline: none; min-height: 140px; font-family: inherit;"></textarea>

            <!-- Vibe Selector -->
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 5px; padding: 10px 0;">
                <div style="display: flex; align-items: center; gap: 8px; width: 100%;">
                    <span style="color: #71767b; font-size: 0.85rem; font-weight: bold;">Vibe:</span>
                    <select name="vibe" id="vibe-select-modal" style="background: #16181c; color: #e7e9ea; border: 1px solid #2f3336; border-radius: 9999px; padding: 6px 14px; font-size: 0.85rem; outline: none; cursor: pointer; flex: 1;">
                        <option value="neutral">☁️ Neutral</option>
                        <option value="flirty">✨ Confident</option>
                        <option value="sad">🌧️ Sad</option>
                        <option value="toxic">🖤 Toxic</option>
                        <option value="excited">🤩 Excited</option>
                        <option value="pittsburgh-pride">🏈 Steelers Pride</option>
                        <option value="frustrated">😤 Frustrated</option>
                        <option value="anxious">😰 Anxious</option>
                        <option value="romantic">💖 Romantic</option>
                        <option value="tired">🥱 Tired</option>
                    </select>
                </div>
            </div>

            <!-- Toolbar + Submit Button -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #2f3336; padding-top: 12px; margin-top: 12px;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;"><svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: currentColor;"><path d="M3 5.5C3 4.119 4.119 3 5.5 3h13C19.881 3 21 4.119 21 5.5v13c0 1.381-1.119 2.5-2.5 2.5h-13C4.119 21 3 19.881 3 18.5v-13zM5.5 5c-.276 0-.5.224-.5.5v9.086l3-3 3 3 5-5 3 3V5.5c0-.276-.224-.5-.5-.5h-13zM19 15.414l-3-3-5 5-3-3-3 3V18.5c0 .276.224.5.5.5h13c.276 0 .5-.224.5-.5v-3.086zM9.75 7C8.784 7 8 7.784 8 8.75s.784 1.75 1.75 1.75 1.75-.784 1.75-1.75S10.716 7 9.75 7z"></path></svg></button>
                    <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;"><svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: currentColor;"><path d="M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z"></path></svg></button>
                    <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;"><svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: currentColor;"><path d="M19 4H5C3.343 4 2 5.343 2 7v10c0 1.657 1.343 3 3 3h14c1.657 0 3-1.343 3-3V7c0-1.657-1.343-3-3-3zM5 6h14c.552 0 1 .448 1 1v10c0 .552-.448 1-1 1H5c-.552 0-1-.448-1-1V7c0-.552.448-1 1-1zm6 3h2v6h-2V9zm-4 2h2v4H7v-4zm8 1h2v3h-2v-3z"></path></svg></button>
                    <button type="button" style="color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer;"><svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: currentColor;"><path d="M12 22.75C6.072 22.75 1.25 17.928 1.25 12S6.072 1.25 12 1.25 22.75 6.072 22.75 12 17.928 22.75 12 22.75zm0-20C7.174 2.75 3.25 6.674 3.25 12S7.174 21.25 12 21.25 20.75 17.326 20.75 12 16.826 2.75 12 2.75zm-2.7 8.913c-.902 0-1.632-.824-1.632-1.84 0-1.016.73-1.84 1.632-1.84.903 0 1.633.824 1.633 1.84 0 1.016-.73 1.84-1.633 1.84zm7.042 0c-.903 0-1.633-.824-1.633-1.84 0-1.016.73-1.84 1.633-1.84.902 0 1.632.824 1.632 1.84 0 1.016-.73 1.84-1.632 1.84zm-6.19 2.75h3.702c1.296 0 2.502.58 3.333 1.55l1.524 1.78-1.524 1.77c-.831.97-2.037 1.55-3.333 1.55H10.15c-1.296 0-2.502-.58-3.333-1.55L5.293 17.75l1.524-1.77c.831-.97 2.037-1.55 3.333-1.55z"></path></svg></button>
                </div>

                <button type="submit" id="btn-postar-modal" style="background-color: #1d9bf0; color: white; border: none; border-radius: 9999px; padding: 8px 18px; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.2s; opacity: 0.5; pointer-events: none;">Post</button>
            </div>
        </form>
    </div>
</div>
<!-- ============================================= -->

<script>
// Modal functions
function abrirModalPost() {
    var modal = document.getElementById('modal-novo-post');
    if (modal) {
        modal.style.display = 'flex';
    }
    var input = document.getElementById('novo-tweet-modal');
    if (input) {
        input.value = '';
        input.focus();
    }
    var btn = document.getElementById('btn-postar-modal');
    if (btn) {
        btn.style.opacity = '0.5';
        btn.style.pointerEvents = 'none';
    }
}

function fecharModalPost() {
    var modal = document.getElementById('modal-novo-post');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Enable/disable modal post button
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('novo-tweet-modal');
    var btn = document.getElementById('btn-postar-modal');
    
    if (input && btn) {
        input.addEventListener('input', function() {
            if (this.value.trim().length > 0) {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            } else {
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
            }
        });
    }
});

// Enter to send (Shift+Enter = new line)
document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('novo-tweet-modal');
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                var btn = document.getElementById('btn-postar-modal');
                if (btn && btn.style.pointerEvents !== 'none') {
                    document.getElementById('form-novo-post').submit();
                }
            }
        });
    }
});

// Close modal clicking outside
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modal-novo-post');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalPost();
            }
        });
    }
});

// Check if post was sent successfully
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('postado') && urlParams.get('postado') === 'ok') {
        fecharModalPost();
        console.log('Post sent successfully!');
    }
});
</script>
<!-- ============================================= -->
<script>
// Enable/disable Post button based on text input
document.addEventListener('DOMContentLoaded', function() {
    var textarea = document.getElementById('novo-tweet');
    var btn = document.getElementById('btn-postar');
    
    if (textarea && btn) {
        textarea.addEventListener('input', function() {
            if (this.value.trim().length > 0) {
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            } else {
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
            }
        });
        
        // Press Enter to send (Shift+Enter = new line)
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim().length > 0) {
                    btn.click();
                }
            }
        });
    }
});
</script>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
if (typeof jQuery === 'undefined') {
    // Fallback to local copy if CDN fails
    document.write('<script src="/Columbia-os/assets/js/jquery-3.6.0.min.js"><\/script>');
}
</script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script src="<?php echo BASE_URL; ?>/assets/js/script.js?v=<?php echo time(); ?>"></script>
        <script src="<?php echo BASE_URL; ?>/assets/js/environment.js?v=<?php echo time(); ?>"></script>
        <script>
            $(document).ready(function() {
                // Captura o momento exato em que o feed foi carregado
                let lastLoadTime = "<?php echo date('Y-m-d H:i:s'); ?>";
                
                // Esconde a pílula de notificação por padrão
                $('#new-posts-pill').hide();

                // Loop a cada 30 segundos (30000 ms)
                setInterval(function() {
                   // 1. Verifica se há posts novos no banco
                 $.post('actions/check_new_posts.php', { last_time: lastLoadTime }, function(data) {
                        let count = parseInt(data);
                        if (count > 0) {
                            $('#new-posts-pill').text(`Show ${count} posts`).slideDown();
                        }
                    });
                }, 30000);

                // Recarrega a página ao clicar na pílula para ver os posts novos
                $('#new-posts-pill').on('click', function() {
                    window.scrollTo(0, 0);
                    location.reload();
                });
               // 2. O CORAÇÃO DO ECOSSISTEMA (Teste: A cada 15 minutos = 90000 ms)
setInterval(function() {
    console.log("Tentando acordar a Lottie e a Fofoca silenciosamente...");
    
    $.ajax({
        url: 'actions/routine_trigger.php',
        method: 'GET',
        success: function(response) {
            console.log("Rotina executada com sucesso no background!");
            // Opcional: Você pode forçar a página a recarregar aqui para ver o post novo na hora
            // location.reload(); 
        },
        error: function(xhr, status, error) {
            console.error("Erro ao tentar rodar a rotina autônoma:", error);
        }
    });
}, 60000);
            });
        </script>
    </body>
</html>