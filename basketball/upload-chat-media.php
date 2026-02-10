<?php
require_once 'config.php';
require_once 'chat-media-config.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: chats.php');
    exit;
}

$chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$chatId) {
    setFlashMessage('error', 'Невірний ID чату');
    header('Location: chats.php');
    exit;
}

// Перевірка доступу до чату
$stmt = $db->prepare("
    SELECT id FROM chats 
    WHERE id = ? AND (student_id = ? OR trainer_id = ?)
");
$stmt->execute([$chatId, $userId, $userId]);

if (!$stmt->fetch()) {
    setFlashMessage('error', 'Немає доступу до цього чату');
    header('Location: chats.php');
    exit;
}

$messageType = 'text';
$mediaPath = null;
$mediaThumbnail = null;

// Обробка завантаження медіа файлу
if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['media'];
    
    // Перевірка помилок завантаження
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = getFileUploadError($file['error']);
        setFlashMessage('error', $errorMessage);
        header('Location: chat.php?id=' . $chatId);
        exit;
    }
    
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileType = $file['type'];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
    
    // Перевірка розміру файлу
    if ($fileSize > MAX_FILE_SIZE) {
        setFlashMessage('error', 'Файл занадто великий. Максимальний розмір: ' . formatFileSize(MAX_FILE_SIZE));
        header('Location: chat.php?id=' . $chatId);
        exit;
    }
    
    // Визначення та перевірка типу файлу
    $fileTypeCategory = isAllowedFileType($fileType, $fileExtension);
    
    if (!$fileTypeCategory) {
        setFlashMessage('error', 'Непідтримуваний формат файлу. Дозволені формати: JPG, PNG, GIF, WEBP, MP4, WEBM');
        header('Location: chat.php?id=' . $chatId);
        exit;
    }
    
    $messageType = $fileTypeCategory;
    
    // Додаткова валідація для відео
    if ($messageType === 'video' && isFFmpegAvailable()) {
        if (!validateVideoFile($fileTmp)) {
            setFlashMessage('error', 'Файл не є дійсним відео');
            header('Location: chat.php?id=' . $chatId);
            exit;
        }
    }
    
    // Створення директорії для завантаження, якщо не існує
    $uploadDir = CHAT_UPLOAD_DIR . $chatId . '/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            setFlashMessage('error', 'Не вдалося створити директорію для завантаження');
            header('Location: chat.php?id=' . $chatId);
            exit;
        }
    }
    
    // Генерація унікального імені файлу
    $newFileName = generateUniqueFileName($fileName);
    $uploadPath = $uploadDir . $newFileName;
    
    // Переміщення та обробка файлу
    if ($messageType === 'image' && COMPRESS_IMAGES) {
        // Стиснення зображення
        if (!compressImage($fileTmp, $uploadPath)) {
            setFlashMessage('error', 'Помилка при обробці зображення');
            header('Location: chat.php?id=' . $chatId);
            exit;
        }
    } else {
        // Звичайне переміщення файлу
        if (!move_uploaded_file($fileTmp, $uploadPath)) {
            setFlashMessage('error', 'Помилка при завантаженні файлу');
            header('Location: chat.php?id=' . $chatId);
            exit;
        }
    }
    
    $mediaPath = $uploadPath;
    
    // Створення мініатюри для відео
    if ($messageType === 'video' && isFFmpegAvailable()) {
        $thumbnailPath = generateVideoThumbnail($uploadPath, $uploadDir);
        if ($thumbnailPath) {
            $mediaThumbnail = $thumbnailPath;
        }
    }
}

// Перевірка чи є повідомлення або медіа
if (empty($message) && !$mediaPath) {
    setFlashMessage('error', 'Повідомлення не може бути порожнім');
    header('Location: chat.php?id=' . $chatId);
    exit;
}

// Збереження повідомлення в базу даних
try {
    $stmt = $db->prepare("
        INSERT INTO chat_messages (chat_id, sender_id, message, message_type, media_path, media_thumbnail, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$chatId, $userId, $message, $messageType, $mediaPath, $mediaThumbnail]);
    
    // Оновлення last_message_at в чаті
    $stmt = $db->prepare("UPDATE chats SET last_message_at = NOW() WHERE id = ?");
    $stmt->execute([$chatId]);
    
    setFlashMessage('success', 'Повідомлення відправлено');
} catch (Exception $e) {
    setFlashMessage('error', 'Помилка при відправці повідомлення');
}

header('Location: chat.php?id=' . $chatId);
exit;

/**
 * Генерація мініатюри для відео
 */
function generateVideoThumbnail($videoPath, $outputDir) {
    if (!isFFmpegAvailable()) {
        return null;
    }
    
    $thumbnailName = 'thumb_' . pathinfo($videoPath, PATHINFO_FILENAME) . '.jpg';
    $thumbnailPath = $outputDir . $thumbnailName;
    
    // Команда для створення мініатюри
    $command = FFMPEG_PATH . ' -i ' . escapeshellarg($videoPath) . 
               ' -ss ' . VIDEO_THUMBNAIL_TIME . 
               ' -vframes 1 -vf scale=' . VIDEO_THUMBNAIL_WIDTH . ':-1 ' . 
               escapeshellarg($thumbnailPath) . ' 2>&1';
    
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0 && file_exists($thumbnailPath)) {
        return $thumbnailPath;
    }
    
    return null;
}
?>