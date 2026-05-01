<div class="header">
    <div>
        <h1 class="page-title">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $pageTitles = [
                'dashboard.php' => 'Upload Image',
                'manage_modules.php' => 'Module Management',
                'manage_todo.php' => 'Todo List Management',
                'manage_calendar.php' => 'Calendar Management',
                'manage.php' => 'Management',
                'analytics.php' => 'Analytics',
            ];
            echo isset($pageTitles[$current_page]) ? $pageTitles[$current_page] : 'Dashboard';
            ?>
        </h1>
    </div>
    <div class="user-profile">
        <div class="avatar">
            <?php echo strtoupper(substr($username, 0, 1)); ?>
        </div>
        <div class="user-info">
            <?php echo htmlspecialchars($username); ?>
        </div>
    </div>
</div>