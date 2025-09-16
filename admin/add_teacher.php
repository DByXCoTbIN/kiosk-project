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




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $teacherData = [
            'full_name' => trim($_POST['full_name']),
            'phone' => trim($_POST['phone']),
            'email' => trim($_POST['email']),
            'specialization' => trim($_POST['specialization']),
            'experience' => (int)$_POST['experience'],
            'education' => trim($_POST['education']),
            'bio' => trim($_POST['bio'])
        ];
        
        // Валидация
        if (empty($teacherData['full_name'])) {
            throw new Exception('Имя педагога обязательно для заполнения');
        }
        
        $db->addTeacher($teacherData);
        header('Location: manage_teachers.php?success=added');
        exit;
        
    } catch (Exception $e) {
        $error = 'Ошибка при добавлении педагога: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить педагога</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>

        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
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
        
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
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
        .btn-secondary { background: #6c757d; color: white; }
        
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
    </style>
</head>
<body>
    <div class="form-container">
        <div class="navigation">
            <a href="../index.php">← Главная</a>
            <a href="dashboard.php">Панель администратора</a>
            <a href="manage_teachers.php">← Управление педагогами</a>
        </div>
        
        <h2>Добавить педагога</h2>

        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">


                <label>ФИО *</label>
                <input type="text" name="full_name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
            </div>


            
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Специализация</label>
                <input type="text" name="specialization" placeholder="Например: Хореография, Вокал, Театр" value="<?php echo isset($_POST['specialization']) ? htmlspecialchars($_POST['specialization']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Стаж работы (лет)</label>
                <input type="number" name="experience" min="0" value="<?php echo isset($_POST['experience']) ? htmlspecialchars($_POST['experience']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Образование</label>
                <input type="text" name="education" value="<?php echo isset($_POST['education']) ? htmlspecialchars($_POST['education']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Биография</label>
                <textarea name="bio" placeholder="Краткая информация о педагоге"><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">Добавить педагога</button>
                <a href="manage_teachers.php" class="btn btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>