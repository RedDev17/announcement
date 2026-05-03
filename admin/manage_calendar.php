<?php
include __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../db/auth.php';
require_once __DIR__ . '/../functions.php';

$authUser = requireAdmin();
$username = $authUser['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_event'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $eventDate = $_POST['event_date'];
        $eventTime = $_POST['event_time'];
        $color = $_POST['color'];
        if (!empty($title) && !empty($eventDate)) {
            addCalendarEvent($title, $description, $eventDate, $eventTime, $color);
        }
        header("Location: manage_calendar.php");
        exit();
    }

    if (isset($_POST['delete_event'])) {
        $id = intval($_POST['event_id']);
        deleteCalendarEvent($id);
        header("Location: manage_calendar.php");
        exit();
    }
}

$events = getAllCalendarEvents();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include "sideNav.php"; ?>
        <div class="main-content">
            <?php include "header.php"; ?>

            <div class="upload-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-calendar-plus"></i></div>
                    <h2 class="card-title">Add New Event</h2>
                </div>
                <form method="POST" class="upload-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Event Title *</label>
                            <input type="text" name="title" placeholder="Enter event title" required>
                        </div>
                        <div class="form-group">
                            <label>Color</label>
                            <input type="color" name="color" value="#3b82f6" class="color-input">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date *</label>
                            <input type="date" name="event_date" required>
                        </div>
                        <div class="form-group">
                            <label>Time (optional)</label>
                            <input type="time" name="event_time">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Enter event description (optional)" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_event" class="submit-button">
                            <i class="fas fa-plus"></i> <span>Add Event</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="upload-card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">All Events (<?php echo count($events); ?>)</h2>
                </div>
                <div class="table-container">
                    <?php if (empty($events)): ?>
                        <p class="empty-msg">No events yet. Add your first event above.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Color</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <div class="color-badge" style="background:<?php echo htmlspecialchars($event['color']); ?>"></div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                            <?php if (!empty($event['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($event['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                        <td><?php echo $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : '<span class="text-muted">-</span>'; ?></td>
                                        <td>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this event?')">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <button type="submit" name="delete_event" class="btn-delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
