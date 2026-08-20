<?php
session_start();
include '../includes/conexao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !isset($_POST['transaction_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$username = $conexao->real_escape_string($_SESSION['username']);
$transaction_id = (int) $_POST['transaction_id'];

// Busca o ID do usuário (Mary)
$sql_user = "SELECT id FROM perfis WHERE username = '$username' LIMIT 1";
$res_user = $conexao->query($sql_user);

if ($res_user && $res_user->num_rows > 0) {
    $row = $res_user->fetch_assoc();
    $user_id = $row['id'];

    // Pega o valor da transação e o status dela
    $sql_trans = "SELECT amount, status FROM transactions WHERE id = $transaction_id";
    $res_trans = $conexao->query($sql_trans);
    
    if ($res_trans && $res_trans->num_rows > 0) {
        $trans_row = $res_trans->fetch_assoc();
        $amount = (float) $trans_row['amount'];
        $status = $trans_row['status'];

        if ($status === 'completed') {
            echo json_encode(['status' => 'error', 'message' => 'Transaction already paid.']);
            exit;
        }

        // Verifica o saldo da Mary
        $sql_stats = "SELECT money FROM player_stats WHERE user_id = $user_id";
        $res_stats = $conexao->query($sql_stats);
        $stats_row = $res_stats->fetch_assoc();
        $current_money = (float) $stats_row['money'];

        if ($current_money >= $amount) {
            // Deduz o dinheiro e atualiza a transação numa tacada só
            $conexao->query("UPDATE player_stats SET money = money - $amount WHERE user_id = $user_id");
            $conexao->query("UPDATE transactions SET status = 'completed' WHERE id = $transaction_id");
            
            // Retorna o novo saldo para o front-end
            $new_balance = $current_money - $amount;
            echo json_encode(['status' => 'success', 'new_balance' => number_format($new_balance, 2)]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Insufficient funds. Go do some freelance work!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'User error.']);
}
?>