<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Auth.php';

$auth = new Auth();
$auth->logout();

// Redirect to login
header('Location: index.php');
exit;
?>
