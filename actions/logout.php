<?php
require_once __DIR__ . '/../includes/auth.php';
$_SESSION = []; // alle sessiegegevens leegmaken
session_destroy(); // sessie beëindigen (FE12)
redirect('/pages/login.php'); // terug naar inloggen