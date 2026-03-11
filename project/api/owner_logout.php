<?php
if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['owner_id'], $_SESSION['owner_nama']);
header('Location: ../login.php');
exit;