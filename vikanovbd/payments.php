<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['auth'])) { header('Location: login.php'); exit; }
require 'config.php';

// Добавление платежа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $required = ['contract_id', 'payment_date', 'amount', 'period_start', 'period_end', 'payment_method'];
    $errors = [];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Поле '$field' обязательно";
        }
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (contract_id, payment_date, amount, payment_period_start, payment_period_end, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['contract_id'],
                $_POST['payment_date'],
                $_POST['amount'],
                $_POST['period_start'],
                $_POST['period_end'],
                $_POST['payment_method']
            ]);
            message('Платеж успешно добавлен');
            header('Location: payments.php');
            exit;
        } catch (PDOException $e) {
            message('Ошибка БД: ' . $e->getMessage(), 'danger');
        }
    } else {
        message(implode(', ', $errors), 'danger');
    }
}

// Редактирование
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    try {
        $stmt = $pdo->prepare("UPDATE payments SET contract_id=?, payment_date=?, amount=?, payment_period_start=?, payment_period_end=?, payment_method=? WHERE payment_id=?");
        $stmt->execute([
            $_POST['contract_id'],
            $_POST['payment_date'],
            $_POST['amount'],
            $_POST['period_start'],
            $_POST['period_end'],
            $_POST['payment_method'],
            $_POST['id']
        ]);
        message('Платеж обновлен');
        header('Location: payments.php');
        exit;
    } catch (PDOException $e) {
        message('Ошибка обновления: ' . $e->getMessage(), 'danger');
    }
}

// Удаление
if (isset($_GET['del'])) {
    try {
        $pdo->prepare("DELETE FROM payments WHERE payment_id=?")->execute([$_GET['del']]);
        message('Платеж удален', 'danger');
    } catch (PDOException $e) {
        message('Ошибка удаления: ' . $e->getMessage(), 'danger');
    }
    header('Location: payments.php');
    exit;
}

// Список платежей
$payments = $pdo->query("
    SELECT p.*, c.contract_number, t.company_name 
    FROM payments p 
    JOIN contracts c ON p.contract_id = c.contract_id 
    JOIN tenants t ON c.tenant_id = t.tenant_id 
    ORDER BY p.payment_date DESC
")->fetchAll();

// Список договоров для выпадающего списка (показываем все, можно отфильтровать по активным)
$contracts = $pdo->query("SELECT contract_id, contract_number FROM contracts ORDER BY contract_number")->fetchAll();

$edit_item = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_id=?");
    $stmt->execute([$_GET['edit']]);
    $edit_item = $stmt->fetch();
    if (!$edit_item) {
        message('Платеж не найден', 'danger');
        header('Location: payments.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html><head><title>Платежи</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="menu">🏢 <a href="index.php">Главная</a> | <a href="buildings.php">Здания</a> | <a href="floors.php">Этажи</a> | <a href="rooms.php">Помещения</a> | <a href="tenants.php">Арендаторы</a> | <a href="contracts.php">Договоры</a> | <a href="payments.php">Платежи</a> | <a href="logout.php">Выход</a></div>
<div class="container">
    <h2>Платежи по договорам</h2>
    <?php show_msg(); ?>

    <?php if ($edit_item): ?>
        <h3>✏️ Редактировать платеж</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $edit_item['payment_id']; ?>">
            <select name="contract_id" required>
                <?php foreach ($contracts as $c): ?>
                    <option value="<?php echo $c['contract_id']; ?>" <?php echo $c['contract_id'] == $edit_item['contract_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['contract_number']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="payment_date" value="<?php echo $edit_item['payment_date']; ?>" required>
            <input type="number" step="0.01" name="amount" value="<?php echo $edit_item['amount']; ?>" required>
            <input type="date" name="period_start" value="<?php echo $edit_item['payment_period_start']; ?>" required>
            <input type="date" name="period_end" value="<?php echo $edit_item['payment_period_end']; ?>" required>
            <select name="payment_method" required>
                <option value="bank_transfer" <?php echo $edit_item['payment_method'] == 'bank_transfer' ? 'selected' : ''; ?>>Безналичный</option>
                <option value="cash" <?php echo $edit_item['payment_method'] == 'cash' ? 'selected' : ''; ?>>Наличные</option>
                <option value="card" <?php echo $edit_item['payment_method'] == 'card' ? 'selected' : ''; ?>>Карта</option>
            </select>
            <button type="submit" name="edit">💾 Сохранить</button>
            <a href="payments.php" class="btn">Отмена</a>
        </form>
    <?php else: ?>
        <h3>Добавление платежей</h3>
        <?php if (count($contracts) == 0): ?>
            <div class="alert alert-danger">Нет договоров. Сначала добавьте договоры аренды.</div>
        <?php else: ?>
        <form method="POST">
            <select name="contract_id" required>
                <option value="">-- Выберите договор --</option>
                <?php foreach ($contracts as $c): ?>
                    <option value="<?php echo $c['contract_id']; ?>"><?php echo htmlspecialchars($c['contract_number']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="payment_date" required>
            <input type="number" step="0.01" name="amount" placeholder="Сумма" required>
            <input type="date" name="period_start" required>
            <input type="date" name="period_end" required>
            <select name="payment_method" required>
                <option value="">-- Способ оплаты --</option>
                <option value="bank_transfer">Безналичный</option>
                <option value="cash">Наличные</option>
                <option value="card">Карта</option>
            </select>
            <button type="submit" name="add">➕ Добавить платеж</button>
        </form>
        <?php endif; ?>
    <?php endif; ?>

    <h3>Список платежей</h3>
    <table border="1" cellpadding="8">
        <tr><th>Договор</th><th>Арендатор</th><th>Сумма</th><th>Дата платежа</th><th>Период оплаты</th><th>Метод</th><th>Действия</th></tr>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['contract_number']); ?></td>
            <td><?php echo htmlspecialchars($p['company_name']); ?></td>
            <td><?php echo number_format($p['amount'], 2); ?> ₽</td>
            <td><?php echo htmlspecialchars($p['payment_date']); ?></td>
            <td><?php echo $p['payment_period_start'] . ' – ' . $p['payment_period_end']; ?></td>
            <td><?php echo $p['payment_method']; ?></td>
            <td>
                <a class="btn" href="?edit=<?php echo $p['payment_id']; ?>">✏️ Редакт.</a>
                <a class="btn btn-danger" href="?del=<?php echo $p['payment_id']; ?>" onclick="return confirm('Удалить платеж?')">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>