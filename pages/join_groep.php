<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$eventId = (int) ($_SESSION['pending_join'] ?? 0); // event van de ingevoerde code
if (!$eventId)
    redirect('/pages/events.php'); // zonder geldige code geen toegang
$me = (int) $_SESSION['account_id'];

try {
    $st = $pdo->prepare('SELECT Groep_id, GroepNaam FROM Groep WHERE Event_id = ? ORDER BY GroepNaam');
    $st->execute([$eventId]);
    $groepen = $st->fetchAll(); // groepen van dit event

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrfCheck();
        $gid = (int) ($_POST['groep_id'] ?? 0);
        $ids = array_column($groepen, 'Groep_id');
        if (in_array($gid, array_map('intval', $ids), true)) { // groep moet bij dit event horen
            $pdo->beginTransaction();
            $pdo->prepare('INSERT IGNORE INTO LidGroep (Groep_id, Account_id) VALUES (?, ?)')->execute([$gid, $me]); // lid toevoegen aan groep
            $pdo->prepare('INSERT IGNORE INTO Stand (Account_id, Groep_id) VALUES (?, ?)')->execute([$me, $gid]); // lege stand-rij aanmaken
            $pdo->commit();
            unset($_SESSION['pending_join']); // niet meer nodig
            setEventRole($pdo, $me, $eventId);
            redirect('/pages/dashboard.php');
        }
    }
} catch (PDOException $ex) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    error_log($ex->getMessage());
    include __DIR__ . '/error.php';
    exit;
}

$pageTitle = 'Kies groep';
include __DIR__ . '/../includes/header.php';
?>
<main class="box">
    <h1>Kies je groep</h1>
    <?php if (!$groepen): ?>
        <p class="error">Dit event heeft nog geen groepen.</p>
    <?php else: ?>
        <form method="post">
            <?= csrfField() ?>
            <label>Groep
                <select name="groep_id">
                    <?php foreach ($groepen as $g): ?>
                        <option value="<?= (int) $g['Groep_id'] ?>">
                            <?= e($g['GroepNaam']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button>Bevestigen</button>
        </form>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>