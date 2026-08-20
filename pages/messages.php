<?php
session_start();
include(__DIR__ . '/../includes/config.php');
// Prevent BFCache (Back-Forward Cache) to fix ghost sessions
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include '../includes/conexao.php';
include_once '../includes/spotify_config.php';
$meu_username = $_SESSION['username'] ?? 'user';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/dynamic_themes.css?v=<?php echo time(); ?>"> 
    <style>
        /* Unread Thread UI Polish */
        .chat-list-item.unread-thread { background-color: rgba(29, 155, 240, 0.05); border-right: 3px solid #1d9bf0; }
        .chat-list-item.unread-thread .chat-preview { font-weight: 700; color: #e7e9ea; }
        .chat-list-item.unread-thread h3 { font-weight: 800; }
        .unread-dot { width: 10px; height: 10px; background-color: #1d9bf0; border-radius: 50%; margin-left: auto; box-shadow: 0 0 5px rgba(29, 155, 240, 0.5); }
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
        <?php include '../includes/sidebar.php'; ?>
        
        <div class="container" style="border-left: 1px solid #2f3336; border-right: 1px solid #2f3336; flex-shrink: 0; max-width: 600px;">

            <header style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; border-bottom: 1px solid #2f3336;">
                ...
            </header>

            <div class="search-container">
                <div class="search-bar">
                    <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #71767b;"><path d="M10.25 3.75c-3.59 0-6.5 2.91-6.5 6.5s2.91 6.5 6.5 6.5c1.795 0 3.419-.726 4.596-1.904 1.178-1.177 1.904-2.801 1.904-4.596 0-3.59-2.91-6.5-6.5-6.5zm-8.5 6.5c0-4.694 3.806-8.5 8.5-8.5s8.5 3.806 8.5 8.5c0 1.986-.682 3.815-1.824 5.262l4.781 4.781-1.414 1.414-4.781-4.781c-1.447 1.142-3.276 1.824-5.262 1.824-4.694 0-8.5-3.806-8.5-8.5z"></path></svg>
                    <input type="text" placeholder="Search Direct Messages">
                </div>
            </div>

            <main>
            <?php
                // Securely fetch all unique users you have DMs with using prepared statements
                $stmt_contatos = $conexao->prepare("SELECT DISTINCT IF(sender = ?, receiver, sender) AS contato FROM dms WHERE sender = ? OR receiver = ?");
                $stmt_contatos->bind_param("sss", $meu_username, $meu_username, $meu_username);
                $stmt_contatos->execute();
                $result_contatos = $stmt_contatos->get_result();

                if ($result_contatos && $result_contatos->num_rows > 0) {
                 // Prepare inner statements outside the loop for optimal performance and security
                 $stmt_perfil = $conexao->prepare("SELECT * FROM perfis WHERE username = ? LIMIT 1");
                 $stmt_ultima = $conexao->prepare("SELECT * FROM dms WHERE ((sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?)) ORDER BY id DESC LIMIT 1");
                 // NEW: Prepare statement to check for unread messages in this specific thread
                 $stmt_unread = $conexao->prepare("SELECT COUNT(id) as unread_count FROM dms WHERE sender = ? AND receiver = ? AND (is_read = 0 OR is_read IS NULL)");

                 while($row_contato = $result_contatos->fetch_assoc()) {
                     $contato_username = $row_contato['contato'];

                     $stmt_perfil->bind_param("s", $contato_username);
                     $stmt_perfil->execute();
                     $result_perfil = $stmt_perfil->get_result();
                     $perfil = ($result_perfil && $result_perfil->num_rows > 0) ? $result_perfil->fetch_assoc() : [];
                     
                     $nome = $perfil['nome'] ?? $contato_username;
                     $avatar = $perfil['avatar'] ?? '👤';

                     $stmt_ultima->bind_param("ssss", $meu_username, $contato_username, $contato_username, $meu_username);
                     $stmt_ultima->execute();
                     $result_ultima = $stmt_ultima->get_result();
                     $ultima_msg = ($result_ultima && $result_ultima->num_rows > 0) ? $result_ultima->fetch_assoc() : null;
                     
                     // NEW: Execute unread check
                     $stmt_unread->bind_param("ss", $contato_username, $meu_username);
                     $stmt_unread->execute();
                     $result_unread = $stmt_unread->get_result();
                     $unread_data = $result_unread->fetch_assoc();
                     $has_unread = ($unread_data['unread_count'] > 0);
                        
                      $preview_texto = "";
                      if ($ultima_msg) {
                          $media_col = $ultima_msg['imagen_url'] ?? '';
                          if (!empty($media_col)) {
                              $preview_texto .= "📷 ";
                          }
                          $preview_texto .= htmlspecialchars($ultima_msg['message_text']);

                          if ($ultima_msg['sender'] == $meu_username) {
                              $preview_texto = "You: " . $preview_texto;
                          }
                      } else {
                          $preview_texto = "No messages yet.";
                      }

                      $avatar_html = (strpos($avatar, 'http') === 0) ? "<img src=\"" . htmlspecialchars($avatar) . "\">" : htmlspecialchars($avatar);
                      
                      // Apply CSS class and dot if unread
                      $unread_class = $has_unread ? " unread-thread" : "";
                      $unread_dot = $has_unread ? "<div class=\"unread-dot\"></div>" : "";

                      echo "<a href=\"dm.php?user=" . urlencode($contato_username) . "\" class=\"chat-list-item{$unread_class}\">";
                      echo "    <div class=\"avatar\">{$avatar_html}</div>";
                      echo "    <div class=\"chat-info\">";
                      echo "        <div class=\"chat-nome-tempo\">";
                      echo "            <h3>" . htmlspecialchars($nome) . "</h3>";
                      // Usa a função que já está no conexao.php
                      $tempo_formatado = isset($ultima_msg['timestamp']) ? format_twitter_time($ultima_msg['timestamp']) : 'now';
                      echo "            <span class=\"chat-tempo\">{$tempo_formatado}</span>";
                      echo "        </div>";
                      echo "        <div class=\"chat-preview\">{$preview_texto}</div>";
                      echo "    </div>";
                      echo      $unread_dot;
                      echo "</a>";
                  }
                } else {
                    echo "<p style=\"padding: 40px 20px; text-align: center; color: #71767b; font-size: 1.1rem; line-height: 1.5;\">Welcome to your inbox!<br>Drop a line, share posts and more with private conversations.</p>";
                }
            ?>
            </main>

            <div class="fab-btn" title="New Message">
                <svg viewBox="0 0 24 24"><path d="M2.504 21.866l.526-2.108C3.04 19.719 4 15.823 4 12s-.96-7.719-.97-7.757l-.527-2.109L22.236 12 2.504 21.866zM5.981 13h5.36c.553 0 1-.447 1-1s-.447-1-1-1H5.981C5.88 9.272 5.53 5.555 5.239 3.209L19.502 12l-14.263 8.791c.291-2.346.64-6.063.742-7.791z"></path></svg>
            </div>

        </div>
        
        <div id="modal-nova-dm" class="modal-fundo" style="display: none;">
            <div class="modal-caixa">
                <div class="modal-header">
                    <div style="display: flex; align-items: center;">
                        <span id="fechar-modal-dm" style="font-size: 24px; cursor: pointer; color: #e7e9ea;">&times;</span>
                        <h2 style="margin-left: 20px; font-size: 1.2rem;">New Message</h2>
                    </div>
                </div>
                <div class="modal-body" style="padding-top: 10px;">
                    <input type="text" id="input-novo-contato" class="input-twitter" placeholder="Type a username (e.g., jackietaylor)" style="margin-bottom: 15px;">
                    <button id="btn-iniciar-conversa" style="width: 100%; background-color: #e7e9ea; color: #0f1419; font-weight: bold; border: none; padding: 10px; border-radius: 9999px; cursor: pointer; transition: 0.2s;">Next</button>
                </div>
            </div>
        </div>

    </div> 
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Abre o modal ao clicar no botão azul flutuante
            $('.fab-btn').click(function() {
                $('#modal-nova-dm').css('display', 'flex').hide().fadeIn(200);
                $('#input-novo-contato').focus();
            });

            // Fecha o modal ao clicar no X
            $('#fechar-modal-dm').click(function() {
                $('#modal-nova-dm').fadeOut(200);
            });

            // Ação de iniciar a conversa
            function abrirDM() {
                let contato = $('#input-novo-contato').val().trim();
                if(contato !== '') {
                    // Limpa o '@' caso você digite sem querer
                    contato = contato.replace('@', '');
                    // Redireciona para a tela de DM daquela pessoa
                    window.location.href = '<?php echo BASE_URL; ?>/pages/dm.php?user=' + encodeURIComponent(contato);
                }
            }

            $('#btn-iniciar-conversa').click(abrirDM);
            $('#input-novo-contato').keypress(function(e) {
                if(e.which == 13) abrirDM();
            });
        });
    </script>
    <script>
    // Verifica novas mensagens a cada 5 segundos e recarrega se tiver
    setInterval(function() {
        $.ajax({
            url: 'actions/check_new_posts.php', // Você pode usar esse mesmo arquivo para verificar DMs, ou criar um check_dms.php
            method: 'GET',
            success: function(data) {
                if(data > 0) {
                    location.reload();
                }
            }
        });
    }, 5000);
</script>
<script>
    // Verifica se tem novas mensagens a cada 8 segundos e atualiza a lista se necessário
    setInterval(function() {
        $.ajax({
            url: '../actions/check_dms.php', // Você criou esse arquivo na mensagem passada
            method: 'GET',
            data: { t: new Date().getTime() }, // Evita cache
            success: function(data) {
                // Se a resposta for '1', tem algo novo, então recarrega a lista de mensagens
                if (data.trim() === '1') {
                    location.reload();
                }
            }
        });
    }, 8000);
</script>
</body>
</html>