<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: http://localhost/onduleur-dashboard/auth/login.php");
    exit;
}
