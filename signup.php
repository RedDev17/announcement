<?php
include __DIR__ . '/db/db.php';

if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $access_level = $_POST['access_level'];

    $pdo = getDB();
    $stmt = $pdo->prepare('INSERT INTO "user" (username, email, password, access_level) VALUES (?, ?, ?, ?)');
    $result = $stmt->execute([$username, $email, $password, $access_level]);

    if (!$result) {
        die("Query failed");
    } else {
        echo "<script>alert('Sign up successful');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up</title>
</head>

<body>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="text" name="access_level" placeholder="Acess level" required>
        <button type="submit" name="signup">Sign Up</button>
    </form>
</body>

</html>