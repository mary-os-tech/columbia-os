<?php
// actions/update_db_and_seed.php
include '../includes/conexao.php';

echo "<div style='background:#000; color:#e7e9ea; padding:20px; font-family:sans-serif;'>";
echo "<h3>Atualizando Banco de Dados - Columbia OS</h3>";

// 1. Alter Table Schema (Adicionando colunas se não existirem)
$check_senha = $conexao->query("SHOW COLUMNS FROM perfis LIKE 'senha'");
if ($check_senha->num_rows == 0) {
    $conexao->query("ALTER TABLE perfis ADD senha VARCHAR(255) NOT NULL AFTER username");
    echo "✅ Coluna 'senha' adicionada com sucesso.<br>";
}

$check_alt = $conexao->query("SHOW COLUMNS FROM perfis LIKE 'is_alt_account'");
if ($check_alt->num_rows == 0) {
    $conexao->query("ALTER TABLE perfis ADD is_alt_account TINYINT(1) DEFAULT 0 AFTER bio");
    echo "✅ Coluna 'is_alt_account' adicionada com sucesso.<br>";
}

// 2. Create the Linked Accounts Table
$sql_table = "CREATE TABLE IF NOT EXISTS contas_vinculadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conta_principal_id INT NOT NULL,
    conta_alt_id INT NOT NULL
)";
if ($conexao->query($sql_table)) {
    echo "✅ Tabela 'contas_vinculadas' verificada/criada.<br>";
}

// 3. Seed Player-Only Accounts
// Inserir ou recuperar ID da conta principal (Mary)
$id_mary = 0;
$res_mary = $conexao->query("SELECT id FROM perfis WHERE username = 'mary'");
if ($res_mary->num_rows == 0) {
    $conexao->query("INSERT INTO perfis (nome, username, senha, is_alt_account) VALUES ('Mary', 'mary', '123', 0)");
    $id_mary = $conexao->insert_id;
    echo "👤 Conta principal 'mary' criada.<br>";
} else {
    $id_mary = $res_mary->fetch_assoc()['id'];
}

// Inserir ou recuperar ID da conta alt (Mary Private)
$id_alt = 0;
$res_alt = $conexao->query("SELECT id FROM perfis WHERE username = 'mary_alt'");
if ($res_alt->num_rows == 0) {
    $conexao->query("INSERT INTO perfis (nome, username, senha, is_alt_account) VALUES ('Mary Private 🔒', 'mary_alt', '123', 1)");
    $id_alt = $conexao->insert_id;
    echo "🕵️‍♀️ Conta secreta 'mary_alt' criada.<br>";
} else {
    $id_alt = $res_alt->fetch_assoc()['id'];
}

// Vincular as contas na tabela contas_vinculadas
if ($id_mary > 0 && $id_alt > 0) {
    $res_link = $conexao->query("SELECT id FROM contas_vinculadas WHERE conta_principal_id = $id_mary AND conta_alt_id = $id_alt");
    if ($res_link->num_rows == 0) {
        $conexao->query("INSERT INTO contas_vinculadas (conta_principal_id, conta_alt_id) VALUES ($id_mary, $id_alt)");
        echo "🔗 Vínculo entre 'mary' e 'mary_alt' estabelecido com sucesso!<br>";
    } else {
        echo "🔗 As contas já estavam vinculadas.<br>";
    }
}

echo "<br><strong>🚀 Atualização concluída! Acesse o login.php e entre com 'mary' e senha '123'.</strong>";
echo "</div>";
?>