<?php
require_once '../config.php';
requireRole('student');

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$courseId) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

// Перевірка, чи студент записаний на курс
$stmt = $db->prepare("
    SELECT c.trainer_id 
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.course_id = ?
");
$stmt->execute([$userId, $courseId]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
    exit;
}

$trainerId = $enrollment['trainer_id'];

// Перевірка, чи вже існує чат
$stmt = $db->prepare("
    SELECT id FROM chats 
    WHERE student_id = ? AND trainer_id = ? AND course_id = ?
");
$stmt->execute([$userId, $trainerId, $courseId]);
$existingChat = $stmt->fetch();

if ($existingChat) {
    echo json_encode(['success' => true, 'chat_id' => $existingChat['id']]);
    exit;
}

// Створення нового чату
try {
    $stmt = $db->prepare("
        INSERT INTO chats (student_id, trainer_id, course_id, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $trainerId, $courseId]);
    
    $chatId = $db->lastInsertId();
    
    echo json_encode(['success' => true, 'chat_id' => $chatId]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to create chat']);
}