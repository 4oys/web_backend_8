<?php

session_start();
require_once 'config.php';

$host = 'localhost';
$dbname = 'u82564';
$username = 'u82564';
$password = '1341640';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получаем ID заявки из URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Неверный ID заявки");
}

// Получаем данные заявки
$stmt = $pdo->prepare("SELECT * FROM autofinder_requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Заявка не найдена");
}

$message = '';
$error = '';

// Обработка редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Проверяем, что пользователь ввёл правильный логин и пароль
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($login) || empty($password)) {
        $error = "Введите логин и пароль для подтверждения";
    } else {
        // Проверяем логин и пароль
        $stmt = $pdo->prepare("SELECT * FROM autofinder_requests WHERE id = ? AND login = ?");
        $stmt->execute([$id, $login]);
        $check = $stmt->fetch();
        
        if (!$check || !password_verify($password, $check['password_hash'])) {
            $error = "Неверный логин или пароль";
        } else {
            // Валидация новых данных
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
                $stmt = $pdo->prepare("
                    UPDATE autofinder_requests 
                    SET name = ?, phone = ?, email = ?, wishes = ?, updated_at = NOW()
                    WHERE id = ? AND login = ?
                ");
                $stmt->execute([$name, $phone, $email, $wishes, $id, $login]);
                
                $message = "✅ Заявка успешно обновлена!";
                
                // Обновляем данные для отображения
                $stmt = $pdo->prepare("SELECT * FROM autofinder_requests WHERE id = ?");
                $stmt->execute([$id]);
                $request = $stmt->fetch();
            } else {
                $error = implode("<br>", $errors);
            }
        }
    }
}

$allowedLanguages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заявки - AutoFinder</title>
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
            max-width: 600px;
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
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #1f2937; }
        .required::after { content: " *"; color: #ef4444; }
        input, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .message {
            background: #dcfce7;
            color: #16a34a;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .btn-save {
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
        .btn-save:hover { transform: translateY(-2px); }
        hr { margin: 20px 0; border: none; border-top: 1px solid #e5e7eb; }
        .auth-info {
            background: #f3f4f6;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>✏️ Редактирование заявки</h1>
        <p>Измените данные своей заявки</p>
    </div>
    
    <div class="form-body">
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>
        
        <div class="auth-info">
            <p><strong>🔐 Для редактирования введите ваш логин и пароль</strong></p>
            <p><small>Логин: <?= htmlspecialchars($request['login']) ?></small></p>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" placeholder="Ваш логин" required>
            </div>
            
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" placeholder="Ваш пароль" required>
            </div>
            
            <hr>
            
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
                <label>Пожелания к авто</label>
                <textarea name="wishes" rows="4"><?= htmlspecialchars($request['wishes']) ?></textarea>
            </div>
            
            <button type="submit" class="btn-save">💾 Сохранить изменения</button>
        </form>
        
        <div style="text-align: center;">
            <a href="index.html" class="back-link">← Вернуться на главную</a>
        </div>
    </div>
</div>
</body>
</html>
