<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        if ($user['access_level'] === 'admin') {
            header("Location: dashboard.php");
            exit();
        }
        $message = "Access denied: admin only";
        echo "<script type='text/javascript'>alert('$message');</script>";
    }
    $message = "Incorrect username or password";
    echo "<script type='text/javascript'>alert('$message');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <title>Admin</title>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <h3>Admin Login</h3>
        </div>
        <form method="post" class="login-form">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="btn-login">Login</button>
        </form>
    </div>
</body>

</html>