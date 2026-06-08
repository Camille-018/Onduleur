<?php
// logout.php : détruit la session (la session est démarrée sur chaque page web pour vérifier que l'utilisateur est connecté)
session_start();
session_destroy();
header("Location: login.php");
exit;
