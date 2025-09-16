<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();

// Получаем все данные расписания
$scheduleData = $db->getAllSchedule();
$teachers = $db->getAllTeachers();
$groups = $db->getAllGroups();

// Группируем данные по педагогам (как на главной странице)
$groupedTeachers = [];

foreach ($scheduleData as $row) {
    $teacherId = $row['teacher_id'];
    if (!isset($groupedTeachers[$teacherId])) {
        $groupedTeachers[$teacherId] = [
            'teacher' => $db->getTeacherById($teacherId),
            'groups' => []
        ];
    }

    // Получаем информацию о группе
    $group = $db->getGroupById($row['group_id']);

    // Для обычных пользователей скрываем группы с visibility: false
    $isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] === true;
    if ($isAdmin || !isset($group['visibility']) || $group['visibility']) {
        $groupedTeachers[$teacherId]['groups'][] = $row;
    }
}

// Сортируем группы внутри каждого преподавателя по sort_order
foreach ($groupedTeachers as $teacherId => &$data) {
    usort($data['groups'], function($a, $b) use ($db) {
        $groupA = $db->getGroupById($a['group_id']);
        $groupB = $db->getGroupById($b['group_id']);

        $sortA = isset($groupA['sort_order']) ? $groupA['sort_order'] : 999;
        $sortB = isset($groupB['sort_order']) ? $groupB['sort_order'] : 999;

        return $sortA <=> $sortB;
    });
}

// Убираем педагогов без видимых групп
foreach ($groupedTeachers as $teacherId => $data) {
    if (empty($data['groups'])) {
        unset($groupedTeachers[$teacherId]);
    }
}

// Создаем массив для хранения всех записей расписания в формате главной страницы
$exportData = [];

// Дни недели в том же порядке, что и на главной странице
$daysOfWeek = [
    'Monday' => 'Понедельник',
    'Tuesday' => 'Вторник',
    'Wednesday' => 'Среда',
    'Thursday' => 'Четверг',
    'Friday' => 'Пятница',
    'Saturday' => 'Суббота',
    'Sunday' => 'Воскресенье'
];

// Функция для форматирования расписания (как на главной странице)
function formatScheduleForExport($scheduleRow, $day)
{
    $dayMap = array(
        'Monday' => 'monday',
        'Tuesday' => 'tuesday',
        'Wednesday' => 'wednesday',
        'Thursday' => 'thursday',
        'Friday' => 'friday',
        'Saturday' => 'saturday',
        'Sunday' => 'sunday'
    );

    $dayKey = isset($dayMap[$day]) ? $dayMap[$day] : strtolower($day);
    $timeSlots = array();

    if (isset($scheduleRow['schedule'][$dayKey]) && is_array($scheduleRow['schedule'][$dayKey])) {
        // Сортировка по времени начала
        usort($scheduleRow['schedule'][$dayKey], function($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        foreach ($scheduleRow['schedule'][$dayKey] as $slot) {
            if (is_array($slot) && !empty($slot['start_time']) && !empty($slot['end_time'])) {
                $timeSlot = $slot['start_time'] . '-' . $slot['end_time'];

                // Добавляем комнату, если указана
                if (!empty($slot['room'])) {
                    $timeSlot .= ' (' . $slot['room'] . ')';
                }

                // Добавляем заметки, если указаны
                if (!empty($slot['notes'])) {
                    $timeSlot .= ' - ' . $slot['notes'];
                }

                array_push($timeSlots, $timeSlot);
            }
        }
    }

    $scheduleText = implode("\n", $timeSlots);
    // Убираем <br> теги и заменяем на переносы строк
    $scheduleText = str_replace(['<br>', '<br/>', '<br />'], "\n", $scheduleText);
    // Убираем другие возможные HTML теги
    $scheduleText = strip_tags($scheduleText);
    return $scheduleText;
}

// Обрабатываем сгруппированные данные
foreach ($groupedTeachers as $teacherId => $data) {
    $teacher = $data['teacher'];
    $groups = $data['groups'];

    foreach ($groups as $groupRow) {
        $group = $db->getGroupById($groupRow['group_id']);

        if (!$teacher || !$group) continue;

        $teacherName = $teacher['full_name'] ?? 'Неизвестно';
        $groupName = $group['name'] ?? 'Неизвестно';

        // Создаем строку с данными в том же формате, что и на главной странице
        $rowData = [
            'Педагог' => $teacherName,
            'Коллектив' => $groupName
        ];

        // Добавляем колонки для каждого дня недели
        foreach ($daysOfWeek as $dayKey => $dayName) {
            $scheduleText = formatScheduleForExport($groupRow, $dayKey);
            $rowData[$dayName] = $scheduleText;
        }

        $exportData[] = $rowData;
    }
}

// Устанавливаем заголовки для скачивания CSV файла
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="schedule_export_' . date('Y-m-d_H-i-s') . '.csv"');

// Создаем поток вывода
$output = fopen('php://output', 'w');

// Записываем BOM для корректного отображения кириллицы в Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Записываем заголовки
fputcsv($output, array_keys($exportData[0] ?? []), ';', '"', '\\');

// Записываем данные
foreach ($exportData as $row) {
    fputcsv($output, $row, ';', '"', '\\');
}

fclose($output);
exit;
?>
