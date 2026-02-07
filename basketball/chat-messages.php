<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];

$chatId = isset($_GET['chat_id']) ? (int)$_GET['chat_id'] : 0;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if (!$chatId) {
    echo json_encode(['success' => false, 'message' => 'Invalid chat ID']);
    exit;
}

// Перевірка доступу до чату
$stmt = $db->prepare("
    SELECT id FROM chats 
    WHERE id = ? AND (student_id = ? OR trainer_id = ?)
");
$stmt->execute([$chatId, $userId, $userId]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Отримання нових повідомлень
$stmt = $db->prepare("
    SELECT cm.*, u.first_name, u.last_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    WHERE cm.chat_id = ? AND cm.id > ?
    ORDER BY cm.created_at ASC
");
$stmt->execute([$chatId, $lastId]);
$messages = $stmt->fetchAll();

// Позначити як прочитані
if (!empty($messages)) {
    $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE chat_id = ? AND sender_id != ? AND id > ?");
    $stmt->execute([$chatId, $userId, $lastId]);
}

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'count' => count($messages)
]);