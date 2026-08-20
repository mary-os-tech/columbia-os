<?php
session_start();
include(__DIR__ . '/../includes/config.php');
if (!isset($_SESSION['username'])) {
    header("Location: ../index.php");
    exit();
}

include_once '../includes/conexao.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. Securely Fetch or Initialize RPG Stats using Prepared Statements
$stmt = $conexao->prepare("SELECT energy, stress, money, affinity, focus_points FROM player_stats WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $stats = $result->fetch_assoc();
} else {
    // Initialize default stats if this is the first time accessing Canvas
    $stmt_init = $conexao->prepare("INSERT INTO player_stats (user_id, energy, stress, money, focus_points, affinity) VALUES (?, 100, 0, 0.00, 0, 50)");
    $stmt_init->bind_param("i", $user_id);
    $stmt_init->execute();
    $stmt_init->close();
    
    $stats = [
        'energy' => 100,
        'stress' => 0,
        'money' => 0.00,
        'affinity' => 50,
        'focus_points' => 0
    ];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canvas - Columbia University</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="canvas-app">
        <!-- Academic Header -->
        <header class="canvas-navbar">
            <h1><i class="fa-solid fa-graduation-cap"></i> CourseWorks (Canvas)</h1>
            <div class="canvas-user-info">
                Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="canvas-content">
            
           <!-- RPG Stats Grid -->
           <div class="canvas-stats-grid">
                <div class="canvas-stat-card">
                    <div class="canvas-stat-title">Energy</div>
                    <div class="canvas-stat-value energy" id="stat-energy"><?php echo $stats['energy']; ?>%</div>
                </div>
                <div class="canvas-stat-card">
                    <div class="canvas-stat-title">Stress</div>
                    <div class="canvas-stat-value stress" id="stat-stress"><?php echo $stats['stress']; ?>%</div>
                </div>
                <div class="canvas-stat-card">
                    <div class="canvas-stat-title">Focus Points</div>
                    <div class="canvas-stat-value focus" id="stat-focus"><?php echo $stats['focus_points']; ?></div>
                </div>
                <div class="canvas-stat-card">
                    <div class="canvas-stat-title">Balance</div>
                    <div class="canvas-stat-value money" id="stat-money">$<?php echo number_format($stats['money'], 2); ?></div>
                </div>
                <div class="canvas-stat-card">
                    <div class="canvas-stat-title">Lottie Affinity</div>
                    <div class="canvas-stat-value affinity" id="stat-affinity"><?php echo $stats['affinity']; ?>/100</div>
                </div>
            </div>

            <!-- Pomodoro Timer UI -->
            <div class="canvas-pomodoro-box">
                <h2 class="canvas-course-title">CS Curriculum Focus</h2>
                <p class="canvas-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i> Focus session active. Switching browser tabs will result in an academic penalty (Stress +15%).
                </p>

                <div class="canvas-timer-circle" id="timer-circle">
                    <span class="canvas-time" id="timer-display">25:00</span>
                </div>

                <button class="canvas-btn" id="btn-start-study">Begin Study Session</button>
            </div>

        </main>
    </div>

  <!-- Pomodoro Logic -->
  <script>
        $(document).ready(function() {
            let timerInterval;
            // Set to 60 seconds for testing. Change to 1500 (25 * 60) for production.
            let totalSeconds = 60; 
            let timeLeft = totalSeconds;
            let isTimerRunning = false;

            const $display = $('#timer-display');
            const $circle = $('#timer-circle');
            const $btnStart = $('#btn-start-study');


            function updateDisplay(seconds) {
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                $display.text(`${m}:${s}`);
            }

            function updateUIStats(data) {
                // Update text values dynamically
                $('#stat-energy').text(data.energy + '%');
                $('#stat-stress').text(data.stress + '%');
                $('#stat-focus').text(data.focus_points);
                
                // Format money to 2 decimal places
                let formattedMoney = parseFloat(data.money).toFixed(2);
                $('#stat-money').text('$' + formattedMoney);
                
                // Visual flash effect to indicate live update
                $('.canvas-stat-value').fadeOut(150).fadeIn(150);
            }

            function resetTimerUI() {
                clearInterval(timerInterval);
                isTimerRunning = false;
                timeLeft = totalSeconds;
                updateDisplay(timeLeft);
                $circle.removeClass('active');
                $btnStart.prop('disabled', false).text('Begin Study Session');
            }

            // 1. Start Button Logic
            $btnStart.on('click', function() {
                if (isTimerRunning) return;
                
                isTimerRunning = true;
                $btnStart.prop('disabled', true).text('Session in Progress...');
                $circle.addClass('active');

                timerInterval = setInterval(function() {
                    timeLeft--;
                    updateDisplay(timeLeft);

                    if (timeLeft <= 0) {
                        handleSuccess();
                    }
                }, 1000);
            });

            // 2. The Visibility API (Tab-Switching Detection)
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && isTimerRunning) {
                    handlePenalty();
                }
            });

            // 3. Penalty Logic
            function handlePenalty() {
                resetTimerUI();
                
                $.post('../actions/update_stats.php', { action: 'penalty' }, function(response) {
                    if (response.status === 'success') {
                        updateUIStats(response);
                        Swal.fire({
                            icon: 'error',
                            title: 'Academic Penalty!',
                            text: 'You switched tabs during a focus session. Stress increased by 15%.',
                            background: '#161b22',
                            color: '#c9d1d9',
                            confirmButtonColor: '#1D4F91'
                        });
                    }
                }, 'json');
            }

            // 4. Success Logic
            function handleSuccess() {
                resetTimerUI();
                
                $.post('../actions/update_stats.php', { action: 'success' }, function(response) {
                    if (response.status === 'success') {
                        updateUIStats(response);
                        Swal.fire({
                            icon: 'success',
                            title: 'Session Complete!',
                            text: 'Excellent focus. Energy +20%, Focus Points +5.',
                            background: '#161b22',
                            color: '#c9d1d9',
                            confirmButtonColor: '#2ea043'
                        });
                    }
                }, 'json');
            }

            // Initialize display on load
            updateDisplay(timeLeft);
        });
    </script>
</body>
</html>