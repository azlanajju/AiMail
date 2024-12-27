<div class="topbar">
    <div class="left-section">
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search emails...">
        </div>
    </div>
    
    <div class="right-section">
        <div class="notifications">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">3</span>
        </div>
        
        <div class="user-profile">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'User'); ?>&background=5e64ff&color=fff" 
                 alt="Profile" 
                 class="profile-image">
            <span class="user-name"><?php echo $_SESSION['user_name'] ?? 'User'; ?></span>
            <i class="fas fa-chevron-down"></i>
            
            <div class="profile-dropdown">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="settings.php" class="dropdown-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.topbar {
    position: fixed;
    top: 0;
    right: 0;
    left: 250px; /* Match sidebar width */
    height: 60px;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 30px;
    box-shadow: 0 2px 4px rgba(94, 100, 255, 0.1);
    z-index: 1000;
}

.left-section {
    display: flex;
    align-items: center;
}

.search-container {
    position: relative;
    width: 400px;
}

.search-input {
    width: 100%;
    padding: 10px 15px 10px 40px;
    border: 1px solid #eef0ff;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #5e64ff;
    box-shadow: 0 0 0 3px rgba(94, 100, 255, 0.1);
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
}

.right-section {
    display: flex;
    align-items: center;
    gap: 20px;
}

.notifications {
    position: relative;
    cursor: pointer;
    padding: 8px;
}

.notifications i {
    font-size: 18px;
    color: #666;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #5e64ff;
    color: white;
    font-size: 10px;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 15px;
    text-align: center;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 5px 10px;
    border-radius: 8px;
    position: relative;
}

.user-profile:hover {
    background: #f5f7ff;
}

.profile-image {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.profile-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(94, 100, 255, 0.15);
    min-width: 200px;
    padding: 8px;
    margin-top: 10px;
    display: none;
    z-index: 1000;
}

.user-profile:hover .profile-dropdown {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: #f5f7ff;
    color: #5e64ff;
}

.dropdown-divider {
    height: 1px;
    background: #eef0ff;
    margin: 8px 0;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .search-container {
        width: 300px;
    }
}

@media (max-width: 768px) {
    .topbar {
        left: 200px; /* Match collapsed sidebar width */
        padding: 0 15px;
    }

    .search-container {
        width: 200px;
    }

    .user-name {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const profile = document.querySelector('.user-profile');
        const dropdown = document.querySelector('.profile-dropdown');
        
        if (!profile.contains(event.target) && dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        }
    });
});
</script> 