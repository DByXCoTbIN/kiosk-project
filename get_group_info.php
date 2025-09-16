<?php
session_start();
require_once 'config/file_database.php';

if (isset($_GET['id'])) {
    $groupId = intval($_GET['id']);
    $db = new FileDatabase();
    $group = $db->getGroupById($groupId);

    if ($group) {
        // Проверяем видимость для обычных пользователей
        $isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
        if ($isAdmin || !isset($group['visibility']) || $group['visibility']) {
            header('Content-Type: application/json');
            echo json_encode($group);
        } else {
            http_response_code(403);
            echo json_encode(array('error' => 'Group not visible'));
        }
    } else {
        http_response_code(404);
        echo json_encode(array('error' => 'Group not found'));
    }
} else {
    http_response_code(400);
    echo json_encode(array('error' => 'No group ID provided'));
}
