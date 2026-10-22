<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin(); // alleen admins

$eventId = (int) $_SESSION['event_id'];
$me = (int) $_SESSION['account_id']; // de admin wordt arbiter
$error = '';

try {
    $st = $pdo->prepare('SELECT Groep_id, GroepNaam FROM Groep WHERE Event_id = ? ORDER BY GroepNaam');
    $st->execute([$eventId]);
    $groepen = $st->fetchAll();

    $st = $pdo->prepare(
        'SELECT lg.Groep_id, a.Account_id, a.Naam
         FROM LidGroep lg
         JOIN Groep g ON g.Groep_id = lg.Groep_id
         JOIN Account a ON a.Account_id = lg.Account_id
         WHERE g.Event_id = ? ORDER BY a.Naam' // alle leden van dit event
    );
    $st->execute([$eventId]);
    $leden = $st->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrfCheck();
        $gid = (int) ($_POST['groep_id'] ?? 0);
        $l1 = (int) ($_POST['lid1'] ?? 0);
        $l2 = (int) ($_POST['lid2'] ?? 0);
        $tijd = str_replace('T', ' ', $_POST['tijd'] ?? ''); // datetime-local naar MySQL-formaat
        $dt = DateTime::createFromFormat('Y-m-d H:i', $tijd); // false bij ongeldige datum

        $inGroep = fn(int $acc) => (bool) array_filter(
            $leden,
            fn($l) => (int) $l['Groep_id'] === $gid && (int) $l['Account_id'] === $acc
        ); // zit dit account in de gekozen groep?
        $groepOk = in_array($gid, array_map('intval', array_column($groepen, 'Groep_id')), true); // groep hoort bij dit event

        if (!$groepOk || !$dt)
            $error = 'Ongeldige groep of datum/tijd.';
        elseif ($l1 === $l2)
            $error = 'Kies twee verschillende spelers.'; // niet tegen jezelf
        elseif (!$inGroep($l1) || !$inGroep($l2))
            $error = 'Beide spelers moeten in de gekozen groep zitten.';
        else {
            $pdo->prepare(
                'INSERT INTO Partij (Groep_id, Lid1_id, Lid2_id, Arbiter_id, Datum, Geplande_Tijd)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$gid, $l1, $l2, $me, $dt->format('Y-m-d'), $dt->format('Y-m-d H:i:s')]); // status wordt standaard Gepland
            redirect('/pages/dashboard.php');
        }
    }
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    include __DIR__ . '/error.php';
    exit;
}
?>
<main class="box">
    <h1>Nieuwe partij</h1>
    <?php if ($error): ?>
        <p class="error">
            <?= e($error) ?>
        </p>
    <?php endif; ?>
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
        <?php foreach (['lid1' => 'Speler 1', 'lid2' => 'Speler 2'] as $name => $label): ?>
            <label>
                <?= $label ?>
                <select name="<?= $name ?>">
                    <?php foreach ($leden as $l): ?>
                        <option value="<?= (int) $l['Account_id'] ?>">
                            <?= e($l['Naam']) ?> (groep
                            <?= (int) $l['Groep_id'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endforeach; ?>
        <label>Geplande tijd <input type="datetime-local" name="tijd" required></label>
        <button>Aanmaken</button>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>