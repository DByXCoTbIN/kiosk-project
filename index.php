<?php
session_start();

// Установка временной зоны
date_default_timezone_set('Europe/Moscow');

// Получение текущей даты и времени для первоначальной загрузки
$current_time = date("H:i:s");
$current_date = date("d.m.Y");
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

// Подключение к файловой базе данных
require_once 'config/file_database.php';
$db = new FileDatabase();
$schedule_data = $db->getAllSchedule();

// Функция для форматирования расписания для отображения в главной таблице
function formatScheduleForDisplay($scheduleRow, $day)
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

                // Добавляем класс дня
                $timeSlot = '<span class="time-slot ' . $dayMap[$day] . '">' . $timeSlot . '</span>';
                array_push($timeSlots, $timeSlot);
            }
        }
    }

    return implode('<br>', $timeSlots);
}

// Группируем данные по педагогам
$groupedTeachers = [];

foreach ($schedule_data as $row) {
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
?>


<?php
function safe_image($path)
{
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/html/' . ltrim($path, '/');
    if (file_exists($full_path)) {
        return $path;
    }
    error_log("Image not found: " . $full_path);
    return "/css/img/admin_";
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            min-width: 600px;
            max-width: 1200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea5b 0%, #764ba241 100%);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            padding: 2rem;
        }

        .info-grid {
            display: grid;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            min-width: 120px;
            margin-right: 1rem;
        }

        .info-value {
            color: #666;
            flex: 1;
            white-space: pre-wrap;
        }

        .clickable-cell {
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .clickable-cell:hover {
            background-color: #e3f2fd !important;
            transform: scale(1.02);
        }

        .clickable-cell::after {
            /* content: "ℹ️"; */
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .clickable-cell:hover::after {
            opacity: 1;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .error {
            text-align: center;
            padding: 2rem;
            color: #dc3545;
            background: #f8d7da;
            border-radius: 8px;
            margin: 1rem 0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-body {
                padding: 1rem;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-label {
                min-width: auto;
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }

        .phone {
            position: fixed;
            width: 45px;
            top: 1rem;
            right: 11.5rem;
            filter: invert(20%);

        }

        /* Стили для отдельных временных слотов */
        .time-slot {
            display: inline-block;
            padding: 2px 6px;
            margin-bottom: 4px;
            border-radius: 4px;
            background-color: #e0f7fa;
            /* Общий цвет по умолчанию */
            transition: all 0.3s ease;
        }


        /* Цвета для разных дней */
        .time-slot.monday {
            background-color: rgba(255, 107, 107, 0.2);
            /* Красный */
        }

        .time-slot.tuesday {
            background-color: rgba(255, 202, 87, 0.2);
            /* Оранжевый */
        }

        .time-slot.wednesday {
            background-color: rgba(255, 235, 59, 0.2);
            /* Желтый */
        }

        .time-slot.thursday {
            background-color: rgba(150, 206, 180, 0.2);
            /* Зеленый */
        }

        .time-slot.friday {
            background-color: rgba(69, 183, 209, 0.2);
            /* Голубой */
        }

        .time-slot.saturday {
            background-color: rgba(142, 68, 173, 0.2);
            /* Фиолетовый */
        }

        .time-slot.sunday {
            background-color: rgba(255, 138, 128, 0.2);
            /* Розовый */
        }



        .number {
            position: fixed;
            top: 1.5rem;
            right: 1rem;
        }

        .mail {
            position: fixed;
            filter: invert(20%);
            width: 53px;
            top: 0.7rem;
            right: 27.2rem;
        }

        .mail-txt {
            position: fixed;
            top: 1.5rem;
            right: 15rem;
        }

        footer {
    position: fixed;
    bottom: -2rem;
    left: 0;
    width: 100%;
    padding: 1.5rem 0;
    text-align: center;
    color: #303030ff;
    font: bold 1.2rem 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    height: 100px;
    /* Стеклянный эффект */
    background-color: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px); /* Для Safari */
    border-radius: 20px 20px 0 0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
    z-index: 9999; /* Поднимаем над всеми элементами */
}
        /* Новые стили для небесной темы */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: #333;
            position: relative;
            overflow-x: hidden;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
        }

        /* Анимированное небо с облаками */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.2) 0px, transparent 40px),
                radial-gradient(circle at 70% 60%, rgba(255, 255, 255, 0.2) 0px, transparent 40px),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.2) 0px, transparent 40px);
            z-index: -2;
            animation: cloudsMove 60s linear infinite;
        }

        @keyframes cloudsMove {
            from { transform: translateX(0); }
            to { transform: translateX(100px); }
        }

        /* Звёзды */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 40px 70px, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 90px 40px, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 130px 80px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 160px 30px, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 210px 60px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 250px 90px, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 280px 20px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 330px 50px, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 360px 70px, #fff, rgba(0,0,0,0));
            background-repeat: repeat;
            background-size: 400px 400px;
            z-index: -3;
            animation: twinkle 8s ease-in-out infinite alternate;
        }

        @keyframes twinkle {
            0% { opacity: 0.3; }
            100% { opacity: 1; }
        }

        header {
            background: rgba(41, 128, 185, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            text-align: center;
            padding: 2.5rem 0;
            margin-bottom: 3rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
            animation: shine 3s infinite linear;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        header h1 {
            font-size: 3rem;
            font-weight: 300;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 2px;
            position: relative;
            display: inline-block;
        }

        header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.7), transparent);
            animation: pulseLine 2s infinite;
        }

        @keyframes pulseLine {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }

        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .schedule-table::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
            z-index: -1;
            border-radius: 20px;
            animation: borderGlow 3s infinite alternate;
        }

        @keyframes borderGlow {
            0% { opacity: 0.5; filter: blur(5px); }
            100% { opacity: 0.8; filter: blur(15px); }
        }

        .schedule-table thead {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.8) 0%, rgba(142, 68, 173, 0.8) 100%);
            position: relative;
            transition: all 0.3s ease;
        }

        .schedule-table th {
            padding: 1.5rem 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: white;
            position: relative;
            transition: all 0.3s ease;
        }

        .schedule-table td {
            padding: 1.2rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(240, 240, 240, 0.7);
            transition: all 0.3s ease;
            position: relative;
        }

        .schedule-table tbody tr {
            transition: all 0.3s ease;
        }

        .schedule-table tbody tr:hover {
            background: rgba(227, 242, 253, 0.7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .schedule-table tbody tr:nth-child(even) {
            background-color: rgba(250, 251, 252, 0.7);
        }

        .schedule-table tbody tr:nth-child(even):hover {
            background: rgba(240, 244, 255, 0.7);
        }

        .current-info {
            margin-top: 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .date-time {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: rgba(255,255,255,0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .date-time:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }

        .current-date {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .current-time {
            font-size: 1.5rem;
            font-weight: 300;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            transition: all 0.3s ease;
        }

        .current-day {
            background: rgba(255,255,255,0.2);
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: 500;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .current-day:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }

        /* Анимация для ячеек с временными слотами */
        .time-slot {
            display: inline-block;
            padding: 4px 8px;
            margin: 2px 0;
            border-radius: 6px;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .time-slot:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Модальные окна */
        .modal-content {
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            /* border: 1px solid rgba(255, 255, 255, 0.5); */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.3) 0%, rgba(142, 68, 173, 0.3) 100%);
            text-align: center;
        }

        /* Кнопки админа */
        .admin-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            font: bolder 10px/1.2 small-caps cursive;
            padding: 10px 15px;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .admin-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .logout {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            font: bolder 20px/1.2 small-caps cursive;
            padding: 10px 15px;
            border-radius: 15px;
            border: none;
            cursor: pointer;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }

        /* Анимация появления элементов */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        
        .schedule-table {
            animation: fadeInUp 0.8s ease-out;
        }

        header {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            header h1 {
                font-size: 2.2rem;
            }
            
            main {
                padding: 0 1rem;
            }
            
            .schedule-table {
                font-size: 0.8rem;
            }
            
            .schedule-table th,
            .schedule-table td {
                padding: 0.8rem 0.5rem;
            }

            .current-info {
                gap: 1rem;
            }
            
            .date-time,
            .current-day {
                padding: 0.6rem 1rem;
            }
            
            .current-time {
                font-size: 1.2rem;
            }
            
            .current-day {
                font-size: 1.1rem;
            }
        }

        /* Анимация для текущего дня */
        .current-day-cell {
            animation: pulseCell 2s infinite;
        }

        @keyframes pulseCell {
            0%, 100% { 
                background: linear-gradient(135deg, rgba(255,235,59,0.1) 0%, rgba(255,193,7,0.1) 100%);
            }
            50% { 
                background: linear-gradient(135deg, rgba(255,235,59,0.2) 0%, rgba(255,193,7,0.2) 100%);
            }
        }

        /* Дополнительные эффекты для интерактивных элементов */
        .clickable-cell {
            transition: all 0.3s ease;
            position: relative;
        }

        .clickable-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1;
        }

        /* Эффект параллакса для фона */
        .parallax-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .parallax-layer {
            position: absolute;
            width: 110%;
            height: 110%;
            top: -5%;
            left: -5%;
        }

        .layer-1 {
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0px, transparent 100px),
                radial-gradient(circle at 70% 60%, rgba(255, 255, 255, 0.1) 0px, transparent 100px);
            animation: moveLayer1 40s infinite linear;
        }

        .layer-2 {
            background: 
                radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.05) 0px, transparent 70px),
                radial-gradient(circle at 60% 10%, rgba(255, 255, 255, 0.05) 0px, transparent 70px);
            animation: moveLayer2 30s infinite linear;
        }

        @keyframes moveLayer1 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        @keyframes moveLayer2 {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-30px, -30px); }
        }

        .container-carusel {
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 10vmin;
  overflow: hidden;
  transform: skew(5deg);
  background-color: #1a29803d;

  .card {
    flex: 1;
    transition: all 1s ease-in-out;
    height: 75vmin;
    position: relative;
    .card__head {
      color: black;
      background: rgba(114, 191, 241, 0.75);
      padding: 0.5em;
      transform: rotate(-90deg);
      transform-origin: 0% 0%;
      transition: all 0.5s ease-in-out;
      min-width: 100%;
      text-align: center;
      position: absolute;
      bottom: 0;
      left: 0;
      font-size: 1em;
      white-space: nowrap;
    }

    &:hover {
      flex-grow: 10;
      img {
        filter: grayscale(0);
      }
      .card__head {
        text-align: center;
        top: calc(100% - 2em);
        color: white;
        background: rgba(44, 91, 243, 0.5);
        font-size: 2em;
        transform: rotate(0deg) skew(-5deg);
      }
    }
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: all 1s ease-in-out;
      filter: grayscale(100%);
    }
    &:not(:nth-child(5)) {
      margin-right: 1em;
    }
  }
}

.energyM-cont {
    display: flexbox;
    width: 600px;
    background-color: white;
}

        .energyM {
    display: flex;
    width: 500px;
}

/* Inline Edit Styles for Admin */
<?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
.editable-group-name {
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
    border-radius: 4px;
    padding: 2px 4px;
}

.editable-group-name:hover {
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.3);
}

.editable-group-name[contenteditable="true"] {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.3);
    outline: none;
    min-width: 150px;
}

.edit-actions {
    display: none;
    gap: 0.5rem;
    margin-top: 0.5rem;
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    padding: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.group-cell.editing .edit-actions {
    display: flex;
}

.btn-save-inline {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-save-inline:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(40, 167, 69, 0.3);
}

.btn-cancel-inline {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    border: none;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-cancel-inline:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(108, 117, 125, 0.3);
}

.group-cell.editing {
    background: rgba(102, 126, 234, 0.1);
    position: relative;
}

.admin-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: rgba(40, 167, 69, 0.9);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    animation: slideInRight 0.5s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.admin-notification.error {
    background: rgba(220, 53, 69, 0.9);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
<?php endif; ?>
 
    .top-logo {
        top: 4rem;
    }

    /* Touch-friendly styles and scrollbar hiding */
    * {
        -webkit-overflow-scrolling: touch;
    }

    html, body {
        overflow-x: hidden;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }

    html::-webkit-scrollbar, body::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }

    /* Touch feedback for interactive elements */
    .clickable-cell, .admin-btn, .logout, .auth-btn, .modal-close {
        transition: all 0.15s ease;
        -webkit-tap-highlight-color: rgba(102, 126, 234, 0.3);
        touch-action: manipulation;
    }

    .clickable-cell:active, .admin-btn:active, .logout:active, .auth-btn:active {
        transform: scale(0.98);
        opacity: 0.8;
    }

    /* Enhanced touch targets */
    .clickable-cell {
        min-height: 44px;
        min-width: 44px;
        padding: 12px;
    }

    .admin-btn, .logout {
        min-height: 44px;
        padding: 12px 20px;
    }

    /* Loading Screen Styles */
    .loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 1;
        transition: opacity 1s ease-out, visibility 1s ease-out;
    }

    .loading-screen.hidden {
        opacity: 0;
        visibility: hidden;
    }

    .loading-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        padding: 3rem 2rem;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        max-width: 400px;
        width: 90%;
        position: relative;
        overflow: hidden;
    }

    .loading-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: shimmer 2s infinite;
    }

    .loading-icon {
        font-size: 4rem;
        margin-bottom: 1.5rem;
        animation: iconPulse 2s ease-in-out infinite;
        display: block;
    }

    .loading-title {
        font-size: 1.8rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .loading-subtitle {
        font-size: 1rem;
        font-weight: 300;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 2rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .loading-progress-container {
        width: 100%;
        height: 6px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 1rem;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .loading-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
        width: 0%;
        animation: progressFill 3s ease-out forwards;
        box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        position: relative;
    }

    .loading-progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        animation: progressShine 1.5s ease-in-out infinite;
    }

    .loading-dots {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 1rem;
    }

    .loading-dot {
        width: 8px;
        height: 8px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 50%;
        animation: dotBounce 1.4s ease-in-out infinite;
    }

    .loading-dot:nth-child(1) { animation-delay: 0s; }
    .loading-dot:nth-child(2) { animation-delay: 0.2s; }
    .loading-dot:nth-child(3) { animation-delay: 0.4s; }

    .floating-elements {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .floating-element {
        position: absolute;
        opacity: 0.3;
        animation: float 6s ease-in-out infinite;
        font-size: 1.5rem;
    }

    .floating-element.book { top: 20%; left: 15%; animation-delay: 0s; }
    .floating-element.calendar { top: 15%; right: 20%; animation-delay: 1s; }
    .floating-element.graduation { bottom: 25%; left: 20%; animation-delay: 2s; }
    .floating-element.pencil { bottom: 20%; right: 15%; animation-delay: 3s; }

    /* Loading Screen Animations */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes iconPulse {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    @keyframes progressFill {
        0% { width: 0%; }
        100% { width: 100%; }
    }

    @keyframes progressShine {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes dotBounce {
        0%, 80%, 100% {
            transform: scale(0);
            opacity: 0.5;
        }
        40% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @keyframes float {
        0%, 100% {
            transform: translateY(0px) rotate(0deg);
            opacity: 0.3;
        }
        25% {
            transform: translateY(-10px) rotate(2deg);
            opacity: 0.6;
        }
        50% {
            transform: translateY(-20px) rotate(0deg);
            opacity: 0.4;
        }
        75% {
            transform: translateY(-10px) rotate(-2deg);
            opacity: 0.5;
        }
    }

    /* Virtual Keyboard Styles */
    .virtual-keyboard {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-top: 2px solid rgba(102, 126, 234, 0.3);
        border-radius: 20px 20px 0 0;
        box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        max-height: 60vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .virtual-keyboard.show {
        transform: translateY(0);
    }

    .keyboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 18px 18px 0 0;
    }

    .keyboard-title {
        font-weight: 600;
        font-size: 1.1rem;
    }

    .keyboard-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        font-size: 1.2rem;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        -webkit-tap-highlight-color: rgba(255, 255, 255, 0.3);
        touch-action: manipulation;
    }

    .keyboard-close:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .keyboard-layout {
        padding: 1rem;
    }

    .keyboard-row {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .key {
        min-width: 40px;
        height: 50px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px solid #dee2e6;
        border-radius: 8px;
        color: #495057;
        font-size: 1.1rem;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
        -webkit-tap-highlight-color: rgba(102, 126, 234, 0.3);
        touch-action: manipulation;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .key:hover {
        background: linear-gradient(135deg, #e9ecef, #dee2e6);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .key:active {
        transform: translateY(0);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .key.pressed {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        transform: scale(0.95);
    }

    .shift-key {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }

    .shift-key.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .backspace-key {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .space-key {
        min-width: 200px;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .enter-key {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }

    /* Responsive keyboard */
    @media (max-width: 768px) {
        .keyboard-row {
            gap: 0.25rem;
        }

        .key {
            min-width: 35px;
            height: 45px;
            font-size: 1rem;
        }

        .space-key {
            min-width: 120px;
        }
    }

    /* Input field focus styles for keyboard trigger */
    input:focus, textarea:focus, [contenteditable="true"]:focus {
        outline: 2px solid #667eea;
        outline-offset: 2px;
        border-radius: 4px;
    }

    /* Hide mobile keyboard when virtual keyboard is active */
    .virtual-keyboard.show input:focus,
    .virtual-keyboard.show textarea:focus {
        caret-color: transparent;
    }

    .loading-content {
        text-align: center;
        color: white;
        animation: loadingPulse 3s ease-in-out infinite;
        position: relative;
    }

    .loading-content::before {
        content: '';
        position: absolute;
        top: -50px;
        left: -50px;
        right: -50px;
        bottom: -50px;
        background: radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                    radial-gradient(circle at 70% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        border-radius: 50%;
        animation: backgroundGlow 4s ease-in-out infinite alternate;
        z-index: -1;
    }

    .loading-books {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 30px;
        position: relative;
        filter: drop-shadow(0 0 20px rgba(139, 69, 19, 0.3));
    }

    .book {
        width: 45px;
        height: 55px;
        background: linear-gradient(135deg, #8B4513, #654321);
        border-radius: 2px 6px 6px 2px;
        position: relative;
        margin: 0 8px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2);
        animation: bookFloat 4s ease-in-out infinite;
        transition: all 0.3s ease;
    }

    .book:hover {
        transform: scale(1.1) rotate(0deg) !important;
    }

    .book::before {
        content: '';
        position: absolute;
        top: 8px;
        left: 8px;
        right: 8px;
        bottom: 8px;
        background: linear-gradient(135deg, #F5F5DC, #FFF8DC);
        border-radius: 1px 4px 4px 1px;
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.1);
    }

    .book::after {
        content: '';
        position: absolute;
        top: 18px;
        left: 12px;
        right: 12px;
        height: 2px;
        background: #8B4513;
        border-radius: 1px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    }

    .book-1 {
        animation-delay: 0s;
        transform: rotate(-8deg);
    }

    .book-2 {
        animation-delay: 0.8s;
        transform: rotate(0deg);
        z-index: 2;
    }

    .book-3 {
        animation-delay: 1.6s;
        transform: rotate(8deg);
    }

    @keyframes bookFloat {
        0% {
            transform: translateY(0) rotate(var(--rotation, 0deg)) scale(1);
            filter: brightness(1);
        }
        25% {
            transform: translateY(-15px) rotate(calc(var(--rotation, 0deg) + 3deg)) scale(1.05);
            filter: brightness(1.1);
        }
        50% {
            transform: translateY(-8px) rotate(calc(var(--rotation, 0deg) - 2deg)) scale(0.98);
            filter: brightness(0.95);
        }
        75% {
            transform: translateY(-20px) rotate(calc(var(--rotation, 0deg) + 1deg)) scale(1.02);
            filter: brightness(1.05);
        }
        100% {
            transform: translateY(0) rotate(var(--rotation, 0deg)) scale(1);
            filter: brightness(1);
        }
    }

    .loading-apple {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #FF6B6B, #FF5252);
        border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
        position: relative;
        margin: 0 auto 25px;
        box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4),
                    inset 0 2px 0 rgba(255, 255, 255, 0.3),
                    inset 0 -2px 0 rgba(0, 0, 0, 0.1);
        animation: appleBounce 3s ease-in-out infinite;
        filter: drop-shadow(0 0 15px rgba(255, 107, 107, 0.3));
    }

    .loading-apple::before {
        content: '';
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 10px;
        height: 15px;
        background: linear-gradient(135deg, #228B22, #32CD32);
        border-radius: 5px 5px 0 0;
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3),
                    inset 0 1px 0 rgba(255, 255, 255, 0.2);
        animation: leafShake 2s ease-in-out infinite;
    }

    .loading-apple::after {
        content: '';
        position: absolute;
        top: 18px;
        left: 22px;
        width: 4px;
        height: 4px;
        background: #8B4513;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
    }

    @keyframes appleBounce {
        0% {
            transform: translateY(0) scale(1) rotate(0deg);
            filter: brightness(1);
        }
        25% {
            transform: translateY(-12px) scale(1.08) rotate(2deg);
            filter: brightness(1.1);
        }
        50% {
            transform: translateY(-6px) scale(0.95) rotate(-1deg);
            filter: brightness(0.9);
        }
        75% {
            transform: translateY(-18px) scale(1.05) rotate(1deg);
            filter: brightness(1.05);
        }
        100% {
            transform: translateY(0) scale(1) rotate(0deg);
            filter: brightness(1);
        }
    }

    @keyframes leafShake {
        0%, 100% { transform: translateX(-50%) rotate(0deg); }
        25% { transform: translateX(-50%) rotate(5deg); }
        75% { transform: translateX(-50%) rotate(-3deg); }
    }

    .loading-text {
        font-size: 2rem;
        font-weight: 500;
        margin-bottom: 8px;
        text-shadow: 0 0 20px rgba(255, 255, 255, 0.5),
                     2px 2px 4px rgba(0, 0, 0, 0.5);
        letter-spacing: 1px;
        color: #FFF;
        animation: textGlow 3s ease-in-out infinite alternate;
        background: linear-gradient(45deg, #FFF, #E0F7FA, #FFF);
        background-size: 200% 200%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .loading-subtitle {
        font-size: 1.2rem;
        font-weight: 300;
        margin-bottom: 30px;
        text-shadow: 0 0 15px rgba(255, 255, 255, 0.4),
                     1px 1px 2px rgba(0, 0, 0, 0.5);
        opacity: 0.9;
        letter-spacing: 0.5px;
        animation: subtitleFade 4s ease-in-out infinite;
    }

    .loading-dots {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 25px;
    }

    .loading-dots span {
        width: 10px;
        height: 10px;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.6));
        border-radius: 50%;
        margin: 0 6px;
        animation: dotPulse 2s ease-in-out infinite;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.6),
                    0 0 20px rgba(255, 255, 255, 0.3);
        position: relative;
    }

    .loading-dots span::before {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.2) 0%, transparent 70%);
        animation: dotRing 2s ease-in-out infinite;
    }

    .loading-dots span:nth-child(1) {
        animation-delay: 0s;
    }

    .loading-dots span:nth-child(2) {
        animation-delay: 0.4s;
    }

    .loading-dots span:nth-child(3) {
        animation-delay: 0.8s;
    }

    @keyframes dotPulse {
        0%, 100% {
            transform: scale(1) translateY(0);
            opacity: 0.7;
            filter: brightness(1);
        }
        50% {
            transform: scale(1.3) translateY(-5px);
            opacity: 1;
            filter: brightness(1.2);
        }
    }

    @keyframes dotRing {
        0%, 100% {
            transform: scale(1);
            opacity: 0.5;
        }
        50% {
            transform: scale(1.5);
            opacity: 0;
        }
    }

    @keyframes textGlow {
        0% {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5),
                         2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        100% {
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.8),
                         0 0 40px rgba(255, 255, 255, 0.6),
                         2px 2px 4px rgba(0, 0, 0, 0.5);
        }
    }

    @keyframes subtitleFade {
        0%, 100% { opacity: 0.8; transform: translateY(0); }
        50% { opacity: 1; transform: translateY(-2px); }
    }

    @keyframes backgroundGlow {
        0% {
            opacity: 0.3;
            transform: scale(1);
        }
        100% {
            opacity: 0.6;
            transform: scale(1.1);
        }
    }

    @keyframes loadingPulse {
        0%, 100% {
            transform: scale(1);
            filter: brightness(1);
        }
        50% {
            transform: scale(1.02);
            filter: brightness(1.05);
        }
    }

    /* Floating particles */
    .loading-particles {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .particle {
        position: absolute;
        width: 4px;
        height: 4px;
        background: rgba(255, 255, 255, 0.6);
        border-radius: 50%;
        animation: particleFloat 6s linear infinite;
    }

    .particle:nth-child(1) { left: 20%; animation-delay: 0s; }
    .particle:nth-child(2) { left: 40%; animation-delay: 1s; }
    .particle:nth-child(3) { left: 60%; animation-delay: 2s; }
    .particle:nth-child(4) { left: 80%; animation-delay: 3s; }
    .particle:nth-child(5) { left: 30%; animation-delay: 4s; }
    .particle:nth-child(6) { left: 70%; animation-delay: 5s; }

    @keyframes particleFloat {
        0% {
            transform: translateY(100vh) rotate(0deg);
            opacity: 0;
        }
        10% {
            opacity: 1;
        }
        90% {
            opacity: 1;
        }
        100% {
            transform: translateY(-100px) rotate(360deg);
            opacity: 0;
        }
    }

    /* Student Character Animations */
    @keyframes studentWalk {
        0% {
            transform: translateX(-10px);
        }
        25% {
            transform: translateX(10px);
        }
        50% {
            transform: translateX(30px);
        }
        75% {
            transform: translateX(50px);
        }
        100% {
            transform: translateX(70px);
        }
    }

    @keyframes blink {
        0%, 90%, 100% { opacity: 1; }
        95% { opacity: 0; }
    }

    @keyframes legSwing {
        0%, 100% {
            transform: rotate(0deg);
        }
        50% {
            transform: rotate(15deg);
        }
    }

    /* Knowledge Elements Animations */
    @keyframes bookFloat {
        0% {
            transform: translateY(0) rotate(-15deg) scale(1);
            filter: brightness(1);
        }
        25% {
            transform: translateY(-20px) rotate(-10deg) scale(1.05);
            filter: brightness(1.1);
        }
        50% {
            transform: translateY(-10px) rotate(-20deg) scale(0.95);
            filter: brightness(0.9);
        }
        75% {
            transform: translateY(-25px) rotate(-5deg) scale(1.02);
            filter: brightness(1.05);
        }
        100% {
            transform: translateY(0) rotate(-15deg) scale(1);
            filter: brightness(1);
        }
    }

    @keyframes capFloat {
        0% {
            transform: translateY(0) rotate(0deg);
        }
        33% {
            transform: translateY(-15px) rotate(5deg);
        }
        66% {
            transform: translateY(-8px) rotate(-3deg);
        }
        100% {
            transform: translateY(0) rotate(0deg);
        }
    }

    @keyframes pencilFloat {
        0% {
            transform: translateY(0) rotate(0deg);
        }
        50% {
            transform: translateY(-18px) rotate(10deg);
        }
        100% {
            transform: translateY(0) rotate(0deg);
        }
    }

    @keyframes calendarPulse {
        0%, 100% {
            transform: scale(1);
            filter: brightness(1);
        }
        50% {
            transform: scale(1.05);
            filter: brightness(1.1);
        }
    }

    @keyframes tasselSwing {
        0%, 100% {
            transform: rotate(0deg);
        }
        25% {
            transform: rotate(10deg);
        }
        75% {
            transform: rotate(-8deg);
        }
    }

    /* Progress Path Animations */
    @keyframes pathFill {
        0% { width: 0%; }
        25% { width: 25%; }
        50% { width: 50%; }
        75% { width: 75%; }
        100% { width: 100%; }
    }

    @keyframes milestonePulse {
        0%, 100% {
            transform: scale(1);
            opacity: 0.6;
        }
        50% {
            transform: scale(1.2);
            opacity: 1;
        }
    }

    /* Text Animations */
    @keyframes textShimmer {
        0% {
            background-position: -200% center;
        }
        100% {
            background-position: 200% center;
        }
    }

    @keyframes subtitleFade {
        0%, 100% {
            opacity: 0.7;
            transform: translateY(0);
        }
        50% {
            opacity: 1;
            transform: translateY(-3px);
        }
    }

    @keyframes percentagePulse {
        0%, 100% {
            transform: scale(1);
            opacity: 1;
        }
        50% {
            transform: scale(1.1);
            opacity: 0.8;
        }
    }

    /* Magical Particles Enhanced */
    .particle.star {
        width: 8px;
        height: 8px;
        background: radial-gradient(circle, #FFD700, #FFA500);
        clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
        top: 20%;
        left: 30%;
        animation-delay: 0s;
        box-shadow: 0 0 10px rgba(255, 215, 0, 0.8);
    }

    .particle.sparkle {
        width: 6px;
        height: 6px;
        background: radial-gradient(circle, #FFF, #E0E0E0);
        clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
        top: 40%;
        right: 25%;
        animation-delay: 2s;
        box-shadow: 0 0 8px rgba(255, 255, 255, 1);
    }

    .particle.light {
        width: 5px;
        height: 5px;
        background: radial-gradient(circle, #87CEEB, #4682B4);
        border-radius: 50%;
        top: 60%;
        left: 70%;
        animation-delay: 4s;
        box-shadow: 0 0 6px rgba(135, 206, 235, 0.8);
    }

    .particle.glow {
        width: 7px;
        height: 7px;
        background: radial-gradient(circle, #98FB98, #32CD32);
        clip-path: polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%);
        top: 30%;
        right: 40%;
        animation-delay: 1s;
        box-shadow: 0 0 8px rgba(152, 251, 152, 0.8);
    }

    .particle.magic {
        width: 6px;
        height: 6px;
        background: radial-gradient(circle, #DDA0DD, #BA55D3);
        clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
        top: 50%;
        left: 20%;
        animation-delay: 3s;
        box-shadow: 0 0 8px rgba(221, 160, 221, 0.8);
    }

    .particle.wisdom {
        width: 7px;
        height: 7px;
        background: radial-gradient(circle, #F0E68C, #FFD700);
        clip-path: polygon(50% 0%, 100% 100%, 0% 100%);
        top: 70%;
        right: 60%;
        animation-delay: 5s;
        box-shadow: 0 0 8px rgba(240, 230, 140, 0.8);
    }

    .loading-progress {
        width: 200px;
        height: 4px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
        margin: 0 auto;
        overflow: hidden;
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
        border-radius: 2px;
        width: 0%;
        animation: progressFill 3s ease-out forwards;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes loadingPulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }

    @keyframes progressFill {
        0% { width: 0%; }
        100% { width: 100%; }
    }

    /* Main content fade-in animation */
    .main-content {
        opacity: 0;
        transform: translateY(30px);
        transition: all 1s ease-out 0.5s;
    }

    .main-content.loaded {
        opacity: 1;
        transform: translateY(0);
    }

    /* Enhanced animations for content reveal */
    .schedule-table.loaded {
        animation: tableSlideIn 1s ease-out 0.8s both;
    }

    @keyframes tableSlideIn {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    header.loaded {
        animation: headerSlideDown 1s ease-out 0.6s both;
    }

    @keyframes headerSlideDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    footer.loaded {
        animation: footerSlideUp 1s ease-out 1s both;
    }

    @keyframes footerSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .container-carusel.loaded {
        animation: carouselFadeIn 1s ease-out 1.2s both;
    }

    @keyframes carouselFadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

 </style>
</head>

<body>
    <header>

        <img src="css/img/top_logo_new.png" class="top-logo">
        <!-- Кнопка входа/выхода для админа -->
        <div class="admin-auth">
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                <span class="admin-welcome">Добро пожаловать, администратор!</span>
            <?php else: ?>
                <a href="admin/login.php" class="auth-btn login-btn">
                    <img src="config/img/admin_img.png" class="admin-icon">
                </a>
            <?php endif; ?>
            <div class="web_txt">dvorecml-podolsk.edumsko.ru</div>
        </div>

        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
            <div class="admin-panel">
                <a href="admin/logout.php" class="auth-btn logout-btn">
                    <button class="logout">
                        Выйти
                    </button>
                </a>
                <div class="admin-buttons">
                    <a href="admin/add_schedule.php">
                        <button class="admin-btn add-btn">
                            Добавить запись
                        </button>
                    </a>
                    <a href="admin/dashboard.php">
                        <button class="admin-btn manage-btn">
                            Управление расписанием
                        </button>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <h1>Расписание</h1>
        <div class="current-info">
            <div class="date-time">
                <span class="current-date"><?php echo $current_date; ?></span>
                <span class="current-time" id="time"><?php echo $current_time; ?></span>
            </div>
            <div class="current-day" id="day"><?php echo $day_of_week_ru; ?></div>
        </div>

    </header>

    <main>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Педагог</th>
                    <th>Коллектив</th>
                    <th class="<?php echo $day_of_week == 'Monday' ? 'current-day-column' : ''; ?>">Пн</th>
                    <th class="<?php echo $day_of_week == 'Tuesday' ? 'current-day-column' : ''; ?>">Вт</th>
                    <th class="<?php echo $day_of_week == 'Wednesday' ? 'current-day-column' : ''; ?>">Ср</th>
                    <th class="<?php echo $day_of_week == 'Thursday' ? 'current-day-column' : ''; ?>">Чт</th>
                    <th class="<?php echo $day_of_week == 'Friday' ? 'current-day-column' : ''; ?>">Пт</th>
                    <th class="<?php echo $day_of_week == 'Saturday' ? 'current-day-column' : ''; ?>">Сб</th>
                    <th class="<?php echo $day_of_week == 'Sunday' ? 'current-day-column' : ''; ?>">Вс</th>
                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                        <th>Действия</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedTeachers as $teacherId => $data): ?>
                    <?php
                    $teacher = $data['teacher'];
                    $groups = $data['groups'];
                    $rowspan = count($groups); // Количество групп у педагога
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    ?>

                    <!-- Первая строка с объединённым именем педагога -->
                    <tr>
                        <td rowspan="<?= $rowspan ?>" class="teacher-cell clickable-cell" onclick="showTeacherModal(<?= $teacher['id'] ?>)">
                            <?= htmlspecialchars($teacher['full_name']) ?>
                        </td>

                        <!-- Данные первой группы -->
                        <?php $firstGroup = $groups[0];
                        $group = $db->getGroupById($firstGroup['group_id']); ?>
                        <td class="group-cell <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>editing<?php endif; ?>" onclick="showGroupModal(<?= $group['id'] ?>)">
                            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                                <span class="editable-group-name" data-group-id="<?= $group['id'] ?>" data-field="name">
                                    <?= htmlspecialchars($group['name']) ?>
                                </span>
                            <?php else: ?>
                                <?= htmlspecialchars($group['name']) ?>
                            <?php endif; ?>
                        </td>

                        <?php foreach ($days as $dayKey => $dayLabel): ?>
                            <td class="<?= $day_of_week == $dayLabel ? 'current-day-cell' : '' ?>">
                                <?= formatScheduleForDisplay($firstGroup, $dayLabel) ?>
                            </td>
                        <?php endforeach; ?>

                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                            <td class="admin-actions">
                                <a href="admin/edit_schedule.php?id=<?= $firstGroup['id'] ?>" class="edit-btn">✏️</a>
                                <a href="admin/delete_schedule.php?id=<?= $firstGroup['id'] ?>" class="delete-btn" onclick="return confirm('Вы уверены?')">🗑️</a>
                            </td>
                        <?php endif; ?>
                    </tr>

                    <!-- Остальные строки для других групп -->
                    <?php for ($i = 1; $i < $rowspan; $i++): ?>
                        <?php $groupRow = $groups[$i];
                        $group = $db->getGroupById($groupRow['group_id']); ?>
                        <tr>
                            <td class="group-cell <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>editing<?php endif; ?>" onclick="showGroupModal(<?= $group['id'] ?>)">
                                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                                    <span class="editable-group-name" data-group-id="<?= $group['id'] ?>" data-field="name">
                                        <?= htmlspecialchars($group['name']) ?>
                                    </span>
                                <?php else: ?>
                                    <?= htmlspecialchars($group['name']) ?>
                                <?php endif; ?>
                            </td>

                            <?php foreach ($days as $dayKey => $dayLabel): ?>
                                <td class="<?= $day_of_week == $dayLabel ? 'current-day-cell' : '' ?>">
                                    <?= formatScheduleForDisplay($groupRow, $dayLabel) ?>
                                </td>
                            <?php endforeach; ?>

                            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                                <td class="admin-actions">
                                    <a href="admin/edit_schedule.php?id=<?= $groupRow['id'] ?>" class="edit-btn">✏️</a>
                                    <a href="admin/delete_schedule.php?id=<?= $groupRow['id'] ?>" class="delete-btn" onclick="return confirm('Вы уверены?')">🗑️</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endfor; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <!-- Модальное окно для педагога -->
    <div id="teacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Информация о педагоге</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="info-grid" id="teacherInfo">
                    <div class="loading">Загрузка...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для коллектива -->
    <div id="groupModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Информация о коллективе</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="info-grid" id="groupInfo">
                    <div class="loading">Загрузка...</div>
                </div>
            </div>
        </div>
    </div>



    <footer>
        <img src="/css/img/телефон.png" class="phone">

        <div class="number">+7(4967)64-44-66</div>
        <img src="/css/img/почта.png" class="mail">
        <div class="mail-txt">dvorecml@yandex.ru</div>
    </footer>

    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-card">
            <div class="loading-icon">📚</div>
            <div class="loading-title">Расписание занятий</div>
            <div class="loading-subtitle">Загрузка данных...</div>

            <div class="loading-progress-container">
                <div class="loading-progress-bar"></div>
            </div>

            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
        </div>

        <div class="floating-elements">
            <div class="floating-element book">📖</div>
            <div class="floating-element calendar">📅</div>
            <div class="floating-element graduation">🎓</div>
            <div class="floating-element pencil">✏️</div>
        </div>
    </div>

    <!-- On-Screen Keyboard -->
    <div id="virtual-keyboard" class="virtual-keyboard">
        <div class="keyboard-header">
            <span class="keyboard-title">Экранная клавиатура</span>
            <button class="keyboard-close" onclick="hideVirtualKeyboard()">✕</button>
        </div>
        <div class="keyboard-layout">
            <!-- First row -->
            <div class="keyboard-row">
                <button class="key" data-key="й">й</button>
                <button class="key" data-key="ц">ц</button>
                <button class="key" data-key="у">у</button>
                <button class="key" data-key="к">к</button>
                <button class="key" data-key="е">е</button>
                <button class="key" data-key="н">н</button>
                <button class="key" data-key="г">г</button>
                <button class="key" data-key="ш">ш</button>
                <button class="key" data-key="щ">щ</button>
                <button class="key" data-key="з">з</button>
                <button class="key" data-key="х">х</button>
                <button class="key" data-key="ъ">ъ</button>
            </div>
            <!-- Second row -->
            <div class="keyboard-row">
                <button class="key" data-key="ф">ф</button>
                <button class="key" data-key="ы">ы</button>
                <button class="key" data-key="в">в</button>
                <button class="key" data-key="а">а</button>
                <button class="key" data-key="п">п</button>
                <button class="key" data-key="р">р</button>
                <button class="key" data-key="о">о</button>
                <button class="key" data-key="л">л</button>
                <button class="key" data-key="д">д</button>
                <button class="key" data-key="ж">ж</button>
                <button class="key" data-key="э">э</button>
            </div>
            <!-- Third row -->
            <div class="keyboard-row">
                <button class="key shift-key" data-key="shift">⇧</button>
                <button class="key" data-key="я">я</button>
                <button class="key" data-key="ч">ч</button>
                <button class="key" data-key="с">с</button>
                <button class="key" data-key="м">м</button>
                <button class="key" data-key="и">и</button>
                <button class="key" data-key="т">т</button>
                <button class="key" data-key="ь">ь</button>
                <button class="key" data-key="б">б</button>
                <button class="key" data-key="ю">ю</button>
                <button class="key backspace-key" data-key="backspace">⌫</button>
            </div>
            <!-- Fourth row -->
            <div class="keyboard-row">
                <button class="key space-key" data-key=" ">Пробел</button>
                <button class="key enter-key" data-key="enter">↵</button>
            </div>
        </div>
    </div>

    <script src="css/js/touch-optimizations.js"></script>
    <script>
        // Loading screen management
        function hideLoadingScreen() {
            const loadingScreen = document.getElementById('loading-screen');
            loadingScreen.classList.add('hidden');
        }

        // Функция для создания элемента информации
        function createInfoItem(label, value) {
            // Убираем пробелы и проверяем на пустоту
            if (!value || value.trim() === '') {
                return '';
            }

            return `
        <div class="info-item">
            <div class="info-label">${label}:</div>
            <div class="info-value">${value}</div>
        </div>
    `;
        }

        // Функция для показа модального окна педагога
        async function showTeacherModal(teacherId) {
            try {
                const response = await fetch(`get_teacher_info.php?id=${teacherId}`);
                const data = await response.json();
                const modal = document.getElementById('teacherModal');
                const infoContainer = document.getElementById('teacherInfo');

                // Очищаем контейнер
                infoContainer.innerHTML = '';

                // Создаем элементы с информацией о педагоге
                const fields = [{
                        label: 'ФИО',
                        value: data.full_name
                    },
                    {
                        label: 'Специализация',
                        value: data.specialization
                    },
                    {
                        label: 'Контактный телефон',
                        value: data.phone
                    },
                    {
                        label: 'Биография',
                        value: data.bio
                    }
                ];

                fields.forEach(field => {
                    const item = createInfoItem(field.label, field.value);
                    if (item) infoContainer.innerHTML += item;
                });

                modal.style.display = 'block';
            } catch (error) {
                console.error('Ошибка при получении информации о педагоге:', error);
            }
        }

        // Функция для показа модального окна коллектива
        async function showGroupModal(groupId) {
            try {
                const response = await fetch(`get_group_info.php?id=${groupId}`);
                const data = await response.json();
                const modal = document.getElementById('groupModal');
                const infoContainer = document.getElementById('groupInfo');

                // Очищаем контейнер
                infoContainer.innerHTML = '';

                // Создаем элементы с информацией о коллективе
                const fields = [{
                        label: 'Название коллектива',
                        value: data.name
                    },
                    {
                        label: 'Описание',
                        value: data.description
                    }
                ];

                fields.forEach(field => {
                    const item = createInfoItem(field.label, field.value);
                    if (item) infoContainer.innerHTML += item;
                });

                modal.style.display = 'block';
            } catch (error) {
                console.error('Ошибка при получении информации о коллективе:', error);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
        }

        // Инициализация
        window.onload = () => {
            setTimeout(hideLoadingScreen, 2000);
        };

        // Функция для загрузки и отображения карусели
        async function loadCarousel() {
            try {
                const response = await fetch('config/data/carousel.json');
                const carouselData = await response.json();
                const carouselContainer = document.getElementById('carousel-container');

                if (!carouselData || carouselData.length === 0) {
                    carouselContainer.innerHTML = '<p>Изображения карусели не найдены</p>';
                    return;
                }

                // Создаем HTML структуру карусели
                let carouselHTML = '';

                carouselData.forEach((item, index) => {
                    const isFirst = index === 0 ? 'active' : '';
                    carouselHTML += `
                        <div class="card ${isFirst}">
                            <img src="${item.image}" alt="${item.title}" onerror="this.src='css/img/admin_img.png'">
                            <div class="card__head">
                                ${item.title}
                            </div>
                        </div>
                    `;
                });

                carouselContainer.innerHTML = carouselHTML;

                // Добавляем анимацию появления карусели
                setTimeout(() => {
                    carouselContainer.classList.add('loaded');
                }, 100);

            } catch (error) {
                console.error('Ошибка при загрузке карусели:', error);
                document.getElementById('carousel-container').innerHTML = '<p>Ошибка загрузки карусели</p>';
            }
        }

        // Добавляем обработчики для закрытия модальных окон
        document.addEventListener('DOMContentLoaded', () => {
            const modals = document.querySelectorAll('.modal');
            const closeButtons = document.querySelectorAll('.close');

            closeButtons.forEach(button => {
                button.onclick = function() {
                    const modal = this.closest('.modal');
                    modal.style.display = 'none';
                }
            });

            window.onclick = function(event) {
                modals.forEach(modal => {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                });
            }


        });
    </script>
    <script src="css/js/script.js"></script>
</body>

</html>
