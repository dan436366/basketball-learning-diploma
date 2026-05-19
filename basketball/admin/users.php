<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// ── Дії ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($_POST['action'] === 'toggle_status' && $userId) {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$userId]);
        setFlashMessage('success', 'Статус користувача змінено');

    } elseif ($_POST['action'] === 'change_role' && $userId) {
        $newRole = sanitizeInput($_POST['role'] ?? '');
        if (in_array($newRole, ['student', 'trainer', 'admin'])) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            setFlashMessage('success', 'Роль змінено');
        }

    } elseif ($_POST['action'] === 'delete' && $userId) {
        // М'яке видалення — деактивація
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND role != 'admin'");
        $stmt->execute([$userId]);
        setFlashMessage('success', 'Користувача деактивовано');
    }

    header('Location: users.php?' . http_build_query(array_filter([
        'role'   => $_GET['role']   ?? '',
        'search' => $_GET['search'] ?? '',
        'status' => $_GET['status'] ?? '',
        'page'   => $_GET['page']   ?? '',
    ])));
    exit;
}

// ── Фільтри ───────────────────────────────────────────────────
$role   = sanitizeInput($_GET['role']   ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$sql    = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($role)   { $sql .= " AND role = ?"; $params[] = $role; }
if ($search) { $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($status !== '') { $sql .= " AND is_active = ?"; $params[] = ($status === 'active') ? 1 : 0; }

// Підрахунок
$countStmt = $db->prepare(str_replace('SELECT *', 'SELECT COUNT(*)', $sql));
$countStmt->execute($params);
$totalUsers = $countStmt->fetchColumn();
$totalPages = ceil($totalUsers / $perPage);

$sql .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// ── Лічильники по ролях ───────────────────────────────────────
$stmt = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$roleCounts = [];
foreach ($stmt->fetchAll() as $row) $roleCounts[$row['role']] = $row['cnt'];

$stmt = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1");
$activeCount = $stmt->fetchColumn();

$pageTitle = 'Користувачі';
include '../includes/header.php';
?>

<style>
    .admin-header { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:40px 0; margin-bottom:0; }
    .admin-header h1 { font-size:2rem; font-weight:700; margin-bottom:6px; }

    .admin-nav { background:white; padding:14px 0; margin-bottom:30px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:0; z-index:100; }
    .admin-nav-links { display:flex; gap:8px; flex-wrap:wrap; }
    .admin-nav-link { padding:9px 16px; background:#f8f9fa; color:#333; text-decoration:none; border-radius:8px; font-weight:600; font-size:.9rem; transition:all .2s; white-space:nowrap; }
    .admin-nav-link:hover { background:#667eea; color:white; }
    .admin-nav-link.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }

    /* Stats row */
    .stats-row { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px; }
    .stat-chip { background:white; border-radius:10px; padding:16px; box-shadow:0 2px 10px rgba(0,0,0,.07); text-align:center; cursor:pointer; transition:all .2s; text-decoration:none; display:block; }
    .stat-chip:hover { transform:translateY(-2px); box-shadow:0 5px 18px rgba(0,0,0,.12); }
    .stat-chip.active-chip { border:2px solid #667eea; }
    .stat-chip .sc-val { font-size:1.6rem; font-weight:700; color:#333; }
    .stat-chip .sc-lbl { font-size:.8rem; color:#666; margin-top:3px; }
    .stat-chip .sc-ico { font-size:1.4rem; margin-bottom:6px; }

    /* Filter */
    .filter-box { background:white; padding:16px 20px; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
    .fg { display:flex; flex-direction:column; gap:4px; flex:1; min-width:160px; }
    .fg label { font-weight:600; color:#555; font-size:.82rem; }
    .fg input, .fg select { padding:9px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:.88rem; }
    .fg input:focus, .fg select:focus { border-color:#667eea; outline:none; }
    .btn-filter { padding:9px 20px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; height:40px; }
    .btn-reset  { padding:9px 16px; background:white; color:#667eea; border:2px solid #667eea; border-radius:8px; font-weight:600; text-decoration:none; height:40px; display:flex; align-items:center; }
    .btn-reset:hover { background:#667eea; color:white; }

    /* Table card */
    .section-card { background:white; border-radius:12px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:24px; }
    .section-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; padding-bottom:12px; border-bottom:2px solid #f0f0f0; flex-wrap:wrap; gap:8px; }
    .section-title { font-size:1.1rem; font-weight:700; color:#333; }
    .total-count { color:#666; font-size:.9rem; }

    table { width:100%; border-collapse:collapse; }
    th { padding:10px 12px; text-align:left; font-weight:600; color:#555; font-size:.82rem; border-bottom:2px solid #f0f0f0; white-space:nowrap; }
    td { padding:11px 12px; border-bottom:1px solid #f8f8f8; color:#666; font-size:.88rem; vertical-align:middle; }
    tr:hover td { background:#f8f9ff; }

    /* User avatar in table */
    .user-cell { display:flex; align-items:center; gap:10px; }
    .u-avatar { width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#667eea,#764ba2); color:white; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.9rem; flex-shrink:0; }
    .u-name { font-weight:600; color:#333; font-size:.9rem; }
    .u-email { font-size:.78rem; color:#999; }

    /* Badges */
    .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.78rem; font-weight:600; }
    .badge-student  { background:#e3f2fd; color:#1976d2; }
    .badge-trainer  { background:#fff3e0; color:#f57c00; }
    .badge-admin    { background:#fce4ec; color:#c2185b; }
    .badge-active   { background:#d4edda; color:#155724; }
    .badge-inactive { background:#f8d7da; color:#721c24; }

    /* Action buttons */
    .actions { display:flex; gap:6px; flex-wrap:wrap; }
    .btn-act { padding:5px 10px; border:none; border-radius:6px; cursor:pointer; font-size:.78rem; font-weight:600; transition:all .2s; white-space:nowrap; }
    .btn-toggle-on  { background:#ffc107; color:#333; }
    .btn-toggle-off { background:#28a745; color:white; }
    .btn-toggle-on:hover  { background:#e0a800; }
    .btn-toggle-off:hover { background:#218838; }

    /* Role select inline */
    .role-form { display:flex; gap:5px; align-items:center; }
    .role-select { padding:4px 8px; border:1px solid #e0e0e0; border-radius:6px; font-size:.78rem; background:white; cursor:pointer; }
    .btn-role { padding:4px 8px; background:#667eea; color:white; border:none; border-radius:6px; cursor:pointer; font-size:.78rem; font-weight:600; }
    .btn-role:hover { background:#5568d3; }

    /* Pagination */
    .pagination { display:flex; justify-content:center; gap:6px; flex-wrap:wrap; margin-top:20px; }
    .page-btn { padding:8px 14px; border:2px solid #e0e0e0; border-radius:8px; text-decoration:none; color:#555; font-weight:600; font-size:.88rem; transition:all .2s; }
    .page-btn:hover { border-color:#667eea; color:#667eea; }
    .page-btn.active-page { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:transparent; }

    /* Flash */
    .flash-msg { padding:12px 16px; border-radius:8px; margin-bottom:20px; font-weight:600; }
    .flash-success { background:#d4edda; border-left:4px solid #28a745; color:#155724; }

    @media(max-width:991px) { .stats-row{grid-template-columns:repeat(3,1fr);} }
    @media(max-width:767px) {
        .admin-header{padding:22px 0;} .admin-header h1{font-size:1.4rem;}
        .stats-row{grid-template-columns:repeat(2,1fr);}
        .section-card{padding:14px;}
        .actions{flex-direction:column;}
    }
</style>

<section class="admin-header">
    <div class="container">
        <h1>👥 Управління користувачами</h1>
        <p>Перегляд, редагування та модерація акаунтів</p>
    </div>
</section>

<div class="container" style="padding-bottom:60px;">
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="admin_dashboard.php" class="admin-nav-link">📊 Огляд</a>
            <a href="users.php"           class="admin-nav-link active">👥 Користувачі</a>
            <a href="admin_courses.php"   class="admin-nav-link">📚 Курси</a>
            <a href="admin_payments.php"  class="admin-nav-link">💳 Платежі</a>
            <a href="withdrawals.php"     class="admin-nav-link">💸 Виплати</a>
            <a href="admin_settings.php"  class="admin-nav-link">⚙️ Налаштування</a>
        </div>
    </nav>

    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="flash-msg flash-success">✅ <?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <!-- Статистика по ролях -->
    <div class="stats-row">
        <a href="users.php" class="stat-chip <?= !$role&&!$status?'active-chip':'' ?>">
            <div class="sc-ico">👥</div>
            <div class="sc-val"><?= array_sum($roleCounts) ?></div>
            <div class="sc-lbl">Всього</div>
        </a>
        <a href="users.php?role=student" class="stat-chip <?= $role==='student'?'active-chip':'' ?>">
            <div class="sc-ico">🎓</div>
            <div class="sc-val"><?= $roleCounts['student'] ?? 0 ?></div>
            <div class="sc-lbl">Студенти</div>
        </a>
        <a href="users.php?role=trainer" class="stat-chip <?= $role==='trainer'?'active-chip':'' ?>">
            <div class="sc-ico">👨‍🏫</div>
            <div class="sc-val"><?= $roleCounts['trainer'] ?? 0 ?></div>
            <div class="sc-lbl">Тренери</div>
        </a>
        <a href="users.php?role=admin" class="stat-chip <?= $role==='admin'?'active-chip':'' ?>">
            <div class="sc-ico">🛠️</div>
            <div class="sc-val"><?= $roleCounts['admin'] ?? 0 ?></div>
            <div class="sc-lbl">Адміни</div>
        </a>
        <a href="users.php?status=active" class="stat-chip <?= $status==='active'?'active-chip':'' ?>">
            <div class="sc-ico">✅</div>
            <div class="sc-val"><?= $activeCount ?></div>
            <div class="sc-lbl">Активні</div>
        </a>
    </div>

    <!-- Фільтри -->
    <div class="filter-box">
        <form method="GET" style="display:contents;">
            <div class="fg">
                <label>Пошук</label>
                <input type="text" name="search" placeholder="Ім'я або email..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="fg" style="max-width:150px;">
                <label>Роль</label>
                <select name="role">
                    <option value="">Всі ролі</option>
                    <option value="student" <?= $role==='student'?'selected':'' ?>>Студент</option>
                    <option value="trainer" <?= $role==='trainer'?'selected':'' ?>>Тренер</option>
                    <option value="admin"   <?= $role==='admin'?'selected':'' ?>>Адмін</option>
                </select>
            </div>
            <div class="fg" style="max-width:140px;">
                <label>Статус</label>
                <select name="status">
                    <option value="">Всі</option>
                    <option value="active"   <?= $status==='active'?'selected':'' ?>>Активні</option>
                    <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Неактивні</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">🔍 Пошук</button>
            <a href="users.php" class="btn-reset">✕ Скинути</a>
        </form>
    </div>

    <!-- Таблиця -->
    <div class="section-card">
        <div class="section-head">
            <h2 class="section-title">Список користувачів</h2>
            <span class="total-count">Знайдено: <strong><?= $totalUsers ?></strong></span>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Користувач</th>
                    <th>Роль</th>
                    <th>Статус</th>
                    <th>Дата реєстрації</th>
                    <th>Змінити роль</th>
                    <th>Дії</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="u-avatar"><?= strtoupper(mb_substr($u['first_name'],0,1)) ?></div>
                            <div>
                                <div class="u-name"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></div>
                                <div class="u-email"><?= htmlspecialchars($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $u['role'] ?>">
                            <?= ['student'=>'Студент','trainer'=>'Тренер','admin'=>'Адмін'][$u['role']] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $u['is_active']?'active':'inactive' ?>">
                            <?= $u['is_active']?'✅ Активний':'⛔ Неактивний' ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['role'] !== 'admin' || $u['id'] !== (int)$_SESSION['user_id']): ?>
                        <form method="POST" class="role-form">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="role" class="role-select">
                                <option value="student" <?= $u['role']==='student'?'selected':'' ?>>Студент</option>
                                <option value="trainer" <?= $u['role']==='trainer'?'selected':'' ?>>Тренер</option>
                                <option value="admin"   <?= $u['role']==='admin'?'selected':'' ?>>Адмін</option>
                            </select>
                            <button type="submit" class="btn-role">✓</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#999;font-size:.8rem;">Поточний адмін</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions">
                            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn-act <?= $u['is_active']?'btn-toggle-on':'btn-toggle-off' ?>">
                                    <?= $u['is_active']?'⏸ Деактивувати':'▶ Активувати' ?>
                                </button>
                            </form>
                            <?php else: ?>
                            <span style="color:#999;font-size:.78rem;">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Пагінація -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&role=<?= $role ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="page-btn">← Назад</a>
            <?php endif; ?>

            <?php
            $from = max(1, $page-2);
            $to   = min($totalPages, $page+2);
            for ($i=$from; $i<=$to; $i++):
            ?>
            <a href="?page=<?= $i ?>&role=<?= $role ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>"
               class="page-btn <?= $i===$page?'active-page':'' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&role=<?= $role ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>" class="page-btn">Вперед →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>