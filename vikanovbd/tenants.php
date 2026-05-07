<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO tenants (company_name, contact_person, phone, email, is_active) VALUES (?, ?, ?, ?, 1)");
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
<html>
<head><title>Арендаторы</title><link rel="stylesheet" href="style.css"></head>
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
            <a href="rooms.php" class="nav-link">Помещения</a>
            <a href="tenants.php" class="nav-link active">Арендаторы</a>
            <a href="contracts.php" class="nav-link">Договоры</a>
            <a href="payments.php" class="nav-link">Платежи</a>
            <a href="#" class="nav-link logout-btn" onclick="showLogoutModal(event)">Выход</a>
        </div>
    </div>
</div>
<div class="container">
    <h2>Управление арендаторами</h2>
    <?php show_msg(); ?>
    
    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать арендатора</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['tenant_id']; ?>">
            <input type="text" name="company_name" value="<?php echo htmlspecialchars($edit_item['company_name']); ?>" required>
            <input type="text" name="contact_person" value="<?php echo htmlspecialchars($edit_item['contact_person']); ?>" placeholder="Контактное лицо">
            <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_item['phone']); ?>" placeholder="Телефон">
            <input type="email" name="email" value="<?php echo htmlspecialchars($edit_item['email']); ?>" placeholder="Email">
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
        <thead>
            <tr><th>ID</th><th>Компания</th><th>Контакт</th><th>Телефон</th><th>Email</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($list as $t): ?>
            <tr>
                <td><?php echo $t['tenant_id']; ?></td>
                <td><?php echo htmlspecialchars($t['company_name']); ?></td>
                <td><?php echo htmlspecialchars($t['contact_person']); ?></td>
                <td><?php echo htmlspecialchars($t['phone']); ?></td>
                <td><?php echo htmlspecialchars($t['email']); ?></td>
                <td>
                    <a href="?edit=<?php echo $t['tenant_id']; ?>" class="btn">✏️ Редакт.</a>
                    <a href="?del=<?php echo $t['tenant_id']; ?>" class="btn btn-danger" onclick="return confirm('Удалить арендатора?')">🗑️ Удалить</a>
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
