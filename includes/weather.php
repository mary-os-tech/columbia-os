<?php
// includes/weather.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDynamicWeather($ajax_lat = null, $ajax_lon = null) {
    global $conexao;

    // Suppress warnings if called directly via AJAX where $conexao isn't global yet
    if (!isset($conexao)) {
        @include_once __DIR__ . '/conexao.php';
    }

    
    $api_key = getenv('OPENWEATHER_API_KEY');

    $is_ny = false;
    if (isset($conexao)) {
        $sql_check = "SELECT setting_value FROM settings WHERE setting_key = 'current_location'";
        $res_check = $conexao->query($sql_check);

        if ($res_check && $row = $res_check->fetch_assoc()) {
            if ($row['setting_value'] === 'NY') {
                $is_ny = true;
            }
        }
    }

// 2. Construct API URL based on Strict Priority

if ($ajax_lat !== null && $ajax_lon !== null) {

    // Highest Priority: Incoming AJAX request (Real-time GPS)

    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$ajax_lat}&lon={$ajax_lon}&units=metric&appid={$api_key}";

} elseif ($is_ny) {

    // Second Priority: Database says we are in NY (Using q= parameter)

    $url = "https://api.openweathermap.org/data/2.5/weather?q=Manhattan,US&units=metric&appid={$api_key}";

} else {

    // Third Priority: Session memory or São Paulo fallback

    $lat = $_SESSION['user_lat'] ?? "-23.5505";

    $lon = $_SESSION['user_lon'] ?? "-46.6333";

    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&units=metric&appid={$api_key}";

    }



    $fallback = [

        'temp' => 20,

        'condition' => 'Clear',

        'icon' => '☀️',

        'city' => 'São Paulo, BR'

    ];



    if (empty($api_key)) {

        return $fallback;

    }



    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    $response = curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);



    if ($http_code === 200 && $response) {

        $data = json_decode($response, true);

        if (isset($data['main']['temp']) && isset($data['weather'][0]['main'])) {

            $temp = round($data['main']['temp']);

            $condition = $data['weather'][0]['main'];

           

            // Extract City and Country Code

            $city = isset($data['name']) ? $data['name'] : 'Unknown';

            $country = isset($data['sys']['country']) ? $data['sys']['country'] : '';

            $location_name = $country ? "{$city}, {$country}" : $city;

           

            $icon = '☁️';

            switch (strtolower($condition)) {

                case 'clear': $icon = '☀️'; break;

                case 'clouds': $icon = '☁️'; break;

                case 'rain':

                case 'drizzle': $icon = '🌧️'; break;

                case 'thunderstorm': $icon = '⛈️'; break;

                case 'snow': $icon = '❄️'; break;

                case 'mist':

                case 'fog':

                case 'haze': $icon = '🌫️'; break;

            }



            return [

                'temp' => $temp,

                'condition' => $condition,

                'icon' => $icon,

                'city' => $location_name

            ];

        }

    }

    return $fallback;

}



// --- STRICT AJAX JSON ENDPOINT LOGIC ---

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lat']) && isset($_POST['lon'])) {

    // Purge any stray whitespace or PHP notices from included files

    if (ob_get_length()) ob_clean();

   

    header('Content-Type: application/json');

   

    $_SESSION['user_lat'] = filter_var($_POST['lat'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $_SESSION['user_lon'] = filter_var($_POST['lon'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

   

    $weather = getDynamicWeather($_POST['lat'], $_POST['lon']);

    echo json_encode(['status' => 'success', 'weather' => $weather]);

    exit; // Terminate script immediately

}



// If included synchronously by index.php / sidebar.php

$current_weather = getDynamicWeather();
?> 

