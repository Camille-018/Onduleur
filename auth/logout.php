<?php
// logout.php: destroy the session (session must be started on every web page to check if the user is connected)
session_start();
session_destroy();
header("Location: login.php");
exit;
