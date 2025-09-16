<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();
$teachers = $db->getAllTeachers();
$groups = $db->getAllGroups();

$success = '';
$error = '';

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
                            'room' => $timeSlot['room'] ?? '',
                            'notes' => $timeSlot['notes'] ?? ''
                        ];
                    }
                }
            }
        }
        
        $db->addSchedule($scheduleData);
        header('Location: ../index.php?success=added');
        exit;
        
    } catch (Exception $e) {
        $error = 'Ошибка при добавлении расписания: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить расписание</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        /* Небесный фон с анимацией */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            color: #333;
            position: relative;
            overflow-x: hidden;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            padding: 20px;
        }

        /* Анимированные облака */
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

        .form-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
            animation: shine 3s infinite linear;
            pointer-events: none;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

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
        
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.7rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.05rem;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid rgba(225, 229, 233, 0.8);
            border-radius: 12px;
            font-size: 1rem;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background: rgba(255, 255, 255, 0.9);
        }
        
        .quick-add-section {
            background: rgba(248, 249, 250, 0.7);
            backdrop-filter: blur(10px);
            border: 2px dashed rgba(222, 226, 230, 0.7);
            border-radius: 15px;
            padding: 1.8rem;
            margin-bottom: 2.2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .quick-add-section:hover {
            border-color: rgba(52, 152, 219, 0.5);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .quick-add-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.2rem;
        }
        
        .btn-quick-add {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 0.7rem 1.3rem;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.3);
        }
        
        .btn-quick-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .schedule-section {
            border: 2px solid rgba(225, 229, 233, 0.7);
            border-radius: 15px;
            padding: 1.8rem;
            margin-bottom: 1.8rem;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }
        
        .schedule-section:hover {
            border-color: rgba(52, 152, 219, 0.5);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .day-header {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.9) 0%, rgba(142, 68, 173, 0.9) 100%);
            color: white;
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.2rem;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .day-header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 50%, rgba(255,255,255,0.1) 100%);
            animation: shine 3s infinite linear;
        }
        
        .time-slot {
            background: rgba(248, 249, 250, 0.7);
            border: 1px solid rgba(222, 226, 230, 0.7);
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.2rem;
            position: relative;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }
        
        .time-slot:hover {
            border-color: rgba(52, 152, 219, 0.5);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 2fr auto;
            gap: 1.2rem;
            align-items: end;
        }
        
        .time-inputs > div {
            display: flex;
            flex-direction: column;
        }
        
        .time-inputs label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .btn {
            padding: 0.85rem 1.7rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.3rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-secondary { 
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }
        
        .btn-small { 
            padding: 0.6rem 1.2rem; 
            font-size: 0.9rem;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary:hover { 
            background: linear-gradient(135deg, #2980b9, #3498db);
        }
        
        .btn-success:hover { 
            background: linear-gradient(135deg, #27ae60, #2ecc71);
        }
        
        .btn-danger:hover { 
            background: linear-gradient(135deg, #c0392b, #e74c3c);
        }
        
        .btn-secondary:hover { 
            background: linear-gradient(135deg, #7f8c8d, #95a5a6);
        }
        
        .success {
            background: rgba(212, 237, 218, 0.8);
            color: #155724;
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(195, 230, 203, 0.7);
            backdrop-filter: blur(5px);
        }
        
        .error {
            background: rgba(248, 215, 218, 0.8);
            color: #721c24;
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.2rem;
            border: 1px solid rgba(245, 198, 203, 0.7);
            backdrop-filter: blur(5px);
        }
        
        .navigation {
            margin-bottom: 2rem;
        }
        
        .navigation a {
            color: #3498db;
            text-decoration: none;
            margin-right: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navigation a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .quick-time-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.2rem;
        }
        
        .quick-time-btn {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.3);
            border-radius: 8px;
            padding: 0.4rem 0.7rem;
            font-size: 0.8rem;
            color: #3498db;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-time-btn:hover {
            background: rgba(52, 152, 219, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.2);
        }
        
        @media (max-width: 768px) {
            .time-inputs {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }
            
            .quick-add-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .form-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .quick-time-buttons {
                justify-content: center;
            }
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .quick-add-section {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .quick-add-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .btn-quick-add {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-quick-add:hover {
            background: #218838;
            transform: translateY(-1px);
        }
        
        .schedule-section {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .day-header {
            background: #667eea;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .time-slot {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 2fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-small { padding: 0.5rem 1rem; font-size: 0.9rem; }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .navigation {
            margin-bottom: 2rem;
        }
        
        .navigation a {
            color: #667eea;
            text-decoration: none;
            margin-right: 1rem;
            font-weight: 500;
        }
        
        .navigation a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .time-inputs {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .quick-add-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="navigation">
        </div>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" id="scheduleForm">
            <div class="form-group">
                <label>Педагог *</label>
                <select name="teacher_id" required>
                    <option value="">Выберите педагога</option>
                    <?php foreach($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>">
                            <?php echo htmlspecialchars($teacher['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Коллектив *</label>
                <select name="group_id" required>
                    <option value="">Выберите коллектив</option>
                    <?php foreach($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>">
                            <?php echo htmlspecialchars($group['name']); ?> (<?php echo htmlspecialchars($group['direction']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Дни недели -->
            <?php 
            $days = array(
                'monday' => 'Понедельник',
                'tuesday' => 'Вторник', 
                'wednesday' => 'Среда',
                'thursday' => 'Четверг',
                'friday' => 'Пятница',
                'saturday' => 'Суббота',
                'sunday' => 'Воскресенье'
            );
            
            foreach($days as $dayKey => $dayName): ?>
            <div class="schedule-section">
                <div class="day-header"><?php echo $dayName; ?></div>
                
                <div class="quick-time-buttons">
                    <span style="font-size: 0.9rem; color: #666;">Быстрый выбор времени:</span>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '09:00', '10:40')">09:00-10:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '10:00', '11:40')">10:00-11:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '11:00', '12:40')">11:00-12:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '12:00', '13:40')">12:00-13:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '13:00', '14:40')">13:00-14:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '14:00', '15:40')">14:00-15:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '15:00', '16:40')">15:00-16:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '16:00', '17:40')">16:00-17:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '17:00', '18:40')">17:00-18:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '18:00', '19:40')">18:00-19:40</button>
                    <button type="button" class="quick-time-btn" onclick="setQuickTime('<?php echo $dayKey; ?>', '19:00', '20:40')">19:00-20:40</button>
                </div>
                
                <div id="<?php echo $dayKey; ?>_slots">
                    <!-- Первый слот по умолчанию -->
                    <div class="time-slot">
                        <div class="time-inputs">
                            <div>
                                <label>Начало</label>
                                <input type="time" name="<?php echo $dayKey; ?>[0][start_time]" step="300">
                            </div>
                            <div>
                                <label>Конец</label>
                                <input type="time" name="<?php echo $dayKey; ?>[0][end_time]" step="300">
                            </div>
                            <div>
                                <label>Кабинет</label>
                                <input type="text" name="<?php echo $dayKey; ?>[0][room]" placeholder="№ кабинета">
                            </div>
                            <div>
                                <label>Примечания</label>
                                <input type="text" name="<?php echo $dayKey; ?>[0][notes]" placeholder="Дополнительная информация">
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger btn-small" onclick="removeTimeSlot(this)">Удалить</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-success btn-small" onclick="addTimeSlot('<?php echo $dayKey; ?>')">
                    + Добавить еще одно занятие
                </button>
            </div>
            <?php endforeach; ?>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Сохранить расписание</button>
                <a href="../index.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>

    <script>
        let slotCounters = {
            monday: 1,
            tuesday: 1,
            wednesday: 1,
            thursday: 1,
            friday: 1,
            saturday: 1,
            sunday: 1
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
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', slotHtml);
            slotCounters[day]++;
        }

        function removeTimeSlot(button) {
            const timeSlot = button.closest('.time-slot');
            const container = timeSlot.parentNode;
            
            // Не удаляем, если это единственный слот
            if (container.children.length > 1) {
                timeSlot.remove();
            } else {
                // Очищаем поля вместо удаления
                const inputs = timeSlot.querySelectorAll('input');
                inputs.forEach(input => input.value = '');
            }
        }

        function setQuickTime(day, startTime, endTime) {
            const slots = document.getElementById(day + '_slots');
            const lastSlot = slots.lastElementChild;
            
            const startInput = lastSlot.querySelector('input[name*="start_time"]');
            const endInput = lastSlot.querySelector('input[name*="end_time"]');
            
            if (startInput && endInput) {
                startInput.value = startTime;
                endInput.value = endTime;
            }
        }

        // Валидация времени
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            const timeInputs = document.querySelectorAll('input[type="time"]');
            let hasValidTime = false;
            
            for (let i = 0; i < timeInputs.length; i += 2) {
                const startTime = timeInputs[i].value;
                const endTime = timeInputs[i + 1].value;
                
                if (startTime && endTime) {
                    hasValidTime = true;
                    if (startTime >= endTime) {
                        alert('Время начала должно быть раньше времени окончания!');
                        e.preventDefault();
                        return;
                    }
                }
            }
            
            if (!hasValidTime) {
                alert('Добавьте хотя бы один временной интервал!');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
