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
// Удаление
if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM buildings WHERE building_id=?")->execute([$_GET['del']]);
        message('Здание удалено');
    } catch (PDOException $e) {
        message('Нельзя удалить: есть связанные этажи', 'danger');
    }
    header('Location: buildings.php');
    exit;
}

$list = $pdo->query("SELECT * FROM buildings ORDER BY building_id")->fetchAll();

// Данные для редактирования
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM buildings WHERE building_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html><head><title>Здания</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="menu">🏢 <a href="index.php">Главная</a> | <a href="buildings.php">Здания</a> | <a href="floors.php">Этажи</a> | <a href="rooms.php">Помещения</a> | <a href="tenants.php">Арендаторы</a> | <a href="contracts.php">Договоры</a> | <a href="payments.php">Платежи</a> | <a href="logout.php">Выход</a></div>
<div class="container">
    <h2>Здания</h2>
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
        <h3>Добавление зданий</h3>
        <form method="POST">
            <input type="text" name="address" placeholder="Адрес" required>
            <input type="text" name="city" placeholder="Город" required>
            <input type="number" name="total_floors" placeholder="Кол-во этажей" required>
            <button type="submit" name="add">➕ Добавить</button>
        </form>
    <?php endif; ?>

    <h3>Список зданий</h3>
    <table>
        <tr><th>ID</th><th>Адрес</th><th>Город</th><th>Этажей</th><th>Действия</th></tr>
        <?php foreach ($list as $b): ?>
        <tr>
            <td><?php echo $b['building_id']; ?></td>
            <td><?php echo htmlspecialchars($b['address']); ?></td>
            <td><?php echo htmlspecialchars($b['city']); ?></td>
            <td><?php echo $b['total_floors']; ?></td>
            <td>
                <a class="btn" href="?edit=<?php echo $b['building_id']; ?>">✏️ Редакт.</a>
                <a class="btn btn-danger" href="?del=<?php echo $b['building_id']; ?>" onclick="return confirm('Удалить здание?')">🗑️ Удалить</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>