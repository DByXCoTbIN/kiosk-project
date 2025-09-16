<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: manage_carousel.php?error=no_id');
    exit;
}

$item = $db->getCarouselItemById($id);

if (!$item) {
    header('Location: manage_carousel.php?error=not_found');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Удаляем файл с диска, если он находится в папке carousel_uploads
    if (strpos($item['image'], 'css/img/carousel_uploads/') === 0) {
        $file_path = '../' . $item['image'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $deleted = $db->deleteCarouselItem($id);

    if ($deleted) {
        header('Location: manage_carousel.php?success=deleted');
        exit;
    } else {
        header('Location: manage_carousel.php?error=delete_failed');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удалить изображение карусели - Панель администратора</title>
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

        .confirm-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .confirm-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #dc3545, #c82333);
            opacity: 0.7;
        }

        .item-preview {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .item-image {
            width: 200px;
            height: 150px;
            border-radius: 12px;
            object-fit: cover;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .item-details {
            color: white;
        }

        .item-details h3 {
            margin: 0 0 1rem 0;
            color: #ff6b6b;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .item-details p {
            margin: 0.5rem 0;
            color: rgba(255,255,255,0.9);
            font-size: 0.95rem;
        }

        .warning-message {
            background: rgba(255, 193, 7, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ffeaa7;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 2;
        }

        .warning-message strong {
            display: block;
            margin-bottom: 0.75rem;
            font-size: 1.2rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .btn {
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            margin: 0 0.5rem;
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

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 3rem;
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

            .confirm-container {
                padding: 2rem 1.5rem;
            }

            .button-group {
                flex-direction: column;
                align-items: center;
            }

            .item-image {
                width: 150px;
                height: 100px;
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
                    <a href="manage_carousel.php" class="nav-link active">
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
                <h1>Удалить изображение карусели</h1>
            </div>

            <div class="confirm-container">
                <div class="warning-message">
                    <strong>⚠️ Внимание!</strong>
                    Вы собираетесь удалить изображение из карусели. Это действие нельзя отменить.
                </div>

                <div class="item-preview">
                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" alt="Изображение для удаления" class="item-image">
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                        <p><strong>Описание:</strong> <?php echo htmlspecialchars($item['description'] ?? 'Без описания'); ?></p>
                        <p><strong>Путь к изображению:</strong> <?php echo htmlspecialchars($item['image']); ?></p>
                    </div>
                </div>

                <p style="color: rgba(255,255,255,0.7); margin-bottom: 2rem;">
                    Вы уверены, что хотите удалить это изображение из карусели?
                </p>

                <form method="POST" action="">
                    <div class="button-group">
                        <button type="submit" class="btn btn-danger">Да, удалить изображение</button>
                        <a href="manage_carousel.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
