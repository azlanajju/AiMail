<style>
    .sidebar {
    width: 250px;
    height: 100vh;
    background-color: #f3f2f1;
    position: fixed;
    left: 0;
    top: 0;
    padding: 20px 0;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    display: flex;
    align-items: center;
    padding: 0 20px;
    margin-bottom: 20px;
}

.outlook-icon {
    width: 30px;
    height: 30px;
    margin-right: 10px;
}

.compose-btn {
    margin: 0 20px 20px;
    padding: 12px 24px;
    background: #1a73e8;
    color: white;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.compose-btn:hover {
    background: #1557b0;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.3s;
    position: relative;
}

.nav-item:hover {
    background-color: #e1dfdd;
}

.nav-item.active {
</style>

<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="sidebar">
    <div class="sidebar-header">
        <img src="assets/images/outlook-icon.png" alt="AIINBOX" class="outlook-icon">
        <span>AIINBOX</span>
    </div>
    
    <button class="compose-btn">
        <i class="fas fa-plus"></i>
        Compose Mail
    </button>

    <nav class="sidebar-nav">
        <a href="home.php" class="nav-item <?php echo $currentPage === 'home' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
            <?php if($currentPage === 'home'): ?>
                <span class="count">3</span>
            <?php endif; ?>
        </a>
        
        <a href="inbox.php" class="nav-item <?php echo $currentPage === 'inbox' ? 'active' : ''; ?>">
            <i class="fas fa-inbox"></i>
            <span>Inbox</span>
        </a>
        
        <a href="sent.php" class="nav-item <?php echo $currentPage === 'sent' ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i>
            <span>Sent Mail</span>
        </a>

        <a href="pinned.php" class="nav-item <?php echo $currentPage === 'pinned' ? 'active' : ''; ?>">
            <i class="fas fa-thumbtack"></i>
            <span>Pinned</span>
        </a>
        
        <a href="draft.php" class="nav-item <?php echo $currentPage === 'draft' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Draft</span>
        </a>
        
        <a href="spam.php" class="nav-item <?php echo $currentPage === 'spam' ? 'active' : ''; ?>">
            <i class="fas fa-ban"></i>
            <span>Spam</span>
        </a>
        
        <a href="trash.php" class="nav-item <?php echo $currentPage === 'trash' ? 'active' : ''; ?>">
            <i class="fas fa-trash-alt"></i>
            <span>Trash</span>
        </a>
    </nav>
</div> 