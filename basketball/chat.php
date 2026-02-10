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
    
    /* Медіа контент */
    .media-content {
        max-width: 300px;
        border-radius: 12px;
        overflow: hidden;
        margin-top: 8px;
        cursor: pointer;
        position: relative;
    }
    
    .media-content img {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .media-content video {
        width: 100%;
        height: auto;
        display: block;
    }
    
    .video-play-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 60px;
        height: 60px;
        background: rgba(0,0,0,0.6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        pointer-events: none;
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
        align-items: flex-end;
    }
    
    .message-input-wrapper {
        flex: 1;
        position: relative;
    }
    
    .message-input {
        width: 100%;
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
    
    .file-preview {
        display: flex;
        gap: 10px;
        padding: 10px;
        margin-bottom: 10px;
        background: #f8f9fa;
        border-radius: 15px;
        align-items: center;
    }
    
    .file-preview-image {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .file-preview-info {
        flex: 1;
    }
    
    .file-preview-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    
    .file-preview-size {
        font-size: 0.8rem;
        color: #999;
    }
    
    .file-preview-remove {
        background: #ff4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .file-preview-remove:hover {
        background: #cc0000;
        transform: scale(1.1);
    }
    
    .chat-actions {
        display: flex;
        gap: 10px;
    }
    
    .attach-btn {
        width: 50px;
        height: 50px;
        background: #f0f0f0;
        color: #667eea;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        position: relative;
    }
    
    .attach-btn:hover {
        background: #e0e0e0;
        transform: scale(1.05);
    }
    
    .attach-btn input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
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
    
    /* Модальне вікно для перегляду медіа */
    .media-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.95);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }
    
    .media-modal.active {
        display: flex;
    }
    
    .media-modal-content {
        max-width: 90%;
        max-height: 90%;
        position: relative;
    }
    
    .media-modal-content img,
    .media-modal-content video {
        max-width: 100%;
        max-height: 90vh;
        border-radius: 8px;
    }
    
    .media-modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255,255,255,0.9);
        color: #333;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        font-size: 2rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }
    
    .media-modal-close:hover {
        background: white;
        transform: scale(1.1);
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
        
        .media-content {
            max-width: 250px;
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
                            <?php if ($message['message_type'] === 'text'): ?>
                                <?= nl2br(htmlspecialchars($message['message'])) ?>
                            <?php elseif ($message['message_type'] === 'image'): ?>
                                <?php if (!empty($message['message'])): ?>
                                    <?= nl2br(htmlspecialchars($message['message'])) ?>
                                <?php endif; ?>
                                <div class="media-content" onclick="openMediaModal('<?= htmlspecialchars($message['media_path']) ?>', 'image')">
                                    <img src="<?= htmlspecialchars($message['media_path']) ?>" alt="Image">
                                </div>
                            <?php elseif ($message['message_type'] === 'video'): ?>
                                <?php if (!empty($message['message'])): ?>
                                    <?= nl2br(htmlspecialchars($message['message'])) ?>
                                <?php endif; ?>
                                <div class="media-content" onclick="openMediaModal('<?= htmlspecialchars($message['media_path']) ?>', 'video')">
                                    <?php if ($message['media_thumbnail']): ?>
                                        <img src="<?= htmlspecialchars($message['media_thumbnail']) ?>" alt="Video thumbnail">
                                    <?php else: ?>
                                        <video src="<?= htmlspecialchars($message['media_path']) ?>" preload="metadata"></video>
                                    <?php endif; ?>
                                    <div class="video-play-overlay">▶</div>
                                </div>
                            <?php endif; ?>
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
    <form method="POST" action="upload-chat-media.php" enctype="multipart/form-data" class="message-form" id="messageForm">
        <input type="hidden" name="chat_id" value="<?= $chatId ?>">
        
        <div class="message-input-wrapper">
            <div id="filePreview" class="file-preview" style="display: none;">
                <img id="previewImage" class="file-preview-image" style="display: none;">
                <video id="previewVideo" class="file-preview-image" style="display: none;" muted></video>
                <div class="file-preview-info">
                    <div class="file-preview-name" id="fileName"></div>
                    <div class="file-preview-size" id="fileSize"></div>
                </div>
                <button type="button" class="file-preview-remove" onclick="removeFile()">×</button>
            </div>
            
            <textarea 
                name="message" 
                id="messageInput" 
                class="message-input" 
                placeholder="Введіть повідомлення..."
                rows="1"
            ></textarea>
        </div>
        
        <div class="chat-actions">
            <label class="attach-btn" title="Прикріпити фото або відео">
                📎
                <input 
                    type="file" 
                    name="media" 
                    id="mediaInput" 
                    accept="image/*,video/*"
                    onchange="handleFileSelect(this)"
                >
            </label>
            
            <button type="submit" class="send-btn" id="sendBtn">
                ➤
            </button>
        </div>
    </form>
</div>

<!-- Модальне вікно для перегляду медіа -->
<div class="media-modal" id="mediaModal" onclick="closeMediaModal()">
    <button class="media-modal-close" onclick="closeMediaModal()">×</button>
    <div class="media-modal-content" onclick="event.stopPropagation()">
        <img id="modalImage" style="display: none;">
        <video id="modalVideo" controls style="display: none;"></video>
    </div>
</div>

<script>
let selectedFile = null;

// Автоматична прокрутка до останнього повідомлення
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    container.scrollTop = container.scrollHeight;
}

window.addEventListener('load', scrollToBottom);

// Обробка вибору файлу
function handleFileSelect(input) {
    const file = input.files[0];
    if (!file) return;
    
    selectedFile = file;
    
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const previewImage = document.getElementById('previewImage');
    const previewVideo = document.getElementById('previewVideo');
    
    // Показуємо назву та розмір
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    
    // Показуємо попередній перегляд
    const reader = new FileReader();
    reader.onload = function(e) {
        if (file.type.startsWith('image/')) {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
            previewVideo.style.display = 'none';
        } else if (file.type.startsWith('video/')) {
            previewVideo.src = e.target.result;
            previewVideo.style.display = 'block';
            previewImage.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);
    
    filePreview.style.display = 'flex';
    updateSendButton();
}

// Видалення файлу
function removeFile() {
    selectedFile = null;
    document.getElementById('mediaInput').value = '';
    document.getElementById('filePreview').style.display = 'none';
    updateSendButton();
}

// Форматування розміру файлу
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Автоматичне розширення textarea
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');

messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    updateSendButton();
});

// Оновлення стану кнопки відправки
function updateSendButton() {
    const hasText = messageInput.value.trim() !== '';
    const hasFile = selectedFile !== null;
    sendBtn.disabled = !hasText && !hasFile;
}

// Надсилання на Enter
messageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (this.value.trim() !== '' || selectedFile) {
            document.getElementById('messageForm').submit();
        }
    }
});

// Початковий стан кнопки
sendBtn.disabled = true;

// Відкриття модального вікна для медіа
function openMediaModal(src, type) {
    const modal = document.getElementById('mediaModal');
    const modalImage = document.getElementById('modalImage');
    const modalVideo = document.getElementById('modalVideo');
    
    if (type === 'image') {
        modalImage.src = src;
        modalImage.style.display = 'block';
        modalVideo.style.display = 'none';
        modalVideo.pause();
    } else if (type === 'video') {
        modalVideo.src = src;
        modalVideo.style.display = 'block';
        modalImage.style.display = 'none';
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Закриття модального вікна
function closeMediaModal() {
    const modal = document.getElementById('mediaModal');
    const modalVideo = document.getElementById('modalVideo');
    
    modal.classList.remove('active');
    modalVideo.pause();
    document.body.style.overflow = '';
}

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