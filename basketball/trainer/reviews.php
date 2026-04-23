<?php
require_once '../config.php';
requireRole('trainer');

$db = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

// Отримання всіх відгуків
$stmt = $db->prepare("
    SELECT r.*, c.title as course_title, u.first_name, u.last_name
    FROM reviews r
    JOIN courses c ON r.course_id = c.id
    JOIN users u ON r.user_id = u.id
    WHERE c.trainer_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$trainerId]);
$reviews = $stmt->fetchAll();

// Статистика відгуків
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews r
    JOIN courses c ON r.course_id = c.id
    WHERE c.trainer_id = ?
");
$stmt->execute([$trainerId]);
$stats = $stmt->fetch();

$pageTitle = 'Відгуки';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        padding: 60px 0;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .reviews-overview {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .rating-summary {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        text-align: center;
    }
    
    .avg-rating {
        font-size: 4rem;
        font-weight: 700;
        color: #fa709a;
        margin-bottom: 10px;
    }
    
    .rating-stars {
        font-size: 2rem;
        color: #ffc107;
        margin-bottom: 10px;
    }
    
    .total-reviews {
        color: #666;
        font-size: 1.1rem;
    }
    
    .rating-distribution {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .distribution-title {
        font-size: 1.3rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 20px;
    }
    
    .rating-bar {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .rating-label {
        min-width: 80px;
        color: #666;
        font-weight: 600;
    }
    
    .bar-container {
        flex: 1;
        height: 25px;
        background: #f0f0f0;
        border-radius: 20px;
        overflow: hidden;
    }
    
    .bar-fill {
        height: 100%;
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        border-radius: 20px;
        transition: width 0.3s;
    }
    
    .rating-count {
        min-width: 50px;
        text-align: right;
        color: #666;
        font-weight: 600;
    }
    
    .reviews-list {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 60px;
    }
    
    .list-header {
        padding: 25px;
        background: #f8f9fa;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .list-title {
        font-size: 1.5rem;
        color: #333;
        font-weight: 700;
    }
    
    .review-item {
        padding: 25px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .review-item:last-child {
        border-bottom: none;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .reviewer-info {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
        flex: 1;
    }
    
    .reviewer-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .reviewer-details {
        min-width: 0;
        overflow: hidden;
    }

    .reviewer-details h4 {
        font-size: 1rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 3px;
        word-break: break-word;
    }
    
    .review-course {
        color: #666;
        font-size: 0.9rem;
    }
    
    .review-rating {
        text-align: right;
    }
    
    .stars {
        color: #ffc107;
        font-size: 1.3rem;
        margin-bottom: 5px;
    }
    
    .review-date {
        color: #999;
        font-size: 0.85rem;
    }
    
    .review-comment {
        color: #666;
        line-height: 1.6;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #fa709a;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }
    
    .empty-icon {
        font-size: 5rem;
        margin-bottom: 20px;
    }
    
    .empty-state h2 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        color: #666;
        font-size: 1.1rem;
    }
    
    @media (max-width: 992px) {
        .reviews-overview {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575px) {
        .page-header { padding: 24px 0; margin-bottom: 20px; }
        .page-header h1 { font-size: 1.6rem; }
        .rating-summary { padding: 20px; }
        .avg-rating { font-size: 2.8rem; }
        .rating-distribution { padding: 16px; }
        .rating-bar { gap: 8px; }
        .rating-label { min-width: 60px; font-size: 0.85rem; }
        .review-item { padding: 16px; }
        .review-comment { padding: 10px 12px; font-size: 0.9rem; }
        .stars { font-size: 1rem; }
    }
</style>

<section class="page-header">
    <div class="container">
        <h1>⭐ Відгуки</h1>
        <p>Що кажуть ваші учні про курси</p>
    </div>
</section>

<div class="container">
    <?php if ($stats['total'] > 0): ?>
    <div class="reviews-overview">
        <div class="rating-summary">
            <div class="avg-rating">
                <?= number_format($stats['avg_rating'], 1) ?>
            </div>
            <div class="rating-stars">
                <?= str_repeat('⭐', round($stats['avg_rating'])) ?>
            </div>
            <div class="total-reviews">
                <?= $stats['total'] ?> відгуків
            </div>
        </div>
        
        <div class="rating-distribution">
            <h3 class="distribution-title">Розподіл оцінок</h3>
            
            <?php
            $ratings = [
                5 => $stats['five_stars'],
                4 => $stats['four_stars'],
                3 => $stats['three_stars'],
                2 => $stats['two_stars'],
                1 => $stats['one_star']
            ];
            
            foreach ($ratings as $stars => $count):
                $percentage = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0;
            ?>
            <div class="rating-bar">
                <div class="rating-label">
                    <?= str_repeat('⭐', $stars) ?>
                </div>
                <div class="bar-container">
                    <div class="bar-fill" style="width: <?= $percentage ?>%"></div>
                </div>
                <div class="rating-count"><?= $count ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="reviews-list">
        <div class="list-header">
            <h2 class="list-title">Всі відгуки</h2>
        </div>
        
        <?php if (empty($reviews)): ?>
            <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <h2>Поки немає відгуків</h2>
                <p>Коли учні залишать відгуки про ваші курси, вони з'являться тут</p>
            </div>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-item">
                <div class="review-header">
                    <div class="reviewer-info">
                        <div class="reviewer-avatar">
                            <?= strtoupper(mb_substr($review['first_name'], 0, 1)) ?>
                        </div>
                        <div class="reviewer-details">
                            <h4><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></h4>
                            <div class="review-course">
                                📚 <?= htmlspecialchars($review['course_title']) ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="review-rating">
                        <div class="stars">
                            <?= str_repeat('⭐', $review['rating']) ?>
                        </div>
                        <div class="review-date">
                            <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($review['comment']): ?>
                <div class="review-comment">
                    <?= nl2br(htmlspecialchars($review['comment'])) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>