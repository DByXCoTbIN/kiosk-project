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

// Получаем ID коллектива
if (!isset($_GET['id'])) {
    header('Location: manage_groups.php?error=no_id');
    exit;
}

$groupId = (int)$_GET['id'];
$group = $db->getGroupById($groupId);

if (!$group) {
    header('Location: manage_groups.php?error=not_found');
    exit;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $direction = trim($_POST['direction'] ?? '');
    $ageGroup = trim($_POST['age_group'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $teacherIds = isset($_POST['teacher_ids']) ? $_POST['teacher_ids'] : [];
    
    // Валидация
    if (empty($name)) {
        $error = 'Пожалуйста, введите название коллектива';
    } elseif (empty($direction)) {
        $error = 'Пожалуйста, выберите направление';
    } elseif (empty($ageGroup)) {
        $error = 'Пожалуйста, выберите возрастную группу';
    } else {
        // Обновляем данные коллектива
        $updatedGroup = [
            'id' => $groupId,
            'name' => $name,
            'direction' => $direction,
            'age_group' => $ageGroup,
            'description' => $description,
            'duration' => $duration,
            'price' => $price,
            'teacher_ids' => $teacherIds
        ];
        
        if ($db->updateGroup($groupId, $updatedGroup)) {
            header('Location: manage_groups.php?success=updated');
            exit;
        } else {
            $error = 'Ошибка при обновлении данных коллектива';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование коллектива - Панель администратора</title>
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
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

        /* Teachers Selection Styles */
        .teachers-selection {
            position: relative;
        }

        .teachers-dropdown {
            position: relative;
        }

        .dropdown-header {
            padding: 0.875rem 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .dropdown-header:hover {
            border-color: #667eea;
        }

        .dropdown-text {
            color: #666;
            font-size: 1rem;
        }

        .dropdown-arrow {
            color: #667eea;
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .teachers-dropdown.open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e1e5e9;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .teachers-dropdown.open .dropdown-content {
            display: block;
        }

        .teacher-option {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
        }

        .teacher-option:hover {
            background: #f8f9fa;
        }

        .teacher-option:last-child {
            border-bottom: none;
        }

        .teacher-option input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #e1e5e9;
            border-radius: 4px;
            margin-right: 0.75rem;
            position: relative;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .teacher-option input[type="checkbox"]:checked + .checkmark {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
        }

        .teacher-option input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .teacher-name {
            flex: 1;
            color: #333;
            font-size: 0.95rem;
        }

        .selected-teachers {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e1e5e9;
        }

        .selected-count {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .selected-teacher-tag {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="navigation">
            <a href="../index.php">← Главная</a>
            <a href="dashboard.php">Панель администратора</a>
            <a href="manage_groups.php">Управление коллективами</a>
        </div>
        
        <div class="admin-header">
            <h1>Редактирование коллектива</h1>
        </div>
        
        <div class="form-container">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Название коллектива *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($group['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="direction">Направление *</label>
                    <select id="direction" name="direction" required>
                        <option value="">Выберите направление</option>
                        <option value="Вокальное" <?php echo ($group['direction'] ?? '') === 'Вокальное' ? 'selected' : ''; ?>>Вокальное</option>
                        <option value="Хореографическое" <?php echo ($group['direction'] ?? '') === 'Хореографическое' ? 'selected' : ''; ?>>Хореографическое</option>
                        <option value="Театральное" <?php echo ($group['direction'] ?? '') === 'Театральное' ? 'selected' : ''; ?>>Театральное</option>
                        <option value="Инструментальное" <?php echo ($group['direction'] ?? '') === 'Инструментальное' ? 'selected' : ''; ?>>Инструментальное</option>
                        <option value="Изобразительное искусство" <?php echo ($group['direction'] ?? '') === 'Изобразительное искусство' ? 'selected' : ''; ?>>Изобразительное искусство</option>
                        <option value="Декоративно-прикладное" <?php echo ($group['direction'] ?? '') === 'Декоративно-прикладное' ? 'selected' : ''; ?>>Декоративно-прикладное</option>
                        <option value="Другое" <?php echo ($group['direction'] ?? '') === 'Другое' ? 'selected' : ''; ?>>Другое</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="age_group">Возрастная группа *</label>
                    <select id="age_group" name="age_group" required>
                        <option value="">Выберите возрастную группу</option>
                        <option value="3-5 лет" <?php echo ($group['age_group'] ?? '') === '3-5 лет' ? 'selected' : ''; ?>>3-5 лет</option>
                        <option value="6-8 лет" <?php echo ($group['age_group'] ?? '') === '6-8 лет' ? 'selected' : ''; ?>>6-8 лет</option>
                        <option value="9-12 лет" <?php echo ($group['age_group'] ?? '') === '9-12 лет' ? 'selected' : ''; ?>>9-12 лет</option>
                        <option value="13-16 лет" <?php echo ($group['age_group'] ?? '') === '13-16 лет' ? 'selected' : ''; ?>>13-16 лет</option>
                        <option value="17+ лет" <?php echo ($group['age_group'] ?? '') === '17+ лет' ? 'selected' : ''; ?>>17+ лет</option>
                        <option value="Младшие" <?php echo ($group['age_group'] ?? '') === 'Младшие' ? 'selected' : ''; ?>>Младшие</option>
                        <option value="Взрослые" <?php echo ($group['age_group'] ?? '') === 'Взрослые' ? 'selected' : ''; ?>>Взрослые</option>
                        <option value="Смешанная" <?php echo ($group['age_group'] ?? '') === 'Смешанная' ? 'selected' : ''; ?>>Смешанная</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Педагоги</label>
                    <div class="teachers-selection">
                        <div class="teachers-dropdown">
                            <div class="dropdown-header" onclick="toggleTeachersDropdown()">
                                <span class="dropdown-text">Выберите педагогов</span>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                            <div class="dropdown-content">
                                <?php
                                $teachers = $db->getAllTeachers();
                                $selectedTeachers = isset($group['teacher_ids']) ? $group['teacher_ids'] : [];
                                foreach($teachers as $teacher):
                                ?>
                                <label class="teacher-option">
                                    <input type="checkbox" name="teacher_ids[]" value="<?php echo $teacher['id']; ?>"
                                           <?php echo in_array($teacher['id'], $selectedTeachers) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    <span class="teacher-name"><?php echo htmlspecialchars($teacher['full_name']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="selected-teachers">
                            <div class="selected-count">Выбрано: <span id="selectedCount">0</span></div>
                            <div id="selectedTeachersList"></div>
                        </div>
                    </div>
                    <div style="font-size: 0.9rem; color: #666; margin-top: 0.25rem;">
                        Выберите одного или нескольких педагогов для этого коллектива
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Описание</label>
                    <textarea id="description" name="description"
                              placeholder="Описание коллектива, программы обучения"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="duration">Продолжительность занятий</label>
                    <input type="text" id="duration" name="duration" 
                           value="<?php echo htmlspecialchars($group['duration'] ?? ''); ?>" 
                           placeholder="Например: 45 минут, 1 час">
                </div>
                
                <div class="form-group">
                    <label for="price">Стоимость</label>
                    <input type="text" id="price" name="price" 
                           value="<?php echo htmlspecialchars($group['price'] ?? ''); ?>" 
                           placeholder="Например: 2000 руб/месяц">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    <a href="manage_groups.php" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleTeachersDropdown() {
            const dropdown = document.querySelector('.teachers-dropdown');
            dropdown.classList.toggle('open');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.querySelector('.teachers-dropdown');
            const header = document.querySelector('.dropdown-header');

            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('open');
            }
        });

        // Update selected teachers display
        function updateSelectedTeachers() {
            const checkboxes = document.querySelectorAll('.teacher-option input[type="checkbox"]');
            const selectedTeachersList = document.getElementById('selectedTeachersList');
            const selectedCount = document.getElementById('selectedCount');
            const dropdownText = document.querySelector('.dropdown-text');

            const selectedTeachers = [];
            let count = 0;

            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const teacherName = checkbox.closest('.teacher-option').querySelector('.teacher-name').textContent;
                    selectedTeachers.push(teacherName);
                    count++;
                }
            });

            // Update counter
            selectedCount.textContent = count;

            // Update dropdown header text
            if (count === 0) {
                dropdownText.textContent = 'Выберите педагогов';
            } else if (count === 1) {
                dropdownText.textContent = 'Выбран 1 педагог';
            } else if (count < 5) {
                dropdownText.textContent = `Выбрано ${count} педагога`;
            } else {
                dropdownText.textContent = `Выбрано ${count} педагогов`;
            }

            // Update selected teachers display
            selectedTeachersList.innerHTML = '';
            selectedTeachers.forEach(teacher => {
                const tag = document.createElement('span');
                tag.className = 'selected-teacher-tag';
                tag.textContent = teacher;
                selectedTeachersList.appendChild(tag);
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedTeachers();

            // Add event listeners to checkboxes
            const checkboxes = document.querySelectorAll('.teacher-option input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedTeachers);
            });
        });
    </script>
</body>
</html>
