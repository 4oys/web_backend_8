<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u82564');  
define('DB_USER', 'u82564');
define('DB_PASS', '1341640');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            die("Ошибка подключения к базе данных");
        }
    }
    return $pdo;
}

function generateLogin($name, $phone) {
    $firstName = trim(strtok($name, ' ')); 
    $firstName = preg_replace('/[^a-zA-Zа-яА-Я]/u', '', $firstName); 
    $firstName = strtolower($firstName); 
    
    if (empty($firstName)) {
        $firstName = 'user';
    }
    
    $phoneSuffix = substr(preg_replace('/[^0-9]/', '', $phone), -4);
    
    $random = rand(100, 999);
    
    return $firstName . '_' . $phoneSuffix . '_' . $random;
}


function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

function validateName($name) {
    if (empty($name)) return 'Имя обязательно';
    if (strlen($name) > 150) return 'Имя не более 150 символов';
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $name)) {
        return 'Имя содержит недопустимые символы';
    }
    return null;
}

function validatePhone($phone) {
    if (empty($phone)) return 'Телефон обязателен';
    if (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        return 'Неверный формат телефона';
    }
    return null;
}

function validateEmail($email) {
    if (empty($email)) return 'Email обязателен';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Неверный email';
    }
    return null;
}
?>
