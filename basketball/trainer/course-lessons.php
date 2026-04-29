<?php
require_once '../config.php';
requireRole('trainer');

$courseId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$trainerId = $_SESSION['user_id'];

if (!$courseId) {
    header('Location: dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Перевірка доступу до курсу
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND trainer_id = ?");
$stmt->execute([$courseId, $trainerId]);
$course = $stmt->fetch();

if (!$course) {
    setFlashMessage('error', 'Курс не знайдено або у вас немає доступу');
    header('Location: dashboard.php');
    exit;
}

$errors = [];

// Обробка додавання уроку
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_lesson') {
        $title = sanitizeInput($_POST['title'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $videoUrl = sanitizeInput($_POST['video_url'] ?? '');
        $duration = intval($_POST['duration_minutes'] ?? 0);
        
        $videoFile = null;
        
        // Обробка завантаження відеофайлу
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
            $uploadResult = uploadFile($_FILES['video_file'], 'videos/', $allowedTypes);
            
            if ($uploadResult['success']) {
                $videoFile = $uploadResult['filename'];
            } else {
                $errors[] = $uploadResult['message'];
            }
        }
        
        // Перевірка: або файл, або URL
        if (empty($videoFile) && empty($videoUrl)) {
            $errors[] = 'Завантажте відеофайл або вкажіть URL';
        }
        
        if (!empty($title) && empty($errors)) {
            // Визначення порядкового номера
            $stmt = $db->prepare("SELECT MAX(order_number) as max_order FROM video_lessons WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $maxOrder = $stmt->fetch()['max_order'] ?? 0;
            
            $stmt = $db->prepare("
                INSERT INTO video_lessons (course_id, title, description, video_url, video_file, duration_minutes, order_number)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $courseId, 
                $title, 
                $description, 
                $videoUrl ? $videoUrl : null, 
                $videoFile, 
                $duration, 
                $maxOrder + 1
            ]);
            
            setFlashMessage('success', 'Урок успішно додано');
            header('Location: course-lessons.php?id=' . $courseId);
            exit;
        }
    } elseif ($_POST['action'] === 'delete_lesson') {
        $lessonId = intval($_POST['lesson_id'] ?? 0);
        
        // Отримуємо інформацію про урок для видалення файлу
        $stmt = $db->prepare("SELECT video_file FROM video_lessons WHERE id = ? AND course_id = ?");
        $stmt->execute([$lessonId, $courseId]);
        $lesson = $stmt->fetch();
        
        if ($lesson && $lesson['video_file']) {
            $filePath = UPLOAD_DIR . 'videos/' . $lesson['video_file'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM video_lessons WHERE id = ? AND course_id = ?");
        $stmt->execute([$lessonId, $courseId]);
        
        setFlashMessage('success', 'Урок видалено');
        header('Location: course-lessons.php?id=' . $courseId);
        exit;
    }
}

// Отримання уроків
$stmt = $db->prepare("SELECT * FROM video_lessons WHERE course_id = ? ORDER BY order_number ASC");
$stmt->execute([$courseId]);
$lessons = $stmt->fetchAll();

$pageTitle = 'Уроки курсу';
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
        margin-bottom: 10px;
    }
    
    .breadcrumb {
        color: rgba(255,255,255,0.8);
    }
    
    .breadcrumb a {
        color: white;
        text-decoration: none;
    }
    
    .main-content {
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 30px;
        margin-bottom: 60px;
        min-width: 0;
    }
    
    .lessons-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        min-width: 0;
        overflow: hidden;
    }
    
    .section-title {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 20px;
        font-weight: 700;
    }
    
    .lesson-item {
        padding: 20px;
        border: 2px solid #f0f0f0;
        border-radius: 12px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .lesson-item:hover {
        border-color: #f093fb;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    
    .lesson-header {
        display: flex;
        align-items: start;
        gap: 15px;
        margin-bottom: 12px;
    }
    
    .lesson-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .lesson-info {
        flex: 1;
    }
    
    .lesson-title {
        font-size: 1.2rem;
        color: #333;
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .lesson-meta {
        display: flex;
        gap: 15px;
        color: #666;
        font-size: 0.9rem;
        flex-wrap: wrap;
    }
    
    .lesson-description {
        color: #666;
        margin: 10px 0;
        line-height: 1.6;
    }
    
    .lesson-actions {
        display: flex;
        gap: 10px;
        margin-top: 12px;
    }
    
    .btn-sm {
        padding: 8px 15px;
        border-radius: 6px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete:hover {
        background: #c82333;
    }
    
    .add-lesson-form {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        position: sticky;
        top: 20px;
        min-width: 0;
        overflow: hidden;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
    }
    
    .form-input,
    .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    
    .form-textarea {
        min-height: 80px;
        resize: vertical;
        font-family: inherit;
    }
    
    .form-input:focus,
    .form-textarea:focus {
        border-color: #f093fb;
        outline: none;
    }
    
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-input-wrapper input[type=file] {
        position: absolute;
        left: -9999px;
    }
    
    .file-input-label {
        display: block;
        padding: 10px 12px;
        border: 2px dashed #e0e0e0;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #f8f9fa;
    }
    
    .file-input-label:hover {
        border-color: #f093fb;
        background: #fff0f8;
    }
    
    .file-input-label i {
        margin-right: 8px;
    }
    
    .file-name {
        margin-top: 8px;
        font-size: 0.9rem;
        color: #666;
        font-style: italic;
    }
    
    .divider {
        text-align: center;
        margin: 15px 0;
        color: #999;
        position: relative;
    }
    
    .divider::before,
    .divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 40%;
        height: 1px;
        background: #e0e0e0;
    }
    
    .divider::before {
        left: 0;
    }
    
    .divider::after {
        right: 0;
    }
    
    .btn-add {
        width: 100%;
        padding: 12px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(240, 147, 251, 0.4);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .empty-icon {
        font-size: 4rem;
        margin-bottom: 15px;
    }
    
    .badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .badge-file {
        background: #28a745;
        color: white;
    }
    
    .badge-url {
        background: #17a2b8;
        color: white;
    }
    
    .error-list {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    
    .error-list ul {
        margin: 0;
        padding-left: 20px;
        color: #721c24;
    }
    
    @media (max-width: 992px) {
        .main-content {
            grid-template-columns: 1fr;
        }
        .add-lesson-form {
            position: static;
        }
    }

    @media (max-width: 767px) {
        .page-header { padding: 20px 0; margin-bottom: 20px; overflow: hidden; }
        .page-header h1 { font-size: 1.3rem; word-break: break-word; }
        .breadcrumb { font-size: 0.8rem; }
        .main-content { gap: 16px; margin-bottom: 30px; }
        .lessons-section { padding: 14px; }
        .add-lesson-form { padding: 14px; }
        .section-title { font-size: 1.1rem; }
        .lesson-item { padding: 12px; }
        .lesson-title { font-size: 1rem; }
        .form-input, .form-textarea { font-size: 0.92rem; padding: 10px 12px; }
        .upload-area { padding: 16px 10px; }
        .upload-area p { font-size: 0.82rem; }
        .lesson-header { gap: 10px; }
        .lesson-number { width: 32px; height: 32px; font-size: 0.85rem; }
        /* Ім'я файлу не виїжджає */
        .lesson-meta span, .lesson-description { 
            word-break: break-all; 
            overflow-wrap: break-word;
        }
    }
</style>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard.php">Панель тренера</a> / <a href="courses.php">Курси</a> / Уроки
        </div>
        <h1>🎥 <?= htmlspecialchars($course['title']) ?></h1>
        <p>Управління уроками курсу</p>
    </div>
</section>

<div class="container">
    <div class="main-content">
        <!-- Lessons List -->
        <div class="lessons-section">
            <h2 class="section-title">📚 Уроки курсу (<?= count($lessons) ?>)</h2>
            
            <?php if (empty($lessons)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎥</div>
                    <h3>Ще немає уроків</h3>
                    <p>Додайте перший урок за допомогою форми справа</p>
                </div>
            <?php else: ?>
                <?php foreach ($lessons as $lesson): ?>
                <div class="lesson-item">
                    <div class="lesson-header">
                        <div class="lesson-number"><?= $lesson['order_number'] ?></div>
                        <div class="lesson-info">
                            <h3 class="lesson-title">
                                <?= htmlspecialchars($lesson['title']) ?>
                                <?php if ($lesson['video_file']): ?>
                                    <span class="badge badge-file">📁 Файл</span>
                                <?php elseif ($lesson['video_url']): ?>
                                    <span class="badge badge-url">🔗 URL</span>
                                <?php endif; ?>
                            </h3>
                            <div class="lesson-meta">
                                <?php if ($lesson['duration_minutes']): ?>
                                    <span>⏱️ <?= $lesson['duration_minutes'] ?> хв</span>
                                <?php endif; ?>
                                <?php if ($lesson['video_file']): ?>
                                    <span>📁 <?= htmlspecialchars($lesson['video_file']) ?></span>
                                <?php elseif ($lesson['video_url']): ?>
                                    <span>🔗 <a href="<?= htmlspecialchars($lesson['video_url']) ?>" target="_blank">Відео</a></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($lesson['description']): ?>
                    <div class="lesson-description">
                        <?= nl2br(htmlspecialchars($lesson['description'])) ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="lesson-actions">
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Ви впевнені?')">
                            <input type="hidden" name="action" value="delete_lesson">
                            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
                            <button type="submit" class="btn-sm btn-delete">🗑️ Видалити</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Add Lesson Form -->
        <div class="add-lesson-form">
            <h3 class="section-title">➕ Додати урок</h3>
            
            <?php if (!empty($errors)): ?>
            <div class="error-list">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_lesson">
                
                <div class="form-group">
                    <label class="form-label">Назва уроку *</label>
                    <input type="text" name="title" class="form-input" 
                           placeholder="Наприклад: Техніка ведення м'яча" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Опис</label>
                    <textarea name="description" class="form-textarea" 
                              placeholder="Опис того, що вивчатимуть у цьому уроці..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Завантажити відеофайл</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="video_file" id="video_file" accept="video/*" onchange="displayFileName()">
                        <label for="video_file" class="file-input-label">
                            <i>📁</i> Оберіть відеофайл
                        </label>
                    </div>
                    <div id="file-name" class="file-name"></div>
                    <small class="form-help">Підтримуються: MP4, AVI, MOV, WEBM (макс. 50MB)</small>
                </div>
                
                <div class="divider">або</div>
                
                <div class="form-group">
                    <label class="form-label">URL відео (YouTube, Vimeo)</label>
                    <input type="url" name="video_url" class="form-input" 
                           placeholder="https://youtube.com/watch?v=...">
                    <small class="form-help">Вставте посилання на відео з YouTube або Vimeo</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Тривалість (хвилини)</label>
                    <input type="number" name="duration_minutes" class="form-input" 
                           placeholder="15" min="0">
                </div>
                
                <button type="submit" class="btn-add">Додати урок</button>
            </form>
        </div>
    </div>
</div>

<script>
function displayFileName() {
    const input = document.getElementById('video_file');
    const fileNameDiv = document.getElementById('file-name');
    
    if (input.files.length > 0) {
        const fileName = input.files[0].name;
        const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
        fileNameDiv.textContent = `Обрано: ${fileName} (${fileSize} MB)`;
    } else {
        fileNameDiv.textContent = '';
    }
}
</script>

<?php include '../includes/footer.php'; ?>