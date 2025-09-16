<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';

if (!isset($_GET['id'])) {
    header('Location: manage_groups.php?error=no_id');
    exit;
}

$db = new FileDatabase();
$groupId = (int)$_GET['id'];

try {
    // Проверяем, существует ли коллектив
    $group = $db->getGroupById($groupId);
    if (!$group) {
        header('Location: manage_groups.php?error=not_found');
        exit;
    }
    
    // Проверяем, не используется ли коллектив в расписании
    $schedules = $db->getAllSchedule();
    $isUsedInSchedule = false;
    
    foreach ($schedules as $schedule) {
        if (isset($schedule['group_id']) && $schedule['group_id'] == $groupId) {
            $isUsedInSchedule = true;
            break;
        }
    }
    
    if ($isUsedInSchedule) {
        header('Location: manage_groups.php?error=used_in_schedule');
        exit;
    }
    
    // Пытаемся удалить коллектив
    $deleteResult = $db->deleteGroup($groupId);
    
    if ($deleteResult) {
        header('Location: manage_groups.php?success=deleted');
    } else {
        // Добавляем отладочную информацию
        error_log("Failed to delete group with ID: " . $groupId);
        header('Location: manage_groups.php?error=delete_failed&debug=method_returned_false');
    }
    
} catch (Exception $e) {
    error_log("Exception when deleting group: " . $e->getMessage());
    header('Location: manage_groups.php?error=delete_failed&debug=exception');
}
exit;
?>