<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - ' : '' ?>Basketball Learning</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-dark: #333;
            --text-light: #757575;
            --bg-light: #f5f5f5;
            --white: #ffffff;
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* ========== HEADER ========== */
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0 !important;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar .container {
            max-width: 1200px;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea !important;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }
        
        .navbar-brand:hover {
            color: #764ba2 !important;
        }
        
        /* Navigation Links */
        .nav-link {
            color: #333 !important;
            font-weight: 500;
            margin: 0 10px;
            padding: 8px 16px !important;
            transition: var(--transition);
            border-radius: 8px;
        }
        
        .nav-link:hover {
            color: #667eea !important;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .nav-link.active {
            color: #667eea !important;
        }
        
        /* Chat Button */
        .chat-btn {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: var(--transition);
            margin: 0 10px;
            white-space: nowrap;
        }
        
        .chat-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .chat-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: linear-gradient(135deg, #dc3545, #ff6b81);
            color: white;
            border-radius: 12px;
            min-width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            padding: 0 6px;
            border: 2px solid white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* User Avatar */
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 14px;
            margin-right: 8px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        /* Dropdown */
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-radius: 12px;
            padding: 8px 0;
            margin-top: 8px !important;
        }
        
        .dropdown-item {
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .dropdown-item:hover {
            background: rgba(102, 126, 234, 0.05);
            color: #667eea;
            padding-left: 24px;
        }
        
        .dropdown-item i {
            width: 20px;
            opacity: 0.7;
        }
        
        /* Auth Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-outline-primary {
            border: 2px solid #667eea;
            color: #667eea;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            background: transparent;
            transition: var(--transition);
        }
        
        .btn-outline-primary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        /* ========== MOBILE MENU (Nike Style) - Only < 991px ========== */
        @media (min-width: 992px) {
            .mobile-menu-overlay,
            .mobile-menu-panel,
            .navbar-toggler {
                display: none !important;
            }
        }
        
        @media (max-width: 991px) {
            /* Hide desktop nav */
            .navbar-nav {
                display: none !important;
            }
            
            /* Mobile Toggle Button */
            .navbar-toggler {
                border: none;
                padding: 8px;
            }
            
            .navbar-toggler:focus {
                box-shadow: none;
            }
            
            /* Overlay */
            .mobile-menu-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1998;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }
            
            .mobile-menu-overlay.active {
                opacity: 1;
                visibility: visible;
            }
            
            /* Mobile Menu Panel */
            .mobile-menu-panel {
                position: fixed;
                top: 0;
                right: 0;
                width: 100%;
                max-width: 400px;
                height: 100%;
                background: white;
                z-index: 1999;
                transform: translateX(100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                overflow-y: auto;
                display: flex;
                flex-direction: column;
            }
            
            .mobile-menu-panel.active {
                transform: translateX(0);
            }
            
            /* Menu Header */
            .mobile-menu-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 20px;
                border-bottom: 1px solid #e5e5e5;
                background: #f7f7f7;
            }
            
            .mobile-menu-back {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: var(--text-dark);
                padding: 0;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                visibility: hidden;
            }
            
            .mobile-menu-back.visible {
                visibility: visible;
            }
            
            .mobile-menu-logo {
                font-size: 20px;
                font-weight: 700;
                color: var(--primary-color);
            }
            
            .mobile-menu-close {
                background: none;
                border: none;
                font-size: 28px;
                cursor: pointer;
                color: var(--text-dark);
                padding: 0;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Menu Content */
            .mobile-menu-content {
                flex: 1;
                overflow-y: auto;
            }
            
            /* Main Menu */
            .mobile-main-menu {
                display: block;
            }
            
            .mobile-main-menu.hidden {
                display: none;
            }
            
            /* Submenu */
            .mobile-submenu-panel {
                display: none;
            }
            
            .mobile-submenu-panel.active {
                display: block;
            }
            
            .mobile-submenu-title {
                padding: 20px 20px 16px;
                font-size: 24px;
                font-weight: 700;
                color: var(--text-dark);
                border-bottom: 1px solid #e5e5e5;
            }
            
            /* Menu Items */
            .mobile-menu-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 18px 20px;
                border-bottom: 1px solid #e5e5e5;
                cursor: pointer;
                transition: background 0.2s ease;
                text-decoration: none;
                color: var(--text-dark);
                font-size: 16px;
                font-weight: 500;
            }
            
            .mobile-menu-item:hover {
                background: #f7f7f7;
            }
            
            .mobile-menu-item-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .mobile-menu-item-icon {
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 18px;
            }
            
            .mobile-menu-item-chevron {
                color: #757575;
                font-size: 18px;
            }
            
            /* User Section */
            .mobile-user-item {
                padding: 18px 20px;
                border-bottom: 1px solid #e5e5e5;
            }
            
            .mobile-user-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .mobile-user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 16px;
            }
            
            .mobile-user-name {
                font-size: 16px;
                font-weight: 500;
                color: var(--text-dark);
            }
            
            /* Submenu Items */
            .mobile-submenu-item {
                padding: 16px 20px;
                border-bottom: 1px solid #f0f0f0;
                font-size: 15px;
                color: #757575;
                cursor: pointer;
                transition: background 0.2s ease;
                text-decoration: none;
                display: block;
            }
            
            .mobile-submenu-item:hover {
                background: #f7f7f7;
                color: var(--text-dark);
            }
            
            /* Extras Section */
            .mobile-menu-extras {
                padding: 20px;
                border-top: 1px solid #e5e5e5;
            }
            
            .mobile-menu-extra-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 0;
                color: var(--text-dark);
                text-decoration: none;
                font-size: 15px;
                font-weight: 500;
            }
            
            .mobile-menu-extra-item i {
                width: 24px;
                text-align: center;
                font-size: 18px;
            }
        }
        
        /* Alerts */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 16px 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .alert .btn-close {
            margin-left: auto;
        }
        
        .container {
            max-width: 1200px;
        }
        
        main {
            flex: 1;
        }
        
        @media (max-width: 480px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .mobile-menu-panel {
                max-width: 100%;
            }
        }
        
        @media (max-width: 320px) {
            .navbar-brand span:last-child {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php
    $currentPath = $_SERVER['PHP_SELF'];
    $isInSubfolder = (strpos($currentPath, '/student/') !== false || 
                      strpos($currentPath, '/trainer/') !== false || 
                      strpos($currentPath, '/admin/') !== false);
    $urlPrefix = $isInSubfolder ? '../' : '';
    
    $unreadCount = 0;
    if (isLoggedIn()) {
        $db = Database::getInstance()->getConnection();
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        
        if ($userRole === 'student') {
            $stmt = $db->prepare("
                SELECT COUNT(*) as unread
                FROM chat_messages cm
                JOIN chats c ON cm.chat_id = c.id
                WHERE c.student_id = ? AND cm.sender_id != ? AND cm.is_read = 0
            ");
            $stmt->execute([$userId, $userId]);
        } else if ($userRole === 'trainer') {
            $stmt = $db->prepare("
                SELECT COUNT(*) as unread
                FROM chat_messages cm
                JOIN chats c ON cm.chat_id = c.id
                WHERE c.trainer_id = ? AND cm.sender_id != ? AND cm.is_read = 0
            ");
            $stmt->execute([$userId, $userId]);
        }
        
        if (isset($stmt)) {
            $result = $stmt->fetch();
            $unreadCount = $result['unread'] ?? 0;
        }
    }
    ?>
    
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="<?= $urlPrefix ?>index.php">
                🏀 <span>Basketball Learning</span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" onclick="openMobileMenu()">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Desktop Navigation -->
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $urlPrefix ?>index.php">Головна</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $urlPrefix ?>courses.php">Курси</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $urlPrefix ?>trainers.php">Тренери</a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php $user = getCurrentUser(); ?>
                        
                        <?php if ($user['role'] === 'student' || $user['role'] === 'trainer'): ?>
                        <li class="nav-item">
                            <a class="chat-btn" href="<?= $urlPrefix ?>chats.php">
                                💬 Мої чати
                                <?php if ($unreadCount > 0): ?>
                                    <span class="chat-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                                </div>
                                <?= htmlspecialchars($user['first_name']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($user['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= $urlPrefix ?>admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i>Панель адміністратора
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php elseif ($user['role'] === 'trainer'): ?>
                                    <li><a class="dropdown-item" href="<?= $urlPrefix ?>trainer/dashboard.php">
                                        <i class="fas fa-chalkboard-teacher"></i>Мої курси
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?= $urlPrefix ?>student/dashboard.php">
                                        <i class="fas fa-book-reader"></i>Мої курси
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= $urlPrefix ?>student/my-courses.php">
                                        <i class="fas fa-graduation-cap"></i>Навчання
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= $urlPrefix ?>profile.php">
                                    <i class="fas fa-user"></i>Профіль
                                </a></li>
                                <li><a class="dropdown-item" href="<?= $urlPrefix ?>logout.php">
                                    <i class="fas fa-sign-out-alt"></i>Вийти
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $urlPrefix ?>login.php">Вхід</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-primary" href="<?= $urlPrefix ?>register.php">Реєстрація</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>
    
    <!-- Mobile Menu Panel -->
    <div class="mobile-menu-panel" id="mobileMenuPanel">
        <!-- Header -->
        <div class="mobile-menu-header">
            <button class="mobile-menu-back" id="mobileMenuBack" onclick="backToMainMenu()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="mobile-menu-logo">🏀 Basketball</div>
            <button class="mobile-menu-close" onclick="closeMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="mobile-menu-content">
            <!-- Main Menu -->
            <div class="mobile-main-menu" id="mobileMainMenu">
                <?php if (isLoggedIn()): ?>
                    <?php $user = getCurrentUser(); ?>
                    
                    <!-- User Item -->
                    <div class="mobile-menu-item" onclick="openUserSubmenu()">
                        <div class="mobile-menu-item-content">
                            <div class="mobile-user-avatar">
                                <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                            </div>
                            <span class="mobile-user-name">Привіт, <?= htmlspecialchars($user['first_name']) ?></span>
                        </div>
                        <i class="fas fa-chevron-right mobile-menu-item-chevron"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Main Links -->
                <a href="<?= $urlPrefix ?>index.php" class="mobile-menu-item">
                    <span>Головна</span>
                </a>
                
                <a href="<?= $urlPrefix ?>courses.php" class="mobile-menu-item">
                    <span>Курси</span>
                </a>
                
                <a href="<?= $urlPrefix ?>trainers.php" class="mobile-menu-item">
                    <span>Тренери</span>
                </a>
                
                <?php if (isLoggedIn() && ($user['role'] === 'student' || $user['role'] === 'trainer')): ?>
                <a href="<?= $urlPrefix ?>chats.php" class="mobile-menu-item">
                    <div class="mobile-menu-item-content">
                        <span class="mobile-menu-item-icon">💬</span>
                        <span>Мої чати</span>
                        <?php if ($unreadCount > 0): ?>
                            <span style="color: #dc3545; font-weight: 700;">(<?= $unreadCount ?>)</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if (!isLoggedIn()): ?>
                <a href="<?= $urlPrefix ?>login.php" class="mobile-menu-item">
                    <span>Вхід</span>
                </a>
                
                <a href="<?= $urlPrefix ?>register.php" class="mobile-menu-item">
                    <span>Реєстрація</span>
                </a>
                <?php endif; ?>
            </div>
            
            <!-- User Submenu -->
            <?php if (isLoggedIn()): ?>
            <div class="mobile-submenu-panel" id="userSubmenu">
                <div class="mobile-submenu-title">Мій Акаунт</div>
                
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="<?= $urlPrefix ?>admin/dashboard.php" class="mobile-submenu-item">
                        Панель адміністратора
                    </a>
                <?php elseif ($user['role'] === 'trainer'): ?>
                    <a href="<?= $urlPrefix ?>trainer/dashboard.php" class="mobile-submenu-item">
                        Мої курси
                    </a>
                <?php else: ?>
                    <a href="<?= $urlPrefix ?>student/dashboard.php" class="mobile-submenu-item">
                        Мої курси
                    </a>
                    <a href="<?= $urlPrefix ?>student/my-courses.php" class="mobile-submenu-item">
                        Навчання
                    </a>
                <?php endif; ?>
                
                <a href="<?= $urlPrefix ?>profile.php" class="mobile-submenu-item">
                    Профіль
                </a>
                
                <a href="<?= $urlPrefix ?>logout.php" class="mobile-submenu-item">
                    Вийти
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    $flashMessage = getFlashMessage();
    if ($flashMessage):
    ?>
    <div class="container">
        <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMessage['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile Menu Functions
        function openMobileMenu() {
            document.getElementById('mobileMenuOverlay').classList.add('active');
            document.getElementById('mobileMenuPanel').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            document.getElementById('mobileMenuOverlay').classList.remove('active');
            document.getElementById('mobileMenuPanel').classList.remove('active');
            document.body.style.overflow = '';
            
            // Reset to main menu
            setTimeout(() => {
                backToMainMenu();
            }, 300);
        }
        
        function openUserSubmenu() {
            document.getElementById('mobileMainMenu').classList.add('hidden');
            document.getElementById('userSubmenu').classList.add('active');
            document.getElementById('mobileMenuBack').classList.add('visible');
        }
        
        function backToMainMenu() {
            document.getElementById('mobileMainMenu').classList.remove('hidden');
            document.getElementById('userSubmenu').classList.remove('active');
            document.getElementById('mobileMenuBack').classList.remove('visible');
        }
        
        // Auto-hide alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>