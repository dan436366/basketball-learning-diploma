<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// ── Статистика ────────────────────────────────────────────────
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active = 1");
$totalUsers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role='student' AND is_active=1");
$totalStudents = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role='trainer' AND is_active=1");
$totalTrainers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM courses WHERE is_active=1");
$totalCourses = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM enrollments");
$totalEnrollments = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'");
$totalRevenue = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(platform_commission),0) FROM payments WHERE status='completed'");
$platformEarnings = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$monthlyRevenue = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'");
$pendingWithdrawals = $stmt->fetchColumn();

// ── Останні платежі ───────────────────────────────────────────
$stmt = $db->query("
    SELECT p.*, u.first_name, u.last_name, c.title as course_title
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    WHERE p.status = 'completed'
    ORDER BY p.created_at DESC LIMIT 8
");
$recentPayments = $stmt->fetchAll();

// ── Популярні курси ───────────────────────────────────────────
$stmt = $db->query("
    SELECT c.*, u.first_name, u.last_name,
           COUNT(DISTINCT e.id) as enrollments_count,
           COALESCE(SUM(p.amount),0) as revenue
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN payments p ON c.id = p.course_id AND p.status='completed'
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY enrollments_count DESC
    LIMIT 6
");
$popularCourses = $stmt->fetchAll();

// ── Нові користувачі ──────────────────────────────────────────
$stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 6");
$recentUsers = $stmt->fetchAll();

// ── Запити на виведення ───────────────────────────────────────
$stmt = $db->query("
    SELECT w.*, u.first_name, u.last_name, u.email
    FROM withdrawals w
    JOIN users u ON w.trainer_id = u.id
    WHERE w.status = 'pending'
    ORDER BY w.created_at ASC
    LIMIT 5
");
$pendingWds = $stmt->fetchAll();

$pageTitle = 'Панель адміністратора';
include '../includes/header.php';
?>

<style>
    .admin-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 0;
    }
    .admin-header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 6px; }

    .admin-nav {
        background: white;
        padding: 14px 0;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .admin-nav-links { display: flex; gap: 8px; flex-wrap: wrap; }
    .admin-nav-link {
        padding: 9px 16px;
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: .9rem;
        transition: all .2s;
        white-space: nowrap;
        position: relative;
    }
    .admin-nav-link:hover { background: #667eea; color: white; }
    .admin-nav-link.active { background: linear-gradient(135deg,#667eea,#764ba2); color: white; }
    .nav-badge {
        position: absolute;
        top: -5px; right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px; height: 18px;
        font-size: .7rem;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700;
    }

    /* Stats */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform .2s;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-card.clickable { cursor: pointer; }
    .stat-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }
    .si-purple { background: linear-gradient(135deg,#667eea,#764ba2); }
    .si-green  { background: linear-gradient(135deg,#11998e,#38ef7d); }
    .si-orange { background: linear-gradient(135deg,#f093fb,#f5576c); }
    .si-blue   { background: linear-gradient(135deg,#4facfe,#00f2fe); }
    .si-yellow { background: linear-gradient(135deg,#f7971e,#ffd200); }
    .si-red    { background: linear-gradient(135deg,#eb3349,#f45c43); }
    .stat-val { font-size: 1.6rem; font-weight: 700; color: #333; line-height: 1.1; }
    .stat-lbl { font-size: .82rem; color: #666; margin-top: 3px; }

    /* Section card */
    .section-card {
        background: white;
        border-radius: 12px;
        padding: 22px;
        box-shadow: 0 2px 12px rgba(0,0,0,.07);
        margin-bottom: 24px;
    }
    .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0f0f0;
        flex-wrap: wrap;
        gap: 8px;
    }
    .section-title { font-size: 1.15rem; font-weight: 700; color: #333; }
    .btn-sm-link {
        padding: 7px 14px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 7px;
        font-size: .85rem;
        font-weight: 600;
        transition: all .2s;
    }
    .btn-sm-link:hover { background: #5568d3; color: white; }

    /* Two col grid */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

    /* Table */
    .admin-table { width: 100%; border-collapse: collapse; }
    .admin-table th { padding: 10px 12px; text-align: left; font-weight: 600; color: #555; font-size: .85rem; border-bottom: 2px solid #f0f0f0; }
    .admin-table td { padding: 10px 12px; border-bottom: 1px solid #f8f8f8; color: #666; font-size: .88rem; }
    .admin-table tr:hover td { background: #f8f9ff; }
    .admin-table td:last-child { text-align: right; }

    /* Badge */
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: .78rem; font-weight: 600; }
    .badge-student  { background: #e3f2fd; color: #1976d2; }
    .badge-trainer  { background: #fff3e0; color: #f57c00; }
    .badge-admin    { background: #fce4ec; color: #c2185b; }
    .badge-success  { background: #d4edda; color: #155724; }
    .badge-pending  { background: #fff3cd; color: #856404; }
    .badge-failed   { background: #f8d7da; color: #721c24; }

    /* Withdrawal quick action */
    .wd-quick {
        padding: 14px;
        border: 2px solid #f0f0f0;
        border-radius: 10px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .wd-quick:hover { border-color: #667eea; }
    .wd-info .trainer-name { font-weight: 700; color: #333; font-size: .95rem; }
    .wd-info .wd-meta { font-size: .82rem; color: #666; margin-top: 2px; }
    .wd-amount { font-size: 1.2rem; font-weight: 700; color: #333; }
    .wd-actions { display: flex; gap: 8px; }
    .btn-approve { padding: 7px 14px; background: #28a745; color: white; border: none; border-radius: 7px; font-weight: 600; font-size: .85rem; cursor: pointer; transition: all .2s; }
    .btn-approve:hover { background: #218838; }
    .btn-reject  { padding: 7px 14px; background: #dc3545; color: white; border: none; border-radius: 7px; font-weight: 600; font-size: .85rem; cursor: pointer; transition: all .2s; }
    .btn-reject:hover { background: #c82333; }

    /* Alert */
    .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 14px 16px; border-radius: 8px; margin-bottom: 20px; color: #856404; font-size: .9rem; }

    @media(max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2,1fr); } }
    @media(max-width: 767px) {
        .admin-header { padding: 22px 0; }
        .admin-header h1 { font-size: 1.4rem; }
        .stats-grid { grid-template-columns: 1fr; gap: 10px; }
        .two-col { grid-template-columns: 1fr; }
        .section-card { padding: 14px; }
    }
</style>

<section class="admin-header">
    <div class="container">
        <h1>🛠️ Панель адміністратора</h1>
        <p>Управління платформою Basketball Learning</p>
    </div>
</section>

<div class="container">
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="admin_dashboard.php" class="admin-nav-link active">📊 Огляд</a>
            <a href="users.php" class="admin-nav-link">👥 Користувачі</a>
            <a href="admin_courses.php" class="admin-nav-link">📚 Курси</a>
            <a href="admin_payments.php" class="admin-nav-link">💳 Платежі</a>
            <a href="withdrawals.php" class="admin-nav-link">
                💸 Виплати
                <?php if ($pendingWithdrawals > 0): ?>
                <span class="nav-badge"><?= $pendingWithdrawals ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_settings.php" class="admin-nav-link">⚙️ Налаштування</a>
        </div>
    </nav>

    <?php if ($pendingWithdrawals > 0): ?>
    <div class="alert-warning">
        ⚠️ <strong><?= $pendingWithdrawals ?> запит(ів) на виведення коштів</strong> очікують обробки.
        <a href="withdrawals.php" style="color:#856404;font-weight:700;">Обробити →</a>
    </div>
    <?php endif; ?>

    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card clickable" onclick="location.href='users.php'">
            <div class="stat-icon si-purple">👥</div>
            <div>
                <div class="stat-val"><?= $totalUsers ?></div>
                <div class="stat-lbl">Всього користувачів</div>
            </div>
        </div>
        <div class="stat-card clickable" onclick="location.href='admin_courses.php'">
            <div class="stat-icon si-blue">📚</div>
            <div>
                <div class="stat-val"><?= $totalCourses ?></div>
                <div class="stat-lbl">Активних курсів</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-orange">📈</div>
            <div>
                <div class="stat-val"><?= $totalEnrollments ?></div>
                <div class="stat-lbl">Записів на курси</div>
            </div>
        </div>
        <div class="stat-card clickable" onclick="location.href='admin_payments.php'">
            <div class="stat-icon si-green">💰</div>
            <div>
                <div class="stat-val"><?= number_format($totalRevenue,0,'','') ?> грн</div>
                <div class="stat-lbl">Загальний оборот</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-yellow">🏆</div>
            <div>
                <div class="stat-val"><?= number_format($platformEarnings,0,'','') ?> грн</div>
                <div class="stat-lbl">Дохід платформи (20%)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-blue">📅</div>
            <div>
                <div class="stat-val"><?= number_format($monthlyRevenue,0,'','') ?> грн</div>
                <div class="stat-lbl">Оборот за місяць</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-orange">🎓</div>
            <div>
                <div class="stat-val"><?= $totalStudents ?></div>
                <div class="stat-lbl">Студентів</div>
            </div>
        </div>
        <div class="stat-card clickable" onclick="location.href='withdrawals.php'">
            <div class="stat-icon si-red">💸</div>
            <div>
                <div class="stat-val"><?= $pendingWithdrawals ?></div>
                <div class="stat-lbl">Запитів на виплату</div>
            </div>
        </div>
    </div>

    <!-- Запити на виведення (якщо є) -->
    <?php if (!empty($pendingWds)): ?>
    <div class="section-card">
        <div class="section-head">
            <h2 class="section-title">⏳ Запити на виведення коштів</h2>
            <a href="withdrawals.php" class="btn-sm-link">Всі запити</a>
        </div>
        <?php foreach ($pendingWds as $wd): ?>
        <div class="wd-quick">
            <div class="wd-info">
                <div class="trainer-name"><?= htmlspecialchars($wd['first_name'].' '.$wd['last_name']) ?></div>
                <div class="wd-meta">
                    Картка: **** <?= substr($wd['card_number'],-4) ?> ·
                    <?= htmlspecialchars($wd['card_holder']) ?> ·
                    <?= date('d.m.Y H:i', strtotime($wd['created_at'])) ?>
                </div>
            </div>
            <div class="wd-amount"><?= number_format($wd['amount'],2) ?> грн</div>
            <div class="wd-actions">
                <form method="POST" action="withdrawals.php" style="display:inline;">
                    <input type="hidden" name="withdrawal_id" value="<?= $wd['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn-approve">✅ Підтвердити</button>
                </form>
                <form method="POST" action="withdrawals.php" style="display:inline;"
                      onsubmit="return confirm('Відхилити? Кошти повернуться тренеру.')">
                    <input type="hidden" name="withdrawal_id" value="<?= $wd['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="admin_note" value="Відхилено адміністратором">
                    <button type="submit" class="btn-reject">❌</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Дві колонки -->
    <div class="two-col">

        <!-- Популярні курси -->
        <div class="section-card">
            <div class="section-head">
                <h2 class="section-title">📊 Популярні курси</h2>
                <a href="admin_courses.php" class="btn-sm-link">Всі курси</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Курс</th>
                        <th>Записів</th>
                        <th>Дохід</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popularCourses as $c): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars(mb_substr($c['title'],0,25)) ?><?= mb_strlen($c['title'])>25?'…':'' ?></strong><br>
                            <small style="color:#999;"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></small>
                        </td>
                        <td><?= $c['enrollments_count'] ?></td>
                        <td><?= number_format($c['revenue'],0,'','') ?> грн</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Нові користувачі -->
        <div class="section-card">
            <div class="section-head">
                <h2 class="section-title">👤 Нові користувачі</h2>
                <a href="users.php" class="btn-sm-link">Всі</a>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Ім'я</th>
                        <th>Роль</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></strong><br>
                            <small style="color:#999;"><?= htmlspecialchars($u['email']) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-<?= $u['role'] ?>">
                                <?= ['student'=>'Учень','trainer'=>'Тренер','admin'=>'Адмін'][$u['role']] ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Останні платежі -->
    <div class="section-card">
        <div class="section-head">
            <h2 class="section-title">💳 Останні платежі</h2>
            <a href="admin_payments.php" class="btn-sm-link">Всі платежі</a>
        </div>
        <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Студент</th>
                    <th>Курс</th>
                    <th>Сума</th>
                    <th>Комісія платформи</th>
                    <th>Тренеру</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentPayments as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></td>
                    <td><?= htmlspecialchars(mb_substr($p['course_title'],0,28)) ?></td>
                    <td><strong><?= number_format($p['amount'],2) ?> грн</strong></td>
                    <td style="color:#28a745;"><?= number_format($p['platform_commission'],2) ?> грн</td>
                    <td><?= number_format($p['trainer_amount'],2) ?> грн</td>
                    <td><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>