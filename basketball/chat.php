<?php
require_once 'config.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$chatId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$chatId) {
    header('Location: chats.php');
    exit;
}

// Перевірка доступу до чату
if ($userRole === 'student') {
    $stmt = $db->prepare("
        SELECT c.*, 
               u.first_name as trainer_first_name, 
               u.last_name as trainer_last_name,
               co.title as course_title
        FROM chats c
        JOIN users u ON c.trainer_id = u.id
        JOIN courses co ON c.course_id = co.id
        WHERE c.id = ? AND c.student_id = ?
    ");
    $stmt->execute([$chatId, $userId]);
} else {
    $stmt = $db->prepare("
        SELECT c.*, 
               u.first_name as student_first_name, 
               u.last_name as student_last_name,
               co.title as course_title
        FROM chats c
        JOIN users u ON c.student_id = u.id
        JOIN courses co ON c.course_id = co.id
        WHERE c.id = ? AND c.trainer_id = ?
    ");
    $stmt->execute([$chatId, $userId]);
}

$chat = $stmt->fetch();

if (!$chat) {
    setFlashMessage('error', 'Чат не знайдено');
    header('Location: chats.php');
    exit;
}

// Позначити всі повідомлення як прочитані
$stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE chat_id = ? AND sender_id != ?");
$stmt->execute([$chatId, $userId]);

// Отримання повідомлень
$stmt = $db->prepare("
    SELECT cm.*, u.first_name, u.last_name
    FROM chat_messages cm
    JOIN users u ON cm.sender_id = u.id
    WHERE cm.chat_id = ?
    ORDER BY cm.created_at ASC
");
$stmt->execute([$chatId]);
$messages = $stmt->fetchAll();

// Обробка надсилання повідомлення
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $stmt = $db->prepare("
            INSERT INTO chat_messages (chat_id, sender_id, message, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$chatId, $userId, $message]);
        
        // Оновлення last_message_at в чаті
        $stmt = $db->prepare("UPDATE chats SET last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$chatId]);
        
        header('Location: chat.php?id=' . $chatId);
        exit;
    }
}

$pageTitle = 'Чат';
include 'includes/header.php';
?>

<style>
    body {
        background: #f5f5f5;
    }
    
    .chat-container {
        max-width: 1000px;
        margin: 20px auto;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 120px);
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .chat-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .back-btn {
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: white;
        font-size: 1.5rem;
        transition: all 0.3s;
    }
    
    .back-btn:hover {
        background: rgba(255,255,255,0.3);
        color: white;
    }
    
    .chat-header-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: white;
        color: #667eea;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        font-weight: 700;
    }
    
    .chat-header-info h2 {
        font-size: 1.3rem;
        margin-bottom: 3px;
        font-weight: 700;
    }
    
    .chat-header-course {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 25px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        background: #f8f9fa;
    }
    
    .message {
        display: flex;
        gap: 12px;
        max-width: 70%;
        animation: messageAppear 0.3s ease;
    }
    
    @keyframes messageAppear {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .message.own {
        align-self: flex-end;
        flex-direction: row-reverse;
    }
    
    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .message.own .message-avatar {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    
    .message-content {
        flex: 1;
    }
    
    .message-bubble {
        background: white;
        padding: 12px 16px;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        word-wrap: break-word;
        line-height: 1.5;
    }
    
    .message.own .message-bubble {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .message-time {
        font-size: 0.75rem;
        color: #999;
        margin-top: 5px;
        padding: 0 5px;
    }
    
    .message.own .message-time {
        text-align: right;
    }
    
    .message-form {
        padding: 20px;
        background: white;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .message-input {
        flex: 1;
        padding: 12px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 25px;
        font-size: 1rem;
        font-family: inherit;
        resize: none;
        max-height: 120px;
        transition: all 0.3s;
    }
    
    .message-input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .send-btn {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        flex-shrink: 0;
    }
    
    .send-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }
    
    .send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: scale(1);
    }
    
    .empty-messages {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }
    
    .empty-messages-icon {
        font-size: 4rem;
        margin-bottom: 15px;
    }
    
    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 80px);
            margin: 0;
            border-radius: 0;
        }
        
        .message {
            max-width: 85%;
        }
    }
</style>

<div class="chat-container">
    <!-- Header -->
    <div class="chat-header">
        <a href="chats.php" class="back-btn">←</a>
        <div class="chat-header-avatar">
            <?php
            if ($userRole === 'student') {
                echo strtoupper(mb_substr($chat['trainer_first_name'], 0, 1));
            } else {
                echo strtoupper(mb_substr($chat['student_first_name'], 0, 1));
            }
            ?>
        </div>
        <div class="chat-header-info">
            <h2>
                <?php
                if ($userRole === 'student') {
                    echo htmlspecialchars($chat['trainer_first_name'] . ' ' . $chat['trainer_last_name']);
                } else {
                    echo htmlspecialchars($chat['student_first_name'] . ' ' . $chat['student_last_name']);
                }
                ?>
            </h2>
            <div class="chat-header-course">
                📚 <?= htmlspecialchars($chat['course_title']) ?>
            </div>
        </div>
    </div>
    
    <!-- Messages -->
    <div class="messages-container" id="messagesContainer">
        <?php if (empty($messages)): ?>
            <div class="empty-messages">
                <div class="empty-messages-icon">💬</div>
                <p>Поки немає повідомлень</p>
                <p>Надішліть перше повідомлення</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <div class="message <?= $message['sender_id'] == $userId ? 'own' : '' ?>">
                    <div class="message-avatar">
                        <?= strtoupper(mb_substr($message['first_name'], 0, 1)) ?>
                    </div>
                    <div class="message-content">
                        <div class="message-bubble">
                            <?= nl2br(htmlspecialchars($message['message'])) ?>
                        </div>
                        <div class="message-time">
                            <?= date('d.m.Y H:i', strtotime($message['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Input Form -->
    <form method="POST" class="message-form" id="messageForm">
        <textarea 
            name="message" 
            id="messageInput" 
            class="message-input" 
            placeholder="Введіть повідомлення..."
            rows="1"
            required
        ></textarea>
        <button type="submit" class="send-btn" id="sendBtn">
            ➤
        </button>
    </form>
</div>

<script>
// Автоматична прокрутка до останнього повідомлення
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

// Прокрутка при завантаженні
window.addEventListener('load', scrollToBottom);

// Автоматичне розширення textarea
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');

messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    
    // Активація кнопки
    sendBtn.disabled = this.value.trim() === '';
});

// Надсилання на Enter (Shift+Enter для нового рядка)
messageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (this.value.trim() !== '') {
            document.getElementById('messageForm').submit();
        }
    }
});

// Початковий стан кнопки
sendBtn.disabled = true;

// Автооновлення повідомлень кожні 3 секунди
setInterval(function() {
    fetch('chat-messages.php?chat_id=<?= $chatId ?>&last_id=<?= !empty($messages) ? end($messages)['id'] : 0 ?>')
        .then(response => response.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                location.reload();
            }
        })
        .catch(error => console.error('Error:', error));
}, 3000);
</script>

<?php include 'includes/footer.php'; ?>