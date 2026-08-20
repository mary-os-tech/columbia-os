<?php
// Detect protocol (http or https)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Get host without port
$host = $_SERVER['HTTP_HOST'];
if (strpos($host, ':') !== false) {
    $host = explode(':', $host)[0];
}

// Detect active port
$port = $_SERVER['SERVER_PORT'];

if ($port == 80 || $port == 443) {
    $url_base = "$protocol://$host/Columbia-os";
} else {
    $url_base = "$protocol://$host:$port/Columbia-os";
}

define('BASE_URL', $url_base);
?>