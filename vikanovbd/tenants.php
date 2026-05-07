<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO tenants (company_name, contact_person, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['company_name'], $_POST['contact_person'], $_POST['phone'], $_POST['email']]);
    message('Арендатор добавлен');
    header('Location: tenants.php');
    exit;
}
// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE tenants SET company_name=?, contact_person=?, phone=?, email=? WHERE tenant_id=?");
    $stmt->execute([$_POST['company_name'], $_POST['contact_person'], $_POST['phone'], $_POST['email'], $_POST['id']]);
    message('Арендатор обновлён');
    header('Location: tenants.php');
    exit;
}
// Удаление
if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM tenants WHERE tenant_id=?")->execute([$_GET['del']]);
        message('Арендатор удалён');
    } catch (PDOException $e) {
        message('Нельзя удалить: есть договоры', 'danger');
    }
    header('Location: tenants.php');
    exit;
}

$list = $pdo->query("SELECT * FROM tenants ORDER BY tenant_id DESC")->fetchAll();
$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tenants WHERE tenant_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html><head><title>Арендаторы</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="menu">🏢 <a href="index.php">Главная</a> | <a href="buildings.php">Здания</a> | <a href="rooms.php">Помещения</a> | <a href="tenants.php">Арендаторы</a> | <a href="contracts.php">Договоры</a> | <a href="payments.php">Платежи</a> | <a href="logout.php">Выход</a></div>
<div class="container">
    <h2>Арендаторы</h2>
    <?php show_msg(); ?>

    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать арендатора</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['tenant_id']; ?>">
            <input type="text" name="company_name" value="<?php echo htmlspecialchars($edit_item['company_name']); ?>" required>
            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($edit_item['contact_person']); ?>">
            <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_item['phone']); ?>">
            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_item['email']); ?>">
            <button type="submit" name="edit">💾 Сохранить</button>
            <a href="tenants.php" class="btn">Отмена</a>
        </form>
    <?php else: ?>
        <h3>Добавление арендаторов</h3>
        <form method="POST">
            <input type="text" name="company_name" placeholder="Название компании" required>
            <input type="text" name="contact_person" placeholder="Контактное лицо">
            <input type="text" name="phone" placeholder="Телефон">
            <input type="email" name="email" placeholder="Email">
            <button type="submit" name="add">➕ Добавить</button>
        </form>
    <?php endif; ?>

    <h3>Список арендаторов</h3>
    <table>
        <tr><th>ID</th><th>Компания</th><th>Контакт</th><th>Телефон</th><th>Email</th><th>Действия</th></tr>
        <?php foreach ($list as $t): ?>
        <tr>
            <td><?php echo $t['tenant_id']; ?></td>
            <td><?php echo htmlspecialchars($t['company_name']); ?></td>
            <td><?php echo htmlspecialchars($t['contact_person']); ?></td>
            <td><?php echo htmlspecialchars($t['phone']); ?></td>
            <td><?php echo htmlspecialchars($t['email']); ?></td>
            <td>
                <a class="btn" href="?edit=<?php echo $t['tenant_id']; ?>">✏️ Редакт.</a>
                <a class="btn btn-danger" href="?del=<?php echo $t['tenant_id']; ?>" onclick="return confirm('Удалить арендатора?')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>