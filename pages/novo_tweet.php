<?php
session_start();
// CORREÇÃO: Usa __DIR__ para caminhos absolutos
include __DIR__ . '/../includes/config.php';
include __DIR__ . '/../includes/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

$meu_user = $_SESSION['username'];

// Busca o avatar do usuário logado
$sql_avatar = "SELECT avatar FROM perfis WHERE username = '$meu_user' LIMIT 1";
$res_avatar = $conexao->query($sql_avatar);
$avatar = ($res_avatar && $res_avatar->num_rows > 0) ? $res_avatar->fetch_assoc()['avatar'] : '👤';
$avatar_html = (strpos($avatar, 'http') === 0) ? "<img src='" . htmlspecialchars($avatar) . "' style='width: 100%; height: 100%; object-fit: cover; border-radius: 50%;'>" : htmlspecialchars($avatar);

// Lógica para salvar o post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['conteudo']) && !empty(trim($_POST['conteudo']))) {
    $conteudo = $conexao->real_escape_string($_POST['conteudo']);
    $vibe = isset($_POST['vibe']) ? $conexao->real_escape_string($_POST['vibe']) : 'neutral';
    $visibilidade = isset($_POST['visibilidade']) ? $conexao->real_escape_string($_POST['visibilidade']) : 'public';

    $sql_insert = "INSERT INTO posts (username, autor, conteudo, vibe, visibility, data_envio, data_criacao) 
                   VALUES ('$meu_user', '$meu_user', '$conteudo', '$vibe', '$visibilidade', NOW(), NOW())";
    
    if ($conexao->query($sql_insert)) {
        header("Location: ../index.php");
        exit();
    } else {
        $erro = "Erro ao publicar: " . $conexao->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Post</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .modal-box {
            background-color: #000000;
            width: 100%;
            max-width: 600px;
            border-radius: 16px;
            padding: 16px 20px;
            border: 1px solid #2f3336;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
            margin: 20px;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .btn-close { background: transparent; border: none; cursor: pointer; color: #e7e9ea; padding: 8px; border-radius: 50%; }
        .btn-close:hover { background-color: rgba(239, 243, 244, 0.1); }
        textarea#conteudo { width: 100%; background: transparent; border: none; color: #e7e9ea; font-size: 1.3rem; resize: none; outline: none; min-height: 140px; font-family: inherit; }
        textarea#conteudo::placeholder { color: #71767b; }
        .btn-submit { background-color: #1d9bf0; color: white; border: none; border-radius: 9999px; padding: 8px 18px; font-weight: bold; font-size: 1rem; cursor: pointer; }
        .btn-submit:hover { background-color: #1a8cd8; }
        .btn-submit:disabled { background-color: #0f4e78; color: #8ecdf8; cursor: not-allowed; }
        select { background: #16181c; color: #e7e9ea; border: 1px solid #2f3336; border-radius: 9999px; padding: 4px 12px; outline: none; cursor: pointer; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #2f3336; padding-top: 12px; margin-top: 12px; }
        .icons { display: flex; gap: 8px; }
        .icon-btn { color: #1d9bf0; background: transparent; border: none; padding: 6px; border-radius: 50%; cursor: pointer; }
        .icon-btn:hover { background-color: rgba(29, 155, 240, 0.1); }
        .icon-btn svg { width: 22px; height: 22px; fill: currentColor; }
    </style>
</head>
<body>

<div class="modal-box">
    
    <div class="modal-header">
        <a href="../index.php" class="btn-close">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:currentColor;"><path d="M10.59 12L4.54 5.96l1.42-1.42L12 10.59l6.04-6.05 1.42 1.42L13.41 12l6.05 6.04-1.42 1.42L12 13.41l-6.04 6.05-1.42-1.42L10.59 12z"></path></svg>
        </a>
        <button class="btn-drafts" style="color:#1d9bf0;background:transparent;border:none;font-weight:bold;cursor:pointer;">Drafts</button>
    </div>

    <?php if (isset($erro)): ?>
        <p style="color: #f4212e; margin-bottom: 10px;"><?php echo $erro; ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <div style="width:40px;height:40px;border-radius:50%;overflow:hidden;background:#333;">
                <?php echo $avatar_html; ?>
            </div>
        </div>

        <textarea name="conteudo" id="conteudo" placeholder="What is happening?!"></textarea>

        <div style="margin:10px 0;">
            <label style="color:#71767b;font-size:0.85rem;">Vibe:</label>
            <select name="vibe" style="margin-left:8px;">
                <option value="neutral">☁️ Neutral</option>
                <option value="flirty">✨ Confident</option>
                <option value="sad">🌧️ Sad</option>
                <option value="toxic">🖤 Toxic</option>
                <option value="excited">🤩 Excited</option>
                <option value="pittsburgh-pride">🏈 Steelers Pride</option>
                <option value="frustrated">😤 Frustrated</option>
            </select>
        </div>

        <div class="toolbar">
            <div class="icons">
                <button type="button" class="icon-btn"><svg viewBox="0 0 24 24"><path d="M3 5.5C3 4.119 4.119 3 5.5 3h13C19.881 3 21 4.119 21 5.5v13c0 1.381-1.119 2.5-2.5 2.5h-13C4.119 21 3 19.881 3 18.5v-13zM5.5 5c-.276 0-.5.224-.5.5v9.086l3-3 3 3 5-5 3 3V5.5c0-.276-.224-.5-.5-.5h-13zM19 15.414l-3-3-5 5-3-3-3 3V18.5c0 .276.224.5.5.5h13c.276 0 .5-.224.5-.5v-3.086zM9.75 7C8.784 7 8 7.784 8 8.75s.784 1.75 1.75 1.75 1.75-.784 1.75-1.75S10.716 7 9.75 7z"></path></svg></button>
                <button type="button" class="icon-btn"><svg viewBox="0 0 24 24"><path d="M4 4.5C4 3.12 5.119 2 6.5 2h11C18.881 2 20 3.12 20 4.5v18.44l-8-5.71-8 5.71V4.5zM6.5 4c-.276 0-.5.22-.5.5v14.56l6-4.29 6 4.29V4.5c0-.28-.224-.5-.5-.5h-11z"></path></svg></button>
            </div>
            <button type="submit" class="btn-submit" id="btn-submit" disabled>Post</button>
        </div>
    </form>
</div>

<script>
    document.getElementById('conteudo').addEventListener('input', function() {
        var btn = document.getElementById('btn-submit');
        btn.disabled = this.value.trim().length === 0;
    });
</script>

</body>
</html>