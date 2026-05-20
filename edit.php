<?php
session_start();
require_once 'config.php';

$pdo = getDBConnection();
$message = '';
$error = '';
$request = null;
$isAuthenticated = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $login = trim($_POST['login'] ?? '');
    $pass = trim($_POST['password'] ?? '');
    
    if (empty($login) || empty($pass)) {
        $error = "Введите логин и пароль";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM autofinder_requests WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['edit_login'] = $user['login'];
            $_SESSION['edit_id'] = $user['id'];
            $isAuthenticated = true;
            $request = $user;
            $message = "Вы успешно вошли!";
        } else {
            $error = "Неверный логин или пароль";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    if (empty($_SESSION['edit_login']) || empty($_SESSION['edit_id'])) {
        $error = "Пожалуйста, войдите снова";
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $wishes = trim($_POST['wishes'] ?? '');
        
        $errors = [];
        if (empty($name)) $errors[] = "Имя обязательно";
        if (empty($phone)) $errors[] = "Телефон обязателен";
        if (empty($email)) $errors[] = "Email обязателен";
        if (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) $errors[] = "Неверный формат телефона";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Неверный email";
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE autofinder_requests SET name = ?, phone = ?, email = ?, wishes = ?, updated_at = NOW() WHERE id = ? AND login = ?");
            $stmt->execute([$name, $phone, $email, $wishes, $_SESSION['edit_id'], $_SESSION['edit_login']]);
            $message = "Заявка успешно обновлена!";
            
            $stmt = $pdo->prepare("SELECT * FROM autofinder_requests WHERE id = ?");
            $stmt->execute([$_SESSION['edit_id']]);
            $request = $stmt->fetch();
        } else {
            $error = implode("<br>", $errors);
            $request = ['name' => $name, 'phone' => $phone, 'email' => $email, 'wishes' => $wishes];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'logout') {
    session_destroy();
    $isAuthenticated = false;
    $request = null;
    $message = "Вы вышли из системы";
}

if (empty($request) && !empty($_SESSION['edit_login']) && !empty($_SESSION['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM autofinder_requests WHERE id = ? AND login = ?");
    $stmt->execute([$_SESSION['edit_id'], $_SESSION['edit_login']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($request) $isAuthenticated = true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование заявки</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            max-width: 550px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 8px; }
        .form-body { padding: 32px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; }
        .required::after { content: " *"; color: #ef4444; }
        input, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
        }
        .message { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 12px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 12px; margin-bottom: 20px; }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
        }
        .btn-logout { background: #ef4444; margin-top: 10px; }
        .btn-logout:hover { background: #dc2626; }
        .back-link { display: block; margin-top: 20px; color: #667eea; text-align: center; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Редактирование заявки</h1>
        <p>Войдите, чтобы изменить свои данные</p>
    </div>
    
    <div class="form-body">
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!$isAuthenticated): ?>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label class="required">Логин</label>
                    <input type="text" name="login" required>
                </div>
                <div class="form-group">
                    <label class="required">Пароль</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn">Войти</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="save">
                <div class="form-group">
                    <label class="required">Имя</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($request['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Телефон</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($request['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="required">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($request['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Пожелания</label>
                    <textarea name="wishes" rows="4"><?= htmlspecialchars($request['wishes']) ?></textarea>
                </div>
                <button type="submit" class="btn">Сохранить изменения</button>
            </form>
            <form method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-logout">Выйти</button>
            </form>
        <?php endif; ?>
        
        <a href="index.html" class="back-link">На главную</a>
    </div>
</div>
</body>
</html>
