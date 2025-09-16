<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/file_database.php';

header('Content-Type: application/json');

// Получаем данные из POST запроса
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['group_id']) || !isset($input['visibility'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$groupId = (int)$input['group_id'];
$visibility = (bool)$input['visibility'];

try {
    $db = new FileDatabase();

    // Получаем все группы
    $groups = $db->getAllGroups();

    // Ищем группу по ID
    $groupFound = false;
    foreach ($groups as &$group) {
        if ($group['id'] == $groupId) {
            $group['visibility'] = $visibility;
            $group['updated_at'] = date('Y-m-d H:i:s');
            $groupFound = true;
            break;
        }
    }

    if (!$groupFound) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        exit;
    }

    // Сохраняем обновленные данные
    $result = file_put_contents('../config/data/groups.json', json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($result === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save data']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Visibility updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
