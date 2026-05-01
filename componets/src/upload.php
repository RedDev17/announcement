<?php
session_start();
include '../../db/db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['submit'])) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
        echo "<script>alert('Please select a file to upload'); window.location.href='dashboard.php';</script>";
        exit();
    }

    $file_name = basename($_FILES['image']['name']);
    $tempname = $_FILES['image']['tmp_name'];
    $folder = 'uploads/' . $file_name;

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($tempname);

    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Only JPG, PNG, GIF, WEBP files are allowed'); window.location.href='dashboard.php';</script>";
        exit();
    }

    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    if (move_uploaded_file($tempname, $folder)) {
        $pdo = getDB();

        $old = $pdo->query("SELECT file FROM image LIMIT 1");
        if ($old && $old->rowCount() > 0) {
            $old_row = $old->fetch();
            $old_file = 'uploads/' . $old_row['file'];
            if (file_exists($old_file) && $old_file !== $folder) {
                unlink($old_file);
            }
        }

        $pdo->query("DELETE FROM image");

        $insert = $pdo->prepare("INSERT INTO image (file, uploaded_at) VALUES (?, NOW())");
        $insert->execute([$file_name]);

        echo "<script>alert('Image uploaded successfully'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to upload file'); window.location.href='dashboard.php';</script>";
    }
}
?>