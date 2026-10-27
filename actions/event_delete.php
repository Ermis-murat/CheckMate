<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    redirect('/pages/events.php'); // alleen via formulier
csrfCheck(); // CSRF-token controleren

$eventId = (int) ($_POST['event_id'] ?? 0);
$me = (int) $_SESSION['account_id'];

try {
    $st = $pdo->prepare('SELECT 1 FROM Event WHERE Event_id = ? AND Organizer = ?'); // alleen de organisator mag verwijderen
    $st->execute([$eventId, $me]);
    if (!$st->fetchColumn())
        forbidden(); // geen organisator: 403

    $pdo->beginTransaction(); // alles of niets
    $groepen = 'SELECT Groep_id FROM Groep WHERE Event_id = ?'; // groepen van dit event
    foreach ([
        "DELETE FROM Partij   WHERE Groep_id IN ($groepen)", // eerst partijen
        "DELETE FROM Stand    WHERE Groep_id IN ($groepen)", // dan standen
        "DELETE FROM LidGroep WHERE Groep_id IN ($groepen)", // dan leden
    ] as $sql) {
        $pdo->prepare($sql)->execute([$eventId]);
    }
    foreach ([
        'DELETE FROM Groep      WHERE Event_id = ?', // dan de groepen
        'DELETE FROM AdminGroep WHERE Event_id = ?', // dan de admins
        'DELETE FROM Event      WHERE Event_id = ?', // als laatste het event zelf
    ] as $sql) {
        $pdo->prepare($sql)->execute([$eventId]);
    }
    $pdo->commit(); // definitief verwijderen

    if (($_SESSION['event_id'] ?? 0) === $eventId) { // was dit het actieve event?
        unset($_SESSION['event_id'], $_SESSION['rol'], $_SESSION['groep_id']); // sessie opschonen
    }
    $_SESSION['flash'] = 'Event verwijderd.';
    redirect('/pages/events.php');
} catch (PDOException $ex) {
    if ($pdo->inTransaction())
        $pdo->rollBack(); // bij fout niets verwijderen
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}