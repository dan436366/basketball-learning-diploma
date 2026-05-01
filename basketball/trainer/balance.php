<?php
/**
 * trainer/balance.php — баланс і виведення коштів тренера
 */
require_once '../config.php';
requireRole('trainer');

$db        = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

// ── Обробка запиту на виведення ───────────────────────────────
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal'])) {
    $amount     = (float)($_POST['amount'] ?? 0);
    $cardNumber = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $cardHolder = trim($_POST['card_holder'] ?? '');

    // Отримуємо поточний баланс
    $stmt = $db->prepare("SELECT available_balance FROM trainer_balances WHERE trainer_id = ?");
    $stmt->execute([$trainerId]);
    $balance = $stmt->fetchColumn() ?: 0;

    if ($amount <= 0)              $errors[] = 'Введіть суму для виведення';
    if ($amount > $balance)        $errors[] = 'Недостатньо коштів (доступно: ' . number_format($balance, 2) . ' грн)';
    if ($amount < 100)             $errors[] = 'Мінімальна сума виведення — 100 грн';
    if (strlen($cardNumber) !== 16) $errors[] = 'Введіть коректний номер картки (16 цифр)';
    if (empty($cardHolder))        $errors[] = 'Введіть ім\'я власника картки';

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Блокуємо суму
            $stmt = $db->prepare("
                UPDATE trainer_balances
                SET available_balance = available_balance - ?
                WHERE trainer_id = ? AND available_balance >= ?
            ");
            $stmt->execute([$amount, $trainerId, $amount]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Недостатньо коштів або конфлікт транзакції');
            }

            // Створюємо запит
            $stmt = $db->prepare("
                INSERT INTO withdrawals (trainer_id, amount, card_number, card_holder, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$trainerId, $amount, $cardNumber, $cardHolder]);
            $withdrawalId = $db->lastInsertId();

            // Лог
            $stmt = $db->prepare("
                INSERT INTO balance_transactions (trainer_id, type, amount, description, withdrawal_id)
                VALUES (?, 'debit', ?, ?, ?)
            ");
            $stmt->execute([$trainerId, $amount, 'Запит на виведення коштів', $withdrawalId]);

            $db->commit();
            $success = 'Запит на виведення ' . number_format($amount, 2) . ' грн успішно створено! Адмін обробить його протягом 1-3 робочих днів.';

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Помилка: ' . $e->getMessage();
        }
    }
}

// ── Дані для відображення ─────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM trainer_balances WHERE trainer_id = ?");
$stmt->execute([$trainerId]);
$balanceData = $stmt->fetch() ?: ['total_earned' => 0, 'available_balance' => 0, 'withdrawn_total' => 0];

// Транзакції
$stmt = $db->prepare("
    SELECT * FROM balance_transactions WHERE trainer_id = ?
    ORDER BY created_at DESC LIMIT 20
");
$stmt->execute([$trainerId]);
$transactions = $stmt->fetchAll();

// Запити на виведення
$stmt = $db->prepare("
    SELECT * FROM withdrawals WHERE trainer_id = ?
    ORDER BY created_at DESC LIMIT 10
");
$stmt->execute([$trainerId]);
$withdrawals = $stmt->fetchAll();

$pageTitle = 'Мій баланс';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    .page-header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 6px; }

    .balance-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }
    .balance-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,.08);
        text-align: center;
    }
    .balance-card .b-icon { font-size: 2.5rem; margin-bottom: 12px; }
    .balance-card .b-amount { font-size: 2rem; font-weight: 700; color: #333; margin-bottom: 6px; }
    .balance-card .b-label { color: #666; font-size: .9rem; }
    .balance-card.available .b-amount { color: #11998e; }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 30px;
        margin-bottom: 60px;
        align-items: start;
    }

    .section-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,.08);
        margin-bottom: 24px;
    }
    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
    }

    /* Форма виведення */
    .form-group { margin-bottom: 18px; }
    .form-label { display: block; margin-bottom: 7px; font-weight: 600; color: #333; }
    .form-input {
        width: 100%;
        padding: 11px 14px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all .3s;
        box-sizing: border-box;
    }
    .form-input:focus { border-color: #11998e; outline: none; box-shadow: 0 0 0 3px rgba(17,153,142,.1); }
    .form-help { font-size: .85rem; color: #666; margin-top: 5px; }

    .btn-withdraw {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all .3s;
    }
    .btn-withdraw:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(17,153,142,.4); }

    /* Транзакції */
    .tx-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    .tx-item:last-child { border-bottom: none; }
    .tx-info { flex: 1; }
    .tx-desc { font-size: .9rem; color: #333; margin-bottom: 3px; }
    .tx-date { font-size: .8rem; color: #999; }
    .tx-amount { font-size: 1.1rem; font-weight: 700; white-space: nowrap; margin-left: 12px; }
    .tx-amount.credit { color: #11998e; }
    .tx-amount.debit  { color: #dc3545; }

    /* Статуси виведення */
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: .8rem;
        font-weight: 600;
    }
    .badge-pending  { background: #fff3cd; color: #856404; }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-paid     { background: #d1ecf1; color: #0c5460; }
    .badge-rejected { background: #f8d7da; color: #721c24; }

    .withdrawal-item {
        padding: 14px;
        border: 2px solid #f0f0f0;
        border-radius: 10px;
        margin-bottom: 12px;
    }
    .withdrawal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .withdrawal-amount { font-size: 1.2rem; font-weight: 700; color: #333; }
    .withdrawal-meta { font-size: .85rem; color: #666; }

    .error-list {
        background: #f8d7da; border-left: 4px solid #dc3545;
        padding: 12px 16px; border-radius: 5px; margin-bottom: 16px;
    }
    .error-list ul { margin: 0; padding-left: 18px; color: #721c24; font-size: .9rem; }
    .success-msg {
        background: #d4edda; border-left: 4px solid #28a745;
        padding: 12px 16px; border-radius: 5px; margin-bottom: 16px;
        color: #155724; font-size: .9rem;
    }

    .min-note {
        background: #f0f4ff;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: .85rem;
        color: #667eea;
        margin-bottom: 18px;
    }

    @media (max-width: 991px) {
        .balance-grid { grid-template-columns: 1fr; gap: 12px; }
        .content-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .page-header { padding: 22px 0; margin-bottom: 20px; }
        .page-header h1 { font-size: 1.4rem; }
        .section-card { padding: 16px; }
        .balance-card { padding: 18px; }
        .balance-card .b-amount { font-size: 1.5rem; }
    }
</style>

<section class="page-header">
    <div class="container">
        <h1>💰 Мій баланс</h1>
        <p>Ваші заробітки та виведення коштів</p>
    </div>
</section>

<div class="container">

    <!-- Картки балансу -->
    <div class="balance-grid">
        <div class="balance-card">
            <div class="b-icon">💵</div>
            <div class="b-amount"><?= number_format($balanceData['total_earned'], 2) ?> грн</div>
            <div class="b-label">Загальний заробіток</div>
        </div>
        <div class="balance-card available">
            <div class="b-icon">✅</div>
            <div class="b-amount"><?= number_format($balanceData['available_balance'], 2) ?> грн</div>
            <div class="b-label">Доступно для виведення</div>
        </div>
        <div class="balance-card">
            <div class="b-icon">🏦</div>
            <div class="b-amount"><?= number_format($balanceData['withdrawn_total'], 2) ?> грн</div>
            <div class="b-label">Вже виведено</div>
        </div>
    </div>

    <div class="content-grid">

        <!-- Ліва: транзакції + запити -->
        <div>
            <!-- Транзакції -->
            <div class="section-card">
                <h2 class="section-title">📋 Останні транзакції</h2>
                <?php if (empty($transactions)): ?>
                    <p style="color:#666;text-align:center;padding:20px 0;">Транзакцій ще немає</p>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                    <div class="tx-item">
                        <div class="tx-info">
                            <div class="tx-desc"><?= htmlspecialchars($tx['description']) ?></div>
                            <div class="tx-date"><?= date('d.m.Y H:i', strtotime($tx['created_at'])) ?></div>
                        </div>
                        <div class="tx-amount <?= $tx['type'] ?>">
                            <?= $tx['type'] === 'credit' ? '+' : '-' ?><?= number_format($tx['amount'], 2) ?> грн
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Запити на виведення -->
            <?php if (!empty($withdrawals)): ?>
            <div class="section-card">
                <h2 class="section-title">🏦 Запити на виведення</h2>
                <?php foreach ($withdrawals as $wd): ?>
                <div class="withdrawal-item">
                    <div class="withdrawal-header">
                        <div class="withdrawal-amount"><?= number_format($wd['amount'], 2) ?> грн</div>
                        <span class="badge badge-<?= $wd['status'] ?>">
                            <?php
                            $wdStatuses = [
                                'pending'  => '⏳ Очікується',
                                'approved' => '✅ Підтверджено',
                                'paid'     => '💳 Виплачено',
                                'rejected' => '❌ Відхилено',
                            ];
                            echo $wdStatuses[$wd['status']] ?? $wd['status'];
                            ?>
                        </span>
                    </div>
                    <div class="withdrawal-meta">
                        Картка: **** **** **** <?= substr($wd['card_number'], -4) ?> ·
                        <?= date('d.m.Y', strtotime($wd['created_at'])) ?>
                    </div>
                    <?php if ($wd['admin_note']): ?>
                    <div style="margin-top:8px;font-size:.85rem;color:#666;">
                        💬 <?= htmlspecialchars($wd['admin_note']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Права: форма виведення -->
        <div>
            <div class="section-card">
                <h2 class="section-title">🏧 Вивести кошти</h2>

                <?php if ($success): ?>
                    <div class="success-msg">✅ <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-list"><ul>
                        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul></div>
                <?php endif; ?>

                <div class="min-note">
                    💡 Мінімальна сума виведення — 100 грн.<br>
                    Обробка запиту: 1–3 робочих дні.
                </div>

                <?php if ($balanceData['available_balance'] >= 100): ?>
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Сума виведення (грн)</label>
                        <input type="number" name="amount" class="form-input"
                               min="100" max="<?= $balanceData['available_balance'] ?>"
                               step="0.01" placeholder="500.00" required>
                        <div class="form-help">
                            Доступно: <strong><?= number_format($balanceData['available_balance'], 2) ?> грн</strong>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Номер картки</label>
                        <input type="text" name="card_number" class="form-input"
                               placeholder="1234 5678 9012 3456" maxlength="19"
                               oninput="this.value=this.value.replace(/\D/g,'').replace(/(\d{4})(?=\d)/g,'$1 ').trim()"
                               required>
                        <div class="form-help">16 цифр, без пробілів</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Власник картки</label>
                        <input type="text" name="card_holder" class="form-input"
                               placeholder="IVAN PETRENKO"
                               style="text-transform:uppercase"
                               oninput="this.value=this.value.toUpperCase()"
                               required>
                        <div class="form-help">Латинськими літерами, як на картці</div>
                    </div>
                    <button type="submit" name="request_withdrawal" class="btn-withdraw">
                        💸 Подати запит на виведення
                    </button>
                </form>
                <?php else: ?>
                <div style="text-align:center;padding:30px 0;color:#666;">
                    <div style="font-size:3rem;margin-bottom:12px;">💤</div>
                    <p>Недостатньо коштів для виведення.<br>
                    Мінімум — 100 грн.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>