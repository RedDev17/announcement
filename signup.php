<?php
require_once __DIR__ . '/db/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $passwordRaw = $_POST['password'] ?? '';

    // Validation
    if ($username === '' || $email === '' || $passwordRaw === '') {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'Username must be 3-30 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($passwordRaw) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        try {
            $pdo = getDB();
            // Duplicate check
            $check = $pdo->prepare('SELECT id FROM "user" WHERE username = ? OR email = ? LIMIT 1');
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = 'Username or email is already taken.';
            } else {
                $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO "user" (username, email, password, access_level) VALUES (?, ?, ?, ?)');
                $stmt->execute([$username, $email, $password, 'user']);
                $success = 'Account created successfully. You can now log in.';
            }
        } catch (PDOException $e) {
            error_log('Signup failed: ' . $e->getMessage());
            $error = 'Signup failed. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up</title>
    <link rel="stylesheet" href="./css/admin.css">
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <h3>Create Account</h3>
        </div>
        <form method="post" class="login-form" autocomplete="off">
            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <input type="text" name="username" placeholder="Username (3-30 chars)" required minlength="3" maxlength="30"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password (min 8 chars)" required minlength="8">
            </div>
            <button type="submit" name="signup" class="btn-login">Sign Up</button>
        </form>
    </div>
</body>

</html>