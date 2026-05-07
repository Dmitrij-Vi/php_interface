<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO floors (building_id, floor_number, area_sqm) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['building_id'], $_POST['floor_number'], $_POST['area_sqm']]);
    message('Этаж добавлен');
    header('Location: floors.php');
    exit;
}
// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE floors SET building_id=?, floor_number=?, area_sqm=? WHERE floor_id=?");
    $stmt->execute([$_POST['building_id'], $_POST['floor_number'], $_POST['area_sqm'], $_POST['id']]);
    message('Этаж обновлён');
    header('Location: floors.php');
    exit;
}
// Удаление
if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM floors WHERE floor_id=?")->execute([$_GET['del']]);
        message('Этаж удалён');
    } catch (PDOException $e) {
        message('Нельзя удалить: есть помещения', 'danger');
    }
    header('Location: floors.php');
    exit;
}

$floors = $pdo->query("SELECT f.*, b.address 
                       FROM floors f 
                       JOIN buildings b USING(building_id) 
                       ORDER BY b.address, f.floor_number")->fetchAll();
$buildings = $pdo->query("SELECT building_id, address FROM buildings ORDER BY address")->fetchAll();

$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM floors WHERE floor_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Этажи</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="header">
    <div class="navbar">
        <div class="logo">
            🏢 <span>Бизнес-центр</span>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-link">Главная</a>
            <a href="buildings.php" class="nav-link">Здания</a>
            <a href="floors.php" class="nav-link active">Этажи</a>
            <a href="rooms.php" class="nav-link">Помещения</a>
            <a href="tenants.php" class="nav-link">Арендаторы</a>
            <a href="contracts.php" class="nav-link">Договоры</a>
            <a href="payments.php" class="nav-link">Платежи</a>
            <a href="#" class="nav-link logout-btn" onclick="showLogoutModal(event)">Выход</a>
        </div>
    </div>
</div>
<div class="container">
    <h2>Управление этажами</h2>
    <?php show_msg(); ?>
    
    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать этаж</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['floor_id']; ?>">
            <select name="building_id" required>
                <?php foreach ($buildings as $b): ?>
                    <option value="<?php echo $b['building_id']; ?>" <?php echo $b['building_id'] == $edit_item['building_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['address']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="floor_number" value="<?php echo $edit_item['floor_number']; ?>" placeholder="Номер этажа" required>
            <input type="number" step="0.01" name="area_sqm" value="<?php echo $edit_item['area_sqm']; ?>" placeholder="Площадь" required>
            <button type="submit" name="edit">💾 Сохранить</button>
            <a href="floors.php" class="btn">Отмена</a>
        </form>
    <?php else: ?>
        <h3>Добавление этажей</h3>
        <?php if (count($buildings) == 0): ?>
            <div class="alert alert-danger">Сначала добавьте здание в разделе "Здания".</div>
        <?php else: ?>
        <form method="POST">
            <select name="building_id" required>
                <option value="">-- Выберите здание --</option>
                <?php foreach ($buildings as $b): ?>
                    <option value="<?php echo $b['building_id']; ?>"><?php echo htmlspecialchars($b['address']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="floor_number" placeholder="Номер этажа" required>
            <input type="number" step="0.01" name="area_sqm" placeholder="Площадь этажа (м²)" required>
            <button type="submit" name="add">➕ Добавить</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
    
    <h3>Список этажей</h3>
    <table>
        <thead>
            <tr><th>ID</th><th>Здание</th><th>Этаж</th><th>Площадь (м²)</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($floors as $f): ?>
            <tr>
                <td><?php echo $f['floor_id']; ?></td>
                <td><?php echo htmlspecialchars($f['address']); ?></td>
                <td><?php echo $f['floor_number']; ?></td>
                <td><?php echo $f['area_sqm']; ?></td>
                <td>
                    <a href="?edit=<?php echo $f['floor_id']; ?>" class="btn">✏️ Редакт.</a>
                    <a href="?del=<?php echo $f['floor_id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить этаж?')">🗑️ Удалить</a>
                </td>
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
