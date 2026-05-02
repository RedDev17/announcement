<?php
session_start();
include __DIR__ . '/../db/db.php';
include __DIR__ . '/../db/storage.php';

if (!isset($_SESSION['username'])) {
    header("Location: admin.php");
    exit();
}

if (isset($_POST['submit'])) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
        echo "<script>alert('Please select a file to upload'); window.location.href='dashboard.php';</script>";
        exit();
    }

    $original_name = basename($_FILES['image']['name']);
    $tempname = $_FILES['image']['tmp_name'];

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($tempname);

    if (!in_array($file_type, $allowed_types)) {
        echo "<script>alert('Only JPG, PNG, GIF, WEBP files are allowed'); window.location.href='dashboard.php';</script>";
        exit();
    }

    // Generate unique filename to avoid collisions
    $ext = pathinfo($original_name, PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
    $file_name = time() . '_' . $safeName . '.' . $ext;

    $storage = supabaseStorage();

    // Upload to Supabase Storage 'images' bucket
    if ($storage->upload('images', $file_name, $tempname, $file_type)) {
        $pdo = getDB();

        // Delete the old image from Supabase
        $old = $pdo->query("SELECT file FROM image LIMIT 1");
        if ($old && $old->rowCount() > 0) {
            $old_row = $old->fetch();
            if (!empty($old_row['file']) && $old_row['file'] !== $file_name) {
                $storage->delete('images', $old_row['file']);
            }
        }

        $pdo->query("DELETE FROM image");

        $insert = $pdo->prepare("INSERT INTO image (file, uploaded_at) VALUES (?, NOW())");
        $insert->execute([$file_name]);

        echo "<script>alert('Image uploaded successfully'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Failed to upload file to Supabase Storage. Check your SUPABASE_URL and SUPABASE_SERVICE_KEY.'); window.location.href='dashboard.php';</script>";
    }
}
?>