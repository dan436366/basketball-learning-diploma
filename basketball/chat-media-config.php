<?php
/**
 * Конфігурація для медіа файлів чату
 */

// Максимальний розмір файлу (в байтах)
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Дозволені MIME типи для зображень
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/jpg', 
    'image/png',
    'image/gif',
    'image/webp'
]);

// Дозволені MIME типи для відео
define('ALLOWED_VIDEO_TYPES', [
    'video/mp4',
    'video/mpeg',
    'video/quicktime',
    'video/x-msvideo',
    'video/webm'
]);

// Дозволені розширення для зображень
define('ALLOWED_IMAGE_EXTENSIONS', [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'webp'
]);

// Дозволені розширення для відео
define('ALLOWED_VIDEO_EXTENSIONS', [
    'mp4',
    'mpeg',
    'mpg',
    'mov',
    'avi',
    'webm'
]);

// Шлях до директорії завантажень
define('CHAT_UPLOAD_DIR', 'uploads/chat_media/');

// Шлях до FFmpeg (для генерації мініатюр відео)
define('FFMPEG_PATH', 'ffmpeg'); // або '/usr/bin/ffmpeg'

// Налаштування мініатюр відео
define('VIDEO_THUMBNAIL_WIDTH', 320);
define('VIDEO_THUMBNAIL_TIME', '00:00:01'); // Час для захоплення кадру

// Налаштування стиснення зображень (опціонально)
define('COMPRESS_IMAGES', false);
define('IMAGE_QUALITY', 85); // 0-100

// Функція для перевірки типу файлу
function isAllowedFileType($mimeType, $extension) {
    $extension = strtolower($extension);
    
    // Перевірка зображень
    if (in_array($mimeType, ALLOWED_IMAGE_TYPES) && 
        in_array($extension, ALLOWED_IMAGE_EXTENSIONS)) {
        return 'image';
    }
    
    // Перевірка відео
    if (in_array($mimeType, ALLOWED_VIDEO_TYPES) && 
        in_array($extension, ALLOWED_VIDEO_EXTENSIONS)) {
        return 'video';
    }
    
    return false;
}

// Функція для форматування розміру файлу
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Функція для генерації унікального імені файлу
function generateUniqueFileName($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid('media_' . time() . '_') . '.' . strtolower($extension);
}

// Функція для отримання повідомлень про помилки
function getFileUploadError($errorCode) {
    $errors = [
        UPLOAD_ERR_OK => 'Файл успішно завантажено',
        UPLOAD_ERR_INI_SIZE => 'Файл перевищує максимальний розмір (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE => 'Файл перевищує максимальний розмір (MAX_FILE_SIZE)',
        UPLOAD_ERR_PARTIAL => 'Файл завантажено частково',
        UPLOAD_ERR_NO_FILE => 'Файл не було завантажено',
        UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова директорія',
        UPLOAD_ERR_CANT_WRITE => 'Помилка запису файлу на диск',
        UPLOAD_ERR_EXTENSION => 'Завантаження файлу зупинено розширенням PHP'
    ];
    
    return $errors[$errorCode] ?? 'Невідома помилка завантаження';
}

// Функція для перевірки доступності FFmpeg
function isFFmpegAvailable() {
    $output = shell_exec(FFMPEG_PATH . ' -version 2>&1');
    return strpos($output, 'ffmpeg version') !== false;
}

// Функція для стиснення зображення
function compressImage($source, $destination, $quality = IMAGE_QUALITY) {
    if (!COMPRESS_IMAGES) {
        return copy($source, $destination);
    }
    
    $info = getimagesize($source);
    
    if ($info === false) {
        return false;
    }
    
    $image = null;
    
    switch ($info['mime']) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if ($image === null) {
        return false;
    }
    
    // Збереження стисненого зображення
    $result = imagejpeg($image, $destination, $quality);
    imagedestroy($image);
    
    return $result;
}

// Функція для валідації відео файлу
function validateVideoFile($filePath) {
    if (!isFFmpegAvailable()) {
        return true; // Якщо FFmpeg не доступний, пропускаємо валідацію
    }
    
    $command = FFMPEG_PATH . ' -i ' . escapeshellarg($filePath) . ' 2>&1';
    $output = shell_exec($command);
    
    // Перевірка чи це справді відео файл
    return strpos($output, 'Video:') !== false;
}

?>