<?php
require_once '../auth.php';
session_destroy();
header('Location: ../index.php');
exit;