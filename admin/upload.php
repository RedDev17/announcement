<?php
include __DIR__ . '/../db/db.php';
include __DIR__ . '/../db/storage.php';
require_once __DIR__ . '/../db/auth.php';

$authUser = requireAdmin();
$username = $authUser['username'];

// If accessed via GET (e.g., direct URL), redirect to dashboard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit();
}

if (isset($_POST['submit'])) {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
        header('Location: dashboard.php?err=' . urlencode('Please select a file to upload.'));
        exit();
    }

    $original_name = basename($_FILES['image']['name']);
    $tempname = $_FILES['image']['tmp_name'];

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($tempname);

    if (!in_array($file_type, $allowed_types, true)) {
        header('Location: dashboard.php?err=' . urlencode('Only JPG, PNG, GIF, WEBP files are allowed.'));
        exit();
    }

    // Generate unique filename to avoid collisions
    $ext = pathinfo($original_name, PATHINFO_EXTENSION);
    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
    $file_name = time() . '_' . $safeName . '.' . $ext;

    $storage = getStorage();

    if ($storage->upload('images', $file_name, $tempname, $file_type)) {
        $pdo = getDB();

        // Delete the old image to free space
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

        header('Location: dashboard.php?ok=1');
        exit();
    }

    header('Location: dashboard.php?err=' . urlencode('Upload failed: ' . $storage->getLastError()));
    exit();
}
?>