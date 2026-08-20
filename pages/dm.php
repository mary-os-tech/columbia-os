<?php
session_start();
include(__DIR__ . '/../includes/config.php');

// Prevent BFCache (Back-Forward Cache) to fix ghost sessions
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
include '../includes/conexao.php';

if (!isset($_GET['user'])) {
    header("Location: ../index.php");
    exit();
}

$npc_username = $conexao->real_escape_string($_GET['user']);
$meu_username = $_SESSION['username'] ?? 'user';

// Dynamically fetch the profile of the user we are chatting with
$sql_npc = "SELECT * FROM perfis WHERE username = '$npc_username' ORDER BY id ASC LIMIT 1";
$result_npc = $conexao->query($sql_npc);
$npc = $result_npc->fetch_assoc() ?? [
    'nome' => 'Unknown User', 
    'username' => $npc_username, 
    'avatar' => '👤', 
    'bio' => 'No bio available.'
];
// 😰 ANXIETY MECHANIC: Mark messages as read upon opening the chat (Case-Insensitive)
$safe_meu_read = $conexao->real_escape_string(trim(strtolower($meu_username)));
$safe_npc_read = $conexao->real_escape_string(trim(strtolower($npc_username)));
$sql_read = "UPDATE dms SET is_read = 1 WHERE LOWER(TRIM(receiver)) = '$safe_meu_read' AND LOWER(TRIM(sender)) = '$safe_npc_read' AND (is_read = 0 OR is_read IS NULL)";
$conexao->query($sql_read);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages with @<?php echo htmlspecialchars($npc_username); ?></title>

  <!-- jQuery - Load EARLY -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="../assets/css/dynamic_themes.css?v=<?php echo time(); ?>">
    
    <style>
        /* Typing Indicator Animation */
        .typing-dots { display: flex; gap: 4px; align-items: center; height: 20px; padding: 0 5px; }
        .typing-dots span { width: 6px; height: 6px; background-color: #71767b; border-radius: 50%; animation: bounce 1.4s infinite ease-in-out both; }
        .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
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
    
    <div class="container dm-container" style="display: flex; flex-direction: column; height: 100vh; flex-shrink: 0; max-width: 600px; border-left: 1px solid #2f3336; border-right: 1px solid #2f3336;">
        
    <header class="dm-header" style="position: sticky; top: 0; z-index: 10; background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); border-bottom: 1px solid #2f3336; padding: 8px 15px; display: flex; align-items: center; justify-content: space-between;">
            
            <!-- Lado Esquerdo: Botão Voltar, Avatar e Nome -->
            <div style="display: flex; align-items: center; gap: 12px; flex-grow: 1; overflow: hidden;">
                
                <!-- Botão Voltar -->
                <a href="messages.php" style="color: #e7e9ea; display: flex; align-items: center; padding: 4px; border-radius: 50%; transition: 0.2s;" onmouseover="this.style.backgroundColor='rgba(239, 243, 244, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                    <svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: currentColor;"><path d="M7.414 13l5.293 5.293-1.414 1.414L3.586 12 11.293 4.293l1.414 1.414L7.414 11H21v2H7.414z"></path></svg>
                </a>

                <!-- Botão para abrir o painel de informações (Avatar + Nome) -->
                <div id="toggle-info-panel" style="display: flex; align-items: center; gap: 10px; cursor: pointer; flex-grow: 1; min-width: 0; padding: 4px 8px 4px 4px; border-radius: 9999px; transition: 0.2s;" onmouseover="this.style.backgroundColor='rgba(239, 243, 244, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                    
                    <div class="avatar" style="width: 36px; height: 36px; margin: 0; border-radius: 50%; overflow: hidden; flex-shrink: 0; background-color: #333;">
                        <?php echo (strpos($npc['avatar'], 'http') === 0) ? '<img src="'.htmlspecialchars($npc['avatar']).'" style="width:100%; height:100%; object-fit:cover;">' : htmlspecialchars($npc['avatar']); ?>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; line-height: 1.2; min-width: 0;">
                        <h2 style="font-size: 1rem; color: #e7e9ea; margin:0; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($npc['nome']); ?></h2>
                        <span style="color: #71767b; font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">@<?php echo htmlspecialchars($npc_username); ?></span>
                    </div>
                </div>
            </div>

            <!-- Lado Direito: Botões de Ação (Ligação, Vídeo e Info) -->
            <div style="display: flex; gap: 4px; align-items: center; flex-shrink: 0;">
                
                <!-- Botão de Ligação -->
                <div id="btn-ligar-audio" style="color: #1d9bf0; padding: 8px; border-radius: 50%; cursor: pointer; transition: 0.2s; display: flex; align-items: center;" onmouseover="this.style.backgroundColor='rgba(29, 155, 240, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M11.96 14.945c-2.433-.67-4.275-2.5-4.945-4.945.69-.537 1.34-1.157 1.838-1.895l-3.327-4.24-2.698 1.4c-.456.24-1.22 1.206-1.156 3.25.105 3.39 2.052 7.07 4.707 9.54 2.827 2.63 6.464 4.31 9.516 4.31.258 0 .5-.014.733-.043 2.054-.256 2.92-1.076 3.125-1.503l1.393-2.73-4.204-3.36-1.928 1.87c-.722.508-1.353 1.176-1.91 1.885-.098.125-.262.164-.403.088l-1.077-.577-.665-.36z"></path></svg>
                </div>

                <!-- Botão de Vídeo -->
                <div id="btn-ligar-video" style="color: #1d9bf0; padding: 8px; border-radius: 50%; cursor: pointer; transition: 0.2s; display: flex; align-items: center;" onmouseover="this.style.backgroundColor='rgba(29, 155, 240, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm4.183 14H7.817c-.443 0-.817-.374-.817-.817V8.817c0-.443.374-.817.817-.817h8.366c.443 0 .817.374.817.817v6.366c0 .443-.374.817-.817.817zM14 11l3-3v8l-3-3v-2z"></path></svg>
                </div>

                <!-- Botão de Informações (O que faltava!) -->
                <div id="btn-dm-options" style="color: #e7e9ea; padding: 8px; border-radius: 50%; cursor: pointer; transition: 0.2s; display: flex; align-items: center; position: relative;" onmouseover="this.style.backgroundColor='rgba(239, 243, 244, 0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M3 12c0-1.1.9-2 2-2s2 .9 2 2-.9 2-2 2-2-.9-2-2zm9 2c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm7 0c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"></path></svg>
                    
                    <!-- Dropdown Menu -->
                    <div id="dm-options-menu" style="display: none; position: absolute; right: 0; top: 40px; background-color: #000; border: 1px solid #2f3336; border-radius: 12px; box-shadow: 0 0 15px rgba(255,255,255,0.1); z-index: 100; width: 220px; font-weight: bold; overflow: hidden;">
                        <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer; transition: 0.2s;">Mute notifications</div>
                        <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer; transition: 0.2s;">Search conversation</div>
                        <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer; transition: 0.2s;">Media, links and docs</div>
                        <div style="height: 1px; background-color: #2f3336; margin: 4px 0;"></div>
                        <div class="dropdown-item" style="padding: 12px 16px; color: #e7e9ea; cursor: pointer; transition: 0.2s;">Clear conversation</div>
                        <div class="dropdown-item" style="padding: 12px 16px; color: #f4212e; cursor: pointer; transition: 0.2s;">Block messages</div>
                        <div class="dropdown-item" style="padding: 12px 16px; color: #f4212e; cursor: pointer; transition: 0.2s;">Report user</div>
                        <div class="dropdown-item" style="padding: 12px 16px; color: #f4212e; cursor: pointer; transition: 0.2s;">Delete conversation</div>
                    </div>
                </div>
            </div>
        </header>
          <!-- SIDEBAR DE INFORMAÇÕES (Corrigida com Position Fixed para não quebrar o layout) -->
        <aside id="dm-info-panel" style="display: none; position: fixed; right: 0; top: 0; height: 100vh; width: 320px; background-color: #000; border-left: 1px solid #2f3336; z-index: 999; overflow-y: auto; box-shadow: -5px 0 20px rgba(0,0,0,0.8);">
            <div style="display: flex; flex-direction: column; align-items: center; text-align: center; padding: 30px 15px 20px 15px; border-bottom: 1px solid #2f3336; position: relative;">
                
                <!-- Botão de fechar interno -->
                <div id="close-info-panel" style="position: absolute; top: 15px; left: 15px; cursor: pointer; color: #e7e9ea; transition: 0.2s;" onmouseover="this.style.color='#1d9bf0'" onmouseout="this.style.color='#e7e9ea'">
                    <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: currentColor;"><path d="M10.59 12L4.54 5.96l1.42-1.42L12 10.59l6.04-6.05 1.42 1.42L13.41 12l6.05 6.04-1.42 1.42L12 13.41l-6.04 6.05-1.42-1.42L10.59 12z"></path></svg>
                </div>
                
                <div class="avatar" style="width: 96px; height: 96px; margin-bottom: 12px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background-color: #202327; border: 2px solid #000; transition: 0.2s;">
                    <?php echo (strpos($npc['avatar'], 'http') === 0) ? '<img src="'.htmlspecialchars($npc['avatar']).'" style="width:100%; height:100%; object-fit:cover;">' : '<span style="font-size: 48px;">'.htmlspecialchars($npc['avatar']).'</span>'; ?>
                </div>
                <h2 style="color: #e7e9ea; font-size: 1.3rem; margin: 0; line-height: 1.2; font-weight: 800;"><?php echo htmlspecialchars($npc['nome']); ?></h2>
                <span style="color: #71767b; font-size: 0.95rem; margin-bottom: 12px;">@<?php echo htmlspecialchars($npc['username']); ?></span>
                
                <p style="color: #e7e9ea; font-size: 0.95rem; margin: 0 0 20px 0; line-height: 1.4;">
                    <?php echo htmlspecialchars($npc['bio'] ?? 'Psych Major 🧠 | NYC | Probably analyzing you.'); ?>
                </p>

                <a href="perfil.php?user=<?php echo urlencode($npc['username']); ?>" style="text-decoration: none; background-color: #eff3f4; color: #0f1419; font-weight: bold; padding: 8px 16px; border-radius: 9999px; font-size: 0.95rem; transition: 0.2s;" onmouseover="this.style.backgroundColor='#d7dbdc'" onmouseout="this.style.backgroundColor='#eff3f4'">
                    View profile
                </a>
            </div>
            
            <div style="display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; cursor: pointer; transition: 0.2s; border-bottom: 1px solid #2f3336;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                    <span style="color: #e7e9ea; font-size: 0.95rem; font-weight: bold;">Disappearing messages</span>
                    <span style="color: #71767b; font-size: 0.9rem;">Off <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor; vertical-align: middle; margin-left: 4px;"><path d="M14.586 12L7.543 4.96l1.414-1.42L17.414 12l-8.457 8.46-1.414-1.42L14.586 12z"></path></svg></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; cursor: pointer; transition: 0.2s; border-bottom: 1px solid #2f3336;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                    <span style="color: #e7e9ea; font-size: 0.95rem; font-weight: bold;">Media, links and docs</span>
                    <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: #71767b;"><path d="M14.586 12L7.543 4.96l1.414-1.42L17.414 12l-8.457 8.46-1.414-1.42L14.586 12z"></path></svg>
                </div>
            </div>
        </aside>

        <div id="modal-chamada" class="modal-chamada">
            <h2 style="font-size: 1.5rem; margin-bottom: 5px;">Calling...</h2>
            <h3 style="color: #1d9bf0; margin-bottom: 40px;">@<?php echo htmlspecialchars($npc_username); ?></h3>
            <div class="chamada-avatar">
                <?php echo (strpos($npc['avatar'], 'http') === 0) ? '<img src="'.htmlspecialchars($npc['avatar']).'" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">' : htmlspecialchars($npc['avatar']); ?>
            </div>
            <div class="chamada-botoes">
                <button id="btn-desligar" class="btn-recusar" title="End Call">
                    <svg viewBox="0 0 24 24"><path d="M10.59 12L4.54 5.96l1.42-1.42L12 10.59l6.04-6.05 1.42 1.42L13.41 12l6.05 6.04-1.42 1.42L12 13.41l-6.04 6.05-1.42-1.42L10.59 12z"></path></svg>
                </button>
            </div>
            <p id="chamada-status" style="margin-top: 20px; color: #71767b; font-style: italic;"></p>
        </div>

        <main id="chat-area" style="flex-grow: 1; overflow-y: auto; padding: 20px 15px; display: flex; flex-direction: column; gap: 12px;">
        <?php
          $safe_meu = $conexao->real_escape_string(trim(strtolower($meu_username)));
          $safe_npc = $conexao->real_escape_string(trim(strtolower($npc_username)));

          $sql_msgs = "SELECT * FROM dms WHERE 
                      (LOWER(TRIM(sender)) = '$safe_meu' AND LOWER(TRIM(receiver)) = '$safe_npc') OR 
                      (LOWER(TRIM(sender)) = '$safe_npc' AND LOWER(TRIM(receiver)) = '$safe_meu') 
                      ORDER BY timestamp ASC";
          
          $result_msgs = $conexao->query($sql_msgs);

          if ($result_msgs && $result_msgs->num_rows > 0) {
              $last_date = ''; // Variável para rastrear a última data exibida
              
              while($msg = $result_msgs->fetch_assoc()) {
                  // Strict comparison ignoring case/spaces
                  $is_sent = (strtolower(trim($msg['sender'])) === strtolower(trim($meu_username)));
                  $classe_linha = $is_sent ? 'sent' : 'received';
                  
                  $raw_text = $msg['message_text'] ?? '';
                  $texto_limpo = htmlspecialchars(stripslashes((string)$raw_text));
                  $texto_limpo = preg_replace('/\[URI: spotify:track:[a-zA-Z0-9]+\]/', '', $texto_limpo);
                  $img_html = "";
                  $media_col = $msg['imagen_url'] ?? '';
                  if (!empty($media_col)) {
                      $img_html = "<img src=\"{$media_col}\" style=\"max-width:100%; border-radius:12px; margin-bottom:8px; display:block;\">";
                  }
                  
                  // --- DATA E HORA ---
                  $timestamp_msg = strtotime($msg['timestamp']);
                  $current_date = date('Y-m-d', $timestamp_msg); // Pega a data do dia atual
                  $hora = date('H:i', $timestamp_msg);
                  
                  // --- SE A DATA MUDOU, INSERE O CHIP ---
                  if ($current_date !== $last_date) {
                      // Formata a data para o padrão "Aug 14, 2026" ou "Yesterday"
                      $data_formatada = format_twitter_time($msg['timestamp']);
                      echo "<div style=\"display: flex; justify-content: center; margin: 20px 0 10px 0;\">";
                      echo "<span style=\"background-color: #16181c; color: #71767b; padding: 4px 14px; border-radius: 9999px; font-size: 0.8rem; font-weight: 500;\">{$data_formatada}</span>";
                      echo "</div>";
                      $last_date = $current_date; // Atualiza a data de referência
                  }
                  
                  $row_style = ($classe_linha === 'sent') ? "display: flex; justify-content: flex-end; margin-bottom: 12px;" : "display: flex; justify-content: flex-start; margin-bottom: 12px;";
                  $bubble_style = ($classe_linha === 'sent') ? "background-color: #1d9bf0; color: #fff; padding: 10px 15px; border-radius: 16px 16px 4px 16px; max-width: 75%;" : "background-color: #2f3336; color: #e7e9ea; padding: 10px 15px; border-radius: 16px 16px 16px 4px; max-width: 75%;";
                  
                  echo "<div class=\"message-row {$classe_linha}\" style=\"{$row_style}\" data-msg-id=\"{$msg['id']}\">";
                  echo "    <div class=\"message-bubble\" style=\"{$bubble_style} display:flex; flex-direction:column;\">";
                  echo          $img_html;
                  echo "        <span style=\"word-wrap:break-word;\">{$texto_limpo}</span>";
                  echo "        <span class=\"msg-time\" style=\"font-size: 0.7rem; opacity: 0.7; margin-top: 4px; text-align: right;\">{$hora}</span>";
                  echo "    </div>";
                  echo "</div>";
              }
          } else {
              echo "<p id=\"no-msg-text\" style=\"text-align:center; color:#71767b; margin-top:20px;\">No messages yet. Say hi!</p>";
          }
        ?>
        </main>

        <footer class="dm-footer" style="border-top: 1px solid #2f3336; background-color: #000; display: flex; flex-direction: column;">
            <div id="image-preview-container" style="display: none; padding: 10px 15px; position: relative; border-bottom: 1px solid #2f3336; text-align: left;">
                <div id="btn-remover-foto" style="position: absolute; top: 15px; left: 20px; background: rgba(0,0,0,0.8); color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; font-size: 1.2rem; transition: 0.2s; z-index:2;">&times;</div>
                <img id="image-preview-img" src="" style="max-width: 100%; max-height: 250px; border-radius: 12px; object-fit: contain; display: block; margin-top: 5px;">
            </div>

            <div style="padding: 10px 15px;">
                <div style="display: flex; align-items: center; background-color: #202327; border-radius: 22px; padding: 8px 15px; gap: 10px;">
                    <!-- Hidden File Input for FileReader -->
                    <input type="file" id="dm-image-upload" accept="image/*" style="display: none;">
                    
                    <svg id="btn-anexar-foto" viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: #1d9bf0; cursor: pointer; transition: 0.2s;"><path d="M3 5.5C3 4.119 4.119 3 5.5 3h13C19.881 3 21 4.119 21 5.5v13c0 1.381-1.119 2.5-2.5 2.5h-13C4.119 21 3 19.881 3 18.5v-13zM5.5 5c-.276 0-.5.224-.5.5v9.086l3-3 3 3 5-5 3 3V5.5c0-.276-.224-.5-.5-.5h-13zM19 15.414l-3-3-5 5-3-3-3 3V18.5c0 .276.224.5.5.5h13c.276 0 .5-.224.5-.5v-3.086zM9.75 7C8.784 7 8 7.784 8 8.75s.784 1.75 1.75 1.75 1.75-.784 1.75-1.75S10.716 7 9.75 7z"></path></svg>
                    <input type="text" id="nova-mensagem" placeholder="Start a new message" style="flex-grow: 1; background: transparent; border: none; color: #e7e9ea; font-size: 1rem; outline: none;">
                    <button id="btn-enviar-dm" type="button" style="background: transparent; border: none; padding: 0; cursor: pointer; display: flex; align-items: center;">
    <svg viewBox="0 0 24 24" style="width: 22px; height: 22px; fill: #1d9bf0;"><path d="M2.504 21.866l.526-2.108C3.04 19.719 4 15.823 4 12s-.96-7.719-.97-7.757l-.527-2.109L22.236 12 2.504 21.866zM5.981 13h5.36c.553 0 1-.447 1-1s-.447-1-1-1H5.981C5.88 9.272 5.53 5.555 5.239 3.209L19.502 12l-14.263 8.791c.291-2.346.64-6.063.742-7.791z"></path></svg>
</button>
                </div>
            </div>
        </footer>
    </div>
     </div> <!-- Fecha o container -->
    </div> <!-- Fecha o app-wrapper -->
    
    <script>
// ==========================================
// VARIÁVEIS GLOBAIS
// ==========================================
let urlFotoAnexada = "";
let callTimeout;

function obterHoraAtual() {
    let agora = new Date();
    let horas = String(agora.getHours()).padStart(2, '0');
    let minutos = String(agora.getMinutes()).padStart(2, '0');
    return horas + ':' + minutos;
}

// ==========================================
// FUNÇÃO PRINCIPAL
// ==========================================
function enviarMensagem() {
    let texto = document.getElementById('nova-mensagem').value;
    let destinatario = '<?php echo $npc_username; ?>';

    if (texto.trim() === '' && urlFotoAnexada === '') return;

    // Disable input
    let sendBtn = document.getElementById('btn-enviar-dm');
    let inputField = document.getElementById('nova-mensagem');
    sendBtn.disabled = true;
    inputField.disabled = true;
    inputField.setAttribute('placeholder', 'Sending...');

    // Limpeza da UI
    document.getElementById('image-preview-container').style.display = 'none';
    inputField.value = '';

    // Monta a bolha do usuário
    let conteudoBolha = "";
    if (urlFotoAnexada !== "") {
        conteudoBolha += '<img src="' + urlFotoAnexada + '" style="max-width:100%; border-radius:12px; margin-bottom:8px; display:block;">';
    }
    if (texto.trim() !== "") {
        conteudoBolha += '<span style="display:block; word-wrap:break-word;">' + texto + '</span>';
    }
    conteudoBolha += '<span class="msg-time">' + obterHoraAtual() + '</span>';

    let bolhaHTML = '<div class="message-row sent"><div class="message-bubble" style="display:flex; flex-direction:column; max-width:75%;">' + conteudoBolha + '</div></div>';
    document.getElementById('chat-area').insertAdjacentHTML('beforeend', bolhaHTML);
    
    let chatArea = document.getElementById('chat-area');
    chatArea.scrollTop = chatArea.scrollHeight;

    let fotoTemp = urlFotoAnexada;
    urlFotoAnexada = "";
    document.getElementById('btn-anexar-foto').style.fill = '#1d9bf0';

    // ==========================================
    // PASSO 1: ENVIAR A MENSAGEM
    // ==========================================
    let params1 = 'message_text=' + encodeURIComponent(texto) + 
                  '&receiver=' + encodeURIComponent(destinatario);
    if (fotoTemp !== "") {
        params1 += '&imagen_url=' + encodeURIComponent(fotoTemp);
    }

    fetch('../actions/enviar_dm.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params1
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() !== 'Success') {
            console.error("Erro no servidor:", data);
            document.querySelector('#chat-area .message-row.sent:last-child').remove();
            inputField.value = texto;
            alert("Erro ao enviar: " + data);
            sendBtn.disabled = false;
            inputField.disabled = false;
            inputField.setAttribute('placeholder', 'Start a new message');
            return;
        }

        let chatArea2 = document.getElementById('chat-area');
        
        // Mostra o digitando
        let digitandoHTML = `
        <div id="typing-indicator" class="message-row received" style="display: flex; justify-content: flex-start; margin-bottom: 12px;">
            <div class="message-bubble" style="background-color: #2f3336; padding: 10px 15px; border-radius: 16px 16px 16px 4px; max-width: 75%;">
                <div class="typing-dots"><span></span><span></span><span></span></div>
            </div>
        </div>`;
        document.getElementById('chat-area').insertAdjacentHTML('beforeend', digitandoHTML);
        chatArea2.scrollTop = chatArea2.scrollHeight;

        // ==========================================
        // PASSO 2: CHAMAR A IA
        // ==========================================
        let params2 = 'npc=' + encodeURIComponent(destinatario);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
            console.log("AI request timed out after 90 seconds");
        }, 90000);

        fetch('../actions/ai_engine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params2,
            signal: controller.signal
        })
        .then(res => {
            clearTimeout(timeoutId);
            if (!res.ok) {
                throw new Error('HTTP error ' + res.status);
            }
            return res.text();
        })
        .then(resposta => {
            clearTimeout(timeoutId);
            document.getElementById('typing-indicator').remove();
            
            if (!resposta || resposta.trim() === '') {
                resposta = "💕 hey babe, i'm here!";
            }
            
            // Verifica se a última mensagem é igual
            let chatBubbles = document.querySelectorAll('#chat-area .message-row.received:last-child .message-bubble span');
            let lastMsg = chatBubbles.length > 0 ? chatBubbles[chatBubbles.length - 1].innerText : '';
            if (lastMsg === resposta) {
                sendBtn.disabled = false;
                inputField.disabled = false;
                inputField.setAttribute('placeholder', 'Start a new message');
                return;
            }
            
            // ✅ DISPLAY THE RESPONSE (ONLY ONCE!)
            let bolhaResposta = '<div class="message-row received">' +
                                '    <div class="message-bubble" style="display:flex; flex-direction:column;">' +
                                '        <span style="word-wrap:break-word;">' + resposta + '</span>' +
                                '        <span class="msg-time">' + obterHoraAtual() + '</span>' +
                                '    </div>' +
                                '</div>';
            
            document.getElementById('chat-area').insertAdjacentHTML('beforeend', bolhaResposta);
            let chatArea3 = document.getElementById('chat-area');
            chatArea3.scrollTop = chatArea3.scrollHeight;
            
            // Mark messages as read
            let params3 = 'npc=' + encodeURIComponent(destinatario);
            fetch('../actions/marcar_lidas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params3
            });
            
            // Re-enable input
            sendBtn.disabled = false;
            inputField.disabled = false;
            inputField.setAttribute('placeholder', 'Start a new message');
        })
        .catch(error => {
            clearTimeout(timeoutId);
            document.getElementById('typing-indicator').remove();
            
            console.error("AI Error:", error);
            
            // Fallback response
            let fallbackMessages = [
                "hey babe, i'm here! 💕",
                "sorry my phone lagged! what's up? 🥺",
                "i'm here! just had a moment 😅",
                "hey baby! miss you too ❤️"
            ];
            let fallbackReply = fallbackMessages[Math.floor(Math.random() * fallbackMessages.length)];
            
            let bolhaResposta = '<div class="message-row received">' +
                                '    <div class="message-bubble" style="display:flex; flex-direction:column; background-color: #2f3336; color: #e7e9ea;">' +
                                '        <span style="word-wrap:break-word;">' + fallbackReply + '</span>' +
                                '        <span class="msg-time">' + obterHoraAtual() + '</span>' +
                                '    </div>' +
                                '</div>';
            
            document.getElementById('chat-area').insertAdjacentHTML('beforeend', bolhaResposta);
            let chatArea3 = document.getElementById('chat-area');
            chatArea3.scrollTop = chatArea3.scrollHeight;
            
            // Re-enable input
            sendBtn.disabled = false;
            inputField.disabled = false;
            inputField.setAttribute('placeholder', 'Start a new message');
        });
    })
    .catch(error => {
        console.error("Rede error:", error);
        sendBtn.disabled = false;
        inputField.disabled = false;
        inputField.setAttribute('placeholder', 'Start a new message');
    });
}

// ==========================================
// EVENTOS DA PÁGINA
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    let chatArea = document.getElementById('chat-area');
    chatArea.scrollTop = chatArea.scrollHeight;

    // Ligação falsa
    document.getElementById('btn-ligar-audio').addEventListener('click', function() {
        document.getElementById('modal-chamada').style.display = 'flex';
        document.getElementById('chamada-status').innerText = "Ringing...";
        callTimeout = setTimeout(function() {
            document.getElementById('chamada-status').innerText = "User is currently busy or offline.";
            setTimeout(() => document.getElementById('modal-chamada').style.display = 'none', 2000);
        }, 5000);
    });

    document.getElementById('btn-ligar-video').addEventListener('click', function() {
        document.getElementById('modal-chamada').style.display = 'flex';
        document.getElementById('chamada-status').innerText = "Ringing...";
        callTimeout = setTimeout(function() {
            document.getElementById('chamada-status').innerText = "User is currently busy or offline.";
            setTimeout(() => document.getElementById('modal-chamada').style.display = 'none', 2000);
        }, 5000);
    });

    document.getElementById('btn-desligar').addEventListener('click', function() {
        clearTimeout(callTimeout);
        document.getElementById('modal-chamada').style.display = 'none';
    });

    // Anexar foto
    document.getElementById('btn-anexar-foto').addEventListener('click', function() {
        document.getElementById('dm-image-upload').click();
    });

    document.getElementById('dm-image-upload').addEventListener('change', function(event) {
        let file = event.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(e) {
                urlFotoAnexada = e.target.result;
                document.getElementById('image-preview-img').src = urlFotoAnexada;
                document.getElementById('image-preview-container').style.display = 'block';
                document.getElementById('btn-anexar-foto').style.fill = '#00ba7c';
                document.getElementById('nova-mensagem').setAttribute('placeholder', 'Add a caption...');
                document.getElementById('nova-mensagem').focus();
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('btn-remover-foto').addEventListener('click', function() {
        urlFotoAnexada = "";
        document.getElementById('dm-image-upload').value = '';
        document.getElementById('image-preview-container').style.display = 'none';
        document.getElementById('btn-anexar-foto').style.fill = '#1d9bf0';
        document.getElementById('nova-mensagem').setAttribute('placeholder', 'Start a new message');
    });

    // Enter e Botão
    document.getElementById('btn-enviar-dm').addEventListener('click', function() {
        enviarMensagem();
    });

    document.getElementById('nova-mensagem').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarMensagem();
        }
    });

    // Menu de três pontinhos e Sidebar
    document.getElementById('btn-dm-options').addEventListener('click', function(e) {
        e.stopPropagation();
        let menu = document.getElementById('dm-options-menu');
        if (menu.style.display === 'block') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
    });

    document.getElementById('toggle-info-panel').addEventListener('click', function(e) {
        e.stopPropagation();
        let panel = document.getElementById('dm-info-panel');
        if (panel.style.display === 'block') {
            panel.style.display = 'none';
        } else {
            panel.style.display = 'block';
        }
    });

    document.getElementById('close-info-panel').addEventListener('click', function() {
        document.getElementById('dm-info-panel').style.display = 'none';
    });

    document.addEventListener('click', function(e) {
        let optMenu = document.getElementById('dm-options-menu');
        let infoPanel = document.getElementById('dm-info-panel');
        
        if (!e.target.closest('#btn-dm-options') && optMenu.style.display === 'block') {
            optMenu.style.display = 'none';
        }
        if (!e.target.closest('#toggle-info-panel') && !e.target.closest('#dm-info-panel') && infoPanel.style.display === 'block') {
            infoPanel.style.display = 'none';
        }
    });
});
</script>
</body>
</html>