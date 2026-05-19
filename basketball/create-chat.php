<?php
/**
 * create-chat.php — створення чату студента з тренером
 * Викликається з trainers.php кнопкою "Написати тренеру"
 */
require_once 'config.php';
requireRole('student');

$db       = Database::getInstance()->getConnection();
$userId   = $_SESSION['user_id'];
$trainerId = isset($_GET['trainer_id']) ? (int)$_GET['trainer_id'] : 0;

if (!$trainerId) {
    header('Location: trainers.php');
    exit;
}

// Перевіряємо що тренер існує
$stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE id=? AND role='trainer' AND is_active=1");
$stmt->execute([$trainerId]);
$trainer = $stmt->fetch();

if (!$trainer) {
    setFlashMessage('error', 'Тренера не знайдено');
    header('Location: trainers.php');
    exit;
}

// Перевіряємо чи вже є загальний чат між студентом і тренером
$stmt = $db->prepare("
    SELECT id FROM chats
    WHERE student_id=? AND trainer_id=? AND chat_type='general'
    LIMIT 1
");
$stmt->execute([$userId, $trainerId]);
$existingChat = $stmt->fetch();

if ($existingChat) {
    // Чат вже є — переходимо туди
    header('Location: chat.php?id=' . $existingChat['id']);
    exit;
}

// Створюємо новий загальний чат
$stmt = $db->prepare("
    INSERT INTO chats (student_id, trainer_id, course_id, chat_type, subject, created_at, last_message_at)
    VALUES (?, ?, NULL, 'general', 'Індивідуальна консультація', NOW(), NOW())
");
$stmt->execute([$userId, $trainerId]);
$chatId = $db->lastInsertId();

// Автоматичне перше повідомлення
$stmt = $db->prepare("
    INSERT INTO chat_messages (chat_id, sender_id, message, message_type, created_at)
    VALUES (?, ?, ?, 'text', NOW())
");
$stmt->execute([
    $chatId,
    $userId,
    'Вітаю! Я хотів би отримати індивідуальний курс від вас. Чи можемо обговорити деталі?'
]);

setFlashMessage('success', 'Чат з тренером ' . $trainer['first_name'] . ' ' . $trainer['last_name'] . ' створено!');
header('Location: chat.php?id=' . $chatId);
exit;