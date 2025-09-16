<?php
require_once 'config/file_database.php';

if (isset($_GET['id'])) {
    $teacherId = intval($_GET['id']);
    $db = new FileDatabase();
    $teacher = $db->getTeacherById($teacherId);

    if ($teacher) {
        header('Content-Type: application/json');
        echo json_encode($teacher);
    } else {
        http_response_code(404);
        echo json_encode(array('error' => 'Teacher not found'));
    }
} else {
    http_response_code(400);
    echo json_encode(array('error' => 'No teacher ID provided'));
}
