<?php
session_start();

// Уничтожаем сессию
session_destroy();

// Перенаправляем на главную страницу
header('Location: ../index.php');
exit;
?>