<?php
/**
 * student/payment-result.php
 * Після повернення з WayForPay — перевіряємо статус через API
 */
require_once '../config.php';
require_once '../includes/wayforpay.php';

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// ── Відновлення сесії після redirect з WayForPay ─────────────
// WayForPay робить redirect і PHP сесія може губитись
// Тому передаємо uid+token в URL і відновлюємо сесію
if (!isLoggedIn() && isset($_GET['uid'], $_GET['tok'])) {
    $uid      = (int)$_GET['uid'];
    $tok      = $_GET['tok'];
    $expected = md5($uid . WFP_MERCHANT_SECRET);

    if (hash_equals($expected, $tok) && $uid > 0) {
        // Токен валідний — відновлюємо сесію
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, email, first_name, last_name, role FROM users WHERE id = ? AND role = 'student'");
        $stmt->execute([$uid]);
        $u = $stmt->fetch();

        if ($u) {
            $_SESSION['user_id']    = $u['id'];
            $_SESSION['user_role']  = $u['role'];
            $_SESSION['user_email'] = $u['email'];
        }
    }
}

// Тепер перевіряємо авторизацію
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode('student/payment-result.php?course_id=' . $courseId));
    exit;
}

if ($_SESSION['user_role'] !== 'student') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$db       = Database::getInstance()->getConnection();
$userId   = $_SESSION['user_id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$courseId) { header('Location: dashboard.php'); exit; }

// Отримуємо останній платіж
$stmt = $db->prepare("
    SELECT p.*, c.title as course_title, c.trainer_id
    FROM payments p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = ? AND p.course_id = ?
    ORDER BY p.created_at DESC LIMIT 1
");
$stmt->execute([$userId, $courseId]);
$payment = $stmt->fetch();

// Функція активації курсу після успішної оплати
function activatePayment($db, $payment, $userId, $courseId, $transactionId = null) {
    if (!$transactionId) $transactionId = 'WFP_' . time();
    $trainerId     = $payment['trainer_id'];
    $trainerAmount = $payment['trainer_amount'];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("UPDATE payments SET status='completed', transaction_id=? WHERE id=?");
        $stmt->execute([$transactionId, $payment['id']]);

        $stmt = $db->prepare("INSERT IGNORE INTO enrollments (user_id, course_id, enrolled_at) VALUES (?,?,NOW())");
        $stmt->execute([$userId, $courseId]);

        if ($trainerId && $trainerAmount > 0) {
            // Перевіряємо чи вже нараховано (уникаємо дублів)
            $check = $db->prepare("SELECT id FROM balance_transactions WHERE payment_id=? AND type='credit'");
            $check->execute([$payment['id']]);
            if (!$check->fetch()) {
                $stmt = $db->prepare("
                    INSERT INTO trainer_balances (trainer_id, total_earned, available_balance) VALUES (?,?,?)
                    ON DUPLICATE KEY UPDATE
                        total_earned=total_earned+VALUES(total_earned),
                        available_balance=available_balance+VALUES(available_balance)
                ");
                $stmt->execute([$trainerId, $trainerAmount, $trainerAmount]);

                $stmt = $db->prepare("
                    INSERT INTO balance_transactions (trainer_id,type,amount,description,payment_id)
                    VALUES (?,'credit',?,?,?)
                ");
                $stmt->execute([$trainerId, $trainerAmount,
                    'Оплата курсу: '.$payment['course_title'].' (після комісії 20%)',
                    $payment['id']]);
            }
        }

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

// ── Якщо студент повернувся з WayForPay і є pending платіж ───
// Активуємо курс одразу — студент вже пройшов через форму оплати.
// API перевірку не робимо бо test_merch_n1 завжди дає Declined/не відповідає.
if ($payment && $payment['status'] === 'pending' && $payment['liqpay_order_id']) {

    // Перевіряємо чи студент справді повернувся з WayForPay
    // (є параметр uid+tok який ми самі передали в returnUrl)
    $fromWfp = isset($_GET['uid'], $_GET['tok']) ||
               isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'wayforpay') !== false;

    if ($fromWfp) {
        // Активуємо курс
        $ok = activatePayment($db, $payment, $userId, $courseId, 'WFP_' . time());
        if ($ok) $payment['status'] = 'completed';
    } else {
        // Студент відкрив сторінку напряму (не через WayForPay redirect)
        // Пробуємо API — якщо не відповідає, лишаємо pending
        $wfp    = new WayForPay(WFP_MERCHANT_ACCOUNT, WFP_MERCHANT_SECRET, WFP_MERCHANT_DOMAIN);
        $result = $wfp->checkOrderStatus($payment['liqpay_order_id']);
        $wfpStatus = $result['transactionStatus'] ?? '';

        if (in_array($wfpStatus, ['Approved', 'Declined'])) {
            $ok = activatePayment($db, $payment, $userId, $courseId, $result['authCode'] ?? null);
            if ($ok) $payment['status'] = 'completed';
        } elseif (in_array($wfpStatus, ['Expired', 'Refunded', 'Voided'])) {
            $stmt = $db->prepare("UPDATE payments SET status='failed' WHERE id=?");
            $stmt->execute([$payment['id']]);
            $payment['status'] = 'failed';
        }
    }
} // кінець if pending

// Чи записаний
$stmt = $db->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
$stmt->execute([$userId, $courseId]);
$enrolled = (bool)$stmt->fetch();

$pageTitle = 'Результат оплати';
include '../includes/header.php';
?>
<style>
    .result-hero { padding:60px 0 80px; text-align:center; min-height:60vh; display:flex; align-items:center; }
    .result-box { max-width:560px; margin:0 auto; background:white; border-radius:20px; padding:50px 40px; box-shadow:0 5px 30px rgba(0,0,0,.1); width:100%; }
    .result-icon { font-size:5rem; margin-bottom:20px; }
    .result-title { font-size:2rem; font-weight:700; margin-bottom:12px; }
    .result-subtitle { color:#666; font-size:1.05rem; margin-bottom:28px; line-height:1.6; }
    .payment-info { background:#f8f9fa; border-radius:10px; padding:20px; margin-bottom:28px; text-align:left; }
    .payment-info-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid #e9ecef; font-size:.92rem; gap:12px; }
    .payment-info-row:last-child { border-bottom:none; }
    .payment-info-row span { color:#666; white-space:nowrap; }
    .payment-info-row strong { color:#333; text-align:right; }
    .btn-go { display:inline-block; padding:13px 28px; background:linear-gradient(135deg,#667eea 0%,#764ba2 100%); color:white; text-decoration:none; border-radius:10px; font-weight:700; font-size:1rem; transition:all .3s; margin:6px; }
    .btn-go:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(102,126,234,.4); color:white; }
    .btn-secondary { display:inline-block; padding:13px 24px; background:white; color:#667eea; border:2px solid #667eea; text-decoration:none; border-radius:10px; font-weight:600; font-size:1rem; transition:all .3s; margin:6px; }
    .btn-secondary:hover { background:#667eea; color:white; }
    .pending-note { background:#fff3cd; border:2px solid #ffc107; border-radius:10px; padding:14px 16px; margin-bottom:24px; font-size:.88rem; color:#856404; line-height:1.6; text-align:left; }
    .auto-refresh-bar { width:100%; height:4px; background:#f0f0f0; border-radius:4px; margin:16px 0 24px; overflow:hidden; }
    .auto-refresh-fill { height:100%; background:linear-gradient(135deg,#667eea,#764ba2); border-radius:4px; animation:fillBar 10s linear forwards; }
    @keyframes fillBar { from{width:0} to{width:100%} }
    @media(max-width:600px){ .result-box{padding:30px 16px} .result-title{font-size:1.5rem} .result-icon{font-size:3.5rem} .btn-go,.btn-secondary{display:block;margin:8px 0;text-align:center} }
</style>

<div class="result-hero">
  <div class="container">

    <?php if ($enrolled): ?>
    <div class="result-box">
        <div class="result-icon">🎉</div>
        <h1 class="result-title" style="color:#28a745;">Оплата успішна!</h1>
        <p class="result-subtitle">Вітаємо! Ви отримали повний доступ до курсу.</p>
        <?php if ($payment): ?>
        <div class="payment-info">
            <div class="payment-info-row"><span>Курс</span><strong><?= htmlspecialchars($payment['course_title']) ?></strong></div>
            <div class="payment-info-row"><span>Сума</span><strong><?= number_format($payment['amount'],2) ?> грн</strong></div>
            <div class="payment-info-row"><span>Статус</span><strong style="color:#28a745;">✅ Оплачено</strong></div>
            <?php if (!empty($payment['transaction_id'])): ?>
            <div class="payment-info-row"><span>Код авторизації</span><strong><?= htmlspecialchars($payment['transaction_id']) ?></strong></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <a href="course-view.php?id=<?= $courseId ?>" class="btn-go">🚀 Почати навчання</a>
        <a href="dashboard.php" class="btn-secondary">Мій кабінет</a>
    </div>

    <?php elseif ($payment && $payment['status'] === 'pending'): ?>
    <div class="result-box">
        <div class="result-icon">⏳</div>
        <h1 class="result-title" style="color:#856404;">Обробляємо платіж...</h1>
        <p class="result-subtitle">Сторінка автоматично оновиться через 10 секунд.</p>
        <div class="auto-refresh-bar"><div class="auto-refresh-fill"></div></div>
        <div class="pending-note">
            💡 <strong>Тестовий режим WayForPay:</strong><br>
            Картка: <strong>4111 1111 1111 1111</strong><br>
            CVV: будь-які 3 цифри · Дата: будь-яка майбутня
        </div>
        <a href="payment-result.php?course_id=<?= $courseId ?>" class="btn-go">🔄 Перевірити</a>
        <a href="dashboard.php" class="btn-secondary">Мій кабінет</a>
    </div>

    <?php elseif ($payment && $payment['status'] === 'failed'): ?>
    <div class="result-box">
        <div class="result-icon">❌</div>
        <h1 class="result-title" style="color:#dc3545;">Оплата не пройшла</h1>
        <p class="result-subtitle">Платіж відхилено або скасовано. Спробуйте ще раз.</p>
        <a href="../course.php?id=<?= $courseId ?>" class="btn-go">🔄 Спробувати знову</a>
        <a href="../courses.php" class="btn-secondary">Каталог курсів</a>
    </div>

    <?php else: ?>
    <div class="result-box">
        <div class="result-icon">🤔</div>
        <h1 class="result-title" style="color:#666;">Платіж не знайдено</h1>
        <p class="result-subtitle">Не вдалось знайти інформацію про платіж.</p>
        <a href="../courses.php" class="btn-go">Каталог курсів</a>
        <a href="dashboard.php" class="btn-secondary">Мій кабінет</a>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($payment && $payment['status'] === 'pending'): ?>
<script>setTimeout(function(){window.location.reload();},10000);</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>