<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';

$db = new FileDatabase();
$error = '';
$success = '';

// Получаем ID педагога
if (!isset($_GET['id'])) {
    header('Location: manage_teachers.php?error=no_id');
    exit;
}

$teacherId = (int)$_GET['id'];
$teacher = $db->getTeacherById($teacherId);

if (!$teacher) {
    header('Location: manage_teachers.php?error=not_found');
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $achievements = trim($_POST['achievements'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Валидация
    if (empty($fullName)) {
        $error = 'Пожалуйста, введите ФИО педагога';
    } elseif (empty($specialization)) {
        $error = 'Пожалуйста, введите специализацию';
    } else {
        // Обновляем данные педагога
        $updatedTeacher = [
            'id' => $teacherId,
            'full_name' => $fullName,
            'specialization' => $specialization,
            'experience' => $experience,
            'education' => $education,
            'achievements' => $achievements,
            'phone' => $phone,
            'email' => $email
        ];
        
        if ($db->updateTeacher($teacherId, $updatedTeacher)) {
            header('Location: manage_teachers.php?success=updated');
            exit;
        } else {
            $error = 'Ошибка при обновлении данных педагога';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование педагога - Панель администратора</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .form-actions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="navigation">
            <a href="../index.php">← Главная</a>
            <a href="dashboard.php">Панель администратора</a>
            <a href="manage_teachers.php">Управление педагогами</a>
        </div>
        
        <div class="admin-header">
            <h1>Редактирование педагога</h1>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">ФИО педагога *</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?php echo htmlspecialchars($teacher['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="specialization">Специализация *</label>
                    <input type="text" id="specialization" name="specialization" 
                           value="<?php echo htmlspecialchars($teacher['specialization'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="experience">Опыт работы</label>
                    <input type="text" id="experience" name="experience" 
                           value="<?php echo htmlspecialchars($teacher['experience'] ?? ''); ?>" 
                           placeholder="Например: 5 лет">
                </div>
                
                <div class="form-group">
                    <label for="education">Образование</label>
                    <textarea id="education" name="education" 
                              placeholder="Укажите образование педагога"><?php echo htmlspecialchars($teacher['education'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="achievements">Достижения</label>
                    <textarea id="achievements" name="achievements" 
                              placeholder="Награды, достижения, сертификаты"><?php echo htmlspecialchars($teacher['achievements'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>" 
                           placeholder="+7 (999) 123-45-67">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($teacher['email'] ?? ''); ?>" 
                           placeholder="teacher@example.com">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    <a href="manage_teachers.php" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>