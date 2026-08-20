<?php
// actions/save_coords.php
session_start();
include_once '../includes/conexao.php';
include_once '../includes/weather.php';

if (isset($_POST['lat']) && isset($_POST['lon'])) {
    $_SESSION['user_lat'] = filter_var($_POST['lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $_SESSION['user_lon'] = filter_var($_POST['lon'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Fetch the new weather based on the newly saved session coordinates
    $new_weather = getDynamicWeather();
    
    echo json_encode([
        'status' => 'success',
        'weather' => $new_weather
    ]);
} else {
    echo json_encode(['status' => 'error']);
}
?>