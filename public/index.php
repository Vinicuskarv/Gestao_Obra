<?php
session_start();
require_once '../config/config.php';
require_once '../src/Database.php';
require_once '../src/Auth.php';

$db = new Database();
$auth = new Auth($db);

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}else{
    header('Location: login.php');
    exit();
}
?>