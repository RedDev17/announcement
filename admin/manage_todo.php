<?php
include __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../db/auth.php';
require_once __DIR__ . '/../functions.php';

$authUser = requireAdmin();
$username = $authUser['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_todo'])) {
        $task = trim($_POST['task'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $deadline = $_POST['deadline'] ?? '';
        $deadlineTime = $_POST['deadline_time'] ?? '';
        if (!empty($task) && !empty($deadline)) {
            addTodo($task, $description, $deadline, $deadlineTime);
        }
        header("Location: manage_todo.php");
        exit();
    }

    if (isset($_POST['delete_todo'])) {
        $id = intval($_POST['todo_id'] ?? 0);
        deleteTodo($id);
        header("Location: manage_todo.php");
        exit();
    }
}

$todos = getAllTodos();
$now = new DateTime();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Todo List</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%233b82f6'%3E%3Cpath d='M3 11l18-5v12L3 14v-3zm14.5 4.5l1.5 4-2 1-2-4.5 2.5-.5z'/%3E%3C/svg%3E">
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
                    <div class="card-icon"><i class="fas fa-plus-circle"></i></div>
                    <h2 class="card-title">Add New Task</h2>
                    <p class="card-description">Tasks automatically disappear from the board after the deadline passes.</p>
                </div>
                <form method="POST" class="upload-form">
                    <div class="form-row">
                        <div class="form-group" style="flex:1">
                            <label>Task Name *</label>
                            <input type="text" name="task" placeholder="Enter task name" required>
                        </div>
                        <div class="form-group">
                            <label>Deadline Date *</label>
                            <input type="date" name="deadline" required>
                        </div>
                        <div class="form-group">
                            <label>Deadline Time</label>
                            <input type="time" name="deadline_time" value="23:59" style="padding:10px 12px; background:#0f172a; border:1px solid #475569; color:#e2e8f0; border-radius:8px; font-size:14px;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Enter task description (optional)" rows="3"></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_todo" class="submit-button">
                            <i class="fas fa-plus"></i> <span>Add Task</span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="upload-card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">All Tasks (<?php echo count($todos); ?>)</h2>
                </div>
                <div class="table-container">
                    <?php if (empty($todos)): ?>
                        <p class="empty-msg">No tasks yet. Add your first task above.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Deadline</th>
                                    <th>Visibility</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todos as $todo):
                                    $dl = new DateTime($todo['deadline'] . ' ' . $todo['deadline_time']);
                                    $expired = $dl < $now;
                                ?>
                                    <tr style="<?php echo $expired ? 'opacity:0.45;' : ''; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($todo['task']); ?></strong>
                                            <?php if (!empty($todo['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($todo['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($todo['deadline'])); ?>
                                            <br><small style="color:#94a3b8"><?php echo date('g:i A', strtotime($todo['deadline_time'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($expired): ?>
                                                <span style="color:#ef4444; font-size:13px;"><i class="fas fa-eye-slash"></i> Expired</span>
                                            <?php else: ?>
                                                <span style="color:#10b981; font-size:13px;"><i class="fas fa-eye"></i> Visible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this task?')">
                                                <input type="hidden" name="todo_id" value="<?php echo $todo['id']; ?>">
                                                <button type="submit" name="delete_todo" class="btn-delete">
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
