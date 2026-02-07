<?php
require_once '../config.php';
requireRole('trainer');

$db = Database::getInstance()->getConnection();
$trainerId = $_SESSION['user_id'];

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$courseId) {
    header('Location: courses.php');
    exit;
}

// Перевірка доступу до курсу
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND trainer_id = ?");
$stmt->execute([$courseId, $trainerId]);
$course = $stmt->fetch();

if (!$course) {
    setFlashMessage('error', 'Курс не знайдено або у вас немає доступу');
    header('Location: courses.php');
    exit;
}

$errors = [];
$formData = $course;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title' => sanitizeInput($_POST['title'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'duration_weeks' => intval($_POST['duration_weeks'] ?? 0),
        'level' => sanitizeInput($_POST['level'] ?? 'beginner'),
        'is_free' => isset($_POST['is_free']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Валідація
    if (empty($formData['title'])) {
        $errors[] = 'Введіть назву курсу';
    }
    
    if (empty($formData['description'])) {
        $errors[] = 'Введіть опис курсу';
    }
    
    if (!$formData['is_free'] && $formData['price'] <= 0) {
        $errors[] = 'Вартість повинна бути більше 0 або позначте курс як безкоштовний';
    }
    
    if ($formData['duration_weeks'] <= 0) {
        $errors[] = 'Тривалість повинна бути більше 0';
    }
    
    if (!in_array($formData['level'], ['beginner', 'intermediate', 'advanced'])) {
        $errors[] = 'Оберіть коректний рівень';
    }
    
    if (empty($errors)) {
        try {
            if ($formData['is_free']) {
                $formData['price'] = 0;
            }
            
            $stmt = $db->prepare("
                UPDATE courses 
                SET title = ?, description = ?, price = ?, is_free = ?, 
                    duration_weeks = ?, level = ?, is_active = ?
                WHERE id = ? AND trainer_id = ?
            ");
            
            $stmt->execute([
                $formData['title'],
                $formData['description'],
                $formData['price'],
                $formData['is_free'],
                $formData['duration_weeks'],
                $formData['level'],
                $formData['is_active'],
                $courseId,
                $trainerId
            ]);
            
            setFlashMessage('success', 'Курс успішно оновлено!');
            header('Location: courses.php');
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Помилка оновлення курсу. Спробуйте пізніше.';
        }
    }
}

$pageTitle = 'Редагувати курс';
include '../includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .breadcrumb {
        color: rgba(255,255,255,0.8);
        margin-bottom: 10px;
    }
    
    .breadcrumb a {
        color: white;
        text-decoration: none;
    }
    
    .form-container {
        max-width: 900px;
        margin: 0 auto 60px;
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .form-section-title {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 600;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-label.required::after {
        content: ' *';
        color: #dc3545;
    }
    
    .form-input,
    .form-textarea {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
    }
    
    .form-textarea {
        min-height: 150px;
        resize: vertical;
        font-family: inherit;
    }
    
    .form-input:focus,
    .form-textarea:focus {
        border-color: #f093fb;
        outline: none;
        box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
    }
    
    .form-help {
        font-size: 0.9rem;
        color: #666;
        margin-top: 5px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .checkbox-wrapper {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    
    .checkbox-label {
        font-weight: 600;
        color: #333;
        cursor: pointer;
    }
    
    .level-options {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
    }
    
    .level-option {
        position: relative;
    }
    
    .level-option input[type="radio"] {
        display: none;
    }
    
    .level-option label {
        display: block;
        padding: 20px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
        text-align: center;
    }
    
    .level-option input[type="radio"]:checked + label {
        border-color: #f093fb;
        background: #fff0f8;
    }
    
    .level-option label:hover {
        border-color: #f093fb;
    }
    
    .level-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    
    .level-name {
        font-weight: 600;
        color: #333;
    }
    
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 2px solid #f0f0f0;
    }
    
    .btn-submit {
        padding: 15px 40px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(240, 147, 251, 0.4);
    }
    
    .btn-cancel {
        padding: 15px 40px;
        background: white;
        color: #666;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s;
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
</style>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php">Панель тренера</a> / 
            <a href="courses.php">Курси</a> / 
            Редагувати
        </div>
        <h1>✏️ Редагувати курс</h1>
    </div>
</section>

<div class="container">
    <div class="form-container">
        <?php if (!empty($errors)): ?>
        <div class="error-list">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Basic Info -->
            <div class="form-section">
                <h2 class="form-section-title">Основна інформація</h2>
                
                <div class="form-group">
                    <label class="form-label required">Назва курсу</label>
                    <input type="text" name="title" class="form-input" 
                           value="<?= htmlspecialchars($formData['title']) ?>" required>
                    <div class="form-help">Коротка та зрозуміла назва курсу</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Опис курсу</label>
                    <textarea name="description" class="form-textarea" required><?= htmlspecialchars($formData['description']) ?></textarea>
                    <div class="form-help">Опишіть програму, цілі та результати навчання</div>
                </div>
            </div>
            
            <!-- Course Details -->
            <div class="form-section">
                <h2 class="form-section-title">Деталі курсу</h2>
                
                <div class="checkbox-wrapper">
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_free" id="is_free" 
                               <?= $formData['is_free'] ? 'checked' : '' ?>
                               onchange="togglePriceField()">
                        <label for="is_free" class="checkbox-label">🎁 Безкоштовний курс</label>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_active" id="is_active" 
                               <?= $formData['is_active'] ? 'checked' : '' ?>>
                        <label for="is_active" class="checkbox-label">✅ Курс активний</label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" id="price-label">Вартість (грн)</label>
                        <input type="number" name="price" id="price-input" class="form-input" 
                               value="<?= htmlspecialchars($formData['price']) ?>"
                               min="0" step="0.01">
                        <div class="form-help">Вартість курсу в гривнях</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required">Тривалість (тижнів)</label>
                        <input type="number" name="duration_weeks" class="form-input" 
                               value="<?= htmlspecialchars($formData['duration_weeks']) ?>"
                               min="1" required>
                        <div class="form-help">Рекомендована тривалість курсу</div>
                    </div>
                </div>
            </div>
            
            <!-- Level -->
            <div class="form-section">
                <h2 class="form-section-title">Рівень складності</h2>
                
                <div class="level-options">
                    <div class="level-option">
                        <input type="radio" name="level" id="level-beginner" value="beginner" 
                               <?= $formData['level'] === 'beginner' ? 'checked' : '' ?>>
                        <label for="level-beginner">
                            <div class="level-icon">🌱</div>
                            <div class="level-name">Початковий</div>
                        </label>
                    </div>
                    
                    <div class="level-option">
                        <input type="radio" name="level" id="level-intermediate" value="intermediate"
                               <?= $formData['level'] === 'intermediate' ? 'checked' : '' ?>>
                        <label for="level-intermediate">
                            <div class="level-icon">🌿</div>
                            <div class="level-name">Середній</div>
                        </label>
                    </div>
                    
                    <div class="level-option">
                        <input type="radio" name="level" id="level-advanced" value="advanced"
                               <?= $formData['level'] === 'advanced' ? 'checked' : '' ?>>
                        <label for="level-advanced">
                            <div class="level-icon">🌳</div>
                            <div class="level-name">Просунутий</div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">💾 Зберегти зміни</button>
                <a href="courses.php" class="btn-cancel">Скасувати</a>
            </div>
        </form>
    </div>
</div>

<script>
function togglePriceField() {
    const isFree = document.getElementById('is_free').checked;
    const priceInput = document.getElementById('price-input');
    const priceLabel = document.getElementById('price-label');
    
    if (isFree) {
        priceInput.value = '0';
        priceInput.disabled = true;
        priceInput.style.opacity = '0.5';
        priceLabel.textContent = 'Вартість (безкоштовно)';
    } else {
        priceInput.disabled = false;
        priceInput.style.opacity = '1';
        priceLabel.textContent = 'Вартість (грн)';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    togglePriceField();
});
</script>

<?php include '../includes/footer.php'; ?>