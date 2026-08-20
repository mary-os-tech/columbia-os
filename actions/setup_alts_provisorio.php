<?php
require_once '../includes/conexao.php';

// 1. Cria a tabela de vínculos caso não exista
$conexao->query("CREATE TABLE IF NOT EXISTS contas_vinculadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    main_id INT NOT NULL,
    alt_id INT NOT NULL,
    UNIQUE KEY unique_link (main_id, alt_id)
)");

// 2. Insere a conta principal (Mary)
$conexao->query("INSERT IGNORE INTO perfis (username, autor, avatar) VALUES ('mary', 'Mary', '👩‍💻')");
$result_main = $conexao->query("SELECT id FROM perfis WHERE username = 'mary'");
$main_id = $result_main->fetch_assoc()['id'];

// 3. Insere as contas secretas/alts
$conexao->query("INSERT IGNORE INTO perfis (username, autor, avatar) VALUES ('lottie_secret', 'Lottie 🕵️‍♀️', '🌻')");
$conexao->query("INSERT IGNORE INTO perfis (username, autor, avatar) VALUES ('void_coder', 'void', '⬛')");

$result_alt1 = $conexao->query("SELECT id FROM perfis WHERE username = 'lottie_secret'");
$alt1_id = $result_alt1->fetch_assoc()['id'];

$result_alt2 = $conexao->query("SELECT id FROM perfis WHERE username = 'void_coder'");
$alt2_id = $result_alt2->fetch_assoc()['id'];

// 4. Vincula as contas alts à conta principal
$conexao->query("INSERT IGNORE INTO contas_vinculadas (main_id, alt_id) VALUES ($main_id, $alt1_id)");
$conexao->query("INSERT IGNORE INTO contas_vinculadas (main_id, alt_id) VALUES ($main_id, $alt2_id)");

echo "Contas provisórias criadas e vinculadas com sucesso!";
?>