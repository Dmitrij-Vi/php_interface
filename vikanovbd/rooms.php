<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO rooms (floor_id, room_number, area_sqm, room_type, monthly_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['floor_id'], $_POST['room_number'], $_POST['area_sqm'], $_POST['room_type'], $_POST['monthly_rate']]);
    message('Помещение добавлено');
    header('Location: rooms.php');
    exit;
}
// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE rooms SET floor_id=?, room_number=?, area_sqm=?, room_type=?, monthly_rate=? WHERE room_id=?");
    $stmt->execute([$_POST['floor_id'], $_POST['room_number'], $_POST['area_sqm'], $_POST['room_type'], $_POST['monthly_rate'], $_POST['id']]);
    message('Помещение обновлено');
    header('Location: rooms.php');
    exit;
}
// Удаление
if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM rooms WHERE room_id=?")->execute([$_GET['del']]);
        message('Помещение удалено');
    } catch (PDOException $e) {
        message('Нельзя удалить: есть договоры', 'danger');
    }
    header('Location: rooms.php');
    exit;
}

// Исправленный SQL запрос - убираем USING и делаем явные JOIN
$rooms = $pdo->query("
    SELECT 
        r.room_id,
        r.room_number,
        r.area_sqm,
        r.room_type,
        r.monthly_rate,
        f.floor_number,
        b.address,
        b.city
    FROM rooms r 
    JOIN floors f ON r.floor_id = f.floor_id
    JOIN buildings b ON f.building_id = b.building_id 
    ORDER BY b.address, f.floor_number, r.room_number
")->fetchAll();

// Получаем список этажей для выпадающего списка
$floors = $pdo->query("
    SELECT 
        f.floor_id, 
        CONCAT(b.address, ' (', b.city, '), этаж ', f.floor_number) as name 
    FROM floors f 
    JOIN buildings b ON f.building_id = b.building_id
    ORDER BY b.address, f.floor_number
")->fetchAll();

$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Помещения</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="header">
    <div class="navbar">
        <div class="logo">
            🏢 <span>Бизнес-центр</span>
        </div>
        <div class="nav-menu">
            <a href="index.php" class="nav-link">Главная</a>
            <a href="buildings.php" class="nav-link">Здания</a>
            <a href="floors.php" class="nav-link">Этажи</a>
            <a href="rooms.php" class="nav-link active">Помещения</a>
            <a href="tenants.php" class="nav-link">Арендаторы</a>
            <a href="contracts.php" class="nav-link">Договоры</a>
            <a href="payments.php" class="nav-link">Платежи</a>
            <a href="#" class="nav-link logout-btn" onclick="showLogoutModal(event)">Выход</a>
        </div>
    </div>
</div>
<div class="container">
    <h2>Управление помещениями</h2>
    <?php show_msg(); ?>
    
    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать помещение</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['room_id']; ?>">
            <select name="floor_id" required>
                <?php foreach ($floors as $f): ?>
                    <option value="<?php echo $f['floor_id']; ?>" <?php echo $f['floor_id'] == $edit_item['floor_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="room_number" value="<?php echo htmlspecialchars($edit_item['room_number']); ?>" required>
            <input type="number" step="0.01" name="area_sqm" value="<?php echo $edit_item['area_sqm']; ?>" required>
            <select name="room_type">
                <option value="office" <?php echo $edit_item['room_type'] == 'office' ? 'selected' : ''; ?>>Офис</option>
                <option value="retail" <?php echo $edit_item['room_type'] == 'retail' ? 'selected' : ''; ?>>Торговое</option>
                <option value="storage" <?php echo $edit_item['room_type'] == 'storage' ? 'selected' : ''; ?>>Склад</option>
            </select>
            <input type="number" step="0.01" name="monthly_rate" value="<?php echo $edit_item['monthly_rate']; ?>" required>
            <button type="submit" name="edit">💾 Сохранить</button>
            <a href="rooms.php" class="btn">Отмена</a>
        </form>
    <?php else: ?>
        <h3>Добавление помещений</h3>
        <?php if (count($floors) == 0): ?>
            <div class="alert alert-danger">Сначала добавьте этажи в разделе "Этажи".</div>
        <?php else: ?>
        <form method="POST">
            <select name="floor_id" required>
                <option value="">-- Выберите этаж --</option>
                <?php foreach ($floors as $f): ?>
                    <option value="<?php echo $f['floor_id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="room_number" placeholder="Номер помещения" required>
            <input type="number" step="0.01" name="area_sqm" placeholder="Площадь (м²)" required>
            <select name="room_type">
                <option value="office">Офис</option>
                <option value="retail">Торговое</option>
                <option value="storage">Склад</option>
            </select>
            <input type="number" step="0.01" name="monthly_rate" placeholder="Ставка (₽/мес)" required>
            <button type="submit" name="add">➕ Добавить</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>
    
    <h3>Список помещений</h3>
    <?php if (count($rooms) == 0): ?>
        <div class="alert alert-warning">Нет добавленных помещений. Добавьте первое помещение через форму выше.</div>
    <?php else: ?>
    <table border="1" cellpadding="8" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Адрес</th>
                <th>Город</th>
                <th>Этаж</th>
                <th>№ помещения</th>
                <th>Площадь</th>
                <th>Тип</th>
                <th>Ставка (₽/мес)</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $r): ?>
            <tr>
                <td><?php echo $r['room_id']; ?></td>
                <td><?php echo htmlspecialchars($r['address']); ?></td>
                <td><?php echo htmlspecialchars($r['city']); ?></td>
                <td><?php echo $r['floor_number']; ?></td>
                <td><?php echo htmlspecialchars($r['room_number']); ?></td>
                <td><?php echo $r['area_sqm']; ?> м²</td>
                <td>
                    <?php 
                    $type_labels = [
                        'office' => '🏢 Офис',
                        'retail' => '🛍️ Торговое',
                        'storage' => '📦 Склад',
                        'conference' => '📊 Конференц-зал',
                        'other' => '📌 Другое'
                    ];
                    echo $type_labels[$r['room_type']] ?? $r['room_type'];
                    ?>
                </td>
                <td><?php echo number_format($r['monthly_rate'], 0, ',', ' '); ?> ₽</td>
                <td>
                    <a href="?edit=<?php echo $r['room_id']; ?>" class="btn">✏️ Редакт.</a>
                    <a href="?del=<?php echo $r['room_id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить помещение?')">🗑️ Удалить</a>
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
