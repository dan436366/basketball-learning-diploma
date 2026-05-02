<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Дії
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    if ($courseId && $action === 'toggle') {
        $stmt = $db->prepare("UPDATE courses SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$courseId]);
        setFlashMessage('success', 'Статус курсу змінено');
    } elseif ($courseId && $action === 'delete') {
        $stmt = $db->prepare("UPDATE courses SET is_active = 0 WHERE id = ?");
        $stmt->execute([$courseId]);
        setFlashMessage('success', 'Курс деактивовано');
    }
    header('Location: admin_courses.php');
    exit;
}

// Фільтр
$search = trim($_GET['search'] ?? '');
$level  = $_GET['level'] ?? '';

$sql = "
    SELECT c.*, u.first_name, u.last_name,
           COUNT(DISTINCT e.id) as enrollments_count,
           COALESCE(SUM(p.amount),0) as revenue,
           COALESCE(AVG(r.rating),0) as avg_rating
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
    LEFT JOIN reviews r ON c.id = r.course_id
    WHERE 1=1
";
$params = [];
if ($search) { $sql .= " AND (c.title LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($level)  { $sql .= " AND c.level = ?"; $params[] = $level; }
$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

$pageTitle = 'Управління курсами';
include '../includes/header.php';
?>
<style>
    .admin-header { background: linear-gradient(135deg,#667eea,#764ba2); color:white; padding:40px 0; margin-bottom:0; }
    .admin-header h1 { font-size:2rem; font-weight:700; }
    .admin-nav { background:white; padding:14px 0; margin-bottom:30px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:0; z-index:100; }
    .admin-nav-links { display:flex; gap:8px; flex-wrap:wrap; }
    .admin-nav-link { padding:9px 16px; background:#f8f9fa; color:#333; text-decoration:none; border-radius:8px; font-weight:600; font-size:.9rem; transition:all .2s; }
    .admin-nav-link:hover { background:#667eea; color:white; }
    .admin-nav-link.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }
    .filter-box { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:24px; display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
    .filter-group { display:flex; flex-direction:column; gap:5px; flex:1; min-width:180px; }
    .filter-group label { font-weight:600; color:#555; font-size:.85rem; }
    .filter-input, .filter-select { padding:9px 12px; border:2px solid #e0e0e0; border-radius:8px; font-size:.9rem; }
    .filter-input:focus, .filter-select:focus { border-color:#667eea; outline:none; }
    .btn-filter { padding:9px 20px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
    .btn-reset  { padding:9px 16px; background:white; color:#667eea; border:2px solid #667eea; border-radius:8px; font-weight:600; text-decoration:none; }
    .section-card { background:white; border-radius:12px; padding:22px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:24px; }
    .section-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; padding-bottom:12px; border-bottom:2px solid #f0f0f0; }
    .section-title { font-size:1.15rem; font-weight:700; color:#333; }
    table { width:100%; border-collapse:collapse; }
    th { padding:10px 12px; text-align:left; font-weight:600; color:#555; font-size:.85rem; border-bottom:2px solid #f0f0f0; white-space:nowrap; }
    td { padding:10px 12px; border-bottom:1px solid #f8f8f8; color:#666; font-size:.88rem; }
    tr:hover td { background:#f8f9ff; }
    .badge { display:inline-block; padding:3px 9px; border-radius:20px; font-size:.78rem; font-weight:600; }
    .badge-active   { background:#d4edda; color:#155724; }
    .badge-inactive { background:#f8d7da; color:#721c24; }
    .badge-beginner { background:#e3f2fd; color:#1976d2; }
    .badge-intermediate { background:#fff3e0; color:#f57c00; }
    .badge-advanced { background:#fce4ec; color:#c2185b; }
    .btn-action { padding:5px 10px; border:none; border-radius:6px; cursor:pointer; font-size:.82rem; font-weight:600; transition:all .2s; }
    .btn-toggle { background:#ffc107; color:#333; }
    .btn-toggle:hover { background:#e0a800; }
    .flash-success { background:#d4edda; border-left:4px solid #28a745; padding:12px 16px; border-radius:8px; margin-bottom:20px; color:#155724; font-weight:600; }
</style>

<section class="admin-header">
    <div class="container">
        <h1>📚 Управління курсами</h1>
        <p>Перегляд і модерація курсів платформи</p>
    </div>
</section>

<div class="container">
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="admin_dashboard.php" class="admin-nav-link">📊 Огляд</a>
            <a href="users.php" class="admin-nav-link">👥 Користувачі</a>
            <a href="admin_courses.php" class="admin-nav-link active">📚 Курси</a>
            <a href="admin_payments.php" class="admin-nav-link">💳 Платежі</a>
            <a href="withdrawals.php" class="admin-nav-link">💸 Виплати</a>
            <a href="admin_settings.php" class="admin-nav-link">⚙️ Налаштування</a>
        </div>
    </nav>

    <?php $flash = getFlashMessage(); if ($flash): ?>
    <div class="flash-success"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="filter-box">
        <form method="GET" style="display:contents;">
            <div class="filter-group">
                <label>Пошук</label>
                <input type="text" name="search" class="filter-input" placeholder="Назва або тренер..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group" style="max-width:160px;">
                <label>Рівень</label>
                <select name="level" class="filter-select">
                    <option value="">Всі</option>
                    <option value="beginner" <?= $level==='beginner'?'selected':'' ?>>Початковий</option>
                    <option value="intermediate" <?= $level==='intermediate'?'selected':'' ?>>Середній</option>
                    <option value="advanced" <?= $level==='advanced'?'selected':'' ?>>Просунутий</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">🔍 Знайти</button>
            <a href="admin_courses.php" class="btn-reset">Скинути</a>
        </form>
    </div>

    <div class="section-card">
        <div class="section-head">
            <h2 class="section-title">Курси (<?= count($courses) ?>)</h2>
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Назва</th>
                    <th>Тренер</th>
                    <th>Рівень</th>
                    <th>Ціна</th>
                    <th>Записів</th>
                    <th>Дохід</th>
                    <th>Рейтинг</th>
                    <th>Статус</th>
                    <th>Дія</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td><strong><?= htmlspecialchars(mb_substr($c['title'],0,30)) ?></strong></td>
                    <td><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></td>
                    <td>
                        <span class="badge badge-<?= $c['level'] ?>">
                            <?= ['beginner'=>'Початк.','intermediate'=>'Середн.','advanced'=>'Просун.'][$c['level']] ?>
                        </span>
                    </td>
                    <td><?= $c['is_free'] ? '<span style="color:#28a745;">Безкошт.</span>' : number_format($c['price'],0).' грн' ?></td>
                    <td><?= $c['enrollments_count'] ?></td>
                    <td><?= number_format($c['revenue'],0) ?> грн</td>
                    <td><?= $c['avg_rating'] > 0 ? '⭐ '.number_format($c['avg_rating'],1) : '—' ?></td>
                    <td><span class="badge badge-<?= $c['is_active']?'active':'inactive' ?>"><?= $c['is_active']?'Активний':'Неактивний' ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="course_id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="action" value="toggle">
                            <button type="submit" class="btn-action btn-toggle">
                                <?= $c['is_active'] ? '⏸ Деакт.' : '▶ Акт.' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>