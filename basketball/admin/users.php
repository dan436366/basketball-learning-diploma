<?php
require_once '../config.php';
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Обробка дій
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$userId]);
        
        setFlashMessage('success', 'Статус користувача змінено');
        header('Location: users.php');
        exit;
    } elseif ($_POST['action'] === 'change_role') {
        $userId = intval($_POST['user_id'] ?? 0);
        $newRole = sanitizeInput($_POST['role'] ?? '');
        
        if (in_array($newRole, ['student', 'trainer', 'admin'])) {
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            
            setFlashMessage('success', 'Роль користувача змінено');
        }
        header('Location: users.php');
        exit;
    }
}

// Фільтри
$role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Пагінація
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Базовий запит
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($role)) {
    $sql .= " AND role = ?";
    $params[] = $role;
}

if (!empty($search)) {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $status === 'active' ? 1 : 0;
}

// Підрахунок
$countSql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Отримання користувачів
$sql .= " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Управління користувачами';
include '../includes/header.php';
?>

<style>
    .admin-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
        margin-bottom: 40px;
    }
    
    .admin-header h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        font-weight: 700;
    }
    
    .admin-nav {
        background: white;
        padding: 15px 0;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    
    .admin-nav-links {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .admin-nav-link {
        padding: 10px 20px;
        background: #f8f9fa;
        color: #333;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .admin-nav-link:hover {
        background: #667eea;
        color: white;
    }
    
    .admin-nav-link.active {
        background: #667eea;
        color: white;
    }
    
    .filters-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    
    .filter-label {
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
    }
    
    .filter-input,
    .filter-select {
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
    }
    
    .btn-filter {
        padding: 10px 25px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        height: 44px;
    }
    
    .btn-reset {
        padding: 10px 25px;
        background: white;
        color: #666;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        height: 44px;
        line-height: 20px;
    }
    
    .users-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .card-title {
        font-size: 1.5rem;
        color: #333;
        font-weight: 700;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
    }
    
    table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #f0f0f0;
        white-space: nowrap;
    }
    
    table td {
        padding: 12px;
        border-bottom: 1px solid #f5f5f5;
        color: #666;
    }
    
    table tr:hover {
        background: #f8f9fa;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .user-avatar-small {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .user-name {
        font-weight: 600;
        color: #333;
    }
    
    .user-email {
        font-size: 0.9rem;
        color: #999;
    }
    
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .badge-student { background: #e3f2fd; color: #1976d2; }
    .badge-trainer { background: #fff3e0; color: #f57c00; }
    .badge-admin { background: #fce4ec; color: #c2185b; }
    .badge-active { background: #d4edda; color: #155724; }
    .badge-inactive { background: #f8d7da; color: #721c24; }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-action {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-toggle {
        background: #ffc107;
        color: #333;
    }
    
    .btn-toggle:hover {
        background: #e0a800;
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
    }
    
    .btn-delete:hover {
        background: #c82333;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-top: 20px;
    }
    
    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        text-decoration: none;
        color: #333;
        font-weight: 600;
    }
    
    .pagination a:hover {
        border-color: #667eea;
        background: #667eea;
        color: white;
    }
    
    .pagination .active {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }
    
    .role-select {
        padding: 4px 8px;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        font-size: 0.85rem;
    }
</style>

<!-- Admin Header -->
<section class="admin-header">
    <div class="container">
        <h1>👥 Управління користувачами</h1>
        <p>Перегляд та редагування користувачів системи</p>
    </div>
</section>

<div class="container">
    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-links">
            <a href="admin_dashboard.php" class="admin-nav-link">📊 Статистика</a>
            <a href="users.php" class="admin-nav-link active">👥 Користувачі</a>
            <a href="admin_courses.php" class="admin-nav-link">📚 Курси</a>
            <a href="admin_payments.php" class="admin-nav-link">💰 Платежі</a>
            <a href="admin_settings.php" class="admin-nav-link">⚙️ Налаштування</a>
        </div>
    </nav>
    
    <!-- Filters -->
    <div class="filters-card">
        <form method="GET">
            <div class="filters-row">
                <div class="filter-group">
                    <label class="filter-label">Пошук</label>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Ім'я або email..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Роль</label>
                    <select name="role" class="filter-select">
                        <option value="">Всі ролі</option>
                        <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Учень</option>
                        <option value="trainer" <?= $role === 'trainer' ? 'selected' : '' ?>>Тренер</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Адмін</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Статус</label>
                    <select name="status" class="filter-select">
                        <option value="">Всі</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Активні</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Неактивні</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">Фільтрувати</button>
                <a href="users.php" class="btn-reset">Скинути</a>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="users-card">
        <div class="card-header">
            <h2 class="card-title">Користувачі (<?= $totalUsers ?>)</h2>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Користувач</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Дата реєстрації</th>
                        <th>Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar-small">
                                    <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                                    <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="role" class="role-select" onchange="this.form.submit()">
                                    <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Учень</option>
                                    <option value="trainer" <?= $user['role'] === 'trainer' ? 'selected' : '' ?>>Тренер</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Адмін</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <span class="badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? 'Активний' : 'Неактивний' ?>
                            </span>
                        </td>
                        <td><?= formatDateTime($user['created_at']) ?></td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn-action btn-toggle">
                                        <?= $user['is_active'] ? '🔒 Блок' : '✅ Актив' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&role=<?= $role ?>&search=<?= $search ?>&status=<?= $status ?>">← Попередня</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&role=<?= $role ?>&search=<?= $search ?>&status=<?= $status ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&role=<?= $role ?>&search=<?= $search ?>&status=<?= $status ?>">Наступна →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>