<?php
require_once 'config.php';
requireLogin();

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Отримання всіх чатів користувача
if ($userRole === 'student') {
    $stmt = $db->prepare("
        SELECT c.*, 
               u.first_name as trainer_first_name, 
               u.last_name as trainer_last_name,
               co.title as course_title,
               (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id AND sender_id != ? AND is_read = 0) as unread_count,
               (SELECT message FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM chats c
        JOIN users u ON c.trainer_id = u.id
        JOIN courses co ON c.course_id = co.id
        WHERE c.student_id = ?
        ORDER BY c.last_message_at DESC, c.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
} else {
    $stmt = $db->prepare("
        SELECT c.*, 
               u.first_name as student_first_name, 
               u.last_name as student_last_name,
               co.title as course_title,
               (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id AND sender_id != ? AND is_read = 0) as unread_count,
               (SELECT message FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM chats c
        JOIN users u ON c.student_id = u.id
        JOIN courses co ON c.course_id = co.id
        WHERE c.trainer_id = ?
        ORDER BY c.last_message_at DESC, c.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
}

$chats = $stmt->fetchAll();

// Підрахунок загальної кількості непрочитаних
$totalUnread = 0;
foreach ($chats as $chat) {
    $totalUnread += $chat['unread_count'];
}

$pageTitle = 'Мої чати';
include 'includes/header.php';
?>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .chats-container {
        max-width: 1200px;
        margin: 0 auto 60px;
    }
    
    .chats-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .chats-title {
        font-size: 1.5rem;
        color: #333;
        font-weight: 700;
    }
    
    .unread-badge {
        background: #dc3545;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 700;
    }
    
    .chats-list {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .chat-item {
        padding: 20px 25px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 20px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
    }
    
    .chat-item:hover {
        background: #f8f9fa;
    }
    
    .chat-item:last-child {
        border-bottom: none;
    }
    
    .chat-item.unread {
        background: #f8f9ff;
    }
    
    .chat-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        flex-shrink: 0;
        position: relative;
    }
    
    .chat-avatar .unread-indicator {
        position: absolute;
        top: -5px;
        right: -5px;
        width: 25px;
        height: 25px;
        background: #dc3545;
        border: 3px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
    }
    
    .chat-info {
        flex: 1;
        min-width: 0;
    }
    
    .chat-header-info {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 8px;
    }
    
    .chat-name {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 3px;
    }
    
    .chat-course {
        font-size: 0.9rem;
        color: #667eea;
    }
    
    .chat-time {
        font-size: 0.85rem;
        color: #999;
        white-space: nowrap;
    }
    
    .chat-last-message {
        color: #666;
        font-size: 0.95rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .chat-last-message.unread {
        font-weight: 600;
        color: #333;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
    }
    
    .empty-icon {
        font-size: 5rem;
        margin-bottom: 20px;
    }
    
    .empty-state h2 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        color: #666;
        font-size: 1.1rem;
    }
</style>

<section class="page-header">
    <div class="container">
        <h1>💬 Мої чати</h1>
        <p>Спілкуйтесь з <?= $userRole === 'student' ? 'тренерами' : 'учнями' ?></p>
    </div>
</section>

<div class="container">
    <div class="chats-container">
        <?php if (!empty($chats)): ?>
            <div class="chats-header">
                <h2 class="chats-title">Всі чати</h2>
                <?php if ($totalUnread > 0): ?>
                    <span class="unread-badge"><?= $totalUnread ?> непрочитаних</span>
                <?php endif; ?>
            </div>
            
            <div class="chats-list">
                <?php foreach ($chats as $chat): ?>
                    <a href="chat.php?id=<?= $chat['id'] ?>" class="chat-item <?= $chat['unread_count'] > 0 ? 'unread' : '' ?>">
                        <div class="chat-avatar">
                            <?php
                            if ($userRole === 'student') {
                                echo strtoupper(mb_substr($chat['trainer_first_name'], 0, 1));
                            } else {
                                echo strtoupper(mb_substr($chat['student_first_name'], 0, 1));
                            }
                            ?>
                            <?php if ($chat['unread_count'] > 0): ?>
                                <span class="unread-indicator"><?= $chat['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="chat-info">
                            <div class="chat-header-info">
                                <div>
                                    <div class="chat-name">
                                        <?php
                                        if ($userRole === 'student') {
                                            echo htmlspecialchars($chat['trainer_first_name'] . ' ' . $chat['trainer_last_name']);
                                        } else {
                                            echo htmlspecialchars($chat['student_first_name'] . ' ' . $chat['student_last_name']);
                                        }
                                        ?>
                                    </div>
                                    <div class="chat-course">
                                        📚 <?= htmlspecialchars($chat['course_title']) ?>
                                    </div>
                                </div>
                                <div class="chat-time">
                                    <?php
                                    if ($chat['last_message_time']) {
                                        $time = strtotime($chat['last_message_time']);
                                        $now = time();
                                        $diff = $now - $time;
                                        
                                        if ($diff < 60) {
                                            echo 'Щойно';
                                        } elseif ($diff < 3600) {
                                            echo floor($diff / 60) . ' хв тому';
                                        } elseif ($diff < 86400) {
                                            echo floor($diff / 3600) . ' год тому';
                                        } else {
                                            echo date('d.m.Y', $time);
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if ($chat['last_message']): ?>
                                <div class="chat-last-message <?= $chat['unread_count'] > 0 ? 'unread' : '' ?>">
                                    <?= htmlspecialchars(mb_substr($chat['last_message'], 0, 100)) ?>
                                    <?= mb_strlen($chat['last_message']) > 100 ? '...' : '' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">💬</div>
                <h2>Поки немає чатів</h2>
                <p>
                    <?php if ($userRole === 'student'): ?>
                        Почніть навчання на курсі та зв'яжіться з тренером
                    <?php else: ?>
                        Коли учні напишуть вам, чати з'являться тут
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>