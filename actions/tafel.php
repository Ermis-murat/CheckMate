<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/partij_helpers.php';
requireAdmin(); // alleen admins (FE2)
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    forbidden();
csrfCheck();

$id = (int) ($_POST['partij_id'] ?? 0);
$tafel = (int) ($_POST['tafel'] ?? 0);
$eventId = (int) $_SESSION['event_id'];

if ($tafel < 1 || $tafel > AANTAL_TAFELS) { // tafel buiten 1 t/m 10
    backToPartij($id, 'Ongeldige tafel.', true);
}

try {
    if (!loadPartij($pdo, $id, $eventId))
        backToPartij($id, 'Partij niet gevonden.', true);

    $st = $pdo->prepare(
        "SELECT 1 FROM Partij p JOIN Groep g ON g.Groep_id = p.Groep_id
         WHERE g.Event_id = ? AND p.Tafel = ? AND p.Status IN ('Gepland','Bezig') AND p.Partij_id <> ?
         LIMIT 1" // is de tafel al in gebruik bij een andere partij?
    );
    $st->execute([$eventId, (string) $tafel, $id]);
    if ($st->fetchColumn()) { // server controleert opnieuw, ook bij een handmatig aangepast verzoek
        backToPartij($id, 'Deze tafel is al in gebruik.', true);
    }

    $pdo->prepare('UPDATE Partij SET Tafel = ? WHERE Partij_id = ?')->execute([(string) $tafel, $id]); // tafel opslaan
    backToPartij($id, 'Tafel toegewezen.');
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}