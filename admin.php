<?php

session_start();
require_once 'config.php';

if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - AutoFinder"');
    echo '<h1>🔐 Требуется авторизация</h1>';
    exit();
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT password_hash FROM autofinder_admin_users WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - AutoFinder"');
    echo '<h1>🔐 Неверный логин или пароль</h1>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['status']) && isset($_POST['id'])) {
    $stmt = $pdo->prepare("UPDATE autofinder_requests SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], (int)$_POST['id']]);
}

$requests = $pdo->query("SELECT * FROM autofinder_requests_view")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора - AutoFinder</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: white; margin-bottom: 30px; }
        table {
            width: 100%;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; text-align: left; }
        td { padding: 12px 15px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8fafc; }
        select, button { padding: 5px 10px; border-radius: 8px; border: 1px solid #ddd; }
        .btn-save { background: #28a745; color: white; border: none; cursor: pointer; }
        .logout { display: inline-block; margin-top: 20px; background: #dc2626; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔐 Панель администратора - Заявки AutoFinder</h1>
    
    <table>
        <thead>
            <tr><th>ID</th><th>Имя</th><th>Телефон</th><th>Email</th><th>Пожелания</th><th>Статус</th><th>Логин</th><th>Дата</th><th>Действие</th></tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><?= htmlspecialchars($req['id']) ?></td>
                <td><?= htmlspecialchars($req['name']) ?></td>
                <td><?= htmlspecialchars($req['phone']) ?></td>
                <td><?= htmlspecialchars($req['email']) ?></td>
                <td><?= htmlspecialchars($req['wishes']) ?></td>
                <td><?= htmlspecialchars($req['status_text']) ?></td>
                <td><?= htmlspecialchars($req['login']) ?></td>
                <td><?= htmlspecialchars($req['created_at']) ?></td>
                <td>
                    <form method="POST" style="display:flex; gap:5px;">
                        <input type="hidden" name="id" value="<?= $req['id'] ?>">
                        <select name="status">
                            <option value="new" <?= $req['status'] == 'new' ? 'selected' : '' ?>>🟢 Новая</option>
                            <option value="processed" <?= $req['status'] == 'processed' ? 'selected' : '' ?>>🟡 В обработке</option>
                            <option value="completed" <?= $req['status'] == 'completed' ? 'selected' : '' ?>>🔵 Завершена</option>
                        </select>
                        <button type="submit" class="btn-save">Сохранить</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <a href="index.html" class="logout" style="background:#667eea;">← На главную</a>
    <a href="?logout=1" class="logout" onclick="return confirm('Выйти?')">🚪 Выйти</a>
</div>
</body>
</html>