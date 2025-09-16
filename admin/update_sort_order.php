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

if (!$input || !isset($input['group_order']) || !is_array($input['group_order'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$groupOrder = $input['group_order'];

try {
    $db = new FileDatabase();

    // Получаем все группы
    $groups = $db->getAllGroups();

    // Обновляем sort_order для каждой группы
    foreach ($groups as &$group) {
        $groupId = $group['id'];
        if (isset($groupOrder[$groupId])) {
            $group['sort_order'] = (int)$groupOrder[$groupId];
            $group['updated_at'] = date('Y-m-d H:i:s');
        }
    }

    // Сохраняем обновленные данные
    $result = file_put_contents('../config/data/groups.json', json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($result === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save data']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Sort order updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
