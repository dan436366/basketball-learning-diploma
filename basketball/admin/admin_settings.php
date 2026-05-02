<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

$success = '';
$errors  = [];

// Зміна пароля адміна
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPwd = $_POST['current_password'] ?? '';
    $newPwd     = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!password_verify($currentPwd, $user['password'])) {
        $errors[] = 'Поточний пароль невірний';
    } elseif (strlen($newPwd) < 6) {
        $errors[] = 'Новий пароль повинен містити мінімум 6 символів';
    } elseif ($newPwd !== $confirmPwd) {
        $errors[] = 'Паролі не співпадають';
    } else {
        $hash = password_hash($newPwd, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $_SESSION['user_id']]);
        $success = 'Пароль успішно змінено';
    }
}

// Зміна профілю адміна
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = sanitizeInput($_POST['first_name'] ?? '');
    $lastName  = sanitizeInput($_POST['last_name']  ?? '');
    $email     = sanitizeInput($_POST['email']      ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $errors[] = 'Заповніть всі поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Невірний формат email';
    } else {
        $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=? WHERE id=?");
        $stmt->execute([$firstName, $lastName, $email, $_SESSION['user_id']]);
        $_SESSION['user_email'] = $email;
        $success = 'Профіль оновлено';
    }
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$adminUser = $stmt->fetch();

// Загальна статистика БД
$stmt = $db->query("SELECT COUNT(*) FROM users");             $usersCount = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM courses");           $coursesCount = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM payments");          $paymentsCount = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM enrollments");       $enrollmentsCount = $stmt->fetchColumn();

$pageTitle = 'Налаштування';
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
    .settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:40px; }
    .section-card { background:white; border-radius:12px; padding:24px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:24px; }
    .section-title { font-size:1.2rem; font-weight:700; color:#333; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid #f0f0f0; }
    .form-group { margin-bottom:16px; }
    .form-label { display:block; margin-bottom:6px; font-weight:600; color:#333; font-size:.9rem; }
    .form-input { width:100%; padding:10px 14px; border:2px solid #e0e0e0; border-radius:8px; font-size:.95rem; box-sizing:border-box; }
    .form-input:focus { border-color:#667eea; outline:none; }
    .btn-save { padding:12px 28px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:.95rem; transition:all .2s; }
    .btn-save:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,.4); }
    .alert-success { background:#d4edda; border-left:4px solid #28a745; padding:12px 16px; border-radius:8px; margin-bottom:20px; color:#155724; font-weight:600; }
    .alert-error   { background:#f8d7da; border-left:4px solid #dc3545; padding:12px 16px; border-radius:8px; margin-bottom:20px; color:#721c24; }
    .info-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
    .info-item { background:#f8f9fa; border-radius:8px; padding:14px; text-align:center; }
    .info-item .val { font-size:1.6rem; font-weight:700; color:#667eea; }
    .info-item .lbl { font-size:.82rem; color:#666; }
    .commission-info { background:#f0f4ff; border:2px solid #667eea; border-radius:10px; padding:16px; }
    .commission-info h4 { color:#667eea; font-weight:700; margin-bottom:8px; }
    .commission-info p { color:#555; font-size:.9rem; margin:0; }
    @media(max-width:767px) { .settings-grid{grid-template-columns:1fr;} .info-grid{grid-template-columns:1fr;} }
</style>

<section class="admin-header">
    <div class="container">
        <h1>⚙️ Налаштування</h1>
        <p>Управління профілем та системою</p>
    </div>
</section>

<div class="container" style="padding-bottom:60px;">
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="admin_dashboard.php" class="admin-nav-link">📊 Огляд</a>
            <a href="users.php" class="admin-nav-link">👥 Користувачі</a>
            <a href="admin_courses.php" class="admin-nav-link">📚 Курси</a>
            <a href="admin_payments.php" class="admin-nav-link">💳 Платежі</a>
            <a href="withdrawals.php" class="admin-nav-link">💸 Виплати</a>
            <a href="admin_settings.php" class="admin-nav-link active">⚙️ Налаштування</a>
        </div>
    </nav>

    <?php if ($success): ?>
    <div class="alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="alert-error">❌ <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- Профіль адміна -->
        <div class="section-card">
            <h2 class="section-title">👤 Профіль адміністратора</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Ім'я</label>
                    <input type="text" name="first_name" class="form-input" value="<?= htmlspecialchars($adminUser['first_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Прізвище</label>
                    <input type="text" name="last_name" class="form-input" value="<?= htmlspecialchars($adminUser['last_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($adminUser['email']) ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn-save">💾 Зберегти профіль</button>
            </form>
        </div>

        <!-- Зміна пароля -->
        <div class="section-card">
            <h2 class="section-title">🔐 Зміна пароля</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Поточний пароль</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Новий пароль</label>
                    <input type="password" name="new_password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Підтвердити пароль</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>
                <button type="submit" name="change_password" class="btn-save">🔒 Змінити пароль</button>
            </form>
        </div>
    </div>

    <!-- Інформація про систему -->
    <div class="section-card">
        <h2 class="section-title">📊 Статистика бази даних</h2>
        <div class="info-grid">
            <div class="info-item"><div class="val"><?= $usersCount ?></div><div class="lbl">Користувачів</div></div>
            <div class="info-item"><div class="val"><?= $coursesCount ?></div><div class="lbl">Курсів</div></div>
            <div class="info-item"><div class="val"><?= $paymentsCount ?></div><div class="lbl">Платежів</div></div>
            <div class="info-item"><div class="val"><?= $enrollmentsCount ?></div><div class="lbl">Записів на курси</div></div>
        </div>
    </div>

    <!-- Комісія платформи -->
    <div class="section-card">
        <h2 class="section-title">💰 Налаштування комісії</h2>
        <div class="commission-info">
            <h4>Поточна комісія платформи: 20%</h4>
            <p>
                При кожній оплаті курсу платформа отримує 20% від суми.<br>
                Тренер отримує 80% на свій баланс.<br>
                Щоб змінити відсоток — відредагуй константу <code>PLATFORM_COMMISSION_PERCENT</code> у файлі <code>config.php</code>.
            </p>
        </div>
    </div>

    <!-- WayForPay налаштування -->
    <div class="section-card">
        <h2 class="section-title">💳 WayForPay інтеграція</h2>
        <div style="background:#f8f9fa;border-radius:8px;padding:16px;font-family:monospace;font-size:.88rem;color:#555;">
            <div style="margin-bottom:8px;"><strong>Merchant Account:</strong> <?= defined('WFP_MERCHANT_ACCOUNT') ? htmlspecialchars(WFP_MERCHANT_ACCOUNT) : 'не налаштовано' ?></div>
            <div style="margin-bottom:8px;"><strong>Merchant Domain:</strong> <?= defined('WFP_MERCHANT_DOMAIN') ? htmlspecialchars(WFP_MERCHANT_DOMAIN) : 'не налаштовано' ?></div>
            <div><strong>Secret Key:</strong> <?= defined('WFP_MERCHANT_SECRET') ? str_repeat('*', min(strlen(WFP_MERCHANT_SECRET), 8)) . '...' : 'не налаштовано' ?></div>
        </div>
        <p style="color:#666;font-size:.88rem;margin-top:12px;">Для зміни ключів відредагуй файл <code>config.php</code></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>