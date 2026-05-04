<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../db/auth.php';

// Already logged in as admin? Go to dashboard
$current = getLoggedInUser();
if ($current && $current['access_level'] === 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please fill in both fields.';
    } else {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT * FROM "user" WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['access_level'] === 'admin') {
                    loginUser($user['username'], $user['email'], $user['access_level']);
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Access denied: admin accounts only.';
                }
            } else {
                $error = 'Incorrect username or password.';
            }
        } catch (PDOException $e) {
            error_log('Admin login failed: ' . $e->getMessage());
            $error = 'Login failed. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%233b82f6'%3E%3Cpath d='M3 11l18-5v12L3 14v-3zm14.5 4.5l1.5 4-2 1-2-4.5 2.5-.5z'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Admin Login</title>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <h3>Admin Login</h3>
        </div>
        <form method="post" class="login-form" autocomplete="off">
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn-login">Login</button>
        </form>
    </div>
</body>

</html>