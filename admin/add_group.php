<?php
session_start();

// Проверяем авторизацию
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
        $groupData = [
            'name' => trim($_POST['name']),
            'direction' => trim($_POST['direction']),
            'description' => trim($_POST['description']),
            'age_group' => trim($_POST['age_group']),
            'price' => isset($_POST['price']) ? trim($_POST['price']) : '',
            'duration' => isset($_POST['duration']) ? trim($_POST['duration']) : '',
            'teacher_ids' => isset($_POST['teacher_ids']) ? $_POST['teacher_ids'] : []
        ];
        
        // Валидация
        if (empty($groupData['name'])) {
            throw new Exception('Название коллектива обязательно для заполнения');
        }
        
        if (empty($groupData['direction'])) {
            throw new Exception('Направление обязательно для заполнения');
        }
        
        if (empty($groupData['age_group'])) {
            throw new Exception('Возрастная группа обязательна для заполнения');
        }
        
        $db->addGroup($groupData);
        header('Location: manage_groups.php?success=added');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить коллектив</title>
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
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #667eea;
        }
        
        .form-header h1 {
            color: #333;
            margin: 0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
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
            box-sizing: border-box;
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
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0.25rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .form-actions {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e1e5e9;
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
        
        .required {
            color: #dc3545;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
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

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-container {
                margin: 1rem;
                padding: 1rem;
            }

            .dropdown-content {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="navigation">
            <a href="../index.php">← Главная</a>
            <a href="dashboard.php">Панель администратора</a>
            <a href="manage_groups.php">Управление коллективами</a>
        </div>
        
        <div class="form-header">
            <h1>🎭 Добавить новый коллектив</h1>
        </div>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Название коллектива <span class="required">*</span></label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <div class="help-text">Например: "Веселые нотки", "Театральная студия"</div>
                </div>
                
                <div class="form-group">
                    <label for="direction">Направление <span class="required">*</span></label>
                    <select id="direction" name="direction" required>
                        <option value="">Выберите направление</option>
                        <option value="Вокал" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Вокал') ? 'selected' : ''; ?>>Вокал</option>
                        <option value="Хореография" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Хореография') ? 'selected' : ''; ?>>Хореография</option>
                        <option value="Театр" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Театр') ? 'selected' : ''; ?>>Театр</option>
                        <option value="Изобразительное искусство" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Изобразительное искусство') ? 'selected' : ''; ?>>Изобразительное искусство</option>
                        <option value="Музыкальные инструменты" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Музыкальные инструменты') ? 'selected' : ''; ?>>Музыкальные инструменты</option>
                        <option value="Декоративно-прикладное творчество" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Декоративно-прикладное творчество') ? 'selected' : ''; ?>>Декоративно-прикладное творчество</option>
                        <option value="Другое" <?php echo (isset($_POST['direction']) && $_POST['direction'] === 'Другое') ? 'selected' : ''; ?>>Другое</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="age_group">Возрастная группа <span class="required">*</span></label>
                    <select id="age_group" name="age_group" required>
                        <option value="">Выберите возрастную группу</option>
                        <option value="3-5 лет" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === '3-5 лет') ? 'selected' : ''; ?>>3-5 лет</option>
                        <option value="6-8 лет" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === '6-8 лет') ? 'selected' : ''; ?>>6-8 лет</option>
                        <option value="9-12 лет" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === '9-12 лет') ? 'selected' : ''; ?>>9-12 лет</option>
                        <option value="13-15 лет" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === '13-15 лет') ? 'selected' : ''; ?>>13-15 лет</option>
                        <option value="16-18 лет" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === '16-18 лет') ? 'selected' : ''; ?>>16-18 лет</option>
                        <option value="Младшая" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === 'Младшая') ? 'selected' : ''; ?>>Младшая</option>
                        <option value="Взрослая" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === 'Взрослая') ? 'selected' : ''; ?>>Взрослая</option>
                        <option value="Смешанная группа" <?php echo (isset($_POST['age_group']) && $_POST['age_group'] === 'Смешанная группа') ? 'selected' : ''; ?>>Смешанная группа</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="duration">Продолжительность занятия</label>
                    <select id="duration" name="duration">
                        <option value="">Выберите продолжительность</option>
                        <option value="30 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '30 минут') ? 'selected' : ''; ?>>30 минут</option>
                        <option value="30 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '35 минут') ? 'selected' : ''; ?>>35 минут</option>
                        <option value="30 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '40 минут') ? 'selected' : ''; ?>>40 минут</option>
                        <option value="45 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '45 минут') ? 'selected' : ''; ?>>45 минут</option>
                        <option value="45 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '50 минут') ? 'selected' : ''; ?>>50 минут</option>
                        <option value="60 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час') ? 'selected' : ''; ?>>1 час</option>
                        <option value="60 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1час 5 минут') ? 'selected' : ''; ?>>1час 5 минут</option>
                        <option value="60 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час 10 минут') ? 'selected' : ''; ?>>1 час 10 минут</option>
                        <option value="60 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час 15 минут') ? 'selected' : ''; ?>>1 час 15 минут</option>
                        <option value="60 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час 20 минут') ? 'selected' : ''; ?>>1 час 20 минут</option>
                        <option value="60 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час 25 минут') ? 'selected' : ''; ?>>1 час 25 минут</option>
                        <option value="90 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час 30 минут') ? 'selected' : ''; ?>>1 час 30 минут</option>
                        <option value="90 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1 час 40 минут') ? 'selected' : ''; ?>>1 час 40 минут</option>
                        <option value="90 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '1час 50 минут') ? 'selected' : ''; ?>>1час 50 минут</option>
                        <option value="120 минут" <?php echo (isset($_POST['duration']) && $_POST['duration'] === '2 часа') ? 'selected' : ''; ?>>2 часа</option>
                    </select>
                    <div class="help-text">Длительность одного занятия</div>
                </div>
                
                <div class="form-group">
                    <label for="price">Стоимость</label>
                    <input type="text" id="price" name="price" placeholder="Например: 2000 руб/месяц"
                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                    <div class="help-text">Стоимость обучения (необязательно)</div>
                </div>
                
                <div class="form-group full-width">
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
                                $selectedTeachers = isset($_POST['teacher_ids']) ? $_POST['teacher_ids'] : [];
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
                    <div class="help-text">Выберите одного или нескольких педагогов для этого коллектива</div>
                </div>

                <div class="form-group full-width">
                    <label for="description">Описание коллектива</label>
                    <textarea id="description" name="description" placeholder="Краткое описание деятельности коллектива, особенности программы..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <div class="help-text">Подробная информация о коллективе (необязательно)</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">✅ Добавить коллектив</button>
                <a href="manage_groups.php" class="btn btn-secondary">❌ Отмена</a>
            </div>
        </form>
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
