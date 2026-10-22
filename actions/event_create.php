<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    redirect('/pages/events.php'); // alleen via formulier
csrfCheck();

$naam = trim($_POST['naam'] ?? '');
$groepen = array_values(array_filter(array_map('trim', explode("\n", $_POST['groepen'] ?? '')))); // één groep per regel, lege regels weg

if ($naam === '' || mb_strlen($naam) > 100 || !$groepen) { // validatie
    $_SESSION['flash'] = 'Vul een eventnaam en minimaal één groep in.';
    redirect('/pages/events.php');
}

try {
    $pdo->beginTransaction(); // alles lukt of niets wordt opgeslagen
    $me = (int) $_SESSION['account_id'];

    $st = $pdo->prepare('INSERT INTO Event (Organizer, Event_name, Lid_Code, Admin_Code) VALUES (?, ?, ?, ?)');
    $st->execute([$me, $naam, generateCode(), generateCode()]); // twee verschillende join-codes
    $eventId = (int) $pdo->lastInsertId(); // id van het nieuwe event

    $st = $pdo->prepare('INSERT INTO Groep (Event_id, GroepNaam) VALUES (?, ?)');
    foreach ($groepen as $g) {
        $st->execute([$eventId, mb_substr($g, 0, 100)]); // groepsnaam maximaal 100 tekens
    }

    $pdo->prepare('INSERT INTO AdminGroep (Event_id, Account_id) VALUES (?, ?)')
        ->execute([$eventId, $me]); // organisator is ook admin

    $pdo->commit(); // definitief opslaan
    setEventRole($pdo, $me, $eventId); // rol meteen in de sessie
    redirect('/pages/dashboard.php');
} catch (PDOException $ex) {
    if ($pdo->inTransaction())
        $pdo->rollBack(); // bij fout alles terugdraaien
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}