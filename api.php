<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') exit(0);

require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        echo json_encode(['success' => false, 'errors' => ['form' => 'Метод не поддерживается']]);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'errors' => ['form' => 'Неверный формат JSON']]);
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
    
    $checkStmt = $pdo->prepare("SELECT login, password_hash FROM autofinder_requests WHERE email = ?");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        echo json_encode([
            'success' => true,
            'login' => $existing['login'],
            'password' => 'пароль был отправлен ранее',
            'message' => 'Вы уже оставляли заявку'
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
    
    $stmt = $pdo->prepare("INSERT INTO autofinder_requests (name, phone, email, wishes, login, password_hash, status) VALUES (?, ?, ?, ?, ?, ?, 'new')");
    $stmt->execute([$name, $phone, $email, $wishes, $login, $passwordHash]);
    $newId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'login' => $login,
        'password' => $plainPassword,
        'message' => 'Заявка принята!',
        'profile_url' => '/web_backend_8/edit.php'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => ['form' => 'Ошибка сервера']]);
}
?>
