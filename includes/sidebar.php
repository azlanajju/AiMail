<div class="sidebar">
    <div class="logo">
        <img src="images/smart_compse_fullLogo.png" alt="Smart Compose Logo">
    </div>
    
    <nav class="nav-menu">
        <a href="home.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="compose.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'compose.php' ? 'active' : ''; ?>">
            <i class="fas fa-pen"></i>
            <span>Compose</span>
        </a>
        <a href="inbox.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'inbox.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Inbox</span>
        </a>
        <a href="sent.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sent.php' ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i>
            <span>Sent</span>
        </a>
        <a href="drafts.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'draft.php' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Drafts</span>
        </a>
        <a href="archive.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'archive.php' ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            <span>Archive</span>
        </a>
        <a href="spam.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'spam.php' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span>Spam</span>
        </a>
        <a href="starred.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'starred.php' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i>
            <span>Starred</span>
        </a>
        <a href="important.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'important.php' ? 'active' : ''; ?>">
            <i class="fas fa-bookmark"></i>
            <span>Important</span>
        </a>
        <a href="trash.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'trash.php' ? 'active' : ''; ?>">
            <i class="fas fa-trash"></i>
            <span>Trash</span>
        </a>
    </nav>

    <div class="user-section">
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #fff;
    color: white;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}

.logo {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: bold;
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
    background: #fff;
}
.logo img{
height: 50px;    
}

.nav-menu {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 0 10px;
    padding-top: 10px;
    overflow-y: auto;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
color: #000;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #5e64ff;
}

.nav-item.active {
    background: #5e64ff;
    color: #fff;
    font-weight: 500;
}

.nav-item i {
    width: 20px;
    text-align: center;
}

.user-section {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: rgba(255, 192, 203, 0.2); /* Light pinkish background */
}

.logout-btn:hover {
    background: rgba(255, 192, 203, 0.3); /* Slightly darker pink on hover */
    color: white;
}

/* Hover effects */
.nav-item:hover i,
.logout-btn:hover i {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

/* Active state animations */
.nav-item.active {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        transform: translateX(-10px);
        opacity: 0.5;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }
    
    .logo span,
    .nav-item span,
    .logout-btn span {
        font-size: 14px;
    }
}

/* Custom scrollbar for sidebar */
.sidebar::-webkit-scrollbar {
    width: 5px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    color: #666;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}

.user-info i {
    font-size: 20px;
}

.logout-btn {
    color: #ff4757;  /* Red color for logout */
    background: rgba(255, 71, 87, 0.1);
}

.logout-btn:hover {
    background: rgba(255, 71, 87, 0.2);
    color: #ff4757;
}

/* Add badges for unread counts */
.nav-item .badge {
    background: #5e64ff;
    color: white;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 12px;
    margin-left: auto;
}

/* Category separators */
.nav-category {
    padding: 8px 20px;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 10px;
}

/* Responsive height for nav-menu */
@media (max-height: 800px) {
    .nav-menu {
        max-height: calc(100vh - 180px);
    }
}
</style> 