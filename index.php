<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/db/storage.php';
require_once __DIR__ . '/functions.php';

$folders = getFolders();
$toDoList = getToDoList();
$storage = getStorage();

$calMonth = isset($_GET['month']) ? intval($_GET['month']) : (int) date('m');
$calYear = isset($_GET['year']) ? intval($_GET['year']) : (int) date('Y');
if ($calMonth < 1 || $calMonth > 12)
    $calMonth = (int) date('m');
if ($calYear < 2000 || $calYear > 2100)
    $calYear = (int) date('Y');

$calendarEvents = getCalendarEvents($calMonth, $calYear);
$upcomingEvents = getUpcomingEvents(5);

$announcementImage = null;
$imgRes = getDB()->query("SELECT * FROM image LIMIT 1");
if ($imgRes && $imgRes->rowCount() > 0) {
    $announcementImage = $imgRes->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Board</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%233b82f6'%3E%3Cpath d='M3 11l18-5v12L3 14v-3zm14.5 4.5l1.5 4-2 1-2-4.5 2.5-.5z'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <div class="header-icon"><i class="fas fa-bullhorn"></i></div>
                <div>
                    <h1>Announcement Board</h1>
                    <p class="header-subtitle"><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="grid-layout">
            <div class="col-left">
                <div class="card announcement-card">
                    <div class="card-head">
                        <i class="fas fa-image"></i>
                        <h3>Schedule</h3>
                    </div>
                    <div class="card-body image-display">
                        <?php if ($announcementImage): ?>
                            <img src="<?php echo htmlspecialchars($storage->publicUrl('images', $announcementImage['file'])); ?>"
                                alt="Schedule">
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-image"></i>
                                <p>No schedule posted yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card modules-card">
                    <div class="card-head">
                        <i class="fas fa-folder"></i>
                        <h3>Modules</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($folders)): ?>
                            <div class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <p>No module folders available</p>
                            </div>
                        <?php else: ?>
                            <div class="module-list">
                                <?php foreach ($folders as $folder): ?>
                                    <a href="module.php?folder=<?php echo $folder['id']; ?>" class="module-item">
                                        <div class="module-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;">
                                            <i class="fas fa-folder"></i>
                                        </div>
                                        <div class="module-info">
                                            <span class="module-title"><?php echo htmlspecialchars($folder['name']); ?></span>
                                            <span class="module-desc"><?php echo $folder['file_count']; ?> file(s)</span>
                                        </div>
                                        <i class="fas fa-chevron-right module-arrow"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-right">
                <div class="card calendar-card">
                    <div class="card-head">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Calendar</h3>
                    </div>
                    <div class="card-body">
                        <?php echo renderCalendar($calMonth, $calYear, $calendarEvents); ?>
                        <?php if (!empty($upcomingEvents)): ?>
                            <div class="upcoming-events">
                                <h4>Upcoming Events</h4>
                                <?php foreach ($upcomingEvents as $event): ?>
                                    <div class="event-item">
                                        <div class="event-color"
                                            style="background:<?php echo htmlspecialchars($event['color']); ?>"></div>
                                        <div class="event-details">
                                            <span class="event-name"><?php echo htmlspecialchars($event['title']); ?></span>
                                            <span class="event-date-text">
                                                <?php echo date('M j', strtotime($event['event_date'])); ?>
                                                <?php if ($event['event_time']): ?>
                                                    at <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card todo-card">
                    <div class="card-head">
                        <i class="fas fa-tasks"></i>
                        <h3>To-Do List</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($toDoList)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>No upcoming tasks</p>
                            </div>
                        <?php else: ?>
                            <div class="todo-list">
                                <?php foreach ($toDoList as $item): ?>
                                    <div class="todo-item">
                                        <div class="todo-status">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="todo-info">
                                            <span class="todo-task"><?php echo htmlspecialchars($item['task']); ?></span>
                                            <span class="todo-deadline">
                                                <i class="fas fa-calendar"></i>
                                                <?php echo date('M j, Y', strtotime($item['deadline'])); ?>
                                                <?php if ($item['deadline_time'] && $item['deadline_time'] !== '23:59:59'): ?>
                                                    at <?php echo date('g:i A', strtotime($item['deadline_time'])); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>

</html>