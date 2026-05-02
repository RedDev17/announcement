<?php
require_once './db/db.php';
require_once './db/storage.php';
require_once './functions.php';

$storage = supabaseStorage();

$view = 'not_found';
$folder = null;
$files = [];
$module = null;
$pageTitle = 'Module';

// View: folder contents
if (isset($_GET['folder'])) {
    $folderId = intval($_GET['folder']);
    $folder = getFolderById($folderId);
    if ($folder) {
        $view = 'folder';
        $files = getModulesByFolder($folderId);
        $pageTitle = $folder['name'];
    }
}

// View: single PDF
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $module = getModuleById($id);
    if ($module) {
        $view = 'pdf';
        $pageTitle = $module['title'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css?v=<?php echo time(); ?>">
    <style>
        .viewer-container { max-width: 1000px; margin: 0 auto; padding: 24px; }
        .nav-bar {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .nav-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: #60a5fa; text-decoration: none; font-size: 14px;
            padding: 8px 16px;
            background-color: #1e293b; border-radius: 8px; border: 1px solid #334155;
            transition: background-color 0.2s;
        }
        .nav-link:hover { background-color: #334155; }
        .page-title {
            font-size: 22px; font-weight: 700; color: #f1f5f9;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
        .page-title i { color: #f59e0b; }

        /* Folder file list */
        .file-list { display: flex; flex-direction: column; gap: 10px; }
        .file-card {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 20px;
            background-color: #1e293b; border-radius: 10px;
            border: 1px solid #334155;
            text-decoration: none; color: #e2e8f0;
            transition: border-color 0.2s, background-color 0.2s;
        }
        .file-card:hover { border-color: #3b82f6; background-color: #172033; }
        .file-card-icon {
            width: 44px; height: 44px;
            background-color: rgba(239, 68, 68, 0.1);
            border-radius: 10px; display: flex;
            align-items: center; justify-content: center;
            color: #ef4444; font-size: 20px; flex-shrink: 0;
        }
        .file-card-info { flex: 1; min-width: 0; }
        .file-card-title { font-weight: 600; font-size: 15px; display: block; }
        .file-card-desc { font-size: 12px; color: #64748b; margin-top: 2px; display: block; }
        .file-card-arrow { color: #475569; font-size: 14px; }

        /* PDF viewer */
        .pdf-card {
            background-color: #1e293b; border-radius: 12px;
            border: 1px solid #334155; overflow: hidden;
        }
        .pdf-header { padding: 20px 24px; border-bottom: 1px solid #334155; }
        .pdf-header h2 { font-size: 20px; color: #f1f5f9; margin-bottom: 6px; }
        .pdf-header p { color: #64748b; font-size: 14px; line-height: 1.5; }
        .pdf-meta {
            display: flex; gap: 16px; margin-top: 10px;
            font-size: 13px; color: #94a3b8; flex-wrap: wrap;
        }
        .pdf-meta span { display: flex; align-items: center; gap: 6px; }
        .pdf-actions { display: flex; gap: 10px; margin-top: 14px; }
        .btn-download { background-color: #1e293b; color: #e2e8f0; }
        .btn-download:hover { background-color: #334155; }
        .pdf-viewer-wrap {
            position: relative; width: 100%; height: 0;
            padding-bottom: 120%; /* tall aspect ratio for PDF */
            overflow: hidden;
        }
        .pdf-viewer {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            border: none; display: block;
        }
        @media (min-width: 641px) {
            .pdf-viewer-wrap { padding-bottom: 0; height: 80vh; }
            .pdf-viewer { position: static; }
        }

        /* Empty / not found */
        .empty-msg { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-msg i { font-size: 40px; margin-bottom: 12px; opacity: 0.4; display: block; }
        .empty-msg h3 { color: #94a3b8; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="viewer-container">

        <?php if ($view === 'folder'): ?>
            <!-- ===== FOLDER VIEW: list PDFs ===== -->
            <div class="nav-bar">
                <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Board</a>
            </div>
            <div class="page-title"><i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($folder['name']); ?></div>

            <?php if (empty($files)): ?>
                <div class="empty-msg">
                    <i class="fas fa-file-circle-xmark"></i>
                    <h3>No Files Yet</h3>
                    <p>This folder is empty.</p>
                </div>
            <?php else: ?>
                <div class="file-list">
                    <?php foreach ($files as $file): ?>
                        <a href="module.php?id=<?php echo $file['id']; ?>" class="file-card">
                            <div class="file-card-icon"><i class="fas fa-file-pdf"></i></div>
                            <div class="file-card-info">
                                <span class="file-card-title"><?php echo htmlspecialchars($file['title']); ?></span>
                                <?php if (!empty($file['description'])): ?>
                                    <span class="file-card-desc"><?php echo htmlspecialchars($file['description']); ?></span>
                                <?php endif; ?>
                                <span class="file-card-desc"><?php echo date('M j, Y', strtotime($file['uploaded_at'])); ?></span>
                            </div>
                            <i class="fas fa-chevron-right file-card-arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($view === 'pdf'): ?>
            <!-- ===== PDF VIEW: native browser viewer + download ===== -->
            <?php
                $pdfLocalPath = $storage->publicUrl('modules', $module['file_name']);
            ?>
            <div class="nav-bar">
                <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Board</a>
                <?php if (!empty($module['folder_id'])): ?>
                    <a href="module.php?folder=<?php echo $module['folder_id']; ?>" class="nav-link"><i class="fas fa-folder"></i> <?php echo htmlspecialchars($module['folder_name'] ?? 'Folder'); ?></a>
                <?php endif; ?>
            </div>

            <div class="pdf-card">
                <div class="pdf-header">
                    <h2><i class="fas fa-file-pdf" style="color:#ef4444; margin-right:8px;"></i><?php echo htmlspecialchars($module['title']); ?></h2>
                    <?php if (!empty($module['description'])): ?>
                        <p><?php echo htmlspecialchars($module['description']); ?></p>
                    <?php endif; ?>
                    <div class="pdf-meta">
                        <span><i class="fas fa-file"></i> <?php echo htmlspecialchars($module['file_name']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($module['uploaded_at'])); ?></span>
                        <?php if (!empty($module['folder_name'])): ?>
                            <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($module['folder_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="pdf-actions">
                        <a href="<?php echo htmlspecialchars($pdfLocalPath); ?>" download class="btn-action btn-download">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                    </div>
                </div>
                <div class="pdf-viewer-wrap">
                    <iframe class="pdf-viewer" src="<?php echo htmlspecialchars($pdfLocalPath); ?>"></iframe>
                </div>
            </div>

        <?php else: ?>
            <!-- ===== NOT FOUND ===== -->
            <div class="nav-bar">
                <a href="index.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to Board</a>
            </div>
            <div class="empty-msg">
                <i class="fas fa-file-circle-xmark"></i>
                <h3>Not Found</h3>
                <p>The requested folder or file could not be found.</p>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>