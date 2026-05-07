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

// Список помещений с адресами
$rooms = $pdo->query("SELECT r.*, f.floor_number, b.address 
                      FROM rooms r 
                      JOIN floors f USING(floor_id) 
                      JOIN buildings b USING(building_id) 
                      ORDER BY r.room_id")->fetchAll();

// Список этажей для выпадающего списка (объединяем с адресом здания)
$floors = $pdo->query("SELECT floor_id, CONCAT(b.address, ' этаж ', f.floor_number) as name 
                       FROM floors f 
                       JOIN buildings b USING(building_id)
                       ORDER BY b.address, f.floor_number")->fetchAll();

// Данные для редактирования
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html><head><title>Помещения</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="menu">🏢 <a href="index.php">Главная</a> | <a href="buildings.php">Здания</a> | <a href="floors.php">Этажи</a> | <a href="rooms.php">Помещения</a> | <a href="tenants.php">Арендаторы</a> | <a href="contracts.php">Договоры</a> | <a href="payments.php">Платежи</a> | <a href="logout.php">Выход</a></div>
<div class="container">
    <h2>Помещения</h2>
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
            <div class="alert alert-danger">Сначала добавьте этажи в разделе "Здания" (через промежуточную таблицу floors).</div>
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
    <table border="1" cellpadding="8">
        <tr><th>ID</th><th>Адрес</th><th>Этаж</th><th>№ помещения</th><th>Площадь</th><th>Ставка</th><th>Действия</th></tr>
        <?php foreach ($rooms as $r): ?>
        <tr>
            <td><?php echo $r['room_id']; ?></td>
            <td><?php echo htmlspecialchars($r['address']); ?></td>
            <td><?php echo $r['floor_number']; ?></td>
            <td><?php echo htmlspecialchars($r['room_number']); ?></td>
            <td><?php echo $r['area_sqm']; ?> м²</td>
            <td><?php echo number_format($r['monthly_rate'], 0); ?> ₽</td>
            <td>
                <a class="btn" href="?edit=<?php echo $r['room_id']; ?>">✏️ Редакт.</a>
                <a class="btn btn-danger" href="?del=<?php echo $r['room_id']; ?>" onclick="return confirm('Удалить помещение?')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>