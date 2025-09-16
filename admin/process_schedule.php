<?php
require_once '../config/file_database.php';
$db = new FileDatabase();

// Собираем данные с формы, учитывая множественный выбор
$scheduleData = [
    'teacher' => $_POST['teacher'],
    'group' => $_POST['group'],
    'monday' => $_POST['monday'] ?? [],
    'tuesday' => $_POST['tuesday'] ?? [],
    // Аналогично для других дней
];

$result = $db->addSchedule($scheduleData);