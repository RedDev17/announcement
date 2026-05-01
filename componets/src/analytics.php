<?php
include '../../db/db.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: admin.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - File Upload</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include "../src/sideNav.php"; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include "../src/header.php"; ?>
        </div>


    </div>
</body>

</html>