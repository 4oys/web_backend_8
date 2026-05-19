<?php

require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $wishes = trim($_POST['wishes'] ?? '');
    
    $errors = [];
    $errors['name'] = validateName($name);
    $errors['phone'] = validatePhone($phone);
    $errors['email'] = validateEmail($email);
    $errors = array_filter($errors);
    
    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        $pdo = getDBConnection();
        $login = generateLogin($name, $phone);
        $plainPassword = generatePassword(8);
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO autofinder_requests (name, phone, email, wishes, login, password_hash, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'new')
        ");
        $stmt->execute([$name, $phone, $email, $wishes, $login, $passwordHash]);
        
        $message = "✅ Заявка принята!<br>📌 Логин: $login<br>🔑 Пароль: $plainPassword";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>AutoFinder - Отправка заявки</title>
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            text-align: center;
        }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .btn { display: inline-block; background: #1a2980; color: white; padding: 12px 24px; border-radius: 30px; text-decoration: none; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <h1>📝 AutoFinder</h1>
    
    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
        <a href="index.html" class="btn">← Вернуться на главную</a>
    <?php elseif ($error): ?>
        <div class="error">❌ <?= $error ?></div>
        <a href="index.html" class="btn">← Попробовать снова</a>
    <?php else: ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Ваше имя" required style="width:100%; padding:12px; margin:10px 0;">
            <input type="tel" name="phone" placeholder="Телефон" required style="width:100%; padding:12px; margin:10px 0;">
            <input type="email" name="email" placeholder="Email" required style="width:100%; padding:12px; margin:10px 0;">
            <textarea name="wishes" placeholder="Пожелания" rows="3" style="width:100%; padding:12px; margin:10px 0;"></textarea>
            <button type="submit" style="background:#1a2980; color:white; padding:12px 24px; border:none; border-radius:30px; cursor:pointer;">Отправить заявку</button>
        </form>
        <a href="index.html" style="display:block; margin-top:20px;">← Назад</a>
    <?php endif; ?>
</div>
</body>
</html>