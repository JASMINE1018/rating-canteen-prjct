<?php
require_once '../seller_auth.php';
unset($_SESSION['seller_id'], $_SESSION['seller_nama']);
header('Location: ../seller_login.php');
exit;