<?php
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

// Получение всех заявок
function getAllRequests($pdo) {
    $stmt = $pdo->query("SELECT * FROM autofinder_requests ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function deleteRequest($pdo, $id) {
    try {
        $pdo->prepare("DELETE FROM autofinder_requests WHERE id = ?")->execute([$id]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Создание таблицы администраторов (если нет)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_users (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        login VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE login = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $pdo->prepare("INSERT INTO admin_users (login, password_hash) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')")->execute();
}

// HTTP-авторизация
if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - AutoFinder"');
    echo '<h1>🔐 Требуется авторизация</h1>';
    exit();
}

$stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel - AutoFinder"');
    echo '<h1>🔐 Неверный логин или пароль</h1>';
    exit();
}

// Обработка удаления
$message = '';
$error = '';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if (deleteRequest($pdo, $id)) {
        $message = "✅ Заявка #{$id} успешно удалена";
    } else {
        $error = "❌ Ошибка при удалении заявки #{$id}";
    }
}

$requests = getAllRequests($pdo);
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
        .message { background: #dcfce7; color: #16a34a; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .error { background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 12px; margin-bottom: 20px; }
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
        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-delete:hover { background: #dc2626; }
    </style>
</head>
<body>
<div class="container">
    <h1>Панель администратора - Заявки AutoFinder</h1>
    
    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr><th>ID</th><th>Имя</th><th>Телефон</th><th>Email</th><th>Пожелания</th><th>Статус</th><th>Логин</th><th>Дата</th><th>Действие</th></tr>
        </thead>
        <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="9" style="text-align: center;">📭 Нет заявок</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $req): ?>
                <tr>
                    <td><?= htmlspecialchars($req['id']) ?></td>
                    <td><?= htmlspecialchars($req['name']) ?></td>
                    <td><?= htmlspecialchars($req['phone']) ?></td>
                    <td><?= htmlspecialchars($req['email']) ?></td>
                    <td><?= htmlspecialchars($req['wishes']) ?></td>
                    <td><?= htmlspecialchars($req['status']) ?></td>
                    <td><?= htmlspecialchars($req['login']) ?></td>
                    <td><?= htmlspecialchars($req['created_at']) ?></td>
                    <td><a href="?delete=<?= $req['id'] ?>" class="btn-delete" onclick="return confirm('Удалить?')">🗑️ Удалить</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
