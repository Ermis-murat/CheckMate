<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/partij_helpers.php';
requireAdmin(); // rolcheck eerst, vóór alles
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    forbidden();
csrfCheck();

$id = (int) ($_POST['partij_id'] ?? 0);
$status = $_POST['status'] ?? '';
$toegestaan = ['Gepland', 'Bezig', 'Afgelopen']; // whitelist (FE5)

if (!in_array($status, $toegestaan, true)) { // gemanipuleerde waarde
    backToPartij($id, 'Ongeldige status.', true);
}

try {
    $p = loadPartij($pdo, $id, (int) $_SESSION['event_id']); // partij moet bij dit event horen
    if (!$p)
        backToPartij($id, 'Partij niet gevonden.', true);

    if ($status === 'Bezig' && $p['Werkelijke_Tijd'] === null) { // eerste keer Bezig
        $pdo->prepare('UPDATE Partij SET Status = ?, Werkelijke_Tijd = ? WHERE Partij_id = ?')
            ->execute([$status, date('Y-m-d H:i:s'), $id]); // werkelijke starttijd vastleggen
    } else {
        $pdo->prepare('UPDATE Partij SET Status = ? WHERE Partij_id = ?')->execute([$status, $id]);
    }
    backToPartij($id, 'Status bijgewerkt.');
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    include __DIR__ . '/../pages/error.php';
    exit;
}