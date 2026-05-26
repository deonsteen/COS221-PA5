<?php
require_once __DIR__ . '/auth.php';
$_SESSION = [];
session_destroy();
header('Location: /COS221-PA5/login.php');
exit;