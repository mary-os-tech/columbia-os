<?php
session_start();
include 'includes/conexao.php';

// If already logged in, redirect directly to the feed using relative path
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$erro = "";
// Retrieve session error (PRG Pattern) to prevent form resubmission alerts
if (isset($_SESSION['login_erro'])) {
    $erro = $_SESSION['login_erro'];
    unset($_SESSION['login_erro']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $user = $conexao->real_escape_string($_POST['username']);
    $pass = $_POST['password'];

    // Search for the user in the database
    $sql = "SELECT * FROM perfis WHERE username = '$user' LIMIT 1";
    $res = $conexao->query($sql);

    if ($res && $res->num_rows > 0) {
        $perfil = $res->fetch_assoc();
        
        // Password validation (direct comparison with the database)
        if ($pass === $perfil['senha']) {
            $_SESSION['user_id'] = $perfil['id'];
            $_SESSION['username'] = $perfil['username'];
            
            // FORCE session write to disk before changing pages
            session_write_close();
            
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['login_erro'] = "Invalid credentials in the Columbia OS system.";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['login_erro'] = "Invalid credentials in the Columbia OS system.";
        header("Location: login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in to Status</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background-color: #000; color: #e7e9ea; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { width: 100%; max-width: 400px; padding: 30px; display: flex; flex-direction: column; gap: 20px; }
        .logo { width: 40px; height: 40px; fill: #e7e9ea; align-self: center; margin-bottom: 10px; }
        h1 { font-size: 1.8rem; font-weight: bold; margin-bottom: 10px; }
        .input-group { display: flex; flex-direction: column; gap: 15px; }
        .input-twitter { background-color: transparent; border: 1px solid #333639; border-radius: 4px; color: #e7e9ea; padding: 16px 10px; font-size: 1rem; outline: none; transition: 0.2s; }
        .input-twitter:focus { border-color: #1d9bf0; box-shadow: 0 0 0 1px #1d9bf0; }
        .btn-login { background-color: #e7e9ea; color: #0f1419; border: none; border-radius: 9999px; padding: 15px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-login:hover { background-color: #d7dbdc; }
        .btn-signup { background-color: transparent; color: #1d9bf0; border: 1px solid #536471; border-radius: 9999px; padding: 15px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.2s; text-align: center; text-decoration: none; margin-top: 5px; }
        .btn-signup:hover { background-color: rgba(29, 155, 240, 0.1); border-color: #1d9bf0; }
        .erro-msg { color: #f4212e; font-size: 0.9rem; text-align: center; background: rgba(244, 33, 46, 0.1); padding: 10px; border-radius: 4px; }
        .lore-text { text-align: center; color: #71767b; font-size: 0.8rem; margin-top: 20px; }
    </style>
</head>
<body>
<div class="login-box">
        <svg class="logo" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 22.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path></svg>
        <h1>Sign in to Status</h1>
        
        <?php if($erro): ?>
            <div class="erro-msg"><?php echo $erro; ?></div>
        <?php endif; ?>

        <form method="POST" class="input-group">
            <input type="text" name="username" class="input-twitter" placeholder="Phone, email, or @username" required autocomplete="off">
            <input type="password" name="password" class="input-twitter" placeholder="Password" required>
            <button type="submit" class="btn-login">Next</button>
            <a href="pages/cadastro.php" class="btn-signup">Sign up</a>
        </form>
        <div class="lore-text">Columbia University OS - Secure Login</div>
    </div>
</body>
</html>