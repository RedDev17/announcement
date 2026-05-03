<?php
require_once __DIR__ . '/../db/auth.php';

logoutUser();
header("Location: admin.php");
exit();
?>
