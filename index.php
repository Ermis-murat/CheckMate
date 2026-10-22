<?php
// index.php
require_once __DIR__ . '/config/db.php';
header('Location: ' . BASE_URL . '/pages/login.php');
exit;