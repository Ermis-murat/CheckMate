<?php
$eventId = (int) $_SESSION['event_id'];

$filterMap = ['alle' => null, 'bezig' => 'Bezig', 'gepland' => 'Gepland']; // whitelist van filters (FE10)
$filter = $_GET['filter'] ?? 'alle';
if (!is_string($filter) || !array_key_exists($filter, $filterMap)) { // onbekende waarde
    $filter = 'alle'; // terug naar standaardweergave
}

$sql = 'SELECT p.Partij_id, p.Datum, p.Tafel, p.Status,
               a1.Naam AS speler1, a2.Naam AS speler2, ar.Naam AS arbiter
        FROM Partij p
        JOIN Groep g ON g.Groep_id = p.Groep_id
        JOIN Account a1 ON a1.Account_id = p.Lid1_id
        JOIN Account a2 ON a2.Account_id = p.Lid2_id
        LEFT JOIN Account ar ON ar.Account_id = p.Arbiter_id
        WHERE g.Event_id = ?'; // alle partijen van dit event
$params = [$eventId];
if ($filterMap[$filter] !== null) { // filter actief
    $sql .= ' AND p.Status = ?';
    $params[] = $filterMap[$filter];
}
$sql .= " ORDER BY FIELD(p.Status, 'Bezig', 'Gepland', 'Afgelopen'), p.Geplande_Tijd"; // Bezig eerst, dan Gepland, dan Afgelopen (FE6)

$st = $pdo->prepare($sql);
$st->execute($params);
$partijen = $st->fetchAll();

$st = $pdo->prepare('SELECT Lid_Code, Admin_Code FROM Event WHERE Event_id = ?'); // join-codes tonen aan de admin
$st->execute([$eventId]);
$codes = $st->fetch();
?>
<h1>Alle partijen</h1>
<p>
    <a href="?filter=alle" class="<?= $filter === 'alle' ? 'active' : '' ?>">Toon Alle Partijen</a> |
    <a href="?filter=bezig" class="<?= $filter === 'bezig' ? 'active' : '' ?>">Nu aan de Tafel</a> |
    <a href="?filter=gepland" class="<?= $filter === 'gepland' ? 'active' : '' ?>">Nog Gepland</a> |
    <a href="partij_nieuw.php">+ Nieuwe partij</a>
</p>

<?php if (!$partijen): ?>
    <p>
        <?= $filter === 'alle' ? 'Nog geen partijen in de competitie' : 'Geen partijen gevonden voor dit filter' ?>
    </p>
<?php else: ?>
    <table>
        <tr>
            <th>Spelers</th>
            <th>Datum</th>
            <th>Tafel</th>
            <th>Status</th>
            <th>Arbiter</th>
            <th></th>
        </tr>
        <?php foreach ($partijen as $p): ?>
            <tr>
                <td>
                    <?= e($p['speler1']) ?> –
                    <?= e($p['speler2']) ?>
                </td>
                <td>
                    <?= e($p['Datum']) ?>
                </td>
                <td>
                    <?= e($p['Tafel'] ?? '-') ?>
                </td>
                <td>
                    <?= statusBadge($p['Status']) ?>
                </td>
                <td>
                    <?= e($p['arbiter'] ?? '-') ?>
                </td>
                <td><a href="partij.php?id=<?= (int) $p['Partij_id'] ?>">Details</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<h2>Join-codes</h2>
<p>Lid: <code><?= e($codes['Lid_Code']) ?></code> · Admin: <code><?= e($codes['Admin_Code']) ?></code></p>