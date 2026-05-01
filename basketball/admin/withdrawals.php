<?php
/**
 * admin/withdrawals.php — управління запитами на виведення коштів
 */
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// ── Обробка дії адміна ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $withdrawalId = (int)($_POST['withdrawal_id'] ?? 0);
    $action       = $_POST['action'] ?? '';
    $adminNote    = trim($_POST['admin_note'] ?? '');

    if ($withdrawalId && in_array($action, ['approve', 'reject', 'paid'])) {
        $stmt = $db->prepare("SELECT * FROM withdrawals WHERE id = ?");
        $stmt->execute([$withdrawalId]);
        $withdrawal = $stmt->fetch();

        if ($withdrawal) {
            if ($action === 'approve') {
                $stmt = $db->prepare("
                    UPDATE withdrawals SET status='approved', admin_note=?, processed_at=NOW()
                    WHERE id=?
                ");
                $stmt->execute([$adminNote, $withdrawalId]);
                setFlashMessage('success', 'Запит підтверджено');

            } elseif ($action === 'reject') {
                // Повертаємо кошти на баланс тренера
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("
                        UPDATE withdrawals SET status='rejected', admin_note=?, processed_at=NOW()
                        WHERE id=?
                    ");
                    $stmt->execute([$adminNote, $withdrawalId]);

                    $stmt = $db->prepare("
                        UPDATE trainer_balances
                        SET available_balance = available_balance + ?
                        WHERE trainer_id = ?
                    ");
                    $stmt->execute([$withdrawal['amount'], $withdrawal['trainer_id']]);

                    $stmt = $db->prepare("
                        INSERT INTO balance_transactions (trainer_id, type, amount, description, withdrawal_id)
                        VALUES (?, 'credit', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $withdrawal['trainer_id'],
                        $withdrawal['amount'],
                        'Повернення коштів: запит на виведення відхилено. ' . $adminNote,
                        $withdrawalId
                    ]);

                    $db->commit();
                    setFlashMessage('success', 'Запит відхилено, кошти повернено тренеру');
                } catch (Exception $e) {
                    $db->rollBack();
                    setFlashMessage('error', 'Помилка: ' . $e->getMessage());
                }

            } elseif ($action === 'paid') {
                // Позначаємо як виплачено — оновлюємо withdrawn_total
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("
                        UPDATE withdrawals SET status='paid', admin_note=?, processed_at=NOW()
                        WHERE id=?
                    ");
                    $stmt->execute([$adminNote, $withdrawalId]);

                    $stmt = $db->prepare("
                        UPDATE trainer_balances
                        SET withdrawn_total = withdrawn_total + ?
                        WHERE trainer_id = ?
                    ");
                    $stmt->execute([$withdrawal['amount'], $withdrawal['trainer_id']]);

                    $db->commit();
                    setFlashMessage('success', 'Виплату позначено як здійснену');
                } catch (Exception $e) {
                    $db->rollBack();
                    setFlashMessage('error', 'Помилка: ' . $e->getMessage());
                }
            }
        }
    }

    header('Location: withdrawals.php');
    exit;
}

// ── Статистика ────────────────────────────────────────────────
$stmt = $db->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'");
$pendingCount = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='pending'");
$pendingAmount = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='paid'");
$paidTotal = $stmt->fetchColumn();

// ── Всі запити ────────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? 'pending';
$allowedStatuses = ['pending','approved','rejected','paid','all'];
if (!in_array($statusFilter, $allowedStatuses)) $statusFilter = 'pending';

$sql = "
    SELECT w.*, u.first_name, u.last_name, u.email,
           tb.available_balance as trainer_balance
    FROM withdrawals w
    JOIN users u ON w.trainer_id = u.id
    LEFT JOIN trainer_balances tb ON w.trainer_id = tb.trainer_id
";
if ($statusFilter !== 'all') {
    $sql .= " WHERE w.status = " . $db->quote($statusFilter);
}
$sql .= " ORDER BY w.created_at DESC";

$stmt = $db->query($sql);
$withdrawals = $stmt->fetchAll();

$pageTitle = 'Управління виплатами';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    .page-header h1 { font-size: 2rem; font-weight: 700; }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-box {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        text-align: center;
    }
    .stat-box .val { font-size: 1.8rem; font-weight: 700; color: #333; }
    .stat-box .lbl { color: #666; font-size: .9rem; margin-top: 4px; }
    .stat-box.warn .val { color: #dc3545; }
    .stat-box.ok .val   { color: #28a745; }

    .filter-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .filter-tab {
        padding: 8px 18px;
        border-radius: 20px;
        text-decoration: none;
        font-weight: 600;
        font-size: .9rem;
        background: #f0f0f0;
        color: #555;
        transition: all .2s;
    }
    .filter-tab:hover { background: #e0e0e0; color: #333; }
    .filter-tab.active { background: linear-gradient(135deg,#11998e,#38ef7d); color: white; }

    .wd-card {
        background: white;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        margin-bottom: 16px;
    }
    .wd-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .wd-trainer { font-size: 1.1rem; font-weight: 700; color: #333; }
    .wd-email   { color: #667eea; font-size: .9rem; }
    .wd-amount  { font-size: 1.5rem; font-weight: 700; color: #333; }

    .wd-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 16px;
        font-size: .9rem;
    }
    .wd-detail-item span { color: #666; display: block; font-size: .8rem; }
    .wd-detail-item strong { color: #333; }

    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: .82rem;
        font-weight: 600;
    }
    .badge-pending  { background: #fff3cd; color: #856404; }
    .badge-approved { background: #d4edda; color: #155724; }
    .badge-paid     { background: #d1ecf1; color: #0c5460; }
    .badge-rejected { background: #f8d7da; color: #721c24; }

    .action-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
    .action-note {
        flex: 1;
        min-width: 200px;
        padding: 9px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: .9rem;
    }
    .action-note:focus { border-color: #11998e; outline: none; }

    .btn-approve { padding: 9px 18px; background: #28a745; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all .2s; }
    .btn-approve:hover { background: #218838; }
    .btn-paid    { padding: 9px 18px; background: #17a2b8; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all .2s; }
    .btn-paid:hover { background: #138496; }
    .btn-reject  { padding: 9px 18px; background: #dc3545; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all .2s; }
    .btn-reject:hover { background: #c82333; }

    .admin-note-display { font-size: .88rem; color: #666; margin-top: 8px; padding: 8px 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #adb5bd; }

    .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; color: #666; }
    .empty-state .icon { font-size: 3rem; margin-bottom: 12px; }

    .flash-msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; }
    .flash-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
    .flash-error   { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }

    @media(max-width:767px){
        .stats-row{ grid-template-columns:1fr; }
        .page-header{padding:22px 0;}
        .page-header h1{font-size:1.4rem;}
        .wd-card{padding:14px;}
        .wd-amount{font-size:1.2rem;}
    }
</style>

<section class="page-header">
    <div class="container">
        <h1>💸 Управління виплатами</h1>
        <p>Обробка запитів тренерів на виведення коштів</p>
    </div>
</section>

<div class="container" style="padding-bottom:60px;">

    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="flash-msg flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Статистика -->
    <div class="stats-row">
        <div class="stat-box warn">
            <div class="val"><?= $pendingCount ?></div>
            <div class="lbl">Очікують обробки</div>
        </div>
        <div class="stat-box warn">
            <div class="val"><?= number_format($pendingAmount, 2) ?> грн</div>
            <div class="lbl">Сума до виплати</div>
        </div>
        <div class="stat-box ok">
            <div class="val"><?= number_format($paidTotal, 2) ?> грн</div>
            <div class="lbl">Всього виплачено</div>
        </div>
    </div>

    <!-- Фільтр -->
    <div class="filter-tabs">
        <?php
        $tabs = ['pending'=>'⏳ Очікують','approved'=>'✅ Підтверджені','paid'=>'💳 Виплачені','rejected'=>'❌ Відхилені','all'=>'Всі'];
        foreach ($tabs as $key => $label):
        ?>
        <a href="?status=<?= $key ?>" class="filter-tab <?= $statusFilter === $key ? 'active' : '' ?>">
            <?= $label ?>
            <?php if ($key === 'pending' && $pendingCount > 0): ?>
            <span style="background:white;color:#dc3545;border-radius:10px;padding:1px 6px;font-size:.75rem;margin-left:4px;"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Запити -->
    <?php if (empty($withdrawals)): ?>
    <div class="empty-state">
        <div class="icon">📭</div>
        <p>Запитів немає</p>
    </div>
    <?php else: ?>
    <?php foreach ($withdrawals as $wd): ?>
    <div class="wd-card">
        <div class="wd-header">
            <div>
                <div class="wd-trainer">
                    <?= htmlspecialchars($wd['first_name'] . ' ' . $wd['last_name']) ?>
                </div>
                <div class="wd-email"><?= htmlspecialchars($wd['email']) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="wd-amount"><?= number_format($wd['amount'], 2) ?> грн</div>
                <span class="badge badge-<?= $wd['status'] ?>">
                    <?php
                    $labels = ['pending'=>'⏳ Очікується','approved'=>'✅ Підтверджено','paid'=>'💳 Виплачено','rejected'=>'❌ Відхилено'];
                    echo $labels[$wd['status']] ?? $wd['status'];
                    ?>
                </span>
            </div>
        </div>

        <div class="wd-details">
            <div class="wd-detail-item">
                <span>Картка</span>
                <strong>**** **** **** <?= substr($wd['card_number'], -4) ?></strong>
            </div>
            <div class="wd-detail-item">
                <span>Власник картки</span>
                <strong><?= htmlspecialchars($wd['card_holder']) ?></strong>
            </div>
            <div class="wd-detail-item">
                <span>Повний номер (для переказу)</span>
                <strong><?= chunk_split($wd['card_number'], 4, ' ') ?></strong>
            </div>
            <div class="wd-detail-item">
                <span>Баланс тренера зараз</span>
                <strong><?= number_format($wd['trainer_balance'] ?? 0, 2) ?> грн</strong>
            </div>
            <div class="wd-detail-item">
                <span>Дата запиту</span>
                <strong><?= date('d.m.Y H:i', strtotime($wd['created_at'])) ?></strong>
            </div>
            <?php if ($wd['processed_at']): ?>
            <div class="wd-detail-item">
                <span>Дата обробки</span>
                <strong><?= date('d.m.Y H:i', strtotime($wd['processed_at'])) ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($wd['admin_note']): ?>
        <div class="admin-note-display">💬 <?= htmlspecialchars($wd['admin_note']) ?></div>
        <?php endif; ?>

        <?php if ($wd['status'] === 'pending'): ?>
        <!-- Дії для pending -->
        <form method="POST">
            <input type="hidden" name="withdrawal_id" value="<?= $wd['id'] ?>">
            <div class="action-form">
                <input type="text" name="admin_note" class="action-note" placeholder="Коментар (необов'язково)">
                <button type="submit" name="action" value="approve" class="btn-approve">✅ Підтвердити</button>
                <button type="submit" name="action" value="reject" class="btn-reject"
                    onclick="return confirm('Відхилити запит? Кошти повернуться тренеру.')">
                    ❌ Відхилити
                </button>
            </div>
        </form>

        <?php elseif ($wd['status'] === 'approved'): ?>
        <!-- Після підтвердження — позначити як виплачено -->
        <form method="POST" style="display:inline;">
            <input type="hidden" name="withdrawal_id" value="<?= $wd['id'] ?>">
            <input type="hidden" name="action" value="paid">
            <div class="action-form">
                <input type="text" name="admin_note" class="action-note"
                    placeholder="Підтвердження переказу (напр. квитанція №...)">
                <button type="submit" class="btn-paid"
                    onclick="return confirm('Підтвердити що виплата здійснена?')">
                    💳 Позначити як виплачено
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include '../includes/footer.php'; ?>