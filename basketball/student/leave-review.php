<?php
require_once '../config.php';
requireRole('student');

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$courseId) {
    header('Location: my-courses.php');
    exit;
}

// Перевірка, чи завершив студент курс
$stmt = $db->prepare("
    SELECT e.*, c.title, c.trainer_id, u.first_name, u.last_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.trainer_id = u.id
    WHERE e.user_id = ? AND e.course_id = ? AND e.completed_at IS NOT NULL
");
$stmt->execute([$userId, $courseId]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    setFlashMessage('error', 'Ви можете залишити відгук тільки після завершення курсу');
    header('Location: my-courses.php');
    exit;
}

// Перевірка, чи вже є відгук
$stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ? AND course_id = ?");
$stmt->execute([$userId, $courseId]);
$existingReview = $stmt->fetch();

if ($existingReview) {
    setFlashMessage('error', 'Ви вже залишили відгук для цього курсу');
    header('Location: my-courses.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = sanitizeInput($_POST['comment'] ?? '');
    
    // Валідація
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Оберіть рейтинг від 1 до 5 зірок';
    }
    
    if (empty($comment)) {
        $errors[] = 'Напишіть ваш відгук';
    }
    
    if (strlen($comment) < 10) {
        $errors[] = 'Відгук повинен містити щонайменше 10 символів';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO reviews (user_id, course_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $courseId, $rating, $comment]);
            
            setFlashMessage('success', 'Дякуємо за ваш відгук!');
            header('Location: my-courses.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Помилка збереження відгуку. Спробуйте пізніше.';
        }
    }
}

$pageTitle = 'Залишити відгук';
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
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .breadcrumb {
        color: rgba(255,255,255,0.8);
        margin-bottom: 10px;
    }
    
    .breadcrumb a {
        color: white;
        text-decoration: none;
    }
    
    .review-container {
        max-width: 800px;
        margin: 0 auto 60px;
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .course-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }
    
    .course-title-review {
        font-size: 1.5rem;
        color: #333;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .course-trainer-review {
        color: #666;
        font-size: 1rem;
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .section-title {
        font-size: 1.3rem;
        color: #333;
        font-weight: 600;
        margin-bottom: 20px;
    }
    
    .rating-selector {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-bottom: 30px;
    }
    
    .star-input {
        display: none;
    }
    
    .star-label {
        font-size: 3rem;
        color: #ddd;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .star-label:hover,
    .star-label.active {
        color: #ffc107;
        transform: scale(1.2);
    }
    
    .rating-text {
        text-align: center;
        font-size: 1.2rem;
        color: #666;
        margin-top: 15px;
        font-weight: 600;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
        font-size: 1.1rem;
    }
    
    .form-textarea {
        width: 100%;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1rem;
        min-height: 200px;
        resize: vertical;
        font-family: inherit;
        transition: all 0.3s;
    }
    
    .form-textarea:focus {
        border-color: #fa709a;
        outline: none;
        box-shadow: 0 0 0 3px rgba(250, 112, 154, 0.1);
    }
    
    .form-help {
        font-size: 0.9rem;
        color: #666;
        margin-top: 8px;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }
    
    .btn-submit {
        flex: 1;
        padding: 15px 30px;
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(250, 112, 154, 0.4);
    }
    
    .btn-submit:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .btn-cancel {
        padding: 15px 30px;
        background: white;
        color: #666;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-block;
    }
    
    .btn-cancel:hover {
        background: #f8f9fa;
        color: #666;
    }
    
    .error-list {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 25px;
    }
    
    .error-list ul {
        margin: 0;
        padding-left: 20px;
        color: #721c24;
    }
    
    .tips-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .tips-title {
        font-weight: 700;
        color: #856404;
        margin-bottom: 10px;
    }
    
    .tips-list {
        margin: 0;
        padding-left: 20px;
        color: #856404;
    }
</style>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="my-courses.php">Мої курси</a> / Залишити відгук
        </div>
        <h1>⭐ Залишити відгук</h1>
        <p>Поділіться своїм досвідом навчання</p>
    </div>
</section>

<div class="container">
    <div class="review-container">
        <div class="course-info">
            <h2 class="course-title-review"><?= htmlspecialchars($enrollment['title']) ?></h2>
            <div class="course-trainer-review">
                👨‍🏫 Тренер: <?= htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']) ?>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="error-list">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" id="reviewForm">
            <!-- Rating -->
            <div class="form-section">
                <h3 class="section-title">Ваша оцінка</h3>
                
                <div class="rating-selector">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" class="star-input" required>
                    <label for="star<?= $i ?>" class="star-label" data-rating="<?= $i ?>">⭐</label>
                    <?php endfor; ?>
                </div>
                
                <div class="rating-text" id="ratingText">Оберіть рейтинг</div>
            </div>
            
            <!-- Comment -->
            <div class="form-section">
                <h3 class="section-title">Ваш відгук</h3>
                
                <div class="tips-box">
                    <div class="tips-title">💡 Корисні поради для відгуку:</div>
                    <ul class="tips-list">
                        <li>Опишіть, що вам сподобалось у курсі</li>
                        <li>Розкажіть, які навички ви отримали</li>
                        <li>Поділіться, як курс допоміг вам</li>
                        <li>Напишіть про якість матеріалів та подачу</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ваш коментар</label>
                    <textarea name="comment" id="comment" class="form-textarea" 
                              placeholder="Розкажіть детальніше про ваш досвід навчання на цьому курсі..." 
                              required></textarea>
                    <div class="form-help">
                        Мінімум 10 символів. Символів: <span id="charCount">0</span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    Опублікувати відгук
                </button>
                <a href="my-courses.php" class="btn-cancel">Скасувати</a>
            </div>
        </form>
    </div>
</div>

<script>
// Рейтинг
const stars = document.querySelectorAll('.star-label');
const ratingInputs = document.querySelectorAll('.star-input');
const ratingText = document.getElementById('ratingText');
let selectedRating = 0;

const ratingTexts = {
    1: '⭐ Погано',
    2: '⭐⭐ Нормально',
    3: '⭐⭐⭐ Добре',
    4: '⭐⭐⭐⭐ Дуже добре',
    5: '⭐⭐⭐⭐⭐ Відмінно!'
};

stars.forEach((star, index) => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        updateStars();
        updateSubmitButton();
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.rating);
        highlightStars(rating);
    });
});

document.querySelector('.rating-selector').addEventListener('mouseleave', function() {
    highlightStars(selectedRating);
});

function highlightStars(rating) {
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
    
    if (rating > 0) {
        ratingText.textContent = ratingTexts[rating];
    } else {
        ratingText.textContent = 'Оберіть рейтинг';
    }
}

function updateStars() {
    ratingInputs.forEach((input, index) => {
        if (parseInt(input.value) === selectedRating) {
            input.checked = true;
        }
    });
    highlightStars(selectedRating);
}

// Підрахунок символів
const commentTextarea = document.getElementById('comment');
const charCount = document.getElementById('charCount');
const submitBtn = document.getElementById('submitBtn');

commentTextarea.addEventListener('input', function() {
    const count = this.value.length;
    charCount.textContent = count;
    updateSubmitButton();
});

function updateSubmitButton() {
    const commentLength = commentTextarea.value.length;
    const hasRating = selectedRating > 0;
    const hasComment = commentLength >= 10;
    
    submitBtn.disabled = !(hasRating && hasComment);
}
</script>

<?php include '../includes/footer.php'; ?>