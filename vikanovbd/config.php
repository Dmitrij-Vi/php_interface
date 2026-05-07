<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$host = 'localhost';        // или '127.0.0.1'
$db   = 'arenda_biznezcentr';
$user = 'admin';
$pass = 'admin';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Ошибка подключения к БД: ' . $e->getMessage());
}

function message($text, $type = 'success') {
    $_SESSION['msg'] = ['text' => $text, 'type' => $type];
}

function show_msg() {
    if (isset($_SESSION['msg'])) {
        $class = $_SESSION['msg']['type'] == 'success' ? '#4caf50' : '#f44336';
        echo '<div style="background:#f9f9f9; border-left:4px solid ' . $class . '; padding:10px; margin:10px 0;">'
             . htmlspecialchars($_SESSION['msg']['text']) . '</div>';
        unset($_SESSION['msg']);
    }
}
?>