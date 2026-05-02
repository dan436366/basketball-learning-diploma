<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

$statusFilter = $_GET['status'] ?? 'completed';
$search       = trim($_GET['search'] ?? '');

$sql = "
    SELECT p.*, u.first_name, u.last_name, u.email, c.title as course_title,
           t.first_name as t_first, t.last_name as t_last
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    JOIN users t ON c.trainer_id = t.id
    WHERE 1=1
";
$params = [];
if ($statusFilter !== 'all') { $sql .= " AND p.status = ?"; $params[] = $statusFilter; }
if ($search) { $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR c.title LIKE ?)"; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }
$sql .= " ORDER BY p.created_at DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Загальна статистика
$stmt = $db->query("SELECT COALESCE(SUM(amount),0), COALESCE(SUM(platform_commission),0), COUNT(*) FROM payments WHERE status='completed'");
[$totalRev, $totalComm, $totalCount] = $stmt->fetch(\PDO::FETCH_NUM);

$pageTitle = 'Платежі';
include '../includes/header.php';
?>
<style>
    .admin-header { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:40px 0; margin-bottom:0; }
    .admin-header h1 { font-size:2rem; font-weight:700; }
    .admin-nav { background:white; padding:14px 0; margin-bottom:30px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:0; z-index:100; }
    .admin-nav-links { display:flex; gap:8px; flex-wrap:wrap; }
    .admin-nav-link { padding:9px 16px; background:#f8f9fa; color:#333; text-decoration:none; border-radius:8px; font-weight:600; font-size:.9rem; transition:all .2s; }
    .admin-nav-link:hover { background:#667eea; color:white; }
    .admin-nav-link.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }
    .stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
    .stat-box { background:white; border-radius:12px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,.07); text-align:center; }
    .stat-box .val { font-size:1.8rem; font-weight:700; color:#333; }
    .stat-box .lbl { color:#666; font-size:.85rem; }
    .stat-box.green .val { color:#28a745; }
    .filter-box { background:white; padding:16px 20px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
    .filter-tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
    .filter-tab { padding:8px 16px; border-radius:20px; text-decoration:none; font-weight:600; font-size:.88rem; background:#f0f0f0; color:#555; transition:all .2s; }
    .filter-tab.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }
    .section-card { background:white; border-radius:12px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.07); }
    .section-title { font-size:1.15rem; font-weight:700; color:#333; margin-bottom:16px; padding-bottom:12px; border-bottom:2px solid #f0f0f0; }
    table { width:100%; border-collapse:collapse; }
    th { padding:10px 12px; text-align:left; font-weight:600; color:#555; font-size:.82rem; border-bottom:2px solid #f0f0f0; white-space:nowrap; }
    td { padding:10px 12px; border-bottom:1px solid #f8f8f8; color:#666; font-size:.85rem; }
    tr:hover td { background:#f8f9ff; }
    .badge { display:inline-block; padding:3px 9px; border-radius:20px; font-size:.78rem; font-weight:600; }
    .badge-completed { background:#d4edda; color:#155724; }
    .badge-pending   { background:#fff3cd; color:#856404; }
    .badge-failed    { background:#f8d7da; color:#721c24; }
    input[type=text] { padding:9px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:.9rem; }
    input[type=text]:focus { border-color:#667eea; outline:none; }
    .btn-filter { padding:9px 20px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
</style>

<section class="admin-header">
    <div class="container">
        <h1>💳 Платежі</h1>
        <p>Всі транзакції платформи</p>
    </div>
</section>

<div class="container" style="padding-bottom:60px;">
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="admin_dashboard.php" class="admin-nav-link">📊 Огляд</a>
            <a href="users.php" class="admin-nav-link">👥 Користувачі</a>
            <a href="admin_courses.php" class="admin-nav-link">📚 Курси</a>
            <a href="admin_payments.php" class="admin-nav-link active">💳 Платежі</a>
            <a href="withdrawals.php" class="admin-nav-link">💸 Виплати</a>
            <a href="admin_settings.php" class="admin-nav-link">⚙️ Налаштування</a>
        </div>
    </nav>

    <div class="stats-row">
        <div class="stat-box"><div class="val"><?= $totalCount ?></div><div class="lbl">Успішних платежів</div></div>
        <div class="stat-box green"><div class="val"><?= number_format($totalRev,2) ?> грн</div><div class="lbl">Загальний оборот</div></div>
        <div class="stat-box green"><div class="val"><?= number_format($totalComm,2) ?> грн</div><div class="lbl">Дохід платформи (20%)</div></div>
    </div>

    <div class="filter-tabs">
        <?php foreach (['completed'=>'✅ Успішні','pending'=>'⏳ Очікують','failed'=>'❌ Невдалі','all'=>'Всі'] as $k=>$v): ?>
        <a href="?status=<?= $k ?>&search=<?= urlencode($search) ?>" class="filter-tab <?= $statusFilter===$k?'active':'' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>

    <div class="filter-box">
        <form method="GET" style="display:contents;">
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <input type="text" name="search" placeholder="Студент або курс..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-filter">🔍 Пошук</button>
        </form>
    </div>

    <div class="section-card">
        <div class="section-title">Платежі (<?= count($payments) ?>)</div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Студент</th><th>Курс</th><th>Тренер</th>
                    <th>Сума</th><th>Комісія</th><th>Тренеру</th>
                    <th>Метод</th><th>Статус</th><th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?><br><small style="color:#999;"><?= htmlspecialchars($p['email']) ?></small></td>
                    <td><?= htmlspecialchars(mb_substr($p['course_title'],0,25)) ?></td>
                    <td><?= htmlspecialchars($p['t_first'].' '.$p['t_last']) ?></td>
                    <td><strong><?= number_format($p['amount'],2) ?> грн</strong></td>
                    <td style="color:#28a745;"><?= number_format($p['platform_commission'],2) ?> грн</td>
                    <td><?= number_format($p['trainer_amount'],2) ?> грн</td>
                    <td><?= strtoupper($p['payment_method']) ?></td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td>
                    <td><?= date('d.m.Y H:i',strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>