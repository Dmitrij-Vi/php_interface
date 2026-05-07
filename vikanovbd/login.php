<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Если уже авторизован, перенаправляем на главную
if (isset($_SESSION['auth'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim($_POST['user'] ?? '');
    $pass = trim($_POST['pass'] ?? '');
    
    // Проверка логина и пароля
    if ($user === 'admin' && $pass === 'admin') {
        $_SESSION['auth'] = true;
        $_SESSION['username'] = 'admin';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - Аренда бизнес-центра</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>🏢 Бизнес-центр</h2>
        <h3>Учет аренды помещений</h3>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>👤 Логин</label>
                <input type="text" 
                       name="user" 
                       placeholder="Введите логин" 
                       autocomplete="off"
                       value="">
            </div>
            <div class="form-group">
                <label>🔒 Пароль</label>
                <input type="password" 
                       name="pass" 
                       placeholder="Введите пароль" 
                       autocomplete="new-password"
                       value="">
            </div>
            <button type="submit" name="login">Войти в систему</button>
        </form>
    </div>
</body>
</html>