<?php
$me = (int) $_SESSION['account_id'];
$eventId = (int) $_SESSION['event_id'];
$groepId = (int) ($_SESSION['groep_id'] ?? 0); // eigen groep

$st = $pdo->prepare(
    'SELECT p.Partij_id, p.Datum, p.Tafel, p.Status, a.Naam AS tegenstander
     FROM Partij p
     JOIN Groep g ON g.Groep_id = p.Groep_id
     JOIN Account a ON a.Account_id = IF(p.Lid1_id = ?, p.Lid2_id, p.Lid1_id) -- de andere speler is de tegenstander
     WHERE g.Event_id = ? AND (p.Lid1_id = ? OR p.Lid2_id = ?) -- ik ben speler 1 of 2
     ORDER BY p.Datum, p.Geplande_Tijd' // gesorteerd op datum (FE3)
);
$st->execute([$me, $eventId, $me, $me]);
$partijen = $st->fetchAll();

$st = $pdo->prepare(
    'SELECT a.Naam, s.Gespeeld, s.Winst, s.Gelijkspel, s.Verlies, s.Punten
     FROM Stand s JOIN Account a ON a.Account_id = s.Account_id
     WHERE s.Groep_id = ?
     ORDER BY s.Punten DESC, a.Naam' // hoogste punten bovenaan (FE4)
);
$st->execute([$groepId]);
$stand = $st->fetchAll();
?>
<h1>Mijn partijen</h1>
<?php if (!$partijen): ?>
    <p>Geen partij gevonden</p>
<?php else: ?>
    <table>
        <tr>
            <th>Datum</th>
            <th>Tegenstander</th>
            <th>Tafel</th>
            <th>Status</th>
            <th></th>
        </tr>
        <?php foreach ($partijen as $p): ?>
            <tr>
                <td>
                    <?= e($p['Datum']) ?>
                </td>
                <td>
                    <?= e($p['tegenstander']) ?>
                </td>
                <td>
                    <?= e($p['Tafel'] ?? '-') ?>
                </td>
                <td>
                    <?= statusBadge($p['Status']) ?>
                </td>
                <td><a href="partij.php?id=<?= (int) $p['Partij_id'] ?>">Details</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Stand van mijn groep</h2>
<table>
    <tr>
        <th>Naam</th>
        <th>Gespeeld</th>
        <th>Winst</th>
        <th>Gelijkspel</th>
        <th>Verlies</th>
        <th>Punten</th>
    </tr>
    <?php foreach ($stand as $s): ?>
        <tr>
            <td>
                <?= e($s['Naam']) ?>
            </td>
            <td>
                <?= (int) $s['Gespeeld'] ?>
            </td>
            <td>
                <?= (int) $s['Winst'] ?>
            </td>
            <td>
                <?= (int) $s['Gelijkspel'] ?>
            </td>
            <td>
                <?= (int) $s['Verlies'] ?>
            </td>
            <td>
                <?= e(number_format((float) $s['Punten'], 1, ',', '')) ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>