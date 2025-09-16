<?php
// Установка временной зоны
date_default_timezone_set('Europe/Moscow');

// Установка заголовка для JSON
header('Content-Type: application/json; charset=utf-8');

// Получение текущей даты и времени
$current_time = date("H:i:s");
$day_of_week = date("l");

// Перевод дня недели на русский
$days_ru = array(
    'Monday' => 'Понедельник',
    'Tuesday' => 'Вторник', 
    'Wednesday' => 'Среда',
    'Thursday' => 'Четверг',
    'Friday' => 'Пятница',
    'Saturday' => 'Суббота',
    'Sunday' => 'Воскресенье'
);

$day_of_week_ru = isset($days_ru[$day_of_week]) ? $days_ru[$day_of_week] : 'Неизвестный день';

// Возврат данных в формате JSON
echo json_encode(array(
    'time' => $current_time,
    'day' => $day_of_week_ru,
    'day_en' => $day_of_week,
    'date' => date("d.m.Y")
), JSON_UNESCAPED_UNICODE);
?> 