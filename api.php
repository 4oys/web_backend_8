<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$resource = end($segments);

try {
    $pdo = getDBConnection();
    
    if ($method == 'POST') {
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => ['form' => 'Неверный формат JSON']]);
            exit();
        }
        
        $errors = [];
        
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $wishes = trim($input['wishes'] ?? '');
        
        $errors['name'] = validateName($name);
        $errors['phone'] = validatePhone($phone);
        $errors['email'] = validateEmail($email);
        $errors = array_filter($errors);
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }
        
        $checkStmt = $pdo->prepare("SELECT id, login, password_hash FROM autofinder_requests WHERE email = ?");
        $checkStmt->execute([$email]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            echo json_encode([
                'success' => true,
                'login' => $existing['login'],
                'password' => 'пароль был отправлен ранее',
                'message' => 'Вы уже оставляли заявку. Ваши данные для входа выше.'
            ]);
            exit();
        }
        
        $login = generateLogin($name, $phone);
        $plainPassword = generatePassword(8);
        $passwordHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $checkLoginStmt = $pdo->prepare("SELECT id FROM autofinder_requests WHERE login = ?");
        $checkLoginStmt->execute([$login]);
        if ($checkLoginStmt->fetch()) {
            $login = $login . '_' . rand(1000, 9999);
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO autofinder_requests (name, phone, email, wishes, login, password_hash, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'new')
        ");
        $stmt->execute([$name, $phone, $email, $wishes, $login, $passwordHash]);
        
        echo json_encode([
            'success' => true,
            'login' => $login,
            'password' => $plainPassword,
            'message' => 'Заявка успешно отправлена!'
        ]);
        exit();
    }
    
    if ($method == 'PUT' && isset($_GET['id'])) {
        
        // Проверка авторизации через сессию
        session_start();
        if (empty($_SESSION['login']) || empty($_SESSION['uid'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Не авторизован']);
            exit();
        }
        
        $id = (int)$_GET['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Проверка, что заявка принадлежит этому пользователю
        $checkStmt = $pdo->prepare("SELECT id FROM autofinder_requests WHERE id = ? AND login = ?");
        $checkStmt->execute([$id, $_SESSION['login']]);
        if (!$checkStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
            exit();
        }
        
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $wishes = trim($input['wishes'] ?? '');
        
        $errors = [];
        $errors['name'] = validateName($name);
        $errors['phone'] = validatePhone($phone);
        $errors['email'] = validateEmail($email);
        $errors = array_filter($errors);
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit();
        }
        
        $stmt = $pdo->prepare("
            UPDATE autofinder_requests 
            SET name = ?, phone = ?, email = ?, wishes = ?, updated_at = NOW()
            WHERE id = ? AND login = ?
        ");
        $stmt->execute([$name, $phone, $email, $wishes, $id, $_SESSION['login']]);
        
        echo json_encode(['success' => true, 'message' => 'Данные обновлены']);
        exit();
    }
    
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => ['form' => 'Не найден']]);
    
} catch (PDOException $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['form' => 'Ошибка сервера']]);
}
?>