<?php
session_start();

// Проверяем авторизацию
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/file_database.php';
$db = new FileDatabase();

$id = $_GET['id'];

if ($db->deleteSchedule($id)) {
    header('Location: ../index.php?success=deleted');
    exit;
} else {
    header('Location: ../index.php?error=not_found');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Удалить запись - Панель администратора</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .delete-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .warning-box .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .schedule-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #dc3545;
        }
        
        .schedule-info h3 {
            margin: 0 0 1rem 0;
            color: #333;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .schedule-days {
            margin-top: 1rem;
        }
        
        .day-item {
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: transform 0.3s ease;
            margin-right: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
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
        
        .button-group {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                padding: 0 0.5rem;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 0.3rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="navigation">
            <a href="../index.php">← Главная</a>
            <a href="dashboard.php">Панель администратора</a>
            <a href="../index.php">Главная</a>
        </div>
        
        <div class="admin-header">
            <h1>Удаление записи</h1>
        </div>
        
        <div class="delete-container">
            <?php if (isset($error)): ?>
                <div class="error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="warning-box">
                <div class="icon">⚠️</div>
                <h3>Внимание!</h3>
                <p>Вы собираетесь удалить запись из расписания. Это действие нельзя отменить.</p>
            </div>
            
            <div class="schedule-info">
                <h3>Информация о записи #<?php echo $schedule['id']; ?></h3>
                
                <div class="info-row">
                    <span class="info-label">Педагог:</span>
                    <span class="info-value"><?php echo htmlspecialchars($schedule['teacher_name']); ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Коллектив:</span>
                    <span class="info-value"><?php echo htmlspecialchars($schedule['group_name']); ?></span>
                </div>
                
                <div class="schedule-days">
                    <strong>Расписание:</strong>
                    <div class="day-item">
                        <span>Понедельник:</span>
                        <span><?php echo htmlspecialchars($schedule['monday']); ?></span>
                    </div>
                    <div class="day-item">
                        <span>Вторник:</span>
                        <span><?php echo htmlspecialchars($schedule['tuesday']); ?></span>
                    </div>
                    <div class="day-item">
                        <span>Среда:</span>
                        <span><?php echo htmlspecialchars($schedule['wednesday']); ?></span>
                    </div>
                    <div class="day-item">
                        <span>Четверг:</span>
                        <span><?php echo htmlspecialchars($schedule['thursday']); ?></span>
                    </div>
                    <div class="day-item">
                        <span>Пятница:</span>
                        <span><?php echo htmlspecialchars($schedule['friday']); ?></span>
                    </div>
                    <div class="day-item">
                        <span>Суббота:</span>
                        <span><?php echo htmlspecialchars($schedule['saturday']); ?></span>
                    </div>
                    <div class="day-item">
                        <span>Воскресенье:</span>
                        <span><?php echo htmlspecialchars($schedule['sunday']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="button-group">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Вы точно уверены, что хотите удалить эту запись?')">
                        🗑️ Да, удалить запись
                    </button>
                </form>
                <a href="../index.php" class="btn btn-secondary">
                    ❌ Отмена
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Дополнительное подтверждение при удалении
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmed = confirm('Это действие нельзя отменить. Вы действительно хотите удалить запись?');
            if (!confirmed) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
