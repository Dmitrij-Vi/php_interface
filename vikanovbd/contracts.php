<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO contracts (contract_number, tenant_id, room_id, start_date, end_date, rent_amount, payment_day, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$_POST['contract_number'], $_POST['tenant_id'], $_POST['room_id'], $_POST['start_date'], $_POST['end_date'], $_POST['rent_amount'], $_POST['payment_day'], $_POST['status']]);
    message('Договор добавлен');
    header('Location: contracts.php');
    exit;
}
// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $stmt = $pdo->prepare("UPDATE contracts SET contract_number=?, tenant_id=?, room_id=?, start_date=?, end_date=?, rent_amount=?, payment_day=?, status=? WHERE contract_id=?");
    $stmt->execute([$_POST['contract_number'], $_POST['tenant_id'], $_POST['room_id'], $_POST['start_date'], $_POST['end_date'], $_POST['rent_amount'], $_POST['payment_day'], $_POST['status'], $_POST['id']]);
    message('Договор обновлён');
    header('Location: contracts.php');
    exit;
}
// Удаление
if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM contracts WHERE contract_id=?")->execute([$_GET['del']]);
        message('Договор удалён');
    } catch (PDOException $e) {
        message('Нельзя удалить: есть платежи', 'danger');
    }
    header('Location: contracts.php');
    exit;
}

// Список договоров
$contracts = $pdo->query("SELECT c.*, t.company_name, r.room_number, b.address 
                          FROM contracts c 
                          JOIN tenants t USING(tenant_id) 
                          JOIN rooms r USING(room_id) 
                          JOIN floors f USING(floor_id) 
                          JOIN buildings b USING(building_id) 
                          ORDER BY c.contract_id DESC")->fetchAll();

// Список арендаторов для выпадающего списка
$tenants = $pdo->query("SELECT tenant_id, company_name FROM tenants ORDER BY company_name")->fetchAll();

// Список помещений (с адресом) для выпадающего списка
$rooms_list = $pdo->query("SELECT room_id, CONCAT(b.address, ' пом.', r.room_number) as name 
                           FROM rooms r 
                           JOIN floors f USING(floor_id) 
                           JOIN buildings b USING(building_id)
                           ORDER BY b.address, r.room_number")->fetchAll();

$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM contracts WHERE contract_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html><head><title>Договоры</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="menu">🏢 <a href="index.php">Главная</a> | <a href="buildings.php">Здания</a> | <a href="floors.php">Этажи</a> | <a href="rooms.php">Помещения</a> | <a href="tenants.php">Арендаторы</a> | <a href="contracts.php">Договоры</a> | <a href="payments.php">Платежи</a> | <a href="logout.php">Выход</a></div>
<div class="container">
    <h2>Договоры аренды</h2>
    <?php show_msg(); ?>

    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать договор</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['contract_id']; ?>">
            <input type="text" name="contract_number" value="<?php echo htmlspecialchars($edit_item['contract_number']); ?>" required>
            <select name="tenant_id" required>
                <?php foreach ($tenants as $t): ?>
                    <option value="<?php echo $t['tenant_id']; ?>" <?php echo $t['tenant_id'] == $edit_item['tenant_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($t['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="room_id" required>
                <?php foreach ($rooms_list as $r): ?>
                    <option value="<?php echo $r['room_id']; ?>" <?php echo $r['room_id'] == $edit_item['room_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="start_date" value="<?php echo $edit_item['start_date']; ?>" required>
            <input type="date" name="end_date" value="<?php echo $edit_item['end_date']; ?>" required>
            <input type="number" step="0.01" name="rent_amount" value="<?php echo $edit_item['rent_amount']; ?>" required>
            <input type="number" name="payment_day" value="<?php echo $edit_item['payment_day']; ?>" required>
            <select name="status">
                <option value="active" <?php echo $edit_item['status'] == 'active' ? 'selected' : ''; ?>>Активен</option>
                <option value="expired" <?php echo $edit_item['status'] == 'expired' ? 'selected' : ''; ?>>Истёк</option>
                <option value="terminated" <?php echo $edit_item['status'] == 'terminated' ? 'selected' : ''; ?>>Расторгнут</option>
            </select>
            <button type="submit" name="edit">💾 Сохранить</button>
            <a href="contracts.php" class="btn">Отмена</a>
        </form>
    <?php else: ?>
        <h3>Добавление договоров</h3>
        <?php if (count($tenants) == 0): ?>
            <div class="alert alert-danger">Нет арендаторов. Сначала добавьте арендаторов.</div>
        <?php elseif (count($rooms_list) == 0): ?>
            <div class="alert alert-danger">Нет помещений. Сначала добавьте помещения.</div>
        <?php else: ?>
        <form method="POST">
            <input type="text" name="contract_number" placeholder="Номер договора" required>
            <select name="tenant_id" required>
                <option value="">-- Выберите арендатора --</option>
                <?php foreach ($tenants as $t): ?>
                    <option value="<?php echo $t['tenant_id']; ?>"><?php echo htmlspecialchars($t['company_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="room_id" required>
                <option value="">-- Выберите помещение --</option>
                <?php foreach ($rooms_list as $r): ?>
                    <option value="<?php echo $r['room_id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="start_date" required>
            <input type="date" name="end_date" required>
            <input type="number" step="0.01" name="rent_amount" placeholder="Сумма аренды" required>
            <input type="number" name="payment_day" placeholder="День платежа (1-31)" required>
            <select name="status">
                <option value="active">Активен</option>
                <option value="expired">Истёк</option>
                <option value="terminated">Расторгнут</option>
            </select>
            <button type="submit" name="add">➕ Добавить договор</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>

    <h3>Список договоров</h3>
    <table border="1" cellpadding="8">
        <tr><th>№ договора</th><th>Арендатор</th><th>Помещение</th><th>Период</th><th>Сумма</th><th>Статус</th><th>Действия</th></tr>
        <?php foreach ($contracts as $c): ?>
        <tr>
            <td><?php echo htmlspecialchars($c['contract_number']); ?></td>
            <td><?php echo htmlspecialchars($c['company_name']); ?></td>
            <td><?php echo htmlspecialchars($c['address']) . ' пом.' . htmlspecialchars($c['room_number']); ?></td>
            <td><?php echo $c['start_date'] . ' – ' . $c['end_date']; ?></td>
            <td><?php echo number_format($c['rent_amount'], 0); ?> ₽</td>
            <td><?php echo $c['status']; ?></td>
            <td>
                <a class="btn" href="?edit=<?php echo $c['contract_id']; ?>">✏️ Редакт.</a>
                <a class="btn btn-danger" href="?del=<?php echo $c['contract_id']; ?>" onclick="return confirm('Удалить договор?')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>