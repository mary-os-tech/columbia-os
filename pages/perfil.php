<?php
session_start();
include(__DIR__ . '/../includes/config.php');
include '../includes/conexao.php';
// --- SPOTIFY OAUTH TRIGGER ---
if (isset($_GET['action']) && $_GET['action'] === 'connect_spotify') {
    require_once '../includes/spotify_auth.php';
    $spotify = new SpotifyAuth();
    header("Location: " . $spotify->getAuthUrl());
    exit();
}
// -----------------------------
$meu_username = $_SESSION['username'] ?? 'mary'; // Pega o nome do usuário logado

// Se não passar o parâmetro ?user= na URL (ex: clicando na sidebar), carrega o seu próprio perfil
$username_get = (isset($_GET['user']) && !empty($_GET['user'])) ? $conexao->real_escape_string($_GET['user']) : $meu_username;

$sql_perfil = "SELECT * FROM perfis WHERE username = '$username_get' LIMIT 1";
$result_perfil = $conexao->query($sql_perfil);

if ($result_perfil->num_rows == 0) {
    $nome_padrao = ucfirst($username_get);
    // Se for você, ganha a bio de CS. Se for NPC gerado na hora, ganha bio genérica.
    $bio_padrao = ($username_get === 'user') ? 'CS Major. Coding late. ☕' : 'Columbia University Student.';
    
    $sql_criar = "INSERT INTO perfis (nome, username, avatar, header_image, bio, status_presenca) 
                  VALUES ('$nome_padrao', '$username_get', '👤', '', '$bio_padrao', 'Online')";
    $conexao->query($sql_criar);
    $result_perfil = $conexao->query($sql_perfil);
}

$perfil = $result_perfil->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@<?php echo htmlspecialchars($perfil['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dynamic_themes.css?v=<?php echo time(); ?>"> <style>
        #btn-tres-pontos:hover, #btn-notificacao-perfil:hover { background-color: rgba(231, 233, 234, 0.1); }
        .dropdown-item:hover { background-color: #16181c; }
        .tweet { transition: 0.2s; }
        .tweet:hover { background-color: rgba(255,255,255,0.03); }
        .tweet-actions { display: flex; justify-content: space-between; max-width: 425px; margin-top: 10px; }
        .action-btn { display: flex; align-items: center; gap: 5px; color: #71767b; font-size: 0.85rem; transition: 0.2s; }
        .action-btn svg { width: 18.75px; height: 18.75px; fill: currentColor; }
        .action-btn.active.like { color: #f91880; }
        .action-btn.active.repost { color: #00ba7c; }
        .action-btn.active.bookmark { color: #1d9bf0; }
        /* Modal Fix */
        .modal-fundo { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; }
    </style>
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
 
        <?php include '../includes/spotify_config.php';
        include '../includes/sidebar.php'; ?> <div class="container" style="border-left: none; border-right: 1px solid #2f3336; flex-grow: 1; max-width: 600px; margin: 0;">
            
            <div class="perfil-topo-barra">
                <a href="../index.php" class="btn-voltar"> <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #e7e9ea;"><path d="M7.414 13l5.293 5.293-1.414 1.414L3.586 12 11.293 4.293l1.414 1.414L7.414 11H21v2H7.414z"></path></svg>
                </a>
                <div>
                    <h2><?php echo htmlspecialchars($perfil['nome']); ?></h2>
                    <span style="color: #71767b; font-size: 0.85rem;">@<?php echo htmlspecialchars($perfil['username']); ?></span>
                </div>
            </div>

            <div class="capa-container">
                <?php 
                $header = $perfil['header_image'] ?? '';
                if (!empty($header) && strpos($header, 'http') === 0): 
                ?>
                    <img src="<?php echo htmlspecialchars($header); ?>" alt="Header" style="width:100%; height:100%; object-fit:cover;">
                <?php else: ?>
                    <div class="capa-padrao"></div>
                <?php endif; ?>
            </div>

            <div class="perfil-avatar-linha">
                <div class="perfil-avatar">
                    <?php 
                    $avatar = $perfil['avatar'] ?? '👤';
                    if (strpos($avatar, 'http') === 0): 
                    ?>
                        <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">
                    <?php else: ?>
                        <span style="font-size: 3.5rem;"><?php echo htmlspecialchars($avatar); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="perfil-botoes-acao" style="position: relative; display: flex; align-items: center; gap: 10px;">
                    
                  <!-- Contêiner relativo para ancorar o menu -->
                  <div style="position: relative;">
                        <div id="btn-tres-pontos" style="border: 1px solid #536471; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;">
                            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #e7e9ea;"><path d="M5 10a2 2 0 100 4 2 2 0 000-4zm14 0a2 2 0 100 4 2 2 0 000-4zm-7 0a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                        </div>
                        
                        <!-- O Dropdown real com ID correto -->
                        <div id="dropdown-menu" style="display: none; position: absolute; top: 40px; right: 0; background-color: #000; border: 1px solid #2f3336; border-radius: 12px; box-shadow: 0 0 15px rgba(255,255,255,0.1); z-index: 100; width: 200px; font-weight: bold; overflow: hidden;">
                            <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer;">🔗 Copy link to profile</div>
                            <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer;">🔇 Mute @<?php echo htmlspecialchars($perfil['username']); ?></div>
                            <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer;">🚫 Block @<?php echo htmlspecialchars($perfil['username']); ?></div>
                            <div class="dropdown-item" style="padding: 12px 16px; color: #f4212e; cursor: pointer;">🚩 Report profile</div>
                        </div>
                    </div>

                    <div id="btn-notificacao-perfil" style="border: 1px solid #536471; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;">
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #e7e9ea; transition: 0.2s;"><path d="M22 17H2v-2h2v-6.988c0-4.244 3.013-7.85 7.151-8.773a2.001 2.001 0 0 1 1.698 0C16.987 2.162 20 5.768 20 10.012V15h2v2zm-6.114 2H8.114a4.001 4.001 0 0 0 7.772 0z"></path></svg>
                    </div>
                    <?php
// Check Spotify connection status for profile
$is_spotify_connected = isset($_SESSION[SPOTIFY_TOKEN_SESSION]);
$is_expired = isset($_SESSION[SPOTIFY_EXPIRY_SESSION]) && time() > $_SESSION[SPOTIFY_EXPIRY_SESSION];
$connected = $is_spotify_connected && !$is_expired;
?>

<!-- In the profile header, add a music status -->
<div class="profile-music-status" style="display: flex; align-items: center; gap: 12px; margin-top: 12px; padding: 8px 12px; background: #1a1a1a; border-radius: 8px; border: 1px solid #2a2a2a;">
    <?php if ($connected): ?>
        <span style="color: #1DB954;">🎵</span>
        <span style="color: #888; font-size: 13px;">Connected to Spotify</span>
        <a href="/Columbia-os/pages/music_player.php" style="margin-left: auto; color: #1DB954; text-decoration: none; font-size: 12px; font-weight: 500;">
            Open Player →
        </a>
    <?php else: ?>
        <span style="color: #888;">🎵</span>
        <span style="color: #666; font-size: 13px;">Connect Spotify to share music with Lottie</span>
        <a href="/Columbia-os/includes/spotify_auth.php?action=login" style="margin-left: auto; color: #1DB954; text-decoration: none; font-size: 12px; font-weight: 500;">
            Connect →
        </a>
</div>
                        <button id="btn-abrir-modal" class="btn-edit-profile">Edit profile</button>
                        <a href="dm.php?user=<?php echo urlencode($perfil['username']); ?>" style="text-decoration:none;">
                            <button class="btn-perfil-twitter secondary">Message</button>
                        </a>
                        <?php endif; ?>
                </div>
            </div> <!-- FECHAMENTO FALTANDO DA perfil-avatar-linha -->

            <div class="perfil-info-bloco">
                <h1><?php echo htmlspecialchars($perfil['nome']); ?></h1>
                <span class="user-arroba">@<?php echo htmlspecialchars($perfil['username']); ?></span>
                
                <div class="status-presenca-tag <?php echo strtolower($perfil['status_presenca']) == 'online' ? 'online' : 'offline'; ?>">
                    <span class="bolinha-status"></span>
                    Status: <?php echo htmlspecialchars($perfil['status_presenca']); ?>
                </div>
                <p class="perfil-bio-texto"><?php echo nl2br(htmlspecialchars($perfil['bio'] ?? 'No bio available.')); ?></p>
                <div class="perfil-metricas">
                    <span><strong>211</strong> Following</span>
                    <span><strong>22</strong> Followers</span>
                </div>
            </div>

            <div class="perfil-abas">
                <div class="aba-item ativa" data-aba="posts">Posts</div>
                <div class="aba-item" data-aba="replies">Replies</div>
                <div class="aba-item" data-aba="media">Media 🔒</div>
                <div class="aba-item" data-aba="likes">Likes</div>
            </div>

            <div id="conteudo-da-aba" style="padding-bottom: 60px;">
            <div id="aba-posts" class="secao-aba">
                    <?php
                    $perfil_username = $conexao->real_escape_string($perfil['username']);
                    $sql_posts = "SELECT * FROM posts WHERE username = '$perfil_username' ORDER BY id DESC";
                    $result_posts = $conexao->query($sql_posts);

                    if ($result_posts->num_rows > 0) {
                        while($row = $result_posts->fetch_assoc()) {
                            $link_tweet = "tweet.php?id=" . $row["id"];
                            $likes_f = ($row['id'] * 47) % 950 + 12;
                            $reposts_f = ($row['id'] * 13) % 150 + 1;
                            $views_f = ($row['id'] * 97) % 90 + 5;
                            
                            // Força o 'Twitter Web App' se estiver vazio, igual no index
                            $context_label = !empty($row['context_label']) ? htmlspecialchars($row['context_label']) : 'Twitter Web App';
                            $is_deleted = isset($row['is_deleted']) && $row['is_deleted'] == 1;
                            
                            // Puxa a vibe e conserta os espaços
                            $vibe_class = isset($row['vibe']) ? 'vibe-' . htmlspecialchars(str_replace(' ', '-', strtolower($row['vibe']))) : 'vibe-neutral';
                            
                            // Imprime a div com a classe da vibe correta!
                            echo '<div class="tweet ' . $vibe_class . '" style="cursor:pointer;" onclick="window.location.href=\''.$link_tweet.'\'">';
                            echo '    <div class="avatar">';
                            // Pega o avatar de quem postou o tweet específico
                            $avatar_tweet = $row['avatar'] ?? '👤';
                            if (strpos($avatar_tweet, 'http') === 0) {
                                echo '<img src="' . htmlspecialchars($avatar_tweet) . '" alt="Avatar" style="width:100%; height:100%; object-fit:cover;">';
                            } else {
                                echo htmlspecialchars($avatar_tweet);
                            }
                            echo '    </div>';
                            echo '    <div class="tweet-content">';
                            echo '        <div class="tweet-header" style="display: flex; flex-direction: column;">';
                          // Pega o tempo formatado usando a função format_twitter_time que já existe no conexao.php
$tempo_formatado = isset($row['data_criacao']) ? format_twitter_time($row['data_criacao']) : 'now';
echo '            <div><strong>' . htmlspecialchars($row["autor"]) . '</strong> <span style="color: #71767b;">@' . htmlspecialchars($row["username"]) . ' · ' . $tempo_formatado . '</span></div>';
                            
                            if ($context_label) {
                                echo '            <span style="color: #71767b; font-size: 0.8rem; margin-top: 2px;">' . $context_label . '</span>';
                            }
                            echo '        </div>';
                            
                            if ($is_deleted) {
                                echo "
                                <div style='background-color: rgba(255,255,255,0.03); border: 1px solid #2f3336; border-radius: 12px; padding: 12px; margin-top: 8px; margin-bottom: 8px; color: #71767b; font-style: italic;'>
                                    This Tweet has been deleted.
                                </div>";
                            } else {
                                echo '        <p>' . htmlspecialchars(stripslashes($row["conteudo"])) . '</p>';
                                
                                echo '        <div class="tweet-actions" onclick="event.stopPropagation();">';
                                echo '            <div class="action-btn reply"><svg viewBox="0 0 24 24"><path d="M1.751 10c0-4.42 3.584-8 8.005-8h4.366c4.49 0 8.129 3.64 8.129 8.13 0 2.96-1.607 5.68-4.196 7.11l-8.054 4.46v-3.69h-.067c-4.49.1-8.183-3.51-8.183-8.01zm8.005-6c-3.317 0-6.005 2.69-6.005 6 0 3.37 2.77 6.08 6.138 6.01l.351-.01h1.761v2.3l5.087-2.81c1.951-1.08 3.163-3.13 3.163-5.36 0-3.39-2.744-6.13-6.129-6.13H9.756z"></path></svg></div>';
                                echo '            <div class="action-btn repost"><svg viewBox="0 0 24 24"><path d="M4.5 3.88l4.432 4.14-1.364 1.46L5.5 7.55V16c0 1.1.896 2 2 2H13v2H7.5c-2.209 0-4-1.79-4-4V7.55L1.432 9.48.068 8.02 4.5 3.88zM16.5 6H11V4h5.5c2.209 0 4 1.79 4 4v8.45l2.068-1.93 1.364 1.46-4.432 4.14-4.432-4.14 1.364-1.46 2.068 1.93V8c0-1.1-.896-2-2-2z"></path></svg> <span class="action-num">'.$reposts_f.'</span></div>';
                                echo '            <div class="action-btn like"><svg viewBox="0 0 24 24"><path d="M16.697 5.5c-1.222-.06-2.679.51-3.89 2.16l-.805 1.09-.806-1.09C9.984 6.01 8.526 5.44 7.304 5.5c-1.243.07-2.349.78-2.91 1.91-.552 1.12-.633 2.78.479 4.82 1.074 1.97 3.257 4.27 7.129 6.61 3.87-2.34 6.052-4.64 7.126-6.61 1.111-2.04 1.03-3.7.477-4.82-.561-1.13-1.666-1.84-2.908-1.91zm4.187 7.69c-1.351 2.48-4.001 5.12-8.379 7.67l-.503.3-.504-.3c-4.379-2.55-7.029-5.19-8.382-7.67-1.36-2.5-1.41-4.86-.514-6.67.887-1.79 2.647-2.91 4.601-3.01 1.651-.09 3.368.56 4.798 2.01 1.429-1.45 3.146-2.1 4.796-2.01 1.954.1 3.714 1.22 4.601 3.01.896 1.81.846 4.17-.514 6.67z"></path></svg> <span class="action-num">'.$likes_f.'</span></div>';
                                echo '            <div class="action-btn views"><svg viewBox="0 0 24 24"><path d="M8.75 21V3h2v18h-2zM18 21V8.5h2V21h-2zM4 21l.004-10h2L6 21H4zm9.248 0v-7h2v7h-2z"></path></svg> <span class="action-num">'.$views_f.'K</span></div>';
                                echo '            <div class="action-btn bookmark"><svg viewBox="0 0 24 24"><path d="M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z"></path></svg></div>';
                                echo '        </div>';
                            }
                            
                            echo '    </div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="padding: 30px; text-align: center; color: #71767b;">No posts from this profile yet.</p>';
                    }
                    ?>
                </div>

                <div id="aba-replies" class="secao-aba" style="display: none;"><p style="padding: 30px; text-align: center; color: #71767b;">No replies yet.</p></div>
                <div id="aba-media" class="secao-aba" style="display: none; padding: 15px;"><p style="color: #71767b; text-align: center; padding: 20px;">Increase your affinity level to unlock secret media in DMs!</p></div>
                <div id="aba-likes" class="secao-aba" style="display: none;"><p style="padding: 30px; text-align: center; color: #71767b;">No liked posts yet.</p></div>
            </div>
        </div>
    </div> 

    <!-- MODAL DE EDIÇÃO DE PERFIL -->
    <div id="modal-editar" class="modal-fundo edit-profile-modal" style="display: none;">
        <div class="modal-caixa" style="max-height: 90vh; overflow: hidden;">
            
            <!-- Cabeçalho do Modal -->
            <div class="modal-header" style="padding: 0 16px; height: 53px;">
                <div style="display: flex; align-items: center; gap: 30px;">
                    <div id="btn-fechar-modal" class="btn-fechar-modal-icon" style="cursor: pointer; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: 0.2s; margin-left: -8px;">
                        <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: #eff3f4;"><path d="M10.59 12L4.54 5.96l1.42-1.42L12 10.59l6.04-6.05 1.42 1.42L13.41 12l6.05 6.04-1.42 1.42L12 13.41l-6.04 6.05-1.42-1.42L10.59 12z"></path></svg>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #e7e9ea; margin: 0;">Edit profile</h2>
                </div>
                <button id="btn-salvar-perfil" style="background-color: #eff3f4; color: #0f1419; font-weight: 700; border: none; border-radius: 9999px; padding: 0 16px; height: 32px; cursor: pointer; font-size: 0.9rem;">Save</button>
            </div>

            <!-- Corpo do Modal (Rolável) -->
            <div class="modal-body" style="overflow-y: auto; padding: 20px 16px; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="color: #71767b; font-size: 0.85rem; padding-left: 8px;">Name</label>
                    <input type="text" id="edit-nome" class="input-twitter" value="<?php echo htmlspecialchars($perfil['nome'] ?? ''); ?>">
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="color: #71767b; font-size: 0.85rem; padding-left: 8px;">Bio</label>
                    <textarea id="edit-bio" class="input-twitter textarea-twitter"><?php echo htmlspecialchars($perfil['bio'] ?? ''); ?></textarea>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="color: #71767b; font-size: 0.85rem; padding-left: 8px;">Avatar (Link or Emoji)</label>
                    <input type="text" id="edit-avatar" class="input-twitter" value="<?php echo htmlspecialchars($perfil['avatar'] ?? ''); ?>">
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="color: #71767b; font-size: 0.85rem; padding-left: 8px;">Header (Link)</label>
                    <input type="text" id="edit-header" class="input-twitter" value="<?php echo htmlspecialchars($perfil['header_image'] ?? ''); ?>">
                </div>
            </div>

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
    <script src="../assets/js/script.js"></script>   
</body>
</html>