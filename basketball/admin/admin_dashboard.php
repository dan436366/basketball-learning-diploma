<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Статистика
$stats = [];

// Загальна кількість користувачів
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
$stats['total_users'] = $stmt->fetch()['total'];

// Кількість студентів
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND is_active = 1");
$stats['students'] = $stmt->fetch()['total'];

// Кількість тренерів
$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainer' AND is_active = 1");
$stats['trainers'] = $stmt->fetch()['total'];

// Загальна кількість курсів
$stmt = $db->query("SELECT COUNT(*) as total FROM courses WHERE is_active = 1");
$stats['courses'] = $stmt->fetch()['total'];

// Активні записи на курси
$stmt = $db->query("SELECT COUNT(*) as total FROM enrollments WHERE completed_at IS NULL");
$stats['active_enrollments'] = $stmt->fetch()['total'];

// Завершені курси
$stmt = $db->query("SELECT COUNT(*) as total FROM enrollments WHERE completed_at IS NOT NULL");
$stats['completed_enrollments'] = $stmt->fetch()['total'];

// Загальний дохід
$stmt = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

// Платежі за останній місяць
$stmt = $db->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$stats['monthly_revenue'] = $stmt->fetch()['total'] ?? 0;

// Останні користувачі
$stmt = $db->query("
    SELECT * FROM users 
    WHERE is_active = 1 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recentUsers = $stmt->fetchAll();

// Популярні курси
$stmt = $db->query("
    SELECT c.*, u.first_name, u.last_name,
           COUNT(e.id) as enrollments_count,
           SUM(p.amount) as revenue
    FROM courses c
    LEFT JOIN users u ON c.trainer_id = u.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN payments p ON c.id = p.course_id AND p.status = 'completed'
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY enrollments_count DESC
    LIMIT 5
");
$popularCourses = $stmt->fetchAll();

// Останні платежі
$stmt = $db->query("
    SELECT p.*, u.first_name, u.last_name, c.title as course_title
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN courses c ON p.course_id = c.id
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPayments = $stmt->fetchAll();

$pageTitle = 'Панель адміністратора';
include '../includes/header.php';
?>

<style>
    .admin-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .admin-header h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .admin-nav {
        background: white;
        padding: 15px 0;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .admin-nav-links {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .admin-nav-link {
        padding: 10px 20px;
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .admin-nav-link:hover {
        background: #667eea;
        color: white;
    }
    
    .admin-nav-link.active {
        background: #667eea;
        color: white;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-icon.purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-icon.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-icon.orange { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-icon.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    
    .stat-value {
        font-size: 2.2rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.95rem;
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
    
    .btn-view-all {
        padding: 8px 20px;
        background: #667eea;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    
    .btn-view-all:hover {
        background: #5568d3;
        color: white;
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
    
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .badge-student { background: #e3f2fd; color: #1976d2; }
    .badge-trainer { background: #fff3e0; color: #f57c00; }
    .badge-admin { background: #fce4ec; color: #c2185b; }
    .badge-success { background: #d4edda; color: #155724; }
</style>

<!-- Admin Header -->
<section class="admin-header">
    <div class="container">
        <h1>🛠️ Панель адміністратора</h1>
        <p>Управління системою навчання баскетболу</p>
    </div>
</section>

<!-- Admin Navigation -->
<div class="container">
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="dashboard.php" class="admin-nav-link active">📊 Статистика</a>
            <a href="users.php" class="admin-nav-link">👥 Користувачі</a>
            <a href="courses.php" class="admin-nav-link">📚 Курси</a>
            <a href="payments.php" class="admin-nav-link">💰 Платежі</a>
            <a href="withdrawals.php" class="admin-nav-link">💸 Виплати</a>
            <a href="settings.php" class="admin-nav-link">⚙️ Налаштування</a>
        </div>
    </nav>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Всього користувачів</div>
                </div>
                <div class="stat-icon purple">👥</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= $stats['courses'] ?></div>
                    <div class="stat-label">Активних курсів</div>
                </div>
                <div class="stat-icon blue">📚</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= $stats['active_enrollments'] ?></div>
                    <div class="stat-label">Активних записів</div>
                </div>
                <div class="stat-icon orange">📈</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= formatPrice($stats['total_revenue']) ?></div>
                    <div class="stat-label">Загальний дохід</div>
                </div>
                <div class="stat-icon green">💰</div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Popular Courses -->
        <div class="col-md-6">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">📊 Популярні курси</h2>
                    <a href="courses.php" class="btn-view-all">Всі курси</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Курс</th>
                                <th>Записів</th>
                                <th>Дохід</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popularCourses as $course): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($course['title']) ?></strong><br>
                                    <small style="color: #999;"><?= htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) ?></small>
                                </td>
                                <td><?= $course['enrollments_count'] ?></td>
                                <td><?= formatPrice($course['revenue'] ?? 0) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="col-md-6">
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">💳 Останні платежі</h2>
                    <a href="payments.php" class="btn-view-all">Всі платежі</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Користувач</th>
                                <th>Курс</th>
                                <th>Сума</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPayments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></td>
                                <td>
                                    <small><?= htmlspecialchars(mb_substr($payment['course_title'], 0, 30)) ?><?= mb_strlen($payment['course_title']) > 30 ? '...' : '' ?></small>
                                </td>
                                <td><strong><?= formatPrice($payment['amount']) ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="section-card">
        <div class="section-header">
            <h2 class="section-title">👤 Нові користувачі</h2>
            <a href="users.php" class="btn-view-all">Всі користувачі</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Ім'я</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Дата реєстрації</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $user['role'] ?>">
                                <?php
                                $roles = ['student' => 'Учень', 'trainer' => 'Тренер', 'admin' => 'Адмін'];
                                echo $roles[$user['role']];
                                ?>
                            </span>
                        </td>
                        <td><?= formatDateTime($user['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>