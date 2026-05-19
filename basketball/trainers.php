<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Пошук
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT u.*,
           COUNT(DISTINCT c.id) as courses_count,
           COUNT(DISTINCT e.id) as students_count,
           AVG(r.rating) as avg_rating
    FROM users u
    LEFT JOIN courses c ON u.id = c.trainer_id AND c.is_active = 1
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN reviews r ON c.id = r.course_id
    WHERE u.role = 'trainer' AND u.is_active = 1
";
$params = [];
if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= " GROUP BY u.id ORDER BY courses_count DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trainers = $stmt->fetchAll();

$pageTitle = 'Наші тренери';
include 'includes/header.php';
?>

<style>
    .trainers-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 60px 0;
        text-align: center;
    }
    
    .trainers-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 15px;
        font-weight: 700;
    }
    
    .trainers-hero p {
        font-size: 1.2rem;
        opacity: 0.95;
    }
    
    .trainers-section {
        padding: 60px 0;
    }

    .trainers-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }

    @media (max-width: 768px) {
        .trainers-hero h1 { font-size: 1.8rem; }
        .trainers-hero p { font-size: 1rem; }
        .trainers-section { padding: 30px 0; }
        .trainers-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }
    
    .trainer-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    
    .trainer-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    
    .trainer-header {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        align-items: flex-start;
        min-width: 0;
    }
    
    .trainer-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    @media (min-width: 769px) {
        .trainer-avatar {
            width: 90px;
            height: 90px;
            font-size: 2.2rem;
        }
    }
    
    .trainer-info {
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }
    
    .trainer-name {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 6px;
        font-weight: 700;
        word-break: break-word;
    }

    @media (min-width: 769px) {
        .trainer-name { font-size: 1.5rem; }
    }
    
    .trainer-experience {
        color: #667eea;
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 8px;
    }
    
    .trainer-stats {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 8px;
    }
    
    .stat-item {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #666;
        font-size: 0.9rem;
        white-space: nowrap;
    }
    
    .stat-item strong {
        color: #333;
        font-size: 1rem;
    }
    
    .trainer-bio {
        color: #555;
        line-height: 1.7;
        margin: 15px 0;
        flex-grow: 1;
        word-break: break-word;
    }
    
    .trainer-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 15px 0;
        padding: 10px 12px;
        background: #f8f9fa;
        border-radius: 8px;
        flex-wrap: wrap;
    }
    
    .rating-stars {
        color: #ffc107;
        font-size: 1.1rem;
    }
    
    .rating-text {
        color: #666;
        font-size: 0.9rem;
    }
    
    .btn-view-courses {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
        display: block;
        transition: all 0.3s;
        margin-top: auto;
        box-sizing: border-box;
    }
    
    .btn-view-courses:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .btn-contact {
        width: 100%;
        padding: 11px;
        background: white;
        color: #667eea;
        border: 2px solid #667eea;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
        display: block;
        transition: all 0.3s;
        margin-top: 10px;
        box-sizing: border-box;
    }

    .btn-contact:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    /* Пошук */
    .search-box {
        max-width: 480px;
        margin: 0 auto 30px;
    }

    .search-form {
        display: flex;
        gap: 10px;
        background: white;
        padding: 8px;
        border-radius: 50px;
        box-shadow: 0 4px 20px rgba(0,0,0,.12);
    }

    .search-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 8px 16px;
        font-size: 1rem;
        background: transparent;
        min-width: 0;
    }

    .search-btn {
        padding: 10px 24px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 40px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: all .2s;
    }

    .search-btn:hover { opacity: .9; }

    .search-result-count {
        text-align: center;
        color: rgba(255,255,255,.8);
        font-size: .9rem;
        margin-top: -10px;
        margin-bottom: 20px;
    }
    
    .no-trainers {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
    }
    
    .no-trainers h3 {
        color: #333;
        margin-bottom: 15px;
        font-size: 1.8rem;
    }
</style>

<!-- Hero Section -->
<section class="trainers-hero">
    <div class="container">
        <h1>👨‍🏫 Наші тренери</h1>
        <p>Професіонали з багаторічним досвідом готові допомогти вам</p>

        <!-- Пошук -->
        <div class="search-box">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input"
                       placeholder="Пошук тренера за іменем..."
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">🔍 Знайти</button>
            </form>
        </div>

        <?php if ($search): ?>
        <div class="search-result-count">
            Знайдено: <strong><?= count($trainers) ?></strong> тренер(ів) за запитом "<?= htmlspecialchars($search) ?>"
            — <a href="trainers.php" style="color:white;text-decoration:underline;">скинути</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Trainers Section -->
<section class="trainers-section">
    <div class="container">
        <?php if (empty($trainers)): ?>
            <div class="no-trainers">
                <h3><?= $search ? 'Тренерів не знайдено' : 'Тренерів поки немає' ?></h3>
                <p><?= $search ? 'Спробуйте інший запит' : 'Скоро з\'являться нові тренери' ?></p>
                <?php if ($search): ?>
                <a href="trainers.php" style="display:inline-block;margin-top:12px;padding:10px 24px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;">Показати всіх тренерів</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="trainers-grid">
                <?php foreach ($trainers as $trainer): ?>
                <div>
                    <div class="trainer-card">
                        <div class="trainer-header">
                            <div class="trainer-avatar">
                                <?= strtoupper(mb_substr($trainer['first_name'], 0, 1)) ?>
                            </div>
                            <div class="trainer-info">
                                <h2 class="trainer-name">
                                    <?= htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']) ?>
                                </h2>
                                <?php if ($trainer['experience_years']): ?>
                                <div class="trainer-experience">
                                    📊 Досвід роботи: <?= $trainer['experience_years'] ?> років
                                </div>
                                <?php endif; ?>
                                <div class="trainer-stats">
                                    <div class="stat-item">
                                        📚 <strong><?= $trainer['courses_count'] ?></strong> курсів
                                    </div>
                                    <div class="stat-item">
                                        👥 <strong><?= $trainer['students_count'] ?></strong> учнів
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($trainer['avg_rating']): ?>
                        <div class="trainer-rating">
                            <div class="rating-stars">
                                <?= str_repeat('⭐', round($trainer['avg_rating'])) ?>
                            </div>
                            <div class="rating-text">
                                <?= number_format($trainer['avg_rating'], 1) ?> / 5.0
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($trainer['bio']): ?>
                        <p class="trainer-bio"><?= htmlspecialchars($trainer['bio']) ?></p>
                        <?php endif; ?>

                        <?php if ($trainer['courses_count'] > 0): ?>
                        <a href="courses.php?trainer=<?= $trainer['id'] ?>" class="btn-view-courses">
                            Переглянути курси тренера
                        </a>
                        <?php endif; ?>

                        <!-- Кнопка написати тренеру -->
                        <?php if (isLoggedIn() && $_SESSION['user_role'] === 'student'): ?>
                            <a href="create-chat.php?trainer_id=<?= $trainer['id'] ?>" class="btn-contact">
                                💬 Написати тренеру
                            </a>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="login.php" class="btn-contact">
                                💬 Увійдіть щоб написати
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>