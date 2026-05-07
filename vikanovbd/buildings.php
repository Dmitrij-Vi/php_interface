<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO buildings (address, city, total_floors) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['address'], $_POST['city'], $_POST['total_floors']]);
    message('Здание добавлено');
    header('Location: buildings.php');
    exit;
}
// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE buildings SET address=?, city=?, total_floors=? WHERE building_id=?");
    $stmt->execute([$_POST['address'], $_POST['city'], $_POST['total_floors'], $_POST['id']]);
    message('Здание обновлено');
    header('Location: buildings.php');
    exit;
}
// Удаление - с проверкой связанных записей
if (isset($_GET['del'])) {
    $building_id = $_GET['del'];
    try {
        // Проверяем, есть ли этажи у этого здания
        $check_floors = $pdo->prepare("SELECT COUNT(*) FROM floors WHERE building_id = ?");
        $check_floors->execute([$building_id]);
        $floors_count = $check_floors->fetchColumn();
        
        if ($floors_count > 0) {
            message('Нельзя удалить здание: сначала удалите все этажи этого здания', 'danger');
        } else {
            $stmt = $pdo->prepare("DELETE FROM buildings WHERE building_id = ?");
            $stmt->execute([$building_id]);
            message('Здание удалено', 'danger');
        }
    } catch (PDOException $e) {
        message('Ошибка удаления: ' . $e->getMessage(), 'danger');
    }
    header('Location: buildings.php');
    exit;
}

$list = $pdo->query("SELECT * FROM buildings ORDER BY building_id")->fetchAll();
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM buildings WHERE building_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Здания</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="header">
    <div class="navbar">
        <div class="logo">
            🏢 <span>Бизнес-центр</span>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-link">Главная</a>
            <a href="buildings.php" class="nav-link active">Здания</a>
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
    <h2>Управление зданиями</h2>
    <?php show_msg(); ?>
    
    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать здание</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['building_id']; ?>">
            <input type="text" name="address" value="<?php echo htmlspecialchars($edit_item['address']); ?>" required>
            <input type="text" name="city" value="<?php echo htmlspecialchars($edit_item['city']); ?>" required>
            <input type="number" name="total_floors" value="<?php echo $edit_item['total_floors']; ?>" required>
            <button type="submit" name="edit">💾 Сохранить</button>
            <a href="buildings.php" class="btn">Отмена</a>
        </form>
    <?php else: ?>
        <h3>➕ Добавить здание</h3>
        <form method="POST">
            <input type="text" name="address" placeholder="Адрес" required>
            <input type="text" name="city" placeholder="Город" required>
            <input type="number" name="total_floors" placeholder="Кол-во этажей" required>
            <button type="submit" name="add">➕ Добавить</button>
        </form>
    <?php endif; ?>
    
    <h3>Список зданий</h3>
    <?php if (count($list) == 0): ?>
        <div class="alert alert-warning">Нет добавленных зданий. Добавьте первое здание через форму выше.</div>
    <?php else: ?>
    <table border="1" cellpadding="8" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr><th>ID</th><th>Адрес</th><th>Город</th><th>Этажей</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($list as $b): ?>
            <tr>
                <td><?php echo $b['building_id']; ?></td>
                <td><?php echo htmlspecialchars($b['address']); ?></td>
                <td><?php echo htmlspecialchars($b['city']); ?></td>
                <td><?php echo $b['total_floors']; ?></td>
                <td>
                    <a href="?edit=<?php echo $b['building_id']; ?>" class="btn">✏️ Редакт.</a>
                    <a href="?del=<?php echo $b['building_id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить здание? Все этажи и помещения будут также удалены!')">🗑️ Удалить</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
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
