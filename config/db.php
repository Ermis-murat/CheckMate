<?php
// config/db.php
date_default_timezone_set('Europe/Amsterdam');// alle tijden in Nederlandse tijd

define('BASE_URL', '/GitHub/CheckMate');// pad van het project, gebruikt in alle links (haal de /GitHub weg als het niet nodig is)
define('APP_DEBUG', true); // true = mails in logboek; false in productie

$dsn = 'mysql:host=localhost;dbname=checkmate;charset=utf8mb4';

try {
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,// fouten als exception, zodat we ze kunnen opvangen
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,// resultaten als associatieve array
        PDO::ATTR_EMULATE_PREPARES => false,// echte prepared statements (tegen SQL-injectie)
    ]);
} catch (PDOException $e) {
    error_log($e->getMessage());// echte fout alleen in het serverlog
    http_response_code(500);// serverfout
    include __DIR__ . '/../pages/error.php';// neutrale foutpagina, geen technische details (FE1)
    exit;
}

