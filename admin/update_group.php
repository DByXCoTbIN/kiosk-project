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

if (!$input || !isset($input['group_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$groupId = (int)$input['group_id'];
$updateData = $input['data'] ?? [];

try {
    $db = new FileDatabase();

    // Получаем все группы
    $groups = $db->getAllGroups();

    // Ищем и обновляем группу
    $groupFound = false;
    foreach ($groups as &$group) {
        if ($group['id'] == $groupId) {
            // Обновляем только разрешенные поля
            $allowedFields = ['name', 'direction', 'description', 'age_group', 'duration', 'price'];
            foreach ($allowedFields as $field) {
                if (isset($updateData[$field])) {
                    $group[$field] = trim($updateData[$field]);
                }
            }

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

    echo json_encode(['success' => true, 'message' => 'Group updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
