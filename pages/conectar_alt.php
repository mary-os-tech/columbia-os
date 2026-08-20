<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar Conta Existente</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #000; color: #e7e9ea; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { width: 100%; max-width: 400px; padding: 30px; display: flex; flex-direction: column; gap: 20px; }
        .logo { width: 40px; height: 40px; fill: #e7e9ea; align-self: center; margin-bottom: 10px; }
        h1 { font-size: 1.8rem; font-weight: bold; margin-bottom: 5px; }
        p.subtitle { color: #71767b; font-size: 0.95rem; margin-bottom: 15px; }
        .input-group { display: flex; flex-direction: column; gap: 15px; }
        .input-twitter { background-color: transparent; border: 1px solid #333639; border-radius: 4px; color: #e7e9ea; padding: 16px 10px; font-size: 1rem; outline: none; transition: 0.2s; }
        .input-twitter:focus { border-color: #1d9bf0; box-shadow: 0 0 0 1px #1d9bf0; }
        .btn-login { background-color: #e7e9ea; color: #0f1419; border: none; border-radius: 9999px; padding: 15px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-login:hover { background-color: #d7dbdc; }
        .btn-cancel { background-color: transparent; color: #e7e9ea; border: 1px solid #536471; border-radius: 9999px; padding: 15px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.2s; text-align: center; text-decoration: none; }
        .btn-cancel:hover { background-color: rgba(231, 233, 234, 0.1); }
    </style>
</head>
<body>
    <div class="login-box">
        <svg class="logo" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 22.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
        <h1>Adicionar conta existente</h1>
        <p class="subtitle">Conecte uma conta alternativa ao seu perfil principal da Columbia OS.</p>
        
        <!-- O form apontará para o script que criaremos no futuro para validar e inserir no banco -->
        <form action="../actions/processar_vinculo.php" method="POST" class="input-group">
            <input type="text" name="username" class="input-twitter" placeholder="Celular, e-mail ou @nome_de_usuario" required autocomplete="off">
            <input type="password" name="password" class="input-twitter" placeholder="Senha" required>
            <button type="submit" class="btn-login">Conectar Conta</button>
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn-cancel">Cancelar</a>
        </form>
    </div>
</body>
</html>