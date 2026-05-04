<?php
require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/db/storage.php';

function getPDO() {
    return getDB();
}

// ==================== MODULE FOLDERS ====================

function getFolders()
{
    $pdo = getPDO();
    $query = $pdo->query("SELECT f.*, (SELECT COUNT(*) FROM files WHERE folder_id = f.id) AS file_count FROM module_folders f ORDER BY f.name ASC");
    if (!$query) return [];
    return $query->fetchAll();
}

function getFolderById($id)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM module_folders WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addFolder($name)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO module_folders (name) VALUES (?)");
    return $stmt->execute([$name]);
}

function renameFolder($id, $name)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE module_folders SET name = ? WHERE id = ?");
    return $stmt->execute([$name, $id]);
}

function deleteFolder($id)
{
    $files = getModulesByFolder($id);
    $storage = getStorage();
    foreach ($files as $file) {
        if (!empty($file['file_name'])) {
            $storage->delete('modules', $file['file_name']);
        }
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM files WHERE folder_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM module_folders WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== MODULES (FILES) ====================

function getModule()
{
    $pdo = getPDO();
    $query = $pdo->query("SELECT f.*, mf.name AS folder_name FROM files f LEFT JOIN module_folders mf ON f.folder_id = mf.id ORDER BY f.uploaded_at DESC");
    if (!$query) return [];
    return $query->fetchAll();
}

function getModuleById($id)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT f.*, mf.name AS folder_name FROM files f LEFT JOIN module_folders mf ON f.folder_id = mf.id WHERE f.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getModulesByFolder($folderId)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM files WHERE folder_id = ? ORDER BY title ASC");
    $stmt->execute([$folderId]);
    return $stmt->fetchAll();
}

function addModule($title, $fileName, $description, $folderId)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO files (folder_id, title, file_name, description) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$folderId, $title, $fileName, $description]);
}

function updateModule($id, $title, $description)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("UPDATE files SET title = ?, description = ? WHERE id = ?");
    return $stmt->execute([$title, $description, $id]);
}

function deleteModule($id)
{
    $module = getModuleById($id);
    if ($module && !empty($module['file_name'])) {
        getStorage()->delete('modules', $module['file_name']);
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== TODO LIST ====================

function getToDoList()
{
    $pdo = getPDO();
    $query = $pdo->query("SELECT * FROM todo_list WHERE (deadline + deadline_time) >= NOW() ORDER BY deadline ASC, deadline_time ASC");
    if (!$query) return [];
    return $query->fetchAll();
}

function getAllTodos()
{
    $pdo = getPDO();
    $query = $pdo->query("SELECT * FROM todo_list ORDER BY deadline ASC, deadline_time ASC");
    if (!$query) return [];
    return $query->fetchAll();
}

function addTodo($task, $description, $deadline, $deadlineTime)
{
    $pdo = getPDO();
    $timeVal = !empty($deadlineTime) ? $deadlineTime : '23:59:59';
    $stmt = $pdo->prepare("INSERT INTO todo_list (task, description, deadline, deadline_time) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$task, $description, $deadline, $timeVal]);
}

function deleteTodo($id)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM todo_list WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== CALENDAR EVENTS ====================

function getCalendarEvents($month, $year)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE EXTRACT(MONTH FROM event_date) = ? AND EXTRACT(YEAR FROM event_date) = ? ORDER BY event_date ASC, event_time ASC");
    $stmt->execute([$month, $year]);
    return $stmt->fetchAll();
}

function getUpcomingEvents($limit = 5)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM calendar_events WHERE event_date >= CURRENT_DATE ORDER BY event_date ASC, event_time ASC LIMIT :lim");
    $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getAllCalendarEvents()
{
    $pdo = getPDO();
    $query = $pdo->query("SELECT * FROM calendar_events ORDER BY event_date DESC, event_time ASC");
    if (!$query) return [];
    return $query->fetchAll();
}

function addCalendarEvent($title, $description, $eventDate, $eventTime, $color)
{
    $pdo = getPDO();
    $timeVal = !empty($eventTime) ? $eventTime : null;
    $colorVal = !empty($color) ? $color : '#3b82f6';
    $stmt = $pdo->prepare("INSERT INTO calendar_events (title, description, event_date, event_time, color) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$title, $description, $eventDate, $timeVal, $colorVal]);
}

function deleteCalendarEvent($id)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("DELETE FROM calendar_events WHERE id = ?");
    return $stmt->execute([$id]);
}

// ==================== CALENDAR RENDERER ====================

function renderCalendar($month, $year, $events)
{
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = (int) date('t', $firstDay);
    $startDay = (int) date('N', $firstDay);
    $today = date('Y-m-d');

    $eventMap = [];
    foreach ($events as $event) {
        $day = (int) date('j', strtotime($event['event_date']));
        if (!isset($eventMap[$day]))
            $eventMap[$day] = [];
        $eventMap[$day][] = $event;
    }

    $prevMonth = $month - 1;
    $prevYear = $year;
    if ($prevMonth < 1) {
        $prevMonth = 12;
        $prevYear--;
    }
    $nextMonth = $month + 1;
    $nextYear = $year;
    if ($nextMonth > 12) {
        $nextMonth = 1;
        $nextYear++;
    }

    $html = '<div class="calendar-widget">';
    $html .= '<div class="cal-header">';
    $html .= '<a href="?month=' . $prevMonth . '&year=' . $prevYear . '" class="cal-nav"><i class="fas fa-chevron-left"></i></a>';
    $html .= '<span class="cal-title">' . date('F Y', $firstDay) . '</span>';
    $html .= '<a href="?month=' . $nextMonth . '&year=' . $nextYear . '" class="cal-nav"><i class="fas fa-chevron-right"></i></a>';
    $html .= '</div>';
    $html .= '<div class="cal-grid">';

    $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    foreach ($dayNames as $dn) {
        $html .= '<div class="cal-day-name">' . $dn . '</div>';
    }

    for ($i = 1; $i < $startDay; $i++) {
        $html .= '<div class="cal-day empty"></div>';
    }

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $classes = 'cal-day';
        if ($dateStr === $today)
            $classes .= ' today';
        if (isset($eventMap[$d]))
            $classes .= ' has-event';

        $html .= '<div class="' . $classes . '">';
        $html .= '<span class="day-num">' . $d . '</span>';
        if (isset($eventMap[$d])) {
            $html .= '<div class="event-dots">';
            foreach (array_slice($eventMap[$d], 0, 3) as $evt) {
                $html .= '<span class="event-dot" style="background:' . htmlspecialchars($evt['color']) . '" title="' . htmlspecialchars($evt['title']) . '"></span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    $html .= '</div></div>';
    return $html;
}
?>