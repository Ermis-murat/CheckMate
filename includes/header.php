<?php
require_once __DIR__ . '/helpers.php'; // statusBadge en generateCode

$eventNaam = null; // naam van het actieve event
if (!empty($_SESSION['event_id'])) { // is er een event gekozen?
    try {
        $st = $pdo->prepare('SELECT Event_name FROM Event WHERE Event_id = ?');
        $st->execute([$_SESSION['event_id']]);
        $eventNaam = $st->fetchColumn() ?: null; // geen resultaat wordt null
    } catch (PDOException $ex) {
        error_log($ex->getMessage()); // fout alleen loggen, pagina blijft werken
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle ?? 'CheckMate') ?></title> <!-- paginatitel, veilig getoond -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>

<body>
    <header class="nav">
        <div class="nav-left"><a href="<?= BASE_URL ?>/pages/dashboard.php">Mijn Partijen</a></div>
        <!-- links (FE12) -->
        <div class="nav-mid"><?= $eventNaam ? e($eventNaam) : 'Geen actieve competitie' ?></div>
        <!-- midden: eventnaam of melding -->
        <div class="nav-right"> <!-- rechts: gebruiker en uitloggen -->
            <?= isAdmin() ? 'Admin – ' : '' ?><a
                href="<?= BASE_URL ?>/pages/profiel.php"><?= e($_SESSION['naam'] ?? '') ?></a>
            · <a href="<?= BASE_URL ?>/pages/events.php">Events</a>
            · <a href="<?= BASE_URL ?>/actions/logout.php">Uitloggen</a>
        </div>
    </header>