<?php
require_once '../config.php';
requireRole('trainer');

$db = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

// Статистика тренера
$stmt = $db->prepare("SELECT COUNT(*) as total FROM courses WHERE trainer_id = ? AND is_active = 1");
$stmt->execute([$trainerId]);
$totalCourses = $stmt->fetch()['total'];

$stmt = $db->prepare("
    SELECT COUNT(DISTINCT e.user_id) as total 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE c.trainer_id = ?
");
$stmt->execute([$trainerId]);
$totalStudents = $stmt->fetch()['total'];

// Баланс тренера з trainer_balances (актуальні дані)
$stmt = $db->prepare("SELECT total_earned FROM trainer_balances WHERE trainer_id = ?");
$stmt->execute([$trainerId]);
$totalRevenue = $stmt->fetchColumn() ?? 0;

// Мої курси
$stmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as students_count,
           (SELECT AVG(rating) FROM reviews WHERE course_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM video_lessons WHERE course_id = c.id) as lessons_count
    FROM courses c
    WHERE c.trainer_id = ? AND c.is_active = 1
    ORDER BY c.created_at DESC
");
$stmt->execute([$trainerId]);
$myCourses = $stmt->fetchAll();

// Останні записи на курси
$stmt = $db->prepare("
    SELECT e.*, c.title as course_title, u.first_name, u.last_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON e.user_id = u.id
    WHERE c.trainer_id = ?
    ORDER BY e.enrolled_at DESC
    LIMIT 5
");
$stmt->execute([$trainerId]);
$recentEnrollments = $stmt->fetchAll();

// Останні відгуки
$stmt = $db->prepare("
    SELECT r.*, c.title as course_title, u.first_name, u.last_name
    FROM reviews r
    JOIN courses c ON r.course_id = c.id
    JOIN users u ON r.user_id = u.id
    WHERE c.trainer_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$trainerId]);
$recentReviews = $stmt->fetchAll();

$pageTitle = 'Панель тренера';
include '../includes/header.php';
?>

<style>
    .trainer-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .trainer-header h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .trainer-nav {
        background: white;
        padding: 15px 20px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        border-radius: 8px;
    }
    
    .trainer-nav-links {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .trainer-nav-link {
        padding: 10px 20px;
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .trainer-nav-link:hover {
        background: #f093fb;
        color: white;
    }
    
    .trainer-nav-link.active {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 30px;
    }

    /* ===== МОБІЛЬНА АДАПТАЦІЯ ===== */
    @media (max-width: 767px) {
        .trainer-header { padding: 25px 0; margin-bottom: 0; }
        .trainer-header h1 { font-size: 1.5rem; }

        .trainer-nav { padding: 10px 20px; margin-bottom: 20px; margin-top: 20px;}
        .trainer-nav-links { gap: 6px; }
        .trainer-nav-link { padding: 7px 10px; font-size: 0.82rem; }

        .stats-grid { grid-template-columns: 1fr; gap: 10px; margin-bottom: 20px; }
        .stat-card { padding: 16px; gap: 14px; }
        .stat-info h3 { font-size: 1.5rem; }

        .section-card { padding: 16px; margin-bottom: 16px; }
        .section-title { font-size: 1.1rem; }
        .section-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        .btn-create { width: 100%; text-align: center; box-sizing: border-box; }

        /* Кнопки курсу — кожна на повну ширину */
        .course-actions {
            flex-direction: column;
            gap: 8px;
        }
        .course-actions .btn-sm {
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }

        /* Таблиці — обрізаємо зайве, не виходимо за межі */
        .bottom-grid { grid-template-columns: 1fr; gap: 0; }
        .table-responsive { overflow-x: hidden; }
        table { table-layout: fixed; width: 100%; }
        table th, table td { 
            padding: 8px 6px; 
            font-size: 0.82rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            word-break: normal;
        }
        /* Колонки таблиці учнів: Учень 35%, Курс 40%, Дата 25% */
        table th:nth-child(1), table td:nth-child(1) { width: 35%; }
        table th:nth-child(2), table td:nth-child(2) { width: 40%; }
        table th:nth-child(3), table td:nth-child(3) { width: 25%; }
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    
    .stat-icon.pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-icon.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    
    .stat-info h3 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 5px;
        font-weight: 700;
    }
    
    .stat-info p {
        color: #666;
        margin: 0;
    }
    
    .section-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .section-title {
        font-size: 1.5rem;
        color: #333;
        font-weight: 700;
    }
    
    .btn-create {
        padding: 10px 25px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
        color: white;
    }
    
    .course-item {
        padding: 20px;
        border: 2px solid #f0f0f0;
        border-radius: 12px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .course-item:hover {
        border-color: #f093fb;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .course-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }
    
    .course-title {
        font-size: 1.2rem;
        color: #333;
        font-weight: 600;
        margin-bottom: 8px;
        word-break: break-word;
    }
    
    .course-stats {
        display: flex;
        gap: 12px;
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .course-stats span {
        white-space: nowrap;
    }
    
    .course-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .course-actions .btn-sm {
        flex: 1 1 auto;
        text-align: center;
    }
    
    .btn-sm {
        padding: 8px 15px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        cursor: pointer;
        display: inline-block;
    }
    
    .btn-edit {
        background: #667eea;
        color: white;
    }
    
    .btn-edit:hover {
        background: #5568d3;
        color: white;
    }
    
    .btn-view {
        background: #f8f9fa;
        color: #333;
        border: 2px solid #e0e0e0;
    }
    
    .btn-view:hover {
        background: #e9ecef;
        color: #333;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #f0f0f0;
    }
    
    table td {
        padding: 12px;
        border-bottom: 1px solid #f5f5f5;
        color: #666;
    }
    
    table tr:hover {
        background: #f8f9fa;
    }
    
    .rating-stars {
        color: #ffc107;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #666;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 15px;
    }
</style>

<!-- Trainer Header -->
<section class="trainer-header">
    <div class="container">
        <h1>👨‍🏫 Панель тренера</h1>
        <p>Керуйте своїми курсами та учнями</p>
    </div>
</section>

<div class="container">
    <!-- Navigation -->
    <nav class="trainer-nav">
        <div class="trainer-nav-links">
            <a href="dashboard.php" class="trainer-nav-link active">📊 Огляд</a>
            <a href="courses.php" class="trainer-nav-link">📚 Мої курси</a>
            <a href="students.php" class="trainer-nav-link">👥 Учні</a>
            <a href="reviews.php" class="trainer-nav-link">⭐ Відгуки</a>
        </div>
    </nav>
    
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pink">📚</div>
            <div class="stat-info">
                <h3><?= $totalCourses ?></h3>
                <p>Активних курсів</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-info">
                <h3><?= $totalStudents ?></h3>
                <p>Учнів</p>
            </div>
        </div>
        
        <div class="stat-card" style="cursor:pointer;" onclick="window.location.href='balance.php'">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <h3><?= formatPrice($totalRevenue) ?></h3>
                <p>Загальний дохід</p>
            </div>
        </div>
    </div>
    
    <!-- My Courses -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">📚 Мої курси</h2>
            <a href="course-create.php" class="btn-create">+ Створити курс</a>
        </div>
        
        <?php if (empty($myCourses)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h3>У вас поки немає курсів</h3>
                <p>Створіть свій перший курс та почніть навчати учнів</p>
                <a href="course-create.php" class="btn-create" style="margin-top: 15px; display: inline-block;">+ Створити курс</a>
            </div>
        <?php else: ?>
            <?php foreach ($myCourses as $course): ?>
            <div class="course-item">
                <div class="course-header">
                    <div>
                        <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                        <div class="course-stats">
                            <span>👥 <?= $course['students_count'] ?> учнів</span>
                            <span>🎥 <?= $course['lessons_count'] ?> уроків</span>
                            <?php if ($course['avg_rating']): ?>
                            <span class="rating-stars">⭐ <?= number_format($course['avg_rating'], 1) ?></span>
                            <?php endif; ?>
                            <span>💰 <?= formatPrice($course['price']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="course-actions">
                    <a href="course-edit.php?id=<?= $course['id'] ?>" class="btn-sm btn-edit">✏️ Редагувати</a>
                    <a href="course-lessons.php?id=<?= $course['id'] ?>" class="btn-sm btn-edit">🎥 Уроки</a>
                    <a href="../course.php?id=<?= $course['id'] ?>" class="btn-sm btn-view">👁️ Переглянути</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="bottom-grid">
        <!-- Recent Enrollments -->
        <div>
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">👥 Нові учні</h2>
                </div>
                
                <?php if (empty($recentEnrollments)): ?>
                    <div class="empty-state">
                        <p>Поки немає нових учнів</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Учень</th>
                                    <th>Курс</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentEnrollments as $enrollment): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($enrollment['course_title']) ?></td>
                                    <td><?= formatDate($enrollment['enrolled_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Reviews -->
        <div>
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">⭐ Останні відгуки</h2>
                </div>
                
                <?php if (empty($recentReviews)): ?>
                    <div class="empty-state">
                        <p>Поки немає відгуків</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Учень</th>
                                    <th>Курс</th>
                                    <th>Рейтинг</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReviews as $review): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></strong></td>
                                    <td><small><?= htmlspecialchars(mb_substr($review['course_title'], 0, 30)) ?></small></td>
                                    <td><span class="rating-stars"><?= str_repeat('⭐', $review['rating']) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>