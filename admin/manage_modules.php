<?php
include __DIR__ . '/../db/db.php';
include __DIR__ . '/../db/storage.php';
require_once __DIR__ . '/../db/auth.php';
require_once __DIR__ . '/../functions.php';

$authUser = requireAdmin();
$username = $authUser['username'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- FOLDER ACTIONS ----
    if (isset($_POST['add_folder'])) {
        $name = trim($_POST['folder_name']);
        if (!empty($name)) {
            addFolder($name);
            $msg = 'Folder created.';
        }
        header("Location: manage_modules.php");
        exit();
    }

    if (isset($_POST['rename_folder'])) {
        $id = intval($_POST['folder_id']);
        $name = trim($_POST['folder_name']);
        if (!empty($name)) {
            renameFolder($id, $name);
        }
        header("Location: manage_modules.php");
        exit();
    }

    if (isset($_POST['delete_folder'])) {
        $id = intval($_POST['folder_id']);
        deleteFolder($id);
        header("Location: manage_modules.php");
        exit();
    }

    // ---- FILE ACTIONS ----
    if (isset($_POST['add_module'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $folderId = intval($_POST['folder_id']);

        if (!empty($title) && $folderId > 0 && isset($_FILES['module_file']) && $_FILES['module_file']['error'] === UPLOAD_ERR_OK) {
            $originalName = basename($_FILES['module_file']['name']);
            $tempName = $_FILES['module_file']['tmp_name'];

            $allowedTypes = ['application/pdf'];
            $fileType = mime_content_type($tempName);

            if (!in_array($fileType, $allowedTypes)) {
                echo "<script>alert('Only PDF files are allowed'); window.location.href='manage_modules.php';</script>";
                exit();
            }

            // Generate unique safe filename
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $uniqueName = time() . '_' . $safeName . '.pdf';

            $storage = supabaseStorage();
            if ($storage->upload('modules', $uniqueName, $tempName, 'application/pdf')) {
                addModule($title, $uniqueName, $description, $folderId);
                header("Location: manage_modules.php");
                exit();
            } else {
                echo "<script>alert('Failed to upload PDF to Supabase Storage. Check your env config.'); window.location.href='manage_modules.php';</script>";
                exit();
            }
        }
    }

    if (isset($_POST['update_module'])) {
        $id = intval($_POST['module_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        if (!empty($title)) {
            updateModule($id, $title, $description);
        }
        header("Location: manage_modules.php");
        exit();
    }

    if (isset($_POST['delete_module'])) {
        $id = intval($_POST['module_id']);
        deleteModule($id);
        header("Location: manage_modules.php");
        exit();
    }
}

$folders = getFolders();
$modules = getModule();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include "sideNav.php"; ?>
        <div class="main-content">
            <?php include "header.php"; ?>

            <!-- ===== FOLDERS SECTION ===== -->
            <div class="upload-card">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-folder-plus"></i></div>
                    <h2 class="card-title">Module Folders</h2>
                    <p class="card-description">Create folders to organize your PDF modules.</p>
                </div>
                <form method="POST" class="upload-form" style="margin-bottom:0">
                    <div class="form-row">
                        <div class="form-group" style="flex:1">
                            <label>Folder Name *</label>
                            <input type="text" name="folder_name" placeholder="Enter folder name" required>
                        </div>
                        <div class="form-group" style="display:flex; align-items:flex-end">
                            <button type="submit" name="add_folder" class="submit-button">
                                <i class="fas fa-plus"></i> <span>Create Folder</span>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="table-container" style="margin-top:16px">
                    <?php if (empty($folders)): ?>
                        <p class="empty-msg">No folders created yet. Create one above first.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Folder Name</th>
                                    <th>Files</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($folders as $folder): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-folder" style="color:#f59e0b; margin-right:6px"></i>
                                            <strong><?php echo htmlspecialchars($folder['name']); ?></strong>
                                        </td>
                                        <td><?php echo $folder['file_count']; ?> PDF(s)</td>
                                        <td><?php echo date('M j, Y', strtotime($folder['created_at'])); ?></td>
                                        <td style="display:flex; gap:6px; align-items:center;">
                                            <form method="POST" style="display:inline-flex; gap:4px; align-items:center;">
                                                <input type="hidden" name="folder_id" value="<?php echo $folder['id']; ?>">
                                                <input type="text" name="folder_name" value="<?php echo htmlspecialchars($folder['name']); ?>" style="width:120px; padding:4px 8px; background:#1e293b; border:1px solid #475569; color:#e2e8f0; border-radius:4px; font-size:13px;">
                                                <button type="submit" name="rename_folder" class="submit-button" style="padding:6px 10px; font-size:12px;">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this folder and ALL its files?')">
                                                <input type="hidden" name="folder_id" value="<?php echo $folder['id']; ?>">
                                                <button type="submit" name="delete_folder" class="btn-delete">
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

            <!-- ===== UPLOAD PDF SECTION ===== -->
            <div class="upload-card" style="margin-top: 24px;">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-file-upload"></i></div>
                    <h2 class="card-title">Upload PDF to Folder</h2>
                    <p class="card-description">Select a folder and upload a PDF file.</p>
                </div>
                <?php if (empty($folders)): ?>
                    <p class="empty-msg">Create a folder first before uploading files.</p>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <div class="form-row">
                            <div class="form-group" style="flex:1">
                                <label>Assign to Folder *</label>
                                <select name="folder_id" required style="width:100%; padding:10px 12px; background:#0f172a; border:1px solid #475569; color:#e2e8f0; border-radius:8px; font-size:14px;">
                                    <option value="">-- Select Folder --</option>
                                    <?php foreach ($folders as $folder): ?>
                                        <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex:1">
                                <label>PDF Title *</label>
                                <input type="text" name="title" placeholder="Enter PDF title" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" placeholder="Enter description (optional)" rows="2"></textarea>
                        </div>
                        <div class="file-input-container">
                            <div class="file-input-wrapper">
                                <input type="file" name="module_file" id="moduleFileInput" accept=".pdf" required>
                                <div class="browse-button">
                                    <i class="fas fa-folder-open"></i>
                                    <span>Choose PDF File</span>
                                </div>
                            </div>
                            <p class="file-types">PDF files only (Max 10MB)</p>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="add_module" class="submit-button">
                                <i class="fas fa-upload"></i> <span>Upload PDF</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- ===== ALL FILES TABLE ===== -->
            <div class="upload-card" style="margin-top: 24px;">
                <div class="card-header">
                    <h2 class="card-title">All PDF Files (<?php echo count($modules); ?>)</h2>
                </div>
                <div class="table-container">
                    <?php if (empty($modules)): ?>
                        <p class="empty-msg">No PDF files uploaded yet.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Folder</th>
                                    <th>File</th>
                                    <th>Uploaded</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $module): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($module['title']); ?></strong>
                                            <?php if (!empty($module['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($module['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="color:#f59e0b"><i class="fas fa-folder"></i></span>
                                            <?php echo htmlspecialchars($module['folder_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td>
                                            <span class="file-badge">
                                                <i class="fas fa-file-pdf" style="color:#ef4444"></i>
                                                <?php echo htmlspecialchars($module['file_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($module['uploaded_at'])); ?></td>
                                        <td style="display:flex; gap:6px; align-items:center;">
                                            <form method="POST" style="display:inline-flex; gap:4px; align-items:center;">
                                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                                <input type="text" name="title" value="<?php echo htmlspecialchars($module['title']); ?>" style="width:100px; padding:4px 8px; background:#1e293b; border:1px solid #475569; color:#e2e8f0; border-radius:4px; font-size:13px;">
                                                <input type="hidden" name="description" value="<?php echo htmlspecialchars($module['description'] ?? ''); ?>">
                                                <button type="submit" name="update_module" class="submit-button" style="padding:6px 10px; font-size:12px;">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this PDF file?')">
                                                <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                                <button type="submit" name="delete_module" class="btn-delete">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('moduleFileInput');
            const browseButton = document.querySelector('.browse-button');
            if (fileInput && browseButton) {
                fileInput.addEventListener('change', function() {
                    if (fileInput.files.length > 0) {
                        browseButton.innerHTML = '<i class="fas fa-check"></i><span>' + fileInput.files[0].name + '</span>';
                        browseButton.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    }
                });
            }
        });
    </script>
</body>
</html>
