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
<div class="header">
    <div class="navbar">
        <div class="logo">
            🏢 <span>Бизнес-центр</span>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-link active">Главная</a>
            <a href="buildings.php" class="nav-link">Здания</a>
            <a href="floors.php" class="nav-link">Этажи</a>
            <a href="rooms.php" class="nav-link">Помещения</a>
            <a href="tenants.php" class="nav-link">Арендаторы</a>
            <a href="contracts.php" class="nav-link">Договоры</a>
            <a href="payments.php" class="nav-link">Платежи</a>
            <a href="#" class="nav-link logout-btn" onclick="showLogoutModal(event)">Выход</a>
        </div>
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
    <table>
        <thead>
            <tr><th>Договор</th><th>Сумма</th><th>Дата</th></tr>
        </thead>
        <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['contract_number']); ?></td>
                <td><?php echo number_format($r['amount'], 2); ?> ₽</td>
                <td><?php echo htmlspecialchars($r['payment_date']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Модальное окно подтверждения выхода -->
<div id="logoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Подтверждение выхода</h3>
            <span class="modal-close" onclick="closeLogoutModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>⚠️ Вы уверены, что хотите выйти из системы?</p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">❌ Отмена</button>
            <button class="modal-btn modal-btn-confirm" onclick="confirmLogout()">✅ Выйти</button>
        </div>
    </div>
</div>

<script>
function showLogoutModal(event) {
    event.preventDefault();
    document.getElementById('logoutModal').style.display = 'block';
}

function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}

function confirmLogout() {
    window.location.href = 'logout.php';
}

window.onclick = function(event) {
    var modal = document.getElementById('logoutModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLogoutModal();
    }
});
</script>
</body>
</html>
