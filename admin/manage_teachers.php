<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();
$teachers = $db->getAllTeachers();

$success = '';
$error = '';

// Обработка сообщений
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'deleted':
            $success = 'Педагог успешно удален!';
            break;
        case 'added':
            $success = 'Педагог успешно добавлен!';
            break;
        case 'updated':
            $success = 'Данные педагога успешно обновлены!';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'not_found':
            $error = 'Педагог не найден!';
            break;
        case 'used_in_schedule':
            $error = 'Невозможно удалить педагога, так как он используется в расписании!';
            break;
        case 'delete_failed':
            $error = 'Ошибка при удалении педагога!';
            break;
        case 'no_id':
            $error = 'Не указан ID педагога!';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление педагогами - Панель администратора</title>
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
                radial-gradient(1px 1px at 10% 20%, rgba(255,255,255,0.8), transparent),
                radial-gradient(1px 1px at 30% 40%, rgba(255,255,255,0.6), transparent),
                radial-gradient(2px 2px at 50% 60%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 1px at 70% 80%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 1px at 90% 10%, rgba(255,255,255,0.5), transparent);
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
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
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
            padding: 2rem;
            position: relative;
        }

        .content-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
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
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
        }

        .stats-bar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .stats-item {
            text-align: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 300;
            color: white;
            margin-bottom: 0.25rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .stats-label {
            color: rgba(255,255,255,0.9);
            font-size: 1rem;
            font-weight: 500;
        }

        .btn-add {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            margin-bottom: 2rem;
            display: inline-block;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        }

        .table-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .teachers-table {
            width: 100%;
            border-collapse: collapse;
        }

        .teachers-table th {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1.5rem 1rem;
            text-align: left;
            font-weight: 500;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .teachers-table td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .teachers-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-edit {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: rgba(255,255,255,0.8);
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 300;
        }

        .success {
            background: rgba(40, 167, 69, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #d4edda;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .error {
            background: rgba(220, 53, 69, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #f8d7da;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .teacher-photo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .teacher-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .teacher-details h4 {
            margin: 0 0 0.5rem 0;
            color: white;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .teacher-details p {
            margin: 0;
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        /* Скрыть столбец ID */
        .teachers-table th:first-child,
        .teachers-table td:first-child {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
            }

            .main-content {
                margin-left: 250px;
            }

            .content-header h1 {
                font-size: 2rem;
            }

            .teachers-table {
                font-size: 0.8rem;
            }

            .teachers-table th,
            .teachers-table td {
                padding: 1rem 0.5rem;
            }

            .teacher-info {
                flex-direction: column;
                text-align: center;
            }
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
            }

            .mobile-menu-btn {
                display: block;
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
        }

        .mobile-menu-btn {
            display: none;
        }
    </style>
</head>

<body>
    <div class="admin-layout">
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
                    <a href="manage_teachers.php" class="nav-link active">
                        <i>👥</i> Педагоги
                    </a>
                </div>

                <div class="nav-item">
                    <a href="manage_groups.php" class="nav-link">
                        <i>🎭</i> Коллективы
                    </a>
                </div>

                <div class="nav-item">
                    <a href="manage_carousel.php" class="nav-link">
                        <i>🎠</i> Карусель
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/index.php" class="nav-link">
                        <i>📅</i> Расписание
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
            <button class="mobile-menu-btn" onclick="toggleSidebar()">☰</button>

            <div class="content-header">
                <h1>Управление педагогами</h1>
            </div>

            <div class="stats-bar">
                <div class="stats-item">
                    <div class="stats-number"><?php echo count($teachers); ?></div>
                    <div class="stats-label">Всего педагогов</div>
                </div>
            </div>

            <a href="add_teacher.php" class="btn btn-add">+ Добавить нового педагога</a>

            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <?php if (empty($teachers)): ?>
                    <div class="empty-state">
                        <h3>Список педагогов пуст</h3>
                        <p>Добавьте первого педагога, нажав кнопку выше</p>
                    </div>
                <?php else: ?>
                    <table class="teachers-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Педагог</th>
                                <th>Специализация</th>
                                <th>Телефон</th>
                                <th>Email</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['id']); ?></td>
                                    <td>
                                        <div class="teacher-info">
                                            <?php if (!empty($teacher['photo'])): ?>
                                                <img src="../uploads/teachers/<?php echo htmlspecialchars($teacher['photo']); ?>"
                                                    alt="Фото" class="teacher-photo">
                                            <?php else: ?>
                                                <div class="teacher-photo" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                                    👤
                                                </div>
                                            <?php endif; ?>
                                            <div class="teacher-details">
                                                <h4><?php echo htmlspecialchars($teacher['full_name']); ?></h4>
                                                <?php if (!empty($teacher['experience'])): ?>
                                                    <p>Стаж: <?php echo htmlspecialchars($teacher['experience']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($teacher['specialization'] ?? 'Не указана'); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['phone'] ?? 'Не указан'); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email'] ?? 'Не указан'); ?></td>
                                    <td>
                                        <a href="edit_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-edit">Редактировать</a>
                                        <a href="delete_teacher.php?id=<?php echo $teacher['id']; ?>" class="btn btn-delete"
                                            onclick="return confirm('Удалить педагога <?php echo htmlspecialchars($teacher['full_name']); ?>?')">Удалить</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../css/js/touch-optimizations.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');

            if (window.innerWidth <= 640) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>

</html>
