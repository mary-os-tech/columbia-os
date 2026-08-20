<?php
session_start();
include(__DIR__ . '/../includes/config.php');
require_once '../includes/conexao.php';

$username = $_SESSION['username'] ?? 'mary';

// Fetch user_id based on the active session
$stmtUser = $conexao->prepare("SELECT id FROM perfis WHERE username = ?");
$stmtUser->bind_param("s", $username);
$stmtUser->execute();
$user_id = $stmtUser->get_result()->fetch_assoc()['id'] ?? 0;

// FAKE DATA INJECTION: If transactions table is empty, create a pending charge from Lottie
$resTx = $conexao->query("SELECT COUNT(*) as total FROM transactions");
$checkTx = $resTx->fetch_assoc()['total'] ?? 0;

if ($checkTx == 0) {
    $insertTx = $conexao->prepare("INSERT INTO transactions (sender_username, receiver_username, amount, description, status) VALUES (?, ?, ?, ?, ?)");
    $receiver = 'lottiematthews';
    $amount = 15.50;
    $desc = 'Late night pizza 🍕';
    $status = 'pending';
    $insertTx->bind_param("ssdss", $username, $receiver, $amount, $desc, $status);
    $insertTx->execute();
}

// Fetch Current Balance
$stmtMoney = $conexao->prepare("SELECT money FROM player_stats WHERE user_id = ?");
$stmtMoney->bind_param("i", $user_id);
$stmtMoney->execute();
$balance = $stmtMoney->get_result()->fetch_assoc()['money'] ?? 0.00;

// Fetch Transaction History
$stmtHistory = $conexao->prepare("SELECT * FROM transactions WHERE sender_username = ? OR receiver_username = ? ORDER BY timestamp DESC");
$stmtHistory->bind_param("ss", $username, $username);
$stmtHistory->execute();
$resHistory = $stmtHistory->get_result();

$transactions = [];
while ($row = $resHistory->fetch_assoc()) {
    $transactions[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venmo - Columbia OS</title>
    
    <!-- External Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Standalone Fintech Aesthetic */
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .venmo-app {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 85vh;
        }

        .venmo-header {
            background-color: #008CFF;
            color: #ffffff;
            padding: 40px 20px 30px;
            text-align: center;
            position: relative;
        }

        .venmo-logo {
            font-size: 24px;
            font-weight: 700;
            font-style: italic;
            letter-spacing: -1px;
            margin-bottom: 5px;
        }

        .venmo-handle {
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .venmo-balance {
            font-size: 52px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -2px;
        }

        .venmo-balance-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.8;
        }

        .venmo-feed {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #ffffff;
        }

        .feed-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .tx-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .tx-card:last-child {
            border-bottom: none;
        }

        .tx-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .tx-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #e0f0ff;
            color: #008CFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .tx-details p {
            margin: 0;
        }

        .tx-title {
            font-size: 14px;
            font-weight: 600;
            color: #111;
        }

        .tx-desc {
            font-size: 13px;
            color: #666;
            margin-top: 2px !important;
        }

        .tx-status {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 4px !important;
            display: inline-block;
        }

        .status-pending { color: #f5a623; }
        .status-completed { color: #28a745; }

        .tx-right {
            text-align: right;
        }

        .tx-amount {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }

        .amount-negative { color: #111; }
        .amount-positive { color: #28a745; }

        .btn-pay {
            background-color: #008CFF;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s;
        }

        .btn-pay:hover {
            background-color: #0077d9;
        }
        
        /* Custom Scrollbar */
        .venmo-feed::-webkit-scrollbar { width: 6px; }
        .venmo-feed::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
    </style>
</head>
<body>

<div class="venmo-app">
    <!-- Header Section -->
    <div class="venmo-header">
        <div class="venmo-logo"><i class="fa-brands fa-vimeo-v"></i>enmo</div>
        <div class="venmo-handle">@<?php echo htmlspecialchars($username); ?></div>
        <div class="venmo-balance" id="venmo-balance">$<?php echo number_format($balance, 2); ?></div>
        <div class="venmo-balance-label">Venmo Balance</div>
    </div>

    <!-- Transactions Feed -->
    <div class="venmo-feed">
        <div class="feed-title">Recent Activity</div>
        
        <?php if (empty($transactions)): ?>
            <p style="text-align: center; color: #888; font-size: 14px; margin-top: 30px;">No transactions yet.</p>
        <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
                <?php 
                    $isSender = ($tx['sender_username'] === $username);
                    $otherUser = $isSender ? $tx['receiver_username'] : $tx['sender_username'];
                    $initial = strtoupper(substr($otherUser, 0, 1));
                ?>
                <div class="tx-card" id="tx-<?php echo $tx['id']; ?>">
                    <div class="tx-left">
                        <div class="tx-avatar"><?php echo $initial; ?></div>
                        <div class="tx-details">
                            <p class="tx-title">
                                <?php echo $isSender ? "You paid @" . htmlspecialchars($otherUser) : "@" . htmlspecialchars($otherUser) . " paid You"; ?>
                            </p>
                            <p class="tx-desc"><?php echo htmlspecialchars($tx['description']); ?></p>
                            <p class="tx-status <?php echo $tx['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                <?php echo htmlspecialchars($tx['status']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="tx-right">
                        <p class="tx-amount <?php echo $isSender ? 'amount-negative' : 'amount-positive'; ?>">
                            <?php echo $isSender ? '-' : '+'; ?>$<?php echo number_format($tx['amount'], 2); ?>
                        </p>
                        <?php if ($tx['status'] === 'pending' && $isSender): ?>
                            <button class="btn-pay" onclick="processVenmoPayment(<?php echo $tx['id']; ?>, <?php echo $tx['amount']; ?>)">
                                Pay
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// FIX 3: State Sync. Automatically refresh the page to fetch new DB balances when the user switches back to this tab.
window.addEventListener('focus', function() {
    // Only reload if there are no active SweetAlert modals open (prevents interrupting the payment flow)
    if (!Swal.isVisible()) {
        location.reload();
    }
});

function processVenmoPayment(transactionId, amount) {
    Swal.fire({
        title: 'Pay $' + amount.toFixed(2) + '?',
        text: "This will be deducted from your balance.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#008CFF',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm Payment',
        borderRadius: '15px'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                // FIX: Relative path traversing up one directory to reach /actions
                url: '../actions/pay_venmo.php', 
                type: 'POST',
                data: { transaction_id: transactionId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Paid!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#008CFF'
                        });
                        
                        // Update DOM dynamically
                        $('#venmo-balance').text('$' + response.new_balance.toFixed(2));
                        
                        let statusEl = $('#tx-' + transactionId + ' .tx-status');
                        statusEl.text('completed');
                        statusEl.removeClass('status-pending').addClass('status-completed');
                        
                        $('#tx-' + transactionId + ' .btn-pay').fadeOut();
                    } else {
                        Swal.fire('Declined', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    Swal.fire('404 Error', 'Could not reach pay_venmo.php. Check your paths!', 'error');
                }
            });
        }
    });
}
</script>

</body>
</html>