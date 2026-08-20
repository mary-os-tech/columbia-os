<?php
session_start();
include '../includes/conexao.php';
include '../includes/notifications.php';

header('Content-Type: application/json');

$username = $_SESSION['username'] ?? '';
$action = $_POST['action'] ?? 'check';

if (empty($username)) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

switch ($action) {
    case 'check':
        // Check for new notifications only
        $count = get_unread_count($username);
        echo json_encode([
            'unread' => $count,
            'has_new' => $count > 0
        ]);
        break;
        
    case 'get':
        // Get full notification list
        $notifications = get_notifications($username);
        $formatted = [];
        
        foreach ($notifications as $notif) {
            $formatted[] = [
                'id' => $notif['id'],
                'type' => $notif['type'],
                'message' => $notif['message'],
                'link' => $notif['link'],
                'is_read' => (bool)$notif['is_read'],
                'time' => format_notification_time($notif['created_at']),
                'icon' => get_notification_icon($notif['type'])
            ];
        }
        
        echo json_encode([
            'notifications' => $formatted,
            'unread' => get_unread_count($username)
        ]);
        break;
        
    case 'mark_read':
        $notif_id = $_POST['notification_id'] ?? 0;
        if ($notif_id) {
            mark_notification_read($notif_id, $username);
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'mark_all_read':
        mark_all_read($username);
        echo json_encode(['success' => true]);
        break;
}
?>