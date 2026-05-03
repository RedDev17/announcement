<div class="sidebar">
    <div class="logo-area">
        <div class="logo-icon">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div class="logo-text">Admin Panel</div>
    </div>

    <div class="nav-menu">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <ul>
            <li>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-image"></i> Upload Image
                </a>
            </li>
            <li>
                <a href="manage_modules.php" class="<?php echo $current_page == 'manage_modules.php' ? 'active' : ''; ?>">
                    <i class="fas fa-file-pdf"></i> Modules
                </a>
            </li>
            <li>
                <a href="manage_todo.php" class="<?php echo $current_page == 'manage_todo.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i> Todo List
                </a>
            </li>
            <li>
                <a href="manage_calendar.php" class="<?php echo $current_page == 'manage_calendar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Calendar
                </a>
            </li>
            <li>
                <a href="../index.php">
                    <i class="fas fa-home"></i> View Site
                </a>
            </li>
            <li>
                <a href="logout.php" style="color:#ef4444;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</div>