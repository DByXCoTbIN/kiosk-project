<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();

$success = '';
$error = '';
$schedule = null;

// Получаем ID из URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../index.php?error=not_found');
    exit;
}

$scheduleId = (int)$_GET['id'];
$schedule = $db->getScheduleById($scheduleId);

if (!$schedule) {
    header('Location: ../index.php?error=not_found');
    exit;
}

$teachers = $db->getAllTeachers();
$groups = $db->getAllGroups();

// Получаем всех преподавателей для выбранного коллектива
$groupTeachers = [];
$selectedGroupId = $schedule['group_id']; // Используем ID из существующего расписания
$allSchedules = $db->getAllSchedule();
foreach ($allSchedules as $scheduleItem) {
    if ($scheduleItem['group_id'] == $selectedGroupId) {
        $groupTeachers[] = $scheduleItem['teacher_id'];
    }
}
$groupTeachers = array_unique($groupTeachers);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Обрабатываем данные формы
        $scheduleData = [
            'teacher_id' => (int)$_POST['teacher_id'],
            'group_id' => (int)$_POST['group_id'],
            'schedule' => []
        ];
        
        // Дни недели
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $scheduleData['schedule'][$day] = [];
            
            // Проверяем, есть ли занятия в этот день
            if (isset($_POST[$day]) && is_array($_POST[$day])) {
                foreach ($_POST[$day] as $index => $timeSlot) {
                    if (isset($timeSlot['start_time']) && isset($timeSlot['end_time']) && 
                        !empty($timeSlot['start_time']) && !empty($timeSlot['end_time'])) {
                        
                        $scheduleData['schedule'][$day][] = [
                            'start_time' => $timeSlot['start_time'],
                            'end_time' => $timeSlot['end_time'],
                            'room' => isset($timeSlot['room']) ? $timeSlot['room'] : '',
                            'notes' => isset($timeSlot['notes']) ? $timeSlot['notes'] : ''
                        ];
                    }
                }
            }
        }
        
        if ($db->updateSchedule($scheduleId, $scheduleData)) {
            header('Location: ../index.php?success=updated');
            exit;
        } else {
            $error = 'Ошибка при обновлении расписания';
        }
        
    } catch (Exception $e) {
        $error = 'Ошибка при обновлении расписания: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать расписание</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            color: #333;
        }

        /* Анимированный фон */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0px, transparent 50px),
                radial-gradient(circle at 70% 60%, rgba(255, 255, 255, 0.1) 0px, transparent 50px),
                radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.1) 0px, transparent 50px);
            z-index: -2;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(1deg); }
            66% { transform: translateY(10px) rotate(-1deg); }
        }

        /* Звезды */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(1px 1px at 10% 20%, rgba(255, 255, 255, 0.8), transparent),
                radial-gradient(1px 1px at 30% 40%, rgba(255, 255, 255, 0.6), transparent),
                radial-gradient(2px 2px at 50% 60%, rgba(255, 255, 255, 0.9), transparent),
                radial-gradient(1px 1px at 70% 80%, rgba(255, 255, 255, 0.7), transparent),
                radial-gradient(1px 1px at 90% 10%, rgba(255, 255, 255, 0.5), transparent);
            background-repeat: repeat;
            background-size: 200px 200px;
            z-index: -1;
            animation: twinkle 10s ease-in-out infinite alternate;
        }

        @keyframes twinkle {
            0% { opacity: 0.3; }
            100% { opacity: 0.8; }
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            margin: 0;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.5rem 0;
        }

        .nav-link {
            display: block;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            border-left: 4px solid transparent;
        }

        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left-color: rgba(255, 255, 255, 0.5);
            transform: translateX(5px);
        }

        .nav-link.active {
            color: white;
            background: rgba(102, 126, 234, 0.2);
            border-left-color: #667eea;
            box-shadow: inset 0 0 10px rgba(102, 126, 234, 0.1);
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .logout-section {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logout-btn {
            width: 100%;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
            background: linear-gradient(135deg, #ee5a52 0%, #ff6b6b 100%);
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 1.5rem;
            position: relative;
            width: calc(100vw - 280px);
            max-width: none;
            margin: 0;
            transition: margin-left 0.3s ease, width 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content-wrapper {
            width: 100%;
            max-width: 1200px;
            padding: 0 1rem;
        }

        .content-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .content-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
            animation: shine 4s infinite linear;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .content-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .content-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 2;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0.7;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: white;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 1rem;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-group select option {
            background: white;
            color: #333;
        }

        .schedule-section {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .schedule-section:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .day-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-weight: 600;
            font-size: 1.1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .day-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine 3s infinite;
        }

        .time-slot {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .time-slot:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-1px);
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.1);
        }

        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 2fr auto;
            gap: 0.875rem;
            align-items: end;
        }

        .time-inputs label {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.375rem;
            display: block;
            font-size: 0.875rem;
        }

        .time-inputs input {
            padding: 0.625rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .time-inputs input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
            background: rgba(255, 255, 255, 0.15);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .success {
            background: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(40, 167, 69, 0.3);
            backdrop-filter: blur(10px);
            text-align: center;
            font-weight: 500;
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(220, 53, 69, 0.3);
            backdrop-filter: blur(10px);
            text-align: center;
            font-weight: 500;
        }

        .navigation {
            margin-bottom: 2rem;
            text-align: center;
        }

        .navigation a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            margin: 0 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .navigation a:hover {
            color: white;
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        @media (max-width: 1024px) {
            .main-content {
                width: calc(100vw - 250px);
                padding: 1rem;
            }

            .content-wrapper {
                padding: 0;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }

            .main-content {
                margin-left: 250px;
                width: calc(100vw - 250px);
                padding: 1rem;
            }

            .time-inputs {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .content-header h1 {
                font-size: 1.8rem;
            }

            .content-header {
                padding: 1.25rem;
            }

            .form-container {
                padding: 1.25rem;
            }

            .schedule-section {
                padding: 1rem;
            }

            .day-header {
                font-size: 1rem;
                padding: 0.625rem 1rem;
            }
        }

        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            backdrop-filter: blur(2px);
        }

        .sidebar-overlay.active {
            display: block;
        }

        @media (max-width: 640px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 0.75rem;
                transition: margin-left 0.3s ease;
            }

            .mobile-menu-btn {
                display: block;
            }

            .content-header h1 {
                font-size: 1.6rem;
            }

            .form-container {
                padding: 1rem;
            }

            .schedule-section {
                padding: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" onclick="closeSidebar()"></div>

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Админ-панель</h2>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i>🏠</i> Главная
                    </a>
                </div>

                <div class="nav-item">
                    <a href="manage_teachers.php" class="nav-link">
                        <i>👥</i> Педагоги
                    </a>
                </div>

                <div class="nav-item">
                    <a href="manage_groups.php" class="nav-link">
                        <i>🎭</i> Коллективы
                    </a>
                </div>

                <div class="nav-item">
                    <a href="../index.php" class="nav-link active">
                        <i>📅</i> Расписание
                    </a>
                </div>

                <div class="nav-item">
                    <a href="manage_carousel.php" class="nav-link">
                        <i>🎠</i> Карусель
                    </a>
                </div>


            </nav>

            <div class="logout-section">
                <a href="logout.php">
                    <button class="logout-btn">
                        🚪 Выйти
                    </button>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

                <div class="content-header">
                    <h1>Редактировать расписание</h1>
                    <p>Изменение данных расписания</p>
                </div>

                <?php if ($success): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="form-container">
                <form method="POST" id="scheduleForm">
            <div class="form-group">
                <label>Педагог *</label>
                <select name="teacher_id" id="teacherSelect" required>
                    <option value="">Выберите педагога</option>
                    <?php
                    // Если у коллектива только один преподаватель - автоматически выбираем его
                    if (count($groupTeachers) === 1) {
                        $autoSelectedTeacher = $groupTeachers[0];
                        foreach($teachers as $teacher) {
                            if ($teacher['id'] == $autoSelectedTeacher) {
                                echo '<option value="' . $teacher['id'] . '" selected>' . htmlspecialchars($teacher['full_name'] ?? '') . '</option>';
                                break;
                            }
                        }
                    } elseif (count($groupTeachers) > 1) {
                        // Если несколько преподавателей - показываем только их
                        foreach($teachers as $teacher) {
                            if (in_array($teacher['id'], $groupTeachers)) {
                                $selected = ($schedule['teacher_id'] == $teacher['id']) ? 'selected' : '';
                                echo '<option value="' . $teacher['id'] . '" ' . $selected . '>' . htmlspecialchars($teacher['full_name'] ?? '') . '</option>';
                            }
                        }
                    } else {
                        // Если преподавателей нет - показываем всех
                        foreach($teachers as $teacher) {
                            $selected = ($schedule['teacher_id'] == $teacher['id']) ? 'selected' : '';
                            echo '<option value="' . $teacher['id'] . '" ' . $selected . '>' . htmlspecialchars($teacher['full_name'] ?? '') . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Коллектив *</label>
                <select name="group_id" required>
                    <option value="">Выберите коллектив</option>
                    <?php foreach($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>"
                                <?php echo ($schedule['group_id'] == $group['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group['name'] ?? ''); ?> 
                            (<?php echo htmlspecialchars($group['direction'] ?? ''); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Дни недели -->
            <?php 
            $days = [
                'monday' => 'Понедельник',
                'tuesday' => 'Вторник', 
                'wednesday' => 'Среда',
                'thursday' => 'Четверг',
                'friday' => 'Пятница',
                'saturday' => 'Суббота',
                'sunday' => 'Воскресенье'
            ];
            
            foreach($days as $dayKey => $dayName): 
                $daySchedule = isset($schedule['schedule'][$dayKey]) ? $schedule['schedule'][$dayKey] : [];
                if (empty($daySchedule)) {
                    $daySchedule = [['start_time' => '', 'end_time' => '', 'room' => '', 'notes' => '']];
                }
            ?>
            <div class="schedule-section">
                <div class="day-header"><?php echo $dayName; ?></div>
                
                <!-- <div class="quick-time-buttons">
                    <span style="font-size: 0.9rem; color: #666;">Быстрый выбор времени:</span>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '09:00', '10:30')">09:00-10:30</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '11:00', '12:30')">11:00-12:30</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '14:00', '15:30')">14:00-15:30</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '16:00', '17:30')">16:00-17:30</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '18:00', '19:30')">18:00-19:30</button>
                </div> -->
                
                <div id="<?php echo $dayKey; ?>_slots">
                    <?php foreach($daySchedule as $index => $slot): ?>
                    <div class="time-slot">
                        <div class="time-inputs">
                            <div>
                                <label>Начало</label>
                                <input type="time" name="<?php echo $dayKey; ?>[<?php echo $index; ?>][start_time]" 
                                       value="<?php echo htmlspecialchars($slot['start_time'] ?? ''); ?>" step="300">
                            </div>
                            <div>
                                <label>Конец</label>
                                <input type="time" name="<?php echo $dayKey; ?>[<?php echo $index; ?>][end_time]" 
                                       value="<?php echo htmlspecialchars($slot['end_time'] ?? ''); ?>" step="300">
                            </div>
                            <div>
                                <label>Кабинет</label>
                                <input type="text" name="<?php echo $dayKey; ?>[<?php echo $index; ?>][room]" 
                                       value="<?php echo htmlspecialchars($slot['room'] ?? ''); ?>" placeholder="№ кабинета">
                            </div>
                            <div>
                                <label>Примечания</label>
                                <input type="text" name="<?php echo $dayKey; ?>[<?php echo $index; ?>][notes]" 
                                       value="<?php echo htmlspecialchars($slot['notes'] ?? ''); ?>" placeholder="Дополнительная информация">
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger btn-small" onclick="removeTimeSlot(this)">Удалить</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div class="btn-cent">
                
                <!-- <button type="button" class="btn btn-success btn-small" onclick="addTimeSlot('<?php echo $dayKey; ?>')">
                    + Добавить еще одно занятие
                </button> -->
                <button type="button" class="btn btn-success btn-small" onclick="addTimeSlot('<?php echo $dayKey; ?>')">
                    + 
                </button>
            </div>
            <?php endforeach; ?>
            
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                        <a href="../index.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </div>

    <script>
        // Данные о преподавателях и их связях с коллективами
        const teachersData = <?php echo json_encode($teachers); ?>;
        const allSchedules = <?php echo json_encode($db->getAllSchedule()); ?>;

        // Функция для получения преподавателей для конкретного коллектива
        function getTeachersForGroup(groupId) {
            const groupTeachers = [];
            allSchedules.forEach(schedule => {
                if (schedule.group_id == groupId) {
                    groupTeachers.push(schedule.teacher_id);
                }
            });
            return [...new Set(groupTeachers)]; // Убираем дубликаты
        }

        // Функция для обновления списка преподавателей
        function updateTeacherSelect(groupId) {
            const teacherSelect = document.getElementById('teacherSelect');
            const groupTeachers = getTeachersForGroup(groupId);

            // Очищаем текущие опции
            teacherSelect.innerHTML = '<option value="">Выберите педагога</option>';

            if (groupTeachers.length === 1) {
                // Если один преподаватель - автоматически выбираем его
                const teacherId = groupTeachers[0];
                const teacher = teachersData.find(t => t.id == teacherId);
                if (teacher) {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.full_name || '';
                    option.selected = true;
                    teacherSelect.appendChild(option);
                }
            } else if (groupTeachers.length > 1) {
                // Если несколько преподавателей - показываем только их
                groupTeachers.forEach(teacherId => {
                    const teacher = teachersData.find(t => t.id == teacherId);
                    if (teacher) {
                        const option = document.createElement('option');
                        option.value = teacher.id;
                        option.textContent = teacher.full_name || '';
                        teacherSelect.appendChild(option);
                    }
                });
            } else {
                // Если преподавателей нет - показываем всех
                teachersData.forEach(teacher => {
                    const option = document.createElement('option');
                    option.value = teacher.id;
                    option.textContent = teacher.full_name || '';
                    teacherSelect.appendChild(option);
                });
            }
        }

        // Обработчик изменения коллектива
        document.addEventListener('DOMContentLoaded', function() {
            const groupSelect = document.querySelector('select[name="group_id"]');
            if (groupSelect) {
                groupSelect.addEventListener('change', function() {
                    const selectedGroupId = this.value;
                    if (selectedGroupId) {
                        updateTeacherSelect(selectedGroupId);
                    }
                });
            }
        });

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const mainContent = document.querySelector('.main-content');

            if (window.innerWidth <= 640) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');

                if (sidebar.classList.contains('open')) {
                    mainContent.style.marginLeft = '250px';
                } else {
                    mainContent.style.marginLeft = '0';
                }
            }
        }

        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const mainContent = document.querySelector('.main-content');

            if (window.innerWidth <= 640) {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                mainContent.style.marginLeft = '0';
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const menuBtn = document.querySelector('.mobile-menu-btn');

            if (window.innerWidth <= 640) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target) && overlay.classList.contains('active')) {
                    closeSidebar();
                }
            }
        });
        let slotCounters = {
            monday: <?php echo count($schedule['schedule']['monday'] ?? []) ?: 1; ?>,
            tuesday: <?php echo count($schedule['schedule']['tuesday'] ?? []) ?: 1; ?>,
            wednesday: <?php echo count($schedule['schedule']['wednesday'] ?? []) ?: 1; ?>,
            thursday: <?php echo count($schedule['schedule']['thursday'] ?? []) ?: 1; ?>,
            friday: <?php echo count($schedule['schedule']['friday'] ?? []) ?: 1; ?>,
            saturday: <?php echo count($schedule['schedule']['saturday'] ?? []) ?: 1; ?>,
            sunday: <?php echo count($schedule['schedule']['sunday'] ?? []) ?: 1; ?>
        };

        function addTimeSlot(day) {
            const container = document.getElementById(day + '_slots');
            const slotIndex = slotCounters[day];
            
            const slotHtml = `
                <div class="time-slot">
                    <div class="time-inputs">
                        <div>
                            <label>Начало</label>
                            <input type="time" name="${day}[${slotIndex}][start_time]" step="300">
                        </div>
                        <div>
                            <label>Конец</label>
                            <input type="time" name="${day}[${slotIndex}][end_time]" step="300">
                        </div>
                        <div>
                            <label>Кабинет</label>
                            <input type="text" name="${day}[${slotIndex}][room]" placeholder="№ кабинета">
                        </div>
                        <div>
                            <label>Примечания</label>
                            <input type="text" name="${day}[${slotIndex}][notes]" placeholder="Дополнительная информация">
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger btn-small" onclick="removeTimeSlot(this)">Удалить</button>
                        </div>
                    </div>
                </div>`;
            
            container.insertAdjacentHTML('beforeend', slotHtml);
            slotCounters[day]++;
        }

        function removeTimeSlot(button) {
            const slot = button.parentElement.parentElement.parentElement;
            slot.remove();
        }

        function setQuickTime(day, startTime, endTime) {
            const container = document.getElementById(day + '_slots');
            const slotIndex = slotCounters[day];
            
            const slotHtml = `
                <div class="time-slot">
                    <div class="time-inputs">
                        <div>
                            <label>Начало</label>
                            <input type="time" name="${day}[${slotIndex}][start_time]" value="${startTime}" step="300">
                        </div>
                        <div>
                            <label>Конец</label>
                            <input type="time" name="${day}[${slotIndex}][end_time]" value="${endTime}" step="300">
                        </div>
                        <div>
                            <label>Кабинет</label>
                            <input type="text" name="${day}[${slotIndex}][room]" placeholder="№ кабинета">
                        </div>
                        <div>
                            <label>Примечания</label>
                            <input type="text" name="${day}[${slotIndex}][notes]" placeholder="Дополнительная информация">
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger btn-small" onclick="removeTimeSlot(this)">Удалить</button>
                        </div>
                    </div>
                </div>`;
            
            container.insertAdjacentHTML('beforeend', slotHtml);
            slotCounters[day]++;
        }
    </script>
</body>
</html>
