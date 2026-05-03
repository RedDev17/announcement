<?php
include __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../db/auth.php';

$authUser = requireAdmin();
$username = $authUser['username'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - File Upload</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include "sideNav.php"; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include "header.php"; ?>

            <!-- Upload Card -->
            <div class="upload-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h2 class="card-title">Upload Your Image</h2>
                    <p class="card-description">
                        Select an image file from your device to upload to the dashboard.
                        Supported formats: JPG, PNG, GIF, WEBP. Max size: 5MB.
                    </p>
                </div>

                <!-- Upload Form -->
                <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="image" id="fileInput" accept="image/*" required>
                            <div class="browse-button">
                                <i class="fas fa-folder-open"></i>
                                <span>Browse Files</span>
                            </div>
                        </div>
                        <p class="file-types">JPG, PNG, GIF, WEBP (Max 5MB)</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-button" name="submit">
                            <i class="fas fa-upload"></i>
                            <span>Upload Image</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Minimal script for visual feedback only
            document.addEventListener('DOMContentLoaded', function () {
                const fileInput = document.getElementById('fileInput');
                const browseButton = document.querySelector('.browse-button');

                // Visual feedback when file is selected
                fileInput.addEventListener('change', function () {
                    if (fileInput.files.length > 0) {
                        browseButton.innerHTML = '<i class="fas fa-check"></i><span>File Selected</span>';
                        browseButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    }
                });

                // Hover effects for navigation
                const navItems = document.querySelectorAll('.nav-item');
                navItems.forEach(item => {
                    item.addEventListener('click', function () {
                        navItems.forEach(nav => nav.classList.remove('active'));
                        this.classList.add('active');
                    });
                });
            });
        </script>
</body>

</html>