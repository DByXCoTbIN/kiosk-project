<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';

if (!isset($_GET['id'])) {
    header('Location: manage_teachers.php?error=no_id');
    exit;
}

$db = new FileDatabase();
$teacherId = (int)$_GET['id'];

try {
    // Проверяем, существует ли педагог
    $teacher = $db->getTeacherById($teacherId);
    if (!$teacher) {
        header('Location: manage_teachers.php?error=not_found');
        exit;
    }
    
    // Проверяем, не используется ли педагог в расписании
    $schedules = $db->getAllSchedule();
    $isUsedInSchedule = false;
    
    foreach ($schedules as $schedule) {
        if (isset($schedule['teacher_id']) && $schedule['teacher_id'] == $teacherId) {
            $isUsedInSchedule = true;
            break;
        }
    }
    
    if ($isUsedInSchedule) {
        header('Location: manage_teachers.php?error=used_in_schedule');
        exit;
    }
    
    // Пытаемся удалить педагога
    $deleteResult = $db->deleteTeacher($teacherId);
    
    if ($deleteResult) {
        header('Location: manage_teachers.php?success=deleted');
    } else {
        // Добавляем отладочную информацию
        error_log("Failed to delete teacher with ID: " . $teacherId);
        header('Location: manage_teachers.php?error=delete_failed&debug=method_returned_false');
    }
    
} catch (Exception $e) {
    error_log("Exception when deleting teacher: " . $e->getMessage());
    header('Location: manage_teachers.php?error=delete_failed&debug=exception');
}
exit;
?>