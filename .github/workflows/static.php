<?php
session_start();

$host = 'localhost';
$dbname = 'cs2_semechki';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем базу данных, если она не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");
    
    // Создаем необходимые таблицы
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `weapon_types` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `rarities` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `class_name` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `conditions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `skins` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `weapon_type_id` int(11) NOT NULL,
            `rarity_id` int(11) NOT NULL,
            `condition_id` int(11) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `image` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`weapon_type_id`) REFERENCES `weapon_types` (`id`),
            FOREIGN KEY (`rarity_id`) REFERENCES `rarities` (`id`),
            FOREIGN KEY (`condition_id`) REFERENCES `conditions` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `cart` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `session_id` varchar(255) NOT NULL,
            `skin_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `session_skin` (`session_id`,`skin_id`),
            FOREIGN KEY (`skin_id`) REFERENCES `skins` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_cart` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `skin_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_skin` (`user_id`,`skin_id`),
            FOREIGN KEY (`skin_id`) REFERENCES `skins` (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `additional_info` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL,
            `description` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    // Добавляем тестовые данные, если таблицы пустые
    $stmt = $pdo->query("SELECT COUNT(*) FROM `weapon_types`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `weapon_types` (`name`) VALUES
            ('Штурмовая винтовка'),
            ('Снайперская винтовка'),
            ('Пистолет'),
            ('Нож'),
            ('Перчатки');
        ");
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM `rarities`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `rarities` (`name`, `class_name`) VALUES
            ('Обычная', 'uncommon'),
            ('Редкая', 'rare'),
            ('Запрещенная', 'legendary'),
            ('Тайная', 'mythical');
        ");
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM `conditions`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `conditions` (`name`) VALUES
            ('Прямо с завода'),
            ('Немного поношенное'),
            ('После полевых испытаний'),
            ('Поношенное'),
            ('Закаленное в боях');
        ");
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM `skins`");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `skins` (`name`, `weapon_type_id`, `rarity_id`, `condition_id`, `price`, `image`) VALUES
            ('AK-47 | Азимов', 1, 3, 1, 1500.00, 'ak47_asiimov.jpg'),
            ('AWP | Драгон Лоре', 2, 3, 1, 3500.00, 'awp_dragonlore.jpg'),
            ('Desert Eagle | Поверхностная закалка', 3, 3, 1, 1200.00, 'deagle_Surfacehardening.jpg'),
            ('Нож-бабочка | Ультрафиолет 2', 4, 4, 1, 12000.00, 'knife_ultraviolet.png'),
            ('Перчатки | Vise', 5, 4, 1, 8500.00, 'gloves_Vice.png');
        ");
    }
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Обработка добавления новой информации
if (isset($_POST['submit_info'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    
    try {
        $sql = "INSERT INTO additional_info (title, description) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description]);
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        die("Ошибка при добавлении информации: " . $e->getMessage());
    }
}

// Обработка добавления в корзину
if (isset($_POST['add_to_cart'])) {
    $skinId = (int)$_POST['skin_id'];
    
    try {
        if (isset($_SESSION['user_id'])) {
            $sql = "INSERT INTO user_cart (user_id, skin_id) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE quantity = quantity + 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $skinId]);
        } else {
            $sessionId = session_id();
            $sql = "INSERT INTO cart (session_id, skin_id) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE quantity = quantity + 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sessionId, $skinId]);
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        die("Ошибка при добавлении в корзину: " . $e->getMessage());
    }
}

// Обработка удаления из корзины
if (isset($_GET['remove_from_cart'])) {
    $skinId = (int)$_GET['remove_from_cart'];
    
    try {
        if (isset($_SESSION['user_id'])) {
            $sql = "DELETE FROM user_cart WHERE user_id = ? AND skin_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $skinId]);
        } else {
            $sessionId = session_id();
            $sql = "DELETE FROM cart WHERE session_id = ? AND skin_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sessionId, $skinId]);
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch (PDOException $e) {
        die("Ошибка при удалении из корзины: " . $e->getMessage());
    }
}

// Фильтрация скинов
$rarity = $_GET['rarity'] ?? 'all';
$type = $_GET['type'] ?? 'all';

try {
    $sql = "SELECT s.*, wt.name AS weapon_type, r.name AS rarity_name, r.class_name, c.name AS condition_name 
            FROM skins s
            JOIN weapon_types wt ON s.weapon_type_id = wt.id
            JOIN rarities r ON s.rarity_id = r.id
            JOIN conditions c ON s.condition_id = c.id
            WHERE (:rarity = 'all' OR r.class_name = :rarity)
            AND (:type = 'all' OR wt.name = :type)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['rarity' => $rarity, 'type' => $type]);
    $skins = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка при загрузке скинов: " . $e->getMessage());
}

// Получение дополнительной информации
try {
    $stmt = $pdo->query("SELECT * FROM additional_info ORDER BY created_at DESC");
    $additionalInfo = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка при загрузке дополнительной информации: " . $e->getMessage());
}

// Работа с корзиной
$cartItems = [];
$cartCount = 0;
$cartTotal = 0;

try {
    if (isset($_SESSION['user_id'])) {
        $sql = "SELECT s.*, uc.quantity 
                FROM user_cart uc
                JOIN skins s ON uc.skin_id = s.id
                WHERE uc.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll();
    } else {
        $sessionId = session_id();
        $sql = "SELECT s.*, c.quantity 
                FROM cart c
                JOIN skins s ON c.skin_id = s.id
                WHERE c.session_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$sessionId]);
        $cartItems = $stmt->fetchAll();
    }
    
    foreach ($cartItems as $item) {
        $cartCount += $item['quantity'];
        $cartTotal += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    die("Ошибка при загрузке корзины: " . $e->getMessage());
}

// Авторизация
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    try {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Переносим товары из корзины гостя в корзину пользователя
            $sessionId = session_id();
            $sql = "INSERT INTO user_cart (user_id, skin_id, quantity)
                    SELECT ?, skin_id, quantity FROM cart WHERE session_id = ?
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $sessionId]);
            
            // Удаляем корзину гостя
            $sql = "DELETE FROM cart WHERE session_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            $loginError = "Неверный email или пароль";
        }
    } catch (PDOException $e) {
        die("Ошибка при авторизации: " . $e->getMessage());
    }
}

// Регистрация
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $registerError = "Пароли не совпадают";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $email, $hashedPassword]);
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $username;
            
            // Переносим товары из корзины гостя в корзину пользователя
            $sessionId = session_id();
            $sql = "INSERT INTO user_cart (user_id, skin_id, quantity)
                    SELECT ?, skin_id, quantity FROM cart WHERE session_id = ?
                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $sessionId]);
            
            // Удаляем корзину гостя
            $sql = "DELETE FROM cart WHERE session_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sessionId]);
            
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $registerError = "Пользователь с таким email уже существует";
            } else {
                die("Ошибка при регистрации: " . $e->getMessage());
            }
        }
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS2 Skins Shop | Магазин скинов для CS:GO</title>
    <style>
        :root {
            --primary: #ff5500;
            --primary-dark: #cc4400;
            --dark: #1e1e1e;
            --light: #f8f8f8;
            --gray: #e0e0e0;
            --rare: #4b69ff;
            --legendary: #8847ff;
            --mythical: #d32ce6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        header {
            background-color: var(--dark);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo img {
            height: 50px;
        }
        
        .logo h1 {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav a:hover {
            color: var(--primary);
        }
        
        .auth-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('cs2_bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .filter-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            background-color: white;
        }
        
        .skins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .skin-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .skin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .skin-image {
            height: 200px;
            background-color: #eee;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }
        
        .skin-details {
            padding: 1.5rem;
        }
        
        .skin-name {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .skin-type {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .skin-condition {
            display: inline-block;
            background-color: var(--gray);
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }
        
        .skin-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .skin-rarity {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }
        
        .uncommon {
            background-color: var(--rare);
        }
        
        .rare {
            background-color: var(--rare);
        }
        
        .legendary {
            background-color: var(--legendary);
        }
        
        .mythical {
            background-color: var(--mythical);
        }
        
        .cart-modal {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background-color: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s;
            z-index: 1000;
            padding: 1.5rem;
            overflow-y: auto;
        }
        
        .cart-modal.open {
            right: 0;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray);
        }
        
        .close-cart {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .cart-items {
            margin-bottom: 1.5rem;
        }
        
        .cart-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray);
        }
        
        .cart-item-image {
            width: 80px;
            height: 60px;
            background-color: #eee;
            background-size: cover;
            background-position: center;
            border-radius: 4px;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        
        .cart-item-price {
            color: var(--primary);
            font-weight: bold;
        }
        
        .remove-item {
            color: #ff4444;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .cart-total {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: right;
            margin-bottom: 1.5rem;
        }
        
        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        footer {
            background-color: var(--dark);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 3rem;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        .copyright {
            color: #aaa;
            font-size: 0.9rem;
        }
        
        .auth-form {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            z-index: 1001;
            display: none;
            width: 90%;
            max-width: 400px;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .auth-form h3 {
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .auth-form input {
            display: block;
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
        }
        
        .auth-form button {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .auth-form button:hover {
            background-color: var(--primary-dark);
        }
        
        .auth-form .close-form {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
        }
        
        .auth-form .form-footer {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .auth-form .form-footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .auth-form .error {
            color: #ff4444;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-menu button {
            background: none;
            border: none;
            color: white;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-menu button:hover {
            color: var(--primary);
        }
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            min-width: 150px;
            display: none;
            z-index: 100;
        }
        
        .user-menu:hover .user-dropdown {
            display: block;
        }
        
        .user-dropdown a {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--dark);
            text-decoration: none;
        }
        
        .user-dropdown a:hover {
            background-color: var(--gray);
        }
        
        .info-section {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .info-section h2 {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .info-item {
            padding: 1.5rem;
            border: 1px solid var(--gray);
            border-radius: 8px;
        }
        
        .info-item h3 {
            margin-bottom: 0.5rem;
            color: var(--primary);
        }
        
        .info-item p {
            color: #666;
        }
        
        .info-item .info-date {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.5rem;
        }
        
        .add-service {
            background-color: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .add-service:hover {
            background-color: var(--primary-dark);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .modal-content .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            text-decoration: none;
            color: #666;
        }
        
        .modal-content h2 {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            text-align: right;
        }
        
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            nav ul {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
            
            .auth-actions {
                margin-top: 1rem;
            }
            
            .cart-modal {
                width: 100%;
                right: -100%;
            }
            
            .skins-grid {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="cs2_logo.png" alt="CS2 Skins Shop">
            <h1>CS2 Skins Shop</h1>
        </div>
        <nav>
            <ul>
                <li><a href="#">Главная</a></li>
                <li><a href="#">Топ скины</a></li>
                <li><a href="#">Ножи</a></li>
                <li><a href="#">Перчатки</a></li>
                <li><a href="#">О нас</a></li>
            </ul>
        </nav>
        <div class="auth-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <button>
                        <?= htmlspecialchars($_SESSION['username']) ?>
                        <span>▼</span>
                    </button>
                    <div class="user-dropdown">
                        <a href="profile.php">Профиль</a>
                        <a href="?logout=1">Выйти</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="btn" id="loginBtn">Войти</button>
            <?php endif; ?>
            <div class="cart-icon" id="cartToggle">
                🛒
                <span class="cart-count"><?= $cartCount ?></span>
            </div>
        </div>
    </header>

    <div class="container">
        <section class="hero">
            <h2>Лучшие скины для CS2</h2>
            <p>Покупайте и продавайте эксклюзивные скины для Counter-Strike 2 по лучшим ценам на рынке. Гарантия безопасной сделки и мгновенной доставки.</p>
            <a href="#skins" class="btn">Смотреть скины</a>
        </section>

        <section class="info-section">
            <h2>
                Дополнительная информация
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="?show_form=1" class="add-service">Добавить информацию</a>
                <?php endif; ?>
            </h2>
            
            <div class="info-list">
                <?php foreach ($additionalInfo as $info): ?>
                <div class="info-item">
                    <h3><?= htmlspecialchars($info['title']) ?></h3>
                    <p><?= htmlspecialchars($info['description']) ?></p>
                    <div class="info-date"><?= date('d.m.Y H:i', strtotime($info['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($additionalInfo)): ?>
                <p>Нет дополнительной информации</p>
                <?php endif; ?>
            </div>
        </section>

        <form method="get" action="">
            <div class="filters">
                <div class="filter-group">
                    <label for="rarity">Редкость:</label>
                    <select id="rarity" name="rarity" onchange="this.form.submit()">
                        <option value="all" <?= $rarity === 'all' ? 'selected' : '' ?>>Все</option>
                        <option value="uncommon" <?= $rarity === 'uncommon' ? 'selected' : '' ?>>Обычные</option>
                        <option value="rare" <?= $rarity === 'rare' ? 'selected' : ''
