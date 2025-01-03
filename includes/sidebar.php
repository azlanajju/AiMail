<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<div class="sidebar">
    <div class="logo">
        <img src="<?php echo $path?>images/smart_compse_fullLogo.svg" alt="Smart Compose Logo">
    </div>
    
    <nav class="nav-menu">
        <a href="<?php echo $path?>home" class="nav-item <?php echo $activePage == 'home' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo $path?>inbox" class="nav-item <?php echo $activePage == 'inbox' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Inbox</span>
        </a>
        <a href="<?php echo $path?>calendar" class="nav-item <?php echo $activePage == 'calendar' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-day"></i>
            <span>Calendar</span>
        </a>

        <a href="<?php echo $path?>sent" class="nav-item <?php echo $activePage == 'sent' ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i>
            <span>Sent</span>
        </a>
        <a href="<?php echo $path?>draft" class="nav-item <?php echo $activePage == 'draft' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Drafts</span>
        </a>
        <a href="<?php echo $path?>archive" class="nav-item <?php echo $activePage == 'archive' ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i>
            <span>Archive</span>
        </a>
        <a href="<?php echo $path?>spam" class="nav-item <?php echo $activePage == 'spam' ? 'active' : ''; ?>">
            <i class="fas fa-exclamation-circle"></i>
            <span>Spam</span>
        </a>
        <a href="<?php echo $path?>classification" class="nav-item <?php echo $activePage == 'classification' ? 'active' : ''; ?>">
            <i class="fas fa-cogs"></i>
            <span>Classifications</span>
        </a>
        <!-- <a href="important" class="nav-item <?php echo $activePage == 'important' ? 'active' : ''; ?>">
            <i class="fas fa-bookmark"></i>
            <span>Important</span>
        </a> -->
        <a href="<?php echo $path?>trash" class="nav-item <?php echo $activePage == 'trash' ? 'active' : ''; ?>">
            <i class="fas fa-trash"></i>
            <span>Trash</span>
        </a>
    </nav>

    <div class="user-section">
        <!-- <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
        </div> -->
        <a href="<?php echo $path?>logout.php" class="logout-btn">
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
    background: #fff;
    margin-bottom: -20px;
}
.logo img{
height: 100px;    
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
    margin-bottom: 20px;
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
        width: 100%;
        height: 60px;
        position: fixed;
        bottom: 0;
        top: auto;
        left: 0;
        padding: 0;
        flex-direction: row;
        z-index: 1000;
    }

    .logo {
        display: none;
    }

    .nav-menu {
        flex-direction: row;
        justify-content: space-around;
        align-items: center;
        padding: 0;
        margin: 0;
        width: 100%;
        overflow-x: auto;
    }

    .nav-item {
        flex-direction: column;
        padding: 8px;
        gap: 4px;
        font-size: 12px;
        text-align: center;
        min-width: fit-content;
    }

    .nav-item i {
        font-size: 18px;
    }

    .nav-item span {
        font-size: 10px;
    }

    .user-section {
        display: none;
    }

    /* Adjust main content area */
    .content-wrapper {
        margin-left: 0 !important;
        margin-bottom: 60px !important;
        padding-bottom: 20px !important;
    }
}

/* Add horizontal scrollbar styling for mobile */
@media (max-width: 768px) {
    .nav-menu::-webkit-scrollbar {
        height: 3px;
    }

    .nav-menu::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .nav-menu::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
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