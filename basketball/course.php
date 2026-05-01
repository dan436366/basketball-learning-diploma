<?php
require_once 'config.php';
require_once 'includes/wayforpay.php';

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$courseId) {
    header('Location: courses.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// ── Інформація про курс ───────────────────────────────────────
$stmt = $db->prepare("
    SELECT c.*, u.first_name, u.last_name, u.bio, u.experience_years, u.id as trainer_user_id,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE course_id = c.id) as reviews_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    WHERE c.id = ? AND c.is_active = 1
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// ── Чи записаний студент ──────────────────────────────────────
$isEnrolled = false;
$currentUser = null;
if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    $stmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $courseId]);
    $isEnrolled = $stmt->fetch() !== false;
}

// ── Уроки ─────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM video_lessons WHERE course_id = ? ORDER BY order_number ASC");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

// ── Відгуки ───────────────────────────────────────────────────
$stmt = $db->prepare("
    SELECT r.*, u.first_name, u.last_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.course_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$courseId]);
$reviews = $stmt->fetchAll();

// ── WayForPay форма (тільки для платних курсів і студентів) ───
$wfpForm = '';

if (!$isEnrolled && isLoggedIn() && $currentUser['role'] === 'student'
    && !$course['is_free'] && $course['price'] > 0) {

    $wfp    = new WayForPay(WFP_MERCHANT_ACCOUNT, WFP_MERCHANT_SECRET, WFP_MERCHANT_DOMAIN);
    $userId = $_SESSION['user_id'];

    // Видаляємо старі pending платежі старші 1 години (підпис вже не валідний)
    $stmt = $db->prepare("
        DELETE FROM payments
        WHERE user_id = ? AND course_id = ? AND status = 'pending'
        AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$userId, $courseId]);

    // Перевіряємо чи вже є pending платіж
    $stmt = $db->prepare("
        SELECT liqpay_order_id, transaction_id FROM payments
        WHERE user_id = ? AND course_id = ? AND status = 'pending'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$userId, $courseId]);
    $existingPending = $stmt->fetch();

    if ($existingPending) {
        $orderReference = $existingPending['liqpay_order_id'];
        // orderDate зберігається в transaction_id поки статус pending
        $orderDate = (int)$existingPending['transaction_id'] ?: time();
    } else {
        $orderReference     = WayForPay::generateOrderReference($courseId, $userId);
        $orderDate          = time();
        $amount             = (float)$course['price'];
        $platformCommission = round($amount * PLATFORM_COMMISSION_PERCENT / 100, 2);
        $trainerAmount      = round($amount - $platformCommission, 2);

        $stmt = $db->prepare("
            INSERT INTO payments
                (user_id, course_id, amount, platform_commission, trainer_amount,
                 payment_method, liqpay_order_id, transaction_id, status)
            VALUES (?, ?, ?, ?, ?, 'wayforpay', ?, ?, 'pending')
        ");
        $stmt->execute([$userId, $courseId, $amount, $platformCommission, $trainerAmount,
                        $orderReference, $orderDate]);
    }

    $returnUrl = BASE_URL . '/student/payment-result.php?course_id=' . $courseId . '&uid=' . $userId . '&tok=' . md5($userId . WFP_MERCHANT_SECRET);
    $userEmail = $currentUser['email'] ?? '';

    $wfpForm = $wfp->buildForm([
        'orderReference' => $orderReference,
        'orderDate'      => $orderDate,
        'amount'         => (float)$course['price'],
        'currency'       => 'UAH',
        'productName'    => 'Курс: ' . $course['title'],
        'productPrice'   => (float)$course['price'],
        'productCount'   => 1,
        'clientEmail'    => $userEmail,
        'returnUrl'      => $returnUrl,
        'serviceUrl'     => '',
    ]);
}

$pageTitle = $course['title'];
include 'includes/header.php';
?>

<style>
    .course-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
    }

    .course-header h1 {
        font-size: 2.8rem;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .course-meta-header {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        font-size: 1.1rem;
    }

    .course-meta-header > div {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .course-level-badge {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 1rem;
        margin-bottom: 16px;
    }

    .badge-beginner    { background: rgba(255,255,255,0.2); }
    .badge-intermediate{ background: rgba(255,193,7,0.3); }
    .badge-advanced    { background: rgba(255,82,82,0.3); }

    /* Layout */
    .main-content { padding: 40px 0; }

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }

    .course-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .section-title {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .course-description {
        color: #555;
        line-height: 1.8;
        font-size: 1.05rem;
    }

    /* Уроки */
    .lessons-list { list-style: none; padding: 0; }

    .lesson-item {
        padding: 15px;
        border: 2px solid #f0f0f0;
        border-radius: 10px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }

    .lesson-item:hover { border-color: #667eea; background: #f8f9ff; }

    .lesson-info { display: flex; align-items: center; gap: 15px; }

    .lesson-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }

    .lesson-title { font-weight: 600; color: #333; }
    .lesson-duration { color: #666; font-size: 0.9rem; }
    .lesson-locked { color: #999; font-size: 0.9rem; }

    /* Тренер */
    .trainer-card { display: flex; gap: 20px; align-items: flex-start; }

    .trainer-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .trainer-info h3 { color: #333; margin-bottom: 5px; }
    .trainer-experience { color: #667eea; font-weight: 600; margin-bottom: 10px; }
    .trainer-bio { color: #666; line-height: 1.6; }

    /* Відгуки */
    .review-item {
        padding: 20px;
        border: 2px solid #f0f0f0;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .review-user { display: flex; gap: 12px; align-items: center; }

    .review-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        flex-shrink: 0;
    }

    .review-user-name { font-weight: 600; color: #333; }
    .review-date { color: #999; font-size: 0.85rem; }
    .review-rating { color: #ffc107; font-size: 1.1rem; }
    .review-text { color: #555; line-height: 1.6; }

    /* Sidebar */
    .sidebar-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        position: sticky;
        top: 20px;
    }

    .price-section {
        text-align: center;
        padding: 20px 0;
        border-bottom: 2px solid #f0f0f0;
        margin-bottom: 20px;
    }

    .price-label { color: #666; font-size: 0.9rem; margin-bottom: 5px; }

    .price-amount {
        font-size: 3rem;
        color: #667eea;
        font-weight: 700;
        line-height: 1.1;
    }

    /* Кнопки */
    .btn-enroll {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.15rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: block;
        text-align: center;
        box-sizing: border-box;
    }

    .btn-enroll:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        color: white;
    }

    .btn-enrolled {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        cursor: default;
    }
    .btn-enrolled:hover { transform: none; box-shadow: none; }

    .btn-disabled {
        background: #6c757d;
        cursor: not-allowed;
        pointer-events: none;
    }
    .btn-disabled:hover { transform: none; box-shadow: none; }

    /* WayForPay кнопка */
    .btn-wayforpay {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-sizing: border-box;
        text-decoration: none;
    }
    .btn-wayforpay:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(102,126,234,0.4);
        color: white;
    }
    #wfp-form { width: 100%; }

    /* Тестова підказка */
    .sandbox-hint {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 10px 14px;
        margin-top: 12px;
        font-size: 0.82rem;
        color: #856404;
        text-align: center;
        line-height: 1.5;
    }

    /* Що входить */
    .course-includes { list-style: none; padding: 0; margin-top: 25px; }

    .course-includes li {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #555;
    }

    .course-includes li:last-child { border-bottom: none; }

    /* Адаптивність */
    @media (max-width: 992px) {
        .content-grid { grid-template-columns: 1fr; }
        .sidebar-card { position: static; }
    }

    @media (max-width: 767px) {
        .course-header { padding: 24px 0; overflow: hidden; }
        .course-header h1 { font-size: 1.5rem; }
        .course-meta-header { gap: 12px; font-size: .9rem; }
        .course-section { padding: 16px; }
        .sidebar-card { padding: 16px; }
        .price-amount { font-size: 2rem; }
        .lesson-item { padding: 10px 12px; }
        .content-grid { gap: 16px; margin-bottom: 30px; }
        .section-title { font-size: 1.3rem; }
        .trainer-card { flex-direction: column; gap: 12px; }
        .trainer-avatar { width: 60px; height: 60px; font-size: 1.5rem; }
    }
</style>

<!-- ── Шапка курсу ── -->
<section class="course-header">
    <div class="container">
        <span class="course-level-badge badge-<?= $course['level'] ?>">
            <?php
            $levels = [
                'beginner'     => 'Початковий рівень',
                'intermediate' => 'Середній рівень',
                'advanced'     => 'Просунутий рівень',
            ];
            echo $levels[$course['level']] ?? $course['level'];
            ?>
        </span>
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <div class="course-meta-header">
            <div>
                <span>👨‍🏫</span>
                <span><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></span>
            </div>
            <div>
                <span>👥</span>
                <span><?= $course['students_count'] ?> учнів</span>
            </div>
            <?php if ($course['avg_rating']): ?>
            <div>
                <span>⭐</span>
                <span><?= number_format($course['avg_rating'], 1) ?> (<?= $course['reviews_count'] ?> відгуків)</span>
            </div>
            <?php endif; ?>
            <div>
                <span>📅</span>
                <span><?= $course['duration_weeks'] ?> тижнів</span>
            </div>
        </div>
    </div>
</section>

<!-- ── Основний контент ── -->
<div class="container main-content">
    <div class="content-grid">

        <!-- Ліва колонка -->
        <div>

            <!-- Опис -->
            <div class="course-section">
                <h2 class="section-title">📖 Про курс</h2>
                <p class="course-description"><?= nl2br(htmlspecialchars($course['description'])) ?></p>
            </div>

            <!-- Програма -->
            <div class="course-section">
                <h2 class="section-title">🎥 Програма курсу (<?= count($lessons) ?> уроків)</h2>
                <?php if (empty($lessons)): ?>
                    <p style="color:#666;">Уроки ще не додані.</p>
                <?php else: ?>
                <ul class="lessons-list">
                    <?php foreach ($lessons as $index => $lesson): ?>
                    <li class="lesson-item">
                        <div class="lesson-info">
                            <div class="lesson-number"><?= $index + 1 ?></div>
                            <div>
                                <div class="lesson-title"><?= htmlspecialchars($lesson['title']) ?></div>
                                <?php if ($lesson['duration_minutes']): ?>
                                <div class="lesson-duration">⏱️ <?= $lesson['duration_minutes'] ?> хв</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$isEnrolled): ?>
                            <span class="lesson-locked">🔒 Заблоковано</span>
                        <?php else: ?>
                            <a href="student/course-view.php?id=<?= $courseId ?>&lesson=<?= $lesson['id'] ?>"
                               style="color:#667eea;font-size:.9rem;font-weight:600;text-decoration:none;">
                                ▶ Дивитись
                            </a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Тренер -->
            <div class="course-section">
                <h2 class="section-title">👨‍🏫 Про тренера</h2>
                <div class="trainer-card">
                    <div class="trainer-avatar">
                        <?= strtoupper(mb_substr($course['first_name'], 0, 1)) ?>
                    </div>
                    <div class="trainer-info">
                        <h3><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></h3>
                        <?php if ($course['experience_years']): ?>
                            <div class="trainer-experience">Досвід: <?= $course['experience_years'] ?> років</div>
                        <?php endif; ?>
                        <?php if ($course['bio']): ?>
                            <p class="trainer-bio"><?= nl2br(htmlspecialchars($course['bio'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Відгуки -->
            <?php if (!empty($reviews)): ?>
            <div class="course-section">
                <h2 class="section-title">⭐ Відгуки учнів</h2>
                <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-header">
                        <div class="review-user">
                            <div class="review-avatar">
                                <?= strtoupper(mb_substr($review['first_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="review-user-name">
                                    <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                </div>
                                <div class="review-date"><?= formatDate($review['created_at']) ?></div>
                            </div>
                        </div>
                        <div class="review-rating">
                            <?= str_repeat('⭐', $review['rating']) ?>
                        </div>
                    </div>
                    <p class="review-text"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div><!-- /left -->

        <!-- Права колонка — Sidebar -->
        <div>
            <div class="sidebar-card">

                <!-- Ціна -->
                <div class="price-section">
                    <div class="price-label">Вартість курсу</div>
                    <div class="price-amount">
                        <?php if ($course['is_free'] || $course['price'] <= 0): ?>
                            <span style="color:#28a745;">Безкоштовно</span>
                        <?php else: ?>
                            <?= number_format($course['price'], 2) ?> грн
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Кнопка / форма оплати ── -->

                <?php if ($isEnrolled): ?>
                    <!-- Вже записаний -->
                    <a href="student/course-view.php?id=<?= $course['id'] ?>" class="btn-enroll btn-enrolled">
                        ✅ Перейти до навчання
                    </a>

                <?php elseif (!isLoggedIn()): ?>
                    <!-- Не авторизований -->
                    <a href="login.php" class="btn-enroll">
                        🔐 Увійти для <?= ($course['is_free'] || $course['price'] <= 0) ? 'запису' : 'покупки' ?>
                    </a>

                <?php elseif ($currentUser['role'] === 'trainer'): ?>
                    <!-- Тренер -->
                    <div class="btn-enroll btn-disabled">
                        Тренери не можуть купувати курси
                    </div>

                <?php elseif ($currentUser['role'] === 'admin'): ?>
                    <!-- Адмін -->
                    <div class="btn-enroll btn-disabled">
                        Адмін не купує курси
                    </div>

                <?php elseif ($course['is_free'] || $course['price'] <= 0): ?>
                    <!-- Безкоштовний курс -->
                    <a href="enroll-free.php?course_id=<?= $course['id'] ?>" class="btn-enroll">
                        🎁 Записатись безкоштовно
                    </a>

                <?php else: ?>
                    <!-- Платний курс — форма WayForPay -->
                    <?= $wfpForm ?>
                    <div class="sandbox-hint">
                        🧪 <strong>Тестовий режим</strong><br>
                        Картка: <strong>4111 1111 1111 1111</strong><br>
                        CVV: будь-які 3 цифри · Дата: будь-яка майбутня
                    </div>

                <?php endif; ?>

                <!-- Що входить у курс -->
                <ul class="course-includes">
                    <li><span style="color:#667eea;">✓</span> <?= count($lessons) ?> відеоуроків</li>
                    <li><span style="color:#667eea;">✓</span> Доступ назавжди</li>
                    <li><span style="color:#667eea;">✓</span> Сертифікат після завершення</li>
                    <li><span style="color:#667eea;">✓</span> Підтримка тренера</li>
                </ul>

            </div>
        </div><!-- /sidebar -->

    </div>
</div>

<?php include 'includes/footer.php'; ?>