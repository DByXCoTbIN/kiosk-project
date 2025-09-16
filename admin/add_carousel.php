<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $uploaded_file = $_FILES['image_file'] ?? null;

    // Валидация
    if (empty($title)) {
        $errors[] = 'Название обязательно для заполнения';
    }

    // Проверяем, был ли загружен файл ИЛИ указан URL
    $final_image_path = '';

    if (!empty($uploaded_file) && $uploaded_file['error'] === UPLOAD_ERR_OK) {
        // Обработка загруженного файла
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($uploaded_file['type'], $allowed_types)) {
            $errors[] = 'Разрешены только изображения (JPEG, PNG, GIF, WebP)';
        }

        if ($uploaded_file['size'] > $max_size) {
            $errors[] = 'Размер файла не должен превышать 5MB';
        }

        if (empty($errors)) {
            $upload_dir = '../css/img/carousel_uploads/';
            $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('carousel_', true) . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($uploaded_file['tmp_name'], $target_path)) {
                $final_image_path = 'css/img/carousel_uploads/' . $file_name;
            } else {
                $errors[] = 'Ошибка при сохранении файла';
            }
        }
    } elseif (!empty($image_url)) {
        // Используем указанный URL
        $full_path = $_SERVER['DOCUMENT_ROOT'] . '/html/' . ltrim($image_url, '/');
        if (!file_exists($full_path)) {
            $errors[] = 'Файл изображения не найден по указанному пути';
        } else {
            $final_image_path = $image_url;
        }
    } else {
        $errors[] = 'Необходимо загрузить файл ИЛИ указать URL существующего изображения';
    }

    if (empty($errors) && !empty($final_image_path)) {
        $data = [
            'title' => $title,
            'description' => $description,
            'image' => $final_image_path
        ];

        $new_id = $db->addCarouselItem($data);

        if ($new_id) {
            header('Location: manage_carousel.php?success=added');
            exit;
        } else {
            $errors[] = 'Ошибка при добавлении изображения';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить изображение в карусель - Панель администратора</title>
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

        .form-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
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
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: white;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .form-input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-textarea {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-size: 1rem;
            min-height: 120px;
            resize: vertical;
            color: white;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-textarea:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }

        .form-textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .btn {
            padding: 1rem 2rem;
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
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
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

        .error ul {
            margin: 0;
            padding-left: 1.5rem;
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

        .help-text {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            font-weight: 400;
        }

        .image-preview {
            margin-top: 1rem;
            max-width: 250px;
            border-radius: 12px;
            display: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .upload-options {
            margin-top: 0.5rem;
        }

        .option-group {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .option-group input[type="radio"] {
            margin-right: 0.75rem;
            accent-color: #667eea;
        }

        .option-group input[type="file"] {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem;
            border-radius: 6px;
            color: white;
            margin-top: 0.5rem;
        }

        .option-group label {
            color: white;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
            justify-content: flex-end;
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

            .form-container {
                padding: 2rem 1.5rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-secondary {
                margin-left: 0;
                margin-top: 1rem;
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
                <h1>Добавить изображение в карусель</h1>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title" class="form-label">Название *</label>
                        <input type="text" id="title" name="title" class="form-input"
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                        <div class="help-text">Название будет отображаться на изображении в карусели</div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Описание</label>
                        <textarea id="description" name="description" class="form-textarea"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="help-text">Краткое описание коллектива или мероприятия</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Выберите способ добавления изображения:</label>

                        <div class="upload-options">
                            <div class="option-group">
                                <input type="radio" id="upload_file" name="upload_method" value="file" checked>
                                <label for="upload_file">Загрузить файл</label>
                                <input type="file" id="image_file" name="image_file" class="form-input" accept="image/*" style="margin-top: 0.5rem;">
                                <div class="help-text">Выберите изображение с вашего компьютера (JPEG, PNG, GIF, WebP, макс. 5MB)</div>
                            </div>

                            <div class="option-group" style="margin-top: 1rem;">
                                <input type="radio" id="use_url" name="upload_method" value="url">
                                <label for="use_url">Использовать существующий файл</label>
                                <input type="text" id="image_url" name="image_url" class="form-input"
                                       value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>"
                                       placeholder="css/img/kollective/example.jpg" style="margin-top: 0.5rem;">
                                <div class="help-text">Путь к изображению относительно корня сайта</div>
                            </div>
                        </div>

                        <img id="image-preview" class="image-preview" alt="Предпросмотр">
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary">Добавить изображение</button>
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

        // Переключение между вариантами загрузки
        const fileRadio = document.getElementById('upload_file');
        const urlRadio = document.getElementById('use_url');
        const fileInput = document.getElementById('image_file');
        const urlInput = document.getElementById('image_url');
        const preview = document.getElementById('image-preview');

        function toggleUploadMethod() {
            if (fileRadio.checked) {
                fileInput.style.display = 'block';
                urlInput.style.display = 'none';
                urlInput.required = false;
                fileInput.required = true;
                preview.style.display = 'none';
            } else {
                fileInput.style.display = 'none';
                urlInput.style.display = 'block';
                fileInput.required = false;
                urlInput.required = true;
            }
        }

        fileRadio.addEventListener('change', toggleUploadMethod);
        urlRadio.addEventListener('change', toggleUploadMethod);

        // Инициализация
        toggleUploadMethod();

        // Предпросмотр для URL
        document.getElementById('image_url').addEventListener('input', function() {
            const url = this.value;
            const preview = document.getElementById('image-preview');

            if (url.trim() !== '') {
                preview.src = '../' + url;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        // Предпросмотр для загруженного файла
        document.getElementById('image_file').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('image-preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Обработка ошибок загрузки изображения
        document.getElementById('image-preview').addEventListener('error', function() {
            this.style.display = 'none';
            alert('Не удалось загрузить изображение. Проверьте правильность пути или файла.');
        });
    </script>
</body>

</html>
