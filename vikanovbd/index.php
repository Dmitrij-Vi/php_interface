<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

$total_buildings = $pdo->query("SELECT COUNT(*) FROM buildings")->fetchColumn();
$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$active_contracts = $pdo->query("SELECT COUNT(*) FROM contracts WHERE status='active'")->fetchColumn();
$total_tenants = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();

$recent = $pdo->query("SELECT p.amount, p.payment_date, c.contract_number 
                       FROM payments p 
                       JOIN contracts c USING(contract_id) 
                       ORDER BY p.payment_date DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Дашборд</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="menu">
    <div class="menu-left">
        <a href="index.php">🏠 Главная</a>
        <a href="buildings.php">🏛️ Здания</a>
        <a href="floors.php">📊 Этажи</a>
        <a href="rooms.php">🚪 Помещения</a>
        <a href="tenants.php">👥 Арендаторы</a>
        <a href="contracts.php">📄 Договоры</a>
        <a href="payments.php">💰 Платежи</a>
    </div>
    <div class="menu-right">
        <a href="logout.php" class="logout-btn">🚪 Выход</a>
    </div>
</div>
<div class="container">
    <h2>Статистика</h2>
    <?php show_msg(); ?>
    <div class="stats">
        <div>🏛️ Зданий: <?php echo $total_buildings; ?></div>
        <div>🚪 Помещений: <?php echo $total_rooms; ?></div>
        <div>📄 Активных договоров: <?php echo $active_contracts; ?></div>
        <div>👥 Арендаторов: <?php echo $total_tenants; ?></div>
    </div>
    <h3>Последние платежи</h3>
    <table border="1" cellpadding="8">
        <tr><th>Договор</th><th>Сумма</th><th>Дата</th></tr>
        <?php foreach ($recent as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['contract_number']); ?></td>
            <td><?php echo number_format($r['amount'], 2); ?> ₽</td>
            <td><?php echo htmlspecialchars($r['payment_date']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>