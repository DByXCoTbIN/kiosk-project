<?php
// Обработка формы при отправке
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация полей
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($username)) {
        $errors[] = "Имя пользователя обязательно";
    } elseif (strlen($username) < 3) {
        $errors[] = "Имя пользователя должно быть не менее 3 символов";
    }

    if (empty($email)) {
        $errors[] = "Email обязателен";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный формат email";
    }

    if (empty($password)) {
        $errors[] = "Пароль обязателен";
    } elseif (strlen($password) < 6) {
        $errors[] = "Пароль должен быть не менее 6 символов";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают";
    }

    // Если ошибок нет - регистрация
    if (empty($errors)) {
        try {
            // Подключение к БД (замените данные на свои)
            $pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8mb4', 'username', 'password');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Хеширование пароля
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Вставка данных в БД
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);

            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Ошибка базы данных: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 2rem auto; }
        .error { color: red; margin-bottom: 1rem; }
        .success { color: green; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php if ($success): ?>
        <div class="success">Вы успешно зарегистрированы!</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>
            Имя пользователя:
            <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>">
        </label><br><br>

        <label>
            Email:
            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
        </label><br><br>

        <label>
            Пароль:
            <input type="password" name="password">
        </label><br><br>

        <label>
            Подтвердить пароль:
            <input type="password" name="confirm_password">
        </label><br><br>

        <button type="submit">Зарегистрироваться</button>
    </form>
</body>
</html>
