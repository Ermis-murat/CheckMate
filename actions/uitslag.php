<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/partij_helpers.php';

requireAdmin(); // FE13: rolcheck vóór database-acties, een lid komt hier nooit voorbij
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    forbidden();
csrfCheck();

$id = (int) ($_POST['partij_id'] ?? 0);
$winnaar = $_POST['winnaar'] ?? '';

if (!in_array($winnaar, ['lid1', 'lid2', 'gelijk'], true)) { // whitelist van uitslagen (FE7)
    backToPartij($id, 'Ongeldige uitslag.', true);
}

try {
    $pdo->beginTransaction(); // alles of niets

    $st = $pdo->prepare(
        'SELECT p.* FROM Partij p JOIN Groep g ON g.Groep_id = p.Groep_id
         WHERE p.Partij_id = ? AND g.Event_id = ? FOR UPDATE' // FOR UPDATE: rij vergrendelen tot we klaar zijn
    );
    $st->execute([$id, (int) $_SESSION['event_id']]);
    $p = $st->fetch();

    if (!$p) { // partij bestaat niet
        $pdo->rollBack();
        backToPartij($id, 'Partij niet gevonden.', true);
    }
    if ($p['Status'] !== 'Afgelopen') { // uitslag alleen bij een afgelopen partij (FE7)
        $pdo->rollBack();
        backToPartij($id, 'Uitslag kan alleen worden ingevoerd bij een afgelopen partij.', true);
    }

    if ($p['Winnaar'] !== null) { // correctie: er was al een uitslag
        applyResult($pdo, (int) $p['Groep_id'], (int) $p['Lid1_id'], (int) $p['Lid2_id'], $p['Winnaar'], -1); // oude bijdrage terugdraaien (FE9)
    }

    $pdo->prepare('UPDATE Partij SET Winnaar = ? WHERE Partij_id = ?')->execute([$winnaar, $id]); // nieuwe winnaar opslaan
    applyResult($pdo, (int) $p['Groep_id'], (int) $p['Lid1_id'], (int) $p['Lid2_id'], $winnaar, +1); // nieuwe bijdrage optellen

    $pdo->commit(); // definitief opslaan
    backToPartij($id, 'Uitslag opgeslagen, partij gesloten.');
} catch (PDOException $ex) {
    if ($pdo->inTransaction())
        $pdo->rollBack(); // bij fout alles terugdraaien
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}