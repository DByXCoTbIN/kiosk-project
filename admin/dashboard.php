<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();

// Получаем статистику
$teachers_count = count($db->getAllTeachers());
$groups_count = count($db->getAllGroups());
$schedule_count = count($db->getAllSchedule());
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
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

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(-10px) rotate(1deg);
            }

            66% {
                transform: translateY(10px) rotate(-1deg);
            }
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
            0% {
                opacity: 0.3;
            }

            100% {
                opacity: 0.8;
            }
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
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%, rgba(255, 255, 255, 0.1) 100%);
            animation: shine 4s infinite linear;
        }

        @keyframes shine {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        .content-header h1 {
            margin: 0 0 1rem 0;
            font-size: 2.5rem;
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .content-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
            position: relative;
            z-index: 2;
        }

        .welcome-message {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2.5rem 2rem;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-number {
            font-size: 4rem;
            font-weight: 300;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }

        .action-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2.5rem 2rem;
            border-radius: 20px;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            justify-content: center;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .action-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0.7;
        }

        .action-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        .action-section h3 {
            color: white;
            margin-bottom: 2rem;
            font-size: 1.4rem;
            font-weight: 500;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            margin: 0.5rem 0.5rem 0.5rem 0;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
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

            .stat-number {
                font-size: 3rem;
            }

            .actions-grid {
                grid-template-columns: 1fr;
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
                    <a href="dashboard.php" class="nav-link active">
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
                    <a href="manage_carousel.php" class="nav-link">
                        <i>🎠</i> Карусель
                    </a>
                </div>

                <div class="nav-item">
                    <a href="/index.php" class="nav-link">
                        <i>📅</i> Расписание
                    </a>
                </div>

                <!-- <div class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i>🌐</i> На сайт
                    </a>
                </div> -->
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

            <div class="welcome-message">
                <strong>Добро пожаловать в панель администратора!</strong>
                <?php if (isset($_SESSION['admin_username'])): ?>
                    Вы вошли как: <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                <?php endif; ?>
            </div>

            <div class="content-header">
                <h1>Панель администратора</h1>
                <p>Управление расписанием, педагогами и коллективами</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $teachers_count; ?></div>
                    <div class="stat-label">Педагогов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $groups_count; ?></div>
                    <div class="stat-label">Коллективов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $schedule_count; ?></div>
                    <div class="stat-label">Записей в расписании</div>
                </div>
            </div>

            <div class="actions-grid">
                <div class="action-section">
                    <h3>👥 Управление педагогами</h3>
                    <a href="manage_teachers.php" class="btn">Список педагогов</a>
                    <a href="add_teacher.php" class="btn btn-success">➕ Добавить педагога</a>
                </div>

                <div class="action-section">
                    <h3>🎭 Управление коллективами</h3>
                    <a href="manage_groups.php" class="btn">Список коллективов</a>
                    <a href="add_group.php" class="btn btn-success">➕ Добавить коллектив</a>
                </div>

                <div class="action-section">
                    <h3>📅 Управление расписанием</h3>
                    <a href="../index.php" class="btn">Управление расписанием</a>
                    <a href="add_schedule.php" class="btn btn-success">➕ Добавить расписание</a>
                    <a href="export_schedule.php" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">📊 Экспорт расписания</a>
                </div>

                <div class="action-section">
                    <h3>🎠 Управление каруселью</h3>
                    <a href="manage_carousel.php" class="btn">Список изображений</a>
                    <a href="add_carousel.php" class="btn btn-success">➕ Добавить изображение</a>
                </div>
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
